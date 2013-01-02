<?php

// On vérifie si le source du sous-script a été demandé
if (isset($_GET['subsrc'])) {
  show_source('tbs_fr_exemples_subtpl_login.php');
  exit;
}

// Préparation des variables
if (isset($_GET['art'])) {
	$art = $_GET['art'];
}	else {
	$art = 0;
}
$tmpl_article = 'tbs_fr_exemples_subtpl_article'.$art.'.htm';
$tmpl_menu = 'tbs_fr_exemples_subtpl_menu.htm';

// Fusion du modèle principal
include_once('tbs_class.php');
$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_subtpl.htm');
$TBS->Show();

?>