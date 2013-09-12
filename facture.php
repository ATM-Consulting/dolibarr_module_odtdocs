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


//require("../../main.inc.php");
include 'config.php';


require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');

require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/custom/tarif/class/tarif.class.php");
require_once(DOL_DOCUMENT_ROOT."/custom/milestone/class/dao_milestone.class.php");

global $db;
$langs->load("bills");


/*
 * View
 */

llxHeader();

$id = isset($_REQUEST["id"])?$_REQUEST["id"]:'';

$fac = new Facture($db);
$fac->fetch($_REQUEST["id"]);
$fac->info($_REQUEST["id"]);
$fac->fetchObjectLinked();

$societe = new Societe($db, $fac->socid);
$societe->fetch($fac->socid);


/*
	 * Liste des comptes bancaires disponible
	 */
require_once(DOL_DOCUMENT_ROOT."/societe/class/companybankaccount.class.php");
$compte = new Account($db);	

$ATMdb=new Tdb;	
$sql= "SELECT rowid FROM ".MAIN_DB_PREFIX."bank_account WHERE entity=".$conf->entity;
$ATMdb->Execute($sql);

$TCompte = array();

while($ATMdb->Get_line()) {
	
	$rowid = $ATMdb->Get_field('rowid');
	$compte->fetch($rowid);
	$TCompte[$rowid] = $compte;
}

$fac->fetchObjectLinked();

$head = facture_prepare_head($fac);
dol_fiche_head($head, 'tabEditions2', $langs->trans("InvoiceCustomer"), 0, 'bill');

require('./class/odt.class.php');

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {
	//print_r($propal);
	
	$tableau=array();
	
	foreach($fac->lines as $ligne) {
		$TTarifFacturedet = new TTarifFacturedet;
		$TTarifFacturedet->load($ATMdb,$ligne->rowid);
		
		$milestone = new DaoMilestone($db);
		$milestone->fetch($ligne->rowid,"facture");
		
		$ligneArray = TODTDocs::asArray($ligne);
		if(empty($ligneArray['product_label'])) $ligneArray['product_label'] = $ligneArray['description'];
		if(empty($ligneArray['product_ref'])) $ligneArray['product_ref'] = '';
		if($ligneArray['remise_percent'] == 0) $ligneArray['remise_percent'] = '';
		
		if(empty($ligneArray['desc']) && $ligne->product_type == 9) $ligneArray['desc'] = html_entity_decode(htmlentities($milestone->label,ENT_QUOTES,"UTF-8"));
		if(empty($ligneArray['tarif_poids'])) $ligneArray['tarif_poids'] = $TTarifFacturedet->tarif_poids;
		if(empty($ligneArray['poids'])){
			switch ($TTarifFacturedet->poids) {
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
		
		$tableau[]=$ligneArray;
	}
	
	$contact = TODTDocs::getContact($db, $fac, $societe);
	if(isset($contact['BILLING'])) {
		$societe->nom = $contact['BILLING']['societe'];
		if($contact['BILLING']['address'] != '') {
			$societe->address = $contact['BILLING']['address'];
			$societe->cp = $contact['BILLING']['cp'];
			$societe->ville = $contact['BILLING']['ville'];
			$societe->pays = $contact['BILLING']['pays'];
		}
	}
	/*echo '<pre>';
	print_r($societe);
	print_r($contact);
	echo '</pre>';*/
	
	/*
	 * Ajout des objets lié :
	 * [fk_facture_source] => [origin] => [origin_id] => [linked_objects
	 * Mais cette valeur ne semble jamais remplie et mes recherches sont infructueuses.
	 */
	
	//Condition de règlement
	$resql = $db->query('SELECT libelle_facture FROM '.MAIN_DB_PREFIX."c_payment_term WHERE rowid = ".$fac->cond_reglement_id);
	$res = $db->fetch_object($resql);
	$contact['reglement'] = $res->libelle_facture;	
	//Mode de règlement
	$resql = $db->query('SELECT libelle FROM '.MAIN_DB_PREFIX."c_paiement WHERE id = ".$fac->mode_reglement_id);
	$res = $db->fetch_object($resql);
	$contact['mode_reglement'] = $res->libelle;
	
	/*echo '<pre>';
	print_r($fac->linkedObjects);
	echo '</pre>';*/
	
	//print_r($tableau); exit;
@	TODTDocs::makeDocTBS(
		'facture'
		, $_REQUEST['modele']
		,array('doc'=>$fac, 'societe'=>$societe, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau, 'contact'=>$contact, 'compte'=>$TCompte[$_REQUEST['account']] ,'linkedObjects'=>$fac->linkedObjects )
		, $conf->facture->dir_output.'/'. dol_sanitizeFileName($fac->ref).'/'.dol_sanitizeFileName($fac->ref).'-'.$_REQUEST['modele']/*.TODTDocs::_ext( $_REQUEST['modele'])*/
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


?>Modèle* à utiliser <?

TODTDocs::combo('facture', 'modele',GETPOST('modele'), $conf->entity);

	if(!empty($TCompte)) {
		
		?>
		Rib du compte à afficher <select name="account"><?
		
			foreach($TCompte as $compte) {
				
					?><option value="<?=$compte->rowid ?>" <?=(isset($_REQUEST['account']) && $_REQUEST['account']==$compte->rowid) ? 'SELECTED' : ''  ?>><?=$compte->label ?></option><?	
				
			}
			
			?></select><?
	}
?>
 <input type="submit" value="Générer" class="button" name="btgen" /> <input type="submit" name="btgenPDF" id="btgenPDF" value="Générer en PDF" class="button" />

<br/><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?
	
TODTDocs::show_docs($db, $conf,$fac, $langs,'facture');


?>
</td></tr></table>
</form>

<?
print '</div>';
$db->close();

llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.34 $');


