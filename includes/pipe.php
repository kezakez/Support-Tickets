<?php

class SupTic_Pipe {

	var $before = '';
	var $after = '';

	function SupTic_Pipe( $text ) {
		$pipe_pos = strpos( $text, '|' );
		if ( false === $pipe_pos ) {
			$this->before = $this->after = $text;
		} else {
			$this->before = substr( $text, 0, $pipe_pos );
			$this->after = substr( $text, $pipe_pos + 1 );
		}
	}
}

class SupTic_Pipes {

	var $pipes = array();

	function SupTic_Pipes( $texts ) {
		if ( ! is_array( $texts ) )
			return;

		foreach ( $texts as $text ) {
			$this->add_pipe( $text );
		}
	}

	function add_pipe( $text ) {
		$pipe = new SupTic_Pipe( $text );
		$this->pipes[] = $pipe;
	}

	function do_pipe( $before ) {
		foreach ( $this->pipes as $pipe ) {
			if ( $pipe->before == $before )
				return $pipe->after;
		}
		return $before;
	}

	function collect_befores() {
		$befores = array();

		foreach ( $this->pipes as $pipe ) {
			$befores[] = $pipe->before;
		}

		return $befores;
	}

	function zero() {
		return empty( $this->pipes );
	}

	function random_pipe() {
		if ( $this->zero() )
			return null;

		return $this->pipes[array_rand( $this->pipes )];
	}
}

?>