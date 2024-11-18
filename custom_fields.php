<?php

function add_custom_fields_meta_box() {
	add_meta_box(
		'extra_product_fields',
		'Additional Product Information',
		'display_custom_fields_meta_box',
		'product',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'add_custom_fields_meta_box');

// Display Meta Box with Rich Editor
function display_custom_fields_meta_box($post) {
	// Retrieve current values
	$beneficii = get_post_meta($post->ID, '_beneficii', true);
	$indicatii = get_post_meta($post->ID, '_indicatii', true);
	$contra_indicatii = get_post_meta($post->ID, '_contra_indicatii', true);

	?>
	<table class="form-table">
		<tr>
			<th><label for="beneficii">Beneficii</label></th>
			<td>
				<?php
				wp_editor($beneficii, 'beneficii', array('textarea_name' => 'beneficii', 'editor_height' => 200));
				?>
			</td>
		</tr>
		<tr>
			<th><label for="indicatii">Indicatii</label></th>
			<td>
				<?php
				wp_editor($indicatii, 'indicatii', array('textarea_name' => 'indicatii', 'editor_height' => 200));
				?>
			</td>
		</tr>
		<tr>
			<th><label for="contra_indicatii">Contra Indicatii</label></th>
			<td>
				<?php
				wp_editor($contra_indicatii, 'contra_indicatii', array('textarea_name' => 'contra_indicatii', 'editor_height' => 200));
				?>
			</td>
		</tr>
	</table>
	<?php
}

// Save Custom Field Data
function save_custom_fields($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (isset($_POST['beneficii'])) update_post_meta($post_id, '_beneficii', wp_kses_post($_POST['beneficii']));
	if (isset($_POST['indicatii'])) update_post_meta($post_id, '_indicatii', wp_kses_post($_POST['indicatii']));
	if (isset($_POST['contra_indicatii'])) update_post_meta($post_id, '_contra_indicatii', wp_kses_post($_POST['contra_indicatii']));
}
add_action('save_post', 'save_custom_fields');

// Display Fields on the Product Page
function display_custom_fields_on_product_page() {
	global $post;
	$beneficii = get_post_meta($post->ID, '_beneficii', true);
	$indicatii = get_post_meta($post->ID, '_indicatii', true);
	$contra_indicatii = get_post_meta($post->ID, '_contra_indicatii', true);

	if ($beneficii || $indicatii || $contra_indicatii) {
		echo '<div class="product-extra-fields">';
		if ($beneficii) echo '<div class="col-xs-3"><h2>Beneficii:</h2> ' . wp_kses_post($beneficii) . '</div>';
		if ($indicatii) echo '<div><h2>Indicatii:</h2> ' . wp_kses_post($indicatii) . '</div>';
		if ($contra_indicatii) echo '<div><h2>Contra Indicatii:</h2> ' . wp_kses_post($contra_indicatii) . '</div>';
		echo '</div>';
	}
}
add_action('woocommerce_product_after_tabs', 'display_custom_fields_on_product_page', 25);
