<?php
/**
 * getWMML.php :: All-in-One file
 *
 * Get Wikimedia Mailing Lists
 * Created on February 1st, 2011
 *
 * Copyright 2011 Krinkle <krinklemail@gmail.com>
 *
 * This file is released in the public domain.
 */
/*
        Shortened:
        Wikitech-l:             <http://bit.ly/wikitechLast>    / <http://bit.ly/wikitechLatest>
                                        <http://bit.ly/wikitechMonth>
        Toolserver-l:   <http://bit.ly/toolserverLast>  / <http://bit.ly/toolserverLatest>
                                        <http://bit.ly/toolserverMonth>
        Commons-l:              <http://bit.ly/toolserverLast>
                                        <http://bit.ly/toolserverMonth>
*/




/** BEWARE: Ugly hacks ahead. Caution proceeding. **/




/**
 * Configuration
 * -------------------------------------------------
 */
require_once( 'CommonStuff.php' );

$c['revID'] = '0.0.2';
$c['revDate'] = '2011-02-01';
$c['title'] = 'Get Wikimedia Mailing Lists';
$c['baseurl'] = $c['tshome'] .'/getWMML.php';


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
        return injectScript( '<script src="' . krGetjQueryURL() . '"></script>', $source );
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

function injectGoToCurrMonth( $source ) {
        global $params;
        $replace = '$1';
        $base = 'http://lists.wikimedia.org/pipermail/' . rawurlencode( $params['list'] ) . '/' . $replace;
        $script = <<<SCRIPT
        <script type="text/javascript">
        jQuery( document ).ready( function( $ ) {
                var currMonthLocation = $( 'a' ).eq( 4 ).attr( 'href' );
                location.href='$base'.replace( '$replace', currMonthLocation );
        } );
        </script>
SCRIPT;
        return injectScript( $script, $source );
}

function injectGoToLastEntry_StepOne( $source ) {
        global $params;
        $p = array_merge( $params, array(
                'action' => 'lastentry-processing',
                'tmp' => '$1',
        ) ) ;
        $replace = rawurlencode( '$1' );
        $base = generatePermalink( $p );
        $script = <<<SCRIPT
        <script type="text/javascript">
        jQuery( document ).ready( function( $ ) {
                var currMonthLocation = $( 'a' ).eq( 4 ).attr( 'href' );
                location.href='$base'.replace( '$replace', currMonthLocation );
        } );
        </script>
SCRIPT;
        return injectScript( $script, $source );
}

function injectGoToLastEntry_StepTwo( $source ) {
        global $params;
        $replace = rawurlencode( '$1' );
        $base = 'http://lists.wikimedia.org/pipermail/' . rawurlencode( $params['list'] ) . '/' . rawurlencode( str_replace( '/date.html', '', $params['tmp'] ) ) . '/' . $replace;
        $script = <<<SCRIPT
        <script type="text/javascript">
        jQuery( document ).ready( function( $ ) {
                var lastEntryLocation = $( 'ul:eq(1) > li:last > a:first' ).attr( 'href' );
                location.href='$base'.replace( '$replace', lastEntryLocation );
        } );
        </script>
SCRIPT;
        return injectScript( $script, $source );
}


/**
 * Settings
 * -------------------------------------------------
 */
$s = array();
$s['validActions'] = array( 'index', 'thismonth', 'lastentry', 'lastentry-processing' );


/**
 * Parameters
 * -------------------------------------------------
 */
$params['list'] = getParamVar( 'list' );
$params['action'] = getParamVar( 'action' );
$params['tmp'] = getParamVar( 'tmp' );

if ( !is_string( $params['list'] ) || $params['list'] == '' ) {
        die( 'Missing list parameter.' );
}

if ( !in_array( $params['action'], $s['validActions'] ) ) {
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
        die;
}

if ( $params['action'] == 'thismonth' ) {
        $html = downloadListPage( $params['list'] );
        $html = injectJQuery( $html );
        echo injectGoToCurrMonth( $html );
        die;
}

if ( $params['action'] == 'lastentry' ) {
        $html = downloadListPage( $params['list'] );
        $html = injectJQuery( $html );
        echo injectGoToLastEntry_StepOne( $html );
        die;
}

if ( $params['action'] == 'lastentry-processing' ) {
        $html = downloadListPage( $params['list'], $params['tmp'] );
        $html = injectJQuery( $html );
        echo injectGoToLastEntry_StepTwo( $html );
        die;
}
