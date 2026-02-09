<?php
/**
 * Admin interface for PIN Platform.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_Admin {

    /**
     * Initialize admin hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
        add_action( 'wp_ajax_pin_admin_verify_officer', array( __CLASS__, 'ajax_verify_officer' ) );
        add_action( 'wp_ajax_pin_admin_update_settings', array( __CLASS__, 'ajax_update_settings' ) );
    }

    /**
     * Register admin menu pages.
     */
    public static function add_admin_menu() {
        add_menu_page(
            'PIN Platform',
            'PIN Platform',
            'manage_options',
            'pin-platform',
            array( __CLASS__, 'render_overview_page' ),
            'dashicons-money-alt',
            30
        );

        add_submenu_page(
            'pin-platform',
            'Overview',
            'Overview',
            'manage_options',
            'pin-platform',
            array( __CLASS__, 'render_overview_page' )
        );

        add_submenu_page(
            'pin-platform',
            'Employers',
            'Employers',
            'manage_options',
            'pin-platform-employers',
            array( __CLASS__, 'render_employers_page' )
        );

        add_submenu_page(
            'pin-platform',
            'Workers',
            'Workers',
            'manage_options',
            'pin-platform-workers',
            array( __CLASS__, 'render_workers_page' )
        );

        add_submenu_page(
            'pin-platform',
            'Officers',
            'Officers',
            'manage_options',
            'pin-platform-officers',
            array( __CLASS__, 'render_officers_page' )
        );

        add_submenu_page(
            'pin-platform',
            'Payrolls',
            'Payrolls',
            'manage_options',
            'pin-platform-payrolls',
            array( __CLASS__, 'render_payrolls_page' )
        );

        add_submenu_page(
            'pin-platform',
            'Distributions',
            'Distributions',
            'manage_options',
            'pin-platform-distributions',
            array( __CLASS__, 'render_distributions_page' )
        );

        add_submenu_page(
            'pin-platform',
            'Settings',
            'Settings',
            'manage_options',
            'pin-platform-settings',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    /**
     * Render the admin overview dashboard.
     */
    public static function render_overview_page() {
        $stats = PIN_Distribution::get_transparency_stats();
        $pool_balance = PIN_Distribution::get_pool_balance();
        $recent = PIN_Distribution::get_recent_distributions( 5 );
        ?>
        <div class="wrap pin-admin-wrap">
            <h1>PIN Platform - Overview</h1>

            <div class="pin-admin-stats">
                <div class="pin-stat-card">
                    <h3>Employers</h3>
                    <span class="pin-stat-number"><?php echo esc_html( number_format( $stats['employer_count'] ) ); ?></span>
                </div>
                <div class="pin-stat-card">
                    <h3>Workers</h3>
                    <span class="pin-stat-number"><?php echo esc_html( number_format( $stats['worker_count'] ) ); ?></span>
                </div>
                <div class="pin-stat-card">
                    <h3>Officers</h3>
                    <span class="pin-stat-number"><?php echo esc_html( number_format( $stats['officer_count'] ) ); ?></span>
                </div>
                <div class="pin-stat-card">
                    <h3>Total Taxes Collected</h3>
                    <span class="pin-stat-number"><?php echo esc_html( '₦' . number_format( $stats['total_taxes'], 2 ) ); ?></span>
                </div>
                <div class="pin-stat-card">
                    <h3>Total Distributed</h3>
                    <span class="pin-stat-number"><?php echo esc_html( '₦' . number_format( $stats['total_distributed'], 2 ) ); ?></span>
                </div>
                <div class="pin-stat-card">
                    <h3>Pool Balance</h3>
                    <span class="pin-stat-number"><?php echo esc_html( '₦' . number_format( $pool_balance, 2 ) ); ?></span>
                </div>
            </div>

            <div class="pin-admin-section">
                <h2>Quick Actions</h2>
                <p>
                    <button type="button" class="button button-primary" id="pin-trigger-distribution">
                        Trigger Officer Distribution
                    </button>
                    <span id="pin-distribution-result" style="margin-left: 10px;"></span>
                </p>
            </div>

            <?php if ( ! empty( $recent ) ) : ?>
            <div class="pin-admin-section">
                <h2>Recent Distributions</h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Officers</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $recent as $dist ) : ?>
                        <tr>
                            <td><?php echo esc_html( $dist->distribution_date ); ?></td>
                            <td><?php echo esc_html( number_format( $dist->officer_count ) ); ?></td>
                            <td><?php echo esc_html( '₦' . number_format( $dist->total_amount, 2 ) ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render employers admin page.
     */
    public static function render_employers_page() {
        $employers = get_users( array( 'role' => 'pin_employer', 'orderby' => 'registered', 'order' => 'DESC' ) );
        ?>
        <div class="wrap pin-admin-wrap">
            <h1>Employers</h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Tax ID</th>
                        <th>Workers</th>
                        <th>Total Taxes</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $employers ) ) : ?>
                        <tr><td colspan="8">No employers registered yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $employers as $emp ) : ?>
                        <tr>
                            <td><?php echo esc_html( $emp->ID ); ?></td>
                            <td><?php echo esc_html( get_user_meta( $emp->ID, 'pin_company_name', true ) ); ?></td>
                            <td><?php echo esc_html( $emp->display_name ); ?></td>
                            <td><?php echo esc_html( $emp->user_email ); ?></td>
                            <td><?php echo esc_html( get_user_meta( $emp->ID, 'pin_tax_id', true ) ); ?></td>
                            <td><?php echo esc_html( PIN_Employer::get_worker_count( $emp->ID ) ); ?></td>
                            <td><?php echo esc_html( '₦' . number_format( PIN_Employer::get_total_taxes( $emp->ID ), 2 ) ); ?></td>
                            <td><?php echo esc_html( $emp->user_registered ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render workers admin page.
     */
    public static function render_workers_page() {
        $workers = get_users( array( 'role' => 'pin_worker', 'orderby' => 'registered', 'order' => 'DESC' ) );
        ?>
        <div class="wrap pin-admin-wrap">
            <h1>Workers</h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Employer</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Bank</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $workers ) ) : ?>
                        <tr><td colspan="7">No workers registered yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $workers as $w ) :
                            $employer_id = get_user_meta( $w->ID, 'pin_employer_id', true );
                            $employer = $employer_id ? get_user_meta( $employer_id, 'pin_company_name', true ) : 'Unlinked';
                        ?>
                        <tr>
                            <td><?php echo esc_html( $w->ID ); ?></td>
                            <td><?php echo esc_html( $w->display_name ); ?></td>
                            <td><?php echo esc_html( $w->user_email ); ?></td>
                            <td><?php echo esc_html( $employer ); ?></td>
                            <td><?php echo esc_html( '₦' . number_format( (float) get_user_meta( $w->ID, 'pin_monthly_salary', true ), 2 ) ); ?></td>
                            <td><?php echo esc_html( get_user_meta( $w->ID, 'pin_worker_status', true ) ?: 'active' ); ?></td>
                            <td><?php echo esc_html( get_user_meta( $w->ID, 'pin_bank_name', true ) ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render officers admin page.
     */
    public static function render_officers_page() {
        $officers = get_users( array( 'role' => 'pin_officer', 'orderby' => 'registered', 'order' => 'DESC' ) );
        ?>
        <div class="wrap pin-admin-wrap">
            <h1>Officers</h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Badge #</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Bank</th>
                        <th>Total Earned</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $officers ) ) : ?>
                        <tr><td colspan="8">No officers registered yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $officers as $o ) :
                            $status = get_user_meta( $o->ID, 'pin_verification_status', true ) ?: 'pending';
                        ?>
                        <tr>
                            <td><?php echo esc_html( $o->ID ); ?></td>
                            <td><?php echo esc_html( $o->display_name ); ?></td>
                            <td><?php echo esc_html( get_user_meta( $o->ID, 'pin_badge_number', true ) ); ?></td>
                            <td><?php echo esc_html( $o->user_email ); ?></td>
                            <td>
                                <span class="pin-status pin-status-<?php echo esc_attr( $status ); ?>">
                                    <?php echo esc_html( ucfirst( $status ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( get_user_meta( $o->ID, 'pin_bank_name', true ) ); ?></td>
                            <td><?php echo esc_html( '₦' . number_format( PIN_Officer::get_total_earned( $o->ID ), 2 ) ); ?></td>
                            <td>
                                <?php if ( 'verified' !== $status ) : ?>
                                <button type="button" class="button pin-verify-officer" data-officer-id="<?php echo esc_attr( $o->ID ); ?>">Verify</button>
                                <?php else : ?>
                                <span class="dashicons dashicons-yes-alt" style="color: #2C5F2D;"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render payrolls admin page.
     */
    public static function render_payrolls_page() {
        global $wpdb;
        $payrolls = $wpdb->get_results(
            "SELECT p.*, u.display_name as employer_name,
                    (SELECT pin_company_name FROM {$wpdb->usermeta} WHERE user_id = p.employer_id AND meta_key = 'pin_company_name' LIMIT 1) as company_name
             FROM {$wpdb->prefix}pin_payrolls p
             JOIN {$wpdb->users} u ON p.employer_id = u.ID
             ORDER BY p.created_at DESC
             LIMIT 100"
        );
        ?>
        <div class="wrap pin-admin-wrap">
            <h1>Payrolls</h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company</th>
                        <th>Period</th>
                        <th>Workers</th>
                        <th>Gross</th>
                        <th>Tax (25%)</th>
                        <th>Net</th>
                        <th>Status</th>
                        <th>WC Order</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $payrolls ) ) : ?>
                        <tr><td colspan="10">No payrolls processed yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $payrolls as $p ) : ?>
                        <tr>
                            <td><?php echo esc_html( $p->id ); ?></td>
                            <td><?php echo esc_html( $p->company_name ?: $p->employer_name ); ?></td>
                            <td><?php echo esc_html( $p->pay_period ); ?></td>
                            <td><?php echo esc_html( $p->worker_count ); ?></td>
                            <td><?php echo esc_html( '₦' . number_format( $p->total_gross, 2 ) ); ?></td>
                            <td><?php echo esc_html( '₦' . number_format( $p->total_tax, 2 ) ); ?></td>
                            <td><?php echo esc_html( '₦' . number_format( $p->total_net, 2 ) ); ?></td>
                            <td>
                                <span class="pin-status pin-status-<?php echo esc_attr( $p->status ); ?>">
                                    <?php echo esc_html( ucfirst( $p->status ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ( $p->wc_order_id ) : ?>
                                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $p->wc_order_id . '&action=edit' ) ); ?>">
                                        #<?php echo esc_html( $p->wc_order_id ); ?>
                                    </a>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $p->created_at ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render distributions admin page.
     */
    public static function render_distributions_page() {
        $distributions = PIN_Distribution::get_recent_distributions( 50 );
        $pool_balance  = PIN_Distribution::get_pool_balance();
        ?>
        <div class="wrap pin-admin-wrap">
            <h1>Distributions</h1>

            <div class="pin-admin-stats" style="margin-bottom: 20px;">
                <div class="pin-stat-card">
                    <h3>Current Pool Balance</h3>
                    <span class="pin-stat-number"><?php echo esc_html( '₦' . number_format( $pool_balance, 2 ) ); ?></span>
                </div>
            </div>

            <?php if ( $pool_balance > 0 ) : ?>
            <p>
                <button type="button" class="button button-primary" id="pin-trigger-distribution">
                    Distribute ₦<?php echo esc_html( number_format( $pool_balance, 2 ) ); ?> to Officers
                </button>
                <span id="pin-distribution-result" style="margin-left: 10px;"></span>
            </p>
            <?php endif; ?>

            <table class="widefat striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Officers</th>
                        <th>Total Distributed</th>
                        <th>Per Officer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $distributions ) ) : ?>
                        <tr><td colspan="4">No distributions yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $distributions as $d ) :
                            $per = $d->officer_count > 0 ? $d->total_amount / $d->officer_count : 0;
                        ?>
                        <tr>
                            <td><?php echo esc_html( $d->distribution_date ); ?></td>
                            <td><?php echo esc_html( number_format( $d->officer_count ) ); ?></td>
                            <td><?php echo esc_html( '₦' . number_format( $d->total_amount, 2 ) ); ?></td>
                            <td><?php echo esc_html( '₦' . number_format( $per, 2 ) ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render settings page.
     */
    public static function render_settings_page() {
        $tax_rate = get_option( 'pin_tax_rate', 25 );
        $force_ngn = get_option( 'pin_force_ngn', false );
        $min_distribution = get_option( 'pin_min_distribution', 0 );
        $auto_distribute = get_option( 'pin_auto_distribute', true );
        ?>
        <div class="wrap pin-admin-wrap">
            <h1>PIN Platform Settings</h1>

            <form id="pin-settings-form" class="pin-admin-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="pin_tax_rate">Tax Rate (%)</label></th>
                        <td>
                            <input type="number" id="pin_tax_rate" name="tax_rate" value="<?php echo esc_attr( $tax_rate ); ?>" min="1" max="100" step="0.1" class="small-text">
                            <p class="description">Percentage of gross salary deducted as tax for officer pool. Default: 25%</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pin_force_ngn">Force NGN Currency</label></th>
                        <td>
                            <input type="checkbox" id="pin_force_ngn" name="force_ngn" value="1" <?php checked( $force_ngn ); ?>>
                            <label for="pin_force_ngn">Override WooCommerce currency to Nigerian Naira (NGN)</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pin_min_distribution">Minimum Distribution Amount (₦)</label></th>
                        <td>
                            <input type="number" id="pin_min_distribution" name="min_distribution" value="<?php echo esc_attr( $min_distribution ); ?>" min="0" step="100" class="regular-text">
                            <p class="description">Minimum pool balance before distribution is triggered. Set to 0 for no minimum.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pin_auto_distribute">Auto-Distribute</label></th>
                        <td>
                            <input type="checkbox" id="pin_auto_distribute" name="auto_distribute" value="1" <?php checked( $auto_distribute ); ?>>
                            <label for="pin_auto_distribute">Automatically distribute to officers monthly via cron</label>
                        </td>
                    </tr>
                </table>

                <h2>Payment Gateway (Placeholder)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="pin_paystack_key">Paystack Secret Key</label></th>
                        <td>
                            <input type="password" id="pin_paystack_key" name="paystack_key" value="<?php echo esc_attr( get_option( 'pin_paystack_key', '' ) ); ?>" class="regular-text">
                            <p class="description">Required for automated bank transfers to officers.</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Save Settings</button>
                    <span id="pin-settings-result" style="margin-left: 10px;"></span>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX: Verify an officer.
     */
    public static function ajax_verify_officer() {
        check_ajax_referer( 'pin_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $officer_id = intval( $_POST['officer_id'] ?? 0 );
        if ( ! $officer_id ) {
            wp_send_json_error( array( 'message' => 'Invalid officer ID.' ) );
        }

        $user = get_user_by( 'ID', $officer_id );
        if ( ! $user || ! in_array( 'pin_officer', (array) $user->roles, true ) ) {
            wp_send_json_error( array( 'message' => 'User is not an officer.' ) );
        }

        update_user_meta( $officer_id, 'pin_verification_status', 'verified' );

        wp_send_json_success( array( 'message' => 'Officer verified successfully.' ) );
    }

    /**
     * AJAX: Update settings.
     */
    public static function ajax_update_settings() {
        check_ajax_referer( 'pin_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $tax_rate = floatval( $_POST['tax_rate'] ?? 25 );
        $force_ngn = ! empty( $_POST['force_ngn'] );
        $min_distribution = floatval( $_POST['min_distribution'] ?? 0 );
        $auto_distribute = ! empty( $_POST['auto_distribute'] );
        $paystack_key = sanitize_text_field( wp_unslash( $_POST['paystack_key'] ?? '' ) );

        update_option( 'pin_tax_rate', $tax_rate );
        update_option( 'pin_force_ngn', $force_ngn );
        update_option( 'pin_min_distribution', $min_distribution );
        update_option( 'pin_auto_distribute', $auto_distribute );
        if ( ! empty( $paystack_key ) ) {
            update_option( 'pin_paystack_key', $paystack_key );
        }

        wp_send_json_success( array( 'message' => 'Settings saved successfully.' ) );
    }
}
