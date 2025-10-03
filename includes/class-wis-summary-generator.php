<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WIS_Summary_Generator {

    public function generate_summary( $year, $month, $document_types ) {
        $orders = $this->get_orders( $year, $month, $document_types );
        if ( empty( $orders ) ) {
            return array();
        }
        return $this->group_by_payment_method( $orders );
    }

    private function get_orders( $year, $month, $document_types ) {
        $start_date = strtotime( "{$year}-{$month}-01 00:00:00" );
        $days_in_month = date('t', $start_date);
        $end_date = strtotime( "{$year}-{$month}-{$days_in_month} 23:59:59" );

        $docs_meta_query = array( 'relation' => 'OR' );
        if ( in_array( 'invoice', $document_types ) ) {
            $docs_meta_query[] = array(
                'key'     => '_wcpdf_invoice_number',
                'compare' => 'EXISTS',
            );
        }

        if ( in_array( 'receipt', $document_types ) ) {
            $docs_meta_query[] = array(
                'key'     => '_wcpdf_receipt_number',
                'compare' => 'EXISTS',
            );
        }

        // If no document types are selected, return no orders.
        if ( count( $docs_meta_query ) <= 1 ) {
            return [];
        }

        $dates_meta_query = array( 'relation' => 'OR' );
        if ( in_array( 'invoice', $document_types ) ) {
            $dates_meta_query[] = array(
                'key'     => '_wcpdf_invoice_date',
                'value'   => array( $start_date, $end_date ),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            );
        }

        if ( in_array( 'receipt', $document_types ) ) {
            $dates_meta_query[] = array(
                'key'     => '_wcpdf_receipt_date',
                'value'   => array( $start_date, $end_date ),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            );
        }

        $args = array(
            'status' => 'completed',
            'limit'  => -1,
            'meta_query' => array(
                'relation' => 'AND',
                $docs_meta_query,
                $dates_meta_query,
            )
        );

        return wc_get_orders( $args );
    }

    private function group_by_payment_method( $orders ) {
        $summary = array();
        $total_sum = 0;
        $total_tax_sum = 0;

        foreach ( $orders as $order ) {
            $payment_method_title = $order->get_payment_method_title();
            if ( ! isset( $summary[ $payment_method_title ] ) ) {
                $summary[ $payment_method_title ] = array(
                    'count' => 0,
                    'total' => 0,
                    'total_tax' => 0,
                );
            }
            $summary[ $payment_method_title ]['count']++;
            $summary[ $payment_method_title ]['total'] += $order->get_total();
            $summary[ $payment_method_title ]['total_tax'] += $order->get_total_tax();
            $total_sum += $order->get_total();
            $total_tax_sum += $order->get_total_tax();
        }

        // Add total sum to the summary
        $summary['all'] = array(
            'total' => $total_sum,
            'total_tax' => $total_tax_sum,
        );

        return $summary;
    }

    public function get_summary_html( $summary ) {
        ob_start();
        include WIS_PLUGIN_PATH . 'templates/summary-results-table.php';
        return ob_get_clean();
    }
}
