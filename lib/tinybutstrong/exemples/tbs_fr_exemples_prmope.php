<?php

include_once('tbs_class.php');

$name1 = 'Marilyn Monroe';
$name2 = 'Fred Astaire';
$name3 = 'Ginger Rogers';
$name4 = 'James Dean';
$name5 = 'Grace Kelly';
$name6 = 'Rita Hayworth';
$name7 = 'Bette Davis';
$name8 = 'Greta Garbo';

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_prmope.htm');
$TBS->Show();

?>