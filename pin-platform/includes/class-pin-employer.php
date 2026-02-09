<?php
/**
 * Employer functionality for PIN Platform.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_Employer {

    /**
     * Initialize employer hooks.
     */
    public static function init() {
        add_action( 'wp_ajax_pin_add_worker', array( __CLASS__, 'ajax_add_worker' ) );
        add_action( 'wp_ajax_pin_update_worker', array( __CLASS__, 'ajax_update_worker' ) );
        add_action( 'wp_ajax_pin_remove_worker', array( __CLASS__, 'ajax_remove_worker' ) );
        add_action( 'wp_ajax_pin_bulk_upload_workers', array( __CLASS__, 'ajax_bulk_upload_workers' ) );
    }

    /**
     * Register a new employer user.
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

        // Set role.
        $user = new WP_User( $user_id );
        $user->set_role( 'pin_employer' );

        // Save employer meta.
        update_user_meta( $user_id, 'pin_company_name', sanitize_text_field( $data['company_name'] ) );
        update_user_meta( $user_id, 'pin_company_address', sanitize_textarea_field( $data['company_address'] ) );
        update_user_meta( $user_id, 'pin_tax_id', sanitize_text_field( $data['tax_id'] ) );
        update_user_meta( $user_id, 'pin_contact_phone', sanitize_text_field( $data['contact_phone'] ) );
        update_user_meta( $user_id, 'first_name', sanitize_text_field( $data['first_name'] ) );
        update_user_meta( $user_id, 'last_name', sanitize_text_field( $data['last_name'] ) );

        // Auto-login.
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );

        return $user_id;
    }

    /**
     * Get all workers for an employer.
     *
     * @param int $employer_id Employer user ID.
     * @return array Array of WP_User objects.
     */
    public static function get_workers( $employer_id ) {
        $args = array(
            'role'       => 'pin_worker',
            'meta_key'   => 'pin_employer_id',
            'meta_value' => $employer_id,
            'orderby'    => 'display_name',
            'order'      => 'ASC',
        );
        return get_users( $args );
    }

    /**
     * Get worker count for an employer.
     *
     * @param int $employer_id Employer user ID.
     * @return int
     */
    public static function get_worker_count( $employer_id ) {
        $args = array(
            'role'       => 'pin_worker',
            'meta_key'   => 'pin_employer_id',
            'meta_value' => $employer_id,
            'count_total' => true,
            'fields'     => 'ID',
        );
        $query = new WP_User_Query( $args );
        return $query->get_total();
    }

    /**
     * Get total payroll amount for an employer (gross).
     *
     * @param int $employer_id Employer user ID.
     * @return float
     */
    public static function get_total_payroll( $employer_id ) {
        $workers = self::get_workers( $employer_id );
        $total   = 0;
        foreach ( $workers as $worker ) {
            $salary = (float) get_user_meta( $worker->ID, 'pin_monthly_salary', true );
            $status = get_user_meta( $worker->ID, 'pin_worker_status', true );
            if ( 'active' === $status || empty( $status ) ) {
                $total += $salary;
            }
        }
        return $total;
    }

    /**
     * Get employer payroll history.
     *
     * @param int $employer_id Employer user ID.
     * @return array
     */
    public static function get_payroll_history( $employer_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pin_payrolls WHERE employer_id = %d ORDER BY created_at DESC",
                $employer_id
            )
        );
    }

    /**
     * Get employer total taxes contributed.
     *
     * @param int $employer_id Employer user ID.
     * @return float
     */
    public static function get_total_taxes( $employer_id ) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_tax) FROM {$wpdb->prefix}pin_payrolls WHERE employer_id = %d AND status = 'completed'",
                $employer_id
            )
        );
        return (float) $result;
    }

    /**
     * AJAX: Add a worker to the employer's payroll.
     */
    public static function ajax_add_worker() {
        check_ajax_referer( 'pin_ajax_nonce', 'nonce' );

        if ( ! PIN_Roles::current_user_has_role( 'pin_employer' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $employer_id = get_current_user_id();

        $first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
        $last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
        $email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $salary     = floatval( $_POST['salary'] ?? 0 );
        $bank_name  = sanitize_text_field( wp_unslash( $_POST['bank_name'] ?? '' ) );
        $bank_acct  = sanitize_text_field( wp_unslash( $_POST['bank_account'] ?? '' ) );
        $acct_name  = sanitize_text_field( wp_unslash( $_POST['account_name'] ?? '' ) );

        if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) || $salary <= 0 ) {
            wp_send_json_error( array( 'message' => 'Please fill all required fields.' ) );
        }

        // Check if email already exists.
        if ( email_exists( $email ) ) {
            $existing_user = get_user_by( 'email', $email );
            if ( in_array( 'pin_worker', (array) $existing_user->roles, true ) ) {
                $linked = get_user_meta( $existing_user->ID, 'pin_employer_id', true );
                if ( $linked && (int) $linked !== $employer_id ) {
                    wp_send_json_error( array( 'message' => 'This worker is already linked to another employer.' ) );
                }
                // Link existing worker to this employer.
                update_user_meta( $existing_user->ID, 'pin_employer_id', $employer_id );
                update_user_meta( $existing_user->ID, 'pin_monthly_salary', $salary );
                update_user_meta( $existing_user->ID, 'pin_worker_status', 'active' );
                wp_send_json_success( array( 'message' => 'Existing worker linked successfully.', 'worker_id' => $existing_user->ID ) );
            } else {
                wp_send_json_error( array( 'message' => 'This email is registered to a non-worker account.' ) );
            }
        }

        // Create new worker user.
        $username = sanitize_user( strtolower( $first_name . '.' . $last_name ) );
        $counter  = 1;
        $base     = $username;
        while ( username_exists( $username ) ) {
            $username = $base . $counter;
            $counter++;
        }

        $password = wp_generate_password( 12 );
        $user_id  = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        }

        $user = new WP_User( $user_id );
        $user->set_role( 'pin_worker' );

        update_user_meta( $user_id, 'first_name', $first_name );
        update_user_meta( $user_id, 'last_name', $last_name );
        update_user_meta( $user_id, 'pin_employer_id', $employer_id );
        update_user_meta( $user_id, 'pin_monthly_salary', $salary );
        update_user_meta( $user_id, 'pin_bank_name', $bank_name );
        update_user_meta( $user_id, 'pin_bank_account', $bank_acct );
        update_user_meta( $user_id, 'pin_account_name', $acct_name );
        update_user_meta( $user_id, 'pin_worker_status', 'active' );

        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => $first_name . ' ' . $last_name,
        ) );

        wp_send_json_success( array(
            'message'   => 'Worker added successfully.',
            'worker_id' => $user_id,
            'username'  => $username,
            'temp_pass' => $password,
        ) );
    }

    /**
     * AJAX: Update a worker's details.
     */
    public static function ajax_update_worker() {
        check_ajax_referer( 'pin_ajax_nonce', 'nonce' );

        if ( ! PIN_Roles::current_user_has_role( 'pin_employer' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $employer_id = get_current_user_id();
        $worker_id   = intval( $_POST['worker_id'] ?? 0 );

        // Verify the worker belongs to this employer.
        $linked = get_user_meta( $worker_id, 'pin_employer_id', true );
        if ( (int) $linked !== $employer_id ) {
            wp_send_json_error( array( 'message' => 'This worker is not in your payroll.' ) );
        }

        $salary = floatval( $_POST['salary'] ?? 0 );
        $status = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );

        if ( $salary > 0 ) {
            update_user_meta( $worker_id, 'pin_monthly_salary', $salary );
        }
        if ( in_array( $status, array( 'active', 'inactive' ), true ) ) {
            update_user_meta( $worker_id, 'pin_worker_status', $status );
        }

        wp_send_json_success( array( 'message' => 'Worker updated successfully.' ) );
    }

    /**
     * AJAX: Remove a worker from employer's payroll.
     */
    public static function ajax_remove_worker() {
        check_ajax_referer( 'pin_ajax_nonce', 'nonce' );

        if ( ! PIN_Roles::current_user_has_role( 'pin_employer' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $employer_id = get_current_user_id();
        $worker_id   = intval( $_POST['worker_id'] ?? 0 );

        $linked = get_user_meta( $worker_id, 'pin_employer_id', true );
        if ( (int) $linked !== $employer_id ) {
            wp_send_json_error( array( 'message' => 'This worker is not in your payroll.' ) );
        }

        update_user_meta( $worker_id, 'pin_worker_status', 'inactive' );
        delete_user_meta( $worker_id, 'pin_employer_id' );

        wp_send_json_success( array( 'message' => 'Worker removed from payroll.' ) );
    }

    /**
     * AJAX: Bulk upload workers from CSV.
     */
    public static function ajax_bulk_upload_workers() {
        check_ajax_referer( 'pin_ajax_nonce', 'nonce' );

        if ( ! PIN_Roles::current_user_has_role( 'pin_employer' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        if ( empty( $_FILES['csv_file'] ) ) {
            wp_send_json_error( array( 'message' => 'No file uploaded.' ) );
        }

        $file = $_FILES['csv_file'];
        if ( 'text/csv' !== $file['type'] && 'application/vnd.ms-excel' !== $file['type'] ) {
            wp_send_json_error( array( 'message' => 'Please upload a CSV file.' ) );
        }

        $employer_id = get_current_user_id();
        $handle      = fopen( $file['tmp_name'], 'r' );
        if ( ! $handle ) {
            wp_send_json_error( array( 'message' => 'Could not read file.' ) );
        }

        $header  = fgetcsv( $handle ); // Skip header row.
        $added   = 0;
        $errors  = array();
        $row_num = 1;

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_num++;
            if ( count( $row ) < 3 ) {
                $errors[] = "Row {$row_num}: Insufficient data.";
                continue;
            }

            $first_name = sanitize_text_field( $row[0] );
            $last_name  = sanitize_text_field( $row[1] );
            $email      = sanitize_email( $row[2] );
            $salary     = isset( $row[3] ) ? floatval( $row[3] ) : 0;
            $bank_name  = isset( $row[4] ) ? sanitize_text_field( $row[4] ) : '';
            $bank_acct  = isset( $row[5] ) ? sanitize_text_field( $row[5] ) : '';
            $acct_name  = isset( $row[6] ) ? sanitize_text_field( $row[6] ) : '';

            if ( empty( $email ) || $salary <= 0 ) {
                $errors[] = "Row {$row_num}: Invalid email or salary.";
                continue;
            }

            if ( email_exists( $email ) ) {
                $errors[] = "Row {$row_num}: Email {$email} already exists.";
                continue;
            }

            $username = sanitize_user( strtolower( $first_name . '.' . $last_name ) );
            $counter  = 1;
            $base     = $username;
            while ( username_exists( $username ) ) {
                $username = $base . $counter;
                $counter++;
            }

            $password = wp_generate_password( 12 );
            $user_id  = wp_create_user( $username, $password, $email );

            if ( is_wp_error( $user_id ) ) {
                $errors[] = "Row {$row_num}: " . $user_id->get_error_message();
                continue;
            }

            $user = new WP_User( $user_id );
            $user->set_role( 'pin_worker' );

            update_user_meta( $user_id, 'first_name', $first_name );
            update_user_meta( $user_id, 'last_name', $last_name );
            update_user_meta( $user_id, 'pin_employer_id', $employer_id );
            update_user_meta( $user_id, 'pin_monthly_salary', $salary );
            update_user_meta( $user_id, 'pin_bank_name', $bank_name );
            update_user_meta( $user_id, 'pin_bank_account', $bank_acct );
            update_user_meta( $user_id, 'pin_account_name', $acct_name );
            update_user_meta( $user_id, 'pin_worker_status', 'active' );

            wp_update_user( array(
                'ID'           => $user_id,
                'display_name' => $first_name . ' ' . $last_name,
            ) );

            $added++;
        }

        fclose( $handle );

        wp_send_json_success( array(
            'message' => "{$added} workers added successfully.",
            'errors'  => $errors,
        ) );
    }
}
