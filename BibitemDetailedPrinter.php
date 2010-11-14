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
class BibitemDetailedPrinter {
	
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

	/**
	 * @var array
	 */
	var $mWikiKeys; 
	
	function BibitemDetailedPrinter($bibfileQuery="", $wikikeys=array()) {
		$this->mBibfileQuery = $bibfileQuery;
		$this->mWikiKeys = $wikikeys;
	}
	
	/**
	 * The main routine. Returns a string with the prettyprinted bibitem.
	 *
	 * @param object $this->mBibitem
	 * @return string
	 */
	function prettyPrint($bibitem) {
		$this->mBibitem = $bibitem;
		#$type = mb_strtolower($this->mBibitem->getType());
		#if ($type == "book") return $this->printBook();
		#elseif ($type == "article") return $this->printArticle();
		#elseif ($type == "incollection") return $this->printIncollection();
		#elseif ($type == "inbook") return $this->printInbook();
		return $this->printMisc();
	}
	
	/**
	 * Split and wrap an entry of a BibTeX record.
	 * @todo rewrite
	 */
	function splitAndWrapLine($keynr) {
		global $wgBreakLines, $wgLineBreakAt;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
		
		$key = $this->mBibitem->getKey($keynr);
  		$pre = "";
  		$middle = "";
  		$post = "";
  		$rv = array();

	  	if ($wgBreakLines == false) {
	  		# just reformat with given linebreaks
	  		$lines = $this->mBibitem->getPrettyVal($keynr);

	  		$lines = explode("\n", $lines);
	  		$linecnt = 0;
	  		foreach($lines as $line) {
	  			if ($line != "")
	  			{
		  			$linecnt++;
		  			if ($linecnt == 1) {
						$middle .= trim($line, " \n\r\t");
		  			}
		  			else
						$middle .= sprintf("\n  %-12s    %s", "", trim($line, " \n\r\t,"));
				}
	  		}
	  	} 
	  	else {
	  		# complete reformat -- wrap lines longer than $wgLineBreakAt
			$value = $this->mBibitem->getPrettyVal($keynr);
			
	  		$sep = " ";
	  		if (strtolower($key)=="url" and strpos($value, "/") > 0) {
		  		$chunks = preg_split('/(\/)/', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		  		$sep = "";
	  		}
	  		else {
		  		$chunks = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		  		$sep = " ";
	  		}
	  		$line = "";
	  		$linecnt = 0;
	  		$chunkcnt = 0;
	  		foreach ($chunks as $chunk) {
	  			$chunkcnt++;
	  			if ($line <> "" and 
				    bwStrlenWithoutHTML($line.$sep.$chunk) > $wgLineBreakAt) {
		  			$linecnt++;
					if ($linecnt == 1)
						$middle = trim($line, " \n\r\t,");
					else
						$middle .= sprintf("\n  %-12s   %s", "", trim($line, " \n\r\t,"));
					$line = "";
	  			}
	  			if ($chunkcnt == 1)
	  				$line = $chunk;
	  			else
	  				$line .= $sep.$chunk;
	  		}
			$linecnt++;
			if ($linecnt == 1)
				$middle = trim($line, " \n\r\t,");
			else
				$middle .= sprintf("\n  %-12s   %s", "", trim($line, " \n\r\t,"));
		}
		$delims = Bibitem::getDelimiters($this->mBibitem->getVal($keynr));
  		$pre = sprintf("  %-12s = %s", $key, $delims["left"]);
  		$post = sprintf("%s,\n", $delims["right"]);
  		return array($pre, $middle, $post);
	}
	
	function printMisc() {
		global $wgRequest, $wgDownloadsUrl, $wgISBNLinkTags, $wgTitleLink,
			$wgAuthorLink, $wgISBNLink,	$wgUser, $wgURLReverseReplacements,
			$wgValueDelimRight,	$wgTitleLinkTags;
        
		#LoadSettings
        if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
        if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        	include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$output .= # $this->mBibfile->getPosition()."/".$bibs_printed.": ". #debug 
		           "@".$this->mBibitem->getType().'{<a name="'.$this->mBibitem->getCiteKey().'" />';
		
		/* NS_BIB if ($page_exists["Bib:".$this->mBibitem->getCiteKey()] > 0) 
			$output .= $wgUser->getSkin()->makeKnownLink("Bib:".$this->mBibitem->getCiteKey(), $this->mBibitem->getCiteKey());
		else
			$output .= $wgUser->getSkin()->makeBrokenLink("Bib:".$this->mBibitem->getCiteKey(), $this->mBibitem->getCiteKey());
		*/
		
		if ($this->mWikiKeys[$this->mBibitem->getCiteKey()] > 0) 
			$output .= $wgUser->getSkin()->makeKnownLink($this->mBibitem->getCiteKey(), $this->mBibitem->getCiteKey());
		else
			$output .= $wgUser->getSkin()->makeBrokenLink($this->mBibitem->getCiteKey(), $this->mBibitem->getCiteKey());

		$output .= ",\n";

		for($keynr=0; $keynr < $this->mBibitem->getValueCount(); $keynr++) {
			$key = $this->mBibitem->getKey($keynr);
			list($pre, $val, $post) = $this->splitAndWrapLine($keynr);
			
			#$wgOut->addHTML($pre);
			#$wgOut->addHTML($val);
			#$wgOut->addHTML($post."<br/>");
			
	    	if (bwStrEqual($key, "crossref") and $val != "") {
		    	$output .= $pre.'<a href="'.Bibliography::getLocalURL(array('view=detailed', 'startkey='.$val, $this->mBibfileQuery)).'">'.$val.'</a>'.$post;
	    	}
	    	elseif (bwStrEqual($key, "doi") and $val != "") {
		    	$output .= $pre.'<a target="doi" href="http://dx.doi.org/'.$val.'">'.$val.'</a>'.$post;
	    	}
	    	elseif (bwStrEqual($key, "arxiv") and $val != "") {
		    	$output .= $pre.'<a target="arxiv" href="http://arxiv.org/abs/'.$val.'">'.$val.'</a>'.$post;
	    	}
	    	elseif (bwStrEqual($key, "isbn") and $val != "" and !empty($wgISBNLinkTags)) {
	    		$post = trim($post, "\n");
	   			$isbn = str_replace("-", "", $val);
	   			$linktags = array();
	   			foreach($wgISBNLinkTags as $t) {
	   				$href = str_replace("\$isbn", $isbn, $t["href"]);
	   				$href = str_replace("\$self", Bibliography::getLocalURL(), $href);
	   				$linktags[] = '<span class="bibeditsection"><a href="'.$href.'" target="'.$t["target"].'">'.$t["text"].'</a></span>';
	   			}
	   			$linktags = implode(' <span class="bibeditsection">|</span> ', $linktags);
	   			if ($wgISBNLink != "") {
					$href = str_replace("\$isbn", $isbn, $wgISBNLink);
			    	$output .= $pre.'<a class="invisible" href="'.$href.'" target="aleph">'.$val.'</a>'.$post.' '.$linktags."\n";
			    }
			    else {
			    	$output .= $pre.$val.$post.' '.$linktags."\n";
			    }
	    	}
	    	elseif ((bwStrEqual($key, "author") or bwStrEqual($key, "editor"))
	    			and $val != "" and $wgAuthorLink != ""
	    		) {
	    		$authors = explode(" and", $val);
	    		foreach ($authors as $a) {
	    			$a = trim($a);	
	    			if ($a != "others" and $a != "") {
	    				$pos = strpos($val, $a);
	    				$len = strlen($a);

	    				$as = $a;
		    			#$as = str_replace("~", " ", $as);
		    			$as_parts = bwParseAuthor($as);
		    			#$as = str_replace("{", "", $as);
		    			#$as = str_replace("}", "", $as);
		    			
			    		$no_utf8_as = urlencode($as);
			    		$utf8_as = urlencode($as);
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

				    	$val = substr_replace($val, '<a href="'.$href.'" target="aleph">'.$a.'</a>', $pos, $len);
	    			}
	    		}
		    	$output .= $pre.$val.$post;
	    	}
	    	elseif ((bwStrEqual($key, "keywords") or bwStrEqual($key, "tags")) and $val != "") {
	    		$keywords = preg_split("/(\s+)/", $val, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	    		$m = "";
	    		foreach ($keywords as $keyword) {
	    			if (preg_match("/\s+/", $keyword)) 
	    				$m .= $keyword;
	    			else {
			    		$keyword = str_replace('&lt;', "", $keyword);
		    			$keyword = str_replace('&gt;', "", $keyword);
		    			$keyword = trim($keyword, " ,;<>");
		    			if ($keyword != "") {
					    	$m .= '<a class="invisible" href="'.
					    		Bibliography::getLocalURL(
					    			array('action='.$wgRequest->getVal("action"), 
					    				'keyword='.$keyword, 
					    				$this->mBibfileQuery
					    			)
					    		).'">'.$keyword.'</a>';
				    	}
			    	}
	    		}
		    	$output .= $pre.$m.$post;
	    	}
	    	elseif (bwStrEqual($key, "title") and $val != "" and $wgTitleLink != "") {
	    		$val_with_reduced_spaces = preg_replace("/\s+/", " ", $val);
	    		$val_with_reduced_spaces = bwRemoveHTML($val_with_reduced_spaces);
	    		$as = urlencode($val_with_reduced_spaces);
	    		$utf8_as = urlencode(bwHTMLDecode($val_with_reduced_spaces));

	    		$href = str_replace('$title', $as, $wgTitleLink);
	    		$href = str_replace('$utf8_title', $utf8_as, $href);
		    	$output .= $pre.'<a href="'.$href.'" target="aleph">'.$val.'</a>'.$post;
	    	}
	    	elseif (bwStrEqual($key, "url") and $val != "") {
	    		$url = $val;
				# delete indent spaces of wrapped urls
	    		$url = bwRemoveHTML($url);
	    		$url = preg_replace("/\\n\s+/", "", $url);

				foreach($wgURLReverseReplacements as $from => $to) {
					$url = preg_replace($from, $to, $url);
				}

		    	$output .= $pre.'<a target="extern" class="invisible" href="'.$url.'">'.$val.'</a>'.$post;
	    	}
	    	elseif ((bwStrEqual($key, "docname") or
	    	         bwStrEqual($key, "file") or
	    	         bwStrEqual($key, "pdf")) and $val != ""
	    	         and $wgDownloadsUrl != "") {
	    		$post = trim($post, "\n");

	    		$url = $val;
	    		$url = bwRemoveHTML($url);
				# reverse the replacements
				foreach($wgURLReverseReplacements as $from => $to)
					$url = str_replace($from, $to, $url);

	   			$url = preg_replace("/\s{2,}/", " ", $url);
	   			$js_url = str_replace("'", "\\'", $url);
	   			$rename_link = '';
		    	if (Bibliography::userIsAllowedToEdit())
		   			$rename_link = ' <span class="bibeditsection">[<span class="renamesection" onClick="return rename(\''.$js_url.'\',\''.$this->mBibitem->getCiteKey().'\',\''.$keycounter[$this->mBibitem->getCiteKey()].'\');">'.wfMsg('bibwiki_rename').'</span>]</span>';
	    		$output .= $pre.'<a class="invisible" target="docname" href="'.$wgDownloadsUrl.'/'.$url.'">'.$val.'</a>'.$post.$rename_link."\n";
	    	}
	    	else
	    		$output .= $pre.$val.$post;
		}
		return $output;
	}
	
}