<?php
/**
 * WooCommerce integration for PIN Platform.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PIN_WooCommerce {

    /**
     * Initialize WooCommerce hooks.
     */
    public static function init() {
        add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'on_order_completed' ) );
        add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'on_order_completed' ) );
        add_filter( 'woocommerce_currency', array( __CLASS__, 'set_currency' ) );
    }

    /**
     * Set WooCommerce currency to NGN if configured.
     *
     * @param string $currency Current currency.
     * @return string
     */
    public static function set_currency( $currency ) {
        $pin_currency = get_option( 'pin_force_ngn', false );
        if ( $pin_currency ) {
            return 'NGN';
        }
        return $currency;
    }

    /**
     * Create a WooCommerce order for a payroll.
     *
     * @param int $payroll_id Payroll ID.
     * @return int|WP_Error Order ID or error.
     */
    public static function create_payroll_order( $payroll_id ) {
        global $wpdb;

        $payroll = PIN_Payroll::get_payroll( $payroll_id );
        if ( ! $payroll ) {
            return new WP_Error( 'invalid_payroll', 'Payroll not found.' );
        }

        $employer = get_user_by( 'ID', $payroll->employer_id );
        if ( ! $employer ) {
            return new WP_Error( 'invalid_employer', 'Employer not found.' );
        }

        $order = wc_create_order( array(
            'customer_id' => $payroll->employer_id,
            'status'      => 'pending',
        ) );

        if ( is_wp_error( $order ) ) {
            return $order;
        }

        // Add net salary as a fee item.
        $net_fee = new WC_Order_Item_Fee();
        $net_fee->set_name( sprintf( 'Worker Salaries (Net) - %s', $payroll->pay_period ) );
        $net_fee->set_amount( $payroll->total_net );
        $net_fee->set_total( $payroll->total_net );
        $order->add_item( $net_fee );

        // Add tax pool as a fee item.
        $tax_fee = new WC_Order_Item_Fee();
        $tax_fee->set_name( sprintf( 'Officer Tax Pool (25%%) - %s', $payroll->pay_period ) );
        $tax_fee->set_amount( $payroll->total_tax );
        $tax_fee->set_total( $payroll->total_tax );
        $order->add_item( $tax_fee );

        // Set order meta.
        $order->update_meta_data( '_pin_payroll_id', $payroll_id );
        $order->update_meta_data( '_pin_pay_period', $payroll->pay_period );
        $order->update_meta_data( '_pin_employer_id', $payroll->employer_id );
        $order->update_meta_data( '_pin_worker_count', $payroll->worker_count );

        // Set billing details.
        $company_name = get_user_meta( $payroll->employer_id, 'pin_company_name', true );
        $order->set_billing_first_name( $employer->first_name );
        $order->set_billing_last_name( $employer->last_name );
        $order->set_billing_email( $employer->user_email );
        $order->set_billing_company( $company_name );

        $order->calculate_totals();
        $order->save();

        // Update payroll with order ID and set to processing.
        $wpdb->update(
            $wpdb->prefix . 'pin_payrolls',
            array(
                'wc_order_id' => $order->get_id(),
                'status'      => 'processing',
            ),
            array( 'id' => $payroll_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );

        // Add order to cart/session for checkout.
        WC()->cart->empty_cart();
        // Store order ID for redirect.
        WC()->session->set( 'pin_pending_order', $order->get_id() );

        return $order->get_id();
    }

    /**
     * Handle order completion - finalize payroll.
     *
     * @param int $order_id WooCommerce order ID.
     */
    public static function on_order_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $payroll_id = $order->get_meta( '_pin_payroll_id' );
        if ( ! $payroll_id ) {
            return;
        }

        // Complete the payroll.
        PIN_Payroll::complete_payroll( (int) $payroll_id );

        // Add order note.
        $order->add_order_note(
            sprintf( 'PIN Payroll #%d completed. Tax added to officer distribution pool.', $payroll_id )
        );
    }

    /**
     * Get the checkout URL for a specific order.
     *
     * @param int $order_id WooCommerce order ID.
     * @return string
     */
    public static function get_order_pay_url( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return wc_get_checkout_url();
        }
        return $order->get_checkout_payment_url();
    }
}
