<?php
/**
 * WC Admin Order
 *
 * WC Admin Order class that adds some functionality on top of general WooCommerce WC_Order.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Order class.
 */
class WC_Admin_Order extends WC_Order {

	/**
	 * Holds refund amounts and quantities for the order.
	 *
	 * @var void|array
	 */
	protected $refunded_line_items;

	/**
	 * Add filter(s) required to hook WC_Admin_Order class to substitute WC_Order.
	 */
	public static function add_filters() {
		add_filter( 'woocommerce_order_class', array( __CLASS__, 'order_class_name' ), 10, 3 );
	}

	/**
	 * Filter function to swap class WC_Order for WC_Admin_Order in cases when it's suitable.
	 *
	 * @param string $classname Name of the class to be created.
	 * @param string $order_type Type of order object to be created.
	 * @param number $order_id Order id to create.
	 *
	 * @return string
	 */
	public static function order_class_name( $classname, $order_type, $order_id ) {
		if ( 'WC_Order' === $classname ) {
			return 'WC_Admin_Order';
		} else {
			return $classname;
		}
	}

	/**
	 * Calculate shipping amount for line item/product as a total shipping amount ratio based on quantity.
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return float|int
	 */
	public function get_item_shipping_amount( $item ) {
		// Shipping amount loosely based on woocommerce code in includes/admin/meta-boxes/views/html-order-item(s).php
		// distributed simply based on number of line items.
		$product_qty = $item->get_quantity( 'edit' );
		$order_items = $this->get_item_count();
		if ( 0 === $order_items ) {
			return 0;
		}

		$total_shipping_amount = $this->get_shipping_total();

		return $total_shipping_amount / $order_items * $product_qty;
	}

	/**
	 * Calculate shipping tax amount for line item/product as a total shipping tax amount ratio based on quantity.
	 *
	 * Loosely based on code in includes/admin/meta-boxes/views/html-order-item(s).php.
	 *
	 * @todo If WC is currently not tax enabled, but it was before (or vice versa), would this work correctly?
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return float|int
	 */
	public function get_item_shipping_tax_amount( $item ) {
		$order_items = $this->get_item_count();
		if ( 0 === $order_items ) {
			return 0;
		}

		$product_qty               = $item->get_quantity( 'edit' );
		$order_taxes               = $this->get_taxes();
		$line_items_shipping       = $this->get_items( 'shipping' );
		$total_shipping_tax_amount = 0;
		foreach ( $line_items_shipping as $item_id => $shipping_item ) {
			$tax_data = $shipping_item->get_taxes();
			if ( $tax_data ) {
				foreach ( $order_taxes as $tax_item ) {
					$tax_item_id                = $tax_item->get_rate_id();
					$tax_item_total             = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : 0;
					$total_shipping_tax_amount += $tax_item_total;
				}
			}
		}
		return $total_shipping_tax_amount / $order_items * $product_qty;
	}

	/**
	 * Calculates coupon amount for specified line item/product.
	 *
	 * Coupon calculation based on woocommerce code in includes/admin/meta-boxes/views/html-order-item.php.
	 *
	 * @param WC_Order_Item $item Line item from order.
	 *
	 * @return float
	 */
	public function get_item_coupon_amount( $item ) {
		return floatval( $item->get_subtotal( 'edit' ) - $item->get_total( 'edit' ) );
	}

	/**
	 * Get the customer ID used for reports in the customer lookup table.
	 *
	 * @return int
	 */
	public function get_report_customer_id() {
		return WC_Admin_Reports_Customers_Data_Store::get_or_create_customer_from_order( $this );
	}

	/**
	 * Returns true if the customer has made an earlier order.
	 *
	 * @return bool
	 */
	public function is_returning_customer() {
		return WC_Admin_Reports_Orders_Stats_Data_Store::is_returning_customer( $this );
	}
}
