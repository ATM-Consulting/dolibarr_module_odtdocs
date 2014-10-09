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

require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/extrafields.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");

require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php");
dol_include_once("/custom/dispatch/class/dispatchdetail.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/expedition/modules_expedition.php");
dol_include_once("/custom/asset/class/asset.class.php");
if ($conf->product->enabled || $conf->service->enabled)  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->stock->enabled)    require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");

if(!$conf->dispatch->enabled) {
	header('location:expedition.php?id='.GETPOST('id'));
}


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
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

/*
 *	View
 */

llxHeader();


function __poids_unite($unite){
	switch ($unite) {
		case -9:
			return('µg');
			break;
		case -6:
			return('mg');
			break;
		case -3:
			return('g');
			break;
		case 0:
			return('kg');
			break;
	}

}

$ATMdb = new TPDOdb;

$expedition = new Expedition($db);
$expedition->fetch($_REQUEST['id']);
$expedition->fetch_lines();

/*echo '<pre>';
print_r($expedition);
echo '</pre>';exit;*/

$commande = new Commande($db);
$commande->fetch($expedition->origin_id);
$commande->fetch_lines();

$societe = new Societe($db);
$societe->fetch($commande->socid);

$head = shipping_prepare_head($expedition);
dol_fiche_head($head, 'tabEditions6', $langs->trans("Sending"), 0, 'sending');

require('./class/odt.class.php');
require('./class/atm.doctbs.class.php');

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {
	
	$fOut =  $conf->expedition->dir_output . '/sending/'. dol_sanitizeFileName($expedition->ref).'/'.dol_sanitizeFileName($expedition->ref).'-'.$_REQUEST['modele']/*. TODTDocs::_ext( $_REQUEST['modele'])*/;
	
	$tableau=array();
	
	//Parcours des lignes de la commande
	foreach($expedition->lines as $eligne) {
		
		$Tid_eligne = TRequeteCore::get_id_from_what_you_want($ATMdb,MAIN_DB_PREFIX."expeditiondet",array('fk_expedition'=>$expedition->id,'fk_origin_line'=>$eligne->origin_line_id),"rowid");
		$id_eligne = (int)$Tid_eligne[0];
		
		$expeditiondet_asset=null;
		if($conf->dispatch->enabled) {
			$expeditiondet_asset = new TDispatchDetail;
			$expeditiondet_asset->load($ATMdb,$id_eligne);
			//Chargement des ligne d'équipement associé à la ligne de commande
			$expeditiondet_asset->loadLines($ATMdb,$id_eligne);
			
			
			foreach($expeditiondet_asset->lines as $dligne){
				/*echo '<pre>';
				print_r($dligne);
				echo '</pre>';exit;*/
				
				$ligneArray = TODTDocs::asArray($dligne);
				
				//Chargement de l'équipement lié à la ligne d'expédition
				$TAsset = new TAsset;
				$TAsset->load($ATMdb,$dligne->fk_asset);
				
				//Chargement du produit lié à l'équipement
				$product = new Product($db);
				$product->fetch($eligne->fk_product);
				
				$ligneArray['product_ref'] = $product->ref;
				$ligneArray['product_label'] = $product->label;
				
				if($eligne->fk_product != 0){
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
						$prod->fetch($eligne->fk_product);
						
						//echo $prod->multilangs[$outputlangs->defaultlang]["label"];exit;
						
						$ligneArray['desc'] = (! empty($prod->multilangs[$outputlangs->defaultlang]["description"])) ? str_replace($prod->multilangs[$langs->defaultlang]["description"],$prod->multilangs[$outputlangs->defaultlang]["description"],$eligne->product_desc) : $eligne->product_desc;
						if($ligneArray['desc'] == $ligneArray['product_label']) $ligneArray['desc'] = '';
						if(!empty($prod->multilangs[$outputlangs->defaultlang]["label"]))
							$ligneArray['product_label'] = $prod->multilangs[$outputlangs->defaultlang]["label"];
						$ligneArray['product_label'] = utf8_decode($ligneArray['product_label']);
						$ligneArray['desc'] = utf8_decode($ligneArray['desc']);
					}
				}
				
				$ligneArray['asset_lot'] = $TAsset->lot_number;
				$ligneArray['weight_unit'] = utf8_decode(__poids_unite($ligneArray['weight_unit']));
				$ligneArray['tare_unit'] = utf8_decode(__poids_unite($ligneArray['tare_unit']));
				$ligneArray['weight_reel_unit'] = utf8_decode(__poids_unite($ligneArray['weight_reel_unit']));
				
				$tableau[]=$ligneArray;
			}
			
		}
		else {
			
			
		}
		
		
		
	}
	
	/*echo '<pre>';
	print_r($tableau);
	echo '</pre>';exit;*/
	
	$contact = TODTDocs::getContact($db, $commande, $societe);
	if(isset($contact['SHIPPING'])) {
		$societe->name = $contact['SHIPPING']['societe'];
		if($contact['SHIPPING']['address'] != '') {
			$societe->address = $contact['SHIPPING']['address'];
			$societe->zip = $contact['SHIPPING']['cp'];
			$societe->town = $contact['SHIPPING']['ville'];
			$societe->country = $contact['SHIPPING']['pays'];
		}
	}
	
	$sql = "SELECT f.facnumber 
			FROM ".MAIN_DB_PREFIX."facture as f
			LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee ON (ee.fk_target = f.rowid)
			WHERE ee.targettype = 'facture' AND sourcetype = 'shipping'
			AND ee.source = ".$expedition->id."
			LIMIT 1";
			
	$ATMdb->Execute($sql);
	if($ATMdb->Get_line()){
		$ref_facture = $ATMdb->Get_field('facnumber');
	}
	
	
	$autre = array(
		'ref' => $expedition->ref,
		'date_jour' => date("d/m/Y H:i:s"),
		'commande' => $commande->ref,
		'facture' => $ref_facture
		);
		
	$code = $langs->getLabelFromKey($db,$expedition->shipping_method_id,'c_shipment_mode','rowid','code');
	$expedition->shipping_method_label = $langs->trans("SendingMethod".strtoupper($code));
	
	/*echo '<pre>';
	print_r($expedition);
	echo '</pre>';*/
	
	TODTDocs::makeDocTBS(
		'expedition'
		, $_REQUEST['modele']
		,array('doc'=>$commande, 'societe'=>$societe, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau, 'contact'=>$contact, 'linkedObjects'=>$commande->linkedObjects, 'dispatch'=>$expedition, 'autre'=>$autre)
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
	<input type="hidden" name="fk_commande" value="<?=$commande->id ?>" />
	<input type="hidden" name="action" value="GENODT" />
<table width="100%"><tr><td>
<?


?>Modèle à utiliser* <?
TODTDocs::combo('expedition', 'modele',GETPOST('modele'), $conf->entity);
TODTDocs::comboLang($db, $societe->default_lang);

?> <input type="submit" value="Générer" class="button" name="btgen" /> <input type="submit" id="btgenPDF"  name="btgenPDF" value="Générer en PDF" class="button" /><?

?><br><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?
	
TODTDocs::show_docs($db, $conf,$expedition, $langs, 'expedition');


?>
</td></tr></table>
</form>

<?
print '</div>';
$db->close();

llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.34 $');


?>
