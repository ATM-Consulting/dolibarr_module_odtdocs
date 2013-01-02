<?php


class TAtmDocTBS extends TSSObjet {
	function __construct(&$db, $table='llx_atm_doctbs') {
		parent::TSSObjet($db, $table);		
	}
	function load_by_entity(&$db, $id_entity) {
		$db->Execute("SELECT rowid FROM ".$this->get_table()." WHERE id_entity=".$id_entity);
		if($db->Get_line()) {
			return $this->load($db, $db->Get_field('rowid'));
		}
		else{
			return false;
		}
	}
}
?>