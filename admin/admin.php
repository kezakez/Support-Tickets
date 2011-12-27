<?php

add_action( 'admin_head', 'suptic_admin_head' );

function suptic_admin_head() {
	global $plugin_page, $pagenow;

	if ( 'index.php' == $pagenow ) {
		$url = suptic_plugin_url( 'admin/dashboard-styles.css' );
		echo '<link rel="stylesheet" href="' . $url . '" type="text/css" />';
		return;
	}

	if ( ! isset( $plugin_page ) || 0 !== strpos( $plugin_page, SUPTIC_PLUGIN_NAME ) )
		return;

	$url = suptic_plugin_url( 'admin/styles.css' );
	echo '<link rel="stylesheet" href="' . $url . '" type="text/css" />';

?>
<script type="text/javascript">
//<![CDATA[
var _suptic = {
	captchaMod: <?php echo ( class_exists( 'ReallySimpleCaptcha' ) ) ? 'true' : 'false' ?>,
	pluginUrl: '<?php echo suptic_plugin_url(); ?>'
};
//]]>
</script>
<?php
}

add_action( 'wp_print_scripts', 'suptic_admin_load_js' );

function suptic_admin_load_js() {
	global $plugin_page;

	if ( ! isset( $plugin_page ) || 0 !== strpos( $plugin_page, SUPTIC_PLUGIN_NAME ) )
		return;

	wp_enqueue_script( 'suptic-admin', suptic_plugin_url( 'admin/scripts.js' ),
		array('jquery'), SUPTIC_VERSION, true );

	wp_localize_script( 'suptic-admin', '_supticL10n', array(
		'optional' => __( 'optional', 'suptic' ),
		'generateTag' => __( 'Generate Tag', 'suptic' ),
		'textField' => __( 'Text field', 'suptic' ),
		'emailField' => __( 'Email field', 'suptic' ),
		'textArea' => __( 'Text area', 'suptic' ),
		'menu' => __( 'Drop-down menu', 'suptic' ),
		'checkboxes' => __( 'Checkboxes', 'suptic' ),
		'radioButtons' => __( 'Radio buttons', 'suptic' ),
		'acceptance' => __( 'Acceptance', 'suptic' ),
		'isAcceptanceDefaultOn' => __( "Make this checkbox checked by default?", 'suptic' ),
		'isAcceptanceInvert' => __( "Make this checkbox work inversely?", 'suptic' ),
		'isAcceptanceInvertMeans' => __( "* That means visitor who accepts the term unchecks it.", 'suptic' ),
		'captcha' => __( 'CAPTCHA', 'suptic' ),
		'quiz' => __( 'Quiz', 'suptic' ),
		'quizzes' => __( 'Quizzes', 'suptic' ),
		'quizFormatDesc' => __( "* quiz|answer (e.g. 1+1=?|2)", 'suptic' ),
		'submit' => __( 'Submit button', 'suptic' ),
		'tagName' => __( 'Name', 'suptic' ),
		'isRequiredField' => __( 'Required field?', 'suptic' ),
		'allowsMultipleSelections' => __( 'Allow multiple selections?', 'suptic' ),
		'insertFirstBlankOption' => __( 'Insert a blank item as the first option?', 'suptic' ),
		'makeCheckboxesExclusive' => __( 'Make checkboxes exclusive?', 'suptic' ),
		'menuChoices' => __( 'Choices', 'suptic' ),
		'label' => __( 'Label', 'suptic' ),
		'defaultValue' => __( 'Default value', 'suptic' ),
		'akismet' => __( 'Akismet', 'suptic' ),
		'akismetAuthor' => __( "This field requires author's name", 'suptic' ),
		'akismetAuthorUrl' => __( "This field requires author's URL", 'suptic' ),
		'akismetAuthorEmail' => __( "This field requires author's email address", 'suptic' ),
		'generatedTag' => __( "Copy this code and paste it into the form left.", 'suptic' ),
		'fgColor' => __( 'Foreground color', 'suptic' ),
		'bgColor' => __( 'Background color', 'suptic' ),
		'imageSize' => __( 'Image size', 'suptic' ),
		'imageSizeSmall' => __( 'Small', 'suptic' ),
		'imageSizeMedium' => __( 'Medium', 'suptic' ),
		'imageSizeLarge' => __( 'Large', 'suptic' ),
		'imageSettings' => __( 'Image settings', 'suptic' ),
		'inputFieldSettings' => __( 'Input field settings', 'suptic' ),
		'tagForImage' => __( 'For image', 'suptic' ),
		'tagForInputField' => __( 'For input field', 'suptic' ),
		'oneChoicePerLine' => __( "* One choice per line.", 'suptic' ),
		'needReallySimpleCaptcha' => __( "Note: To use CAPTCHA, you need Really Simple CAPTCHA plugin installed.", 'suptic' )
	) );
}

add_action( 'admin_menu', 'suptic_admin_init' );

function suptic_admin_init() {
	if ( isset( $_POST['suptic-create-form'] ) ) {
		check_admin_referer( 'suptic-create-form' );

		if ( ! suptic_you_can_manage_forms() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		$form = suptic_insert_form( array(
			'form_name' => $_POST['form-name'],
			'form_design' => $_POST['form-design'],
			'form_page' => $_POST['form-page'] ) );

		wp_redirect( suptic_admin_url( 'edit-forms.php',
			array( 'form_id' => $form->id, 'message' => 'form_created' ) ) );
		exit();

	} elseif ( isset( $_POST['suptic-edit-form'] ) ) {
		$form_id = (int) $_POST['form-id'];
		check_admin_referer( 'suptic-edit-form-' . $form_id );

		if ( ! suptic_you_can_manage_forms() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		suptic_update_form( $form_id, array(
			'form_name' => $_POST['form-name'],
			'form_design' => $_POST['form-design'],
			'form_page' => $_POST['form-page'] ) );

		wp_redirect( suptic_admin_url( 'edit-forms.php',
			array( 'form_id' => $form_id, 'message' => 'form_updated' ) ) );
		exit();

	} elseif ( isset( $_POST['suptic-delete-form'] ) ) {
		$form_id = (int) $_POST['form-id'];
		check_admin_referer( 'suptic-delete-form-' . $form_id );

		if ( ! suptic_you_can_manage_forms() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		suptic_delete_form( $form_id );

		wp_redirect( suptic_admin_url( 'edit-forms.php', array( 'message' => 'form_deleted' ) ) );
		exit();

	} elseif ( isset( $_POST['suptic-mark-ticket'] ) ) {
		$ticket_id = (int) $_POST['ticket-id'];
		check_admin_referer( 'suptic-edit-ticket-' . $ticket_id );

		if ( ! suptic_you_can_access_all_tickets() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		if ( $ticket = suptic_get_ticket( $ticket_id ) )
			$ticket->set_status( 'read' );

		wp_redirect( suptic_admin_url( 'edit-tickets.php',
			array( 'ticket_id' => $ticket_id, 'message' => 'ticket_read' ) ) );
		exit();
	} elseif ( isset( $_POST['suptic-close-ticket'] ) ) {
		$ticket_id = (int) $_POST['ticket-id'];
		check_admin_referer( 'suptic-edit-ticket-' . $ticket_id );

		if ( ! suptic_you_can_access_all_tickets() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		if ( $ticket = suptic_get_ticket( $ticket_id ) )
			$ticket->set_status( 'closed' );

		wp_redirect( suptic_admin_url( 'edit-tickets.php',
			array( 'ticket_id' => $ticket_id, 'message' => 'ticket_closed' ) ) );
		exit();

	} elseif ( isset( $_POST['suptic-reopen-ticket'] ) ) {
		$ticket_id = (int) $_POST['ticket-id'];
		check_admin_referer( 'suptic-edit-ticket-' . $ticket_id );

		if ( ! suptic_you_can_access_all_tickets() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		if ( $ticket = suptic_get_ticket( $ticket_id ) )
			$ticket->set_status( 'new' );

		wp_redirect( suptic_admin_url( 'edit-tickets.php',
			array( 'ticket_id' => $ticket_id, 'message' => 'ticket_reopened' ) ) );
		exit();

	} elseif ( isset( $_POST['suptic-delete-ticket'] ) ) {
		$ticket_id = (int) $_POST['ticket-id'];
		check_admin_referer( 'suptic-delete-ticket-' . $ticket_id );

		if ( ! suptic_you_can_access_all_tickets() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		if ( suptic_delete_ticket( $ticket_id ) ) {
			wp_redirect( suptic_admin_url( 'edit-tickets.php',
				array( 'message' => 'ticket_deleted' ) ) );
		} else {
			wp_redirect( suptic_admin_url( 'edit-tickets.php',
				array( 'ticket_id' => $ticket_id ) ) );
		}

		exit();

	} elseif ( isset( $_GET['suptic-bulk-edit-tickets'] )
			|| isset( $_GET['suptic-bulk-edit-tickets2'] ) ) {
		check_admin_referer( 'suptic-bulk-edit-tickets' );

		if ( ! suptic_you_can_access_all_tickets() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		$action = isset( $_GET['suptic-bulk-edit-tickets'] )
			? $_GET['action'] : $_GET['action2'];
		$tickets = (array) stripslashes_deep( $_GET['ticket'] );

		$message = '';

		foreach ( $tickets as $ticket_id ) {
			$ticket_id = (int) $ticket_id;

			if ( 'close' == $action ) {
				if ( ( $ticket = suptic_get_ticket( $ticket_id ) )
					&& $ticket->set_status( 'closed' ) )
					$message = 'tickets_closed';
			} elseif ( 'delete' == $action ) {
				if ( suptic_delete_ticket( $ticket_id ) )
					$message = 'tickets_deleted';
			}
		}

		wp_redirect( suptic_admin_url( 'edit-tickets.php', array( 'message' => $message ) ) );

		exit();

	} elseif ( 1 == $_GET['suptic-toggle-message-status'] ) {
		check_admin_referer( 'suptic-toggle-message-status' );

		if ( ! suptic_you_can_access_all_tickets() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		if ( $message = suptic_get_message( $_GET['message_id'] ) ) {
			if ( $message->has_status( 'publish' ) ) {
				$message->set_status( 'draft' );
				$message = 'message_status_changed';
			} elseif ( $message->has_status( 'draft' ) ) {
				$message->set_status( 'publish' );
				$message = 'message_status_changed';
			}
		}

		wp_redirect( suptic_admin_url( 'edit-tickets.php',
			array( 'ticket_id' => absint( $_GET['ticket_id'] ), 'message' => $message ) ) );

		exit();

	} elseif ( isset( $_POST['suptic-edit-message'] ) ) {
		$message_id = (int) $_POST['message-id'];
		check_admin_referer( 'suptic-edit-message-' . $message_id );

		if ( ! suptic_you_can_access_all_tickets() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		if ( $message = suptic_get_message( $message_id ) )
			$message->revise( $_POST['message-body'] );

		wp_redirect( suptic_admin_url( 'edit-tickets.php',
			array( 'message_id' => $message_id, 'message' => 'message_updated' ) ) );
		exit();

	} elseif ( isset( $_POST['suptic-delete-message'] ) ) {
		$message_id = (int) $_POST['message-id'];
		check_admin_referer( 'suptic-delete-message-' . $message_id );

		if ( ! suptic_you_can_access_all_tickets() )
			wp_die( __( 'Cheatin&#8217; uh?', 'suptic' ) );

		$message = suptic_get_message( $message_id );
		$ticket_id = $message->ticket_id;

		if ( suptic_delete_message( $message_id ) ) {
			wp_redirect( suptic_admin_url( 'edit-tickets.php',
				array( 'ticket_id' => $ticket_id, 'message' => 'message_deleted' ) ) );
		} else {
			wp_redirect( suptic_admin_url( 'edit-tickets.php',
				array( 'message_id' => $message_id ) ) );
		}

		exit();

	}

	do_action( 'suptic_admin_init' );

	suptic_add_pages();
}

function suptic_add_pages() {
	$forms = suptic_plugin_path( 'admin/edit-forms.php' );
	$tickets = suptic_plugin_path( 'admin/edit-tickets.php' );

	$parent = $forms;

	add_menu_page( __( 'Support Tickets', 'suptic' ), __( 'Support', 'suptic' ),
		suptic_manage_forms_capability(), $parent );

	add_submenu_page( $parent, __( 'Edit Forms', 'suptic' ), __( 'Forms', 'suptic' ),
		suptic_manage_forms_capability(), $forms );

	add_submenu_page( $parent, __( 'Edit Tickets', 'suptic' ), __( 'Tickets', 'suptic' ),
		suptic_access_all_tickets_capability(), $tickets );
}

function suptic_admin_update_message() {
	$format = '<div id="message" class="updated fade"><p>%s</p></div>';

	switch ( $_GET['message'] ) {
		case 'form_created':
			return sprintf( $format, __( "Form created.", 'suptic' ) );
		case 'form_updated':
			return sprintf( $format, __( "Form updated.", 'suptic' ) );
		case 'form_deleted':
			return sprintf( $format, __( "Form deleted.", 'suptic' ) );
		case 'ticket_closed':
			return sprintf( $format, __( "Ticket closed.", 'suptic' ) );
		case 'ticket_reopened':
			return sprintf( $format, __( "Ticket reopened.", 'suptic' ) );
		case 'ticket_deleted':
			return sprintf( $format, __( "Ticket deleted.", 'suptic' ) );
		case 'ticket_updated':
			return sprintf( $format, __( "Ticket updated.", 'suptic' ) );
		case 'tickets_closed':
			return sprintf( $format, __( "Tickets closed.", 'suptic' ) );
		case 'tickets_deleted':
			return sprintf( $format, __( "Tickets deleted.", 'suptic' ) );
		case 'message_status_changed':
			return sprintf( $format, __( "Message status changed.", 'suptic' ) );
		case 'message_updated':
			return sprintf( $format, __( "Message updated.", 'suptic' ) );
		case 'message_deleted':
			return sprintf( $format, __( "Message deleted.", 'suptic' ) );
	}

	return '';
}

add_action( 'wp_dashboard_setup', 'suptic_dashboard_setup' );

function suptic_dashboard_setup() {
	if ( suptic_you_can_access_all_tickets() )
		wp_add_dashboard_widget('suptic',
			__( 'Recent Updated Support Tickets', 'suptic' ), 'suptic_dashboard_recent_tickets' );
}

function suptic_dashboard_recent_tickets() {
	$tickets = suptic_get_tickets( array(
		'perpage' => 5,
		'orderby' => 'update_time',
		'status' => array( 'new', 'waiting_reply' ) ) );

	if ( ! $tickets ) {
		echo '<p>' . esc_html( __( "No pending tickets now.", 'suptic' ) ) . '</p>';
		return;
	}

	$html = '<div id="suptic-the-support-ticket-list">' . "\n";

	foreach ( $tickets as $ticket ) {
		$html .= '<div id="suptic-ticket-' . $ticket->id . '" class="ticket">' . "\n";

		$last_message = $ticket->last_message();
		$author_name = ( $last_message ) ? $last_message->author_name() : $ticket->author_name();

		$html .= '<h4>'
			. sprintf( __( 'From %1$s on %2$s', 'suptic' ),
				'<cite class="message-author">' . $author_name . '</cite>',
				'<a href="' . suptic_admin_url( 'edit-tickets.php',
					array( 'ticket_id' => $ticket->id ) ) . '">'
				. esc_html( $ticket->subject ) . '</a> <a href="' . $ticket->url() . '">#</a>' )
			. ' <abbr>' . suptic_human_time( $ticket->update_time ) . '</abbr></h4>';

		if ( $last_message )
			$html .= '<blockquote><p>' . $last_message->excerpt() . '</p></blockquote>';
		else
			$html .= '<p>' . esc_html( __( "No messages yet.", 'suptic' ) ) . '</p>';

		$html .= '</div>' . "\n";
	}

	$html .= '</div>' . "\n\n";

	$html .= '<p class="textright"><a href="'
		. suptic_admin_url( 'edit-tickets.php' ) . '" class="button">'
		. __( 'View all', 'suptic' ) . '</a></p>';

	echo $html;
}

function suptic_generate_subsubsub( $subsubsub ) {
	$html = '';

	$subsubsub = array_reverse( $subsubsub );

	$br = true;

	foreach ( $subsubsub as $subsub ) {
		if ( 'br' == $subsub ) {
			$html = '<li><br class="clear" /></li>' . $html;
			$br = true;
		} else {
			if ( $br )
				$html = '<li>' . $subsub . '</li> ' . $html;
			else
				$html = '<li>' . $subsub . ' |</li> ' . $html;
			$br = false;
		}
	}

	$html = '<ul class="subsubsub">' . $html . '</ul>';

	return $html;	
}

?>