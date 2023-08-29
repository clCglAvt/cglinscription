<?php 
		
 		require_once('../../main.inc.php');
		require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');
		$ID = $_GET["ID"];
	
		$sql ="SELECT rdvPrinc, rdvAlter  ";
		$sql .= "FROM " . MAIN_DB_PREFIX . "agefodd_place as p ";
		$sql .= "WHERE rowid='".$ID."'";
        $rsql = $db->query($sql); 
if ($rsql) { 
		$obj = $db->fetch_object($rsql); 
		$rep = $obj->rdvPrinc.'?'.$obj->rdvAlter;
	}	
$db->free($rsql); 
 		echo( $rep);

		


?>