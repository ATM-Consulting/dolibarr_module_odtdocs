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
require_once(DOL_DOCUMENT_ROOT."/core/lib/contract.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/extrafields.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");


$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("banks");
$langs->load("users");
$langs->Load("contract");

$id = isset($_REQUEST["id"])?$_REQUEST["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat', $socid);


/*
 *	View
 */

llxHeader();

$contrat = new Contrat($db);
$contrat->fetch($_REQUEST["id"]);
$contrat->fetchObjectLinked();

$contrat->date_contrat=date('d/m/Y', $contrat->date_contrat);
$contrat->date_modification=date('d/m/Y', $contrat->date_modification);
$contrat->date_validation=date('d/m/Y', $contrat->date_validation);
$contrat->date_cloture=date('d/m/Y', $contrat->date_cloture);
//pre($contrat, true);
$societe = new Societe($db);
$societe->fetch($contrat->socid);
//var_dump($societe);

$projet = new Project($db);
$projet->fetch($contrat->fk_project);


$propal = new Propal($db);
$lines=array();
if(!empty($contrat->linkedObjects['propal'])){
		foreach ($contrat->linkedObjects['propal'] as $prop) {
			$propal->fetch($prop->id);
			$soustotal=0;
			foreach ($prop->lines as $line) {
				$isst=0;
				// $titre =1 si c'est une ligne de titre, 2 si c'est un sous total, 0 si c'est une ligne normale
				$titre=0;
				$soustotal+=$line->total_ht;
				if($line->fk_product==null){
					if(empty($line->desc))$line->desc = $line->label;
						$line->qty = '';
						$titre=1;
					}
					if ($line->desc=='Sous-total'){
						$line->qty = '';
						$line->price = '';
						$line->total_ht = $soustotal;
						$soustotal=0;
						$line->remise_percent = '';
						$titre=2;
					}
						
				if(empty($line->desc)){
					$line->desc = $line->label;
				}
				if($line->total_ht==0){
					$line->total_ht = '';
				}
				if ($line->tva_tx==0){
					$line->tva_tx = '';
				}
				if($line->price==0){
					$line->price = '';
				}
				if($line->remise_percent==0){
					$line->remise_percent = '';
				}
				//var_dump($line);
				if(!empty($line->price)){
					$lines[]=array(
						'description' => utf8_decode($line->desc),
						'tva'         => mb_strimwidth($line->tva_tx, 0, 4),
						'puHT'        => price(intval($line->price)),
						'qty'         => $line->qty,
						'totalHT'     => $line->total_ht,
						'remise'      => $line->remise_percent,
						'titre'       => $titre
						);
				}else{
					$lines[]=array(
						'description' => utf8_decode($line->desc),
						'tva'         => mb_strimwidth($line->tva_tx, 0, 4),
						'puHT'        => price(intval($line->subprice)),
						'qty'         => $line->qty,
						'totalHT'     => price(intval($line->total_ht)),
						'remise'      => $line->remise_percent,
						'titre'       => $titre
						);
				}
			}
	}
}
//var_dump($lines);
$head = contract_prepare_head($contrat);
dol_fiche_head($head, 'tabEditions5', $langs->trans('ThirdParty'), 0, 'company');

require('./class/odt.class.php');
require('./class/atm.doctbs.class.php');

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {
	//print_r($propal);
	$TVA = TODTDocs::getTVA($propal);
	//var_dump($contrat->linkedObjects['propal']);
//	print_r($tableau); exit;
	$fOut =  $conf->contrat->dir_output.'/'. dol_sanitizeFileName($contrat->ref).'/'.dol_sanitizeFileName($contrat->ref).'-'.$_REQUEST['modele']/*. TODTDocs::_ext( $_REQUEST['modele'])*/;
	TODTDocs::makeDocTBS(
		'contract'
		, $_REQUEST['modele']
		,array('mysoc'=>$mysoc, 'societe'=>$societe,'conf'=>$conf, 'contrat'=>$contrat, 'propal_lines'=>$lines, 'propal'=>$propal, 'tva'=>$TVA)
		,$fOut
		, $conf->entity
		,isset($_REQUEST['btgenPDF'])
	);
	
	
}

?>
<form name="genfile" method="get" action="<?=$_SERVER['PHP_SELF'] ?>">
	<input type="hidden" name="id" value="<?=$id ?>" />
	<input type="hidden" name="action" value="GENODT" />
<table width="100%"><tr><td>
<?


?>Modèle à utiliser* <?
TODTDocs::combo('contract', 'modele',GETPOST('modele'), $conf->entity);
TODTDocs::comboLang($db, $contrat->default_lang);
?> <input type="submit" value="Générer" class="button" name="btgen" /> <input type="submit" id="btgenPDF"  name="btgenPDF" value="Générer en PDF" class="button" /><?

?><br><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?
	
TODTDocs::show_docs($db, $conf,$contrat, $langs, 'contract');


?>
</td></tr></table>
</form>

<?
print '</div>';
$db->close();


llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.34 $');


?>
