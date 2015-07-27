<?php

/** Fonction qui permet de créer un tableau contenant la totalité des extrafields de l'élément en tenant compte des types extrafields tableau qui contiennent en valeur uniquement la clef déclarée dans l'extrafield
 * Exemple pour les extrafields de type liste de sélection :
 * Si j'ai, pour l'extrafield tableau_test en valeur :
 * 1,val1
 * 2,val2
 * 3,val3
 * La fonction permettra de retourner par exemple un tableau de la forme suivante $TExtrafields = array(tableau_test=>val2)
 * Pour les dates, elles sont formattées au format français.
 * @return $TExtrafields 
 */ 
function get_tab_extrafields($array_options) {
	
	dol_include_once('/core/class/extrafields.class.php');
	
	global $db;
	
	$TExtrafields = array();
	foreach ($array_options as $name_extrafield => $value_extrafield) {
		
		$e = new ExtraFields($db);
		
		$array_name_label = $e->fetch_name_optionals_label('propal');
		$array_attribute_params = $e->attribute_param;
		$array_attribute_type = $e->attribute_type;
		
		$name_extrafield_short = substr($name_extrafield, 8);
		
		if($array_attribute_type[$name_extrafield_short] === 'select') { // C'est une liste de sélection ou autre type d'extrafield particulier
		
			$TExtrafields[$name_extrafield_short] = $array_attribute_params[$name_extrafield_short]['options'][$value_extrafield];
		
		} elseif(($array_attribute_type[$name_extrafield_short] === 'int') ||
					$array_attribute_type[$name_extrafield_short] === 'varchar'||
					$array_attribute_type[$name_extrafield_short] === 'text') { // Ce n'est pas un tableau (donc un champ simple entier date ou texte)
					
			$TExtrafields[$name_extrafield_short] = $value_extrafield;
			
		} elseif($array_attribute_type[$name_extrafield_short] === 'date') {
			
			$TExtrafields[$name_extrafield_short] = date('d/m/Y', strtotime($value_extrafield));
			
		}

	}

	return $TExtrafields;

}