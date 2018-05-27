<?php
/*
Plugin Name: LearnPress - Pay.ir Payment
Plugin URI: http://midiyasoft.com/learnpress
Description: Pay.ir payment gateway for LearnPress.
Author: Midiya Soft
Version: 1.0.0
Author URI: http://midiyasoft.com
Tags: learnpress, pay.ir
Text Domain: learnpress-payir
Domain Path: /languages/
*/

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

define( 'LP_ADDON_PAYIR_PAYMENT_FILE', __FILE__ );
define( 'LP_ADDON_PAYIR_PAYMENT_VER', '1.0.0' );
define( 'LP_ADDON_PAYIR_PAYMENT_REQUIRE_VER', '1.0.0' );

/**
 * Class LP_Addon_Payir_Payment_Preload
 */
class LP_Addon_Payir_Payment_Preload {

	/**
	 * LP_Addon_Payir_Payment_Preload constructor.
	 */
	public function __construct() {
		load_plugin_textdomain( 'learnpress-payir', false, basename( dirname(__FILE__) ) . '/languages' );
		add_action( 'learn-press/ready', array( $this, 'load' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Load addon
	 */
	public function load() {
		LP_Addon::load( 'LP_Addon_Payir_Payment', 'inc/load.php', __FILE__ );
		remove_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Admin notice
	 */
	public function admin_notices() {
		?>
        <div class="error">
            <p><?php echo wp_kses(
					sprintf(
						__( '<strong>%s</strong> addon version %s requires %s version %s or higher is <strong>installed</strong> and <strong>activated</strong>.', 'learnpress-payir' ),
						__( 'LearnPress Pay.ir Payment', 'learnpress-payir' ),
						LP_ADDON_PAYIR_PAYMENT_VER,
						sprintf( '<a href="%s" target="_blank"><strong>%s</strong></a>', admin_url( 'plugin-install.php?tab=search&type=term&s=learnpress' ), __( 'LearnPress', 'learnpress-payir' ) ),
						LP_ADDON_PAYIR_PAYMENT_REQUIRE_VER
					),
					array(
						'a'      => array(
							'href'  => array(),
							'blank' => array()
						),
						'strong' => array()
					)
				); ?>
            </p>
        </div>
		<?php
	}
}

new LP_Addon_Payir_Payment_Preload();