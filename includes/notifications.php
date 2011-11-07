<?php

add_action( 'suptic_control_create_ticket', 'suptic_ticket_created_notification' );

function suptic_ticket_created_notification( &$ticket ) {
	if ( ! $ticket )
		return;

	$subject = sprintf( __( '[%s] Support Tickets - A new ticket (TKT:%d)', 'suptic' ),
		get_option( 'blogname' ), $ticket->id );

	$body = __( "A new ticket has opened.", 'suptic' ) . "\n\n";
	$body .= sprintf( __( 'Subject: %s', 'suptic' ), $ticket->subject ) . "\n";
	$body .= sprintf( __( 'Author: %s', 'suptic' ), $ticket->author_name() ) . "\n\n";

	$body .= sprintf( __( "See the messages on the web at %s", 'suptic' ),
		$ticket->url( true ) ) . "\n\n";

	$footer = "--\n";
	$footer .= __( "Please do not reply to this message; it was sent from an unmonitored email address.", 'suptic' ) . "\n";
	$footer .= sprintf( '_ticket_tracking_code: %s', $ticket->access_key ) . "\n";

	if ( is_email( $ticket->author_email ) ) {
		$greeting = sprintf( __( "Hi, %s.", 'suptic' ), $ticket->author_name() ) . "\n\n";
		@wp_mail( $ticket->author_email, $subject, $greeting . $body . $footer );
	}

	if ( ( $admin_email = get_option('admin_email') ) && $admin_email != $ticket->author_email ) {
		$message_body = '';

		if ( $initial_message = $ticket->first_message() ) {
			$message_body = __( 'Message Body:', 'suptic' ) . "\n";
			$message_body .= $initial_message->message_body;
			$message_body .= "\n\n\n";
		}

		@wp_mail( $admin_email, $subject, $body . $message_body . $footer );
	}
}

add_action( 'suptic_control_add_message', 'suptic_message_replied_notification', 10, 2 );

function suptic_message_replied_notification( &$ticket, &$message ) {
	if ( ! $ticket || ! $message )
		return;

	if ( ! $message->has_status( 'publish' ) )
		return;

	if ( $message->is_admin_reply() ) {

		$subject = sprintf( __( '[%s] Support Tickets - You have a reply (TKT:%d)', 'suptic' ),
			get_option( 'blogname' ), $ticket->id );

		$body = sprintf( __( "Hi, %s.", 'suptic' ), $ticket->author_name() ) . "\n\n";
		$body .= sprintf( __( "You have a new reply message from %s", 'suptic' ),
			$message->author_name() ) . "\n\n";

		$body .= sprintf( __( "See the message and reply on the web at %s", 'suptic' ),
			$ticket->url( true ) ) . "\n\n";

		$body .= "--\n";
		$body .= __( "Please do not reply to this message; it was sent from an unmonitored email address.", 'suptic' ) . "\n";
		$body .= sprintf( '_ticket_tracking_code: %s', $ticket->access_key ) . "\n";

		if ( is_email( $ticket->author_email ) )
			@wp_mail( $ticket->author_email, $subject, $body );

	} else {

		$subject = sprintf( __( '[%s] Support Tickets - You have a reply (TKT:%d)', 'suptic' ),
			get_option( 'blogname' ), $ticket->id );

		$body = sprintf( __( "You have a new reply message from %s", 'suptic' ),
			$message->author_name() ) . "\n\n";

		$body .= sprintf( __( "See the message and reply on the web at %s", 'suptic' ),
			$ticket->url( true ) ) . "\n\n";

		$body .= __( 'Message Body:', 'suptic' ) . "\n";
		$body .= $message->message_body;
		$body .= "\n\n\n";

		$body .= "--\n";
		$body .= __( "Please do not reply to this message; it was sent from an unmonitored email address.", 'suptic' ) . "\n";
		$body .= sprintf( '_ticket_tracking_code: %s', $ticket->access_key ) . "\n";

		if ( $admin_email = get_option('admin_email') )
			@wp_mail( $admin_email, $subject, $body );

	}
}

?>