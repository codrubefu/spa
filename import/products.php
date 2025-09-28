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


	protected function import_product( $product_data ): void {
		if ( empty( $product_data['ART'] ) ) {
			return;
		}

		$sku                 = $product_data['AID'];
		$existing_product_id = wc_get_product_id_by_sku( $sku );
		$price_variations    = $this->prices->loadPrices( $product_data['ART'] );

		$product = $this->initialize_product( $existing_product_id, $sku, count( $price_variations ) );
		$this->set_basic_product_data( $product, $product_data, $price_variations );

		$product_id = $product->get_id();
		$this->update_product_meta( $product_id, $product_data );
		$this->add_translations( $product_data );

		$attribute_slug = 'pricing-options';
		$this->add_product_attributes( $product, $attribute_slug, $price_variations );

		if ( count( $price_variations ) > 2 ) {
			$this->create_variations( $product, $product_id, $price_variations );
		}

		$this->add_product_to_category( $product_id, $product_data['TGR'] );
		$product->save();
	}

	private function initialize_product( $existing_product_id, $sku, $variation_count ) {
		if ( $existing_product_id ) {
			return $variation_count > 2
				? new WC_Product_Variable( $existing_product_id )
				: new WC_Product( $existing_product_id );
		} else {
			$product = $variation_count > 2 ? new WC_Product_Variable() : new WC_Product();
			$product->set_sku( $sku );

			return $product;
		}
	}

	private function set_basic_product_data( $product, $product_data, $price_variations ): void {
		$product->set_name( $product_data['ART'] );
		$product->set_short_description( $product_data['TDS'] );
		$product->set_description( $product_data['TBS'] );

		if ( count( $price_variations ) < 3 ) {
			$this->set_simple_product_prices( $product, $price_variations, $product_data['PRC'] );
			$product->set_manage_stock( false );
		}

		$product->set_shipping_class_id( 1 );
		$product->save();
	}

	private function set_simple_product_prices( $product, $price_variations, $default_price ): void {
		if ( count( $price_variations ) === 2 ) {
			$product->set_price( $price_variations[0]['PRC'] );
			$product->set_regular_price( $price_variations[0]['PRC'] );
			$product->set_sale_price( $price_variations[1]['PRC'] );
		} elseif ( count( $price_variations ) === 1 ) {
			$product->set_price( $price_variations[0]['PRC'] );
			$product->set_regular_price( $price_variations[0]['PRC'] );
		} else {
			$product->set_price( $default_price );
			$product->set_regular_price( $default_price );
		}
	}

	private function update_product_meta( $product_id, $product_data ): void {
		update_post_meta( $product_id, '_beneficii', wp_kses_post( $product_data['TI1'] ) );
		update_post_meta( $product_id, '_indicatii', wp_kses_post( $product_data['TI3'] ) );
		update_post_meta( $product_id, '_contra_indicatii', wp_kses_post( $product_data['TI2'] ) );
	}

	private function add_translations( $product_data ): void {
		$this->add_translation( $product_data['TI1'], $product_data['TL1'] );
		$this->add_translation( $product_data['TI2'], $product_data['TL2'] );
		$this->add_translation( $product_data['TI3'], $product_data['TL3'] );
		$this->add_translation( $product_data['TBS'], $product_data['TS2'] );
	}

	private function add_product_attributes( $product, $attribute_slug, $price_variations ): void {
		$attribute_taxonomy = $this->get_attribute_taxonomy_by_slug( $attribute_slug );
		if ( ! $attribute_taxonomy ) {
			return;
		}

		$attribute_data = new WC_Product_Attribute();
		$attribute_data->set_id( $attribute_taxonomy->attribute_id );
		$attribute_data->set_name( wc_attribute_taxonomy_name( $attribute_taxonomy->attribute_name ) );

		$terms     = array_column( $price_variations, 'INF' );
		$terms_ids = array_map( function ( $term ) use ( $attribute_taxonomy ) {
			return $this->get_term_id_by_value( $term, 'pa_' . $attribute_taxonomy->attribute_name );
		}, $terms );

		$attribute_data->set_options( $terms_ids );
		$attribute_data->set_position( 0 );
		$attribute_data->set_visible( 1 );
		$attribute_data->set_variation( 1 );

		$product->set_attributes( [ $attribute_data ] );
		$product->save();

		foreach ( $price_variations as $variation_data ) {
			if ( ! term_exists( $variation_data['INF'], 'pa_' . $attribute_taxonomy->attribute_name ) ) {
				wp_insert_term( $variation_data['INF'], 'pa_' . $attribute_taxonomy->attribute_name );
			}
		}
	}

	private function create_variations( $product, $product_id, $price_variations ): void {
		foreach ( $price_variations as $key => $variation_data ) {
			if ( $key === 0 || empty( $variation_data['PRC'] ) ) {
				continue;
			}

			$variable_sku          = $product->get_name() . ' ' . $variation_data['INF'] . $key;
			$existing_variation_id = wc_get_product_id_by_sku( $variable_sku );

			$variation = $existing_variation_id
				? new WC_Product_Variation( $existing_variation_id )
				: new WC_Product_Variation();

			$variation->set_parent_id( $product_id );
			$variation->set_sku( $variable_sku );
			$variation->set_name( $product->get_name() . '<span>-</span> ' . $variation_data['INF'] );
			$variation->set_regular_price( $variation_data['PRC'] );
			$variation->set_attributes( [
				'attribute_pa_pricing-options' => wc_sanitize_taxonomy_name( $variation_data['INF'] )
			] );

			$variation->save();
		}
	}

	private function add_translation( $roText, $engText ): void {

		if ( ! trim( $engText ) ) {
			return;
		}

		// Add translation for the English product name
		$product_name_en = $engText; // Assuming 'ART_EN' holds the English name

		// Check if TranslatePress table and translation function exist
		global $wpdb;
		$table_name = $wpdb->prefix . 'trp_dictionary_ro_ro_en_us';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			return;
		}

		$ro = preg_split( '/\r\n|\r|\n/', $roText );
		$ro = array_filter( $ro ); // Remove empty elements
		$ro = array_values( $ro ); // Reindex the array
		$en = preg_split( '/\r\n|\r|\n/', $engText );
		$en = array_filter( $en ); // Remove empty elements
		$en = array_values( $en ); // Reindex the array

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
