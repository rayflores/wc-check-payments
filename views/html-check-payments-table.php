<?php
/**
 * The HTML for the check payments table.
 *
 * @package WC_Check_Payments
 */

if ( $this->payments ) : ?>

<div class="payments-table <?php echo count( $this->payments( get_the_ID() ) ) > 3 ? 'has-scrollbar' : ''; ?>">
	<table class="widefat striped">
		<thead>
			<tr>
				<th>Date</th>
				<th class="amount-column">Amount</th>
			</tr>
		</thead>
		<tbody>

			<?php foreach ( $this->payments( get_the_ID() ) as $payment ) : ?>
				<tr>
					<td>
						
						<?php echo esc_html( $payment->check_date ) . ' via: Check #' . esc_html( $payment->check_number ); ?>

					</td>
					<td class="amount-column">
                        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscape 
						echo wc_price( $payment->check_amount );
						?>

					</td>
				</tr>

			<?php endforeach; ?>

		</tbody>
	</table>
</div>

<?php else : ?>

<div class="payments-table-placeholder">Your payments will show up here.</div>

<?php endif; ?>
