<?php
/**
 * Template: Employer Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user         = wp_get_current_user();
$employer_id  = $user->ID;
$company_name = get_user_meta( $employer_id, 'pin_company_name', true );
$workers      = PIN_Employer::get_workers( $employer_id );
$total_payroll = PIN_Employer::get_total_payroll( $employer_id );
$total_taxes  = PIN_Employer::get_total_taxes( $employer_id );
$payroll_history = PIN_Employer::get_payroll_history( $employer_id );
$worker_count = count( $workers );
$tax_amount   = $total_payroll * PIN_TAX_RATE;
$net_amount   = $total_payroll - $tax_amount;
?>
<div class="pin-dashboard pin-dashboard-employer">
    <!-- Header -->
    <div class="pin-dash-header">
        <div>
            <h1>Employer Dashboard</h1>
            <p class="pin-dash-company"><?php echo esc_html( $company_name ); ?></p>
        </div>
        <div class="pin-dash-actions">
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/pin/' ) ) ); ?>" class="pin-btn pin-btn-outline pin-btn-sm">Log Out</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="pin-dash-stats">
        <div class="pin-dash-stat-card">
            <span class="pin-dash-stat-label">Total Workers</span>
            <span class="pin-dash-stat-value"><?php echo esc_html( $worker_count ); ?></span>
        </div>
        <div class="pin-dash-stat-card">
            <span class="pin-dash-stat-label">Monthly Payroll</span>
            <span class="pin-dash-stat-value"><?php echo esc_html( PIN_Public::format_currency( $total_payroll ) ); ?></span>
        </div>
        <div class="pin-dash-stat-card">
            <span class="pin-dash-stat-label">Tax Portion (25%)</span>
            <span class="pin-dash-stat-value"><?php echo esc_html( PIN_Public::format_currency( $tax_amount ) ); ?></span>
        </div>
        <div class="pin-dash-stat-card">
            <span class="pin-dash-stat-label">Total Taxes Contributed</span>
            <span class="pin-dash-stat-value"><?php echo esc_html( PIN_Public::format_currency( $total_taxes ) ); ?></span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="pin-tabs">
        <button class="pin-tab active" data-tab="workers">Workers</button>
        <button class="pin-tab" data-tab="payroll">Process Payroll</button>
        <button class="pin-tab" data-tab="history">Payment History</button>
    </div>

    <!-- Workers Tab -->
    <div class="pin-tab-content active" id="tab-workers">
        <div class="pin-section-header">
            <h2>Worker Management</h2>
            <button type="button" class="pin-btn pin-btn-primary pin-btn-sm" id="pin-show-add-worker">Add Worker</button>
        </div>

        <!-- Add Worker Form (hidden by default) -->
        <div class="pin-add-worker-form" id="pin-add-worker-form" style="display: none;">
            <h3>Add New Worker</h3>
            <form id="pin-worker-form-ajax">
                <div class="pin-form-row">
                    <div class="pin-form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="pin-form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>
                <div class="pin-form-row">
                    <div class="pin-form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="pin-form-group">
                        <label>Monthly Salary (₦) <span class="required">*</span></label>
                        <input type="number" name="salary" min="1000" step="100" required>
                    </div>
                </div>
                <div class="pin-form-row">
                    <div class="pin-form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name">
                    </div>
                    <div class="pin-form-group">
                        <label>Account Number</label>
                        <input type="text" name="bank_account" maxlength="10">
                    </div>
                </div>
                <div class="pin-form-group">
                    <label>Account Name</label>
                    <input type="text" name="account_name">
                </div>
                <div class="pin-form-actions">
                    <button type="submit" class="pin-btn pin-btn-primary">Add Worker</button>
                    <button type="button" class="pin-btn pin-btn-outline" id="pin-cancel-add-worker">Cancel</button>
                </div>
                <div id="pin-add-worker-result" class="pin-ajax-result"></div>
            </form>

            <hr style="margin: 1.5rem 0;">

            <h3>Bulk Upload (CSV)</h3>
            <p class="pin-form-hint">CSV format: First Name, Last Name, Email, Salary, Bank Name, Account Number, Account Name</p>
            <form id="pin-csv-upload-form" enctype="multipart/form-data">
                <div class="pin-form-row">
                    <div class="pin-form-group">
                        <input type="file" name="csv_file" accept=".csv">
                    </div>
                    <div class="pin-form-group">
                        <button type="submit" class="pin-btn pin-btn-secondary">Upload CSV</button>
                    </div>
                </div>
                <div id="pin-csv-result" class="pin-ajax-result"></div>
            </form>
        </div>

        <!-- Workers Table -->
        <div class="pin-table-responsive">
            <table class="pin-table" id="pin-workers-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Gross Salary</th>
                        <th>Tax (25%)</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $workers ) ) : ?>
                        <tr><td colspan="7" class="pin-text-center">No workers added yet. Click "Add Worker" to get started.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $workers as $w ) :
                            $salary = (float) get_user_meta( $w->ID, 'pin_monthly_salary', true );
                            $tax    = $salary * PIN_TAX_RATE;
                            $net    = $salary - $tax;
                            $status = get_user_meta( $w->ID, 'pin_worker_status', true ) ?: 'active';
                        ?>
                        <tr data-worker-id="<?php echo esc_attr( $w->ID ); ?>">
                            <td><?php echo esc_html( $w->display_name ); ?></td>
                            <td><?php echo esc_html( $w->user_email ); ?></td>
                            <td><?php echo esc_html( PIN_Public::format_currency( $salary ) ); ?></td>
                            <td><?php echo esc_html( PIN_Public::format_currency( $tax ) ); ?></td>
                            <td><?php echo esc_html( PIN_Public::format_currency( $net ) ); ?></td>
                            <td><span class="pin-badge pin-badge-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
                            <td>
                                <button class="pin-btn pin-btn-sm pin-btn-outline pin-remove-worker" data-worker-id="<?php echo esc_attr( $w->ID ); ?>">Remove</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payroll Tab -->
    <div class="pin-tab-content" id="tab-payroll">
        <h2>Process Payroll</h2>
        <?php if ( $worker_count === 0 ) : ?>
            <div class="pin-notice pin-notice-warning">You need to add workers before processing payroll.</div>
        <?php else : ?>
            <form id="pin-payroll-form">
                <div class="pin-form-group">
                    <label for="pay_period">Pay Period</label>
                    <input type="month" id="pay_period" name="pay_period" value="<?php echo esc_attr( current_time( 'Y-m' ) ); ?>" required>
                </div>

                <div class="pin-payroll-preview" id="pin-payroll-preview">
                    <h3>Payroll Summary</h3>
                    <div class="pin-summary-grid">
                        <div class="pin-summary-item">
                            <span class="pin-summary-label">Active Workers</span>
                            <span class="pin-summary-value" id="preview-workers">-</span>
                        </div>
                        <div class="pin-summary-item">
                            <span class="pin-summary-label">Total Gross</span>
                            <span class="pin-summary-value" id="preview-gross">-</span>
                        </div>
                        <div class="pin-summary-item">
                            <span class="pin-summary-label">Tax to Officer Pool (25%)</span>
                            <span class="pin-summary-value" id="preview-tax">-</span>
                        </div>
                        <div class="pin-summary-item">
                            <span class="pin-summary-label">Net to Workers</span>
                            <span class="pin-summary-value" id="preview-net">-</span>
                        </div>
                        <div class="pin-summary-item pin-summary-total">
                            <span class="pin-summary-label">Total Due</span>
                            <span class="pin-summary-value" id="preview-total">-</span>
                        </div>
                    </div>
                    <button type="button" class="pin-btn pin-btn-primary" id="pin-load-preview">Load Preview</button>
                </div>

                <div class="pin-form-actions">
                    <button type="submit" class="pin-btn pin-btn-primary pin-btn-lg" id="pin-process-payroll-btn" disabled>
                        Process Payroll & Pay
                    </button>
                </div>
                <div id="pin-payroll-result" class="pin-ajax-result"></div>
            </form>
        <?php endif; ?>
    </div>

    <!-- History Tab -->
    <div class="pin-tab-content" id="tab-history">
        <h2>Payment History</h2>
        <div class="pin-table-responsive">
            <table class="pin-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Workers</th>
                        <th>Gross</th>
                        <th>Tax</th>
                        <th>Net</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $payroll_history ) ) : ?>
                        <tr><td colspan="7" class="pin-text-center">No payrolls processed yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $payroll_history as $p ) : ?>
                        <tr>
                            <td><?php echo esc_html( $p->pay_period ); ?></td>
                            <td><?php echo esc_html( $p->worker_count ); ?></td>
                            <td><?php echo esc_html( PIN_Public::format_currency( $p->total_gross ) ); ?></td>
                            <td><?php echo esc_html( PIN_Public::format_currency( $p->total_tax ) ); ?></td>
                            <td><?php echo esc_html( PIN_Public::format_currency( $p->total_net ) ); ?></td>
                            <td><span class="pin-badge pin-badge-<?php echo esc_attr( $p->status ); ?>"><?php echo esc_html( ucfirst( $p->status ) ); ?></span></td>
                            <td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $p->created_at ) ) ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
