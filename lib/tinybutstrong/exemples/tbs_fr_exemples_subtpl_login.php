<?php

if (isset($this)) {
  // On est dans le mode Sous-modèle de TBS =>
	//   les variables sont toujours locales, pas globales,
	//   et l'objet TBS est référencé par la variable locale $this.
	$TBS =& $this;
} else {
  // Ce sous-script peut aussi être exécuté en mode normal =>
  //  sont modèle correspondant sera affiché comme un modèle principal.
	include_once('tbs_class.php');
	$TBS = new clsTinyButStrong;
}

global $err_log; // N'oubliez pas que les variables sont toujours locales en mode Sous-modèle.

if (isset($_POST['btn_ok'])) {
  // Imaginez que l'on vérifie compte/mot-de-passe...
	$err_log = 1;
}	else {
	$err_log = 0;
}

$TBS->LoadTemplate('tbs_fr_exemples_subtpl_login.htm');
$TBS->Show() ;  // Quand cette méthode est appelé en mode Sous-modèle, le script principal n'est pas stoppé, et ce sous-modèle fusionné sera insérer dans le modèle principal.

?>