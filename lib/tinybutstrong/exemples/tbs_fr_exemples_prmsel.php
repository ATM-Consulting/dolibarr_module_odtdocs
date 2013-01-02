<?php

include_once('tbs_class.php');
include_once('tbs_plugin_html.php'); // Plug-in pour sélectionner des items HTML.

// Exemple avec des données enreistrées dans un tableau.
$item_lst = array();
$item_lst[] = array('name'=>'Rouge','id'=>1);
$item_lst[] = array('name'=>'Vert' ,'id'=>2);
$item_lst[] = array('name'=>'Bleu' ,'id'=>3);
$item_lst[] = array('name'=>'Jaune','id'=>4);
$item_lst[] = array('name'=>'Blanc','id'=>5);

$sel1_name = 'Jaune';
$sel1_id = 4;
$sel2_name = array('Vert','Bleu','Jaune');
$sel2_id = array(2,3,4);

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_prmsel.htm');

// Remplissage des listes 
$TBS->MergeBlock('lst1v',$item_lst);
$TBS->MergeBlock('lst1' ,$item_lst);
$TBS->MergeBlock('lst3v',$item_lst);
$TBS->MergeBlock('lst3' ,$item_lst);

// La sélection des items se fait ici
$TBS->Show();

?>