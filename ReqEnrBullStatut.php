<?php 
 		require_once('../../main.inc.php');
		require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');
		require_once('./class/bulletin.class.php');
		
		$ID = $_GET["ID"];
		$demande = $_GET["demande"];		
		$bull = new Bulletin($db);
		$bull->id = $ID;
		if ($demande =='DepartFait') 
			$ret = $bull->Statut_DepartFait ();
		elseif ($demande =='BullClos') 
			$ret = $bull->Statut_Clos ();

		print $ret;
		print 'Coucou';
?>