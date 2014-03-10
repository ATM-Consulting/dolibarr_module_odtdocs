<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *		\version    $Id: skeleton_page.php,v 1.19 2011/07/31 22:21:57 eldy Exp $
 *		\author		Put author name here
 *		\remarks	Put here some comments
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');	// If there is no menu to show
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');	// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');		// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
include '../config.php';
// Change this following line to use the correct relative path from htdocs (do not remove DOL_DOCUMENT_ROOT)


// Get parameters
$myparam = isset($_GET["myparam"])?$_GET["myparam"]:'';

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}



/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/



llxHeader('','Gestion des équipements, à propos','');

require('../class/odt.class.php');

if(isset($_FILES['fichier'])) {
	print "Chargement du fichier : ".$_FILES['fichier']['name']."<br>";

	if(TODTDocs::validFile($_FILES['fichier']['name'])) {
		TODTDocs::addFile($_REQUEST['typeDoc'],$_FILES['fichier']['tmp_name'],$_FILES['fichier']['name'], $conf->entity);		
	}
	
}


//include '../class/atm.doctbs.class.php';

/*
$dbATM=new Tdb;
$dTBS = new TAtmDocTBS($dbATM);
$dTBS->load_by_entity($dbATM, $conf->entity);
*/
if(isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'DELETE':
			TODTDocs::delFile($_REQUEST['type'],$_REQUEST['fichier'], $conf->entity);
			
			break;
		/*case 'LIVEDOCX':
			$dTBS->id_entity = $conf->entity;
			$dTBS->set_values($_POST);
			$dTBS->save($dbATM);
			
			break;*/
	}	
	
}

//$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre('Gestion des modèles',$linkback,'setup');

$form=new TFormCore;


/*<form action="<?=$_SERVER['PHP_SELF'] ?>" name="livedocx-form" method="POST" enctype="multipart/form-data">
	<input type="hidden" name="action" value="LIVEDOCX" />
<table width="100%" class="noborder">
	<tr class="liste_titre">
		<td>Module LiveDocx</td>
		<td align="center">&nbsp;</td>
		</tr>
		<tr class="impair">
			<td valign="top">Login / Mot de passe</td>
			<td align="center">
				echo $form->combo('Utiliser le service <a href="http://www.livedocx.com/" target="_blank">LiveDocx</a>', 'livedocx_use', array(0=>'Non', 1=>'Oui'), $dTBS->livedocx_use);	
			<input type="text" name="livedocx_login" value="<?=$dTBS->livedocx_login ?>" />		
			<input type="password" name="livedocx_password" value="<?=$dTBS->livedocx_password ?>" />
			<input type="submit" name="btvalid" value="Valider" />	
			</td>
			
		</td>
	</tr>
	
</table>
</form>*/

	showFormModel('propal',$conf->entity);
	showFormModel('commande',$conf->entity);
	showFormModel('fournisseur',$conf->entity);
	showFormModel('facture',$conf->entity);
	showFormModel('company',$conf->entity);
	showFormModel('expedition',$conf->entity);


function showFormModel($typeDoc='propal', $entity = 1) {
	?><form action="<?=$_SERVER['PHP_SELF'] ?>" name="load-<?=$typeDoc ?>" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="typeDoc" value="<?=$typeDoc ?>" />
	<table width="100%" class="noborder" style="background-color: #fff;">
		<tr class="liste_titre">
			<td><? 
				switch ($typeDoc) {
					case 'propal':
						print 'Modèles Propale';
						
						break;
					
					case 'facture':
						print 'Modèles facture';
						
						break;
					
					case 'fournisseur':
						print 'Modèles  Bons de commande fournisseur';
						
						break;
					
					case 'commande':
						print 'Modèles Bons de commande';
						
						break;
						
					case 'company':
						print 'Modèles pour les tiers';
						
						break;
					
					case 'expedition':
						print 'Modèles pour les expéditions';
						break;
					
					default:
						print 'Modèles Divers';
						
						break;
				}
			
			?></td>
			<td align="center">&nbsp;</td>
			</tr>
			<tr class="impair">
				<td valign="top" width="40%">Charger un modèle de document</td>
				<td align="left">
				<input type="file" name="fichier" value="" />		
				<input type="submit" name="btload" value="Charger" />	
				</td>
				
			</td>
		</tr>
		<?
		
		$TDocs = TODTDocs::getListe($typeDoc, $entity);
		
		if(count($TDocs)>0)  {
			?>
			<tr>
				<td colspan="2"><strong>Liste des modèles chargés</strong></td>
			</tr>
			<?
			
			foreach($TDocs as $fichier) {
				?><tr>
					<td><a href="<?=dol_buildpath('/odtdocs/modele/'.$entity.'/'.$typeDoc.'/'.$fichier,1) ?>" target="_blank" style="font-weight:normal;"><?=$fichier ?></a></td>
					<td><a href="<?=$_SERVER['PHP_SELF'] ?>?action=DELETE&fichier=<?=urlencode($fichier) ?>&type=<?=$typeDoc ?>">Supprimer</a></td>
				</tr><?
			}
		}
		?>
		
	</table>
	</form>
	<br /><br />
	<?
}
?>

<table width="100%" class="noborder">
	<tr class="liste_titre">
		<td>A propos</td>
		<td align="center">&nbsp;</td>
		</tr>
		<tr class="impair">
			<td valign="top">Module développé par </td>
			<td align="center">
				<img src="<?=DOL_URL_ROOT?>/custom/asset/img/logo2-w-small.png" align="absmiddle"/>
				
			</td>
			
		</td>
	</tr>
</table>
<?

// Put here content of your page
// ...

/***************************************************
* LINKED OBJECT BLOCK
*
* Put here code to view linked object
****************************************************/
//$somethingshown=$asset->showLinkedObjectBlock();

// End of page
$db->close();
llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
?>
