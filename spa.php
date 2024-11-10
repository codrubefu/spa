<?php
/*
Plugin Name: Spa Plugin
Description: Importă produse prin cURL și trimite o notificare la achiziție prin cURL în WooCommerce.
Version: 1.0
Author: Codrut
*/
require_once 'custom_fields.php';
require_once 'soap.php';
require_once 'products.php';
require_once 'delete.php';


// Hook for plugin activation
register_activation_hook(__FILE__, 'create_custom_woocommerce_attribute');

function create_custom_woocommerce_attribute() {
	global $wpdb;

	// Define attribute name and slug
	$attribute_name = 'Optiuni';
	$attribute_slug = wc_sanitize_taxonomy_name(stripslashes('pricing-options'));

	// Check if the attribute already exists
	$attribute_id = $wpdb->get_var($wpdb->prepare(
		"SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
		$attribute_slug
	));

	// If attribute doesn't exist, create it
	if (!$attribute_id) {
		$args = [
			'attribute_label'   => $attribute_name,
			'attribute_name'    => $attribute_slug,
			'attribute_type'    => 'select',
			'attribute_orderby' => 'menu_order',
			'attribute_public'  => 0, // Set to 1 if you want it to be visible on product pages
		];

		// Insert attribute into WooCommerce attribute taxonomy table
		$wpdb->insert("{$wpdb->prefix}woocommerce_attribute_taxonomies", $args);

		// Flush WooCommerce cache and permalinks
		delete_transient('wc_attribute_taxonomies');
		wc_delete_product_transients();
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Protecție acces direct
error_reporting( E_ALL );
class spa {

	private products $products;

	public function __construct() {
		$this->products = new products();
		// Hook pentru importul periodic de produse
		add_action( 'init', [ $this, 'schedule_import' ] );
		add_action( 'woocommerce_curl_import_event', [ $this, 'import_products' ] );

		// Hook pentru apel cURL la finalizarea unei comenzi
		add_action( 'woocommerce_order_status_completed', [ $this, 'notify_curl_on_purchase' ] );

		// Programare/deprogramare cron la activare/dezactivare
		register_activation_hook( __FILE__, [ $this, 'activate_cron' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate_cron' ] );

		// Adaugă un endpoint personalizat pentru a rula importul manual
		add_action( 'admin_init', [ $this, 'manual_import_endpoint' ] );
	}

	public function manual_import_endpoint() {
		// Verifică dacă acțiunea personalizată a fost solicitată și dacă utilizatorul are permisiunile necesare
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'run_import_products' && current_user_can( 'manage_options' ) ) {

			$this->products->import_products();
			wp_die( 'Importul de produse a fost executat cu succes.' );
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
function dump($str) {
	echo "<pre>";
	print_r($str);
	echo "</pre>";
}

function dd($str) {
	dump($str);
	die();

}
new spa();
