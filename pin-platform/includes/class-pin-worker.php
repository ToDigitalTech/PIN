<?php
/**
 * Worker functionality for PIN Platform.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_Worker {

    /**
     * Initialize worker hooks.
     */
    public static function init() {
        add_action( 'wp_ajax_pin_worker_update_bank', array( __CLASS__, 'ajax_update_bank_details' ) );
    }

    /**
     * Register a new worker user (self-registration).
     *
     * @param array $data Registration data.
     * @return int|WP_Error User ID or error.
     */
    public static function register( $data ) {
        $username = sanitize_user( $data['username'] );
        $email    = sanitize_email( $data['email'] );
        $password = $data['password'];

        if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
            return new WP_Error( 'missing_fields', 'All fields are required.' );
        }

        if ( username_exists( $username ) ) {
            return new WP_Error( 'username_exists', 'This username is already taken.' );
        }

        if ( email_exists( $email ) ) {
            return new WP_Error( 'email_exists', 'This email is already registered.' );
        }

        $user_id = wp_create_user( $username, $password, $email );
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        $user = new WP_User( $user_id );
        $user->set_role( 'pin_worker' );

        update_user_meta( $user_id, 'first_name', sanitize_text_field( $data['first_name'] ) );
        update_user_meta( $user_id, 'last_name', sanitize_text_field( $data['last_name'] ) );
        update_user_meta( $user_id, 'pin_bank_name', sanitize_text_field( $data['bank_name'] ?? '' ) );
        update_user_meta( $user_id, 'pin_bank_account', sanitize_text_field( $data['bank_account'] ?? '' ) );
        update_user_meta( $user_id, 'pin_account_name', sanitize_text_field( $data['account_name'] ?? '' ) );
        update_user_meta( $user_id, 'pin_worker_status', 'active' );

        // Link to employer if code provided.
        if ( ! empty( $data['employer_code'] ) ) {
            $employer = get_user_by( 'login', sanitize_text_field( $data['employer_code'] ) );
            if ( $employer && in_array( 'pin_employer', (array) $employer->roles, true ) ) {
                update_user_meta( $user_id, 'pin_employer_id', $employer->ID );
            }
        }

        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => sanitize_text_field( $data['first_name'] ) . ' ' . sanitize_text_field( $data['last_name'] ),
        ) );

        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );

        return $user_id;
    }

    /**
     * Get salary breakdown for a worker.
     *
     * @param int $worker_id Worker user ID.
     * @return array
     */
    public static function get_salary_breakdown( $worker_id ) {
        $gross    = (float) get_user_meta( $worker_id, 'pin_monthly_salary', true );
        $tax_rate = PIN_TAX_RATE;
        $tax      = $gross * $tax_rate;
        $net      = $gross - $tax;

        return array(
            'gross'    => $gross,
            'tax_rate' => $tax_rate,
            'tax'      => $tax,
            'net'      => $net,
        );
    }

    /**
     * Get payment history for a worker.
     *
     * @param int $worker_id Worker user ID.
     * @return array
     */
    public static function get_payment_history( $worker_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT wp.*, p.pay_period, p.status as payroll_status
                 FROM {$wpdb->prefix}pin_worker_payments wp
                 JOIN {$wpdb->prefix}pin_payrolls p ON wp.payroll_id = p.id
                 WHERE wp.worker_id = %d
                 ORDER BY wp.created_at DESC",
                $worker_id
            )
        );
    }

    /**
     * Get the employer of a worker.
     *
     * @param int $worker_id Worker user ID.
     * @return WP_User|false
     */
    public static function get_employer( $worker_id ) {
        $employer_id = get_user_meta( $worker_id, 'pin_employer_id', true );
        if ( ! $employer_id ) {
            return false;
        }
        return get_user_by( 'ID', $employer_id );
    }

    /**
     * Get total tax contribution for a worker.
     *
     * @param int $worker_id Worker user ID.
     * @return float
     */
    public static function get_total_tax_contribution( $worker_id ) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(tax_deducted) FROM {$wpdb->prefix}pin_worker_payments WHERE worker_id = %d",
                $worker_id
            )
        );
        return (float) $result;
    }

    /**
     * AJAX: Update worker bank details.
     */
    public static function ajax_update_bank_details() {
        check_ajax_referer( 'pin_ajax_nonce', 'nonce' );

        if ( ! PIN_Roles::current_user_has_role( 'pin_worker' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $user_id   = get_current_user_id();
        $bank_name = sanitize_text_field( wp_unslash( $_POST['bank_name'] ?? '' ) );
        $bank_acct = sanitize_text_field( wp_unslash( $_POST['bank_account'] ?? '' ) );
        $acct_name = sanitize_text_field( wp_unslash( $_POST['account_name'] ?? '' ) );

        update_user_meta( $user_id, 'pin_bank_name', $bank_name );
        update_user_meta( $user_id, 'pin_bank_account', $bank_acct );
        update_user_meta( $user_id, 'pin_account_name', $acct_name );

        wp_send_json_success( array( 'message' => 'Bank details updated successfully.' ) );
    }
}
