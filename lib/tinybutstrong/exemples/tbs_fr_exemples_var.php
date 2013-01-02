<?php

include_once('tbs_class.php');

$montant = 3.55;
$tache['lundi'] = '<ménage>';

class clsObj {
	var $param = 'bonjour';
}
$obj = new clsObj;

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_var.htm');
$TBS->Show();

?>