<?php
/**
 * The HTML for the check payments table.
 *
 * @package WC_Check_Payments
 */

if ( $this->payments ) : ?>

<div class="payments-table <?php echo count( $this->payments ) > 3 ? 'has-scrollbar' : ''; ?>">
	<table class="widefat striped">
		<thead>
			<tr>
				<th>Date</th>
				<th class="amount-column">Amount</th>
			</tr>
		</thead>
		<tbody>

			<?php foreach ( $this->payments as $payment ) : ?>

				<tr>
					<td>
						
						<?php echo esc_html( $payment['date'] ) . ' via: Check #' . esc_html( $payment['check_number'] ); ?>

					</td>
					<td class="amount-column">

						<?php echo esc_html( wc_price( $payment['amount'], array( 'currency' => $payment['currency'] ) ) ); ?>

					</td>
				</tr>

			<?php endforeach; ?>

		</tbody>
	</table>
</div>

<?php else : ?>

<div class="check-payments-table-placeholder">Your payments will show up here.</div>

<?php endif; ?>
