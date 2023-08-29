<?php 
		
 		require_once('../../main.inc.php');
		require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');
		require_once('./class/html.formdepart.class.php');
		$ID = $_GET["ID"];
		
		$w= new FormCglDepart($db);
		
		$tab = $w->fetch_agf_prix($ID);	
			
		$rep = $tab['PrixAdulte'].'?'.$tab['PrixEnfant'] ;
		$rep .='?'.$tab['PrixGroupe'] .'?'.$tab['PrixExclu'];
		echo( $rep);
		
?>
