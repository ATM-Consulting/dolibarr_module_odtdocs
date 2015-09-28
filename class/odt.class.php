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
		
		if(is_dir(dol_buildpath('/odtdocs/modele/'.$entity.'/'.$type))){
			if ($handle = opendir(dol_buildpath('/odtdocs/modele/'.$entity.'/'.$type))) {
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
		
		if(!is_dir(dol_buildpath('/odtdocs/modele/').$entity.'/'.$type.'/')) mkdir(dol_buildpath('/odtdocs/modele/').$entity.'/'.$type.'/', 0777, true);
		copy($source, dol_buildpath('/odtdocs/modele/'.$entity.'/'.$type.'/' ).strtolower(strtr(mb_convert_encoding($name, 'ascii'), array('?'=>'')  )));
		
		
	}
	function delFile($type, $fichier, $entity=1) {
		/* suppression d'un modèle*/
		if (file_exists(dol_buildpath('/odtdocs/modele/'.$entity.'/'.$type.'/'.$fichier)))
			unlink(dol_buildpath('/odtdocs/modele/'.$entity.'/'.$type.'/'.$fichier));
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
		
        global $conf;
        if(!empty($conf->global->ODTDOCS_REPLACE_BY_THE_LAST)) {
            @unlink($upload_dir.'/'.$object->ref.'.pdf');
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
		
		$TBS->LoadTemplate(dol_buildpath('/odtdocs/modele/'.$entity.'/'.$type.'/'.$modele));
		//$TBS->LoadTemplate(dol_buildpath('/odtdocs/modele/'.$entity.'/'.$type.'/'.$modele.'#styles.xml;content.xml;settings.xml');
		
		//$TBS->MergeBlock('societe',array(0=> TODTDocs::asArray($object['societe'])));
		global $mysocPDP;
		$mysocPDP = TODTDocs::asArray($object['mysoc']);
		$mysoc = &$object['mysoc'];
		$mysoc->address_nobr = strtr($mysoc->address,array("\n"=>' - ', "\r"=>''));
		
		require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
		$mysoc->forme_juridique = utf8_decode(getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE));
		$mysoc->logo = DOL_DATA_ROOT.'/mycompany/logos/'.$mysoc->logo;
		
		$conf = &$object['conf'];
		$entity = $conf->entity;
		
		$mysoc->logo_path = DOL_DATA_ROOT.'/'.(($entity>1)?$entity.'/':'').'mycompany/logos/'.$mysoc->logo;
		//$mysoc->logo_path = DOL_DATA_ROOT.'/mycompany/logos/'.$mysoc->logo;
		
		$TBS->MergeField('mysoc',TODTDocs::asArray($object['mysoc']));
		/*foreach(TODTDocs::asArray($mysoc) as $k=>$v) {
			//${'mysoc_'.$k} = $v;
			$TPdp[$k] = strtr($v,array("\n"," - ","\r"=>''));
		}*/

		global $projet;
        $projet = &$object['projet'];
        $projet = TODTDocs::asArray($object['projet']);
		
		
		$outputlangs = new Translate("",$conf);
		//$outputlangs=$langs;
        $outputlangs->setDefaultLang($newlang);
		$outputlangs->load('dict');
		$outputlangs->load('suppliers');
		foreach ($langsToLoad as $domain) {
			$outputlangs->load($domain);
		}
		
		$TBS->MergeField('langs', $outputlangs);
		
		// Traduction de certains éléments du doc
		$object['doc']->cond_reglement = $outputlangs->transnoentities("PaymentCondition".$object['doc']->cond_reglement_code)!=('PaymentCondition'.$object['doc']->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object['doc']->cond_reglement_code):$outputlangs->convToOutputCharset($object['doc']->cond_reglement_doc);
		$object['doc']->mode_reglement = $outputlangs->transnoentities("PaymentType".$object['doc']->mode_reglement_code)!=('PaymentType'.$object['doc']->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object['doc']->mode_reglement_code):$outputlangs->convToOutputCharset($object['doc']->mode_reglement);
		
		if(isset($object['societe']))$TBS->MergeField('societe',TODTDocs::asArray($object['societe']));
		if(isset($object['projet']))$TBS->MergeField('projet',$projet);
		if(isset($object['extrafields']))$TBS->MergeField('extrafields',TODTDocs::asArray($object['extrafields']));
		if(isset($object['doc']))$TBS->MergeField('doc',TODTDocs::asArray($object['doc']));
		if(isset($object['dispatch']))$TBS->MergeField('dispatch',TODTDocs::asArray($object['dispatch']));
		if(isset($object['autre']))$TBS->MergeField('autre',TODTDocs::asArray($object['autre']));
		if(isset($object['tva']))$TBS->MergeBlock('tva',$object['tva']);
		//print_r($object['tableau'][0]);
		if(isset($object['tableau'])) $TBS->MergeBlock('tab,tab2',TODTDocs::checkTableau( TODTDocs::addUnits($object['tableau'])));
		if(isset($object['contact'])) {
			//print_r(TODTDocs::asArray($object['contact']));
			
			TODTDocs::arrayDecode($object['contact'] );
			$TBS->MergeField('contact', $object['contact']);
		}
		
		if(isset($object['contact_block'])) $TBS->MergeBlock('contact_block',TODTDocs::checkTableau($object['contact_block'])); 
		
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
		
		$TBS->LoadTemplate('#styles.xml');
		$TBS->MergeField('doc_linked',$TLinkedObjects);
		if(isset($object['doc']))$TBS->MergeField('doc',TODTDocs::asArray($object['doc']));
		if(isset($object['projet']))$TBS->MergeField('projet',TODTDocs::asArray($object['projet']));
		if(isset($object['dispatch']))$TBS->MergeField('dispatch',TODTDocs::asArray($object['dispatch']));
		$TBS->MergeField('langs', $outputlangs);
		
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
			$pdfName = TODTDocs::convertToPDF($outName);
			
			/*$urlSPDF = 'http://127.0.0.1/PDF/service/odt-pdf.php?doc='.urlencode($outName);
			//print $urlSPDF.'<br>';
			$fPDF = file_get_contents( $urlSPDF );
			$outNamePDF = substr($outName,0, strrpos($outName,'/')).'/'.basename($fPDF);
			
			copy($fPDF, $outNamePDF);*/
			print "Création du fichier $pdfName (module ATM/ODT-PDF)<br>";
			if(is_file($outName) && !$conf->global->ODTDOCS_NO_DELETE_ODT_FILE ) unlink($outName);
		}

	}

    function addUnits($Tab) {
        
        dol_include_once('/core/lib/product.lib.php');
        
        foreach($Tab as &$row) {
            
            if(!empty($row['product'])) {
                $p = & $row['product'];
                
                if(!empty($p->array_options['options_unite_vente'])) {
                    
                    $uv = $p->array_options['options_unite_vente'];
                    if($uv == 'size')$uv = 'length';
                    
                    $p->conditionnement_vente = $p->{$uv};
                    $p->unite_vente = measuring_units_string($p->{$uv.'_units'},$p->array_options['options_unite_vente']); // bah c'est size là ... $p->array_options['options_unite_vente']
                }
                
                
            }
            
            $row['amount_ht'] = $row['subprice'] * $row['qty'];
            
        }
        
        
        return $Tab;
    }

	function arrayDecode(&$Tab) {
		
		foreach($Tab as $k=>&$v) {
			
			if(is_array($v)) TODTDocs::arrayDecode($v);
			else {
				
				$v = utf8_decode($v);
				
			}
			
		}
		
		
	}
	
	function checkTableau($Tab) {
		
		$trans=array(
			'&#039;'=>"'"
		);
		
		foreach($Tab as &$row) {
			 if(!empty($row['desc']) && !empty($row['product_label'])) {
			 	$row['desc']=strtr($row['desc'],$trans);
				$row['product_label']=strtr($row['product_label'],$trans);
				
			 	if($row['desc']==$row['product_label']) {
			 		$row['desc']='';	
			 	}
			 } 
		}
		
		return $Tab;
	}
	
	function asArray($object) {
		$Tab=array();
		
		$TToDate = array('date', 'datec', 'datev', 'datep', 'date_livraison', 'fin_validite', 'date_delivery', 'date_commande', 'date_validation', 'date_lim_reglement', 'date_creation', 'date_delivery', 'date_start', 'date_end');
		$TNoBR = array('address');
		
		if(is_array($object)) {
			if(isset($object['zip']))$object['cp']=$object['zip'];
			if(isset($object['name']))$object['nom']=$object['name'];
            if(isset($object['town']))$object['ville']=$object['town'];
            if(!empty($object['label']) && empty($object['product_label'])) $object['product_label'] = $object['label'];
            if(!empty($object['desc']) && !empty($object['product_label']) && $object['desc']==$object['product_label'])  $object['desc']='';

	
		}
		else {
			if(isset($object->name))$object->nom=$object->name;
    		if(isset($object->zip))$object->cp=$object->zip;
            if(isset($object->town))$object->ville=$object->town;
            if(!empty($object->label) && empty($object->product_label)) $object->product_label = $object->label;
            if(!empty($object->desc) && !empty($object->product_label) && $object->desc==$object->product_label)          $object->desc='';
        }


		foreach($object as $k=>$v) {
			//if(is_int($v) || is_string($v) || is_float($v)) {
			if(!is_object($v) && !is_array($v)) {
				//$Tab[$k] = utf8_decode( strtr($v, array('<br />'=>"\n")));
				$Tab[$k] = utf8_decode( $v );
				//$Tab[$k] = "!".$v;
				
				if(in_array($k, $TToDate)) {
					$Tab[$k.'_fr'] = (!empty($v))?date('d/m/Y', (int)$v):'';
					$Tab[$k.'_ns'] = (!empty($v))?date('W', (int)$v):'';
					
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
				'cp' => $c->zip,
				'ville' => $c->town,
				'pays' => $c->country,
				'email' => $c->email,
				'phone' => $c->phone_pro,
				'fax' => $c->fax,
				'societe' => $c->societe->nom,
				'phone_mobile'=>$c->phone_mobile
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
				'phone' => $u->office_phone,
				'fax' => $u->fax
				,'phone_mobile'=>$u->user_mobile
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
		
			require_once(dol_buildpath('/odtdocs/lib/odt/library/odf.php'));

			$odf = new odf(dol_buildpath('/odtdocs/modele/'.$type.'/'.$modele));
			
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
			
		if(defined('USE_ONLINE_SERVICE')) {
			
			//print USE_ONLINE_SERVICE;				
			$postdata = http_build_query(
			    array(
			        'f1Data' => file_get_contents($file)
					,'f1'=>basename($file)
			    )
			);
			
			$opts = array('http' =>
			    array(
			        'method'  => 'POST',
			        'header'  => 'Content-type: application/x-www-form-urlencoded',
			        'content' => $postdata
			    )
			);
			
			$context  = stream_context_create($opts);
			//print USE_ONLINE_SERVICE;
			$result = file_get_contents(USE_ONLINE_SERVICE, false, $context);
			//exit($result);
			$filePDF = $filepath.'/'.basename($result);
			
			copy(strtr($result, array(' '=>'%20')), $filePDF); 
			//exit($result.', '.$filePDF);
			return $filePDF;
		}	
		else {
	
	//		print "Conversion locale en PDF";
			// Transformation en PDF
			ob_start();

			 $cmd = 'export HOME=/tmp'."\n";
			$cmd.=CMD_CONVERT_TO_PDF.' "'.$filepath.'" "'.$file.'"';

			system($cmd);
			$res = ob_get_clean();
			return $res;

		}	
	}

	function getTVA(&$object) {
		$TTVA = array();
		foreach($object->lines as $ligne) {
			if($ligne->total_tva != 0) {
				if(empty($TTVA[$ligne->tva_tx])) $TTVA[$ligne->tva_tx] = array('baseht'=>0,'montant'=>0);
				$TTVA[$ligne->tva_tx]['label'] = $ligne->tva_tx;
				$TTVA[$ligne->tva_tx]['baseht'] += $ligne->total_ht;
				$TTVA[$ligne->tva_tx]['montant'] += $ligne->total_tva;
			}
		}

		return $TTVA;
	}
	
	public function htmlToUTFAndPreOdf($value)
	{
		// We decode into utf8, entities
		$value=dol_html_entity_decode($value, ENT_QUOTES);

		// We convert html tags
		$ishtml=dol_textishtml($value);
		if ($ishtml)
		{
	        // If string is "MYPODUCT - Desc <strong>bold</strong> with &eacute; accent<br />\n<br />\nUn texto en espa&ntilde;ol ?"
    	    // Result after clean must be "MYPODUCT - Desc bold with é accent\n\nUn texto en espa&ntilde;ol ?"

			// We want to ignore \n and we want all <br> to be \n
			$value=preg_replace('/(\r\n|\r|\n)/i','',$value);
			$value=preg_replace('/<br>/i',"\n",$value);
			$value=preg_replace('/<br\s+[^<>\/]*>/i',"\n",$value);
			$value=preg_replace('/<br\s+[^<>\/]*\/>/i',"\n",$value);

			//$value=preg_replace('/<strong>/','__lt__text:p text:style-name=__quot__bold__quot____gt__',$value);
			//$value=preg_replace('/<\/strong>/','__lt__/text:p__gt__',$value);

			$value=dol_string_nohtmltag($value, 0);
		}

		return $value;
	}
	
}
?>
