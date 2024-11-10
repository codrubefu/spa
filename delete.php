<?php
// Ensure this code runs only in WordPress context.
if (!defined('ABSPATH')) {
	exit;
}

// Function to delete all WooCommerce products
function delete_all_products() {
	$product_ids = wc_get_products([
		'limit' => -1,
		'return' => 'ids',
		'status' => 'any',
	]);

	foreach ($product_ids as $product_id) {
		wp_delete_post($product_id, true); // Force delete products permanently
	}
	echo "All products deleted.<br>";
}

// Function to delete all WooCommerce categories
function delete_all_categories() {
	$product_cat_ids = get_terms([
		'taxonomy' => 'product_cat',
		'hide_empty' => false,
		'fields' => 'ids',
	]);

	foreach ($product_cat_ids as $cat_id) {
		wp_delete_term($cat_id, 'product_cat');
	}
	echo "All categories deleted.<br>";
}

// Function to delete all WooCommerce attributes
function delete_all_attributes() {
	global $wpdb;

	// Get all attribute IDs from WooCommerce
	$attributes = wc_get_attribute_taxonomies();

	foreach ($attributes as $attribute) {
		$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

		// Delete the attribute terms
		$terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids']);
		foreach ($terms as $term_id) {
			wp_delete_term($term_id, $taxonomy);
		}

		// Delete the attribute itself
		$wpdb->delete("{$wpdb->prefix}woocommerce_attribute_taxonomies", ['attribute_id' => $attribute->attribute_id]);
		// Unregister taxonomy and delete it from WordPress
		unregister_taxonomy($taxonomy);
		delete_option("{$taxonomy}_children");
	}

	// Flush rewrite rules only if WooCommerce helper function exists
	if (function_exists('wc_flush_rewrite_rules')) {
		wc_flush_rewrite_rules();
	} else {
		flush_rewrite_rules(); // Fall back to standard WordPress function
	}

	echo "All attributes deleted.<br>";
}


