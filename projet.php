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

include 'config.php';
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
dol_include_once("/tarif/class/tarif.class.php");
dol_include_once("/milestone/class/dao_milestone.class.php");
dol_include_once('/projet/class/project.class.php');
dol_include_once('/projet/class/task.class.php');

global $db, $langs;
$langs->load('orders');
$langs->load('sendings');
$langs->load('bills');
$langs->load('companies');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');
$langs->load('odtdocs@odtdocs');

$id = isset($_REQUEST["id"])?$_REQUEST["id"]:'';

// Security check
/*if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'propale', $id, 'propal');*/

/*
 *	View
 */
 
/*echo '<pre>';
print_r($db);
echo '</pre>';*/

llxHeader();

$ATMdb = new TPDOdb;

$projet = new Project($db);
$projet->fetch($_REQUEST["id"]);
$projet->fetchObjectLinked();

$taskstatic = new Task($db);

$societe = new Societe($db);
$societe->fetch($projet->socid);

$head = project_prepare_head($projet);
dol_fiche_head($head, 'tabEditions7', $langs->trans('Project'), 0, 'project');

$action='builddoc';
$hookmanager->initHooks(array('projectcard'));
$parameters=array('socid'=>$projet->socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$projet,$action);    // Note that $action and $object may have been modified by some hooks


require('./class/odt.class.php');
require('./class/atm.doctbs.class.php');

function getMoreInfoContacts($contacts) {
	
	global $db, $mysoc;
	$TContacts = array();
	foreach($contacts as $TDataContact) {
		
		$c = new Contact($db);
		$c->fetch($TDataContact['id']);
		$TDataContact['phone_pro'] = $c->phone_pro;
		
		$soc = new Societe($db);
		if($soc->fetch($TDataContact['socid']) > 0) {
			$TDataContact['nom_tiers'] = $soc->nom;
		} else $TDataContact['nom_tiers'] = $mysoc->nom;
		
		$TContacts[] = $TDataContact;
		
	}
	
	return $TContacts;
	
}

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {

	$tableau=array();
	
	$TProjectTasks = $taskstatic->getTasksArray(0, 0, $projet->id);
	
	foreach($TProjectTasks as $ligneArray) {
		
		$tableau[]=TODTDocs::asArray($ligneArray);
	
	}
	
	$contact = array_merge($projet->liste_contact(-1,'internal'), $projet->liste_contact(-1));
	$contact = getMoreInfoContacts($contact);
	
	$generatedfilename = dol_sanitizeFileName($projet->ref).'-'.$_REQUEST['modele'];
	if($conf->global->ODTDOCS_FILE_NAME_AS_OBJECT_REF) {
		$generatedfilename = dol_sanitizeFileName($projet->ref);
	}
	$fOut = $fOut =  $conf->propal->dir_output.'/'. dol_sanitizeFileName($projet->ref).'/'.$generatedfilename;
	
	$projet->date_start = date('d/m/Y', $projet->date_start);
	$projet->date_end = date('d/m/Y', $projet->date_end);

	$societe->country = strtr($societe->country, array("'"=>' '));
	
	TODTDocs::makeDocTBS(
		'projet'
		, $_REQUEST['modele']
		,array('doc'=>$projet, 'societe'=>$societe, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau, 'contact_block'=>$contact,'autre'=>$autre)
		,$fOut
		, $conf->entity
		,isset($_REQUEST['btgenPDF'])
		,$_REQUEST['lang_id']
		,array('orders', 'odtdocs@odtdocs','main','dict','products','sendings','bills','companies','propal','deliveries')
	);
	
	
}

function decode($FieldName, &$CurrVal)
{
    return $CurrVal = html_entity_decode($CurrVal);
}

?>
<form name="genfile" method="get" action="<?=$_SERVER['PHP_SELF'] ?>">
	<input type="hidden" name="id" value="<?=$id ?>" />
	<input type="hidden" name="action" value="GENODT" />
<table width="100%"><tr><td>
<?


?>Modèle à utiliser* <?
TODTDocs::combo('projet', 'modele',GETPOST('modele'), $conf->entity);
TODTDocs::comboLang($db, $societe->default_lang);
?> <input type="submit" value="Générer" class="button" name="btgen" /> <input type="submit" id="btgenPDF"  name="btgenPDF" value="Générer en PDF" class="button" /><?

?><br><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?
	
TODTDocs::show_docs($db, $conf,$projet, $langs);


?>
</td></tr></table>
</form>

<?
print '</div>';
$db->close();

llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.34 $');


?>
