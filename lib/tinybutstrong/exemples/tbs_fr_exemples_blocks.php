<?php

include_once('tbs_class.php');

$country = array('France','Angleterre','Espagne','Italie','Allemagne');

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_blocks.htm');
$TBS->MergeBlock('blk1,blk2,blk3,blk4,blk5,blk6,blk7',$country); // Fusionne différents blocs avec les mêmes données.
$TBS->Show();

?>