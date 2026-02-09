<?php
/**
 * Plugin activation handler.
 *
 * Creates custom database tables and registers roles.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_Activator {

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        self::create_tables();
        self::create_roles();
        self::create_pages();
        flush_rewrite_rules();

        update_option( 'pin_version', PIN_VERSION );
        update_option( 'pin_activated', time() );
    }

    /**
     * Create custom database tables.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Payrolls table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pin_payrolls (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            employer_id BIGINT UNSIGNED NOT NULL,
            pay_period VARCHAR(7) NOT NULL,
            total_gross DECIMAL(15,2) NOT NULL DEFAULT 0,
            total_tax DECIMAL(15,2) NOT NULL DEFAULT 0,
            total_net DECIMAL(15,2) NOT NULL DEFAULT 0,
            worker_count INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            wc_order_id BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY employer_id (employer_id),
            KEY pay_period (pay_period),
            KEY status (status)
        ) {$charset_collate};";

        // Worker payments table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pin_worker_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            payroll_id BIGINT UNSIGNED NOT NULL,
            worker_id BIGINT UNSIGNED NOT NULL,
            gross_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
            tax_deducted DECIMAL(15,2) NOT NULL DEFAULT 0,
            net_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY payroll_id (payroll_id),
            KEY worker_id (worker_id),
            KEY payment_status (payment_status)
        ) {$charset_collate};";

        // Officer distributions table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pin_officer_distributions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            officer_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            distribution_date DATE NOT NULL,
            source_payroll_ids LONGTEXT DEFAULT NULL,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY officer_id (officer_id),
            KEY distribution_date (distribution_date),
            KEY payment_status (payment_status)
        ) {$charset_collate};";

        // Tax pool table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pin_tax_pool (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            payroll_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            distributed TINYINT(1) NOT NULL DEFAULT 0,
            distribution_id BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY payroll_id (payroll_id),
            KEY distributed (distributed)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $sql as $query ) {
            dbDelta( $query );
        }
    }

    /**
     * Create custom user roles.
     */
    private static function create_roles() {
        // Employer role.
        add_role( 'pin_employer', 'PIN Employer', array(
            'read'         => true,
            'upload_files' => true,
        ) );

        // Worker role.
        add_role( 'pin_worker', 'PIN Worker', array(
            'read' => true,
        ) );

        // Officer role.
        add_role( 'pin_officer', 'PIN Officer', array(
            'read' => true,
        ) );
    }

    /**
     * Create required WordPress pages with shortcodes.
     */
    private static function create_pages() {
        $pages = array(
            'pin-landing' => array(
                'title'     => 'PIN Platform',
                'shortcode' => '[pin_landing]',
                'slug'      => 'pin',
            ),
            'pin-dashboard' => array(
                'title'     => 'PIN Dashboard',
                'shortcode' => '[pin_dashboard]',
                'slug'      => 'pin-dashboard',
            ),
            'pin-register-employer' => array(
                'title'     => 'Employer Registration',
                'shortcode' => '[pin_register_employer]',
                'slug'      => 'pin-register-employer',
            ),
            'pin-register-worker' => array(
                'title'     => 'Worker Registration',
                'shortcode' => '[pin_register_worker]',
                'slug'      => 'pin-register-worker',
            ),
            'pin-register-officer' => array(
                'title'     => 'Officer Registration',
                'shortcode' => '[pin_register_officer]',
                'slug'      => 'pin-register-officer',
            ),
            'pin-transparency' => array(
                'title'     => 'Transparency Dashboard',
                'shortcode' => '[pin_transparency]',
                'slug'      => 'pin-transparency',
            ),
        );

        foreach ( $pages as $key => $page ) {
            $existing = get_page_by_path( $page['slug'] );
            if ( ! $existing ) {
                wp_insert_post( array(
                    'post_title'   => $page['title'],
                    'post_content' => $page['shortcode'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_name'    => $page['slug'],
                ) );
            }
        }
    }
}
