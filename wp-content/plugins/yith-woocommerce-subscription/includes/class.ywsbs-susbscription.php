<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements YWSBS_Subscription Class
 *
 * @class   YWSBS_Subscription
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWSBS_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'YWSBS_Subscription' ) ) {
	/**
	 * Class YWSBS_Subscription
	 */
	class YWSBS_Subscription {

		/**
		 * Subscription meta data.
		 *
		 * @var array
		 */
		protected $subscription_meta_data = array(
			'status'                  => 'pending',
			'start_date'              => '',
			'payment_due_date'        => '',
			'expired_date'            => '',
			'cancelled_date'          => '',
			'payed_order_list'        => array(),
			'product_id'              => '',
			'variation_id'            => '',
			'product_name'            => '',
			'quantity'                => '',
			'line_subtotal'           => '',
			'line_total'              => '',
			'line_subtotal_tax'       => '',
			'line_tax'                => '',
			'line_tax_data'           => '',

			'cart_discount'           => '',
			'cart_discount_tax'       => '',

			'order_total'             => '',
			'order_currency'          => '',
			'renew_order'             => 0,

			'prices_include_tax'      => '',

			'payment_method'          => '',
			'payment_method_title'    => '',

			'subscriptions_shippings' => '',

			'price_is_per'            => '',
			'price_time_option'       => '',
			'max_length'              => '',

			'order_ids'               => array(),
			'order_id'                => '',
			'user_id'                 => 0,
			'customer_ip_address'     => '',
			'customer_user_agent'     => '',

			'billing_first_name'      => '',
			'billing_last_name'       => '',
			'billing_company'         => '',
			'billing_address_1'       => '',
			'billing_address_2'       => '',
			'billing_city'            => '',
			'billing_state'           => '',
			'billing_postcode'        => '',
			'billing_country'         => '',
			'billing_email'           => '',
			'billing_phone'           => '',

			'shipping_first_name'     => '',
			'shipping_last_name'      => '',
			'shipping_company'        => '',
			'shipping_address_1'      => '',
			'shipping_address_2'      => '',
			'shipping_city'           => '',
			'shipping_state'          => '',
			'shipping_postcode'       => '',
			'shipping_country'        => '',

			'ywsbs_free_version'      => YITH_YWSBS_VERSION,
		);

		/**
		 * The subscription (post) ID.
		 *
		 * @var int
		 */
		public $id = 0;


		/**
		 * Price time option
		 *
		 * @var string
		 */
		public $price_time_option;

		/**
		 * Variation id
		 *
		 * @var int
		 */
		public $variation_id;

		/**
		 * $post Stores post data
		 *
		 * @var WP_Post $post
		 */
		public $post = null;

		/**
		 * $post Stores post data
		 *
		 * @var string
		 */
		public $status;


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @param int   $subscription_id Subscription id.
		 * @param array $args Arguments.
		 *
		 * @since  1.0.0
		 */
		public function __construct( $subscription_id = 0, $args = array() ) {
			add_action( 'init', array( $this, 'register_post_type' ) );

			// populate the subscription if $subscription_id is defined.
			if ( $subscription_id ) {
				$this->id = $subscription_id;
				$this->populate();
			}

			// add a new subscription if $args is passed.
			if ( '' === $subscription_id && ! empty( $args ) ) {
				$this->add_subscription( $args );
			}

		}

		/**
		 * __get function.
		 *
		 * @param string $key Key.
		 *
		 * @return mixed
		 */
		public function __get( $key ) {
			$value = get_post_meta( $this->id, $key, true );

			if ( ! empty( $value ) ) {
				$this->$key = $value;
			}

			return $value;
		}

		/*
		|--------------------------------------------------------------------------
		| Getters
		|--------------------------------------------------------------------------
		*/

		/**
		 * Returns the unique ID for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  1.7.2
		 */
		public function get_id( $context = 'view' ) {
			return (int) $this->get( 'id', $context );
		}


		/**
		 * Returns the number for this object.
		 *
		 * @return int
		 * @since  2.0
		 */
		public function get_number() {
			return apply_filters( 'ywsbs_get_number', '#' . $this->get_id(), $this );
		}



		/**
		 * Set function
		 *
		 * @param string $property Property to set.
		 * @param mixed  $value Value.
		 *
		 * @return bool|int
		 */
		public function set( $property, $value ) {
			$this->$property = $value;
			return update_post_meta( $this->id, $property, $value );
		}

		/**
		 * Get function
		 *
		 * @param string $property Property to retrieve.
		 * @return bool|int
		 */
		/**
		 * Get function.
		 *
		 * @param string $prop Property name.
		 * @param string $context Change this string if you want the value stored in database.
		 *
		 * @return mixed
		 */
		public function get( $prop, $context = 'view' ) {

			$value = $this->$prop;
			if ( 'view' === $context ) {
				// APPLY_FILTER : ywsbs_subscription_{$key}: filtering the post meta of a subscription.
				$value = apply_filters( 'ywsbs_subscription_' . $prop, $value, $this );
			}

			return $value;
		}

		/**
		 * Isset function
		 *
		 * @param string $key Key.
		 * @return bool
		 */
		public function __isset( $key ) {
			if ( ! $this->id ) {
				return false;
			}

			return metadata_exists( 'post', $this->id, $key );
		}

		/**
		 * Populate the subscription
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function populate() {

			$this->post = get_post( $this->id );

			foreach ( $this->get_subscription_meta() as $key => $value ) {
				$this->$key = $value;
			}

			do_action( 'ywsbs_subscription_loaded', $this );
		}

		/**
		 * Add the subscription
		 *
		 * @param array $args Arguments.
		 *
		 * @return int|WP_Error
		 */
		public function add_subscription( $args ) {

			$subscription_id = wp_insert_post(
				array(
					'post_status' => 'publish',
					'post_type'   => 'ywsbs_subscription',
				)
			);

			if ( $subscription_id ) {
				$this->id = $subscription_id;
				$meta     = apply_filters( 'ywsbs_add_subcription_args', wp_parse_args( $args, $this->get_default_meta_data() ), $this );
				$this->update_subscription_meta( $meta );
				$this->populate();
			}

			return $subscription_id;
		}

		/**
		 * Update post meta in subscription
		 *
		 * @param array $meta Array of metas to update.
		 * @since  1.0.0
		 * @return void
		 */
		public function update_subscription_meta( $meta ) {
			foreach ( $meta as $key => $value ) {
				update_post_meta( $this->id, $key, $value );
			}
		}

		/**
		 * Start the subscription
		 *
		 * @param int $order_id Order id.
		 *
		 * @internal param $subscription_id
		 */
		public function start_subscription( $order_id ) {

			$payed = $this->payed_order_list;

			// do not nothing if this subscription has payed with this order.
			if ( ! empty( $payed ) && is_array( $payed ) && in_array( $order_id, $payed ) ) { //phpcs:ignore
				return;
			}

			$payed = empty( $payed ) ? array() : $payed;

			$new_status  = 'active';
			$rates_payed = 1;
			if ( '' === $this->start_date ) {
				$this->set( 'start_date', time() );
			}

			if ( '' === $this->payment_due_date ) {
				// Change the next payment_due_date.
				$this->set( 'payment_due_date', $this->get_next_payment_due_date( 0, $this->start_date ) );
			}

			if ( '' === $this->expired_date && '' !== $this->max_length ) {
				$timestamp = ywsbs_get_timestamp_from_option( time(), $this->max_length, $this->price_time_option );
				$this->set( 'expired_date', $timestamp );
			}

			$this->set( 'status', $new_status );

			do_action( 'ywsbs_customer_subscription_payment_done_mail', $this );

			$payed[] = $order_id;

			$this->set( 'rates_payed', $rates_payed );
			$this->set( 'payed_order_list', $payed );

		}


		/**
		 * Update the subscription if a payment is done manually from user
		 * order_id is the id of the last order created
		 *
		 * @param int $order_id Order id.
		 * @since  1.0.0
		 * @return void
		 */
		public function update_subscription( $order_id ) {

			$payed = $this->payed_order_list;
			// do not nothing if this subscription has payed with this order.
			if ( ! empty( $payed ) && is_array( $payed ) && in_array( $order_id, $payed ) ) { //phpcs:ignore
				return;
			}

			// Change the status to active.
			$this->set( 'status', 'active' );

			// Change the next payment_due_date.
			$price_is_per      = $this->price_is_per;
			$price_time_option = $this->price_time_option;
			$timestamp         = ywsbs_get_timestamp_from_option( time(), $price_is_per, $price_time_option );

			$this->set( 'payment_due_date', $timestamp );
			// update _payed_order_list.
			$payed[] = $order_id;
			$this->set( 'payed_order_list', $payed );
			$this->set( 'renew_order', 0 );

		}


		/**
		 * Return the subscription meta
		 *
		 * @return array
		 * @internal param $subscription_id
		 */
		public function get_subscription_meta() {
			$subscription_meta = array();
			foreach ( $this->get_default_meta_data() as $key => $value ) {
				$meta_value                = get_post_meta( $this->id, $key, true );
				$subscription_meta[ $key ] = empty( $meta_value ) ? get_post_meta( $this->id, '_' . $key, true ) : $meta_value;
			}

			return $subscription_meta;
		}

		/**
		 * Return an array of all custom fields subscription
		 *
		 * @return array
		 * @since  1.0.0
		 */
		private function get_default_meta_data() {
			return $this->subscription_meta_data;
		}


		/**
		 * Cancel subscription
		 *
		 * @internal param $subscription_id
		 */
		public function cancel_subscription() {

			// Change the status to active.

			$this->set( 'status', 'cancelled' );
			$this->set( 'cancelled_date', date( 'Y-m-d H:i:s' ) ); //phpcs:ignore

			do_action( 'ywsbs_subscription_cancelled', $this->id );

			// if there's a pending order for this subscription change the status of the order to cancelled.
			$order_in_pending = $this->renew_order;
			if ( $order_in_pending ) {
				$order = wc_get_order( $order_in_pending );
				if ( $order ) {
					$order->update_status( 'failed' );
				}
			}

		}

		/**
		 * Returns the status for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 * @since  2.0.0
		 */
		public function get_status( $context = 'view' ) {
			return $this->get( 'status', $context );
		}

		/**
		 * Returns the user id for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  1.7.2
		 */
		public function get_user_id( $context = 'view' ) {
			return (int) $this->get( 'user_id', $context );
		}


		/**
		 * Return the next payment due date if there are rates not payed.
		 *
		 * @param int $trial_period Trial period.
		 * @param int $start_date Start date.
		 *
		 * @return bool|int
		 * @since  1.0.0
		 */
		public function get_next_payment_due_date( $trial_period = 0, $start_date = 0 ) {

			$start_date = ( $start_date ) ? $start_date : time();
			if ( '' === $this->num_of_rates || ( intval( $this->num_of_rates ) - intval( $this->rates_payed ) ) > 0 ) {
				$payment_due_date = ( '' === $this->payment_due_date ) ? $start_date : $this->payment_due_date;
				if ( 0 !== $trial_period ) {
					$timestamp = $start_date + $trial_period;
				} else {
					$timestamp = ywsbs_get_timestamp_from_option( $payment_due_date, $this->price_is_per, $this->price_time_option );
				}

				return $timestamp;
			}

			return false;

		}

		/**
		 * Returns the product name for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 * @since  2.0.0
		 */
		public function get_product_name( $context = 'view' ) {
			return $this->get( 'product_name', $context );
		}

		/**
		 * Returns the start date for this object in timestamp format
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  2.0.0
		 */
		public function get_start_date( $context = 'view' ) {
			return (int) $this->get( 'start_date', $context );
		}


		/**
		 * Returns the order_shipping for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return float
		 * @since  2.0.0
		 */
		public function get_order_shipping( $context = 'view' ) {
			return (float) $this->get( 'order_shipping', $context );
		}

		/**
		 * Returns the order_shipping_tax for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return float
		 * @since  2.0.0
		 */
		public function get_order_shipping_tax( $context = 'view' ) {
			return (float) $this->get( 'order_shipping_tax', $context );
		}

		/**
		 * Returns the payment due date for this object in timestamp format
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  2.0.0
		 */
		public function get_payment_due_date( $context = 'view' ) {
			return (int) $this->get( 'payment_due_date', $context );
		}

		/**
		 * Returns the expired date for this object in timestamp format
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  2.0.0
		 */
		public function get_expired_date( $context = 'view' ) {
			return (int) $this->get( 'expired_date', $context );
		}

		/**
		 * Returns the line_subtotal_tax for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return float
		 * @since  2.0.0
		 */
		public function get_line_subtotal( $context = 'view' ) {
			return (float) $this->get( 'line_subtotal', $context );
		}

		/**
		 * Returns the end date for this object in timestamp format
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  2.0.0
		 */
		public function get_end_date( $context = 'view' ) {
			return (int) $this->get( 'end_date', $context );
		}

		/**
		 * Returns the line_total for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return float
		 * @since  2.0.0
		 */
		public function get_line_total( $context = 'view' ) {
			return (float) $this->get( 'line_total', $context );
		}

		/**
		 * Returns the order currency for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 * @since  2.0.0
		 */
		public function get_order_currency( $context = 'view' ) {
			return $this->get( 'order_currency', $context );
		}

		/**
		 * Returns the line_subtotal_tax for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return float
		 * @since  2.0.0
		 */
		public function get_line_subtotal_tax( $context = 'view' ) {
			return (float) $this->get( 'line_subtotal_tax', $context );
		}


		/**
		 * Returns the payment method title for this object
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 * @since  2.0.0
		 */
		public function get_payment_method_title( $context = 'view' ) {
			return $this->get( 'payment_method_title', $context );
		}

		/**
		 * Returns the line_tax for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return float
		 * @since  2.0.0
		 */
		public function get_line_tax( $context = 'view' ) {
			return (float) $this->get( 'line_tax', $context );
		}

		/**
		 * Get the order object.
		 *
		 * @return WC_Order
		 */
		public function get_order() {
			$this->order = ( $this->order instanceof WC_Order ) ? $this->order : wc_get_order( $this->order_id );
			return $this->order;
		}

		/**
		 * Get billing customer email
		 *
		 * @return string
		 */
		public function get_billing_email() {
			$billing_email = ! empty( $this->_billing_email ) ? $this->_billing_email : yit_get_prop( $this->get_order(), '_billing_email' );
			return apply_filters( 'ywsbs_customer_billing_email', $billing_email, $this );
		}

		/**
		 * Get billing customer email
		 *
		 * @return string
		 */
		public function get_billing_phone() {
			$billing_phone = ! empty( $this->_billing_phone ) ? $this->_billing_phone : yit_get_prop( $this->get_order(), '_billing_phone' );
			return apply_filters( 'ywsbs_customer_billing_phone', $billing_phone, $this );
		}

		/**
		 * Get subscription customer billing or shipping fields.
		 *
		 * @param string  $type Type of information.
		 * @param boolean $no_type No type.
		 * @param string  $prefix Prefix.
		 *
		 * @return array
		 * @throws Exception Return error.
		 */
		public function get_address_fields( $type = 'billing', $no_type = false, $prefix = '' ) {

			$fields = array();

			$value_to_check = '_' . $type . '_first_name';
			if ( ! isset( $this->$value_to_check ) ) {
				$fields = $this->get_address_fields_from_order( $type, $no_type, $prefix );
			} else {
				$order = $this->get_order();
				if ( $order ) {
					$meta_fields = $order->get_address( $type );

					foreach ( $meta_fields as $key => $value ) {
						$field_name                     = '_' . $type . '_' . $key;
						$field_key                      = $no_type ? $key : $type . '_' . $key;
						$fields[ $prefix . $field_key ] = $this->$field_name;
					}
				}
			}

			return $fields;
		}

		/**
		 * Return the fields billing or shipping from the parent order
		 *
		 * @param string $type Type of information.
		 * @param bool   $no_type No type.
		 * @param string $prefix Prefix.
		 *
		 * @return array
		 */
		public function get_address_fields_from_order( $type = 'billing', $no_type = false, $prefix = '' ) {
			$fields = array();
			$order  = $this->get_order();

			if ( $order ) {
				$meta_fields = $order->get_address( $type );

				if ( is_array( $meta_fields ) ) {
					foreach ( $meta_fields as $key => $value ) {
						$field_key                      = $no_type ? $key : $type . '_' . $key;
						$fields[ $prefix . $field_key ] = $value;
					}
				}
			}

			return $fields;
		}

		/**
		 * Return if the subscription can be cancelled by user
		 *
		 * @return  bool
		 * @since   1.0.0
		 */
		public function can_be_cancelled() {
			$status = array( 'pending', 'cancelled' );

			// the administrator and shop manager can switch the status to cancelled.
			$post_type_object = get_post_type_object( YITH_WC_Subscription()->post_name );
			if ( current_user_can( $post_type_object->cap->delete_post, $this->ID ) ) {
				$return = true;
			} elseif ( ! in_array( $this->status, $status, true ) && get_option( 'ywsbs_allow_customer_cancel_subscription' ) === 'yes' ) {
				$return = true;
			} else {
				$return = false;
			}

			return apply_filters( 'ywsbs_can_be_cancelled', $return, $this );
		}

		/**
		 * Return if the subscription can be reactivate by user
		 *
		 * @return  bool
		 * @since   1.0.0
		 */
		public function can_be_create_a_renew_order() {
			$status = array( 'pending', 'expired' );

			// exit if no valid subscription status.
			if ( in_array( $this->status, $status, true ) || $this->payment_due_date === $this->expired_date ) {
				return false;
			}

			// check if the subscription have a renew order.
			$renew_order = $this->has_a_renew_order();

			// if order doesn't exist, or is cancelled, we create order.
			if ( ! $renew_order || ( $renew_order->get_status() === 'cancelled' ) ) {
				$result = true;
			} else {
				$result = yit_get_order_id( $renew_order );
			}

			return apply_filters( 'ywsbs_can_be_create_a_renew_order', $result, $this );
		}

		/**
		 * Returns the payed_order_list for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 * @since  2.0.0
		 */
		public function get_paid_order_list( $context = 'view' ) {
			$paid_order_list = $this->get( 'payed_order_list', $context );
			return empty( $paid_order_list ) ? array() : (array) $paid_order_list;
		}


		/**
		 * Get billing customer first name
		 *
		 * @return string
		 * @throws Exception Throws an exception.
		 */
		public function get_billing_first_name() {

			$billing_fields     = $this->get_address_fields();
			$billing_first_name = isset( $billing_fields['billing_first_name'] ) ? $billing_fields['billing_first_name'] : '';

			return apply_filters( 'ywsbs_customer_billing_first_name', $billing_first_name, $this );
		}

		/**
		 * Return if the subscription can be editable
		 *
		 * @param string $key Field editable.
		 * @return bool
		 * @since  1.0.0
		 */
		public function can_be_editable( $key ) {
			$is_editable = false;
			$status      = array( 'cancelled', 'expired' );
			$gateway     = ywsbs_get_payment_gateway_by_subscription( $this );

			if ( ! $this->has_status( $status ) ) {
				if ( $gateway ) {
					if ( ! $gateway->supports( 'yith_subscriptions' ) && 'yes' === get_option( 'ywsbs_enable_manual_renews' ) ) {
						$is_editable = true;
					} else {
						switch ( $key ) {
							case 'payment_date':
								$is_editable = $gateway->supports( 'yith_subscriptions_payment_date' );
								break;
							case 'recurring_amount':
								$is_editable = $gateway->supports( 'yith_subscriptions_recurring_amount' );
								break;
							default:
						}
					}
				} else {
					$is_editable = true;
				}
			}

			return apply_filters( 'ywsbs_subscription_is_editable', $is_editable, $key, $this );
		}

		/**
		 * Get method of payment.
		 *
		 * @return mixed|string
		 */
		public function get_payment_method() {
			return apply_filters( 'ywsbs_get_payment_method', $this->get( 'payment_method', 'edit' ), $this );
		}




		/**
		 * Get billing customer last name
		 *
		 * @return string
		 * @since 2.0.4
		 * @throws Exception Throws an exception.
		 */
		public function get_billing_last_name() {

			$billing_fields    = $this->get_address_fields();
			$billing_last_name = isset( $billing_fields['billing_last_name'] ) ? $billing_fields['billing_last_name'] : '';

			return apply_filters( 'ywsbs_customer_billing_last_name', $billing_last_name, $this );
		}


		/**
		 * Returns the rates_payed for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  2.0.0
		 */
		public function get_paid_rates( $context = 'view' ) {
			return (int) $this->get( 'rates_payed', $context );
		}


		/**
		 * Check if the subscription product must be shipping
		 *
		 * @return bool
		 * @since  1.4.0
		 */
		public function needs_shipping() {
			return ( ! empty( $this->get( 'subscriptions_shippings' ) && apply_filters( 'ywsbs_edit_shipping_address', true, $this ) ) );
		}

		/**
		 * Returns the order_ids for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 * @since  2.0.0
		 */
		public function get_order_ids( $context = 'view' ) {
			$order_ids = $this->get( 'order_ids', $context );
			return empty( $order_ids ) ? array() : (array) $order_ids;
		}


		/**
		 * Returns the subscription_total for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return float
		 * @since  2.0.0
		 */
		public function get_subscription_total( $context = 'view' ) {
			return (float) $this->get( 'subscription_total', $context );
		}

		/**
		 * Returns the subscriptions shippings for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 * @since  2.0.0
		 */
		public function get_subscriptions_shippings( $context = 'view' ) {
			$subscriptions_shippings = $this->get( 'subscriptions_shippings', $context );
			return empty( $subscriptions_shippings ) ? array() : (array) $subscriptions_shippings;
		}


		/**
		 * Returns the max length for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  2.0.0
		 */
		public function get_max_length( $context = 'view' ) {
			return (int) $this->get( 'max_length', $context );
		}


		/**
		 * Returns the quantity for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  2.0.0
		 */
		public function get_quantity( $context = 'view' ) {
			return (int) $this->get( 'quantity', $context );
		}


		/**
		 * Returns the product id for this object.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 * @since  2.0.0
		 */
		public function get_product_id( $context = 'view' ) {
			return (int) $this->get( 'product_id', $context );
		}

		/**
		 * Get last billing date
		 *
		 * @return WC_DateTime|string object if the date is set or empty string if there is no date.
		 */
		public function get_last_billing_date() {
			$paid_order_list = $this->get_paid_order_list();
			$paid_date       = '';

			if ( ! $paid_order_list ) {
				return $paid_date;
			}

			$paid_order_list = array_reverse( $paid_order_list );

			foreach ( $paid_order_list as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order instanceof WC_Order ) {
					$paid_date = $order->get_date_paid();
					if ( ! is_null( $paid_date ) ) {
						break;
					}
				}
			}

			return $paid_date;
		}

		/**
		 * Return the renew order if exists
		 *
		 * @return  bool|WC_Order
		 * @since   1.1.5
		 */
		public function has_a_renew_order() {

			$return         = false;
			$renew_order_id = $this->renew_order;

			if ( $renew_order_id ) {
				$order = wc_get_order( $renew_order_id );
				if ( $order ) {
					$return = $order;
				}
			}

			return $return;
		}

		/**
		 * Add failed attemp
		 *
		 * @param bool $attempts Attempts.
		 * @param bool $latest_attemp if is the last attemp doesn't send email.
		 *
		 * @since  1.1.3
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function register_failed_attemp( $attempts = false, $latest_attemp = false ) {

			$order = wc_get_order( $this->order_id );

			if ( false === $attempts ) {
				$failed_attemp = yit_get_prop( $order, 'failed_attemps' );
				$attempts      = intval( $failed_attemp ) + 1;
			}

			if ( ! $latest_attemp ) {
				yit_save_prop( $order, 'failed_attemps', $attempts, false );
				do_action( 'ywsbs_customer_subscription_payment_failed_mail', $this );
			}
		}


		/**
		 * Return if the subscription as a specific status
		 *
		 * @param string|array $status Status to check.
		 *
		 * @return bool
		 * @since  2.0.0
		 */
		public function has_status( $status ) {
			$status = (array) $status;

			return in_array( $this->get_status(), $status, true );
		}

		/**
		 * Delete the subscription
		 *
		 * @since 2.0.0
		 */
		public function delete() {

			do_action( 'ywsbs_before_subscription_deleted', $this->id );

			// Cancel the subscription before delete.
			$this->cancel();

			wp_delete_post( $this->id, true );
			do_action( 'ywsbs_subscription_deleted', $this->id );
		}


		/**
		 * Cancel the subscription
		 *
		 * @return void
		 * @since  2.0.0
		 */
		public function cancel() {

			// Change the status to cancelled.

			$this->set( 'status', 'cancelled' );
			do_action( 'ywsbs_subscription_cancelled', $this->id );

			// if there's a pending order for this subscription change the status of the order to cancelled.
			// translators: placeholder subscription number.
			$note = sprintf( __( 'This order has been cancelled because subscription %s has been cancelled', 'yith-woocommerce-subscription' ), $this->get_number() );
			$this->cancel_renew_order( $note );
		}



		/**
		 * Change the status of renew order if exists
		 *
		 * @param string $note Order note.
		 * @since  2.0.0
		 */
		public function cancel_renew_order( $note = '' ) {
			$renew_order = $this->get( 'renew_order' );
			if ( $renew_order ) {
				$order = wc_get_order( $renew_order );
				if ( $order ) {
					$order->update_status( 'cancelled' );
					if ( ! empty( $note ) ) {
						$order->add_order_note( $note );
					}
				}
			}
		}



		/**
		 * Return if the subscription can be set active.
		 *
		 * @return bool
		 * @since  1.0.0
		 */
		public function can_be_active() {
			$can_be_active = false;

			$status = array( 'pending', 'cancelled' );

			// the administrator and shop manager can switch the status to active.
			$post_type_object = get_post_type_object( YITH_YWSBS_POST_TYPE );
			if ( current_user_can( $post_type_object->cap->delete_post, $this->get_id() ) && $this->has_status( $status ) && ! ( $this->has_status( 'cancelled' ) && 'customer' === $this->get( 'cancelled_by' ) ) ) {
				$can_be_active = true;
			}

			return apply_filters( 'ywsbs_subscription_can_be_active', $can_be_active, $this );
		}

		/**
		 * Updates status of subscription
		 *
		 * @param string $new_status Status to change.
		 * @param string $from Who make the change.
		 *
		 * @return bool
		 * @since  1.0.0
		 */
		public function update_status( $new_status, $from = '' ) {

			if ( ! $this->id ) {
				return false;
			}

			$old_status     = $this->get( 'status' );
			$from_list      = ywsbs_get_from_list();
			$status_updated = false;

			if ( $new_status !== $old_status || ! in_array( $new_status, array_keys( ywsbs_get_status() ), true ) ) {

				switch ( $new_status ) {
					case 'active':
						// reset some custom data.
						$this->set( 'expired_pause_date', '' );
						// Check if subscription is cancelled. Es. for echeck payments.
						if ( 'cancelled' === $old_status ) {
							if ( 'administrator' === $from ) {
								$this->set( 'status', $new_status );
								do_action( 'ywsbs_customer_subscription_actived_mail', $this );

								$this->set( 'payment_due_date', $this->get( 'end_date' ) );
								$this->set( 'end_date', '' );
								$this->set( 'cancelled_date', '' );
							} else {
								$this->set( 'end_date', $this->get( 'payment_due_date' ) );
								$this->set( 'payment_due_date', '' );
								do_action( 'ywsbs_no_activated_just_cancelled', $this );

								return false;
							}
						} else {
							$this->set( 'status', $new_status );
							do_action( 'ywsbs_customer_subscription_actived_mail', $this );
							// translators: %s: Who set the new status request.
						}

						break;
					case 'cancelled':
						// if the subscription is cancelled the payment_due_date become the expired_date.
						// the subscription will be active until the date of the next payment.
						$this->set( 'end_date', $this->get_payment_due_date() );
						$this->set( 'payment_due_date', '' );
						$this->set( 'cancelled_date', time() ); // phpcs:ignore
						$this->set( 'status', $new_status );
						$this->set( 'cancelled_by', $from );
						$this->cancel_renew_order();
						do_action( 'ywsbs_customer_subscription_cancelled_mail', $this );
						break;
					case 'expired':
						$this->set( 'status', $new_status );
						$this->cancel_renew_order();
						do_action( 'ywsbs_customer_subscription_expired_mail', $this );
						break;
					default:
				}

				// Status was changed.
				do_action( 'ywsbs_subscription_status_' . $new_status, $this->id );
				do_action( 'ywsbs_subscription_status_' . $old_status . '_to_' . $new_status, $this->id );
				do_action( 'ywsbs_subscription_status_changed', $this->id, $old_status, $new_status );

				$status_updated = true;
			}

			return $status_updated;
		}
	}




}

