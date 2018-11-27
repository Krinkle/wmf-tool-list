<?php
/**
 * Wikimedia Mailing Lists utilities
 *
 * Usage:
 *
 *  - https://tools.wmflabs.org/list/?name=wikitech-l&action=lastentry
 *
 * BEWARE: Ugly hacks ahead. Proceed with caution!
 *
 * @author Timo Tijhof, 2010-2018
 */

namespace Krinkle\WmfListTool;

use HttpRequest;

class WmfListTool {

	public static function help() {
		http_response_code( 400 );
		echo '<!doctype html>'
			. "\n" . '<title>Wikimedia Mailing Lists utilities</title>'
			. "\n". '<h1>Wikimedia Mailing Lists utilities</h1>'
			. "\n". '<p>See <a href="https://github.com/Krinkle/wmf-tool-list#readme">Documentation</a>.</p>';
		exit;
	}

	public static function error( $msg ) {
		echo '<!doctype html>'
			. "\n" . '<title>Error | Wikimedia Mailing Lists utilities</title>'
			. "\n". '<h1>Error</h1>'
			. "\n". '<p>' . htmlspecialchars( $msg ) . '</p>'
			. '<hr>'
			. '<p>Wikimedia mailing list utilities: <a href="https://github.com/Krinkle/wmf-tool-list#readme">Documentation</a></p>';
		exit;
	}

	public static function injectScript( $scriptTag, $source ) {
		if ( !is_string( $scriptTag ) || !is_string( $source ) ) {
			self::error( 'Error while loading script.' );
		}
		return str_ireplace( '</body>', $scriptTag . '</body>', $source );

	}

	public static function injectJQuery( $source ) {
		return self::injectScript( '<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/1.11.1/jquery.min.js" integrity="sha256-VAvG3sHdS5LqTT+5A/aeq/bZGa/Uj04xKxY8KM/w9EE=" crossorigin="anonymous"></script>', $source );
	}

	public static function downloadListPage( $list = false, $path = '' ) {
		$url = 'https://lists.wikimedia.org/pipermail/' . rawurlencode( $list ) . '/' . $path;

		$html = HttpRequest::get( $url );
		if ( !$html ) {
			self::error( 'Error while retrieving list index.' );
		}
		return $html;
	}

	public static function injectGoToCurrMonth( $source, $list ) {
		$replace = rawurlencode( '$1' );
		$base = 'https://lists.wikimedia.org/pipermail/' . rawurlencode( $list ) . '/' . $replace;
		$script = <<<SCRIPT
		<script>
		jQuery(function ($) {
			var currMonthLocation = $('a').eq(4).attr('href');
			location.href = '$base'.replace('$replace', currMonthLocation);
		});
		</script>
SCRIPT;
		return self::injectScript( $script, $source );
	}

	public static function injectGoToLastEntry_StepOne( $source, $params ) {
		$p = array_merge( $params, array(
			'action' => 'lastentry-processing',
			'tmp' => '$1',
		) ) ;
		$replace = rawurlencode( '$1' );
		$base = './?' . http_build_query( $p );
		$script = <<<SCRIPT
		<script>
		jQuery(function ($) {
			var currMonthLocation = $('a').eq(4).attr('href');
			location.href = '$base'.replace('$replace', currMonthLocation);
		});
		</script>
SCRIPT;
		return self::injectScript( $script, $source );
	}

	public static function injectGoToLastEntry_StepTwo( $source, $list, $tmp ) {
		$replace = rawurlencode( '$1' );
		$base = 'https://lists.wikimedia.org/pipermail/' .
			rawurlencode( $list ) . '/' .
			rawurlencode( str_replace( '/date.html', '', $tmp ) ) .
			'/' .
			$replace;

		$script = <<<SCRIPT
		<script>
		jQuery(function ($) {
			var lastEntryLocation = $('ul:eq(1) > li:last > a:first').attr('href');
			location.href = '$base'.replace('$replace', lastEntryLocation);
		});
		</script>
SCRIPT;
		return self::injectScript( $script, $source );
	}
}

/**
 * Do it
 * -------------------------------------------------
 */

require_once __DIR__ . '/../vendor/autoload.php';
global $kgReq;

$validActions = array( 'index', 'thismonth', 'lastentry', 'lastentry-processing' );

$params = array(
	// Support 'list' for back-compat. Preferred key is 'name'.
	'list' => $kgReq->getVal( 'list', $kgReq->getVal( 'name' ) ),
	'action' => $kgReq->getVal( 'action' ),
	'tmp' => $kgReq->getVal( 'tmp' ),
);

// Validation
if ( !$kgReq->getQueryString() ) {
	WmfListTool::help();
}
if ( $params['list'] === null || $params['list'] === '' ) {
	WmfListTool::error( 'Missing "name" parameter.' );
}
if ( !in_array( $params['action'], $validActions ) ) {
	WmfListTool::error( 'Invalid value for "action" parameter.' );
}

// Output
if ( $params['action'] == 'index' ) {
	$html = WmfListTool::downloadListPage( $params['list'] );
	$html = WmfListTool::injectJQuery( $html );
	echo $html;
	exit;
}

if ( $params['action'] == 'thismonth' ) {
	$html = WmfListTool::downloadListPage( $params['list'] );
	$html = WmfListTool::injectJQuery( $html );
	echo WmfListTool::injectGoToCurrMonth( $html, $params['list'] );
	exit;
}

if ( $params['action'] == 'lastentry' ) {
	$html = WmfListTool::downloadListPage( $params['list'] );
	$html = WmfListTool::injectJQuery( $html );
	echo injectGoToLastEntry_StepOne( $html, $params );
	exit;
}

if ( $params['action'] == 'lastentry-processing' ) {
	$html = WmfListTool::downloadListPage( $params['list'], $params['tmp'] );
	$html = WmfListTool::injectJQuery( $html );
	echo WmfListTool::injectGoToLastEntry_StepTwo( $html, $params['list'], $params['tmp'] );
	exit;
}
