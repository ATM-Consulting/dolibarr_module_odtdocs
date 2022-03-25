<?php

/**
 * Copyright © 2015 Marcos García de La Fuente <hola@marcosgdf.com>
 *
 * This file is part of Multismtp.
 *
 * Multismtp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Multismtp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Multismtp.  If not, see <http://www.gnu.org/licenses/>.
 */

class ActionsOdtdocs
{
    
    
    function getFormMail($parameters, &$object, &$action, $hookmanager) {
        
        if (in_array('formmail', explode(':', $parameters['context'])))
        {
          
            global $db, $user, $conf;

            if(!empty($conf->global->ODTDOCS_ADD_ALL_FILES_IN_MAIL)) {
                 dol_include_once('/core/lib/files.lib.php');       
               //var_dump($object);
               
                 $keytoavoidconflict = empty($object->trackid)?'':'-'.$object->trackid;   // this->trackid must be defined
                 
                 if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths=explode(';',$_SESSION["listofpaths".$keytoavoidconflict]);
                 if (! empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames=explode(';',$_SESSION["listofnames".$keytoavoidconflict]);
                 if (! empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes=explode(';',$_SESSION["listofmimes".$keytoavoidconflict]);
//                  var_dump($_SESSION["listofpaths".$keytoavoidconflict],$listofpaths);
			   	 if (!empty($listofpaths) && GETPOST('mode') === 'init') {
//			   	 	$listofpaths=explode(';', $listofpaths);
					$dir = dirname($listofpaths[0]);
					if(empty($dir)) return 0;
					
	                 $object->clear_attached_files();
	                
	                $tmparray=dol_dir_list($dir,'files',0);
					foreach($tmparray as &$f) {
	                    
						$file = $f['fullname'];
						
	                    $object->add_attached_files($file, basename($file), dol_mimetype($file));    
	                }

			   	 }
			   
                
            }
        }
        
    }
    
    
    
    function afterPDFCreation($parameters, &$object, &$action, $hookmanager)
    {
        if (in_array('pdfgeneration', explode(':', $parameters['context'])))
        {

            global $db, $user, $conf;

            if(!empty($conf->global->ODTDOCS_REPLACE_BY_THE_LAST)) {
               // $ref = !empty($objectPDF->facnumber) ?  $objectPDF->facnumber : $objectPDF->ref;
                $objectPDF = & $parameters['object'];
                
                if($objectPDF->element=='propal' || $objectPDF->element == 'expedition' || $objectPDF->element == 'facture'
                || $objectPDF->element == 'commande') {
					/* DA021590 - Factures PRO FORMA */
					dol_include_once('/core/lib/files.lib.php');
					$fileparams = dol_most_recent_file($conf->{$objectPDF->element}->dir_output . '/' . $objectPDF->ref);
					$file = $fileparams['fullname'];

					if(!empty($file) && $file != $parameters['file']){
						@unlink($parameters['file']);

						copy($file, $parameters['file']);
					}
					/* FIN - DA021590 - Factures PRO FORMA */

                }
                
                
            }

            
            return 1;
          
        }
        
    }

}
