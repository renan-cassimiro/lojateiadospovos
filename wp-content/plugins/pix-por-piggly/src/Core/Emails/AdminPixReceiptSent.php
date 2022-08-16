<?php
namespace Piggly\WooPixGateway\Core\Emails;

use Piggly\WooPixGateway\Core\Entities\PixEntity;
use Piggly\WooPixGateway\CoreConnector;
use Piggly\WooPixGateway\Vendor\Piggly\Wordpress\Core\WP;

use WC_Email;
use WC_Order;

/**
 * Sent when a receipt is received.
 * 
 * @package \Piggly\WooPixGateway
 * @subpackage \Piggly\WooPixGateway\Core\Emails
 * @version 2.0.0
 * @since 2.0.0
 * @category Entities
 * @author Caique Araujo <caique@piggly.com.br>
 * @author Piggly Lab <dev@piggly.com.br>
 * @license GPLv3 or later
 * @copyright 2021 Piggly Lab <dev@piggly.com.br>
 */
class AdminPixReceiptSent extends WC_Email
{
	/**
	 * Pix.
	 *
	 * @var PixEntity
	 * @since 2.0.0
	 */
	protected $pix;

	/**
	 * Construct class to verify account e-mail.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function __construct ()
	{
		$this->id = 'wc_piggly_pix_admin_receipt_sent';
		$this->title = CoreConnector::__translate('(Admin) Comprovante Pix Enviado');
		$this->description = CoreConnector::__translate('E-mail enviado quando um comprovante Pix foi enviado.');

		$this->customer_email = false;

		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// email template path
		$this->template_html  = 'woocommerce/emails/admin-pix-receipt-sent.php';
		$this->template_plain = 'woocommerce/emails/plain/admin-pix-receipt-sent.php';
		$this->template_base  = CoreConnector::plugin()->getTemplatePath();

		WP::add_action('pgly_wc_piggly_pix_after_save_receipt', $this, 'trigger', 10, 3);
		
		parent::__construct();
		$this->manual = false;

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Prepare and send e-mail.
	 *
	 * @param PixEntity $pix
	 * @param WC_Order $order
	 * @param integer $order_id
	 * @since 2.0.0
	 * @return void
	 */
	public function trigger ( PixEntity $pix, WC_Order $order, int $order_id )
	{
		$this->setup_locale();

		if ( !empty($order) )
		{
			// Placeholders
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();

			$this->pix = $pix;

			if ( $this->is_enabled() && $this->get_recipient() )
			{
				CoreConnector::debugger()->debug(\sprintf('Disparo de e-mail %s para %s', $this->id, $this->get_recipient()));

				$sent = $this->send(
					$this->get_recipient(),
					$this->get_subject(),
					$this->get_content(),
					$this->get_headers(),
					$this->get_attachments()
				);

				if ( !$sent )
				{ CoreConnector::debugger()->debug(\sprintf('Erro ao enviar e-mail %s para %s', $this->id, $this->get_recipient())); }
			}
		}
		
		$this->restore_locale();
	}

	/**
	 * Get email subject.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_default_subject() {
		return CoreConnector::__translate('Comprovante Pix enviado para o Pedido #{order_number}');
	}

	/**
	 * Get email heading.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_default_heading() {
		return CoreConnector::__translate('O cliente enviou um comprovante de pagamento para o Pix');
	}

	/**
	 * Get the email content in HTML format.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( 
			$this->template_html, 
			array(
				'pix' => $this->pix,
				'order' => $this->pix->getOrder(),
				'domain' => CoreConnector::domain(),
				'additional_content' => $this->get_additional_content(),
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this
			), 
			'', 
			$this->template_base 
		);
	}

	/**
	 * Get content plain.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( 
			$this->template_plain, 
			array(
				'pix' => $this->pix,
				'order' => $this->pix->getOrder(),
				'domain' => CoreConnector::domain(),
				'additional_content' => $this->get_additional_content(),
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this
			), 
			'', 
			$this->template_base 
		);
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		/* translators: %s: list of placeholders */
		$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
		
		$this->form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce' ),
				'default' => 'yes',
			),
			'recipient'          => array(
				'title'       => __( 'Recipient(s)', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			),
			'subject'            => array(
				'title'       => __( 'Subject', 'woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'            => array(
				'title'       => __( 'Email heading', 'woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'additional_content' => array(
				'title'       => __( 'Additional content', 'woocommerce' ),
				'description' => __( 'Text to appear below the main email content.', 'woocommerce' ) . ' ' . $placeholder_text,
				'css'         => 'width:400px; height: 75px;',
				'placeholder' => __( 'N/A', 'woocommerce' ),
				'type'        => 'textarea',
				'default'     => $this->get_default_additional_content(),
				'desc_tip'    => true,
			),
			'email_type'         => array(
				'title'       => __( 'Email type', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
}