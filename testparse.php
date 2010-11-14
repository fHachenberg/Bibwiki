<?php

require("Bibitem.php");

print "X\n\n";

$bibitem = new Bibitem;
$bibitem->set('@Book{Strauss.etal:1996,
  author       = {Strauss, Anselm L. and Corbin, Juliet},
  title        = {{Grounded Theory: Grundlagen qualitativer Sozialforschung}},
  address      = {Weinheim},
  publisher    = {Beltz, Psychologie-Verlag-Union},
  pages        = 227,
  isbn         = {3-621-27265-8},
  year         = 1996,
  keywords     = {Qualitative Sozialforschung},
  hardcopylocation = {1. Einleitung: Ordner~12; 5. Offenes Kodieren: Ordner~12; 6. Techniken zum Erh{\"o}hen der theoretischen Sensibilit{\"a}t: Ordner~12; 7. Axiales Kodieren: Ordner~12; 8. Selektives Kodieren: Ordner~12},
  bibdate      = {20.04.2006},
}

');

$bibitem->parseEntry();

print_r($bibitem->mValues);

print "X\n\n";


?></pre>