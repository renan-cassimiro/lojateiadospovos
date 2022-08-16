<?php
/**
 * Subscription Functions
 *
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWSBS_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Implements helper functions for YITH WooCommerce Subscription
 *
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */
if ( ! function_exists( 'ywsbs_get_price_per_string' ) ) {
	/**
	 * Return the recurring period string.
	 *
	 * @param int    $price_per Subscription recurring quantity.
	 * @param string $time_option Subscription recurring.
	 * @param bool   $show_one_number Option to show or not the number 1 before the time period.
	 *
	 * @return int
	 * @since 1.0.0
	 */
	function ywsbs_get_price_per_string( $price_per, $time_option, $show_one_number = false ) {

		$price_html = ( ( 1 == $price_per && ! $show_one_number ) ? '' : $price_per ) . ' '; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison

		switch ( $time_option ) {
			case 'days':
				$price_html .= esc_html( _n( 'day', 'days', $price_per, 'yith-woocommerce-subscription' ) );
				break;
			case 'weeks':
				$price_html .= esc_html( _n( 'week', 'weeks', $price_per, 'yith-woocommerce-subscription' ) );
				break;
			case 'months':
				$price_html .= esc_html( _n( 'month', 'months', $price_per, 'yith-woocommerce-subscription' ) );
				break;
			case 'years':
				$price_html .= esc_html( _n( 'year', 'years', $price_per, 'yith-woocommerce-subscription' ) );
				break;
			default:
		}

		return $price_html;
	}
}

if ( ! function_exists( 'ywsbs_get_time_options' ) ) {

	/**
	 * Return the list of time options to add in product editor panel
	 *
	 * @return array
	 * @since 1.0.0
	 */
	function ywsbs_get_time_options() {
		$options = array(
			'days'   => __( 'days', 'yith-woocommerce-subscription' ),
			'months' => __( 'months', 'yith-woocommerce-subscription' ),
		);

		return apply_filters( 'ywsbs_time_options', $options );
	}
}

if ( ! function_exists( 'ywsbs_get_price_time_option_paypal' ) ) {
	/**
	 * Return the symbol used by PayPal Standard Payment for time options.
	 *
	 * @param string $time_option Time option.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function ywsbs_get_price_time_option_paypal( $time_option ) {
		$options = array(
			'days'   => 'D',
			'months' => 'M',
		);

		return isset( $options[ $time_option ] ) ? $options[ $time_option ] : '';
	}
}

if ( ! function_exists( 'yith_ywsbs_locate_template' ) ) {
	/**
	 * Locate the templates and return the path of the file found
	 *
	 * @param string $path .
	 * @param array  $var .
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function yith_ywsbs_locate_template( $path, $var = null ) {

		global $woocommerce;

		if ( function_exists( 'WC' ) ) {
			$woocommerce_base = WC()->template_path();
		} elseif ( defined( 'WC_TEMPLATE_PATH' ) ) {
			$woocommerce_base = WC_TEMPLATE_PATH;
		} else {
			$woocommerce_base = $woocommerce->plugin_path() . '/templates/';
		}

		$template_woocommerce_path = $woocommerce_base . $path;
		$template_path             = '/' . $path;
		$plugin_path               = YITH_YWSBS_DIR . 'templates/' . $path;

		$located = locate_template(
			array(
				$template_woocommerce_path,
				$template_path,
				$plugin_path,
			)
		);

		if ( ! $located && file_exists( $plugin_path ) ) {
			return apply_filters( 'yith_ywsbs_locate_template', $plugin_path, $path );
		}

		return apply_filters( 'yith_ywsbs_locate_template', $located, $path );
	}
}

if ( ! function_exists( 'ywsbs_get_timestamp_from_option' ) ) {

	/**
	 * Add a date to a timestamp
	 *
	 * @param int    $time_from Start time.
	 * @param int    $qty Quantity.
	 * @param string $time_opt Time options.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function ywsbs_get_timestamp_from_option( $time_from, $qty, $time_opt ) {

		$timestamp = 0;
		switch ( $time_opt ) {
			case 'days':
				$timestamp = ywsbs_add_date( $time_from, intval( $qty ) );
				break;
			case 'weeks':
				$timestamp = ywsbs_add_date( $time_from, intval( $qty ) * 7 );
				break;
			case 'months':
				$timestamp = ywsbs_add_date( $time_from, 0, intval( $qty ) );
				break;
			case 'years':
				$timestamp = ywsbs_add_date( $time_from, 0, 0, intval( $qty ) );
				break;
			default:
		}

		return $timestamp;
	}
}

if ( ! function_exists( 'ywsbs_get_paypal_limit_options' ) ) {

	/**
	 * Return the list of time options with the max value that paypal accept
	 *
	 * @return array
	 * @since 1.0.0
	 */
	function ywsbs_get_paypal_limit_options() {
		$options = array(
			'days'   => 90,
			'months' => 24,
		);

		return apply_filters( 'ywsbs_paypal_limit_options', $options );
	}
}

if ( ! function_exists( 'ywsbs_get_max_length_period' ) ) {

	/**
	 * Return the max length of period that can be accepted from paypal
	 *
	 * @return string
	 * @internal param int $time_from
	 * @internal param int $qty
	 * @since    1.0.0
	 */
	function ywsbs_get_max_length_period() {

		$max_length = array(
			'days'   => 90,
			'weeks'  => 52,
			'months' => 24,
			'years'  => 5,
		);

		return apply_filters( 'ywsbs_get_max_length_period', $max_length );

	}
}



if ( ! function_exists( 'ywsbs_validate_max_length' ) ) {

	/**
	 * Return the max length of period that can be accepted from paypal
	 *
	 * @param int    $max_length Max length.
	 * @param string $time_opt Time opt.
	 *
	 * @return int
	 * @since    1.0.0
	 */
	function ywsbs_validate_max_length( $max_length, $time_opt ) {

		$max_lengths = ywsbs_get_max_length_period();
		$max_length  = ( $max_length > $max_lengths[ $time_opt ] ) ? $max_lengths[ $time_opt ] : $max_length;

		return $max_length;
	}
}

if ( ! function_exists( 'ywsbs_add_date' ) ) {

	/**
	 * Add day, months or year to a date.
	 *
	 * @param int $given_date Date start.
	 * @param int $day Day.
	 * @param int $mth Month.
	 * @param int $yr Year.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function ywsbs_add_date( $given_date, $day = 0, $mth = 0, $yr = 0 ) {
		$new_date = $given_date;
		$new_date = strtotime( '+' . $day . ' days', $new_date );
		$new_date = strtotime( '+' . $mth . ' month', $new_date );
		$new_date = strtotime( '+' . $yr . ' year', $new_date );
		return $new_date;
	}
}


if ( ! function_exists( 'yith_check_privacy_enabled' ) ) {

	/**
	 * Check if the tool for export and erase personal data are enabled.
	 *
	 * @param      bool $wc Tell if WooCommerce privacy is needed.
	 * @return     bool
	 * @deprecated 2.0.0
	 * @since      1.0.0
	 */
	function yith_check_privacy_enabled( $wc = false ) {
		global $wp_version;
		$enabled = $wc ? version_compare( WC()->version, '3.4.0', '>=' ) && version_compare( $wp_version, '4.9.5', '>' ) : version_compare( $wp_version, '4.9.5', '>' );
		return apply_filters( 'yith_check_privacy_enabled', $enabled, $wc );
	}
}

if ( ! function_exists( 'ywsbs_get_subscription' ) ) {

	/**
	 * Return the subscription object
	 *
	 * @param int $subscription_id Subscription id.
	 *
	 * @return YWSBS_Subscription
	 * @since 1.0.0
	 */
	function ywsbs_get_subscription( $subscription_id ) {
		return new YWSBS_Subscription( $subscription_id );
	}
}

if ( ! function_exists( 'ywsbs_get_status' ) ) {

	/**
	 * Return the list of status available
	 *
	 * @return array
	 * @since 1.0.0
	 */
	function ywsbs_get_status() {
		$options = array(
			'active'    => __( 'active', 'yith-woocommerce-subscription' ),
			'paused'    => __( 'paused', 'yith-woocommerce-subscription' ),
			'pending'   => __( 'pending', 'yith-woocommerce-subscription' ),
			'overdue'   => __( 'overdue', 'yith-woocommerce-subscription' ),
			'trial'     => __( 'trial', 'yith-woocommerce-subscription' ),
			'cancelled' => __( 'cancelled', 'yith-woocommerce-subscription' ),
			'expired'   => __( 'expired', 'yith-woocommerce-subscription' ),
			'suspended' => __( 'suspended', 'yith-woocommerce-subscription' ),
		);

		return apply_filters( 'ywsbs_status', $options );
	}
}

if ( ! function_exists( 'ywsbs_get_max_failed_attemps_list' ) ) {

	/**
	 * Return the list of max failed attempts for each compatible gateways
	 *
	 * @return array
	 */
	function ywsbs_get_max_failed_attemps_list() {
		$arg = array(
			'paypal'      => 3,
			'yith-stripe' => 4,
		);

		return apply_filters( 'ywsbs_max_failed_attemps_list', $arg );
	}
}

if ( ! function_exists( 'ywsbs_get_num_of_days_between_attemps' ) ) {

	/**
	 * Return the list of max failed attemps for each compatible gateways
	 *
	 * @return array
	 */
	function ywsbs_get_num_of_days_between_attemps() {
		$arg = array(
			'paypal'      => 5,
			'yith-stripe' => 5,
		);

		return apply_filters( 'ywsbs_get_num_of_days_between_attemps', $arg );
	}
}

if ( ! function_exists( 'ywsbs_is_an_order_with_subscription' ) ) {
	/**
	 * Checks if in the order there's a subscription product
	 * returns false if is not an order with subscription or
	 * returns the type of subscription order ( parent|renew )
	 *
	 * @param mixed $order Order.
	 *
	 * @return string|bool
	 * @since 1.2.0
	 */
	function ywsbs_is_an_order_with_subscription( $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$order_subscription_type = false;
		$subscriptions           = yit_get_prop( $order, 'subscriptions' );
		$is_renew                = yit_get_prop( $order, 'is_renew' );

		if ( $subscriptions ) {
			$order_subscription_type = empty( $is_renew ) ? 'parent' : 'renew';
		}

		return $order_subscription_type;

	}
}


if ( ! function_exists( 'ywsbs_coupon_is_valid' ) ) {

	/**
	 * Check if a coupon is valid.
	 *
	 * @param WC_Coupon $coupon Coupon.
	 * @param array     $object Object.
	 *
	 * @return bool|WP_Error
	 * @throws Exception Throws an Exception.
	 */
	function ywsbs_coupon_is_valid( $coupon, $object = array() ) {
		if ( version_compare( WC()->version, '3.2.0', '>=' ) ) {
			$wc_discounts = new WC_Discounts( $object );
			$valid        = $wc_discounts->is_coupon_valid( $coupon );
			$valid        = is_wp_error( $valid ) ? false : $valid;
		} else {
			$valid = $coupon->is_valid();
		}

		return $valid;
	}
}


if ( ! function_exists( 'ywsbs_get_status_colors' ) ) {
	/**
	 * Return the list of status available with colors.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	function ywsbs_get_status_colors() {

		$status_colors = array(
			'active'    => array(
				'color'            => '#ffffff',
				'background-color' => '#b2ac00',
			),
			'paused'    => array(
				'color'            => '#ffffff',
				'background-color' => '#34495e',
			),
			'pending'   => array(
				'color'            => '#ffffff',
				'background-color' => '#d38a0b',
			),
			'overdue'   => array(
				'color'            => '#ffffff',
				'background-color' => '#d35400',
			),
			'trial'     => array(
				'color'            => '#ffffff',
				'background-color' => '#8e44ad',
			),
			'cancelled' => array(
				'color'            => '#ffffff',
				'background-color' => '#c0392b',
			),
			'expired'   => array(
				'color'            => '#ffffff',
				'background-color' => '#bdc3c7',
			),
			'suspended' => array(
				'color'            => '#ffffff',
				'background-color' => '#e74c3c',
			),
		);

		foreach ( $status_colors as $status => $value ) {
			if ( get_option( 'ywsbs_' . $status . '_subscription_status_style' ) ) {
				$status_colors[ $status ] = get_option( 'ywsbs_' . $status . '_subscription_status_style' );
			}
		}

		// APPLY_FILTER: ywsbs_status_colors: the list of status of a subscription.
		return apply_filters( 'ywsbs_status_colors', $status_colors );
	}
}

if ( ! function_exists( 'ywsbs_get_view_subscription_url' ) ) {
	/**
	 * Return the subscription detail page url
	 *
	 * @param int  $subscription_id Subscription id.
	 * @param bool $admin Admin page.
	 *
	 * @return string
	 * @since 2.0.0
	 */
	function ywsbs_get_view_subscription_url( $subscription_id, $admin = false ) {
		if ( $admin ) {
			$view_subscription_url = admin_url( 'post.php?post=' . $subscription_id . '&action=edit' );
		} else {
			$view_subscription_url = wc_get_endpoint_url( 'view-subscription', $subscription_id, wc_get_page_permalink( 'myaccount' ) );
		}

		return apply_filters( 'ywsbs_get_subscription_url', $view_subscription_url, $subscription_id, $admin );
	}
}


if ( ! function_exists( 'ywsbs_get_order_fields_to_edit' ) ) {
	/**
	 * Return the list of fields that can be edited on a subscription.
	 *
	 * @param string $type Type of fields.
	 *
	 * @return array|void
	 */
	function ywsbs_get_order_fields_to_edit( $type ) {
		$fields = array();

		if ( 'billing' === $type ) {
			// APPLY_FILTER: ywsbs_admin_billing_fields : filtering the admin billing fields.
			$fields = apply_filters(
				'ywsbs_admin_billing_fields',
				array(
					'first_name' => array(
						'label' => esc_html__( 'First name', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'last_name'  => array(
						'label' => esc_html__( 'Last name', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'company'    => array(
						'label' => esc_html__( 'Company', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'address_1'  => array(
						'label' => esc_html__( 'Address line 1', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'address_2'  => array(
						'label' => esc_html__( 'Address line 2', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'city'       => array(
						'label' => esc_html__( 'City', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'postcode'   => array(
						'label' => esc_html__( 'Postcode / ZIP', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'country'    => array(
						'label'   => esc_html__( 'Country', 'yith-woocommerce-subscription' ),
						'show'    => false,
						'class'   => 'js_field-country select short',
						'type'    => 'select',
						'options' => array( '' => esc_html__( 'Select a country&hellip;', 'yith-woocommerce-subscription' ) ) + WC()->countries->get_allowed_countries(),
					),
					'state'      => array(
						'label' => esc_html__( 'State / County', 'yith-woocommerce-subscription' ),
						'class' => 'js_field-state select short',
						'show'  => false,
					),
					'email'      => array(
						'label' => esc_html__( 'Email address', 'yith-woocommerce-subscription' ),
					),
					'phone'      => array(
						'label' => esc_html__( 'Phone', 'yith-woocommerce-subscription' ),
					),
				)
			);
		} elseif ( 'shipping' === $type ) {
			// APPLY_FILTER: ywsbs_admin_shipping_fields : filtering the admin shipping fields.
			$fields = apply_filters(
				'ywsbs_admin_shipping_fields',
				array(
					'first_name' => array(
						'label' => esc_html__( 'First name', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'last_name'  => array(
						'label' => esc_html__( 'Last name', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'company'    => array(
						'label' => esc_html__( 'Company', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'address_1'  => array(
						'label' => esc_html__( 'Address line 1', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'address_2'  => array(
						'label' => esc_html__( 'Address line 2', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'city'       => array(
						'label' => esc_html__( 'City', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'postcode'   => array(
						'label' => esc_html__( 'Postcode / ZIP', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'country'    => array(
						'label'   => esc_html__( 'Country', 'yith-woocommerce-subscription' ),
						'show'    => false,
						'type'    => 'select',
						'class'   => 'js_field-country select short',
						'options' => array( '' => esc_html__( 'Select a country&hellip;', 'yith-woocommerce-subscription' ) ) + WC()->countries->get_shipping_countries(),
					),
					'state'      => array(
						'label' => esc_html__( 'State / County', 'yith-woocommerce-subscription' ),
						'class' => 'js_field-state select short',
						'show'  => false,
					),
				)
			);
		}

		return $fields;
	}
}


if ( ! function_exists( 'ywsbs_get_status_label' ) ) {
	/**
	 * Return the readable version ot status.
	 *
	 * @param string $status Status.
	 * @return string
	 * @since 1.0.0
	 */
	function ywsbs_get_status_label( $status ) {
		$list = ywsbs_get_status();
		if ( isset( $list[ $status ] ) ) {
			$status = $list[ $status ];
		}

		return $status;
	}
}


if ( ! function_exists( 'ywsbs_get_status_label_counter' ) ) {
	/**
	 * Return the list of status for the label counter.
	 *
	 * @return array
	 * @since 2.0.0
	 */
	function ywsbs_get_status_label_counter() {
		$options = array(
			'active'    => esc_html_x( 'Active', 'Subscription filter status', 'yith-woocommerce-subscription' ),
			'paused'    => esc_html_x( 'Paused', 'Subscription filter status', 'yith-woocommerce-subscription' ),
			'pending'   => esc_html_x( 'Pending', 'Subscription filter status', 'yith-woocommerce-subscription' ),
			'overdue'   => esc_html_x( 'Overdue', 'Subscription filter status', 'yith-woocommerce-subscription' ),
			'trial'     => esc_html_x( 'Trial', 'Subscription filter status', 'yith-woocommerce-subscription' ),
			'cancelled' => esc_html_x( 'Cancelled', 'Subscription filter status', 'yith-woocommerce-subscription' ),
			'expired'   => esc_html_x( 'Expired', 'Subscription filter status', 'yith-woocommerce-subscription' ),
			'suspended' => esc_html_x( 'Suspended', 'Subscription filter status', 'yith-woocommerce-subscription' ),
		);

		// APPLY_FILTER: ywsbs_status: the list of status of a subscription.
		return apply_filters( 'ywsbs_status_label_counter', $options );
	}
}

if ( ! function_exists( 'ywsbs_get_max_failed_attempts_list' ) ) {
	/**
	 * Return the list of max failed attempts for each compatible gateways
	 *
	 * @return array
	 */
	function ywsbs_get_max_failed_attempts_list() {
		$arg = array(
			'paypal' => 3,
		);

		// APPLY_FILTER: ywsbs_max_failed_attempts_list: filtering the max failed attempts list.
		return apply_filters( 'ywsbs_max_failed_attempts_list', $arg );
	}
}


if ( ! function_exists( 'ywsbs_get_payment_gateway_by_subscription' ) ) {
	/**
	 * Get the gateway registered for the $subscription
	 *
	 * @param YWSBS_Subscription $subscription Subscription.
	 *
	 * @return WC_Payment_Gateway|bool
	 * @since 1.4.5
	 */
	function ywsbs_get_payment_gateway_by_subscription( $subscription ) {

		$payment_method = $subscription->get_payment_method();

		if ( empty( $payment_method ) ) {
			return false;
		}

		$payment_gateways = array();

		if ( WC()->payment_gateways() ) {
			foreach ( WC()->payment_gateways()->payment_gateways as $gateway ) {
				if ( 'yes' === $gateway->enabled ) {
					$payment_gateways[ $gateway->id ] = $gateway;
				}
			}
		}

		return isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ] : false;
	}
}

if ( ! function_exists( 'ywsbs_check_valid_admin_page' ) ) {
	/**
	 * Return if the current pagenow is valid for a post_type, useful if you want add metabox, scripts inside the editor of a particular post type.
	 *
	 * @param string $post_type_name Post type.
	 *
	 * @return bool
	 */
	function ywsbs_check_valid_admin_page( $post_type_name ) {
		global $pagenow;

		$post = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : ( isset( $_REQUEST['post_ID'] ) ? $_REQUEST['post_ID'] : 0 ); // phpcs:ignore
		$post = get_post( $post );

		if ( ( $post && $post->post_type === $post_type_name ) || ( 'post-new.php' === $pagenow && isset( $_REQUEST['post_type'] ) && $post_type_name === $_REQUEST['post_type'] ) ) { // phpcs:ignore
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'ywsbs_delete_cancelled_pending_enabled' ) ) {
	/**
	 * Check if the tool for export and erase personal data are enabled.
	 *
	 * @return bool
	 * @since 2.0.0
	 */
	function ywsbs_delete_cancelled_pending_enabled() {
		$delete_pending_and_cancelled = ( 'yes' === get_option( 'ywsbs_delete_personal_info', 'no' ) );
		return apply_filters( 'ywsbs_delete_cancelled_pending_enabled', $delete_pending_and_cancelled );
	}
}


if ( ! function_exists( 'ywsbs_subscription_order_type' ) ) {
	/**
	 * Return the relation between the order and the subscription
	 *
	 * @param YWSBS_Subscription $subscription Subscription Object.
	 * @param WC_Order           $order Order.
	 *
	 * @return string
	 */
	function ywsbs_subscription_order_type( $subscription, $order ) {

		$type                = '';
		$is_a_renew          = $order->get_meta( 'is_a_renew' );
		$order_subscriptions = $order->get_meta( 'subscriptions' );

		if ( (int) $subscription->get( 'order_id' ) === $order->get_id() ) {
			$type = esc_html__( 'Parent Order', 'yith-woocommerce-subscription' );
		}

		if ( $is_a_renew && in_array( $subscription->get_id(), $order_subscriptions ) ) { // phpcs:ignore
			$type = esc_html__( 'Renew Order', 'yith-woocommerce-subscription' );
		}

		return $type;
	}
}


if ( ! function_exists( 'ywsbs_get_from_list' ) ) {
	/**
	 * Return the list of who can make actions on subscription
	 *
	 * @return array
	 * @since 1.0.0
	 */
	function ywsbs_get_from_list() {
		$options = array(
			'customer'      => esc_html__( 'customer', 'yith-woocommerce-subscription' ),
			'administrator' => esc_html__( 'administrator', 'yith-woocommerce-subscription' ),
			'gateway'       => esc_html__( 'gateway', 'yith-woocommerce-subscription' ),
		);

		// APPLY_FILTER: ywsbs_from_list: the the list of who can make actions on subscription : it can be used by the gateways.
		return apply_filters( 'ywsbs_from_list', $options );
	}
}
