<?php
/**
 * Template: Public Transparency Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$stats   = PIN_Distribution::get_transparency_stats();
$recent  = PIN_Distribution::get_recent_distributions( 10 );
$monthly = PIN_Distribution::get_monthly_stats( 12 );
?>
<div class="pin-transparency">
    <div class="pin-transparency-header">
        <h1>Transparency Dashboard</h1>
        <p>All data is public. No personal information is displayed. Every naira is accounted for.</p>
    </div>

    <!-- Key Metrics -->
    <div class="pin-stats-grid pin-stats-large">
        <div class="pin-stat-item">
            <span class="pin-stat-value"><?php echo esc_html( '₦' . number_format( $stats['taxes_this_month'], 0 ) ); ?></span>
            <span class="pin-stat-label">Taxes Collected This Month</span>
        </div>
        <div class="pin-stat-item">
            <span class="pin-stat-value"><?php echo esc_html( number_format( $stats['officer_count'] ) ); ?></span>
            <span class="pin-stat-label">Registered Officers</span>
        </div>
        <div class="pin-stat-item">
            <span class="pin-stat-value"><?php echo esc_html( number_format( $stats['employer_count'] ) ); ?></span>
            <span class="pin-stat-label">Employers Participating</span>
        </div>
        <div class="pin-stat-item">
            <span class="pin-stat-value"><?php echo esc_html( number_format( $stats['worker_count'] ) ); ?></span>
            <span class="pin-stat-label">Workers Enrolled</span>
        </div>
    </div>

    <!-- All-Time Stats -->
    <div class="pin-section">
        <h2>All-Time Impact</h2>
        <div class="pin-stats-grid">
            <div class="pin-stat-item">
                <span class="pin-stat-value"><?php echo esc_html( '₦' . number_format( $stats['total_taxes'], 0 ) ); ?></span>
                <span class="pin-stat-label">Total Taxes Collected</span>
            </div>
            <div class="pin-stat-item">
                <span class="pin-stat-value"><?php echo esc_html( '₦' . number_format( $stats['total_distributed'], 0 ) ); ?></span>
                <span class="pin-stat-label">Total Distributed to Officers</span>
            </div>
            <div class="pin-stat-item">
                <span class="pin-stat-value"><?php echo esc_html( number_format( $stats['officers_paid_month'] ) ); ?></span>
                <span class="pin-stat-label">Officers Paid This Month</span>
            </div>
            <div class="pin-stat-item">
                <span class="pin-stat-value"><?php echo esc_html( '₦' . number_format( $stats['avg_payment'], 0 ) ); ?></span>
                <span class="pin-stat-label">Average Per Officer</span>
            </div>
        </div>
    </div>

    <!-- Pool Balance -->
    <div class="pin-section">
        <div class="pin-pool-card">
            <h2>Current Tax Pool</h2>
            <span class="pin-pool-amount"><?php echo esc_html( '₦' . number_format( $stats['total_taxes'] - $stats['total_distributed'], 0 ) ); ?></span>
            <p>Awaiting next distribution to <?php echo esc_html( number_format( $stats['officer_count'] ) ); ?> officers</p>
        </div>
    </div>

    <!-- Distribution Feed -->
    <div class="pin-section">
        <h2>Distribution History</h2>
        <?php if ( empty( $recent ) ) : ?>
            <p class="pin-text-center pin-text-muted">No distributions have been made yet. Distributions happen monthly when the tax pool has funds.</p>
        <?php else : ?>
            <div class="pin-feed">
                <?php foreach ( $recent as $d ) :
                    $per = $d->officer_count > 0 ? $d->total_amount / $d->officer_count : 0;
                ?>
                <div class="pin-feed-item">
                    <div class="pin-feed-dot"></div>
                    <div class="pin-feed-content">
                        <strong><?php echo esc_html( '₦' . number_format( $d->total_amount, 0 ) ); ?></strong>
                        distributed to
                        <strong><?php echo esc_html( number_format( $d->officer_count ) ); ?></strong> officers
                        (<?php echo esc_html( '₦' . number_format( $per, 0 ) ); ?> each)
                        <span class="pin-feed-date"><?php echo esc_html( wp_date( 'F j, Y', strtotime( $d->distribution_date ) ) ); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Growth -->
    <?php if ( ! empty( $monthly ) ) : ?>
    <div class="pin-section">
        <h2>Monthly Growth</h2>
        <div class="pin-table-responsive">
            <table class="pin-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Officers Paid</th>
                        <th>Total Distributed</th>
                        <th>Per Officer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $monthly as $m ) :
                        $per = $m->officer_count > 0 ? $m->total_amount / $m->officer_count : 0;
                    ?>
                    <tr>
                        <td><?php echo esc_html( $m->month ); ?></td>
                        <td><?php echo esc_html( number_format( $m->officer_count ) ); ?></td>
                        <td><?php echo esc_html( '₦' . number_format( $m->total_amount, 0 ) ); ?></td>
                        <td><?php echo esc_html( '₦' . number_format( $per, 0 ) ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA -->
    <div class="pin-section pin-text-center">
        <h2>Be Part of the Solution</h2>
        <p>Join the platform and help ensure every officer gets paid fairly.</p>
        <div class="pin-hero-ctas">
            <a href="<?php echo esc_url( home_url( '/pin-register-employer/' ) ); ?>" class="pin-btn pin-btn-primary">Register as Employer</a>
            <a href="<?php echo esc_url( home_url( '/pin-register-officer/' ) ); ?>" class="pin-btn pin-btn-accent">Register as Officer</a>
        </div>
    </div>
</div>
