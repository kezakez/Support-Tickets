<?php
/**
** A base module for [textarea] and [textarea*]
**/

/* Shortcode handler */

suptic_add_shortcode( 'textarea', 'suptic_textarea_shortcode_handler', true );
suptic_add_shortcode( 'textarea*', 'suptic_textarea_shortcode_handler', true );

function suptic_textarea_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];
	$content = $tag['content'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';
	$cols_att = '';
	$rows_att = '';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^([0-9]*)[x/]([0-9]*)$%', $option, $matches ) ) {
			$cols_att = (int) $matches[1];
			$rows_att = (int) $matches[2];
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	if ( $cols_att )
		$atts .= ' cols="' . $cols_att . '"';
	else
		$atts .= ' cols="40"'; // default size

	if ( $rows_att )
		$atts .= ' rows="' . $rows_att . '"';
	else
		$atts .= ' rows="10"'; // default size

	// Value
	if ( isset( $_POST[$name] ) ) {
		$value = $_POST[$name];
	} else {
		$value = trim( $values[0] );
	}

	$html = '<textarea name="' . $name . '"' . $atts . '>' . esc_html( $value ) . '</textarea>';

	if ( $validation_error = $_POST['_suptic_validation_errors']['messages'][$name] ) {
		$validation_error = '<span class="suptic-not-valid-tip-no-ajax">'
			. esc_html( $validation_error ) . '</span>';
	} else {
		$validation_error = '';
	}

	$html = '<span class="suptic-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}

add_filter( 'suptic_validate_textarea', 'suptic_textarea_validation_filter', 10, 2 );
add_filter( 'suptic_validate_textarea*', 'suptic_textarea_validation_filter', 10, 2 );

function suptic_textarea_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];

	$_POST[$name] = (string) $_POST[$name];

	if ( 'textarea*' == $type ) {
		if ( '' == $_POST[$name] ) {
			$result['valid'] = false;
			$result['reason'][$name] = __( "Please fill the required field.", 'suptic' );
		}
	}

	return $result;
}

?>