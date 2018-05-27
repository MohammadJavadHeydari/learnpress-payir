<?php
/**
 * Template for displaying Pay.ir payment error message.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/payir-payment/payment-error.php.
 *
 * @author   Mohmmad Javad Heydari 
 * @package  LearnPress/Payir/Templates
 * @version  1.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php $settings = LP()->settings; ?>

<div class="learn-press-message error ">
	<div><?php echo __( 'Transation failed', 'learnpress-payir' ); ?></div>		
</div>
