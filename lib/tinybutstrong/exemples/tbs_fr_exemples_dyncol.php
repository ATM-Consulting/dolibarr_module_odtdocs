<?php

include_once('tbs_class.php');

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_dyncol.htm');

// Récupération de la saisie de l'utilisateur
if (!isset($_GET)) $_GET=&$HTTP_GET_VARS;
$nbr_row = (isset($_GET['nbr_row'])) ? intval($_GET['nbr_row']) : 10;
$nbr_col = (isset($_GET['nbr_col'])) ? intval($_GET['nbr_col']) : 10;

// Liste des noms de colonne
$columns = array();
for ($col=1;$col<=$nbr_col;$col++) {
	$columns[$col] = 'column_'.$col;
}

// Création des données
$data = array();
for ($row=1;$row<=$nbr_row;$row++) {
	$record = array();
	for ($col=1;$col<=$nbr_col;$col++) {
		$record[$columns[$col]] = $row * $col;
	}
	$data[$row] = $record;
}

// Extension des colonnes
$TBS->MergeBlock('c0,c1,c2',$columns);

// Fusion des lignes
$TBS->MergeBlock('r',$data);
$TBS->Show();

?>