<?php
/**
 * Custom roles and capabilities for PIN Platform.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_Roles {

    /**
     * Initialize role hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_roles' ) );
        add_filter( 'login_redirect', array( __CLASS__, 'role_based_redirect' ), 10, 3 );
    }

    /**
     * Ensure roles exist (idempotent).
     */
    public static function register_roles() {
        if ( ! get_role( 'pin_employer' ) ) {
            add_role( 'pin_employer', 'PIN Employer', array(
                'read'         => true,
                'upload_files' => true,
            ) );
        }
        if ( ! get_role( 'pin_worker' ) ) {
            add_role( 'pin_worker', 'PIN Worker', array(
                'read' => true,
            ) );
        }
        if ( ! get_role( 'pin_officer' ) ) {
            add_role( 'pin_officer', 'PIN Officer', array(
                'read' => true,
            ) );
        }
    }

    /**
     * Redirect users to their role-specific dashboard after login.
     *
     * @param string  $redirect_to Default redirect URL.
     * @param string  $requested   Requested redirect URL.
     * @param WP_User $user        Logged-in user.
     * @return string
     */
    public static function role_based_redirect( $redirect_to, $requested, $user ) {
        if ( ! is_wp_error( $user ) && isset( $user->roles ) ) {
            $roles = (array) $user->roles;
            if ( in_array( 'pin_employer', $roles, true )
                || in_array( 'pin_worker', $roles, true )
                || in_array( 'pin_officer', $roles, true )
            ) {
                return home_url( '/pin-dashboard/' );
            }
        }
        return $redirect_to;
    }

    /**
     * Check if the current user has a specific PIN role.
     *
     * @param string $role Role slug.
     * @return bool
     */
    public static function current_user_has_role( $role ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        $user = wp_get_current_user();
        return in_array( $role, (array) $user->roles, true );
    }

    /**
     * Get the PIN role of the current user.
     *
     * @return string|false Role slug or false.
     */
    public static function get_current_pin_role() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        $user  = wp_get_current_user();
        $roles = (array) $user->roles;

        $pin_roles = array( 'pin_employer', 'pin_worker', 'pin_officer' );
        foreach ( $pin_roles as $role ) {
            if ( in_array( $role, $roles, true ) ) {
                return $role;
            }
        }
        return false;
    }
}
