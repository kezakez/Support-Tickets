<?php

function suptic_plugin_path( $path = '' ) {
	return path_join( SUPTIC_PLUGIN_DIR, trim( $path, '/' ) );
}

function suptic_plugin_url( $path = '' ) {
	return plugins_url( $path, SUPTIC_PLUGIN_BASENAME );
}

function suptic_db_table( $table_name ) {
	global $wpdb;

	$prefix = $wpdb->prefix;

	if ( 'suptic_' != substr( $table_name, 0, 7 ) )
		$table_name = 'suptic_' . $table_name;

	return $prefix . trim( $table_name );
}

$suptic_form = null;

require_once SUPTIC_PLUGIN_DIR . '/includes/functions.php';
require_once SUPTIC_PLUGIN_DIR . '/includes/formatting.php';
require_once SUPTIC_PLUGIN_DIR . '/includes/pipe.php';
require_once SUPTIC_PLUGIN_DIR . '/includes/shortcodes.php';

require_once SUPTIC_PLUGIN_DIR . '/includes/classes.php';
require_once SUPTIC_PLUGIN_DIR . '/includes/notifications.php';
require_once SUPTIC_PLUGIN_DIR . '/includes/controller.php';

if ( is_admin() ) {
	require_once SUPTIC_PLUGIN_DIR . '/admin/install.php';
	require_once SUPTIC_PLUGIN_DIR . '/admin/admin.php';
}

function suptic_admin_url( $file, $query = '' ) {
	$file = trim( $file, ' /' );
	if ( 'admin/' != substr( $file, 0, 6 ) )
		$file = 'admin/' . $file;

	$path = 'admin.php';
	$path .= '?page=' . SUPTIC_PLUGIN_NAME . '/' . $file;

	if ( $query = build_query( $query ) )
		$path .= '&' . $query;

	$url = admin_url( $path );

	return $url;
}

function suptic_upload_dir( $type = false ) {
	$siteurl = get_option( 'siteurl' );
	$upload_path = trim( get_option( 'upload_path' ) );
	if ( empty( $upload_path ) )
		$dir = WP_CONTENT_DIR . '/uploads';
	else
		$dir = $upload_path;

	$dir = path_join( ABSPATH, $dir );

	if ( ! $url = get_option( 'upload_url_path' ) ) {
		if ( empty( $upload_path ) || $upload_path == $dir )
			$url = WP_CONTENT_URL . '/uploads';
		else
			$url = trailingslashit( $siteurl ) . $upload_path;
	}

	if ( defined( 'UPLOADS' ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	if ( 'dir' == $type )
		return $dir;
	if ( 'url' == $type )
		return $url;
	return array( 'dir' => $dir, 'url' => $url );
}

/* Loading modules */

add_action( 'plugins_loaded', 'suptic_load_modules', 1 );

function suptic_load_modules() {
	$dir = SUPTIC_PLUGIN_MODULES_DIR;

	if ( ! ( is_dir( $dir ) && $dh = opendir( $dir ) ) )
		return false;

	while ( ( $module = readdir( $dh ) ) !== false ) {
		if ( substr( $module, -4 ) == '.php' )
			include_once $dir . '/' . $module;
	}
}

add_action( 'wp_head', 'suptic_head' );

function suptic_head() {
	$stylesheet_url = suptic_plugin_url( 'styles.css' );

	if ( SUPTIC_LOAD_CSS )
		echo '<link rel="stylesheet" href="' . $stylesheet_url . '" type="text/css" />';
}

add_action( 'wp_print_scripts', 'suptic_enqueue_scripts' );

function suptic_enqueue_scripts() {
	global $wpdb;

	if ( is_admin() )
		return;

	$table = suptic_db_table( 'forms' );
	$query = "SELECT DISTINCT page_id FROM $table ORDER BY page_id";
	$form_pages = $wpdb->get_col( $query );

	if ( SUPTIC_LOAD_JS && is_page( $form_pages ) ) {
		$in_footer = true;
		if ( 'header' === SUPTIC_LOAD_JS )
			$in_footer = false;

		wp_enqueue_script( 'suptic', suptic_plugin_url( 'scripts.js' ),
			array( 'jquery', 'jquery-form' ), SUPTIC_VERSION, $in_footer );
	}
}

/* L10N */
add_action( 'init', 'suptic_load_plugin_textdomain' );

function suptic_load_plugin_textdomain() {
	load_plugin_textdomain( 'suptic',
		'wp-content/plugins/' . SUPTIC_PLUGIN_NAME . '/languages',
		SUPTIC_PLUGIN_NAME . '/languages' );
}

/* Capability */
function suptic_manage_forms_capability() {
	return apply_filters( 'suptic_manage_forms_capability', SUPTIC_MANAGE_FORMS_CAPABILITY );
}

function suptic_you_can_manage_forms() {
	return current_user_can( suptic_manage_forms_capability() );
}

function suptic_access_all_tickets_capability() {
	return apply_filters( 'suptic_access_all_tickets_capability',
		SUPTIC_ACCESS_ALL_TICKETS_CAPABILITY );
}

function suptic_you_can_access_all_tickets() {
	return current_user_can( suptic_access_all_tickets_capability() );
}

?>