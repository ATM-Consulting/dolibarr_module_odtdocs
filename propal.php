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
require_once(DOL_DOCUMENT_ROOT."/core/lib/propal.lib.php");
dol_include_once("/tarif/class/tarif.class.php");
dol_include_once('/odtdocs/lib/odtdocs.lib.php');
if (!empty($conf->milestone->enabled)) dol_include_once("/milestone/class/dao_milestone.class.php");
dol_include_once('/projet/class/project.class.php');

global $db, $langs;
$langs->load('orders');
$langs->load('sendings');
$langs->load('bills');
$langs->load('companies');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');
$langs->load('odtdocs@odtdocs');
$dolversion = (float) DOL_VERSION;

$id = isset($_REQUEST["id"])?$_REQUEST["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'propale', $id, 'propal');

/*
 *	View
 */
 
/*echo '<pre>';
print_r($db);
echo '</pre>';*/

llxHeader();

$ATMdb = new TPDOdb;

$propal = new Propal($db);
$propal->fetch($_REQUEST["id"]);
$propal->fetch_optionals($propal->id); // Compatibility
$propal->fetchObjectLinked();

foreach($propal as $k=>&$v) {
	if(!is_object($v) && !is_array($v)) $v = dol_string_nohtmltag($v);
}

$societe = new Societe($db);
$societe->fetch($propal->socid);

$head = propal_prepare_head($propal);
dol_fiche_head($head, 'tabEditions1', $langs->trans('Proposal'), 0, 'propal');

$action='builddoc';
$hookmanager->initHooks(array('propalcard'));
$parameters=array('socid'=>$propal->socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$propal,$action);    // Note that $action and $object may have been modified by some hooks


require('./class/odt.class.php');
require('./class/atm.doctbs.class.php');

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {
	//print_r($propal);
	$Ttva = array();
	$tableau=array();
	$TExtrafields = array();
	$projet = new Project($db);
	if($propal->fk_project) $projet->fetch($propal->fk_project);
	
	if(!empty($propal->array_options)) {
		$TExtrafields = array_merge(get_tab_extrafields($propal->array_options, 'propal'), get_tab_extrafields_evo($propal));
	}
	
	foreach($propal->lines as $ligne) {
		
		if(!empty($ligne->fk_product)) {
			// Chargement du produit correspondant
			$product = new Product($db);
			$product->fetch($ligne->fk_product);
			
			// Chemin des photos du produit
			if($dolversion >= 3.8) {
				$pdir = get_exdir($product->id,2,0,0,$product,'product').dol_sanitizeFileName($product->ref).'/';
			} else {
				$pdir = get_exdir($product->id,2) . $product->id ."/photos/";
			}
			$sdir = $conf->product->multidir_output[$product->entity];
			$dir = $sdir . '/'. $pdir;
			
			$photo_urlAbs = '';
			if($product->is_photo_available($sdir)) {
				$photo = $product->liste_photos($dir);
				if(!empty($photo[0])) $photo_urlAbs = $sdir . '/'. $pdir . $photo[0]['photo']; 
				//$photo_urlRel = DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$conf->entity.'&file='.urlencode($pdir.$photo[0]['photo']);
			}
			
			$ligne->product_photo = $photo_urlAbs;
		}
		
		// Gestion label et description uniformise les donées
		$ligne->label = html_entity_decode($ligne->label, ENT_QUOTES);
		$ligne->desc = html_entity_decode($ligne->desc, ENT_QUOTES);
		
		$ligne->desc = dol_string_nohtmltag($ligne->desc);
		
		$ligneArray = TODTDocs::asArray($ligne);
		
		if (!empty($ligne->fk_unit) && method_exists($ligne, 'getLabelOfUnit')) $ligneArray['unit_label'] = $ligne->getLabelOfUnit('short');
		
		if(class_exists('TTarifPropaldet')) {
			$TTarifPropaldet = new TTarifPropaldet;
			$TTarifPropaldet->load($ATMdb,$ligne->rowid);
			
			if(!empty($TTarifPropaldet->tarif_poids)) $ligneArray['tarif_poids'] = $TTarifPropaldet->tarif_poids;
			else $ligneArray['tarif_poids'] = "";
			if(!empty($TTarifPropaldet->poids)){
				switch ($TTarifPropaldet->poids) {
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
		
		if(class_exists('DaoMilestone')) {
			$milestone = new DaoMilestone($db);
			$milestone->fetch($ligne->rowid,"propal");
		}
		
		if($conf->maccaferri->enabled){
			$resql = $db->query("SELECT ppdet.devise_pu as devise_pu, ppdet.devise_mt_ligne as devise_mt_ligne, p.product_unit
							 FROM ".MAIN_DB_PREFIX."propaldet as ppdet
							 LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = ppdet.fk_product)
							 WHERE ppdet.rowid = ".$ligne->rowid);
		
			$res = $db->fetch_object($resql);
			
			$ligneArray['devise_pu'] = (empty($res->devise_pu)) ? $ligneArray['subprice'] : $res->devise_pu;
			$ligneArray['devise_mt_ligne'] = (empty($res->devise_mt_ligne)) ? $ligneArray['total_ht'] : $res->devise_mt_ligne;
			$ligneArray['unite'] = (empty($res->product_unit)) ? '' : $res->product_unit;
		}
		
		/*if(empty($ligneArray['product_label'])) { // Les lignes libres n'ont pas de libellé mais seulement description
			$ligneArray['product_label'] = $ligneArray['description'];
			$ligneArray['description'] = '';
		}*/
		if(empty($ligneArray['desc']) && $ligne->product_type == 9){
			if(!empty($milestone)) $ligneArray['desc'] = html_entity_decode(htmlentities($milestone->label,ENT_QUOTES,"UTF-8"));
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
		}
		
		if(empty($ligneArray['label'])) $ligneArray['label'] = $ligneArray['desc'];
		if(empty($ligneArray['product_ref'])) $ligneArray['product_ref'] = '';
		if($ligneArray['remise_percent'] == 0) $ligneArray['remise_percent'] = '';
		if(empty($ligneArray['price'])) $ligneArray['price'] = $ligneArray['subprice'] * (1-($ligneArray['remise_percent']/100));
		
		if(!empty($conf->global->ODTDOCS_LOAD_PRODUCT_IN_LINES) && $ligne->fk_product>0) {
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
		
		// Check if line is subtotal or title
		if ($conf->subtotal->enabled) {
			if (! class_exists('TSubtotal')) {
				dol_include_once('/subtotal/class/subtotal.class.php');
			}
			if (TSubtotal::isSubtotal($ligne)){
				$ligneArray['titre'] = 2;
			}
			if (TSubtotal::isTitle($ligne)) {			
				$ligneArray['total_ht'] = '';
			}	
		}
		
		$tableau[]=$ligneArray;
		$Ttva[$ligneArray['tva_tx']] += $ligneArray['total_tva'];
		}

	$contact = TODTDocs::getContact($db, $propal, $societe);
	if(isset($contact['CUSTOMER'])) {
		$societe->name = $contact['CUSTOMER']['societe'];
		if($contact['CUSTOMER']['address'] != '') {
			$societe->address = $contact['CUSTOMER']['address'];
			$societe->cp = $contact['CUSTOMER']['cp'];
			$societe->ville = $contact['CUSTOMER']['ville'];
			$societe->pays = $contact['CUSTOMER']['pays'];
		}
	}
	
	/*echo '<pre>';
	print_r($tableau);
	echo '</pre>';*/
	
	if($conf->maccaferri->enabled){
		$resql = $db->query("SELECT c.name as devise, i.code, i.libelle, p.ref, p.title
						FROM ".MAIN_DB_PREFIX."currency as c
						LEFT JOIN ".MAIN_DB_PREFIX."propal as pp ON (pp.devise_code = c.code)
						LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON (p.rowid = pp.fk_projet)
						LEFT JOIN ".MAIN_DB_PREFIX."c_incoterms as i ON (i.rowid = pp.fk_incoterms)
						WHERE pp.rowid = ".$propal->id);
		
		$res = $db->fetch_object($resql);
		
		$autre = array("devise"=>$res->devise,
					   "incoterm"=>$res->code." - ".$res->libelle,
					   "date_devis_fr"=>date('d/m/Y'),
					   "fin_validite"=>date('d/m/Y',$propal->fin_validite),
					   "projet"=>$res->ref." ".$res->title);
	}
	elseif($conf->clisynovo->enabled){
		dol_include_once('/clisynovo/lib/clisynovo.lib.php');
		$autre = getDataPropalForODTDoc($propal);
	}
	else{
		$autre = array();
	}

	$TVA = TODTDocs::getTVA($propal);
	
	$generatedfilename = dol_sanitizeFileName($propal->ref).'-'.$_REQUEST['modele'];
	if($conf->global->ODTDOCS_FILE_NAME_AS_OBJECT_REF) {
		$generatedfilename = dol_sanitizeFileName($propal->ref);
	}
	$fOut = $fOut =  $conf->propal->dir_output.'/'. dol_sanitizeFileName($propal->ref).'/'.$generatedfilename;
//var_dump($propal->projet->ref,$propal->projet);
	$societe->country = strtr($societe->country, array("'"=>' '));
	if(!empty($projet->title)) {
		$projet->title = ((mb_detect_encoding($projet->title) === 'UTF-8') ? utf8_decode($projet->title) : $projet->title);
	}

	$TAutre = array();
	$parameters = array(
		'currentContext' => 'propalOdtDoc'
		,'projet' => &$projet
		,'extrafields'=>&$TExtrafields
		,'societe'=>&$societe
		,'mysoc'=>&$mysoc
		,'conf'=>&$conf
		,'tableau'=>&$tableau
		,'contact'=>&$contact
		,'linkedObjects'=>&$propal->linkedObjects
		,'autre'=>&$autre
		,'TAutre'=>&$TAutre
		,'tva'=>&$TVA
	);
	
	$reshook=$hookmanager->executeHooks('beforeGenerateOdtDoc',$parameters,$propal,$action);
	TODTDocs::makeDocTBS(
		'propal'
		, $_REQUEST['modele']
		,array('doc'=>$propal, 'projet'=>$projet, 'extrafields'=>$TExtrafields, 'societe'=>$societe, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau, 'contact'=>$contact,'linkedObjects'=>$propal->linkedObjects,'autre'=>$autre,'tva'=>$TVA, 'TAutre'=>$TAutre)
		,$fOut
		, $conf->entity
		,isset($_REQUEST['btgenPDF'])
		,$_REQUEST['lang_id']
		,array('orders', 'odtdocs@odtdocs','main','dict','products','sendings','bills','companies','propal','deliveries')
	);
	
	$reshook=$hookmanager->executeHooks('afterGenerateOdtDoc',$parameters,$propal,$action);
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
TODTDocs::combo('propal', 'modele',GETPOST('modele'), $conf->entity);
TODTDocs::comboLang($db, $societe->default_lang);

if (!empty($conf->global->ODTDOCS_CAN_GENERATE_ODT))
{
	print '<input type="submit" value="Générer" class="button" name="btgen" />&nbsp;';
}
if (!empty($conf->global->ODTDOCS_CAN_GENERATE_PDF))
{
	print '<input type="submit" id="btgenPDF"  name="btgenPDF" value="Générer en PDF" class="button" />';
}

?><br><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?php
	
TODTDocs::show_docs($db, $conf,$propal, $langs);


?>
</td></tr></table>
</form>

<?php
print '</div>';
$db->close();

llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.34 $');


?>
