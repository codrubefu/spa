<?php
/*
Plugin Name: Spa Plugin
Description: Importă produse prin cURL și trimite o notificare la achiziție prin cURL în WooCommerce.
Version: 1.0
Author: Codrut
*/
require_once 'soap.php';
require_once 'products.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Protecție acces direct

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

new spa();
