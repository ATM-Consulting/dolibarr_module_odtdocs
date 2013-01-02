<?php

include_once('tbs_class.php');
include_once('tbs_plugin_html.php'); // Plug-in pour la sélection d'items HTML

$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_form.htm');

$typelist = array('<autre>'=>'-','Monsieur'=>'M.','Madame'=>'Mme','Mademoiselle'=>'Mlle') ; 
$TBS->MergeBlock('typeblk',$typelist) ; 

if (!isset($_POST)) $_POST=&$HTTP_POST_VARS;
if (!isset($_POST['x_type'])) { 
  $x_type = '-' ; 
  $x_name = '' ; 
  $x_subname = '' ; 
  $msg_text = 'Après avoir saisi, cliquez sur le bouton [Valider].' ; 
  $msg_color = '#0099CC' ; //bleu
} else { 
  $msg_text = '';
  $msg_body = array();
  $x_type = $_POST['x_type'];
  $x_name = $_POST['x_name'];
  $x_subname = $_POST['x_subname'] ; 
  if (trim($x_type)=='-')   $msg_body[] = ' votre civilité' ; 
  if (trim($x_name)=='')    $msg_body[] = ' votre nom' ; 
  if (trim($x_subname)=='') $msg_body[] = ' votre prénom' ; 
  if (count($msg_body)==0) {
    $msg_text = 'Merci d\'avoir rempli ce formulaire.' ; 
    $msg_color = '#336600' ; //vert
	} else {
    $msg_text = 'Vous devez saisir' . join($msg_body, ' et');
    $msg_color = '#990000' ; //rouge
	}
} 

$TBS->Show();

?>
