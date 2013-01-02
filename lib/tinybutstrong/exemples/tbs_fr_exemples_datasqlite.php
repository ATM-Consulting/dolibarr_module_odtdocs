<?php

include_once('tbs_class.php');

//Exemple avec une connection SQLite
//$cnx_id = sqlite_open('mydatabase.dat');

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_datasqlite.htm');
$TBS->MergeBlock('blk1',$cnx_id,'SELECT * FROM t_tbs_exemples');
sqlite_close($cnx_id);
$TBS->Show();

?>