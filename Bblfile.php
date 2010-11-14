<?php
if (!defined('MEDIAWIKI'))
	die();

/**
 * Bblfile
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

require_once(dirname( __FILE__ ) ."/Bibfile.php");

/**
 * Class for parsing .bbl files.
 */
class BblFile {
	
	var $mfh;
	var $mRecord;
	var $mTokenArray;
	var $mTokenArrayPos;
	var $mFirstRecord;
	
	function open($filename) { 
		$this->mfh = fopen($filename, "r"); 
		$this->mFirstRecord = true;
	}
	
	function close() { fclose($this->mfh); }
	
	function eot() {
		#eot = end of TokenArray; 
		return ($this->mTokenArrayPos >= count($this->mTokenArray));
	}
	
	function getToken() { return $this->mTokenArray[$this->mTokenArrayPos]; }
	
	function nextRecord() {
		$this->mRecord = "";
		$sz = fgets($this->mfh);
		while (trim($sz) != "") {
			$this->mRecord .= $sz;
			$sz = fgets($this->mfh);
		}
		return $this->mRecord;
	}
	
	function prepareRecord() {
		$this->mRecord = preg_replace("/%[\\r\\n]+/", "", $this->mRecord);
		$this->mRecord = preg_replace("/\\n+/", "", $this->mRecord);
		$this->mRecord = str_replace("\"\"", "", $this->mRecord);
		$this->mRecord = str_replace("\\_", "_", $this->mRecord);
		$this->mRecord = preg_replace("/([^\\\\])~/", "$1&nbsp;", $this->mRecord);
		$this->mRecord = str_replace("\\$", "&#36;", $this->mRecord);
		$this->mRecord = str_replace("\\,", "&nbsp;", $this->mRecord);
		$this->mRecord = str_replace("\"\"", "", $this->mRecord);
		$this->mRecord = str_replace("\\par", "<br>", $this->mRecord);
		$this->mRecord = str_replace("\\&", "&amp;", $this->mRecord);
		$this->mRecord = str_replace("\\%", "%", $this->mRecord);
		$this->mRecord = str_replace(">>", "&raquo;", $this->mRecord);
		$this->mRecord = str_replace("<<", "&laquo;", $this->mRecord);
		$this->mRecord = str_replace("---", "&mdash;", $this->mRecord);
		$this->mRecord = str_replace("--", "&ndash;", $this->mRecord);
		$this->mTokenArray = preg_split("/([{}\\[\\]\\\\\\s\\(\\)\\$\\/])/", $this->mRecord, -1, PREG_SPLIT_DELIM_CAPTURE);
		$this->mTokenArrayPos = 0;
	}
	
	function nextToken() {
		$this->mTokenArrayPos++;
		while ($this->eot() == false and $this->getToken() == "")
			$this->mTokenArrayPos++;
		if ($this->eot()) {
			$this->nextRecord();
			$this->prepareRecord();
		}
	}
	
	function parseArg() {
		$arg = "";
		$delim = "";
		
		if ($this->getToken() == "{") $delim = "}";
		if ($this->getToken() == "[") $delim = "]";
		
		$this->nextToken();
		while ($this->eot() == false and $this->getToken() != $delim) {
			if ($this->getToken() == "\\") 
				$arg .= $this->parseCmd();
			elseif ($this->getToken() == "[" or $this->getToken() == "{")
			    $arg .= $this->parseArg();
			else {
				$arg .= $this->getToken();
				$this->nextToken();
			}
		}
		$this->nextToken();
		return $arg;
	}
	
	function parseCmd() {
		$opt = "";
		$arg = "";
		
		$this->nextToken();
		$cmd = $this->getToken();
		$this->nextToken();
		
		$first = substr($cmd, 0, 1);
		if (strlen($cmd) > 1 and strchr('^"\'=\.~`-', $first) !== false) {
			$arg = substr($cmd, 1);
			$cmd = substr($cmd, 0, 1);
		}
		else {
			if ($this->getToken() == "[") $opt = $this->parseArg();
			if ($this->getToken() == "{") $arg = $this->parseArg();
			$cmd_esc = preg_replace('/([\\\.\?\[\]\-])/', '\\$1', $cmd);

			#fran\c cais => fran\c{c}ais
			if ($arg == "" and 
			    strlen($cmd) == 1 and
			    stristr('^"\'=\.~`vbcudh', $cmd_esc) !== false) {

				#skip spaces
				if (preg_match('/\s+/', $this->getToken()))
					$this->nextToken();

				$first = substr($this->getToken(), 0, 1);
				if (preg_match('/\w+/', $first)) {
					$token = $this->getToken();
					$this->mTokenArray[$this->mTokenArrayPos] = substr($token, 1);
					$arg = $first;
				}
			}
		}
		return $this->format_cmd(strtolower($cmd), $opt, $arg, $cmd);
	}
	
	function format_cmd_debug($cmd, $opt, $arg, $ocmd) {
		return "\n<br>\\<b>".$cmd.'</b>[<font color="blue">'.$opt.'</font>]{<font color="red">'.$arg.'</font>}';
	}
	
	function format_cmd($cmd, $opt, $arg, $ocmd) {
		if ($cmd == "begin")
			return '';
		elseif ($cmd == "end")
			return '';
		elseif ($cmd == "setstretch")
			return '';
		elseif ($cmd == "providecommand")
			return '';
		elseif ($cmd == "samepage")
			return '';
		elseif ($cmd == "pagebreak")
			return '';
		elseif ($cmd == "hyphenation")
			return '';
		elseif ($cmd == "noopsort")
			return '';
		elseif ($cmd == "dinatlabel")
			return '';
		elseif ($cmd == "citep")
			return $arg;
		elseif ($cmd == "citet")
			return $arg;
		elseif ($cmd == "penalty0")
			return '';
		elseif ($cmd == "name")
			return '<span style="font-variant:small-caps;">'.$arg.'</span>';
		elseif ($cmd == "sc")
			return '<span style="font-variant:small-caps;">'.$arg.'</span>';
		elseif ($cmd == "textsc")
			return '<span style="font-variant:small-caps;">'.$arg.'</span>';
		elseif ($cmd == "articletitle")
			return '&raquo;'.$arg.'&laquo;';
		elseif ($cmd == "title")
			return '<i>'.$arg.'</i>';
		elseif ($cmd == "cite")
			return $arg;
		elseif ($cmd == "it")
			return '<i>'.$arg.'</i>';
		elseif ($cmd == "sl")
			return '<i>'.$arg.'</i>';
		elseif ($cmd == "tt")
			return $arg;
		elseif ($cmd == "texttt")
			return $arg;
		elseif ($cmd == "textit")
			return '<i>'.$arg.'</i>';
		elseif ($cmd == "emph")
			return '<i>'.$arg.'</i>';
		elseif ($cmd == "em")
			return '<i>'.$arg.'</i>';
		elseif ($cmd == "bf")
			return '<b>'.$arg.'</b>';
		elseif ($cmd == "hyperlink")
			return $arg;
		elseif ($cmd == "url")
			return $arg;
		elseif ($cmd == "mbox")
			return $arg;
		elseif ($cmd == "textbf")
			return '<b>'.$arg.'</b>';
		elseif ($cmd == "bibitem") {
			$out = "";
			if ($this->mFirstRecord == false) 
				$out = "</div>";
			$out .= '<div class="bibitem">';
			$this->mFirstRecord = false;
			return $out;
		}
		elseif ($cmd == "natexlab")
			return $arg;
		elseif ($cmd == "hspace")
			return ' ';
		elseif ($cmd == " ")
			return ' ';
		elseif ($cmd == "page")
			return 'S.&nbsp;';
		elseif ($cmd == "emdash")
			return '&mdash;';
		elseif ($cmd == "endash")
			return '&ndash;';
		elseif ($cmd == "/")
			return '/';
		elseif ($cmd == "hg")
			return '(Hg.)';
		elseif ($cmd == "tex")
			return 'TeX';
		elseif ($cmd == "metafont")
			return 'Metafont';
		elseif ($cmd == "mf")
			return 'MF';
		elseif ($cmd == "latex")
			return 'LaTeX';
		elseif ($cmd == "latexe")
			return 'LaTeX2e';
		elseif ($cmd == "latex2e")
			return 'LaTeX2e';
		elseif ($cmd == "ldots")
			return '&hellip;';
		elseif ($cmd == "amstex")
			return 'AMSTeX';
		elseif ($cmd == "lamstex")
			return 'LAMSTeX';
		elseif ($cmd == "amslatex")
			return 'AMSLaTeX';
		elseif ($cmd == "ps")
			return 'PS';
		elseif ($cmd == "cmr")
			return 'CMR';
		elseif ($cmd == "postscript")
			return 'Postscript';
		elseif ($cmd == "slash")
			return '/';
		elseif ($cmd == "\\")
			return '<br>';
		elseif ($cmd == "newline")
			return '';
		elseif ($cmd == "widetilde")
			return '~';
		elseif ($cmd == "pi")
			return '&pi;';
		elseif ($cmd == "ss")
			return '&szlig;';
		elseif ($cmd == "frq")
			return '&rsaquo;';
		elseif ($cmd == "flq")
			return '&lsaquo;';
		elseif ($cmd == "frqq")
			return '&raquo;';
		elseif ($ocmd == "i")
			return 'i';
		elseif ($ocmd == "j")
			return 'j';
		elseif ($ocmd == "l")
			return 'l';
		elseif ($ocmd == "L")
			return 'L';
		elseif ($ocmd == "o")
			return '&oslash;';
		elseif ($ocmd == "O")
			return '&oslash;';
		elseif ($cmd == "flqq")
			return '&laquo;';
		elseif ($cmd == "newblock")
			return '';
		elseif ($cmd == "\"" and $arg != "" and stristr('aeiouy', $arg) !== false)
			return '&'.$arg.'uml;';
		elseif ($cmd == "'" and $arg != "" and stristr('aeiuy', $arg) !== false)
			return '&'.$arg.'acute;';
		elseif ($cmd == "`" and $arg != "" and stristr('aeiou', $arg) !== false)
			return '&'.$arg.'agrave;';
		elseif ($cmd == "~" and $arg != "" and stristr('aony', $arg) !== false)
			return '&'.$arg.'tilde;';
		elseif ($cmd == "^" and $arg != "" and stristr('aeiou', $arg) !== false)
			return '&'.$arg.'circ;';
		elseif ($cmd == "v" and strtolower($arg) == "s")
			return '&'.$arg.'caron;';
		elseif ($cmd == "c" and strtolower($arg) == "c")
			return '&'.$arg.'cedil;';
		elseif ($cmd == "o" and strtolower($arg) == 'o')
			return '&'.$ocmd.'slash;';
		elseif ($cmd == "." and $arg == "z")
			return '&#380;';
		elseif ($cmd == "." and $arg == "Z")
			return '&#381;';
		elseif ($ocmd == "aa")
			return '&aring;</b>';
		elseif ($ocmd == "AA")
			return '&Aring;</b>';
		elseif ($ocmd == "ae")
			return '&aelig;</b>';
		elseif ($ocmd == "AE")
			return '&AElig;</b>';
		elseif ($ocmd == "oe")
			return '&oelig;</b>';
		elseif ($ocmd == "OE")
			return '&OElig;</b>';
		elseif (stristr("\"'`~^\.vucokh=", $cmd) !== false)
			return $arg;
		elseif ($ocmd == "-")
			return $arg;
		if ($opt != "") $opt = "[".$opt."]";
		if ($arg != "") $arg = "{".$arg."}";
		return "<b><font color=\"red\">\\".$ocmd.$opt.$arg."</font></b>";
	}
	
	function convert($filename) {
		$rv = "";
		
		$this->open($filename);
		$this->nextRecord(); # dump the header
		$this->nextRecord();
		$this->prepareRecord();

		while ($this->eot() == false and feof($this->mfh) == false) {
			if ($this->getToken() == "\\") {
				$rv .= $this->parseCmd();
			}
			elseif (($this->getToken() == "[") or 
			       ($this->getToken() == "{")) {
				$rv .= $this->parseArg();
			}
			else {
				$rv .= $this->getToken();
				$this->nextToken();
			}
		}
		$this->close();
		$rv .= "</div>";
		return $rv;
	}

	/**
	 * @param Bibfile
	 * @return void
	 */
	function export($bibliography) {
		global $wgOut, $wgRequest, $wgBibTeXExecutable, $wgTempDir,
			$wgBibStyles; 
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
		
		print ("
			<html>
			<head>
				<meta http-equiv='Content-Type' content='text/html; charset=utf8' />
				<link rel='shortcut icon' href='favicon.ico' />
				<title>Export</title>
				<style type='text/css'>
				pre {
					border-left: 5px solid lightgray;
					padding: 10px;
					background-color: #EFEFEF;
				}
				ul {
					list-style:none;
					margin:0;
					padding:1px;
				}
				li {
					display:inline;
					padding: 0px 6px 2px 6px;
					margin-right: -1px;
					border: 1px solid #DDDDDD;
				}
				li a {
					text-decoration:none;
					font-family: Arial;
					font-size:8pt;
					color:black;
				}
				input {
					border: 1px solid #DDDDDD;
					font-family: Arial;
					font-size:8pt;
					padding: 4px 6px 3px 6px;
					margin-right: 5px;
				}
				.bibitem {
					margin-left:2em;
					text-indent:-2em;
				}
				</style>
		");

		# CHECK SETTINGS
		
		$wgTempDir_Error = false;
		$d = @dir($wgTempDir);
		if (empty($d)) 
			$wgTempDir_Error = true;
		else
			$d->close();
		if (is_readable($wgTempDir) == false) $wgTempDir_Error = true;

		if ($wgTempDir_Error)
		{
			$bibliography->errorBox("Can't access path given at \$wgTempDir. Check settings in BibwikiSettings.php.");
			exit();
		}

		if (file_exists($wgBibTeXExecutable) == false or 
			is_readable($wgBibTeXExecutable) == false) 
		{
			$bibliography->errorBox("Can't access file given at \$wgBibTeXExecutable. Check settings in BibwikiSettings.php");
			exit();
		}
		
		# REMOVE OLD FILES
		
		@unlink(bwMakePath($wgTempDir, 'bibexport.bbl'));
		@unlink(bwMakePath($wgTempDir, 'bibexport.aux'));
		
		# WRITE .AUX-FILE
		
		$style = $wgBibStyles[0];
		if ($wgRequest->getVal("style") != "")
			$style = $wgRequest->getVal("style");
		
		$texfile = bwMakePath($wgTempDir, 'bibexport.aux');
		
		$f = fopen($texfile, "w");
		
		$found_keys = "";
		$compare_keys = array();
		$not_found = array();
		if ($wgRequest->getVal("content") != "") {
			if ($bibliography->mBibfile->open() == false) print ("Error: Opening file failed.");
			$keys = $bibliography->getKeys();
			$lwrkeys = array_map("strtolower", $keys);
			$keys = array_combine($lwrkeys, $bibliography->getKeys());

			$content = $wgRequest->getVal("content");
			
			# remove "-" since MS Word doesn't remove hyphens when you copy
			# hyphenated text into the clipboard. Silly!
			$content = str_replace("-", "", $content);
			
			if (preg_match_all("/\[\[.*\]\]/U", $content, $matches) > 0) {
				# find all keys matching [[...]] and save them in $compare_keys
				foreach ($matches[0] as $m) {
					$m = str_replace("[", "", $m);
					$m = str_replace("]", "", $m);
					$compare_keys[] = strtolower($m);
				}
			}
			
			$content = preg_replace("/[^a-zA-Z0-9\\.:_]+/", " ", $content);
			$matches = array();
			$words = explode(" ", $content);
			foreach ($words as $w) {
				if (in_array(strtolower($w), $lwrkeys)) {
			        fputs($f,"\\citation{".$keys[strtolower($w)]."}\n");
			        if ($found_keys != "") $found_keys .= " ";
			        $found_keys .= $keys[strtolower($w)];
			    }
			    elseif (in_array(strtolower($w), $compare_keys)) {
			    	# $w is a BibTeX key, but wasn't found in the .bib-file
			    	$not_found[] = $w;
			    }
			}
			$bibliography->mBibfile->close();
		}
		elseif ($wgRequest->getVal("keys") != "")
		{
			$found_keys = $wgRequest->getVal("keys");
			$keys = explode(" ", $found_keys);
			foreach ($keys as $key) {
		        fputs($f, "\\citation{".$key."}\n");
			}
		}
		else {
			$bibitem = new Bibitem();
			if ($bibliography->mBibfile->open() == false) print ("Error: Opening file failed.");
			while (($sz = $bibliography->mBibfile->nextFilteredRecord()) !== false) {
				$bibitem->set($sz);
				$bibitem->parse() or die("<pre>parsing bibitem failed:\n$sz</pre>");
		        fputs($f,"\\citation{".$bibitem->getCiteKey()."}\n");
			}
			$bibliography->mBibfile->close();
		}

		fputs($f, '\bibstyle{'.$style."}\n");
		fputs($f, '\bibdata{'.str_replace('.bib','',$bibliography->mBibfile->getName())."}\n");
		
		fclose($f);
		
		# PRINT TOOLBAR

		print ('<body'.(($wgRequest->getVal("font") != "")?' style="font-family:'.urldecode($wgRequest->getVal("font")).';"':'').'>');
		
		if ($wgRequest->getVal("notoolbar") != 1) {
			print ('<form method="get" action="'.$bibliography->getLocalURL().'">');
			print ('<ul>');
			print ('<input type="text" name="keyword" value="'.$bibliography->mFilter.'">');
			print ('<input type="hidden" name="action" value="export">');
			print ('<input type="hidden" name="view" value="'.$wgRequest->getVal("view").'">');
			print ('<input type="hidden" name="f" value="'.$bibliography->mBibfile->getName.'">');
			print ('<input type="hidden" name="style" value="'.$style.'">');
			print ('<input type="hidden" name="keys" value="'.$found_keys.'">');
			print ('<input type="hidden" name="font" value="'.$wgRequest->getVal("font").'">');
			foreach ($wgBibStyles as $s) {
				print ('<li><a href="'.$bibliography->getLocalURL(array('action=export', $bibliography->mBibfileQuery, $bibliography->mFilterQuery, 'style='.$s, 'keys='.$found_keys, 'font='.$wgRequest->getVal("font"))).'">'.(($style==$s)?"<b>".$s."</b>":$s).'</a></li>');
			}
			print ('<li style="margin-left:5px;"><a href="'.$bibliography->getLocalURL(array('action=export', $bibliography->mBibfileQuery, $bibliography->mFilterQuery, 'style='.$style, 'keys='.$found_keys, 'font=Arial')).'">Arial</a></li>');
			print ('<li><a href="'.$bibliography->getLocalURL(array('action=export', $bibliography->mBibfileQuery, $bibliography->mFilterQuery, 'style='.$style, 'keys='.$found_keys, 'font=Times')).'">Times</a></li>');
			print ('</ul></form>');
		}

		# START BIBTEX
		
		if (file_exists($wgBibTeXExecutable) == false) {
			print "BibTeX not found.<br><code>\$wgBibTeXExecutable</code> in <code>BibwikiSettings.php</code> is set to:<pre>$wgBibTeXExecutable</pre><br><br>\n";
			exit();
		}
		
		if (stristr($wgBibTeXExecutable, "bibtex") === false) {
			print "Is <pre>$wgBibTeXExecutable</pre> really BibTeX? I doubt so.\n";
			exit();
		}
		
		if ($wgRequest->getVal("viewaux") == 1) {
			print ("<pre>");
			$sz = file_get_contents(bwMakePath($wgTempDir, 'bibexport.aux'));
			$sz = str_replace("<", "&lt;", $sz);
			$sz = str_replace(">", "&gt;", $sz);
			print ($sz);
			print ("</pre>");
		}
		$cmdline = $wgBibTeXExecutable.' '.bwMakePath($wgTempDir, 'bibexport');
		
		exec($cmdline, $var, $rv);
		
		$varj = implode("\n", $var);
		
		if (stristr($varj, "bibtex") === false) {
			print "Program doesn't seem to be BibTeX. Check the output and <code>\$wgBibTeXExecutable</code> in <code>BibwikiSettings.php</code>.<br>\n";
			print "<pre>Output of the program:\n".$varj."</pre>";
			exit();
		}

		if (file_exists(bwMakePath($wgTempDir, 'bibexport.bbl')) == false) {
			print "BibTeX produced no output. Check <code>\$wgBibTeXExecutable</code> in <code>BibwikiSettings.php</code>.<br><br>\n";
			exit();
		}
		
		if ($rv != 0) {
			print "BibTeX returned > 0. Check the output.<br><br>\n";
			print "<pre>".$varj."</pre>";
			exit();
		}
		
		$bblfile = file_get_contents(bwMakePath($wgTempDir, 'bibexport.bbl'));
		
		if (count($not_found) > 0) {
			print "<pre>Warning! This keys do not exist in the .bib-file:\n".implode(", ", $not_found)."</pre>";
		}

		if (stristr($bblfile, "bibitem") === false and 
		    stristr($bblfile, "bibliography") === false and 
		    stristr($bblfile, "begin") === false) {
			print "BibTeX produced malformed output. Check file contents.\n";
			print "<pre><b>Executed file:</b>\n$wgBibTeXExecutable</pre>";
			print ("<pre><b>Input:</b> \n");
			$sz = file_get_contents(bwMakePath($wgTempDir, 'bibexport.aux'));
			$sz = str_replace("<", "&lt;", $sz);
			$sz = str_replace(">", "&gt;", $sz);
			print ($sz);
			print ("</pre>");
			print "<pre><b>BibTeX's messages:</b>\n".$varj."</pre>";
			print "<pre><b>Output:</b> \n".$bblfile."</pre>";
			exit();
		}
		
		if (($wgRequest->getVal("debug") == 1 or $wgRequest->getVal("content") != "")
		    and stristr($varj, "error message") !== false)
			print "<pre>".$varj."</pre>";
		elseif (stristr($varj, "error message") !== false)
			print "BibTeX reported errors. <a href=\"".$bibliography->getLocalURL(array('action=export', $bibliography->mBibfileQuery, $bibliography->mFilterQuery, 'style='.$style, "font=".$wgRequest->getVal("font"), "debug=1"))."\">Click here to see them.</a><br><br>\n";
		
		if ($wgRequest->getVal("viewbbl") == 1) {
			print ("<pre>");
			$sz = file_get_contents(bwMakePath($wgTempDir, 'bibexport.bbl'));
			$sz = str_replace("<", "&lt;", $sz);
			$sz = str_replace(">", "&gt;", $sz);
			print ($sz);
			print ("</pre>");
		}
		
		# PROCESS BBL-FILE: TRANSFORM INTO HTML-CODE
		
		print ($this->convert(bwMakePath($wgTempDir, 'bibexport.bbl')));
		
		print ("</body></html>");
		
		exit();
	}
}


?>