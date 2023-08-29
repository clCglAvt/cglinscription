<?php 
/*
* ReqEnrBullDoss
*
 * Version CAV - 2.8 - hiver 2023 - reassociation BU/LO à un autre contrat
 *
 */
 		require_once('../../main.inc.php');
		require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');
		require_once('./class/bulletin.class.php');
		
		global $conf;
		
		$id_bull = $_GET["id_bull"];	
		$id_dossier = $_GET["id_dossier"];		
		$bull = new Bulletin($db);
		$bull->id = $id_bull;
		
		if ($conf->cahiersuivi) {
			$ret = $bull->update_champs("fk_dossier",$id_dossier);
		}

		print $ret;
?>