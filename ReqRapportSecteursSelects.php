<?php 
/*
 * Claude Castellano	claude@cigaleaventure.com
 * 
 * Version CAV - 2.8 - hiver 2023 -
 *			- creation
 * Version CAV - 2.8.4 - printemps 2023 -
 *			- amélioration interface.. Rapport d'activité.
 *
*/
/**
 *	\file        htdocs/custom/cglinscription/ReqRapportSecteursSelects.php
 *	\brief       Page permettant au js d'afficher la nouvelle liste des secteurs sélectionnés
 */

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');
require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/cglRapportSecteur.class.php";
require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/html_formRapportSecteur.class.php";

		$secteurs = array();
		$secteurs = $_GET["secteurs"];
		$RaportMethode = new cglRapportSecteur ($db);
		$FormRapport = new FormRapportSecteur ();	
		// Reçoit la liste des secteurs sélectionnés par urldecode
		
		$tabSects = $RaportMethode->ConstSecteur();
		//construit le tableau tabSelect
		$tabSectSels = $RaportMethode->ConstSectSel($tabSects, $secteurs, 'id'	);
		print $FormRapport->html_ListSelectionnes ($tabSectSels, 'Req');

		


?>