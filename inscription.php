<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 été 2022 - Migration Dolibarr V15
 * Verson CAV 2.7.1 - automne 2022   gestion des erreurs au retour de  EnrActPart
 * Version CAV - 2.8 - hiver 2023 -
 *			- bulletin technique
 *			- Fenêtre modale pour modif pour echange
 *			- fiabilisation des foreach
 *			- reassociation BU/LO à un autre contrat
 *			- remise à plat des status BU/LO
 *			- suppression de la saise de la personne recours
 * Version CAV - 2.8.3 - printemps 2023
 *		- suppression des guillemets dans les textarea
 *		- ajout suppression echange dans pavesuivi
 *
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
 *   	\file       custum/cglinscription/inscription.php
 *		\ingroup   cglinscription
 *		\brief      Permet la saisie des bulletins 4 saisons
 */

 global $langs;
// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if ('INCLUDE' == 'INCLUDE') {
	if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
	//dol_include_once ("cglinscription/lib/cgl_variable.lib.php");

	require_once DOL_DOCUMENT_ROOT."/custom/cglavt/class/cglFctDolibarrRevues.class.php";
	require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/bulletin.class.php";
	require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/cglinscription.class.php";
	require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/html.formcommun.class.php";
	require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/html.forminscription.class.php";
	require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/cglcommunlocInsc.class.php";
	require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/cgldepart.class.php";
if ($conf->stripe->enabled) 	require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/cglstripe.class.php";
	require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/cgllocation.class.php";

	require_once DOL_DOCUMENT_ROOT."/custom/agefodd/class/agsession.class.php";
	require_once DOL_DOCUMENT_ROOT."/custom/agefodd/class/html.formagefodd.class.php";
	require_once DOL_DOCUMENT_ROOT."/custom/agefodd/class/agefodd_session_formateur.class.php"; 
	require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/html.formdepart.class.php"; 
	require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
	}

// Load traductions files requiredby by page
if ('TRADUCTION' == 'TRADUCTION') {
	$langs->load("companies");
	$langs->load("other");
	$langs->load('cglinscription@cglinscription');
	$langs->load('agefodd@agefodd');
}

if ('VARIABLE_GLOBALE' == 'VARIABLE_GLOBALE') {

	global $langs,$id_client, $action,  $db, $conf;
	global  $ENR_TIERS, $VIDE_TIERS, $SEL_TIERS, $CREE_BULL, $CREE_TIERS_BULL_DOSS, $CREE_BULL_DOSS ;	
	global $ACT_MAJ_PAIMT, $CRE_PARTICIPATION, $BtEncais, $BtStripeMail, $BtStripeSMS,  $id_stripe;
	global $ACT_INSCRIRE,  $BUL_ANNULER, $BUL_CONFANNULER, $BUL_DESARCHIVER, $BUL_ABANDON, $BUL_ANULCLIENT, $BUL_CONFANULCLIENT, $BUL_CONFABANDON, $PAIE_CONFNEGATIF, $PAIE_NEGATIF, $CRE_ENCAISS;
	global $BULLNonFacturable, $BULLFacturable;
	global $SEND_MAIL, $SEND_SMS, $PREP_MAIL , $PREP_SMS ,  $confim, $vientde;			

	global  $EDT_CMD, $EDT_BULL, $ENR_LOCINFO, $ActPartQte;
	global $ACT_STRIPESUPP, $CONF_STRIPESUPP, $ACT_STRIPEREMB, $ACT_STRIPERELMAIL, $ACT_STRIPERELSMS;
	
	global $id_bull,$Session,$id_part, $BullOrig, $bull, $id_bulldet, $tabrowid;	
	global $ACT_SEL_ACTPART,  $ACT_ENR_ACTPART, $EnrPart, $ACT_SUP_ACTPART  ,$CONF_SUP_ACTPART;
	global $CNTLOC_REOUVRIR,	$ACT_CRE_ACTPART , $CONF_ACT_SEL_PART;
	global  $FILTRDEPART, $ACT_SUP_REMFIX, $ConfSUPRemFix;
	global $Refdossier, $rdnvdoss, $nvdossier, $priorite;
	global $ACT_SEL_PERS_RESP, $ACT_MAJ_PERS_RESP, $ACT_PRE_INSCRIRE,  $ENR_DEPART, $CRE_DEPART, $SAIS_RDV, $ACT_SEL_PAIMT, $PaimtMtt, $CONF_SUP_PAIMT;
	global $FiltrPasse, $ActPartQte, $fk_ventil, $confirm;
	
	global $FctBtRemParticipation, $FctBtCreer, $FctBtMod, $id_actMod,$RaisRemGen, $mttremisegen, $textremisegen, $FctBtDelParticipation;
		global $StripeMailPayeur, $StripeMtt, $StripeNomPayeur, $libelleCarteStripe, $id_stripe, $modelmailchoisi;
}	
$mesg=''; $error=0; $errors=array();	

// Protection if external user
if ($user->societe_id > 0)	accessforbidden();

if ('VARIABLE' == 'VARIABLE') {
	 $TraitInscription = new CglInscription($db);
	 $TraitCommun = new CglCommunLocInsc($db);
	 $FormInscription  =new FormCglInscription($db);
	 $wf = new FormCglCommun ($db);
	 $cglInscDolibarr  = new cglInscDolibarr($db); 
	$TraitDepart = new CglDepart ($db);
}

$TraitInscription->Init();
$TraitCommun->debug_cav(); 

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
//if ('MAIL' == 'MAIL' and strpos(GETPOST('etape','alpha'), 'M')>= 0 )  {
	$TopEnvoiMail = '';
	if ('MAIL' == 'MAIL' and ( !empty(GETPOST('sendmail','alpha')) or  strlen(GETPOST("etape",'alpha')) == 2 ) )  {
	global $fl_PreInscrire;
	$id = $bull->id_client;

	$actiontypecode='AC_OTH_AUTO';
	$paramname='id_bull='.$bull->id.'&socid='.$bull->id_client;
	$object = New Societe($db);
	$id = $object->id = $bull->id_client;
	$object->SubMailStripe = 1;
	//if (empty($bull->TiersMail)) 	$bull->TiersMail = strtoupper($user->email);
	
	$fl_PreInscrire = false;
	// Gestion Stripe
	if ($conf->stripe->enabled and strlen(GETPOST('etape','alpha')) == 3 )
	{
		$object->Environ = 'Stripe';
		$wstr = new CglStripe ($db);
		$ret = $wstr->GestionEnvoiDemandeStripe();
		if ($ret >= 0) $fl_PreInscrire = $ret;
		unset ($wstr);
	}
	
	$object->stripeUrl = $bull->stripeUrl;
	if (!empty(GETPOST('sendmail','alpha'))) $action = 'send';
	include DOL_DOCUMENT_ROOT.'/custom/cglavt/core/actions_sendmails.inc.php';
	if ($bull->TiersMail == strtoupper($user->email)) $bull->TiersMail = '';
	if ($conf->stripe->enabled and strpos(GETPOST('etape','alpha'), '2') >0 )
	{		
		if ($fl_PreInscrire == true and $bull->type == 'Insc'   ) {
			 $action = 	$ACT_PRE_INSCRIRE;	
			$flMAJBase = false;
		}
		if ($fl_PreInscrire == true and $bull->type == 'Loc' ) {
			 $action = 	$CNT_PRE_RESERVER;	
			$flMAJBase = false;		
		}			
	}	
}

// ACTIONS POUR ENVOI SMS
if ('SMS' == 'SMS1' ) // poiur protéger le code tant que SMS n'a pas été terminé
{
if ($conf->ovh->enabled  and strpos(GETPOST('etape','alpha'), 'M') === false )    {					
	$id=$bull->id_client;
	$paramname='id_bull='.$bull->id.'&socid';
	$object = $bull;
	$bull->socid= $bull->id_client;
	$bull->fk_soc= $bull->id_client;
	
		
	$fl_PreInscrire = false;
	// Gestion Stripe
	if ($conf->stripe and (!empty (GETPOST('sendSMS' ,'alpha')) and $action <> 'sendSMS' and $action <> 'presend'))
	{
		$object->Environ = 'Stripe';
		$wstr = new CglStripe ($db);
		$fl_PreInscrire = $wstr->GestionEnvoiDemandeStripe();
		unset ($wstr);
	}

	if (!empty(GETPOST('sendSMS','alpha'))) $action = 'send';
	//$fl_modaction = false;
	include DOL_DOCUMENT_ROOT.'/custom/cglavt/core/actions_sendSMSs.inc.php';
	if ($bull->TiersTel == strtoupper($user->user_mobile)) $bull->TiersTel = '';
	if ($bull->TiersTel2 == strtoupper($user->user_mobile)) $bull->TiersTel2 = '';
	//if ( $fl_modaction == true) {$action = 'sendSMS';$fl_modaction =false; }
print 'Ne pas s_etonner, la Inscription-Préinscription est du même type que Mail , sera faite quand l_envoi SMS sera OK- Voir la partie qui sera supprimée dans MAIL == MAIL';
}
 } //'SMS' == 'SMS1' 

// ACTIONS POUR GESTION SUIVI
if ('SUIVI' == 'SUIVI' and $conf->cahiersuivi->enabled) {
	/*
	$id=$bull->id_client;
	$paramname='id_bull='.$bull->id.'&socid';
	$object = $bull;
	$bull->socid= $bull->id_client;
	$bull->fk_soc= $bull->id_client;
	*/
	include DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/core/actions_gestionSuivi.inc.php';
}


 
/* EDITION BULLETINS*/
if (isset($action) and $action==$EDT_BULL)	{
	$linetmp=$bull->RecherchePremLignBySess($Session);
	$ret = $TraitInscription->creer_bulletin($Session, $linetmp->activite_lieu);
			if ($ret >= 0) {	
				$id_bulldet = '';
				$ancre = "#AncreLstDetail";
				$flMAJBase = true;
			}
}

// ANNULER le bulletin au statut ' En cours' 
 if (isset($action) and $action==$BUL_CONFANNULER and $confirm = 'yes') 	 {
	 	$bull->fetch_complet_filtre(100,$bull->id); // aller chercher les détails supprimés
		$ret = $TraitCommun->Conf_Annuler();
	if ($ret >= 0) {
		$id_bull="" ;	
		Header ("Location: list.php" );
		exit();
	}
}


 if (isset($action) and $action==$BUL_CONFANULCLIENT and $confirm = 'yes') 	 {
	 	$bull->fetch_complet_filtre(100,$bull->id); // aller chercher les détails supprimés
		$ret = $TraitCommun->Conf_AnnuleParClient();
	if ($ret >= 0) {
		//Header ("Location: facturation.php?ecran=facture&type=Insc&rowid[".$bull->id."]=".$bull->id );
		Header ("Location: list.php?idmenu=16929&mainmenu=CglInscription&token=".newtoken() );
		$id_bull="" ;	
		exit();
	}
}

 
 if (isset($action) and $action==$BUL_CONFABANDON and $confirm = 'yes') 	 {
	$ret = $TraitCommun->Conf_Abandon($bull, 'abandon'); // la transmission dans Dolibarr et Agefodd est faite dans Conf_Abandon
	if ($ret > 0) {
		$ancre = "";
		$id_bull="" ;	
		Header ("Location: list.php?idmenu=16929&mainmenu=CglLocation&token=".newtoken() );
		exit();
	}		
} 

// CREATION NOUVELLE PARTICIPATION */
if (isset($action) and $action == $ACT_ENR_ACTPART)	{
	if (empty($confirm)) {
		if (empty($id_bulldet)) $flDepart_Complet = $TraitInscription->is_session_complete($id_act, 'Enregistre' );
		// Modification ou non dépassement de seuil de depart

		if (!empty($id_bulldet) or !$flDepart_Complet ) 	{
			$ret = $TraitInscription->EnrActPart();
			$ret = $bull->fetch_complet_filtre(-1,$bull->id);
			if ($bull->statut > $bull->BULL_ENCOURS and $ret <> -1)		{	
				if ($ActPartQte == 1 or empty($ActPartQte)) { 
				$ret = $cglInscDolibarr->DolibIndParticipation('Maj');
				}
				else			{							
					if ($bull->statut == $bull->BULL_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
					elseif ($bull->statut == $bull->BULL_PRE_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');
				}		
			}			
			// renvoyer l'URL sans les info pour créer un dossier, ainsi, on limitera les possibilité de créer des doublons
			if ($ret >= 0) {	
				$id_bulldet = '';
				$ancre = "#AncreLstDetail";
				$flMAJBase = true;
			}
		}
		// else voir après Header pour permettre affichage de la boite de confirmation
	}
	// En cas de confirmation sur dépassement de seuil de l'activité
	if ( $confirm == 'yes') {
		// Sur confirmation d'un dépassement de seuil du départ*/
		$ret = $TraitInscription->EnrActPart();	
		if ($ret >= 0) 
		{
			if ($bull->statut > $bull->BULL_ENCOURS and $ret <> -1)		{		
				if ($ActPartQte == 1) {
					$ret = $cglInscDolibarr->DolibIndParticipation('Maj');
				}
				else			{		
					if ($bull->statut == $bull->BULL_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
					elseif ($bull->statut == $bull->BULL_PRE_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');
				}		
			}
			// renvoyer l'URL sans les info pour créer un dossier, ainsi, on limitera les possibilité de créer des doublons
			if ($ret >= 0) {	
				$id_bulldet = '';
				$ancre = "#AncreLstDetail";
				$flMAJBase = true;
			}
			else 	$action = $ACT_SAISIEPARTICIPATION;	
		}
		else
			setEventMessage( $langs->trans('AbortNvPart').'Erreur:'.$ret, 'warnings');
	}
	elseif ( $confirm == 'no') 	{
		$action = $ACT_SAISIEPARTICIPATION;	
	}
}

// REMISE POUR PLUSIEURS PARTICIPATIONS 	
if (! empty($FctBtRemParticipation)) {
	//print  'Modifier remise des participations';
	$ret = $TraitCommun->CreerRemise($RaisRemGen, $mttremisegen,$textremisegen);
	if ($ret >= 0) {
		if ($bull->statut == $bull->BULL_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
		elseif ($bull->statut == $bull->BULL_PRE_INS) $ret = $cglInscDolibarr->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');
		if ($ret >= 0) {
			$ancre = "#AncreLstDetail";
			$flMAJBase = true;
		}
	}
}

/* CREATION/MISE A JOUR TIERS et BULLETIN */
if ('TiersBullDoss'=='TiersBullDoss') {
	$werrors = 0;
	 $flgnvdoss = false;
	 // CREATION OU MODIFICATION DU TIERS OU/ET DU DOSSIER
	if (!empty($action) and ($action==$CREE_TIERS_BULL_DOSS or $action==$CREE_BULL_DOSS) ) { // Arrivée 1 par Inscription ou Suivi Tiers ou activites du tiers
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

	 // CREATION DU BULLETIN
	if (!empty($action) and ($action==$CREE_TIERS_BULL_DOSS or $action==$CREE_BULL_DOSS or  $action==$CREE_BULL) )  {// Crée Bull
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
						$retdossier = $dossier->Maj_dossier ($Refdossier, $bull->ref, '' , '', 0, '', '', '', $user->id, null );
					if ($retinst <= 0) { $werrors++; setEventMessage($langs->trans("ErrNomDos"),"errors");		}
					unset ($dossier);
				}
			}
		}
		 if ($werrors == 0) $flMAJBase = true;
	}

	// SELECTION D'UN TIERS
	  
	if (isset($action) and $action==$SEL_TIERS ) {	
		if ($id_client == -1 or empty($id_client) ) $action = $VIDE_TIERS;
	 }

	// REVENIR A LA LISTE DE SELECTION TOTALE DES TIERS

	 if (isset($action) and $action==$VIDE_TIERS)  {
		$id_client='';
	 }

	 // ENREGISTRER LES MODIFICATIONS D'UN TIERS

	if (isset($action) and $action==$ENR_TIERS ) {
		if (!$id_client)  	$id_client=$bull->id_client;
		$ret = $wf->MajTiers($id_client);
		if ($ret >= 0) $ret = $wf->UpdateTiersOrigine();	
		if ($ret >= 0) $ret = $bull->update_tel ('TiersTel', $TiersTel);
		if ($ret >= 0) $flMAJBase = true;
	}

	/* SUPRESSION PARTICIPATIONS*/	
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

	if (isset($action) and ($action==$CONF_SUP_ACTPART ))	{
		$ret = $TraitInscription->ConfSupActPart();
		if ($ret and $bull->statut > $bull->BULL_ENCOURS)	{
				$ret = 	$cglInscDolibarr->DolibIndParticipation('Sup');	
		}
		if ($ret > 0) {	
			$id_bulldet = '';
			$ancre = "#AncreLstDetail";
			$flMAJBase = true;
		}	
	}
}//TiersBullDoss

/* PAIEMENT */
if (isset($action) and $action==$CONF_SUP_PAIMT)	{
	$ret = $TraitCommun->ConfSupPaiement($id_bull, $id_bulldet,$type, 'id_bull', 'id_bulldet');
	if ($ret > 0) {
		if ($bull->statut > $bull->BULL_ENCOURS) $ret = $cglInscDolibarr->DolibIndPaiement();
		if ($ret >0) {
			$ancre = "#AncrePaiement";
			$flMAJBase = true;
		}
	}
}

if ((isset($action) and $action==$PAIE_CONFNEGATIF and GETPOST('confirm' ,'alpha')=='yes') 
		 or (isset($action) and $action==$ACT_MAJ_PAIMT) and $PaimtMtt > 0) 	 {
	$TraitCommun = new CglCommunLocInsc($db);
	$ret = $TraitCommun->MajPaiement($id_bull, $id_bulldet) ;
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
/*		// si bulletin non réservé, prévenir
		if (($bull->type == 'Insc' and $bull->statut < $bull->BULL_PRE_INS and $bull->TotalFac() <> $bull->TotalPaimnt()) 
			or ($bull->type == 'Loc' and  $bull->statut < $bull->BULL_PRE_INSCRIT and !$bull->IsLocPaimtReserv())  
			) { 
				setEventMessage($langs->trans("WarningPaiementReservation"),'warnings');
		}
*/
		$ancre = "#AncrePaiement";
		$action = $ACT_SEL_PAIMT;
		$flMAJBase = true;
	}
	if ($ret < 0) $BtEncais =  $CRE_ENCAISS;
} 

if (isset($action) and $action==$ENR_PROCREGL) {
	$ret = $TraitCommun->EnrProcRegl();
 	if ($ret >= 0 ) $flMAJBase = true;
}

// SUPPRESSION REMISE		
if (isset($action) and $action==$ConfSUPRemFix )	{
	$ret = $TraitCommun->ConfSupRemFix();
	if ($ret> 0) {
		$ancre = "#AncreLstDetail";
		$flMAJBase = true;
	}		
}

/* PERSONNE RECOURS*/
if (isset($action) and $action==$ACT_MAJ_PERS_RESP)	{
	$ret = $TraitInscription->MajPersonRecours(1);
 	if ($ret> 0) {
		$ancre = "#AncreChoixPart";
		$flMAJBase = true;
	}		
}

/* RENDEZ-VOUS */
if ($action == $SAIS_RDV and  $_POST["rdv_update"]) {
	$ret = $TraitInscription->MajRdv();
	if ($ret > 0) {
		$ancre = "#AncreChoixPart";
		$flMAJBase = true;
	}		
}

 /* COMMANDE de VALIDATION */
 if (isset($action) and $action==$ACT_INSCRIRE) 	 {
	$TraitInscription->is_sessions_completes($bull );
	$ret = $TraitInscription->Inscrire();	
	if ($ret > 0) {
		$ancre = "#AncreLstDetail";
		$flMAJBase = true;
	}		
}
 
 if (isset($action) and $action==$ACT_PRE_INSCRIRE) 	 {	 
	$TraitInscription->is_sessions_completes($bull );
	$ret = $TraitInscription->PreInscrire();	
	if ($ret > 0) {
		$ancre = "#AncreLstDetail";
		$flMAJBase = true;
	}		
}

/* CREATION DEPART */
if (isset($action) and $action==$ENR_DEPART)	{
	$ret = $TraitDepart->Maj_depart();
 	if ($ret >= 0) $flMAJBase = true;
	else $action = $CRE_DEPART;
}

// INFO PRIVEE - déprécié
if (isset($action) and $action==$ENR_LOCINFO) {
  	$TraitInscription->EnrInfoPriv(); 	
	$flMAJBase = true;
}

// Enregistre un bulletin BULLNonFacturable
// Enregistre un bulletin BULLFacturable
if (isset($action) and ($action == $BULLFacturable or $action == $BULLNonFacturable)) {
//	if ($action == $BULLFacturable) $fl_BullFacturable = 'yes';
//	if ($action == $BULLFacturable) $fl_BullFacturable = 'yes';
	if ($action == $BULLFacturable) $fl_BullFacturable = 1;
	if ($action == $BULLNonFacturable) $fl_BullFacturable = 0;
	
	$ret = $bull->updatefacturable( $fl_BullFacturable);
}
// Dossier
if ($action == 'PaveSuivi')
{	
	$flMAJBase = true;
}

// Renvoie l'URL simple
if ($flMAJBase == true) {
	// Filtres
	$urlcomplement = '&FiltrPasse='.$FiltrPasse.$urlcompmail;
	// Ancre
	$urlcomplement .= $ancre;
	Header('Location:'  . $_SERVER ['PHP_SELF'] . "?id_bull=".$bull->id.$urlcomplement);
	exit();
} 


// Gestion de la suppression des double guillements
print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/custom/cglavt/core/js/lib_filtre_car_saisie.js"></script>'."\n";
global $event_filtre_car_saisie;
$event_filtre_car_saisie = '';
$event_filtre_car_saisie .= " onchange='remplaceToutGuillements(this, event);return false;'";
// On privilégie le travail lors de la fin de la saisie, pour récupéré les copier/coller, plutot que le changement imédiat sur l'écran, pour lisibilité
//$event_filtre_car_saisie .= " onkeypress='remplaceGuillements(this, event);return false;'";



// Debut affichage
$wf->AfficheEcranEnvironnement("Recherche du client", 1, 'Insc');
 
require_once DOL_DOCUMENT_ROOT."/custom/cglavt/core/js/info_bulle.js";
/* pour les COULEURS http://fr.wikipedia.org/wiki/Liste_de_couleurs*/

if ('ACTIONS' == 'ACTIONS') {
	/* BULLETIN */
	 
	/* PAIEMENT - si paiement négatif demande raison*/ 
	if (isset($action) and ($action==$ACT_MAJ_PAIMT) and $PaimtMtt <0)	{
		$TraitCommun = new FormCglCommun($db);
		 $TraitCommun->RaisonPaiementNegatif($id_bulldet, $PaimtMtt);
		if ($ret < 0)	$action = $ACT_SEL_PAIMT;
	}


	if ($action == $TYPE_SESSION) {
		$TraitInscription->EnrTypeSession();
	 }

	/* ACTIVITES - PARTICIPANTS - Confirmation dépassement départ*/
	if (isset($action) and $action == $ACT_ENR_ACTPART)	{	
			if ($flDepart_Complet)
					$TraitInscription->demandeConfDepassementSession ($id_act, 'Enregistre' );
	}
	 
	 // Choix dans le pavé des Participations
	if (isset($action) and $action == "Participations"  )	{
		if (! empty($FctBtCreer))  {
			if ( empty($confirm)) {
				$flDepart_Complet = $TraitInscription->is_session_complete($id_act ,'Ajoute');
				if ($flDepart_Complet) {		
						$TraitInscription->demandeConfDepassementSession ($id_act, 'Ajoute' );
						$action = 	'';
					}
				else  	$action =  $ACT_SAISIEPARTICIPATION;
			}
			elseif ( $confirm == 'yes') {
				$action =  $ACT_SAISIEPARTICIPATION;	
		
				}
		}

		// MODIFICATION de l'ACTIVITE des PLUSIEURS PARTICIPATIONS
		if  (! empty($FctBtMod)) {
			$TraitInscription->ChangeActiviteParticipation($id_actMod);
			
		}
	}

	// FILTRE pour ACTIVITES ANTERIEURES	
	if (isset($action) and $action==$FILTRDEPART )	{
		$TraitInscription->MajFiltreDepart();
		if ( empty($id_bulldet) or  $id_bulldet == 0) $action = $CRE_PARTICIPATION;
		else $action = $ACT_SEL_ACTPART;
	}
	
	// SUPPRESSION DEMANDE STRIPE NON ENCAISSEE
	if (isset($action) and ($action==$ACT_STRIPESUPP ))	{
		$wcst = new CglStripe ($db);
		$wcst->SupDemandeAcompte($id_stripe);
		unset($wcst);
	}

	// Gestion PARTICIPATION */			
	if (isset($action) and ($action==$ACT_SUP_ACTPART ))	{
		$TraitInscription->SupActPart();
	}

	// SUPPRESSION REMISE		
	if (isset($action) and $action==$ACT_SUP_REMFIX )	{
		$TraitCommun->SupRemFix();
	}

	/* SUPPRIMER PLUSIEURS PARTICIPATIONS */
		if (! empty($FctBtDelParticipation)) {
			$TraitCommun->SupActPartMulti();
		}


	/* SUPPRESSION PAIEMENT */
	if (isset($action) and $action==$ACT_SUP_PAIMT)	{
		$TraitCommun->SupPaiement($id_bull, $id_bulldet,$type, 'id_bull', 'id_bulldet');
	}		

	/* ANNULATION*/
	if (isset($action) and $action==$BUL_ANNULER)  $wf->Annuler();		

	/* COMMANDE de DESARCHIVAGE */
	if (isset($action) and $action==$BUL_DESARCHIVER) 	 {
		$ret = $TraitCommun->Desarchiver();
		if ($ret >= 0) {
			$flMAJBase=true;
		}
	}	 
	
	/* COMMANDE de REOUVERTURE */

	  if (isset($action) and $action==$CNTLOC_REOUVRIR) 	 {
		$ret = $TraitCommun->Reouvrir();
		if ($ret >= 0) {
			$flMAJBase=true;
		}
	 } 
	/* COMMANDE de DéInscription */

	  if (isset($action) and $action==$CNT_NONRESERVER) 	 {
		$ret = $TraitCommun->Reouvrir();
		if ($ret >= 0) {
			$flMAJBase=true;
		}
	 } 

	 /* ABANDON */
	 if (isset($action) and $action==$BUL_ABANDON) 	 {	  
		$ret = $wf->Abandon();
	}

	/* ANNULATION par le CLIENT */
	 if (isset($action) and $action==$BUL_ANULCLIENT) 	 {	  	  
		$ret = $wf->AnnuleParClient();
	 
	}
	
	/* EDITION BON COMMANDE*/
	if ($bull->facturable and isset($action) and $action==$EDT_CMD)	{
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

/*====   AFFICHAGE    =====*/	
if ( ($id_bull=="" or(!isset($id_bull)) or is_null($id_bull)) or $id_bull ==-1 )
	 $wf->AfficheTrouveTiers();
else {
	if ($id_bull )  	{
		unset($bull);
		$bull=new Bulletin($db);
		$bull->fetch_complet_filtre(-1, $id_bull);
		// Vérification de la présence de toutes les ecritures
		if ($bull->statut > $bull->BULL_ENCOURS) $bull->TestPresenceEcriture(0); 
		$ret = $bull->CalculRegle();
		if ($ret >=0 and $bull->regle <> $bull->BULL_ARCHIVE and $bull->regle <> $bull->BULL_FACTURE) {
				$bull->regle = $ret;
				$bull->updateregle($ret);
		}
	}
	if (GETPOST('FiltrPasse','int')== null) $FiltrPasse = $bull->filtrpass;
	$FormInscription->AfficheTiersBullDepart();

	$FormInscription->AfficheActivite_Participant();
	// Afficher la personne recours s'il y a des mineurs dans les participations
	//if ($bull->IsMineur()) $FormInscription->AffichePersonneRecours();
	if ($bull->facturable) 
		$wf->AffichePaiemRem($id_bull, $id_bulldet, $type, 'id_bull', 'id_bulldet');
	
	//$FormInscription->AfficheRemise();
	//$FormInscription->AfficheRdv();
		
	if ($bull->facturable) 
		$wf->AfficheTotalFacture();
	
/*	Enlever car le changement de priorite du dossier en haut d'écran  n'entraine pas celui d'en bas et vice et versa
	if ($conf->cahiersuivi) {
		print '<div id="AffDossFondDePage" >';
		require_once  DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/html.suivi_client.class.php';
		require_once  DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/suivi_client.class.php';
		$wfdossier = new FormCglSuivi ($db);
		print '<span  style="font-size:12px; font-weight:bold">'.$langs->trans('Dossier:').'&nbsp&nbsp</span>'. $wfdossier->html_AffDossier($bull->fk_dossier, $bull->DosLib, $bull->fk_DosPriorite, 'priorite1');
		unset ($wfdossier);
		print '</div  >';
	}
*/
	// Vérification construction pdf
//	$TraitCommun->rapatrie_pdf($bull, true);
	$wf->AfficheBoutonValidation();
			
		print '<table border="1"><tbody><tr><td>';
		print '<table class="liste"><tbody><tr><td>';

//	$FormInscription->AfficheBoutonValidation();
	$FormInscription->AfficheEdition();		// fin d'encapsulage  ayant démarré sur les boutons de validation
		print '</tbody></table >';
		
		print '</td></tr></tbody></table>';
		
		print '<a name="AncreBouton" id="AncreBouton"></a>';

// Pour un traitement commun avec Bulletin.Pb avec Edition
//	$wf->AfficheEdition();



//	if ($action == $PREP_MAIL or $action == $ACT_STRIPERELMAIL or !empty($_POST['modelselected'] or !empty($BtStripeMail)))  $wf->Preparation_Mail($BtStripeMail, $id_stripe);
//	if ($action == $PREP_SMS  or $action == $ACT_STRIPERELSMS or !empty($_POST['SMSmodelselected']  or !empty($BtStripeSMS)))  $wf->Preparation_SMS($BtStripeSMS,  $id_stripe);
 
	if (GETPOST('etape','alpha') ==  CglStripe::STRIPE_MAIL_STRIPE )
				$wf->Preparation_Mail_Stripe($BtStripeMail, $id_stripe);
	elseif (empty($TopEnvoiMail) or $TopEnvoiMail == 'impossible') {
		if (!empty(GETPOST('etape','alpha')) and 
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
llxFooter();
$db->close();
?>