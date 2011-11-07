<?php

/* Form */

class SupTic_Form {

	var $initial = false;

	var $id;
	var $name;
	var $page_id;
	var $form_design;
	var $status;

	var $responses_count = 0;
	var $scanned_form_tags;

	function SupTic_Form( $obj = null ) {
		if ( is_object( $obj ) ) {
			$vars = get_object_vars( $obj );
			foreach ( $vars as $var => $value ) {
				if ( isset( $obj->$var ) )
					$this->$var = $obj->$var;
			}
		}
	}

	function render() {
		$html = '<div class="suptic form">';
		$html .= '<form action="" method="post">';
		$html .= '<div style="display: none;">';
		$html .= wp_nonce_field( 'suptic-form-' . $this->id . '-create-ticket',
			"_wpnonce", false, false );
		$html .= '<input type="hidden" name="_suptic_action" value="create_ticket" />';
		$html .= '<input type="hidden" name="_suptic_form_id" value="' . $this->id . '" />';
		$html .= '<input type="hidden" name="_suptic_version" value="' . SUPTIC_VERSION . '" />';
		$html .= '<input type="hidden" name="_suptic_page_id" value="' . get_the_ID() . '" />';
		$html .= '</div>';

		$html .= $this->form_elements();

		if ( ! $this->responses_count )
			$html .= $this->form_response_output();

		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	function form_response_output() {
		$class = 'suptic-response-output';

		if ( isset( $_POST['_suptic_form_response'] )
			&& $_POST['_suptic_form_response']['id'] == $this->id ) {
			if ( ! $_POST['_suptic_form_response']['ok'] ) {
				$class .= ' suptic-form-ng';
				if ( $_POST['_suptic_form_response']['spam'] )
					$class .= ' suptic-spam-blocked';
				$content = $_POST['_suptic_form_response']['message'];
			}
		} elseif ( isset( $_POST['_suptic_validation_errors'] )
			&& $_POST['_suptic_validation_errors']['id'] == $this->id ) {
			$class .= ' suptic-validation-errors';
			$content = __( "Validation errors occurred. Please confirm the fields and submit it again.", 'suptic' );
		} elseif ( $_GET['message'] == 'failed_to_create_ticket' ) {
			$class .= ' suptic-form-ng';
			$content = __( "Problem occurred. Please try later or contact administrator by other way.", 'suptic' );
		} else {
			$class .= ' suptic-display-none';
		}

		$class = ' class="' . $class . '"';

		return '<div' . $class . '>' . $content . '</div>';
	}

	function form_do_shortcode() {
		global $suptic_shortcode_manager;

		$form = $this->form_design;

		$form = $suptic_shortcode_manager->do_shortcode( $form );
		$this->scanned_form_tags = $suptic_shortcode_manager->scanned_tags;

		if ( SUPTIC_AUTOP )
			$form = suptic_autop( $form );

		return $form;
	}

	function form_scan_shortcode( $cond = null ) {
		global $suptic_shortcode_manager;

		if ( ! empty( $this->scanned_form_tags ) ) {
			$scanned = $this->scanned_form_tags;
		} else {
			$scanned = $suptic_shortcode_manager->scan_shortcode( $this->form_design );
			$this->scanned_form_tags = $scanned;
		}

		if ( empty( $scanned ) )
			return null;

		if ( ! is_array( $cond ) || empty( $cond ) )
			return $scanned;

		for ( $i = 0, $size = count( $scanned ); $i < $size; $i++ ) {

			if ( is_string( $cond['type'] ) && ! empty( $cond['type'] ) ) {
				if ( $scanned[$i]['type'] != $cond['type'] ) {
					unset( $scanned[$i] );
					continue;
				}
			} elseif ( is_array( $cond['type'] ) ) {
				if ( ! in_array( $scanned[$i]['type'], $cond['type'] ) ) {
					unset( $scanned[$i] );
					continue;
				}
			}

			if ( is_string( $cond['name'] ) && ! empty( $cond['name'] ) ) {
				if ( $scanned[$i]['name'] != $cond['name'] ) {
					unset ( $scanned[$i] );
					continue;
				}
			} elseif ( is_array( $cond['name'] ) ) {
				if ( ! in_array( $scanned[$i]['name'], $cond['name'] ) ) {
					unset( $scanned[$i] );
					continue;
				}
			}
		}

		return array_values( $scanned );
	}

	function form_elements() {
		$form = $this->form_do_shortcode();

		// Response output
		$response_regex = '%\[\s*response\s*\]%';
		$form = preg_replace_callback( $response_regex,
			array( &$this, 'response_replace_callback' ), $form );

		return $form;
	}

	function response_replace_callback( $matches ) {
		$this->responses_count += 1;
		return $this->form_response_output();
	}

	/* Validate */

	function validate() {
		$codes = $this->form_scan_shortcode();

		$result = array( 'valid' => true, 'reason' => array() );

		foreach ( $codes as $code ) {
			$type = $code['type'];
			$name = $code['name'];

			if ( empty( $name ) )
				continue;

			$result = apply_filters( 'suptic_validate_' . $type, $result, $code );
		}

		return $result;
	}

	function accepted() {
		$accepted = true;

		return apply_filters( 'suptic_acceptance', $accepted );
	}

	/* Akismet */

	function akismet() {
		global $akismet_api_host, $akismet_api_port;

		if ( ! function_exists( 'akismet_http_post' ) ||
			! ( get_option( 'wordpress_api_key' ) || $wpcom_api_key ) )
			return false;

		$akismet_ready = false;
		$author = $author_email = $author_url = $content = '';
		$fes = $this->form_scan_shortcode();

		foreach ( $fes as $fe ) {
			if ( 'captchac' == $fe['type'] || 'captchar' == $fe['type'] )
				continue;

			if ( ! is_array( $fe['options'] ) ) continue;

			if ( preg_grep( '%^akismet:author$%', $fe['options'] ) && '' == $author ) {
				$author = $_POST[$fe['name']];
				$akismet_ready = true;
			}

			if ( preg_grep( '%^akismet:author_email$%', $fe['options'] ) && '' == $author_email ) {
				$author_email = $_POST[$fe['name']];
				$akismet_ready = true;
			}

			if ( preg_grep( '%^akismet:author_url$%', $fe['options'] ) && '' == $author_url ) {
				$author_url = $_POST[$fe['name']];
				$akismet_ready = true;
			}

			if ( '' != $content )
				$content .= "\n\n";

			$content .= $_POST[$fe['name']];
		}

		if ( ! $akismet_ready )
			return false;

		$c['blog'] = get_option( 'home' );
		$c['user_ip'] = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
		$c['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		$c['referrer'] = $_SERVER['HTTP_REFERER'];
		$c['comment_type'] = 'support-tickets-plugin';
		if ( $permalink = get_permalink() )
			$c['permalink'] = $permalink;
		if ( '' != $author )
			$c['comment_author'] = $author;
		if ( '' != $author_email )
			$c['comment_author_email'] = $author_email;
		if ( '' != $author_url )
			$c['comment_author_url'] = $author_url;
		if ( '' != $content )
			$c['comment_content'] = $content;

		$ignore = array( 'HTTP_COOKIE' );

		foreach ( $_SERVER as $key => $value )
			if ( ! in_array( $key, (array) $ignore ) )
				$c["$key"] = $value;

		$query_string = '';
		foreach ( $c as $key => $data )
			$query_string .= $key . '=' . urlencode( stripslashes( $data ) ) . '&';

		$response = akismet_http_post( $query_string, $akismet_api_host,
			'/1.1/comment-check', $akismet_api_port );
		if ( 'true' == $response[1] )
			return true;
		else
			return false;
	}

	function save() {
		global $wpdb;

		$table = suptic_db_table( 'forms' );

		if ( $this->initial ) {
			$result = $wpdb->insert( $table, stripslashes_deep( array(
				'name' => $this->name,
				'page_id' => $this->page_id,
				'form_design' => $this->form_design,
				'status' => $this->status ) ), array( '%s', '%d', '%s', '%s') );

			if ( $result ) {
				$this->initial = false;
				$this->id = $wpdb->insert_id;

				do_action_ref_array( 'suptic_after_create_form', array( &$this ) );
			} else {
				return false; // Failed to save
			}

		} else { // Update
			if ( ! absint( $this->id ) )
				return false; // Missing ID

			$result = $wpdb->update( $table, stripslashes_deep( array(
				'name' => $this->name,
				'page_id' => $this->page_id,
				'form_design' => $this->form_design,
				'status' => $this->status ) ), array( 'id' => absint( $this->id) ),
				array( '%s', '%d', '%s', '%s' ), '%d' );

			if ( false !== $result ) {
				do_action_ref_array( 'suptic_after_update_form', array( &$this ) );
			} else {
				return false; // Failed to save
			}
		}

		do_action_ref_array( 'suptic_after_save_form', array( &$this ) );
		return true; // Succeeded to save
	}

	function build_ticket( $ticketarr = array() ) {
		$ticket = new Suptic_Ticket();
		$ticket->initial = true;
		$ticket->form_id = $this->id;
		$ticket->user_id = $ticketarr['user_id'];
		$ticket->page_id = $ticketarr['page_id'];
		$ticket->author_email = $ticketarr['author_email'];
		$ticket->author_first_name = $ticketarr['author_first_name'];
		$ticket->author_last_name = $ticketarr['author_last_name'];
		$ticket->subject = $ticketarr['subject'];
		$ticket->status = $ticketarr['status'];
		return $ticket;
	}

	function create_ticket( $ticketarr = array() ) {
		$ticket = $this->build_ticket( $ticketarr );
		if ( $ticket->save() )
			return $ticket;
		else
			return false;
	}

	function create_ticket_and_first_message() {
		global $user_ID;

		$key_vars = array( 'email', 'first-name', 'last-name', 'subject', 'message');
		$key_posted = array();
		$posted = array();

		$codes = $this->form_scan_shortcode();
		foreach ( $codes as $code ) {
			$type = $code['type'];
			$name = $code['name'];

			if ( 'captchac' == $type || 'captchar' == $type )
				continue;

			if ( empty( $name ) )
				continue;

			if ( in_array( $name, $key_vars ) )
				$key_posted[$name] = $_POST[$name];
			else
				$posted[$name] = $_POST[$name];
		}

		$page_id = $_POST['_suptic_page_id'];

		$ticket = $this->create_ticket(
			array(
				'user_id' => $user_ID,
				'page_id' => (int) $page_id,
				'author_email' => trim( $key_posted['email'] ),
				'author_first_name' => trim( $key_posted['first-name'] ),
				'author_last_name' => trim( $key_posted['last-name'] ),
				'subject' => trim( $key_posted['subject'] ),
				'status' => 'new' ) );

		if ( $ticket ) {
			if ( $message_body = trim( $key_posted['message'] ) )
				$ticket->create_message( array( 'message_body' => $message_body ) );

			foreach ( $posted as $key => $value ) {
				foreach ( (array) $value as $v ) {
					$ticket->add_meta( $key, $v );
				}
			}

			do_action_ref_array( 'suptic_control_create_ticket', array( &$ticket ) );

			return $ticket;
		}

		return null;
	}
}

function suptic_get_forms() {
	global $wpdb;

	$table = suptic_db_table( 'forms' );
	$query = "SELECT * FROM $table";
	if ( ! $forms = $wpdb->get_results( $query ) )
		return;

	$results = array();
	foreach ( $forms as $form ) {
		$results[] = new SupTic_Form( $form );
	}

	return $results;
}

function suptic_get_form( $id ) {
	global $wpdb;

	if ( ! $id = absint( $id ) )
		return false;

	$table = suptic_db_table( 'forms' );
	$query = $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );

	if ( $result = $wpdb->get_row( $query ) )
		return new SupTic_Form( $result );

	return false;
}

function suptic_get_form_for_page( $page_id ) {
	global $wpdb;

	if ( ! $page_id = absint( $page_id ) )
		return false;

	$table = suptic_db_table( 'forms' );
	$query = $wpdb->prepare( "SELECT * FROM $table WHERE page_id = %d", $page_id );

	if ( $result = $wpdb->get_row( $query ) )
		$form = new SupTic_Form( $result );

	return apply_filters( 'suptic_get_form_for_page', $form, $page_id );
}

function suptic_insert_form( $formarr = array() ) {
	$form_name = $formarr['form_name'];
	$form_name = suptic_sanitize_form_name( $form_name );

	$form_design = $formarr['form_design'];

	$page_id = (int) $formarr['form_page'];

	$form = new SupTic_Form();
	$form->initial = true;
	$form->name = $form_name;
	$form->form_design = $form_design;
	$form->page_id = $page_id;

	if ( $form->save() )
		return $form;
}

function suptic_update_form( $form_id, $formarr = array() ) {
	$form_name = $formarr['form_name'];
	$form_name = suptic_sanitize_form_name( $form_name );

	$form_design = $formarr['form_design'];

	$page_id = (int) $formarr['form_page'];

	$form = suptic_get_form( $form_id );
	$form->name = $form_name;
	$form->form_design = $form_design;
	$form->page_id = $page_id;

	if ( $form->save() )
		return $form;
}

function suptic_delete_form( $form_id ) {
	global $wpdb;

	$table = suptic_db_table( 'forms' );
	$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id = %d", absint( $form_id ) ) );
}

function suptic_sanitize_form_name( $text ) {
	$text = strip_tags( $text );
	$text = normalize_whitespace( $text );
	$text = trim( $text );
	if ( empty( $text ) )
		$text = __( 'Untitled', 'suptic' );

	return $text;
}

function suptic_default_form() {
	global $locale;

	$form = new SupTic_Form();
	$form->initial = true;
	$form->name = __( 'Untitled', 'suptic' );
	$form->form_design =
		__( 'Your Email', 'suptic' ) . ' ' . __( '(required)', 'suptic' ) . "\n"
		. '[email* email]' . "\n\n"
		. __( 'First Name', 'suptic' ) . "\n"
		. '[text first-name]' . "\n\n"
		. __( 'Last Name', 'suptic' ) . "\n"
		. '[text last-name]' . "\n\n"
		. __( 'Subject', 'suptic' ) . ' ' . __( '(required)', 'suptic' ) . "\n"
		. '[text* subject]' . "\n\n"
		. __( 'Message', 'suptic' ) . ' ' . __( '(required)', 'suptic' ) . "\n"
		. '[textarea* message]' . "\n\n"
		. '[submit "' . __( 'Send', 'suptic' ) . '"]';
	return $form;
}


/* Ticket */

class SupTic_Ticket {

	var $initial = false;

	var $id;
	var $form_id;
	var $user_id;
	var $page_id;
	var $access_key;
	var $author_email;
	var $author_first_name;
	var $author_last_name;
	var $subject;
	var $ticket_type = 'ticket';
	var $parent_id;
	var $status;
	var $create_time;
	var $update_time;

	var $form;

	function SupTic_Ticket( $obj = null ) {
		if ( is_object( $obj ) ) {
			$vars = get_object_vars( $obj );
			foreach ( $vars as $var => $value ) {
				if ( isset( $obj->$var ) )
					$this->$var = $obj->$var;
			}
		}

		if ( ! $this->access_key )
			$this->access_key = $this->generate_access_key();
	}

	function render() {
		$html = '<div class="suptic ticket">';
		$html .= '<h3 class="ticket-subject">' . esc_html( $this->subject ) . '</h3>';
		$html .= $this->render_messages();
		$html .= '<form action="" method="post">';
		$html .= '<div style="display: none;">';
		$html .= wp_nonce_field( 'suptic-ticket-' . $this->id . '-add-message',
			"_wpnonce", false, false );
		$html .= '<input type="hidden" name="_suptic_action" value="add_message" />';
		$html .= '<input type="hidden" name="_suptic_ticket_id" value="' . $this->id . '" />';
		$html .= '<input type="hidden" name="_suptic_version" value="' . SUPTIC_VERSION . '" />';
		$html .= '</div>';

		$html .= '<div class="message-input">';
		if ( $this->has_status( 'closed' ) ) {
			$html .= esc_html( __( "This ticket has been closed.", 'suptic' ) );
		} else {
			$input_box = $this->show_message_input();
			$html .= $input_box;
		}
		$html .= '</div>';
		$html .= '</form>';
		$html .= $this->form_response_output();
		$html .= $this->show_ticket_info();
		$html .= '</div>';

		return $html;
	}

	function show_message_input() {
		$html = '<textarea name="message-body" class="message-input" cols="40" rows="5"></textarea>';
		$html .= '<input type="submit" value="' . esc_attr( __( 'Send Message', 'suptic' ) ) . '" />';

		return apply_filters( 'suptic_ticket_message_input', $html, $this->id );
	}

	function show_ticket_info() {
		$html = '';

		if ( suptic_you_can_access_all_tickets() ) {
			$html .= '<form action="" method="post">';
			$html .= '<div style="display: none;">';
			$html .= wp_nonce_field( 'suptic-ticket-' . $this->id . '-close-or-reopen',
				"_wpnonce", false, false );
			$html .= '<input type="hidden" name="_suptic_action" value="close_or_reopen_ticket" />';
			$html .= '<input type="hidden" name="_suptic_ticket_id" value="' . $this->id . '" />';
			$html .= '<input type="hidden" name="_suptic_version" value="'
				. SUPTIC_VERSION . '" />';
			$html .= '</div>';
			$html .= '<p>';
			$html .= '<strong>' . esc_html( __( 'Status', 'suptic' ) ) . '</strong>';
			$html .= ' <span class="ticket-status">'
				. esc_html( $this->status ) . '</span>';
			$html .= '</p>';

			$html .= '<p>';
			if ( $this->has_status( 'closed' ) ) {
				$html .= '<input type="submit" name="suptic-reopen-ticket" value="' . esc_attr( __( "Reopen This Ticket", 'suptic' ) ) . '" />';
			} else {
				$html .= '<input type="submit" name="suptic-close-ticket" value="' . esc_attr( __( "Close This Ticket", 'suptic' ) ) . '" />';
			}
			$html .= ' | <a href="' . suptic_admin_url( 'edit-tickets.php',
				array( 'ticket_id' => $this->id ) ) . '">'
				. esc_html( __( 'Edit', 'suptic' ) ) . '</a>';
			$html .= '</p>';
			$html .= '</form>';

			$previous_ticket = $this->previous_ticket(
				array( 'status' => array( 'new', 'waiting_reply' ) ) );
			if ( $previous_ticket ) {
				$html .= '<p><strong>' . esc_html( __( 'Previous pending ticket', 'suptic' ) )
					. '</strong> <a href="' . $previous_ticket->url() . '">'
					. esc_html( $previous_ticket->subject )
					. '</a> </p>';
			}

			$next_ticket = $this->next_ticket(
				array( 'status' => array( 'new', 'waiting_reply' ) ) );
			if ( $next_ticket ) {
				$html .= '<p><strong>' . esc_html( __( 'Next pending ticket', 'suptic' ) )
					. '</strong> <a href="' . $next_ticket->url() . '">'
					. esc_html( $next_ticket->subject )
					. '</a></p>';
			}
		}

		$html = apply_filters( 'suptic_ticket_info', $html );

		if ( ! empty( $html ) )
			$html = '<div class="ticket-info">' . "\n"
				. '<div class="title">' . esc_html( __( 'Ticket Info', 'suptic' ) ) . '</div>'
				. "\n" . $html . '</div>' . "\n";

		return $html;
	}

	function form_response_output() {
		$class = 'suptic-response-output';

		if ( isset( $_POST['_suptic_form_response'] )
			&& $_POST['_suptic_form_response']['id'] == $this->id ) {
			if ( ! $_POST['_suptic_form_response']['ok'] ) {
				$class .= ' suptic-form-ng';
				$content = $_POST['_suptic_form_response']['message'];
			}
		}

		$class = ' class="' . $class . '"';

		return '<div' . $class . '>' . $content . '</div>';
	}

	function render_messages() {
		if ( suptic_you_can_access_all_tickets() ) // Admin
			$messages = $this->messages( array( 'status' => array( 'publish', 'draft' ) ) );
		else
			$messages = $this->messages( array( 'status' => array( 'publish' ) ) );

		if ( ! $messages )
			return '';

		$html = '<div class="ticket-messages">' . "\n\n";

		foreach ( $messages as $message ) {
			$html .= $message->render();
		}

		$html .= "\n\n" . '</div>';

		return $html;
	}

	function avatar( $size = 96 ) {
		if ( $this->author_email )
			return get_avatar( $this->author_email, $size );

		return null;
	}

	function author_name() {
		if ( $this->author_first_name || $this->author_last_name )
			return sprintf( _c( '%1$s %2$s|Name of person', 'suptic' ),
				$this->author_first_name, $this->author_last_name );

		return $this->author_email;
	}

	function url( $accesskey = false ) {
		$args = array( 'ticket' => $this->id );

		if ( $accesskey )
			$args['accesskey'] = $this->access_key;

		if ( ! $page_id = $this->page_id ) {
			$form = suptic_get_form( $this->form_id );
			$page_id = $form->page_id;
		}

		return add_query_arg( $args, get_page_link( $page_id ) );
	}

	function message_count() {
		global $wpdb;

		if ( $this->initial )
			return 0;

		$table = suptic_db_table( 'messages' );
		$query = "SELECT COUNT(id) as count FROM $table WHERE message_type LIKE 'message'";
		$query .= $wpdb->prepare( " AND ticket_id = %d", $this->id );

		return (int) $wpdb->get_var( $query );
	}

	function form() {
		if ( ! is_a( $this->form, 'SupTic_Form' ) )
			$this->form = suptic_get_form( $this->form_id );

		return $this->form;
	}

	function messages( $args = '' ) {
		global $wpdb;

		if ( ! $id = $this->id )
			return null;

		$defaults = array(
			'perpage' => 0,
			'offset' => 0,
			'status' => array(),
			'order' => 'older-first',
			'from' => 'both' );

		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );

		$messages_table = suptic_db_table( 'messages' );
		$tickets_table = suptic_db_table( 'tickets' );

		$fields = "messages.*";
		$join = "INNER JOIN $tickets_table AS tickets ON (messages.ticket_id = tickets.id)";

		$where = "AND message_type LIKE 'message'";
		$where .= $wpdb->prepare( " AND ticket_id = %d", $id );

		if ( is_array( $status ) && ! empty( $status ) ) {
			$where .= " AND (1=0";
			foreach ( $status as $s ) {
				$where .= $wpdb->prepare( " OR messages.status LIKE %s", '%' . $s . '%' );
			}
			$where .= ")";
		}

		if ( 'visitor' == $from ) {
			$where .= " AND (messages.user_id = 0 OR messages.user_id = tickets.user_id)";
		} elseif ( 'admin' == $from ) {
			$where .= " AND messages.user_id != tickets.user_id";
		}

		if ( 'newer-first' == $order ) {
			$orderby = "ORDER BY messages.create_time DESC";
		} else {
			$orderby = "ORDER BY messages.create_time ASC";
		}

		if ( $offset && $perpage ) {
			$limits = $wpdb->prepare( "LIMIT %d,%d", $offset, $perpage );
		} elseif ( $perpage ) {
			$limits = $wpdb->prepare( "LIMIT %d", $perpage );
		}

		$query_parts = compact( 'fields', 'join', 'where', 'orderby', 'limits' );
		$query_parts = apply_filters('suptic_messages_request', $query_parts);
		extract( $query_parts, EXTR_OVERWRITE );

		$query = "SELECT $fields FROM $messages_table AS messages $join"
			. " WHERE 1=1 $where $orderby $limits";

		if ( ! $messages = $wpdb->get_results( $query ) )
			return null;

		$message_objs = array();
		foreach ( $messages as $message ) {
			$message_objs[] = new SupTic_Message( $message );
		}
		return $message_objs;
	}

	function get_message( $id ) {
		global $wpdb;

		if ( ! $id = absint( $id ) )
			return null;

		$table = suptic_db_table( 'messages' );
		$query = "SELECT * FROM $table WHERE 1=1";
		$query .= $wpdb->prepare( " AND ticket_id = %d", $this->id );
		$query .= $wpdb->prepare( " AND id = %d", $id );

		return new SupTic_Message( $wpdb->get_row( $query ) );
	}

	function first_message() {
		$messages = $this->messages( array( 'perpage' => 1, 'order' => 'older-first' ) );
		return $messages[0];
	}

	function last_message() {
		$messages = $this->messages( array( 'perpage' => 1, 'order' => 'newer-first' ) );
		return $messages[0];
	}

	function save() {
		global $wpdb;

		$table = suptic_db_table( 'tickets' );

		if ( $this->initial ) {
			$result = $wpdb->insert( $table, stripslashes_deep( array(
				'form_id' => $this->form_id,
				'user_id' => $this->user_id,
				'page_id' => $this->page_id,
				'access_key' => $this->access_key,
				'author_email' => $this->author_email,
				'author_first_name' => $this->author_first_name,
				'author_last_name' => $this->author_last_name,
				'subject' => $this->subject,
				'ticket_type' => $this->ticket_type,
				'parent_id' => $this->parent_id,
				'status' => $this->status,
				'create_time' => current_time( 'mysql' ),
				'update_time' => current_time( 'mysql' ) ) ),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s') );

			if ( $result ) {
				$this->initial = false;
				$this->id = $wpdb->insert_id;

				do_action_ref_array( 'suptic_after_create_ticket', array( &$this ) );
			} else {
				return false; // Failed to save
			}

		} else { // Update
			if ( ! absint( $this->id ) )
				return false; // Missing ID

			$result = $wpdb->update( $table, stripslashes_deep( array(
				'author_email' => $this->author_email,
				'author_first_name' => $this->author_first_name,
				'author_last_name' => $this->author_last_name,
				'subject' => $this->subject,
				'status' => $this->status,
				'update_time' => current_time( 'mysql' ) ) ),
				array( 'id' => absint( $this->id) ), '%s', '%d' );

			if ( false !== $result ) {
				do_action_ref_array( 'suptic_after_update_ticket', array( &$this ) );
			} else {
				return false; // Failed to save
			}
		}

		do_action_ref_array( 'suptic_after_save_ticket', array( &$this ) );
		return true; // Succeeded to save
	}

	function create_message( $msgarr = array(), $update_ticket = true ) {
		global $wpdb, $user_ID;

		$current_time = current_time( 'mysql' );

		$body = apply_filters( 'suptic_pre_message_body', $msgarr['message_body'] );

		$user_id = isset( $msgarr['user_id'] ) ? $msgarr['user_id'] : $user_ID;
		$message_type = isset( $msgarr['message_type'] ) ? $msgarr['message_type'] : 'message';
		$status = isset( $msgarr['status'] ) ? $msgarr['status'] : 'publish';
		$parent_id = isset( $msgarr['parent_id'] ) ? $msgarr['parent_id'] : 0;

		$table = suptic_db_table( 'messages' );
		$result = $wpdb->insert( $table, stripslashes_deep( array(
			'ticket_id' => $this->id,
			'user_id' => $user_id,
			'message_body' => $body,
			'word_count' => count( explode( ' ', strip_tags( $body ) ) ),
			'message_type' => $message_type,
			'parent_id' => $parent_id,
			'status' => $status,
			'create_time' => $current_time ) ),
			array( '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s' ) );

		if ( $result ) {
			$message_id = $wpdb->insert_id;
			do_action( 'suptic_after_create_message', $message_id );

			if ( $update_ticket ) {
				$table = suptic_db_table( 'tickets' );
				$wpdb->update( $table, array( 'update_time' => $current_time ),
					array( 'id' => absint( $this->id ) ), '%s', '%d' );
			}

			return $this->get_message( $message_id );
		}

		return false;
	}

	function generate_access_key() {
		return wp_generate_password( 12, false );
	}

	function accessible() {
		global $user_ID;

		if ( suptic_you_can_access_all_tickets() )
			return true;

		if ( is_user_logged_in() && $this->user_id == $user_ID )
			return true;

		if ( $this->access_key == $_GET['accesskey'] )
			return true;

		return false;
	}

	function add_meta( $meta_key, $value ) {
		global $wpdb;

		$table = suptic_db_table( 'meta' );
		$result = $wpdb->insert( $table, array(
			'object_type' => 'ticket',
			'object_id' => $this->id,
			'meta_key' => $meta_key,
			'meta_value' => maybe_serialize( $value ) ),
			array( '%s', '%d', '%s', '%s' ) );

		if ( $result )
			return $wpdb->insert_id;

		return false;
	}

	function update_meta( $meta_key, $new_value ) {
		global $wpdb;

		$old_value = $this->get_meta( $meta_key );

		if ( $new_value === $old_value )
			return false;

		if ( false === $old_value )
			return $this->add_meta( $meta_key, $new_value );

		$table = suptic_db_table( 'meta' );
		$wpdb->update( $table,
			array( 'meta_value' => maybe_serialize( $new_value ) ),
			array( 'object_type' => 'ticket', 'object_id' => $this->id, 'meta_key' => $meta_key ),
			'%s', array( '%s', '%d', '%s' ) );

		return true;
	}

	function get_meta( $meta_key, $single = true ) {
		global $wpdb;

		$table = suptic_db_table( 'meta' );
		$query = "SELECT meta_value FROM $table WHERE object_type LIKE 'ticket'"
			. $wpdb->prepare( " AND object_id = %d", $this->id )
			. $wpdb->prepare( " AND meta_key LIKE %s", $meta_key );
		$meta_values = $wpdb->get_col( $query );

		if ( empty( $meta_values ) )
			return false;

		if ( $single )
			return maybe_unserialize( $meta_values[0] );

		return array_map( 'maybe_unserialize', $meta_values );
	}

	function get_metas( $include_hidden = false ) {
		global $wpdb;

		$table = suptic_db_table( 'meta' );
		$query = "SELECT meta_key, meta_value FROM $table WHERE object_type LIKE 'ticket'"
			. $wpdb->prepare( " AND object_id = %d", $this->id );

		if ( ! $include_hidden )
			$query .= " AND meta_key NOT LIKE '\_%'";

		$query .= " ORDER BY meta_key";
		return $wpdb->get_results( $query );
	}

	function set_status( $status ) {
		global $wpdb;

		$table = suptic_db_table( 'tickets' );
		$result = $wpdb->update( $table,
			array( 'status' => $status ), array( 'id' => $this->id ),
			'%s', '%d' );

		return (bool) $result;
	}

	function get_status() {
		return $this->status;
	}

	function has_status( $status ) {
		$current = $this->get_status();
		return $status == $current;
	}

	function previous_ticket( $args = '' ) {
		$args['next'] = 0;
		return $this->next_ticket( $args );
	}

	function next_ticket( $args = '' ) {
		global $wpdb;

		if ( ! $this->id )
			return null;

		$defaults = array(
			'next' => 1,
			'status' => array()
		);

		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );

		$table = suptic_db_table( 'tickets' );
		$query = "SELECT * FROM $table WHERE ticket_type LIKE 'ticket'";

		if ( is_array( $status ) && ! empty( $status ) ) {
			$query .= " AND (1=0";
			foreach ( $status as $s ) {
				$query .= $wpdb->prepare( " OR status LIKE %s", '%' . $s . '%' );
			}
			$query .= ")";
		}

		if ( $next ) {
			$query .= $wpdb->prepare( " AND id > %d", $this->id );
			$query .= " ORDER BY id ASC";
		} else {
			$query .= $wpdb->prepare( " AND id < %d", $this->id );
			$query .= " ORDER BY id DESC";
		}

		if ( $result = $wpdb->get_row( $query ) )
			return new SupTic_Ticket( $result );
	}
}

function suptic_get_tickets( $args = '' ) {
	global $wpdb;

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
	$query = "SELECT DISTINCT tickets.* FROM $tickets_table as tickets"
		. " LEFT JOIN $messages_table AS messages ON ticket_id = tickets.id"
		. " LEFT JOIN $meta_table AS meta ON object_type LIKE 'ticket' AND object_id = tickets.id"
		. " WHERE ticket_type LIKE 'ticket'";

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

function suptic_count_tickets( $args = '' ) {
	global $wpdb;

	$defaults = array( 'status' => array() );

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	$table = suptic_db_table( 'tickets' );
	$query = "SELECT COUNT(*) FROM $table WHERE ticket_type LIKE 'ticket'";

	if ( is_array( $status ) && ! empty( $status ) ) {
		$query .= " AND (1=0";
		foreach ( $status as $s ) {
			$query .= $wpdb->prepare( " OR status LIKE %s", '%' . $s . '%' );
		}
		$query .= ")";
	}

	return $wpdb->get_var( $query );
}

function suptic_get_ticket( $id ) {
	global $wpdb;

	if ( ! $id = absint( $id ) )
		return false;

	$table = suptic_db_table( 'tickets' );
	$query = $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );

	if ( $result = $wpdb->get_row( $query ) )
		return new SupTic_Ticket( $result );

	return false;
}

function suptic_delete_ticket( $id ) {
	global $wpdb;

	if ( ! $id = absint( $id ) )
		return false;

	$table = suptic_db_table( 'tickets' );
	$query = $wpdb->prepare( "DELETE FROM $table WHERE id = %d LIMIT 1", $id );

	$result = (bool) $wpdb->query( $query );

	if ( $result ) {
		$messages_table = suptic_db_table( 'messages' );

		$msg_ids = $wpdb->get_col(
			$wpdb->prepare( "SELECT id FROM $messages_table WHERE ticket_id = %d", $id ) );

		foreach ( (array) $msg_ids as $msg_id ) {
			suptic_delete_message( $msg_id );
		}

		do_action( 'suptic_after_delete_ticket', $id );
	}

	return $result;
}

/* Message */

class SupTic_Message {

	var $initial = false;

	var $id;
	var $ticket_id;
	var $user_id;
	var $message_body;
	var $word_count;
	var $message_type = 'message';
	var $parent_id;
	var $status;
	var $create_time;

	var $ticket;

	function SupTic_Message( $obj = null ) {
		if ( is_object( $obj ) ) {
			$vars = get_object_vars( $obj );
			foreach ( $vars as $var => $value ) {
				if ( isset( $obj->$var ) )
					$this->$var = $obj->$var;
			}
		}
	}

	function is_admin_reply() {
		$ticket = $this->ticket();

		if ( $this->user_id && $this->user_id != $ticket->user_id )
			return true;

		return false;
	}

	function ticket() {
		if ( ! is_a( $this->ticket, 'SupTic_Ticket' ) )
			$this->ticket = suptic_get_ticket( $this->ticket_id );

		return $this->ticket;
	}

	function render() {
		$class = 'ticket-message';

		if ( $this->is_admin_reply() )
			$class .= ' from-admin';

		if ( $is_draft = $this->has_status( 'draft' ) )
			$class .= ' draft';

		$html = '<div id="suptic-message-' . $this->id . '" class="' . $class . '">';

		$html .= '<div class="message-body">';
		$html .= apply_filters( 'suptic_message_body', $this->message_body, $this->id );

		$html .= '<div class="description">';
		$html .= '<span class="author">' . esc_html( $this->author_name() ) . '</span>';

		$date = suptic_human_time( $this->create_time );

		$html .= ' <span class="date">' . esc_html( $date ) . '</span>';

		if ( $is_draft )
			$html .= ' <span class="draft-notice"> - ' . esc_html( __( 'Draft', 'suptic' ) ) . '</span>';

		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="author-info">';
		$html .= $this->avatar( 36 );
		$html .= '</div>';

		$html .= '</div>' . "\n\n";
		return $html;
	}

	function author_name() {
		if ( $this->is_admin_reply() && $user = get_userdata( $this->user_id ) )
			return $user->display_name;
		elseif ( $ticket = $this->ticket() )
			return $ticket->author_name();
	}

	function excerpt() {
		$body = $this->message_body;
		$body = strip_tags( $body );
		$blah = explode( ' ', $body );

		if ( count( $blah ) > 20 ) {
			$k = 20;
			$use_dotdotdot = 1;
		} else {
			$k = count( $blah );
			$use_dotdotdot = 0;
		}

		$excerpt = '';
		for ( $i = 0; $i < $k; $i++ ) {
			$excerpt .= $blah[$i] . ' ';
		}
		$excerpt .= ( $use_dotdotdot ) ? '...' : '';

		return $excerpt;
	}

	function avatar( $size = 96 ) {
		if ( $this->user_id )
			return get_avatar( $this->user_id, $size );

		if ( $ticket = $this->ticket() )
			return $ticket->avatar( $size );

		return null;
	}

	function add_meta( $meta_key, $value ) {
		global $wpdb;

		$table = suptic_db_table( 'meta' );
		$result = $wpdb->insert( $table, array(
			'object_type' => 'message',
			'object_id' => $this->id,
			'meta_key' => $meta_key,
			'meta_value' => maybe_serialize( $value ) ),
			array( '%s', '%d', '%s', '%s' ) );

		if ( $result )
			return $wpdb->insert_id;

		return false;
	}

	function update_meta( $meta_key, $new_value ) {
		global $wpdb;

		$old_value = $this->get_meta( $meta_key );

		if ( $new_value === $old_value )
			return false;

		if ( false === $old_value )
			return $this->add_meta( $meta_key, $new_value );

		$table = suptic_db_table( 'meta' );
		$wpdb->update( $table,
			array( 'meta_value' => maybe_serialize( $new_value ) ),
			array( 'object_type' => 'message', 'object_id' => $this->id, 'meta_key' => $meta_key ),
			'%s', array( '%s', '%d', '%s' ) );

		return true;
	}

	function get_meta( $meta_key, $single = true ) {
		global $wpdb;

		$table = suptic_db_table( 'meta' );
		$query = "SELECT meta_value FROM $table WHERE object_type LIKE 'message'"
			. $wpdb->prepare( " AND object_id = %d", $this->id )
			. $wpdb->prepare( " AND meta_key LIKE %s", $meta_key );
		$meta_values = $wpdb->get_col( $query );

		if ( empty( $meta_values ) )
			return false;

		if ( $single )
			return maybe_unserialize( $meta_values[0] );

		return array_map( 'maybe_unserialize', $meta_values );
	}

	function set_status( $status ) {
		global $wpdb;

		$table = suptic_db_table( 'messages' );
		$result = $wpdb->update( $table,
			array( 'status' => $status ), array( 'id' => $this->id ),
			'%s', '%d' );

		return (bool) $result;
	}

	function get_status() {
		return $this->status;
	}

	function has_status( $status ) {
		$current = $this->get_status();
		return $status == $current;
	}

	function revise( $body ) {
		global $wpdb;

		$body = trim( wp_filter_kses( $body ) );

		$table = suptic_db_table( 'messages' );
		$result = $wpdb->update( $table,
			array( 'message_body' => $body ), array( 'id' => $this->id ),
			'%s', '%d' );

		return (bool) $result;
	}
}

function suptic_get_message( $id ) {
	global $wpdb;

	if ( ! $id = absint( $id ) )
		return false;

	$table = suptic_db_table( 'messages' );
	$query = $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );

	if ( $result = $wpdb->get_row( $query ) )
		return new SupTic_Message( $result );

	return false;
}

function suptic_delete_message( $id ) {
	global $wpdb;

	if ( ! $id = absint( $id ) )
		return false;

	$table = suptic_db_table( 'messages' );
	$query = $wpdb->prepare( "DELETE FROM $table WHERE id = %d LIMIT 1", $id );

	$result = (bool) $wpdb->query( $query );

	if ( $result )
		do_action( 'suptic_after_delete_message', $id );

	return $result;
}

add_filter( 'suptic_pre_message_body', 'trim' );
add_filter( 'suptic_pre_message_body', 'wp_filter_kses' );

add_filter( 'suptic_message_body', 'wptexturize' );
add_filter( 'suptic_message_body', 'make_clickable' );
add_filter( 'suptic_message_body', 'links_add_target' );
add_filter( 'suptic_message_body', 'convert_smilies' );
add_filter( 'suptic_message_body', 'convert_chars' );
add_filter( 'suptic_message_body', 'wpautop' );

?>