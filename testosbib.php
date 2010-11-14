<?php


include_once("OSBiB/format/BIBFORMAT.php");
$bibformat = new BIBFORMAT(FALSE, TRUE);

list($info, $citation, $footnote, $styleCommon, $styleTypes) = 
			$bibformat->loadStyle("OSBiB/styles/bibliography/", "apa");

$bibformat->getStyle($styleCommon, $styleTypes, $footnote);

$resourceArray = array(
'author' => 'Erna Appelt',
'title' => 'Sozialpartnerschaft und Fraueninteressen',
'editor' => 'Emmerich T\'alos',
'booktitle' => 'Sozialpartnerschaft',
'publisher' => 'Verlag für Gesellschaftskritik',
'address' => 'Wien',
'year' => '1993',
'pages' => '42--111',
);
			

$resourceType = 'book_article';

$bibformat->preProcess($resourceType, $resourceArray);
$string = $bibformat->map();

print $string;

?>