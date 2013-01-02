<?php

include_once('tbs_class.php');

//Connexion à la base de donnée
if (!isset($_SERVER)) $_SERVER=&$HTTP_SERVER_VARS ; //PHP<4.1.0
require($_SERVER['DOCUMENT_ROOT'].'/cnx_mysql.php');

//Le fichier cnx_mysql.php contiens les lignes suivnates :
//  $cnx_id = mysql_connect('localhost','user','password');
//  mysql_select_db('dbname',$cnx_id);

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_datamysql.htm');
$TBS->MergeBlock('blk1',$cnx_id,'SELECT * FROM t_tbs_exemples');
mysql_close($cnx_id);
$TBS->Show();

?>