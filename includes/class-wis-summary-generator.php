<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WIS_Summary_Generator {

	public function generate_summary( $year, $month, $document_types ) {
		$orders = $this->get_orders_for_period( $year, $month, $document_types );
		$refunds = $this->get_refunds_for_period( $year, $month, $document_types );
		return $this->group_by_payment_method( array_merge( $orders, $refunds ) );
	}

	private function get_orders_for_period( $year, $month, $document_types ) {
		$start_date = strtotime( "{$year}-{$month}-01 00:00:00" );
		$days_in_month = date( 't', $start_date );
		$end_date = strtotime( "{$year}-{$month}-{$days_in_month} 23:59:59" );

		$docs_meta_query = [ 'relation' => 'OR' ];
		if ( in_array( 'invoice', $document_types ) ) {
			$docs_meta_query[] = [ 'key' => '_wcpdf_invoice_number', 'compare' => 'EXISTS' ];
		}
		if ( in_array( 'receipt', $document_types ) ) {
			$docs_meta_query[] = [ 'key' => '_wcpdf_receipt_number', 'compare' => 'EXISTS' ];
		}

		if ( count( $docs_meta_query ) <= 1 ) {
			return [];
		}

		$dates_meta_query = [ 'relation' => 'OR' ];
		if ( in_array( 'invoice', $document_types ) ) {
			$dates_meta_query[] = [ 'key' => '_wcpdf_invoice_date', 'value' => [ $start_date, $end_date ], 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ];
		}
		if ( in_array( 'receipt', $document_types ) ) {
			$dates_meta_query[] = [ 'key' => '_wcpdf_receipt_date', 'value' => [ $start_date, $end_date ], 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ];
		}

		$args = [
			'type'       => 'shop_order',
			'status'     => [ 'completed', 'refunded' ], // Include both completed and refunded statuses
			'limit'      => -1,
			'meta_query' => [
				'relation' => 'AND',
				$docs_meta_query,
				$dates_meta_query,
			]
		];

		return wc_get_orders( $args );
	}

	private function get_refunds_for_period( $year, $month, $document_types ) {
		// Only process if refund document types are requested
		if ( ! in_array( 'refund-invoice', $document_types ) && ! in_array( 'refund-receipt', $document_types ) ) {
			return [];
		}

		$start_date = strtotime( "{$year}-{$month}-01 00:00:00" );
		$days_in_month = date( 't', $start_date );
		$end_date = strtotime( "{$year}-{$month}-{$days_in_month} 23:59:59" );

		// Get all refunds for the period
		$args = [
			'type'         => 'shop_order_refund',
			'limit'        => -1,
			'date_created' => $start_date . '...' . $end_date,
		];

		$all_refunds = wc_get_orders( $args );
		$filtered_refunds = [];

		foreach ( $all_refunds as $refund ) {
			// Check if parent order has _billing_vat meta (receipt refund) or not (invoice refund)
			$has_billing_vat = get_post_meta( $refund->get_parent_id(), '_billing_vat', true ) !== '';

			if ( $has_billing_vat === false ) {
				$order_parent = wc_get_order( $refund->get_parent_id() );
				$has_billing_vat = ! empty( $order_parent->get_meta( '_billing_vat', true ) );
			}

			if ( ! $has_billing_vat && in_array( 'refund-receipt', $document_types ) ) {
				$filtered_refunds[] = $refund;
			} elseif ( $has_billing_vat && in_array( 'refund-invoice', $document_types ) ) {
				$filtered_refunds[] = $refund;
			}
		}

		return $filtered_refunds;
	}

	private function group_by_payment_method( $orders ) {
		$summary = [];
		$grand_total = 0;
		$grand_total_tax = 0;
		$grand_total_count = 0;

		foreach ( $orders as $order ) {
			// Get payment method - for refunds, get it from parent order
			if ( $order->get_type() === 'shop_order_refund' ) {
				$parent_order = wc_get_order( $order->get_parent_id() );
				$payment_method_title = $parent_order ? $parent_order->get_payment_method_title() : __( 'Unknown', 'woocommerce-invoice-summary' );
			} else {
				$payment_method_title = $order->get_payment_method_title();
			}

			if ( ! isset( $summary[ $payment_method_title ] ) ) {
				$summary[ $payment_method_title ] = [ 'count' => 0, 'total' => 0, 'total_tax' => 0 ];
			}

			$net_total = $order->get_total();
			$net_tax = $order->get_total_tax();

			$summary[ $payment_method_title ]['count']++;
			$summary[ $payment_method_title ]['total'] += $net_total;
			$summary[ $payment_method_title ]['total_tax'] += $net_tax;

			$grand_total += $net_total;
			$grand_total_tax += $net_tax;
			$grand_total_count++;
		}

		ksort( $summary );

		$summary['all'] = [
			'total'     => $grand_total,
			'total_tax' => $grand_total_tax,
			'count'     => $grand_total_count,
		];

		return $summary;
	}

	public function get_summary_html( $summary ) {
		ob_start();
		include WIS_PLUGIN_PATH . 'templates/summary-results-table.php';
		return ob_get_clean();
	}

	public function generate_csv( $summary, $year, $month, $document_types ) {
		if ( empty( $summary ) || ( count( $summary ) === 1 && isset( $summary['all'] ) && $summary['all']['count'] == 0 ) ) {
			return '';
		}

		$totals = $summary['all'];
		unset( $summary['all'] );

		// Start output buffering
		ob_start();
		$output = fopen( 'php://output', 'w' );

		// Add BOM for Excel UTF-8 compatibility
		fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

		// Add header with report details
		fputcsv( $output, array( __( 'Invoice Summary Report', 'woocommerce-invoice-summary' ) ) );
		fputcsv( $output, array( __( 'Period:', 'woocommerce-invoice-summary' ) . ' ' . date_i18n( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ) );
		fputcsv( $output, array( __( 'Document Types:', 'woocommerce-invoice-summary' ) . ' ' . implode( ', ', $this->get_document_type_labels( $document_types ) ) ) );
		fputcsv( $output, array( __( 'Generated:', 'woocommerce-invoice-summary' ) . ' ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) );
		fputcsv( $output, array() ); // Empty row

		// Add column headers
		fputcsv( $output, array(
			__( 'Payment Method', 'woocommerce-invoice-summary' ),
			__( 'Number of Orders', 'woocommerce-invoice-summary' ),
			__( 'Total VAT', 'woocommerce-invoice-summary' ),
			__( 'Total Amount', 'woocommerce-invoice-summary' )
		) );

		// Add data rows
		foreach ( $summary as $method => $data ) {
			if ( $data['count'] == 0 && $data['total'] == 0 ) {
				continue;
			}

			fputcsv( $output, array(
				$method,
				$data['count'],
				number_format( $data['total_tax'], 2, '.', '' ),
				number_format( $data['total'], 2, '.', '' )
			) );
		}

		// Add totals row
		fputcsv( $output, array(
			__( 'Total:', 'woocommerce-invoice-summary' ),
			$totals['count'],
			number_format( $totals['total_tax'], 2, '.', '' ),
			number_format( $totals['total'], 2, '.', '' )
		) );

		fclose( $output );
		return ob_get_clean();
	}

	private function get_document_type_labels( $document_types ) {
		$labels = array();
		$type_map = array(
			'invoice' => __( 'Invoices', 'woocommerce-invoice-summary' ),
			'receipt' => __( 'Receipts', 'woocommerce-invoice-summary' ),
			'refund-invoice' => __( 'Refunds (Invoices)', 'woocommerce-invoice-summary' ),
			'refund-receipt' => __( 'Refunds (Receipts)', 'woocommerce-invoice-summary' )
		);

		foreach ( $document_types as $type ) {
			if ( isset( $type_map[ $type ] ) ) {
				$labels[] = $type_map[ $type ];
			}
		}

		return $labels;
	}
}
