<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WIS_Admin_Menu {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
    }

    public function add_admin_page() {
        $hook = add_submenu_page(
            'woocommerce',
            __( 'Invoice Summary', 'woocommerce-invoice-summary' ),
            __( 'Invoice Summary', 'woocommerce-invoice-summary' ),
            'manage_woocommerce',
            'wc-invoice-summary',
            array( $this, 'render_summary_page' )
        );
        add_action( "admin_print_scripts-{$hook}", array( $this, 'enqueue_scripts' ) );
    }

    public function render_summary_page() {
        include_once WIS_PLUGIN_PATH . 'templates/summary-page.php';
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'wis-summary-generator',
            WIS_PLUGIN_URL . 'assets/js/summary-generator.js',
            array( 'jquery', 'wp-api-request' ), // Add wp-api-request dependency
            '1.4.1',
            true
        );

        wp_localize_script( 'wis-summary-generator', 'wis_ajax', array(
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'rest_url' => rest_url('wis/v1/summary')
        ) );
    }
}
