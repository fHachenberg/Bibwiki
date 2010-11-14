<?php
if (!defined('MEDIAWIKI'))
	die();

/**
 * Bibwiki - bibliography management extension for Mediawiki
 * 
 * @addtogroup Extensions
 * @package Bibwiki
 *
 * @link http://www.plaschg.net/bibwiki Homepage
 * @link http://www.plaschg.net/bibwiki/docs Code documentation
 * @author Wolfgang Plaschg <wpl@gmx.net>
 * @copyright Copyright (C) 2007 Wolfgang Plaschg
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the skin file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install Bibwiki, put the following line in LocalSettings.php:
include_once( "$IP/extensions/<Your Bibwiki folder>/Bibwiki.php" );
EOT;
        exit( 1 );
}

require_once(dirname(__FILE__)."/BibMarkup.php");
 
$wgAutoloadClasses['Bibliography'] = dirname(__FILE__) . '/Bibwiki.body.php';  # Tell MediaWiki to load the extension body.
$wgExtensionMessagesFiles['Bibliography'] = dirname(__FILE__) . '/Bibwiki.i18n.php';
$wgSpecialPages['Bibliography'] = 'Bibliography'; 						       # Let MediaWiki know about your new special page.

$wgHooks['LanguageGetSpecialPageAliases'][] = 'BibliographyLocalizedPageName'; # Add any aliases for the special page.
$wgHooks['LoadAllMessages'][] = 'Bibliography::loadMessages'; 				   # Load the internationalization messages for your special page.
$wgHooks['RenderPageTitle'][] = 'wfRenderPageTitle';

$wgExtensionCredits['specialpage'][] = array(
       'name' => 'Bibwiki',
       'author' =>'Wolfgang Plaschg, OSBiB by Mark Grimshaw, BibCite.php based on Cite.php by Ævar Arnfjörð Bjarmason', 
       'url' => 'http://www.plaschg.net/bibwiki', 
       'description' => 'Extension for managing BibTeX Databases'
       );

define("NS_BIB", 222);       
define("NS_BIB_TALK", 223);
$wgExtraNamespaces[NS_BIB] = "Bib";
$wgExtraNamespaces[NS_BIB_TALK] = "BibTalk";
$wgContentNamespaces[] = NS_BIB;


function BibliographyLocalizedPageName(&$specialPageArray, $code) {
  # The localized title of the special page is among the messages of the extension:
  
  wfLoadExtensionMessages(wfMsg('bibliography'));
  
  # Convert from title in text form to DBKey and put it into the alias array:
  $title = Title::newFromText(wfMsg('bibliography'));
  $specialPageArray[wfMsg('bibliography')][] = wfMsg('bibliography');
  
  return true;
}

function wfRenderPageTitle(&$skin) {
	global $wgScript, $wgUser, $wgContLang;

	/*
	$title = $skin->data['title'];
	$f = "";
	if (strstr($title, "/") !== false) {
		$split = explode("/", $title, 2);
		$f = strtolower($split[0]);
		$key = $split[1];
		print $wgUser->getSkin()->makeKnownLink( $wgContLang->specialPage( "Bibliography" ), $key, "startkey=".$key . "&f=".$f );
	}
	*/
	
	if (strpos($skin->data['title'], "Bib:") === 0) {
		$key = str_replace("Bib:", "", $skin->data['title']);
		print $wgUser->getSkin()->makeKnownLink( $wgContLang->specialPage( wfMsg("bibliography") ), $skin->data['title'], "startkey=".$key );
	}
    else
		#print $skin->data['title'];
		print $wgUser->getSkin()->makeKnownLink( $wgContLang->specialPage( wfMsg("bibliography") ), $skin->data['title'], "startkey=".$skin->data['title'] );

	return true;
}
