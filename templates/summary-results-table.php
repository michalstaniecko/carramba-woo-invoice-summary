<?php
/**
 * Template for displaying the summary results table.
 *
 * @var array $summary The summary data.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( empty( $summary ) || ( count( $summary ) === 1 && isset( $summary['all'] ) && $summary['all']['total'] == 0 ) ) {
    echo '<p>' . __( 'No matching orders found.', 'woocommerce-invoice-summary' ) . '</p>';
    return;
}

$total_sum = $summary['all']['total'];
$total_tax_sum = $summary['all']['total_tax'];
unset( $summary['all'] );

?>
<table class="widefat fixed" cellspacing="0">
    <thead>
        <tr>
            <th><?php _e( 'Payment Method', 'woocommerce-invoice-summary' ); ?></th>
            <th><?php _e( 'Number of Orders', 'woocommerce-invoice-summary' ); ?></th>
            <th style="text-align:right"><?php _e( 'Total VAT', 'woocommerce-invoice-summary' ); ?></th>
            <th style="text-align:right"><?php _e( 'Total Amount', 'woocommerce-invoice-summary' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $summary as $method => $data ) : ?>
            <tr>
                <td><?php echo esc_html( $method ); ?></td>
                <td><?php echo esc_html( $data['count'] ); ?></td>
                <td style="text-align:right"><?php echo esc_html( number_format( $data['total_tax'], 2 ) ); ?></td>
                <td style="text-align:right"><?php echo esc_html( number_format( $data['total'], 2 ) ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="2" style="text-align:right"><strong><?php _e( 'Total:', 'woocommerce-invoice-summary' ); ?></strong></th>
            <th style="text-align:right"><strong><?php echo esc_html( number_format( $total_tax_sum, 2 ) ); ?></strong></th>
            <th style="text-align:right"><strong><?php echo esc_html( number_format( $total_sum, 2 ) ); ?></strong></th>
        </tr>
    </tfoot>
</table>
