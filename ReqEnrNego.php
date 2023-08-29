<?php 
/*
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
 *
 * Version CAV - 2.7.1 automne 2022 - Réécriture de la fonction EnrNego
 *
*/		
 		require_once('../../main.inc.php');
		require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');
		$ID = GETPOST("ID", 'int');
		$PartMon =GETPOST("PartMon", 'decimal');
		$Pourcent =GETPOST("Pourcent", 'decimal');
		$facture =GETPOST("Facture", 'int');
		$type =GETPOST("type", 'alpha');
 		require_once('class/cgldepart.class.php');

		$w= new CglDepart($db);
		
		$rep = $w->EnrNego($ID, $PartMon, $Pourcent, $facture ,(int) $type);
		echo( $rep);
		
?>
