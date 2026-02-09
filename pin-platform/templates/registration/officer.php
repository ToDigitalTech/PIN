<?php
/**
 * Template: Officer Registration
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
        <h2>Officer Registration</h2>
        <p class="pin-form-desc">Register to receive your share of the tax distribution pool. You will need your badge number and government-issued ID.</p>

        <?php if ( $error ) : ?>
            <div class="pin-notice pin-notice-error"><?php echo esc_html( $error ); ?></div>
        <?php endif; ?>

        <form method="post" class="pin-form" id="pin-officer-form">
            <?php wp_nonce_field( 'pin_register_officer', 'pin_register_nonce' ); ?>
            <input type="hidden" name="pin_registration_action" value="officer">

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

            <h3>Identification</h3>
            <div class="pin-form-row">
                <div class="pin-form-group">
                    <label for="badge_number">Badge Number <span class="required">*</span></label>
                    <input type="text" id="badge_number" name="badge_number" required>
                    <span class="pin-form-hint">Your official badge/service number</span>
                </div>
                <div class="pin-form-group">
                    <label for="id_number">Government ID Number <span class="required">*</span></label>
                    <input type="text" id="id_number" name="id_number" required>
                    <span class="pin-form-hint">NIN or other government-issued ID number</span>
                </div>
            </div>

            <h3>Bank Details</h3>
            <p class="pin-form-hint">Required to receive distributions. Ensure details are accurate.</p>
            <div class="pin-form-row">
                <div class="pin-form-group">
                    <label for="bank_name">Bank Name <span class="required">*</span></label>
                    <select id="bank_name" name="bank_name" required>
                        <option value="">Select Bank</option>
                        <option value="Access Bank">Access Bank</option>
                        <option value="Citibank">Citibank</option>
                        <option value="Ecobank">Ecobank</option>
                        <option value="Fidelity Bank">Fidelity Bank</option>
                        <option value="First Bank">First Bank of Nigeria</option>
                        <option value="First City Monument Bank">First City Monument Bank</option>
                        <option value="Globus Bank">Globus Bank</option>
                        <option value="Guaranty Trust Bank">Guaranty Trust Bank</option>
                        <option value="Heritage Bank">Heritage Bank</option>
                        <option value="Keystone Bank">Keystone Bank</option>
                        <option value="Polaris Bank">Polaris Bank</option>
                        <option value="Providus Bank">Providus Bank</option>
                        <option value="Stanbic IBTC Bank">Stanbic IBTC Bank</option>
                        <option value="Standard Chartered">Standard Chartered</option>
                        <option value="Sterling Bank">Sterling Bank</option>
                        <option value="SunTrust Bank">SunTrust Bank</option>
                        <option value="Titan Trust Bank">Titan Trust Bank</option>
                        <option value="Union Bank">Union Bank of Nigeria</option>
                        <option value="United Bank for Africa">United Bank for Africa</option>
                        <option value="Unity Bank">Unity Bank</option>
                        <option value="Wema Bank">Wema Bank</option>
                        <option value="Zenith Bank">Zenith Bank</option>
                    </select>
                </div>
                <div class="pin-form-group">
                    <label for="bank_account">Account Number <span class="required">*</span></label>
                    <input type="text" id="bank_account" name="bank_account" required maxlength="10" pattern="[0-9]{10}">
                    <span class="pin-form-hint">10-digit NUBAN account number</span>
                </div>
            </div>

            <div class="pin-form-group">
                <label for="account_name">Account Name <span class="required">*</span></label>
                <input type="text" id="account_name" name="account_name" required>
                <span class="pin-form-hint">Name as it appears on your bank account</span>
            </div>

            <div class="pin-notice pin-notice-info">
                Your registration will be reviewed and verified by an administrator before you can receive distributions.
            </div>

            <div class="pin-form-actions">
                <button type="submit" class="pin-btn pin-btn-primary pin-btn-full">Register as Officer</button>
            </div>

            <p class="pin-form-footer">
                Already registered? <a href="<?php echo esc_url( wp_login_url( home_url( '/pin-dashboard/' ) ) ); ?>">Log in</a>
                | <a href="<?php echo esc_url( home_url( '/pin/' ) ); ?>">Back to Home</a>
            </p>
        </form>
    </div>
</div>
