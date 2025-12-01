<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WIS_Rest_Api {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'wis/v1', '/summary', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_summary_data' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );

        register_rest_route( 'wis/v1', '/summary/export-csv', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'export_csv' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );
    }

    public function permissions_check() {
        return current_user_can( 'manage_woocommerce' );
    }

    public function get_summary_data( WP_REST_Request $request ) {
        $year = $request->get_param( 'year' );
        $month = $request->get_param( 'month' );
        $document_types = $request->get_param( 'document_types');

        if ( ! $year || ! $month || ! $document_types ) {
            return new WP_Error( 'bad_request', 'Missing parameters', array( 'status' => 400 ) );
        }

        $generator = new WIS_Summary_Generator();
        $summary_data = $generator->generate_summary( (int) $year, (int) $month, (array) $document_types );
        $html = $generator->get_summary_html( $summary_data );

        return new WP_REST_Response( array( 'html' => $html ), 200 );
    }

    public function export_csv( WP_REST_Request $request ) {
        $year = $request->get_param( 'year' );
        $month = $request->get_param( 'month' );
        $document_types = $request->get_param( 'document_types');

        if ( ! $year || ! $month || ! $document_types ) {
            return new WP_Error( 'bad_request', 'Missing parameters', array( 'status' => 400 ) );
        }

        $generator = new WIS_Summary_Generator();
        $summary_data = $generator->generate_summary( (int) $year, (int) $month, (array) $document_types );

        if ( empty( $summary_data ) || ( count( $summary_data ) === 1 && isset( $summary_data['all'] ) && $summary_data['all']['count'] == 0 ) ) {
            return new WP_Error( 'no_data', __( 'No matching documents found for the selected period.', 'woocommerce-invoice-summary' ), array( 'status' => 404 ) );
        }

        $csv_content = $generator->generate_csv( $summary_data, (int) $year, (int) $month, (array) $document_types );

        // Generate filename with document type
        $month_name = date_i18n( 'F', mktime( 0, 0, 0, $month, 1 ) );
        $doc_type_slug = $this->get_document_type_slug( (array) $document_types );
        $filename = sprintf( 'invoice-summary-%s-%s-%s.csv', $doc_type_slug, $month_name, $year );

        return new WP_REST_Response(
            array(
                'csv' => $csv_content,
                'filename' => $filename
            ),
            200
        );
    }

    private function get_document_type_slug( $document_types ) {
        // Sort to ensure consistent naming
        sort( $document_types );

        $type_map = array(
            'invoice' => 'invoices',
            'receipt' => 'receipts',
            'refund-invoice' => 'refunds-invoices',
            'refund-receipt' => 'refunds-receipts'
        );

        $slugs = array();
        foreach ( $document_types as $type ) {
            if ( isset( $type_map[ $type ] ) ) {
                $slugs[] = $type_map[ $type ];
            }
        }

        // If multiple types selected, join them with hyphen
        return ! empty( $slugs ) ? implode( '-', $slugs ) : 'summary';
    }
}
