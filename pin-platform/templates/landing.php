<?php
/**
 * Template: Landing Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$stats = PIN_Distribution::get_transparency_stats();
?>
<div class="pin-landing">
    <!-- Hero Section -->
    <section class="pin-hero">
        <div class="pin-hero-content">
            <h1>Direct Salary Distribution</h1>
            <p class="pin-hero-subtitle">No Corruption. No Middlemen. Every Naira Accounted For.</p>
            <p class="pin-hero-desc">
                Employers pay workers directly. A compulsory 25% tax automatically funds officer salaries.
                Transparent. Secure. Fair.
            </p>
            <div class="pin-hero-ctas">
                <a href="<?php echo esc_url( home_url( '/pin-register-employer/' ) ); ?>" class="pin-btn pin-btn-primary">I'm an Employer</a>
                <a href="<?php echo esc_url( home_url( '/pin-register-worker/' ) ); ?>" class="pin-btn pin-btn-secondary">I'm a Worker</a>
                <a href="<?php echo esc_url( home_url( '/pin-register-officer/' ) ); ?>" class="pin-btn pin-btn-accent">I'm an Officer</a>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="pin-section pin-how-it-works">
        <h2>How It Works</h2>
        <div class="pin-steps">
            <div class="pin-step">
                <div class="pin-step-number">1</div>
                <h3>Employer Pays Salary</h3>
                <p>Companies register and add workers to their payroll. Process monthly payments through our secure platform.</p>
            </div>
            <div class="pin-step">
                <div class="pin-step-number">2</div>
                <h3>Tax Auto-Deducted</h3>
                <p>25% compulsory tax is automatically calculated and separated. Workers receive 75% net salary directly.</p>
            </div>
            <div class="pin-step">
                <div class="pin-step-number">3</div>
                <h3>Officers Get Paid</h3>
                <p>Tax pool is distributed equally to all registered officers. Every distribution is tracked and transparent.</p>
            </div>
        </div>
    </section>

    <!-- Live Stats -->
    <section class="pin-section pin-live-stats">
        <h2>Platform Impact</h2>
        <div class="pin-stats-grid">
            <div class="pin-stat-item">
                <span class="pin-stat-value"><?php echo esc_html( number_format( $stats['employer_count'] ) ); ?></span>
                <span class="pin-stat-label">Employers Participating</span>
            </div>
            <div class="pin-stat-item">
                <span class="pin-stat-value"><?php echo esc_html( number_format( $stats['worker_count'] ) ); ?></span>
                <span class="pin-stat-label">Workers Enrolled</span>
            </div>
            <div class="pin-stat-item">
                <span class="pin-stat-value"><?php echo esc_html( number_format( $stats['officer_count'] ) ); ?></span>
                <span class="pin-stat-label">Officers Registered</span>
            </div>
            <div class="pin-stat-item">
                <span class="pin-stat-value"><?php echo esc_html( '₦' . number_format( $stats['total_distributed'], 0 ) ); ?></span>
                <span class="pin-stat-label">Total Distributed to Officers</span>
            </div>
        </div>
        <div class="pin-text-center" style="margin-top: 2rem;">
            <a href="<?php echo esc_url( home_url( '/pin-transparency/' ) ); ?>" class="pin-btn pin-btn-outline">View Full Transparency Dashboard</a>
        </div>
    </section>

    <!-- Payment Flow Diagram -->
    <section class="pin-section pin-flow-section">
        <h2>Payment Flow</h2>
        <div class="pin-flow">
            <div class="pin-flow-item">
                <div class="pin-flow-icon">💼</div>
                <strong>Gross Salary</strong>
                <span>₦100,000</span>
            </div>
            <div class="pin-flow-arrow">→</div>
            <div class="pin-flow-item">
                <div class="pin-flow-icon">📊</div>
                <strong>Tax (25%)</strong>
                <span>₦25,000 → Officer Pool</span>
            </div>
            <div class="pin-flow-arrow">→</div>
            <div class="pin-flow-item">
                <div class="pin-flow-icon">👤</div>
                <strong>Net to Worker</strong>
                <span>₦75,000</span>
            </div>
        </div>
    </section>

    <!-- Already registered? -->
    <section class="pin-section pin-text-center">
        <h2>Already Registered?</h2>
        <p>
            <a href="<?php echo esc_url( wp_login_url( home_url( '/pin-dashboard/' ) ) ); ?>" class="pin-btn pin-btn-primary">Log In to Dashboard</a>
        </p>
    </section>
</div>
