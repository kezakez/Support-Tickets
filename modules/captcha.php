<?php
/**
** A base module for [captchac] and [captchar]
**/

/* Shortcode handler */

suptic_add_shortcode( 'captchac', 'suptic_captcha_shortcode_handler', true );
suptic_add_shortcode( 'captchar', 'suptic_captcha_shortcode_handler', true );

function suptic_captcha_shortcode_handler( $tag ) {
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

	if ( 'captchac' == $type )
		$class_att .= ' suptic-captcha-' . $name;

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

	// Value
	$value = $values[0];

	if ( 'captchac' == $type ) {
		if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
			return '<em>' . __( 'To use CAPTCHA, you need <a href="http://wordpress.org/extend/plugins/really-simple-captcha/">Really Simple CAPTCHA</a> plugin installed.', 'suptic' ) . '</em>';
		}

		$op = array();
		// Default
		$op['img_size'] = array( 72, 24 );
		$op['base'] = array( 6, 18 );
		$op['font_size'] = 14;
		$op['font_char_width'] = 15;

		$op = array_merge( $op, suptic_captchac_options( $options ) );

		if ( ! $filename = suptic_generate_captcha( $op ) )
			return '';

		if ( is_array( $op['img_size'] ) )
			$atts .= ' width="' . $op['img_size'][0] . '" height="' . $op['img_size'][1] . '"';

		$captcha_url = trailingslashit( suptic_captcha_tmp_url() ) . $filename;
		$html = '<img alt="captcha" src="' . $captcha_url . '"' . $atts . ' />';
		$ref = substr( $filename, 0, strrpos( $filename, '.' ) );
		$html = '<input type="hidden" name="_suptic_captcha_challenge_' . $name . '" value="' . $ref . '" />' . $html;

		return $html;

	} elseif ( 'captchar' == $type ) {
		if ( $size_att )
			$atts .= ' size="' . $size_att . '"';
		else
			$atts .= ' size="40"'; // default size

		if ( $maxlength_att )
			$atts .= ' maxlength="' . $maxlength_att . '"';

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
}


/* Validation filter */

add_filter( 'suptic_validate_captchar', 'suptic_captcha_validation_filter', 10, 2 );

function suptic_captcha_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];

	$_POST[$name] = (string) $_POST[$name];

	$captchac = '_suptic_captcha_challenge_' . $name;

	if ( ! suptic_check_captcha( $_POST[$captchac], $_POST[$name] ) ) {
		$result['valid'] = false;
		$result['reason'][$name] = __( "Your entered code is incorrect.", 'suptic' );
	}

	suptic_remove_captcha( $_POST[$captchac] );

	return $result;
}


/* Ajax echo filter */

add_filter( 'suptic_ajax_json_echo', 'suptic_captcha_ajax_echo_filter' );

function suptic_captcha_ajax_echo_filter( $items ) {
	global $suptic_form;

	if ( ! is_a( $suptic_form, 'SupTic_Form' ) )
		return $items;

	if ( ! is_array( $items ) )
		return $items;

	$fes = $suptic_form->form_scan_shortcode(
		array( 'type' => 'captchac' ) );

	if ( empty( $fes ) )
		return $items;

	$refill = array();

	foreach ( $fes as $fe ) {
		$name = $fe['name'];
		$options = $fe['options'];

		if ( empty( $name ) )
			continue;

		$op = suptic_captchac_options( $options );
		if ( $filename = suptic_generate_captcha( $op ) ) {
			$captcha_url = trailingslashit( suptic_captcha_tmp_url() ) . $filename;
			$refill[$name] = $captcha_url;
		}
	}

	if ( ! empty( $refill ) )
		$items['captcha'] = $refill;

	return $items;
}


/* CAPTCHA functions */

function suptic_init_captcha() {
	global $suptic_captcha;

	if ( ! class_exists( 'ReallySimpleCaptcha' ) )
		return false;

	if ( ! is_object( $suptic_captcha ) )
		$suptic_captcha = new ReallySimpleCaptcha();
	$captcha =& $suptic_captcha;

	$captcha->tmp_dir = trailingslashit( suptic_captcha_tmp_dir() );
	wp_mkdir_p( $captcha->tmp_dir );
	return true;
}

function suptic_generate_captcha( $options = null ) {
	global $suptic_captcha;

	if ( ! suptic_init_captcha() )
		return false;
	$captcha =& $suptic_captcha;

	if ( ! is_dir( $captcha->tmp_dir ) || ! is_writable( $captcha->tmp_dir ) )
		return false;

	$img_type = imagetypes();
	if ( $img_type & IMG_PNG )
		$captcha->img_type = 'png';
	elseif ( $img_type & IMG_GIF )
		$captcha->img_type = 'gif';
	elseif ( $img_type & IMG_JPG )
		$captcha->img_type = 'jpeg';
	else
		return false;

	if ( is_array( $options ) ) {
		if ( isset( $options['img_size'] ) )
			$captcha->img_size = $options['img_size'];
		if ( isset( $options['base'] ) )
			$captcha->base = $options['base'];
		if ( isset( $options['font_size'] ) )
			$captcha->font_size = $options['font_size'];
		if ( isset( $options['font_char_width'] ) )
			$captcha->font_char_width = $options['font_char_width'];
		if ( isset( $options['fg'] ) )
			$captcha->fg = $options['fg'];
		if ( isset( $options['bg'] ) )
			$captcha->bg = $options['bg'];
	}

	$prefix = mt_rand();
	$captcha_word = $captcha->generate_random_word();
	return $captcha->generate_image( $prefix, $captcha_word );
}

function suptic_check_captcha( $prefix, $response ) {
	global $suptic_captcha;

	if ( ! suptic_init_captcha() )
		return false;
	$captcha =& $suptic_captcha;

	return $captcha->check( $prefix, $response );
}

function suptic_remove_captcha( $prefix ) {
	global $suptic_captcha;

	if ( ! suptic_init_captcha() )
		return false;
	$captcha =& $suptic_captcha;

	$captcha->remove( $prefix );
}

function suptic_captcha_tmp_dir() {
	if ( defined( 'SUPTIC_CAPTCHA_TMP_DIR' ) )
		return SUPTIC_CAPTCHA_TMP_DIR;
	else
		return suptic_upload_dir( 'dir' ) . '/suptic_captcha';
}

function suptic_captcha_tmp_url() {
	if ( defined( 'SUPTIC_CAPTCHA_TMP_URL' ) )
		return SUPTIC_CAPTCHA_TMP_URL;
	else
		return suptic_upload_dir( 'url' ) . '/suptic_captcha';
}

if ( ! is_admin() && 'GET' == $_SERVER['REQUEST_METHOD'] )
	suptic_cleanup_captcha_files();

function suptic_cleanup_captcha_files() {
	global $suptic_captcha;

	if ( ! suptic_init_captcha() )
		return false;
	$captcha =& $suptic_captcha;

	if ( is_callable( array( $captcha, 'cleanup' ) ) )
		return $captcha->cleanup();

	$dir = trailingslashit( suptic_captcha_tmp_dir() );

	if ( ! is_dir( $dir ) || ! is_readable( $dir ) || ! is_writable( $dir ) )
		return false;

	if ( $handle = @opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( ! preg_match( '/^[0-9]+\.(php|png|gif|jpeg)$/', $file ) )
				continue;

			$stat = @stat( $dir . $file );
			if ( $stat['mtime'] + 3600 < time() ) // 3600 secs == 1 hour
				@unlink( $dir . $file );
		}
		closedir( $handle );
	}
}

function suptic_captchac_options( $options ) {
	if ( ! is_array( $options ) )
		return array();

	$op = array();
	$image_size_array = preg_grep( '%^size:[smlSML]$%', $options );

	if ( $image_size = array_shift( $image_size_array ) ) {
		preg_match( '%^size:([smlSML])$%', $image_size, $is_matches );
		switch ( strtolower( $is_matches[1] ) ) {
			case 's':
				$op['img_size'] = array( 60, 20 );
				$op['base'] = array( 6, 15 );
				$op['font_size'] = 11;
				$op['font_char_width'] = 13;
				break;
			case 'l':
				$op['img_size'] = array( 84, 28 );
				$op['base'] = array( 6, 20 );
				$op['font_size'] = 17;
				$op['font_char_width'] = 19;
				break;
			case 'm':
			default:
				$op['img_size'] = array( 72, 24 );
				$op['base'] = array( 6, 18 );
				$op['font_size'] = 14;
				$op['font_char_width'] = 15;
		}
	}

	$fg_color_array = preg_grep( '%^fg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $options );
	if ( $fg_color = array_shift( $fg_color_array ) ) {
		preg_match( '%^fg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $fg_color, $fc_matches );
		if ( 3 == strlen( $fc_matches[1] ) ) {
			$r = substr( $fc_matches[1], 0, 1 );
			$g = substr( $fc_matches[1], 1, 1 );
			$b = substr( $fc_matches[1], 2, 1 );
			$op['fg'] = array( hexdec( $r . $r ), hexdec( $g . $g ), hexdec( $b . $b ) );
		} elseif ( 6 == strlen( $fc_matches[1] ) ) {
			$r = substr( $fc_matches[1], 0, 2 );
			$g = substr( $fc_matches[1], 2, 2 );
			$b = substr( $fc_matches[1], 4, 2 );
			$op['fg'] = array( hexdec( $r ), hexdec( $g ), hexdec( $b ) );
		}
	}

	$bg_color_array = preg_grep( '%^bg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $options );
	if ( $bg_color = array_shift( $bg_color_array ) ) {
		preg_match( '%^bg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $bg_color, $bc_matches );
		if ( 3 == strlen( $bc_matches[1] ) ) {
			$r = substr( $bc_matches[1], 0, 1 );
			$g = substr( $bc_matches[1], 1, 1 );
			$b = substr( $bc_matches[1], 2, 1 );
			$op['bg'] = array( hexdec( $r . $r ), hexdec( $g . $g ), hexdec( $b . $b ) );
		} elseif ( 6 == strlen( $bc_matches[1] ) ) {
			$r = substr( $bc_matches[1], 0, 2 );
			$g = substr( $bc_matches[1], 2, 2 );
			$b = substr( $bc_matches[1], 4, 2 );
			$op['bg'] = array( hexdec( $r ), hexdec( $g ), hexdec( $b ) );
		}
	}

	return $op;
}

$suptic_captcha = null;

?>