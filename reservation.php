<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15 et PHP7 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       custum/cglinscription/reservation.php
 *		\ingroup   cglinscription
 *		\brief      Permet la saisie des reservation 4 saisons ou CAV est juste intermediaire
 */

 global $langs;
// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
require_once("class/cgldepart.class.php");
require_once("../cglavt/class/cglFctDolibarrRevues.class.php");
require_once ( "./class/cglinscription.class.php");
require_once ( "./class/cgllocation.class.php");
require_once("./class/bulletin.class.php");
require_once ( "./class/cglreservation.class.php");
require_once("./class/html.formcommun.class.php");
require_once ( "./class/html.formreservation.class.php");
require_once("./class/cglcommunlocInsc.class.php");

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load('cglinscription@cglinscription');
$langs->load('cglinscription@agefodd');

	global $langs,$id_client, $action,  $db, $conf;
	global  $ENR_TIERS, $VIDE_TIERS, $SEL_TIERS, $CREE_BULL, $CREE_TIERS_BULL_DOSS, $CREE_BULL_DOSS ;
	global $CRE_PARTICIPATION, $CONF_SUP_ACTPART, $ACT_SUP_ACTPART, $RESA_ENR;
	global $ACT_INSCRIRE,  $BUL_ANNULER, $BUL_CONFANNULER, $BUL_ABANDON, $BUL_CONFABANDON, $PAIE_CONFNEGATIF, $PAIE_NEGATIF, $CNT_RESERVER;
	global $SEND_MAIL, $PREP_MAIL , $SEND_SMS, $PREP_SMS ,   $confim, $vientde, $TiersTel;			

	global $SAIS_REMISEGENREALE, $ENR_LOCINFO;
	global  $ActPartQte,  $Refdossier, $rdnvdoss, $nvdossier, $priorite;
	
	global $id_resa,$Session,$id_part, $BullOrig, $bull, $id_resadet, $tabrowid;	
	global $ACT_SEL_PERS_RESP, $ACT_MAJ_PERS_RESP, $ACT_PRE_INSCRIRE, $CRE_DEPART, $ENR_DEPART, $SAIS_RDV, $ACT_SEL_PAIMT;
	global  $tbNomPrenom, $tbobservation, $tbprix, $tbqte;
	
	global $FctBtRemParticipation,  $FctBtMod, $id_actMod,$RaisRemGen, $mttremisegen;
	
$mesg=''; $error=0; $errors=array();	

// Protection if external user
if ($user->societe_id > 0)	accessforbidden();

 $TraitResa = new CglReservation($db);
 $TraitCommun = new CglCommunLocInsc($db);
 $TraitResa->Init();
 $FormResa  =new FormCglReservation($db);
 $wf = new FormCglCommun ($db);
 $cglInscDolibarr  = new cglInscDolibarr($db); 

 
// Initialisation du flag por renvoyer l'URL ainsi, on limitera les possibilités de créer des doublons
 $flMAJBase = false;
 
// ACTIONS POUR ENVOI EMAIL
if ('MAIL' == 'MAIL') {
	
	$TopEnvoiMail = '';
	$id=$bull->id_client;
	$actiontypecode='AC_OTH_AUTO';
	$paramname='id_resa='.$bull->id.'&socid';
	$object = $bull;
	$bull->socid= $bull->id_client;
	$bull->fk_soc= $bull->id_client;
	//if (empty($bull->TiersMail)) 	$bull->TiersMail = strtoupper($user->email);

	if (GETPOST('etape', 'alpha') == 'M2') $action = 'send';
	include DOL_DOCUMENT_ROOT.'/custom/cglavt/core/actions_sendmails.inc.php';
	if ($bull->TiersMail == strtoupper($user->email)) $bull->TiersMail = '';
	if ($$TopEnvoiMail  ==  'reussi' ) $flMAJBase = true;	
}
 
// ACTIONS POUR ENVOI EMAIL
if ('SMS' == 'SMS1') {
	$id=$bull->id_client;
	$paramname='id_resa='.$bull->id.'&socid';
	$object = $bull;
	$bull->socid= $bull->id_client;
	$bull->fk_soc= $bull->id_client;
	include DOL_DOCUMENT_ROOT.'/custom/cglavt/core/actions_sendSMSs.inc.php';
}
 
 
 
// ACTIONS POUR GESTION SUIVI
if ('SUIVI' == 'SUIVI' and $conf->cahiersuivi) {
	$id=$bull->id_client;
	$paramname='id_bull='.$bull->id.'&socid';
	$object = $bull;
	$bull->socid= $bull->id_client;
	$bull->fk_soc= $bull->id_client;

	include DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/core/actions_gestionSuivi.inc.php';
}




/* EDITION BULLETINS*/
if (isset($action) and $action==$EDT_BULL)	{
	$TraitResa->creer_resa($Session);
}

	
 /* TIERS et BULLETIN */	
$werrors = 0;
 $flgnvdoss = false;
if (isset($action) and ($action==$CREE_TIERS_BULL_DOSS or $action==$CREE_BULL_DOSS) ) { // Arrivée 1 par Inscription eou Suivi Tiers
	$retTiers = $wf->MajTiers($id_client);
	if ($retTiers < 0) 	{ $werrors++; setEventMessage($langs->trans("ErrEnrTiers"),"errors");}
	if ($conf->cahiersuivi) {
		if (empty($Refdossier) or  $Refdossier == -1) {	
			$flgnvdoss = true;		
			require_once(DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/suivi_client.class.php');
			$dossier = new cgl_dossier ($db);
			if (empty($priorite)) $priorite =  $conf->global->CGL_SUIVI_PRIORITE_AUTO;
			$retdossier = $dossier->Maj_dossier ('-1', $nvdossier, '', '', 0, $priorite, '', $id_client, $user->id, null );
			if ($retdossier > 0) 		$Refdossier = $retdossier;	
			else { $werrors++; setEventMessage($langs->trans("ErrEnrDossier"),"errors");}
			unset ($dossier);
		}			
	}
}

if (isset($action) and ($action==$CREE_TIERS_BULL_DOSS or $action==$CREE_BULL_DOSS or  $action==$CREE_BULL) )  {// Crée Bull
 	if ( ($id_bull=="" or(!isset($id_bull)) or is_null($id_bull)) )  { 
		$retinst = $TraitCommun->CreInstanceBull();
		if ($retinst <= 0) { $werrors++; setEventMessage($langs->trans("ErrCreBull"),"errors");		}
		else {
			$id_bull = $retinst;
			$bull->id = $id_bull;
			$bull->fetch($id_bull);
			$ret = $bull->update_champs('fk_dossier', $Refdossier );	
			$ret1 = $wf->UpdateTiersOrigine();		
			if ($ret <0 or $ret1 < 0)  { $werrors++; setEventMessage($langs->trans("ErrUpader"),"errors");}
			// Mettre à jour nom du dosier si non renseigné
			if ($conf->cahiersuivi and $flgnvdoss) {  
				$dossier = new cgl_dossier ($db);
				$retdossier = 0;
				if (empty($nvdossier ) or $nvdossier == 'nouveau dossier') 
					$retdossier = $dossier->Maj_dossier ($Refdossier, $bull->ref, '', '', 0, '', '', '', $user->id, null );
				if ($retinst <= 0) { $werrors++; setEventMessage($langs->trans("ErrNomDos"),"errors");		}
				unset ($dossier);
			}
		}
	}
	 if ($werrors == 0) $flMAJBase = true;
}


 
  if (isset($action) and $action==$ENR_TIERS ) {
	if (!$id_client)  	$id_client=$bull->id_client;
 	$wf->MajTiers($id_client);
 	$wf->UpdateTiersOrigine();	
	if ($conf->version > '1.1') $bull->update_tel ('TiersTel', $TiersTel);
	$flMAJBase = true;
}
 
if (isset($action) and  $action == "confirm_delete" and GETPOST("confirm", 'alpha') == "yes") {
	$TraitResa->DeleteLigneResa();
	$flMAJBase = true;
}

// RESERVER PARTICIPATION */
if (isset($action) and $action==$CNT_RESERVER) 	 {
	$TraitResa->Reserver();
	$flMAJBase = true;
}

  if (isset($action) and $action==$BUL_CONFABANDON) 	 {
	$TraitCommun->Conf_AbandonArchive($bull, 'abandon');
	$flMAJBase = true;
 }
 
 if (isset($action) and $action==$BUL_CONFANNULER) 	 {
	$TraitCommun->Conf_Annuler();
	Header ("Location: listeresa.php?idmenu=219&idmenu=16775&mainmenu=CglResa&leftmenu=" );
	exit();
 } 
 
 
if (isset($action) and  $action == 'ConfSUPActPartMulti') {
	if (!empty($tabrowid)) {
		$TraitResa->ConfSupActPartMulti();
		if ($bull->statut > $bull->BULL_ENCOURS)
			if ($bull->statut >= $bull->BULL_INS) $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
			elseif ($bull->statut == $bull->BULL_PRE_INS) $cglInscDolibarr->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');
		$id_resadet = '';
		$flMAJBase = true;
	}			
}

// COMMANDE de CLOTURE

  if (isset($action) and $action==$RESA_CLOS) 	 {
	$ret = $TraitResa->Clore();
	if ($ret >= 0) {
		header('Location: listeresa.php?idmenu=219&idmenu=16843&mainmenu=CglResa&leftmenu=');
		exit();
	}
 } 	
 
// COMMANDE de REOUVERTURE

  if (isset($action) and $action==$RESA_REOUVRIR) 	 {
	$ret = $TraitResa->Reouvrir();
	if ($ret >= 0) {
		$flMAJBase=true;
	}
 } 
  
// Renvoie l'URL simple
if ($flMAJBase == true) {
	// Filtres
	// Ancre
	$urlcomplement .= $ancre;
	Header('Location: '  . $_SERVER ['PHP_SELF'] . "?id_resa=".$bull->id);
	exit();
}
/*
------------------------------
AFFICHAGE
------------------------------
*/	
 $wf->AfficheEcranEnvironnement("Recherche du client", 1, 'Resa');
if ("ACTION" == "ACTION") {
	 if (isset($action) and $action==$BUL_ANNULER) 	 
			$TraitCommun->Annuler();
	 
	  if (isset($action) and $action==$BUL_ABANDON) 	 
		$TraitCommun->AbandonArchive();

	  if (isset($action) and $action==$SEL_TIERS ) {	
		if ($id_client == -1 or empty($id_client) ) $action = $VIDE_TIERS;
	 }			
		

	 if (isset($action) and $action==$VIDE_TIERS)  {
		$id_client='';
	 }
	 
	// Info generales	
		if (isset($action) and $action==$ENR_RESAINFO)  $TraitResa->EnrInfoGene();
	  // INFO PRIVEE
	  if (isset($action) and $action==$ENR_LOCINFO) 
		  $TraitResa->EnrInfoGene();


	// CREATION NOUVELLE PARTICIPATION */
	if (isset($action) and $action == $RESA_ENR)	{
		$ret = $TraitResa->EnrActPart();
		if ($ret >= 0 ) unset($tbNomPrenom);
		else 
			setEventMessage( $langs->trans('AbortNvPart'), 'warnings');
		$id_resadet = '';
	}
	
	/*  Supprime ligne echange	 */
	if ($action == $SUP_ECH and $conf->cahiersuivi ) {

		if ($bull->type == 'Insc') $id_obj = 'id_bull';
		elseif ($bull->type == 'Loc') $id_obj = 'id_contrat';
		elseif ($bull->type == 'Resa') $id_obj = 'id_resa';
		
		$form = new Form($db);
		$wline_echange = new cgl_echange($db);
		$wline_echange->fetch($Idechange);
		$question=$wline_echange->titre;
		$titre = $langs->trans('ConfEffacerEchange');
		//action='.$SUP_ECH.'&'.$id_obj.'='.$bull->id.'&type='.$bull->type.'&Reftiers='.$Reftiers.'&dossier='.$id_dossier.'&echange='.$obj->IdEchang.

		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'.$id_obj.'='.$bull->id.'&type='.$bull->type.'&Reftiers='.$bull->id_client.'&dossier='.$Refdossier.'&btaction='.$CONF_SUP_ECH.'&echange='.$Idechange ,$titre,$question,$CONF_SUP_ECH,'','',2);
		unset ($form);
		unset ($wline_echange);
		print $formconfirm;	
	}

}
 
if ( ($id_resa=="" or(!isset($id_resa)) or is_null($id_resa)) or $id_resa ==-1 )
	 $wf->AfficheTrouveTiers();
else {		
	if ($id_resa )  	{
		unset($bull);
		$bull=new Bulletin($db);
		$bull->fetch_complet_filtre(-1, $id_resa);
	}	
	$wf->AfficheTiersBullInfo();
	
	//$FormResa->AfficheTiersBull();
	$id_resadet = $bull->lines[0]->id;
	$FormResa->Activite_Participant();
	// Afficher la personne recours s'il y a des mineurs dans les participations
	
	
	if ($conf->cahiersuivi) {
		require_once  DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/html.suivi_client.class.php';
		require_once  DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/suivi_client.class.php';
		$wfdossier = new FormCglSuivi ($db);
		print '<span style="font-size:12px; font-weight:bold">'.$langs->trans('Dossier:').'</span>'. $wfdossier->html_AffDossier($bull->fk_dossier, $bull->DosLib, $bull->fk_DosPriorite, 'priorite1');
		unset ($wfdossier);
	}


	$wf->AfficheBoutonValidation();
	$wf->AfficheEdition(); 
	
	
	if (empty($TopEnvoiMail) or $TopEnvoiMail == 'impossible') {
		if ($action == $PREP_MAIL or  !empty($_POST['modelselected'] )) 	$wf->Preparation_Mail("","");
		if ($action == $PREP_SMS or !empty($_POST['SMSmodelselected'] ))  $wf->Preparation_SMS("");
	}
}
llxFooter();
$db->close();
?>
