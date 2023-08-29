<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
 * Version CAV - 2.8 - hiver 2023 -
 *			- contrat technique
 *			- Fenêtre modale pour modif pour echange
 *			- fiabilisation des foreach
 *			- reassociation BU/LO à un autre contrat
 *			- remise à plat des status BU/LO
 * Version CAV - 2.8.3 - printemps 2023
 *		- suppression des guillemets dans les textarea
 *		- ajout suppression echange dans pavesuivi
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
 *   	\file       custum/cglinscription/location.php
 *		\ingroup    cglinscription
 *		\brief      Permet la saisie des contrats de  location
 */

 global $langs;
// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;

if ('INCLUDE' == 'INCLUDE') {
	if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';

	//dol_include_once ("cglinscription/lib/cgl_variable.lib.php");
	require_once("class/cgldepart.class.php");
	require_once("../cglavt/class/cglFctDolibarrRevues.class.php");
	if ($conf->stripe) 	require_once("./class/cglstripe.class.php");
	require_once("./class/bulletin.class.php");
	require_once("./class/cglcommunlocInsc.class.php");
	require_once("./class/cgllocation.class.php");
	require_once("./class/html.formcommun.class.php");
	require_once("./class/html.formlocation.class.php");
	require_once("./class/cglinscription.class.php"); // nécessaire pour les fonction de conversion de date - voir à mettre en module commun
	require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
}

// Load traductions files requiredby by page
if ('TRADUCTION' == 'TRADUCTION') {
	$langs->load("companies");
	$langs->load("other");
	$langs->load('cglinscription@cglinscription');	
}	
	
if ('VARIABLE_GLOBALE' == 'VARIABLE_GLOBALE') {
	global $langs,$id_client, $action,  $db;	
	global  $ENR_TIERS, $VIDE_TIERS, $SEL_TIERS, $CREE_BULL, $CREE_TIERS_BULL_DOSS, $CREE_BULL_DOSS ;
	global  $BtEncais, $BtStripeMail, $BtStripeSMS,  $id_stripe;
	global $ACT_CRE_PAIMT, $ACT_MAJ_PAIMT, $CNTLOC_CLOS, $CNTLOC_DEPARTFAIT, $CNTLOC_DEPARTNONFAIT, $CNTLOC_REOUVRIR, $CNTLOC_DESARCHIVER, $BUL_ABANDON,$BUL_ANULCLIENT, $BUL_CONFABANDON, $CNT_RESERVER, $CNT_PRE_RESERVER, $CNT_DEPART, $CNT_RETOUR, $ACT_SEL_PAIMT;
	global $ACT_INSCRIRE,  $BUL_ANNULER, $BUL_CONFANNULER, $BUL_CONFANULCLIENT, $ACT_SUP_LOCDET, $CONF_SUP_LOCDET, $SAIS_CAUTACC, $MOD_LOCINFO;
	global $ENR_LOCINFO, $UPD_MATMAD, $UPD_MATMAD_RET, $UPD_RANDO, $UPD_RANDO_RET, $UPD_LOC_RET, $UPD_REMFIX,  $UPD_CAUTACC, $CAL_ACPT, $RETGENMAT, $RETGENMAD, $RETGENRAND;
	global $BULLNonFacturable, $BULLFacturable;
	global $PREP_MAIL, $PREP_SMS,  $SEND_MAIL, $BtEncStripe, $confirm, $vientde;		
	
	global $PAIE_CONFNEGATIF, $ACT_SUP_PAIMT, $CONF_SUP_PAIMT;
	global $ACT_STRIPESUPP, $CONF_STRIPESUPP, $ACT_STRIPEREMB, $ACT_STRIPERELMAIL, $ACT_STRIPERELSMS;
	
	global  $EDT_CMD, $EDIT_CNTLOC;
	global $FctBtRemParticipation, $ACT_SUP_REMFIX, $ConfSUPRemFix, $tabrowid, $Refdossier, $rdnvdoss, $nvdossier, $priorite;
	
	global $id_contrat,$Session,$id_part, $TiersOrig, $bull, $id_contratdet, $PaimtMtt, $RaisRemGen, $mttremisegen, $textremisegen, $FctBtDelParticipation;	
	global $ACT_SEL_ACTPART,  $ENR_LOCDET, $LOC_DUP, $ACT_CRE_ACTPART ;
	global  $ACT_CRE_BULL;
	global $FiltrPasse, $ActPartQte;
		global $StripeMailPayeur, $StripeMtt, $StripeNomPayeur, $libelleCarteStripe, $id_stripe, $modelmailselected;
	
}
$mesg=''; $error=0; $errors=array();



// Protection if external user
if ($user->societe_id > 0)	accessforbidden();

if ('VARIABLE' == 'VARIABLE') {
	 $TraitLocation = new CglLocation($db);
	 $TraitCommun = new CglCommunLocInsc($db);
	 $wf = new FormCglCommun ($db);

	 $FormLocation  =new FormCglLocation($db);
	 $cglInscDolibarr  = new cglInscDolibarr($db);
	  /* pour les COULEURS http://fr.wikipedia.org/wiki/Liste_de_couleurs*/
}
 $TraitLocation->Init();

/*
print '<br> CCA==============----------------------========================SESSION:';
var_dump ($_SESSION);
*/	 
// Action sur Demande Stripe
if ($conf->stripe->enabled) {
	if ($action == $CONF_STRIPESUPP)
	{
		$wcst = new CglStripe ($db);
		$wcst->ConfSupDemandeAcompte($id_stripe);
		unset($wcst);
	} 
	if ($action ==$ACT_STRIPEREMB)
	{
		print '<br>=========================Rembourser Acompte';
		print '<br>========================Ecra n de saisie d"encaissement avec cas particulier - remb Stripe';
		print '<br>==========================Paiement Dolibarr  négatif - lié à Acompte - Acompte payé';
		print '<br>==========================Paiement CAV négatif - type 1';
	} 

	if ($action == $ACT_STRIPERELSMS)
	{
		print '<br>========================"action=sendSMS" avec info nécessaires';
	} 
 
}


// éviter que l'ajout ou la suppression d'un fichier ne provoque l'envoi du message
if (!empty(GETPOST('addfile','alpha')) or !empty(GETPOST('removedfile','alpha')) ){ 	$txttmp = GETPOST("etape",'alpha') ;
	$txttmp = substr($txttmp, 0,strlen($txttmp)-1);
	$txttmp .= '2';
	$_POST["etape"] = $txttmp;
}


// Initialisation du flag por renvoyer l'URL ainsi, on limitera les possibilités de créer des doublons
$flMAJBase = false;
$ancre = '';

// ACTIONS POUR ENVOI EMAIL
if ('MAIL' == 'MAIL' and ( !empty(GETPOST('sendmail','alpha')) or  strlen(GETPOST("etape",'alpha')) == 2 ))  {
	$TopEnvoiMail = '';
	global $fl_PreInscrire;
	$id=$bull->id_client;
	$actiontypecode='AC_OTH_AUTO';
	$paramname='id_contrat='.$bull->id.'&socid';
	$object = New Societe($db);
	$object->id = $bull->id_client;
	$object->SubMailStripe = 1;
	$fl_PreInscrire = false;
	// Gestion Stripe
//		if ($conf->stripe->enabled and strlen(GETPOST('etape','alpha')) == 3 and strpos(GETPOST('etape', 'alpha'), '3') >0 )
	if ($conf->stripe->enabled and strlen(GETPOST('etape','alpha')) == 3 )
	{
		$object->Environ = 'Stripe';
		$wstr = new CglStripe ($db);
		$ret = $wstr->GestionEnvoiDemandeStripe();
		if ($ret >= 0) $fl_PreInscrire = $ret;
		unset ($wstr);		
	}
	
	$object->stripeUrl = $bull->stripeUrl;
	$fl_modaction = false;
	if (!empty(GETPOST('sendmail','alpha'))) $action = 'send';
	include DOL_DOCUMENT_ROOT.'/custom/cglavt/core/actions_sendmails.inc.php';
	if ($bull->TiersMail == strtoupper($user->email)) $bull->TiersMail = '';
	if ( $fl_modaction == true) {$action = 'sendmail';$fl_modaction =false; }

	if ($conf->stripe->enabled and 	strpos(GETPOST('etape','alpha'), '2') >0 and  $TopEnvoiMail == 'reussi'  and ($bull->type == 'Insc'  or $bull->type == 'Loc' )) {
		$flMAJBase = true;		
	}			
	if (	strpos(GETPOST('etape','alpha'), '2') >0 and $flMAJBase == true) {
		$action = '';
		$urlcompmail = '&etape=';
	}
	else $urlcompmail = '';
}

// ACTIONS POUR ENVOI SMS
if ('SMS' == 'SMS1' ) // poiur protéger le code tant que SMS n'a pas été terminé
{
if ($conf->ovh->enabled	  and strpos(GETPOST('etape','alpha'), 'M') === false ) {
	$id=$bull->id_client;
	$paramname='id_bull='.$bull->id.'&socid';
	$object = $bull;
	$bull->socid= $bull->id_client;
	$bull->fk_soc= $bull->id_client;
		
	$fl_PreInscrire = false;
	// Gestion Stripe
	//if ($conf->stripe and (!empty (GETPOST('sendSms' ,'alpha')) and $action <> 'send' and $action <> 'presend'))
	if ($conf->stripe->enabled and 	strpos(GETPOST('etape','alpha'), '3') >0 )
	{
		$object->Environ = 'Stripe';
		$wstr = new CglStripe ($db);
		$fl_PreInscrire = $wstr->GestionEnvoiDemandeStripe();
		unset ($wstr);
	}

	if (!empty(GETPOST('sendSms','alpha'))) $action = 'sendSms';
	$fl_modaction = false;
	include DOL_DOCUMENT_ROOT.'/custom/cglavt/core/actions_sendSMSs.inc.php';
	if ($bull->TiersTel == strtoupper($user->user_mobile)) $bull->TiersTel = '';
	if ($bull->TiersTel2 == strtoupper($user->user_mobile)) $bull->TiersTel2 = '';
	if ( $fl_modaction == true) {$action = 'sendSms';$fl_modaction =false; }
print 'Ne pas s_etonner, la Inscription-Préinscription est du même type que Mail , sera faite quand l_envoi SMS sera OK- Voir la partie qui sera supprimée dans MAIL == MAIL';
}
} //'SMS' == 'SMS1' 

// ACTIONS POUR GESTION SUIVI
if ('SUIVI' == 'SUIVI' and $conf->cahiersuivi) {
/*	$id=$bull->id_client;
	$paramname='id_bull='.$bull->id.'&socid';
	$object = $bull;
	$bull->socid= $bull->id_client;
	$bull->fk_soc= $bull->id_client;
*/
	include DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/core/actions_gestionSuivi.inc.php';
}

/* EDITION CONTRATS de LOCATION*/
	if (isset($action) and $action==$EDIT_CNTLOC){
	$ret = $TraitLocation->creer_contratLoc();
			if ($ret >= 0) {	
				$id_bulldet = '';
				$ancre = "#AncreLstDetail";
				$flMAJBase = true;
			}
}

// ANNULER le bulletin au statut ' En cours' 
 if (isset($action) and $action==$BUL_CONFANNULER and $confirm == 'yes') 	 {
	$bull->fetch_complet_filtre(100,$bull->id); // aller chercher les détails supprimés
	$ret = $TraitCommun->Conf_Annuler();
	if ($ret >= 0) {
		$id_contrat='';
		Header  ("Location: facturation.php?ecran=facture&type=Loc&rowid[".$bull->id."]=".$bull->id );
		exit();
	}
}

 if (isset($action) and $action==$BUL_CONFANULCLIENT and $confirm = 'yes') 	 {
	 	$bull->fetch_complet_filtre(100,$bull->id); // aller chercher les détails supprimés
		$ret = $TraitCommun->Conf_AnnuleParClient();
	if ($ret >= 0) {
		$id_bull="" ;	
		Header ("Location: listeloc.php?idmenu=16929&mainmenu=CglLocation&token=".newtoken() );
		exit();
	}
}


  if (isset($action) and $action==$BUL_CONFABANDON  and $confirm == 'yes') 	 {
	$ret = $TraitCommun->Conf_Abandon($bull,'abandon');
	if ($ret > 0) {
		$ancre = "";			
		$id_contrat='';
		header('Location: listeloc.php?idmenu=109&idmenu=16451&mainmenu=CglLocation');
		exit();
	}		
 }

/* CREATION NOUVEAU LIGNE CONTRAT  LOCATIONS */
if (!empty(GETPOST('BtEnrLigne','alpha'))){
	// enregistrer la ligne de contrat
	$ret = $TraitLocation->EnrLoDet();		

		if ($bull->statut > $bull->BULL_ENCOURS)		{
			if ($ActPartQte == 1) $ret = $cglInscDolibarr->DolibIndLocation('Maj');
			else {
				if ($bull->statut == $bull->BULL_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
				elseif ($bull->statut == $bull->BULL_PRE_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');
				}		
			}		
		
		$id_contratdet = '';
		if ($ret >= 0) {	
				$id_bulldet = '';
				$ancre = "#AncreLstDetail";
				$flMAJBase = true;
		}
}
	
// REMISE POUR PLUSIEURS PARTICIPATIONS 	
if (! empty($FctBtRemParticipation)) {
	//print  'Modifier remise des participations';
	if ($ret >= 0) {
	$ret = $TraitCommun->CreerRemise($RaisRemGen, $mttremisegen,$textremisegen);
	if ($bull->statut == $bull->BULL_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
	elseif ($bull->statut == $bull->BULL_PRE_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');
	if ($ret >= 0) {
			$ancre = "#AncreLstDetail";
			$flMAJBase = true;
		}
	}
}
	
/* PAIEMENT */ 
if ((isset($action) and $action==$PAIE_CONFNEGATIF and GETPOST('confirm' ,'alpha')=='yes') or
		(isset($action) and $action==$ACT_MAJ_PAIMT) and $PaimtMtt > 0) 	 {
	$TraitCommun = new CglCommunLocInsc($db);
	$ret = $TraitCommun->MajPaiement($id_contrat, $id_contratdet) ;

//	if ($ret == 0 and $bull->statut > $bull->BULL_ENCOURS) 	{	
	$ret = $cglInscDolibarr->DolibIndPaiement();
	
	if ($ret >= 0) {
		if ($bull->type == 'Insc' and $bull->statut < $bull->BULL_INS 
			and !empty($bull->TotalPaimnt()) AND $bull->TotalFac() > $bull->TotalPaimnt()){
				$bull->updateStat ($bull->BULL_PRE_INS,'');
			}
		else if ($bull->type == 'Insc' and $bull->statut < $bull->BULL_INS 
			and $bull->TotalFac() == $bull->TotalPaimnt()) {
				$bull->updateStat ($bull->BULL_INS,''); 
			}
		elseif ($bull->type == 'Loc' and  $bull->statut < $bull->BULL_VAL 
			and $bull->IsLocPaimtReserv())  		{					
				$bull->updateStat ($bull->BULL_VAL,'');
			}
		elseif ($bull->type == 'Loc' and  $bull->statut < $bull->BULL_VAL 
			and !empty($bull->TotalPaimnt()) and !$bull->IsLocPaimtReserv()) { 			
				$bull->updateStat ($bull->BULL_PRE_INSCRIT,'');
			}
		$ancre = "#AncrePaiement";
		$action = $ACT_SEL_PAIMT;
		$flMAJBase = true;
	}
	if ($ret < 0) $action =  $CRE_ENCAISS;	
} 
	
 /* TIERS et CONTRATLOC */	
$werrors = 0;
 $flgnvdoss = false;
if (isset($action) and ($action==$CREE_TIERS_BULL_DOSS or $action==$CREE_BULL_DOSS) ) { // Arrivée 1 par Inscription eou Suivi Tiers
	$retTiers = $wf->MajTiers($id_client);
	if ($retTiers < 0) 	{ $werrors++; setEventMessage($langs->trans("ErrEnrTiers"),"errors");}
	if ($conf->cahiersuivi) {
		if (empty($Refdossier) or  $Refdossier == -1) {
			require_once(DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/suivi_client.class.php');
			$dossier = new cgl_dossier ($db);
			$flgnvdoss = true;
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

 
 if (isset($action) and $action==$SEL_TIERS ) {	
	if ($id_client == -1 or empty($id_client) ) $action = $VIDE_TIERS;
 }

 if (isset($action) and $action==$VIDE_TIERS)  {
	$id_client='';
 }
 
  if (isset($action) and $action==$ENR_TIERS )
 {
	if (!$id_client)  	$id_client=$bull->id_client;
 	$ret = $wf->MajTiers($id_client);
 	if ($ret >= 0) $ret = $wf->UpdateTiersOrigine();	
	if ($ret >= 0) $ret = $bull->update_tel ('TiersTel', $TiersTel);
	if ($ret >= 0) $flMAJBase = true;
}

/* SUPPRIMER  LIGNES LOCATION */
if (isset($action) and  $action == 'ConfSUPActPartMulti') {

	if (!empty($tabrowid)) {
		$ret = $TraitCommun->ConfSupActPartMulti();
		if ($ret >= 0 and $bull->statut > $bull->BULL_ENCOURS) {
			if ($bull->statut >= $bull->BULL_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
			elseif ($bull->statut == $bull->BULL_PRE_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');
		}
		if ($ret >= 0) {	
			$id_bulldet = '';
				$ancre = "#AncreLstDetail";
				$flMAJBase = true;
		}		
	}			
}

if (isset($action) and ($action==$CONF_SUP_LOCDET ))	{
	$ret = $TraitLocation->ConfLocDet();
	if ($bull->statut > $bull->BULL_ENCOURS)	
		$ret = $cglInscDolibarr->DolibIndLocation('Sup');		
	if ($ret > 0) {	
		$id_contratdet = '';
		$ancre = "#AncreLstDetail";
		$flMAJBase = true;
	}
}

/* PAIEMENT */
if (isset($action) and $action==$CONF_SUP_PAIMT)	{
	$ret = $TraitCommun->ConfSupPaiement($id_contrat, $id_contratdet,$type, 'id_contrat', 'id_contratdet');
	if ($ret > 0) {
		if ($bull->statut > $bull->BULL_ENCOURS) $ret = $cglInscDolibarr->DolibIndPaiement();
		if ($ret >=0) {
			$ancre = "#AncrePaiement";
			$flMAJBase = true;
		}
	}
}		
  	

if (isset($action) and $action==$ENR_PROCREGL) {
	$ret = $TraitCommun->EnrProcRegl();
 	if ($ret >= 0 ) $flMAJBase = true;
}
 
// SUPPRESSION REMISE
if (isset($action) and $action==$ConfSUPRemFix )	{
	if ($ret> 0) {
		$ancre = "#AncreLstDetail";
		$flMAJBase = true;
	}		
}	
 
/* COMMANDE de CLOTURE */

  if (isset($action) and $action==$CNTLOC_CLOS) 	 {
	$ret = $TraitLocation->Clore();
	if ($ret >= 0) {
		header('Location: listematloue.php?type=materiel&idmenu=160&idmenu=16837&mainmenu=CglLocation&leftmenu=CglLocation');
		exit();
	}
 } 	
  
/* COMMANDE de DEPART FAIT */

  if (isset($action) and $action==$CNTLOC_DEPARTFAIT) 	 {
	$ret = $bull->Statut_DepartFait();
	if ($ret >= 0) {
		header('Location: listematloue.php?type=materiel&idmenu=160&idmenu=16837&mainmenu=CglLocation&leftmenu=CglLocation');
		exit();
	}
 } 	
/* COMMANDE de DEPART NON FAIT */

  if (isset($action) and $action==$CNTLOC_DEPARTNONFAIT) 	 {
	$ret = $bull->Statut_Reserver();
	if ($ret >= 0) {
			$ancre = "#AncrePaiement";
			$flMAJBase = true;	}
 } 	
 
/* COMMANDE de REOUVERTURE */

  if (isset($action) and $action==$CNTLOC_REOUVRIR) 	 {
	$ret = $TraitCommun->Reouvrir();
	if ($ret >= 0) {
		$flMAJBase=true;
	}
 } 
 
 /* COMMANDE de DESARCHIVAGE */

  if (isset($action) and $action==$CNTLOC_DESARCHIVER) 	 {
	$ret = $TraitCommun->Desarchiver();
	if ($ret >= 0) {
		$flMAJBase=true;
	}
 }
 
/* COMMANDE de DéRéservation */

  if (isset($action) and $action==$CNT_NONRESERVER) 	 {
	$ret = $TraitCommun->Dereservation();
	if ($ret >= 0) {
		$flMAJBase=true;
	}
 } 
// Enregistre un bulletin BULLNonFacturable
// Enregistre un bulletin BULLFacturable
if (isset($action) and ($action == $BULLFacturable or $action == $BULLNonFacturable)) {
//	if ($action == $BULLFacturable) $fl_BullFacturable = 'yes';
//	if ($action == $BULLNonFacturable) $fl_BullFacturable = 'no';
	if ($action == $BULLFacturable) $fl_BullFacturable = 1;
	if ($action == $BULLNonFacturable) $fl_BullFacturable = 0;	
	$ret = $bull->updatefacturable( $fl_BullFacturable);
}

// Renvoie l'URL simple
if ($flMAJBase == true) {
	// Filtres
	$urlcomplement = '&FiltrPasse='.$FiltrPasse.$urlcompmail;

	// Ancre
	$urlcomplement .= $ancre;
	Header('Location: '  . $_SERVER ['PHP_SELF'] . "?id_contrat=".$bull->id.$urlcomplement);
	exit();
}

// Gestion de la suppression des double guillements
$event_filtre_car_saisie = '';
$event_filtre_car_saisie .= " onchange='remplaceToutGuillements(this, event);return false;'";
// On privilégie le travail lors de la fin de la saisie, pour récupéré les copier/coller, plutot que le changement imédiat sur l'écran, pour lisibilité
//$event_filtre_car_saisie .= " onkeypress='remplaceGuillements(this, event);return false;'";



/*
------------------------------
AFFICHAGE
------------------------------
*/	
$wf->AfficheEcranEnvironnement("Recherche du client", 1, 'Loc');
require_once DOL_DOCUMENT_ROOT."/custom/cglavt/core/js/info_bulle.js";
 
if ("ACTION" == "ACTION") {
	 if ($action == $TYPE_SESSION) {
		$TraitLocation->EnrTypeSession();
	 } 
	 
	/* Informations générale 
	//if (isset($action) and $action==$ENR_LOCINFO) {*/
	if (!empty(GETPOST("BtEnrInfo",'alpha'))) {
			$TraitLocation->EnrInfoLoc();
	}
	// SUPPRESSION DEMANDE STRIPE NON ENCAISSEE
	if (isset($action) and ($action==$ACT_STRIPESUPP ))	{
		$wcst = new CglStripe ($db);
		$wcst->SupDemandeAcompte($id_stripe);
		unset($wcst);
	}

	if (isset($action) and ($action==$ACT_SUP_LOCDET ))	{
		$TraitLocation->SupLocDet();
	}	

	if (isset($action) and $action==$RETGENMAT) {
		$TraitLocation->RetGenMat();// retour général materiel
	}

	if (isset($action) and $action==$RETGENMATPART) {
		$TraitLocation->RetGenMatPart();// retour partiel materiel
	}

	if (isset($action) and  $action==$LOC_DUP )	{
		$id_contratdet = $TraitLocation->DupLocDet();
		if ($bull->statut == $bull->BULL_INS) $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
		elseif ($bull->statut == $bull->BULL_PRE_INS) $cglInscDolibarr->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');
	}
		
	// SUPPRESSION REMISE	
	if (isset($action) and $action==$ACT_SUP_REMFIX )	{
		$TraitCommun->SupRemFix();
	}

	/* SUPPRIMER PLUSIEURS PARTICIPATIONS */
	if (! empty($FctBtDelParticipation)) {
		$TraitCommun->SupActPartMulti();		
	}


	/* PAIEMENT */
	if (isset($action) and $action==$ACT_SUP_PAIMT)	{
		$TraitCommun->SupPaiement($id_contrat, $id_contratdet,$type, 'id_contrat', 'id_contratdet');
	}		
	
if (isset($action) and ($action==$ACT_MAJ_PAIMT) and $PaimtMtt <0)	{
	$TraitCommun = new FormCglCommun($db);
	 $TraitCommun->RaisonPaiementNegatif($id_contratdet, $PaimtMtt);
	if ($ret < 0)	$action = $ACT_SEL_PAIMT;
}
 
	// CAUTION
	//if (isset($action) and ($action==$UPD_MATMAD or $action == $CAL_ACPT ))	{
	if (!empty(GETPOST("BtEnrCaution",'alpha'))){
		//$TraitLocation->EnrMatMad($action);	
		//$TraitLocation->EnrLocRando($action);	
		$TraitLocation->EnrCautAccptt();
		$TraitLocation->EnrLocCaution();	
		if ($action==$UPD_MATMAD) $action = '';
	}

	if (isset($action) and $action==$RETGENMAD) {
		$TraitLocation->RetGenMad();
	}

	if (isset($action) and $action==$RETGENRAND) {
		$TraitLocation->RetGenRand();
	}

	if (isset($action) and $action==$UPD_LOC_RET)	{
		$TraitLocation->EnrLocRet();		
	}				
		
	//if (isset($action) and $action==$RET_LOC_RET)	{
	//	$TraitLocation->GenRetLocPart(); 		
	//}		
		
	if (isset($action) and $action==$RETGENCAUT)	{
		$TraitLocation->RetGenCaut();		
	}

	if (isset($action) and $action==$RETGEN)	{
		$TraitLocation->RetGenMat();
		$TraitLocation->RetGenRand(); 		
		$TraitLocation->RetGenCaut();	
		$TraitLocation->RetGenMad();	
	}

	if ($action == $CAL_ACPT) {
		$TraitLocation->Calc_Acpte();
	}


	/* COMMANDES de VALIDATION */
	  if (isset($action) and $action==$CNT_PRE_RESERVER) 	 {
		$TraitLocation->PreReserver();
	 }
	  if (isset($action) and $action==$CNT_RESERVER) 	 {
		$TraitLocation->Reserver();
	 }
	  if (isset($action) and $action==$CNT_DEPART) 	 {
		$TraitLocation->Depart();
	 }
	  if (isset($action) and $action==$CNT_RETOUR) 	 {
		$TraitLocation->Retour();
	 } 	
	 
	 if (isset($action) and $action==$BUL_ANNULER) 	 {
		$wf->Annuler();
	 }
	  if (isset($action) and $action==$BUL_ABANDON) 	 {
		$wf->Abandon();
	}	
	/* ANNULATION par le CLIENT */
	 if (isset($action) and $action==$BUL_ANULCLIENT) 	 {	   	  
		$ret = $wf->AnnuleParClient();
	}
	

	/* EDITION BON COMMANDE*/
	if ($bull->facturable and  isset($action) and $action==$EDT_CMD)	{
		$cglInscDolibarr->creer_bon_commande($bull->fk_commande);
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

		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'.$id_obj.'='.$bull->id.'&type='.$bull->type.'&Reftiers='.$bull->id_client.'&dossier='.$Refdossier.'&btaction='.$CONF_SUP_ECH.'&arg_idEchange='.$Idechange ,$titre,$question,$CONF_SUP_ECH,'','',2);
		unset ($form);
		unset ($wline_echange);
		print $formconfirm;	
	}

}

if ( ($id_contrat=="" or(!isset($id_contrat)) or is_null($id_contrat)) or $id_contrat ==-1 )
	 $wf->AfficheTrouveTiers();

else
{
	if ($id_contrat ) {
		unset($bull);
		$bull=new Bulletin($db);
		$bull->fetch_complet_filtre(-1, $id_contrat);
		// Vérification de la présence de toutes les ecritures
		if ($bull->statut > $bull->BULL_ENCOURS) $bull->TestPresenceEcriture(0); 
		$ret = $bull->CalculRegle();
		if ($ret >=0 and $bull->regle <> $bull->BULL_ARCHIVE and $bull->regle <> $bull->BULL_FACTURE ) {
				$bull->regle = $ret;
				$bull->updateregle($ret);
				}	
	}
	
	$wf->AfficheTiersBullInfo();

//	$FormLocation->AfficheLocDet();
	$FormLocation->AffLocDetRemCaution();
//	$FormLocation->AfficheLocMat_Rando_Cond();
if ($bull->facturable) {
		$wf->AffichePaiemRem($id_contrat, $id_contratdet, $type, 'id_contrat', 'id_contratdet');
		$wf->AfficheTotalFacture();	
	}
/*	Enlever car le changement de priorite du dossier en haut d'écran  n'entraine pas celui d'en bas et vice et versa
	if ($conf->cahiersuivi) {
		print '<div id="AffDossFondDePage" >';
		require_once  DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/html.suivi_client.class.php';
		require_once  DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/suivi_client.class.php';
		$wfdossier = new FormCglSuivi ($db);
		print '<span style="font-size:12px; font-weight:bold">'.$langs->trans('Dossier:').'&nbsp&nbsp&nbsp</span>'. $wfdossier->html_AffDossier($bull->fk_dossier, $bull->DosLib, $bull->fk_DosPriorite, 'priorite1');
		unset ($wfdossier);
		print '</div  >';
	}
	*/

	$wf->AfficheBoutonValidation();
	// Vérification construction pdf
//	$TraitCommun->rapatrie_pdf($bull, true);
	$wf->AfficheEdition();
	
//	if ($action == $PREP_MAIL or $action == $ACT_STRIPERELMAIL or!empty($_POST['modelselected'] or !empty($BtStripeMail)))  $wf->Preparation_Mail($BtStripeMail,  $id_stripe);
//	if ($action == $PREP_SMS or $action == $ACT_STRIPERELSMS or !empty($_POST['SMSmodelselected']  or !empty($BtStripeSMS))) $wf->Preparation_SMS($BtStripeSMS,  $id_stripe);


//	if (!empty(GETPOST('etape','alpha'))  and  $action == 'presend' and   

	if (GETPOST('etape','alpha') ==  CglStripe::STRIPE_MAIL_STRIPE )
		$wf->Preparation_Mail_Stripe($BtStripeMail, $id_stripe);
	elseif (empty($TopEnvoiMail) or $TopEnvoiMail == 'impossible') {
		if (!empty(GETPOST('etape','alpha'))  and
			(GETPOST('etape','alpha')==  CglStripe::STRIPE_MAIL_GENERAL
			or GETPOST('etape','alpha')==  CglStripe::STRIPE_REL_MAIL_STRIPE
			or GETPOST('etape','alpha')==  CglStripe::STRIPE_MAIL_APPLY
			or GETPOST('etape','alpha')==  CglStripe::STRIPE_MAIL_APPLY_STRIPE
			or GETPOST('etape','alpha')==  CglStripe::STRIPE_REL_MAIL_APPLY_STRIPE
			))
				$wf->Preparation_Mail($BtStripeMail, $id_stripe);
	}

	if (!empty(GETPOST('etape','alpha')) and  
		( GETPOST('etape','alpha')==  CglStripe::STRIPE_SMS_GENERAL
		or GETPOST('etape','alpha')==  CglStripe::STRIPE_SMS_STRIPE
		or GETPOST('etape','alpha')==  CglStripe::STRIPE_REL_SMS_STRIPE
		or GETPOST('etape','alpha')==  CglStripe::STRIPE_SMS_APPLY
		or GETPOST('etape','alpha')==  CglStripe::STRIPE_SMS_APPLY_STRIPE
		or GETPOST('etape','alpha')==  CglStripe::STRIPE_REL_SMS_APPLY_STRIPE
		))
			$wf->Preparation_SMS($BtStripeSMS,  $id_stripe);

}

// End of page
llxFooter();
$db->close();
?>
