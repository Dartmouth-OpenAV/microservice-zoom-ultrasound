#!/usr/bin/php
<?php
$last_checked_for_new_code = false ;
$sleep_for = 2 ;

while( true ) {
	if( $last_checked_for_new_code===false ||
		(time()-$last_checked_for_new_code)>2 ) {

		$orchestrator_url = "http://" . trim(file_get_contents("/container_to_container_ip")) . "/backend.php?command=get_status" ;

		$ch = curl_init() ;
		curl_setopt( $ch, CURLOPT_URL, $orchestrator_url ) ;
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 ) ;
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ) ;
		curl_setopt( $ch, CURLOPT_TIMEOUT, 5 ) ;
		$response = curl_exec( $ch ) ;
		$response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE ) ;
		$curl_errno = curl_errno( $ch ) ;
		curl_close( $ch ) ;

		if( $response_code==200 ) {
			$response = json_decode( $response, true ) ;
			if( !isset($response['zoom_room']['sharing_info']['pairing_code']) ) {
				echo "> no pairing code found\n" ;
				// echo "> unexpected response with:\n" . var_export( $response ) . "\n" ;
				// add_errors( "unexpected response with:\n" . var_export( $response ) ) ;
				$sleep_for = 10 ;
			} else {
				echo "> code: {$response['zoom_room']['sharing_info']['pairing_code']}\n" ;
				file_put_contents( "/web/code.txt", $response['zoom_room']['sharing_info']['pairing_code'] ) ;
				$last_checked_for_new_code = time() ;
				$sleep_for = 2 ;
			}
		} else {
			echo "> non-200 response code from orchestrator: {$response_code}\n" ;
			add_errors( "non-200 response code from orchestrator: {$response_code}" ) ;
		}
	}
	sleep( $sleep_for ) ;
}


function add_errors( $error_message ) {
	$current_errors = array() ;
	if( file_exists("/web/errors.json") ) {
		$current_errors = json_decode( file_get_contents("/web/errors.json"), true ) ;
		if( !is_array($current_errors) ) {
			$current_errors = array() ;
		}
	}
	$current_errors[] = $error_message ;
	file_put_contents( "/web/errors.json", json_encode($current_errors) ) ;
	chmod( "/web/errors.json", 0777 ) ;
}

?>