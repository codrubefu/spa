<?php

require_once 'services.php';
require_once 'prices.php';

class products {

	private services $services;
	private prices $prices;
	private $attribute_name = 'Option';

	public function __construct() {
		$this->services = new services();
		$this->prices   = new prices();
	}

	private function create_option_attribute() {

		$taxonomy = 'pa_' . wc_sanitize_taxonomy_name($this->attribute_name);

		if (!taxonomy_exists($taxonomy)) {
			$args = [
				'slug'         => $taxonomy,
				'type'         => 'select',
				'orderby'      => 'menu_order',
				'has_archives' => false,
				'public'       => true,
				'show_in_rest' => true,
			];
			wc_create_attribute($args);
		}
	}

	public function import_products() {
		$product_data = $this->services->loadServices();
		//replace
		$this->create_option_attribute();
		if ( $product_data && is_array( $product_data ) ) {
			foreach ( $product_data as $product ) {
				if($product['AID'] != '544'){
					continue;
				}
				$this->import_product( $product );
			}
		}
	}

	private function get_attribute_taxonomy_by_slug($slug) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach ($attribute_taxonomies as $taxonomy) {
			if ($taxonomy->attribute_name === $slug) {
				return $taxonomy;
			}
		}
		return null;
	}


	protected function import_product($product_data) {
		$sku = $product_data['AID'];
		$existing_product_id = wc_get_product_id_by_sku($sku);
		// Load prices to determine if we need a variable or simple product
		$price_variations = [];

		$is_variable_product = count($price_variations) > 2;


		if ($existing_product_id) {
			$product = $is_variable_product ? new WC_Product_Variable($existing_product_id) : new WC_Product($existing_product_id);
		} else {
			$product = $is_variable_product ? new WC_Product_Variable() : new WC_Product();
			$product->set_sku($sku);
		}

		// Set common product properties
		$product->set_name($product_data['ART']);
		$product->set_description($product_data['TBS']);
		if(!$is_variable_product){
			$product->set_price($product_data['PRC']);
			$product->set_regular_price($product_data['PRC']);
			$product->set_stock_quantity(100000);
			$product->set_manage_stock(true);

		}
		$product->set_shipping_class_id(1);
		$product->save();

		$product_id = $product->get_id();
		$beneficii = $product_data['TI3'];
		$contra_indicatii = $product_data['TI2'];
		$indication = $product_data['TI2'];

		update_post_meta($product_id, '_beneficii', wp_kses_post($beneficii));
		update_post_meta($product_id, '_indicatii', wp_kses_post($indication));
		update_post_meta($product_id, '_contra_indicatii', wp_kses_post($contra_indicatii));

		// Step 2: Add attribute for variations (e.g., "Pricing Options")
		$attribute_slug = 'pricing-options';

		$attribute_taxonomy = $this->get_attribute_taxonomy_by_slug($attribute_slug);

		if ($attribute_taxonomy) {
			$attribute_data = new WC_Product_Attribute();
			$attribute_data->set_id($attribute_taxonomy->attribute_id);
			$attribute_data->set_name(wc_attribute_taxonomy_name($attribute_taxonomy->attribute_name));
			$attribute_data->set_options(array_column($price_variations, 'INF'));
			$attribute_data->set_position(0);
			$attribute_data->set_visible(1);
			$attribute_data->set_variation(1);

			$product->set_attributes([$attribute_data]);
			$product->save();

			foreach ($price_variations as $variation_data) {
				if (!term_exists($variation_data['INF'], 'pa_'.$attribute_taxonomy->attribute_name)) {
					wp_insert_term($variation_data['INF'], 'pa_'.$attribute_taxonomy->attribute_name);
				}
			}
		}


		// Step 3: Add each variation
		foreach ($price_variations as $variation_data) {
			$variableSku = $product->get_name() . ' ' . $variation_data['INF'];
			$existing_variation_id = wc_get_product_id_by_sku($variableSku);
			$variation = $existing_variation_id ? new WC_Product_Variation($existing_variation_id) : new WC_Product_Variation();
			$variation->set_parent_id($product_id);
			$variation->set_sku($variableSku);
			$variation->set_name( $product->get_name() . '<span>-</span> ' . $variation_data['INF']);
			$variation->set_regular_price($variation_data['PRC']);
			$variation->set_attributes(['attribute_pa_pricing-options' => $variation_data['INF']]);
			$variation->save();
		}
	}
	private function add_translation($roText,$engText){

		if(!trim($engText)){
			return;
		}

		// Add translation for the English product name
		$product_name_en = $engText; // Assuming 'ART_EN' holds the English name

		// Check if TranslatePress table and translation function exist
		global $wpdb;
		$table_name = $wpdb->prefix . 'trp_dictionary_ro_ro_en_gb';
		$ro = preg_split('/\r\n|\r|\n/', $roText);
		$ro = array_filter($ro); // Remove empty elements
		$ro = array_values($ro); // Reindex the array
		$en = preg_split('/\r\n|\r|\n/', $engText);
		$en = array_filter($en); // Remove empty elements
		$en = array_values($en); // Reindex the array

		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
			foreach ($en as $key=>$value){
				$wpdb->insert(
					$table_name,
					[
						'original' => trim($ro[$key]),
						'translated' => trim($en[$key]),
						'status' => '2'
					]
				);

			}
		}
	}

	private function add_product_to_category( $product_id, $category_name ): void {
		if($category_name == '') {
			return;
		}
		// Check if the category exists
		$category_id = get_term_by( 'name', $category_name, 'product_cat' );

		// If the category does not exist, create it
		if ( ! $category_id ) {
			$category_id = wp_insert_term( $category_name, 'product_cat' );
			if ( is_wp_error( $category_id ) ) {
				throw new Exception( 'Failed to create category.' );
			}
			$category_id = $category_id['term_id'];
		} else {
			$category_id = $category_id->term_id;
		}
		// Assign the product to the category
		wp_set_object_terms( $product_id, $category_id, 'product_cat' );
	}

}
