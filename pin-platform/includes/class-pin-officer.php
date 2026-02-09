<?php
/**
 * Officer functionality for PIN Platform.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_Officer {

    /**
     * Initialize officer hooks.
     */
    public static function init() {
        add_action( 'wp_ajax_pin_officer_update_bank', array( __CLASS__, 'ajax_update_bank_details' ) );
    }

    /**
     * Register a new officer user.
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

        $badge_number = sanitize_text_field( $data['badge_number'] );
        $id_number    = sanitize_text_field( $data['id_number'] );

        if ( empty( $badge_number ) || empty( $id_number ) ) {
            return new WP_Error( 'missing_fields', 'Badge number and ID number are required.' );
        }

        // Check for duplicate badge numbers.
        $existing = get_users( array(
            'meta_key'   => 'pin_badge_number',
            'meta_value' => $badge_number,
            'number'     => 1,
            'fields'     => 'ID',
        ) );
        if ( ! empty( $existing ) ) {
            return new WP_Error( 'duplicate_badge', 'This badge number is already registered.' );
        }

        // Check for duplicate ID numbers.
        $existing_id = get_users( array(
            'meta_key'   => 'pin_id_number',
            'meta_value' => $id_number,
            'number'     => 1,
            'fields'     => 'ID',
        ) );
        if ( ! empty( $existing_id ) ) {
            return new WP_Error( 'duplicate_id', 'This ID number is already registered.' );
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
        $user->set_role( 'pin_officer' );

        update_user_meta( $user_id, 'first_name', sanitize_text_field( $data['first_name'] ) );
        update_user_meta( $user_id, 'last_name', sanitize_text_field( $data['last_name'] ) );
        update_user_meta( $user_id, 'pin_badge_number', $badge_number );
        update_user_meta( $user_id, 'pin_id_number', $id_number );
        update_user_meta( $user_id, 'pin_bank_name', sanitize_text_field( $data['bank_name'] ?? '' ) );
        update_user_meta( $user_id, 'pin_bank_account', sanitize_text_field( $data['bank_account'] ?? '' ) );
        update_user_meta( $user_id, 'pin_account_name', sanitize_text_field( $data['account_name'] ?? '' ) );
        update_user_meta( $user_id, 'pin_verification_status', 'pending' );

        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => sanitize_text_field( $data['first_name'] ) . ' ' . sanitize_text_field( $data['last_name'] ),
        ) );

        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );

        return $user_id;
    }

    /**
     * Get all verified officers.
     *
     * @return array Array of WP_User objects.
     */
    public static function get_verified_officers() {
        return get_users( array(
            'role'       => 'pin_officer',
            'meta_key'   => 'pin_verification_status',
            'meta_value' => 'verified',
        ) );
    }

    /**
     * Get all officers (any verification status).
     *
     * @return array
     */
    public static function get_all_officers() {
        return get_users( array(
            'role' => 'pin_officer',
        ) );
    }

    /**
     * Get total number of registered officers.
     *
     * @return int
     */
    public static function get_officer_count() {
        $result = count_users();
        return isset( $result['avail_roles']['pin_officer'] ) ? $result['avail_roles']['pin_officer'] : 0;
    }

    /**
     * Get distribution history for an officer.
     *
     * @param int $officer_id Officer user ID.
     * @return array
     */
    public static function get_distribution_history( $officer_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pin_officer_distributions WHERE officer_id = %d ORDER BY distribution_date DESC",
                $officer_id
            )
        );
    }

    /**
     * Get total earned by an officer.
     *
     * @param int $officer_id Officer user ID.
     * @return float
     */
    public static function get_total_earned( $officer_id ) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$wpdb->prefix}pin_officer_distributions WHERE officer_id = %d",
                $officer_id
            )
        );
        return (float) $result;
    }

    /**
     * AJAX: Update officer bank details.
     */
    public static function ajax_update_bank_details() {
        check_ajax_referer( 'pin_ajax_nonce', 'nonce' );

        if ( ! PIN_Roles::current_user_has_role( 'pin_officer' ) ) {
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
