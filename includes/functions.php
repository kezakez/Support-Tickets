<?php

function suptic_json( $items ) {
	if ( is_array( $items ) ) {
		if ( empty( $items ) )
			return 'null';

		$keys = array_keys( $items );
		$all_int = true;
		foreach ( $keys as $key ) {
			if ( ! is_int( $key ) ) {
				$all_int = false;
				break;
			}
		}

		if ( $all_int ) {
			$children = array();
			foreach ( $items as $item ) {
				$children[] = suptic_json( $item );
			}
			return '[' . join( ', ', $children ) . ']';
		} else { // Object
			$children = array();
			foreach ( $items as $key => $item ) {
				$key = esc_js( (string) $key );
				//if ( preg_match( '/[^a-zA-Z]/', $key ) )
					$key = '"' . $key . '"';

				$children[] = $key . ': ' . suptic_json( $item );
			}
			return '{ ' . join( ', ', $children ) . ' }';
		}
	} elseif ( is_numeric( $items ) ) {
		return (string) $items;
	} elseif ( is_bool( $items ) ) {
		return $items ? '1' : '0';
	} elseif ( is_null( $items ) ) {
		return 'null';
	} else {
		return '"' . esc_js( (string) $items ) . '"';
	}
}

?>