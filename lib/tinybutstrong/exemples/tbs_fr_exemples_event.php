<?php

include_once('tbs_class.php');

$data_array[] = array('res_name'=>'Marie',  'res_score'=>300, 'res_date'=>'2003-01-10');
$data_array[] = array('res_name'=>'Eric', 'res_score'=>215, 'res_date'=>'2003-01-10');
$data_array[] = array('res_name'=>'Marc', 'res_score'=>180, 'res_date'=>'2003-01-10');
$data_array[] = array('res_name'=>'Paul', 'res_score'=>175, 'res_date'=>'2003-01-10');
$data_array[] = array('res_name'=>'Mat', 'res_score'=>120, 'res_date'=>'2003-01-10');
$data_array[] = array('res_name'=>'Sophie', 'res_score'=>115, 'res_date'=>'2003-01-10');

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_event.htm');
$TBS->MergeBlock('b1',$data_array);
$TBS->Show();

//Fonction évènementielle
function m_event_b1($NomBloc,&$CurrRec,$RecNum){
//$NomBloc   : nom du bloc qui appel la fonction (lecture seule)
//$CurrRec   : tableau contenant les champs de l'enregistrement en cours (lecture/écriture)
//$RecNum    : numéro de l'enregsitrement en cours (lecture seule)
  if ($RecNum==1) $CurrRec['res_name'] = $CurrRec['res_name']. ' (gagnant)';
  if ($CurrRec['res_score']<100) $CurrRec['level'] = 'mauvais';
  if ($CurrRec['res_score']>=100) $CurrRec['level'] = '<font color="#669933">moyen</font>';
  if ($CurrRec['res_score']>=200) $CurrRec['level'] = '<font color="#3366CC">bon</font>';
  if ($CurrRec['res_score']>=300) $CurrRec['level'] = '<font color="#CCCC00"><strong>excellent</strong></font>';
}

?>