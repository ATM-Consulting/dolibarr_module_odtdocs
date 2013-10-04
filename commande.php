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
require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/order.lib.php");

dol_include_once("/custom/tarif/class/tarif.class.php");
dol_include_once("/custom/milestone/class/dao_milestone.class.php");

global $db, $langs;
$langs->load('orders');
$langs->load('sendings');
$langs->load('bills');
$langs->load('companies');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');
$langs->load('odtdocs@odtdocs');

/*echo '<pre>';
print_r($langs);
echo '</pre>';*/

/*
 * View
 */

llxHeader();
$ATMdb = new Tdb;
$id = isset($_REQUEST["id"])?$_REQUEST["id"]:'';

$commande = new commande($db);
$commande->fetch($_REQUEST["id"]);
$commande->info($_REQUEST["id"]);
$commande->fetchObjectLinked();

$societe = new Societe($db, $commande->socid);
$societe->fetch($commande->socid);

$head = commande_prepare_head($commande);
dol_fiche_head($head, 'tabEditions3', $langs->trans("CustomerOrder"), 0, 'order');

require('./class/odt.class.php');

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {
	//print_r($propal);
	
	$tableau=array();
	
	foreach($commande->lines as $ligne) {
		$ligneArray = TODTDocs::asArray($ligne);	
		
		if(class_exists('DaoMilestone')) {	
			$milestone = new DaoMilestone($db);
			$milestone->fetch($ligne->rowid,"commande");
		}
	
		if(class_exists('TTarifCommandedet')) {	
			$TTarifCommandedet = new TTarifCommandedet;
			$TTarifCommandedet->load($ATMdb,$ligne->rowid);
			
			if(empty($ligneArray['tarif_poids'])) $ligneArray['tarif_poids'] = $TTarifCommandedet->tarif_poids;
			if(empty($ligneArray['poids'])){
				switch ($TTarifCommandedet->poids) {
					case -9:
						$ligneArray['poids'] = "ug";
						break;
					case -6:
						$ligneArray['poids'] = "mg";
						break;
					case -3:
						$ligneArray['poids'] = "g";
						break;
					case 0:
						$ligneArray['poids'] = "kg";
						break;
				}
			}
			
		}
		
		//print_r($TTarifCommandedet);
		if(empty($ligneArray['desc']) && $ligne->product_type == 9) $ligneArray['desc'] = html_entity_decode(htmlentities($milestone->label,ENT_QUOTES,"UTF-8"));
		/*print_r($ligneArray);*/
		if(empty($ligneArray['product_label'])) $ligneArray['product_label'] = $ligneArray['desc'];
		if(empty($ligneArray['product_ref'])) $ligneArray['product_ref'] = '';
		if($ligneArray['remise_percent'] == 0) $ligneArray['remise_percent'] = '';
		if(empty($ligneArray['subprice'])) $ligneArray['subprice'] = 0;
		
		/*echo '<pre>';
		print_r($ligneArray);
		echo '</pre>';*/
		
		
		$tableau[]=$ligneArray;
	}
	
	$contact = TODTDocs::getContact($db, $commande, $societe);
	if(isset($contact['CUSTOMER'])) {
		$societe->nom = $contact['CUSTOMER']['societe'];
		if($contact['CUSTOMER']['address'] != '') {
			$societe->address = $contact['CUSTOMER']['address'];
			$societe->cp = $contact['CUSTOMER']['cp'];
			$societe->ville = $contact['CUSTOMER']['ville'];
			$societe->pays = $contact['CUSTOMER']['pays'];
		}
	}
	
	/*echo '<pre>';
	print_r($commande);
	echo '</pre>';*/

	
	TODTDocs::makeDocTBS(
		'commande'
		, $_REQUEST['modele']
		,array('doc'=>$commande, 'societe'=>$societe, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau, 'contact'=>$contact, 'linkedObjects'=>$commande->linkedObjects)
		, $conf->commande->dir_output.'/'. dol_sanitizeFileName($commande->ref).'/'.dol_sanitizeFileName($commande->ref).'-'.$_REQUEST['modele']/*.TODTDocs::_ext( $_REQUEST['modele'])*/
		, $conf->entity
		,isset($_REQUEST['btgenPDF'])
		,$_REQUEST['lang_id']
		,array('orders', 'odtdocs@odtdocs','main','dict','products','sendings','bills','companies','propal','deliveries')
	);

}

?>
<form name="genfile" method="get" action="<?=$_SERVER['PHP_SELF'] ?>">
	<input type="hidden" name="id" value="<?=$id ?>" />
	<input type="hidden" name="action" value="GENODT" />
<table width="100%"><tr><td>
<?


?>Modèle* à utiliser <?
TODTDocs::combo('commande', 'modele',GETPOST('modele'), $conf->entity);
TODTDocs::comboLang($db, $societe->default_lang);

?> <input type="submit" value="Générer" class="button" name="btgen" /> <input type="submit" name="btgenPDF" id="btgenPDF" value="Générer en PDF" class="button" /><?
?>
<br/><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?
	
TODTDocs::show_docs($db, $conf,$commande, $langs,'commande');


?>
</td></tr></table>
</form>

<?
print '</div>';
$db->close();

llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.34 $');


?>
