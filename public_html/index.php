<?php
/**
 * Wikimedia Mailing Lists utilities
 *
 * Usage:
 *
 *  - https://tools.wmflabs.org/list/?name=wikitech-l&action=lastentry
 *
 * Short urls:
 *
 * - wikitech-l:
 *   - http://bit.ly/wikitechLast
 *   - http://bit.ly/wikitechLatest
 *
 * - toolserver-l:
 *   - http://bit.ly/toolserverLast
 *   - http://bit.ly/toolserverLatest
 *   - http://bit.ly/toolserverMonth
 *
 * - commons-l:
 *   - http://bit.ly/commonsLast
 *   - http://bit.ly/commonsMonth
 *
 * BEWARE: Ugly hacks ahead. Caution proceeding.
 *
 * @license http://krinkle.mit-license.org/
 * @author Timo Tijhof, 2010-2014
 */

/**
 * Configuration
 * -------------------------------------------------
 */
require_once __DIR__ . '/../lib/basetool/InitTool.php';

/**
 * Functions
 * -------------------------------------------------
 */

function injectScript( $scriptTag, $source ) {
	if ( !is_string( $scriptTag ) || !is_string( $source ) ) {
		die( 'Error while loading script.' );
	}
	return str_ireplace( '</body>', $scriptTag . '</body>', $source );

}

function injectJQuery( $source ) {
	return injectScript( '<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>', $source );
}

function downloadListPage( $list = false, $path = '' ) {
	$url = 'http://lists.wikimedia.org/pipermail/' . rawurlencode( $list ) . '/' . $path;
	$sourceCode = file_get_contents( $url );
	if ( $sourceCode ) {
		return $sourceCode;
	} else {
		die( 'Error while retrieving list index.' );
	}
}

function injectGoToCurrMonth( $source, $list ) {
	$replace = rawurlencode( '$1' );
	$base = 'http://lists.wikimedia.org/pipermail/' . rawurlencode( $list ) . '/' . $replace;
	$script = <<<SCRIPT
	<script>
	jQuery(function ($) {
		var currMonthLocation = $('a').eq(4).attr('href');
		location.href = '$base'.replace('$replace', currMonthLocation);
	});
	</script>
SCRIPT;
	return injectScript( $script, $source );
}

function injectGoToLastEntry_StepOne( $source, $params ) {
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
	return injectScript( $script, $source );
}

function injectGoToLastEntry_StepTwo( $source, $list, $tmp ) {
	$replace = rawurlencode( '$1' );
	$base = 'http://lists.wikimedia.org/pipermail/' .
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
	return injectScript( $script, $source );
}

/**
 * Variables
 * -------------------------------------------------
 */

$validActions = array( 'index', 'thismonth', 'lastentry', 'lastentry-processing' );

$params = array(
	// Support 'list' for back-compat. Preferred key is 'name'.
	'list' => $kgReq->getVal( 'list', $kgReq->getVal( 'name' ) ),
	'action' => $kgReq->getVal( 'action' ),
	'tmp' => $kgReq->getVal( 'tmp' ),
);

/**
 * Validation
 * -------------------------------------------------
 */

if ( $params['list'] === null || $params['list'] === '' ) {
	die( 'Missing list parameter.' );
}

if ( !in_array( $params['action'], $validActions ) ) {
	die( 'Invalid action.' );
}

/**
 * Do it
 * -------------------------------------------------
 */

if ( $params['action'] == 'index' ) {
	$html = downloadListPage( $params['list'] );
	$html = injectJQuery( $html );
	echo $html;
	exit;
}

if ( $params['action'] == 'thismonth' ) {
	$html = downloadListPage( $params['list'] );
	$html = injectJQuery( $html );
	echo injectGoToCurrMonth( $html, $params['list'] );
	exit;
}

if ( $params['action'] == 'lastentry' ) {
	$html = downloadListPage( $params['list'] );
	$html = injectJQuery( $html );
	echo injectGoToLastEntry_StepOne( $html, $params );
	exit;
}

if ( $params['action'] == 'lastentry-processing' ) {
	$html = downloadListPage( $params['list'], $params['tmp'] );
	$html = injectJQuery( $html );
	echo injectGoToLastEntry_StepTwo( $html, $params['list'], $params['tmp'] );
	exit;
}
