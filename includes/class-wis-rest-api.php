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
}
