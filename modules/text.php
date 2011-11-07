<?php
/**
** A base module for [text], [text*], [email], and [email*]
**/

/* Shortcode handler */

suptic_add_shortcode( 'text', 'suptic_text_shortcode_handler', true );
suptic_add_shortcode( 'text*', 'suptic_text_shortcode_handler', true );
suptic_add_shortcode( 'email', 'suptic_text_shortcode_handler', true );
suptic_add_shortcode( 'email*', 'suptic_text_shortcode_handler', true );

function suptic_text_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';
	$size_att = '';
	$maxlength_att = '';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $option, $matches ) ) {
			$size_att = (int) $matches[1];
			$maxlength_att = (int) $matches[2];
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	if ( $size_att )
		$atts .= ' size="' . $size_att . '"';
	else
		$atts .= ' size="40"'; // default size

	if ( $maxlength_att )
		$atts .= ' maxlength="' . $maxlength_att . '"';

	// Value
	if ( isset( $_POST[$name] ) ) {
		$value = $_POST[$name];
	} else {
		$value = trim( $values[0] );
		if ( ! $value && is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( 'email' == $name ) {
				$value = $user->user_email;
			} elseif ( 'first-name' == $name ) {
				$value = get_usermeta( $user->ID, 'first_name' );
			} elseif ( 'last-name' == $name ) {
				$value = get_usermeta( $user->ID, 'last_name' );
			}
		}
	}

	$html = '<input type="text" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $atts . ' />';

	if ( $validation_error = $_POST['_suptic_validation_errors']['messages'][$name] ) {
		$validation_error = '<span class="suptic-not-valid-tip-no-ajax">'
			. esc_html( $validation_error ) . '</span>';
	} else {
		$validation_error = '';
	}

	$html = '<span class="suptic-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}

add_filter( 'suptic_validate_text', 'suptic_text_validation_filter', 10, 2 );
add_filter( 'suptic_validate_text*', 'suptic_text_validation_filter', 10, 2 );
add_filter( 'suptic_validate_email', 'suptic_text_validation_filter', 10, 2 );
add_filter( 'suptic_validate_email*', 'suptic_text_validation_filter', 10, 2 );

function suptic_text_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];

	$_POST[$name] = trim( strtr( (string) $_POST[$name], "\n", " " ) );

	if ( 'text*' == $type ) {
		if ( '' == $_POST[$name] ) {
			$result['valid'] = false;
			$result['reason'][$name] = __( "Please fill the required field.", 'suptic' );
		}
	}

	if ( 'email' == $type || 'email*' == $type ) {
		if ( 'email*' == $type && '' == $_POST[$name] ) {
			$result['valid'] = false;
			$result['reason'][$name] = __( "Please fill the required field.", 'suptic' );
		} elseif ( '' != $_POST[$name] && ! is_email( $_POST[$name] ) ) {
			$result['valid'] = false;
			$result['reason'][$name] = __( "Email address seems invalid.", 'suptic' );
		}
	}

	return $result;
}

?>