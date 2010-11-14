<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<!--<form action="#">
<input type="text" name="text" value="<?php print str_replace('\\\\\\', '\\', $_GET["text"]);?>">
<input type="submit">
</form>-->
<pre>
<?php
$str = file_get_contents("test.tex");
include("TexToHTMLConverter.php");
$texcon = new TeXToHTMLConverter;
print $str."<br/>";
$rv = $texcon->convert($str);
?>
</pre>
<?php
print $rv;

$str = preg_replace("/\W+/", "", $str);

print "\n".$str."\n";
?>
</body>
</html>