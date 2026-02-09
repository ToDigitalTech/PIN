<?php
/**
 * Officer distribution processing for PIN Platform.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_Distribution {

    /**
     * Initialize distribution hooks.
     */
    public static function init() {
        add_action( 'wp_ajax_pin_trigger_distribution', array( __CLASS__, 'ajax_trigger_distribution' ) );
        // Monthly cron distribution.
        add_action( 'pin_monthly_distribution', array( __CLASS__, 'run_distribution' ) );
        if ( ! wp_next_scheduled( 'pin_monthly_distribution' ) ) {
            wp_schedule_event( time(), 'monthly', 'pin_monthly_distribution' );
        }
    }

    /**
     * Run distribution of undistributed tax pool to officers.
     *
     * @return array|WP_Error Distribution result or error.
     */
    public static function run_distribution() {
        global $wpdb;

        // Get undistributed tax pool entries.
        $pool_entries = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}pin_tax_pool WHERE distributed = 0"
        );

        if ( empty( $pool_entries ) ) {
            return new WP_Error( 'empty_pool', 'No undistributed funds in the tax pool.' );
        }

        $total_pool = 0;
        $payroll_ids = array();
        foreach ( $pool_entries as $entry ) {
            $total_pool += (float) $entry->amount;
            $payroll_ids[] = $entry->payroll_id;
        }

        // Get all officers (verified preferred, but include all registered for MVP).
        $officers = get_users( array( 'role' => 'pin_officer' ) );

        if ( empty( $officers ) ) {
            return new WP_Error( 'no_officers', 'No registered officers to distribute to.' );
        }

        $officer_count = count( $officers );
        $per_officer   = round( $total_pool / $officer_count, 2 );
        $distribution_date = current_time( 'Y-m-d' );
        $source_ids = wp_json_encode( $payroll_ids );

        // Create distribution records for each officer.
        foreach ( $officers as $officer ) {
            $wpdb->insert(
                $wpdb->prefix . 'pin_officer_distributions',
                array(
                    'officer_id'         => $officer->ID,
                    'amount'             => $per_officer,
                    'distribution_date'  => $distribution_date,
                    'source_payroll_ids' => $source_ids,
                    'payment_status'     => 'pending',
                    'created_at'         => current_time( 'mysql' ),
                ),
                array( '%d', '%f', '%s', '%s', '%s', '%s' )
            );
        }

        $distribution_id = $wpdb->insert_id;

        // Mark pool entries as distributed.
        foreach ( $pool_entries as $entry ) {
            $wpdb->update(
                $wpdb->prefix . 'pin_tax_pool',
                array(
                    'distributed'     => 1,
                    'distribution_id' => $distribution_id,
                ),
                array( 'id' => $entry->id ),
                array( '%d', '%d' ),
                array( '%d' )
            );
        }

        // Placeholder: Trigger bank transfers via Paystack/Flutterwave.
        self::process_bank_transfers( $officers, $per_officer );

        return array(
            'total_distributed' => $total_pool,
            'officer_count'     => $officer_count,
            'per_officer'       => $per_officer,
            'date'              => $distribution_date,
        );
    }

    /**
     * Placeholder for bank transfer processing.
     *
     * @param array $officers    Array of WP_User objects.
     * @param float $per_officer Amount per officer.
     */
    private static function process_bank_transfers( $officers, $per_officer ) {
        // TODO: Integrate with Paystack Transfer API or Flutterwave.
        // For each officer:
        //   1. Get bank details from user meta.
        //   2. Initiate transfer via payment gateway API.
        //   3. Update payment_status to 'sent' on success.
        //
        // Example Paystack Transfer API call:
        // POST https://api.paystack.co/transfer
        // {
        //     "source": "balance",
        //     "amount": $per_officer * 100, // Paystack uses kobo
        //     "recipient": $recipient_code,
        //     "reason": "PIN Platform Officer Distribution"
        // }
    }

    /**
     * Get current undistributed pool balance.
     *
     * @return float
     */
    public static function get_pool_balance() {
        global $wpdb;
        $result = $wpdb->get_var(
            "SELECT SUM(amount) FROM {$wpdb->prefix}pin_tax_pool WHERE distributed = 0"
        );
        return (float) $result;
    }

    /**
     * Get total amount ever distributed.
     *
     * @return float
     */
    public static function get_total_distributed() {
        global $wpdb;
        $result = $wpdb->get_var(
            "SELECT SUM(amount) FROM {$wpdb->prefix}pin_officer_distributions"
        );
        return (float) $result;
    }

    /**
     * Get recent distributions for the transparency dashboard.
     *
     * @param int $limit Number of records.
     * @return array
     */
    public static function get_recent_distributions( $limit = 10 ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT distribution_date, COUNT(*) as officer_count, SUM(amount) as total_amount
                 FROM {$wpdb->prefix}pin_officer_distributions
                 GROUP BY distribution_date
                 ORDER BY distribution_date DESC
                 LIMIT %d",
                $limit
            )
        );
    }

    /**
     * Get monthly distribution stats for charts.
     *
     * @param int $months Number of months to look back.
     * @return array
     */
    public static function get_monthly_stats( $months = 12 ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(distribution_date, '%%Y-%%m') as month,
                        COUNT(DISTINCT officer_id) as officer_count,
                        SUM(amount) as total_amount
                 FROM {$wpdb->prefix}pin_officer_distributions
                 GROUP BY DATE_FORMAT(distribution_date, '%%Y-%%m')
                 ORDER BY month DESC
                 LIMIT %d",
                $months
            )
        );
    }

    /**
     * Get transparency stats.
     *
     * @return array
     */
    public static function get_transparency_stats() {
        global $wpdb;

        $current_month = current_time( 'Y-m' );

        // Total taxes collected this month.
        $taxes_this_month = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_tax) FROM {$wpdb->prefix}pin_payrolls WHERE pay_period = %s AND status = 'completed'",
                $current_month
            )
        );

        // All-time totals.
        $total_taxes = (float) $wpdb->get_var(
            "SELECT SUM(total_tax) FROM {$wpdb->prefix}pin_payrolls WHERE status = 'completed'"
        );

        $total_distributed = self::get_total_distributed();
        $pool_balance      = self::get_pool_balance();
        $officer_count     = PIN_Officer::get_officer_count();

        // Employer and worker counts.
        $user_counts    = count_users();
        $employer_count = isset( $user_counts['avail_roles']['pin_employer'] ) ? $user_counts['avail_roles']['pin_employer'] : 0;
        $worker_count   = isset( $user_counts['avail_roles']['pin_worker'] ) ? $user_counts['avail_roles']['pin_worker'] : 0;

        // Officers paid this month.
        $officers_paid_month = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT officer_id) FROM {$wpdb->prefix}pin_officer_distributions
                 WHERE DATE_FORMAT(distribution_date, '%%Y-%%m') = %s",
                $current_month
            )
        );

        // Average payment per officer.
        $avg_payment = $officer_count > 0 && $total_distributed > 0
            ? $total_distributed / $officer_count
            : 0;

        return array(
            'taxes_this_month'    => $taxes_this_month,
            'total_taxes'         => $total_taxes,
            'total_distributed'   => $total_distributed,
            'pool_balance'        => $pool_balance,
            'officer_count'       => $officer_count,
            'employer_count'      => $employer_count,
            'worker_count'        => $worker_count,
            'officers_paid_month' => $officers_paid_month,
            'avg_payment'         => $avg_payment,
        );
    }

    /**
     * AJAX: Manually trigger distribution (admin only).
     */
    public static function ajax_trigger_distribution() {
        check_ajax_referer( 'pin_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $result = self::run_distribution();
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }
}
