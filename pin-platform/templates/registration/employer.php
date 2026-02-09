<?php
/**
 * Template: Employer Registration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$error = '';
$sid   = session_id();
if ( $sid ) {
    $error = get_transient( 'pin_register_error_' . $sid );
    if ( $error ) {
        delete_transient( 'pin_register_error_' . $sid );
    }
}
?>
<div class="pin-registration">
    <div class="pin-form-container">
        <h2>Employer Registration</h2>
        <p class="pin-form-desc">Register your company to start processing payroll through PIN Platform.</p>

        <?php if ( $error ) : ?>
            <div class="pin-notice pin-notice-error"><?php echo esc_html( $error ); ?></div>
        <?php endif; ?>

        <form method="post" class="pin-form" id="pin-employer-form">
            <?php wp_nonce_field( 'pin_register_employer', 'pin_register_nonce' ); ?>
            <input type="hidden" name="pin_registration_action" value="employer">

            <h3>Personal Details</h3>
            <div class="pin-form-row">
                <div class="pin-form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="pin-form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>

            <div class="pin-form-row">
                <div class="pin-form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="pin-form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>

            <div class="pin-form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required minlength="8">
                <span class="pin-form-hint">Minimum 8 characters</span>
            </div>

            <h3>Company Details</h3>
            <div class="pin-form-group">
                <label for="company_name">Company Name <span class="required">*</span></label>
                <input type="text" id="company_name" name="company_name" required>
            </div>

            <div class="pin-form-group">
                <label for="company_address">Company Address <span class="required">*</span></label>
                <textarea id="company_address" name="company_address" rows="3" required></textarea>
            </div>

            <div class="pin-form-row">
                <div class="pin-form-group">
                    <label for="tax_id">Tax Identification Number</label>
                    <input type="text" id="tax_id" name="tax_id">
                </div>
                <div class="pin-form-group">
                    <label for="contact_phone">Contact Phone <span class="required">*</span></label>
                    <input type="tel" id="contact_phone" name="contact_phone" required>
                </div>
            </div>

            <div class="pin-form-actions">
                <button type="submit" class="pin-btn pin-btn-primary pin-btn-full">Register as Employer</button>
            </div>

            <p class="pin-form-footer">
                Already registered? <a href="<?php echo esc_url( wp_login_url( home_url( '/pin-dashboard/' ) ) ); ?>">Log in</a>
                | <a href="<?php echo esc_url( home_url( '/pin/' ) ); ?>">Back to Home</a>
            </p>
        </form>
    </div>
</div>
