<?php
/**
 * Wikimedia Mailing list utilities
 *
 * Usage: <https://list.toolforge.org/?name=wikitech-l&action=lastentry>
 *
 * BEWARE: Ugly hacks ahead. Proceed with caution!
 *
 * @author Timo Tijhof, 2010-2019
 */

namespace Krinkle\WmfListTool;

class WmfListTool {

	public static function help() {
		echo '<!DOCTYPE html>'
			. "\n" . '<title>Mailing list utilities</title>'
			. "\n" . '<h1>Wikimedia Mailing list utilities</h1>'
			. "\n" . '<p>See <a href="https://github.com/Krinkle/wmf-tool-list#readme">Documentation (on github.com)</a>.</p>';
		exit;
	}

	public static function userError( $text ) {
		http_response_code( 404 );
		echo '<!DOCTYPE html>'
			. "\n" . '<title>Mailing list utilities</title>'
			. "\n" . '<h1>Request Error</h1>'
			. "\n" . '<p>' . htmlspecialchars( $text ) . '</p>'
			. '<hr>'
			. "\n" . '<p>See <a href="https://github.com/Krinkle/wmf-tool-list#readme">Documentation (on github.com)</a>.</p>';
		exit;
	}

	public static function serverError( $text ) {
		http_response_code( 500 );
		echo '<!DOCTYPE html>'
			. "\n" . '<title>Mailing Lists utilities</title>'
			. "\n" . '<h1>Internal Server Error</h1>'
			. "\n" . '<p>' . htmlspecialchars( $text ) . '</p>'
			. '<hr>'
			. "\n" . '<p>See <a href="https://github.com/Krinkle/wmf-tool-list#readme">Documentation (on github.com)</a>.</p>';
		exit;
	}

	public static function injectScript( $scriptTag, $html ) {
		if ( !is_string( $scriptTag ) || !is_string( $html ) ) {
			self::serverError( 'Error while loading script.' );
		}
		return str_ireplace( '</body>', $scriptTag . '</body>', $html );

	}

	public static function injectJQuery( $html ) {
		return self::injectScript( '<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/1.11.1/jquery.min.js" integrity="sha256-VAvG3sHdS5LqTT+5A/aeq/bZGa/Uj04xKxY8KM/w9EE=" crossorigin="anonymous"></script>', $html );
	}

	public static function downloadListPage( $list = false, $path = '' ) {
		$url = 'https://lists.wikimedia.org/pipermail/' . rawurlencode( $list ) . '/' . $path;
		$html = file_get_contents( $url );
		if ( !$html ) {
			self::serverError( 'Error while retrieving list index.' );
		}
		return $html;
	}

	public static function injectGoToCurrMonth( $html, $list ) {
		// The Pipermail page contains HTML like the following:
		//  <a href="2019-July/date.html">[ Date ]</a>
		//
		// We serve this JavaScript code client-side, from where the Mailing list utilities are
		// hosted (e.g. Toolforge), not from the Pipermail server. This means we need to
		// expand this href relative to where HTML was scraped it from. That's hard,
		// so just assume for now that the href value is simple suffix.
		$base = json_encode( 'https://lists.wikimedia.org/pipermail/' . rawurlencode( $list ) . '/' );
		$script = <<<HTML
		<script>
		jQuery(function ($) {
			var currMonthSubpath = $('a[href*="date.html"]').attr('href');
			location.href = $base + currMonthSubpath;
		});
		</script>
HTML;
		return self::injectScript( $script, $html );
	}

	public static function injectGoToLastEntry_StepOne( $html, $params ) {
		$params = array_merge( $params, array(
			'action' => 'lastentry-processing',
			'tmp' => '$1',
		) ) ;
		$replace = rawurlencode( '$1' );
		$base = './?' . http_build_query( $params );
		$script = <<<HTML
		<script>
		jQuery(function ($) {
			var currMonthSubpath = $('a[href*="date.html"]').attr('href');
			location.href = '$base'.replace('$replace', currMonthSubpath);
		});
		</script>
HTML;
		return self::injectScript( $script, $html );
	}

	public static function injectGoToLastEntry_StepTwo( $html, $list, $tmp ) {
		$replace = rawurlencode( '$1' );
		$base = 'https://lists.wikimedia.org/pipermail/' .
			rawurlencode( $list ) . '/' .
			# eg. change "2019-July/date.html" to "2019-July"
			rawurlencode( str_replace( '/date.html', '', $tmp ) ) .
			'/' . $replace;

		$script = <<<HTML
		<script>
		jQuery(function ($) {
			var latestEntrySubpath = $('ul > li:last-child > a:first-child').attr('href');
			location.href = '$base'.replace('$replace', latestEntrySubpath);
		});
		</script>
HTML;
		return self::injectScript( $script, $html );
	}
}

ini_set( 'user_agent', '<https://github.com/Krinkle/wmf-tool-list> (<https://list.toolforge.org/>)' );

$validActions = array( 'index', 'thismonth', 'lastentry', 'lastentry-processing' );
$params = array(
	// Backward compatibility: Support 'name' parameter as alias for 'list'.
	'list' => $_GET['list'] ?? $_GET['name'] ?? '',
	'action' => $_GET['action'] ?? null,
	'tmp' => $_GET['tmp'] ?? null,
);
if ( !strlen( $_SERVER['QUERY_STRING'] ?? '' ) ) {
	WmfListTool::help();
}
if ( $params['list'] === '' ) {
	WmfListTool::userError( 'Missing "name" parameter.' );
}
if ( !in_array( $params['action'], $validActions ) ) {
	WmfListTool::userError( 'Invalid value for "action" parameter.' );
}

// Output
if ( $params['action'] == 'index' ) {
	$html = WmfListTool::downloadListPage( $params['list'] );
	echo $html;
	exit;
}

if ( $params['action'] == 'thismonth' ) {
	$html = WmfListTool::downloadListPage( $params['list'] );
	$html = WmfListTool::injectJQuery( $html );
	$html = WmfListTool::injectGoToCurrMonth( $html, $params['list'] );
	echo $html;
	exit;
}

if ( $params['action'] == 'lastentry' ) {
	$html = WmfListTool::downloadListPage( $params['list'] );
	$html = WmfListTool::injectJQuery( $html );
	$html = WmfListTool::injectGoToLastEntry_StepOne( $html, $params );
	echo $html;
	exit;
}

if ( $params['action'] == 'lastentry-processing' ) {
	$html = WmfListTool::downloadListPage( $params['list'], $params['tmp'] );
	$html = WmfListTool::injectJQuery( $html );
	$html = WmfListTool::injectGoToLastEntry_StepTwo( $html, $params['list'], $params['tmp'] );
	echo $html;
	exit;
}
