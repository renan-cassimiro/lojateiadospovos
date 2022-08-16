<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements frontend features of YITH WooCommerce Subscription
 *
 * @class   YITH_WC_Subscription_Frontend
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWSBS_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'YITH_WC_Subscription_Frontend' ) ) {
	/**
	 * Class YITH_WC_Subscription_Frontend
	 */
	class YITH_WC_Subscription_Frontend {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Subscription_Frontend
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Subscription_Frontend
		 * @since  1.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			is_user_logged_in() && YWSBS_Subscription_My_Account::get_instance();

			YWSBS_Subscription_Cart::get_instance();

			// Change add to cart label.
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_label' ), 99, 2 );
			add_filter( 'add_to_cart_text', array( $this, 'change_add_to_cart_label' ), 99 );
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'change_add_to_cart_label' ), 99, 2 );

			// Checkout page.
			add_filter( 'woocommerce_order_button_text', array( $this, 'change_place_order_button_label' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );

		}


		/**
		 * Enqueue scripts and styles
		 */
		public function enqueue_scripts() {
			if ( ! apply_filters( 'ywsbs_load_assets', true ) ) {
				return;
			}
			wp_register_style( 'yith_ywsbs_frontend', YITH_YWSBS_ASSETS_URL . '/css/frontend.css', false, YITH_YWSBS_VERSION );

			wp_enqueue_style( 'yith_ywsbs_frontend' );
		}


		/**
		 * Change add to cart label in subscription product.
		 *
		 * @param string          $label Current add to cart label.
		 * @param null|WC_Product $product Current product.
		 *
		 * @return string
		 * @since  2.0.0
		 */
		public function change_add_to_cart_label( $label, $product = null ) {

			if ( is_null( $product ) ) {
				global $product;
				if ( is_null( $product ) ) {
					global $post;
					if ( empty( $post ) ) {
						return $label;
					}
					$product = wc_get_product( $post->ID );
				}
			}

			if ( is_null( $product ) || ! is_object( $product ) || $product->is_type( 'variable' ) ) {
				return $label;
			}

			$id        = $product->get_id();
			$new_label = get_option( 'ywsbs_add_to_cart_label' );

			if ( $id && $new_label && ywsbs_is_subscription_product( $id ) && $product->is_purchasable() ) {
				$label = apply_filters( 'yith_subscription_add_to_cart_text', $new_label, $product );
			}

			return $label;
		}

		/**
		 * Add custom params to variations
		 *
		 * @access public
		 *
		 * @param array                $args Arguments.
		 * @param WC_Product           $product Current product.
		 * @param WC_Product_Variation $variation WC_Product_Variation.
		 *
		 * @return array
		 * @since  2.0.0
		 */
		public function add_params_to_available_variation( $args, $product, $variation ) {

			$args['is_subscription'] = ywsbs_is_subscription_product( $variation->get_id() );
			$args['is_switchable']   = 'yes' === $variation->get_meta( '_ywsbs_switchable' );

			return $args;
		}

		/**
		 * Customize the Place Order label on checkout page if on cart there's a subscription.
		 *
		 * @access public
		 *
		 * @param array $label Current Place Order label.
		 *
		 * @return string
		 * @since  2.0.0
		 */
		public function change_place_order_button_label( $label ) {

			if ( ! YWSBS_Subscription_Cart::cart_has_subscriptions() ) {
				return $label;
			}

			return get_option( 'ywsbs_place_order_label', $label );

		}


	}


}

/**
 * Unique access to instance of YITH_WC_Subscription_Frontend class
 *
 * @return YITH_WC_Subscription_Frontend
 */
function YITH_WC_Subscription_Frontend() { //phpcs:ignore
	return YITH_WC_Subscription_Frontend::get_instance();
}
