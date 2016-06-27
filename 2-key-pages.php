<?php

/**
 * Step 1 - Register the submenu page
 */
function kp_add_submenu_page() {

	// Use a global to reference the submenu page throughout our process.
	global $hide_process_pages;

	// Setup the submenu page under the Dashboard item.
	$hide_process_pages = add_submenu_page(
		'index.php',
		'Site Settings',
		'Site Settings',
		'manage_options',
		'site-settings',
		'kp_site_settings'
	);

}
add_action( 'admin_menu', 'kp_add_submenu_page' );

/**
 * Step 2 - Create UI for key settings
 */
function kp_site_settings() {
	?>

	<h2>Key Pages</h2>

	<form method="POST">

		<?php
		$pages = get_pages();
		?>
	  
		<select name="contact_page">
			<?php foreach ( $pages as $page ) { ?>
				<option 
					value="<?php echo absint( $page->ID ); ?>" 
					<?php
					if ( is_array( $current_selection ) && in_array( $page->ID, $current_selection ) ) {
						echo 'selected';
					} ?>>

					<?php echo esc_html( $page->post_title ); ?>

				</option>
			<?php } ?>
		</select>

		<?php wp_nonce_field( 'hpp-update-pages' ); ?>

		<p class="submit">
			<input class="button-primary" type="submit" name="hpp_update_pages" value="Update Selection"/>
		</p>

	</form>

<?php
/**
 * Step 3 - Update user's page selection on submit
 */
function hpp_update_pages() {

	$process_pages = array();

	// Only continue if our form has been submitted.
	if ( isset( $_POST['hpp_update_pages'] ) ) {

		// Ensure this is a request directly from our form.
		if ( check_admin_referer( 'hpp-update-pages' ) ) {

			// Get the submitted information, ensuring each item in the array is a number.
			$process_pages = array_map( 'absint', $_POST['process_pages'] );

			// Update our option in the database.
			update_option( 'hpp_pages', $process_pages, 'no' );

			// Show confirmation to the user.
			add_action( 'admin_notices', 'hpp_updated_notice' );

		}
	}

}
add_action( 'admin_init', 'hpp_update_pages' );
