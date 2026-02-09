<?php
/**
 * Template: Officer Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user       = wp_get_current_user();
$officer_id = $user->ID;
$badge      = get_user_meta( $officer_id, 'pin_badge_number', true );
$status     = get_user_meta( $officer_id, 'pin_verification_status', true ) ?: 'pending';
$history    = PIN_Officer::get_distribution_history( $officer_id );
$total_earned = PIN_Officer::get_total_earned( $officer_id );
$bank_name    = get_user_meta( $officer_id, 'pin_bank_name', true );
$bank_account = get_user_meta( $officer_id, 'pin_bank_account', true );
$account_name = get_user_meta( $officer_id, 'pin_account_name', true );

$officer_count = PIN_Officer::get_officer_count();
$pool_balance  = PIN_Distribution::get_pool_balance();
?>
<div class="pin-dashboard pin-dashboard-officer">
    <!-- Header -->
    <div class="pin-dash-header">
        <div>
            <h1>Officer Dashboard</h1>
            <p>Badge: <?php echo esc_html( $badge ); ?> | Status:
                <span class="pin-badge pin-badge-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
            </p>
        </div>
        <div class="pin-dash-actions">
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/pin/' ) ) ); ?>" class="pin-btn pin-btn-outline pin-btn-sm">Log Out</a>
        </div>
    </div>

    <?php if ( 'pending' === $status ) : ?>
        <div class="pin-notice pin-notice-warning">
            Your account is pending verification. You will begin receiving distributions once verified by an administrator.
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="pin-dash-stats">
        <div class="pin-dash-stat-card pin-stat-highlight">
            <span class="pin-dash-stat-label">Total Earned</span>
            <span class="pin-dash-stat-value"><?php echo esc_html( PIN_Public::format_currency( $total_earned ) ); ?></span>
        </div>
        <div class="pin-dash-stat-card">
            <span class="pin-dash-stat-label">Current Pool Balance</span>
            <span class="pin-dash-stat-value"><?php echo esc_html( PIN_Public::format_currency( $pool_balance ) ); ?></span>
        </div>
        <div class="pin-dash-stat-card">
            <span class="pin-dash-stat-label">Registered Officers</span>
            <span class="pin-dash-stat-value"><?php echo esc_html( number_format( $officer_count ) ); ?></span>
        </div>
        <div class="pin-dash-stat-card">
            <span class="pin-dash-stat-label">Est. Next Distribution</span>
            <span class="pin-dash-stat-value">
                <?php
                if ( $officer_count > 0 && $pool_balance > 0 ) {
                    echo esc_html( PIN_Public::format_currency( $pool_balance / $officer_count ) );
                } else {
                    echo 'Awaiting funds';
                }
                ?>
            </span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="pin-tabs">
        <button class="pin-tab active" data-tab="o-history">Distribution History</button>
        <button class="pin-tab" data-tab="o-bank">Bank Details</button>
    </div>

    <!-- Distribution History -->
    <div class="pin-tab-content active" id="tab-o-history">
        <h2>Distribution History</h2>
        <div class="pin-table-responsive">
            <table class="pin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $history ) ) : ?>
                        <tr><td colspan="3" class="pin-text-center">No distributions received yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $history as $h ) : ?>
                        <tr>
                            <td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $h->distribution_date ) ) ); ?></td>
                            <td class="pin-text-green"><strong><?php echo esc_html( PIN_Public::format_currency( $h->amount ) ); ?></strong></td>
                            <td><span class="pin-badge pin-badge-<?php echo esc_attr( $h->payment_status ); ?>"><?php echo esc_html( ucfirst( $h->payment_status ) ); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bank Details -->
    <div class="pin-tab-content" id="tab-o-bank">
        <h2>Bank Details</h2>
        <div class="pin-notice pin-notice-info">Ensure your bank details are correct to receive distributions.</div>
        <form id="pin-officer-bank-form">
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
            <div id="pin-officer-bank-result" class="pin-ajax-result"></div>
        </form>
    </div>
</div>
