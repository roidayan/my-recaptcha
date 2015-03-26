<?php
/*
 * reCAPTCHA V2
 * Copyright (c) 2015 Roi Dayan (http://roidayan.com)
 *
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

define('RECAPTCHA_VERIFY_SERVER', 'https://www.google.com/recaptcha/api/siteverify');
define('RECAPTCHA_API_SCRIPT', 'https://www.google.com/recaptcha/api.js');
define('RECAPTHCA_DEFAULT_LANG_CODE', 'en');


class ReCaptchaResponse {
	var $is_valid;
	var $error;
}

/**
 * Get response for reCAPTCHA
 */
function recaptcha_get_response( $url ) {
	if ( function_exists( 'wp_remote_get' ) ) {
		$response = wp_remote_get( $url );
		$content = wp_remote_retrieve_body( $response );
	} else {
		$content = file_get_contents( $url );
	}
	return $content;
}

function recaptcha_get_script_url( $lang ) {
	if ( ! $lang ) {
		$lang = RECAPTHCA_DEFAULT_LANG_CODE;
	}

	return RECAPTCHA_API_SCRIPT . '?hl=' . $lang;
}

/**
 * Show reCAPTCHA html code
 */
function recaptcha_get_html( $pubkey, $lang ) {
	if ( empty( $pubkey ) ) {
		die( 'reCAPTCHA was not configured' );
	}

	echo '<div id="recaptcha_widget_div" class="g-recaptcha" data-sitekey="' . $pubkey . '"></div>';
}

/**
 * @return: The value of 'g-recaptcha-response'.
 */
function recaptcha_check_answer( $privkey, $remoteip, $response ) {
	if ( empty( $response ) || empty( $privkey ) || empty( $remoteip ) ) {
		$recaptcha_response = new ReCaptchaResponse();
		$recaptcha_response->is_valid = false;
		$recaptcha_response->error = 'error';
		return $recaptcha_response;
	}

	$q = http_build_query( array(
		'secret' => $privkey,
		'response' => $response,
		'remoteip' => $remoteip
	));

	$url = RECAPTCHA_VERIFY_SERVER . '?' . $q;
	$res = recaptcha_get_response( $url );
	$res = json_decode( $res, true );
	$recaptcha_response = new ReCaptchaResponse();

	if ( ! empty ( $res['success'] ) && $res['success'] ) {
		$recaptcha_response->is_valid = true;
	} else {
		$recaptcha_response->is_valid = false;
		$recaptcha_response->error = 'incorrect';
	}

	return $recaptcha_response;
}