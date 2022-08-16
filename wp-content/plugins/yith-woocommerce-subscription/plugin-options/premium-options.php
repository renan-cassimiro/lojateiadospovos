<?php
/**
 * Subscription Options
 *
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.


return array(
	'premium' => array(
		'home' => array(
			'type'         => 'custom_tab',
			'action'       => 'yith_ywsbs_premium_tab',
			'hide_sidebar' => true,
		),
	),
);
