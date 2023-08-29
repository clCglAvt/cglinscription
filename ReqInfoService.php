<?php 
		
 		require_once('../../main.inc.php');
		require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');
		$ID = $_GET["ID"];
		
		$sql ="SELECT price_ttc as journee, price_min_ttc as joursup  ";
		$sql .= "FROM " . MAIN_DB_PREFIX . "product  as p ";
		$sql .= "WHERE rowid='".$ID."'";	
        $rsql = $db->query($sql); 
if ($rsql) { 
	 $num = $db->num_rows($rsql);
	 $i=0;	 
		$obj = $db->fetch_object($rsql); 
		$rep = $obj->journee.'?'.$obj->joursup;
	}
	
$db->free($rsql); 
 		echo( $rep);

		


?>