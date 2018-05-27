<?php
/**
 * Payir payment gateway class.
 *
 * @author   MidyaSoft
 * @package  LearnPress/Payir/Classes
 * @version  1.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Gateway_Payir' ) ) {
	/**
	 * Class LP_Gateway_Payir
	 */
	class LP_Gateway_Payir extends LP_Gateway_Abstract {

		/**
		 * @var array
		 */
		private $form_data = array();

		/**
		 * @var string
		 */
		private $sendUrl = 'https://pay.ir/payment/send';
		
		/**
		 * @var string
		 */
		private $gatewayUrl = 'https://pay.ir/payment/gateway/';
		
		/**
		 * @var string
		 */
		private $verifyUrl = 'https://pay.ir/payment/verify';
		
		/**
		 * @var string
		 */
		private $api = null;

		/**
		 * @var array|null
		 */
		protected $settings = null;

		/**
		 * @var null
		 */
		protected $order = null;

		/**
		 * @var null
		 */
		protected $posted = null;

		/**
		 * Request TransId
		 *
		 * @var string
		 */
		protected $transId = null;

		/**
		 * LP_Gateway_Payir constructor.
		 */
		public function __construct() {
			$this->id = 'payir';

			$this->method_title       =  __( 'Payir', 'learnpress-payir' );;
			$this->method_description = __( 'Make a payment with Pay.ir.', 'learnpress-payir' );
			$this->icon               = '';

			// Get settings
			$this->title       = LP()->settings->get( "{$this->id}.title", $this->method_title );
			$this->description = LP()->settings->get( "{$this->id}.description", $this->method_description );

			$settings = LP()->settings;

			// Add default values for fresh installs
			if ( $settings->get( "{$this->id}.enable" ) ) {
				$this->settings                     = array();
				$this->settings['api']        = $settings->get( "{$this->id}.api" );
			}
			
			$this->api = $this->settings['api'];
			
			
			if ( did_action( 'learn_press/payir-add-on/loaded' ) ) {
				return;
			}

			// check payment gateway enable
			add_filter( 'learn-press/payment-gateway/' . $this->id . '/available', array(
				$this,
				'payir_available'
			), 10, 2 );

			do_action( 'learn_press/payir-add-on/loaded' );

			parent::__construct();
			
			// web hook
			if ( did_action( 'init' ) ) {
				$this->register_web_hook();
			} else {
				add_action( 'init', array( $this, 'register_web_hook' ) );
			}
			add_action( 'learn_press_web_hooks_processed', array( $this, 'web_hook_process_payir' ) );
			
			add_action("learn-press/before-checkout-order-review", array( $this, 'error_message' ));
		}
		
		/**
		 * Register web hook.
		 *
		 * @return array
		 */
		public function register_web_hook() {
			learn_press_register_web_hook( 'payir', 'learn_press_payir' );
		}
	
		/**
		 * Admin payment settings.
		 *
		 * @return array
		 */
		public function get_settings() {

			return apply_filters( 'learn-press/gateway-payment/payir/settings',
				array(
					array(
						'title'   => __( 'Enable', 'learnpress-payir' ),
						'id'      => '[enable]',
						'default' => 'no',
						'type'    => 'yes-no'
					),
					array(
						'type'       => 'text',
						'title'      => __( 'Title', 'learnpress-payir' ),
						'default'    => __( 'Pay.ir', 'learnpress-payir' ),
						'id'         => '[title]',
						'class'      => 'regular-text',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'type'       => 'textarea',
						'title'      => __( 'Description', 'learnpress-payir' ),
						'default'    => __( 'Pay with Pay.ir', 'learnpress-payir' ),
						'id'         => '[description]',
						'editor'     => array(
							'textarea_rows' => 5
						),
						'css'        => 'height: 100px;',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'title'      => __( 'API', 'learnpress-payir' ),
						'id'         => '[api]',
						'type'       => 'text',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					)
				)
			);
		}

		/**
		 * Payment form.
		 */
		public function get_payment_form() {
			/*ob_start();
			$template = learn_press_locate_template( 'form.php', learn_press_template_path() . '/addons/payir-payment/', LP_ADDON_PAYIR_PAYMENT_TEMPLATE );
			include $template;*/

			return "";
		}

		/**
		 * Error message.
		 *
		 * @return array
		 */
		public function error_message() {
			if(!isset($_SESSION))
				session_start();
			if(isset($_SESSION['payir_error']) && intval($_SESSION['payir_error']) === 1) {
				$_SESSION['payir_error'] = 0;
				$template = learn_press_locate_template( 'payment-error.php', learn_press_template_path() . '/addons/payir-payment/', LP_ADDON_PAYIR_PAYMENT_TEMPLATE );
				include $template;
			}
		}
		
		/**
		 * @return mixed
		 */
		public function get_icon() {
			if ( empty( $this->icon ) ) {
				$this->icon = LP_ADDON_PAYIR_PAYMENT_URL . 'assets/images/payir.png';
			}

			return parent::get_icon();
		}

		/**
		 * Check gateway available.
		 *
		 * @return bool
		 */
		public function payir_available() {

			if ( LP()->settings->get( "{$this->id}.enable" ) != 'yes' ) {
				return false;
			}

			return true;
		}
		
		/**
		 * Get form data.
		 *
		 * @return array
		 */
		public function get_form_data() {
			if ( $this->order ) {
				$user            = learn_press_get_current_user();
				$currency_code = learn_press_get_currency()  ;
				if ($currency_code == 'IRR') {
					$amount = $this->order->order_total / 10 ;
				} else {
					$amount = $this->order->order_total ;
				}

				$this->form_data = array(
					'amount'      => $amount,
					'currency'    => strtolower( learn_press_get_currency() ),
					'token'       => $this->token,
					'description' => sprintf( __("Charge for %s","learnpress-payir"), $user->get_data( 'email' ) ),
					'customer'    => array(
						'name'          => $user->get_data( 'display_name' ),
						'billing_email' => $user->get_data( 'email' ),
					),
					'errors'      => isset( $this->posted['form_errors'] ) ? $this->posted['form_errors'] : ''
				);
			}

			return $this->form_data;
		}
		
		/**
		 * Validate form fields.
		 *
		 * @return bool
		 * @throws Exception
		 * @throws string
		 */
		public function validate_fields() {
			$posted        = learn_press_get_request( 'learn-press-payir' );
			$email   = !empty( $posted['email'] ) ? $posted['email'] : "";
			$mobile  = !empty( $posted['mobile'] ) ? $posted['mobile'] : "";
			$error_message = array();
			/*if ( !empty( $email ) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$error_message[] = __( 'Invalid email format.', 'learnpress-payir' );
			}
			if ( !empty( $mobile ) && !preg_match("/^(09)(\d{9})$/", $mobile)) {
				$error_message[] = __( 'Invalid mobile format.', 'learnpress-payir' );
			}
			
			if ( $error = sizeof( $error_message ) ) {
				throw new Exception( sprintf( '<div>%s</div>', join( '</div><div>', $error_message ) ), 8000 );
			}*/
			$this->posted = $posted;

			return $error ? false : true;
		}
		
		/**
		 * Pay.ir payment process.
		 *
		 * @param $order
		 *
		 * @return array
		 * @throws string
		 */
		public function process_payment( $order ) {
			$this->order = learn_press_get_order( $order );
			$payir = $this->send();
			$gateway_url = $this->gatewayUrl.$this->transId;
			
			$json = array(
				'result'   => $payir ? 'success' : 'fail',
				'redirect'   => $payir ? $gateway_url : ''
			);

			return $json;
		}

		
		/**
		 * Send.
		 *
		 * @return bool|object
		 */
		public function send() {
			if ( $this->get_form_data() ) {
				$redirect = urlencode(get_site_url() . '/?' . learn_press_get_web_hook( 'payir' ) . '=1&order_id='.$this->order->get_id());
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->sendUrl);
				curl_setopt($ch, CURLOPT_POSTFIELDS,"api=".$this->api."&amount=".$this->form_data['amount']."&redirect=$redirect&factorNumber=".$this->order->get_id());
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				$result = curl_exec($ch);
				curl_close($ch);
				$result = json_decode($result);
				if($result->status) {
					$this->transId = $result->transId;
					return true;
				}
			}
			return false;
		}

		/**
		 * Handle a web hook
		 *
		 */
		public function web_hook_process_payir() {
			$request = $_REQUEST;
			
			if(isset($request['learn_press_payir']) && intval($request['learn_press_payir']) === 1) {
				$transId = $_POST['transId'];
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->verifyUrl);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "api=".$this->api."&transId=$transId");
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				$result = curl_exec($ch);
				curl_close($ch);
				$result = json_decode($result);
				
				$order = LP_Order::instance( $request['order_id'] );
				$currency_code = learn_press_get_currency();
				if ($currency_code == 'IRR') {
					$amount = $order->order_total / 10 ;
				} else {
					$amount = $order->order_total ;
				}
				
				if(intval($result->status) === 1 && $result->amount ==  $amount) {
					$this->authority = intval($_GET['Authority']);
					$this->payment_status_completed($order , $request);
					wp_redirect(esc_url( $this->get_return_url( $order ) ));
					exit();
				}
				if(!isset($_SESSION))
					session_start();
				$_SESSION['payir_error'] = 1;
				wp_redirect(esc_url( learn_press_get_page_link( 'checkout' )  ));
				exit();
			}
		}
		
		/**
		 * Handle a completed payment
		 *
		 * @param LP_Order
		 * @param request
		 */
		protected function payment_status_completed( $order, $request ) {

			// order status is already completed
			if ( $order->has_status( 'completed' ) ) {
				exit;
			}

			$this->payment_complete( $order, ( !empty( $request['transId'] ) ? $request['transId'] : '' ), __( 'Payment has been successfully completed', 'learnpress-payir' ) );

		}

		/**
		 * Handle a pending payment
		 *
		 * @param  LP_Order
		 * @param  request
		 */
		protected function payment_status_pending( $order, $request ) {
			$this->payment_status_completed( $order, $request );
		}

		/**
		 * @param        LP_Order
		 * @param string $txn_id
		 * @param string $note - not use
		 */
		public function payment_complete( $order, $trans_id = '', $note = '' ) {
			$order->payment_complete( $trans_id );
		}
	}
}