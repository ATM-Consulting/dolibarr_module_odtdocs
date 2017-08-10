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

dol_include_once("/tarif/class/tarif.class.php");
dol_include_once("/asset/class/asset.class.php");
dol_include_once("/projet/class/project.class.php");
dol_include_once("/odtdocs/lib/odtdocs.lib.php");
if (!empty($conf->milestone->enabled)) dol_include_once("/milestone/class/dao_milestone.class.php");

global $db, $langs, $user;
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
$ATMdb = new TPDOdb;
$id = isset($_REQUEST["id"])?$_REQUEST["id"]:'';

$commande = new commande($db);
$commande->fetch($_REQUEST["id"]);
$commande->info($_REQUEST["id"]);
$commande->fetchObjectLinked();

foreach($commande as $k=>&$v) {
	if(!is_object($v) && !is_array($v)) $v = dol_string_nohtmltag($v);
}

if($commande->fk_project) {
	$projet = new Project($db);
	$projet->fetch($commande->fk_project);
}

$societe = new Societe($db, $commande->socid);
$societe->fetch($commande->socid);

$action='builddoc';
$hookmanager->initHooks(array('ordercard'));
$parameters=array('socid'=>$commande->socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$commande,$action);

$head = commande_prepare_head($commande);
dol_fiche_head($head, 'tabEditions3', $langs->trans("CustomerOrder"), 0, 'order');

require('./class/odt.class.php');

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {
	//print_r($propal);
	$Ttva = array();
	$tableau=array();
	$TExtrafields = array();
	
	if(!empty($commande->array_options)) {
		$TExtrafields = array_merge(get_tab_extrafields($commande->array_options, 'commande'), get_tab_extrafields_evo($commande));
	}
	
	foreach($commande->lines as $ligne) {
		$ligneArray = TODTDocs::asArray($ligne);	
		
		/*echo '<pre>';
		print_r($ligne);
		echo '</pre>';exit;*/
		
		if(class_exists('DaoMilestone')) {	
			$milestone = new DaoMilestone($db);
			$milestone->fetch($ligne->rowid,"commande");
		}
	
		if (!empty($ligne->fk_unit) && method_exists($ligne, 'getLabelOfUnit')) $ligneArray['unit_label'] = $ligne->getLabelOfUnit('short');
		
		if(class_exists('TTarifCommandedet')) {	
			$TTarifCommandedet = new TTarifCommandedet;
			$TTarifCommandedet->load($ATMdb,$ligne->rowid);
			
			if(!empty($TTarifCommandedet->tarif_poids)) $ligneArray['tarif_poids'] = $TTarifCommandedet->tarif_poids;
			else $ligneArray['tarif_poids'] = "";
			if(!empty($TTarifCommandedet->poids)){
				switch ($TTarifCommandedet->poids) {
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
		
		if(class_exists('TAsset')) {
			$resql = $db->query("SELECT asset_lot
							 FROM ".MAIN_DB_PREFIX."commandedet as cmdet
							 WHERE cmdet.rowid = ".$ligne->id);
			if($resql){	
				$res = $db->fetch_object($resql);
				if(!empty($res->asset_lot)) {
					$asset = new TAsset;
					$asset->load($ATMdb,$res->asset_lot);
					$ligneArray['asset'] = $asset;
				}
			}
		}

		if($conf->maccaferri->enabled){
			$resql = $db->query("SELECT cmdet.devise_pu as devise_pu, cmdet.devise_mt_ligne as devise_mt_ligne, p.product_unit
							 FROM ".MAIN_DB_PREFIX."commandedet as cmdet
							 LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = cmdet.fk_product)
							 WHERE cmdet.rowid = ".$ligne->id);
		
			$res = $db->fetch_object($resql);
			
			$ligneArray['devise_pu'] = (empty($res->devise_pu)) ? $ligneArray['subprice'] : $res->devise_pu;
			$ligneArray['devise_mt_ligne'] = (empty($res->devise_mt_ligne)) ? $ligneArray['total_ht'] : $res->devise_mt_ligne;
			$ligneArray['unite'] = (empty($res->product_unit)) ? '' : $res->product_unit;
		}
		
		//print_r($TTarifCommandedet);
		if(empty($ligneArray['desc']) && $ligne->product_type == 9){
			if(!empty($milestone))	$ligneArray['desc'] = html_entity_decode(htmlentities($milestone->label,ENT_QUOTES,"UTF-8"));
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
				
				//echo $prod->multilangs[$outputlangs->defaultlang]["label"];exit;
				
				$ligneArray['desc'] = (! empty($prod->multilangs[$outputlangs->defaultlang]["description"])) ? str_replace($prod->multilangs[$langs->defaultlang]["description"],$prod->multilangs[$outputlangs->defaultlang]["description"],$ligne->desc) : $ligne->desc;
				if($ligneArray['desc'] == $ligneArray['product_label']) $ligneArray['desc'] = '';
				if(!empty($prod->multilangs[$outputlangs->defaultlang]["label"]))
					$ligneArray['product_label'] = $prod->multilangs[$outputlangs->defaultlang]["label"];
				$ligneArray['product_label'] = utf8_decode($ligneArray['product_label']);
				$ligneArray['desc'] = utf8_decode($ligneArray['desc']);
			}
		}
		
		//echo $prod->multilangs[$outputlangs->defaultlang]["label"]; exit;
		
		/*print_r($ligneArray);*/
		if(empty($ligneArray['product_label'])) $ligneArray['product_label'] = ((mb_detect_encoding($ligneArray['desc']) === 'UTF-8') ? utf8_decode($ligneArray['desc']) : $ligneArray['desc']);
		if(empty($ligneArray['product_ref'])) $ligneArray['product_ref'] = '';
		if($ligneArray['remise_percent'] == 0) $ligneArray['remise_percent'] = '';
		if(empty($ligneArray['subprice'])) $ligneArray['subprice'] = 0;
		
		/*echo '<pre>';
		print_r($ligneArray);
		echo '</pre>';*/
		
		if(!empty($conf->global->ODTDOCS_LOAD_PRODUCT_IN_LINES)) {
			$prod = new Product($db);
			$prod->fetch($ligne->fk_product);
			$prod->fetch_optionals($ligne->fk_product);
			
			// Pays d'origine
			if((float)DOL_VERSION > 3.6) {
				dol_include_once('/core/class/ccountry.class.php');
				$p = new Ccountry($db);
				$p->fetch($prod->country_id);
				$prod->pays_origine = ($p->code && $langs->transnoentitiesnoconv("Country".$p->code)!="Country".$p->code?$langs->transnoentitiesnoconv("Country".$p->code):($p->label!='-'?$p->label:''));
			} else {
				dol_include_once('/core/class/cpays.class.php');
				$p = new Cpays($db);
				$p->fetch($prod->country_id);
				$prod->pays_origine = ($p->code && $langs->transnoentitiesnoconv("Country".$p->code)!="Country".$p->code?$langs->transnoentitiesnoconv("Country".$p->code):($p->label!='-'?$p->label:''));
			}
			
			switch ($prod->weight_units) {
				case -6:
					$poids = "mg";
					break;
				case -3:
					$poids = "g";
					break;
				case 0:
					$poids = "kg";
					break;
				case 3:
					$poids = "tonnes";
					break;
				case 99:
					$poids = "livre";
					break;
				default:
					$poids = "";
					break;
			}

			$prod->unite = utf8_decode($poids);
			
			$ligneArray['product'] = $prod;
		}
		
		$tableau[]=$ligneArray;
		$Ttva[$ligneArray['tva_tx']] += $ligneArray['total_tva'];
	}
	
	$contact = TODTDocs::getContact($db, $commande, $societe);
	if(isset($contact['CUSTOMER'])) {
		$societe->name = $contact['CUSTOMER']['societe'];
		if($contact['CUSTOMER']['address'] != '') {
			$societe->address = $contact['CUSTOMER']['address'];
			$societe->zip = $contact['CUSTOMER']['cp'];
			$societe->town = $contact['CUSTOMER']['ville'];
			$societe->country = $contact['CUSTOMER']['pays'];
		}
	}
	
	/*echo '<pre>';
	print_r($commande);
	echo '</pre>';*/
	if($conf->maccaferri->enabled){
		$resql = $db->query("SELECT c.name as devise, i.code, i.libelle, p.ref, p.title
						FROM ".MAIN_DB_PREFIX."currency as c
						LEFT JOIN ".MAIN_DB_PREFIX."commande as cmd ON (cmd.devise_code = c.code)
						LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON (p.rowid = cmd.fk_projet)
						LEFT JOIN ".MAIN_DB_PREFIX."c_incoterms as i ON (i.rowid = cmd.fk_incoterms)
						WHERE cmd.rowid = ".$commande->id);
		
		$res = $db->fetch_object($resql);
		
		$autre = array("devise"=>$res->devise,
					   "incoterm"=>$res->code." - ".$res->libelle,
					   "date_commande_fr"=>date('d/m/Y'),
					   "date_livraison"=>date('d/m/Y',$commande->date_livraison),
					   "projet"=>$res->ref." ".$res->title,
					   "TVA"=>$Ttva);
	}
	else{
		$autre = array(
				'date_commande' => date("d/m/Y", $commande->date),
				'date_jour' => date("d/m/Y H:i:s"),
				'ref' => $commande->ref
			);
	}
	
	$TVA = TODTDocs::getTVA($commande);
	
	$generatedfilename = dol_sanitizeFileName($commande->ref).'-'.$_REQUEST['modele'];
	if($conf->global->ODTDOCS_FILE_NAME_AS_OBJECT_REF) {
		$generatedfilename = dol_sanitizeFileName($commande->ref);
	}
	$fOut = $conf->commande->dir_output.'/'. dol_sanitizeFileName($commande->ref).'/'.$generatedfilename;
	$societe->country = strtr($societe->country, array("'"=>' '));
	if(!empty($projet->title)) {
		$projet->title = ((mb_detect_encoding($projet->title) === 'UTF-8') ? utf8_decode($projet->title) : $projet->title);
	}
	
	$reshook=$hookmanager->executeHooks('beforeGenerateOdtDoc',$commande,$propal,$action);
	TODTDocs::makeDocTBS(
		'commande'
		, $_REQUEST['modele']
		,array('doc'=>$commande, 'societe'=>$societe, 'extrafields'=>$TExtrafields, 'projet'=>$projet, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau, 'contact'=>$contact, 'linkedObjects'=>$commande->linkedObjects,'autre'=>$autre,'tva'=>$TVA)
		, $fOut
		, $conf->entity
		,isset($_REQUEST['btgenPDF'])
		,$_REQUEST['lang_id']
		,array('orders', 'odtdocs@odtdocs','main','dict','products','sendings','bills','companies','propal','deliveries')
	);
	$reshook=$hookmanager->executeHooks('afterGenerateOdtDoc',$parameters,$commande,$action);
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
TODTDocs::combo('commande', 'modele',GETPOST('modele'), $conf->entity);
TODTDocs::comboLang($db, $societe->default_lang);

?> <input type="submit" value="Générer" class="button" name="btgen" /> <input type="submit" name="btgenPDF" id="btgenPDF" value="Générer en PDF" class="button" /><?php
?>
<br/><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?php
	
TODTDocs::show_docs($db, $conf,$commande, $langs,'commande');


?>
</td></tr></table>
</form>

<?php
print '</div>';
$db->close();

llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.34 $');


?>
