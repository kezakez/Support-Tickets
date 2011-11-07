<?php

function suptic_db_version() {
	return 144298;
}

add_action( 'activate_' . SUPTIC_PLUGIN_BASENAME, 'suptic_install' );

function suptic_install() {
	global $wpdb;

	$charset_collate = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}

	$forms = suptic_db_table( 'forms' );
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$forms'" ) != $forms ) {
		$wpdb->query( "CREATE TABLE IF NOT EXISTS $forms (
		id bigint(20) unsigned NOT NULL auto_increment,
		name varchar(200) NOT NULL default '',
		page_id bigint(20) unsigned NOT NULL,
		form_design longtext NOT NULL,
		status varchar(200) NOT NULL default '',
		PRIMARY KEY (id)) $charset_collate;" );

		$default_form = suptic_default_form();
		$default_form->save();
	}

	$tickets = suptic_db_table( 'tickets' );
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$tickets'" ) != $tickets ) {
		$wpdb->query( "CREATE TABLE IF NOT EXISTS $tickets (
		id bigint(20) unsigned NOT NULL auto_increment,
		form_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		page_id bigint(20) unsigned NOT NULL,
		access_key varchar(20) NOT NULL default '',
		author_email varchar(200) NOT NULL default '',
		author_first_name varchar(200) NOT NULL default '',
		author_last_name varchar(200) NOT NULL default '',
		subject varchar(200) NOT NULL default '',
		ticket_type varchar(200) NOT NULL default 'ticket',
		parent_id bigint(20) unsigned NOT NULL,
		status varchar(200) NOT NULL default '',
		create_time datetime NOT NULL default '0000-00-00 00:00:00',
		update_time datetime NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY (id)) $charset_collate;" );
	}

	$messages = suptic_db_table( 'messages' );
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$messages'" ) != $messages ) {
		$wpdb->query( "CREATE TABLE IF NOT EXISTS $messages (
		id bigint(20) unsigned NOT NULL auto_increment,
		ticket_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		message_body longtext NOT NULL,
		word_count bigint(20) unsigned NOT NULL,
		message_type varchar(200) NOT NULL default 'message',
		parent_id bigint(20) unsigned NOT NULL,
		status varchar(200) NOT NULL default '',
		create_time datetime NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY (id)) $charset_collate;" );
	}

	$meta = suptic_db_table( 'meta' );
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$meta'" ) != $meta ) {
		$wpdb->query( "CREATE TABLE IF NOT EXISTS $meta (
		id bigint(20) unsigned NOT NULL auto_increment,
		object_type varchar(200) NOT NULL default '',
		object_id bigint(20) unsigned NOT NULL,
		meta_key varchar(200) NOT NULL default '',
		meta_value longtext NOT NULL,
		PRIMARY KEY (id)) $charset_collate;" );
	}

	$option = (array) get_option( 'suptic' );
	if ( ! (int) $option['db_version'] ) {
		$option['db_version'] = suptic_db_version();
		update_option( 'suptic', $option );
	}
}

?>