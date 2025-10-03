<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WIS_Summary_Generator {

    public function generate_summary( $year, $month, $document_types ) {
        $orders = $this->get_orders_for_period( $year, $month, $document_types );
        return $this->group_by_payment_method( $orders );
    }

    private function get_orders_for_period( $year, $month, $document_types ) {
        $start_date = strtotime( "{$year}-{$month}-01 00:00:00" );
        $days_in_month = date('t', $start_date);
        $end_date = strtotime( "{$year}-{$month}-{$days_in_month} 23:59:59" );

        $docs_meta_query = ['relation' => 'OR'];
        if ( in_array( 'invoice', $document_types ) ) {
            $docs_meta_query[] = ['key' => '_wcpdf_invoice_number', 'compare' => 'EXISTS'];
        }
        if ( in_array( 'receipt', $document_types ) ) {
            $docs_meta_query[] = ['key' => '_wcpdf_receipt_number', 'compare' => 'EXISTS'];
        }

        if ( count( $docs_meta_query ) <= 1 ) {
            return [];
        }

        $dates_meta_query = ['relation' => 'OR'];
        if ( in_array( 'invoice', $document_types ) ) {
            $dates_meta_query[] = ['key' => '_wcpdf_invoice_date', 'value' => [$start_date, $end_date], 'compare' => 'BETWEEN', 'type' => 'NUMERIC'];
        }
        if ( in_array( 'receipt', $document_types ) ) {
            $dates_meta_query[] = ['key' => '_wcpdf_receipt_date', 'value' => [$start_date, $end_date], 'compare' => 'BETWEEN', 'type' => 'NUMERIC'];
        }

        $args = [
            'type'   => 'shop_order',
            'status' => ['completed', 'refunded'], // Include both completed and refunded statuses
            'limit'  => -1,
            'meta_query' => [
                'relation' => 'AND',
                $docs_meta_query,
                $dates_meta_query,
            ]
        ];

        return wc_get_orders( $args );
    }

    private function group_by_payment_method( $orders ) {
        $summary = [];
        $grand_total = 0;
        $grand_total_tax = 0;
        $grand_total_count = 0;

        foreach ( $orders as $order ) {
            $payment_method_title = $order->get_payment_method_title();
            if ( ! isset( $summary[ $payment_method_title ] ) ) {
                $summary[ $payment_method_title ] = ['count' => 0, 'total' => 0, 'total_tax' => 0];
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

        ksort($summary);

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
}
