<?php
/**
 * Single product template options
 *
 * @package YITH WooCommerce Subscription
 * @since   2.0.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Vars used on this template.
 *
 * @var string $_ywsbs_price_time_option Period (days, weeks ..).
 * @var int    $_ywsbs_price_is_per Duration.
 * @var array  $max_lengths Limit of time foreach period.
 * @var int    $_ywsbs_max_length Max duration of the subscription.
 * @var bool   $_ywsbs_enable_limit Enable or not the limit.
 * @var string $_ywsbs_limit Subscription limit.
 * @var bool   $_ywsbs_enable_max_length Max length enabled.
 */

/**
 * WordPress date and time locale object.
 *
 * @global WP_Locale
 */
global $wp_locale;

$time_opt     = $_ywsbs_price_time_option ? $_ywsbs_price_time_option : 'days';
$time_options = ywsbs_get_time_options();
?>
<div class="options_group show_if_simple ywsbs-general-section">
	<h4 class="ywsbs-title-section"><?php esc_html_e( 'Subscription Settings', 'yith-woocommerce-subscription' ); ?></h4>

	<p class="form-field ywsbs_price_is_per">
		<label
			for="_ywsbs_price_is_per"><?php esc_html_e( 'Users will pay every', 'yith-woocommerce-subscription' ); ?></label>
		<span class="wrap">
						<input type="number" class="ywsbs-short" name="_ywsbs_price_is_per" id="_ywsbs_price_is_per" min="1"
							value="<?php echo esc_attr( $_ywsbs_price_is_per ); ?>"/>
						<select id="_ywsbs_price_time_option" name="_ywsbs_price_time_option"
							class="select ywsbs-with-margin yith-short-select ywsbs_price_time_option">
						<?php foreach ( $time_options as $key => $value ) : ?>
							<option
								value="<?php echo esc_attr( $key ); ?>" <?php selected( $_ywsbs_price_time_option, $key ); ?>  data-max="<?php echo esc_attr( $max_lengths[ $key ] ); ?>"
								data-text="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
		</span>
		<span
			class="description"><?php esc_html_e( 'Set the length of each recurring subscription period to daily, weekly, monthly or annually.', 'yith-woocommerce-subscription' ); ?></span>
	</p>

	<?php
	$args = array(
		'label'   => esc_html__( 'Subscription ends', 'yith-woocommerce-subscription' ),
		'value'   => $_ywsbs_enable_max_length,
		'id'      => '_ywsbs_enable_max_length',
		'name'    => '_ywsbs_enable_max_length',
		'default' => 'no',
		'options' => array(
			'no'  => esc_html__( 'Never', 'yith-woocommerce-subscription' ),
			'yes' => esc_html__( 'Set an end time', 'yith-woocommerce-subscription' ),
		),
	);
	woocommerce_wp_radio( $args );
	?>

	<p class="form-field ywsbs_max_length" data-deps-on="_ywsbs_enable_max_length" data-deps-val="yes"
		data-type="radio">
		<label for="_ywsbs_max_length"></label>
		<span class="ywsbs-inline-fields">
			<span><?php esc_html_e( 'Subscription will end after', 'yith-woocommerce-subscription' ); ?></span>
			<input
				type="number" class="ywsbs-short" name="_ywsbs_max_length" id="_ywsbs_max_length"
				value="<?php echo esc_attr( $_ywsbs_max_length ); ?>" min="1" />
			<span class="max-length-time-opt"><?php echo esc_html( $time_options[ $time_opt ] ); ?></span>
		</span>

	</p>
	<p class="form-field ywsbs_max_length-description"><span
			class="description"><?php esc_html_e( 'Choose if the subscription has an end time or not.', 'yith-woocommerce-subscription' ); ?></span>
	</p>



	<fieldset class="form-field yith-plugin-ui ywsbs_enable_limit onoff">
		<legend
			for="_ywsbs_enable_limit"><?php esc_html_e( 'Apply subscription limits', 'yith-woocommerce-subscription' ); ?></legend>
		<?php
		$args = array(
			'type'  => 'onoff',
			'id'    => '_ywsbs_enable_limit',
			'name'  => '_ywsbs_enable_limit',
			'value' => $_ywsbs_enable_limit,
		);
		yith_plugin_fw_get_field( $args, true );
		?>
		<span
			class="description"><?php esc_html_e( 'Enable to apply limits to the customer purchasing this subscription.', 'yith-woocommerce-subscription' ); ?></span>
	</fieldset>

	<div data-deps-on="_ywsbs_enable_limit" data-deps-val="yes" class="form-field">
	<?php
	$args = array(
		'label'       => esc_html__( 'Limit subscription', 'yith-woocommerce-subscription' ),
		'description' => esc_html__( 'Set optional limits for this product subscription.', 'yith-woocommerce-subscription' ),
		'value'       => $_ywsbs_limit,
		'id'          => '_ywsbs_limit',
		'name'        => '_ywsbs_limit',
		'options'     => array(
			'one-active' => esc_html__( 'Limit user to allow only one active subscription', 'yith-woocommerce-subscription' ),
			'one'        => esc_html__( 'Limit user to allow only one subscription of any status, either active or not', 'yith-woocommerce-subscription' ),
		),
	);
	woocommerce_wp_radio( $args );
	?>
	</div>

</div>
