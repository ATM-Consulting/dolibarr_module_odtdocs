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
dol_include_once('/core/lib/admin.lib.php');
dol_include_once('/core/class/extrafields.class.php');
$langs->load('odtdocs@odtdocs');

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



llxHeader('','Gestion des editions, à propos','');

require('../class/odt.class.php');

if(isset($_FILES['fichier'])) {
	print "Chargement du fichier : ".$_FILES['fichier']['name']."<br>";

	if(TODTDocs::validFile($_FILES['fichier']['name'])) {
		TODTDocs::addFile($_REQUEST['typeDoc'],$_FILES['fichier']['tmp_name'],$_FILES['fichier']['name'], $conf->entity);		
	}
	
}


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

$action = GETPOST('action', 'alpha');

if($action=='save') {
    
    foreach($_REQUEST['TDivers'] as $name=>$param) {
        
        dolibarr_set_const($db, $name, $param);
        
    }
    
    setEventMessage( $langs->trans('RegisterSuccess') );
}

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

//$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre('Gestion des modèles',$linkback,'setup');

$form=new TFormCore;


$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
// Example with a yes / no select
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ODTDOCS_CAN_GENERATE_ODT").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ODTDOCS_CAN_GENERATE_ODT');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ODTDOCS_CAN_GENERATE_PDF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ODTDOCS_CAN_GENERATE_PDF');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ODTDOCS_SHOW_MESSAGE_ON_GENERATION").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ODTDOCS_SHOW_MESSAGE_ON_GENERATION');
print '</td></tr>';
	
print '</table><br /><br />';

	showFormModel('propal',$conf->entity);
	showFormModel('commande',$conf->entity);
	showFormModel('fournisseur',$conf->entity);
	showFormModel('facture',$conf->entity);
	showFormModel('company',$conf->entity);
	showFormModel('expedition',$conf->entity);
	showFormModel('projet',$conf->entity);
	showFormModel('contract',$conf->entity);
	


function showFormModel($typeDoc='propal', $entity = 1) {
	?><form action="<?php echo $_SERVER['PHP_SELF'] ?>" name="load-<?php echo $typeDoc ?>" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="typeDoc" value="<?php echo $typeDoc ?>" />
	<table width="100%" class="noborder" style="background-color: #fff;">
		<tr class="liste_titre">
			<td><?php
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
					
					case 'projet':
						print 'Modèles pour les projets';
						break;
						
					case 'contract':
						print 'Modèles pour les contrats';
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
				<input type="submit" name="btload" value="Charger" class="butAction" />	
				</td>
				
			</td>
		</tr>
		<?php
		
		$TDocs = TODTDocs::getListe($typeDoc, $entity);
		
		if(count($TDocs)>0)  {
			?>
			<tr>
				<td colspan="2"><strong>Liste des modèles chargés</strong></td>
			</tr>
			<?php
			
			foreach($TDocs as $fichier) {
				?><tr>
					<td><a href="<?php echo dol_buildpath('/odtdocs/modele/'.$entity.'/'.$typeDoc.'/'.$fichier,1) ?>" target="_blank" style="font-weight:normal;"><?php echo $fichier ?></a></td>
					<td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?action=DELETE&fichier=<?php echo urlencode($fichier) ?>&type=<?php echo $typeDoc ?>" onClick="return confirm('Vouslez-vous vraiment supprimer ce modèle?');">Supprimer</a></td>
				</tr><?php
			}
		}
		?>
		
	</table>
	</form>
	<br /><br />
	<?php
}
?>

<table width="100%" class="noborder" style="background-color: #fff;">
        <tr class="liste_titre">
            <td colspan="2"><?php echo $langs->trans('Parameters') ?></td>
        </tr>
        
        <tr>
            <td><?php echo $langs->trans('ReplaceStandardPDFonGenerationByLastCustom') ?></td><td><?php
            
                if($conf->global->ODTDOCS_REPLACE_BY_THE_LAST==0) {
                    
                     ?><a href="?action=save&TDivers[ODTDOCS_REPLACE_BY_THE_LAST]=1"><?php echo img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
                    
                }
                else {
                     ?><a href="?action=save&TDivers[ODTDOCS_REPLACE_BY_THE_LAST]=0"><?php echo img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
                    
                }
            
            ?></td>             
        </tr>
        <tr>
            <td><?php echo $langs->trans('AddAllGeneratedFileInMail') ?></td><td><?php
            
                if($conf->global->ODTDOCS_ADD_ALL_FILES_IN_MAIL==0) {
                    
                     ?><a href="?action=save&TDivers[ODTDOCS_ADD_ALL_FILES_IN_MAIL]=1"><?php echo img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
                    
                }
                else {
                     ?><a href="?action=save&TDivers[ODTDOCS_ADD_ALL_FILES_IN_MAIL]=0"><?php echo img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
                    
                }
            
            ?></td>             
        </tr>
       <tr>
            <td><?php echo $langs->trans('setODTDOCS_LOAD_PRODUCT_IN_LINES') ?></td><td><?php
            
                if($conf->global->ODTDOCS_LOAD_PRODUCT_IN_LINES==0) {
                    
                     ?><a href="?action=save&TDivers[ODTDOCS_LOAD_PRODUCT_IN_LINES]=1"><?php echo img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
                    
                }
                else {
                     ?><a href="?action=save&TDivers[ODTDOCS_LOAD_PRODUCT_IN_LINES]=0"><?php echo img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
                    
                }
            
            ?></td>             
        </tr>
        
        
        
</table>
<br /><br />
<table width="100%" class="noborder">
	<tr class="liste_titre">
		<td>A propos</td>
		<td align="center">&nbsp;</td>
		</tr>
		<tr class="impair">
			<td valign="top">Module développé par </td>
			<td align="center">
				<img src="<?php echo dol_buildpath('/odtdocs/img/logo2-w-small.png',1); ?>" align="absmiddle"/>
				
			</td>
			
		</td>
	</tr>
</table>
<?php

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
