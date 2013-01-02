<?php

include_once('tbs_class.php');

//Connection à la base de donnée
//Le fichier ci-dessous doit se connecter à la base de donner et renseigner la variable $cnx_id.
if (!isset($_SERVER)) $_SERVER=&$HTTP_SERVER_VARS ; //PHP<4.1.0
require($_SERVER['DOCUMENT_ROOT'].'/cnx_mysql.php');

//Exemple avec une connection PostgreSQL
//$cnx_id = pg_connect('host=localhost port=5432 dbname=books user=peter password=xxxx');

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_datapostgresql.htm');
$TBS->MergeBlock('blk1',$cnx_id,'SELECT * FROM t_tbs_exemples');
mssql_close($cnx_id);
$TBS->Show();

?>