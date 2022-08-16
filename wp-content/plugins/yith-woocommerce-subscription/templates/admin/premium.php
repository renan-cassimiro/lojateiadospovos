<?php
/**
 * Subscription details
 *
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH
 */

?>
<style>
	.landing {
		margin-right: 15px;
		border: 1px solid #d8d8d8;
		border-top: 0;
	}

	.section {
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
		background: #fafafa;
	}

	.section h1 {
		text-align: center;
		text-transform: uppercase;
		color: #445674;
		font-size: 35px;
		font-weight: 700;
		line-height: normal;
		display: inline-block;
		width: 100%;
		margin: 50px 0 0;
	}

	.section .section-title h2 {
		vertical-align: middle;
		padding: 0;
		line-height: normal;
		font-size: 24px;
		font-weight: 600;
		color: #445674;
		text-transform: none;
		background: none;
		border: none;
		text-align: center;
	}

	.section p {
		margin: 15px 0;
		font-size: 19px;
		line-height: 32px;
		font-weight: 300;
		text-align: center;
	}

	.section ul li {
		margin-bottom: 4px;
	}

	.section.section-cta {
		background: #fff;
	}

	.cta-container,
	.landing-container {
		display: flex;
		max-width: 1200px;
		margin-left: auto;
		margin-right: auto;
		padding: 30px 0;
		align-items: center;
	}

	.landing-container-wide {
		flex-direction: column;
	}

	.cta-container {
		display: block;
		max-width: 860px;
	}

	.landing-container:after {
		display: block;
		clear: both;
		content: '';
	}

	.landing-container .col-1,
	.landing-container .col-2 {
		float: left;
		box-sizing: border-box;
		padding: 0 15px;
	}

	.landing-container .col-1 {
		width: 58.33333333%;
	}

	.landing-container .col-2 {
		width: 41.66666667%;
	}

	.landing-container .col-1 img,
	.landing-container .col-2 img,
	.landing-container .col-wide img {
		max-width: 100%;
	}

	.premium-cta {
		color: #4b4b4b;
		border-radius: 10px;
		padding: 30px 25px;
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		box-sizing: border-box;
	}

	.premium-cta:after {
		content: '';
		display: block;
		clear: both;
	}

	.premium-cta p {
		margin: 10px 0;
		line-height: 1.5em;
		display: inline-block;
		text-align: left;
	}

	.premium-cta a.button {
		border-radius: 25px;
		float: right;
		background: #e09004;
		box-shadow: none;
		outline: none;
		color: #fff;
		position: relative;
		padding: 10px 50px 8px;
		text-align: center;
		text-transform: uppercase;
		font-weight: 600;
		font-size: 20px;
		line-height: 25px;
		border: none;
	}

	.premium-cta a.button:hover,
	.premium-cta a.button:active,
	.wp-core-ui .yith-plugin-ui .premium-cta a.button:focus {
		color: #fff;
		background: #d28704;
		box-shadow: none;
		outline: none;
	}

	.premium-cta .highlight {
		text-transform: uppercase;
		background: none;
		font-weight: 500;
	}


	@media (max-width: 768px) {
		.landing-container{
			display: block;
			padding: 50px 0 30px;
		}

		.landing-container .col-1,
		.landing-container .col-2{
			float: none;
			width: 100%;
		}

		.premium-cta{
			display: block;
			text-align: center;
		}

		.premium-cta p{
			text-align: center;
			display: block;
			margin-bottom: 30px;
		}
		.premium-cta a.button{
			float: none;
			display: inline-block;
		}
	}

	@media (max-width: 480px) {
		.wrap {
			margin-right: 0;
		}

		.section {
			margin: 0;
		}

		.landing-container .col-1,
		.landing-container .col-2 {
			width: 100%;
			padding: 0 15px;
		}

		.section-odd .col-1 {
			float: left;
			margin-right: -100%;
		}

		.section-odd .col-2 {
			float: right;
			margin-top: 65%;
		}
	}

	@media (max-width: 320px) {
		.premium-cta a.button {
			padding: 9px 20px 9px 70px;
		}

		.section .section-title img {
			display: none;
		}
	}
</style>
<div class="landing">
	<div class="section section-cta section-odd">
		<div class="landing-container">
			<div class="premium-cta">
				<p>
					<?php
					// translators: placeholders html tag.
					echo wp_kses_post( sprintf( __( 'Upgrade to %1$spremium version%2$s of %1$sYITH WooCommerce Subscription%2$s to benefit from all features!', 'yith-woocommerce-subscription' ), '<span class="highlight">', '</span>' ) );
					?>
				</p>
				<a href="<?php echo esc_url( $this->get_premium_landing_uri() ); ?>" target="_blank"
					class="premium-cta-button button btn">
					<?php esc_html_e( 'UPGRADE', 'yith-woocommerce-subscription' ); ?>
				</a>
			</div>
		</div>
	</div>
	<div class="one section section-even clear">
		<h1><?php esc_html_e( 'Premium Features', 'yith-woocommerce-subscription' ); ?></h1>
		<div class="landing-container">
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/02-1.webp" alt="Feature 01"/>
			</div>
			<div class="col-2">
				<div class="section-title">
					<h2>
					<?php
						// translators: placeholders html tag.
						esc_html_e( 'Use product variations to create different subscription plans', 'yith-woocommerce-subscription' );
					?>
						</h2>
				</div>
				<p>
					<?php
					// translators: placeholders html tag.
					echo wp_kses_post( sprintf( __( 'Use product variations to create unlimited subscription plans for your product or service and allow your users to easily upgrade and downgrade or switch from one plan to the other. %1$sYou can set the conditions, for example, if a customer will need to pay the difference between the old and new subscription plan.', 'yith-woocommerce-subscription' ), '<br>' ) );
					?>
				</p>
			</div>
		</div>
	</div>
	<div class="two section section-odd clear">
		<div class="landing-container">
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Allow users to switch, pause or cancel the subscription plan', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php
					// translators: placeholders html tag.
					echo wp_kses_post( sprintf( __( 'Choose whether to let the user %1$spause a subscription%2$s (set limits like the maximum number of pauses or the maximum number of paused days allowed before it automatically gets reactivated), to switch to another plan or to cancel a subscription right from their account.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) );
					?>
				</p>
			</div>
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/03-1.webp" alt="feature 02"/>
			</div>
		</div>
	</div>
	<div class="three section section-even clear">
		<div class="landing-container">
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/04-1.webp" alt="Feature 03"/>
			</div>
			<div class="col-2">
				<div class="section-title">
					<h2>
					<?php
						// translators: placeholders html tag.
						esc_html_e( 'Set a free trial period to create a list of customers and push them to buy later', 'yith-woocommerce-subscription' );
					?>
						</h2>
				</div>
				<p>
					<?php
					// translators: placeholders html tag.
					echo wp_kses_post( sprintf( __( 'A %1$sfree trial period%2$s might be the most effective tool to encourage your users to subscribe and test your products or services for free: on the trial expiration, it will be easier for you to push them to buy and increase conversions.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) );
					?>
				</p>
			</div>
		</div>
	</div>
	<div class="four section section-odd clear">
		<div class="landing-container">
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Choose how to handle failed payments and when to suspend or cancel a subscription', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'Thanks to some advanced options, you can choose how to handle subscriptions with failed payments: set the number of days allowed before the subscription gets suspended and how long it can stay suspended before it gets canceled.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/05.webp" alt="Feature 04"/>
			</div>
		</div>
	</div>
	<div class="five section section-even clear">
		<div class="landing-container">
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/06.webp" alt="Feature 05"/>
			</div>
			<div class="col-2">
				<div class="section-title">
					<h2>
					<?php
						// translators: placeholders html tag.
						esc_html_e( 'A wide range of e-mail notifications for admins and customers', 'yith-woocommerce-subscription' );
					?>
						</h2>
				</div>
				<p>
					<?php
					// translators: placeholders html tag.
					echo wp_kses_post( sprintf( __( 'The plugin allows sending different kinds of email notifications both to the admin and to the subscribers. %1$sFor example, the user can be notified multiple times about the expiring subscription, the expired subscription, a failed payment or successful payment, if the subscription is getting suspended and so on.', 'yith-woocommerce-subscription' ), '<br>' ) );
					?>
				</p>
			</div>
		</div>
	</div>

	<div class="six section section-odd clear">
		<div class="landing-container">
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Set a sign-up fee on your subscriptions', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'Choose whether to ask for a sign-up fee on your subscription-based products.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/07.webp" alt="Feature 06"/>
			</div>
		</div>
	</div>
	<div class="seven section section-even clear">
		<div class="landing-container">
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/08.webp" alt="Feature 07"/>
			</div>
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Two additional coupon types to apply discounts on the sign-up fee or on the recurring payment price', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'Create a coupon to offer your customers a discount on the sign-up fee or on the recurring fee.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
		</div>
	</div>
	<div class="eight section section-odd clear">
		<div class="landing-container">
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'A dashboard to easily track all subscriptions and subscription activities', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'Monitor the status of every subscription (start and end date, next payment date, payment amount etc.) from the built-in dashboard.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/09.webp" alt="Feature 08"/>
			</div>
		</div>
	</div>
	<div class="nine section section-even clear">
		<div class="landing-container">
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/10.webp" alt="Feature 09"/>
			</div>
			<div class="col-2">
				<div class="section-title">
					<h2>
					<?php
					esc_html_e(
						'A Gutenberg block to easily create and show subscription modules in your shop
',
						'yith-woocommerce-subscription'
					);
					?>
					</h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'If you are using Gutenberg, you will be able to find the “Subscription plan” block and create custom forms in a couple of clicks to visually display your available subscription plans. You can customize colors, typography, add gradients, icons and much more.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
		</div>
	</div>
	<div class="ten section section-odd clear">
		<div class="landing-container">
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Integration with YITH WooCommerce Membership ', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'Integrate our Membership plugin and let your subscribers access to private contents and to sections with restricted access.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/11.webp" alt="Feature 10"/>
			</div>
		</div>
	</div>
	<div class="eleven section section-even clear">
		<div class="landing-container">
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/12.webp" alt="Feature 11"/>
			</div>
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Integration with YITH WooCommerce Stripe', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'Stripe is one of the integrated payment gateways to let your customers join and renew a subscription plan automatically.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
		</div>
	</div>
	<div class="section section-odd clear">
		<div class="landing-container">

			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Synchronize all recurring payments to a specific day (of the week, of each month etc.)', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'Streamline the management of your store subscriptions by synchronizing all recurring payments to the same day (the first day of the month, every Monday and so on) and decide how to handle the first subscription payment: you can charge the user a prorated amount or postpone the payment of the total amount to the day selected in the synchronization options.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/14.webp" alt="Feature 11"/>
			</div>
		</div>
	</div>
	<div class="eleven section section-even clear">
		<div class="landing-container">
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/13.webp" alt="Feature 11"/>
			</div>
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Export all the subscriptions to a CSV file', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'Do you want to download and print an overview of all the subscriptions of your store? Download the CSV file with all the subscription details in one click right from the dashboard.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
		</div>
	</div>
	<div class="section section-odd clear">
		<div class="landing-container">

			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Schedule the delivery of products linked to a subscription', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'Do your subscriptions involve scheduling the shipping of products (like a product box, a printed magazine etc.)? Take advantage of the dedicated Delivery Schedule option and decide if you want to sync all deliveries on the same day (ex. every Monday, every 1st day of the month and so on)', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/16.webp" alt="Feature 11"/>
			</div>
		</div>
	</div>
	<div class="eleven section section-even clear">
		<div class="landing-container">
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/17.webp" alt="Feature 17"/>
			</div>
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Print a list of PDF addresses to manage the shipping easily', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'If you sell subscription-based products that need to be shipped, you can download a list with your customers’ addresses into a PDF file and print the shipping labels in one click.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="eleven section section-even clear">
		<div class="landing-container">
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Monitor your subscriptions and income from the built-in dashboard', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'In the first page of the plugin settings, you will find a modern and powerful dashboard to monitor the most popular subscriptions, the net income, the monthly average income, the conversion percent rate from the free version, and much more.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/18.webp" alt="Feature 17"/>
			</div>

		</div>
	</div>

	<div class="eleven section section-even clear">
		<div class="landing-container">
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/19.webp" alt="Feature 17"/>
			</div>
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Create a discount coupon that can be applied to recurring payments', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'The new "Subscription Recurring Discount" option allows you to offer your customers a discount on, for example, the first three months of their subscription.', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>

		</div>
	</div>

	<div class="eleven section section-even clear">
		<div class="landing-container">
			<div class="col-2">
				<div class="section-title">
					<h2><?php esc_html_e( 'Create an order and the related subscription manually and assign it to one of your users from the backend', 'yith-woocommerce-subscription' ); ?></h2>
				</div>
				<p>
					<?php echo wp_kses_post( sprintf( __( 'One of your customers has decided to subscribe and you want to handle the subscription manually? Thanks to our plugin, you can create an order, assign it to the user and finally convert the order into a subscription to one of your products. This is also the easiest solution to manage payments in cash or prevent losing sales from those customers that would have trouble with subscribing by themselves (because of their age, lack of time, or non-familiarity with the e-commerce system, etc.).', 'yith-woocommerce-subscription' ), '<b>', '</b>' ) ); ?>
				</p>
			</div>
			<div class="col-1">
				<img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL ); ?>/images/20.webp" alt="Feature 17"/>
			</div>

		</div>
	</div>

	<div class="section section-cta section-even">
		<div class="landing-container">
			<div class="premium-cta">
				<p>
					<?php
					// translators: placeholders html tag.
					echo sprintf( esc_html( __( 'Upgrade to %1$spremium version%2$s of %1$sYITH WooCommerce Subscription%2$s to benefit from all features!', 'yith-woocommerce-subscription' ) ), '<span class="highlight">', '</span>' );
					?>
				</p>
				<a href="<?php echo esc_url( $this->get_premium_landing_uri() ); ?>" target="_blank"
					class="premium-cta-button button btn">
					<?php esc_html_e( 'UPGRADE', 'yith-woocommerce-subscription' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>
