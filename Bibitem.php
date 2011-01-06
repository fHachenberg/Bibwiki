<?php
#if (!defined('MEDIAWIKI'))
#	die();

/**
 * Bibitem
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
 * Class that represents a BibTeX record
 */
class Bibitem {

	/**
	 * The BibTeX source.
	 *
	 * @var string
	 * @access private;
	 */
	var $mRecord;

	/**
	 * The media type of the bibliographic record (eg. "Book").
	 * The type is stored in the same case as in the record.
	 *
	 * @var string
	 * @access private;
	 */
	var $mType;
	
	/**
	 * The key of the bibliographic record (eg. "Knuth:1999").
	 * The cite key is stored in the same case as in the record.
	 *
	 * @var string
	 * @access private;
	 */
	var $mCiteKey;
	
	/**
	 * The keys of the keynr=value pairs.
	 * 
	 * The keys in a bibitem aren't unique, so a counter
	 * $keynr is used for referrering to the key=value pairs.
	 * Thus, it stores duplications.
	 * <code>
	 * array(
	 *   0 => "author",
	 *   1 => "title",
	 *   2 => "adress",
	 *   3 => "publisher",
	 *   4 => "note",
	 *   5 => "note"
	 * );
	 * </code>
	 *
	 * @var array
	 * @access private;
	 */ 
	var $mKeys;  
	
	/**
	 * The Values of the key=value pairs.
	 * <code>
	 * array(
	 *   "author" => "Wolfgang Plaschg",
	 *   "title" => "Bibwiki documentation"
	 * );
	 * </code>
	 *
	 * @var array
	 * @access private;
	 */
	var $mValues;

	/**
	 * The values with replaced BibTeX string macros.
	 *
	 * @var array
	 * @access private;
	 */ 
	var $mReplacedValues;

	/**
	 * Stores the untouched line in keynr => value form.
	 *
	 * @var array
	 * @access private;
	 */ 
	var $mOrigValues;

	/**
	 * Stores the formattted line in keynr => value form.
	 * Pretty values are
	 * 1) TeX commands are transformed to HTML
	 * 2) BibTeX macros are expanded
	 * 3) Delimiters are removed
	 *
	 * @var array
	 * @access private;
	 */ 
	var $mPrettyValues;

	/**
	 * Stores the BibTeX string macros in key => value form.
	 *
	 * @var array
	 * @access private;
	 */ 
	var $mMacros; 
		
	/**
	 * Constructor.
	 */
	function Bibitem() {
		$this->mRecord = "";
		$this->mType = "";
		$this->mCiteKey = "";
		$this->mKeys = array();
		$this->mValues = array();
		$this->mReplacedValues = array();
		$this->mOrigValues = array();
		$this->mPrettyValues = array();
		$this->mMacros = array();
	}
	
	/**
	 * @param string $val Be sure to give a trimmed value!
	 * @return boolean
	 * @todo Rewrite for better checking: should return false in
	 * '{test} test {test}'
	 */
	static function hasDelimiters($val) {
		$delimstack = array();
		$i = 0; 
		$delimclosed = false;
		while ($i < strlen($val))
		{
			$beforechr = "";
			if ($i > 0) $beforechr = $val[$i-1];

			if ($val[$i] == '"' and $beforechr != "\\") {
				if (count($delimstack) > 0) {
					$lastdelim = array_pop($delimstack);
					if ($lastdelim == '"')
						$delimclosed = true;
					else {
						$delimstack[] = $lastdelim;
						$delimstack[] = '"';
					}
				}
				else {
					if ($delimclosed and count($delimstack) == 0) return false;
					$delimstack[] = '"';
				}
			}
			elseif ($val[$i] == '{' and $beforechr != "\\") {
				if ($delimclosed and count($delimstack) == 0) return false;
				$delimstack[] = '{';
			}
			elseif ($val[$i] == '}' and $beforechr != "\\") {
				if (count($delimstack) > 0) {
					$lastdelim = array_pop($delimstack);
					if ($lastdelim == '{')
						$delimclosed = true; 
					else
						$delimstack[] = $lastdelim;
				}
			}
			else {
				$delimclosed = false;
				if (count($delimstack) == 0) return false;
			}
			$i++;
		}
		
		if (count($delimstack) > 0) return null;
		return $delimclosed;
	}
	
	function getPrettyValForEditing($keynr) {
		if (!$this->hasVal($keynr)) return false;
		$val = $this->getVal($keynr);
		$val = trim($this->removeDelimiters($val));
		#$converter = new TeXToHTMLConverter(array("thFormatTeXMacrosForEditing"), array("thEditPostFormat"));
		#$this->mPrettyValues[$keynr] = $converter->convert($val);
		$this->mPrettyValues[$keynr] = $val;
		return $this->mPrettyValues[$keynr];
	}
	
	/**
	 * @param string
	 * @return string
	 */
	function formatForEditing() {
		$rv = "@".$this->mType."{".$this->mCiteKey.",\n";
		foreach($this->mKeys as $keynr => $key) {
			$delim = $this->getDelimiters($this->getVal($keynr));
			$rv .= $key . " = ". $delim["left"] . $this->getPrettyValForEditing($keynr) . $delim["right"] . ",\n";
		}
		$rv .= "}\n";
		return $rv;
	}

	/**
	 * @access private
	 * @param string $author
	 * @param string
	 */
	function getFirstSurname($author) {
		# this must be first
		$val = bwUmlautsSimplify($author);
		# this must be second
		$val = bwDiacriticsSimplify($val);
		$etal = "";
		$val = explode(" and ", $val);
		if (count($val) > 1) $etal = ".etal";
		
		$author = bwParseAuthor($val[0]);
		return $author["surname_simplified"].$etal;
	}

	/**
	 * @access private
	 * @param array $existing_keys
	 * @param string
	 */
	function generateCiteKey($existing_keys) {
		$name = "";
		$year = "";
		
		$author = $this->getPrettyValByKey("editor");
		if ($author == false or $author == "")
			$author = $this->getPrettyValByKey("author");
		if ($author == false or $author == "")
			$name = "Unknown";
		else
			$name = $this->getFirstSurname($author);
		if ($name == "")
			$name = "Unknown";

		$year = $this->getPrettyValByKey("year");
		if ($year == "" and $this->getPrettyValByKey("crossref") != "")
		{
			preg_match("/\d{2,4}/", $this->getPrettyValByKey("crossref"), $match);
			$year = $match[0];
			if ($name == "") {
				$dummy = explode(":", $parts[1],2);
				$name = trim($dummy[0], "\" ");
			}
		}

		if ($year == "" or $year == "???" or
			strtolower($year) == "xxx" or
			strtolower($year) == "xyz" or
			strtolower($year) == "xy") {
			$now = getdate();
			$year = sprintf("%04d", $now["year"]);
		}

		$add = "";
		while (in_array($name.":".$year.$add, $existing_keys)) {
			if ($add == "")
				$add = "b";
			elseif ($add == "z") {
				$year = $year."a";
				$add = "b";
			}
			else
				$add = chr(ord($add)+1);
		};
		return $name.":".$year.$add;
	}
	
	function formatDelimiters($key, $value) {
		global $wgValueDelimLeft, $wgValueDelimRight, $wgTitleDelimLeft,
			$wgTitleDelimRight, $wgConvertAnsiToTeX;
			
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
	        
		if ($wgConvertAnsiToTeX == true)
			$value = bwUtf8ToTeX($value);
		
	    if (!$this->hasDelimiters($value) or
	    	#don't touch anything with '#' in it
	        strpos($value, "#") !== false)
	    	return $value;
	    	
	    $key = strtolower(trim($key));
	    $value = trim($value);
	        
	    if (($key == "title" or
	         $key == "titleaddon" or
	         $key == "booktitle" or
	         $key == "booktitleaddon") and 
	         isset($wgTitleDelimLeft) and 
	         $wgTitleDelimLeft != "" and
	         isset($wgTitleDelimRight) and
	         $wgTitleDelimRight != "") {
		    $value = $this->removeDelimiters($value);
		    $value = trim($value);
	        return $wgTitleDelimLeft.$value.$wgTitleDelimRight;
	    }
	    elseif (isset($wgValueDelimLeft) and 
	         $wgValueDelimLeft != "" and
	         isset($wgValueDelimRight) and
	         $wgValueDelimRight != "") {
		    $value = $this->removeDelimiters($value);
		    $value = trim($value);
	        return $wgValueDelimLeft.$value.$wgValueDelimRight;
	    }
	    return $value;
	}
	
	function formatKeyValuePair($key, $value) {
		global $wgConvertAnsiToTeX;
			
		# Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
	        
		if ($wgConvertAnsiToTeX == true)
			$value = bwUtf8ToTeX($value);
		
		# don't touch user's delimiters
		# $value = $this->formatDelimiters($key, $value);
		$rv = sprintf("  %-12s = ", $key);
		$lines = explode("\n", $value);
		$linecnt = 1;
		foreach($lines as $l) {
			if ($linecnt == 1)
				$rv .= $l;
			else
				$rv .= "\n".sprintf("  %-13s   %s", $l);
			$linecnt++;
		}
		$rv .= ",\n";

		return $rv;
	}
	
	function formatCiteKey() {
		$this->mCiteKey = preg_replace('/[^\w\.\:\-\_]+/', "", $this->mCiteKey);
		return $this->mCiteKey;
	}
	
	/**
	 * @param array $existing_keys Array of existing keys in a bibfile.
	 * @return string
	 */
	function formatForWriting($existing_keys = array()) {
		if ($this->getCiteKey() == "*") {
			$this->mCiteKey = $this->generateCiteKey($existing_keys);
		}
		$rv = "@".$this->mType."{".$this->formatCiteKey().",\n";
		foreach($this->mKeys as $keynr => $key) {
			$value = trim($this->mValues[$keynr], " \n,");
			$rv .= $this->formatKeyValuePair($key, $value);
		}
		$rv .= "}\n";
		return $rv;
	}
	
	/**
	 * @param string
	 * @return string
	 * @todo Rewrite for better checking: should return false in
	 * '{test} test {test}'
	 */
	static function removeDelimiters($val) {
		$delims = Bibitem::getDelimiters($val);
		if (strlen($delims["left"]) > 0)
			$val = substr($val, strlen($delims["left"]), strlen($val)-2*strlen($delims["left"])); 
		return $val;
	}
	
	/**
	 * This function works like explode('#',$val) but has to take into account whether
	 * the character # is part of a string (i.e., is enclosed into "..." or {...} ) 
	 * or defines a string concatenation as in @string{ "x # x" # ss # {xx{x}x} }
	 * 
	 * @author Mark Grimshaw 2006
	 * @link http://bibliophile.sourceforge.net
	 */
	static function explodeString($val)
	{
		$openquote = $bracelevel = $i = $j = 0; 
		while ($i < strlen($val))
		{
			$beforechr = "";
			if ($i > 0) $beforechr = $val[$i-1];
			if ($val[$i] == '"' and $beforechr != "\\")
				$openquote = !$openquote;
			elseif ($val[$i] == '{' and $beforechr != "\\")
				$bracelevel++;
			elseif ($val[$i] == '}' and $beforechr != "\\")
				$bracelevel--;
			elseif ( $val[$i] == '#' && !$openquote && !$bracelevel )
			{
				$strings[] = substr($val,$j,$i-$j);
				$j=$i+1;
			}
			$i++;
		}
		$strings[] = substr($val,$j);
		return $strings;
	}
	
	/**
	 * Checks if the data is sane.
	 *
	 * @access private
	 * @param string
	 * @return boolean
	 */
	static function validate($text) {
		$len = strlen($text);
		if ($len > 20000) return false;       # refuse insanely huge records
		for ($i = 0; $i < $len; $i++) {
			if (substr($text, $i, 1) != "\n" and
				substr($text, $i, 1) != "\t" and 
				substr($text, $i, 1) != "\r" and 
				ord(substr($text, $i, 1)) < 32)
				return false;                 # refuse control characters
		}
		return true;
	}
	
	function correctUserErrors() {
		/*$lines = explode("\n", $this->mRecord);
		foreach($lines as $line) {
		}
		$this->mRecord = implode("\n", $lines);*/
		return true;
	}
	
	/**
	 * @param string
	 * @return boolean
	 */
	function set($bibtexrecord) {
		if ($this->validate($bibtexrecord) == false)
			return false;
		$this->mRecord = $bibtexrecord;
		$this->mType = "";
		$this->mCiteKey = "";
		$this->mKeys = array();
		$this->mValues = array();
		$this->mReplacedValues = array();
		$this->mOrigValues = array();
		$this->mPrettyValues = array();
		return true;
	}
	
	/**
	 * @param array
	 * @return void
	 */
	function setMacros($macros) {
		$this->mMacros = $macros;
		return true;
	}

	/**
	 * @param array
	 * @return void
	 */
	function addMacros($macros) {
		if (!is_array($macros)) return false;
		$this->mMacros = array_merge($this->mMacros, $macros);
		return true;
	}

	/**
	 * Replace the BibTeX string macros with their values in a string.
	 */
	function replaceMacros($str) {
		if (strpos($str, "#") !== false and !$this->hasDelimiters($str)) {
			$parts = $this->explodeString($str);
			$replacedparts = array();
			foreach ($parts as $part) {
				$part = trim($part);
				if (Bibitem::hasDelimiters($part))
					$replacedparts[] = $this->removeDelimiters($part);
				else {
					if (count($this->mMacros) > 0) {
						$lwrpart = mb_strtolower($part);
						foreach ($this->mMacros as $key => $val) {
							if (bwStrEqual($lwrpart, $key)) $part = $val;
						}
					}
					$replacedparts[] = $part;
				}
			}
			$str = join("", $replacedparts);
		}
		else if (count($this->mMacros) > 0) {
			$lwrstr = mb_strtolower($str);
			foreach ($this->mMacros as $key => $val) {
				if (bwStrEqual($lwrstr, $key)) return $val;
			}
		}
		return $str;
	}
	
	/**
	 * @return string
	 */
	function getSource() {
		return $this->mRecord;
	}
	
	function getKeynr($key) {
		$keylwr = mb_strtolower($key);
		for ($i=0; $i < count($this->mKeys); $i++) {
			if (bwStrEqual($this->mKeys[$i], $keylwr))
				return $i;
		}
		return false;
	}

	/**
	 * Returns an array with all keynrs for a key (eg. "isbn"). 
	 *
	 * A BibTeX record can contain several entries of a key (eg. several
	 * "file" attachments, so we need a function to address the all key=value
	 * pairs for a given key.
	 *
	 * @param string
	 * @return array The array with the keynrs. Can be empty, with one or 
	 *    more elements.
	 */
	function getAllKeynrs($key) {
		$rv = array();
		if (is_array($key)) {
			foreach($key as $k) {
				$k = mb_strtolower($k);
				for ($i=0; $i < count($this->mKeys); $i++) {
					if (bwStrEqual($this->mKeys[$i], $k)) $rv[] = $i;
				}
			}
		}
		else {
			$key = mb_strtolower($key);
			for ($i=0; $i < count($this->mKeys); $i++) {
				if (bwStrEqual($this->mKeys[$i], $key)) $rv[] = $i;
			}
		}
		return $rv;
	}

	/**
	 * @param int
	 * @return boolean
	 */
	function hasVal($keynr) {
		return isset($this->mValues[$keynr]);
	}

	/**
	 * @param int
	 * @return string
	 */
	function getKey($keynr) {
		if (!$this->hasVal($keynr)) return false;
		return $this->mKeys[$keynr];
	}

	/**
	 * @param int
	 * @return string
	 */
	function getVal($keynr) {
		if (!$this->hasVal($keynr)) return false;
		return $this->mValues[$keynr];
	}

	/**
	 * @param string
	 * @return string
	 */
	function getValByKey($key) {
		$keynr = $this->getKeynr($key);
		if ($keynr === false) return false;
		return $this->getVal($keynr);
	}

	/**
	 * @param int
	 * @return string
	 */
	function getOrigVal($keynr) {
		if (!$this->hasVal($keynr)) return false;
		return $this->mOrigValues[$keynr];
	}

	/**
	 * @param int
	 * @return string
	 */
	function getOrigValByKey($key) {
		$keynr = $this->getKeynr($key);
		if ($keynr === false) return false;
		return $this->getOrigVal($keynr); 
	}

	/**
	 * Remove delimiters ("..." or {...}), replace string macros and
	 * replace TeX commands by UTF8 characters.
	 *
	 * @param int $keynr 
	 * @return string
	 */
	function getPrettyVal($keynr) {
		global $wgConvertAnsiToTeX;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		if (!$this->hasVal($keynr)) return false;
		if (isset($this->mPrettyValues[$keynr]))
			return $this->mPrettyValues[$keynr];
		if (!isset($this->mReplacedValues[$keynr]))
	  		$this->mReplacedValues[$keynr] = $this->replaceMacros($this->getVal($keynr));
		$val = trim($this->mReplacedValues[$keynr]);
		$val = trim($this->removeDelimiters($val));
		if ($wgConvertAnsiToTeX == true)
			$this->mPrettyValues[$keynr] = bwTeXToHTML($val);
		else
			$this->mPrettyValues[$keynr] = $val;
		return $this->mPrettyValues[$keynr];
	}
	
	/**
	 * @param string $val trimmed value
	 */
	static function getDelimiters($val) {
		$rv = array("left" => "", "right" => "");

		$delimstack = array();
		$isdelim = array();
		$leftdelim = array();
		$rightdelim = array();
		$i = 0; 
		$delimclosed = false;
		$delimstate = false;
		$build_delims = true;
		while ($i < strlen($val))
		{
			$beforechr = "";
			if ($i > 0) $beforechr = $val[$i-1];

			if ($val[$i] == '"' and $beforechr != "\\") {
				if (count($delimstack) > 0) {
					$lastdelim = array_pop($delimstack);
					if ($lastdelim == '"') {
						$delimclosed = true;
						# $delimstate is true if the opening delimiter is
						# part of $leftdelim
						$delimstate = array_pop($isdelim);
					}
					else {
						$delimstack[] = $lastdelim;
						$delimstack[] = '"';
						if ($build_delims) {
							$isdelim[] = true;
							$leftdelim[] = '"';
							array_unshift($rightdelim, '"');
						}
						else
							$isdelim[] = false;
					}
				}
				else {
					if ($delimclosed and count($delimstack) == 0) return $rv;
					$delimstack[] = '"';
					if ($build_delims) {
						$leftdelim[] = '"';
						$isdelim[] = true;
						array_unshift($rightdelim, '"');
					}
					else
						$isdelim[] = false;
				}
			}
			elseif ($val[$i] == '{' and $beforechr != "\\") {
				if ($delimclosed and count($delimstack) == 0) return $rv;
				$delimstack[] = '{';
				if ($build_delims) {
					$leftdelim[] = '{';
					$isdelim[] = true;
					array_unshift($rightdelim, '}');
				}
				else
					$isdelim[] = false;
			}
			elseif ($val[$i] == '}' and $beforechr != "\\") {
				if (count($delimstack) > 0) {
					$lastdelim = array_pop($delimstack);
					if ($lastdelim == '{') {
						$delimclosed = true;
						$delimstate = array_pop($isdelim);
					} 
					else
						$delimstack[] = $lastdelim;
				}
			}
			else {
				if ($delimclosed and $delimstate) {
					# handle this '{{this} is}'
					#                     |
					#                     +-- we are here                     
					# there was a closing delimiter that is part of
					# $leftdelim. since there are still characters remove
					# the delim from $leftdelim and $rightdelim.
					array_pop($leftdelim);
					array_shift($rightdelim);
				}
				$delimclosed = false;
				$build_delims = false;
				if (count($delimstack) == 0) return $rv;
			}
			$i++;
		}
		
		if (count($delimstack) > 0) return null;
		return array("left" => join("", $leftdelim), "right" => join("", $rightdelim));

		/*
		if (substr($val, 0, 2) == "{{" and substr($val, -2) == "}}")
			return array("left" => "{{", "right" => "}}");
		elseif (substr($val, 0, 2) == "\"{" and substr($val, -2) == "}\"")
			return array("left" => "\"{", "right" => "}\"");
		elseif (substr($val, 0, 1) == "{" and substr($val, -1) == "}")
			return array("left" => "{", "right" => "}");
		elseif (substr($val, 0, 1) == "\"" and substr($val, -1) == "\"")
			return array("left" => "{", "right" => "}");

		return array("left" => "", "right" => "");
		*/
	}

	/**
	 * remove "..." or {...}
	 *
	 * @param string
	 * @param string
	 */
	function getPrettyValByKey($key) {
		$keynr = $this->getKeynr($key);
		if ($keynr === false) return false;
		return $this->getPrettyVal($keynr); 
	}

	function getValueCount() {
		return count($this->mValues);
	}
	
	/**
	 * Format a bibtexrecord with OSBiB.
	 *
	 * @return string
	 */
	function formatWithOSBiB($style) {
		global $wgDefaultReferencesStyle;
			
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
	        
		include_once(dirname(__FILE__).'/bibtexParse/PARSEENTRIES.php');
		include_once(dirname(__FILE__)."/OSBiB/format/BIBFORMAT.php");
		include_once(dirname(__FILE__)."/OSBiB/LOADSTYLE.php");
		$styles = LOADSTYLE::loadDir(dirname(__FILE__)."/OSBiB/styles/bibliography");
		$styleKeys = array_keys($styles);
		$style = mb_strtolower($style);
		$found = false;
		foreach ($styleKeys as $s) {
			if (bwStrEqual($s, $style)) $found = true;
		}
		if (!$found) $style = $wgDefaultReferencesStyle;

		$parse = new PARSEENTRIES();
		$parse->expandMacro = true;
		$parse->loadStringMacro($this->mMacros);
		$parse->loadBibtexString($this->mRecord);
		$parse->extractEntries();
		list($preamble, $strings, $entries, $undefinedStrings) = $parse->returnArrays();
		
		$entry = $entries[0];

		$rep = array(
			"--" => "&ndash;",
			"\\&amp;" => "&amp;",
			"~" => " ",
			"\," => " ",
			"\\frq{}" => "&rsaquo;",
			"\\flq{}" => "&lsaquo;",
			"\\frqq{}" => "&raquo;",
			"\\flqq{}" => "&laquo;",
			"&lt;&lt;" => "&laquo;",
			"&gt;&gt;" => "&raquo;"
		);

		foreach($entry as $key => $val) {
			$entry[$key] = bwTeXToHTML($entry[$key]);
			#$entry[$key] = str_replace(array_keys($rep), array_values($rep), $entry[$key]);
		}

		$bibformat = new BIBFORMAT(FALSE, TRUE);

		list($info, $citation, $footnote, $styleCommon, $styleTypes) = 
					$bibformat->loadStyle(dirname(__FILE__)."/OSBiB/styles/bibliography/", $style);

		$bibformat->getStyle($styleCommon, $styleTypes, $footnote);
		$resourceType = $entry['bibtexEntryType'];
		$bibformat->preProcess($resourceType, $entry);
		$string = $bibformat->map();
		#$string = preg_replace("/{(.*)}/U", "$1", $string);
		return $string;
	}
	
	/**
	 * @author Mark Grimshaw 2006 with small modifications by Wolfgang Plaschg
	 * @license GNU Public License Version 2
	 * @link http://bibliophile.sourceforge.net
	 */
	function fieldSplit($seg)
	{
// echo "****************************<br>";
// echo "1**** \$seg = |";
// print_r($seg);
// echo "|<BR>";
		// handle fields like another-field = {}
		
		// 05.03.2008 wpl
		// solves "Parsing problem with 0.99d"
		// $array = preg_split("/,\s*([-_.:,a-zA-Z0-9]+)\s*={1}\s*/U", $seg, PREG_SPLIT_DELIM_CAPTURE);
    	$array = preg_split("/,\s*([-_.:,a-zA-Z0-9]+)\s*={1}\s*/", $seg, 2, PREG_SPLIT_DELIM_CAPTURE);
    		
//  echo "2**** \$array = ";print_r($array);echo "<BR>";
		//$array = preg_split("/,\s*(\w+)\s*={1}\s*/U", $seg, PREG_SPLIT_DELIM_CAPTURE);
		if(!array_key_exists(1, $array))
			return array($array[0], FALSE);

		// 05.03.2008 wpl
		// solves "Parsing problem with 0.99d"
		// return array($array[0], $array[1]);
		return array($array[0], $array[2]);
	}

	/**
	 * Extract and format fields.
	 *
	 * @author Mark Grimshaw 2006 with small modifications by Wolfgang Plaschg
	 * @license GNU Public License Version 2
	 * @link http://bibliophile.sourceforge.net
	 */
	function parseFields($oldString)
	{
		// 03/05/2005 G. Gardey. Do not remove all occurences, juste one
		// * correctly parse an entry ended by: somefield = {aValue}}
		$oldString = trim($oldString);

		// 2008-02-26 wpl, BUG 1902200
		// solves Jabref newline problem
		$oldString = str_replace("\n", '', $oldString);


		$lg = strlen($oldString);
		if($oldString[$lg-1] == "}" || $oldString[$lg-1] == ")" || $oldString[$lg-1] == ",")
			$oldString = substr($oldString,0,$lg-1);
		// $oldString = rtrim($oldString, "}),");
		$split = preg_split("/=/", $oldString, 2);
		$string = $split[1];
		while($string)
		{
			list($entry, $string) = $this->fieldSplit($string);
			$values[] = $entry;
		}
		foreach($values as $value)
		{
			$pos = strpos($oldString, $value);
			$oldString = substr_replace($oldString, '', $pos, strlen($value));
		}
		$rev = strrev(trim($oldString));
		if($rev{0} != ',')
			$oldString .= ',';
		
		// 05.03.2008 wpl
		// solves "Parsing problem with 0.99d"
		// $keys = preg_split("/=,/", $oldString);
	    $keys = preg_split("/=\s*,/", $oldString);


		// 22/08/2004 - Mark Grimshaw
		// I have absolutely no idea why this array_pop is required but it is.  Seems to always be 
		// an empty key at the end after the split which causes problems if not removed.
		array_pop($keys);
		$keynr = 0;
		foreach($keys as $key)
		{
			$value = trim(array_shift($values));
			$rev = strrev($value);
			// remove any dangling ',' left on final field of entry
			if($rev{0} == ',')
				$value = rtrim($value, ",");
			if(!$value)
				continue;
			// 21/08/2004 G.Gardey -> expand macro
			// Don't remove delimiters now needs to know if the value is a string macro
			$key = strtolower(trim($key));
			$value = trim($value);

	  		$this->mOrigValues[$keynr] = $value;
	  		$value = preg_replace('/\s+/', " ", $value);
	  		$this->mKeys[$keynr] = $key;
	  		$this->mValues[$keynr] = $value;
	  		# replace when needed
	  		# $this->mReplacedValues[$keynr] = $this->replaceMacros($value);
	  		$keynr++;
		}
// echo "**** ";print_r($this->entries[$this->count]);echo "<BR>";
	}

	/**
	 * Parse bibtex type and citation key.
	 *
	 * @author Mark Grimshaw 2006 with small modifications by Wolfgang Plaschg
	 * @license GNU Public License Version 2
	 * @link http://bibliophile.sourceforge.net
	 */
	function parseHeader($entry) {
		$matches = preg_split("/@(.*)[{(](.*),/U", $entry, 2, PREG_SPLIT_DELIM_CAPTURE); 
		$this->mType = trim($matches[1]);
		// sometimes a bibtex entry will have no citation key
		if(preg_match("/=/", $matches[2])) // this is a field
			$matches = preg_split("/@(.*)\s*[{(](.*)/U", $entry, 2, PREG_SPLIT_DELIM_CAPTURE);
		// print_r($matches); print "<P>";
		$this->mCiteKey = trim($matches[2]);
		return $matches[3];
	}

	/**
	 * Parse the bibtex entry.
	 *
	 * @author Mark Grimshaw 2006 with small modifications by Wolfgang Plaschg
	 * @license GNU Public License Version 2
	 * @link http://bibliophile.sourceforge.net
	 * @return boolean
	 */
	function parse()
	{
		$this->mType = "";
		$this->mCiteKey = "";
		$this->mKeys = array();
		$this->mValues = array();
		$this->mOrigValues = array();
		$this->mPrettyValues = array();
		$this->mReplacedValues = array();
		
		if(preg_match("/@(.*)([{(])/U", preg_quote($this->mRecord), $matches)) 
		{
			if(!array_key_exists(1, $matches))
				return false;
			if(preg_match("/string/i", trim($matches[1])))
				return false;
			else if(preg_match("/preamble/i", trim($matches[1])))
				return false;
			else if(preg_match("/comment/i", $matches[1]))
				return false;
			else {
				$entry = $this->parseHeader($this->mRecord);
				$this->parseFields($entry);
				#return (count($this->mKeys) > 0);
				return true;
			}
		}
		return false;
	}


	/**
	 * Get the the BibTeX citation key of the BibTeX record, or, if the data
	 * isn't analyzed yet parse it and store the type in $this->mType and the
	 * key in $this->mCiteKey.
	 *
	 * @param string
	 * @return string 
	 */
	function getCiteKey() {
		if ($this->mCiteKey != "")
			return $this->mCiteKey;
			
		if(preg_match("/@(.*)([{(])/U", preg_quote($this->mRecord), $matches)) 
		{
			if(!array_key_exists(1, $matches))
				return false;
			if(preg_match("/string/i", trim($matches[1])))
				return false;
			else if(preg_match("/preamble/i", trim($matches[1])))
				return false;
			else if(preg_match("/comment/i", $matches[1]))
				return false;
			else {
				$this->parseHeader($this->mRecord);
				return $this->mCiteKey;
			}
		}
		return false;
	}
	
	/**
	 * Gets the BibTeX media type.
	 */
	function getType() {
		if ($this->mType != "")
			return $this->mType;
		if ($this->getCiteKey() === false)
			return false;
		return $this->mType;
	}
	
	/**
	 * Render BibTeX Record out of stored data.
	 */
	function renderSource() {
		$rv = "@".$this->mType."{".$this->mCiteKey.",\n";
		foreach($this->mKeys as $knr => $k) {
			$rv .= $k." = ".$this->mOrigValues[$knr].",\n";
		}
		$rv .= "}\n";
		return $rv;
	}
	
	/**
	 * @param Bibitem $bibitem
	 */
	function merge($bibitem) {
		$kcnt = count($this->mKeys);
		foreach ($bibitem->mKeys as $knr => $k) {
			if (!in_array($k, $this->mKeys)) {
				$this->mKeys[$kcnt] = $bibitem->mKeys[$knr];
				$this->mValues[$kcnt] = $bibitem->mValues[$knr];
				$this->mReplacedValues[$kcnt] = $bibitem->mReplacedValues[$knr];
				$this->mOrigValues[$kcnt] = $bibitem->mOrigValues[$knr];
				$this->mPrettyValues[$kcnt] = $bibitem->mPrettyValues[$knr];
				$kcnt++;
			}
		}
		$this->mMacros = array_merge($this->mMacros, $bibitem->mMacros);
		#rebuild record source
		$this->mRecord = $this->renderSource();
	}
	
	/**
	 * @param Bibfile $bibfile
	 */
	function expandCrossref($bibfile) {
		$cr = $this->getPrettyValByKey("crossref");
		if ($cr !== false) {
			$rec = $bibfile->loadRecord($cr);
			if ($rec == false) return false;
			$refitem = new Bibitem;
			$refitem->set($rec);
			if (!$refitem->parse()) return false;
			$this->merge($refitem);
			# delete crossref entry
			$crkeynrs = $this->getAllKeynrs("crossref");
			foreach ($crkeynrs as $knr) {
				unset($this->mKeys[$knr]);
				unset($this->mValues[$knr]);
				unset($this->mReplacedValues[$knr]);
				unset($this->mPrettyValues[$knr]);
				unset($this->mOrigValues[$knr]);
			}
			#rebuild record
			$this->mRecord = $this->renderSource();
		}
		return true;
	}
	
	
	/**
	 * Bibitem parsing functions
	 * @access private
	 */
	 
	function _split($string) {
		#split
		$this->mTokenArray = preg_split('/([^a-zA-Z0-9])/', $this->mRecord, -1, PREG_SPLIT_DELIM_CAPTURE);
		print implode("|", $this->mTokenArray)."\n";
		$this->mTokenArrayPos = 0;
	}

	/**
	 * eot = end of TokenArray;
	 */ 
	function _eot() {
		return ($this->mTokenArrayPos >= count($this->mTokenArray));
	}
	
	function _getToken() {
		if (!$this->_eot())
			return $this->mTokenArray[$this->mTokenArrayPos];
		return "";
	}
	
	function _nextToken() {
		$this->mTokenArrayPos++;
		while ($this->_getToken() == "" and $this->_eot() == false)
			$this->mTokenArrayPos++;
		return $this->_getToken();
	}
	
	function _getBlock() {
		$rv = "";
		while ($this->_getToken() != "{" and 
		       $this->_getToken() != "}" and 
		       $this->_eot() == false) {
			$rv .= $this->_getToken();
			$this->_nextToken();
		}
		return $rv;
	}
	
	function _isSpace($s) {
		return preg_match("/\s+/", $s);
	}

	function _isWord($s) {
		return preg_match("/\w+/", $s);
	}

	function _nextWord() {
		$this->mTokenArrayPos++;
		while (($this->_isSpace($this->_getToken()) or
			   $this->_getToken() == "") and
			   $this->_eot() == false)
			$this->mTokenArrayPos++;
		return $this->_getToken();
	}
	
}

function thEditPostFormat(&$str) {
	$str = str_replace("&gt;&gt;", "&raquo;", $str);
	$str = str_replace("&lt;&lt;", "&laquo;", $str);
	$str = str_replace("---", "&mdash;", $str);
	$str = str_replace("--", "&ndash;", $str);

	# returning false aborts the post formatting hook chain. 	
	return false;
}

function thFormatTeXMacrosForEditing($cmd, $opt, $arg) {
	$rep_array = array(
		"AmSTeX"		=> '{\AmSTeX}',
		"AmSTeX"		=> '{\AMSTeX}',
		"TeX"			=> '{\TeX}',
		"LaTeX"			=> '{\LaTeX}',
		"LaTeXe"		=> '{\LaTeX2e}',
		"LaTeX2e"		=> '{\LaTeX2e}',
		"AMXTeX"		=> '{\AMSTeX}',
		"LAMSTeX"		=> '{\LAMSTeX}',
		"AMSLaTeX"		=> '{\AMSLaTeX}',
	);
	foreach ($rep_array as $k => $v)
		if ($cmd == $k) return $v.$arg;
	return false;
}

?>
