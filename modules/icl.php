<?php
/**
** ICL module for ICanLocalize translation service
** WPML plugin is necessary for these functions to work.
** WPML: http://wordpress.org/extend/plugins/sitepress-multilingual-cms/
**/

function suptic_icl_wpml_available() {
	global $sitepress;

	return is_a( $sitepress, 'SitePress' );
}

if ( ! suptic_icl_wpml_available() )
	return;

/**
**	Translation for Form Design Template
**/

/* Shortcode handler */

suptic_add_shortcode( 'icl', 'suptic_icl_shortcode_handler', true );

function suptic_icl_shortcode_handler( $tag ) {

	if ( ! is_array( $tag ) )
		return '';

	$name = $tag['name'];
	$values = (array) $tag['values'];
	$content = $tag['content'];

	$content = trim( $content );
	if ( ! empty( $content ) ) {
		$string_name = suptic_icl_string_name( $content, $name );
		return suptic_icl_translate( $string_name, $content );
	}

	$value = trim( $values[0] );
	if ( ! empty( $value ) ) {
		$string_name = suptic_icl_string_name( $value, $name, 0 );
		return suptic_icl_translate( $string_name, $value );
	}

	return '';
}


/* Form tag filter */

add_filter( 'suptic_form_tag', 'suptic_icl_form_tag_filter' );

function suptic_icl_form_tag_filter( $tag ) {
	if ( ! is_array( $tag ) )
		return $tag;

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];
	$raw_values = (array) $tag['raw_values'];
	$pipes = $tag['pipes'];
	$content = $tag['content'];

	$icl_option = array();
	foreach ( $options as $option ) {
		if ( 'icl' == $option ) {
			$icl_option = array( 'icl', null );
			break;
		} elseif ( preg_match( '/^icl:(.+)$/', $option, $matches ) ) {
			$icl_option = array( 'icl', $matches[1] );
			break;
		}
	}

	if ( ! ('icl' == $type || $icl_option ) )
		return $tag;

	$str_id = $icl_option[1] ? $icl_option[1] : $name;

	$new_values = array();

	if ( $raw_values && $pipes && is_a( $pipes, 'SupTic_Pipes' ) && ! $pipes->zero() ) {
		$new_raw_values = array();
		foreach ( $raw_values as $key => $value ) {
			$string_name = suptic_icl_string_name( $value, $str_id, $key );
			$new_raw_values[$key] = suptic_icl_translate( $string_name, $value );
		}

		$new_pipes = new SupTic_Pipes( $new_raw_values );
		$new_values = $new_pipes->collect_befores();
		$tag['pipes'] = $new_pipes;

	} elseif ( $values ) {
		foreach ( $values as $key => $value ) {
			$string_name = suptic_icl_string_name( $value, $str_id, $key );
			$new_values[$key] = suptic_icl_translate( $string_name, $value );
		}
	}

	if ( preg_match( '/^(?:text|email|textarea|captchar|submit)[*]?$/', $type ) )
		$tag['labels'] = $tag['values'] = $new_values;
	else
		$tag['labels'] = $new_values;

	$content = trim( $content );

	if ( ! empty( $content ) ) {
		$string_name = suptic_icl_string_name( $content, $str_id );
		$content = suptic_icl_translate( $string_name, $content );
		$tag['content'] = $content;
	}

	return $tag;
}


/* Collecting strings hook after saving */

add_action( 'suptic_after_save_form', 'suptic_icl_collect_strings' );

function suptic_icl_collect_strings( &$contact_form ) {
	$scanned = $contact_form->form_scan_shortcode();

	foreach ( $scanned as $tag ) {
		if ( ! is_array( $tag ) )
			continue;

		$type = $tag['type'];
		$name = $tag['name'];
		$options = (array) $tag['options'];
		$raw_values = (array) $tag['raw_values'];
		$content = $tag['content'];

		$icl_option = array();
		foreach ( $options as $option ) {
			if ( 'icl' == $option ) {
				$icl_option = array( 'icl', null );
				break;
			} elseif ( preg_match( '/^icl:(.+)$/', $option, $matches ) ) {
				$icl_option = array( 'icl', $matches[1] );
				break;
			}
		}

		if ( ! ('icl' == $type || $icl_option ) )
			continue;

		$str_id = $icl_option[1] ? $icl_option[1] : $name;

		if ( ! empty( $content ) ) {
			$string_name = suptic_icl_string_name( $content, $str_id );
			suptic_icl_register_string( $string_name, $content );

		} elseif ( ! empty( $raw_values ) ) {
			foreach ( $raw_values as $key => $value ) {
				$value = trim( $value );
				$string_name = suptic_icl_string_name( $value, $str_id, $key );
				suptic_icl_register_string( $string_name, $value );
			}
		}
	}
}


/* Functions */

function suptic_icl_string_name( $value, $name = '', $key = '' ) {
	if ( ! empty( $name ) ) {
		$string_name = '@' . $name;
		if ( '' !== $key )
			$string_name .= ' ' . $key;
	} else {
		$string_name = '#' . md5( $value );
	}

	return $string_name;
}

function suptic_icl_register_string( $name, $value ) {
	if ( ! function_exists( 'icl_register_string' ) )
		return false;

	$context = 'Support Tickets';

	$value = trim( $value );
	if ( empty( $value ) )
		return false;

	icl_register_string( $context, $name, $value );
}

function suptic_icl_translate( $name, $value = '' ) {
	if ( ! function_exists( 'icl_t' ) )
		return $value;

	if ( empty( $name ) )
		return $value;

	$context = 'Support Tickets';

	return icl_t( $context, $name, $value );
}


/* Filter for suptic_get_form_for_page() */

add_filter( 'suptic_get_form_for_page', 'suptic_icl_get_form_for_page_filter', 10, 2 );

function suptic_icl_get_form_for_page_filter( $form, $page_id ) {
	global $wpdb;

	if ( ! empty( $form ) )
		return $form;

	$translations = wpml_get_content_translations( 'post', $page_id );

	foreach ( (array) $translations as $translation ) {
		if ( ! ( $t_page_id = absint( $translation ) ) || $t_page_id == $page_id )
			continue;

		$table = suptic_db_table( 'forms' );
		$query = $wpdb->prepare( "SELECT * FROM $table WHERE page_id = %d", $t_page_id );

		if ( $result = $wpdb->get_row( $query ) )
			return new SupTic_Form( $result );
	}

	return $form;
}


/**
**	Translation for Ticket Message Content
**/

/* Storing ticket data into translations table */

add_action( 'suptic_after_create_ticket', 'suptic_icl_store_translation_for_ticket' );

function suptic_icl_store_translation_for_ticket( &$ticket ) {
	wpml_add_translatable_content( 'suptic-ticket', $ticket->id, wpml_get_current_language() );
}

/* Show ticket count per language on edit tickets page */

add_filter( 'suptic_edit_tickets_subsubsub', 'suptic_icl_edit_tickets_subsubsub' );

function suptic_icl_edit_tickets_subsubsub( $subsubsub ) {
	$subsubsub[] = 'br';

	$current_lang = $_GET['lang'];

	if ( $languages = wpml_get_active_languages() ) {
		foreach ( $languages as $lang ) {
			$language_code = $lang['code'];
			$language_name = $lang['english_name'];
			if ( ! $language_code || ! $language_name )
				continue;

			$subsub = '<a href="'
				. suptic_admin_url( 'edit-tickets.php',
					array( 'status' => 'all', 'lang' => $language_code ) )
				. '"' . ( $language_code == $current_lang ? ' class="current"' : '' ) . '>'
				. esc_html( $language_name );

			if ( $count = suptic_icl_count_tickets_by_language( $language_code ) )
				$subsub .= ' <span class="count">(' . $count . ')</span>';

			$subsub .= '</a>';

			$subsubsub[] = $subsub;
		}
	}

	$subsubsub[] = '<a href="'
		. suptic_admin_url( 'edit-tickets.php', array( 'status' => 'all', 'lang' => 'all' ) )
		. '"' . ( ( empty( $current_lang ) || 'all' == $current_lang ) ? ' class="current"' : '' )
		. '>' . esc_html( __( 'All languages', 'suptic' ) )
		. ' <span class="count">(' . suptic_icl_count_tickets_by_language() . ')</span></a>';

	return $subsubsub;
}

add_action( 'suptic_admin_tickets_filter', 'suptic_icl_admin_tickets_filter' );

function suptic_icl_admin_tickets_filter() {
	if ( ! $lang = $_GET['lang'] )
		return;

?>
<div style="display: none;">
<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>" />
</div>
<?php
}

add_filter( 'suptic_admin_tickets_on_edit_tickets', 'suptic_icl_admin_get_tickets_filter', 10, 2 );

function suptic_icl_admin_get_tickets_filter( $tickets, $args ) {
	if ( ! ( $lang = $_GET['lang'] ) || 'all' == $lang )
		return $tickets;

	$tickets = suptic_icl_get_tickets_by_language( $lang, $args );

	return $tickets;
}

function suptic_icl_get_tickets_by_language( $lang = '', $args = '' ) {
	global $wpdb;

	if ( ! $lang )
		return suptic_get_tickets( $args );

	$defaults = array(
		'perpage' => 0,
		'offset' => 0,
		'orderby' => 'id',
		'status' => array(),
		'search' => '' );

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	$tickets_table = suptic_db_table( 'tickets' );
	$messages_table = suptic_db_table( 'messages' );
	$meta_table = suptic_db_table( 'meta' );
	$tr_table = $wpdb->prefix . 'icl_translations';
	$query = "SELECT DISTINCT tickets.* FROM $tickets_table as tickets"
		. " INNER JOIN $tr_table AS tc ON tickets.id = tc.element_id"
		. " AND tc.element_type LIKE 'suptic-ticket'"
		. " LEFT JOIN $messages_table AS messages ON ticket_id = tickets.id"
		. " LEFT JOIN $meta_table AS meta ON object_type LIKE 'ticket' AND object_id = tickets.id"
		. " WHERE ticket_type LIKE 'ticket'"
		. $wpdb->prepare( " AND language_code LIKE %s", $lang );

	if ( is_array( $status ) && ! empty( $status ) ) {
		$query .= " AND (1=0";
		foreach ( $status as $s ) {
			$query .= $wpdb->prepare( " OR tickets.status LIKE %s", '%' . $s . '%' );
		}
		$query .= ")";
	}

	if ( $search = trim( stripslashes( $search ) ) ) {
		$search = explode( ' ', $search );
		foreach( $search as $s ) {
			$query .= " AND (1 = 0";
			$query .= $wpdb->prepare( " OR subject LIKE %s", '%' . $s . '%' );
			$query .= $wpdb->prepare( " OR author_email LIKE %s", '%' . $s . '%' );
			$query .= $wpdb->prepare( " OR author_first_name LIKE %s", '%' . $s . '%' );
			$query .= $wpdb->prepare( " OR author_last_name LIKE %s", '%' . $s . '%' );
			$query .= $wpdb->prepare( " OR message_body LIKE %s", '%' . $s . '%' );
			$query .= $wpdb->prepare( " OR (meta_key NOT LIKE '_%' AND meta_value LIKE %s)",
				'%' . $s . '%' );
			$query .= ")";
		}
	}

	if ( 'update_time' == $orderby )
		$query .= " ORDER BY tickets.update_time DESC";
	elseif ( 'create_time' == $orderby )
		$query .= " ORDER BY tickets.create_time DESC";
	else
		$query .= " ORDER BY tickets.id ASC";

	if ( $offset && $perpage ) {
		$query .= $wpdb->prepare( " LIMIT %d,%d", $offset, $perpage );
	} elseif ( $perpage ) {
		$query .= $wpdb->prepare( " LIMIT %d", $perpage );
	}

	if ( $result = $wpdb->get_results( $query ) ) {
		$tickets = array();
		foreach ( $result as $t ) {
			$tickets[] = new SupTic_Ticket( $t );
		}
		return $tickets;
	}

	return null;
}

function suptic_icl_count_tickets_by_language( $lang = '', $args = '' ) {
	global $wpdb;

	if ( ! $lang )
		return suptic_count_tickets( $args );

	$defaults = array( 'status' => array() );

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	$tickets_table = suptic_db_table( 'tickets' );
	$tr_table = $wpdb->prefix . 'icl_translations';
	$query = "SELECT COUNT(*) FROM $tickets_table as tickets"
		. " INNER JOIN $tr_table AS tc ON tickets.id = tc.element_id"
		. " AND tc.element_type LIKE 'suptic-ticket'"
		. " WHERE ticket_type LIKE 'ticket'"
		. $wpdb->prepare( " AND language_code LIKE %s", $lang );

	if ( is_array( $status ) && ! empty( $status ) ) {
		$query .= " AND (1=0";
		foreach ( $status as $s ) {
			$query .= $wpdb->prepare( " OR status LIKE %s", '%' . $s . '%' );
		}
		$query .= ")";
	}

	return (int) $wpdb->get_var( $query );
}

/* Ticket message input box filter */

add_filter( 'suptic_ticket_message_input', 'suptic_icl_show_ticket_message_input', 10, 2 );

function suptic_icl_show_ticket_message_input( $html, $ticket_id ) {
	global $user_ID;

	if ( ! $ticket = suptic_get_ticket( $ticket_id ) )
		return $html;

	if ( ! $user_ID || $ticket->user_id == $user_ID ) // Visitor
		return $html;

	if ( ! $ticket_language_code = wpml_get_current_language() )
		return $html;

	if ( ! $admin_language_code = get_usermeta( $user_ID, 'icl_admin_language' ) )
		return $html;

	if ( $ticket_language_code == $admin_language_code )
		return $html;

	if ( ! suptic_icl_is_content_translation_enabled() )
		return $html;

	$html .= ' <input type="checkbox" name="suptic_icl_apply_translation_for_this_message" />';
	$html .= esc_html( __( "Translate this reply to visitor's language?", 'suptic' ) );

	$html .= ' <em>(' . esc_html( sprintf( __( '%1$s &raquo; %2$s', 'suptic' ),
		suptic_icl_get_language_display_name( $admin_language_code ),
		suptic_icl_get_language_display_name( $ticket_language_code ) ) ) . ')</em>';

	return $html;
}

add_action( 'suptic_after_create_message', 'suptic_icl_after_create_message_action' );

function suptic_icl_after_create_message_action( $message_id ) {
	if ( ! $message = suptic_get_message( $message_id ) )
		return;

	$ticket = $message->ticket();

	$original_language = get_usermeta( $message->user_id, 'icl_admin_language' );
	$current_language = wpml_get_current_language();

	$word_count = suptic_icl_get_message_word_count( $message->message_body, $original_language );
	suptic_icl_update_message_word_count( $message->id, $word_count );

	$need_translate = $message->is_admin_reply()
		&& $_POST['suptic_icl_apply_translation_for_this_message']
		&& $original_language != $current_language;

	if ( $need_translate ) {
		$message->set_status( 'draft' );

		wpml_add_translatable_content( 'suptic-message', $message->id, $original_language );

		if ( wpml_send_content_to_translation( $message->message_body, $message->id,
			'suptic-message', $original_language, $current_language ) ) {

			$message->update_meta( 'translation_status', 'in_progress' );
		} else {
			$message->update_meta( 'translation_status', 'failed' );
		}
	} else {
		wpml_add_translatable_content( 'suptic-message', $message->id, $current_language );
	}
}


/* Add translated message when the translation is completed */

add_action( 'init', 'suptic_icl_add_callback_for_received_translation' );

function suptic_icl_add_callback_for_received_translation() {
	wpml_add_callback_for_received_translation( 'suptic-message',
		'suptic_icl_add_message_translation' );
}

function suptic_icl_add_message_translation( $object_id, $to_language, $translation ) {
	global $wpdb;

	$message = suptic_get_message( $object_id );

	$word_count = suptic_icl_get_message_word_count( $translation, $to_language );

	$table = suptic_db_table( 'messages' );
	$result = $wpdb->update( $table,
		array( 'message_body' => $translation, 'word_count' => $word_count ),
		array( 'id' => absint( $message->id ) ),
		array( '%s', '%d' ), '%d' );

	if ( $result ) {
		wpml_update_translatable_content( 'suptic-message', $message->id, $to_language );

		$message->update_meta( 'translation_status', 'complete' );

		suptic_icl_translation_complete_notification( $message->id );
	}
}

function suptic_icl_translation_complete_notification( $message_id ) {
	if ( ! $message = suptic_get_message( $message_id ) )
		return;

	$ticket = $message->ticket();

	$subject = sprintf( __( '[%s] Support Tickets - Translation is complete (TKT:%d)', 'suptic' ),
		get_option( 'blogname' ), $ticket->id );

	$body = sprintf( __( "Translation for your message is complete.", 'suptic' ) ) . "\n\n";

	$body .= sprintf( __( "See the message and publish it on the web at %s", 'suptic' ),
		$ticket->url() ) . "\n\n";

	$body .= "--\n";
	$body .= __( "Please do not reply to this message; it was sent from an unmonitored email address.", 'suptic' ) . "\n";
	$body .= sprintf( '_ticket_tracking_code: %s', $ticket->access_key ) . "\n";

	if ( $admin_email = get_option('admin_email') )
		@wp_mail( $admin_email, $subject, $body );
}


/* Word count based on wpml_get_word_count() */

function suptic_icl_update_message_word_count( $message_id, $word_count ) {
	global $wpdb;

	$table = suptic_db_table( 'messages' );
	$result = $wpdb->update( $table,
		array( 'word_count' => $word_count ), array( 'id' => absint( $message_id ) ),
		'%d', '%d' );

	return (bool) $result;
}

function suptic_icl_get_message_word_count( $string, $language ) {
	$wc = wpml_get_word_count( $string, $language );
	return $wc['count'];
}


/* Put information after a message body */

add_filter( 'suptic_message_body', 'suptic_icl_message_body_filter', 10, 2 );

function suptic_icl_message_body_filter( $message_body, $message_id ) {
	if ( ! suptic_you_can_access_all_tickets() ) // For admins only
		return $message_body;

	$message = suptic_get_message( $message_id );

	if ( $message->is_admin_reply() ) {
		$translation_status = $message->get_meta( 'translation_status' );

		if ( 'in_progress' == $translation_status ) {
			$translation_status = '<div class="icl-translation-status inprogress">'
				. esc_html( __( "Translation for this message is now in progress.", 'suptic' ) )
				. '</div>';
		} elseif ( 'failed' == $translation_status ) {
			$translation_status = '<div class="icl-translation-status failed">'
				. esc_html( __( "Translation for this message is failed.", 'suptic' ) )
				. '</div>';
		} elseif ( 'complete' == $translation_status ) {
			$translation_status = '<div class="icl-translation-status complete">'
				. esc_html( __( "Translation for this message is complete.", 'suptic' ) );

			if ( ! $message->has_status( 'publish' ) ) {
				$pubnot_url = add_query_arg( array(
					'message_id' => $message_id, '_suptic_action' => 'icl_publish_notify' ) );

				$pubnot_url = wp_nonce_url( $pubnot_url,
					'suptic-message-publish-notify-' . $message_id );

				$translation_status .= '<div class="publish-notify"><a href="' . $pubnot_url . '">' . esc_html( __( "&raquo; Publish this message and notify the ticket author.", 'suptic' ) ) . '</a></div>';
			}

			$translation_status .= '</div>';
		}

		return $message_body . $translation_status;
	} else {
		$translation = suptic_icl_get_machine_translation( $message->message_body );
		return $message_body . $translation;
	}
}

function suptic_icl_get_machine_translation( $message_body ) {
	global $user_ID;

	$original_language = wpml_get_current_language();
	$admin_language = get_usermeta( $user_ID, 'icl_admin_language' );

	if ( $original_language == $admin_language )
		return '';

	$translated_message_body = wpml_machine_translation( $message_body,
		$original_language, $admin_language );

	$translated_message_body = wptexturize( $translated_message_body );
	$translated_message_body = make_clickable( $translated_message_body );
	$translated_message_body = links_add_target( $translated_message_body );
	$translated_message_body = convert_smilies( $translated_message_body );
	$translated_message_body = convert_chars( $translated_message_body );
	$translated_message_body = wpautop( $translated_message_body );

	$translation = '<div class="icl-translation"><div class="icl-translation-toggle"><a>'
		. esc_html( __( 'Translation', 'suptic' ) ) . '</a></div>';

	$desc = '<div class="description">' . esc_html( __( 'Translated by Google', 'suptic' ) )
		. ' (' . esc_html( sprintf( __( '%1$s &raquo; %2$s', 'suptic' ),
			suptic_icl_get_language_display_name( $original_language ),
			suptic_icl_get_language_display_name( $admin_language ) ) ) . ')</div>';

	$translation .= '<div class="icl-translation-body">'
		. $translated_message_body . $desc . '</div></div>';

	return $translation;
}

add_action( 'init', 'suptic_icl_init_switch', 11 );

function suptic_icl_init_switch() {
	if ( is_admin() )
		return;

	if ( 'icl_publish_notify' == $_GET['_suptic_action'] ) {
		$message_id = (int) $_GET['message_id'];

		if ( ! suptic_check_referer( 'suptic-message-publish-notify-' . $message_id ) )
			return;

		if ( ! suptic_you_can_access_all_tickets() )
			return;

		if ( ! $message = suptic_get_message( $message_id ) )
			return;

		$message->set_status( 'publish' );

		suptic_message_replied_notification( $message->ticket(), $message );
	}
}


/* Header */

add_action( 'wp_head', 'suptic_icl_head' );

function suptic_icl_head() {
	if ( ! isset( $_GET['ticket'] ) )
		return;
?>
<style type="text/css">
	div.icl-translation {
		margin: 0.5em;
	}

	div.icl-translation div.icl-translation-toggle {
		font-size: smaller;
		font-weight: bold;
	}

	div.icl-translation div.icl-translation-body {
		padding: 0.5em;
		-moz-border-radius: 6px;
		-khtml-border-radius: 6px;
		-webkit-border-radius: 6px;
		border-radius: 6px;
		background-color: #c7d7ff;
	}

	div.icl-translation-status {
		margin: 0.5em;
		color: #009999;
	}

	div.icl-translation-status div.publish-notify a {
		color: #009999;
		font-weight: bold;
	}
</style>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
    try {
        jQuery('div.icl-translation div.icl-translation-body').hide();

		jQuery('div.icl-translation div.icl-translation-toggle').click(function() {
			jQuery(this).next('div.icl-translation-body').toggle('fast');
		});
    } catch (e) {
    }
});
//]]>
</script>
<?php
}


/* Delete corresponding entry from icl_translations table */

add_action( 'suptic_after_delete_ticket', 'suptic_icl_delete_ticket_action' );

function suptic_icl_delete_ticket_action( $ticket_id ) {
	wpml_delete_translatable_content( 'suptic-ticket', $ticket_id );
}

add_action( 'suptic_after_delete_message', 'suptic_icl_delete_message_action' );

function suptic_icl_delete_message_action( $message_id ) {
	wpml_delete_translatable_content( 'suptic-message', $message_id );
}


/* Wrapper functions for Sitepress class */

function suptic_icl_get_language_display_name( $lang_code ) {
	global $sitepress;

	$details = $sitepress->get_language_details( $lang_code );
	return $details['display_name'];
}

function suptic_icl_is_content_translation_enabled() {
	global $sitepress;

	return $sitepress->get_icl_translation_enabled();
}

?>