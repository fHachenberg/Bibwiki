<?php
#if (!defined('MEDIAWIKI'))
#	die();

/**
 * TeXToHTMLConverter
 * 
 * @addtogroup Extensions
 * @package Bibwiki
 *
 * @author Wolfgang Plaschg <wpl@gmx.net>
 * @copyright Copyright (C) 2007 Wolfgang Plaschg
 *
 * @link http://www.ctan.org/tex-archive/info/symbols/comprehensive/symbols-a4.pdf The Comprehensive LaTeX Symbol List
 * @link http://www.plaschg.net/bibwiki Homepage
 * @link http://www.plaschg.net/bibwiki/docs Code documentation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * Class for converting TeX string to UTF8 HTML.
 *
 * @link http://www.ctan.org/tex-archive/info/symbols/comprehensive/symbols-a4.pdf The Comprehensive LaTeX Symbol List
 */
class TeXToHTMLConverter {

	var $mTokenArray;
	var $mTokenArrayPos;
	var $mHooks;

	var $mOpenTags;
	
	function TeXToHTMLConverter($hooks=array(), $post=array()) {
		/**
		 * Hooks are executed in reversed order, so add a hook at the
		 * end of the array to overwrite a formatting rule.
		 */
		$this->mHooks = array(
							"thFormatSymbols",
							"thFormatGreekLetters",
							"thFormatDiacritics",
							"thFormatTeXMacros",
							"thFormatTeXFontStylesToHTML",
							"thFormatTeXToHTML",
						);
		$this->mHooks = array_merge($this->mHooks, $hooks);
		$this->mPost = array(
							"thPostFormat",
						);
		$this->mPost = array_merge($this->mPost, $post);
		$this->mOpenTags = array();
	}
	
	function set($string) {
		#split
		$this->mTokenArray = preg_split('/([^a-zA-Z0-9])/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
		#print implode("|", $this->mTokenArray)."\n";
		$this->mTokenArrayPos = 0;
	}
	
	/**
	 * eot = end of TokenArray;
	 */ 
	function eot() {
		return ($this->mTokenArrayPos >= count($this->mTokenArray));
	}
	
	function getToken() {
		if (!$this->eot())
			return $this->mTokenArray[$this->mTokenArrayPos];
		return "";
	}
	
	function nextToken() {
		$this->mTokenArrayPos++;
		while ($this->getToken() == "" and $this->eot() == false)
			$this->mTokenArrayPos++;
		return $this->getToken();
	}
	
	function getBlock() {
		$rv = "";
		while ($this->getToken() != "\\" and 
		       $this->getToken() != "{" and 
		       $this->getToken() != "}" and 
		       $this->getToken() != "[" and 
		       $this->getToken() != "]" and 
		       $this->eot() == false) {
			$rv .= $this->getToken();
			$this->nextToken();
		}
		return $rv;
	}
	
	function parseArg() {
		$arg = "";
		$delim = "";
		
		$this->mOpenTags[] = array();
		
		if ($this->getToken() == "}" or $this->getToken() == "]")
			return "";
		elseif ($this->getToken() == "{") {
			$delim = "}";
			$this->nextToken();
		}
		elseif ($this->getToken() == "[") {
			$delim = "]";
			$this->nextToken();
		}
		while (true) {
			if ($this->getToken() == "\\") {
				$arg .= $this->parseCmd();
			}
			elseif ($this->getToken() == "[" or $this->getToken() == "{") {
			    $arg .= $this->parseArg();
			}
			elseif ($this->getToken() == $delim) {
				$this->nextToken();
				break;
			}
			elseif ($delim == "") {
				$arg = $this->format_token($this->getToken());
				$this->nextToken();
			}
			else {
				$arg .= $this->format_token($this->getBlock());
			}
			
			if ($delim == "") {
				break;
			}
			
			if ($this->eot())
				break;
		}
		
		$opentags = array_pop($this->mOpenTags);
		while(count($opentags) > 0) {
			$arg .= array_pop($opentags);
		}

		return $arg;
	}
	
	function isSpace($s) {
		return preg_match("/\s+/", $s);
	}

	function isWord($s) {
		return preg_match("/\w+/", $s);
	}

	function nextWord() {
		$this->mTokenArrayPos++;
		while (($this->isSpace($this->getToken()) or
			   $this->getToken() == "") and
			   $this->eot() == false)
			$this->mTokenArrayPos++;
		return $this->getToken();
	}
	
	function unwind($prevpos) {
		$rv = "";
		$i = $prevpos;
		while ($i < $this->mTokenArrayPos) {
			$rv .= $this->mTokenArray[$i++];
		}
		return $rv;
	}
	
	function parseCmd() {
		$opt = "";
		$arg = "";
		
		$prevpos = $this->mTokenArrayPos;

		# skip '\'
		$this->nextToken();
		# get command
		$cmd = $this->getToken();
		
		# process commands that don't have arguments
		$rv = $this->format_cmd($cmd, null, null);
		if ($rv !== false) {
			# skip command
			if ($this->isWord($cmd))
				$this->nextWord();
			else
				$this->nextToken();
			return $rv;
		}

		# grap options and arguments
		$this->nextWord();
		if ($this->getToken() == "[")
			$opt = $this->parseArg();
		$arg = $this->parseArg();

		#return $this->format_cmd_debug($cmd, $opt, $arg);
		$rv = $this->format_cmd($cmd, $opt, $arg);
		if ($rv !== false)
			return $rv;
		
		return $this->unwind($prevpos);
	}
	
	function convert($string) {
		
		$this->mOpenTags[] = array();

		# remove math mode 
		$string = preg_replace("/([^\\\\])\\$/", "$1", $string);

		$rv = "";
		mb_internal_encoding("UTF-8");
		$this->set($string);
		while (!$this->eot()) {
			if ($this->getToken() == "\\")
				$rv .= $this->parseCmd();
			elseif (($this->getToken() == "[") or 
			        ($this->getToken() == "{"))
				$rv .= $this->parseArg();
			else {
				$rv .= $this->format_token($this->getToken());
				$this->nextToken();
			}
		}

		$opentags = array_pop($this->mOpenTags);
		while(count($opentags) > 0) {
			$rv .= array_pop($opentags);
		}
		
		$post = array_reverse($this->mPost);
		foreach($post as $p) {
			if ($p($rv) == false) break;
		}
		return $rv;
	}
	
	function format_cmd_debug($cmd, $opt="", $arg="") {
		#return "\n\\<b>".$cmd.'</b>[<font color="blue">'.$opt.'</font>]{<font color="red">'.$arg.'</font>}';
		return "\\<b>".$cmd.'</b>[<font color="blue">'.$opt.'</font>]{<font color="red">'.$arg.'</font>}';
	}
	
	function format_cmd($cmd, $opt="", $arg="") {
		$hooks = array_reverse($this->mHooks);
		foreach($hooks as $hook) {
			$rv = $hook($cmd, $opt, $arg, $this->mOpenTags);
			if ($rv !== false)
				return $rv;
		}
		return false;
	}
	
	function format_token($rv) {
		$rv = str_replace("&", "&amp;", $rv);
		$rv = str_replace(">", "&gt;", $rv);
		$rv = str_replace("<", "&lt;", $rv);
		#$rv = preg_replace("/([^\\\\])~/", "$1&nbsp;", $rv);
		$rv = str_replace("~", "&nbsp;", $rv);
		return $rv;
	}
}

/**
 * Post formatting.
 * 
 * Do some string manipulations after all the formatting of the TeX code.
 * If the function returns false the hook chain aborts.
 */
function thPostFormat(&$str) {
	#$str = str_replace("fi", "", $str);
	#$str = str_replace("fl", "", $str);
	$str = str_replace("f\\/i", "fi", $str);
	$str = str_replace("f\\/l", "fl", $str);

	$str = str_replace("&gt;&gt;", "&raquo;", $str);
	$str = str_replace("&lt;&lt;", "&laquo;", $str);
	$str = str_replace("---", "&mdash;", $str);
	$str = str_replace("--", "&ndash;", $str);
	$str = str_replace(",,", "„", $str);
	$str = str_replace("``", "“", $str);
	$str = str_replace("''", "”", $str);
	$str = str_replace("\"\"", "", $str);
	
	return true;
}

/**
 * Format TeX commands.
 * 
 * If the function returns !== false the hook chain aborts.
 */
function thFormatTeXFontStylesToHTML($cmd, $opt, $arg, &$opentags = array()) {
	if ($arg === null) {
		#process commands without arguments
		
		if ($cmd == "em") {
			$tags = array_pop($opentags);
			$tags[] = "</i>";
			$opentags[] = $tags;
			return "<i>";
		}
		elseif ($cmd == "bf") {
			$tags = array_pop($opentags);
			$tags[] = "</b>";
			$opentags[] = $tags;
			return "<b>";
		}

		return false;
	} 
	
	#process commands with arguments

	if ($cmd == "sc" or $cmd == "textsc")
		return '<span style="font-variant:small-caps;">'.$arg.'</span>';
	elseif ($cmd == "it" or $cmd == "sl" or 
	        $cmd == "textit" or 
	        $cmd == "emph")
		return '<i>'.$arg.'</i>';
	elseif ($cmd == "tt" or $cmd == "texttt")
		return $arg;
	elseif ($cmd == "textbf")
		return '<b>'.$arg.'</b>';
	return false;
}

function thFormatTeXMacros($cmd, $opt, $arg) {
	if ($arg !== null) return false;
 
 	$rep_array = array(
		"mbox"			=> '',
		"hspace"		=> ' ',
		"AmSTeX"		=> 'AMST<sub>E</sub>X',
		"AmSTeX"		=> 'AMST<sub>E</sub>X',
		"TeX"			=> 'T<sub>E</sub>X',
		"Metafont"		=> 'Metafont',
		"METAFONT"		=> 'METAFONT',
		"mf"			=> 'MF',
		"LaTeX"			=> 'L<sup>A</sup>T<sub>E</sub>X',
		"LaTeXe"		=> 'L<sup>A</sup>T<sub>E</sub>X2<sub>ε</sub>',
		"LaTeX2e"		=> 'L<sup>A</sup>T<sub>E</sub>X2<sub>ε</sub>',
		"AMXTeX"		=> 'AMST<sub>E</sub>X',
		"LAMSTeX"		=> 'LAMST<sub>E</sub>X',
		"AMSLaTeX"		=> 'AMSLaT<sub>E</sub>X',
		"ps"			=> 'PS',
		"Postscript"	=> 'Postscript',
		"POSTSCRIPT"	=> 'POSTSCRIPT',
	);
	foreach ($rep_array as $k => $v)
		if ($cmd == $k) return $v.$arg;
	return false;
}

function thFormatSymbols($cmd, $opt, $arg) {
	if ($arg !== null) return false;

	$rep_array = array(
		"-" 			=> '',
		"_"				=> "_",
		"$"				=> "$",
		","				=> " ", 
		"&"				=> "&",
		"%"				=> "%",
		" "				=> ' ',
		"ldots"			=> '…',
		"/"				=> '\\/',
		"slash"			=> '/',
		"emdash"		=> '—',
		"endash"		=> '–',
		"newline"		=> '',
		"widetilde"		=> '~',
		"textasciitilde" => "˜",
		"texttildelow"  => "~",
		"ss"			=> 'ß',
		"i"				=> 'ı',
		"j"				=> 'j',
		"l"				=> 'ł',
		"L"				=> 'Ł',
		'DH'			=> 'Ð',
		'dh'			=> 'ð',
		'DJ'			=> 'Ð',
		'dj'			=> 'đ',
		'NG'			=> 'Ŋ',
		'ng'			=> 'ŋ',
		'TH'			=> 'Þ',
		'th'			=> 'þ',
		"o"				=> 'ø',
		"O"				=> 'Ø',
		"frq"			=> '›',
		"guilsinglright"=> '›',
		"flq" 			=> '‹',
		"guilsinglleft"	=> '‹',
		"frqq"			=> '»',
		"guillemotleft"	=> '»',
		"flqq" 			=> '«',
		"guillemotright"=> '«',	
		"aa"			=> 'å',
		"AA"			=> 'Å',
		"ae"			=> 'æ',
		"AE"			=> 'Æ',
		"oe"			=> 'œ',
		"OE"			=> 'Œ',
		"textbackslash"	=> '\\',
		'textbar' 		=> '|',
		'{'				=> '{',
		'textbraceleft' => '{',
		'}'				=> '}',
		'textbraceright'=> '}',
		'textbullet' 	=> '•',
		'copyright'		=> '©',
		'textcopyright' => '©',
		'textdagger' 	=> '†',
		'dag'			=> '†',
		'textdaggerdbl' => '‡',
		'ddag'			=> '‡',
		'textdollar' 	=> '$',
		'pounds' 		=> '£',
		'euro'	 		=> '€',
		'textellipsis' 	=> '…',
		'dots'			=> '…',
		'textemdash' 	=> '—',
		'textendash' 	=> '–',
		'textexclamdown'=> '¡',
		'textgreater' 	=> '>',
		'textless' 		=> '<',
		'textordfeminine' => 'ª',
		'textordmasculine' => 'º',
		'P'				=> '¶',
		'textparagraph'	=> '¶',
		'textperiodcentered' => '·',
		'textquestiondown' => '¿',
		'textquotedblleft' => '“',
		'textquotedblright' => '”',
		'textquoteleft'	=> '‘',
		'textquoteright' => '’',
		'circledR'		=> '®',
		'textregistered' => '®',
		'S'				=> '§',
		'textsection'	=> '§',
		'textsterling'	=> '£',
		'pounds'		=> '£',
		'texttrademark'	=> '™',
		'textunderscore' => '_',
		'textvisiblespace' => ' ',
		/**
		 * @todo add more
		 */
	);
	foreach ($rep_array as $k => $v)
		if ($cmd == $k) return $v.$arg;
	return false;
}

function thFormatGreekLetters($cmd, $opt, $arg) {
	if ($arg !== null) return false;

	$rep_array = array(
		"Alpha"			=> 'Α',
		"Beta"			=> 'Β',
		"Gamma"			=> 'Γ',
		"Delta"			=> 'Δ',
		"Epsilon"		=> 'Ε',
		"Zeta"			=> 'Ζ',
		"Eta"			=> 'Η',
		"Theta"			=> 'Θ',
		"Iota"			=> 'Ι',
		"Kappa"			=> 'Κ',
		"Lambda"		=> 'Λ',
		"Mu"			=> 'Μ',
		"Nu"			=> 'Ν',
		"Xi"			=> 'Ξ',
		"Omicron"		=> 'Ο',
		"Pi"			=> 'Π',
		"Rho"			=> 'Ρ',
		"Sigma"			=> 'Σ',
		"Tau"			=> 'Τ',
		"Upsilon"		=> 'Υ',
		"Phi"			=> 'Φ',
		"Chi"			=> 'Χ',
		"Psi"			=> 'Ψ',
		"Omega"			=> 'Ω',

		"alpha"			=> 'α',
		"beta"			=> 'β',
		"gamma"			=> 'γ',
		"delta"			=> 'δ',
		"epsilon"		=> 'ε',
		"zeta"			=> 'ζ',
		"eta"			=> 'η',
		"theta"			=> 'θ',
		"iota"			=> 'ι',
		"kappa"			=> 'κ',
		"lambda"		=> 'λ',
		"mu"			=> 'μ',
		"nu"			=> 'ν',
		"xi"			=> 'ξ',
		"omicron"		=> 'ο',
		"pi"			=> 'π',
		"rho"			=> 'ρ',
		"sigma"			=> 'ς',
		"varsigma"		=> 'σ',
		"tau"			=> 'τ',
		"upsilon"		=> 'υ',
		"phi"			=> 'φ',
		"chi"			=> 'χ',
		"psi"			=> 'ψ',
		"omega"			=> 'ω',
	);
	foreach ($rep_array as $k => $v)
		if ($cmd == $k) return $v.$arg;
	return false;
}

function thFormatTeXToHTML($cmd, $opt, $arg) {
	if ($arg !== null) return false;
 
 	$rep_array = array(
		"-" 			=> '&shy;',
		"_"				=> "_",
		"$"				=> "&#36;",
		","				=> "&nbsp;", 
		'textgreater' 	=> '&gt;',
		'textless' 		=> '&lt;',
	);
	foreach ($rep_array as $k => $v)
		if ($cmd == $k) return $v.$arg;
	return false;
}

function thFormatDiacritics($cmd, $opt, $arg) {
	if ($arg === null) return false;

	$firstchar = mb_substr($arg, 0, 1);
	$rest = mb_substr($arg, 1);
	if ($cmd == "\"")
		return thFormatUmlaut($cmd, $opt, $firstchar).$rest;
	elseif ($cmd == "`")
		return thFormatAgrave($cmd, $opt, $firstchar).$rest;
	elseif ($cmd == "'")
		return thFormatAcute($cmd, $opt, $firstchar).$rest;
	elseif ($cmd == "~")
		return thFormatTilde($cmd, $opt, $firstchar).$rest;
	elseif ($cmd == "^")
		return thFormatCircumflex($cmd, $opt, $firstchar).$rest;
	elseif ($cmd == "v")
		return thFormatCaron($cmd, $opt, $firstchar).$rest;
	elseif ($cmd == "c")
		return thFormatCedille($cmd, $opt, $firstchar).$rest;
	/**
	 * @todo Implement these functions
	 */
	#elseif ($cmd == ".") # Ė
	#	return thFormatAboveDot($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "=") # Ā
	#	return thFormatMacron($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "|") # 
	#	return thFormatAboveBar($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "d") # Ạ
	#	return thFormatBelowDot($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "G") # ???
	#	return thFormatDoubleGravis($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "h") # ???
	#	return thFormat...($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "H") # Ő
	#	return thFormatDoubleAcute($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "k") # Ę
	#	return thFormatOgonek($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "r") # å
	#	return thFormatAboveCircle($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "u") # Ĕ
	#	return thFormatBrevis($cmd, $opt, $firstchar).$rest;
	#elseif ($cmd == "U") # ???
	#	return thFormat...($cmd, $opt, $firstchar).$rest;
	return false;
}

/**
 * \"{...}
 */
function thFormatUmlaut($cmd, $opt, $arg) {
	$rep_array = array(
		'a' => 'ä',
		'e' => 'ë',
		'i' => 'ï',
		'o' => 'ö',
		'u' => 'ü',
		'y' => 'ÿ',
		'A' => 'Ä',
		'E' => 'Ë',
		'I' => 'Ï',
		'O' => 'Ö',
		'U' => 'Ü',
		'W' => 'Ẅ',
		'w' => 'ẅ',
		'ι'	=> 'ϊ',   # iota
		'υ' => 'ϋ',   # upsilon
		'Ι'	=> 'Ϊ',   # Iota
		'Υ' => 'Ϋ'    # Upsilon
	);
	foreach ($rep_array as $k => $v)
		if ($arg == $k) return $v;
	return $arg;
}

/**
 * \`{...}
 */
function thFormatAgrave($cmd, $opt, $arg) {
	$rep_array = array(
		'A' => 'À',
		'E' => 'È',
		'I' => 'Ì',
		'O' => 'Ò',
		'U' => 'Ù',
		'a' => 'à',
		'e' => 'è',
		'i' => 'ì',
		'o' => 'ò',
		'u' => 'ù',
	);
	foreach ($rep_array as $k => $v)
		if ($arg == $k) return $v;
	return $arg;
}

/**
 * \'{...}
 */
function thFormatAcute($cmd, $opt, $arg) {
	$rep_array = array(
		'A' => 'Á',
		'E' => 'É',
		'I' => 'Í',
		'O' => 'Ó',
		'U' => 'Ú',
		'Y' => 'Ý',
		'a' => 'á',
		'e' => 'é',
		'i' => 'í',
		'ı' => 'í',
		'o' => 'ó',
		'u' => 'ú',
		'y' => 'ý',
		'C' => 'Ć',
		'c' => 'ć',
		'L' => 'Ĺ',
		'l' => 'ĺ',
		'N' => 'Ń',
		'n' => 'ń',
		'R' => 'Ŕ',
		'r' => 'ŕ',
		'S' => 'Ś',
		's' => 'ś',
		'Z' => 'Ź',
		'z' => 'ź',
		'α' => 'ά', 
		'ε' => 'έ',
		'η' => 'ή',
		'ι' => 'ί',
		'ο' => 'ό',
		'υ' => 'ύ',
		'ω' => 'ώ',
		'Γ' => 'Ѓ',
	);
	foreach ($rep_array as $k => $v)
		if ($arg == $k) return $v;
	return $arg;
}

/**
 * \~{...}
 */
function thFormatTilde($cmd, $opt, $arg) {
	$rep_array = array(
		'A' => 'Ã',
		'N' => 'Ñ',
		'O' => 'Õ',
		'a' => 'ã',
		'n' => 'ñ',
		'o' => 'õ',
		'I' => 'Ĩ',
		'i' => 'ĩ',
		'U' => 'Ũ',
		'u' => 'ũ',
	);
	foreach ($rep_array as $k => $v)
		if ($arg == $k) return $v;
	return $arg;
}

/**
 * \^{...}
 */
function thFormatCircumflex($cmd, $opt, $arg) {
	$rep_array = array(
		'A' => 'Â',
		'E' => 'Ê',
		'I' => 'Î',
		'O' => 'Ô',
		'U' => 'Û',
		'a' => 'â',
		'e' => 'ê',
		'i' => 'î',
		'o' => 'ô',
		'u' => 'û',
		'C' => 'Ĉ',
		'c' => 'ĉ',
		'G' => 'Ĝ',
		'g' => 'ĝ',
		'H' => 'Ĥ',
		'h' => 'ĥ',
		'J' => 'Ĵ',
		'j' => 'ĵ',
		'S' => 'Ŝ',
		's' => 'ŝ',
		'W' => 'Ŵ',
		'w' => 'ŵ',
		'Y' => 'Ŷ',
		'y' => 'ŷ'
	);
	foreach ($rep_array as $k => $v)
		if ($arg == $k) return $v;
	return $arg;
}

/**
 * \v{...}
 */
function thFormatCaron($cmd, $opt, $arg) {
	$rep_array = array(
		'C' => 'Č', 
		'c' => 'č',
		'D' => 'Ď',
		'E' => 'Ě',
		'e' => 'ě',
		'N' => 'Ň',
		'n' => 'ň',
		'R' => 'Ř',
		'r' => 'ř',
		'S' => 'Š',
		's' => 'š',
		'Z' => 'Ž',
		'z' => 'ž',
		'A' => 'Ǎ',
		'a' => 'ǎ',
		'i' => 'ǐ',
		'o' => 'Ǒ',
		'I' => 'Ǐ',
		'o' => 'ǒ',
		'U' => 'Ǔ',
		'u' => 'ǔ'
	);
	foreach ($rep_array as $k => $v)
		if ($arg == $k) return $v;
	return $arg;
}

/**
 * \c{...}
 */
function thFormatCedille($cmd, $opt, $arg) {
	$rep_array = array(
		'C' => 'Ç',
		'c' => 'ç',
		'G' => 'Ģ',
		'K' => 'Ķ',
		'k' => 'ķ',
		'L' => 'Ļ',
		'l' => 'ļ',
		'N' => 'Ņ',
		'n' => 'ņ',
		'R' => 'Ŗ',
		'r' => 'ŗ',
		'S' => 'Ş',
		's' => 'ş',
		'T' => 'Ţ',
		't' => 'ţ'
	);
	foreach ($rep_array as $k => $v)
		if ($arg == $k) return $v;
	return $arg;
}

/**
 * @todo: make more
 */

