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

#### BEHAVIOURS ####

# Define the URL that should be opened when you click on the name of
# an author.
# Use $firstname as a placeholder for the first christian name.
# Use $firstname_normalized as a placeholder for the simplified first christian name ("Danyé Ben Rubín" => "Danye").
# Use $firstname_initial as a placeholder for the initial of the first christian name ("Danyé Ben Rubín" => "D").
# Use $firstnames as a placeholder for all christian names ("Danyé Ben Rubín" => "Danyé Ben").
# Use $firstnames_normalized as a placeholder for all simplified christian names ("Danyé Ben Rubín" => "Danye Ben").
# Use $firstnames_intiales as a placeholder for the initials of the christian names ("Danyé Ben Rubín" => "DB").
# Use $surname as a placeholder for the surname ("Danyé Ben Rubín" => "Rubín").
# Use $surname_normalized as a placeholder for the simplified name ("Danyé Ben Rubín" => "Rubin").
# Use $author as a placeholder for the whole name.
# Use $utf8_author as a placeholder for the whole utf8 converted name.
$wgAuthorLink = 'http://opac.hu-berlin.de/F/?func=find-b&find_code=WRD&adjacent=N&request=$utf8_author&x=0&y=0';

# Define the URL that should be opened when you click on the title of
# a bibliographic entry.
# Use $title as a placeholder for the title.
# Use $utf8_title as a placeholder for the utf8 converted title.
$wgTitleLink = 'http://opac.hu-berlin.de/F/?func=find-b&find_code=WTI&adjacent=N&request=$utf8_title&x=0&y=0';

# Define the Links next to a title field.
# Use $author as a placeholder for the authors (or editors).
# Use $title as a placeholder for the title.
# Use $utf8_title as a placeholder for the utf8 converted title.
# Use $self as a placeholder for the URL to the bibliography.
$wgTitleLinkTags = array (
	array ("href" => 'http://books.google.com/books?q=$title',
		   "target" => "google",
		   "text" => "Google Books"),
	array ("href" => 'http://scholar.google.com/scholar?q=allintitle:%22$title%22',
		   "target" => "google",
		   "text" => "Google Scholar")
);

# Define the URL that should be opened when you click on the ISBN of
# a bibliographic entry.
# Use $isbn as a placeholder for the ISBN.
$wgISBNLink = 'http://opac.hu-berlin.de/F/?func=find-b&find_code=IBN&adjacent=N&request=$isbn&x=0&y=0';

# Define the Links and the corresponding URLs next to an ISBN-tag.
# Use $isbn as a placeholder for the ISBN.
# Use $self as a placeholder for the URL to the bibliography.
$wgISBNLinkTags		= array (
	array ("href" => 'http://kvk.ubka.uni-karlsruhe.de/hylib-bin/kvk/nph-kvk2.cgi?maske=kvk-last&title=UB+Karlsruhe:+Karlsruher+Virtueller+Katalog+KVK+:+Ergebnisanzeige&header=http://www.ubka.uni-karlsruhe.de/kvk/kvk/kvk-header_de_2006_11.html&spacer=http://www.ubka.uni-karlsruhe.de/kvk/kvk/kvk-spacer_de_2006_11.html&footer=http://www.ubka.uni-karlsruhe.de/kvk/kvk/kvk-footer_de_2006_11.html&css=http://www.ubka.uni-karlsruhe.de/kvk/kvk/kvk-neu2.css&input-charset=utf-8&kvk-session=5MFBFXL4&ALL=&TI=&PY=&AU=&SB=$isbn&CI=&SS=&ST=&PU=&VERBUENDE=&kataloge=SWB&kataloge=BVB&kataloge=NRW&kataloge=HEBIS&kataloge=HEBIS_RETRO&kataloge=KOBV&kataloge=GBV&kataloge=DDB&kataloge=STABI_BERLIN&kataloge=TIB&kataloge=OEVK_GBV&kataloge=VD16&kataloge=VD17&kataloge=ZDB&target=_blank&Timeout=120',
		   "target" => "kvk",
		   "text" => "KVK"),
	array ("href" => 'http://dispatch.opac.d-nb.de/DB=4.1/SET=1/TTL=1/CMD?ACT=SRCHA&IKT=8500&SRT=YOP&TRM=$isbn',
		   "target" => "dnb",
		   "text" => "DNB"),
	array ("href" => 'http://www.amazon.de/exec/obidos/ASIN/$isbn',
		   "target" => "amazon",
		   "text" => wfMsg("bibwiki_amazon"))
);

# Define the URL that should be opened when you click on the name of
# a journal. 
# Use $journal as a placeholder for the journal.
# Not implemented yet.
$wgJournalLink = '';

?>