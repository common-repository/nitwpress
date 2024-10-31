<?php

/*
Copyright (c) 2009-2020 sakuratan.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
 * Remove unused fileds from json response.
 */
function nitwpress_compact_json( $json, $options ) {
	$user_replacement = array();
	if ( array_key_exists( 'profile_background_image_url', $options ) &&
		 $options['profile_background_image_url'] ) {
		$user_replacement['profile_background_image_url'] = $options['profile_background_image_url'];
		if ( array_key_exists( 'profile_background_tile', $options ) ) {
			if ( $options['profile_background_tile'] ) {
				$user_replacement['profile_background_tile'] = 'true';
			} else {
				$user_replacement['profile_background_tile'] = 'false';
			}
		}
	}

	$flagment_pickup = array( 'created_at', 'text', 'entities' );

	$user_pickup = array(
		'profile_image_url',
		'profile_image_url_https',
		'profile_background_color',
		'profile_text_color',
		'profile_link_color',
		'profile_sidebar_fill_color',
		'profile_sidebar_border_color',
		'profile_background_image_url',
		'profile_background_image_url_https',
		'profile_background_tile',
	);

	$compact_json = array();
	foreach ( $json as $datum ) {
		$elem = array();
		foreach ( $flagment_pickup as $field ) {
			if ( array_key_exists( $field, $datum ) ) {
				$elem[ $field ] = $datum[ $field ];
			}
		}
		$elem['user'] = array();
		foreach ( $user_pickup as $field ) {
			if ( array_key_exists( $field, $user_replacement ) ) {
				$elem['user'][ $field ] = $user_replacement[ $field ];
			} else {
				if ( array_key_exists( $field, $datum['user'] ) ) {
					$elem['user'][ $field ] = $datum['user'][ $field ];
				}
			}
		}
		array_push( $compact_json, $elem );
	}

	return $compact_json;
}

/*
 * Update cache files.
 */
function nitwpress_twitter_update_caches( $dir, $options ) {
	if ( ! @is_dir( $dir ) ) {
		if ( ! mkdir( $dir, 0755, true ) ) {
			return false;
		}
	}

	$api_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json?count=50&screen_name=' . rawurlencode( $options['username'] );

	// Call Twitter UserTimeline API
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
	curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
	curl_setopt( $ch, CURLOPT_URL, $api_url );
	$authorization = 'Authorization: Bearer ' . $options['bearertoken'];
	$httpheader    = array( 'Content-Type: application/json', $authorization );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $httpheader );

	$ctx = curl_exec( $ch );
	if ( ! $ctx ) {
		curl_close( $ch );
		return false;
	}
	$code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	curl_close( $ch );
	if ( $code != 200 ) {
		trigger_error( "Twitter api returns error with {$code}", E_USER_WARNING );
		return false;
	}

	$json = json_decode( $ctx, true );
	$json = nitwpress_compact_json( $json, $options );
	file_put_contents( "{$dir}/user_timeline.json", json_encode( $json ) );

	return true;
}


