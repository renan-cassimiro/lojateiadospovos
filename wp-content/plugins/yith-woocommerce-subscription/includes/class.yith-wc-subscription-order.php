<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements YWSBS_Subscription_Order Class
 *
 * @class   YWSBS_Subscription_Order
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWSBS_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'YWSBS_Subscription_Order' ) ) {

	/**
	 * Class YWSBS_Subscription_Order
	 */
	class YWSBS_Subscription_Order {

		/**
		 * Single instance of the class
		 *
		 * @var YWSBS_Subscription_Order
		 */
		protected static $instance;

		/**
		 * Post type name
		 *
		 * @var string
		 */
		public $post_type_name = 'ywsbs_subscription';

		/**
		 * Subscription meta
		 *
		 * @var array
		 */
		public $subscription_meta = array();

		/**
		 * Returns single instance of the class
		 *
		 * @return YWSBS_Subscription_Order
		 * @since  1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		public function __construct() {

			if ( version_compare( WC()->version, '2.7.0', '>=' ) ) {
				add_action( 'woocommerce_new_order_item', array( $this, 'add_subscription_order_item_meta' ), 20, 3 );
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'get_extra_subscription_meta' ), 10, 2 );
			} else {
				add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_subscription_order_item_meta_before_wc3' ), 20, 3 );
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'get_extra_subscription_meta_before_wc3' ), 10, 2 );
			}

			// Add subscriptions from orders.
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'check_order_for_subscription' ), 100, 2 );

			// Start subscription after payment received.
			add_action( 'woocommerce_payment_complete', array( $this, 'payment_complete' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'payment_complete' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'payment_complete' ) );

			add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'can_reduce_order_stock' ), 10, 2 );

			if ( get_option( 'ywsbs_delete_subscription_order_cancelled', 'yes' ) === 'yes' ) {
				add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'trash_subscriptions' ), 10 );
			} else {
				add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'cancel_subscriptions' ), 10 );
			}

			if ( ywsbs_delete_cancelled_pending_enabled( true ) ) {
				add_action( 'ywsbs_trash_pending_subscriptions', array( $this, 'ywsbs_trash_pending_subscriptions' ) );
				add_action( 'ywsbs_trash_cancelled_subscriptions', array( $this, 'ywsbs_trash_cancelled_subscriptions' ) );
			}
		}

		/**
		 * Save the options of subscription in an array with order item id
		 *
		 * @access public
		 *
		 * @param int                   $item_id Order item id.
		 * @param WC_Order_Item_Product $item Order Item object.
		 * @param int                   $order_id Order id.
		 *
		 * @return void
		 */
		public function add_subscription_order_item_meta( $item_id, $item, $order_id ) {
			if ( isset( $item->legacy_cart_item_key ) ) {
				$this->cart_item_order_item[ $item->legacy_cart_item_key ] = $item_id;
			}
		}

		/**
		 * Save the options of subscription in an array with order item id
		 *
		 * @access public
		 *
		 * @param int   $item_id Item id.
		 * @param array $values Values.
		 * @param int   $cart_item_key Cart item key.
		 *
		 * @return void
		 */
		public function add_subscription_order_item_meta_before_wc3( $item_id, $values, $cart_item_key ) {
			$this->cart_item_order_item[ $cart_item_key ] = $item_id;
		}


		/**
		 * Save some info if a subscription is in the cart
		 *
		 * @access public
		 *
		 * @param int   $order_id Order id.
		 * @param array $posted Posted.
		 * @throws Exception Throws an Exception.
		 */
		public function get_extra_subscription_meta( $order_id, $posted ) {

			if ( ! YITH_WC_Subscription()->cart_has_subscriptions() ) {
				return;
			}

			$this->actual_cart = WC()->session->get( 'cart' );

			add_filter( 'ywsbs_price_check', '__return_false' );

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

				$product = $cart_item['data'];
				$id      = $product->get_id();

				if ( YITH_WC_Subscription()->is_subscription( $product ) ) {

					$new_cart = new WC_Cart();

					$subscription_info = array(
						'shipping' => array(),
						'taxes'    => array(),
					);

					if ( isset( $cart_item['variation'] ) ) {
						$subscription_info['variation'] = $cart_item['variation'];
					}

					$new_cart_item_key = $new_cart->add_to_cart(
						$cart_item['product_id'],
						$cart_item['quantity'],
						( isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : '' ),
						( isset( $cart_item['variation'] ) ? $cart_item['variation'] : '' ),
						$cart_item
					);

					$new_cart = apply_filters( 'ywsbs_add_cart_item_data', $new_cart, $new_cart_item_key, $cart_item );

					$new_cart_item_keys = array_keys( $new_cart->cart_contents );

					$applied_coupons = WC()->cart->get_applied_coupons();

					foreach ( $new_cart_item_keys as $new_cart_item_key ) {
						$save_temp_session_values = array();

						// shipping.
						if ( $new_cart->needs_shipping() && $product->needs_shipping() ) {
							if ( method_exists( WC()->shipping, 'get_packages' ) ) {
								$packages = WC()->shipping->get_packages();

								foreach ( $packages as $key => $package ) {
									if ( isset( $package['rates'][ $posted['shipping_method'][ $key ] ] ) ) {
										if ( isset( $package['contents'][ $cart_item_key ] ) || isset( $package['contents'][ $new_cart_item_key ] ) ) {
											// This shipping method has the current subscription.
											$shipping['method']      = $posted['shipping_method'][ $key ];
											$shipping['destination'] = $package['destination'];

											break;
										}
									}
								}

								if ( isset( $shipping ) ) {
									// Get packages based on renewal order details.
									$new_packages = apply_filters(
										'woocommerce_cart_shipping_packages',
										array(
											0 => array(
												'contents' => $new_cart->get_cart(),
												'contents_cost' => isset( $new_cart->cart_contents[ $new_cart_item_key ]['line_total'] ) ? $new_cart->cart_contents[ $new_cart_item_key ]['line_total'] : 0,
												'applied_coupons' => $new_cart->applied_coupons,
												'destination' => $shipping['destination'],
											),
										)
									);

									// subscription_shipping_method_temp.
									$save_temp_session_values = array(
										'shipping_method_counts'  => WC()->session->get( 'shipping_method_counts' ),
										'chosen_shipping_methods' => WC()->session->get( 'chosen_shipping_methods' ),
									);

									WC()->session->set( 'shipping_method_counts', array( 1 ) );
									WC()->session->set( 'chosen_shipping_methods', array( $shipping['method'] ) );

									add_filter( 'woocommerce_shipping_chosen_method', array( $this, 'change_shipping_chosen_method_temp' ) );
									$this->subscription_shipping_method_temp = $shipping['method'];

									WC()->shipping->calculate_shipping( $new_packages );

									remove_filter( 'woocommerce_shipping_chosen_method', array( $this, 'change_shipping_chosen_method_temp' ) );

									unset( $this->subscription_shipping_method_temp );
								}
							}
						}

						foreach ( $applied_coupons as $coupon_code ) {
							$coupon        = new WC_Coupon( $coupon_code );
							$coupon_type   = $coupon->get_discount_type();
							$coupon_amount = $coupon->get_amount();
							$valid         = ywsbs_coupon_is_valid( $coupon, WC()->cart );
							if ( $valid && in_array( $coupon_type, array( 'recurring_percent', 'recurring_fixed' ), true ) ) {

								$price               = $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal'];
								$price_tax           = $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal_tax'];
								$discount_amount     = 0;
								$discount_amount_tax = 0;
								switch ( $coupon_type ) {
									case 'recurring_percent':
										$discount_amount     = round( ( $price / 100 ) * $coupon_amount, WC()->cart->dp );
										$discount_amount_tax = round( ( $price_tax / 100 ) * $coupon_amount, WC()->cart->dp );
										break;
									case 'recurring_fixed':
										$discount_amount     = ( $price < $coupon_amount ) ? $price : $coupon_type;
										$discount_amount_tax = 0;
										break;
								}

								$subscription_info['coupons'][] = array(
									'coupon_code'         => $coupon_code,
									'discount_amount'     => $discount_amount * $cart_item['quantity'],
									'discount_amount_tax' => $discount_amount_tax * $cart_item['quantity'],
								);

								$new_cart->applied_coupons[]   = $coupon_code;
								$new_cart->coupon_subscription = true;

							}
						}

						if ( ! empty( $new_cart->applied_coupons ) ) {
							WC()->cart->discount_cart       = 0;
							WC()->cart->discount_cart_tax   = 0;
							WC()->cart->subscription_coupon = 1;
						}

						$new_cart->calculate_totals();

						// Recalculate totals.
						// save some order settings.
						$subscription_info['order_shipping']     = wc_format_decimal( $new_cart->shipping_total );
						$subscription_info['order_shipping_tax'] = wc_format_decimal( $new_cart->shipping_tax_total );
						$subscription_info['cart_discount']      = wc_format_decimal( $new_cart->get_cart_discount_total() );
						$subscription_info['cart_discount_tax']  = wc_format_decimal( $new_cart->get_cart_discount_tax_total() );
						$subscription_info['order_discount']     = $new_cart->get_total_discount();
						$subscription_info['order_tax']          = wc_format_decimal( $new_cart->tax_total );
						$subscription_info['order_subtotal']     = wc_format_decimal( $new_cart->subtotal, get_option( 'woocommerce_price_num_decimals' ) );
						$subscription_info['order_total']        = wc_format_decimal( $new_cart->total, get_option( 'woocommerce_price_num_decimals' ) );
						$subscription_info['line_subtotal']      = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal'] );
						$subscription_info['line_subtotal_tax']  = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal_tax'] );
						$subscription_info['line_total']         = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_total'] );
						$subscription_info['line_tax']           = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_tax'] );
						$subscription_info['line_tax_data']      = $new_cart->cart_contents[ $new_cart_item_key ]['line_tax_data'];
					}
					// Get shipping details.
					if ( $product->needs_shipping() ) {

						if ( isset( $shipping['method'] ) && isset( WC()->shipping->packages[0]['rates'][ $shipping['method'] ] ) ) {

							$method                        = WC()->shipping->packages[0]['rates'][ $shipping['method'] ];
							$subscription_info['shipping'] = array(
								'name'      => $method->label,
								'method_id' => $method->id,
								'cost'      => wc_format_decimal( $method->cost ),
								'taxes'     => $method->taxes,
							);

							// Set session variables to original values and recalculate shipping for original order which is being processed now.
							WC()->session->set( 'shipping_method_counts', $save_temp_session_values['shipping_method_counts'] );
							WC()->session->set( 'chosen_shipping_methods', $save_temp_session_values['chosen_shipping_methods'] );
							WC()->shipping->calculate_shipping( WC()->shipping->packages );
						}
					}

					// CALCULATE TAXES.
					$taxes          = $new_cart->get_cart_contents_taxes();
					$shipping_taxes = $new_cart->get_shipping_taxes();

					foreach ( $new_cart->get_tax_totals() as $rate_key => $rate ) {

						$rate_args = array(
							'name'     => $rate_key,
							'rate_id'  => $rate->tax_rate_id,
							'label'    => $rate->label,
							'compound' => absint( $rate->is_compound ? 1 : 0 ),

						);

						if ( version_compare( WC()->version, '3.2.0', '>=' ) ) {
							$rate_args['tax_amount']          = wc_format_decimal( isset( $taxes[ $rate->tax_rate_id ] ) ? $taxes[ $rate->tax_rate_id ] : 0 );
							$rate_args['shipping_tax_amount'] = wc_format_decimal( isset( $shipping_taxes[ $rate->tax_rate_id ] ) ? $shipping_taxes[ $rate->tax_rate_id ] : 0 );
						} else {
							$rate_args['tax_amount']          = wc_format_decimal( isset( $new_cart->taxes[ $rate->tax_rate_id ] ) ? $new_cart->taxes[ $rate->tax_rate_id ] : 0 );
							$rate_args['shipping_tax_amount'] = wc_format_decimal( isset( $new_cart->shipping_taxes[ $rate->tax_rate_id ] ) ? $new_cart->shipping_taxes[ $rate->tax_rate_id ] : 0 );
						}

						$subscription_info['taxes'][] = $rate_args;
					}

					$subscription_info['payment_method']       = '';
					$subscription_info['payment_method_title'] = '';
					if ( isset( $posted['payment_method'] ) && $posted['payment_method'] ) {
						$enabled_gateways = WC()->payment_gateways->get_available_payment_gateways();

						if ( isset( $enabled_gateways[ $posted['payment_method'] ] ) ) {
							$payment_method = $enabled_gateways[ $posted['payment_method'] ];
							$payment_method->validate_fields();
							$subscription_info['payment_method']       = $payment_method->id;
							$subscription_info['payment_method_title'] = $payment_method->get_title();
						}
					}

					if ( isset( $this->cart_item_order_item[ $cart_item_key ] ) ) {
						$order_item_id                                       = $this->cart_item_order_item[ $cart_item_key ];
						$this->subscriptions_info['order'][ $order_item_id ] = $subscription_info;
						wc_add_order_item_meta( $order_item_id, '_subscription_info', $subscription_info, true );
					}
				}
			}

			WC()->session->set( 'cart', $this->actual_cart );
		}

		/**
		 * Save some info if a subscription is in the cart
		 *
		 * @access public
		 *
		 * @param int   $order_id Order id.
		 * @param array $posted Posted.
		 *
		 * @throws Exception Throws an Exception.
		 */
		public function get_extra_subscription_meta_before_wc3( $order_id, $posted ) {

			if ( ! YITH_WC_Subscription()->cart_has_subscriptions() ) {
				return;
			}

			$this->actual_cart = WC()->cart;

			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

				$id      = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
				$product = wc_get_product( $id );

				if ( YITH_WC_Subscription()->is_subscription( $id ) ) {

					$new_cart = new WC_Cart();

					$subscription_info = array(
						'shipping' => array(),
						'taxes'    => array(),
					);

					if ( isset( $cart_item['variation'] ) ) {
						$subscription_info['variation'] = $cart_item['variation'];
					}

					$new_cart_item_key = $new_cart->add_to_cart(
						$cart_item['product_id'],
						$cart_item['quantity'],
						( isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : '' ),
						( isset( $cart_item['variation'] ) ? $cart_item['variation'] : '' ),
						$cart_item
					);

					$new_cart = apply_filters( 'ywsbs_add_cart_item_data', $new_cart, $new_cart_item_key, $cart_item );

					$new_cart_item_keys = array_keys( $new_cart->cart_contents );

					$applied_coupons = WC()->cart->get_applied_coupons();

					foreach ( $new_cart_item_keys as $new_cart_item_key ) {
						// shipping.
						if ( $new_cart->needs_shipping() && $product->needs_shipping() ) {
							if ( method_exists( WC()->shipping, 'get_packages' ) ) {
								$packages = WC()->shipping->get_packages();
								foreach ( $packages as $key => $package ) {
									if ( isset( $package['rates'][ $posted['shipping_method'][ $key ] ] ) ) {
										if ( isset( $package['contents'][ $cart_item_key ] ) || isset( $package['contents'][ $new_cart_item_key ] ) ) {
											// This shipping method has the current subscription.

											$shipping['method']      = $posted['shipping_method'][ $key ];
											$shipping['destination'] = $package['destination'];

											break;
										}
									}
								}

								if ( ! isset( $shipping ) ) {
									continue;
								}

								// Get packages based on renewal order details.
								$new_packages = apply_filters(
									'woocommerce_cart_shipping_packages',
									array(
										0 => array(
											'contents'    => $new_cart->get_cart(),
											'contents_cost' => isset( $new_cart->cart_contents[ $new_cart_item_key ]['line_total'] ) ? $new_cart->cart_contents[ $new_cart_item_key ]['line_total'] : 0,
											'applied_coupons' => $new_cart->applied_coupons,
											'destination' => $shipping['destination'],
										),
									)
								);

								// subscription_shipping_method_temp.
								$save_temp_session_values = array(
									'shipping_method_counts'  => WC()->session->get( 'shipping_method_counts' ),
									'chosen_shipping_methods' => WC()->session->get( 'chosen_shipping_methods' ),
								);

								WC()->session->set( 'shipping_method_counts', array( 1 ) );
								WC()->session->set( 'chosen_shipping_methods', array( $shipping['method'] ) );

								add_filter(
									'woocommerce_shipping_chosen_method',
									array(
										$this,
										'change_shipping_chosen_method_temp',
									)
								);
								$this->subscription_shipping_method_temp = $shipping['method'];

								WC()->shipping->calculate_shipping( $new_packages );

								remove_filter(
									'woocommerce_shipping_chosen_method',
									array(
										$this,
										'change_shipping_chosen_method_temp',
									)
								);

								unset( $this->subscription_shipping_method_temp );
							}
						}
						foreach ( $applied_coupons as $coupon_code ) {
							$coupon = new WC_Coupon( $coupon_code );
							$valid  = ywsbs_coupon_is_valid( $coupon, WC()->cart );
							if ( $valid && in_array(
								$coupon->discount_type,
								array(
									'recurring_percent',
									'recurring_fixed',
								),
								true
							)
							) {

								$price     = $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal'];
								$price_tax = $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal_tax'];
								switch ( $coupon->discount_type ) {
									case 'recurring_percent':
										$discount_amount     = round( ( $price / 100 ) * $coupon->amount, WC()->cart->dp );
										$discount_amount_tax = round( ( $price_tax / 100 ) * $coupon->amount, WC()->cart->dp );
										break;
									case 'recurring_fixed':
										$discount_amount     = ( $price < $coupon->amount ) ? $price : $coupon->amount;
										$discount_amount_tax = 0;
										break;
								}

								$subscription_info['coupons'][] = array(
									'coupon_code'         => $coupon_code,
									'discount_amount'     => $discount_amount * $cart_item['quantity'],
									'discount_amount_tax' => $discount_amount_tax * $cart_item['quantity'],
								);
								$new_cart->applied_coupons[]    = $coupon_code;
								$new_cart->coupon_subscription  = true;
							}
						}

						if ( ! empty( $new_cart->applied_coupons ) ) {
							WC()->cart->discount_cart       = 0;
							WC()->cart->discount_cart_tax   = 0;
							WC()->cart->subscription_coupon = 1;
						}

						$new_cart->calculate_totals();

						// Recalculate totals.
						// save some order settings.
						$subscription_info['order_shipping']     = wc_format_decimal( $new_cart->shipping_total );
						$subscription_info['order_shipping_tax'] = wc_format_decimal( $new_cart->shipping_tax_total );
						$subscription_info['cart_discount']      = wc_format_decimal( $new_cart->get_cart_discount_total() );
						$subscription_info['cart_discount_tax']  = wc_format_decimal( $new_cart->get_cart_discount_tax_total() );
						$subscription_info['order_discount']     = $new_cart->get_total_discount();
						$subscription_info['order_tax']          = wc_format_decimal( $new_cart->tax_total );
						$subscription_info['order_subtotal']     = wc_format_decimal( $new_cart->subtotal, get_option( 'woocommerce_price_num_decimals' ) );
						$subscription_info['order_total']        = wc_format_decimal( $new_cart->total, get_option( 'woocommerce_price_num_decimals' ) );
						$subscription_info['line_subtotal']      = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal'] );
						$subscription_info['line_subtotal_tax']  = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal_tax'] );
						$subscription_info['line_total']         = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_total'] );
						$subscription_info['line_tax']           = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_tax'] );
						$subscription_info['line_tax_data']      = $new_cart->cart_contents[ $new_cart_item_key ]['line_tax_data'];

					}

					// Get shipping details.
					if ( $product->needs_shipping() ) {

						if ( isset( $shipping['method'] ) && isset( WC()->shipping->packages[0]['rates'][ $shipping['method'] ] ) ) {

							$method                        = WC()->shipping->packages[0]['rates'][ $shipping['method'] ];
							$subscription_info['shipping'] = array(
								'name'      => $method->label,
								'method_id' => $method->id,
								'cost'      => wc_format_decimal( $method->cost ),
								'taxes'     => $method->taxes,
							);

							// Set session variables to original values and recalculate shipping for original order which is being processed now.
							WC()->session->set( 'shipping_method_counts', $save_temp_session_values['shipping_method_counts'] );
							WC()->session->set( 'chosen_shipping_methods', $save_temp_session_values['chosen_shipping_methods'] );
							WC()->shipping->calculate_shipping( WC()->shipping->packages );
						}
					}

					// CALCULATE TAXES.
					foreach ( $new_cart->get_tax_totals() as $rate_key => $rate ) {
						$subscription_info['taxes'][] = array(
							'name'                => $rate_key,
							'rate_id'             => $rate->tax_rate_id,
							'label'               => $rate->label,
							'compound'            => absint( $rate->is_compound ? 1 : 0 ),
							'tax_amount'          => wc_format_decimal( isset( $new_cart->taxes[ $rate->tax_rate_id ] ) ? $new_cart->taxes[ $rate->tax_rate_id ] : 0 ),
							'shipping_tax_amount' => wc_format_decimal( isset( $new_cart->shipping_taxes[ $rate->tax_rate_id ] ) ? $new_cart->shipping_taxes[ $rate->tax_rate_id ] : 0 ),
						);
					}

					$subscription_info['payment_method']       = '';
					$subscription_info['payment_method_title'] = '';
					if ( isset( $posted['payment_method'] ) && $posted['payment_method'] ) {
						$enabled_gateways = WC()->payment_gateways->get_available_payment_gateways();

						if ( isset( $enabled_gateways[ $posted['payment_method'] ] ) ) {
							$payment_method = $enabled_gateways[ $posted['payment_method'] ];
							$payment_method->validate_fields();
							$subscription_info['payment_method']       = $payment_method->id;
							$subscription_info['payment_method_title'] = $payment_method->get_title();
						}
					}

					if ( isset( $this->cart_item_order_item[ $cart_item_key ] ) ) {
						$order_item_id                                       = $this->cart_item_order_item[ $cart_item_key ];
						$this->subscriptions_info['order'][ $order_item_id ] = $subscription_info;
						wc_add_order_item_meta( $order_item_id, '_subscription_info', $subscription_info, true );
					}
				}
			}

			WC()->session->set( 'cart', $this->actual_cart );
		}

		/**
		 * Check in the order if there's a subscription and create it
		 *
		 * @param int   $order_id Order ID.
		 * @param array $posted $_POST variable.
		 *
		 * @return void
		 * @throws Exception Trigger an error.
		 */
		public function check_order_for_subscription( $order_id, $posted ) {

			$order          = wc_get_order( $order_id );
			$order_items    = $order->get_items();
			$order_args     = array();
			$user_id        = method_exists( $order, 'get_customer_id' ) ? $order->get_customer_id() : yit_get_prop( $order, '_customer_user', true );
			$order_currency = method_exists( $order, 'get_currency' ) ? $order->get_currency() : yit_get_prop( $order, '_order_currency' );
			// check id the the subscriptions are created.
			$subscriptions = yit_get_prop( $order, 'subscriptions', true );

			if ( empty( $order_items ) || ! empty( $subscriptions ) ) {
				return;
			}

			$subscriptions = yit_get_prop( $order, 'subscriptions', true );
			$subscriptions = is_array( $subscriptions ) ? $subscriptions : array();

			foreach ( $order_items as $key => $order_item ) {

				if ( version_compare( WC()->version, '3.0.0', '>=' ) ) {
					$_product = $order_item->get_product();
				} else {
					$_product = $order->get_product_from_item( $order_item );
				}

				if ( false === $_product ) {
					continue;
				}

				$id = $_product->get_id();

				$args = array();

				if ( YITH_WC_Subscription()->is_subscription( $id ) ) {

					if ( ! isset( $this->subscriptions_info['order'][ $key ] ) ) {
						continue;
					}

					$subscription_info = $this->subscriptions_info['order'][ $key ];

					$max_length        = yit_get_prop( $_product, '_ywsbs_max_length' );
					$price_is_per      = yit_get_prop( $_product, '_ywsbs_price_is_per' );
					$price_time_option = yit_get_prop( $_product, '_ywsbs_price_time_option' );
					$fee               = yit_get_prop( $_product, '_ywsbs_fee' );
					$duration          = ( empty( $max_length ) ) ? '' : ywsbs_get_timestamp_from_option( 0, $max_length, $price_time_option );

					// DOWNGRADE PROCESS.
					// Set a trial period for the new downgrade subscription so the next payment will be due at the expiration date of the previous subscription.
					if ( get_user_meta( get_current_user_id(), 'ywsbs_trial_' . $id, true ) !== '' ) {
						$trial_info        = get_user_meta( get_current_user_id(), 'ywsbs_trial_' . $id, true );
						$trial_period      = isset ( $trial_info['trial_days'] ) ?  $trial_info['trial_days'] : 0;

						$trial_time_option = 'days';
					} else {
						$trial_period      = yit_get_prop( $_product, '_ywsbs_trial_per' );
						$trial_time_option = yit_get_prop( $_product, '_ywsbs_trial_time_option' );
					}

					// if this subscription is a downgrade the old subscription will be cancelled.
					$subscription_to_update_id = get_user_meta( get_current_user_id(), 'ywsbs_downgrade_' . $id, true );
					if ( '' !== $subscription_to_update_id ) {
						$args_cancel_subscription = array(
							'subscription_to_cancel' => $subscription_to_update_id,
							'process_type'           => 'downgrade',
							'product_id'             => $id,
							'user_id'                => get_current_user_id(),
						);

						$order_args['_ywsbs_subscritpion_to_cancel'] = $args_cancel_subscription;
					}

					// UPGRADE PROCESS
					// if the we are in the upgrade process and the prorate must be done.
					$subscription_old_id       = '';
					$pay_gap                   = '';
					$prorate_length            = yit_get_prop( $_product, '_ywsbs_prorate_length' );
					$gap_payment               = yit_get_prop( $_product, '_ywsbs_gap_payment' );
					$subscription_upgrade_info = get_user_meta( get_current_user_id(), 'ywsbs_upgrade_' . $id, true );

					if ( ! empty( $subscription_upgrade_info ) ) {
						$subscription_old_id = $subscription_upgrade_info['subscription_id'];
						$pay_gap             = $subscription_upgrade_info['pay_gap'];
						$trial_period        = '';

						// if this subscription is an upgrade the old subscription will be cancelled.
						if ( '' !== $subscription_old_id ) {
							$args_cancel_subscription = array(
								'subscription_to_cancel' => $subscription_old_id,
								'process_type'           => 'upgrade',
								'product_id'             => $id,
								'user_id'                => get_current_user_id(),
							);

							$order_args['_ywsbs_subscritpion_to_cancel'] = $args_cancel_subscription;
						}
					}

					if ( 'yes' === $gap_payment && $pay_gap > 0 ) {
						// change the fee of the subscription adding the total amount of the previous rates.
						$fee = $pay_gap;
					}
					// fill the array for subscription creation.
					$args = array(
						'product_id'              => $order_item['product_id'],
						'variation_id'            => $order_item['variation_id'],
						'variation'               => ( isset( $subscription_info['variation'] ) ? $subscription_info['variation'] : '' ),
						'product_name'            => $order_item['name'],

						// order details.
						'order_id'                => $order_id,
						'order_item_id'           => $key,
						'order_ids'               => array( $order_id ),
						'line_subtotal'           => $subscription_info['line_subtotal'],
						'line_total'              => $subscription_info['line_total'],
						'line_subtotal_tax'       => $subscription_info['line_subtotal_tax'],
						'line_tax'                => $subscription_info['line_tax'],
						'line_tax_data'           => $subscription_info['line_tax_data'],
						'cart_discount'           => $subscription_info['cart_discount'],
						'cart_discount_tax'       => $subscription_info['cart_discount_tax'],
						'coupons'                 => ( isset( $subscription_info['coupons'] ) ) ? $subscription_info['coupons'] : '',
						'order_total'             => $subscription_info['order_total'],
						'subscription_total'      => $subscription_info['order_total'],
						'order_tax'               => $subscription_info['order_tax'],
						'order_subtotal'          => $subscription_info['order_subtotal'],
						'order_discount'          => $subscription_info['order_discount'],
						'order_shipping'          => $subscription_info['order_shipping'],
						'order_shipping_tax'      => $subscription_info['order_shipping_tax'],
						'subscriptions_shippings' => $subscription_info['shipping'],
						'payment_method'          => $subscription_info['payment_method'],
						'payment_method_title'    => $subscription_info['payment_method_title'],
						'order_currency'          => $order_currency,
						'prices_include_tax'      => yit_get_prop( $order, '_prices_include_tax' ),
						// user details.
						'quantity'                => $order_item['qty'],
						'user_id'                 => $user_id,
						'customer_ip_address'     => yit_get_prop( $order, '_customer_ip_address' ),
						'customer_user_agent'     => yit_get_prop( $order, '_customer_user_agent' ),
						// item subscription detail.
						'price_is_per'            => $price_is_per,
						'price_time_option'       => $price_time_option,
						'max_length'              => $max_length,
						'trial_per'               => $trial_period,
						'trial_time_option'       => $trial_time_option,
						'fee'                     => $fee,
						'num_of_rates'            => ( $max_length && $price_is_per ) ? $max_length / $price_is_per : '',
					);

					$subscription = new YWSBS_Subscription( '', $args );
					YWSBS_Subscription_User::delete_user_cache( $user_id );
					// save the version of plugin in the order.
					$order_args['_ywsbs_order_version'] = YITH_YWSBS_VERSION;

					if ( $subscription->id ) {
						$subscriptions[]             = $subscription->id;
						$order_args['subscriptions'] = $subscriptions;
						// translators: placeholder subscription id.
						$order->add_order_note( sprintf( __( 'A new subscription #%d has been created from this order', 'yith-woocommerce-subscription' ), $subscription->id ) );

						$product_id = ( $subscription->variation_id ) ? $subscription->variation_id : $subscription->product_id;
						delete_user_meta( $subscription->user_id, 'ywsbs_trial_' . $product_id );
					}
				}
			}

			if ( $order_args ) {
				yit_save_prop( $order, $order_args, false, true );
			}
		}



		/**
		 * After payment complete
		 *
		 * @param int $order_id Order id.
		 */
		public function payment_complete( $order_id ) {
			$order         = wc_get_order( $order_id );
			$subscriptions = $order->get_meta( 'subscriptions' );
			if ( ! empty( $subscriptions ) ) {
				foreach ( $subscriptions as $subscription_id ) {
					$subscription = ywsbs_get_subscription( $subscription_id );
					$renew_order  = $subscription->renew_order;
					if ( 0 !== $renew_order && $renew_order == $order_id ) { //phpcs:ignore
						$subscription->update_subscription( $order_id );
					} elseif ( empty($renew_order) ) {  //phpcs:ignore
						$subscription->start_subscription( $order_id );
					}
				}
			}
		}

		/**
		 * Create the renew order
		 *
		 * @param int $subscription_id Subscription id.
		 * @return false|int|null
		 * @throws WC_Data_Exception Throws an Exception.
		 */
		public function renew_order( $subscription_id ) {

			$subscription   = new YWSBS_Subscription( $subscription_id );
			$status         = $this->get_renew_order_status( $subscription );
			$renew_order_id = $subscription->can_be_create_a_renew_order();

			if ( $renew_order_id && is_int( $renew_order_id ) ) {
				return $renew_order_id;
			} elseif ( false === $renew_order_id ) {
				return false;
			}

			if ( apply_filters( 'ywsbs_skip_create_renew_order', false, $subscription ) ) {
				return false;
			}

			$or_status = version_compare( WC()->version, '2.7.0', '>=' ) ? 'renew' : $status;

			$args = array(
				'status'      => $or_status,
				'customer_id' => $subscription->user_id,
			);

			$order = wc_create_order( $args );

			$args = array(
				'subscriptions'  => array( $subscription_id ),
				'payment_method' => $subscription->payment_method,
				'order_currency' => $subscription->order_currency,
			);

			if ( method_exists( $order, 'set_customer_note' ) ) {
				$parent_order          = wc_get_order( $subscription->order_id );
				$customer_note         = yit_get_prop( $parent_order, 'customer_note' );
				$args['customer_note'] = $customer_note;
			}

			// get billing.
			$billing_fields = $subscription->get_address_fields( 'billing' );
			// get shipping.
			$shipping_fields = $subscription->get_address_fields( 'shipping' );

			$args = array_merge( $args, $shipping_fields, $billing_fields );

			if ( version_compare( WC()->version, '2.7.0', '>=' ) ) {

				foreach ( $billing_fields as $key => $field ) {
					$set = 'set_' . $key;
					method_exists( $order, $set ) && $order->$set( $field );
				}

				foreach ( $shipping_fields as $key => $field ) {
					$set = 'set_' . $key;
					method_exists( $order, $set ) && $order->$set( $field );
				}

				yit_set_prop( $order, $args );

			}

			$order_id = $order->get_id();

			foreach ( $args as $key => $value ) {
				if ( 'subscriptions' === $key ) {
					add_post_meta( $order_id, $key, $value );
				}
				update_post_meta( $order_id, '_' . $key, $value );
			}

			$_product = wc_get_product( ( isset( $subscription->variation_id ) && ! empty( $subscription->variation_id ) ) ? $subscription->variation_id : $subscription->product_id );

			$total     = 0;
			$tax_total = 0;

			$variations = array();

			$item_id = $order->add_product(
				$_product,
				$subscription->quantity,
				array(
					'variation' => $variations,
					'totals'    => array(
						'subtotal'     => $subscription->line_subtotal,
						'subtotal_tax' => $subscription->line_subtotal_tax,
						'total'        => $subscription->line_total,
						'tax'          => $subscription->line_tax,
						'tax_data'     => maybe_unserialize( $subscription->line_tax_data ),
					),
				)
			);

			if ( ! $item_id ) {
				throw new Exception( __( 'Error 402: unable to create the order. Please try again.', 'yith-woocommerce-subscription' ) );
			} else {
				$total     += floatval( $subscription->line_total );
				$tax_total += floatval( $subscription->line_tax );
				$metadata   = get_metadata( 'order_item', $subscription->order_item_id );

				if ( $metadata ) {
					foreach ( $metadata as $key => $value ) {
						if ( apply_filters( 'ywsbs_renew_order_item_meta_data', is_array( $value ) && count( $value ) === 1, $subscription->order_item_id, $key, $value ) ) {
							add_metadata( 'order_item', $item_id, $key, maybe_unserialize( $value[0] ), true );
						}
					}
				}
			}

			$shipping_cost = 0;

			// Shipping.
			if ( ! empty( $subscription->subscriptions_shippings ) ) {

				$shipping_item_id = wc_add_order_item(
					$order_id,
					array(
						'order_item_name' => $subscription->subscriptions_shippings['name'],
						'order_item_type' => 'shipping',
					)
				);

				$shipping_cost     = $subscription->subscriptions_shippings['cost'];
				$shipping_cost_tax = 0;

				wc_add_order_item_meta( $shipping_item_id, 'method_id', $subscription->subscriptions_shippings['method_id'] );
				wc_add_order_item_meta( $shipping_item_id, 'cost', wc_format_decimal( $shipping_cost ) );
				wc_add_order_item_meta( $shipping_item_id, 'taxes', $subscription->subscriptions_shippings['taxes'] );

				if ( ! empty( $subscription->subscriptions_shippings['taxes'] ) ) {
					foreach ( $subscription->subscriptions_shippings['taxes'] as $tax_cost ) {
						$shipping_cost_tax += $tax_cost;
					}
				}

				if ( version_compare( WC()->version, '2.7.0', '>=' ) ) {
					$order->set_shipping_total( $shipping_cost );
					$order->set_shipping_tax( $subscription->subscriptions_shippings['taxes'] );
					$order->save();
				} else {
					$order->set_total( wc_format_decimal( $shipping_cost ), 'shipping' );
				}
			} else {
				do_action( 'ywsbs_add_custom_shipping_costs', $order, $subscription );
			}

			$cart_discount_total     = 0;
			$cart_discount_total_tax = 0;

			// coupons.
			if ( ! empty( $subscription->coupons ) ) {
				foreach ( $subscription->coupons as $coupon ) {
					$order->add_coupon( $coupon['coupon_code'], $coupon['discount_amount'], $coupon['discount_amount_tax'] );
					$cart_discount_total     += $coupon['discount_amount'];
					$cart_discount_total_tax += $coupon['discount_amount_tax'];
				}
			}
			if ( version_compare( WC()->version, '2.7.0', '>=' ) ) {
				$order->set_discount_total( $cart_discount_total );

				if ( isset( $subscription->subscriptions_shippings['taxes'] ) && $subscription->subscriptions_shippings['taxes'] ) {
					/**
					 * This fix the shipping taxes removed form WC settings
					 * if in a previous tax there was the taxes this will be forced
					 * even if they are disabled for the shipping
					 */
					add_action( 'woocommerce_find_rates', array( $this, 'add_shipping_tax' ), 10 );
				}
				$order->update_taxes();
				$order->calculate_totals();
			} else {
				$order->set_total( $cart_discount_total, 'cart_discount' );
				$order->set_total( $cart_discount_total_tax, 'cart_discount_tax' );
				$order->update_taxes();
				$totals = $order->calculate_totals();
				$order->set_total( $totals );
			}

			$order_id = yit_get_order_id( $order );
			// attach the new order to the subscription.
			$subscription->order_ids[] = $order_id;

			update_post_meta( $subscription->id, 'order_ids', $subscription->order_ids );
			// translators: placeholder subscription id.
			$order->add_order_note( sprintf( __( 'This order has been created to renew subscription #%s', 'yith-woocommerce-subscription' ), admin_url( 'post.php?post=' . $subscription->id . '&action=edit' ), $subscription->id ) );

			$subscription->set( 'renew_order', $order_id );

			yit_save_prop(
				$order,
				array(
					'status'     => $status,
					'is_a_renew' => 'yes',
				)
			);

			do_action( 'ywsbs_renew_subscription', $order_id, $subscription_id );

			return $order_id;

		}

		/**
		 * Old function
		 *
		 * @param int $subscription_id Subscription id.
		 *
		 * @return mixed
		 * @throws Exception Throws an Exception.
		 */
		public function renew_order_old( $subscription_id ) {

			$subscription      = new YWSBS_Subscription( $subscription_id );
			$subscription_meta = $subscription->get_subscription_meta();

			$order = wc_create_order(
				array(
					'status'      => 'on-hold',
					'customer_id' => $subscription_meta['user_id'],
				)
			);
			$args  = array(
				'subscriptions'       => array( $subscription_id ),
				'billing_first_name'  => $subscription_meta['billing_first_name'],
				'billing_last_name'   => $subscription_meta['billing_last_name'],
				'billing_company'     => $subscription_meta['billing_company'],
				'billing_address_1'   => $subscription_meta['billing_address_1'],
				'billing_address_2'   => $subscription_meta['billing_address_2'],
				'billing_city'        => $subscription_meta['billing_city'],
				'billing_state'       => $subscription_meta['billing_state'],
				'billing_postcode'    => $subscription_meta['billing_postcode'],
				'billing_country'     => $subscription_meta['billing_country'],
				'billing_email'       => $subscription_meta['billing_email'],
				'billing_phone'       => $subscription_meta['billing_phone'],
				'shipping_first_name' => $subscription_meta['shipping_first_name'],
				'shipping_last_name'  => $subscription_meta['shipping_last_name'],
				'shipping_company'    => $subscription_meta['shipping_company'],
				'shipping_address_1'  => $subscription_meta['shipping_address_1'],
				'shipping_address_2'  => $subscription_meta['shipping_address_2'],
				'shipping_city'       => $subscription_meta['shipping_city'],
				'shipping_state'      => $subscription_meta['shipping_state'],
				'shipping_postcode'   => $subscription_meta['shipping_postcode'],
				'shipping_country'    => $subscription_meta['shipping_country'],
			);

			foreach ( $args as $key => $value ) {
				yit_save_prop( $order, '_' . $key, $value );
			}
			$_product = wc_get_product( ( isset( $subscription_meta['variation_id'] ) && ! empty( $subscription_meta['variation_id'] ) ) ? $subscription_meta['variation_id'] : $subscription_meta['product_id'] );

			$total     = 0;
			$tax_total = 0;

			$variations = array();

			$order_id = yit_get_order_id( $order );
			$item_id  = $order->add_product(
				$_product,
				$subscription_meta['quantity'],
				array(
					'variation' => $variations,
					'totals'    => array(
						'subtotal'     => $subscription_meta['line_subtotal'],
						'subtotal_tax' => $subscription_meta['line_subtotal_tax'],
						'total'        => $subscription_meta['line_total'],
						'tax'          => $subscription_meta['line_tax'],
						'tax_data'     => maybe_unserialize( $subscription_meta['line_tax_data'] ),
					),
				)
			);

			if ( ! $item_id ) {
				throw new Exception( __( 'Error 404: unable to create the order. Please try again.', 'yith-woocommerce-subscription' ) );
			} else {
				$total     += $subscription_meta['line_total'];
				$tax_total += $subscription_meta['line_tax'];
			}

			$shipping_cost = 0;
			// Shipping.
			if ( ! empty( $subscription_meta['subscriptions_shippings'] ) ) {
				foreach ( $subscription_meta['subscriptions_shippings'] as $ship ) {
					$args             = array(
						'order_item_name' => $ship['method']->label,
						'order_item_type' => 'shipping',
					);
					$shipping_item_id = wc_add_order_item( $order_id, $args );

					$shipping_cost += $ship['method']->cost;
					wc_add_order_item_meta( $shipping_item_id, 'method_id', $ship['method']->method_id );
					wc_add_order_item_meta( $shipping_item_id, 'cost', wc_format_decimal( $ship['method']->cost ) );
					wc_add_order_item_meta( $shipping_item_id, 'taxes', $ship['method']->taxes );
				}

				if ( version_compare( WC()->version, '2.7.0', '>=' ) ) {
					$order->set_shipping_total( $shipping_cost );
					$order->set_shipping_tax( $subscription->subscriptions_shippings['taxes'] );
				} else {
					$order->set_total( wc_format_decimal( $shipping_cost ), 'shipping' );
				}
			}

			if ( version_compare( WC()->version, '2.7.0', '>=' ) ) {
				$order->calculate_taxes();
				$order->calculate_totals();
			} else {
				$order->set_total( $total + $tax_total + $shipping_cost );
				$order->update_taxes();
			}

			// attach the new order to the subscription.
			$subscription_meta['order_ids'][] = $order_id;
			$subscription->set( 'order_ids', $subscription_meta['order_ids'] );
			// translators: placeholder is the subscription id.
			$order->add_order_note( sprintf( __( 'This order has been created to renew the subscription #%d', 'yith-woocommerce-subscription' ), $subscription_id ) );

			return $order_id;

		}

		/**
		 * Get the renew order status
		 *
		 * @param YWSBS_Subscription $subscription Subscription.
		 *
		 * @return string
		 */
		public function get_renew_order_status( $subscription = null ) {

			$new_status = 'on-hold';

			if ( ! is_null( $subscription ) && 'bacs' === $subscription->payment_method ) {
				$new_status = 'pending';
			}

			// the status must be register as wc status.
			$status = apply_filters( 'ywsbs_renew_order_status', $new_status, $subscription );

			return $status;
		}

		/**
		 * This fix the shipping taxes removed form WC settings
		 * if in a previous tax there was the taxes this will be forced
		 * even if they are disabled for the shipping.
		 *
		 * @param array $shipping_taxes Shipping taxes.
		 *
		 * @return mixed
		 */
		public function add_shipping_tax( $shipping_taxes ) {

			foreach ( $shipping_taxes as &$shipping_tax ) {
				$shipping_tax['shipping'] = 'yes';

			}

			return $shipping_taxes;
		}

		/**
		 * Return false if the option reduce order stock is disabled for the renew order
		 *
		 * @param bool     $result Current filter value.
		 * @param WC_Order $order Order.
		 *
		 * @return bool
		 * @since  2.0.0
		 */
		public function can_reduce_order_stock( $result, $order ) {
			$is_a_renew = $order->get_meta( 'is_a_renew' );

			if ( 'yes' === $is_a_renew && 'yes' === get_option( 'ywsbs_disable_the_reduction_of_order_stock_in_renew' ) ) {
				$result = false;
			}

			return $result;
		}


		/**
		 * Delete all subscription if the main order in deleted.
		 *
		 * @param int $order_id Order id.
		 */
		public static function delete_subscriptions( $order_id ) {
			if ( 'shop_order' === get_post_type( $order_id ) ) {

				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					return;
				}

				$is_a_renew    = $order->get_meta( 'is_a_renew' );
				$subscriptions = $order->get_meta( 'subscriptions' );

				if ( empty( $subscriptions ) || 'yes' === $is_a_renew ) {
					return;
				}

				foreach ( $subscriptions as $subscription_id ) {
					$subscription = ywsbs_get_subscription( $subscription_id );
					// check if the subscription exists.
					if ( is_null( $subscription->post ) ) {
						continue;
					}

					$subscription->delete();
				}
			}
		}

		/**
		 * Trash all subscriptions if the main order in trashed.
		 *
		 * @param int $order_id Order id.
		 *
		 * @return void
		 */
		public static function trash_subscriptions( $order_id ) {
			if ( 'shop_order' === get_post_type( $order_id ) ) {

				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					return;
				}

				$is_a_renew    = $order->get_meta( 'is_a_renew' );
				$subscriptions = $order->get_meta( 'subscriptions' );

				if ( empty( $subscriptions ) || 'yes' === $is_a_renew ) {
					return;
				}

				foreach ( $subscriptions as $subscription_id ) {
					$subscription = ywsbs_get_subscription( $subscription_id );
					// check if the subscription exists.
					if ( is_null( $subscription->post ) ) {
						continue;
					}

					$subscription->delete();
				}
			}
		}

	}
}

/**
 * Unique access to instance of YWSBS_Subscription_Order class
 *
 * @return YWSBS_Subscription_Order
 */
function YWSBS_Subscription_Order() { //phpcs:ignore
	return YWSBS_Subscription_Order::get_instance();
}
