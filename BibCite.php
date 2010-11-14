<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
/**#@+
 * A parser extension that adds two tags, <bibref> and <bibreferences> for adding
 * citations to pages
 *
 * @addtogroup Extensions
 *
 * @link http://meta.wikimedia.org/wiki/Cite/Cite.php Documentation
 * @link http://www.w3.org/TR/html4/struct/text.html#edef-CITE <cite> definition in HTML
 * @link http://www.w3.org/TR/2005/WD-xhtml2-20050527/mod-text.html#edef_text_cite <cite> definition in XHTML 2.0
 *
 * @bug 4579
 *
 * @author Wolfgang Plaschg <wpl@gmx.net>
 * @author Ævar Arnfjörð Bjarmason <avarab@gmail.com>
 * @copyright Copyright © 2005, Ævar Arnfjörð Bjarmason
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionFunctions[] = 'wfBibCite';
$wgExtensionCredits['parserhook'][] = array(
	'name' => 'BibCite',
	'author' => 'Ævar Arnfjörð Bjarmason / Wolfgang Plaschg',
	'description' => 'Adds <nowiki><bibref[ f=file]></nowiki> and <nowiki><bibreferences/></nowiki> tags, for citations',
	'url' => 'http://www.plaschg.net/bibwiki'
);
#$wgParserTestFiles[] = dirname( __FILE__ ) . "/citeParserTests.txt";

/**
 * Error codes, first array = internal errors; second array = user errors
 */
$wgBibCiteErrors = array(
	'system' => array(
		'BIBCITE_ERROR_STR_INVALID',
		'BIBCITE_ERROR_KEY_INVALID_1',
		'BIBCITE_ERROR_KEY_INVALID_2',
		'BIBCITE_ERROR_STACK_INVALID_INPUT'
	),
	'user' => array(
		'BIBCITE_ERROR_REF_NUMERIC_KEY',
		'BIBCITE_ERROR_REF_NO_KEY',
		'BIBCITE_ERROR_REF_TOO_MANY_KEYS',
		'BIBCITE_ERROR_REF_NO_INPUT',
		'BIBCITE_ERROR_REFERENCES_INVALID_INPUT',
		'BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS',
		'BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL',
		'BIBCITE_ERROR_REFERENCES_NO_TEXT',
		'BIBCITE_ERROR_REFERENCE_NOT_FOUND',
	)
);

for ( $i = 0; $i < count( $wgBibCiteErrors['system'] ); ++$i )
	// System errors are negative integers
	define( $wgBibCiteErrors['system'][$i], -($i + 1) );
for ( $i = 0; $i < count( $wgBibCiteErrors['user'] ); ++$i )
	// User errors are positive integers
	define( $wgBibCiteErrors['user'][$i], $i + 1 );

# Internationalisation file
require_once( dirname(__FILE__) . '/BibCite.i18n.php' );

include_once( dirname(__FILE__) . '/Bibfile.php' );
include_once( dirname(__FILE__) . '/Bibitem.php' );


function wfBibCite() {
	# Add messages
	global $wgMessageCache, $wgBibCiteMessages;
	foreach( $wgBibCiteMessages as $key => $value ) {
		$wgMessageCache->addMessages( $wgBibCiteMessages[$key], $key );
	}
	
	class BibCite {
		/**#@+
		 * @access private
		 */
		
		/**
		 * Datastructure representing <ref> input, in the format of:
		 * <code>
		 * array(
		 * 	'user supplied' => array(
		 *		'text' => 'user supplied reference & key',
		 *		'count' => 1, // occurs twice
		 * 		'number' => 1, // The first reference, we want
		 * 		               // all occourances of it to
		 * 		               // use the same number
		 *	),
		 *	0 => 'Anonymous reference',
		 *	1 => 'Another anonymous reference',
		 *	'some key' => array(
		 *		'text' => 'this one occurs once'
		 *		'count' => 0,
		 * 		'number' => 4
		 *	),
		 *	3 => 'more stuff'
		 * );
		 * </code>
		 *
		 * This works because:
		 * * PHP's datastructures are guarenteed to be returned in the
		 *   order that things are inserted into them (unless you mess
		 *   with that)
		 * * User supplied keys can't be integers, therefore avoiding
		 *   conflict with anonymous keys
		 *
		 * @var array
		 **/
		var $mRefs = array();
		var $mBibTeXRefs = array();
		var $mBibTeXFiles = array();
		var $mBibTeXMacros = array();
		var $mRefStyle = null;
		var $mRefBibfile = null;
		
		/**
		 * Count for user displayed output (ref[1], ref[2], ...)
		 *
		 * @var int
		 */
		var $mOutCnt = 0;

		/**
		 * Internal counter for anonymous references, seperate from
		 * $mOutCnt because anonymous references won't increment it,
		 * but will incremement $mOutCnt
		 *
		 * @var int
		 */
		var $mInCnt = 0;

		/**
		 * The backlinks, in order, to pass as $3 to
		 * 'bibcite_references_link_many_format', defined in
		 * 'bibcite_references_link_many_format_backlink_labels
		 *
		 * @var array
		 */
		var $mBacklinkLabels;
		
		/**
		 * @var object
		 */
		var $mParser;
		
		/**
		 * True when a <ref> or <references> tag is being processed.
		 * Used to avoid infinite recursion
		 * 
		 * @var boolean
		 */
		var $mInCite = false;
		
		/**#@-*/

		/**
		 * Constructor
		 */
		function BibCite() {
			$this->setHooks();
		}

		/**#@+ @access private */

		/**
		 * Callback function for <ref>
		 *
		 * @param string $str Input
		 * @param array $argv Arguments
		 * @return string
		 */
		function ref( $str, $argv, $parser ) {
			if ( $this->mInCite ) {
				return htmlspecialchars( "<ref>$str</ref>" );
			} else {
				$this->mInCite = true;
				$ret = $this->guardedRef( $str, $argv, $parser );
				$this->mInCite = false;
				return $ret;
			}
		}
		
		function guardedRef( $str, $argv, $parser ) {
			$this->mParser = $parser;
			$key = $this->refArg( $argv );
			$filename = $this->refFilename( $argv );
			
			if ($filename !== null)
				$this->mBibTeXFiles[$filename] = 1;
			
			if ($key === null) $key = trim(strtolower($str));
			if ($key === null or $key=='') 
				$this->croak( BIBCITE_ERROR_KEY_INVALID_1, serialize( $key ) );

			if ($filename !== null)
				$key = $filename."/".$key;
			
			if ( $key !== null ) {
				return $this->stack( $key );
			} else
				$this->croak( BIBCITE_ERROR_STR_INVALID, serialize( $str ) );
		}

		/**
		 * Parse the arguments to the <ref> tag
		 *
		 * @static
		 *
		 * @param array $argv The argument vector
		 * @return mixed false on invalid input, a string on valid
		 *               input and null on no input
		 */
		function refArg( $argv ) {
			if ( isset( $argv['name'] ) )
				return $this->validateName( trim(strtolower($argv['name'])) );
			else
				return null;
		}

		function refFilename( $argv ) {
			if ( isset( $argv['f'] ) )
				return $argv['f'];
			else
				return null;
		}
		
		function refStyle( $argv ) {
			if ( isset( $argv['style'] ) and $argv['style'] != "")
				return $argv['style'];
			else
				return null;
		}

		/**
		 * Since the key name is used in an XHTML id attribute, it must
		 * conform to the validity rules. The restriction to begin with
		 * a letter is lifted since references have their own prefix.
		 *
		 * @fixme merge this code with the various section name transformations
		 * @fixme double-check for complete validity
		 * @return string if valid, false if invalid
		 */
		function validateName( $name ) {
			if( preg_match( '/^[A-Za-z0-9:_.-]*$/i', $name ) ) {
				return $name;
			} else {
				// WARNING: CRAPPY CUT AND PASTE MAKES BABY JESUS CRY
				$text = urlencode( str_replace( ' ', '_', $name ) );
				$replacearray = array(
					'%3A' => ':',
					'%' => '.'
				);
				return str_replace(
					array_keys( $replacearray ),
					array_values( $replacearray ),
					$text );
			}
		}

		/**
		 * Populate $this->mRefs based on input and arguments to <ref>
		 *
		 * @param string $str Input from the <ref> tag
		 * @param mixed $key Argument to the <ref> tag as returned by $this->refArg()
		 * @return string 
		 */
		function stack( $key ) {
			
			if ( is_string( $key ) ) {
				// Valid key
			
				if ( ! isset( $this->mRefs[$key] ) || ! is_array( $this->mRefs[$key] ) ) {
					// First occourance
					$this->mRefs[$key] = array(
						'text' => $key,
						'count' => 0,
						'number' => ++$this->mOutCnt
					);
					return
						$this->linkRef(
							$key,
							$this->mRefs[$key]['count'],
							$this->mRefs[$key]['number']
						);
				} else {
					// We've been here before
					if ( $this->mRefs[$key]['text'] === null && $str !== '' ) {
						// If no text found before, use this text
						$this->mRefs[$key]['text'] = $str;
					};
					return 
						$this->linkRef(
							$key,
							++$this->mRefs[$key]['count'],
							$this->mRefs[$key]['number']
						); }
			}
			else
				$this->croak( BIBCITE_ERROR_STACK_INVALID_INPUT, serialize( array( $key ) ) );
		}
		
		/**
		 * Callback function for <references>
		 *
		 * @param string $str Input
		 * @param array $argv Arguments
		 * @return string
		 */
		function references( $str, $argv, $parser ) {
			if ( $this->mInCite ) {
				if ( is_null( $str ) ) {
					return htmlspecialchars( "<references/>" );
				} else {
					return htmlspecialchars( "<references>$str</references>" );
				}
			} else {
				$this->mInCite = true;
				$ret = $this->guardedReferences( $str, $argv, $parser );
				$this->mInCite = false;
				return $ret;
			}
		}
		
		function guardedReferences( $str, $argv, $parser ) {
			$this->mParser = $parser;
			$this->mRefStyle = $this->refStyle($argv);
			$this->mRefBibfile = $this->refFilename($argv);
			if ( $str !== null )
				return $this->error( BIBCITE_ERROR_REFERENCES_INVALID_INPUT );
			else
				return $this->referencesFormat();
		}

		/**
		 * Make output to be returned from the references() function
		 *
		 * @return string XHTML ready for output
		 */
		function referencesFormat() {
			if ( count( $this->mRefs ) == 0 )
				return '';
			
			$ent = array();
			$ent[] = $this->prepareBibTeXRecords();
			
			foreach ( $this->mRefs as $k => $v )
				$ent[] = $this->referencesFormatEntry( $k, $v );
			
			$prefix = wfMsgForContentNoTrans( 'bibcite_references_prefix' );
			$suffix = wfMsgForContentNoTrans( 'bibcite_references_suffix' );
			$content = implode( "\n", $ent );
			
			// Live hack: parse() adds two newlines on WM, can't reproduce it locally -ævar
			return rtrim( $this->parse( $prefix . $content . $suffix ), "\n" );
		}

		/**
		 * Format a single entry for the referencesFormat() function
		 *
		 * @param string $key The key of the reference
		 * @param mixed $val The value of the reference, string for anonymous
		 *                   references, array for user-suppplied
		 * @return string Wikitext
		 */
		function referencesFormatEntry( $key, $val ) {
			// Anonymous reference
			if ( ! is_array( $val ) )
				return
					wfMsgForContentNoTrans(
						'bibcite_references_link_one',
						$this->referencesKey( $key ),
						$this->refKey( $key ),
						$this->referencesFormatEntryText($val),
						$this->linkToBibliography($key)
					);
			else if ($val['text']=='') return
					wfMsgForContentNoTrans(
						'bibcite_references_link_one',
						$this->referencesKey( $key ),
						$this->refKey( $key, $val['count'] ),
						$this->error(BIBCITE_ERROR_REFERENCES_NO_TEXT),
						$this->linkToBibliography($key)
					);
			// Standalone named reference, I want to format this like an
			// anonymous reference because displaying "1. 1.1 Ref text" is
			// overkill and users frequently use named references when they
			// don't need them for convenience
			else if ( $val['count'] === 0 )
				return
					wfMsgForContentNoTrans(
						'bibcite_references_link_one',
						$this->referencesKey( $key ),
						$this->refKey( $key, $val['count'] ),
						( $val['text'] != '' ? $this->referencesFormatEntryText($val['text']) : $this->error( BIBCITE_ERROR_REFERENCES_NO_TEXT ) ),
						$this->linkToBibliography($key)
					);
			// Named references with >1 occurrences
			else {
				$links = array();

				for ( $i = 0; $i <= $val['count']; ++$i ) {
					$links[] = wfMsgForContentNoTrans(
							'bibcite_references_link_many_format',
							$this->refKey( $key, $i ),
							$this->referencesFormatEntryNumericBacklinkLabel( $val['number'], $i, $val['count'] ),
							$this->referencesFormatEntryAlternateBacklinkLabel( $i )
					);
				}

				$list = $this->listToText( $links );

				return
					wfMsgForContentNoTrans( 'bibcite_references_link_many',
						$this->referencesKey( $key ),
						$list,
						( $val['text'] != '' ? $this->referencesFormatEntryText($val['text']) : $this->error( BIBCITE_ERROR_REFERENCES_NO_TEXT ) ),
						$this->linkToBibliography($key)
					);
			}
		}
		
		function linkToBibliography($key) {
			global $wgDefaultBib;
		
			#Load Settings
			if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
		    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
			if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
		        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

			$f = $wgDefaultBib;
			if (strpos($key, "/") === false) {
				if ($this->mRefBibfile !== null)
					$f = $this->mRefBibfile;
			}
			else {
				$parts = explode("/", $key, 2);
				$f = $parts[0];
				$key = $parts[1];
			}
			return wfMsgForContentNoTrans( 'bibcite_references_bib_backlink',
				Title::makeTitle(NS_SPECIAL, "Bibliography")->getFullText() . "/$f/$key",
				'Bib'
			);
			/*return wfMsgForContentNoTrans( 'bibcite_references_bib_backlink_fullurl',
				Title::makeTitle(NS_SPECIAL, "Bibliography")->getFullURL("f=$f&startkey=$key"),
				'Bib'
			);*/
		}
		
		function expandCrossref($filename, $record) {
			$bibfile = new Bibfile;
			$bibitem = new Bibitem;
			$bibfile->init($filename);
			$bibitem->set($record);
			$bibitem->parse();
			$bibitem->expandCrossref($bibfile);
			return $bibitem->getSource();
		}
		
		/**
		 * Loads bibtex records for the given bibtex keys.
		 *
		 * $CiteKeys must have the form:
		 * "<filename>/<key>". Eg. <code>$CiteKeys = array("test.bib/entry1", 
		 * "test.bib/entry2");</code>
		 */
		function LoadRecords($filename, $CiteKeys) {
			$bibfile = new Bibfile();
			$bibfile->init($filename);
			$bibfile->open();
			$data = array();
			do {
				$rv = $bibfile->nextRecord();
				if ($rv === false) break;
	
				# make key for return array			
				$key = strtolower($bibfile->getName()."/".$rv["key"]);
				
				if (isset($CiteKeys[$key]) and $CiteKeys[$key] > 0) {
					#found
				  	$data[$key] = $this->expandCrossref($filename, $rv["record"]);
				  	unset($CiteKeys[$key]);
				}
			} while (count($CiteKeys) > 0);
			$this->mBibTeXMacros = array_merge($this->mBibTeXMacros, $bibfile->mMacros);
			$this->mBibTeXRefs = array_merge($this->mBibTeXRefs, $data);
			$bibfile->close();
			return true;
		}
	
		function prepareBibTeXRecords() {
			global $wgDefaultBib, $wgUser, $wgContLang;
		
			#Load Settings
			if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
		    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
			if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
		        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

			# fill CiteKeys
			
			$CiteKeys = array();
			foreach($this->mRefs as $refkey => $ref) {
				$key = $refkey;
				if (strpos($key, "/") === false) {
					// no filename given
					if ($this->mRefBibfile !== null) {
						// filename via <bibreferences/>
						$key = $this->mRefBibfile."/".$key;
						$this->mBibTeXFiles[$this->mRefBibfile] = 1;
					}
					else {
						// default filename
						$key = $wgDefaultBib."/".$key;
						$this->mBibTeXFiles[$wgDefaultBib] = 1;
					}
				}
				if ($key != "")
					$CiteKeys[$key] = 1;
				else						
					$this->error(BIBCITE_ERROR_REF_NO_KEY);
			}
			
			$debug_str = array();
			
			$this->mBibTeXRefs = array();
			$this->mBibTeXMacros = array();
			foreach($this->mBibTeXFiles as $filename => $cnt) {
				#$debug_str[] = "<p>".$filename.": ".$cnt."</p>";
				$this->loadRecords($filename, $CiteKeys);
			}
			#foreach($this->mBibTeXMacros as $key => $val) {
			#	$debug_str[] = "<p>".$key.": ".$val."</p>";
			#}
			$bibitem = new Bibitem();
			$bibitem->setMacros($this->mBibTeXMacros);
			foreach($this->mBibTeXRefs as $key => $val) {
				$bibitem->set($val);
				$this->mBibTeXRefs[$key] = $bibitem->formatWithOSBiB(($this->mRefStyle !== null)? $this->mRefStyle : $wgDefaultReferencesStyle);
				$keyparts = explode("/", $key, 2);
				# $this->mBibTeXRefs[$key] .= " ".$wgUser->getSkin()->makeKnownLink($wgContLang->specialPage("Bibliography"), "Bib", bwImplodeQuery(array("f=".$keyparts[0], "startkey=".$keyparts[1])));
				# $debug_str[] = "<p>".$key.": ".$val."</p>";
			}
			return implode("\n", $debug_str);
		}
		
		function referencesFormatEntryText( $key ) {
			global $wgDefaultBib, $wgUser, $wgContLang;
		
			#Load Settings
			if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.Default.php'))
		    	include( dirname( __FILE__ ) . '/BibwikiSettings.Default.php' );
			if (file_exists(dirname( __FILE__ ) . '/BibwikiSettings.php'))
		        include( dirname( __FILE__ ) . '/BibwikiSettings.php' );

			if (strpos($key, "/") === false) {
				// no filename given
				if ($this->mRefBibfile !== null) {
					// filename via <bibreferences/>
					$key = $this->mRefBibfile."/".$key;
				}
				else {
					// default filename
					$key = $wgDefaultBib."/".$key;
				}
			}
			
			if (isset($this->mBibTeXRefs[$key]))
				return $this->mBibTeXRefs[$key];
			else
				return "<strong>".$key."</strong>: ".$this->error( BIBCITE_ERROR_REFERENCE_NOT_FOUND );
		}
		

		/**
		 * Generate a numeric backlink given a base number and an
		 * offset, e.g. $base = 1, $offset = 2; = 1.2
		 * Since bug #5525, it correctly does 1.9 -> 1.10 as well as 1.099 -> 1.100
		 *
		 * @static
		 *
		 * @param int $base The base
		 * @param int $offset The offset
		 * @param int $max Maximum value expected.
		 * @return string
		 */
		function referencesFormatEntryNumericBacklinkLabel( $base, $offset, $max ) {
			global $wgContLang;
			$scope = strlen( $max );
			$ret = $wgContLang->formatNum(
				sprintf("%s.%0{$scope}s", $base, $offset)
			);
			return $ret;
		}

		/**
		 * Generate a custom format backlink given an offset, e.g.
		 * $offset = 2; = c if $this->mBacklinkLabels = array( 'a',
		 * 'b', 'c', ...). Return an error if the offset > the # of
		 * array items
		 *
		 * @param int $offset The offset
		 *
		 * @return string
		 */
		function referencesFormatEntryAlternateBacklinkLabel( $offset ) {
			if ( !isset( $this->mBacklinkLabels ) ) {
				$this->genBacklinkLabels();
			}
			if ( isset( $this->mBacklinkLabels[$offset] ) ) {
				return $this->mBacklinkLabels[$offset];
			} else {
				// Feed me!
				return $this->error( BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL );
			}
		}

		/**
		 * Return an id for use in wikitext output based on a key and
		 * optionally the # of it, used in <references>, not <ref>
		 * (since otherwise it would link to itself)
		 *
		 * @static
		 *
		 * @param string $key The key
		 * @param int $num The number of the key
		 * @return string A key for use in wikitext
		 */
		function refKey( $key, $num = null ) {
			$prefix = wfMsgForContent( 'bibcite_reference_link_prefix' );
			$suffix = wfMsgForContent( 'bibcite_reference_link_suffix' );
			if ( isset( $num ) )
				$key = wfMsgForContentNoTrans( 'bibcite_reference_link_key_with_num', $key, $num );
			
			return $prefix . $key . $suffix;
		}

		/**
		 * Return an id for use in wikitext output based on a key and
		 * optionally the # of it, used in <ref>, not <references>
		 * (since otherwise it would link to itself)
		 *
		 * @static
		 *
		 * @param string $key The key
		 * @param int $num The number of the key
		 * @return string A key for use in wikitext
		 */
		function referencesKey( $key, $num = null ) {
			$prefix = wfMsgForContent( 'bibcite_references_link_prefix' );
			$suffix = wfMsgForContent( 'bibcite_references_link_suffix' );
			if ( isset( $num ) )
				$key = wfMsgForContentNoTrans( 'bibcite_reference_link_key_with_num', $key, $num );
			
			return $prefix . $key . $suffix;
		}

		/**
		 * Generate a link (<sup ...) for the <ref> element from a key
		 * and return XHTML ready for output
		 *
		 * @param string $key The key for the link
		 * @param int $count The # of the key, used for distinguishing
		 *                   multiple occourances of the same key
		 * @param int $label The label to use for the link, I want to
		 *                   use the same label for all occourances of
		 *                   the same named reference.
		 * @return string
		 */
		function linkRef( $key, $count = null, $label = null ) {
			global $wgContLang;

			return
				$this->parse(
					wfMsgForContentNoTrans(
						'bibcite_reference_link',
						$this->refKey( $key, $count ),
						$this->referencesKey( $key ),
						$wgContLang->formatNum( is_null( $label ) ? ++$this->mOutCnt : $label )
					)
				);
		}

		/**
		 * This does approximately the same thing as
		 * Langauge::listToText() but due to this being used for a
		 * slightly different purpose (people might not want , as the
		 * first seperator and not 'and' as the second, and this has to
		 * use messages from the content language) I'm rolling my own.
		 *
		 * @static
		 *
		 * @param array $arr The array to format
		 * @return string
		 */
		function listToText( $arr ) {
			$cnt = count( $arr );

			$sep = wfMsgForContentNoTrans( 'bibcite_references_link_many_sep' );
			$and = wfMsgForContentNoTrans( 'bibcite_references_link_many_and' );

			if ( $cnt == 1 )
				// Enforce always returning a string
				return (string)$arr[0];
			else {
				$t = array_slice( $arr, 0, $cnt - 1 );
				return implode( $sep, $t ) . $and . $arr[$cnt - 1];
			}
		}

		/**
		 * Parse a given fragment and fix up Tidy's trail of blood on
		 * it...
		 *
		 * @param string $in The text to parse
		 * @return string The parsed text
		 */
		function parse( $in ) {
			if ( method_exists( $this->mParser, 'recursiveTagParse' ) ) {
				// New fast method
				return $this->mParser->recursiveTagParse( $in );
			} else {
				// Old method
				$ret = $this->mParser->parse(
					$in,
					$this->mParser->mTitle,
					$this->mParser->mOptions,
					// Avoid whitespace buildup
					false,
					// Important, otherwise $this->clearState()
					// would get run every time <ref> or
					// <references> is called, fucking the whole
					// thing up.
					false
				);
				$text = $ret->getText();
				
				return $this->fixTidy( $text );
			}
		}

		/**
		 * Tidy treats all input as a block, it will e.g. wrap most
		 * input in <p> if it isn't already, fix that and return the fixed text
		 *
		 * @static
		 *
		 * @param string $text The text to fix
		 * @return string The fixed text
		 */
		function fixTidy( $text ) {
			global $wgUseTidy;

			if ( ! $wgUseTidy )
				return $text;
			else {
				$text = preg_replace( '~^<p>\s*~', '', $text );
				$text = preg_replace( '~\s*</p>\s*~', '', $text );
				$text = preg_replace( '~\n$~', '', $text );
				
				return $text;
			}
		}

		/**
		 * Generate the labels to pass to the
		 * 'bibcite_references_link_many_format' message, the format is an
		 * arbitary number of tokens seperated by [\t\n ]
		 */
		function genBacklinkLabels() {
			wfProfileIn( __METHOD__ );
			$text = wfMsgForContentNoTrans( 'bibcite_references_link_many_format_backlink_labels' );
			$this->mBacklinkLabels = preg_split( '#[\n\t ]#', $text );
			wfProfileOut( __METHOD__ );
		}

		/**
		 * Gets run when Parser::clearState() gets run, since we don't
		 * want the counts to transcend pages and other instances
		 */
		function clearState() {
			$this->mOutCnt = $this->mInCnt = 0;
			$this->mRefs = array();

			return true;
		}

		/**
		 * Initialize the parser hooks
		 */
		function setHooks() {
			global $wgParser, $wgHooks;
			
			$wgParser->setHook( 'bibref' , array( &$this, 'ref' ) );
			$wgParser->setHook( 'bibreferences' , array( &$this, 'references' ) );

			$wgHooks['ParserClearState'][] = array( &$this, 'clearState' );
		}

		/**
		 * Return an error message based on an error ID
		 *
		 * @param int $id ID for the error
		 * @return string XHTML ready for output
		 */
		function error( $id ) {
			if ( $id > 0 )
				// User errors are positive
				return 
					$this->parse(
						'<strong class="error">' .
						wfMsg( 'bibcite_error', $id, wfMsg( "BIBCITE_ERROR_$id" ) ) .
						'</strong>'
					);
			else if ( $id < 0 )
				return wfMsg( 'bibcite_error', $id );
		}

		/**
		 * Die with a backtrace if something happens in the code which
		 * shouldn't have
		 *
		 * @param int $error  ID for the error
		 * @param string $data Serialized error data
		 */
		function croak( $error, $data ) {
			wfDebugDieBacktrace( wfMsgForContent( 'bibcite_croak', $this->error( $error ), $data ) );
		}

		/**#@-*/
	}

	new BibCite;
}

/**#@-*/

