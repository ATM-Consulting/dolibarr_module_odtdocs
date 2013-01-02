<?php

include_once('tbs_class.php');

$x = 'Bonjour';

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_hello.htm');
$TBS->Show();

?>