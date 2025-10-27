<?php
/**
 * Plugin Name: WooCommerce Invoice Summary
 * Description: Generates a monthly summary of invoices grouped by payment method.
 * Version: 1.4.2
 * Author: iHumbak.website
 * Text Domain: woocommerce-invoice-summary
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'WIS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WIS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class WIS_Plugin {

    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    public function init() {
        // Check for dependencies
        if ( ! $this->check_dependencies() ) {
            add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
            return;
        }

        $this->load_files();

        // Initialize other classes
        new WIS_Admin_Menu();
        new WIS_Rest_Api();
    }

    private function check_dependencies() {
        return is_plugin_active( 'woocommerce/woocommerce.php' ) && is_plugin_active( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php' ) && is_plugin_active( 'woocommerce-pdf-ips-pro/woocommerce-pdf-ips-pro.php' );
    }

    public function dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php _e( 'WooCommerce Invoice Summary requires WooCommerce, WooCommerce PDF Invoices & Packing Slips, and the Professional add-on to be active.', 'woocommerce-invoice-summary' ); ?>
            </p>
        </div>
        <?php
    }

    private function load_files() {
        require_once WIS_PLUGIN_PATH . 'includes/class-wis-admin-menu.php';
        require_once WIS_PLUGIN_PATH . 'includes/class-wis-summary-generator.php';
        require_once WIS_PLUGIN_PATH . 'includes/class-wis-rest-api.php';
    }
}

// Instantiate the plugin.
WIS_Plugin::get_instance();
