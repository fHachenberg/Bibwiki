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

/**
 *
 * DON'T EDIT THIS FILE. COPY IT TO "BibwikiSettings.php" FIRST AND EDIT
 * "BibwikiSettings.php" INSTEAD.
 *
 */

/**
 *
 * === Changes in BibwikiSettings.php ===
 *
 * === Version 0.99d ===
 * 
 * new      $wgDefaultReferencesStyle
 * renamed  $wgConvertSpecialCharsToTeXCommands to $wgConvertAnsiToTeX
 * 
 * === Version 0.99c ===
 * 
 * new      $wgBookCoverDirectory
 * new      $wgRestrictEditsToBureaucrats
 * deleted  require_once(".../lang/Language-...");
 * 
 * === Version 0.99b ===
 * 
 * no changes to Version 0.99
 * 
 */

#### ACCESS RIGHTS TO BIBWIKI SETTTINGS ####

# Restrict editing this file via Bibwiki to the given IPs. Seperate IPs with
# spaces. If nobody should be able to edit this file, set the variable
# to '-'.
$wgAllowEditSettingsFromIPs = '127.0.0.1';

# Set $wgRestrictEditsToBureaucrats to True if you want to restrict editing
# the bibliography (add, delete, edit, insert new, import) to bureaucrats
# (and sysops).
$wgRestrictEditsToBureaucrats = False;

#### PATH SETTINGS ####

# Define the absolute path to your BibTeX (.bib) files.
# Example: '/texmf/bibtex/bib'.
# This is a MANDATORY setting.
$wgBibPath = '';

# The filename of the default bibliograpy in $wgBibPath.
# This bibliography is loaded when nothing else is specified.
# Example: 'my.bib'.
# This is a MANDATORY setting.
$wgDefaultBib = '';

# Define the absolute path where to store backups (= old versions)
# of the .bib-files. Example: '/texmf/bibtex/bib/bak'.
# Every time you edit a .bib-file it will be copied with a timestamp to this
# location.
$wgBackupPath = $wgBibPath.'/bak';

# How many backups should be preserved. Don't be greedy with that amount.
# Set to 0 if you don't want to backup anything (not recommended).
$wgKeepBackups = 50;

# A shortcut for building URLs.
# Usually you don't have to change this option.
$wgWikiHost = $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];

# Absolute path to store downloaded documents. Apache must have write
# permission to this location.
# Example: '/www/htdocs/papers'.
$wgDownloadsPath = '';

# The URL to $wgDownloadsPath as specified in the webserver's configuration.
# Example: '/papers'.
$wgDownloadsUrl = '';

#### LIBRARY SETTINGS ####

# Set to your preferred library.
@include(dirname(__FILE__)."/libs/Library-US-Washington--LoC.php");

#### AMAZON SETTINGS ####

# Enable this option if you want Bibwiki to look for book cover images
# at Amazon. Every time you edit (or create) a bibliographic record Bibwiki
# looks for an ISBN-tag and searches for a matching cover image.
$wgFetchAndViewBookCovers = false;

# Define your Amazon Access Key. You can get a free key at Amazon Web Services
# http://aws.amazon.com/.
$accesskey = '';

# The directory where the book covers are stored.
include("LocalSettings.php");
$wgBookCoverDirectory = $wgUploadDirectory;

# Amazon's URL: Used when fetching cover images or when importing bibliographic
# information.
$wgAmazonURL = "amazon.com";

#### EXPORT SETTINGS ####

# Bibwiki can use BibTeX to export the bibliograpy in pretty-printed format. To do so,
# it invokes BibTeX and transformes the output to HTML. Since BibWiki is not a true
# TeX to HTML converter this only works for the common BST styles. Don't assume that
# crazy or complicated bibliographies will be rendered without error.
# Enable or disable the export feature (= show the "export"-tab) 
$wgEnableExport = false;

# An absolute path for temorary files. Apache MUST have write permissions to
# this location. It's necessary if you want to export a bibliography via BibTeX.
# Example: '/tmp'.
$wgTempDir = '';

# The absolute path to the BibTeX executable.
# Example: '/bin/texmf/bibtex'.
$wgBibTeXExecutable = '';

# Define the export styles here. Just use the names of the installed
# BibTeX styles (=.bst).
# Example: array('plain', 'abbrv', 'unsort');
$wgBibStyles = array('plain', 'abbrv');

# The default rendering style for <bibreferences />.
$wgDefaultReferencesStyle = 'APA';

#### STYLE AND FORMAT SETTINGS ####

# Set the style of bibliography to compact (true) or detailed (false).
$wgCompactStyle = true;

# Format of date and time strings. Use strftime variables.
$wgDateTimeFormat = "%Y-%m-%d";

# How many bibliographic items should be viewed per page.
$wgHowManyItemsPerPage = 20;

# Not implemented yet.
# $wgImportSplitTitles = true;

# Enable this setting if you want Bibwiki to convert diacrits to its TeX
# command. Example: replace 'ä' with '\"{a}'.
$wgConvertAnsiToTeX = true;

# Bibwiki automatically replaces publisher and address information
# with given string definitions. You MUST have defined the strings in
# your .bib file via the @STRING{...} command.
# This option takes a regular expression on the left and a substitute for
# the publisher name and its address.
$wgImportReplacements = array (
#  "sage" =>   array("pub-SAGE", "pub-SAGE:adr"),
#  "wiley" =>  array("pub-WILEY", "pub-WILEY:adr"),
);

$wgURLReplacements = array (
    '/\b\/\b/' =>    '/""',
    '/_/' =>         '\_',
    '/&/' =>         '\&',
    '/%/' =>         '\%',
    '/~/' =>         '\widetilde{}'
);

$wgURLReverseReplacements = array (
    '/\/""/' =>             '/',
    '/\\\\_/' =>            '_',
    '/\\\\&/' =>            '&',
    '/\\\\%/' =>            '%',
    '/\\\\widetilde{}/' =>  '~'
);

# Set the delimitation of the values of a bibliographic item.
# $wgTitle... is used for title, titleaddon, booktitle, and booktitleaddon.
# $wgValue... for all others.
$wgValueDelimLeft   = '{';
$wgValueDelimRight  = '}';
$wgTitleDelimLeft   = '{';
$wgTitleDelimRight  = '}';

# Enable this option if you want Bibwiki to wrap lines of your .bib-file when
# viewing the file. This won't affect the way how lines are stored in the
# .bib-file. (This only affects the style of the detailed view)
$wgBreakLines = true;

# Set the column at which to wrap the lines. (This only affects the style of
# the detailed view)
$wgLineBreakAt = 50;

# Define a pattern of how Bibwiki should name a downloaded file. Use
# <Author>, <Year>, <Title> and <Basename> as placeholders for its
# corresponding values.
$wgDocnamePattern = '<Author><Year><Title> - <Basename>';

# Define the maximum length of the <Title>-Placehoder here. Longer Titles will
# be cut off.
$wgMaxDocnameTitleLength = 80;

?>
