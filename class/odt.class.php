<?php
/*
 * Class de création d'objet ODT/DOCX/XLSX/ODS
 * Se base du la librairie TinyButStrong et le plugin ODT associé
 * Alexis Algoud / ATM Consulting
 * 29/02/2012
 */
class TODTDocs {
	function getListe($type, $entity=1) {
	/* Liste des modèles valides */
		$Tab=array();
		
		if(is_dir(DOL_DOCUMENT_ROOT_ALT.'/odtdocs/modele/'.$entity.'/'.$type)){
			if ($handle = opendir(DOL_DOCUMENT_ROOT_ALT.'/odtdocs/modele/'.$entity.'/'.$type)) {
			    while (false !== ($entry = readdir($handle))) {
			    	if($entry[0]!='.' && TODTDocs::validFile($entry))  $Tab[] = $entry;
			    }
			
			    closedir($handle);
			}
		}
		
		sort($Tab);
		
		return $Tab;
	}
	function _ext($file) {
	/* extension d'un fichier */
		$ext = substr ($file, strrpos($file,'.'));
		return $ext;
	}
	function validFile($name) {
	/* Fichier valid pour le traitement ? */
		$ext = TODTDocs::_ext($name);
		
		if($ext=='.odt' || $ext=='.docx' || $ext=='.xlsx' || $ext=='.ods') return TRUE;
		else { print "Type de fichier ($ext) non supporté ($name)."; return false; }
		
	}
	
	function addFile($type, $source, $name, $entity=1) {
	/* Ajout d'un modèle // la validation devra être prévalente */	
		@mkdir(DOL_DOCUMENT_ROOT_ALT.'/odtdocs/modele/'.$entity.'/'.$type.'/', 0777, true);
		copy($source, DOL_DOCUMENT_ROOT_ALT.'/odtdocs/modele/'.$entity.'/'.$type.'/'.strtolower(strtr(mb_convert_encoding($name, 'ascii'), array('?'=>'')  )) );
		
		
	}
	function delFile($type, $fichier, $entity=1) {
	/* suppression d'un modèle*/
		unlink(DOL_DOCUMENT_ROOT_ALT.'/odtdocs/modele/'.$entity.'/'.$type.'/'.$fichier);
		
	}
	function show_docs(&$db,&$conf, &$object,&$langs, $type='propal') {
	/*
	 * Récupération des docs généré pour un objet grâce au fonction DOL 
	 */
		if($type=='propal') {
			$upload_dir = $conf->propal->dir_output.'/'.dol_sanitizeFileName($object->ref);	
		}
		elseif($type=='facture') {
			$upload_dir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($object->ref);	
		}
		elseif($type=='commande') {
			$upload_dir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($object->ref);	
		}
		elseif($type=='commande_fournisseur') {
			$upload_dir = $conf->fournisseur->dir_output.'/commande/'.dol_sanitizeFileName($object->ref);	
		}
		elseif($type=='company') {
			$upload_dir = $conf->societe->dir_output.'/'.dol_sanitizeFileName($object->ref);
		}
		elseif($type=='expedition') {
			$upload_dir = $conf->expedition->dir_output . '/sending/'.dol_sanitizeFileName($object->ref);
		}
		
		//print $upload_dir;
		$sortfield = GETPOST("sortfield",'alpha');
		$sortorder = GETPOST("sortorder",'alpha');
		require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
		
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

		$formfile=new FormFile($db);
		// List of document
		$param='&id='.$object->id;
		$formfile->list_of_documents($filearray,$object,$type,$param,0,'',0);
	
		
	}
	function combo($type, $nom='modele',$defaut='', $entity=1) {
	/* Code combo pour sélection modèle */
		$TDocs = TODTDocs::getListe($type, $entity);
		
		?><script language="javascript">
			function showButtonPDF_<?=$nom?>() {
				if( $('#<?=$nom?> option:selected').attr('extension')=='.odt' ) { 
					$('#btgenPDF').show(); 
				} else { 
					$('#btgenPDF').hide();
				}
			}
			$(document).ready(function() { showButtonPDF_<?=$nom?>(); });
			
		</script>
		<select name="<?=$nom?>" id="<?=$nom?>" onchange="showButtonPDF_<?=$nom?>()" class="flat"><?
			
		foreach($TDocs as $fichier) {
			
			?><option value="<?=$fichier ?>" <?=($defaut==$fichier)?'selected="selected"':''?> extension="<?=TODTDocs::_ext($fichier)  ?>"><?=$fichier ?></option><?
			
		}
	
		?></select><?
		
	}
	function comboLang(&$db, $codelang='fr_FR') {
		global $langs;
		
		dol_include_once('/core/class/html.formadmin.class.php');
		
		?>
		- Langue : 
		<?
		
		$formadmin=new FormAdmin($db);
        $defaultlang=!empty($codelang) ? $codelang : $langs->getDefaultLang();
        print $formadmin->select_language($defaultlang);
		
	}
	function langs() {
		
	}
	function makeDocTBS($type, $modele, $object, $outName, $entity=1, $PDFconversion = false, $newlang='fr_FR', $langsToLoad=array()) {
	/* Création du fichier à proprement parler
	 * $objet aura la forme d'un tableau 
	 * Array( 
	 * 		'mysoc'=>objet compagnie entête
	 * 		,'societe'=>objet société cliente
	 * 		,'conf'=>objet configuration globale
	 * 		,'doc'=>objet maître (devis/propale/facture/...)
	 * 		,'tableau'=>tableau de donnée/ligne du document   
	 * )	
	 *  */	
		global $conf, $langs;
	
	 	if($type=='propal')$dir = 'propale/';
		else $dir=$type.'/';
		
		@mkdir( dirname($outName), 0777, true );
		
		$TBS = new clsTinyButStrong; // new instance of TBS
		$TBS->NoErr = true;
		$TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN); // load OpenTBS plugin
		
		$TBS->LoadTemplate(DOL_DOCUMENT_ROOT_ALT.'/odtdocs/modele/'.$entity.'/'.$type.'/'.$modele);
	
		
		//$TBS->MergeBlock('societe',array(0=> TODTDocs::asArray($object['societe'])));
		global $mysocPDP;
		$mysocPDP = TODTDocs::asArray($object['mysoc']);
		$mysoc = &$object['mysoc'];
		$mysoc->address_nobr = strtr($mysoc->address,array("\n"=>' - ', "\r"=>''));
		
		
		$conf = &$object['conf'];
		$entity = $conf->entity;
		
		$mysoc->logo_path = DOL_DATA_ROOT.'/'.(($entity>1)?$entity.'/':'').'mycompany/logos/'.$mysoc->logo;
		//$mysoc->logo_path = DOL_DATA_ROOT.'/mycompany/logos/'.$mysoc->logo;
		
		$TBS->MergeField('mysoc',$mysoc);
		/*foreach(TODTDocs::asArray($mysoc) as $k=>$v) {
			//${'mysoc_'.$k} = $v;
			$TPdp[$k] = strtr($v,array("\n"," - ","\r"=>''));
		}*/

		$outputlangs = new Translate("",$conf);
		//$outputlangs=$langs;
        $outputlangs->setDefaultLang($newlang);
		foreach ($langsToLoad as $domain) {
			$outputlangs->load($domain);
		}
		
		$TBS->MergeField('langs', $outputlangs);
		
		if(isset($object['societe']))$TBS->MergeField('societe',TODTDocs::asArray($object['societe']));
		if(isset($object['doc']))$TBS->MergeField('doc',TODTDocs::asArray($object['doc']));
		if(isset($object['dispatch']))$TBS->MergeField('dispatch',TODTDocs::asArray($object['dispatch']));
		if(isset($object['autre']))$TBS->MergeField('autre',TODTDocs::asArray($object['autre']));
		//print_r($object['tableau'][0]);
		if(isset($object['tableau'])) $TBS->MergeBlock('tab,tab2',$object['tableau']);
		if(isset($object['contact'])) {
			//print_r(TODTDocs::asArray($object['contact']));
			
			TODTDocs::arrayDecode($object['contact'] );
			$TBS->MergeField('contact', $object['contact']);
		} 
		
		if(isset($object['compte'])) {
			$TBS->MergeField('compte',TODTDocs::asArray($object['compte']));
		} 
		if(isset($object['linkedObjects'])) {
			$TLinkedObjects = array();
			foreach($object['linkedObjects'] as $typeObj => $TObject) {
				$TLinkedObjects[$typeObj] = array();
				foreach($TObject as $i => $obj) {
					if($typeObj == 'commande' && ($type == 'facture' || $type == 'expedition')) {
						$obj->fetchObjectLinked('','propal',$obj->id,$obj->element);
						if(isset($obj->linkedObjects['propal']) && $obj->linkedObjects['propal'][0]) {
							$TLinkedObjects['propal'] = array(TODTDocs::asArray($obj->linkedObjects['propal'][0]));
						}
					}
					$TLinkedObjects[$typeObj][$i] = TODTDocs::asArray($obj);
				}
			}
			$TBS->MergeField('doc_linked',$TLinkedObjects);
		} 
		
		/*echo '<pre>';
		print_r($object);
		echo '</pre>';*/
		
		
		$ext = TODTDocs::_ext($modele);
		
		if($ext==".odt" || $ext=='.docx') {
			
			$TBS->PlugIn(OPENTBS_DELETE_COMMENTS);
			
		}
		elseif($ext=='.xlsx') {
			null; // au cas où
		}
		
		print "Création du fichier $outName (module ATM/TBS)<br>";
		$TBS->Show(OPENTBS_FILE, $outName);

		if($PDFconversion) {
			TODTDocs::convertToPDF($outName);
			
			/*$urlSPDF = 'http://127.0.0.1/PDF/service/odt-pdf.php?doc='.urlencode($outName);
			//print $urlSPDF.'<br>';
			$fPDF = file_get_contents( $urlSPDF );
			$outNamePDF = substr($outName,0, strrpos($outName,'/')).'/'.basename($fPDF);
			
			copy($fPDF, $outNamePDF);*/
			print "Création du fichier $outName (module ATM/ODT-PDF)<br>";
		}

	}
	function arrayDecode(&$Tab) {
		
		foreach($Tab as $k=>&$v) {
			
			if(is_array($v)) TODTDocs::arrayDecode($v);
			else {
				
				$v = utf8_decode($v);
				
			}
			
		}
		
		
	}
	function asArray($object) {
		$Tab=array();
		
		$TToDate = array('date', 'datec', 'datev', 'datep', 'date_livraison', 'fin_validite', 'date_delivery', 'date_commande', 'date_validation', 'date_lim_reglement');
		$TNoBR = array('address');
		
		foreach($object as $k=>$v) {
			//if(is_int($v) || is_string($v) || is_float($v)) {
			if(!is_object($v) && !is_array($v)) {
				$Tab[$k] = utf8_decode( $v );
//				$Tab[$k] = $v;
				
				if(in_array($k, $TToDate)) {
					$Tab[$k.'_fr'] = date('d/m/Y', (int)$v);
				}
				if(in_array($k, $TNoBR)) {
					$Tab[$k.'_nobr'] = strtr($v,array("\n"=>' - ', "\r"=>''));
				}
				
			}	
		}
		//print_r($Tab);
		return $Tab;
	}
	function getContact(&$db, &$object, &$societe) {
		require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
		$r = array();
		$TRes = array();
		$TContact = &$object->liste_contact(-1,'external');
		foreach($TContact as $contact) {
			$c = new Contact($db);
			$c->fetch($contact['id']);
			$c->societe = new Societe($db);
			$c->societe->fetch($contact['socid']);
			
			$TRes[$contact['code']] = array(
				'id' => $c->id,
				'civility' => $c->getCivilityLabel(),
				'firstname' => $c->firstname,
				'lastname' => $c->lastname,
				'address' => $c->address,
				'cp' => $c->cp,
				'ville' => $c->ville,
				'pays' => $c->pays,
				'email' => $c->email,
				'phone' => $c->phone_pro,
				'fax' => $c->fax,
				'societe' => $c->societe->nom
			);
		}
		
		$TUser = &$object->liste_contact(-1,'internal');
		foreach($TUser as $user) {
			$u = new User($db);
			$u->fetch($user['id']);
			
			$TRes[$user['code']] = array(
				'id' => $u->id,
				//'civility' => $u->getCivilityLabel(),
				'firstname' => $u->firstname,
				'lastname' => $u->lastname,
				'address' => $u->address,
				'cp' => $u->cp,
				'ville' => $u->ville,
				'pays' => $u->pays,
				'email' => $u->email,
				'phone' => $u->phone_pro,
				'fax' => $u->fax
			);
		}
		
		return $TRes;
		
		/*if(empty($r)) {
			$r['societe'] = $societe->nom;
			$r['nom'] = $societe->nom_particulier;
			$r['lastname'] = $societe->nom_particulier;
			$r['firstname'] = $societe->prenom;
			$r['email'] = $societe->email;
			
			$r['address'] = $societe->address;
			$r['cp'] = $societe->cp;
			$r['ville'] = $societe->ville;
						
			
		}
		else {
			$companystatic = new Societe($db);
			$companystatic->fetch($r['socid']);
			$r['societe'] = $companystatic->nom;
		}
		
		
		return $r;*/
		
	}
	function makeDoc($type, $modele, $object, $outName) {
		
			if($type=='propal')$dir = 'propale/';
			else $dir=$type.'/';
		
			require_once(DOL_DOCUMENT_ROOT_ALT.'/odtdocs/lib/odt/library/odf.php');

			$odf = new odf(DOL_DOCUMENT_ROOT_ALT.'/odtdocs/modele/'.$type.'/'.$modele);
			
			TODTDocs::_md_setVar($odf, $object);
			
			print "Création du fichier $outName<br>";
			
			$odf->saveToDisk($outName);
		
	}
	
	function _md_setVar(&$odf, &$object) {
		
		
		if(isset($object['mysoc'])) {
			$mysoc = & $object['mysoc']; 
			/*
		mycompany_logo = {mycompany_logo}
		mycompany_name = {mycompany_name}
		mycompany_address = {mycompany_address}
		mycompany_zip = {mycompany_zip}
		mycompany_town = {mycompany_town}
		mycompany_country = {mycompany_country}
		mycompany_phone = {mycompany_phone}
		mycompany_fax = {mycompany_fax}
		mycompany_email = {mycompany_email}
		mycompany_web = {mycompany_web}
		mycompany_barcode = {mycompany_barcode}
		mycompany_capital= {mycompany_capital}
		mycompany_juridicalstatus= {mycompany_juridicalstatus}
		mycompany_idprof1 = {mycompany_idprof1}
		mycompany_idprof2 = {mycompany_idprof2}
		mycompany_idprof3 = {mycompany_idprof3}
		mycompany_idprof4 = {mycompany_idprof4}
		mycompany_vatnumber = {mycompany_vatnumber}
		mycompany_note = {mycompany_note}*/
			
			$odf->setVars('mycompany_name', $mysoc->name);
			
			$odf->setImage('mycompany_logo', DOL_DATA_ROOT.'/mycompany/logos/'.$mysoc->logo,200);
			
			$odf->setVars('mycompany_address', $mysoc->address);
			$odf->setVars('mycompany_zip', $mysoc->zip);
			$odf->setVars('mycompany_town', $mysoc->town );
			$odf->setVars('mycompany_country', $mysoc->country );
			$odf->setVars('mycompany_phone', $mysoc->phone );
			$odf->setVars('mycompany_fax', $mysoc->fax);
			$odf->setVars('mycompany_email', $mysoc->email);
			$odf->setVars('mycompany_web', $mysoc->url);
			$odf->setVars('mycompany_barcode', $mysoc->siret);
			$odf->setVars('mycompany_capital', $mysoc->capital);
			$odf->setVars('mycompany_juridicalstatus', $mysoc->forme_juridique);
			$odf->setVars('mycompany_idprof1', $mysoc->idprof1);
			$odf->setVars('mycompany_idprof2', $mysoc->idprof2);
			$odf->setVars('mycompany_idprof3', $mysoc->idprof3);
			$odf->setVars('mycompany_idprof4', $mysoc->idprof4);
			$odf->setVars('mycompany_vatnumber', $mysoc->ape);
			$odf->setVars('mycompany_note', $mysoc->note);
			
		}
	
		
		/*
		Customers, prospects or suppliers information
		
		company_name = {company_name}
		company_address = {company_address}
		company_zip = {company_zip}
		company_town = {company_town}
		company_country = {company_country}
		company_phone = {company_phone}
		company_fax = {company_fax}
		company_email = {company_email}
		company_web = {company_web}
		company_barcode = {company_barcode}
		company_customercode = {company_customercode}
		company_suppliercode = {company_suppliercode}
		company_capital = {company_capital}
		company_juridicalstatus = {company_juridicalstatus}
		company_idprof1 = {company_idprof1}
		company_idprof2 = {company_idprof2}
		company_idprof3 = {company_idprof3}
		company_idprof4 = {company_idprof4}
		company_vatnumber = {company_vatnumber}
		company_note = {company_note}
		
		User information
		
		myuser_lastname = {myuser_lastname}
		myuser_firstname = {myuser_firstname}
		myuser_login = {myuser_login}
		myuser_email = {myuser_email}
		...
		
		Object information (invoice, commercial proposal, order, ...)
		
		object_id = {object_id}
		object_ref = {object_ref}
		object_ref_customer = {object_ref_customer}
		object_ref_supplier = {object_ref_supplier}
		object_date = {object_date}
		object_date_creation = {object_date_creation}
		object_date_validation = {object_date_validation}
		object_total_ht = {object_total_ht}
		object_total_vat = {object_total_vat}
		object_total_ttc = {object_total_ttc}
		object_vatrate = {object_vatrate}
		object_note_private = {object_note_private}
		object_note = {object_note}
		...
		Specific to proposals:
		object_date_end = {object_date_end}    End date of validity of proposal
				
		*/
		
	}

	static function convertToPDF($file) {
		$infos = pathinfo($file);
		$filepath = $infos['dirname'];
		
		// Transformation en PDF
		$cmd = 'export HOME=/tmp'."\n";
		$cmd.= 'libreoffice --invisible --norestore --headless --convert-to pdf --outdir "'.$filepath.'" "'.$file.'"';
		ob_start();
		system($cmd);
		$res = ob_get_clean();
		return $res;
	}
}
?>
