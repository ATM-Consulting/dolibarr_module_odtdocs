<?php

include_once('tbs_class.php');

//Valeur par défaut
if (!isset($_GET)) $_GET=&$HTTP_GET_VARS;
if (isset($_GET['empty'])) {
  $empty = $_GET['empty'];
} else {
  $empty = 0;
}

if ($empty) {
	$url = '';
	$image = '';
	$line1 = '1 rue de Paris';
	$line2 = '';
} else {
	$url = 'www.tinybutstrong.com';
	$image = 'tbs_fr_exemples_prmmagnet.gif';
	$line1 = '2 rue de France';
	$line2 = 'BP 255';
}

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_prmmagnet.htm');
$TBS->Show();

?>