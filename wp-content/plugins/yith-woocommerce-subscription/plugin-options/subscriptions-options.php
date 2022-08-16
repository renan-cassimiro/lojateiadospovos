<?php
/**
 * Subscription Options
 *
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */

$section = array(
	'subscription_list_table' => array(
		'type'          => 'post_type',
		'post_type'     => YITH_YWSBS_POST_TYPE,
		'wp-list-style' => 'classic',
	),
);


return apply_filters( 'ywsbs_subscriptions_options', array( 'subscriptions' => $section ) );
