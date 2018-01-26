<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
        \file       htdocs/comm/propal/info.php
        \ingroup    propale
		\brief      Page d'affichage des infos d'une proposition commerciale
		\version    $Id: info.php,v 1.34 2011/08/03 00:46:34 eldy Exp $
*/

require('config.php');

require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/extrafields.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php');

dol_include_once('/odtdocs/lib/odtdocs.lib.php');

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("banks");
$langs->load("users");

$id = isset($_REQUEST["id"])?$_REQUEST["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);


/*
 *	View
 */

llxHeader();

$societe = new Societe($db);
$societe->fetch($_REQUEST["id"]);

$head = societe_prepare_head($societe);
dol_fiche_head($head, 'tabEditions5', $langs->trans('ThirdParty'), 0, 'company');

require('./class/odt.class.php');
require('./class/atm.doctbs.class.php');

$action=GETPOST('action');
$hookmanager->initHooks(array('societecard'));
$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$societe,$action);    // Note that $action and $object may have been modified by some hooks


if($action == 'GENODT') {
	//print_r($propal);
	
	
//	print_r($tableau); exit;
	$fOut =  $conf->societe->dir_output.'/'. dol_sanitizeFileName($societe->ref).'/'.dol_sanitizeFileName($societe->ref).'-'.$_REQUEST['modele']/*. TODTDocs::_ext( $_REQUEST['modele'])*/;

	$TExtrafields = array();
	if(!empty($societe->array_options)) {
		$TExtrafields = array_merge(get_tab_extrafields($societe->array_options, 'propal'), get_tab_extrafields_evo($societe));
	}
	
	// Dsl, mais dolibarr gère encore l'affichage en dur dans le code
	$societe->client_type = '';
	if ($societe->client == 0) $societe->client_type = $langs->trans('NorProspectNorCustomer');
	elseif ($societe->client == 1) $societe->client_type = $langs->trans('Customer');
	elseif ($societe->client == 2) $societe->client_type = $langs->trans('Prospect');
	elseif ($societe->client == 3) $societe->client_type = $langs->trans('ProspectCustomer');
	
	
	//$societe->note_private = dol_string_nohtmltag($societe->note_private);
	$societe->note_private = preg_replace('/<[^<>]+>/', '', $societe->note_private);
	$societe->note_public = preg_replace('/<[^<>]+>/', '', $societe->note_public);

	$TContact = $societe->contact_array_objects();
	foreach ($TContact as $key => $ObjectVal) {
		$TContact[$key]->poste = utf8_decode($ObjectVal->poste);
	}
	$TBlock = array('TContact' => $TContact);
	
	if (empty($societe->capital)) $societe->capital = 0;

	$tableau=array();
	$TAutre = array();
	$parameters = array(
		'currentContext' => 'propalOdtDoc'
		,'extrafields'=>&$TExtrafields
		,'societe'=>&$societe
		,'mysoc'=>&$mysoc
		,'conf'=>&$conf
		,'autre'=>&$autre
		,'TAutre'=>&$TAutre
		,'TContact'=>&$TContact
		,'TBlock'=>&$TBlock
	);
	
	$reshook=$hookmanager->executeHooks('beforeGenerateOdtDoc',$parameters,$societe,$action);
	
	TODTDocs::makeDocTBS(
		'company'
		, $_REQUEST['modele']
		,array('societe'=>$societe, 'mysoc'=>$mysoc, 'conf'=>$conf, 'extrafields'=>$TExtrafields, 'TAutre' => $TAutre, 'autre' => $autre,  'TBlock' => $TBlock)
		,$fOut
		, $conf->entity
		,isset($_REQUEST['btgenPDF'])	
	);
	
	
}
function decode($FieldName, &$CurrVal)
{
    return $CurrVal = html_entity_decode($CurrVal);
}
?>
<form name="genfile" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<input type="hidden" name="id" value="<?php echo $id; ?>" />
	<input type="hidden" name="action" value="GENODT" />
<table width="100%"><tr><td>
<?php


?>Modèle à utiliser* <?php
TODTDocs::combo('company', 'modele',GETPOST('modele'), $conf->entity);
?> <input type="submit" value="Générer" class="button" name="btgen" /> <input type="submit" id="btgenPDF"  name="btgenPDF" value="Générer en PDF" class="button" /><?php

?><br><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?php
	
TODTDocs::show_docs($db, $conf,$societe, $langs, 'company');


?>
</td></tr></table>
</form>

<?php
print '</div>';

llxFooter();

$db->close();