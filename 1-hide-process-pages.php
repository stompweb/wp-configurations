<?php

/**
 * Step 1 - Add the submenu page to the Pages menu item
 */
function hpp_add_submenu_page() {

	global $hide_process_pages;

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

	<style>
		.select2-container {
			width: 50%;
			min-width: 320px;
		}
		.select2-results {
			width: 100%;
		}
	</style>

	<h2>Hide Process Pages</h2>

	<?php if ( isset( $_POST['hpp_update_pages'] ) && $_POST['hpp_update_pages'] ) { ?>

		<div id="message" class="updated">
			<p>Selection Updated.</p>
		</div>

		<br />

	<?php } ?>

	<p>Select the pages that should be hidden users (Non-Administrators). They will be hidden from the pages section.</p>

	<form method="POST">

		<?php

		$pages = get_pages();
		$current_selection = get_option( 'hpp_pages' );

		?>
	  
		<select name="process_pages[]" id="hide-pages" multiple>
			<?php foreach ( $pages as $page ) { ?>
				<option value="<?php echo $page->ID; ?>" <?php if ( in_array( $page->ID, $current_selection ) ) { echo 'selected'; } ?>>
					<?php echo esc_html( $page->post_title ); ?>
				</option>
			<?php } ?>
		</select>

		<?php wp_nonce_field( 'update-pages_'.$comment_id ); ?>

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

function admin_inline_js( $hook ) {

	global $hide_process_pages;

	if ( $hook != $hide_process_pages ) {
		return;
	}
	?>

	<script>
		jQuery(document).ready(function() { 
			jQuery("#hide-pages").select2(); 
		});
	</script>

<?php }
add_action( 'admin_print_scripts', 'admin_inline_js' );

/**
 * Step 3 - Update user's page selection on submit
 */
function hpp_update_pages() {

	$process_pages = array();

	if ( isset( $_POST['hpp_update_pages'] ) ) {

		if ( check_admin_referer( 'delete-comment_'.$comment_id ) ) {

			$process_pages = array_map( 'absint', $_POST['process_pages'] );

			update_option( 'hpp_pages', $process_pages );

			add_action( 'admin_notices', 'hpp_updated_notice' );

		}
	}

}
add_action( 'admin_init', 'hpp_update_pages' );

function hpp_updated_notice() {
	?>
	<div class="update-nag notice">
	  <p><?php _e( 'Please install Advanced Custom Fields, it is required for this plugin to work properly!', 'my_plugin_textdomain' ); ?></p>
	</div>
	<?php
}

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

	if ( 'page' != $typenow ) {
		return;
	}

	// Get the current array of pages.
	$current_selection = get_option( 'hpp_pages' );

	// If is admin and is the main query.
	if ( is_admin() && $query->is_main_query() ) {
		$query->set( 'post__not_in', $current_selection );
	}

}
add_action( 'pre_get_posts', 'hpp_hide_pages_admin' );
