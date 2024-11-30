<?php

namespace import;

use WC_Product;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_plugin_files();

class products {

	private services $services;
	private prices $prices;

	public function __construct() {
		$this->services = new services();
		$this->prices   = new prices();
	}


	public function import_products() {
		$product_data = $this->services->loadServices();

		//replace
		if ( $product_data && is_array( $product_data ) ) {
			foreach ( $product_data as $product ) {
				$this->import_product( $product );
			}
		}
	}

	private function get_attribute_taxonomy_by_slug( $slug ) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach ( $attribute_taxonomies as $taxonomy ) {
			if ( $taxonomy->attribute_name === $slug ) {
				return $taxonomy;
			}
		}

		return null;
	}

	private function get_term_id_by_value( $term_value, $taxonomy ): ?int {
		$term = get_term_by( 'name', $term_value, $taxonomy );
		if ( $term ) {
			return $term->term_id;
		}

		return null; // Return null if the term is not found
	}


	protected function import_product( $product_data ) {
		$sku                 = $product_data['AID'];
		$existing_product_id = wc_get_product_id_by_sku( $sku );
		// Load prices to determine if we need a variable or simple product
		$price_variations = $this->prices->loadPrices( $product_data['ART'] );

		$is_variable_product = count( $price_variations ) > 2;
		if ( $is_variable_product < 2 ) {
			return;
		}
		if ( $existing_product_id ) {
			$product = $is_variable_product ? new WC_Product_Variable( $existing_product_id ) : new WC_Product( $existing_product_id );
		} else {
			$product = $is_variable_product ? new WC_Product_Variable() : new WC_Product();
			$product->set_sku( $sku );
		}
		// Set common product properties
		$product->set_name( $product_data['ART'] );
		$product->set_description( $product_data['TBS'] );
		if ( ! $is_variable_product ) {
			$product->set_price( $product_data['PRC'] );
			$product->set_regular_price( $product_data['PRC'] );
			$product->set_stock_quantity( 100000 );
			$product->set_manage_stock( true );

		}
		$product->set_shipping_class_id( 1 );
		$product->save();
		$product_id       = $product->get_id();
		$beneficii        = $product_data['TI1'];
		$contra_indicatii = $product_data['TI2'];
		$indication       = $product_data['TI3'];

		update_post_meta( $product_id, '_beneficii', wp_kses_post( $beneficii ) );
		update_post_meta( $product_id, '_indicatii', wp_kses_post( $indication ) );
		update_post_meta( $product_id, '_contra_indicatii', wp_kses_post( $contra_indicatii ) );
		$this->add_translation( $product_data['TI1'], $product_data['TL1'] );
		$this->add_translation( $product_data['TI2'], $product_data['TL2'] );
		$this->add_translation( $product_data['TI3'], $product_data['TL3'] );
		$this->add_translation( $product_data['TBS'], $product_data['TS2'] );
		// Step 2: Add attribute for variations (e.g., "Pricing Options")
		$attribute_slug = 'pricing-options';

		$attribute_taxonomy = $this->get_attribute_taxonomy_by_slug( $attribute_slug );

		if ( $attribute_taxonomy ) {

			$attribute_data = new WC_Product_Attribute();
			$attribute_data->set_id( $attribute_taxonomy->attribute_id );
			$attribute_data->set_name( wc_attribute_taxonomy_name( $attribute_taxonomy->attribute_name ) );

			$terms    = array_column( $price_variations, 'INF' );
			$termsIds = [];
			foreach ( $terms as $term ) {
				$termsIds[] = $this->get_term_id_by_value( $term, 'pa_' . $attribute_taxonomy->attribute_name );
			}

			$attribute_data->set_options( $termsIds );
			$attribute_data->set_position( 0 );
			$attribute_data->set_visible( 1 );
			$attribute_data->set_variation( 1 );

			$product->set_attributes( [ $attribute_data ] );
			$product->save();
			foreach ( $price_variations as $variation_data ) {
				if ( ! $variation_data['INF'] || $variation_data['INF'] == '' ) {
					$variation_data['INF'] = 'Pret de baza';
				}
				if ( ! term_exists( $variation_data['INF'], 'pa_' . $attribute_taxonomy->attribute_name ) ) {
					wp_insert_term( $variation_data['INF'], 'pa_' . $attribute_taxonomy->attribute_name );
				}
			}
		}


		// Step 3: Add each variation
		foreach ( $price_variations as $variation_data ) {
			if ( ! $variation_data['PRC'] || $variation_data['PRC'] == '' ) {
				continue;
			}
			$variableSku           = $product->get_name() . ' ' . $variation_data['INF'];
			$existing_variation_id = wc_get_product_id_by_sku( $variableSku );
			$variation             = $existing_variation_id ? new WC_Product_Variation( $existing_variation_id ) : new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_sku( $variableSku );
			$variation->set_name( $product->get_name() . '<span>-</span> ' . $variation_data['INF'] );
			$variation->set_regular_price( $variation_data['PRC'] );
			$variation->set_attributes( [ 'attribute_pa_pricing-options' => wc_sanitize_taxonomy_name( $variation_data['INF'] ) ] );

			$variation->save();

		}

		$this->add_product_to_category( $product_id, $product_data['TCL'] );

		$product->save();
	}

	private function add_translation( $roText, $engText ) {

		if ( !trim( $engText ) ) {
			return;
		}

		// Add translation for the English product name
		$product_name_en = $engText; // Assuming 'ART_EN' holds the English name

		// Check if TranslatePress table and translation function exist
		global $wpdb;
		$table_name = $wpdb->prefix . 'trp_dictionary_ro_ro_en_us';

		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
			return;
		}

		$ro         = preg_split( '/\r\n|\r|\n/', $roText );
		$ro         = array_filter( $ro ); // Remove empty elements
		$ro         = array_values( $ro ); // Reindex the array
		$en         = preg_split( '/\r\n|\r|\n/', $engText );
		$en         = array_filter( $en ); // Remove empty elements
		$en         = array_values( $en ); // Reindex the array

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			foreach ( $en as $key => $value ) {
				if ( isset( $ro[ $key ] ) && isset( $en[ $key ] ) ) {
					$wpdb->insert(
						$table_name,
						[
							'original'   => trim( $ro[ $key ] ?? '' ),
							'translated' => trim( $en[ $key ] ?? '' ),
							'status'     => '2'
						]
					);
				}

			}
		}
	}

	private function add_product_to_category( $product_id, $category_name ): void {
		if ( $category_name == '' ) {
			return;
		}
		if ( $category_name == '' ) {
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
