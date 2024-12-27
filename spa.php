<?php
/*
Plugin Name: Master Spa Plugin
Description: Importă produse prin cURL și trimite o notificare la achiziție prin cURL în WooCommerce.
Version: 1.0
Author: Codrut
Author URI: https://befu.ro
*/


use import\products;
session_start(); // Start the session

function include_plugin_files(): void {
	require_once plugin_dir_path( __FILE__ ) . 'bomba.php';
	require_once plugin_dir_path( __FILE__ ) . 'helper/soap.php';

	require_once plugin_dir_path( __FILE__ ) . 'admin/extra_fields.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/order.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom_fields.php';

	require_once plugin_dir_path( __FILE__ ) . 'settings.php';
	require_once plugin_dir_path( __FILE__ ) . 'finalize_order.php';

	require_once plugin_dir_path( __FILE__ ) . 'import/services.php';
	require_once plugin_dir_path( __FILE__ ) . 'import/products.php';
	require_once plugin_dir_path( __FILE__ ) . 'import/prices.php';


	require_once plugin_dir_path( __FILE__ ) . 'order/order.php';
	require_once plugin_dir_path( __FILE__ ) . 'order/customer.php';
	require_once plugin_dir_path( __FILE__ ) . 'order/partner.php';
	require_once plugin_dir_path( __FILE__ ) . 'frontend/updateItems.php';


}


// Hook for plugin activation
register_activation_hook( __FILE__, 'create_custom_woocommerce_attribute' );

function create_custom_woocommerce_attribute(): void {
	global $wpdb;

	// Define attribute name and slug
	$attribute_name = 'Optiuni';
	$attribute_slug = wc_sanitize_taxonomy_name( stripslashes( 'pricing-options' ) );

	// Check if the attribute already exists
	$attribute_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
		$attribute_slug
	) );

	// If attribute doesn't exist, create it
	if ( ! $attribute_id ) {
		$args = [
			'attribute_label'   => $attribute_name,
			'attribute_name'    => $attribute_slug,
			'attribute_type'    => 'select',
			'attribute_orderby' => 'menu_order',
			'attribute_public'  => 0, // Set to 1 if you want it to be visible on product pages
		];

		// Insert attribute into WooCommerce attribute taxonomy table
		$wpdb->insert( "{$wpdb->prefix}woocommerce_attribute_taxonomies", $args );

		// Flush WooCommerce cache and permalinks
		delete_transient( 'wc_attribute_taxonomies' );
		wc_delete_product_transients();
	}
}


// Add custom rewrite rule
function add_custom_endpoint(): void {
	add_rewrite_rule( '^resend_order/([0-9]+)/?', 'index.php?resend_order=$matches[1]', 'top' );
	add_rewrite_tag( '%resend_order%', '([0-9]+)' );
}

add_action( 'init', 'add_custom_endpoint' );

// Handle the custom endpoint
function handle_custom_endpoint(): void {
	global $wp_query;
	if ( isset( $wp_query->query_vars['resend_order'] ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			$order_id       = intval( $wp_query->query_vars['resend_order'] );
			$finalize_order = new finalize_order();
			$finalize_order->onOrderCompleted( $order_id );
			// Capture the referrer URL
			$referrer = wp_get_referer();
			if ( $referrer ) {
				wp_safe_redirect( $referrer );
				exit;
			}
			exit;
		} else {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}
	}

}

add_action( 'template_redirect', 'handle_custom_endpoint' );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
error_reporting( E_ALL );

class spa {

	private products $products;
	private bomba $bomba;

	public function __construct() {
		include_plugin_files();
		$this->products = new products();
		$this->bomba = new bomba();
		
		// Hook pentru importul periodic de produse
		add_action( 'init', [ $this, 'schedule_import' ] );
		add_action( 'woocommerce_curl_import_event', [ $this, 'import_products' ] );


		// Programare/deprogramare cron la activare/dezactivare
		register_activation_hook( __FILE__, [ $this, 'activate_cron' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate_cron' ] );

		// Adaugă un endpoint personalizat pentru a rula importul manual
		add_action( 'admin_init', [ $this, 'manual_import_endpoint' ] );
		add_action( 'init', [ $this, 'manual_bomba' ] );
	}

	public function manual_import_endpoint() {
		// Verifică dacă acțiunea personalizată a fost solicitată și dacă utilizatorul are permisiunile necesare
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'run_import_products' && current_user_can( 'manage_options' ) ) {

			$this->products->import_products();
			wp_die( 'Importul de produse a fost executat cu succes.' );
		}
	}

	public function manual_bomba() {
		// Verifică dacă acțiunea personalizată a fost solicitată și dacă utilizatorul are permisiunile necesare
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'client') {

			$this->bomba->run();
			wp_die( 'Gata.' );
		}
	}

	public function schedule_import() {
		if ( ! wp_next_scheduled( 'woocommerce_curl_import_event' ) ) {
			wp_schedule_event( time(), 'hourly', 'woocommerce_curl_import_event' );
		}
	}

	public function activate_cron() {
		$this->schedule_import();
	}

	public function deactivate_cron() {
		wp_clear_scheduled_hook( 'woocommerce_curl_import_event' );
	}


}

new spa();

function enqueue_custom_styles() {
	wp_enqueue_style( 'custom-styles', plugin_dir_url( __FILE__ ) . 'src/styles.css' );
}

add_action( 'wp_enqueue_scripts', 'enqueue_custom_styles' );

function dd() {
	array_map( function ( $x ) {
		dump( $x );
	}, func_get_args() );
	die;
}

function dump( $str ) {
	echo '<pre>';
	print_r( $str );
	echo '</pre>';
}