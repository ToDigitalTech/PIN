<?php
/**
 * Template: Worker Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user      = wp_get_current_user();
$worker_id = $user->ID;
$breakdown = PIN_Worker::get_salary_breakdown( $worker_id );
$employer  = PIN_Worker::get_employer( $worker_id );
$history   = PIN_Worker::get_payment_history( $worker_id );
$total_tax = PIN_Worker::get_total_tax_contribution( $worker_id );

$company_name = $employer ? get_user_meta( $employer->ID, 'pin_company_name', true ) : 'Not linked';
$officer_count = PIN_Officer::get_officer_count();
$bank_name    = get_user_meta( $worker_id, 'pin_bank_name', true );
$bank_account = get_user_meta( $worker_id, 'pin_bank_account', true );
$account_name = get_user_meta( $worker_id, 'pin_account_name', true );
?>
<div class="pin-dashboard pin-dashboard-worker">
    <!-- Header -->
    <div class="pin-dash-header">
        <div>
            <h1>Worker Dashboard</h1>
            <p>Welcome, <?php echo esc_html( $user->display_name ); ?></p>
        </div>
        <div class="pin-dash-actions">
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/pin/' ) ) ); ?>" class="pin-btn pin-btn-outline pin-btn-sm">Log Out</a>
        </div>
    </div>

    <!-- Employer Info -->
    <div class="pin-info-bar">
        <span>Employer: <strong><?php echo esc_html( $company_name ); ?></strong></span>
    </div>

    <!-- Salary Breakdown -->
    <div class="pin-salary-breakdown">
        <h2>Monthly Salary Breakdown</h2>
        <div class="pin-breakdown-visual">
            <div class="pin-breakdown-bar">
                <div class="pin-breakdown-net" style="width: 75%;">
                    <span>Net: <?php echo esc_html( PIN_Public::format_currency( $breakdown['net'] ) ); ?></span>
                </div>
                <div class="pin-breakdown-tax" style="width: 25%;">
                    <span>Tax: <?php echo esc_html( PIN_Public::format_currency( $breakdown['tax'] ) ); ?></span>
                </div>
            </div>
        </div>
        <div class="pin-dash-stats">
            <div class="pin-dash-stat-card">
                <span class="pin-dash-stat-label">Gross Salary</span>
                <span class="pin-dash-stat-value"><?php echo esc_html( PIN_Public::format_currency( $breakdown['gross'] ) ); ?></span>
            </div>
            <div class="pin-dash-stat-card">
                <span class="pin-dash-stat-label">Tax Deduction (25%)</span>
                <span class="pin-dash-stat-value pin-text-amber"><?php echo esc_html( PIN_Public::format_currency( $breakdown['tax'] ) ); ?></span>
            </div>
            <div class="pin-dash-stat-card">
                <span class="pin-dash-stat-label">Net Salary</span>
                <span class="pin-dash-stat-value pin-text-green"><?php echo esc_html( PIN_Public::format_currency( $breakdown['net'] ) ); ?></span>
            </div>
            <div class="pin-dash-stat-card">
                <span class="pin-dash-stat-label">Total Tax Contributed</span>
                <span class="pin-dash-stat-value"><?php echo esc_html( PIN_Public::format_currency( $total_tax ) ); ?></span>
            </div>
        </div>
    </div>

    <!-- Tax Impact -->
    <div class="pin-section pin-tax-impact">
        <h2>Your Tax Impact</h2>
        <div class="pin-impact-card">
            <p>Your monthly tax contribution of <strong><?php echo esc_html( PIN_Public::format_currency( $breakdown['tax'] ) ); ?></strong> helps fund
            <strong><?php echo esc_html( number_format( $officer_count ) ); ?></strong> registered officers.</p>
            <p>
                <a href="<?php echo esc_url( home_url( '/pin-transparency/' ) ); ?>" class="pin-btn pin-btn-outline pin-btn-sm">View Transparency Dashboard</a>
            </p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="pin-tabs">
        <button class="pin-tab active" data-tab="w-history">Payment History</button>
        <button class="pin-tab" data-tab="w-bank">Bank Details</button>
    </div>

    <!-- Payment History -->
    <div class="pin-tab-content active" id="tab-w-history">
        <h2>Payment History</h2>
        <div class="pin-table-responsive">
            <table class="pin-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Gross</th>
                        <th>Tax</th>
                        <th>Net</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $history ) ) : ?>
                        <tr><td colspan="5" class="pin-text-center">No payments recorded yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $history as $h ) : ?>
                        <tr>
                            <td><?php echo esc_html( $h->pay_period ); ?></td>
                            <td><?php echo esc_html( PIN_Public::format_currency( $h->gross_salary ) ); ?></td>
                            <td><?php echo esc_html( PIN_Public::format_currency( $h->tax_deducted ) ); ?></td>
                            <td><?php echo esc_html( PIN_Public::format_currency( $h->net_salary ) ); ?></td>
                            <td><span class="pin-badge pin-badge-<?php echo esc_attr( $h->payment_status ); ?>"><?php echo esc_html( ucfirst( $h->payment_status ) ); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bank Details -->
    <div class="pin-tab-content" id="tab-w-bank">
        <h2>Bank Details</h2>
        <form id="pin-worker-bank-form">
            <div class="pin-form-row">
                <div class="pin-form-group">
                    <label>Bank Name</label>
                    <input type="text" name="bank_name" value="<?php echo esc_attr( $bank_name ); ?>">
                </div>
                <div class="pin-form-group">
                    <label>Account Number</label>
                    <input type="text" name="bank_account" value="<?php echo esc_attr( $bank_account ); ?>" maxlength="10">
                </div>
            </div>
            <div class="pin-form-group">
                <label>Account Name</label>
                <input type="text" name="account_name" value="<?php echo esc_attr( $account_name ); ?>">
            </div>
            <div class="pin-form-actions">
                <button type="submit" class="pin-btn pin-btn-primary">Update Bank Details</button>
            </div>
            <div id="pin-worker-bank-result" class="pin-ajax-result"></div>
        </form>
    </div>
</div>
