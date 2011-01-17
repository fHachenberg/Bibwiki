<?php
if (!defined('MEDIAWIKI'))
	die();

/**
 * Misc - miscellaneous functions
 *
 * @addtogroup Extensions
 * @package Bibwiki
 *
 * @author Wolfgang Plaschg <wpl@gmx.net>
 * @copyright Copyright (C) 2007 Wolfgang Plaschg
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
 
include_once(dirname(__FILE__)."/TeXToHTMLConverter.php");

function bwStrEqual($str1, $str2) {
	return $str1 == $str2;
	#return (mb_strtolower(trim($str1)) == mb_strtolower(trim($str2)));
}

function bwMakePath($dir, $file) {
	if (substr($dir, -1) != DIRECTORY_SEPARATOR)
		return $dir.DIRECTORY_SEPARATOR.$file;
	else
		return $dir.$file;
}

function bwCiteKeyExists($key, $f) {
	$f_in = fopen($f,'r');
	$rv = false;
	while (!feof($f_in)) {
		$s = fgets($f_in);
		if (preg_match("/^\s*@\s*\w+\s*[({]{1,1}(.*),\s*$/", $s, $matches)) {
			if (strtolower($matches[1]) == strtolower($key)) {
				$rv = true;
				break;
			}
		}
	}
	fclose($f_in);
	return $rv;
}

function bwUmlautsSimplify($val) {
	$rep = array(
		"ä" => "ae",
		"ö" => "oe",
		"ü" => "ue",
		"Ä" => "Ae",
		"Ö" => "Oe",
		"Ü" => "Ue",
		"ß" => "ss",
	);	
	return str_replace(array_keys($rep), array_values($rep), $val);
}

function bwHTMLDecode($val) {
	$val = html_entity_decode($val);
	$rep = array(
		"&ndash;" => "-",
		"&mdash;" => "-",
	);	
	return str_replace(array_keys($rep), array_values($rep), $val);
}


/**
 * @todo rewrite
 */
function bwDiacriticsSimplify($val) {
	$rep = array(
		"ä" => "a",
		"ö" => "o",
		"ü" => "u",
		"Ä" => "A",
		"Ö" => "O",
		"Ü" => "U",
		"ß" => "ss",
		"á" => "a",
		"é" => "e",
		"í" => "i",
		"ó" => "o",
		"ú" => "u",
		"Á" => "A",
		"É" => "E",
		"Í" => "I",
		"Ó" => "O",
		"Ú" => "U",
		"à" => "a",
		"è" => "e",
		"ì" => "i",
		"ò" => "o",
		"ù" => "u",
		"À" => "A",
		"È" => "E",
		"Ì" => "I",
		"Ò" => "O",
		"Ù" => "U",
		"â" => "a",
		"ê" => "e",
		"î" => "i",
		"ô" => "o",
		"û" => "u",
		"Â" => "A",
		"Ê" => "E",
		"Î" => "I",
		"Ô" => "O",
		"Û" => "U",
		"Æ" => "AE",
		"Ç" => "C",
		"Ë" => "E",
		"Ï" => "I",
		"Ð" => "D",
		"Ñ" => "N",
		"Õ" => "O",
		"Ø" => "O",
		"Ý" => "Y",
		"ã" => "a",
		"å" => "a",
		"æ" => "ae",
		"ç" => "c",
		"ë" => "e",
		"ð" => "o",
		"ñ" => "n",
		"õ" => "o",
		"ø" => "o",
		"ý" => "y",
		"ÿ" => "y",
		"Ā" => "A",
		"ā" => "a",
		"ă" => "a",
		"Ă" => "A",
		"Ą" => "A",
		"ą" => "a",
		"Ć" => "C",
		"ć" => "c",
		"Ĉ" => "C",
		"ĉ" => "c",
		"Ċ" => "C",
		"ċ" => "c",
		"Č" => "C",
		"č" => "c",
		"Ď" => "D",
		"ď" => "d",
		"Đ" => "D",
		"đ" => "d",
		"Ē" => "E",
		"ē" => "e",
		"Ĕ" => "E",
		"ĕ" => "e",
		"Ė" => "E",
		"ė" => "e",
		"Ę" => "E",
		"ę" => "e",
		"Ě" => "E",
		"ě" => "e",
		"Ĝ" => "G",
		"ĝ" => "g",
		"Ğ" => "G",
		"ğ" => "g",
		"Ġ" => "G",
		"ġ" => "g",
		"Ģ" => "G",
		"ģ" => "g",
		"Ĥ" => "H",
		"ĥ" => "h",
		"Ħ" => "H",
		"ħ" => "h",
		"Ĩ" => "I",
		"ĩ" => "i",
		"Ī" => "I",
		"ī" => "i",
		"Ĭ" => "I",
		"ĭ" => "i",
		"Į" => "I",
		"į" => "i",
		"İ" => "I",
		"ı" => "i",
		"Ĳ" => "IJ",
		"ĳ" => "ij",
		"Ĵ" => "J",
		"ĵ" => "j",
		"Ķ" => "K",
		"ķ" => "k",
		"ĸ" => "k",
		"Ĺ" => "L",
		"ĺ" => "l",
		"Ļ" => "L",
		"ļ" => "l",
		"Ľ" => "L",
		"ľ" => "l",
		"Ŀ" => "L",
		"ŀ" => "l",
		"Ł" => "L",
		"ł" => "l",
		"Ń" => "N",
		"ń" => "n",
		"Ņ" => "N",
		"ņ" => "n",
		"Ň" => "N",
		"ň" => "n",
		"ŉ" => "n",
		"Ŋ" => "NJ",
		"ŋ" => "nj",
		"Ō" => "O",
		"ō" => "o",
		"Ŏ" => "O",
		"ŏ" => "o",
		"Ő" => "O",
		"ő" => "o",
		"Œ" => "OE",
		"œ" => "oe",
		"Ŕ" => "R",
		"ŕ" => "r",
		"Ŗ" => "R",
		"ŗ" => "r",
		"Ř" => "R",
		"ř" => "r",
		"Ś" => "S",
		"ś" => "s",
		"Ŝ" => "S",
		"ŝ" => "s",
		"Ş" => "S",
		"ş" => "s",
		"Š" => "S",
		"š" => "s",
		"Ţ" => "T",
		"ţ" => "t",
		"Ť" => "T",
		"ť" => "t",
		"Ŧ" => "T",
		"ŧ" => "t",
		"Ũ" => "U",
		"ũ" => "u",
		"Ū" => "U",
		"ū" => "u",
		"Ŭ" => "U",
		"ŭ" => "u",
		"Ů" => "U",
		"ů" => "u",
		"Ű" => "U",
		"ű" => "u",
		"Ų" => "U",
		"ų" => "u",
		"Ŵ" => "W",
		"ŵ" => "w",
		"Ŷ" => "Y",
		"ŷ" => "y",
		"Ÿ" => "Y",
		"Ź" => "Z",
		"ź" => "z",
		"Ż" => "Z",
		"ż" => "z",
		"Ž" => "Z",
		"ž" => "z",
		"ſ" => "f",
		"Ə" => "e",
		"ƒ" => "f",
		"Ơ" => "O",
		"ơ" => "o",
		"Ư" => "U",
		"ư" => "u",
		"Ǎ" => "A",
		"ǎ" => "a",
		"Ǐ" => "I",
		"ǐ" => "i",
		"Ǒ" => "O",
		"ǒ" => "o",
		"Ǔ" => "U",
		"ǔ" => "u",
		"Ǖ" => "U",
		"ǖ" => "u",
		"Ǘ" => "U",
		"ǘ" => "u",
		"Ǚ" => "U",
		"ǚ" => "u",
		"Ǜ" => "U",
		"ǜ" => "u",
		"Ǻ" => "A",
		"ǻ" => "a",
		"Ǽ" => "AE",
		"ǽ" => "ae",
		"Ǿ" => "O",
		"ǿ" => "o",
		"ə" => "e"
		# @todo add more...
	);	
	return str_replace(array_keys($rep), array_values($rep), $val);
}

function bwUtf8ToTeX($val) {
	$rep = array(
		"ä" => '{\"a}',
		"ö" => '{\"o}',
		"ü" => '{\"u}',
		"Ä" => '{\"A}',
		"Ö" => '{\"O}',
		"Ü" => '{\"U}',
		"Ë" => '{\"E}',
		"Ï" => '{\"I}',
		"ë" => '{\"e}',
		"ï" => '{\"i}',
		"ÿ" => '{\"y}',
		"Ÿ" => '{\"Y}',

		"ß" => '{\ss}',
	
		"á" => "{\\'a}",
		"é" => "{\\'e}",
		"í" => "{\\'i}",
		"ó" => "{\\'o}",
		"ú" => "{\\'u}",
		"Á" => "{\\'A}",
		"É" => "{\\'E}",
		"Í" => "{\\'I}",
		"Ó" => "{\\'O}",
		"Ú" => "{\\'U}",
		"Ý" => "{\\'Y}",
		"ý" => "{\\'y}",
		"Ć" => "{\\'C}",
		"ć" => "{\\'c}",
		"Ĺ" => "{\\'L}",
		"ĺ" => "{\\'l}",
		"Ń" => "{\\'N}",
		"ń" => "{\\'n}",
		"Ŕ" => "{\\'R}",
		"ŕ" => "{\\'r}",
		"Ś" => "{\\'S}",
		"ś" => "{\\'s}",
		"Ź" => "{\\'Z}",
		"ź" => "{\\'z}",
	
		"à" => "{\\`a}",
		"è" => "{\\`e}",
		"ì" => "{\\`i}",
		"ò" => "{\\`o}",
		"ù" => "{\\`u}",
		"À" => "{\\`A}",
		"È" => "{\\`E}",
		"Ì" => "{\\`I}",
		"Ò" => "{\\`O}",
		"Ù" => "{\\`U}",
	
		"â" => "{\\^a}",
		"ê" => "{\\^e}",
		"î" => "{\\^i}",
		"ô" => "{\\^o}",
		"û" => "{\\^u}",
		"Â" => "{\\^A}",
		"Ê" => "{\\^E}",
		"Î" => "{\\^I}",
		"Ô" => "{\\^O}",
		"Û" => "{\\^U}",
		"Ĉ" => "{\\^C}",
		"ĉ" => "{\\^c}",
		"Ĝ" => "{\\^G}",
		"ĝ" => "{\\^g}",
		"Ĥ" => "{\\^H}",
		"ĥ" => "{\\^h}",
		"Ĵ" => "{\\^J}",
		"ĵ" => "{\\^j}",
		"Ŝ" => "{\\^S}",
		"ŝ" => "{\\^s}",
		"Ŵ" => "{\\^W}",
		"ŵ" => "{\\^w}",
		"Ŷ" => "{\\^Y}",
		"ŷ" => "{\\^y}",

		"»" => '\frqq{}',
		"«" => '\flqq{}',
		"›" => '\frq{}',
		"‹" => '\flq{}',
		"“" => '\textquotedblleft{}',
		"”" => '\textquotedblright{}',
		"‘" => '\textquoteleft{}',
		"’" => '\textquoteright{}',
		"~" => '\widetilde{}',
		"–" => '--',

		/**
		 * @todo add more
		 */
	);
	# _ => \_
	$val = preg_replace('/([^\\\]{1,1})_/', '\1\\_', $val);
	# % => \%
	$val = preg_replace('/([^\\\]{1,1})%/', '\1\\%', $val);
	# & => \&
	$val = preg_replace('/([^\\\]{1,1})&/', '\1\\&', $val);
	return str_replace(array_keys($rep), array_values($rep), $val);
}

function bwFirstOf($strs) {
	if (!is_array($strs)) return $str;
	foreach ($strs as $str) {
		if (!empty($str) and $str != "") return $str;
	}
	return "";
}

function bwUserIsSysop() {
	global $wgUser;
	
	$groups = $wgUser->getGroups();
	foreach ($groups as $g) {
		if ($g=="sysop") return true;
	}
	return false;
}

function bwUserIsBureaucrat() {
	global $wgUser, $wgRestrictEditsToBureaucrats;

	$groups = $wgUser->getGroups();
	foreach ($groups as $g) {
		if ($g=="bureaucrat") return true;
		if ($g=="sysop") return true;
	}
	return false;
}

/**
 * Splits a BibTeX name in its pieces.
 *
 * <code>
 * "Wolfgang F. Plaschg" --> |Wolfgang| |F.| |Plaschg|
 * "Wolfgang {de la} Plaschg" --> |Wolfgang| |de la| |Plaschg|
 * </code>
 */
function bwSplitName($val) {
	$rv = array();
	if (strpos($val, "{") === false)
		return preg_split("/\s+/", trim($val));
	else {
		while (strpos($val, "{") !== false and 
		       strpos($val, "}") > strpos($val, "{")) {
			$pos = strpos($val, "{");
			$part = substr($val, 0, $pos);
			$val = substr($val, $pos, strlen($val)-$pos);
			$pos = strpos($val, "}");
			$part2 = substr($val, 1, $pos-1);
			$val = substr($val, $pos+1, strlen($val)-$pos);
			
			$tmp = preg_split("/\s+/", trim($part));
			foreach($tmp as $t) if (empty($t) == false) $rv[] = $t;
			$rv[] = $part2;
		}
		$tmp = preg_split("/\s+/", trim($val));
		foreach($tmp as $t) if (empty($t) == false) $rv[] = $t;
	}
	return $rv;
}

function bwIsUpper($val) {
	if (preg_match("/[A-ZÄÖÜÁÉÍÓÚÀÂÃÅÇËÌÍÎÏÐÑÒÔÕÖØÙÚÛÜÝŒŠŸ]+/", $val[0])) return true;
	return false;
}

/**
 *  Parses the elements of a BibTeX name.
 *
 *	Structure of returned array:
 *  <code>
 *  array(
 *      "firstname" => 	           First christian name
 *	    "firstname_initial" =>     Initial of the first christian name ("Danyé Ben Rubín" => "D")
 *	    "firstname_simplified" =>  Simplified first christian name ("Danyé Ben Rubín" => "Danye")
 * 	    "firstnames" =>            All christian names ("Danyé Ben Rubín" => "Danyé Ben")
 * 	    "firstnames_simplified" => All simplified christian names ("Danyé Ben Rubín" => "Danye Ben")
 *	    "firstnames_initials" =>   Initials of the christian names ("Danyé Ben Rubín" => "DB")
 *	    "middlepart" =>            Middle part of the name
 *	    "middlepart_simplified" => Simplified middle part
 *	    "surname" =>               Surname ("Danyé Ben Rubín" => "Rubín")
 *	    "surname_simplified" =>    Simplified name ("Danyé Ben Rubín" => "Rubin")
 *  )
 *  </code>
 *
 *  @param string
 *  @return array
 */
function bwParseAuthor($val) {

	#print ("\n\n".$val."<br>\n");
	#print ("\n\n".bwDiacriticsSimplify($val)."<br>\n");

	$rv = array();
	$val = trim($val);
	$rv["firstname"] = "";
	$rv["firstname_simplified"] = "";
	$rv["firstname_initial"] = "";
	$rv["firstnames"] = "";
	$rv["firstnames_simplified"] = "";
	$rv["firstnames_initials"] = "";
	$rv["middlepart"] = "";
	$rv["middlepart_simplified"] = "";
	$rv["surname"] = "";
	$rv["surname_simplified"] = "";

	if (strpos($val, ",") !== false) {
		$parts = explode(",", $val, 2);
		$rv["surname"] = trim($parts[0]);
		$rv["surname"] = str_replace("{", "", $rv["surname"]);
		$rv["surname"] = str_replace("}", "", $rv["surname"]);
		$parts = bwSplitName($parts[1]);
		foreach($parts as $p) {
			if (bwIsUpper($p)) {
				if ($rv["firstname"] == "") {
					$rv["firstname"] = $p;
					$rv["firstname_initial"] = $p[0];
				}
				if ($rv["firstnames"] != "") $rv["firstnames"] .= " ";
				$rv["firstnames"] .= $p;
				$rv["firstnames_initials"] .= $p[0];
			}
			else {
				if ($rv["middlepart"] != "") $rv["middlepart"] .= " ";
				$rv["middlepart"] .= $p;
			}
		}
	} else {
		$parts = bwSplitName($val);
		$last_part = array_pop($parts);
		$in_middlepart = false;
		$middlepart_done = false;

		foreach($parts as $p) {
			if (bwIsUpper($p)) {
				if ($in_middlepart) {
					$in_middlepart = false;
					$middlepart_done = true;
				}
				if ($middlepart_done) {
					if ($rv["surname"] != "") $rv["surname"] .= " ";
					$rv["surname"] .= $p;
				} else {
					if ($rv["firstname"] == "") {
						$rv["firstname"] = $p;
						$rv["firstname_initial"] = $p[0];
					}
					if ($rv["firstnames"] != "") $rv["firstnames"] .= " ";
					$rv["firstnames"] .= $p;
					$rv["firstnames_initials"] .= $p[0];
				}
			}
			else {
				if ($middlepart_done) {
					if ($rv["surname"] != "") $rv["surname"] .= " ";
					$rv["surname"] .= $p;
				}
				else {
					if ($rv["middlepart"] != "") $rv["middlepart"] .= " ";
					$rv["middlepart"] .= $p;
					$in_middlepart = true;
				}
			}
		}
		if ($rv["surname"] != "") $rv["surname"] .= " ";
		$rv["surname"] .= $last_part;
	}
	
	$rv["surname_simplified"] = bwDiacriticsSimplify($rv["surname"]);
	$rv["firstname_simplified"] = bwDiacriticsSimplify($rv["firstname"]);
	$rv["firstnames_simplified"] = bwDiacriticsSimplify($rv["firstnames"]);
	#we have to check whether a middle name was provided in the bibtex entry
	if(array_key_exists("middlename", $rv))
		$rv["middlename_simplified"] = bwDiacriticsSimplify($rv["middlename"]);
	else
		$rv["middlename_simplified"] = "";

	#print (htmlentities($rv["surname"])."<br>");
	#print ($rv["surname_simplified"]."<br>");
	return $rv;
}

function bwTeXToHTML($string) {
	$converter = new TeXToHTMLConverter;
	return $converter->convert($string);
}

/**
 * @todo Integrate this function into Bibfile.
 */
function bwGenerateSurname($val) {
	$val = bwDiacriticsSimplify(bwTeXToHTML($val));
	$etal = "";
	$val = explode(" and ", $val);
	if (count($val) > 1) $etal = ".etal";
	$val = $val[0];
	if (strpos($val, ",") > 0) {
		$val = substr($val, 0, strpos($val, ","));
		return trim($val).$etal;
	} else {
		$val = explode(" ", $val);
		return trim($val[count($val)-1]).$etal;
	}
}

/**
 * @todo Integrate this function into Bibfile.
 */
function bwGenerateSurnameForDocname($val) {
	$val = bwDiacriticsSimplify($val);
	$etal = "";
	$val = explode(" and ", $val);
	if (count($val) > 1) $etal = " et al";
	$val = $val[0];
	if (strpos($val, ",") > 0) {
		$val = substr($val, 0, strpos($val, ","));
		return trim($val).$etal;
	} else {
		$val = explode(" ", $val);
		return trim($val[count($val)-1]).$etal;
	}
}

/**
 * @todo Integrate this function into Bibfile.
 */
function bwGenerateKey($text, $file) {
	$lines = explode("\n", $text);
	$name = "";
	$year = "";
	foreach ($lines as $l) {
		if (strpos($l, "=") > 0) {
			$parts = explode("=", $l, 2);
			if (strtolower(trim($parts[0])) == "author")
				$name = bwGenerateSurname(trim($parts[1], " \"{},\n\r"));
			elseif ($name == "" and strtolower(trim($parts[0])) == "editor")
				$name = bwGenerateSurname(trim($parts[1], " \"{},\n\r"));
			elseif (strtolower(trim($parts[0])) == "year")
				$year = trim($parts[1], " \"{},\n\r");
			elseif ($year == "" and strtolower(trim($parts[0])) == "crossref")
			{
				preg_match("/\d{2,4}/", $parts[1], $dummy);
				$year = $dummy[0];
				if ($name == "") {
					$dummy = explode(":", $parts[1],2);
					$name = trim($dummy[0], "\" ");
				}
			}
		}
	}
	if ($year == "" or
		$year == "???" or
		strtolower($year) == "xxx" or
		strtolower($year) == "xyz" or
		strtolower($year) == "xy") {
		$now = getdate();
		$year = sprintf("%04d", $now[year]);
	}
	if ($name == "") 
		$name = "Unknown";
	$add = "";
	while (bwCiteKeyExists($name.":".$year.$add, $file)) {
		if ($add == "")
			$add = "b";
		else
			$add = chr(ord($add)+1);
	};
	return $name.":".$year.$add;
}


function bwGetPublicBibfiles() {
	global $wgBibPath;

	#Load Settings
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

	$bibcount = 0;
	$rv = array();
	$d = dir($wgBibPath);
	if (empty($d) == false) {
		while (false !== ($f = $d->read())) {
		    if (preg_match('/\.bib$/',$f)) {
		    	$rv[] = $f;
		    }
		}
		$d->close();
	}
	return $rv;
}


function bwGetPrivateBibfiles() {
	global $wgBibPath, $wgUser;

	#Load Settings
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

	$rv = array();
	if ($wgUser->isAnon()) return $rv;
	$d = @dir($wgBibPath.DIRECTORY_SEPARATOR.$wgUser->getName());
	if (empty($d) == false) {
		while (false !== ($f = $d->read())) {
		    if (preg_match('/\.bib$/',$f)) {
		    	$bibcount += 1;
		    	$rv[] = $f;
		    }
		}	
		$d->close();
	}
	return $rv;
}

function bwGenerateDocname($text) {
	global $wgMaxDocnameTitleLength;

	#Load Settings
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

	$name = "";
	$title = "";
	$year = "";
	$docname = "";
	$lines = explode("\n", urldecode($text));

	foreach ($lines as $l) {
		if (strpos($l, "=") > 0) {
			$parts = explode("=", $l, 2);
			if (strtolower(trim($parts[0])) == "author")
				$name = bwGenerateSurnameForDocname(trim($parts[1], " \\\"'{},\n\r\t"));
			elseif (strtolower(trim($parts[0])) == "year")
				$year = trim($parts[1], " \\\"'{},\n\r\t");
			elseif (strtolower(trim($parts[0])) == "docname" or 
			        strtolower(trim($parts[0])) == "file" or 
			        strtolower(trim($parts[0])) == "pdf") {
				$docname = trim($parts[1], " \\\"'{},\n\r\t");
			}
			elseif (strtolower(trim($parts[0])) == "title") {
				$title = trim($parts[1], " \\\"'{},\n\r\t");
				if (strlen($title) > $wgMaxDocnameTitleLength) {
					$rpos = strrpos(substr($title, 0, $wgMaxDocnameTitleLength), ' ');
					if ($rpos === false) $rpos = $wgMaxDocnameTitleLength;
					$title = substr($title, 0, $rpos) . " etc";
				}
			}
			elseif ($year == "" and strtolower(trim($parts[0])) == "crossref")
			{
				$dummy = trim($parts[1], " \"{},\n\r");
				$dummy = explode(":", $dummy, 2);
				$year = $dummy[1];
			}
		}
	}
	if ($year == "" or
		$year=="???" or
		strtolower($year)=="xxx" or
		strtolower($year)=="xyz" or
		strtolower($year)=="xy") {
		$now = getdate();
		$year = sprintf("%04d", $now[year]);
	}
	if ($name == "") 
		$name = "Unknown";
	if ($name != "") $name .= " - ";
	if ($year != "") $year .= " - ";
	$docname = str_replace("<Author>", $name, $docname);
	$docname = str_replace("<Year>", $year, $docname);
	$docname = str_replace("<Title>", $title, $docname);
	$docname = str_replace("&lt;Author&gt;", $name, $docname);
	$docname = str_replace("&lt;Year&gt;", $year, $docname);
	$docname = str_replace("&lt;Title&gt;", $title, $docname);
	$docname = str_replace(":", "", $docname);
	$docname = str_replace("*", "", $docname);
	$docname = str_replace("?", "", $docname);
	$docname = str_replace("!", "", $docname);
	$docname = str_replace("/", "", $docname);
	$docname = str_replace("\\", "", $docname);
	$docname = str_replace("<", "", $docname);
	$docname = str_replace(">", "", $docname);

	$val = bwDiacriticsSimplify($val);
	
	return $docname;
}

function bwSearchBib($bibfile, $term) {
	global $wgScriptPath, $wgOut, $wgBibPath, $wgUser,
		$wgContLang;
	
	#Load Settings
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
	
	$terms = explode(" ", $term);
	$found = array();
	$found_preview = array();
	$found_title = "";
	$found_chap = "";
	$current_key = "";
	$titleprinted = 0;
	$result = "";
	$fullbibfile = bwMakePath($wgBibPath, $bibfile);
	$path_parts = pathinfo($fullbibfile);
	if ($in = fopen($fullbibfile, "r")) {
		$current_key = "";
		while (!feof($in)) {
			$s = fgets($in);
			$sz = trim($s);
			if (substr($sz, 0, 1) == "@") {
				if (stristr($sz, '@string') == false and 
				    stristr($sz, '@comment') == false and 
				    stristr($sz, '@preamble') == false) {
	
					$words = explode('{',$sz,2);
					$current_key = $words[1];
					$current_key = trim($current_key);
					$current_key = rtrim($current_key, ",");
					$found_title = "";
				}	
			}
			elseif (stripos($sz, "title") !== false) {
				$words = explode('=',$sz,2);
				if (strtolower(trim($words[0])) == "title" or
				    strtolower(trim($words[0])) == "booktitle") {
				    $found_title = trim(utf8_encode($words[1]));
				    if (substr($found_title, -1, 1) != ",")
				    {
				    	$found_title = trim($found_title, '"{}');
				    	$found_title = ", ".$found_title."...";
				    }
				    else
				    	$found_title = ", ".trim($found_title, '"{},');
				    if (strlen($found_title)+strlen($current_key) > 45) 
				    	$found_title = substr_replace($found_title, "...", 45-strlen($current_key));
			    }
			}
			elseif (stripos($sz, "chapter") !== false) {
				$words = explode('=',$sz,2);
				if (strtolower(trim($words[0])) == "chapter") {
				    $found_chap = trim(utf8_encode($words[1]));
				    if (substr($found_chap, -1, 1) != ",")
				    {
				    	$found_chap = trim($found_chap, '"{}');
				    	$found_chap = ", ".$found_chap."...";
				    }
				    else
				    	$found_chap = ", ".trim($found_chap, '"{},');
				    if (strlen($found_chap)+strlen($current_key) > 45) 
				    	$found_chap = substr_replace($found_chap, "...", 45-strlen($current_key));
			    }
			}
			elseif ($sz == "}" or $sz == "") {
				$found_all = true;
				foreach($terms as $t) {
					if (!isset($found[$t])) {
						$found_all = false;
						break;
					}
				}
				if ($found_all) {
					if ($titleprinted == 0) {
						$result .= "<h2>".wfMsg("bibwiki_search_h1_bib")." ".$bibfile."</h2>";
						$titleprinted = 1;
						$result .= "<ol>";
					}
					$result .= "<li>";
					$result .= $wgUser->getSkin()->makeKnownLink($wgContLang->specialPage("Bibliography"), utf8_encode($current_key).($found_title?$found_title:$found_chap), bwImplodeQuery(array("startkey=".$current_key, "f=".$bibfile, $actionquery, $bibquery))).'<br /><small>';
					foreach ($found_preview as $p) {
						$result .= $p."<br />\n";
					}
					$result .= "</small></li>";
				}
				$found = array();
				$found_preview = array();
				$current_key = "";
			}
			
			if ($current_key != "") {
				foreach($terms as $t) {
					$s = utf8_encode($s);
	      			if(strlen($t) > 0 and stripos($s, $t) !== false) {
	      				$pos = stripos($s, $t);
	      				if (stripos($s, $t) > 40)
	      					$preview = "...".substr($s, $pos-(40-strlen($t)));
	      				else
	      					$preview = $s;
	      				if (strlen($preview) > 55)
	      					$preview = substr_replace($preview, "...", 55);
	      				$pos = stripos($preview, $t);
	      				array_push($found_preview,substr_replace($preview, "<span class='searchmatch'>".substr($preview, $pos, strlen($t))."</span>", $pos, strlen($t)));
	      				$found[$t] = true;
	      				$num++;
					}
				}
			}
		}
		fclose($in);
		if ($titleprinted == 1) {
			$result .= "</ol>";
		}
	}
	else
		$result = $bibfile." konnte nicht ge&ouml;ffnet werden!";
	return $result;
}

function bwSearchPapers($path, $term, $pathurl) {
	global $wgOut;
	$result = "";
	$d = dir($path) or die($php_errormsg);
	while (false !== ($f = $d->read())) {
		if ($f != "." and $f != "..") {
			if (is_dir($path."\\".$f)) {
				$result .= bwSearchPapers($path."\\".$f, $term, $pathurl."/".utf8_encode($f));
			}
			
			$terms = explode(" ", $term);
			$found_all = true;
			foreach($terms as $t) {
			    if (stripos($f, $t) === false) {
			    	$found_all = false;
			    	break;
			    }
			}
			if ($found_all == true) {
				$result .= "<li>";
				$fname = substr_replace($f, "...", 45);
				$result .= '<a href="'.$pathurl.'/'.utf8_encode($f).'">'.utf8_encode($fname).'</a><br />';
				$result .= "</li>";
			}
		}
	}
	$d->close();
	return $result;
}

/**
 * Returns true if $string is valid UTF-8 and false otherwise.
 */
function bwIsUtf8($string) {
    // From http://w3.org/International/questions/qa-forms-utf-8.html
    return preg_match('%^(?:
          [\x09\x0A\x0D\x20-\x7E]            # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
    )*$%xs', $string);
}

function bwToUtf8($string) {
	if (!bwIsUtf8($string))
		return utf8_encode($string);
	return $string;
}

function bwUtf8ToHTML($string) {
	return htmlspecialchars($string);
}

function bwHTMLToUtf8($string) {
	return html_entity_decode($string, ENT_QUOTES, "UTF-8");
}

function bwUtf8ToASCII($string) {
	if (bwIsUtf8($string))
		return utf8_decode($string);
	return $string;
}

function bwRemoveHTML($string) {
	return preg_replace("/<.*>/U", "", $string);
}

function bwStrlenWithoutHTML($string) {
	$i = 0;
	$in_html = false;
	$rv = 0;
	while ($i < strlen($string))
	{
		if ($string[$i] == "<") $in_html = true;
		if (!$in_html) $rv++;
		if ($string[$i] == ">") $in_html = false;
		$i++;
	}
	return $rv;
}

?>
