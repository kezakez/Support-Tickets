<?php
/**
** A base module for [checkbox], [checkbox*], and [radio]
**/

/* Shortcode handler */

suptic_add_shortcode( 'checkbox', 'suptic_checkbox_shortcode_handler', true );
suptic_add_shortcode( 'checkbox*', 'suptic_checkbox_shortcode_handler', true );
suptic_add_shortcode( 'radio', 'suptic_checkbox_shortcode_handler', true );

function suptic_checkbox_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];
	$labels = (array) $tag['labels'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';

	$defaults = array();

	$label_first = false;
	$use_label_element = false;

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '/^default:([0-9_]+)$/', $option, $matches ) ) {
			$defaults = explode( '_', $matches[1] );

		} elseif ( preg_match( '%^label[_-]?first$%', $option ) ) {
			$label_first = true;

		} elseif ( preg_match( '%^use[_-]?label[_-]?element$%', $option ) ) {
			$use_label_element = true;

		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$multiple = preg_match( '/^checkbox[*]?$/', $type ) && ! preg_grep( '%^exclusive$%', $options );

	$html = '';

	if ( preg_match( '/^checkbox[*]?$/', $type ) && ! $multiple )
		$onclick = ' onclick="supticExclusiveCheckbox(this);"';

	$input_type = rtrim( $type, '*' );

	foreach ( $values as $key => $value ) {
		$checked = false;

		if ( in_array( $key + 1, (array) $defaults ) )
			$checked = true;

		if ( $multiple && in_array( esc_sql( $value ), (array) $_POST[$name] ) )
			$checked = true;
		if ( ! $multiple && $_POST[$name] == esc_sql( $value ) )
			$checked = true;

		$checked = $checked ? ' checked="checked"' : '';

		if ( isset( $labels[$key] ) )
			$label = $labels[$key];
		else
			$label = $value;

		if ( $label_first ) { // put label first, input last
			$item = '<span class="suptic-list-item-label">' . esc_html( $label ) . '</span>&nbsp;';
			$item .= '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . esc_attr( $value ) . '"' . $checked . $onclick . ' />';
		} else {
			$item = '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . esc_attr( $value ) . '"' . $checked . $onclick . ' />';
			$item .= '&nbsp;<span class="suptic-list-item-label">' . esc_html( $label ) . '</span>';
		}

		if ( $use_label_element )
			$item = '<label>' . $item . '</label>';

		$item = '<span class="suptic-list-item">' . $item . '</span>';
		$html .= $item;
	}

	$html = '<span' . $atts . '>' . $html . '</span>';

	if ( $validation_error = $_POST['_suptic_validation_errors']['messages'][$name] ) {
		$validation_error = '<span class="suptic-not-valid-tip-no-ajax">'
			. esc_html( $validation_error ) . '</span>';
	} else {
		$validation_error = '';
	}

	$html = '<span class="suptic-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Validation filter */

add_filter( 'suptic_validate_checkbox', 'suptic_checkbox_validation_filter', 10, 2 );
add_filter( 'suptic_validate_checkbox*', 'suptic_checkbox_validation_filter', 10, 2 );
add_filter( 'suptic_validate_radio', 'suptic_checkbox_validation_filter', 10, 2 );

function suptic_checkbox_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];
	$values = $tag['values'];

	if ( is_array( $_POST[$name] ) ) {
		foreach ( $_POST[$name] as $key => $value ) {
			$value = stripslashes( $value );
			if ( ! in_array( $value, (array) $values ) ) // Not in given choices.
				unset( $_POST[$name][$key] );
		}
	} else {
		$value = stripslashes( $_POST[$name] );
		if ( ! in_array( $value, (array) $values ) ) //  Not in given choices.
			$_POST[$name] = '';
	}

	if ( 'checkbox*' == $type ) {
		if ( empty( $_POST[$name] ) ) {
			$result['valid'] = false;
			$result['reason'][$name] = __( "Please fill the required field.", 'suptic' );
		}
	}

	return $result;
}

?>