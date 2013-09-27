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
dol_include_once(DOL_DOCUMENT_ROOT."/custom/tarif/class/tarif.class.php");
dol_include_once(DOL_DOCUMENT_ROOT."/custom/milestone/class/dao_milestone.class.php");

global $db;
$langs->load('propal');
$langs->load('compta');

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

$ATMdb = new Tdb;

$propal = new Propal($db);
$propal->fetch($_REQUEST["id"]);
$propal->fetchObjectLinked();

$societe = new Societe($db);
$societe->fetch($propal->socid);

$head = propal_prepare_head($propal);
dol_fiche_head($head, 'tabEditions1', $langs->trans('Proposal'), 0, 'propal');

require('./class/odt.class.php');
require('./class/atm.doctbs.class.php');

if(isset($_REQUEST['action']) && $_REQUEST['action']=='GENODT') {
	//print_r($propal);
	
	$tableau=array();
	
	foreach($propal->lines as $ligne) {
		if(!empty($ligne->fk_product)) {
			// Chargement du produit correspondant
			$product = new Product($db);
			$product->fetch($ligne->fk_product);
			
			// Chemin des photos du produit
			$pdir = get_exdir($product->id,2) . $product->id ."/photos/";
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
		
		if(class_exists('TTarifPropaldet')) {
			$TTarifPropaldet = new TTarifPropaldet;
			$TTarifPropaldet->load($ATMdb,$ligne->rowid);
			
			if(empty($ligneArray['tarif_poids'])) $ligneArray['tarif_poids'] = $TTarifPropaldet->tarif_poids;
			if(empty($ligneArray['poids'])){
				switch ($TTarifPropaldet->poids) {
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
		
		if(class_exists('DaoMilestone')) {
			$milestone = new DaoMilestone($db);
			$milestone->fetch($ligne->rowid,"propal");
		}
		
		$ligneArray = TODTDocs::asArray($ligne);
		
		if(empty($ligneArray['product_label'])) { // Les lignes libres n'ont pas de libellé mais seulement description
			$ligneArray['product_label'] = $ligneArray['description'];
			$ligneArray['description'] = '';
		}
		if(empty($ligneArray['desc']) && $ligne->product_type == 9) $ligneArray['desc'] = html_entity_decode(htmlentities($milestone->label,ENT_QUOTES,"UTF-8"));
		
		if(empty($ligneArray['product_ref'])) $ligneArray['product_ref'] = '';
		if($ligneArray['remise_percent'] == 0) $ligneArray['remise_percent'] = '';
		$tableau[]=$ligneArray;
	}

	$fOut =  $conf->propal->dir_output.'/'. dol_sanitizeFileName($propal->ref).'/'.dol_sanitizeFileName($propal->ref).'-'.$_REQUEST['modele']/*. TODTDocs::_ext( $_REQUEST['modele'])*/;

	$contact = TODTDocs::getContact($db, $propal, $societe);
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
	print_r($tableau);
	echo '</pre>';*/
	
	TODTDocs::makeDocTBS(
		'propal'
		, $_REQUEST['modele']
		,array('doc'=>$propal, 'societe'=>$societe, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau, 'contact'=>$contact,'linkedObjects'=>$propal->linkedObjects)
		,$fOut
		, $conf->entity
		,isset($_REQUEST['btgenPDF'])
		,$_REQUEST['lang_id']
	);
	
	//print_r(array('doc'=>$propal, 'societe'=>$societe, 'mysoc'=>$mysoc, 'conf'=>$conf, 'tableau'=>$tableau));
	
	/*
	$dbATM=new Tdb;
	$dTBS = new TAtmDocTBS($dbATM);
	$dTBS->load_by_entity($dbATM, $conf->entity);
	
	if($dTBS->livedocx_use==1) {
		
		?><a href="<?=$fOut ?>">Télécharger au format PDF</a><?
		
	}*/
	
/*	TODTDocs::makeDocTBS(
		'propal'
		, $_REQUEST['modele']
		,array('propal'=>$propal, 'societe'=>$societe, 'mysoc'=>$mysoc)
		, $conf->propale->dir_output.'/'. dol_sanitizeFileName($propal->ref).'/'.dol_sanitizeFileName($propal->ref).'.odt'
	);*/
}

?>
<form name="genfile" method="get" action="<?=$_SERVER['PHP_SELF'] ?>">
	<input type="hidden" name="id" value="<?=$id ?>" />
	<input type="hidden" name="action" value="GENODT" />
<table width="100%"><tr><td>
<?


?>Modèle à utiliser* <?
TODTDocs::combo('propal', 'modele',GETPOST('modele'), $conf->entity);
TODTDocs::comboLang($db, $societe->default_lang);
?> <input type="submit" value="Générer" class="button" name="btgen" /> <input type="submit" id="btgenPDF"  name="btgenPDF" value="Générer en PDF" class="button" /><?

?><br><small>* parmis les formats OpenDocument (odt, ods) et Microsoft&reg; office xml (docx, xlsx)</small>
	<p><hr></p>
	<?
	
TODTDocs::show_docs($db, $conf,$propal, $langs);


?>
</td></tr></table>
</form>

<?
print '</div>';
$db->close();

llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.34 $');


?>
