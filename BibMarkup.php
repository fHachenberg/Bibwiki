<?php
if (!defined('MEDIAWIKI'))
	die();

/**
 * Bibwiki - bibliography managament extension for Mediawiki
 * 
 * @addtogroup Extensions
 * @package Bibwiki
 *
 * @author Wolfgang Plaschg <wpl@gmx.net>
 * @copyright Copyright (C) 2007 Wolfgang Plaschg
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionFunctions[] = "wfBibExtensions";
$wgHooks['BeforePageDisplay'][] = 'wfBibMarkupStyles';

function bwImplodeQuery($args) {
	$rv = "";
	foreach($args as $arg) {
		if ($arg != "")
		{
			if ($rv != "")
				$rv .= "&";
			$rv .= $arg;
		}		
	}
	return $rv;
}

function wfBibExtensions() {
    global $wgParser;
    $wgParser->setHook("bib", "BibMarkup");
    $wgParser->setHook("paper", "PaperMarkup");
    $wgParser->setHook("h", "Highlight");
    $wgParser->setHook("qu", "Blockquote");
}

# The callback function for converting the input text to HTML output
function BibMarkup( $input, $argv, $parser = null) {
	global $wgScriptPath, $wgUser, $wgContLang;
	
	if (empty($argv["key"])) 
		$key = $input;
	else
		$key = $argv["key"];
	return $wgUser->getSkin()->makeKnownLink($wgContLang->specialPage("Bibliography"), $input, bwImplodeQuery(array((($argv["f"]!="")?'f='.$argv["f"]:''), 'startkey='.$key)));
	# return $wgUser->getSkin()->makeKnownLink($wgContLang->specialPage("Bibliography"), $input, bwImplodeQuery(array((($argv["f"]!="")?'f='.$argv["f"]:''), 'startkey='.$argv["key"])));
}

# The callback function for converting the input text to HTML output
function PaperMarkup( $input, $argv, $parser = null) {
	global $wgScriptPath, $wgDownloadsUrl;
	require_once(dirname( __FILE__ ) ."/BibwikiSettings.php");

    return '<a href="'.$wgDownloadsUrl.'/'.(($argv["f"]!="")?$argv["f"]:$input).'">'.$input.'</a>';
}

function Highlight( $input, $argv, $parser = null) {
    return '<span class="highlight">'.$input.'</span>';
}

function Blockquote( $input, $argv, $parser = null) {
    return '<div class="blockquote">'.$input.'</div>';
}

function wfBibMarkupStyles(&$out) {
	$out->addScript('
<style type="text/css">
<!--
.highlight {
    background-color: yellow;
}
.blockquote {
	border-left: 12px solid #BBBBBB;
	margin:5px 0 5px -25px;
	padding:0;
	padding-left: 15px;
}
-->
</style>');
	return true;
}