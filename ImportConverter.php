<?php
if (!defined('MEDIAWIKI'))
	die();

/**
 * ImportConverter
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
 * ImportConverter
 */
class ImportConverter{
	
	function ImportConverter() {
	}
	
	function convertPublisher(&$publisher, &$address) {
		global $wgImportReplacements, $wgValueDelimLeft, $wgValueDelimRight;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
	
		# returns "..." if no abbreviation is found
		# the abbreviation must not be enclosed in " "
		if ($publisher != "" and $publisher != $wgValueDelimLeft.$wgValueDelimRight)
			$publisher = $wgValueDelimLeft.$publisher.$wgValueDelimRight;
		if ($address != "" and $address != $wgValueDelimLeft.$wgValueDelimRight)
			$address = $wgValueDelimLeft.$address.$wgValueDelimRight;
		
		foreach ($wgImportReplacements as $key => $val) {
		    if (preg_match("/".$key."/i", $publisher)) {
		        $publisher = $val[0];
		        $address = $val[1];
		        break;
		    }
		}
	}
	
	/**
	 * Convert content from DDB.
	 */
	function convertDDBSource($content) {
		global $wgValueDelimLeft, $wgValueDelimRight, $wgDateTimeFormat,
			$wgTitleDelimLeft, $wgTitleDelimRight;
			
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
		
		$lines = explode("\n", $content);
		
		$booktitle = "";
		$booktitleaddon = "";
		$author = "";
   		$authorkey = "author";
		$andothers = "";
		$address = $wgValueDelimLeft.$wgValueDelimRight;
		$publisher = $wgValueDelimLeft.$wgValueDelimRight;
		$edition = "";
		$isbn = "";
		$pages = "";
		$year = "{}";
		$keywords = "";
		
		foreach($lines as $line) {
			$line = $line;
			$parts = explode("\t", $line, 2);
			$parts[1] = trim($parts[1]);
			if (preg_match("/\btitel\b/i", $parts[0])) {
				$parts = explode(" / ", $parts[1], 2);
				if (strstr($booktitleaddon, " : ") !== false) {
					$t = explode(" : ", $parts[0], 2);
					$booktitle = trim($t[0]);
					$booktitleaddon = trim($t[1]);
					$booktitleaddon[0] = strtoupper($booktitleaddon[0]);
				}
				else
					$booktitle = trim($parts[0]);
				if (mb_strpos($t[1], "...") !== false) $andothers = " and others";
			}
			elseif (preg_match("/verfasser/i", $parts[0])) {
		   		$author = str_replace(";", "and", $parts[1]);
		   		$author = preg_replace('/mitarb\./i', "", $author);
				if (preg_match("/hrsg/i", $parts[1])) {
					$authorkey = "editor";
					$author = preg_replace('/\s*\[?hrsg\.?\]?/i', "", $author);
					$author = preg_replace('/\s*\[?hg\.?\]?/i', "", $author);
				}
			}
			elseif (preg_match("/verleger/i", $parts[0])) {
				$t = explode(" : ", $parts[1], 2);
				$address = trim($t[0]);
				$publisher = trim($t[1]);
				$this->convertPublisher($publisher, $address);
			}
			elseif (preg_match("/jahr/i", $parts[0])) {
				if (preg_match("/\d+/", $parts[1], $matches)) {
					$year = $matches[0];
				}
			}
			elseif (preg_match("/isbn/i", $parts[0])) {
				if (preg_match("/[\d\-A-Z]+/", $parts[1], $matches)) {
					$isbn = $matches[0];
				}
			}
			elseif (preg_match("/ausgabe/i", $parts[0]) or 
			        preg_match("/auflage/i", $parts[0])) {
				if (preg_match("/\d+/", $parts[1], $matches)) {
					$edition = $matches[0].".";
				}
			}
			elseif (preg_match("/umfang/i", $parts[0])) {
				if (preg_match("/\d+/", $parts[1], $matches)) {
					$pages = $matches[0]." S.";
				}
			}
			elseif (preg_match("/schlagw/i", $parts[0])) {
				$keywords = str_replace("; ", "", $parts[1]);
				$keywords = str_replace(",", "", $keywords);
			}
		}
		
		$rv = "";
		$rv .= "@Book{*,\n";
		$rv .= ''.$authorkey.' = '.$wgValueDelimLeft.$author.$andothers.$wgValueDelimRight.','."\n";
		$rv .= 'title = '.$wgTitleDelimLeft.$booktitle.$wgTitleDelimRight.','."\n";
		if ($booktitleaddon <> "") $rv .= 'titleaddon = '.$wgTitleDelimLeft.$booktitleaddon.$wgTitleDelimRight.','."\n";
		if ($authorkey == "editor") {
			$rv .= 'booktitle = '.$wgTitleDelimLeft.$booktitle.$wgTitleDelimRight.','."\n";
			if ($booktitleaddon <> "") $rv .= 'booktitleaddon = '.$wgTitleDelimLeft.$booktitleaddon.$wgTitleDelimRight.','."\n";
		}
		$rv .= 'publisher = '.$publisher.','."\n";
		$rv .= 'address = '.$address.','."\n";
		if ($edition <> "") $rv .= 'edition = '.$wgValueDelimLeft.$edition.$wgValueDelimRight.','."\n";
		$rv .= 'year = '.$year.','."\n";
		if ($isbn <> "") $rv .= 'isbn = '.$wgValueDelimLeft.$isbn.$wgValueDelimRight.','."\n";
		if ($keywords <> "" or $this->mFilter <> "")
			$rv .= 'keywords = '.$wgValueDelimLeft.$this->mFilter.(($keywords == "" or $this->mFilter == "")? "" : " ").$keywords.$wgValueDelimRight.",\n";
		$rv .= 'bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n";
		$rv .= "}\n";
		return $rv;
	}
	
	/**
	 * Convert content from OPAC.
	 */
	function convertOpacSource($content) {
		global $wgValueDelimLeft, $wgValueDelimRight, $wgDateTimeFormat,
			$wgTitleDelimLeft, $wgTitleDelimRight;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$lines = explode("\n", $content);
		
		$booktitle = "";
		$booktitleaddon = "";
		$author = "";
		$authorkey = "";
		$andothers = "";
		$isbn = "";
		$address = $wgValueDelimLeft.$wgValueDelimRight;
		$publisher = $wgValueDelimLeft.$wgValueDelimRight;
		$edition = "";
		$isbn = "";
		$volume = "";
		$series = "";
		$edition = "";
		$isbn = "";
		$pages = "";
		$year = "";
		$keywords = "";
		
		foreach($lines as $line) {
			$line = $line;
			$parts = explode("\t", $line, 2);
			$parts[1] = preg_replace("/\bLink\B/", "", trim($parts[1]));
			$parts[1] = preg_replace("/&/", "\\&", $parts[1]);
			$parts[1] = preg_replace('/"\b/', ">>", $parts[1]);
			$parts[1] = preg_replace('/\b"/', "<<", $parts[1]);
			$parts[1] = preg_replace('/\b"/', "<<", $parts[1]);
			$parts[0] = preg_replace('/&nbsp;/', " ", $parts[0]);
			$parts[1] = preg_replace('/&nbsp;/', " ", $parts[1]);
			$parts[0] = trim($parts[0]);
			$parts[1] = trim($parts[1]);
			$parts[1] = preg_replace('/[\[\]\(\)]+/', "", $parts[1]);
			
			if (preg_match("/^titel$/i", $parts[0])) {
				$booktitle = trim($parts[1]);
			}
			elseif (preg_match("/^title\b/i", $parts[0])) {
				$p = explode(";", $parts[1], 2);
				$p = explode("/", $p[0], 2);
				
				if (trim($p[1]) <> "") {
			   		$authorkey = "author";
					$author = $p[1];
					$author = str_replace("\\&", "&", $author);
					if (preg_match("/edited/i", $author)) {
						$authorkey = "editor";
						$author = preg_replace('/edited(\s+by)+/i', "", $author);
						$author = preg_replace('/ed\.?(\s+by)+/i', "", $author);
					}
					if (preg_match("/eds\.?/i", $author) or 
					    preg_match("/editors/i", $author)) {
						$authorkey = "editor";
						$author = preg_replace('/\(?editors\)?/i', "", $author);
						$author = preg_replace('/\(?eds\.\)?/i', "", $author);
					}
					if (preg_match("/hg\.?/i", $author) or 
					    preg_match("/herausge/i", $author) or 
					    preg_match("/hrsg\.?/i", $author)) {
						$authorkey = "editor";
						$author = preg_replace('/herausgegeben\s+von/i', "", $author);
						$author = preg_replace('/\(?Hg\.?\)?/i', "", $author);
						$author = preg_replace('/\(?Hrsg\.?\)?/i', "", $author);
					}
					if (preg_match("/et\s+al/", $author) or 
					    preg_match("/\.\.\./", $author)) {
					    $author = preg_replace("/\s*\.{3,3}\s*et\s+al\.?/", " and others", $author);
					    $author = preg_replace("/\s*\.{3,3}/", " and others", $author);
					    $author = preg_replace("/\s*et\s+al\.?/", " and others", $author);
					}
					$author = trim($author, " .,");
					$author = preg_replace("/,\s+&/", " and", trim($author));
					$author = preg_replace("/\s+&/", " and", trim($author));
					$author = str_replace(",", " and", trim($author));
				}
				
				$p = explode(":", $p[0], 2);
				$booktitle = trim($p[0]);
				$booktitleaddon = trim($p[1]);
			}
			elseif (preg_match("/\bzusatz\s*zum\s*titel\b/i", $parts[0])) {
				$booktitleaddon = trim($parts[1]);
				$booktitleaddon[0] = strtoupper($booktitleaddon[0]);
			}
			elseif (preg_match("/^zusatz$/i", $parts[0])) {
				$booktitleaddon = trim($parts[1]);
				$booktitleaddon[0] = strtoupper($booktitleaddon[0]);
			}
			elseif (preg_match("/\bgesamttitel\b/i", $parts[0])) {
				$series = trim($parts[1]);
			}
			elseif (preg_match("/\bbandangabe\b/i", $parts[0])) {
				$volume = trim($parts[1]);
			}
			elseif (preg_match("/(\d+\.)?\s*Autor/i", $parts[0]) or 
			        preg_match("/(\d+\.)?\s*Author/i", $parts[0])) {
			    $parts[1] = preg_replace("/\d{4,4}\s*-\s*\d*/", "", $parts[1]);
				$m = explode(",", $parts[1], 2);
				$m = trim($m[1])." ".trim($m[0]);
				if ($author <> "")
					$author = $author . " and " . $m;
				else
					$author = $m;
		   		$author = preg_replace('/\s+mitarb\./i', "", $author);
		   		$authorkey = "author";
		   		if (mb_strpos($parts[1], "...") !== false)
		   			$andothers = " and others";
				if (preg_match("/hrsg/i", $parts[1])) {
					$authorkey = "editor";
					$author = preg_replace('/\s*?hrsg\.?/i', "", $author);
					$author = preg_replace('/\s*?hg\.?/i', "", $author);
					$author = preg_replace('/\s*?hrsg\.?/i', "", $author);
					$author = preg_replace('/\s*?hg\.?/i', "", $author);
				}
			}
			elseif (preg_match("/verfasserang/i", $parts[0])) {
		   		if (mb_strpos($parts[1], "...") !== false)
		   			$andothers = " and others";
			}
			elseif (preg_match("/verlag\b/i", $parts[0])) {
				$parts[1] = preg_replace('/verl\./i', "Verlag", $parts[1]);
				$publisher = trim($parts[1]);
				$this->convertPublisher($publisher, $address);
			}
			elseif (preg_match("/publisher/i", $parts[0])) {
				if (preg_match("/\d{4,4}/", $parts[1], $matches)) {
					$year = $matches[0];
				}
				$p = explode(":", $parts[1]);
				$address = trim($p[0]);
				$publisher = preg_replace("/\w?\d{4,4}/", "", $p[1]);
				$publisher = trim($publisher, " ,;.");
				$this->convertPublisher($publisher, $address);
			}
			elseif (preg_match("/jahr/i", $parts[0]) or 
			        preg_match("/jahr/i", $year[0])) {
				if (preg_match("/\d+/", $parts[1], $matches)) {
					$year = $matches[0];
				}
			}
			elseif (preg_match("/ausgabe/i", $parts[0]) or 
			        preg_match("/auflage/i", $parts[0])) {
				if (preg_match("/\d+/", $parts[1], $matches)) {
					$edition = $matches[0].".";
				}
			}
			elseif ((preg_match("/\bort\b/i", $parts[0]) or 
					 preg_match("/\bverlagsort\b/i", $parts[0])
					) and 
			        ($address == "" or $address = $wgValueDelimLeft.$wgValueDelimRight)) {
				$address = $parts[1];
			}
			elseif (preg_match("/isbn/i", $parts[0])) {
				if (preg_match("/[\d\-A-Z]+/", $parts[1], $matches)) {
					$isbn = $matches[0];
				}
			}
			elseif (preg_match("/umfang/i", $parts[0])) {
				if (preg_match("/\d+/", $parts[1], $matches)) {
					$pages = $matches[0]." S.";
				}
			}
			elseif (preg_match("/schlagw/i", $parts[0])) {
				$parts[1] = preg_replace("/<.*>/", "", $parts[1]);
				if ($keywords == "")
					$keywords = preg_replace("/\s*\//", "", $parts[1]);
				else
					$keywords = $keywords . " " . preg_replace("/\s*\//", "", $parts[1]);
			}
			elseif (preg_match("/subject/i", $parts[0])) {
				$parts[1] = preg_replace("/[\.]+/", "", $parts[1]);
				$parts[1] = preg_replace("/<.*>/", "", $parts[1]);
				if ($keywords == "")
					$keywords = preg_replace("/\s*\//", "", $parts[1]);
				else
					$keywords = $keywords . " " . preg_replace("/\s*\//", "", $parts[1]);
			}
		}
		
		if ($keywords <> "") {
			$keywords = explode(" ", $keywords);
			$keywords = array_unique($keywords);
			$keywords = join(" ", $keywords);
		}
		
		$rv = "";
		$rv .= "@Book{*,\n";
		$rv .= $authorkey.' = '.$wgValueDelimLeft.$author.$andothers.$wgValueDelimRight.','."\n";
		$rv .= 'title = '.$wgTitleDelimLeft.$booktitle.$wgTitleDelimRight.','."\n";
		if ($booktitleaddon <> "") $rv .= 'titleaddon = '.$wgTitleDelimLeft.$booktitleaddon.$wgTitleDelimRight.','."\n";
		if ($authorkey == "editor") {
			$rv .= 'booktitle = '.$wgTitleDelimLeft.$booktitle.$wgTitleDelimRight.','."\n";
			if ($booktitleaddon <> "") $rv .= 'booktitleaddon = '.$wgTitleDelimLeft.$booktitleaddon.$wgTitleDelimRight.','."\n";
		}
		$rv .= 'publisher = '.$publisher.','."\n";
		$rv .= 'address = '.$address.','."\n";
		//if ($series <> "") $rv .= 'series = '.$wgValueDelimLeft.$series.$wgValueDelimRight.','."\n";
		//if ($volume <> "") $rv .= 'volume = '.$wgValueDelimLeft.$volume.$wgValueDelimRight.','."\n";
		if ($edition <> "") $rv .= 'edition = '.$wgValueDelimLeft.$edition.$wgValueDelimRight.','."\n";
		if ($pages <> "") $rv .= 'pages = '.$wgValueDelimLeft.$pages.$wgValueDelimRight.','."\n";
		$rv .= 'year = '.$year.','."\n";
		if ($isbn <> "") $rv .= 'isbn = '.$wgValueDelimLeft.$isbn.$wgValueDelimRight.','."\n";
		if ($keywords <> "" or $this->mFilter <> "")
			$rv .= 'keywords = '.$wgValueDelimLeft.$this->mFilter.(($keywords == "" or $this->mFilter == "")? "" : " ").$keywords.$wgValueDelimRight.",\n";
		$rv .= 'bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n";
		$rv .= "}\n";
		return $rv;
	}
	
	/**
	 * Convert content from ProQuest/CSA.
	 */
	function convertSASource($content) {
		global $wgValueDelimLeft, $wgValueDelimRight, $wgDateTimeFormat,
			$wgTitleDelimLeft, $wgTitleDelimRight, $wgRequest;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$lines = explode("\n", $content);
		
		$title = "";
		$titleaddon = "";
		$author = "";
		$andothers = "";
		$journal = "";
		$volume = "";
		$number = "";
		$pages = "";
		$day = "";
		$month = "";
		$year = "";
		$keywords = "";
		$issn = "";
		$abstract = "";
		
		foreach($lines as $line) {
			$line = $line;
			$parts = explode("\t", $line, 2);
			$parts[0] = trim($parts[0]);
			$parts[1] = trim($parts[1]);
			if (preg_match("/\btitle\b/i", $parts[0])) {
				$title = $parts[1];
				$title = preg_replace('/\"\b/', ">>", $title);
				$title = preg_replace('/\b([\.\?\!]?)\"/', "$1<<", $title);
				$title = preg_replace('/\.$/', "", $title);
			}
			elseif (preg_match("/^author$/i", $parts[0])) {
				$parts[1] = preg_replace("/[^\wÃƒÂ¤ÃƒÂ¶ÃƒÂ¼Ãƒâ€žÃƒâ€“ÃƒÅ“ ÃƒÅ¸;,.\-]/", "", $parts[1]);
				$n = explode(";",$parts[1]);
				foreach ($n as $a) {
					$m = explode(",", $a, 2);
					$m = trim($m[1])." ".trim($m[0]);
					if ($author <> "")
						$author = $author . " and " . $m;
					else
						$author = $m;
				}
			}
			elseif (preg_match("/\byear\b/i", $parts[0])) {
				if (preg_match("/\d+/", $parts[1], $matches)) {
					$year = $matches[0];
				}
			}
			elseif (preg_match("/issn/i", $parts[0])) {
				if (preg_match("/[\d\-A-Z]+/", $parts[1], $matches)) {
					$issn = $matches[0];
				}
			}
			elseif (preg_match("/\bpages\b/i", $parts[0])) {
				$pages = str_replace("-","--",$parts[1]);
			}
			elseif (preg_match("/\bvolume\b/i", $parts[0])) {
				$volume = $parts[1];
			}
			elseif (preg_match("/\bissue\b/i", $parts[0])) {
				$number = $parts[1];
			}
			elseif (preg_match("/\bsource\b/i", $parts[0])) {
				$journal = $parts[1];
				if (preg_match("/[;,]?\s*(\d+)[;,]{1,1}\s*(\d+)[;,]{1,1}\s*([\d\-]+)/", $journal, $matches)) {
					#, 12, 4, 122-133
					$volume = $matches[1];
					$number = $matches[2];
					$pages = str_replace("-", "--", $matches[3]);
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*\bvol\b\.?\s*(\d+)/", $journal, $matches)) {
					#, vol. 12
					$volume = $matches[1];
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*\bv\b\.?\s*(\d+)/", $journal, $matches)) {
					#, v12
					$volume = $matches[1];
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*(\d+)\s+\((\d+)\)/", $journal, $matches)) {
					#, 12 (19)
					$volume = $matches[1];
					$number = $matches[2];
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*\((\d+)\)/", $journal, $matches)) {
					#, (19)
					$volume = $matches[1];
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*\bno\b\.?\s*(\d+)/", $journal, $matches)) {
					#, no. 19
					$number = $matches[1];
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*\bn\b\.?\s*(\d+)/", $journal, $matches)) {
					#, n19
					$number = $matches[1];
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*\bpp?\b\.?\s*([\d\-]+)/", $journal, $matches)) {
					#, pp. 19-23
					$pages = str_replace("-", "--", $matches[1]);
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*\bpp?\b\.?\s*(\w+)/", $journal, $matches)) {
					#, pp. np
					$pages = str_replace("np", "???", $matches[1]);
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/\-+\s*(\d+)?\s*(jan|feb|mar|apr|may|jun|jul|aug|sept|sep|oct|nov|dec)\.?\s+(\d{4,4})/i", $journal, $matches)) {
					#- Mar 2002
					# delete
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*(\d+)?\s*(jan|feb|mar|apr|may|jun|jul|aug|sept|sep|oct|nov|dec)\.?/i", $journal, $matches)) {
					#03 Mar
					$day = $matches[1];
					$month = $matches[2];
					$journal = str_replace($matches[0], "", $journal);
				}
				if (preg_match("/[;,]?\s*(\d{4,4})/", $journal, $matches)) {
					#, 2002
					$year = $matches[1];
					$journal = str_replace($matches[0], "", $journal);
				}
			}
			elseif (preg_match("/^abstract$/i", $parts[0])) {
				$abstract = "";
				$words = preg_split("/\s/", $parts[1]);
				$line = "";
				foreach ($words as $w) {
					if ($line <> "") $line .= " ";
					$line .= $w;
					if (mb_strlen($line) > 40) { 
						$abstract = $abstract."\n".$line;
						$line = "";
					}
				}
				$abstract = $abstract."\n".$line;
				$abstract = trim($abstract);
			}
		}
		
		$rv = "";
		$rv .= "@Article{*,\n";
		$rv .= 'author = '.$wgValueDelimLeft.$author.$andothers.$wgValueDelimRight.','."\n";
		$rv .= 'title = '.$wgTitleDelimLeft.$title.$wgTitleDelimRight.','."\n";
		if ($titleaddon <> "") $rv .= 'titleaddon = '.$wgTitleDelimLeft.$titleaddon.$wgTitleDelimRight.','."\n";
		$rv .= 'journal = '.$wgValueDelimLeft.$journal.$wgValueDelimRight.','."\n";
		if ($volume <> "") $rv .= 'volume = '.$volume.','."\n";
		if ($number <> "") $rv .= 'number = '.$number.','."\n";
		if ($pages <> "") $rv .= 'pages = '.$wgValueDelimLeft.$pages.$wgValueDelimRight.','."\n";
		if ($day <> "") $rv .= 'day = '.$wgValueDelimLeft.$day.$wgValueDelimRight.','."\n";
		if ($month <> "") $rv .= 'month = '.strtolower($month).','."\n";
		$rv .= 'year = '.$wgValueDelimLeft.$year.$wgValueDelimRight.','."\n";
		if ($isbn <> "") $rv .= 'isbn = '.$wgValueDelimLeft.$isbn.$wgValueDelimRight.','."\n";
		if ($issn <> "") $rv .= 'issn = '.$wgValueDelimLeft.$issn.$wgValueDelimRight.','."\n";
		if ($keywords <> "" or $this->mFilter <> "")
			$rv .= 'keywords = '.$wgValueDelimLeft.$this->mFilter.(($keywords == "" or $this->mFilter == "")? "" : " ").$keywords.$wgValueDelimRight.",\n";
		$rv .= 'bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n";
		if ($abstract <> "" and $wgRequest->getVal("abstract") == "on") $rv .= 'abstract = '.$wgValueDelimLeft.$abstract.$wgValueDelimRight.','."\n";
		$rv .= "}\n";
		return $rv;
	}
	
	/**
	 * Import content from Amazon.
	 */
	function convertAmazonSource($url) {
		global $wgValueDelimLeft, $wgValueDelimRight,
			$wgTitleDelimLeft, $wgTitleDelimRight, $wgDateTimeFormat,
			$accesskey, $wgAmazonURL;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
		
		if (empty($accesskey) or $accesskey == "" or $accesskey == "XXX")
			return wfMsg("bibwiki_error_empty_amazon_key");
		
		if (preg_match('/\d{5,}[A-Z]?/', $url, $matches))
			$isbn = $matches[0];
		else
			return;
		
		# Assemble the REST request URL.
		# Ex: http://webservices.amazon.com/onca/xml?Service=AWSECommerceService&AWSAccessKeyId=087VQWVFFHRTJC4Y89G2&Operation=ItemLookup&ItemId=349602495X&ResponseGroup=ItemAttributes&Version=2005-10-13
		$request =
		"http://webservices.".$wgAmazonURL."/onca/xml?" .
		"Service=AWSECommerceService&" .
		"AWSAccessKeyId=$accesskey&" .
		"Operation=ItemLookup&" .
		"ItemId=".$isbn."&" .
		"ResponseGroup=ItemAttributes&" .
		"Version=2005-10-13";
		
		$content = file_get_contents($request);
		
		$author = "";
		$authorkey = "author";
		$andothers = "";
		$booktitle = "";
		$booktitleaddon = "";
		$address = "";
		$address = $wgValueDelimLeft.$wgValueDelimRight;
		$publisher = $wgValueDelimLeft.$wgValueDelimRight;
		$isbn = "";
		$pages = "";
		$year = "";
		
		#print $content;
		
		if (preg_match_all('/<Author>\s*(.*)\s*<\/Author>/iU', $content, $matches)) {
			$matches = $matches[1];
			foreach ($matches as $a) {
				if ($author != "") {
					$author .= " and ";
					$andothers .= ".etal";
					#$authorkey = "editor";
				}
				$author .= $a;
			}
		}
		if (preg_match_all('/<Title>\s*(.*)\s*<\/Title>/iU', $content, $matches)) {
			$d = explode(".", $matches[1][0], 2);
			$booktitle = trim($d[0]);
			$booktitleaddon = trim($d[1]);
		}
		if (preg_match_all('/<Label>\s*(.*)\s*<\/Label>/iU', $content, $matches)) {
			$publisher = $matches[1][0];
			$this->convertPublisher($publisher, $address);
		}
		if (preg_match_all('/<NumberOfPages>\s*(.*)\s*<\/NumberOfPages>/iU', $content, $matches)) {
			$pages = $matches[1][0]." S.";
		}
		if (preg_match_all('/<ISBN>\s*(.*)\s*<\/ISBN>/iU', $content, $matches)) {
			$isbn = $matches[1][0];
		}
		if (preg_match_all('/<PublicationDate>\s*(\d{4,}).*<\/PublicationDate>/iU', $content, $matches)) {
			$year = $matches[1][0];
		}
	
		$rv = "";
		$rv .= "@Book{*,\n";
		$rv .= $authorkey.' = '.$wgValueDelimLeft.$author.$wgValueDelimRight.','."\n";
		$rv .= 'title = '.$wgTitleDelimLeft.$booktitle.$wgTitleDelimRight.','."\n";
		if ($booktitleaddon <> "") $rv .= 'titleaddon = '.$wgTitleDelimLeft.$booktitleaddon.$wgTitleDelimRight.','."\n";
		$rv .= 'publisher = '.$publisher.','."\n";
		if ($address <> "" and $address <> $wgValueDelimLeft.$wgValueDelimRight) $rv .= 'address = '.$address.','."\n";
		if ($pages <> "") $rv .= 'pages = '.$wgValueDelimLeft.$pages.$wgValueDelimRight.','."\n";
		$rv .= 'year = '.$year.','."\n";
		if ($isbn <> "") $rv .= 'isbn = '.$wgValueDelimLeft.$isbn.$wgValueDelimRight.','."\n";
		if ($keywords <> "" or $this->mFilter <> "")
			$rv .= 'keywords = '.$wgValueDelimLeft.$this->mFilter.(($keywords == "" or $this->mFilter == "")? "" : " ").$keywords.$wgValueDelimRight.",\n";
		$rv .= 'bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n";
		$rv .= "}\n";
		return $rv;
	}

	/**
	 * Convert content from ArXiv.
	 */
	function convertArxivSource($content) {
		global $wgValueDelimLeft, $wgValueDelimRight, $wgRequest,
			$wgTitleDelimLeft, $wgTitleDelimRight, $wgDateTimeFormat,
			$accesskey, $wgAmazonURL, $wgLineBreakAt;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
        
		$title = "";
		$author = "";
		$pages = "";
		$day = "";
		$month = "";
		$year = "{}";
		$arxivnr = "";
		$keywords = "";
		$abstract = "";
		
		$lines = explode("\n", $content);
		
		foreach($lines as $line) {
			$line = $line;
			if (preg_match('/title:\s+(.*)/i', $line, $matches)) {
				$title = trim($matches[1]);
			}
			if (preg_match('/authors:\s+(.*)/i', $line, $matches)) {
				$author = trim($matches[1]);
				$author = str_replace(", ", " and ", $author);
				$author = preg_replace('/\s+\([^\)]+\)/', '', $author);
			}
			if (preg_match('/abstract:\s+(.*)/i', $line, $matches)) {
				$abstract = "";
				$words = preg_split("/\s/", trim($matches[1]));
				$line = "";
				foreach ($words as $w) {
					if ($line <> "") $line .= " ";
					$line .= $w;
					if (mb_strlen($line) > $wgLineBreakAt) { 
						$abstract = $abstract."\n".$line;
						$line = "";
					}
				}
				$abstract = $abstract."\n".$line;
				$abstract = trim($abstract);
			}
			if (preg_match('/^\s*cite as:\s+([0-9a-zA-Z:\.\-\/]+)/i', $line, $matches)) {
				$arxivnr = $matches[1];
				$arxivnr = str_replace("arXiv:", "", $matches[1]);
			}
			if (preg_match('/^\s*\[v[0-9]+\]\s+[a-zA-Z,]+\s+([0-9]+)\s+([a-zA-Z]+)\s+([0-9]+)/i', $line, $matches)) {
				$day = $matches[1];
				$month = $matches[2];
				$year = $matches[3];
			}
		}
		
		if ($title == "") $title = trim($lines[0]);
		
		$rv = "";
		$rv .= "@Article{*,\n";
		$rv .= 'author = '.$wgValueDelimLeft.$author.$wgValueDelimRight.','."\n";
		$rv .= 'title = '.$wgTitleDelimLeft.$title.$wgTitleDelimRight.','."\n";
		$rv .= 'journal = '.$wgValueDelimLeft."ArXiv".$wgValueDelimRight.','."\n";
		if ($pages <> "") $rv .= 'pages = '.$wgValueDelimLeft.$pages.$wgValueDelimRight.','."\n";
		if ($day <> "") $rv .= 'day = '.$day.','."\n";
		if ($month <> "") $rv .= 'month = '.strtolower($month).','."\n";
		$rv .= 'year = '.$year.','."\n";
		$rv .= 'arxiv = '.$wgValueDelimLeft.$arxivnr.$wgValueDelimRight.','."\n";
		$rv .= 'url = '.$wgValueDelimLeft."http://arxiv.org/pdf/".$arxivnr.$wgValueDelimRight.','."\n";
		if ($keywords <> "" or $this->mFilter <> "")
			$rv .= 'keywords = '.$wgValueDelimLeft.$this->mFilter.(($keywords == "" or $this->mFilter == "")? "" : " ").$keywords.$wgValueDelimRight.",\n";
		$rv .= 'bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n";
		if ($abstract <> "" and $wgRequest->getVal("abstract") == "on") $rv .= 'abstract = '.$wgValueDelimLeft.$abstract.$wgValueDelimRight.','."\n";
		$rv .= "}\n";
		
		return $rv;
	}

	/**
	 * Convert content from Library of Congress.
	 */
	function convertLoCSource($content) {
		global $wgValueDelimLeft, $wgValueDelimRight, $wgRequest,
			$wgTitleDelimLeft, $wgTitleDelimRight, $wgDateTimeFormat,
			$wgLineBreakAt;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
        
		$title = "";
		$titleaddon = "";
		$author = "";
		$authorfield = "author";
		$publisher = "";
		$address = "";
		$pages = "";
		$year = "";
		$isbn = "";
		$lcc = "";
		$keywords = "";
		
		$lines = explode("\n", $content);
		
		foreach($lines as $line) {
			$line = trim($line);
			
			if (preg_match('/Personal Name:\s+([^0-9\(]+)/i', $line, $matches)) {
				$author = trim($matches[1], " ,.\t\n\r");
				$aparts = explode(" ",$author);
				$lpart = array_pop($aparts);
				if (mb_strlen($lpart) == 1) $author .= ".";
			}
			if (preg_match('/Main Title:\s+(.+)/i', $line, $matches)) {
				$l = $matches[1];
				if (mb_strpos($l, "edited by") !== false) {
					$authorfield = "editor";
				}
				if (preg_match('|(.+) / (edited by )?(.+)|i', $l, $m)) {
					$l = $m[1];
					$author = trim($m[3], " ,.\t\n\r");
					$aparts = explode(" ",$author);
					$lpart = array_pop($aparts);
					if (mb_strlen($lpart) == 1) $author .= ".";
					$author = str_replace(", ", " and ", $author);
					$author = str_replace(" with ", " and ", $author);
					if (mb_strpos($author, " ; translated") !== false) {
						$author = preg_replace("/ ; translated .*/", "", $author);
					}
				}
				if (preg_match('|(.+) : (.+)|i', $l, $m)) {
					$l = $m[1];
					$titleaddon = ucfirst($m[2]);
				}
				$title = trim($l," \t\n\r.");
			}
			if (preg_match('/isbn:\s+([0-9A-Z\-a-z]+)/i', $line, $matches)) {
				$isbn = $matches[1];
			}
			if (preg_match('/LC Control No.:\s+([0-9A-Z\-a-z]+)/i', $line, $matches)) {
				$lcc = $matches[1];
			}
			if (preg_match('|Published/Created:\s+(.+)|i', $line, $matches)) {
				$l = $matches[1];
				$m = explode(" : ", $l, 2);
				$address = str_replace(" ; ", "; ", $m[0]);
				$m = explode(", ", $m[1], 2);
				$publisher = $m[0];
				preg_match("|[0-9]+|", $m[1], $m);
				$year = $m[0];
			}
			if (preg_match('/description:\s+(.+)/i', $line, $matches)) {
				preg_match('/([0-9]+) p./', $matches[1], $m);
				$pages = $m[1];
			}
		}
		
		$rv = "";
		$rv .= "@Book{*,\n";
		$rv .= $authorfield.' = '.$wgValueDelimLeft.$author.$wgValueDelimRight.','."\n";
		$rv .= 'title = '.$wgTitleDelimLeft.$title.$wgTitleDelimRight.','."\n";
		if ($titleaddon <> "") $rv .= 'titleaddon = '.$wgTitleDelimLeft.$titleaddon.$wgTitleDelimRight.','."\n";
		if ($authorfield == "editor") {
			$rv .= 'booktitle = '.$wgTitleDelimLeft.$title.$wgTitleDelimRight.','."\n";
			if ($titleaddon <> "") $rv .= 'booktitleaddon = '.$wgTitleDelimLeft.$titleaddon.$wgTitleDelimRight.','."\n";
		}		
		if ($publisher <> "") $rv .= 'publisher = '.$wgValueDelimLeft.$publisher.$wgValueDelimRight.','."\n";
		if ($address <> "") $rv .= 'address = '.$wgValueDelimLeft.$address.$wgValueDelimRight.','."\n";
		if ($pages <> "") $rv .= 'pages = '.$wgValueDelimLeft.$pages.$wgValueDelimRight.','."\n";
		if ($isbn <> "") $rv .= 'isbn = '.$wgValueDelimLeft.$isbn.$wgValueDelimRight.','."\n";
		if ($lcc <> "") $rv .= 'lccn = '.$lcc.','."\n";
		if ($year <> "") $rv .= 'year = '.$year.','."\n";
		$rv .= 'bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n";
		$rv .= "}\n";
		
		return $rv;
	}
}