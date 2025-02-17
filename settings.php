<?php
// Add the admin menu and options page
add_action('admin_menu', 'master_spa_add_admin_page');
function master_spa_add_admin_page() {
	add_menu_page(
		'Master Spa Optiuni',          // Page title
		'Master Spa',                  // Menu title
		'manage_options',             // Capability
		'master-spa-options',          // Menu slug
		'master_spa_options_page',     // Callback function
		'dashicons-admin-generic',    // Icon
		100                           // Position
	);
}

// Render the options page
function master_spa_options_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Master Spa', 'master-spa'); ?></h1>
		<form method="post" action="options.php">
			<?php
			// Output nonce, action, and option_group fields
			settings_fields('master_spa_options_group');

			// Output settings sections and their fields
			do_settings_sections('master-spa-options');

			// Submit button
			submit_button();
			?>
		</form>
	</div>
	<?php
}

// Register settings, sections, and fields
add_action('admin_init', 'master_spa_register_settings');
function master_spa_register_settings() {
	// Register settings
	register_setting('master_spa_options_group', 'master_spa_server_address');
	register_setting('master_spa_options_group', 'master_spa_email_for_reports');

	// Add settings section
	add_settings_section(
		'master_spa_main_section',             // Section ID
		__('Sectari', 'master-spa'),     // Title
		'master_spa_main_section_callback',    // Callback
		'master-spa-options'                   // Page
	);

	// Add Server Address field
	add_settings_field(
		'master_spa_server_address',               // Field ID
		__('Server Address', 'master-spa'),        // Title
		'master_spa_server_address_callback',      // Callback
		'master-spa-options',                      // Page
		'master_spa_main_section'                  // Section
	);

	// Add Email for Reports field
	add_settings_field(
		'master_spa_email_for_reports',            // Field ID
		__('Email-uri pentru mesaje de eroare ', 'master-spa'),     // Title
		'master_spa_email_for_reports_callback',   // Callback
		'master-spa-options',                      // Page
		'master_spa_main_section'                  // Section
	);

	// Add new section for "Ruleaza importul"
	add_settings_section(
		'master_spa_import_section',             // Section ID
		__('Ruleaza importul', 'master-spa'),    // Title
		'master_spa_import_section_callback',    // Callback
		'master-spa-options'                     // Page
	);
}

// Callback for the section
function master_spa_main_section_callback() {
	echo '<p>' . esc_html__('Configurari pentru conexiunea cu Master Spa  .', 'master-spa') . '</p>';
}

// Callback for the Server Address field
function master_spa_server_address_callback() {
	$value = get_option('master_spa_server_address', '');
	echo '<input type="text" id="master_spa_server_address" name="master_spa_server_address" value="' . esc_attr($value) . '" class="regular-text" />';
}

// Callback for the Email for Reports field
function master_spa_email_for_reports_callback() {
	$value = get_option('master_spa_email_for_reports', '');
	echo '<textarea rows="10"  id="master_spa_email_for_reports" name="master_spa_email_for_reports"  class="regular-text" >' . esc_attr($value) . '</textarea>';
}

// Callback for the new "Ruleaza importul" section
function master_spa_import_section_callback() {
	echo '<p><a target="_blank" href="/wp-admin/?action=run_import_products" >Ruleaza importul</a></p>';
}