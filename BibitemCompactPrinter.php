<?php
if (!defined('MEDIAWIKI'))
	die();

/**
 * BibitemCompactPrinter
 *
 * @addtogroup Extensions
 * @package Bibwiki
 *
 * @author Wolfgang Plaschg <wpl@gmx.net>
 * @copyright Copyright (C) 2007 Wolfgang Plaschg
 *
 * @link http://www.plaschg.net/bibwiki Homepage
 * @link http://www.plaschg.net/bibwiki/docs Code documentation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

require_once(dirname( __FILE__ ) ."/Bibitem.php");
require_once(dirname( __FILE__ ) ."/Misc.php");

/**
 * Prettyprints a given Bibitem depending of the bibliographic type.
 */
class BibitemCompactPrinter {

	/**
	 * The bibitem
	 *
	 * @var object
	 */
	var $mBibitem;
	/**
	 * Stores the query string (Usually this is "f=<filename>").
	 * Needed for building the hyperlinks of authors, titles etc.
	 *
	 * @var string
	 */
	var $mBibfileQuery;

	function BibitemCompactPrinter($bibfileQuery="") {
		$this->mBibfileQuery = $bibfileQuery;
	}

	/**
	 * The main routine. Returns a string with the prettyprinted bibitem.
	 *
	 * @param object $bibitem
	 * @return string
	 */
	function prettyPrint($bibitem) {
		$this->mBibitem = $bibitem;
		$type = mb_strtolower($this->mBibitem->getType());
		if ($type == "book") return $this->printBook();
		elseif ($type == "article") return $this->printArticle();
		elseif ($type == "incollection") return $this->printIncollection();
		elseif ($type == "inbook") return $this->printInbook();
		else return $this->printMisc();
	}

	/**
	 * @param  string
	 * @return string
	 */
	function concat($pre, $middle, $post="") {
		if ($middle != "")
			return ($pre.$middle.$post);
		return "";
	}

	/**
	 * @param  string
	 * @return string
	 */
	function removeHTMLTags($str) {
		if ($str != "") return preg_replace("/<.*>/U", "", $str);
		return "";
	}

	/**
	 * @param  string
	 * @return boolean
	 */
	function endsWithPunctuation($str) {
		if ($str == "") return false;
		$str = $this->removeHTMLTags($str);
		if (strpos(".?!;", substr(trim($str), -1)) !== false) return true;
		return false;
	}

	/**
	 * @param  string
	 * @return string
	 */
	function addPunctuation($str, $punct=".") {
		if ($str != "" and $this->endsWithPunctuation($str) === false) return $str.$punct;
		return $str;
	}

	/**
	 * @param  string $key  A bibitem key (eg. "author").
	 * @return string The formatted value of a given key of the bibitem.
	 */
	function val($key) {
		return $this->mBibitem->getPrettyValByKey($key);
	}

	/**
	 * Similar to PHP's implode but ignores empty strings.
	 *
	 * @param  string $glue
	 * @return string
	 */
	function implode($glue, $pieces) {
		$rv = "";
		foreach($pieces as $p) {
			if ($p != "") {
				if ($rv != "") $rv .= $glue . $p;
				else
					$rv = $p;
			}
		}
		return $rv;
	}

	/**
	 * Returns the first element of the array that is not empty.
	 *
	 * @param  array
	 * @return string
	 */
	function bwFirstOf($strs) {
		foreach ($strs as $str) {
			if ($str != "") return $str;
		}
		return "";
	}

	/**
	 * Returns the hyperlinked and formatted authors.
	 *
	 * @param  string
	 * @return string
	 */
	function printAuthor($val) {
		global $wgAuthorLink;

		if ($val == "") return "";

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$authors = explode(" and", $val);
		$rv = "";
		foreach ($authors as $a) {
			$a = trim($a);
			if ($a != "others" and $a != "") {
				$as = $a;
    			$as = str_replace("~", " ", $as);
    			$as_parts = bwParseAuthor(bwHTMLDecode($as));
    			$as = str_replace("{", "", $as);
    			$as = str_replace("}", "", $as);

	    		$no_utf8_as = urlencode($as);
	    		$utf8_as = urlencode($as);
	    		if (!empty($wgAuthorLink) and $wgAuthorLink != "") {
					$href = str_replace('$author', $no_utf8_as, $wgAuthorLink);
					$href = str_replace('$utf8_author', $utf8_as, $href);
					$href = str_replace('$firstname_initial', $as_parts["firstname_initial"], $href);
					$href = str_replace('$firstnames_initials', $as_parts["firstnames_initials"], $href);
					$href = str_replace('$firstname_normalized', $as_parts["firstname_simplified"], $href);
					$href = str_replace('$firstnames_normalized', $as_parts["firstnames_simplified"], $href);
					$href = str_replace('$middlepart_normalized', $as_parts["middlepart_simplified"], $href);
					$href = str_replace('$surname_normalized', $as_parts["surname_simplified"], $href);
					$href = str_replace('$firstname', $as_parts["firstname"], $href);
					$href = str_replace('$middlepart', $as_parts["middlepart"], $href);
					$href = str_replace('$surname', $as_parts["surname"], $href);
		    		$href = bwRemoveHTML($href);
			    	$rv_a = '<a href="'.$href.'" target="aleph">'.$a.'</a>';
			    }
			    else
			    	$rv_a = $a;
		    	if ($rv != "") $rv .= ", ";
		    	$rv .= $rv_a;
			}
			elseif ($a == "others") {
				$rv .= " ".wfMsg("bibwiki_etal");
			}
		}
    	return $rv;
	}

	/**
	 * @param  string
	 * @return string
	 */
	function makeTitleLink($val) {
		global $wgTitleLink;

		if ($val == "") return;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$len = strlen($val);
		$val_with_reduced_spaces = preg_replace("/\s+/", " ", $val);
   		$val_with_reduced_spaces = bwRemoveHTML($val_with_reduced_spaces);
		$as = urlencode(bwHTMLDecode($val_with_reduced_spaces));
		$utf8_as = urlencode(bwHTMLDecode($val_with_reduced_spaces));

		if (!empty($wgTitleLink) and $wgTitleLink != "") {
			$href = str_replace('$title', $as, $wgTitleLink);
			$href = str_replace('$utf8_title', $utf8_as, $href);
	    	return '<a href="'.$href.'" target="aleph">'.$val.'</a>';
		}
		return $val;
	}

	function printNote() {
		return $this->concat("<i>", $this->addPunctuation($this->val("note"), "."), "</i>");
	}

	/**
	 * @return string
	 */
	function printBook() {
		return

			$this->implode(" ",
				array(

					# Author Year:

					$this->concat("",
						$this->implode(" ",
							array(
								$this->bwFirstOf(array(
									$this->printAuthor($this->val("author")),
									$this->concat("", $this->printAuthor($this->val("editor")), " ".wfMsg("bibwiki_editors")),
								)),
								#$this->concat("(", $this->mBibitem->getPrettyValByKey("year"), ")")
								$this->mBibitem->getPrettyValByKey("year")
							)
						),
						":"
					),

					# Title.

					$this->bwFirstOf(array(
						$this->implode(" ",
							array(
								$this->addPunctuation($this->makeTitleLink($this->val("title"))),
								$this->addPunctuation($this->val("titleaddon"))
							)
						),

						$this->implode(" ",
							array(
								$this->addPunctuation($this->makeTitleLink($this->val("booktitle"))),
								$this->addPunctuation($this->val("booktitleaddon"))
							)
						)
					)),

					# Adress: Publisher.

					$this->addPunctuation (
						$this->implode(": ",
							array(
								$this->val("address"),
								$this->val("publisher")
							)
						),
						"."
					),

					$this->printNote()
				)
			);
	}

	/**
	 * @return string
	 */
	function printJournalVolNrPages() {
		$jour = $this->val("journal");
		$volnr = $this->implode("/",
			array(
				$this->val("volume"),
				$this->val("number")
			)
		);
		#$pages = $this->concat(wfMsg("bibwiki_page"), $this->val("pages"), "");
		$pages = $this->val("pages");

		if ($jour != "" and $volnr != "" and $pages != "")
			return $this->implode(", ",
				array(
					$this->implode(" ", array($jour, $volnr)),
					$pages
				)
			);
		elseif ($jour != "" and $volnr == "" and $pages != "")
			return $this->implode(", ", array($jour, $pages));
		else
			return $jour;
	}

	/**
	 * @return string
	 */
	function printArticle() {
		return
			$this->implode(" ",
				array(

					# Author Year:

					$this->concat("",
						$this->implode(" ",
							array(
								$this->printAuthor($this->val("author")),
								#$this->concat("(", $this->mBibitem->getPrettyValByKey("year"), ")")
								$this->mBibitem->getPrettyValByKey("year")
							)
						),
						":"
					),

					# Title.

					$this->implode(" ",
						array(
							$this->addPunctuation($this->makeTitleLink($this->val("title"))),
							$this->addPunctuation($this->val("titleaddon"))
						)
					),

					# Journal (Vol/Nr) Pages

					$this->addPunctuation (
						$this->printJournalVolNrPages(), "."
					),

					$this->printNote()
				)
			);
	}

	/**
	 * @return string
	 */
	function printCollection() {
		if ($this->val("crossref") != "") {
			return '<a href="'.Bibliography::getLocalURL(
				array(
					"view=compact",
					"startkey=".$this->val("crossref"),
					$this->mBibfileQuery
				)
			).'">'.$this->val("crossref").'</a>';
		}
		else {
			return $this->implode(" ",
				array(

					# Author:

					$this->concat("", $this->printAuthor($this->val("editor")), " ".wfMsg("bibwiki_editors").":"),

					# Title.

					$this->implode(" ",
						array(
							$this->addPunctuation($this->makeTitleLink($this->val("booktitle"))),
							$this->addPunctuation($this->val("booktitleaddon"))
						)
					),

					# Adress: Publisher.

					$this->addPunctuation (
						$this->implode(": ",
							array(
								$this->val("address"),
								$this->val("publisher")
							)
						),
						"."
					)
				)
			);
		}
	}

	/**
	 * @return string
	 */
	function printIncollection() {
		return

			$this->implode(" ",
				array(

					# Author Year:

					$this->concat("",
						$this->implode(" ",
							array(
								$this->printAuthor($this->val("author")),
								#$this->concat("(", $this->mBibitem->getPrettyValByKey("year"), ")")
								$this->mBibitem->getPrettyValByKey("year")
							)
						),
						":"
					),

					# Title.

					$this->implode(" ",
						array(
							$this->addPunctuation($this->makeTitleLink($this->val("title"))),
							$this->addPunctuation($this->val("titleaddon"))
						)
					),

					$this->addPunctuation (
						$this->concat(
							wfMsg("bibwiki_in").": ",

							$this->implode(", ", array(
								$this->printCollection(),
#								$this->concat(wfMsg("bibwiki_page"),$this->val("pages"),"")
								$this->val("pages")
							)), ""
						), "."
					),

					$this->printNote()
				)
			);
	}

	/**
	 * @return string
	 */
	function printInbook() {
		return

			$this->implode(" ",
				array(

					# Author Year:

					$this->concat("",
						$this->implode(" ",
							array(
								$this->printAuthor($this->val("author")),
								#$this->concat("(", $this->mBibitem->getPrettyValByKey("year"), ")")
								$this->mBibitem->getPrettyValByKey("year")
							)
						),
						":"
					),

					# Title.

					$this->implode(" ",
						array(
							$this->addPunctuation($this->makeTitleLink($this->val("title"))),
							$this->addPunctuation($this->val("titleaddon")),
							$this->addPunctuation($this->val("chapter")),
						)
					),

					$this->addPunctuation (
						$this->concat(
							wfMsg("bibwiki_in").": ",

							$this->implode(", ", array(
								$this->printCollection(),
#								$this->concat(wfMsg("bibwiki_page"),$this->val("pages"),"")
								$this->val("pages")
							)), ""
						),
						"."
					),

					$this->printNote()
				)
			);
	}

	/**
	 * @return string
	 */
	function printMisc() {

		return

		$this->implode(" ",
			array(

				# Author Year:

				$this->concat("",
					$this->implode(" ",
						array(
							$this->bwFirstOf(array(
								$this->printAuthor($this->val("author")),
								$this->concat("", $this->printAuthor($this->val("editor")), " ".wfMsg("bibwiki_editors")),
								$this->val("organization"),
								$this->val("institution")
							)),
							$this->concat("(", $this->mBibitem->getPrettyValByKey("year"), ")")
						)
					),
					":"
				),

				# Title.

				$this->bwFirstOf(array(
					$this->implode(" ",
						array(
							$this->addPunctuation($this->makeTitleLink($this->val("title"))),
							$this->addPunctuation($this->val("titleaddon"))
						)
					),

					$this->implode(" ",
						array(
							$this->addPunctuation($this->makeTitleLink($this->val("booktitle"))),
							$this->addPunctuation($this->val("booktitleaddon"))
						)
					)
				)),

				# Publisher, Adress.

				$this->addPunctuation (
					$this->implode(": ",
						array(
							$this->val("address"),
							$this->bwFirstOf(
								array(
									$this->val("publisher"),
									$this->val("school"),
									$this->val("institution")
								)
							)
						)
					),
					"."
				),

				$this->addPunctuation($this->val("howpublished"), "."),

				$this->printNote()
			)
		);
	}

}

?>
