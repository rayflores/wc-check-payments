<?php
/**
 * The HTML for the check payments table.
 *
 * @package WC_Check_Payments
 */

defined( 'ABSPATH' ) || die; ?>

<div id="woo-mp-main">

	<div class="global-notice" hidden></div>

	<div data-panel="main" <?php echo $this->charges ? 'data-current-panel' : ''; ?>>

		<?php require plugin_dir_path( __FILE__ ) . 'views/html-check-payments-table.php'; ?>
		

		<div class="action-button-bar">
			<div class="action-buttons">
				<button type="button" class="button button-primary right" data-cp-open-panel="check">Add Check Payment</button>
			</div>
		</div>
	</div>
	<div id="check" data-panel="check" <?php echo $this->charges ? '' : 'data-cp-current-panel'; ?>>
		<div class="panel-notice" hidden></div>
		<div class="cp-form">
			
		</div>
		<div class="action-button-bar">
			<div class="action-buttons">
				<button type="button" class="button cp-close right" data-cp-close-panel>Close</button>
			</div>
		</div>
	</div>

	<?php $this->template( 'notice-template' ); ?>

	<?php $this->template( 'notice-sections-template' ); ?>

</div>
