<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements admin features of YITH WooCommerce Subscription
 *
 * @class   YITH_WC_Subscription_Admin
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWSBS_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'YITH_WC_Subscription_Admin' ) ) {
	/**
	 * Class YITH_WC_Subscription_Admin
	 */
	class YITH_WC_Subscription_Admin {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Subscription_Admin
		 */

		protected static $instance;

		/**
		 * Panel Object
		 *
		 * @var YIT_Plugin_Panel_WooCommerce
		 */
		protected $panel;

		/**
		 * Premium page.
		 *
		 * @var string $_premium Premium tab template file name.
		 */
		protected $premium = 'premium.php';

		/**
		 * Landing page.
		 *
		 * @var string Premium version landing link
		 */
		protected $premium_landing = 'https://yithemes.com/themes/plugins/yith-woocommerce-subscription/';

		/**
		 * Panel page
		 *
		 * @var string Panel page
		 */
		protected $panel_page = 'yith_woocommerce_subscription';

		/**
		 * CPT Obj Subscription
		 *
		 * @var mixed
		 */
		public $cpt_obj_subscriptions;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Subscription_Admin
		 * @since 1.0.0
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

			$this->create_menu_items();

			// Add action links.
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_YWSBS_DIR . '/' . basename( YITH_YWSBS_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );

			// custom styles and javascripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 11 );

			// product editor.
			add_filter( 'product_type_options', array( $this, 'add_type_options' ) );

			// Sanitize the options before that are saved.
			add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_value_option' ), 20, 3 );
			add_filter( 'woocommerce_admin_settings_sanitize_option', array( __CLASS__, 'maybe_regenerate_capabilities' ), 10, 3 );

		}

		/**
		 * Add a product type option in single product editor
		 *
		 * @access public
		 *
		 * @param array $types Types.
		 *
		 * @return array
		 * @since 1.0.0
		 */
		public function add_type_options( $types ) {
			$types['ywsbs_subscription'] = array(
				'id'            => '_ywsbs_subscription',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Subscription', 'yith-woocommerce-subscription' ),
				'description'   => __( 'Create a subscription for this product', 'yith-woocommerce-subscription' ),
				'default'       => 'no',
			);
			return $types;
		}


		/**
		 * Enqueue styles and scripts
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function enqueue_styles_scripts() {

			wp_register_style( 'yith_ywsbs_backend', YITH_YWSBS_ASSETS_URL . '/css/backend.css', array( 'woocommerce_admin_styles', 'jquery-ui-style' ), YITH_YWSBS_VERSION );
			wp_register_script( 'yith_ywsbs_admin', YITH_YWSBS_ASSETS_URL . '/js/ywsbs-admin' . YITH_YWSBS_SUFFIX . '.js', array( 'jquery' ), YITH_YWSBS_VERSION, true );
			wp_register_script( 'jquery-blockui', YITH_YWSBS_ASSETS_URL . '/js/jquery.blockUI.min.js', array( 'jquery' ), YITH_YWSBS_VERSION, true );
			wp_register_style( 'yith-ywsbs-product', YITH_YWSBS_ASSETS_URL . '/css/ywsbs-product-editor.css', array( 'yith-plugin-fw-fields' ), YITH_YWSBS_VERSION );

			wp_register_script( 'yith-ywsbs-product', YITH_YWSBS_ASSETS_URL . '/js/ywsbs-product-editor' . YITH_YWSBS_SUFFIX . '.js', array( 'jquery' ), YITH_YWSBS_VERSION, true );

			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			if ( 'edit-' . YITH_YWSBS_POST_TYPE === $screen_id || ywsbs_check_valid_admin_page( YITH_YWSBS_POST_TYPE ) || ( isset( $_REQUEST['page'] ) && 'yith_woocommerce_subscription' === $_REQUEST['page'] ) ) { //phpcs:ignore
				wp_enqueue_style( 'yith_ywsbs_backend' );
				wp_enqueue_script( 'yith_ywsbs_admin' );
				wp_enqueue_script( 'selectWoo' );
				wp_enqueue_script( 'wc-enhanced-select' );
				wp_enqueue_script( 'yith-plugin-fw-fields' );
			}

			if ( ywsbs_check_valid_admin_page( 'product' ) ) {
				wp_enqueue_style( 'yith-ywsbs-product' );
				wp_enqueue_script( 'yith-ywsbs-product' );
				wp_enqueue_script( 'yith-plugin-fw-fields' );
			}

			wp_localize_script(
				'yith_ywsbs_admin',
				'yith_ywsbs_admin',
				array(
					'ajaxurl'                      => admin_url( 'admin-ajax.php' ),
					'back_to_all_subscription'     => esc_html__( 'back to all subscriptions', 'yith-woocommerce-subscription' ),
					'url_back_to_all_subscription' => add_query_arg( array( 'post_type' => YITH_YWSBS_POST_TYPE ), admin_url( 'edit.php' ) ),
					'block_loader'                 => apply_filters( 'yith_ywsbs_block_loader_admin', YITH_YWSBS_ASSETS_URL . '/images/block-loader.gif' ),
				)
			);
		}


		/**
		 * Create Menu Items
		 *
		 * Print admin menu items
		 *
		 * @since  1.0
		 */
		private function create_menu_items() {

			// Add a panel under YITH Plugins tab.
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
			add_action( 'yith_ywsbs_subscriptions_tab', array( $this, 'subscriptions_tab' ) );
			add_action( 'yith_ywsbs_premium_tab', array( $this, 'premium_tab' ) );
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use      /YIT_Plugin_Panel_WooCommerce class
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->panel ) ) {
				return;
			}

			$admin_tabs = array(
				'subscriptions' => esc_html__( 'Subscriptions', 'yith-woocommerce-subscription' ),
				'general'       => esc_html__( 'General Settings', 'yith-woocommerce-subscription' ),
				'customization' => esc_html__( 'Customization', 'yith-woocommerce-subscription' ),
			);

			$admin_tabs['premium'] = __( 'Premium Version', 'yith-woocommerce-subscription' );

			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => 'YITH WooCommerce Subscriptions',
				'menu_title'       => 'Subscriptions',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yith_plugin_panel',
				'page'             => $this->panel_page,
				'plugin_slug'      => YITH_YWSBS_SLUG,
				'admin-tabs'       => $admin_tabs,
				'class'            => yith_set_wrapper_class(),
				'options-path'     => YITH_YWSBS_DIR . '/plugin-options',
			);

			// enable shop manager to set Manage subscriptions.
			if ( 'yes' === get_option( 'ywsbs_enable_shop_manager' ) ) {
				add_filter( 'option_page_capability_yit_' . $args['parent'] . '_options', array( $this, 'change_capability' ) );
				$args['capability'] = 'manage_woocommerce';
			}

			/* === Fixed: not updated theme  === */
			if ( ! class_exists( 'YIT_Plugin_Panel' ) ) {
				require_once YITH_YWSBS_DIR . '/plugin-fw/lib/yit-plugin-panel.php';
			}
			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once YITH_YWSBS_DIR . '/plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );

		}


		/**
		 * Premium Tab Template
		 *
		 * Load the premium tab template on admin page
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function premium_tab() {
			$premium_tab_template = YITH_YWSBS_TEMPLATE_PATH . '/admin/' . $this->premium;

			if ( file_exists( $premium_tab_template ) ) {
				include_once $premium_tab_template;
			}
		}


		/**
		 * Action Links
		 *
		 * @param array $links Links plugin array.
		 *
		 * @return mixed
		 * @use    plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {
			if ( function_exists( 'yith_add_action_links' ) ) {
				$links = yith_add_action_links( $links, $this->panel_page, false );
			}
			return $links;
		}


		/**
		 * Add the action links to plugin admin page.
		 *
		 * @param array  $new_row_meta_args Plugin Meta New args.
		 * @param string $plugin_meta Plugin Meta.
		 * @param string $plugin_file Plugin file.
		 * @param array  $plugin_data Plugin data.
		 * @param string $status Status.
		 * @param string $init_file Init file.
		 *
		 * @return array
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_YWSBS_FREE_INIT' ) {
			if ( defined( $init_file ) && constant( $init_file ) === $plugin_file ) {
				$new_row_meta_args['slug'] = YITH_YWSBS_SLUG;
			}

			return $new_row_meta_args;
		}


		/**
		 * Get the premium landing uri
		 *
		 * @return  string The premium landing link
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 * @since   1.0.0
		 */
		public function get_premium_landing_uri() {
			return apply_filters( 'yith_plugin_fw_premium_landing_uri', $this->premium_landing, YITH_YWSBS_SLUG );
		}

		/**
		 * Subscriptions List Table
		 *
		 * Load the subscriptions on admin page
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Emanuela Castorina
		 */
		public function subscriptions_tab() {
			$this->cpt_obj_subscriptions = new YITH_YWSBS_Subscriptions_List_Table();

			$subscriptions_tab = YITH_YWSBS_TEMPLATE_PATH . '/admin/subscriptions-tab.php';

			if ( file_exists( $subscriptions_tab ) ) {
				include_once $subscriptions_tab;
			}
		}


		/**
		 * Sanitize the option of type 'relative_date_selector' before that are saved.
		 *
		 * @param mixed  $value Value.
		 * @param string $option Option.
		 * @param mixed  $raw_value Raw value.
		 *
		 * @return array
		 * @since 1.4
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function sanitize_value_option( $value, $option, $raw_value ) {

			if ( isset( $option['id'] ) && in_array( $option['id'], array( 'ywsbs_trash_pending_subscriptions', 'ywsbs_trash_cancelled_subscriptions' ), true ) ) { //phpcs:ignore
				$raw_value = maybe_unserialize( $raw_value );
				$value     = wc_parse_relative_date_option( $raw_value );
			}

			return $value;
		}

		/**
		 * Maybe regenerate the capabilities if the shop manager is disabled.
		 *
		 * @param mixed $value Current value.
		 * @param array $option Option info.
		 * @param mixed $raw_value Raw value.
		 *
		 * @return mixed
		 * @since  2.0.0
		 */
		public static function maybe_regenerate_capabilities( $value, $option, $raw_value ) {
			$enable_shop_manager = get_option( 'ywsbs_enable_shop_manager' );
			if ( isset( $option['id'] ) && 'ywsbs_enable_shop_manager' === $option['id'] && 'yes' !== $value && $enable_shop_manager !== $value ) {
				YWSBS_Subscription_Helper::maybe_regenerate_capabilities();
			}

			return $value;
		}

	}
}

/**
 * Unique access to instance of YITH_WC_Subscription_Admin class
 *
 * @return YITH_WC_Subscription_Admin
 */
function YITH_WC_Subscription_Admin() { //phpcs:ignore
	return YITH_WC_Subscription_Admin::get_instance();
}
