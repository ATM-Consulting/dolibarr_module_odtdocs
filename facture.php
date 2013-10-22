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
echo '</pre>';exit;*/

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

	
$ATMdb=new Tdb;	
$sql= "SELECT rowid FROM ".MAIN_DB_PREFIX."bank_account WHERE entity=".$conf->entity;
$ATMdb->Execute($sql);

$TCompte = array();

while($ATMdb->Get_line()) {
	
	$rowid = $ATMdb->Get_field('rowid');
	
	$compte = new Account($db);	
	$compte->fetch($rowid);
	
	$TCompte[$rowid] = $compte;
}

$fac->fetchObjectLinked();

$head = facture_prepare_head($fac);
dol_fiche_head($head, 'tabEditions2', $langs->trans("InvoiceCustomer"), 0, 'bill');

require('./class/odt.class.php');

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {
	//print_r($propal);
	$Ttva = array();
	$tableau=array();
	
	foreach($fac->lines as $ligne) {

		if(class_exists('DaoMilestone')) {

			$milestone = new DaoMilestone($db);
			$milestone->fetch($ligne->rowid,"facture");
		
		}
		
		$ligneArray = TODTDocs::asArray($ligne);
		
		
		if(class_exists('TTarifFacturedet')) {
					
			
			$TTarifFacturedet = new TTarifFacturedet;
			$TTarifFacturedet->load($ATMdb,$ligne->rowid);

			if(empty($ligneArray['tarif_poids'])) $ligneArray['tarif_poids'] = $TTarifFacturedet->tarif_poids;
			if(empty($ligneArray['poids'])){
				switch ($TTarifFacturedet->poids) {
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
		
		if($conf->maccaferri->enabled){
			$resql = $db->query("SELECT fdet.devise_pu as devise_pu, fdet.devise_mt_ligne as devise_mt_ligne, p.product_unit
							 FROM ".MAIN_DB_PREFIX."facturedet as fdet
							 LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = fdet.fk_product)
							 WHERE fdet.rowid = ".$ligne->rowid);
		
			$res = $db->fetch_object($resql);
			
			$ligneArray['devise_pu'] = (empty($res->devise_pu)) ? $ligneArray['subprice'] : $res->devise_pu;
			$ligneArray['devise_mt_ligne'] = (empty($res->devise_mt_ligne)) ? $ligneArray['total_ht'] : $res->devise_pu;
			$ligneArray['unite'] = (empty($res->product_unit)) ? '' : $res->product_unit;
		}
		
		if(empty($ligneArray['product_label'])) $ligneArray['product_label'] = $ligneArray['description'];
		if(empty($ligneArray['product_ref'])) $ligneArray['product_ref'] = '';
		if($ligneArray['remise_percent'] == 0) $ligneArray['remise_percent'] = '';
		if(empty($ligneArray['price'])) $ligneArray['price'] = $ligneArray['subprice']*(1-($ligneArray['remise_percent']/100));
		
		if(empty($ligneArray['desc']) && $ligne->product_type == 9) $ligneArray['desc'] = html_entity_decode(htmlentities($milestone->label,ENT_QUOTES,"UTF-8"));
		
		$tableau[]=$ligneArray;
		$Ttva[$ligneArray['tva_tx']] += $ligneArray['total_tva'];
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
	if($conf->maccaferri->enabled){ 
		$resql = $db->query("SELECT c.name as devise, i.code, i.libelle, p.ref, p.title
							FROM ".MAIN_DB_PREFIX."currency as c
							LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON (f.devise_code = c.code)
							LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON (p.rowid = f.fk_projet)
							LEFT JOIN ".MAIN_DB_PREFIX."c_incoterms as i ON (i.rowid = f.fk_incoterms)
							WHERE f.rowid = ".$fac->id);
		
		$res = $db->fetch_object($resql);
		
		$autre = array("devise"=>$res->devise,
					   "incoterm"=>$res->code." - ".$res->libelle,
					   "date_facture_fr"=>date('d/m/Y'),
					   "date_lim_reglement_fr"=>date('d/m/Y',$fac->date_lim_reglement),
					   "projet"=>$res->ref." ".$res->title);
	}
	else{
		$autre = array();
	}
	
	foreach ($Ttva as $cle=>$val){
		$TVA[] = array("label"=>$cle,"montant"=>$val);
	}
	
	//Condition de règlement
	$resql = $db->query('SELECT libelle_facture FROM '.MAIN_DB_PREFIX."c_payment_term WHERE rowid = ".$fac->cond_reglement_id);
	$res = $db->fetch_object($resql);
	$contact['reglement'] = $res->libelle_facture;	
	//Mode de règlement
	$resql = $db->query('SELECT libelle FROM '.MAIN_DB_PREFIX."c_paiement WHERE id = ".$fac->mode_reglement_id);
	$res = $db->fetch_object($resql);
	$contact['mode_reglement'] = $res->libelle;
	
	//print_r($tableau); exit;
@	TODTDocs::makeDocTBS(
		'facture'
		, $_REQUEST['modele']
		,array('doc'=>$fac, 'societe'=>$societe, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau, 'contact'=>$contact, 'compte'=>$TCompte[$_REQUEST['account']] ,'linkedObjects'=>$fac->linkedObjects,'autre'=>$autre,'tva'=>$TVA)
		, $conf->facture->dir_output.'/'. dol_sanitizeFileName($fac->ref).'/'.dol_sanitizeFileName($fac->ref).'-'.$_REQUEST['modele']/*.TODTDocs::_ext( $_REQUEST['modele'])*/
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


?>Modèle* à utiliser <?

TODTDocs::combo('facture', 'modele',GETPOST('modele'), $conf->entity);
//print_r($societe);
TODTDocs::comboLang($db, $societe->default_lang);

	if(!empty($TCompte)) {
		
		?>
		- Rib du compte à afficher <select name="account" class="flat"><?
		
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


