<?php

/**
 * Step 1 - Add the submenu page to the Pages menu item
 */
function hpp_add_submenu_page() {

	// Use a global to reference the submenu page throughout our process.
	global $hide_process_pages;

	// Setup the submenu page under the Page menu item.
	$hide_process_pages = add_submenu_page(
		'edit.php?post_type=page',
		'Hide Process Pages',
		'Process Pages',
		'manage_options',
		'process-pages',
		'hpp_manage_pages'
	);

}
add_action( 'admin_menu', 'hpp_add_submenu_page' );

/**
 * Step 2 - Create UI for users to add/remove pages
 */
function hpp_manage_pages() {
	?>

	<h2>Hide Process Pages</h2>

	<p>Select the pages that should be hidden.</p>

	<form method="POST">

		<?php
		$pages = get_pages();
		$current_selection = get_option( 'hpp_pages' );
		?>
	  
		<select name="process_pages[]" id="hidden-pages" multiple>
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

<?php }

/**
 * Step 2b (optional) - Add select2 library for enhanced UI
 *
 * @param ( $hook ) Current page provided by WordPress to selectively load elements.
 */
function hpp_enqueue_select2( $hook ) {

	global $hide_process_pages;

	if ( $hook != $hide_process_pages ) {
		return;
	}

	wp_register_script( 'select2', plugin_dir_url( __FILE__ ) . 'js/select2/select2.min.js', array( 'jquery' ) );
	wp_register_style( 'select2',  plugin_dir_url( __FILE__ ) . 'js/select2/select2.css',    array() );

	wp_enqueue_script( 'select2' );
	wp_enqueue_style( 'select2' );

}
add_action( 'admin_enqueue_scripts', 'hpp_enqueue_select2' );

/**
 * Step 2b (optional) - Initiation select2 on the select boxes
 *
 * @param ( $hook ) Current page provided by WordPress to selectively load elements.
 */
function hpp_admin_inline_js( $hook ) {

	global $hide_process_pages;

	if ( $hook != $hide_process_pages ) {
		return;
	}
	?>

	<script>
		jQuery(document).ready(function() { 
			jQuery("#hidden-pages").select2(); 
		});
	</script>

<?php }
add_action( 'admin_print_scripts', 'hpp_admin_inline_js' );

/**
 * Step 3 - Update user's page selection on submit
 */
function hpp_update_pages() {

	$process_pages = array();

	// Only continue if our form has been submitted.
	if ( '' !== ( sanitize_text_field( $_POST['hpp_update_pages'] ) ) ) {

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

/**
 * Step 4 - Hide all the pages in the admin from users without privledges.
 *
 * @param ( $query ) The current, filterable, WordPress query object.
 */
function hpp_hide_pages_admin( $query ) {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $typenow;

	// Ensure we're only going working with pages.
	if ( 'page' != $typenow ) {
		return;
	}

	// Get the current array of pages.
	$current_selection = array_map( 'absint', get_option( 'hpp_pages' ) );

	// If is admin and is the main query.
	if ( is_admin() && $query->is_main_query() ) {

		// Exclude our process pages from the loop.
		$query->set( 'post__not_in', $current_selection );
	}

}
add_action( 'pre_get_posts', 'hpp_hide_pages_admin' );

/**
 * Helper Function - Update Notice
 */
function hpp_updated_notice() {
	?>
	<div id="message" class="updated">
		<p>Selection updated.</p>
	</div>
	<?php
}
