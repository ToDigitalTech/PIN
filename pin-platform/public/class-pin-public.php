<?php
/**
 * Public-facing functionality: shortcodes and form handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_Public {

    /**
     * Initialize public hooks.
     */
    public static function init() {
        // Register shortcodes.
        add_shortcode( 'pin_landing', array( __CLASS__, 'shortcode_landing' ) );
        add_shortcode( 'pin_register_employer', array( __CLASS__, 'shortcode_register_employer' ) );
        add_shortcode( 'pin_register_worker', array( __CLASS__, 'shortcode_register_worker' ) );
        add_shortcode( 'pin_register_officer', array( __CLASS__, 'shortcode_register_officer' ) );
        add_shortcode( 'pin_dashboard', array( __CLASS__, 'shortcode_dashboard' ) );
        add_shortcode( 'pin_transparency', array( __CLASS__, 'shortcode_transparency' ) );

        // Handle registration form submissions.
        add_action( 'init', array( __CLASS__, 'handle_registration' ) );
    }

    /**
     * Landing page shortcode.
     */
    public static function shortcode_landing() {
        ob_start();
        include PIN_PLUGIN_DIR . 'templates/landing.php';
        return ob_get_clean();
    }

    /**
     * Employer registration shortcode.
     */
    public static function shortcode_register_employer() {
        if ( is_user_logged_in() ) {
            ob_start();
            echo '<div class="pin-notice pin-notice-info">You are already registered. <a href="' . esc_url( home_url( '/pin-dashboard/' ) ) . '">Go to Dashboard</a></div>';
            return ob_get_clean();
        }
        ob_start();
        include PIN_PLUGIN_DIR . 'templates/registration/employer.php';
        return ob_get_clean();
    }

    /**
     * Worker registration shortcode.
     */
    public static function shortcode_register_worker() {
        if ( is_user_logged_in() ) {
            ob_start();
            echo '<div class="pin-notice pin-notice-info">You are already registered. <a href="' . esc_url( home_url( '/pin-dashboard/' ) ) . '">Go to Dashboard</a></div>';
            return ob_get_clean();
        }
        ob_start();
        include PIN_PLUGIN_DIR . 'templates/registration/worker.php';
        return ob_get_clean();
    }

    /**
     * Officer registration shortcode.
     */
    public static function shortcode_register_officer() {
        if ( is_user_logged_in() ) {
            ob_start();
            echo '<div class="pin-notice pin-notice-info">You are already registered. <a href="' . esc_url( home_url( '/pin-dashboard/' ) ) . '">Go to Dashboard</a></div>';
            return ob_get_clean();
        }
        ob_start();
        include PIN_PLUGIN_DIR . 'templates/registration/officer.php';
        return ob_get_clean();
    }

    /**
     * Dashboard shortcode - routes to role-specific dashboard.
     */
    public static function shortcode_dashboard() {
        if ( ! is_user_logged_in() ) {
            ob_start();
            echo '<div class="pin-notice pin-notice-warning">Please <a href="' . esc_url( wp_login_url( home_url( '/pin-dashboard/' ) ) ) . '">log in</a> to access your dashboard.</div>';
            return ob_get_clean();
        }

        $role = PIN_Roles::get_current_pin_role();

        ob_start();
        switch ( $role ) {
            case 'pin_employer':
                include PIN_PLUGIN_DIR . 'templates/dashboard-employer.php';
                break;
            case 'pin_worker':
                include PIN_PLUGIN_DIR . 'templates/dashboard-worker.php';
                break;
            case 'pin_officer':
                include PIN_PLUGIN_DIR . 'templates/dashboard-officer.php';
                break;
            default:
                echo '<div class="pin-notice pin-notice-warning">Your account does not have a PIN Platform role. Please register as an <a href="' . esc_url( home_url( '/pin-register-employer/' ) ) . '">Employer</a>, <a href="' . esc_url( home_url( '/pin-register-worker/' ) ) . '">Worker</a>, or <a href="' . esc_url( home_url( '/pin-register-officer/' ) ) . '">Officer</a>.</div>';
        }
        return ob_get_clean();
    }

    /**
     * Transparency dashboard shortcode.
     */
    public static function shortcode_transparency() {
        ob_start();
        include PIN_PLUGIN_DIR . 'templates/transparency.php';
        return ob_get_clean();
    }

    /**
     * Handle registration form submissions.
     */
    public static function handle_registration() {
        if ( empty( $_POST['pin_registration_action'] ) ) {
            return;
        }

        $action = sanitize_text_field( wp_unslash( $_POST['pin_registration_action'] ) );

        // Verify nonce.
        if ( ! isset( $_POST['pin_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pin_register_nonce'] ) ), 'pin_register_' . $action ) ) {
            wp_die( 'Security check failed.' );
        }

        $data = array();
        foreach ( $_POST as $key => $value ) {
            if ( 'password' === $key ) {
                $data[ $key ] = $value; // Don't sanitize passwords.
            } else {
                $data[ $key ] = sanitize_text_field( wp_unslash( $value ) );
            }
        }

        $result = null;

        switch ( $action ) {
            case 'employer':
                $result = PIN_Employer::register( $data );
                break;
            case 'worker':
                $result = PIN_Worker::register( $data );
                break;
            case 'officer':
                $result = PIN_Officer::register( $data );
                break;
        }

        if ( is_wp_error( $result ) ) {
            // Store error for display.
            set_transient( 'pin_register_error_' . session_id(), $result->get_error_message(), 60 );
            wp_safe_redirect( wp_get_referer() );
            exit;
        }

        if ( $result ) {
            wp_safe_redirect( home_url( '/pin-dashboard/' ) );
            exit;
        }
    }

    /**
     * Format currency amount.
     *
     * @param float $amount Amount to format.
     * @return string
     */
    public static function format_currency( $amount ) {
        return '₦' . number_format( (float) $amount, 2 );
    }
}
