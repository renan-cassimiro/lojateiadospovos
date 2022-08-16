<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements YWSBS_Subscription_Helper Class
 *
 * @class   YWSBS_Subscription_Helper
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWSBS_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'YWSBS_Subscription_Helper' ) ) {

	/**
	 * Class YWSBS_Subscription_Helper
	 */
	class YWSBS_Subscription_Helper {

		/**
		 * Single instance of the class
		 *
		 * @var YWSBS_Subscription_Helper
		 */

		protected static $instance;


		/**
		 * Returns single instance of the class
		 *
		 * @access public
		 *
		 * @return YWSBS_Subscription_Helper
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

			add_action( 'init', array( $this, 'register_subscription_post_type' ) );
			add_action( 'ywsbs_after_register_post_type', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );

			// Add Capabilities to Administrator and Shop Manager.
			add_action( 'admin_init', array( $this, 'add_subscription_capabilities' ), 1 );

		}


		/**
		 * Register ywsbs_subscription post type
		 *
		 * @since 1.0.0
		 */
		public function register_subscription_post_type() {

			$supports = false;

			if ( apply_filters( 'ywsbs_test_on', YITH_YWSBS_TEST_ON ) ) {
				$supports = array( 'custom-fields' );
			}

			$labels = array(
				'name'               => esc_html_x( 'Subscriptions', 'Post Type General Name', 'yith-woocommerce-subscription' ),
				'singular_name'      => esc_html_x( 'Subscription', 'Post Type Singular Name', 'yith-woocommerce-subscription' ),
				'menu_name'          => esc_html__( 'Subscription', 'yith-woocommerce-subscription' ),
				'parent_item_colon'  => esc_html__( 'Parent Item:', 'yith-woocommerce-subscription' ),
				'all_items'          => esc_html__( 'All Subscriptions', 'yith-woocommerce-subscription' ),
				'view_item'          => esc_html__( 'View Subscriptions', 'yith-woocommerce-subscription' ),
				'add_new_item'       => esc_html__( 'Add New Subscription', 'yith-woocommerce-subscription' ),
				'add_new'            => esc_html__( 'Add New Subscription', 'yith-woocommerce-subscription' ),
				'edit_item'          => esc_html__( 'Edit Subscription', 'yith-woocommerce-subscription' ),
				'update_item'        => esc_html__( 'Update Subscription', 'yith-woocommerce-subscription' ),
				'search_items'       => esc_html__( 'Search by Subscription ID', 'yith-woocommerce-subscription' ),
				'not_found'          => esc_html__( 'Not found', 'yith-woocommerce-subscription' ),
				'not_found_in_trash' => esc_html__( 'Not found in Trash', 'yith-woocommerce-subscription' ),
			);

			$args = array(
				'label'               => esc_html__( 'ywsbs_subscription', 'yith-woocommerce-subscription' ),
				'labels'              => $labels,
				'supports'            => $supports,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'capability_type'     => 'ywsbs_sub',
				'capabilities'        => array(
					'read_post'          => 'read_ywsbs_sub',
					'read_private_posts' => 'read_ywsbs_sub',
					'edit_post'          => 'edit_ywsbs_sub',
					'edit_posts'         => 'edit_ywsbs_subs',
					'edit_others_post'   => 'edit_others_ywsbs_subs',
					'delete_post'        => 'delete_ywsbs_sub',
					'delete_others_post' => 'delete_others_ywsbs_subs',
				),
				'map_meta_cap'        => false,
			);

			register_post_type( YITH_YWSBS_POST_TYPE, $args );

			do_action( 'ywsbs_after_register_post_type' );

		}

		/**
		 * Flush rules if the event is queued.
		 *
		 * @since 2.0.0
		 */
		public static function maybe_flush_rewrite_rules() {
			if ( ! get_option( 'ywsbs_queue_flush_rewrite_rules' ) ) {
				update_option( 'ywsbs_queue_flush_rewrite_rules', 'yes' );
				flush_rewrite_rules();
			}
		}

		/**
		 * Return the list of subscription capabilities
		 *
		 * @return array
		 * @since  2.0.0
		 */
		public static function get_subscription_capabilities() {
			$caps = array(
				'read_post'          => 'read_ywsbs_sub',
				'read_others_post'   => 'read_others_ywsbs_subs',
				'edit_post'          => 'edit_ywsbs_sub',
				'edit_posts'         => 'edit_ywsbs_subs',
				'edit_others_post'   => 'edit_others_ywsbs_subs',
				'delete_post'        => 'delete_ywsbs_sub',
				'delete_others_post' => 'delete_others_ywsbs_subs',
			);

			return apply_filters( 'ywsbs_get_subscription_capabilities', $caps );
		}

		/**
		 * Add subscription management capabilities to Admin and Shop Manager
		 *
		 * @since 1.0.0
		 */
		public function add_subscription_capabilities() {

			// gets the admin and shop_manager roles.
			$admin               = get_role( 'administrator' );
			$enable_shop_manager = ( 'yes' === get_option( 'ywsbs_enable_shop_manager' ) );
			$shop_manager        = get_role( 'shop_manager' );

			foreach ( self::get_subscription_capabilities() as $key => $cap ) {
				$admin && $admin->add_cap( $cap );
				if ( $enable_shop_manager ) {
					$shop_manager && $shop_manager->add_cap( $cap );
				}
			}
		}

		/**
		 * Regenerate the capabilities.
		 *
		 * @since 2.0.0
		 */
		public static function maybe_regenerate_capabilities() {
			$shop_manager = get_role( 'shop_manager' );
			foreach ( self::get_subscription_capabilities() as $key => $cap ) {
				$shop_manager && $shop_manager->remove_cap( $cap );
			}
		}

		/**
		 * Return the subscription recurring price formatted
		 *
		 * @param YWSBS_Subscription $subscription Subscription.
		 * @param string             $tax_display Display tax.
		 * @param bool               $show_time_option Show time option.
		 * @param bool               $shipping Add shipping price to total.
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function get_formatted_recurring( $subscription, $tax_display = '', $show_time_option = true, $shipping = false ) {

			$price_time_option_string = ywsbs_get_price_per_string( $subscription->get( 'price_is_per' ), $subscription->get( 'price_time_option' ) );
			$tax_inc                  = get_option( 'woocommerce_prices_include_tax' ) === 'yes';

			if ( wc_tax_enabled() && ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) || $tax_inc ) ) {
				$shipping_price = $shipping ? $subscription->get_order_shipping() + $subscription->get_order_shipping_tax() : 0;
				$sbs_price      = $subscription->get_line_total() + $subscription->get_line_tax() + $shipping_price;
			} else {
				$shipping_price = $shipping ? $subscription->get_order_shipping() : 0;
				$sbs_price      = $subscription->get_line_total();
			}

			$recurring  = wc_price( $sbs_price, array( 'currency' => $subscription->get( 'order_currency' ) ) );
			$recurring .= $show_time_option ? ' / ' . $price_time_option_string : '';

			$recurring = apply_filters_deprecated( 'ywsbs-recurring-price', array( $recurring, $subscription ), '2.0.0', 'ywsbs_recurring_price', 'This filter will be removed in the next major release' );

			return apply_filters( 'ywsbs_recurring_price', $recurring, $subscription );
		}

		/**
		 * Return the subscription max_length of a product.
		 *
		 * @param WC_Product $product Product.
		 * @param bool|array $subscription_info Subscription information.
		 *
		 * @return string
		 */
		public static function get_total_subscription_price( $product, $subscription_info ) {

			$max_length = self::get_subscription_product_max_length( $product );

			if ( ! $max_length ) {
				return '';
			}

			$recurring_price = $subscription_info && isset( $subscription_info['recurring_price'] ) ? $subscription_info['recurring_price'] : $product->get_price();

			$total_price = $recurring_price * $max_length;

			if ( ! empty( $subscription_info['price_is_per'] ) ) {
				$total_price = $total_price / $subscription_info['price_is_per'];
			}

			return $total_price;

		}


		/**
		 * Get the formatted period for price
		 *
		 * @param WC_Product $product Product.
		 * @param array      $subscription_info List of subscription parameters.
		 *
		 * @return string
		 */
		public static function get_subscription_max_length_formatted_for_price( $product, $subscription_info = false ) {

			$max_length = $subscription_info ? $subscription_info['max_length'] : self::get_subscription_product_max_length( $product );

			if ( empty( $max_length ) ) {
				return '';
			}

			$price_time_option    = $subscription_info ? $subscription_info['price_time_option'] : $product->get_meta( '_ywsbs_price_time_option' );
			$max_length_formatted = ywsbs_get_price_per_string( $max_length, $price_time_option, true );

			// APPLY_FILTER: ywsbs_subscription_max_length_formatted_for_price: to filter the formatted subscription period for price.
			return apply_filters( 'ywsbs_subscription_max_length_formatted_for_price', $max_length_formatted, $product );
		}


		/**
		 * Get all subscriptions of a user
		 *
		 * @param int $user_id User ID.
		 * @param int $page Page number.
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function get_subscriptions_by_user( $user_id, $page = -1 ) {

			$args = array(
				'post_type'  => YITH_YWSBS_POST_TYPE,
				'meta_key'   => 'user_id',  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $user_id,   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			);

			if ( -1 === $page ) {
				$args['posts_per_page'] = -1;
			} else {
				$args['posts_per_page'] = apply_filters( 'ywsbs_num_of_subscription_on_a_page_my_account', 10 );
				$args['paged']          = $page;
			}

			$subscriptions = get_posts( $args );

			return $subscriptions;
		}

		/**
		 * Get the formatted period for price
		 *
		 * @param WC_Product $product Product.
		 * @param array      $subscription_info List of subscription parameters.
		 *
		 * @return string
		 */
		public static function get_subscription_period_for_price( $product, $subscription_info = false ) {

			if ( ! $product ) {
				return '';
			}

			$price_is_per             = $subscription_info ? $subscription_info['price_is_per'] : $product->get_meta( '_ywsbs_price_is_per' );
			$price_time_option        = $subscription_info ? $subscription_info['price_time_option'] : $product->get_meta( '_ywsbs_price_time_option' );
			$price_time_option_string = ywsbs_get_price_per_string( $price_is_per, $price_time_option, false );

			// APPLY_FILTER: ywsbs_subscription_period_for_price: to filter the formatted subscription period for price.
			return apply_filters( 'ywsbs_subscription_period_for_price', $price_time_option_string, $product, $subscription_info );
		}

		/**
		 * Return the subscription max_length of a product.
		 *
		 * @param WC_Product $product Product.
		 *
		 * @return string
		 */
		public static function get_subscription_product_max_length( $product ) {

			$max_length        = $product->get_meta( '_ywsbs_max_length' );
			$enable_max_length = $product->get_meta( '_ywsbs_enable_max_length' );

			// previous version.
			if ( empty( $enable_max_length ) ) {
				return $max_length;
			}

			return ( 'yes' === $enable_max_length ) ? $max_length : '';
		}


		/**
		 * Get the raw recurring price.
		 *
		 * @param WC_Product $product Product.
		 * @param array      $subscription_info List of subscription parameters.
		 *
		 * @return string
		 */
		public static function get_subscription_recurring_price( $product, $subscription_info = false ) {

			$recurring_price = $subscription_info && isset( $subscription_info['recurring_price'] ) ? $subscription_info['recurring_price'] : $product->get_price();

			// APPLY_FILTER: ywsbs_subscription_recurring_price: to filter raw recurring price.
			return apply_filters( 'ywsbs_subscription_recurring_price', $recurring_price, $product, $subscription_info );
		}

	}

}


/**
 * Unique access to instance of YWSBS_Subscription class
 *
 * @return YWSBS_Subscription_Helper
 */
function YWSBS_Subscription_Helper() {  //phpcs:ignore
	return YWSBS_Subscription_Helper::get_instance();
}
