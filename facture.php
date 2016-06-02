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
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');

require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

dol_include_once("/tarif/class/tarif.class.php");	
dol_include_once("/milestone/class/dao_milestone.class.php");
dol_include_once("/projet/class/project.class.php");
dol_include_once("/odtdocs/lib/odtdocs.lib.php");
dol_include_once('/includes/odtphp/odf.php');

global $db, $langs, $conf;
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
$fac->fetch_optionals($fac->id);

$societe = new Societe($db, $fac->socid);
$societe->fetch($fac->socid);

if($fac->fk_project) {
	$projet = new Project($db);
	$projet->fetch($fac->fk_project);
}

/*
	 * Liste des comptes bancaires disponible
	 */
require_once(DOL_DOCUMENT_ROOT."/societe/class/companybankaccount.class.php");

	
$ATMdb=new TPDOdb;	
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

$hookmanager->initHooks(array('invoicecard'));
$parameters=array('socid'=>$fac->socid);
$action='builddoc';
$reshook=$hookmanager->executeHooks('doActions',$parameters,$fac,$action);    // Note that $action and $object may have been modified by some hooks


$head = facture_prepare_head($fac);
dol_fiche_head($head, 'tabEditions2', $langs->trans("InvoiceCustomer"), 0, 'bill');

require('./class/odt.class.php');

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {
	$tableau=array();
	$TExtrafields = array();
	
	if(!empty($fac->array_options)) {
		$TExtrafields = get_tab_extrafields($fac->array_options, 'facture');
	}
	
	$TPaiement = array('lines' => array(), 'total' => array('total_ttc' => 0));
	// Payments already done (from payment on this invoice)
	$sql = 'SELECT p.datep as dp, p.num_paiement, p.rowid, p.fk_bank,';
	$sql .= ' c.code as payment_code, c.libelle as payment_label,';
	$sql .= ' pf.amount,';
	$sql .= ' ba.rowid as baid, ba.ref, ba.label';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_paiement as c, ' . MAIN_DB_PREFIX . 'paiement_facture as pf, ' . MAIN_DB_PREFIX . 'paiement as p';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank as b ON p.fk_bank = b.rowid';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON b.fk_account = ba.rowid';
	$sql .= ' WHERE pf.fk_facture = ' . $fac->id . ' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
	$sql .= ' ORDER BY p.datep, p.tms';
	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		while($row = $db->fetch_object($resql))
		{
			$TPaiement['lines'][] = array(
				'rowid' => $row->rowid
				,'ref' => $row->ref
				,'payment_code' => $row->payment_code
				,'payment_label' => $row->payment_label
				,'amount' => $row->amount
			);
			
			$total += $row->amount;
		}

		$TPaiement['total']['total_ttc'] = $total;
	}

	$TAcompte = array('lines' => array(), 'total' => array());
	// Loop on each credit note or deposit amount applied
	$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
	$sql .= " re.description, re.fk_facture_source";
	$sql .= " FROM " . MAIN_DB_PREFIX . "societe_remise_except as re";
	$sql .= " WHERE fk_facture = " . $fac->id;
	$resql = $db->query($sql);
	if ($resql) {
		$total_ht = $total_tva = $total_ttc = 0;
		while($row = $db->fetch_object($resql))
		{
			$TAcompte['lines'][] = array(
				'rowid' => $row->rowid
				,'amount_ht' => $row->amount_ht
				,'amount_tva' => $row->amount_tva
				,'amount_ttc' => $row->amount_ttc
			);
			
			$total_ht += $row->amount_ht;
			$total_tva += $row->amount_tva;
			$total_ttc += $row->amount_ttc;
		}

		$TAcompte['total']['ht'] = $total_ht;
		$TAcompte['total']['tva'] = $total_tva;
		$TAcompte['total']['ttc'] = $total_ttc;
	}
	
	// New : calcul du reste à payer sur la facture
	$fac->remain_to_pay = $fac->total_ttc;
	if(!empty($TPaiement['total']['total_ttc'])) $fac->remain_to_pay -= $TPaiement['total']['total_ttc'];
	if(!empty($TAcompte['total']['ttc'])) $fac->remain_to_pay -= $TAcompte['total']['ttc'];
	
	foreach($fac->lines as $ligne) {
		
		if(class_exists('DaoMilestone')) {
			$milestone = new DaoMilestone($db);
			$milestone->fetch($ligne->rowid,"facture");
		}
		
		$ligneArray = TODTDocs::asArray($ligne);
		//var_dump($ligneArray['desc']);
		if(class_exists('TTarifFacturedet')) {
			
			$TTarifFacturedet = new TTarifFacturedet;
			$TTarifFacturedet->load($ATMdb,$ligne->rowid);

			if(!empty($TTarifFacturedet->tarif_poids)) $ligneArray['tarif_poids'] = $TTarifFacturedet->tarif_poids;
			else $ligneArray['tarif_poids'] = "";
			if(!empty($TTarifFacturedet->poids)){
				switch ($TTarifFacturedet->poids) {
					case -9:
						$ligneArray['poids'] = "µg";
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
					default:
						$ligneArray['poids'] = "";
						break;
				}
			}
			else {
				$ligneArray['poids'] = "";
			}		
			$ligneArray['poids'] = utf8_decode($ligneArray['poids']);
		}
		
		if($conf->maccaferri->enabled){
			$resql = $db->query("SELECT fdet.devise_pu as devise_pu, fdet.devise_mt_ligne as devise_mt_ligne, p.product_unit
							 FROM ".MAIN_DB_PREFIX."facturedet as fdet
							 LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = fdet.fk_product)
							 WHERE fdet.rowid = ".$ligne->rowid);

			$res = $db->fetch_object($resql);
			
			$ligneArray['devise_pu'] = (empty($res->devise_pu)) ? $ligneArray['subprice'] : $res->devise_pu;
			$ligneArray['devise_mt_ligne'] = (empty($res->devise_mt_ligne)) ? $ligneArray['total_ht'] : $res->devise_mt_ligne;
			$ligneArray['unite'] = (empty($res->product_unit)) ? '' : $res->product_unit;
		}
		
		//if(empty($ligneArray['product_label'])) $ligneArray['product_label'] = $ligneArray['description'];
		
		if(empty($ligneArray['product_ref'])) $ligneArray['product_ref'] = '';
		if($ligneArray['remise_percent'] == 0) $ligneArray['remise_percent'] = '';
		if(empty($ligneArray['price'])) $ligneArray['price'] = $ligneArray['subprice']*(1-($ligneArray['remise_percent']/100));
		
		if(empty($ligneArray['desc']) && $ligne->product_type == 9){
			$ligneArray['desc'] = html_entity_decode(htmlentities($milestone->label,ENT_QUOTES,"UTF-8"));
		}
		elseif($ligne->fk_product != 0){
			if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
			{
				$outputlangs = $langs;
				$newlang='';
				if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
				if (empty($newlang)) $newlang=$fac->client->default_lang;
				if (! empty($newlang))
				{
					$outputlangs = new Translate("",$conf);
					$outputlangs->setDefaultLang($newlang);
				}
				
				$prod = new Product($db);
				$prod->fetch($ligne->fk_product);
				
				$ligneArray['desc'] = (! empty($prod->multilangs[$outputlangs->defaultlang]["description"])) ? str_replace($prod->multilangs[$langs->defaultlang]["description"],$prod->multilangs[$outputlangs->defaultlang]["description"],$ligne->desc) : $ligne->desc;
				if($ligneArray['desc'] == $ligneArray['product_label']) $ligneArray['desc'] = '';
				if(! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) $ligneArray['product_label'] = $prod->multilangs[$outputlangs->defaultlang]["label"];
				$ligneArray['product_label'] = utf8_decode($ligneArray['product_label']);
				$ligneArray['desc'] = utf8_decode($ligneArray['desc']);
			}

			if(!empty($conf->global->ODTDOCS_LOAD_PRODUCT_IN_LINES)) {
				$prod = new Product($db);
				$prod->fetch($ligne->fk_product);
				$prod->fetch_optionals($ligne->fk_product);
				$ligneArray['product'] = $prod;
			}
		}
		if(!empty($prod->customcode) && !empty($conf->global->ODTDOCS_ADD_CODE_DOUANE_ON_LINES) ) $ligneArray['product_label'] .= "\n(Code douane : ".$prod->customcode.")";
		$tableau[]=$ligneArray;
	}
	
	$contact = TODTDocs::getContact($db, $fac, $societe);
	if(isset($contact['BILLING'])) {
		if ($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) {
			$societe->name = $contact['BILLING']['societe'];	
			$societe->nom = $societe->name;
		}
		
		if($contact['BILLING']['address'] != '') {
			$societe->address = $contact['BILLING']['address'];
			$societe->zip = $contact['BILLING']['cp'];
			$societe->town = $contact['BILLING']['ville'];
			$societe->country = $contact['BILLING']['pays'];
		}
	}
	
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
	
	$TVA = TODTDocs::getTVA($fac);
	
	//Condition de règlement
	$resql = $db->query('SELECT libelle_facture FROM '.MAIN_DB_PREFIX."c_payment_term WHERE rowid = ".$fac->cond_reglement_id);
	$res = $db->fetch_object($resql);
	$contact['reglement'] = $res->libelle_facture;	
	//Mode de règlement
	$resql = $db->query('SELECT libelle FROM '.MAIN_DB_PREFIX."c_paiement WHERE id = ".$fac->mode_reglement_id);
	$res = $db->fetch_object($resql);
	$contact['mode_reglement'] = $res->libelle;
	
	
	/*echo '<pre>';
	print_r($societe);
	echo '</pre>';exit;*/
	$generatedfilename = dol_sanitizeFileName($fac->ref).'-'.$_REQUEST['modele'];
	if($conf->global->ODTDOCS_FILE_NAME_AS_OBJECT_REF) {
		$generatedfilename = dol_sanitizeFileName($fac->ref);
	}
	$fOut = $conf->facture->dir_output.'/'. dol_sanitizeFileName($fac->ref).'/'.$generatedfilename;

	$fac->note_public = TODTDocs::htmlToUTFAndPreOdf($fac->note_public);
	
	if(is_array($fac->linkedObjects['commande'])){
		$TKeys = array_keys($fac->linkedObjects['commande']);
		$fac->linkedObjects['commande'][$TKeys['0']]->date_commande = date("d/m/Y",$fac->linkedObjects['commande']['0']->date_commande);
	}
	
	$societe->country = strtr($societe->country, array("'"=>' '));
	
@	TODTDocs::makeDocTBS(
		'facture'
		, $_REQUEST['modele']
		,array('TPaiementLines' => $TPaiement['lines'], 'TPaiementTot' => $TPaiement['total'], 'TAcompteLines' => $TAcompte['lines'], 'TAcompteTot' => $TAcompte['total'], 'doc'=>$fac, 'societe'=>$societe, 'extrafields'=>$TExtrafields, 'projet'=>$projet, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau, 'contact'=>$contact, 'compte'=>$TCompte[$_REQUEST['account']] ,'linkedObjects'=>$fac->linkedObjects,'autre'=>$autre,'tva'=>$TVA)
		, $fOut
		, $conf->entity
		,isset($_REQUEST['btgenPDF'])
		,$_REQUEST['lang_id']
		,array('orders', 'odtdocs@odtdocs','main','dict','products','sendings','bills','companies','propal','deliveries','banks')
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


?>Modèle* à utiliser <?php

TODTDocs::combo('facture', 'modele',GETPOST('modele'), $conf->entity);
//print_r($societe);
TODTDocs::comboLang($db, $societe->default_lang);

	if(!empty($TCompte)) {
		
		?>
		- Rib du compte à afficher <select name="account" class="flat"><?php
		
			foreach($TCompte as $compte) {
				
					?><option value="<?php echo $compte->rowid; ?>" <?php echo (isset($_REQUEST['account']) && $_REQUEST['account']==$compte->rowid) ? 'SELECTED' : '' ; ?>><?php echo $compte->label; ?></option><?php	
				
			}
			
			?></select><?php
	}
?>
 <input type="submit" value="Générer" class="button" name="btgen" /> <input type="submit" name="btgenPDF" id="btgenPDF" value="Générer en PDF" class="button" />

<br/><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?php
	
TODTDocs::show_docs($db, $conf,$fac, $langs,'facture');


?>
</td></tr></table>
</form>

<?php
print '</div>';
$db->close();

llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.34 $');


