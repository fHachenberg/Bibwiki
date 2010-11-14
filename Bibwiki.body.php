<?php
if (!defined('MEDIAWIKI'))
	die();

/**
 * Bibwiki.body
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

include_once("includes/SpecialPage.php");
include_once(dirname( __FILE__ ) ."/BibMarkup.php");
require_once(dirname( __FILE__ ) ."/Misc.php");
require_once(dirname( __FILE__ ) ."/Bblfile.php");
require_once(dirname( __FILE__ ) ."/Bibitem.php");
require_once(dirname( __FILE__ ) ."/Bibfile.php");
require_once(dirname( __FILE__ ) ."/BibitemCompactPrinter.php");
require_once(dirname( __FILE__ ) ."/BibitemDetailedPrinter.php");
require_once(dirname( __FILE__ ) ."/ImportConverter.php");

/**
 * The special page.
 */
class Bibliography extends SpecialPage {
	/**
	 * @var Bibfile
	 */
	var $mBibfile;

	/**
	 * @var string
	 */
	var $mFilter;

	/**
	 * @var array
	 */
	var $mFilterArray;

	/**
	 * @var string
	 */
	var $mBibfileQuery;

	/**
	 * @var string
	 */
	var $mFilterQuery;

	/**
	 * @var string
	 */
	var $mAction;

	/**
	 * @var string
	 */
	var $mImportSource;

	/**
	 * @var string
	 */
	var $mBibfilename;

	/**
	 * @var string
	 */
	var $mStartkey;

	/**
	 * Constructor.
	 */
	function Bibliography() {
		global $wgRequest, $wgDefaultBib, $wgOut;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

        self::loadMessages();
        SpecialPage::SpecialPage(wfMsg("bibliography"));
	}

	function loadMessages() {
        static $messagesLoaded = false;
        global $wgMessageCache;
        if ( !$messagesLoaded ) {
	        $messagesLoaded = true;

	        require( dirname( __FILE__ ) . '/Bibwiki.i18n.php' );
	        foreach ( $allBibwikiMessages as $lang => $langMessages ) {
	                $wgMessageCache->addMessages( $langMessages, $lang );
	        }
        }
        return true;
	}

    function execute($par) {
        global $wgRequest, $wgOut, $wgHooks;

		$wgHooks['SkinTemplateContentActions'][] = 'fnBibwikiAddTabs';
		$wgHooks['BibliographyToolbox'][] = 'wfBibliographyToolbox';
		$wgHooks['MonoBookTemplateToolboxEnd'][] = 'wfBibliographyToolbox';

        $this->setHeaders();
		$this->processActions();
    }

    function checkPath($path) {
		$rv = true;
		if (is_readable($path) == false)
			$rv = false;
		elseif (is_writeable($path) == false)
			$rv = false;
		else {
			$d = @dir($path);
			if (empty($d))
				$rv = false;
			else
				$d->close();

			# testing file creation, renaming and unlinking

			$tmpname = time().".tmp";
	        $tmp = @fopen(bwMakePath($path, $tmpname), "w");
	        if ($tmp) {
	        	@fclose($tmp);
	        	if (!@rename(bwMakePath($path, $tmpname), bwMakePath($path, $tmpname.".new"))) {
					$rv = false;
		        	if (!@unlink(bwMakePath($path, $tmpname)))
						$rv = false;
	        	}
		        elseif (!@unlink(bwMakePath($path, $tmpname.".new")))
					$rv = false;
	        }
	        else
				$rv = false;
		}
		return $rv;
    }

	function checkFile($path_to_file) {
		if (@file_exists($path_to_file) == false or @is_readable($path_to_file) == false)
			return false;
		return true;
	}

	function checkSettings() {
		global $wgOut, $wgBibPath, $wgBackupPath, $wgKeepBackups, $wgDownloadsPath,
		$wgDefaultBib, $wgEnableExport, $wgTempDir, $wgBibTeXExecutable,
		$wgDateTimeFormat, $wgAmazonURL, $wgHowManyItemsPerPage, $wgEnableEdit,
		$wgEnableExport;

		### Load Settings ###
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );
	    else {
			$this->errorBox(wfMsg("bibwiki_error_no_settings")." <a href='".$this->getLocalURL(array("action=setup"))."'>".wfMsg("bibwiki_error_click_here")."</a>.", "");
			return false;
		}

		### $wgBibPath ###
		if (!$this->checkPath($wgBibPath))
		{
			$this->errorBox(wfmsg("bibwiki_error_no_bibpath"));
			return false;
		}

		### $wgBackupPath ###
		if ($wgKeepBackups > 0) {
			if (!$this->checkPath($wgBackupPath))
			{
				$this->errorBox(wfMsg("bibwiki_error_no_backuppath"));
				return false;
			}
		}

		### $wgDefaultBib ###
		if (file_exists(bwMakePath($wgBibPath, $wgDefaultBib)) == false or
		    is_readable(bwMakePath($wgBibPath, $wgDefaultBib)) == false)
		{
			$this->errorBox(wfMsg("bibwiki_error_no_default_bib"));
			return false;
		}

		### $wgDownloadsPath ###
		if ($wgDownloadsPath != "" and !$this->checkPath($wgDownloadsPath))
		{
			$this->errorBox(wfMsg("bibwiki_error_no_downloadspath"), wfMsg("bibwiki_warning"));
			#return false;
		}

		return true;
	}

	static function getStartkey() {
		global $wgRequest;

		$self = Bibliography::getStaticTitle()->getFullText();

		/**
		 * parse URLs with this form: Special:Bibliography/<file.bib>[/<key>]
		 */
		if (preg_match('|'.$self.'/([\w\d:\._\-/]+)|', $_SERVER['REQUEST_URI'], $matches))
		{
			$query = $matches[1];
			$query_parts = explode("/", $query);
			if (count($query_parts) > 1)
				return mb_strtolower(array_pop($query_parts));
		}
		return mb_strtolower($wgRequest->getVal("startkey"));
	}

	static function getBibfilename() {
		global $wgDefaultBib, $wgRequest, $wgUser, $wgBibPath;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$self = Bibliography::getStaticTitle()->getFullText();

		/**
		 * parse URLs with this form: Special:Bibliography/<file.bib>[/<key>]
		 */
		$filename = "";
		if (preg_match('|'.$self.'/([\w\d:\._\-/]+)|', $_SERVER['REQUEST_URI'], $matches))
		{
			$query = $matches[1];
			$query_parts = explode("/", $query);
			if (count($query_parts) > 1) {
				array_pop($query_parts);
				$filename = implode("/", $query_parts);
			}
			else
				$filename = $query;
		}
		else
			$filename = ($wgRequest->getVal("f") != "")? $wgRequest->getVal("f") : $_COOKIE["BIBWIKI_BIBFILE"];

		$filename = Bibfile::checkBibfilename($filename);

		if ($filename != $_COOKIE["BIBWIKI_BIBFILE"])
			setcookie("BIBWIKI_BIBFILE", $filename, time()+60*60*24);

		return $filename;
	}

	function init() {
		global $wgDefaultBib, $wgRequest, $wgBibPath, $wgOut;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$this->mStartkey = Bibliography::getStartkey();
		$this->mBibfilename = Bibliography::getBibfilename();

		#$wgOut->addHTML("key: |".$this->mStartkey."|<br/>");
		#$wgOut->addHTML("file: ".$this->mBibfilename."<br/>");

		$this->mFilter = trim($wgRequest->getVal("keyword"));
		$this->mAction = $wgRequest->getVal("action");

		$this->mActionQuery = "";
		$this->mFilterQuery = "";
		if ($this->mAction != "")
			$this->mActionQuery = "action=".$this->mAction;
		if ($this->mFilter != "")
			$this->mFilterQuery = "keyword=".$this->mFilter;

		$this->mBibfile = new Bibfile;
		$this->mBibfile->init($this->mBibfilename, $this->mFilter);
		$this->mBibfileQuery = "f=".$this->mBibfile->getName();
	}

	function processActions() {
		global $wgOut, $wgHooks, $wgRequest, $wgBookCoverDirectory,
		   $wgDefaultBib, $wgBibPath;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		if ($wgRequest->getVal("action") == "debug") {
			$this->debug();
		}

		$this->init();

		if ($this->mAction == "load_settings") {
			$this->loadSettings();
			return;
		}
		elseif ($this->mAction == "setup") {
			$this->loadSettingsForSetup();
			return;
		}
		elseif ($this->mAction == "setup2") {
			if ($this->checkPath($wgRequest->getVal("wgBibPath")) == false or
			    ($wgRequest->getVal("wgDownloadsPath") != "" and $this->checkPath($wgRequest->getVal("wgDownloadsPath")) == false) or
			    $this->checkFile(bwMakePath(
			    	$wgRequest->getVal("wgBibPath"),
			    	$wgRequest->getVal("wgDefaultBib")
			    )) == false) {
				$this->loadSettingsForSetup();
				return;
			}
			else {
				if ($this->generateBibwikiSettings()) return;
			}
		}
		elseif ($this->mAction == "save_settings") {
			$this->saveSettings();
			return;
		}

		if ($this->checkSettings() === false) return;


		if ($this->mAction == "export") {
			$bblfile = new Bblfile;
			$bblfile->export($this);
		}
		elseif ($this->mAction == "export_from_doc") {
			$wgHooks['RenderPageTitle'] = array('wfRenderBibliographyTitle');
			$this->exportFromDocument();
		}
		elseif ($this->mAction == "testsql") {
			$this->testSQL();
		}
		elseif ($this->mAction == "viewauthors") {
			$wgHooks['RenderPageTitle'] = array('wfRenderBibliographyTitle');
			$wgHooks['BeforePageDisplay'][] = 'wfBeforePageDisplay';
			$this->viewAuthors();
		}
		elseif ($this->mAction == "viewkeywords") {
			$wgHooks['RenderPageTitle'] = array('wfRenderBibliographyTitle');
			$wgHooks['BeforePageDisplay'][] = 'wfBeforePageDisplay';
			$this->viewKeywords();
		}
		elseif ($this->mAction == "viewsource") {
			$wgHooks['RenderPageTitle'] = array('wfRenderBibliographyTitle');
			$wgHooks['BeforePageDisplay'][] = 'wfBeforePageDisplay';
			$this->viewSource();
		}
		elseif ($this->mAction == "viewstats") {
			$wgHooks['RenderPageTitle'] = array('wfRenderBibliographyTitle');
			$wgHooks['BeforePageDisplay'][] = 'wfBeforePageDisplay';
			$this->viewStatistics();
		}
		elseif ($this->mAction == "search") {
			$wgHooks['RenderPageTitle'] = array('wfRenderBibliographyTitle');
			$wgHooks['BeforePageDisplay'][] = 'wfBeforePageDisplay';
			$this->globalSearch();
		}
		elseif ($this->mAction == "new") {
			if ($this->userIsAllowedToEdit())
				$this->newEntry();
			else
				$this->errorBox(wfMsg("bibwiki_error_edit_forbidden"));
		}
		elseif ($this->mAction == "savenew") {
			if ($this->userIsAllowedToEdit())
				$this->saveNew();
			else
				$this->errorBox(wfMsg("bibwiki_error_edit_forbidden"));
		}
		elseif ($this->mAction == "edit") {
			if ($this->userIsAllowedToEdit())
				$this->editEntry();
			else
				$this->errorBox(wfMsg("bibwiki_error_edit_forbidden"));
		}
		elseif ($this->mAction == "savechanges") {
			if ($this->userIsAllowedToEdit())
				$this->saveChanges();
			else
				$this->errorBox(wfMsg("bibwiki_error_edit_forbidden"));
		}
		elseif ($this->mAction == "checkurl") {
			$this->checkUrl();
		}
		elseif ($this->mAction == "saveurl") {
			if ($this->userIsAllowedToEdit())
				$this->saveUrl();
			else
				$this->errorBox(wfMsg("bibwiki_error_edit_forbidden"));
		}
		elseif ($this->mAction == "import") {
			if ($this->userIsAllowedToEdit()) {
				$this->mImportSource = $wgRequest->getVal("source");
				$this->import();
			}
			else
				$this->errorBox(wfMsg("bibwiki_error_edit_forbidden"));
		}
		elseif ($this->mAction == "rename") {
			if ($this->userIsAllowedToEdit())
				$this->renamePaper();
			else
				$this->errorBox(wfMsg("bibwiki_error_rename_forbidden"));
		}
		elseif ($this->mAction == "allcopies") {
			$f = file_get_contents('http://aleph.univie.ac.at/F/?func=find-b&request='.$wgRequest->getVal("isbn").'&find_code=IBN', "r");
			$docnumber = substr($f, strpos($f, "doc_number=")+11, 9);
			header("Location: http://aleph.univie.ac.at/F/?func=item-global&doc_library=UBW01&doc_number=".$docnumber);
		}
		else {
			$this->viewBibliography();
		}
	}

	function testSQL() {
		$dbw =& wfGetDB( DB_MASTER );
		#$dbw->insert( /* ...see docs... */ );
	}

	function viewBibliography() {
		global $wgHooks, $wgRequest;

		$wgHooks['RenderPageTitle'] = array('wfRenderBibliographyTitle');
		$wgHooks['BeforePageDisplay'][] = 'wfBeforePageDisplay';

		$view = $wgRequest->getVal("view");
		if ($view == "")
			$view = $_COOKIE["BIBWIKI_VIEW"];
		if ($view == "")
			$view = "compact";

		if ($wgRequest->getVal("errormsg") != "")
			$this->errorBox($wgRequest->getVal("errormsg"));

		if ($view == "detailed")
			$this->viewDetailed();
		else
			$this->viewCompact();

		setcookie("BIBWIKI_VIEW", $view, time()+60*60*24*30);
	}

	function debug() {
		global $wgOut, $wgHooks, $wgRequest, $wgBookCoverDirectory,
		   $wgDefaultBib, $wgContLang, $wgBibPath;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		print("<h3>Testing Encodings</h3>");

		/* Testing Name Parsing
		$rv = bwSplitName($wgRequest->getVal("text"));
		foreach ($rv as $r) {
			print("|".$r."|<br/>");
		}

		print("isupper: ".(bwIsUpper($wgRequest->getVal("text"))?"yes":"no")."<br/>");

		$rv = bwParseAuthor($wgRequest->getVal("text"));

		print("firstname: |".$rv["firstname"]."|<br/>");
		print("firstname_initial: |".$rv["firstname_initial"]."|<br/>");
		print("firstnames: |".$rv["firstnames"]."|<br/>");
		print("firstnames_initials: |".$rv["firstnames_initials"]."|<br/>");
		print("middlepart: |".$rv["middlepart"]."|<br/>");
		print("surname: |".$rv["surname"]."|<br/>");

		print ('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />');
		 
		print('<form id="editform" name="editform" method="get" action="#" enctype="multipart/form-data">');
		print('<input type="hidden" name="title" value="Spezial:Bibliography">');
		print('<input type="hidden" name="action" value="debug">');
		print('<input type="text" name="text">');
		print('<input type="submit">');
		print('</form>');

		print("<pre>句の内容がない場合には<br/>");

		print("empty(): ".empty($unset_variable)."\n");
		print("BIBWIKI_BIBFILE: ".$_COOKIE["BIBWIKI_BIBFILE"]."\n");
		print(strtolower("Hallo")."\n");
		print(mb_strtolower("Hallo")."\n");
		print(mb_strtolower("句の内容がない場")."\n");
		print(mb_strpos("句の内容がない場", "Ø")."\n");
		print(strpos("句の内容がない場", "Ø")."\n");
		print(strstr("句の内容がない場", "ØÝð")."\n");
		print("</pre>");

		print("<h3>Testing directory permissions</h3>");

		print("<pre>");
		$wgBibPath_Error = false;
		$d = @dir($wgBibPath);
		if (empty($d))
			print "$wgBibPath isn't a directory.<br>";
		else {
			print "dir(\$wgBibPath) OK.<br>";
			$d->close();
		}

		print "\$wgBibPath is_writeable: " . (is_writable($wgBibPath)? "Yes" : "Error") . "<br>";
		print "\$wgBibPath is_readable: " . (is_readable($wgBibPath)? "Yes" : "Error") . "<br>";

		print "\$wgBackupPath is_writeable: " . (is_writable($wgBackupPath)? "Yes" : "Error") . "<br>";
		print "\$wgBackupPath is_readable: " . (is_readable($wgBackupPath)? "Yes" : "Error") . "<br>";

		print "\$wgDownloadsPath is_writeable: " . (is_writable($wgDownloadsPath)? "Yes" : "Error") . "<br>";
		print "\$wgDownloadsPath is_readable: " . (is_readable($wgDownloadsPath)? "Yes" : "Error") . "<br>";

		print "\$wgDefaultBib exists: ". (file_exists(bwMakePath($wgBibPath, $wgDefaultBib))? "Yes" : "No!") . "<br>";
		print "\$wgDefaultBib is_readable: ". (is_readable(bwMakePath($wgBibPath, $wgDefaultBib))? "Yes" : "No!") . "<br>";

		print "\$wgDefaultBib is_writable: ". (is_writable(bwMakePath($wgBibPath, $wgDefaultBib))? "Yes" : "No!") . "<br>";
		print "file creating, file writing, file deleting...<br>";
		$filename = bwMakePath($wgBibPath, $wgDefaultBib).".".time().".tmpx";
		$out = fopen($filename, "w");
		fputs($out, "EVERYTHING IS FINE");
		fclose($out);
		print "file content: " . file_get_contents($filename) . "<br>";
		unlink($filename);
		print "done.\n";

		echo mb_internal_encoding()."\n"; 

		$filecontent = file_get_contents(bwMakePath($wgBibPath,"format-utf8-dos.bib"));

		$filecontent = bwToUtf8($filecontent);
		$filecontent = bwTeXToHTML($filecontent);
		print($filecontent);

		print(utf8_decode($filecontent));
				*/

		print("<pre>");

		$sz = '"{s{d{est} # {tet}}}"';
		$rv = Bibitem::getDelimiters($sz);
		print ($sz.": ".$rv["left"]." - ".$rv["right"]."\n\n");

		$bibfile = new Bibfile;
		$bibfile->init("wpl.bib");
		$rec = $bibfile->loadRecord("Karlhofer:2006");

		$bibitem = new Bibitem;
		$bibitem->set($rec);
		$bibitem->parse();
		$bibitem->expandCrossref($bibfile);
		print $bibitem->getSource();

		print $bibitem->formatWithOSBib("myapa");

		print("</pre>");


		exit();
	}

	function errorBox($msg, $type="_") {
		global $wgOut;
		if ($type === "_") $type = wfMsg("bibwiki_error");
		$wgOut->addHTML("<p style='border: 2px solid darkred; padding: 10px 20px; background-color:#F3F3F3; width:400pt'><span style='color:darkred'>$type</span> ");
		$wgOut->addHTML($msg);
		$wgOut->addHTML("</p><br />");
	}

	/**
	 * @return boolean
	 */
	static function userIsAllowedToEdit() {
		global $wgRestrictEditsToBureaucrats;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		return ($wgRestrictEditsToBureaucrats == false or bwUserIsBureaucrat());
	}

	function loadSettings() {
		global $wgRequest, $wgOut, $wgHooks, $wgDefaultReferencesStyle;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include_once( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$settings = array();
		$library = "";
		$hints = array();
		$lasthint = "";

		function makeSeparator() {
			global $wgOut;

			$wgOut->addHTML('<p>&nbsp;</p>');
		}

		function makeTextField($varName, $label, $settings, $hints) {
			global $wgOut;

			makeSeparator();
			$wgOut->addHTML('<p class="settings_title" style="font-weight:bold">'.$label.'</p>');
			$wgOut->addHTML('<input class="settings_edit" style="width:400pt" type="text" name="'.$varName.'" value="'.trim($settings[$varName],'"').'" />');
			$wgOut->addHTML('<p class="settings_info" style="color:gray; font-size:9pt; width:400pt">'.$hints[$varName].'</p>');
		}

		function makeCheckbox($varName, $label, $settings, $hints) {
			global $wgOut;

			makeSeparator();
			$wgOut->addHTML('<p class="settings_title" style="font-weight:bold">');
			$wgOut->addHTML('<input type="hidden" name="'.$varName.'_cb" value="true" />');
			$wgOut->addHTML('<input type="checkbox" name="'.$varName.'" value="true" '.(($settings[$varName]=="true")? "checked":"").' /> '.$label.'</p>');
			$wgOut->addHTML('<p class="settings_info" style="color:gray; font-size:9pt; width:400pt">'.$hints[$varName].'</p>');
		}

		function checkPath($path) {
			global $wgOut;

			$Path_Error = false;
			$d = @dir($path);
			if (empty($d))
				$Path_Error = true;
			else
				$d->close();
			if (is_readable($path) == false) $Path_Error = true;
			if ($Path_Error) {
				$wgOut->addHTML('<p class="settings_info" style="color:red; font-size:9pt; width:400pt; font-weight:bold;">'.wfMsg('bibwiki_error_path_not_found').'</p>');
			}
		}

		# testing file writing

		$tmpname = time().".tmp";
        $tmp = @fopen(dirname(__FILE__) . "/".$tmpname, "w");
        if ($tmp) {
        	fclose($tmp);
        	unlink(dirname(__FILE__) . "/".$tmpname);
        }
        else {
        	$this->errorBox(wfMsg("bibwiki_error_write_config"));
        	return;
    	}

		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        $in = @fopen(dirname(__FILE__) . "/BibwikiSettings.php","r");
		else
			$in = @fopen(dirname(__FILE__) . "/BibwikiSettings.Default.php","r");

		if (!$in) {
			$this->errorBox(wfMsg("bibwiki_error_read_settings"));
			return;
		}

		while (!feof($in)) {
			$s = fgets($in);
			if (preg_match("/^\\s*\\$([a-zA-Z_0-9]+)\\s*=\\s*(.+);\\s*$/", $s, $matches)) {
				$settings[$matches[1]] = str_replace('"', '&quot;', str_replace("\\\\", "\\", trim($matches[2], '\'\"')));
				$hints[$matches[1]] = $lasthint;
			}
			else if (preg_match("/^\\s*#\\s*(.*)\\s*$/", $s, $matches)) {
				if ($lasthint != "") $lasthint .= " ";
				$lasthint .= $matches[1];
			}
			else if (preg_match('|Library-[a-zA-Z\-]+\.php|', $s, $matches)) {
				$library = $matches[0];
			}
			else
				$lasthint = "";
		}
		fclose($in);

		unset($wgHooks['RenderPageTitle']);
		$wgOut->setPageTitle(wfMsg("bibwiki_edit_settings")." ".$wgRequest->getVal("key"));

		if (isset($settings["wgAllowEditSettingsFromIPs"]) and
			$settings["wgAllowEditSettingsFromIPs"] != "") {
			if (strstr($settings["wgAllowEditSettingsFromIPs"], $_SERVER["REMOTE_ADDR"]) == false) {
				$wgOut->addHTML(wfMsg("bibwiki_error_settings_forbidden"));
				return;
			}
		}
		else if ($_SERVER["REMOTE_ADDR"] != "127.0.0.1")
		{
			$wgOut->addHTML(wfMsg("bibwiki_error_settings_view_forbidden"));
			return;
		}

		if (bwUserIsSysop() == false) {
			$this->errorBox(wfMsg("bibwiki_error_settings_view_forbidden"));
			return;
		}

		$this->errorBox(wfMsg('bibwiki_settings_hint'));
		$wgOut->addHTML("<br/>");

		$wgOut->addHTML('<form id="settingsform" name="settingsform" method="post" action="'.$this->getLocalURL().'" enctype="multipart/form-data">');
		$wgOut->addHTML('<input type="hidden" name="action" value="save_settings" />');

		#$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_amazon_related').'</h2>');

		#makeTextField("accesskey", wfMsg("bibwiki_setting_amazon"), $settings, $hints);
		#makeCheckbox("wgFetchAndViewBookCovers", wfMsg("bibwiki_setting_wgFetchAndViewBookCovers"), $settings, $hints);
		#makeSeparator();

		$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_library').'</h2>');

		$wgOut->addHTML('<p class="settings_info" style="color:gray; font-size:9pt; width:400pt">Choose your nearest library.</p>');
		$d = opendir(dirname( __FILE__ ) . '/libs') or die($php_errormsg);
		$wgOut->addHTML('<p class="settings_title" style="font-weight:normal">');
		while (false !== ($f = readdir($d))) {
			$wgOut->addHTML('<tr><td>');
			if (preg_match('|Library-[a-zA-Z\-]+\.php|', $f)) {
				$t = str_replace(".php", "", $f);
				$t = str_replace("Library-", "", $t);
				$t = str_replace("--", " &ndash; ", $t);
				$t = str_replace("-", " ", $t);
				if ($f == $library)
					$wgOut->addHTML('<input type="radio" name="library" value="'.$f.'" checked="checked" /> '.$t.'<br/>');
				else
					$wgOut->addHTML('<input type="radio" name="library" value="'.$f.'" /> '.$t.'<br/>');
			}
		}
		closedir($d);
		$wgOut->addHTML('</p>');
		makeSeparator();

		#$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_export').'</h2>');

		#makeCheckbox("wgEnableExport", wfMsg("bibwiki_setting_wgEnableExport"), $settings, $hints);
		#makeTextField("wgTempDir", wfMsg("bibwiki_setting_wgTempDir"), $settings, $hints);
		#checkPath($settings["wgTempDir"]);
		#makeTextField("wgBibTeXExecutable", wfMsg("bibwiki_setting_wgBibTeXExecutable"), $settings, $hints);
		#makeTextField("wgBibStyles", wfMsg("bibwiki_setting_wgBibStyles"), $settings, $hints);

		makeSeparator();

		#$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_path').'</h2>');

		#makeTextField("wgBibPath", wfMsg("bibwiki_setting_wgBibPath"), $settings, $hints);
		#checkPath($settings["wgBibPath"]);
		#makeTextField("wgDefaultBib", wfMsg("bibwiki_setting_wgDefaultBib"), $settings, $hints);
		#makeTextField("wgBackupPath", wfMsg("bibwiki_setting_wgBackupPath"), $settings, $hints);
		#makeTextField("wgKeepBackups", wfMsg("bibwiki_setting_wgKeepBackups"), $settings, $hints);
		#makeTextField("wgDownloadsPath", wfMsg("bibwiki_setting_wgDownloadsPath"), $settings, $hints);
		#checkPath($settings["wgDownloadsPath"]);
		#makeTextField("wgDownloadsUrl", wfMsg("bibwiki_setting_wgDownloadsUrl"), $settings, $hints);
		#makeSeparator();

		$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_styleformat').'</h2>');

		$wgOut->addHTML('<p class="settings_title" style="font-weight:bold">'.wfMsg('bibwiki_rendering_styles'));
		include_once(dirname(__FILE__)."/OSBiB/LOADSTYLE.php");
		$styles = LOADSTYLE::loadDir(dirname(__FILE__)."/OSBiB/styles/bibliography");
		$styleKeys = array_keys($styles);
		$wgOut->addHTML('<p class="settings_info" style="color:gray; font-size:9pt; width:400pt">'.wfMsg("bibwiki_choose_style").'</p>');
		$wgOut->addHTML('<p class="settings_title" style="font-weight:normal">');
		foreach($styles as $style => $value)
		{
			if($style == $settings["wgDefaultReferencesStyle"])
				$wgOut->addHTML("<input type='radio' name='wgDefaultReferencesStyle' value=\"$style\" checked=\"checked\"> $value<br/>\n");
			else
				$wgOut->addHTML("<input type='radio' name='wgDefaultReferencesStyle' value=\"$style\"> $value<br/>\n");
		}
		$wgOut->addHTML('</p>');

		makeTextField("wgHowManyItemsPerPage", wfMsg("bibwiki_setting_wgHowManyItemsPerPage"), $settings, $hints);
		makeCheckbox("wgBreakLines", wfMsg("bibwiki_setting_wgBreakLines"), $settings, $hints);
		makeTextField("wgLineBreakAt", wfMsg("bibwiki_setting_wgLineBreakAt"), $settings, $hints);
		#makeTextField("wgDocnamePattern", wfMsg("bibwiki_setting_wgDocnamePattern"), $settings, $hints);
		#makeTextField("wgMaxDocnameTitleLength", wfMsg("bibwiki_setting_wgMaxDocnameTitleLength"), $settings, $hints);

		makeSeparator();

		$wgOut->addHTML('<input type="hidden" name="view" value="'.$wgRequest->getVal('view').'" />');
		$wgOut->addHTML('<input type="Submit" name="Submit" value="'.wfMsg("bibwiki_settings_save_and_close").'" /> ');
		$wgOut->addHTML('<input type="Submit" name="Submit" value="'.wfMsg('bibwiki_cancel').'" />');

		makeSeparator();

		$wgOut->addHTML('</form>');
	}

	function loadSettingsForSetup() {
		global $wgRequest, $wgOut, $wgHooks, $wgDefaultReferencesStyle,
		$wgLang;

		$settings = array();
		$library = "";
		$hints = array();
		$lasthint = "";

		function makeSeparator() {
			global $wgOut;

			$wgOut->addHTML('<p>&nbsp;</p>');
		}

		function makeTextField($varName, $label, $settings, $hints) {
			global $wgOut;

			makeSeparator();
			$wgOut->addHTML('<p class="settings_title" style="font-weight:bold">'.$label.'</p>');
			$wgOut->addHTML('<input class="settings_edit" style="width:400pt" type="text" name="'.$varName.'" value="'.trim($settings[$varName],'"').'" />');
			$wgOut->addHTML('<p class="settings_info" style="color:gray; font-size:9pt; width:400pt">'.$hints[$varName].'</p>');
		}

		function makeCheckbox($varName, $label, $settings, $hints) {
			global $wgOut;

			makeSeparator();
			$wgOut->addHTML('<p class="settings_title" style="font-weight:bold">');
			$wgOut->addHTML('<input type="hidden" name="'.$varName.'_cb" value="true" />');
			$wgOut->addHTML('<input type="checkbox" name="'.$varName.'" value="true" '.(($settings[$varName]=="true")? "checked":"").' /> '.$label.'</p>');
			$wgOut->addHTML('<p class="settings_info" style="color:gray; font-size:9pt; width:400pt">'.$hints[$varName].'</p>');
		}

		function checkPath($path) {
			global $wgOut;

			$Path_Error = false;
			$d = @dir($path);
			if (empty($d))
				$Path_Error = true;
			else
				$d->close();
			if (is_readable($path) == false) $Path_Error = true;
			if ($Path_Error) {
				$wgOut->addHTML('<p class="settings_info" style="color:red; font-size:9pt; width:400pt; font-weight:bold;">'.wfMsg('bibwiki_error_path_not_found').'</p>');
			}
		}

		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php')) {
			$this->errorBox(wfMsg("bibwiki_error_bibwikisettings_exists"));
			return;
	    }
		else
			$in = @fopen(dirname(__FILE__) . "/BibwikiSettings.Default.php","r");

		if (!$in) {
			$this->errorBox(wfMsg("bibwiki_error_cantread_bibwikisettings"));
			return;
		}

		while (!feof($in)) {
			$s = fgets($in);
			if (preg_match("/^\\s*\\$([a-zA-Z_0-9]+)\\s*=\\s*(.+);\\s*$/", $s, $matches)) {
				#$settings[$matches[1]] = str_replace('"', '&quot;', str_replace("\\\\", "\\", trim($matches[2], '\'\"')));
				if ($wgRequest->getVal($matches[1]) != "")
					$settings[$matches[1]] = $wgRequest->getVal($matches[1]);
				$hints[$matches[1]] = $lasthint;
			}
			else if (preg_match("/^\\s*#\\s*(.*)\\s*$/", $s, $matches)) {
				if ($lasthint != "") $lasthint .= " ";
				$lasthint .= $matches[1];
			}
			else if (preg_match('|Library-[a-zA-Z\-]+\.php|', $s, $matches)) {
				$library = $matches[0];
			}
			else
				$lasthint = "";
		}
		fclose($in);

		unset($wgHooks['RenderPageTitle']);
		$wgOut->setPageTitle(wfMsg("bibwiki_setup_title_1")." ".$wgRequest->getVal("key"));

		/*if (isset($settings["wgAllowEditSettingsFromIPs"]) and
			$settings["wgAllowEditSettingsFromIPs"] != "") {
			if (strstr($settings["wgAllowEditSettingsFromIPs"], $_SERVER["REMOTE_ADDR"]) == false) {
				$wgOut->addHTML(wfMsg("bibwiki_error_settings_forbidden"));
				return;
			}
		}
		else if ($_SERVER["REMOTE_ADDR"] != "127.0.0.1")
		{
			$wgOut->addHTML(wfMsg("bibwiki_error_settings_view_forbidden"));
			return;
		}

		if (bwUserIsSysop() == false) {
			$this->errorBox(wfMsg("bibwiki_error_settings_view_forbidden"));
			return;
		}*/

		$wgOut->addHTML(wfMsg("bibwiki_setup_infotext"));

		$wgOut->addHTML('<form id="settingsform" name="settingsform" method="post" action="'.$this->getLocalURL().'" enctype="multipart/form-data">');
		$wgOut->addHTML('<input type="hidden" name="action" value="setup2" />');

		/*$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_library').'</h2>');

		$wgOut->addHTML('<p class="settings_info" style="color:gray; font-size:9pt; width:400pt">'.wfMsg("bibwiki_choose_library").'</p>');
		$d = opendir(dirname( __FILE__ ) . '/libs') or die($php_errormsg);
		$wgOut->addHTML('<p class="settings_title" style="font-weight:normal">');
		while (false !== ($f = readdir($d))) {
			$wgOut->addHTML('<tr><td>');
			if (preg_match('|Library-[a-zA-Z\-]+\.php|', $f)) {
				$t = str_replace(".php", "", $f);
				$t = str_replace("Library-", "", $t);
				$t = str_replace("--", " &ndash; ", $t);
				$t = str_replace("-", " ", $t);
				if ($f == $library)
					$wgOut->addHTML('<input type="radio" name="library" value="'.$f.'" checked="checked" /> '.$t.'<br/>');
				else
					$wgOut->addHTML('<input type="radio" name="library" value="'.$f.'" /> '.$t.'<br/>');
			}
		}
		closedir($d);
		$wgOut->addHTML('</p>');
		makeSeparator();*/

		/*$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_export').'</h2>');

		makeCheckbox("wgEnableExport", wfMsg("bibwiki_setting_wgEnableExport"), $settings, $hints);
		makeTextField("wgTempDir", wfMsg("bibwiki_setting_wgTempDir"), $settings, $hints);
		checkPath($settings["wgTempDir"]);
		makeTextField("wgBibTeXExecutable", wfMsg("bibwiki_setting_wgBibTeXExecutable"), $settings, $hints);
		makeTextField("wgBibStyles", wfMsg("bibwiki_setting_wgBibStyles"), $settings, $hints);
		*/
		makeSeparator();

		$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_mandatory').'</h2>');

		makeTextField("wgBibPath", wfMsg("bibwiki_setting_wgBibPath"), $settings, $hints);
		if ($wgRequest->getVal("action") == "setup2")
			checkPath($settings["wgBibPath"]);
		makeTextField("wgDefaultBib", wfMsg("bibwiki_setting_wgDefaultBib"), $settings, $hints);
		if ($wgRequest->getVal("action") == "setup2" and $this->checkFile(bwMakePath($settings["wgBibPath"], $settings["wgDefaultBib"])) == false)
			$wgOut->addHTML('<p class="settings_info" style="color:red; font-size:9pt; width:400pt; font-weight:bold;">'.wfMsg('bibwiki_error_file_not_found').'</p>');
		#makeTextField("wgBackupPath", wfMsg("bibwiki_setting_wgBackupPath"), $settings, $hints);
		#makeTextField("wgKeepBackups", wfMsg("bibwiki_setting_wgKeepBackups"), $settings, $hints);
		makeSeparator();

		$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_optional').'</h2>');

		makeTextField("wgDownloadsPath", wfMsg("bibwiki_setting_wgDownloadsPath"), $settings, $hints);
		if ($wgRequest->getVal("action") == "setup2" and $settings["wgDownloadsPath"] != "")
			checkPath($settings["wgDownloadsPath"]);
		makeTextField("wgDownloadsUrl", wfMsg("bibwiki_setting_wgDownloadsUrl"), $settings, $hints);
		#makeSeparator();

		/*$wgOut->addHTML('<h2>'.wfMsg("bibwiki_settings_styleformat").'</h2>');

		$wgOut->addHTML('<p class="settings_title" style="font-weight:bold">'.wfMsg('bibwiki_rendering_styles'));
		include_once(dirname(__FILE__)."/OSBiB/LOADSTYLE.php");
		$styles = LOADSTYLE::loadDir(dirname(__FILE__)."/OSBiB/styles/bibliography");
		$styleKeys = array_keys($styles);
		$wgOut->addHTML('<p class="settings_info" style="color:gray; font-size:9pt; width:400pt">Choose your favourite style.</p>');
		$wgOut->addHTML('<p class="settings_title" style="font-weight:normal">');
		foreach($styles as $style => $value)
		{
			if($style == $settings["wgDefaultReferencesStyle"])
				$wgOut->addHTML("<input type='radio' name='wgDefaultReferencesStyle' value=\"$style\" checked=\"checked\"> $value<br/>\n");
			else
				$wgOut->addHTML("<input type='radio' name='wgDefaultReferencesStyle' value=\"$style\"> $value<br/>\n");
		}
		$wgOut->addHTML('</p>');

		makeSeparator();*/

		#$wgOut->addHTML('<h2>'.wfMsg('bibwiki_settings_amazon_related').'</h2>');

		makeTextField("accesskey", wfMsg("bibwiki_setting_amazon"), $settings, $hints);
		#makeCheckbox("wgFetchAndViewBookCovers", wfMsg("bibwiki_setting_wgFetchAndViewBookCovers"), $settings, $hints);
		makeSeparator();

		/*makeTextField("wgHowManyItemsPerPage", wfMsg("bibwiki_setting_wgHowManyItemsPerPage"), $settings, $hints);
		makeCheckbox("wgBreakLines", wfMsg("bibwiki_setting_wgBreakLines"), $settings, $hints);
		makeTextField("wgLineBreakAt", wfMsg("bibwiki_setting_wgLineBreakAt"), $settings, $hints);
		makeTextField("wgDocnamePattern", wfMsg("bibwiki_setting_wgDocnamePattern"), $settings, $hints);
		makeTextField("wgMaxDocnameTitleLength", wfMsg("bibwiki_setting_wgMaxDocnameTitleLength"), $settings, $hints);*/

		makeSeparator();

		$wgOut->addHTML("<input type='hidden' name='lang' value='".$wgLang->getCode()."' />\n");
		$wgOut->addHTML('<input type="hidden" name="view" value="'.$wgRequest->getVal('view').'" />');
		$wgOut->addHTML('<input type="Submit" name="Submit" value="'.wfMsg("bibwiki_generate").'" /> ');
		#$wgOut->addHTML('<input type="Submit" name="Submit" value="'.wfMsg('bibwiki_cancel').'" />');

		makeSeparator();

		$wgOut->addHTML('</form>');
	}

	function saveSettings() {
		global $wgRequest, $wgOut, $wgHooks;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		if ($wgRequest->getVal("Submit") == wfMsg("bibwiki_settings_save_and_close")) {

			if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
		        $in = @fopen(dirname(__FILE__) . "/BibwikiSettings.php","r");
			else
				$in = @fopen(dirname(__FILE__) . "/BibwikiSettings.Default.php","r");

			if ($in != false and
			    ($out = @fopen(dirname( __FILE__ ) . "/BibwikiSettings.php.tmp", "w")) == TRUE) {
				while (!feof($in)) {
					$s = fgets($in);
					if (preg_match("/^\\s*\\$([a-zA-Z_0-9]+)\\s*=\\s*(.+);\\s*$/", $s, $matches)) {
						$varname = $matches[1];
						$value = $matches[2];
						if (strtolower($value) == "true" or
							strtolower($value) == "false")
						{
							if ($wgRequest->getVal($varname."_cb") == "true") {
								$newval = trim($wgRequest->getVal($varname));
								if ($newval == "") $newval = "false";
								fputs($out, "$".$varname." = ".$newval.";\n");
							}
							else
								fputs($out, $s);
						}
						elseif ($wgRequest->getVal($varname) != "")
						{
							$newval = trim($wgRequest->getVal($varname));

							if (preg_match("/^\d+$/", $newval))
								fputs($out, "$".$varname." = ".$newval.";\n");
							elseif (preg_match("/^array/", $newval))
								fputs($out, "$".$varname." = ".$newval.";\n");
							elseif (preg_match("/^'.*'$/", $newval))
								fputs($out, "$".$varname." = ".$newval.";\n");
							elseif (preg_match("/^\".*\"$/", $newval))
								fputs($out, "$".$varname." = ".$newval.";\n");
							else
								fputs($out, "$".$varname." = '".$newval."';\n");
						}
						else
							fputs($out, $s);
					}
					else if (preg_match('|/libs/([A-Za-z_\.\-]+)|', $s) and
					    $wgRequest->getVal("library") != "" and
					    preg_match("|Library-[A-Za-z_\\-]+\.php|", $wgRequest->getVal("library")) and
					    file_exists(dirname( __FILE__ ) . "/libs/".$wgRequest->getVal("library"))) {
						fputs($out, "@include(dirname( __FILE__ ).'/libs/".$wgRequest->getVal("library")."');\n");
					}
					else {
						fputs($out, $s);
					}
				}
				fclose($in);
				fclose($out);
				if (file_exists( dirname( __FILE__ ) . "/BibwikiSettings.php.bak"))
					unlink( dirname( __FILE__ ) . "/BibwikiSettings.php.bak");
				rename( dirname( __FILE__ ) . "/BibwikiSettings.php",  dirname( __FILE__ ) . "/BibwikiSettings.php.".time().".bak");
				rename( dirname( __FILE__ ) . "/BibwikiSettings.php.tmp",  dirname( __FILE__ ) . "/BibwikiSettings.php");

				header("Location: ".$this->getFullURL(array("view=".$wgRequest->getVal("view"), 'print_settings_saved=1', $this->mFilterQuery, $this->mBibfileQuery)));
			}
			else {
				$this->errorBox(wfMsg("bibwiki_error_bibwikisettingsdefault_not_found"));
				return;
			}
		}
		header("Location: ".$this->getFullURL(array("view=".$wgRequest->getVal("view"), $this->mFilterQuery, $this->mBibfileQuery)));
	}

	function generateBibwikiSettings() {
		global $wgRequest, $wgOut, $wgHooks, $wgLang;

	    $language_specific_settings = array(
	    	"de" => array(
	    		'library' => 'Library-DE-Berlin--HU.php',
	    		'wgDateTimeFormat' => '%d.%m.%Y',
	    		'wgValueDelimLeft' => '{',
	    		'wgValueDelimRight' => '}',
	    		'wgTitleDelimLeft' => '{{',
	    		'wgTitleDelimRight' => '}}',
	    		'wgAmazonURL' => 'amazon.de',
	    	),
	    );

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$settings = "";

		$lang = $wgLang->getCode();
		if (isset($language_specific_settings[$lang]) == false)
			$lang = "en";

		if ($wgRequest->getVal("Submit") == wfMsg("bibwiki_generate")) {

			$in = @fopen(dirname(__FILE__) . "/BibwikiSettings.Default.php","r");

			if ($in != false) {
				while (!feof($in)) {
					$s = htmlentities(fgets($in));
					if (preg_match("/^\\s*\\$([a-zA-Z_0-9]+)\\s*=\\s*(.+);\\s*$/", $s, $matches)) {
						$varname = $matches[1];
						$value = $matches[2];
						$default_val = "";
						if (isset($language_specific_settings["en"][$varname]))
							$default_val = $language_specific_settings[$lang][$varname];
						if (isset($language_specific_settings[$lang][$varname]))
							$default_val = $language_specific_settings[$lang][$varname];

						if (strtolower($value) == "true" or
							strtolower($value) == "false")
						{
							if ($wgRequest->getVal($varname."_cb") == "true") {
								$newval = trim($wgRequest->getVal($varname));
								if ($newval == "") $newval = "false";
								$settings .= "$".$varname." = ".$newval.";\n";
							}
							elseif ($default_val != "")
								$settings .= "$".$varname." = ".$default_val.";\n";
							else
								$settings .= $s;
						}
						elseif ($wgRequest->getVal($varname) != "" or
						        $default_val != "")
						{
							$newval = $default_val;
							if (trim($wgRequest->getVal($varname)) != "")
								$newval = trim($wgRequest->getVal($varname));

							if (preg_match("/^\d+$/", $newval))
								$settings .= "$".$varname." = ".$newval.";\n";
							elseif (preg_match("/^array/", $newval))
								$settings .= "$".$varname." = ".$newval.";\n";
							elseif (preg_match("/^'.*'$/", $newval))
								$settings .= "$".$varname." = ".$newval.";\n";
							elseif (preg_match("/^\".*\"$/", $newval))
								$settings .= "$".$varname." = ".$newval.";\n";
							else
								$settings .= "$".$varname." = '".$newval."';\n";
						}
						else
							$settings .= $s;
					}
					elseif (preg_match('|/libs/([A-Za-z_\.\-]+)|', $s)) {
						$lib = "";
						if (isset($language_specific_settings["en"]["library"]))
							$lib = $language_specific_settings["en"]["library"];
						if (isset($language_specific_settings[$lang]["library"]))
							$lib = $language_specific_settings[$lang]["library"];

						if ($wgRequest->getVal("library") != "" and
					    	preg_match("|Library-[A-Za-z_\\-]+\.php|", $wgRequest->getVal("library")) and
					    	file_exists(dirname( __FILE__ ) . "/libs/".$wgRequest->getVal("library")))
					    {
							$settings .= "@include(dirname( __FILE__ ).'/libs/".$wgRequest->getVal("library")."');\n";
						}
						elseif ($lib != "" and
					    	preg_match("|Library-[A-Za-z_\\-]+\.php|", $lib) and
					    	file_exists(dirname( __FILE__ ) . "/libs/".$lib))
					    {
							$settings .= "@include(dirname( __FILE__ ).'/libs/".$lib."');\n";
						}
						else
							$settings .= $s;
					}
					else {
						$settings .= $s;
					}
				}
				fclose($in);

				unset($wgHooks['RenderPageTitle']);
				$wgOut->setPageTitle(wfMsg("bibwiki_setup_title_2")." ".$wgRequest->getVal("key"));

				$wgOut->addHTML(wfMsg("bibwiki_setup_instructions")."<br/><br/><textarea rows=30 readonly>".$settings."</textarea>");

				$wgOut->addHTML('<form id="settingsform" name="settingsform" method="post" action="'.$this->getLocalURL().'" enctype="multipart/form-data">');
				$wgOut->addHTML('<input type="hidden" name="action" value="view" />');
				$wgOut->addHTML('<input type="Submit" name="Submit" value="Return to Bibliography" /> ');
				$wgOut->addHTML('</form>');
				return true;
			}
			else {
				$this->errorBox("bibwiki_error_bibwikisettingsdefault_not_found");
				return false;
			}
		}
		else
			return false;
	}

	/**
	 * @todo rewrite.
	 */
	function renamePaper() {
		global $wgDownloadsPath, $wgRequest;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$oldname = $wgRequest->getVal("oldname");
		$newname = $wgRequest->getVal("newname");
		$newname = preg_replace("/\s+/", " ", $newname);

		if ($this->mStartkey !== "" &&
		    $oldname !== "" && $newname) {

			$rename_rv = rename(
				bwMakePath($wgDownloadsPath, $oldname),
				bwMakePath($wgDownloadsPath, $newname)
			);

			if ($rename_rv == true) {
				$inkey = 0;
				if (($in = @fopen($this->mBibfile->getAbsoluteName(),'r')) and
				    ($out = @fopen($this->mBibfile->getAbsoluteName().".tmp",'w'))) {
					$current_key = "";
					$keys = array();
					$done = 0;
					while (!feof($in)) {
						$s = fgets($in);
						$sz = trim($s);
						if ($done == 0 and preg_match("/^\s*@\s*\w+\s*[{(]{1,1}\s*[\w:\*\.\-]+/", $sz)) {
							if (stristr($sz, '@string') === false and
							    stristr($sz, '@comment') === false and
							    stristr($sz, '@preamble') === false) {

							    #Entry Point Found
								preg_match_all("/^\s*@\s*\w+\s*[{(]{1,1}\s*([\w:\*\.\-]+)/", $sz, $matches, PREG_SET_ORDER);
								$current_key = $matches[0][1];
								$keys[$current_key]++;
								if ((($keys[$current_key] == $wgRequest->getVal("nr") or
								      $wgRequest->getVal("nr") == ""
								     ) and
								     bwStrEqual($current_key, $this->mStartkey)
								    ) and $done == 0) {
									$inkey = 1;
								}
								else $inkey = 0;
							}
							else $inkey = 0;
						}

						if ($inkey == 1 && $done == 0) {
						    if (strstr($s, "=")) {
						    	$words = explode("=", $s);
						    	$key = $words[0];
						    	$val = $words[1];
						    	$key = trim($key);
						    	$val = trim($val);
						    	$val = trim($val, '",{}');

						    	if ((bwStrEqual($key, "docname") or
						    	     bwStrEqual($key, "file") or
						    	     bwStrEqual($key, "pdf")
						    	    ) and $val != "") {
						    		$pos = mb_strpos($s, $val);
						    		$len = mb_strlen($val);
							    	fputs($out, substr_replace($s, urldecode($newname), $pos, $len));
							    	$done = 1;
						    	}
						    	else fputs($out, $s);
						    }
						    else fputs($out, $s);
						}
						else fputs($out, $s);
					}
					fclose($in);
					fclose($out);
					if (file_exists($this->mBibfile->getAbsoluteName().".bak"))
						unlink($this->mBibfile->getAbsoluteName().".bak");
					rename($this->mBibfile->getAbsoluteName(), $this->mBibfile->getAbsoluteName().".bak");
					rename($this->mBibfile->getAbsoluteName().".tmp", $this->mBibfile->getAbsoluteName());

					$this->mBibfile->backup();
				}
			}
		}

		header("Location: ".$this->getFullURL(array("view=".$wgRequest->getVal("view"), $this->mFilterQuery, $this->mBibfileQuery, "renamewarn=".!$rename_rv, "startkey=".$this->mStartkey)));
	}

	function getDescription() {
		return wfMsg("bibwiki_bibliography");
	}

	static function getStaticTitle() {
		return Title::makeTitle(NS_SPECIAL, wfMsg("bibwiki_bibliography"));
	}

	static function makeKnownLink($linktext, $query="") {
		global $wgUser, $wgContLang;
		if (is_array($query)) $query = bwImplodeQuery($query);
		if (!is_string($query)) $query = strval($query);
		return $wgUser->getSkin()->makeKnownLink(Bibliography::getSpecialPageName(), $linktext, $query);
	}

	static function makeBrokenLink($linktext, $query="") {
		global $wgUser, $wgContLang;
		if (is_array($query)) $query = bwImplodeQuery($query);
		if (!is_string($query)) $query = strval($query);
		return $wgUser->getSkin()->makeBrokenLink(Bibliography::getSpecialPageName(), $linktext, $query);
	}

	static function getLocalURL($query = "") {
		if (is_array($query)) $query = bwImplodeQuery($query);
		if (!is_string($query)) $query = strval($query);
		return Bibliography::getStaticTitle()->getLocalURL($query);
	}

	static function getFullURL($query = "") {
		if (is_array($query)) $query = bwImplodeQuery($query);
		if (!is_string($query)) $query = strval($query);
		return Bibliography::getStaticTitle()->getFullURL($query);
	}

	static function getSpecialPageName() {
		global $wgContLang;
		return $wgContLang->specialPage( wfMsg("bibwiki_bibliography") );
	}

	function exportFromDocument() {
		global $wgOut, $wgHooks;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		unset($wgHooks['RenderPageTitle']);
		$wgOut->setPageTitle(wfMsg("bibwiki_export_from_doc"));
		$wgOut->AddHTML(
		wfMsg('bibwiki_export_hint')."
		<form method='post' action='".$this->getLocalURL()."'>
		<textarea tabindex='1' accesskey=',' name='content' id='bibeditbox' rows='25' cols='80'></textarea><br/>
		<input type='hidden' name='action' value='export'/>
		<input type='hidden' name='f' value='".$this->mBibfile->getName."'/>
		<input type='submit' value='".wfMsg('bibwiki_export')."'/></td>
		</form>");
	}

	function fetchBookCoverFromAmazon($text) {
		global $wgFetchAndViewBookCovers, $accesskey, $wgRequest,
			$wgBookCoverDirectory, $wgAmazonURL,
			$image_url, $bigimage_url;

		$state_smallimage = false;
		$state_bigimage = false;
		$state_url = false;
		$image_url = "";
		$bigimage_url = "";

		function characterData($parser, $data)
		{
			global $state_url, $state_smallimage, $state_bigimage, $image_url, $bigimage_url;
			if ($state_smallimage and $state_url) $image_url = $data;
			if ($state_bigimage and $state_url) $bigimage_url = $data;
		}

		function startElement($parser, $name, $attrs)
		{
			global $state_url, $state_smallimage, $state_bigimage;
			if ($name == "SMALLIMAGE") $state_smallimage = true;
			if ($name == "LARGEIMAGE") $state_bigimage = true;
			if ($name == "MEDIUMIMAGE") $state_bigimage = true;
			if ($name == "URL") $state_url = true;
		}

		function endElement($parser, $name) {
			global $state_url, $state_smallimage, $state_bigimage;
			if ($name == "URL") $state_url = false;
			if ($name == "LARGEIMAGE") $state_bigimage = false;
			if ($name == "MEDIUMIMAGE") $state_bigimage = false;
			if ($name == "SMALLIMAGE") $state_smallimage = false;
		}

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		if (empty($wgAmazonURL)) $wgAmazonURL = "amazon.com";

		#fetch bookimage from Amazon
		if ($wgFetchAndViewBookCovers and $accesskey != "") {
			if (preg_match('/isbn\s*=\s*["{]?([\-\w]+)/i', $text, $matches)) {
			    $isbn = $matches[1];
			    $isbn = str_replace("-", "", $isbn);
				if (file_exists(bwMakePath($wgBookCoverDirectory, $isbn).".jpg") == false)
				{
					if ($accesskey == "") return;

					$request =
					"http://webservices.".$wgAmazonURL."/onca/xml?" .
					"Service=AWSECommerceService&" .
					"AWSAccessKeyId=$accesskey&" .
					"Operation=ItemLookup&" .
					"ItemId=$isbn&" .
					"ResponseGroup=Images&" .
					"Version=2005-10-13";

					$image_url = "";
					$bigimage_url = "";

					$xml_parser = xml_parser_create();
					xml_set_element_handler($xml_parser, "startElement", "endElement");
					xml_set_character_data_handler($xml_parser, "characterData");
					xml_parse($xml_parser, @file_get_contents($request));
					xml_parser_free($xml_parser);

					if ($image_url != "" and file_exists(bwMakePath($wgBookCoverDirectory, $isbn).".jpg") == false)
						file_put_contents(bwMakePath($wgBookCoverDirectory, $isbn).".jpg", file_get_contents($image_url));
					if ($bigimage_url != "" and file_exists(bwMakePath($wgBookCoverDirectory, $isbn)."-big.jpg") == false)
						file_put_contents(bwMakePath($wgBookCoverDirectory, $isbn)."-big.jpg", file_get_contents($bigimage_url));
				}
			}
		}
	}

	/**
	 * @todo: rewrite
	 */
	function translateDelimiters($text) {

		function checkDelimiters($key, $ldelim, $val, $rdelim) {
			global $wgValueDelimLeft, $wgValueDelimRight, $wgTitleDelimLeft,
				$wgTitleDelimRight;

			# don't touch anything if $val contains "#"
			# eg.  date = dec # " 12",
			if (mb_strpos($val, "#") !== false)
				return "$key = $ldelim$val$rdelim,\n";

			#Load Settings
			if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
		    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
			if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
		        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

			# print "$key = |$ldelim|$val|$rdelim|,<br/>";
			$titlekey = false;

			if (mb_strlen($ldelim) == 2 and mb_strlen($wgTitleDelimLeft) == 1) {
				$val = substr($ldelim, -1) . $val;
				$ldelim = substr($ldelim, 1, 1);
			}
			if (mb_strlen($rdelim) == 2 and mb_strlen($wgTitleDelimRight) == 1) {
				$val = $val.mb_substr($rdelim, 1, 1);
				$rdelim = mb_substr($ldelim, -1);
			}

			$ldelim = $wgValueDelimLeft;
			$rdelim = $wgValueDelimRight;
			if (bwStrEqual($key, "title") or
				bwStrEqual($key, "booktitle") or
				bwStrEqual($key, "titleaddon") or
				bwStrEqual($key, "booktitleaddon")) {
				$titlekey = true;
				$ldelim = $wgTitleDelimLeft;
				$rdelim = $wgTitleDelimRight;
			}

			if (mb_strpos($val, '"') !== false) {
				if ($titlekey and mb_strlen($ldelim) == 2) {
					$ldelim = "{{";
					$rdelim = "}}";
				}
				else {
					$ldelim = "{";
					$rdelim = "}";
				}
			}

			# print "$key = |$ldelim|$val|$rdelim|,<br/>";
			return "$key = $ldelim$val$rdelim,\n";
		}

		$rv = "";
		$lines = explode("\n", $text);
		foreach($lines as $l) {
			if (preg_match('/^\s*(\w+)\s*=\s*([{"]{1,1}{)(.+)(}[}"]{1,1}),?\s*$/', $l, $matches)) {
				$rv .= checkDelimiters($matches[1], $matches[2], $matches[3], $matches[4]);
				$val = "";
			}
			elseif (preg_match('/^\s*(\w+)\s*=\s*([{"]{1,1})(.+)([}"]{1,1}),?\s*$/', $l, $matches)) {
				$rv .= checkDelimiters($matches[1], $matches[2], $matches[3], $matches[4]);
				$val = "";
			}
			elseif (preg_match('/^\s*(\w+)\s*=\s*([{"]{1,1}{?)(.+)\s*$/', $l, $matches)) {
				$key = $matches[1];
				$ldelim = $matches[2];
				$val = $matches[3]."\n";
			}
			elseif ($val != "" and preg_match('/^\s*(.+)(}?[}"]{1,1}),?\s*$/U', $l, $matches)) {
				$val .= " ".$matches[1];
				$rdelim = $matches[2];
				$rv .= checkDelimiters($key, $ldelim, $val, $rdelim);
				$val = "";
			}
			elseif ($val != "") {
				$val .= trim($l)."\n";
			}
			else {
				$rv .= $l."\n";
			}
		}
		# exit();
		return $rv;
	}

	function saveNew() {
		global $wgRequest;

		$record = $wgRequest->getVal("text");
		$this->fetchBookCoverFromAmazon($record);
		if ($this->mBibfile->insertRecord($record) == false) {
			$this->errorBox("Creating new record failed.");
			return false;
		}
		$key = $this->mBibfile->getCiteKeyOfLastCommand();

		if ($this->mFilter != "")
			header("Location: ".$this->getFullURL(array("view=".$wgRequest->getVal("view"), $this->mFilterQuery, $this->mBibfileQuery)."#".$key));
		else
			header("Location: ".$this->getFullURL(array("view=".$wgRequest->getVal("view"), "startkey=".$key, $this->mFilterQuery, $this->mBibfileQuery)));
		return true;
	}

	/**
	 * Saves changes to a record to bibliography database.
	 */
	function saveChanges() {
		global $wgOut, $wgRequest, $wgBackupPath, $wgHooks, $wgKeepBackups,
			$wgConvertAnsiToTeX;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$this->fetchBookCoverFromAmazon($wgRequest->getVal("text"));
		$key = $wgRequest->getVal("key");
		$keynr = ($wgRequest->getVal("nr") == "")? 1 : $wgRequest->getVal("nr");
		$record = $wgRequest->getVal("text");
		$this->mBibfile->saveChanges($key, $keynr, $record);

		if ($this->mFilter != "")
			header("Location: ".$this->getFullURL(array("view=".$wgRequest->getVal("view"), $this->mFilterQuery, $this->mBibfileQuery))."#".$this->mBibfile->getCiteKeyOfLastCommand());
		else
			header("Location: ".$this->getFullURL(array("view=".$wgRequest->getVal("view"), "startkey=".$this->mBibfile->getCiteKeyOfLastCommand(), $this->mFilterQuery, $this->mBibfileQuery)));
	}

	/**
	 * @todo Make parts of it a method of Bibitem.
	 */
	function editEntry() {
		global $wgOut, $wgRequest, $wgHooks;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		unset($wgHooks['RenderPageTitle']);
		$wgOut->setPageTitle(wfMsg("bibwiki_editing")." ".$this->mBibfilename.DIRECTORY_SEPARATOR.$wgRequest->getVal("key"));

		$wgOut->addHTML('<form id="editform" name="editform" method="post" action="'.$this->getLocalURL().'" enctype="multipart/form-data">');
		$wgOut->addHTML("<textarea tabindex='1' accesskey=',' name='text' id='bibeditbox' rows='25' cols='80'>");

		$key = $wgRequest->getVal("key");
		$nr = $wgRequest->getVal("nr");
		$record = $this->mBibfile->loadRecord($key, $nr);
		$bibitem = new Bibitem;
		$bibitem->set($record);
		$bibitem->parse();
		$wgOut->addHTML($bibitem->formatForEditing());

		$wgOut->addHTML('</textarea>');
		$wgOut->addHTML("<div class='editOptions'>");
		$wgOut->addHTML("<div class='editButtons'>");
		$wgOut->addHTML('<input name="c" type="hidden" value="'.$wgRequest->getVal("c").'" />');
		$wgOut->addHTML('<input id="wpSave" name="wpSave" type="submit" tabindex="5" value="'.wfMsg('bibwiki_save').'" accesskey="1">');
		$wgOut->addHTML("</div><!-- editButtons -->");
		$wgOut->addHTML("</div><!-- editOptions -->");
		$wgOut->addHTML('<input type="hidden" value="savechanges" name="action" />');
		$wgOut->addHTML('<input type="hidden" value="'.$this->mBibfile->getName().'" name="f" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("view").'" name="view" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("key").'" name="key" />');
		$wgOut->addHTML('<input type="hidden" value="'.$this->mFilter.'" name="keyword" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("start").'" name="start" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("nr").'" name="nr" />');
		$wgOut->addHTML('</form>');
		$wgOut->addHTML("<span style='font-family: Arial, sans-serif; font-size: 8pt'><br><a href='http://en.wikipedia.org/wiki/BibTeX#Entry_Types' target='help'>Wikipedia: BibTeX's Entry Types</a></span>");
	}

	function newEntry() {
		global $wgValueDelimLeft, $wgValueDelimRight,
			$wgTitleDelimLeft, $wgTitleDelimRight, $wgDateTimeFormat,
			$wgOut, $wgRequest, $wgHooks, $wgUser;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		unset($wgHooks['RenderPageTitle']);
		$wgOut->setPageTitle(wfMsg("bibwiki_new_entry"));

		$importconverter = new ImportConverter;

		$content = $wgRequest->getVal("content");
		if ($content != "" and !Bibitem::validate($content)) {
			$this->errorBox(wfMsg(""));
			return;
		}

		$wgOut->addHTML('<form id="editform" name="editform" method="post" action="'.$this->getLocalURL().'" enctype="multipart/form-data">');

		$wgOut->addHTML("<textarea tabindex='1' accesskey=',' name='text' id='bibeditbox' rows='25' cols='80'>");

		if ($wgRequest->getVal("type") == "Book") {
			$wgOut->addHTML("@".$wgRequest->getVal("type")."{*,\n");
			$wgOut->addHTML('author = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('title = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('titleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('address = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('publisher = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('year = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('keywords = '.$wgValueDelimLeft.$this->mFilter.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
			$wgOut->addHTML("}");
		}
		elseif ($wgRequest->getVal("type") == "Collection") {
			$wgOut->addHTML("@Book{*,\n");
			$wgOut->addHTML('editor = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('title = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('titleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('booktitle = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('booktitleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('address = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('publisher = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('year = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('keywords = '.$wgValueDelimLeft.$this->mFilter.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
			$wgOut->addHTML("}");
		}
		elseif ($wgRequest->getVal("type") == "Article") {
			$wgOut->addHTML("@".$wgRequest->getVal("type")."{*,\n");
			$wgOut->addHTML('author = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('title = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('titleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('journal = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('volume = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('number = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('year = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('pages = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('keywords = '.$wgValueDelimLeft.$this->mFilter.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
			$wgOut->addHTML("}");
		}
		elseif ($wgRequest->getVal("type") == "Incollection") {
			$wgOut->addHTML("@".$wgRequest->getVal("type")."{*,\n");
			$wgOut->addHTML('author = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('title = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('titleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('crossref = '.$wgValueDelimLeft.$wgRequest->getVal('crossref').$wgValueDelimRight.','."\n");
			$wgOut->addHTML('pages = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('keywords = '.$wgValueDelimLeft.$this->mFilter.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
			$wgOut->addHTML("}");
		}
		elseif ($wgRequest->getVal("type") == "IncollectionLarge") {
			$wgOut->addHTML("@Incollection{*,\n");
			$wgOut->addHTML('author = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('title = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('titleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");

			$wgOut->addHTML('editor = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('booktitle = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('booktitleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('publisher = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('address = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");

			$wgOut->addHTML('pages = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('keywords = '.$wgValueDelimLeft.$this->mFilter.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
			$wgOut->addHTML("}");
		}
		elseif ($wgRequest->getVal("type") == "Misc") {
			$wgOut->addHTML("@".$wgRequest->getVal("type")."{*,\n");
			$wgOut->addHTML('author = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('title = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('titleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('howpublished = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('address = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('publisher = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('url = {},'."\n");
			$wgOut->addHTML('urldate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
			$wgOut->addHTML('docname = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('year = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('keywords = '.$wgValueDelimLeft.$this->mFilter.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
			$wgOut->addHTML("}");
		}
		elseif ($wgRequest->getVal("type") == "Opac") {
			#$wgOut->addHTML($wgRequest->getVal("content"));
			$wgOut->addHTML($importconverter->convertOpacSource($content));
		}
		elseif ($wgRequest->getVal("type") == "SA") {
			$wgOut->addHTML($importconverter->convertSASource($content));
		}
		elseif ($wgRequest->getVal("type") == "DDB") {
			$wgOut->addHTML($importconverter->convertDDBSource($content));
		}
		elseif ($wgRequest->getVal("type") == "arxiv") {
			$wgOut->addHTML($importconverter->convertArxivSource($content));
		}
		elseif ($wgRequest->getVal("type") == "loc") {
			$wgOut->addHTML($importconverter->convertLoCSource($content));
		}
		elseif ($wgRequest->getVal("type") == "Amazon") {
			$wgOut->addHTML($importconverter->convertAmazonSource($wgRequest->getVal("url")));
		}
		else {
			$wgOut->addHTML("@".$wgRequest->getVal("type")."{*,\n");
			$wgOut->addHTML('author = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('title = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('titleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
			$wgOut->addHTML('year = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('keywords = '.$wgValueDelimLeft.$this->mFilter.$wgValueDelimRight.','."\n");
			$wgOut->addHTML('bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
			$wgOut->addHTML("}");
		}
		$wgOut->addHTML('</textarea>');

		$wgOut->addHTML("<div class='editOptions'>");
		$wgOut->addHTML("<div class='editButtons'>");
		$wgOut->addHTML('<input name="c" type="hidden" value="'.$wgRequest->getVal("c").'" />');
		$wgOut->addHTML('<input id="wpSave" name="wpSave" type="submit" tabindex="5" value="'.wfMsg('bibwiki_save').'" accesskey="1">');

		$wgOut->addHTML('in &nbsp;&nbsp;<select width="30" name="f">');
		$privatebibs = bwGetPrivateBibfiles();
		foreach($privatebibs as $f) {
			$f = $wgUser->getName().DIRECTORY_SEPARATOR.$f;
			if ($f == $this->mBibfilename)
				$wgOut->addHTML('<option value="'.$f.'" selected>'.$f.'</option>');
			else
				$wgOut->addHTML('<option value="'.$f.'">'.$f.'</option>');
		}
		$pubbibs = bwGetPublicBibfiles();
		foreach($pubbibs as $f) {
			if ($f == $this->mBibfilename)
				$wgOut->addHTML('<option value="'.$f.'" selected>'.$f.'</option>');
			else
				$wgOut->addHTML('<option value="'.$f.'">'.$f.'</option>');
		}
		$wgOut->addHTML('</select>');

		$wgOut->addHTML("</div><!-- editButtons -->");
		$wgOut->addHTML("</div><!-- editOptions -->");
		$wgOut->addHTML('<input type="hidden" value="savenew" name="action" />');
		$wgOut->addHTML('<input type="hidden" value="'.$this->mFilter.'" name="keyword" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("view").'" name="view" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("key").'" name="key" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("start").'" name="start" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("nr").'" name="nr" />');
		$wgOut->addHTML("<span style='font-family: Arial, sans-serif; font-size: 8pt'><br><a href='http://en.wikipedia.org/wiki/BibTeX#Entry_Types' target='help'>Wikipedia: BibTeX's Entry Types</a></span>");
		$wgOut->addHTML('</form>');
	}

	function saveUrl() {
		global $wgOut, $wgRequest, $wgHooks, $wgBibPath,
			$wgDownloadsPath, $wgKeepBackups, $wgURLReplacements,
			$wgConvertAnsiToTeX, $wgValueDelimLeft,
			$wgValueDelimRight;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$errmsg = "";

		$text = $wgRequest->getVal("text");
		if ($wgConvertAnsiToTeX == true)
			$text = bwUtf8ToTeX($text);
		$text = $this->translateDelimiters($text);
		$a = explode("\n", $text);
		$text = "";
		$url = "";
		$docname = "";

		foreach($a as $v) {
			$b = explode ("=", $v, 2);
			$b[0] = trim(strtolower($b[0]));
			if (bwStrEqual($b[0], "url"))
			{
				$url = trim($b[1]," \\\"{}',\r\n\t");
				$url = trim($b[1]," \\\"{}',\r\n\t");

				$url = preg_replace(array_keys($wgURLReverseReplacements), $wgURLReverseReplacements, $url);

				$text .= $v . "\n";
			}
			else if (bwStrEqual($b[0], "docname") or
			         bwStrEqual($b[0], "file") or
			         bwStrEqual($b[0], "pdf"))
			{
				$docname = bwGenerateDocname($wgRequest->getVal("text"));
				$text .= "docname = " . $wgValueDelimLeft . $docname . $wgValueDelimRight . ",\n";
			}
			else $text .= $v . "\n";
		}

		if ($wgRequest->getVal("save") == "on" and $docname != "") {
			if ($this->checkPath($wgDownloadsPath) == true) {
				if ($in = @fopen(str_replace(" ", "%20", $url), "rb")) {
					if ($out = @fopen(bwMakePath($wgDownloadsPath, $docname), "wb")) {
						while (!feof($in)) {
							$content = fread($in, 3000000);
							fwrite($out, $content);
						}
						fclose($in);
						fclose($out);
					}
					else {
						$errmsg = wfMsg("bibwiki_error_creating")." ".$docname."!";
					}
				}
				else {
					$errmsg = wfMsg("bibwiki_error_reading")." ".$url."!";
				}
			}
			else {
				$errmsg = wfMsg("bibwiki_error_creating")." ".$docname."!";
			}
		}

		if (($in = @fopen($this->mBibfile->getAbsoluteName(),'r')) and
		    ($out = @fopen($this->mBibfile->getAbsoluteName().".tmp",'w'))) {
			$new_inserted = 0;
			while (!feof($in)) {
				$s = fgets($in);
				$sz = trim($s);
				if (preg_match("/^\s*@\s*\w+\s*[{(]{1,1}\s*([\w:\*\.\-]+)/", $sz)) {
					if (stristr($sz, '@string') === false and
					    stristr($sz, '@comment') === false and
					    stristr($sz, '@preamble') === false) {

					    if ($new_inserted == 0) {
							$new_inserted = 1;
							$lines = explode("\n", $text);
							foreach($lines as $l) {
								if (preg_match_all("/^\s*@\s*\w+\s*[{(]{1,1}\s*([\w:\*\.\-]+)/", $l, $matches, PREG_SET_ORDER)) {
									$dummy = $matches[0][1];
									if ($dummy == "*") {
										$dummy = bwGenerateKey($text, $this->mBibfile->getAbsoluteName());
										$l = str_replace("*", $dummy, $l);
									}
									#$wgRequest->getVal("key") = $dummy;
									fputs($out, $l."\n");
								}
								elseif (trim($l) == "}" or trim($l) == ")") {
									fputs($out, $l."\n\n");
								}
								elseif (trim($l) != "") {
									$parts = explode("=", $l, 2);
									if (count($parts) == 2)
									{
										$parts[1] = rtrim($parts[1], "\n\r\t ,");
										$m = sprintf("  %-12s = %s,\n", trim($parts[0]), trim($parts[1]));
									}
									else
									{
										$parts[0] = rtrim($parts[0], "\n\r\t ,");
										$m = sprintf("  %-13s   %s,\n", "", trim($parts[0]));
									}
									fputs($out, $m);
								}
							}
					    }
					}
				}
				fputs($out, $s);
			}
			fclose($in);
			fclose($out);
			if (file_exists($this->mBibfile->getAbsoluteName().".bak"))
				unlink($this->mBibfile->getAbsoluteName().".bak");
			rename($this->mBibfile->getAbsoluteName(), $this->mBibfile->getAbsoluteName().".bak");
			rename($this->mBibfile->getAbsoluteName().".tmp", $this->mBibfile->getAbsoluteName());

			$this->mBibfile->backup();
		}
		header("Location: ".$this->getFullURL(array("view=".$wgRequest->getVal("view"), $this->mFilterQuery, $this->mBibfileQuery, "errormsg=".$errmsg)));
		return true;
	}

	function checkUrl() {
		global $wgOut, $wgRequest, $wgHooks, $wgDocnamePattern,
			$wgTitleDelimLeft, $wgTitleDelimRight, $wgValueDelimLeft,
			$wgDownloadsPath,
			$wgValueDelimRight, $wgURLReplacements, $wgDateTimeFormat, $wgUser;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		unset($wgHooks['RenderPageTitle']);
		$wgOut->setPageTitle(wfMsg("bibwiki_import_url"));

		if ($wgDownloadsPath == "")
			$this->errorBox(wfMsg("bibwiki_error_empty_downloadspath"));

		$wgOut->addHTML('<form id="editform" name="editform" method="POST" action="'.$this->getLocalURL().'" enctype="multipart/form-data">');
		$wgOut->addHTML('<textarea tabindex="1" accesskey="," name="text" id="bibeditbox" rows="25"	cols="80" >');

		$url = $wgRequest->getVal("url");
		if (!empty($url)) {
			if (mb_strpos($url, "://") == FALSE)
				$url = "http://" . trim($url);
			$url_parts = parse_url($url);

			$fname = "";
			$path_parts = pathinfo($url_parts["path"]);

			if ($path_parts["extension"] != "") {
				$fname = $path_parts["basename"];
				if ($path_parts["extension"] == "php")
					$fname .= ".html";
			}
			else {
				$fname = trim($fname);
				if ($fname == "")
					$fname = "URL - " . time();
				$fname .= ".html";
			}

			$fname = str_replace("\\", "", urldecode($fname));
			$fname = str_replace("/", "", $fname);
			$fname = str_replace(">", "", $fname);
			$fname = str_replace("<", "", $fname);
			$fname = str_replace(":", "", $fname);
			$fname = str_replace("?", "", $fname);
			$fname = str_replace("*", "", $fname);
			$path_parts = pathinfo($fname);
			$fname = str_replace("<Basename>", $path_parts["basename"], $wgDocnamePattern);
			$fname = str_replace("<Filename>", $path_parts["filename"], $fname);
			$fname = str_replace("<Ext>", $path_parts["extension"], $fname);

			#$url = urlencode($url);
			#$url = str_replace("%3A", ":", $url);
			#$url = str_replace("%2F", "/", $url);

			# replace dangerous patterns such as _ % or ~
			foreach($wgURLReplacements as $from => $to)
				$url = preg_replace($from, $to, $url);
		}

		$wgOut->addHTML("@Misc{*,\n");
		$wgOut->addHTML('author = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
		$wgOut->addHTML('title = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
		$wgOut->addHTML('titleaddon = '.$wgTitleDelimLeft.$wgTitleDelimRight.','."\n");
		$wgOut->addHTML('howpublished = '.$wgValueDelimLeft.wfMsg('bibwiki_onlinepaper').$wgValueDelimRight.','."\n");
		$wgOut->addHTML('year = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
		$wgOut->addHTML('keywords = '.$wgValueDelimLeft.$wgValueDelimRight.','."\n");
		$wgOut->addHTML('url = {'.$url.'},'."\n");
		$wgOut->addHTML('urldate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
		if ($wgDownloadsPath != "" and $this->checkPath($wgDownloadsPath) == true)
			$wgOut->addHTML('docname = '.$wgValueDelimLeft.$fname.$wgValueDelimRight.','."\n");
		$wgOut->addHTML('bibdate = '.$wgValueDelimLeft.strftime($wgDateTimeFormat,time()).$wgValueDelimRight.",\n");
		$wgOut->addHTML("}");

		$wgOut->addHTML("</textarea>
		<input id='wpSave' name='wpSave' type='submit' tabindex='5' value='".wfMsg('bibwiki_import')."' accesskey='s'/>");

		#<input type='hidden' name='f' value='".$this->mBibfile->getName()."'/>

		$wgOut->addHTML('in &nbsp;&nbsp;<select width="30" name="f">');
		$privatebibs = bwGetPrivateBibfiles();
		foreach($privatebibs as $f) {
			$f = $wgUser->getName().DIRECTORY_SEPARATOR.$f;
			if ($f == $this->mBibfilename)
				$wgOut->addHTML('<option value="'.$f.'" selected>'.$f.'</option>');
			else
				$wgOut->addHTML('<option value="'.$f.'">'.$f.'</option>');
		}
		$pubbibs = bwGetPublicBibfiles();
		foreach($pubbibs as $f) {
			if ($f == $this->mBibfilename)
				$wgOut->addHTML('<option value="'.$f.'" selected>'.$f.'</option>');
			else
				$wgOut->addHTML('<option value="'.$f.'">'.$f.'</option>');
		}
		$wgOut->addHTML('</select>');

		if ($wgDownloadsPath != "" and $this->checkPath($wgDownloadsPath) == true)
			$wgOut->addHTML("<input class='saveInput' type='checkbox' name='save' checked/>".wfMsg('bibwiki_save_url_to_disc'));
		$wgOut->addHTML("<input type='hidden' name='view' value='".$wgRequest->getVal('view')."'/>
		<input type='hidden' name='action' value='saveurl'/>
		</form>");
	}

	function import() {
		global $wgOut, $wgRequest, $wgHooks, $accesskey, $wgDownloadsPath;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		unset($wgHooks['RenderPageTitle']);

		if ($this->mImportSource == "Amazon") {
			$wgOut->setPageTitle(wfMsg("bibwiki_import_amazon"));
			if ($accesskey == "" or $accesskey == "XXX")
				$this->errorBox(wfMsg("bibwiki_error_empty_amazon_key"));
			else {
			$wgOut->AddHTML("
				<form method='post' action='".$this->getLocalURL()."'>
				<input class='saveInput' style='width:8cm;' type='text' name='url' value=''/><br/>
				<p class='settings_info' style='font-size:12pt; width:400pt'>Amazon URL, ASIN or ISBN 10</p><br/>
				<input type='hidden' name='title' value='".$this->getSpecialPageName()."'/>
				<input type='hidden' name='type' value='Amazon'/>
				<input type='hidden' name='action' value='new'/>
				<input type='hidden' name='view' value='".$wgRequest->getVal("view")."'/>
				<input type='hidden' name='c' value='amazon'/>
				<input type='hidden' name='f' value='".$this->mBibfile->getName()."'/>
				<input type='hidden' name='keyword' value='".$this->mFilter."'/>
				<input type='submit' value='".wfMsg('bibwiki_import')."'/>
				</form>");
			}
		}
		else if ($this->mImportSource == "DDB") {
			$wgOut->setPageTitle(wfMsg("bibwiki_import_ddb"));
			$wgOut->AddHTML("
			<form method='post' action='".$this->getLocalURL()."'>
			".wfMsg('bibwiki_import_hint')."
			<br/>
			<textarea tabindex='1' accesskey=',' name='content' id='bibeditbox' rows='25' cols='80'></textarea><br/>
			<input type='hidden' name='title' value='".$this->getSpecialPageName()."'/>
			<input type='hidden' name='type' value='DDB'/>
			<input type='hidden' name='action' value='new'/>
			<input type='hidden' name='view' value='".$wgRequest->getVal("view")."'/>
			<input type='hidden' name='c' value='ddb'/>
			<input type='hidden' name='keyword' value='".$this->mFilter."'/>
			<input type='hidden' name='f' value='".$this->mBibfile->getName()."'/>
			<input type='submit' value='".wfMsg('bibwiki_import')."'/></td>
			</form>");
		}
		else if ($this->mImportSource == "SA") {
			$wgOut->setPageTitle(wfMsg("bibwiki_import_sa"));
			$wgOut->AddHTML("
			<form method='post' action='".$this->getLocalURL()."'>
			".wfMsg('bibwiki_import_hint')."
			<br/>
			<textarea tabindex='1' accesskey=',' name='content' id='bibeditbox' rows='25' cols='80'></textarea><br/>
			<input type='hidden' name='title' value='".$this->getSpecialPageName()."'/>
			<input type='hidden' name='type' value='SA'/>
			<input type='hidden' name='action' value='new'/>
			<input type='hidden' name='view' value='".$wgRequest->getVal("view")."'/>
			<input type='hidden' name='c' value='sa'/>
			<input type='hidden' name='keyword' value='".$this->mFilter."'/>
			<input type='hidden' name='f' value='".$this->mBibfile->getName()."'/>
			<input type='submit' value='".wfMsg('bibwiki_import')."'/></td>
			<input type='checkbox' name='abstract'".((($_COOKIE['abstract'] == 'on') or (isset($_COOKIE['abstract']) == false))?'checked':'')."/> ".wfMsg('bibwiki_import_abstracts')."
			</form>");
		}
		else if ($this->mImportSource == "Opac") {
			$wgOut->setPageTitle(wfMsg("bibwiki_import_opac"));
			$wgOut->AddHTML("
			<form method='get' action='".$this->getLocalURL()."'>
			".wfMsg('bibwiki_import_hint')."
			<br/>
			<textarea tabindex='1' accesskey=',' name='content' id='bibeditbox' rows='25' cols='80'></textarea><br/>
			<input type='hidden' name='title' value='".$this->getSpecialPageName()."'/>
			<input type='hidden' name='type' value='Opac'/>
			<input type='hidden' name='action' value='new'/>
			<input type='hidden' name='view' value='".$wgRequest->getVal("view")."'/>
			<input type='hidden' name='c' value='opac'/>
			<input type='hidden' name='keyword' value='".$this->mFilter."'/>
			<input type='hidden' name='f' value='".$this->mBibfile->getName()."'/>
			<input type='submit' value='".wfMsg('bibwiki_import')."'/></td>
			</form>");
		}
		else if ($this->mImportSource == "arxiv") {
			$wgOut->setPageTitle(wfMsg("bibwiki_import_arxiv"));
			$wgOut->AddHTML("
			<form method='post' action='".$this->getLocalURL()."'>
			".wfMsg('bibwiki_import_hint')."
			<br/>
			<textarea tabindex='1' accesskey=',' name='content' id='bibeditbox' rows='25' cols='80'></textarea><br/>
			<input type='hidden' name='title' value='".$this->getSpecialPageName()."'/>
			<input type='hidden' name='type' value='arxiv'/>
			<input type='hidden' name='action' value='new'/>
			<input type='hidden' name='view' value='".$wgRequest->getVal("view")."'/>
			<input type='hidden' name='c' value='sa'/>
			<input type='hidden' name='keyword' value='".$this->mFilter."'/>
			<input type='hidden' name='f' value='".$this->mBibfile->getName()."'/>
			<input type='submit' value='".wfMsg('bibwiki_import')."'/></td>
			<input type='checkbox' name='abstract'".((($_COOKIE['abstract'] == 'on') or (isset($_COOKIE['abstract']) == false))?'checked':'')."/> ".wfMsg('bibwiki_import_abstracts')."
			</form>");
		}
		else if ($this->mImportSource == "loc") {
			$wgOut->setPageTitle(wfMsg("bibwiki_import_loc"));
			$wgOut->AddHTML("
			<form method='post' action='".$this->getLocalURL()."'>
			".wfMsg('bibwiki_import_hint')."
			<br/>
			<textarea tabindex='1' accesskey=',' name='content' id='bibeditbox' rows='25' cols='80'></textarea><br/>
			<input type='hidden' name='title' value='".$this->getSpecialPageName()."'/>
			<input type='hidden' name='type' value='loc'/>
			<input type='hidden' name='action' value='new'/>
			<input type='hidden' name='view' value='".$wgRequest->getVal("view")."'/>
			<input type='hidden' name='c' value='sa'/>
			<input type='hidden' name='keyword' value='".$this->mFilter."'/>
			<input type='hidden' name='f' value='".$this->mBibfile->getName()."'/>
			<input type='submit' value='".wfMsg('bibwiki_import')."'/></td>
			</form>");
		}
		else if ($this->mImportSource == "URL") {
			$wgOut->setPageTitle(wfMsg("bibwiki_import_url"));
			$wgOut->AddHTML("
			<form method='post' action='".$this->getLocalURL()."'>
			<table>
			<tr>
			<td><b>URL</b></td>
			<td><input class='saveInput' style='width:8cm;' type='text' name='url' value=''/></td>
			</tr>
			<tr>
			<tr>
			<td></td>
			<td>
			<input type='hidden' name='action' value='checkurl'/>
			<input type='hidden' name='title' value='".$this->getSpecialPageName()."'/>
			<input type='hidden' name='view' value='".$wgRequest->getVal("view")."'/>
			<input type='hidden' name='keyword' value='".$this->mFilter."'/>
			<input type='hidden' name='f' value='".$this->mBibfile->getName()."'/>
			<input type='submit' value='".wfMsg('bibwiki_import')."'/></td>
			</tr>
			</table>
			</form>");
		}
	}

	function viewSource() {
		global $wgOut;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

    	$this->printActionBox();

		$startkey = strtolower($this->mStartkey);

		#$wgOut->addHTML("<div name='bibContent'>");
		$wgOut->addHTML("<pre id='bibContent'>");
		if ($this->mBibfile->open() == false) {
			$wgOut->errorBox(wfMsg("bibwiki_error_opening"));
			return false;
		}
		$print_this_bibitem = false;
		while (($sz = $this->mBibfile->nextFilteredRecord()) !== false) {
			if ($startkey != "") {
				$lwrkey = strtolower($this->mBibfile->parseCiteKey($sz));
				# we are looking for a specific key
				if (bwStrEqual($startkey, $lwrkey))
					$print_this_bibitem = true;
			}
			else
				$print_this_bibitem = true;

			if ($print_this_bibitem)
				$wgOut->addHTML($sz."\n");

			if ($startkey != "" and $print_this_bibitem)
				break;
		}
		$this->mBibfile->close();
		$wgOut->addHTML('</pre>');
		#$wgOut->addHTML('</div>');
	}

	function viewKeywords() {
		global $wgOut;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$bibitem = new Bibitem();
		$found_keywords = array();
		$max = 0;

		if ($this->mBibfile->open() == false) {
			$wgOut->errorBox(wfMsg("bibwiki_error_opening"));
			return false;
		}
		while (($sz = $this->mBibfile->nextFilteredRecord()) !== false) {
			$bibitem->set($sz);
			if ($bibitem->parse() == false) {
				$this->errorBox(wfMsg("bibwiki_error_parse"));
				return false;
			}
			foreach(explode(" ", $bibitem->getPrettyValByKey("keywords")) as $keyword) {
				$found_keywords[$keyword]++;
				if ($found_keywords[$keyword] > $max)
					$max = $found_keywords[$keyword];
			}
		}
		$this->mBibfile->close();

    	$this->printActionBox();

    	ksort($found_keywords);
    	foreach($found_keywords as $k => $v) {
    		$r = round(($v/$max)*20+10);
    		$wgOut->addHTML('<a style="font-size:'.$r.'pt;" href="'.$this->getLocalURL(array('keyword='.$k, $this->mBibfileQuery)).'">'.$k.'</a> ');
    	}
	}

	function viewAuthors() {
		global $wgOut;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$bibitem = new Bibitem();
		$found_authors = array();
		$max = 0;

		if ($this->mBibfile->open() == false)
			$wgOut->addHTML(wfMsg("bibwiki_error_opening"));
		while (($sz = $this->mBibfile->nextFilteredRecord()) !== false) {
			$bibitem->set($sz);
			if ($bibitem->parse() == false) {
				$this->errorBox(wfMsg("bibwiki_error_parse"));
				return false;
			}

			/**
			 * @todo Make a full parser for authors... Though this
			 * will work for the most cases.
			 */
			foreach (array("author", "editor") as $key) {
	    		$authors = explode(" and ", $bibitem->getPrettyValByKey($key));
	    		foreach ($authors as $a) {
	    			if ($a != "" and $a != "others") {
	    				$a = bwTeXToHTML($a);
		    			$rv = bwParseAuthor($a);
		    			if ($rv["firstname_initial"] != "")
	    					$found_authors[strtolower($rv["surname_simplified"])] = $rv["surname"].", ".$rv["firstname_initial"].".";
	    				else
	    					$found_authors[strtolower($rv["surname_simplified"])] = $rv["surname"];
    				}
	    		}
    		}
		}
		$this->mBibfile->close();

    	ksort($found_authors);
    	$cnt = count($found_authors);
    	$c = "";
    	$i = 0;
    	$print_h3 = false;
    	$new_list = false;

    	$this->printActionBox();

		$wgOut->addHTML('<br style="clear:both;"/>');
		$wgOut->addHTML('<table width="100%"><tr valign="top"><td>');
    	foreach($found_authors as $k => $v) {
    		if (array_search(strtolower(trim($k,", ")), array("k.a.", "k.~a.,", "o.a.", "o.~a.", "???")) !== false) continue;
    		$i++;
    		$ac = substr($v, 0, 1);
			if ($i > $cnt/3) {
				$wgOut->addHTML('</ul></td><td>');
				$print_h3 = true;
				$new_list = true;
				$i = 0;
			}
    		if ($ac != $c) {
				$c = $ac;
				if (!$new_list) $wgOut->addHTML('</ul>');
				$wgOut->addHTML('<h3>'.$c.'</h3>');
				$new_list = true;
				$print_h3 = false;
			}
    		if ($print_h3) {
				if (!$new_list) $wgOut->addHTML('</ul>');
				$wgOut->addHTML('<h3>'.$c.' ('.wfMsg('bibwiki_cont').')</h3>');
				$new_list = true;
				$print_h3 = false;
			}
    		if ($new_list) {
				$wgOut->addHTML('<ul>');
				$new_list = false;
			}
			$ap = explode(",",$v);
    		$wgOut->addHTML('<li><a href="'.$this->getLocalURL(array('keyword='.$ap[0], $this->mBibfileQuery)).'">'.$v.'</a></li>');
    	}
    	$wgOut->addHTML('</ul></td></tr></table>');
	}

	function viewStatistics() {
		global $wgOut;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$bibitem = new Bibitem();
		$count["All"] = 0;

		if ($this->mBibfile->open() == false) $wgOut->addHTML("Error: Opening file failed.");
		while (($sz = $this->mBibfile->nextFilteredRecord()) !== false) {
			$bibitem->set($sz);
			if ($bibitem->parse() == false) {
				$this->errorBox(wfMsg("bibwiki_error_parse"));
				return false;
			}
			$count["All"]++;
			$count[mb_strtolower($bibitem->getType())]++;
		}
		$this->mBibfile->close();

    	$this->printActionBox();

		$wgOut->addHTML("<pre id='bibContent'><br />");
		$wgOut->addHTML(wfMsg('bibwiki_all_entries').': '.sprintf("%".(20-mb_strlen(wfMsg('bibwiki_all_entries')))."d",$count["All"]).'<br><br>');
		foreach($count as $k => $v) {
			if ($k != "All")
				$wgOut->addHTML($k.": ".sprintf("%".(20-mb_strlen($k))."d",$v)."<br>");
		}
		$wgOut->addHTML("<br /></pre>");
	}

	function globalSearch() {
	}

	function printActionBox() {
		global $wgOut, $wgRequest, $wgHowManyItemsPerPage, $wgBreakLines,
			$wgLineBreakAt, $wgBibPath, $wgFetchAndViewBookCovers, $wgBookCoverDirectory,
			$wgUploadPath, $wgArticlePath, $wgScript, $wgDownloadsUrl,
			$wgISBNLinkTags, $wgTitleLink, $wgAuthorLink, $wgISBNLink,
			$wgDefaultBib, $wgUser, $wgURLReverseReplacements,
			$wgEnableExport, $wgValueDelimRight, $wgContLang,
			$wgConvertAnsiToTeX, $wgTitleLinkTags, $wgEnableEdit;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$bibfile = $this->mBibfile->mFilename;
		$bibquery = "f=".$bibfile;
		$actionquery = "";
		if ($wgRequest->getVal("action") != "")
			$actionquery = "action=".$wgRequest->getVal("action");

		$wgOut->addHTML('<div id="bibcommand"><form method="get" action="'.$this->getLocalURL().'">');
		$wgOut->addHTML('<input type="hidden" value="'.$bibfile.'" name="f" />');
		$wgOut->addHTML('<input type="hidden" value="'.$this->getTitle()->getFullText().'" name="title" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("view").'" name="view" />');
		$wgOut->addHTML('<input type="hidden" value="'.$wgRequest->getVal("action").'" name="action" />');
		if ($this->mBibfilename != "" and $this->mBibfilename != $wgDefaultBib) {
			$wgOut->addHTML('<input type="hidden" value="'.$bibfile.'" name="f" />');
		}

		#$wgOut->addHTML('<table><tr><td>');
		$wgOut->addHTML(wfMsg("bibwiki_file").' ');
		#$wgOut->addHTML('</td><td>');
		$wgOut->addHTML($this->makeKnownLink($this->mBibfilename, array($actionquery, $bibquery)));

		/*$wgOut->addHTML('</td><td>');
		$wgOut->addHTML('view ');
		$wgOut->addHTML('</td><td>');
		$wgOut->addHTML('<a href="'.$this->getLocalURL(array($bibquery, $keywordquery)).'">bibliography</a> ');
		$wgOut->addHTML('| <a href="'.$this->getLocalURL(array("action=viewauthors", $bibquery, $keywordquery)).'">authors</a>'."\n");
		$wgOut->addHTML('| <a href="'.$this->getLocalURL(array("action=viewkeywords", $bibquery, $keywordquery)).'">keywords</a>'."\n");
		$wgOut->addHTML('| <a href="'.$this->getLocalURL(array("action=viewjournals", $bibquery, $keywordquery)).'">journals</a>'."\n");
		$wgOut->addHTML('| <a href="'.$this->getLocalURL(array("action=viewsource", $bibquery, $keywordquery)).'">bibtex-code</a>'."\n");
		$wgOut->addHTML('| <a href="'.$this->getLocalURL(array("action=viewstats", $bibquery, $keywordquery)).'">statistics</a>'."\n");
		$wgOut->addHTML('</td></tr><tr><td>');*/

		#print $this->getFullURL();

		/*$sort = $wgRequest->getVal("sort");
		$sortorder = $wgRequest->getVal("sortorder");*/
		$wgOut->addHTML(', '.wfMsg("bibwiki_filter_by").' ');
		#$wgOut->addHTML('</td><td>');
		$wgOut->addHTML('<input class="bibtaginput" type="text" name="keyword" value="'.$wgRequest->getVal("keyword").'">');
		if ($this->mFilter != "") {
			$wgOut->addHTML(" (");
			$wgOut->addHTML($this->makeKnownLink(wfMsg("bibwiki_remove_filter"), array("view=".$wgRequest->getVal("view"), $actionquery, $bibquery)));
			$wgOut->addHTML(")");
		}
		/*$wgOut->addHTML('</td><td>');
		$wgOut->addHTML('sort by ');
		$wgOut->addHTML('</td><td>');
		$wgOut->addHTML($this->makeKnownLink('bibdate', array($actionquery, $bibquery, "sortorder=asc"))." ");
		$wgOut->addHTML('| '.$this->makeKnownLink('year', array($actionquery, $bibquery, "sort=year", "sortorder=asc"))." ");
		$wgOut->addHTML('| '.$this->makeKnownLink('author', array($actionquery, $bibquery, "sort=author", "sortorder=asc"))." ");
		$wgOut->addHTML('| '.$this->makeKnownLink('journal', array($actionquery, $bibquery, "sort=jounal", "sortorder=asc"))." ");
		*/

		#$wgOut->addHTML('</td></tr></table>');
		#debug $wgOut->addHTML(' BIBWIKI_BIBFILE: '.$_COOKIE["BIBWIKI_BIBFILE"]);
		$wgOut->addHTML('</form></div>');
	}

	function makeDetailedHeader() {
	}

	function viewDetailed() {
		global $wgOut, $wgUser, $wgRequest, $wgHowManyItemsPerPage, $wgBreakLines,
			$wgLineBreakAt, $wgBibPath, $wgFetchAndViewBookCovers, $wgBookCoverDirectory,
			$wgUploadPath, $wgArticlePath, $wgScript, $wgDownloadsUrl,
			$wgISBNLinkTags, $wgTitleLink, $wgAuthorLink, $wgISBNLink,
			$wgDefaultBib, $wgUser, $wgURLReverseReplacements,
			$wgEnableExport, $wgValueDelimRight, $wgContLang,
			$wgConvertAnsiToTeX, $wgTitleLinkTags, $wgEnableEdit;

		#LoadSettings
        if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
        if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        	include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$start_timer = microtime(true);

		$startbib = ($wgRequest->getVal("start") > 0)? $wgRequest->getVal("start") : 1;
		$maxbib = ($wgRequest->getVal("max") > 0)? $wgRequest->getVal("max") : $wgHowManyItemsPerPage;
		$startkey = strtolower($this->mStartkey);
		$startkey_found = false;
		$bibs_printed = 0;
		$print_navbar = ($this->mFilter == "" and $wgHowManyItemsPerPage > 0);
		$keycounter = array();

		if ($this->mBibfile->open() == false)
			$wgOut->addHTML(wfMsg("bibwiki_error_opening"));


		#make a linkbatch for the bibitems
		$linkbatch = new LinkBatch();
		foreach ($this->mBibfile->getKeys() as $key)
			#$linkbatch->add(NS_BIB, $key);
			$linkbatch->add(NS_MAIN, $key);
		$wikikeys = $linkbatch->execute();

		$bibitem = new Bibitem();

		$detailedPrinter = new BibitemDetailedPrinter($this->mBibfileQuery,
			$wikikeys);

		$output = "";    # gather output here

		while (($sz = $this->mBibfile->nextFilteredRecord()) !== false) {
			# To identify a bibitem in a file, not only the
			# key is used but also the number of that key. This
			# is necessary in case of double keys, which should
			# not happen in a file, but Bibwiki should also
			# be able to handle such cases.
			# So $keycounter counts the occurrences of each key.
			# This information is passed to other functions such
			# as edit via the nr= CGI argument.

			$bibitem->set($sz);
			$key = $bibitem->getCiteKey();
			if ($key == false) {
				$this->errorBox(wfMsg("bibwiki_error_parse"));
				return false;
			}
			$lwrkey = mb_strtolower($key);
			$keycounter[$key]++;

			# check if we can print this bibitem

			$print_this_bibitem = false;
			if ($print_navbar == false)
				# there's no navigationbar so print out everything
				$print_this_bibitem = true;
			elseif ($startkey != "") {
				# we are looking for a specific key
				if ($startkey_found == true or bwStrEqual($startkey, $lwrkey)) {
					# we have already found or successfully passed the key
					if ($maxbib > 0 and $bibs_printed <= $maxbib)
						# we are within the limits
						$print_this_bibitem = true;
					elseif ($maxbib == 0)
						# no limit at all
						$print_this_bibitem = true;
					elseif ($bibs_printed >= $maxbib)
						# we are outside the limits
						break;

					if (bwStrEqual($startkey, $lwrkey)) {
						$startkey_found = true;
						$startbib = $this->mBibfile->getPosition();
					}
				}
			}
			elseif ($maxbib > 0) {
				# there a limit of items per page
				if ($this->mBibfile->getPosition() >= $startbib and $bibs_printed < $maxbib)
					# we are within the limit
					$print_this_bibitem = true;
				elseif ($bibs_printed >= $maxbib)
					break;
			}

			if ($print_this_bibitem == true)
				$bibs_printed++;
			else
				continue;

			# we're gonna printing this, so parse it
			if ($bibitem->parse() == false) {
				$this->errorBox(wfMsg("bibwiki_error_parse"));
				return false;
			}

			#
			#   print header of bibitem with links (edit, export etc.)
			#

			$header = array();

			### Book cover ###

			$coverimage = "";
			$isbn = str_replace("-", "", $bibitem->getPrettyValByKey("isbn"));
		    if ($isbn != "" and
		        $wgFetchAndViewBookCovers and
		        file_exists(bwMakePath($wgBookCoverDirectory, $isbn).".jpg"))
	        	$coverimage .= "<div style='float:right; border:1px solid #BBBBBB;'><a href='".$wgUploadPath."/".$isbn."-big.jpg'><img src='".$wgUploadPath."/".$isbn.".jpg' alt=''/></a></div>";

			# $output .= ',<br/>';

			### edit ###

			if ($this->userIsAllowedToEdit()) {
				$header[] = '<span class="bibeditsection"><a href="'.$this->getLocalURL(array('action=edit', 'view='.$wgRequest->getVal("view"), 'key='.$bibitem->getCiteKey(), 'start='.$startbib, 'nr='.$keycounter[$bibitem->getCiteKey()], $this->mFilterQuery, $this->mBibfileQuery)).'">'.wfMsg('bibwiki_edit').'</a></span>';
			}

			### export ###

			if ($wgEnableExport) {
				$header[] = '<span class="bibeditsection"><a target="export" href="'.$this->getLocalURL(array('action=export', 'keyword='.$bibitem->getCiteKey(), $this->mBibfileQuery)).'">'.wfMsg('bibwiki_export_item').'</a></span>';
			}

			### add article ###

			$lwrtype = mb_strtolower($bibitem->getType());
			if (bwStrEqual($lwrtype, "book") and
				$bibitem->getValByKey("editor") != "" and
				$bibitem->getValByKey("author") == "")
				$header[] = '<span class="bibeditsection"><a href="'.$this->getLocalURL(array('action=new', 'view='.$wgRequest->getVal("view"), 'type=Incollection', 'crossref='.$bibitem->getCiteKey(), 'start='.$startbib, $this->mBibfileQuery, $this->mFilterQuery)).'">'.wfMsg('bibwiki_add_article').'</a></span> ';

			### view source ###

			$header[] = '<span class="bibeditsection"><a href="'.$this->getLocalURL(array('action=viewsource', 'startkey='.$bibitem->getCiteKey(), $this->mFilterQuery, $this->mBibfileQuery)).'">'.wfMsg('bibwiki_view_source').'</a></span>';

			### title link tags ###

			if ($bibitem->getValByKey("title") != "" and !empty($wgTitleLinkTags)) {
				$author = $bibitem->getValByKey("author");
				if ($author == "") $author = $bibitem->getValByKey("editor");
				$title = $bibitem->getValByKey("title");
				$title = bwTeXToHTML($title);
				$author = bwTeXToHTML($author);
	    		$title = trim($title, '{}"');
	    		$title = urlencode(bwHTMLDecode($title));
	    		$author = trim($author, '{}"');
	    		$author = urlencode(bwHTMLDecode($author));
	   			$linktags = array();
	   			foreach($wgTitleLinkTags as $t) {
	   				$href = str_replace("\$author", $author, $t["href"]);
	   				$href = str_replace("\$title", $title, $href);
	   				$href = str_replace("\$self", $this->getLocalURL(), $href);
	   				$linktags[] = '<span class="bibeditsection"><a href="'.$href.'" target="'.$t["target"].'">'.$t["text"].'</a></span>';
	   			}
				$header[] = implode(' <span class="bibeditsection">|</span> ',$linktags);
			}

			$output .= $coverimage.implode(' <span class="bibeditsection">|</span> ',$header) . "<br/>";

			$output .= '<pre id="bibContent">';
			$bibitem->setMacros($this->mBibfile->getMacros());
			$output .= $detailedPrinter->prettyPrint($bibitem);
    		$output .= "}</pre><br/>";
		}

		#
		#  Now printing the output
		#
		#  1st: header stuff
		#
		$this->printActionBox();

		if ($wgRequest->getVal("print_settings_saved") == "1") {
			$wgOut->addHTML("<p style='border: 2px solid darkblue; padding: 10px 20px; background-color:#F3F3F3; width:400pt'>".wfMsg("bibwiki_settings_saved")."</p>");
		}

		if ($wgUser->getName() == "BibwikiAdmin" and
			$wgUser->mPassword == $wgUser->encryptPassword("secret")) {
			$this->errorBox(wfMsg("bibwiki_change_pwd_hint"));
		}

		if ($bibs_printed == 0)
			$output .= wfMsg("bibwiki_notfound");

		# $wgOut->addHTML('<div name="bibContent" id="bibContent">');
		# $wgOut->addHTML('<pre id="bibContent">');

		#
		#   2nd: navigation bar
		#

		if ($print_navbar)
			if ($this->printNavbar($startbib, $maxbib))
				$wgOut->addHTML("<br/><br/>");

		$print_double_key_warning = false;
		if (count($this->mBibfile->getDoubleKeys()) > 0) {
			$dk = $this->mBibfile->getDoubleKeys();
			$msg = "";
			while (count($dk) > 0) {
				$key = array_pop($dk);
				$msg .= "<br/>&bull; " . $key;
				if (strpos($this->mFilter, $key) !== 0) {
				 	$msg .= " (<a href='".$this->getLocalURL(array('keyword='.$key, $this->mBibfileQuery))."'>".wfMsg("bibwiki_filter_by_key")."</a>)\n";
				}
			}
			$msg .= "";
			$this->errorBox(wfMsg("bibwiki_double_key_warning").": ".$msg, wfMsg("bibwiki_warning"));
			#$print_double_key_warning = true;
			$double_key = array_pop($this->mBibfile->getDoubleKeys());
		}

		#
		#  3rd: the bibliography
		#

		$wgOut->addHTML($output);

		#
		#  4th: bottom navigation bar
		#

		$stop_timer = microtime(true);
		$wgOut->addHTML(sprintf("<div class='bibeditsection' style='float:right'>%.1f secs</div>", $stop_timer - $start_timer));

		if ($print_navbar)
			if ($this->printNavbar($startbib, $maxbib))

		#
		#  5th: footer stuff (javascripted error boxes etc)
		#

		#$wgOut->addHTML("</pre>");
		/*$wgOut->addHTML('<span class="bibeditsection">');
		$wgOut->addHTML(wfMsg('bibwiki_all_entries').': '.$count["All"].'<br>');
		foreach($count as $k => $v) {
			if ($k != "All") $wgOut->addHTML($k.": ".$v."<br>");
		}
		$wgOut->addHTML('</span>');*/
		#$wgOut->addHTML("</div>");


		/*if ($print_double_key_warning and $wgRequest->getVal("nowarn") !== "1") {
			$wgOut->addHTML('<script language="Javascript">');
			$wgOut->addHTML('alert("'.$double_key.': '.wfMsg("bibwiki_doublekey_error").'");');
			$wgOut->addHTML('window.location.href = "'.$_SERVER["SCRIPT_NAME"].'/'.$double_key.'?'.$_SERVER["QUERY_STRING"].'&f='.$this->mBibfilename.'&view='.$wgRequest->getVal("view").'&nowarn=1&doublekey='.$double_key.'";');
			$wgOut->addHTML('window.location.href = "'.$this->getFullURL(array("keyword=".$double_key, 'view='.$wgRequest->getVal("view"), $this->mBibfileQuery, 'nowarn=1', 'doublekey='.$double_key)).'";');
			$wgOut->addHTML('</script>');
		}*/
		if ($wgRequest->getVal("renamewarn") == "1") {
			$wgOut->addHTML('<script language="Javascript">');
			$wgOut->addHTML('alert("'.wfMsg('bibwiki_rename_error').'");');
			$wgOut->addHTML('</script>');
		}

		#
		#  6th: Closing the Bibfile
		#

		$this->mBibfile->close();

	}

	function printNavbar($startbib, $maxbib) {
		global $wgOut, $wgRequest;

		$navbar = array();
		if ($startbib > 1) {
			$prev_start = $startbib-$maxbib;
			$pprev_start = $startbib-2*$maxbib;
			if ($prev_start < 1) $prev_start = 1;
			if ($pprev_start < 1) $pprev_start = 1;

			# debug
			# $wgOut->addHTML($nav_start." = ".$startbib." - ".$maxbib);

			if ($prev_start > 1) $navbar[] = '<a href="'.$this->getLocalURL(array('view='.$wgRequest->getVal("view"), 'start='.$pprev_start, $this->mFilterQuery, $this->mBibfileQuery))."\">&lt;&lt;</a>";
			$navbar[] = '<a href="'.$this->getLocalURL(array('view='.$wgRequest->getVal("view"), 'start='.$prev_start, $this->mFilterQuery, $this->mBibfileQuery))."\">&lt; ".wfMsg("bibwiki_prev")."</a>";
		}
		if ($this->mBibfile->nomoreFilteredItems() == false) {
			$navbar[] = '<a href="'.$this->getLocalURL(array('view='.$wgRequest->getVal("view"), 'start='.($this->mBibfile->getPosition()), $this->mFilterQuery, $this->mBibfileQuery))."\">".wfMsg("bibwiki_next")." &gt;</a>";
			$navbar[] = '<a href="'.$this->getLocalURL(array('view='.$wgRequest->getVal("view"), 'start='.($this->mBibfile->getPosition()+$maxbib), $this->mFilterQuery, $this->mBibfileQuery))."\">&gt;&gt;</a>";
		}
		$navbar = implode(" | ", $navbar);
		if ($navbar != "") {
			$wgOut->addHTML($navbar);
			return true;
		}
		return false;
	}

	function makeCompactHeader($bibitem, $startbib = 0, $keycounter = array(),
		$wikikeys = array()) {
		global $wgFetchAndViewBookCovers, $wgBookCoverDirectory,
			$wgEnableEdit, $wgEnableExport, $wgTitleLinkTags,
			$wgConvertAnsiToTeX, $wgUser,
			$wgDownloadsUrl, $wgDownloadsPath, $wgISBNLinkTags, $wgRequest;

		#Load Settings
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
		if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		# Array of return values;
		$rva = array();

	    ### Name anchor

    	$anchor = "<a name='".$bibitem->getCiteKey()."'></a>";

	    ### Book Cover

		$isbn = str_replace("-", "", $bibitem->getPrettyValByKey("isbn"));
		$img = "";
	    if ($isbn != "" and
	        $wgFetchAndViewBookCovers and
	        file_exists(bwMakePath($wgBookCoverDirectory, $isbn).".jpg"))
        	$img = "<div style='float:right; border:1px solid #BBBBBB; margin-top:6px; margin-left:3px;'>".
        		"<a href='".$wgUploadPath."/".$isbn."-big.jpg'>".
        		"<img height='30' src='".$wgUploadPath."/".$isbn.".jpg' alt=''/>".
        		"</a></div>";

		### Link

		if ($wikikeys[$bibitem->getCiteKey()] > 0)
			$rva[] = '<span class="bibeditsection_neutral">'.
				$wgUser->getSkin()->makeKnownLink($bibitem->getCiteKey()).
				'</span>';
		else
			$rva[] = '<span class="bibeditsection">'.
				$wgUser->getSkin()->makeBrokenLink($bibitem->getCiteKey()).
				'</span>';

		### detailed view

		$title = $bibitem->getPrettyValByKey("title");
		#preg_match("/[a-zA-Z0-9\. ]+/", $title, $match);
		$rva[] = '<span class="bibeditsection">'.
			'<a href="'.$this->getLocalURL(
				array('view=detailed',
					#'keyword='.implode(" ", array($bibitem->getCiteKey(), $match[0], $bibitem->getPrettyValByKey("isbn"))),
					'startkey='.$bibitem->getCiteKey(),
					$this->mBibfileQuery)).
			'">'.wfMsg('bibwiki_detailedview').'</a>'.
			'</span> ';

		### edit

		if ($this->userIsAllowedToEdit()) {
			$rva[] = '<span class="bibeditsection">'.
				'<a href="'.$this->getLocalURL(
					array('action=edit',
						'view='.$wgRequest->getVal("view"),
						'key='.$bibitem->getCiteKey(),
						'start='.$startbib,
						'nr='.$keycounter[$bibitem->getCiteKey()],
						$this->mFilterQuery,
						$this->mBibfileQuery)).
				'">'.wfMsg('bibwiki_edit').'</a>'.
				'</span> ';
		}

		### export

		if ($wgEnableExport) {
			$rva[] = '<span class="bibeditsection"><a target="export" href="'.
				$this->getLocalURL(
					array(
						'action=export',
						'keyword='.$bibitem->getCiteKey(),
						$this->mBibfileQuery
					)
				).
				'">'.
				wfMsg('bibwiki_export_item').
				'</a></span> ';
		}

		### file article

		$lwrtype = mb_strtolower($bibitem->getType());
		if (bwStrEqual($lwrtype, "book") and
			$bibitem->getPrettyValByKey("editor") != "" and
			$bibitem->getPrettyValByKey("author") == "")
			$rva[] = '<span class="bibeditsection"><a href="'.
				$this->getLocalURL(
					array('action=new',
						'view='.$wgRequest->getVal("view"),
						'type=Incollection',
						'crossref='.$bibitem->getCiteKey(),
						'start='.$startbib,
						$this->mBibfileQuery,
						$this->mFilterQuery
					)
				).
				'">'.wfMsg('bibwiki_add_article').'</a></span>';

		### Title Links

		if ($bibitem->getPrettyValByKey("title") != "" and
			!empty($wgTitleLinkTags))
		{
			$author = bwFirstOf(
				array(
					$bibitem->getPrettyValByKey("author"),
					$bibitem->getPrettyValByKey("editor")
				)
			);
			$title = $bibitem->getPrettyValByKey("title");
    		$title = trim($title, '{}"');
    		$title = urlencode(bwHTMLDecode($title));
    		$author = trim($author, '{}"');
    		$author = urlencode(bwHTMLDecode($author));
   			$linktags = array();
   			foreach($wgTitleLinkTags as $t) {
   				$href = str_replace("\$author", $author, $t["href"]);
   				$href = str_replace("\$title", $title, $href);
   				$href = str_replace("\$self", $this->getLocalURL(), $href);
   				$linktags[] .= '<span class="bibeditsection"><a href="'.
   					$href.'" target="'.$t["target"].'">'.$t["text"].
   					'</a></span>';
   			}
			$rva[] = implode(' <span class="bibeditsection">|</span> ', $linktags);
		}

		### DOI

		if ($bibitem->getPrettyValByKey("doi") != "") {
	    	$rva[] = '<span class="bibeditsection">'.
		    	'<a target="doi" href="http://dx.doi.org/'.$bibitem->getPrettyValByKey("doi").'">DOI</a>'.
		    	'</span>';
		}

		### ArXiv

		if ($bibitem->getPrettyValByKey("arxiv") != "") {
	    	$rva[] = '<span class="bibeditsection">'.
			    '<a target="arxiv" href="http://arxiv.org/abs/'.$bibitem->getPrettyValByKey("arxiv").'">ArXiv</a>'.
		    	'</span>';
		}

		### URL

    	if ($bibitem->getPrettyValByKey("url") != "") {
    		$url = bwHTMLDecode($bibitem->getPrettyValByKey("url"));

			# reverse the replacements
			$url = preg_replace(array_keys($wgURLReverseReplacements),
				array_values($wgURLReverseReplacements), $url
			);

	    	$rva[] = '<span class="bibeditsection">'.
	    		'<a target="extern" class="invisible" href="'.$url.'">URL</a>'.
		    	'</span>';
    	}

    	### ISBN

    	if ($bibitem->getPrettyValByKey("isbn") != "" and !empty($wgISBNLinkTags)) {
   			$linktags = array();
   			foreach($wgISBNLinkTags as $t) {
   				$href = str_replace("\$isbn", $isbn, $t["href"]);
   				$href = str_replace("\$self", $this->getLocalURL(), $href);
   				$linktags[] = '<span class="bibeditsection"><a href="'.$href.'" target="'.$t["target"].'">'.$t["text"].'</a></span>';
   			}
			$rva[] = implode(' <span class="bibeditsection">|</span> ', $linktags);
    	}

		### PDF

    	$keynrs = $bibitem->getAllKeynrs(array("docname", "pdf", "file"));
    	if (count($keynrs) > 0 and $wgDownloadsUrl != "")
    	{
	    	$pdf = '<span class="bibeditsection">'.wfMsg('bibwiki_attachment').' ';
	    	$pdfcount = 1;
	    	foreach($keynrs as $keynr)
	    	{
	    		$url = $bibitem->getPrettyVal($keynr);
		    	if ($url != "") {
					# reverse the replacements
					#$url = preg_replace(array_keys($wgURLReverseReplacements),
					#	array_values($wgURLReverseReplacements), $url
					#);
			    	$pdf .= '<a target="docname" class="link-pdf" href="'.$wgDownloadsUrl.'/'.$url.'">&nbsp;</a>';
			    	$pdfcount++;
		    	}
	    	}
		    $pdf .= '</span>';
	    	$rva[] = $pdf;
		}

		return "\n".$anchor.$img.implode(' <span class="bibeditsection">|</span> ', $rva);
	}


	function viewCompact() {
		global $wgOut, $wgUser, $wgRequest, $wgHowManyItemsPerPage, $wgBreakLines,
			$wgLineBreakAt, $wgBibPath, $wgFetchAndViewBookCovers, $wgBookCoverDirectory,
			$wgUploadPath, $wgArticlePath, $wgScript, $wgDownloadsUrl,
			$wgISBNLinkTags, $wgTitleLink, $wgAuthorLink, $wgISBNLink,
			$wgDefaultBib, $wgUser, $wgURLReverseReplacements,
			$wgEnableExport, $wgValueDelimRight, $wgContLang,
			$wgConvertAnsiToTeX, $wgTitleLinkTags, $wgEnableEdit;

		#LoadSettings
        if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
	        include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
        if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        	include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

		$start_timer = microtime(true);

		$startbib = ($wgRequest->getVal("start") != "")? $wgRequest->getVal("start") : 1;
		$maxbib = ($wgRequest->getVal("max") != "")? $wgRequest->getVal("max") : $wgHowManyItemsPerPage;
		$startkey = strtolower($this->mStartkey);
		$startkey_found = false;
		$bibs_printed = 0;
		$print_navbar = ($this->mFilter == "" and $wgHowManyItemsPerPage > 0);
		$keycounter = array();

		if ($this->mBibfile->open() == false)
			$wgOut->addHTML(wfMsg("bibwiki_error_opening"));

		#make a linkbatch for the bibitems
		$linkbatch = new LinkBatch();
		foreach ($this->mBibfile->getKeys() as $key)
			#$linkbatch->add(NS_BIB, $key);
			$linkbatch->add(NS_MAIN, $key);
		$wikikeys = $linkbatch->execute();

		$bibitem = new Bibitem();

		/*$print_double_key_warning = false;
		if (count($this->mBibfile->getDoubleKeys()) > 0) {
			$print_double_key_warning = true;
			$double_key = array_pop($this->mBibfile->getDoubleKeys());
		}*/

		$compactPrinter = new BibitemCompactPrinter($this->mBibfileQuery,
			$this->mFilterQuery, $this->mBibfile->getMacros());

		$output = "";    # gather output here

		while (($sz = $this->mBibfile->nextFilteredRecord()) !== false) {

			# To identify a bibitem in a file, not only the
			# key is used but also the number of that key. This
			# is necessary in case of double keys, which should
			# not happen in a file, but Bibwiki should also
			# be able to handle such cases.
			# So $keycounter counts the occurrences of each key.
			# This information is passed to other functions such
			# as edit via the nr= CGI argument.

			$bibitem->set($sz);
			$key = $bibitem->getCiteKey();
			$lwrkey = strtolower($bibitem->getCiteKey());
			if ($key == false) {
				$this->errorBox(wfMsg("bibwiki_error_parse"));
				return false;
			}
			$keycounter[$key]++;

			# check if we can print this bibitem

			$print_this_bibitem = false;
			if ($print_navbar == false)
				# there's no navigationbar so print out everything
				$print_this_bibitem = true;
			elseif ($startkey != "") {
				# we are looking for a specific key
				if ($startkey_found == true or bwStrEqual($startkey, $lwrkey)) {
					# we have already found or successfully passed the key
					if ($maxbib > 0 and $bibs_printed <= $maxbib)
						# we are within the limits
						$print_this_bibitem = true;
					elseif ($maxbib == 0)
						# no limit at all
						$print_this_bibitem = true;
					elseif ($bibs_printed >= $maxbib)
						# we are outside the limits
						break;

					if (bwStrEqual($startkey, $lwrkey)) {
						$startkey_found = true;
						$startbib = $this->mBibfile->getPosition();
					}
				}
			}
			elseif ($maxbib > 0) {
				# there a limit of items per page
				if ($this->mBibfile->getPosition() >= $startbib and $bibs_printed < $maxbib)
					# we are within the limit
					$print_this_bibitem = true;
				elseif ($bibs_printed >= $maxbib)
					break;
			}

			if ($print_this_bibitem == true)
				$bibs_printed++;
			else
				continue;

			# we're gonna printing this, so parse it
			$bibitem->setMacros($this->mBibfile->getMacros());
			if ($bibitem->parse() == false) {
				$this->errorBox(wfMsg("bibwiki_error_parse"));
				return false;
			}

			if ($bibs_printed > 1) {
				$output .= "<div style='border-bottom:1px dotted #aaa; width:50px; margin-top:-10px; margin-bottom:5px;'>&nbsp;</div>";
			}

			$compactHeader = $this->makeCompactHeader($bibitem, $startbib, $keycounter, $wikikeys);
			$prettyPrinting = $compactPrinter->prettyPrint($bibitem);
			$output .= "<div>".$compactHeader."<br/>".$prettyPrinting."</div>";

		}

		#
		#  printing the output
		#
		#  1st: header stuff
		#
		$this->printActionBox();

		if ($wgRequest->getVal("print_settings_saved") == "1") {
			$wgOut->addHTML("<p style='border: 2px solid darkblue; padding: 10px 20px; background-color:#F3F3F3; width:400pt'>".wfMsg("bibwiki_settings_saved")."</p>");
		}

		if ($wgUser->getName() == "BibwikiAdmin" and
			$wgUser->mPassword == $wgUser->encryptPassword("secret")) {
			$wgOut->errorBox("You are still using the default password! ".
				"For security reasons go to your ".
				$wgUser->getSkin()->makeKnownLink(
					$wgContLang->specialPage( "Preferences" ), 'preferences'
				).
				" and change it there.");
		}

		if ($bibs_printed == 0)
			$output .= wfMsg("bibwiki_notfound");

		# $wgOut->addHTML('<div name="bibContent" id="bibContent">');
		#$wgOut->addHTML('<pre id="bibContent">');

		#
		#   2nd: navigation bar
		#

		if ($print_navbar)
			if ($this->printNavbar($startbib, $maxbib))
				$wgOut->addHTML("<br/><br/>");

		$print_double_key_warning = false;
		if (count($this->mBibfile->getDoubleKeys()) > 0) {
			$dk = $this->mBibfile->getDoubleKeys();
			$msg = "";
			while (count($dk) > 0) {
				$key = array_pop($dk);
				$msg .= "<br/>&bull; " . $key;
				if (strpos($this->mFilter, $key) !== 0) {
				 	$msg .= " (<a href='".$this->getLocalURL(array('keyword='.$key, $this->mBibfileQuery))."'>".wfMsg("bibwiki_filter_by_key")."</a>)\n";
				}
			}
			$msg .= "";
			$this->errorBox(wfMsg("bibwiki_double_key_warning").": ".$msg, wfMsg("bibwiki_warning"));
			#$print_double_key_warning = true;
			$double_key = array_pop($this->mBibfile->getDoubleKeys());
		}

		#
		#  3rd: the bibliography
		#

		$wgOut->addHTML($output);
		$wgOut->addHTML("<br/>");

		#
		#  4th: bottom navigation bar
		#

		$stop_timer = microtime(true);
		$wgOut->addHTML(sprintf("<div class='bibeditsection' style='float:right'>%.1f secs</div>", $stop_timer - $start_timer));

		if ($print_navbar)
			if ($this->printNavbar($startbib, $maxbib))

		#
		#  5th: footer stuff (javascripted error boxes etc)
		#

		#$wgOut->addHTML("</pre>");
		/*$wgOut->addHTML('<span class="bibeditsection">');
		$wgOut->addHTML(wfMsg('bibwiki_all_entries').': '.$count["All"].'<br>');
		foreach($count as $k => $v) {
			if ($k != "All") $wgOut->addHTML($k.": ".$v."<br>");
		}
		$wgOut->addHTML('</span>');*/
		#$wgOut->addHTML("</div>");


		/*if ($print_double_key_warning and $wgRequest->getVal("nowarn") !== "1") {
			$wgOut->addHTML('<script language="Javascript">');
			$wgOut->addHTML('alert("'.$double_key.': '.wfMsg("bibwiki_doublekey_error").'");');
			$wgOut->addHTML('window.location.href = "'.$_SERVER["SCRIPT_NAME"].'/'.$double_key.'?'.$_SERVER["QUERY_STRING"].'&f='.$this->mBibfilename.'&view='.$wgRequest->getVal("view").'&nowarn=1&doublekey='.$double_key.'";');
			$wgOut->addHTML('window.location.href = "'.$this->getFullURL(array("keyword=".$double_key, 'view='.$wgRequest->getVal("view"), $this->mBibfileQuery, 'nowarn=1', 'doublekey='.$double_key)).'";');
			$wgOut->addHTML('</script>');
		}*/
		if ($wgRequest->getVal("renamewarn") == "1") {
			$wgOut->addHTML('<script language="Javascript">');
			$wgOut->addHTML('alert("'.wfMsg('bibwiki_rename_error').'");');
			$wgOut->addHTML('</script>');
		}

		#
		#  6th: Closing the Bibfile
		#

		$this->mBibfile->close();

	}
}

function fnBibwikiAddTabs(&$content_actions) {
	global $wgBibPath, $wgRequest, $wgDefaultBib,
		$wgUser, $wgContLang, $wgEnableExport, $wgAllowEditSettingsFromIPs,
		$wgOut, $wgUser;

	#Load Settings
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

	$keywordquery = "";
	$bibquery = "";

	if ($wgRequest->getVal("keyword") != "")
		$keywordquery = "keyword=".$wgRequest->getVal("keyword");

	$bibfile = Bibliography::getBibfilename();
	$bibquery = "f=".$bibfile;

	$view = $wgRequest->getVal("view");
	$action = $wgRequest->getVal("action");
	if ($view == "")
		$view = $_COOKIE["BIBWIKI_VIEW"];
	if ($view == "")
		$view = "compact";
	if ($action != "view" and $action != "")
		$view = "";

	$content_actions = array();
	$content_actions['bibliography'] = array(
		'class' => ($view == "compact")? "selected" : false,
		'text' => wfMsg("bibwiki_bibliography"),
		'href' => Bibliography::getLocalURL(array("view=compact", "startkey=".Bibliography::getStartkey(), "start=".$wgRequest->getVal('start'), $bibquery, $keywordquery))
	);
	$content_actions['view_details'] = array(
		'class' => ($view == "detailed")? "selected" : false,
		'text' => wfMsg("bibwiki_detailedview"),
		'href' => Bibliography::getLocalURL(array("view=detailed", "startkey=".Bibliography::getStartkey(), "start=".$wgRequest->getVal('start'), $bibquery, $keywordquery))
	);
	$content_actions['view_authors'] = array(
		'class' => ($wgRequest->getVal("action") == "viewauthors")? "selected" : false,
		'text' => wfMsg("bibwiki_view_authors"),
		'href' => Bibliography::getLocalURL(array("action=viewauthors", $bibquery, $keywordquery))
	);
	$content_actions['view_keywords'] = array(
		'class' => ($wgRequest->getVal("action") == "viewkeywords")? "selected" : false,
		'text' => wfMsg("bibwiki_view_keywords"),
		'href' => Bibliography::getLocalURL(array("action=viewkeywords", $bibquery, $keywordquery))
	);
	return $content_actions;
}

function wfBibliographyToolbox( &$monobook ) {
	global $wgBibPath, $wgRequest, $wgDefaultBib,
		$wgUser, $wgContLang, $wgEnableExport, $wgAllowEditSettingsFromIPs,
		$wgOut, $wgUser;

	#Load Settings
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

    print('</ul></div></div>');

	$keywordquery = "";
	if ($wgRequest->getVal("keyword") != "")
		$keywordquery = "keyword=".$wgRequest->getVal("keyword");

	$bibfile = Bibliography::getBibfilename();
	$bibquery = "f=".$bibfile;

	# Check if there are more than one bib-files in $wgBibPath

	$bibcount = 0;
	$biblist = '<ul>';
	$publicbibs = bwGetPublicBibfiles();
	foreach($publicbibs as $f) {
		$bibcount += 1;
	    $biblist .= '<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'f='.$f, $keywordquery)).'">'.$f.'</a></li>';
	}
	$biblist .= "</ul>";

	if ($bibcount > 0) {
		print('<div class="portlet" id="p-bib">');
		print('<h5>'.wfMsg("bibwiki_bibliographies").'</h5>');
		print('<div class="pBody">');
		print($biblist);
		print('</div></div>');
	}

	$bibcount = 0;
	$biblist = '<ul>';
	$privatebibs = bwGetPrivateBibfiles();
	foreach($privatebibs as $f) {
		$bibcount += 1;
	    $biblist .= '<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'f='.$wgUser->getName().DIRECTORY_SEPARATOR.$f, $keywordquery)).'">'.$f.'</a></li>';
	}
	$biblist .= "</ul>";

	if ($bibcount > 0) {
		print('<div class="portlet" id="p-bib">');
		print('<h5>'.wfMsg("bibwiki_my_bibliographies").'</h5>');
		print('<div class="pBody">');
		print($biblist);
		print('</div></div>');
	}

	if (Bibliography::userIsAllowedToEdit()) {
		print('<div class="portlet" id="p-bib">');
		print('<h5>'.wfMsg("bibwiki_new_entry").'</h5>');
		print('<div class="pBody">');
		print('<ul>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=new', 'type=Book', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_book').'</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=new', 'type=Collection', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_collection').'</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=new', 'type=Incollection', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_incollection').'</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=new', 'type=Article', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_article').'</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=new', 'type=Misc', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_misc').'</a></li>');
		print('</ul></div></div>');

		print('<div class="portlet" id="p-bib">');
		print('<h5>'.wfMsg("bibwiki_import_entry").'</h5>');
		print('<div class="pBody">');
		print('<ul>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=import', 'source=Opac', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_opac').'</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=import', 'source=DDB', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_ddb').'</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=import', 'source=SA', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_sa').'</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=import', 'source=Amazon', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_amazon').'</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=import', 'source=arxiv', $bibquery, $keywordquery)).'">ArXiv</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=import', 'source=loc', $bibquery, $keywordquery)).'">LoC</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), 'action=import', 'view='.$wgRequest->getVal("view"), 'source=URL', $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_url').'</a></li>');
		print('</ul></div></div>');

		print('<div class="portlet" id="p-bib">');
		print('<h5>'.wfMsg("bibwiki_bib_toolbox").'</h5>');
		print('<div class="pBody">');
		print('<ul>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), "action=viewsource", $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_view_source').'</a></li>');
		print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), "action=viewstats", $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_view_stats').'</a></li>');
		if ($wgEnableExport) {
			print('<li><a target="export" href="'.Bibliography::getLocalURL(array("action=export", $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_export').'</a></li>');
			print('<li><a href="'.Bibliography::getLocalURL(array("action=export_from_doc", $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_export_from_doc').'</a></li>');
		}
		if (bwUserIsSysop() and
			(strstr($wgAllowEditSettingsFromIPs, $_SERVER["REMOTE_ADDR"]) !== FALSE or
			(empty($wgAllowEditSettingsFromIPs) and $_SERVER["REMOTE_ADDR"] == "127.0.0.1")))
		{
			print('<li><a href="'.Bibliography::getLocalURL(array('view='.$wgRequest->getVal("view"), "action=load_settings", $bibquery, $keywordquery)).'">'.wfMsg('bibwiki_edit_settings').'</a></li>');
		}
	}
    return true;
}

function wfBeforePageDisplay(&$out) {
	global $wgRequest, $wgBibPath, $wgStylePath;

	#Load Settings
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

	$bibfile = Bibliography::getBibfilename();
	$bibquery = "f=".$bibfile;

	$keywordquery = "";
	if ($wgRequest->getVal("keyword") != "")
		$keywordquery = "keyword=".$wgRequest->getVal("keyword");

	$out->addScript('
<script language="javascript">
<!--
function rename(oldname,key,nr) {
	newname = prompt("'.wfMsg("bibwiki_new_name").'", oldname);
	if (newname != null) {
'.sprintf('		window.location.href = "%s&newname=" + newname + "&oldname=" + oldname + "&startkey=" + key;',Bibliography::getFullURL(array("action=rename", "view=".$wgRequest->getVal("view"), $bibquery, $keywordquery))).'
	}
}
-->
</script>
<style type="text/css">
<!--
#bodyContent {
	width:100%;
}
.renamesection {
	color: #999999;
	text-decoration: none;
	background: none;
}
.renamesection:visited {
	color: #5a3696;
}
.renamesection:active {
	color: #faa700;
}
.renamesection:hover{
	text-decoration:underline;
	cursor:pointer;
}
.bibeditsection a {
	color: #999999;
	/*border: 1px dotted #999999;*/
	/*padding: 0px 3px;*/
}
.bibeditsection_neutral a {
	font-family: Arial, sans-serif;
	font-size:8pt;
}
.bibtaginput
{
	border: 1px solid #999999;
	padding: 1px 4px;
}
.link-pdf {
	background: url('.$wgStylePath.'/monobook/file_icon.gif) center right no-repeat;
	overflow:visible;
	/*width:20px;*/
	padding-left: 0px;
	padding-right: 14px;
}
a.link-pdf:hover {
	text-decoration:none;
}
#bibcommand
{
	/*border: 1px solid #999999;*/
	/*display:none;*/
	/*font-size:90%;*/
	margin-bottom:1em;
}
.bibeditsection
{
	color: #999999;
	font-family: Arial, sans-serif;
	font-size:8pt;
}
#bibContent {
	line-height:1.3em;
	width: 100%;
	overflow: visible;
	border: none;
	background-color:white;
	padding: 0;
	margin: 0;
}
.highlight {
    background-color: yellow;
}
.blockquote {
	border-left: 12px solid #BBBBBB;
	margin:5px 0 5px -25px;
	padding:0;
	padding-left: 15px;
}
-->
</style>');
	return true;
}

function wfRenderBibliographyTitle(&$skin) {
	global $wgRequest, $wgDefaultBib, $wgUser, $wgContLang,
		$wgBibPath;

	#Load Settings
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
	if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

	$actionquery = "";
	If ($wgRequest->Getval("Action") != "")
		$actionquery = "Action=".$wgRequest->getVal("Action");
	$bibquery = "";

	$bibfile = Bibliography::getBibfilename();
	$bibquery = "f=".$bibfile;

	/*print '<form method="get" action="'.Title::makeTitle(NS_SPECIAL, "Bibliography")->getLocalURL().'">';
	print '<input type="hidden" value="'.$bibfile.'" name="f" />';
	print '<input type="hidden" value="'.$wgRequest->getVal("action").'" name="action" />';
	if ($this->mBibfilename != "" and $this->mBibfilename != $wgDefaultBib) {
		print '<input type="hidden" value="'.$bibfile.'" name="f" />';
	}*/

	print Bibliography::makeKnownLink($bibfile, array($actionquery, $bibquery));

	/*print '/<input class="bibtaginput" type="text" name="keyword" value="'.$wgRequest->getVal("keyword").'">';
	print '</form>';*/
	return true;
}
