<?php
if (!defined('MEDIAWIKI'))
	die();

/**
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
$wgAuthorLink = 'http://catalogue.ulrls.lon.ac.uk/search/?searchtype=X&searcharg=$author&searchscope=24&SORT=A&extended=0&SUBMIT=Search&searchlimits=';

# Define the URL that should be opened when you click on the title of
# a bibliographic entry.
# Use $title as a placeholder for the title.
# Use $utf8_title as a placeholder for the utf8 converted title.
$wgTitleLink = 'http://catalogue.ulrls.lon.ac.uk/search/?searchtype=t&searcharg=$title&searchscope=24&SORT=A&extended=0&SUBMIT=Search&searchlimits=';

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
$wgISBNLink = 'http://catalogue.ulrls.lon.ac.uk/search/i?SEARCH=$isbn';

# Define the Links and the corresponding URLs next to an ISBN-tag.
# Use $isbn as a placeholder for the ISBN.
# Use $self as a placeholder for the URL to the bibliography.
$wgISBNLinkTags		= array (
	array ("href" => 'http://catalogue.bl.uk/F/?func=find-b&request=$isbn&find_code=WIS&adjacent=N&image.x=40&image.y=11',
		   "target" => "dnb",
		   "text" => "BL"),
	array ("href" => 'http://www.worldcat.org/search?q=isbn:$isbn',
		   "target" => "worldcat",
		   "text" => "WorldCat"),
	array ("href" => 'http://www.amazon.co.uk/exec/obidos/ASIN/$isbn',
		   "target" => "amazon",
		   "text" => wfMsg("bibwiki_amazon"))
);

# Define the URL that should be opened when you click on the name of
# a journal. 
# Use $journal as a placeholder for the journal.
# Not implemented yet.
$wgJournalLink = '';

?>