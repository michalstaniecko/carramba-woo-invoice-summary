<?php
/**
 * Template for displaying the summary results table.
 *
 * @var array $summary The summary data.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( empty( $summary ) || ( count( $summary ) === 1 && isset( $summary['all'] ) && $summary['all']['count'] == 0 ) ) {
    echo '<p>' . __( 'No matching documents found for the selected period.', 'woocommerce-invoice-summary' ) . '</p>';
    return;
}

$totals = $summary['all'];
unset( $summary['all'] );

?>
<div style="margin-bottom: 10px;">
    <button type="button" id="wis-export-csv" class="button button-secondary">
        <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
        <?php _e( 'Export to CSV', 'woocommerce-invoice-summary' ); ?>
    </button>
</div>
<table class="widefat fixed" cellspacing="0">
    <thead>
        <tr>
            <th><?php _e( 'Payment Method', 'woocommerce-invoice-summary' ); ?></th>
            <th style="text-align:center"><?php _e( 'Number of Orders', 'woocommerce-invoice-summary' ); ?></th>
            <th style="text-align:right"><?php _e( 'Total VAT', 'woocommerce-invoice-summary' ); ?></th>
            <th style="text-align:right"><?php _e( 'Total Amount', 'woocommerce-invoice-summary' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $summary as $method => $data ) : ?>
            <?php if ($data['count'] == 0 && $data['total'] == 0) continue; ?>
            <tr>
                <td><?php echo esc_html( $method ); ?></td>
                <td style="text-align:center"><?php echo esc_html( $data['count'] ); ?></td>
                <td style="text-align:right"><?php echo wp_kses_post( wc_price( $data['total_tax'] ) ); ?></td>
                <td style="text-align:right"><?php echo wp_kses_post( wc_price( $data['total'] ) ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="1" style="text-align:right"><strong><?php _e( 'Total:', 'woocommerce-invoice-summary' ); ?></strong></th>
            <th style="text-align:center"><strong><?php echo esc_html( $totals['count'] ); ?></strong></th>
            <th style="text-align:right"><strong><?php echo wp_kses_post( wc_price( $totals['total_tax'] ) ); ?></strong></th>
            <th style="text-align:right"><strong><?php echo wp_kses_post( wc_price( $totals['total'] ) ); ?></strong></th>
        </tr>
    </tfoot>
</table>
