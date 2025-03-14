<?php
/**
 * The HTML for the check payments table.
 *
 * @package WC_Check_Payments
 */

defined( 'ABSPATH' ) || die; ?>

<?php
$order = wc_get_order( get_the_ID() );
?>
<div id="wc-cp-main">

	<div class="cp-global-notice" hidden></div>
	<div data-cp-panel="cp-main" <?php echo $this->payments( get_the_ID() ) ? 'data-cp-current-panel' : ''; ?>>

		<?php require plugin_dir_path( __FILE__ ) . 'html-check-payments-table.php'; ?>
		

		<div class="action-button-bar">
			<div class="action-buttons">
				<button type="button" class="button button-primary right" data-cpopen-panel="check">Add Check Payment</button>
			</div>
		</div>
	</div>
	<div id="check" data-cp-panel="check" <?php echo $this->payments( get_the_ID() ) ? '' : 'data-cp-current-panel'; ?>>
		<div class="panel-notice" hidden></div>
		<div class="cp-transaction-form">
			<input type="hidden" id="order_id" name="order_id" value="<?php echo $order->get_id(); ?>"/>
			<div class="field-row">
				<div class="field-column">
					<label for="check_date">Check Date</label>
					<input type="date" id="check_date" name="check_date" class="field" placeholder="mm/dd/yyyy" data-cp-required data-cp-field-name="check date"/>
				</div>
				<div class="field-column">
					<label for="check_number">Check Number</label>
					<input type="text" id="check_number" name="check_number" class="field" data-cp-required data-cp-field-name="check number" placeholder="1005"/>
				</div>
			</div>
			<div class="field-row">
				<div class="field-column">
					<label for="check_amount">Check Amount</label>
					<div class="cp-check-amount-field-and-btn-container">
						<input type="number" min="0" step="any" id="check_amount" name="check_amount" class="field cp-money-field" data-cp-required data-cp-field-name="check amount" placeholder="0.00"/>
						<button type="button" id="cp-check-amount-autofill-btn" class="check-amount-autofill-btn" title="Autofill unpaid balance (<?php echo $order->get_total(); ?>)" style="display: inline-block;">of <?php echo $order->get_total(); ?></button>
					</div>
				</div>
			</div>        
		</div>
		<div class="action-button-bar">
			<div class="action-buttons">
				<button type="button" id="cp-check-btn" class="button button-primary">Process Check $0.00</button>
				<button type="button" class="button cp-close right" data-cpclose-panel>Close</button>
			</div>
		</div>
	</div>
</div>
