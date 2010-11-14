<?php
if (!defined('MEDIAWIKI'))
	die();

/**
 * Bibfile
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

include_once(dirname(__FILE__)."/Misc.php");

/**
 * Class for accessing .bib files.
 */
class Bibfile {
	var $mFilename;
	var $mHandle;
	var $mIsOpen;
	var $mPosition;
	var $mFilter;
	var $mFilterArray;
	var $mKeys;
	var $mDoubleKeys;
	var $mMacros;

	/**
	 * insertNew() and saveChanges() store their generated BibTeX key
	 * here. This is needed for the "Location:" redirect.
	 *
	 * @access private
	 */
	var $mTmpCiteKey;
	
	function getCiteKeyOfLastCommand() {
		return $this->mTmpCiteKey;
	}

	function open() {
		if ($this->mIsOpen)
			$this->close();
		$this->mPosition = 0;
		$this->mKeys = array();
		$this->mDoubleKeys = array();
		$this->mIsOpen = false;
		if (file_exists($this->getAbsoluteName()) == false)
			return false;
		if (is_readable($this->getAbsoluteName()) == false)
			return false;
		$this->mHandle = @fopen($this->getAbsoluteName(), "r");
		if ($this->mHandle == false)
			return false;
		
		$this->mIsOpen = true;
		return true;
	}
	
	static function checkBibfilename($filename) {
		global $wgDefaultBib, $wgRequest, $wgUser, $wgBibPath;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		if ($filename == "") $filename = $wgDefaultBib;
		
		if ($wgUser->isAnon() == false)
			$userdir = "(".$wgUser->getName()."\\".DIRECTORY_SEPARATOR.")?";
	
		if (preg_match("/^".$userdir.'[\w_\-]+\.bib$/', $filename) == false or
			strlen($filename) > 255 or 
			file_exists(bwMakePath($wgBibPath, $filename)) == false or
			is_readable(bwMakePath($wgBibPath, $filename)) == false
			)
			$filename = $wgDefaultBib;

		return $filename;
	}
	
	function setFilter($filter="") {
		$this->mFilter = bwDiacriticsSimplify($filter);
		$this->mFilterArray = array();
		if ($this->mFilter != "" and strpos($this->mFilter, " ") !== false) 
			$this->mFilterArray = explode(" ", $this->mFilter);
		elseif ($this->mFilter != "")
			$this->mFilterArray = array($this->mFilter);
	}
	
	function init($filename="", $filter="") {
		$this->close();
		$this->mHandle = 0;
		$this->mIsOpen = false;
		$this->mPosition = 0;
		$this->mFilter = "";
		$this->mFilterArray = array();
		$this->mMacros = array();
		$this->mKeys = array();
		$this->mDoubleKeys = array();
		$this->setFilter($filter);
		$this->mFilename = $this->checkBibfilename($filename);
	}
	
	function close() {
		if ($this->mIsOpen) {
			$this->mPosition = 0;
			$this->mKeys = array();
			$this->mDoubleKeys = array();
			@fclose($this->mHandle);
			$this->mIsOpen = false;
		}
	}
	
	/**
	 * Returns true if there are no more bibtex records that pass the filter.
	 */
	function nomoreFilteredItems() {
		if (!$this->mIsOpen)
			return true;
		if (feof($this->mHandle)) return true;
		$filepos = ftell($this->mHandle);
		$counter = $this->mPosition;
		$rv = ($this->nextFilteredRecord() === false);
		fseek($this->mHandle, $filepos);
		$this->mPosition = $counter;
		return $rv;
	}
	
	function getName() {
		return $this->mFilename;
	}
	
	function getAbsoluteName() {
		global $wgBibPath;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		return bwMakePath($wgBibPath, $this->mFilename);
	}
	
	function getPosition() {
		return $this->mPosition;
	}
	
	function isStartOfBibItem($s) {
		if (strpos(trim($s), "@") === 0 and
			stristr($s, "@String") === false and 
			stristr($s, "@Comment") === false and 
			stristr($s, "@Preamble") === false)
			return true;
		return false;
	}
	
	/**
	 * Extracts the BibTeX key from $s.
	 * 
	 * @param string $s A plain BibTeX record
	 * @return string
	 */
	function parseCiteKey($s) {
		$matches = array();
		if (preg_match("/^\s*@\s*\w+\s*[{(]{1,1}\s*(.+)\s*,/U", $s, $matches) === false)
			return false;
		return $matches[1];
	}
	
	/**
	 * Reads the keys of a bibfile to $this->mKeys and double keys to
	 * $this->mDoubleKeys. Returns an array of all keys. Doesn't touch
	 * internal file handle.
	 *
	 * @return array
	 */
	function getKeys() {
		if (count($this->mKeys) == 0) {
			if ($fh = @fopen($this->getAbsoluteName(),'r')) {
				while (!feof($fh)) {
					$s = fgets($fh);
					if ($this->isStartOfBibitem($s)) {
						$key = $this->parseCiteKey($s);
						if ($key != false and $key != "") {
							if (isset($this->mKeys[$key]))
								$this->mKeys[$key]++;
							else
								$this->mKeys[$key] = 1;
							if ($this->mKeys[$key] > 1)
								$this->mDoubleKeys[$key] = 1;
						}
					}
				}
				fclose($fh);
			}
		}
		return array_keys($this->mKeys);
	}

	function getMacros() {
		return $this->mMacros;
	}

	function getDoubleKeys() {
		if (count($this->mKeys) == 0)
			$this->getKeys();
		return array_keys($this->mDoubleKeys);
	}
	
	/**
	 * parse a string definition in key/string pair
	 * 
	 * @param string $s the string definition " @string{<key> = <string>} "
	 */
	function parseStringDefinition($s) {
		$tmp = str_replace("@String", "", $s);
		$tmp = trim($tmp, " \t\n\r");
		$tmp = Bibitem::removeDelimiters($tmp);
		$parts = explode('=', $tmp, 2);
		if (count($parts) == 2) {
			$key = trim($parts[0], " \t\n\r");
			$val = trim($parts[1], " \t\n\r");
			$val = Bibitem::removeDelimiters($val);
			if ($key != "" and $val != "")
				return array("key" => mb_strtolower($key), "string" => $val);
			else
				return false;
		}
		return false;
	}
	
	/**
	 * Gets the next unfiltered BibTeX record
	 *
	 * Doesn't increase $this->mPosition!
	 *
	 * @todo Parses string definitions too, but expects them to be in one line!
	 * @return array <code>array("key" => $key, "record" => $bibtexrecord)</code>
	 */
	function nextRecord() {
		if (!$this->mIsOpen) return false;
		$filepos = ftell($this->mHandle);

		do {
			# go to the the start of the next bibtex record
			
		  	$s = fgets($this->mHandle);
			if (feof($this->mHandle)) {
				# leave everything untouched 
				fseek($this->mHandle, $filepos);
				return false;
			}
	
			# if we stepped over a string defintion store it for future use
			if (strpos(trim($s), "@String") === 0) {
				/**
				 * @todo make better multi-line @string parsing
				 */
				$s = trim($s);
				while (substr($s,-1) != "}") {
					if (feof($this->mHandle)) return false;
				  	$s .= fgets($this->mHandle);
					$s = trim($s);
				}
				
				$strdef = $this->parseStringDefinition($s);
				if (is_array($strdef)) {
					$this->mMacros[$strdef["key"]] = $strdef["string"];
					# set filepos after string defintion
					$filepos = ftell($this->mHandle);
				}
			}
		} while (!$this->isStartOfBibItem($s));

		$key = $this->parseCiteKey($s);
		if ($key === false) {
			# no key, so leave everything untouched 
			fseek($this->mHandle, $filepos);
			return false;
		}
		
		# ok, we have a bibtex record
		# grab everything until the start of the end of the record,
		# a single } or ) in a line
		$record = $s;
		do {
			$filepos = ftell($this->mHandle);
		  	$s = fgets($this->mHandle);

			if (strpos(trim($s), "}") === 0 or
				strpos(trim($s), ")") === 0) {
			  	$record .= $s;
				break;
			}
		  	/*
		  	if ($this->isStartOfBibItem($s) or
		  	    strpos(trim($s), "@String") === 0) {
		  	    #ups, too far
				fseek($this->mHandle, $filepos);
				break;
		  	}
		  	*/
		  	$record .= $s;
		} while (!feof($this->mHandle));

		return array("key" => $key, "record" => bwToUtf8($record));
	}
	
	/**
	 * Return a record from the file.
	 */
	function loadRecord($citekey, $nr=1) {
		#start from beginning
		if ($this->mIsOpen === true)
			$this->close();
		$this->open();
		
		$found = false;
		$key_cnt = 0;
		$lwrcitekey = strtolower($citekey);
		$rv = false;
		do {
			$rec = $this->nextRecord();
			if ($rec === false) break;

			if (bwStrEqual(strtolower($rec["key"]), $lwrcitekey)) {
				#found
				$key_cnt++;
				if ($key_cnt == $nr) {
					$rv = $rec["record"];
					$found = true;
				}
			}
		} while (!$found);
		$this->close();
		return $rv;
	}
	
	/**
	 * Returns true if $bibtexrecord passes through the filter.
	 *
	 * @param  string  $bibtexrecord  a plain text bibtex record
	 * @return boolean 
	 */
	function passThroughFilter($bibtexrecord) {
		$found = true;
		if (count($this->mFilterArray) > 0) {
			$reduced_bibtexrecord = preg_replace("/\W+/", "", $bibtexrecord);
			#takes too long $bibtexrecord = bwTeXToHTML($bibtexrecord);
			#$bibtexrecord = bwToUtf8($bibtexrecord);
			$bibtexrecord = bwDiacriticsSimplify($bibtexrecord);
			foreach ($this->mFilterArray as $filter) {
				$reduced_filter = preg_replace("/\W+/", "", $filter);
				if ($filter <> "" and 
					stristr($bibtexrecord, $filter) === false and
					stristr($reduced_bibtexrecord, $reduced_filter) === false
				) {
					$found = false;
					break;
				}
			}
		}
		return $found;
	}

	/**
	 * Return the next bibtex record that passes the filter.
	 * Increases $this->mPosition.
	 *
	 * @return string The bibtex record
	 */
	function nextFilteredRecord() {
		if ($this->mIsOpen === false) return false;
		if (feof($this->mHandle)) return false;

		do {
			$data = $this->nextRecord();
			if ($data == false) return false;
			$passed = $this->passThroughFilter($data["record"]);
		} while (!$passed);
		$this->mPosition++;
		return $data["record"];
	}

	function backup() {
		global $wgKeepBackups, $wgBackupPath;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		if ($wgKeepBackups > 0) {
			copy($this->getAbsoluteName(), bwMakePath($wgBackupPath, $this->getName()).".".(time()));
			$this->removeOldBackups();
		}
	}

	function removeOldBackups() {
		global $wgBackupPath, $wgKeepBackups;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
		
		$dir = dir(rtrim($wgBackupPath, DIRECTORY_SEPARATOR));
		$files = array();
		while (($f = $dir->read()) !== false) {
			if ($f != "." and $f != "..") {
				#truncate timestamp from filename
				$fname = preg_replace("/\.\d+$/", "", $f);
				# print "#".$fname."#<br>";
				# print $f."<br>";
				$files[$fname][] = $f;
			}
		}
		# print "<br>====<br>";
		$dir->close();
		foreach($files as $f) {
			# print count($f)."<br>";
			rsort($f);
			while(count($f) > $wgKeepBackups) {
				$fn = array_pop($f);
				# print $fn."<br>";
				unlink(bwMakePath($wgBackupPath, $fn));
			}	
		}
	}
	
	function formatRecordForWriting($record) {
		$bibitem = new Bibitem;
		$bibitem->set($record);
		$bibitem->correctUserErrors();
		if (!$bibitem->parse()) return false;
		$record = $bibitem->formatForWriting($this->getKeys());
		$this->mTmpCiteKey = $bibitem->getCiteKey();
		return $record;
	}
	
	/**
	 * Inserts $record at the beginning of the file.
	 *
	 * @param string $record A plain BibTeX record.
	 * @return boolean Success status
	 */
	function insertRecord($record) {
		global $wgRequest, $wgBackupPath, $wgKeepBackups,
			$wgConvertAnsiToTeX;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
	        
	    $in = @fopen($this->getAbsoluteName(),'r');
	    $out = @fopen($this->getAbsoluteName().".tmp",'w');
	    
	    $record = $this->formatRecordForWriting($record);
	    if ($record == false) return false;
	        
		if ($in and $out) {
			$inserted = false;
			while (!feof($in)) {
				$sz = fgets($in);
				if (!$inserted and $this->isStartOfBibItem($sz)) {
					fputs($out, $record."\n");
					$inserted = true;
				}
				fputs($out, $sz);
			}
			if (!$inserted) fputs($out, $record."\n");
			fclose($in);
			fclose($out);

			if (file_exists($this->getAbsoluteName().".bak"))
				unlink($this->getAbsoluteName().".bak");
			rename($this->getAbsoluteName(), $this->getAbsoluteName().".bak");
			rename($this->getAbsoluteName().".tmp", $this->getAbsoluteName());
	
			$this->backup();
		}
		else return false;
		return true;
	}
	
	/**
	 * @todo Make this a method of Bibfile.
	 */
	function saveChanges($key, $keynr, $record) {
		global $wgOut, $wgRequest, $wgBackupPath, $wgKeepBackups,
			$wgConvertAnsiToTeX;
	
		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php');
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php');

	    $in = @fopen($this->getAbsoluteName(), 'r');
	    $out = @fopen($this->getAbsoluteName().".tmp", 'w');
	    
	    if ($record != "") {
		    $record = $this->formatRecordForWriting($record);
		    if ($record == false) return false;
		}
	        
		if ($in and $out) {
			$inserted = false;
			$in_record = false;
			$lwrkey = strtolower($key);
			$tmplwrkey = "";
			$tmpkeynr = 0;
			while (!feof($in)) {
				$sz = fgets($in);
				if (!$inserted and
				    $this->isStartOfBibItem($sz)) {
					$tmplwrkey = strtolower($this->parseCiteKey($sz));
					if ($tmplwrkey == $lwrkey) {
						$tmpkeynr++;
						if ($tmpkeynr == $keynr) {
							fputs($out, $record);
							$inserted = true;
							$in_record = true;
						}
					}
				}
				if ($in_record) {
					if (trim($sz) == "}")
						$in_record = false;
				}
				else
					fputs($out, $sz);
			}
			fclose($in);
			fclose($out);

			if (file_exists($this->getAbsoluteName().".bak"))
				unlink($this->getAbsoluteName().".bak");
			rename($this->getAbsoluteName(), $this->getAbsoluteName().".bak");
			rename($this->getAbsoluteName().".tmp", $this->getAbsoluteName());
	
			$this->backup();
		}
		else return false;
	}
}

?>