<?php
/**
 * Payroll processing for PIN Platform.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_Payroll {

    /**
     * Initialize payroll hooks.
     */
    public static function init() {
        add_action( 'wp_ajax_pin_process_payroll', array( __CLASS__, 'ajax_process_payroll' ) );
        add_action( 'wp_ajax_pin_get_payroll_preview', array( __CLASS__, 'ajax_get_payroll_preview' ) );
    }

    /**
     * Create a payroll record.
     *
     * @param int    $employer_id Employer user ID.
     * @param string $pay_period  Pay period in YYYY-MM format.
     * @return int|WP_Error Payroll ID or error.
     */
    public static function create_payroll( $employer_id, $pay_period ) {
        global $wpdb;

        // Check for duplicate payroll in same period.
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}pin_payrolls WHERE employer_id = %d AND pay_period = %s AND status != 'cancelled'",
                $employer_id,
                $pay_period
            )
        );
        if ( $existing ) {
            return new WP_Error( 'duplicate_payroll', 'A payroll for this period already exists.' );
        }

        // Get active workers.
        $workers = PIN_Employer::get_workers( $employer_id );
        $active_workers = array();
        foreach ( $workers as $worker ) {
            $status = get_user_meta( $worker->ID, 'pin_worker_status', true );
            if ( 'active' === $status || empty( $status ) ) {
                $active_workers[] = $worker;
            }
        }

        if ( empty( $active_workers ) ) {
            return new WP_Error( 'no_workers', 'No active workers found.' );
        }

        $total_gross = 0;
        $total_tax   = 0;
        $total_net   = 0;

        foreach ( $active_workers as $worker ) {
            $salary = (float) get_user_meta( $worker->ID, 'pin_monthly_salary', true );
            $tax    = $salary * PIN_TAX_RATE;
            $net    = $salary - $tax;

            $total_gross += $salary;
            $total_tax   += $tax;
            $total_net   += $net;
        }

        $wpdb->insert(
            $wpdb->prefix . 'pin_payrolls',
            array(
                'employer_id'  => $employer_id,
                'pay_period'   => $pay_period,
                'total_gross'  => $total_gross,
                'total_tax'    => $total_tax,
                'total_net'    => $total_net,
                'worker_count' => count( $active_workers ),
                'status'       => 'pending',
                'created_at'   => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%f', '%f', '%f', '%d', '%s', '%s' )
        );

        $payroll_id = $wpdb->insert_id;

        // Create individual worker payment records.
        foreach ( $active_workers as $worker ) {
            $salary = (float) get_user_meta( $worker->ID, 'pin_monthly_salary', true );
            $tax    = $salary * PIN_TAX_RATE;
            $net    = $salary - $tax;

            $wpdb->insert(
                $wpdb->prefix . 'pin_worker_payments',
                array(
                    'payroll_id'     => $payroll_id,
                    'worker_id'      => $worker->ID,
                    'gross_salary'   => $salary,
                    'tax_deducted'   => $tax,
                    'net_salary'     => $net,
                    'payment_status' => 'pending',
                    'created_at'     => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%f', '%f', '%f', '%s', '%s' )
            );
        }

        return $payroll_id;
    }

    /**
     * Mark payroll as completed and add tax to pool.
     *
     * @param int $payroll_id Payroll ID.
     * @return bool
     */
    public static function complete_payroll( $payroll_id ) {
        global $wpdb;

        $payroll = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pin_payrolls WHERE id = %d",
                $payroll_id
            )
        );

        if ( ! $payroll || 'processing' !== $payroll->status ) {
            return false;
        }

        // Update payroll status.
        $wpdb->update(
            $wpdb->prefix . 'pin_payrolls',
            array(
                'status'       => 'completed',
                'processed_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $payroll_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        // Update worker payment statuses.
        $wpdb->update(
            $wpdb->prefix . 'pin_worker_payments',
            array( 'payment_status' => 'sent' ),
            array( 'payroll_id' => $payroll_id ),
            array( '%s' ),
            array( '%d' )
        );

        // Add tax to pool.
        $wpdb->insert(
            $wpdb->prefix . 'pin_tax_pool',
            array(
                'payroll_id' => $payroll_id,
                'amount'     => $payroll->total_tax,
                'distributed' => 0,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%f', '%d', '%s' )
        );

        return true;
    }

    /**
     * Get payroll details.
     *
     * @param int $payroll_id Payroll ID.
     * @return object|null
     */
    public static function get_payroll( $payroll_id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pin_payrolls WHERE id = %d",
                $payroll_id
            )
        );
    }

    /**
     * Get worker payments for a payroll.
     *
     * @param int $payroll_id Payroll ID.
     * @return array
     */
    public static function get_payroll_payments( $payroll_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT wp.*, u.display_name as worker_name
                 FROM {$wpdb->prefix}pin_worker_payments wp
                 JOIN {$wpdb->users} u ON wp.worker_id = u.ID
                 WHERE wp.payroll_id = %d
                 ORDER BY u.display_name ASC",
                $payroll_id
            )
        );
    }

    /**
     * AJAX: Get payroll preview data.
     */
    public static function ajax_get_payroll_preview() {
        check_ajax_referer( 'pin_ajax_nonce', 'nonce' );

        if ( ! PIN_Roles::current_user_has_role( 'pin_employer' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $employer_id = get_current_user_id();
        $workers     = PIN_Employer::get_workers( $employer_id );

        $preview = array();
        $total_gross = 0;
        $total_tax   = 0;
        $total_net   = 0;

        foreach ( $workers as $worker ) {
            $status = get_user_meta( $worker->ID, 'pin_worker_status', true );
            if ( 'inactive' === $status ) {
                continue;
            }

            $salary = (float) get_user_meta( $worker->ID, 'pin_monthly_salary', true );
            $tax    = $salary * PIN_TAX_RATE;
            $net    = $salary - $tax;

            $preview[] = array(
                'id'     => $worker->ID,
                'name'   => $worker->display_name,
                'gross'  => $salary,
                'tax'    => $tax,
                'net'    => $net,
            );

            $total_gross += $salary;
            $total_tax   += $tax;
            $total_net   += $net;
        }

        wp_send_json_success( array(
            'workers'     => $preview,
            'total_gross' => $total_gross,
            'total_tax'   => $total_tax,
            'total_net'   => $total_net,
            'officer_count' => PIN_Officer::get_officer_count(),
        ) );
    }

    /**
     * AJAX: Process payroll - creates WooCommerce order.
     */
    public static function ajax_process_payroll() {
        check_ajax_referer( 'pin_ajax_nonce', 'nonce' );

        if ( ! PIN_Roles::current_user_has_role( 'pin_employer' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $employer_id = get_current_user_id();
        $pay_period  = sanitize_text_field( wp_unslash( $_POST['pay_period'] ?? '' ) );

        if ( empty( $pay_period ) || ! preg_match( '/^\d{4}-\d{2}$/', $pay_period ) ) {
            wp_send_json_error( array( 'message' => 'Invalid pay period format. Use YYYY-MM.' ) );
        }

        $payroll_id = self::create_payroll( $employer_id, $pay_period );
        if ( is_wp_error( $payroll_id ) ) {
            wp_send_json_error( array( 'message' => $payroll_id->get_error_message() ) );
        }

        // Create WooCommerce order.
        $order_id = PIN_WooCommerce::create_payroll_order( $payroll_id );
        if ( is_wp_error( $order_id ) ) {
            wp_send_json_error( array( 'message' => $order_id->get_error_message() ) );
        }

        $payroll = self::get_payroll( $payroll_id );

        wp_send_json_success( array(
            'message'    => 'Payroll created. Please complete payment.',
            'payroll_id' => $payroll_id,
            'order_id'   => $order_id,
            'checkout_url' => wc_get_checkout_url(),
            'total_gross'  => $payroll->total_gross,
            'total_tax'    => $payroll->total_tax,
            'total_net'    => $payroll->total_net,
        ) );
    }
}
