#!/usr/bin/php
<?php

while( true ) {
	if( file_exists("/web/code.txt") ) {
		$need_to_regen = false ;
		if( !$need_to_regen &&
			!file_exists("/web/code.gened") ) {
			$need_to_regen = true ;
		}
		if( !$need_to_regen &&
			file_exists("/web/code.gened") &&
			trim(file_get_contents("/web/code.gened"))!=trim(file_get_contents("/web/code.txt")) ) {
			$need_to_regen = true ;
		}

		if( $need_to_regen ) {
			$code_to_gen = trim( file_get_contents("/web/code.txt") ) ;
			echo "> " . date( "Y-m-d H:i:s" ) . " - regening code to: {$code_to_gen}\n" ;
			shell_exec( "/gensound.php {$code_to_gen} > /web/code.wav" ) ;
			shell_exec( "/usr/bin/amixer set PCM 85%" ) ;
			file_put_contents( "/web/code.gened", $code_to_gen ) ;
		}
		shell_exec( "/usr/bin/play /web/code.wav" ) ;
	} else {
		echo "> " . date( "Y-m-d H:i:s" ) . " - no code file\n" ;
		sleep( 1 ) ;
	}
}

?>