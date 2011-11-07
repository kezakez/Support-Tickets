<?php
/* PayPal module for paid support */

add_filter( 'suptic_message_body', 'suptic_paypal_in_message', 11, 2 );

function suptic_paypal_in_message( $message_body, $message_id ) {
	global $suptic_message;

	$suptic_message = suptic_get_message( $message_id );	

	$pattern = '/\[paypal(?:\s(.*))?\]/';

	return preg_replace_callback( $pattern, 'suptic_paypal_tag_cb', $message_body );
}

function suptic_paypal_get_options( $text ) {
	$texts = explode( ' ', $text );

	$currency = 'USD';
	$amount = null;

	foreach ( $texts as $text ) {
		if ( preg_match( '/^[A-Z]{3}$/i', $text ) ) {
			$currency = $text;
		} elseif ( preg_match( '/^[0-9.]+$/', $text ) ) {
			$amount = $text;
		}
	}

	return compact( 'currency', 'amount' );
}

function suptic_paypal_tag_cb( $matches ) {
	global $suptic_message;

	$options = suptic_paypal_get_options( $matches[1] );
	extract( $options );

	$ticket = $suptic_message->ticket();

	$action = 'https://www.paypal.com/cgi-bin/webscr';
	// $action = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

	$paypal_address = get_option('admin_email');

	$html = '<form action="' . $action . '" method="POST">' . "\n";
	$html .= '<div class="suptic-paypal">' . "\n";
	$html .= '<input type="hidden" name="cmd" value="_xclick" />' . "\n";
	$html .= '<input type="hidden" name="upload" value="1" />' . "\n";

	$html .= '<input type="hidden" name="business" value="'
		. esc_attr( $paypal_address ) . '" />' . "\n";

	$item_name = sprintf( __( '[%s] Support Tickets - Ticket #%d', 'suptic' ),
		get_option( 'blogname' ), $ticket->id );
	$html .= '<input type="hidden" name="item_name" value="'
		. esc_attr( $item_name ) . '" />' . "\n";

	$html .= '<input type="hidden" name="cbt" value="'
		. esc_attr( __( 'Return to Support Ticket Page', 'suptic' ) ) . '" />' . "\n";

	$html .= '<input type="hidden" name="amount" value="' . $amount . '" />' . "\n";
	$html .= '<input type="hidden" name="currency_code" value="' . $currency . '" />' . "\n";
	$html .= '<input type="hidden" name="rm" value="2" />' . "\n";
	$html .= '<input type="hidden" name="no_shipping" value="1" />' . "\n";
	$html .= '<input type="hidden" name="shipping" value="0" />' . "\n";
	$html .= '<input type="hidden" name="shipping2" value="0" />' . "\n";
	$html .= '<input type="hidden" name="tax" value="0" />' . "\n";

	$html .= '<input type="hidden" name="return" value="'
		. esc_attr( add_query_arg( array(), $ticket->url( true ) ) ) . '" />' . "\n";

	$html .= '<input type="hidden" name="cancel_return" value="'
		. esc_attr( add_query_arg( array(), $ticket->url( true ) ) ) . '" />' . "\n";
	$html .= '<input type="hidden" name="charset" value="utf-8" />' . "\n";

	$html .= '<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_paynow_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online."><img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">' . "\n";

	$html .= '</div>' . "\n";
	$html .= '</form>' . "\n";

	return $html;
}

?>