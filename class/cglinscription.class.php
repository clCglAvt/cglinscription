<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
 *								- le montant du paiement par Stripe est un décimal et non plus un entier
 * Verson CAV 2.7.1 - automne 2022 - Bug sur création de participations en dépassement du seuil 
 *									- gestion des erreurs dans EnrActPart
 *									- technique : protection des foreach
 *									- correction de variable $line->rem inexistante, remplacer par line->remise_percent
 * Version CAV - 2.8 - hiver 2023 -
 *			- bulletin technique
 *			- Fenêtre modale pour modif pour echange
 *			- remise à plat des status BU/LO 
 *			- Edition en un tableau des poids/taille/age/prenom
 * Version CAV - 2.8.3 - printemps 2023
 *		- ajout suppression echange dans pavesuivi
 * Version CAV - 2.8.5 - printemps 2023
 *		- vérification à la volée des conflit de vélo  (308b)
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
 *   	\file       custum/cglinscription/class/cglinscription.class.php
 *		\ingroup    cglinscription
 *		\brief      Objet permettant le rapatriement des données de Dolibarr vers Inscription
 */

 /**************************/
 
require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/core/modules/cglinscription/modules_cglinscription.php";
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agsession.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agefodd_session_formateur.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agefodd_session_calendrier.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agefodd_session_formateur_calendrier.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctDolibarrRevues.class.php';

	
/**
 *	Put here description of your class
 */
class CglInscription 
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='skeleton';			//!< Id that identify managed objects
	var $table_element='skeleton';		//!< Name of table without prefix where object is stored

    var $id;
    var $prop1;
    var $prop2;
	var $FormInscription;
	//...

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
		$this->type_session_cgl = 2;
        return 1;
    }

	/**
	 * affiche un champs select contenant la liste des sessions en partance.
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	 
	 function Init()
	{		
		global $langs, $bull, $idmenu, $conf;	

		// Load traductions files requiredby by page
		//$langs->load("companies");
		//$langs->load("other");
		// Variables globales
		global $CRE_PARTICIPATION , $ACT_SAISIEPARTICIPATION;
		global $FILTRDEPART, $MOD_LOCINFO, $ENR_LOCINFO;
		global $ACT_SEL_ACTPART, $ACT_SUP_ACTPART , $BtEncais, $BtStripeMail, $BtStripeSMS, $BtStripmail , $ACT_ANNULPART;
		global $ACT_ENR_ACTPART, $ACT_CRE_PERS_RESP, $ACT_SEL_PERS_RESP, $ACT_MAJ_PERS_RESP,  $CRE_PMTLIGNE, $ENR_PROCREGL, $ACT_MAJ_PAIMT , $ACT_SEL_PAIMT;
		global $MAJ_TIERS,   $TYPE_SESSION, $SAIS_RDV, $ACT_SUP_PAIMT;		
		global $ENR_TIERS, $VIDE_TIERS, $SEL_TIERS, $CREE_BULL , $CREE_TIERS_BULL_DOSS , $CREE_BULL_DOSS;

	    global  $RaisRemGen, $ACT_SUP_REMFIX, $ConfSUPRemFix;
		global $SAIS_REMISEGENREALE, $UPD_REMFIX, $MOD_REMFIX,$mttremisegen, $textremisegen, $mttremisefix,$actremgen, $modactrdv, $RaisRemFix;
		global $ACT_INSCRIRE, $ACT_PRE_INSCRIRE, $BUL_ANNULER,$BUL_CONFANNULER, $BUL_CONFABANDON, $BUL_ABANDON;
		global $CNTLOC_REOUVRIR, $BUL_ANULCLIENT, $CNT_NONRESERVER;
		global $BUL_CONFANULCLIENT, $BUL_DESARCHIVER,  $EDT_CMD, $EDT_BULL,  $CONF_SUP_ACTPART, $CONF_SUP_PAIMT;
		global $CRE_DEPART, $ENR_DEPART, $MAJ_DEPART;
		global $PAIE_CONFNEGATIF, $PAIE_NEGATIF;
		global $PREP_MAIL,$PREP_SMS, $SEND_SMS,  $SEND_SMS,  $SEND_MAIL	;
		global $ACT_STRIPESUPP, $CONF_STRIPESUPP, $ACT_STRIPEREMB, $ACT_STRIPERELMAIL, $ACT_STRIPERELSMS;
		global $BULLNonFacturable, $BULLFacturable;
		
		global $FctBtRemParticipation, $FctBtCreer, $FctBtMod, $EnrPart, $tabrowid, $FctBtDelParticipation;
		global $confirm, $vientde;
		global $id_client, $action, $id_bull, $id_bulldet, $fl_BullFacturable, $Session,$db, $type, $InfoPrive, $ActionFuture, $PmtFutur, $id_depart;
		global $FiltrPasse;
		global $tiersNom, $TiersVille, $TiersIdPays, $TiersTel,$TiersTel2, $TiersMail,$AuthMail, $TiersAdresse, $TiersCP, $TiersOrig, $Villegiature;
		global $firstname, $civility_id, $Refdossier, $nvdossier, $rdnvdoss, $priorite, $prioritedossier;
		global $id_contact, $id_act,  $id_actMod;
		global $PartNom, $PartPrenom, $PartAdresse, $PartDateNaissance, $PartTaille,  $PartPoids, $PartENF, $PartTel, $PartMail,$id_part, $PartCiv;
		global $PartAge, $PartDateInfo;
		global $ActPartPU, $ActPartPT, $ActPartRem , $ActPartQte, $ActPartObs, $ActPartIdRdv, $NomPrenom;
		global $ActLibelle, $ActMoniteur, $ActNbPlace, $ActnbInscrit, $ActnbPreInscrit   ;
		global $ActPartRang ,$TypeSessionCli_Agf;
		global $id_persrec, $PersNom, $PersPrenom, $PersTel, $PersParent,$PersCiv;
		global $PaimtMode, $PaimtOrg, $PaimtNomTireur, $PaimtMtt, $PaimtDate, $PaimtCheque,$id_paimt, $PaimtNeg;
		global $place, $formation, $intitule_custo, $TypeSessionDep_Agf, $session_status, $DepartDate, $nb_place, $notes;
		global  $moniteur_id , $HeureFin, $HeureDeb, $type_tva;
		global $PrixAdulte, $PrixEnfant, $PrixExclusif, $PrixGroupe, $DureeAct, $rdvprinc, $alterrdv, $fk_ventil, $tbrowid;
		
		global $StripeMailPayeur, $StripeMtt, $StripeNomPayeur, $libelleCarteStripe , $id_stripe, $modelmailchoisi, $StripeSmsPayeur;

		
		
		$type = 'Insc';
		// Get parameters
		$action	= GETPOST('action','alpha');
		$fl_BullFacturable=GETPOST('BullFacturable');	
		
		$wfcom =  new CglFonctionCommune ($this->db);
		// Constantes
		// action de l'écran
		$VIDE_TIERS='VidTiers';
		$SEL_TIERS='SelTiers';
		$CREE_BULL='CreeBull';
		$MAJ_TIERS='MajTiers';	
		$ENR_TIERS='EnrTiers';
		$FILTRDEPART="FiltreDepart";
		$MOD_LOCINFO='ModLocInfo';
		$ENR_LOCINFO='EnrLocInfo';
		$CREE_TIERS_BULL_DOSS='CreBullTiersDoss';
		$CREE_BULL_DOSS='CreeBullDos';
		
		$CRE_PARTICIPATION='Cre_participation';
		$ACT_SEL_ACTPART='SelActPart';
		$ACT_SUP_ACTPART='SUPActPart';
		$ACT_ANNULPART='AnnulPart';
		$ACT_SAISIEPARTICIPATION='SaisiePart';
		
		$CONF_SUP_ACTPART='ConfSUPActPart';
		$ACT_ENR_ACTPART='EnrActPart';	

		$ACT_CRE_PERS_RESP='CrePersResp';
		$ACT_SEL_PERS_RESP='SelPersResp';
		$ACT_MAJ_PERS_RESP='MajPersResp';
		$SAIS_RDV='ChoixRdv';
		$SAIS_REMISEGENREALE='RemiseGlobale'; 
		$UPD_REMFIX='RemFix';
		
		$ConfSUPRemFix	= 'ConfSUPRemFix';
		$ACT_SUP_REMFIX	= 'SupRemFix';
		
		$CRE_PMTLIGNE='PmtLigne';		
		$ENR_PROCREGL='MajProcRegl';
		$ACT_MAJ_PAIMT='MajPaimt';
		$ACT_SUP_PAIMT='SupPaimt';
		$CONF_SUP_PAIMT='ConfSUPActPaimt';	
		$ACT_SEL_PAIMT='SelPaimt';
		$ACT_INSCRIRE='Inscrire';
		$ACT_PRE_INSCRIRE='PreInscrire';
		$BUL_ANNULER='Annuler';
		$BUL_CONFANNULER='Conf_Annuler';
		$CNT_NONRESERVER="DeReservation";
		
		$BUL_DESARCHIVER='Desarchiver';
		$CNTLOC_REOUVRIR='Reouvrir';
		$BUL_ABANDON='Abandonner';		
		$BUL_ANULCLIENT='AnnulClient';
		$BUL_CONFANULCLIENT='Conf_AnnulClient';
		$BUL_CONFABANDON='Conf_Abandonner';
		$PAIE_CONFNEGATIF='RaisonPaiementNeg';
		$PAIE_NEGATIF='DemRaisonPaiementNeg';

		$EDT_CMD = 'CreerCommande';
		$EDT_BULL = 'CreerBulletin';
		$TYPE_SESSION = 'EnrType_Session';
		$CRE_DEPART='creerdepart';
		$ENR_DEPART='enregistrerdepart'; // en commun avec  fichedepart.php
		$MAJ_DEPART='majdepart';
		
		
		$ACT_STRIPESUPP='StripeSupp';
		$ACT_STRIPEREMB='StripeRemb';
		$ACT_STRIPERELMAIL='StripeMail';
		$ACT_STRIPERELSMS='StripeSms';
		$CONF_STRIPESUPP='ConfStripeSupp';
		

		$PREP_MAIL='presend';
		$PREP_SMS='presendsms';
		$SEND_MAIL='send';	
		$SEND_SMS='sendSMS';	
		
		$BULLNonFacturable='NonFacturable';
		$BULLFacturable='Facturable';
	
		$tabrowid = array(); 
		$tabrowid = GETPOST("rowid", 'array');
		
		$FctBtCreer	= GETPOST('FctBtCreer','alpha');
		$FctBtMod	= GETPOST('FctBtMod','alpha');
		$FctBtRemParticipation	= GETPOST('FctBtRemParticipation','alpha');
		$FctBtDelParticipation	= GETPOST('FctBtDelParticipation','alpha');
		$BtEncais= GETPOST('BtEncais','alpha');
		$BtStripeMail= GETPOST('BtStripeMail','alpha');
		$BtStripeSMS= GETPOST('BtStripeSMS','alpha');
		//$BtStripmail=GETPOST("BtStripmail", 'alpha');
		$id_stripe=GETPOST('id_stripe','int');			
	
		$StripeNomPayeur=GETPOST('StripeNomPayeur','alpha');
			$StripeMailPayeur=GETPOST('StripeMailPayeur', 'alpha');
			if (!empty($StripeMailPayeur)) $_POST['sendto'] = $StripeMailPayeur;
			$StripeSmsPayeur=GETPOST('StripeSmsPayeur','alpha');
			$StripeMtt=GETPOST('StripeMtt','decimal');	
			$modelmailchoisi=GETPOST('modelmailstripe','int');	
			
		$libelleCarteStripe=GETPOST('libelleCarteStripe','alpha');
			if (empty($modelmailchoisi) or $modelmailchoisi == -1) $modelmailchoisi =GETPOST('modelmailselected','int');	 
			
		
			//$modelmailselected=GETPOST('selectmodelmailselected');
//			if (!empty($_SESSION['modelmailselected'])) $modelmailselected  =	$_SESSION['modelmailselected'];
//		}
		
		if (!empty(GETPOST('sendmail','alpha') )) // on va envoyer le message, on récupère les valeurs de _SESSION
		{
			 $StripeNomPayeur  =	$_SESSION['StripeNomPayeur'];
			  $StripeMailPayeur  =	$_SESSION['StripeMailPayeur'];
			  $libelleCarteStripe  =	$_SESSION['libelleCarteStripe'];			  
			  $StripeSmsPayeur  =	$_SESSION['StripeSmsPayeur'];
			  $StripeMtt  =	$_SESSION['StripeMtt'];
			  $modelmailchoisi  =	$_SESSION['modelmailchoisi'];
			 if (empty($id_stripe)) $id_stripe  =	$_SESSION['id_stripe'];
		}
		
		if (( $action == 'presend' and empty(GETPOST('modelselected','int')))//Préparer le mail
			or !empty(GETPOST('sendmail','alpha') )  or !empty(GETPOST('sendSMS','alpha') )// Envoi mail prêt
			or ( ( !empty($BtStripeSMS) or !empty($BtStripeMail) or $action == $ACT_STRIPERELMAIL ) and empty(GETPOST('modelselected','int')) and empty(GETPOST('sendmail','alpha') )))		 // Preparer Mail de demande Stripe ou relance	
			// vider les valeurs de _SESSION
			{
				unset($_SESSION['StripeNomPayeur']); 
				unset($_SESSION['libelleCarteStripe']); 				
				unset($_SESSION['StripeMailPayeur']);  
				unset($_SESSION['StripeSmsPayeur']);  
				unset($_SESSION['StripeMtt']); 
				unset($_SESSION['modelmailchoisi']);
		}

		$EnrPart 	= GETPOST('EnrPart','alpha');
		$confirm	= GETPOST('confirm','alpha');
		$vientde= GETPOST('vientde','alpha');
		
		$idmenu	= GETPOST('idmenu','int');
		if (empty($idmenu)) $idmenu = 163;
		$id_client	= GETPOST('id_client','int');
		$id_bull	= GETPOST('id_bull','int');
		$idbull		= GETPOST('idbull','int');
		$btaction	= GETPOST('btaction','alpha');
				
		if ($conf->cahiersuivi) {
			if (empty($id_bull) and !empty($idbull) and $btaction == 'Supprime') // défini dans html_pavé de html_suivi_client.class.php
			{
				$id_bull = $idbull;
				$action = 'SupEchange'; // défini dans actions_gestionSuivi.inc.php
			}
		}
		
		// départ	
		$id_depart	= GETPOST('id_depart','int');
		
		$id_bulldet	= GETPOST('id_bulldet','int');
		$id_paimt	= GETPOST('id_paimt','int');
		$PaimtNeg	= GETPOST('PaimtNeg','alpha');
		$id_persrec	= GETPOST('id_persrec','int');

		
		if ($action == $CREE_BULL) {// Arrivée par Suivi Dossier - dossier connu
				$Refdossier = GETPOST('dossier','int'); 
		}
		//
		$firstname	= GETPOST('firstname','alpha');
		$civility_id	= GETPOST('civility_id','alpha');
		$tiersNom	= GETPOST('tiersNom','alpha');
		$TiersVille	= GETPOST('TiersVille','alpha');
		$TiersIdPays	= GETPOST('TiersIdPays','int');		
		$TiersTel	= GETPOST('TiersTel','alpha');
		$TiersTel2	= GETPOST('options_s_tel2','alpha');
		$TiersMail	= GETPOST('TiersMail','alpha');
		$AuthMail	= GETPOST('AuthMail','int');
		$TiersAdresse = GETPOST('TiersAdresse','alpha');
		$TiersAdresse	= $wfcom->cglencode($TiersAdresse);
		$Villegiature = GETPOST('Villegiature','alpha');
		$Villegiature	= $wfcom->cglencode($Villegiature);
		$TiersCP 	= GETPOST('TiersCP','int');
		$id_act		= GETPOST('id_act','int');
		$id_actMod		= GETPOST('id_actMod','int');
		$id_part 	= GETPOST('id_part','int');
		$ActPartObs	= GETPOST('ActPartObs','text');
		$ActPartObs	= $wfcom->cglencode($ActPartObs);
		$TiersOrig 	= GETPOST('TiersOrig','int');
		$TypeSessionCli_Agf = GETPOST('TypeSessionCli_Agf','int');
		$ActPartIdRdv	= GETPOST('ActPartIdRdv','int');
		$PartNom 	= GETPOST('PartNom','alpha');
		$PartCiv 	= GETPOST('PartCiv','alpha');
		$PartPrenom 	= GETPOST('PartPrenom','alpha');
		$PartAdresse 	= GETPOST('PartAdresse','alpha');
		$PartAdresse	= $wfcom->cglencode($PartAdresse);
		
		$PartDateNaissance 	= GETPOST('PartDateNaissance','date');
		$PartTaille 	= GETPOST('PartTaille','alpha');
		$PartPoids 	= GETPOST('PartPoids','alpha');
	
		$PartENF 	= GETPOST('PartENF','int');	
		$PartAge	= GETPOST('PartAge','int');	
		$PartDateInfo	= GETPOST('PartDateInfo','int');	
		$PartMail	= GETPOST('PartMail','alpha');
		$PartTel	= GETPOST('PartTel','alpha');
		$ActPartPU	= GETPOST('ActPartPU','decimal');
		$ActPartPT	= GETPOST('ActPartPT','decimal');
		$ActPartRem	= GETPOST('ActPartRem','decimal');
		$ActPartQte	= GETPOST('ActPartQte','int');
		if ($action == $FctBtCreer) $ActPartQte = 1;
		$ActPartRang	= GETPOST('ActPartRang','int');
		$PersNom 	= GETPOST('PersNom','alpha');
		$PersPrenom	= GETPOST('PersPrenom','alpha');
		$NomPrenom 	= GETPOST('NomPrenom','alpha');
		$PersTel	= GETPOST('PersTel','alpha');
		$PersCiv	= GETPOST('PersCiv','alpha');
//	print '<p>DEBUG INIT - PersCiv:'.$PersCiv.'</p>';
		$modactrdv	= GETPOST('modactrdv','int');
		$mttremisegen= GETPOST('mttremisegen','decimal');
		$textremisegen= GETPOST('textremisegen','alpha');
		$actremgen	= GETPOST('actremgen','int');
		$RaisRemGen	= GETPOST('RaisRemGen','alpha');
		$RaisRemGen	= $wfcom->cglencode($RaisRemGen);
	
		$PersParent	= GETPOST('PersParent','alpha');
		$PaimtMode	= GETPOST('PaimtMode','alpha');
		$PaimtCheque	= GETPOST('PaimtCheque','alpha');
		$PaimtOrg	= GETPOST('PaimtOrg','alpha');
		$PaimtNomTireur	= GETPOST('PaimtNomTireur','alpha');
		$PaimtMtt 	= GETPOST('PaimtMtt','decimal');
		$PaimtDate 	= GETPOST('PaimtDate','date');
		if (strlen(substr($PaimtDate,6)) == 2) $PaimtDate = substr($PaimtDate,0,6).(int)'20'.substr($PaimtDate,6,2);
		$Session 	= GETPOST('session','int');
		
		$InfoPrive	= GETPOST('InfoPrive','alpha');
		$InfoPrive	= $wfcom->cglencode($InfoPrive);
		$ActionFuture	= GETPOST('ActionFuture','alpha');
		$ActionFuture	= $wfcom->cglencode($ActionFuture);
		$PmtFutur	= GETPOST('PmtFutur','alpha');
		$PmtFutur	= $wfcom->cglencode($PmtFutur);
		$FiltrPasse= GETPOST('FiltrPasse','int');
		if (empty($FiltrPasse)) $FiltrPasse = 0;
		// RECUPERATION DONNES Depart
		$PrixAdulte 	= GETPOST('PrixAdulte','decimal');
		$PrixEnfant 	= GETPOST('PrixEnfant','decimal');
		$PrixExclusif 	= GETPOST('PrixExclusif','decimal');
		$PrixGroupe 	= GETPOST('PrixGroupe','decimal');
		$DureeAct = GETPOST('DureeAct','decimal');		
		$place		= GETPOST('place','int');
		$formation	= GETPOST('formation','int');
		$intitule_custo	= GETPOST('intitule_custo','alpha');
		$intitule_custo	= $wfcom->cglencode($intitule_custo);
		$TypeSessionDep_Agf	= GETPOST('TypeSessionDep_Agf','int');
		$nb_place	= GETPOST('nb_place','int');
		$notes	= GETPOST('notes','alpha');
		$notes	= $wfcom->cglencode($notes);
		$session_status	= GETPOST('session_status','int');
		$rdvprinc	= GETPOST('rdvprinc','alpha');
		$alterrdv	= GETPOST('alterrdv','alpha');
		$rdvprinc	= $wfcom->cglencode($rdvprinc);
		$alterrdv	= $wfcom->cglencode($alterrdv);
		
		//$DepartDate 	= GETPOST('HeureFin','alpha'); // format JJ/MM/AAAA
		$DepartDate 	= GETPOST('HeureDeb','alpha'); // format JJ/MM/AAAA		
		if (strlen(substr($DepartDate,6)) == 2) $DepartDate = substr($DepartDate,0,6).(int)'20'.substr($DepartDate,6,2);

		$HeureDeb = GETPOST('HeureDebhour','alpha').':'.GETPOST('HeureDebmin','alpha');
		$HeureFin = GETPOST('HeureFinhour','alpha').':'.GETPOST('HeureFinmin','alpha');
		
		$moniteur_id= GETPOST('moniteur_id','int');
		$type_tva= GETPOST('type_tva','int');
		$fk_ventil= GETPOST('fk_ventil','int');	
			
				
		$paramsBull = "&amp;search_client=".$id_client."&amp;id_bull=".$id_bull."&amp;ActionFuture=".$ActionFuture;
		$paramsTiers = "&amp;action=".$action."&amp;search_client=".$id_client."&amp;tiersNom=".$tiersNom."&amp;TiersVille=".$TiersVille."&amp;TiersIdPays=".$TiersIdPays;
		$paramsTiers .= "&amp;TiersTel=".$TiersTel."&amp;TiersTel2=".$TiersTel2."&amp;TiersMail=".$TiersMail."&amp;TiersAdresse=".$TiersAdresse;
		$paramsActPart = "&amp;id_act=".$id_act."&amp;id_part=".$id_part;
		$paramsPart = "&amp;id_part=".$id_part."&amp;PartNom=".$PartNom."&amp;PartPrenom=".$PartPrenom."&amp;PartDateNaissance=".$PartDateNaissance;
		$paramsPart .= "&amp;id_bulldet=".$id_bulldet."&amp;PartTaille=".$PartTaille."&amp;PartPoids=".$PartPoids."&amp;PartENF=".$PartENF."&amp;PartTel=".$PartTel."&amp;ActPartPU=".$ActPartPU;
		$paramremise = "&amp;RaisRemGen=".$RaisRemGen."&amp;textremisegen=".$textremisegen;
		$paramsPart .= "&amp;ActPartPT=".$ActPartPT."&amp;ActPartRem=".$ActPartRem."&amp;ActPartQte=".$ActPartQte."&amp;ActPartRang=".$ActPartRang."&amp;ActPartRang=".$PartTel;
		$paramsPersRec = "&amp;id_persrec=".$id_persrec."&amp;PersNom=".$PersNom."&amp;PersPrenom=".$PersPrenom."&amp;id_client=".$id_client;
		$paramsPersRec .= "&amp;PersTel=".$PersTel."&amp;PersParent=".$PersParent."&amp;id_age_contact=".$id_age_contact;
		$paramsPaiement = "&amp;PaimtMode=".$PaimtMode."&amp;PaimtOrg=".$PaimtOrg."&amp;PaimtNomTireur=".$PaimtNomTireur;
		$paramsPaiement .= "&amp;PaimtMtt=".$PaimtMtt."&amp;Paimtdate=".$PaimtDate."&amp;id_paimt=".$id_paimt;
		$paramsPaiement .= "&amp;InfoPrive=".$InfoPrive."&amp;PaimtNeg=".$PaimtNeg."&amp;PmtFutur=".$PmtFutur;
	
		$bull=new Bulletin($db);
		if ($id_bull ) 
		{
			$bull->fetch_complet_filtre(-1, $id_bull);
		}	
	
		unset ($wfcom);
	} /*  init */	
	
	/*
	* Bascule  le depart de groupe à individuel
	*
	*/
	function EnrTypeSession()
	{
		global $bull, $TypeSessionCli_Agf;			
		//$bull->type_session_cgl = $TypeSessionCli_Agf + 1;
		if ($bull->type_session_cgl == 1) $bull->type_session_cgl =2;
		else $bull->type_session_cgl = 1;

		$bull->updateTypesession();
	} //EnrTypeSession
	function RecupAct($idact, $line)
	{ 
		global  $db, $bull;
		$sql='';
		$sql.='select s.rowid as id, intitule_custo, nb_place, ';
//		$sql .= "(select SUM(qte) from ".MAIN_DB_PREFIX."cglinscription_bull as b , ".MAIN_DB_PREFIX."cglinscription_bull_det as bds ";
//		$sql .= " 		where bds.action = 'A' and bds.fk_bull = b.rowid and b.statut < ".$bull->BULL_INS." and ";
//		$sql .= "			type = 0 and bds.fk_activite = s.rowid) as activite_nbencrins, ";				
//		$sql .= "(select COUNT(rowid) from ".MAIN_DB_PREFIX."agefodd_session_stagiaire as sst  where sst.fk_session_agefodd = s.rowid and  status_in_session  = 0) as nb_preinscrit, ";
//		$sql .= "(select COUNT(rowid) from ".MAIN_DB_PREFIX."agefodd_session_stagiaire as sst  where sst.fk_session_agefodd = s.rowid and  status_in_session  = 2) as nb_stagiaire, ";

		$sql.=' p.ref_interne,   dated, heured , heuref,';
		$sql.=" case when isnull(cf.lastname) then cu.lastname else cf.lastname end as MonNom,  ";
		$sql.=" case when isnull(cf.firstname) then cu.firstname else cf.firstname end as MonPrenom, ";
		$sql.=" case when isnull(cf.phone_mobile) then cu.user_mobile else cf.phone_mobile end as MonTel, ";
		$sql.=" case when isnull(cf.phone) then cu.office_phone else cf.phone end as Monperso, ";
		$sql.=" case when isnull(cf.email) then cu.email else cf.email end as MonMail, ";
		$sql.=' se.s_PVIndAdl as DepartPV_Adlt, sce.s_PVIndAdl as ActivitePV_Adlt,  pe.s_PVIndAdl as ProduitPV_Adlt,';
		$sql.=' se.s_PVIndEnf as DepartPV_Enf, sce.s_PVIndEnf as ActivitePV_Enf,  pe.s_PVIndEnf as ProduitPV_Enf, ';
		$sql.=' se.s_pvgroupe as DepartPV_Grp, sce.s_pvgroupe as ActivitePV_Grp,  pe.s_pvgroupe as ProduitPV_grp, ';
		$sql.=' se.s_pvexclu as DepartPV_Excl, sce.s_pvexclu as ActivitePV_Excl, ' ;
		$sql.=' se.s_rdvPrinc, se.s_rdvAlter, ';
		$sql.=' s.fk_product as SesProduit, sg.fk_product as ActProduit' ;
		$sql.=' from '.MAIN_DB_PREFIX.'agefodd_session as s';
		$sql.=' left join '.MAIN_DB_PREFIX.'agefodd_session_extrafields   as se on se.fk_object=s.rowid ';
		$sql.=' left join '.MAIN_DB_PREFIX.'agefodd_place as p on fk_session_place=p.rowid ';
		$sql.=' left join '.MAIN_DB_PREFIX.'agefodd_session_calendrier as sc on sc.fk_agefodd_session = s.rowid ';
		$sql.=' left join '.MAIN_DB_PREFIX.'agefodd_session_formateur as sf on sf.fk_session = s.rowid';
		$sql.=' left join '.MAIN_DB_PREFIX.'agefodd_formateur as f on sf.fk_agefodd_formateur = 	f.rowid';
		$sql.=' left join '.MAIN_DB_PREFIX.'socpeople as cf on cf.rowid = f.fk_socpeople';
		$sql.=" left join llx_user as cu on cu.rowid = f.fk_user";
		$sql.=' left join '.MAIN_DB_PREFIX.'agefodd_formation_catalogue as sg on sg.rowid = s.fk_formation_catalogue';
		$sql.=' left join '.MAIN_DB_PREFIX.'agefodd_formation_catalogue_extrafields as sce on sce.fk_object = s.fk_formation_catalogue';
		$sql.=' left join '.MAIN_DB_PREFIX.'product_extrafields as pe on pe.fk_object = s.fk_product';
		$sql.=" where s.rowid = '".$idact."'";
		
		dol_syslog(get_class($this).'::RecupAct ', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{	
				$objp = $this->db->fetch_object($result);

				$line->activite_label		=$objp->intitule_custo;
				$line->id_act				=$objp->id;
				$line->activite_nbmax		=(empty($objp->nb_place))?0:$objp->nb_place;

				$wdep = new CglDepart ($this->db);
				$line->activite_nbpreinscrit = 0;
				$line->activite_nbencrins = $wdep->NbPartDep(0,$line->id_act);
				$line->activite_nbpreinscrit = $wdep->NbPartDep(1,$line->id_act);
				$line->activite_nbinscrit = $wdep->NbPartDep(2,$line->id_act);
/*
				$line->activite_nbencrins = $wdep->NbPartDep(0,$id_act);
				$line->activite_nbpreinscrit = $wdep->NbPartDep(1,id_act);
				$line->activite_nbinscrit = $wdep->NbPartDep(2,$id_act);
*/
				if (empty($line->activite_nbinscrit)) $line->activite_nbinscrit	=0;
				if (empty($line->activite_nbpreinscrit)) $line->activite_nbpreinscrit	=0;
				if (empty($line->activite_nbencrins)) $line->activite_nbencrins	=0;

				//$line->activite_nbinscrit	=(empty($objp->nb_stagiaire))?0:$objp->nb_stagiaire;
				//$line->activite_nbpreinscrit=(empty($objp->nb_preinscrit))?0:$objp->nb_preinscrit;	
				//$line->activite_nbencrins  =(empty($objp->activite_nbencrins))?0:$objp->activite_nbencrins;	
				$line->activite_lieu		=$objp->ref_interne;
				$line->activite_dated		=$objp->dated;
				$line->activite_heured		=$objp->heured;
				$line->activite_heuref		=$objp->heuref;
				$line->act_moniteur_nom		=$objp->MonNom;
				$line->act_moniteur_prenom	=$objp->MonPrenom;	
				if ( empty ($line->activite_rdv) ) $line->activite_rdv =1;
				if ($objp->MonTel) 	$line->act_moniteur_tel	=$objp->MonTel;
				else 				$line->act_moniteur_tel	=$objp->Monperso;
				$line->act_moniteur_email	=$objp->MonMail;
				if ($objp->SesProduit>0) $line->fk_produit = $objp->SesProduit;
				else $line->fk_produit = $objp->ActProduit ;
				
				if ($objp->DepartPV_Adlt>0) $line->pu_adlt = $objp->DepartPV_Adlt;
				elseif (($objp->ActivitePV_Adlt)>0) $line->pu_adlt = $objp->ActivitePV_Adlt ;
				elseif (($objp->ProduitPV_Adlt)>0) $line->pu_adlt = $objp->ProduitPV_Adlt ;    
				else	$line->pu_adlt = 0;	

				if ($objp->DepartPV_Enf>0) $line->pu_enf = $objp->DepartPV_Enf;
				elseif ($objp->ActivitePV_Enf>0) $line->pu_enf = $objp->ActivitePV_Enf ;  
				elseif ($objp->ProduitPV_Enf>0) $line->pu_enf = $objp->ProduitPV_Enf ;  
				else	$line->pu_enf = 0;	
				
				if ($objp->DepartPV_Grp>0) $line->pu_grp = $objp->DepartPV_Grp;
				elseif ($objp->ActivitePV_Grp>0) $line->pu_grp = $objp->ActivitePV_Grp ;  
				elseif ($objp->ProduitPV_grp>0) $line->pu_grp = $objp->ProduitPV_grp ;  
				
				else	$line->pu_grp = 0;
			
/*				if ($objp->DepartPV_Grp>0) $lineajout->pu_excl = $objp->DepartPV_Excl;
				elseif ($objp->ActivitePV_Grp>0) $lineajout->pu_excl = $objp->ActivitePV_Excl ; 
				else	$lineajout->pu_excl = 0;
*/	

				//if ($lineajout->pu == 0 or empty($lineajout->pu))	$lineajout->pu = ($lineajout->PartENF == 'Adulte')?$lineajout->pu_adlt:$lineajout->pu_enf;				
				$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::RecupAct - '.$this->error,LOG_ERR);
			return -3;
		}

	}//RecupAct
	
	function MajFiltreDepart()
	{
		global   $db;		
		global  $id_bull,  $FiltrPasse;							

				$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull SET ";
				$sql .= "  filtrpass='".$this->db->escape($FiltrPasse)."' ";
				$sql .= " WHERE rowid=".$id_bull;
				dol_syslog(get_class($this).':MajFiltreDepart:update sql='.$sql, LOG_DEBUG);
				$result1 = $this->db->query($sql);
				if ($result1 <= 0)
				{
					$this->error=$this->db->error();
					dol_syslog(get_class($this).':MajFiltreDepart:update'.$this->error,LOG_ERR);
					return -1;
				}			
	} // MajFiltreDepart
	
	function SupActPart()
	{
		global  $id_bulldet, $db, $langs, $bull, $confirm;

//		print "<p>SUP ACTIVITE PARTICIPANT - Confirmation Suppression - id_bulldet:".$id_bulldet."</p>";
		$line = $bull->RechercheLign ($id_bulldet);
		$text='Participation  de '.$line->NomPrenom.' a cette activite '.$line->activite_label;
		
		$form = new Form($db);
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id_bull='.$bull->id.'&id_bulldet='.$id_bulldet,$langs->trans('DeleteParticipation'),$text,'ConfSUPActPart','','',1);

		print $formconfirm;
	}/*SupActPart()*/
		
	function DupActPart()
	{
	global $bull, $id_bulldet, $actionT, $user;
	
	$linedup = new BulletinLigne ($this->db);
	$line_orig = $bull->RechercheLign($id_bulldet);		
	// Pour les enr de type 0 - activité
	$linedup->qte =$line_orig->qte;				// Quantity (example 2)
	$linedup->pu_enf =$line_orig->pu_enf;      	// prix enfant du départ
	$linedup->pu_grp =$line_orig->pu_grp;      	// prix groupe du départ
	$linedup->pu_adlt =$line_orig->pu_adlt;     	// prix adlt du départ
	$linedup->pu =$line_orig->pu;      	// P.U. HT (example 100)
	$linedup->remise_percent =$line_orig->remise_percent;	// % de la remise ligne (example 20%)
	$linedup->rangdb = 0 ;
	$linedup->rangecran = 0 ;

	$linedup->observation =$line_orig->observation;
	$linedup->type_TVA =$line_orig->type_TVA; // $langs->trans("TVACommissionnement") ==> 0%
	
	// Diffusion dans la base
	$linedup->action ='A'; // A pour Ajout, M pour Modifier, S pour Supprimer, X pour Ne plus sÃ©lectionner
	$linedup->id_ag_stagiaire =$line_orig->id_ag_stagiaire;
	$linedup->id_ag_session =$line_orig->id_ag_session;
	$linedup->fk_produit =$line_orig->fk_produit;
	$linedup->fk_code_ventilation = 0 ;
	$linedup->product_type = 1;	// Type  1 = Service	
	 $ret = $linedup->insertParticipation($user,0);
	unset ($linedup);
	return $ret;
	} //DupActPart	
	
	
	function ConfSupActPart()
	{
		global $confirm, $bull, $id_bulldet, $langs;
		
		
		if ($confirm = 'yes')
		{
			$line = $bull->RechercheLign ($id_bulldet);
			// en cas de bulletin dejà iffusée dans Dolibarr, on met juste S dans à ligne
			if (!empty($line) and $bull->statut != $bull->BULL_ENCOURS) 
			{
				$ret = $line->updateaction('S');
				if ($ret >= 0) $line->action = 'S';
			}
			else if (!empty($line))
			{
				$line->delete();
			}
			else 	 setEventMessage($langs->trans("ErrorSupression",$langs->transnoentitiesnoconv("Ligne")),'errors');		
		}
		return 1;
	} /*ConfSupActPart()*/
	/*
	* Crée une ou plusieurs participation ou enregiste les modifications
	*
	*	@return		 si OK, 
	*				-1 si condition de saisie non réalisee (Age obligatoire)
	*				-11 si la modification de patticipation n'a pas abouti
	*				-18 si l'insertion d'une seule ligne n'a pas abouti
	*				-15, -20, -25... si l'insertion multiligne n'a pas abouti
	*/
	function EnrActPart()
	{
		global  $bull, $db, $user, $id_bulldet, $id_part, $id_bull, $id_act, $langs;
		global $ACT_SAISIEPARTICIPATION;
		global $ActPartPU, $ActPartRem , $ActPartQte, $ActPartIdRdv, $ActPartObs, $TypeSessionCli_Agf, $NomPrenom;
		global $PartNom, $PartPrenom, $PartIdCivilite, $PartAdresse, $PartDateNaissance, $PartTaille, $PartPoids, $PartCiv, $PartENF, $PartTel, $PartMail, $PartAge, $PartDateInfo;
		global $action ;
		$error=0;
		
		$line=new BulletinLigne ($db);
		// calcul de l'age si non renseigne
		if ($PartAge == -1 )
		{			
			$datenais = $this->transfDateMysql($PartDateNaissance);
			$now= dol_print_date(dol_now('tzuser'), '%Y-%m-%d');
			if ($datenais>0) $PartAge		= $bull->calculAge($datenais, $now);	
		}	
		if ($bull->type_session_cgl == 2 and $bull->statut == $bull->BULL_INS)  // type session Individuel, champs obligatoire : pr鮯m, taille, 
		{
			// age  obligatoire 
			if (empty($PartAge) )   { $error++; setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("AgeDtNais")),'errors');}
		} 
		
		if ($error > 0) {
			$action  = $ACT_SAISIEPARTICIPATION;
			return -1;
		}
		$error = -10;
		if ($id_bulldet) {
				$line = $bull->RechercheLign($id_bulldet);
		}
		if (isset($line->$type_enr) and  $line->$type_enr <> 0)	 return 0;		
		else $line->type_enr  = 0;
		$line->fk_bull  = $bull->id;
		$line->id_act  = $id_act;
		if (empty($id_part)) $line->id_part  = 0; else $line->id_part = $bull->id_contactTiers;
		$line->product_type  = 1;
		$line->qte  = $ActPartQte;
		if (empty($ActPartQte)) $line->qte  = 1;
		$line->PartTaille  = $PartTaille;
		$line->PartPoids  = $PartPoids;
		$line->NomPrenom = $NomPrenom;
		$line->pu  = price2num($ActPartPU,'');
		$line->remise_percent  = $ActPartRem;
		$line->PartAge  = $PartAge;
		$line->PartdateInfo  = $PartDateInfo;
		$line->observation  = $ActPartObs;
		$line->PartTel  = $PartTel;	

		if (empty( $ActPartIdRdv)) $line->activite_rdv = 1;
		else $line->activite_rdv  = $ActPartIdRdv;
		if ($id_bulldet) {
				$ret = $line->updateParticipation($user,0);
				if  ($ret < 0) $error -= 1;
		}
		else 	{	
			//autant de line identique qu'il y a de Qte, avec un Qte = 1
			$nbligne = $line->qte ;
			$line->qte  = 1;
			$ret = $line->insertParticipation($user,0);
			if ( $ret > 0 and $bull->STATUT == $bull->BULL_ENCOURS)  
			{
				$id_bulldet = $line->id;			
				// en cas de groupe et pour un bulletin en cours , le PU est sur la première ligne 
				if ($bull->type_session_cgl <> 2)  $line->pu  = 0;
				//$line->id_part = '';
				$line->PartNom = $bull->tiersNom;
				$line->PartPrenom = '';
				$line->NomPrenom = $bull->tiersNom;
				$line->PartDateNaissance = '';
				if (!($line->PartAge == 99) and !($line->PartAge == 100)) $line->PartAge = '';
				$line->PartENF = '';
				$line->PartMail = '';
				$line->PartTel = '';
				$line->PartCP = '';
				$line->PartVille = '';
				$line->PartAdresse = '';
				$line->PartCiv = '';
				$line->PartTaille = '';
				$line->PartPoids = '';
				$line->PartDateInfo = '';	
				$ret = 0;
				for ($i=1; $i<$nbligne; $i++) 
				{
					$ret = $line->insertParticipation($user,0);
					if ($ret < 0) { $error -= 5; break;}
				}
			}
			else $error -= 8;
		}	
		if ($error == 0) {
			if (!empty($TypeSessionCli_Agf))  $bull->type_session_cgl = $TypeSessionCli_Agf + 1;
	
			// Pour pallier à un pb indetectable
			$ret = $this->TestErrCalendrier($bull);
			if ($ret >0) $error = 20;
			}
		if ($error == -10) $error = 0;
		return $error;
		
	} /* EnrActPart */
	
	
	
	function ChangeActiviteParticipation($activite)
	{
		global $tabrowid,  $bull, $langs;
		$tabi = array();
		$tabline = array();
		$cglInscDolibarr  = new cglInscDolibarr($this->db); 
		$i=0;
		
		if ( ! empty($tabrowid)) {
				foreach ($tabrowid as $participation) {
				// recherche des lignes à modifier
				if ( ! empty($bull->lines)) {
						foreach ($bull->lines as $line) {
						if ($line->id == $participation)  { 
							$tabi[] = $line->id;  $tabline[] = $line;
						}
						$i++;		
					}	 // foreach line
				}
			} // Foreach participation
		}
		
		/* Desincrire*/
		for ($i=0;$i<count($tabi);$i++) {
			$line = $tabline[$i];
			$line->action = 'S';
			$ret = $line->updateaction ( 'S');
		}

		/* transferer dans Dolibarr */
		$cglInscDolibarr->TransfertDataDolibarr('desincrire', '');
	
		/* Reinscrire */
		for ($i=0;$i<count($tabi);$i++) {
			$line = $tabline[$i];
			$line->action = 'A';
			$ret = $line->updateaction ( 'A');
			$line->id_act = $activite;
			// Recherche le label de la nouvelle activité
			$agses = New Agsession($this->db);
			$agses->fetch($activite);
			$line->activite_label = $agses->intitule_custo;
			if (!empty($line->PartAge) and ($line->PartAge <18 or $line->PartAge  == 100)) $line->pu = $agses->array_options['options_s_PVIndEnf'];
			else $line->pu = $agses->array_options['options_s_PVIndAdl']	;
			if (!empty($line->remise_percent )) setEventMessage($langs->trans('AideRem1'), 'warnings');
			$line->remise_percent = 0;						
			unset ($agses);
			$line->ficbull = '';		
			$ret = $line->update_champs ( 'fk_activite', $activite,'ficbull', '' );	
			$ret = $line->update_champs ( 'pu', $line->pu,'rem', 0 );				
		}

		/* transferer dans Dolibarr */
		if ($bull->statut == $bull->BULL_INS) $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
		elseif ($bull->statut == $bull->BULL_PRE_INS) $cglInscDolibarr->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');	
		
	}//ChangeActiviteParticipation
	
	
	
	/*	function CrePersonRecours()
	{
		global $PersNom, $PersPrenom, $PersTel , $PersParent , $id_persrec, $id_age_contact, $id_act;

		global $id_client, $action,$id_bull;
//		print "<p>CREER PERSONNE RECOURS - dans TBX_socpeople (PersNom=$PersNom, PersPrenom=$PersPrenom, id_client=$id_client, PersTel=$PersTel, PersParent=$PersParent  recuperer id_persrec=$id_persrec</p>";
	} /*CrePersonRecours*/

		
	function recupContact($idpersrec)
	{
		global $id_bull,  $bull, $conf;
					
        $sql = "SELECT s.rowid as id, lastname, firstname, birthday,  phone_mobile ,phone , civility as civilite, poste as parente";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as s";
        $sql.= " WHERE s.rowid = ".$idpersrec;
		if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND s.statut <> 0 ";	
        $sql.= " AND s.entity = 1";
    	dol_syslog(get_class($this)."::fetch sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $objp = $this->db->fetch_object($resql);				
				
				$bull->fk_persrec			= $objp->id;
				$bull->pers_civ				= $objp->civilite;
				$bull->pers_nom				= $objp->lastname;
				if (empty($bull->pers_nom)) $bull->pers_nom	= $bull->tiersNom;
				$bull->pers_prenom			= $objp->firstname;
				$bull->pers_parente			= $objp->parente;	

				if ($objp->phone_mobile) 	$bull->pers_tel	=$objp->phone_mobile;
				else 				$bull->pers_tel	=$objp->phone;
            }
			$this->db->free($resql);
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }		
		return 0;		
	}
	/*recupContact*/
	
	function MajPersonRecours()
	{
		global $id_persrec , $PersNom, $PersPrenom, $PersTel, $PersParent, $PersCiv , $ACT_SEL_PERS_RESP;
		global $id_client, $action,$id_bull,$db, $bullm ,$langs , $bull, $user;
		$contact=new Contact($db);				
		if ($id_persrec >= 1) $contact->fetch($id_persrec, $user);
		if (!empty($PersCiv)) $contact->civilite_id = $PersCiv;
		if (!empty($PersNom)) $contact->lastname = $PersNom; 
		if (!empty($PersPrenom)) $contact->firstname = $PersPrenom; 
		// Message d'erreur 
			
		
		$error=0;
		if (empty($PersTel	) ) {
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Telephone")),'errors');
			$error++;
		}
		if ($error > 0) {
			$action  = $ACT_SEL_PERS_RESP;
			return -1;
		}
		if (substr($PersTel,0,2)=='06') 	$contact->phone_mobile	=$PersTel;
		else 	$contact->phone_perso	= $PersTel;
		
		if ($id_persrec >= 1) 	{		
			$ret = $contact->update($id_persrec,$user, 0,'update');
		}
		else  { 			
			$contact->socid = $bull->id_client;	
			$ret = $contact->create( $user);
		}
		/* mettre ࠪour le bulletin */
		$bull->fk_persrec = $contact->id;
		$bull->pers_tel=$PersTel;
		$id_persrec = $bull->fk_persrec ;
		$ret = $bull->update();		
		
	} /*MajPersonRecours*/

	function Inscrire()
	{
		global $bull, $langs;
		global $ACT_INSCRIRE;	
		$confirm = GETPOST('confirm','alpha');		
		
		if (empty($confirm)) {
			$regle = $bull->CalculRegle();
			$flssMon = $bull->IsMoniteurAbsent();
			//if ( $regle <> $bull->BULL_PAYE or $flMon) {
			if ( $flssMon) {
					$titre = $langs->trans ('QstFactImp');	
				/*if ($regle  <> $bull->BULL_PAYE  and !$flMon) {
						$question = $langs->trans('QstBullInsRegle');
				}
				elseif ($regle  == $bull->BULL_PAYE  and $flMon) 
						$question = $langs->trans('QstBullInsMon');	
				elseif ($regle  <> $bull->BULL_PAYE  and $flMon) 
						$question = $langs->trans('QstBullIns');
				*/
				$question = $langs->trans('QstBullInsMon');
				$form = new Form($db);
				//$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id_contrat='.$bull->id.'&id_contratdet='.$id_contratdet,$langs->trans('DeleteParticipation'),$text,$CONF_SUP_LOCDET,'','',1);
				$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id_bull='.$bull->id.'&action='.$ACT_INSCRIRE,$titre,$question,'','','',2);	
				print $formconfirm;
			}
		}			
		if ( $confirm <> 'no') {
			$objdata=  new cglInscDolibarr($this->db);
			$statut_old = $bull->statut;
			$bull->statut = $bull->BULL_INS;
			$bull->updateStat ($bull->BULL_INS,'');	 // Inscrit	
			$res =$objdata->TransfertDataDolibarr('Inscrit', 'Inscrire');
			if ($res != -9) {
				if ($bull->facturable) $objdata->creer_bon_commande($bull->fk_commande);
			}
			else $bull->updateStat ($statut_old,'');	 // revenu état antérieur
			unset ($objdata);
		}
		
		// Pour pallier à un pb indetectable
		$this->TestErrCalendrier($bull);
	
	} /*Inscrire*/
	
	function PreInscrire()
	{
		global $bull;		
		
		$objdata = new cglInscDolibarr($this->db);
		$statut_old = $bull->statut;
			$bull->statut = $bull->BULL_PRE_INS;
		$bull->updateStat ($bull->BULL_PRE_INS,'');	// Pre-Inscrit
		$ret = $objdata->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');	
		if ($ret < 0) {
			$bull->statut = $statut_old;
			$bull->updateStat ($statut_old,'');	// retour état précédent sur erreur
		}
		unset ($objdata);
		// Pour pallier à un pb indetectable
		$this->TestErrCalendrier($bull);
	} /*PreInscrire*/
	
	/*
	* Verifier que les départs de ce bulletins n'ont pas leur calendriers à 0
	*/
	function TestErrCalendrier($bull)
	{
		global $langs;
		
		$departs = array();
		
		if ( ! empty($bull->lines)) {
			foreach ($bull->lines as $line)
			{
				if ($line->type_enr == 0)
					if (!isset($departs[$line->id_act])) $departs[$line->id_act] = $line->id_act;
			}
		}
		if ( ! empty($departs)) {
			foreach($departs as $key => $val)
			{
				$sql = "select heured, heuref , fk_agefodd_session from  ".MAIN_DB_PREFIX ."agefodd_session_calendrier 
							where fk_agefodd_session = ".$val;       
				dol_syslog(get_class($this)."::TestErrCalendrier");
				$resql=$this->db->query($sql);
				if ($resql)
				{
					 $obj = $this->db->fetch_object($resql);
					 if (empty($obj->heured) or empty($obj->heuref) or $obj->heured == '1970-01-01 00:00:00' or $obj->heuref == '1970-01-01 00:00:00' )
					 {
						setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CalSessErr", $obj->fk_agefodd_session)),'errors');
						return -1;
					 }
					 return 1;
				}
				else	return -1;		
			} // foreach
		}
	
	} // TestErrCalendrier
	function CherchStrTypeSession($array,$id)
	{
		if ( !empty($array)) {
           foreach($array as $key => $value)
            {
				if ($key == $id)
						break;
			}// foreach
		}
			return $value;
	} //CherchStrTypeSession
	
	function select_rdv($selected, $htmlname, $exclude='', $showempty = 1, $id_act)
	{
        global $conf,$langs;
        $sql = "SELECT s_rdvAlter, s_rdvPrinc";
        $sql.= " FROM ".MAIN_DB_PREFIX ."agefodd_session_extrafields as se";
        $sql.= " WHERE  se.fk_object='".$id_act."'";
        dol_syslog(get_class($this)."::select_rdv sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($htmlname != 'none' || $options_only) $out.= '<select class="flat'.($moreclass?' '.$moreclass:'').'" id="'.$htmlname.'" name="'.$htmlname.'">';
 //           if ($showempty == 1) $out.= '<option value="0"'.($selected=='0'?' selected="selected"':'').'></option>';
            $num = $this->db->num_rows($resql);
            if ($num)
            {
                if (!class_exists ('Contact')) include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
                $contactstatic=new Contact($db);
                $obj = $this->db->fetch_object($resql);
				$out.='<option ';
				if ($selected == 1 or empty($selected)) $out.='<selected="selected"';
				$out.=' value="1">'.$obj->s_rdvPrinc.'</option>';
				$out.='<option ';
				if ($selected == 2) $out.='selected="selected"';
				$out.=' value="2">'.$obj->s_rdvAlter.'</option>';                
            }
            else
			{
            	$out.= '<option value="-1"'.($showempty==2?'':' selected="selected"').' disabled="disabled"></option>';
            }
            if ($htmlname != 'none' || $options_only)
            {
                $out.= '</select>';
            }
            $this->num = $num;
            return $out;
        }
        else
        {
            dol_print_error($db);
            return -1;
        }

	} //select_rdv
	
	/* Obsolete */
	function select_typetva1 ( $selected, $htmlname)
	{
		global $langs;
		
        if ($htmlname != 'none' ) $out = '<select class="flat'.($moreclass?' '.$moreclass:'').'" id="'.$htmlname.'" name="'.$htmlname.'">';
 		$out.='<option ';
		if ($selected == 1 or empty($selected)) $out.='<selected="selected"';
		$out.=' value="'.$langs->trans("TVACommissionnement").'">'.$langs->trans("TVACommissionnement").'</option>';
		$out.='<option ';
		if ($selected == 2) $out.='selected="selected"';
		$out.=' value="'.$langs->trans("TVANormal").'">'.$langs->trans("TVANormal").'</option>';                
        if ($htmlname != 'none' )        $out.= '</select>';
        return $out;
	} //select_typetva
	
	
	function select_age($selectid, $htmlname = 'Age',  $showempty = 1, $forcecombo = 0, $event = array(), $classccs="") 
	{	
		global $conf, $user, $langs, $db;
		$out = '';
		
//		if ($conf->use_javascript_ajax && $conf->global->AGF_TRAINING_USE_SEARCH_TO_SELECT && ! $forcecombo) {
//			$out .= ajax_combobox ( $htmlname, $event );
//		}
		if (!empty($classcss)) $class='class="flat '.$classcss.'"';
		else  $class="flat";
		$out .= '<select id="' . $htmlname . '" name="' . $htmlname . '" '.$class.' ' . $htmlname . '">';
		if ($showempty)
			$out .= '<option value="-1"></option>';
		$num = 19;
		$i = 19;
		if ($selectid  == 99) {
			$out .= '<option value="99" selected="selected">Adulte</option>';
		} else {
			$out .= '<option value="99">Adulte</option>';
		}	

		if ($selectid  == 100) 
			$out .= '<option value="100" selected="selected">Enfant</option>';		
		else
			$out .= '<option value="100" >Enfant</option>';	
		
		if ($selectid > 19 and $selectid  != 99 and  $selectid  != 100)  $out .= '<option value="' . $selectid . '" selected="selected">' . $selectid.'&nbspans</option>';
		while ( $i > 3 ) {
			$label = $obj->libelle;
			
			if ($selectid > 0 && $selectid == $i) {
				$out .= '<option value="' . $i . '" selected="selected">' . $i.'&nbspans</option>';
			} else {
				$out .= '<option value="' . $i . '">' . $i.'&nbspans</option>';
			}
			$i --;
		}
		$out .= '</select>';
		
		return $out;
	
	} /* select_age */
	function select_poids($selected='',$htmlname='PartPoids',$htmloption='',$maxlength=0)
    {
        global $conf,$langs;
        $langs->load("dict");

        $out='';
        $TabPoids=array();
        $label=array();

        $sql = "SELECT rowid , code, libelle ";
        $sql.= " FROM ".MAIN_DB_PREFIX."cgl_c_poids";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY ordre ASC";
        dol_syslog(get_class($this)."::select_poids sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)        {
            $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" '.$htmloption.'>';
            $num = $this->db->num_rows($resql);
            $i = 0;					
            if ($num)    {
                $foundselected=false;
				$out.= '<option value="">';
                while ($i < $num)                {
                    $obj = $this->db->fetch_object($resql);
                    $TabPoids[$i]['code'] 	= $obj->code;
                    $TabPoids[$i]['rowid'] 	= $obj->rowid;
                    $TabPoids[$i]['label']		= $obj->code . ' - ' .$obj->libelle;
                    $label[$i] = dol_string_unaccent($TabPoids[$i]['label']);
                    $i++;
                }
				if ( !empty($TabPoids)) {
					foreach ($TabPoids as $row)                 {
						//print 'rr'.$selected.'-'.$row['label'].'-'.$row['code_iso'].'<br>';
						if ($selected && ($selected != '-1' and  ($selected == $row['rowid'] )) )                    {
							$foundselected=true;
							$out.= '<option value="'.$row['rowid'].'" selected="selected">';
						}
						else					{
							$out.= '<option value="'.$row['rowid'].'">';
						}
						$out.= dol_trunc($row['label'],$maxlength,'middle');
						$out.= '</option>';
					}// foreach
				}
            }
            $out.= '</select>';
        }
        else		{
            dol_print_error($this->db);
        }

        return $out;
    }// select_poids

	function select_raison_remise($selected='',$htmlname='RaisRemGen',$htmloption='',$maxlength=0)
    {
        global $conf,$langs;
        $langs->load("dict");

        $out='';
        $TabRaisRem=array();
        $label=array();

        $sql = "SELECT rowid , libelle ";
        $sql.= " FROM ".MAIN_DB_PREFIX."cgl_c_raison_remise";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY ordre ASC";

        dol_syslog(get_class($this)."::select_raison_remise sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" '.$htmloption.'>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
				$out.= '<option value="">';
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $TabRaisRem[$i]['rowid'] 	= $obj->rowid;
                    $TabRaisRem[$i]['label']	= $obj->libelle;
                    $label[$i] = dol_string_unaccent($TabRaisRem[$i]['label']);
                    $i++;
                }
                if ( !empty($TabRaisRem)) {
					foreach ($TabRaisRem as $row)
					{
						if ($selected && $selected != '-1' && ($selected == $row['rowid'] ) )
						{
							$foundselected=true;
							$out.= '<option value="'.$row['rowid'].'" selected="selected">';
						}
						else
						{
							$out.= '<option value="'.$row['rowid'].'">';
						}
						$out.= dol_trunc($row['label'],$maxlength,'middle');
						$out.= '</option>';
					} // foreach
				}
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
	} // select_raison_remise
		
	
	function creer_bulletin($idSession, $nomsite)
	{
		global $langs, $bull;
	
		$typeModele = $bull->NommageEditionBulletin('modele', $idSession);
		cgl_bull_create($this->db, $idSession,  $typeModele, $langs, $file, $socid, $courrier='');

		// Gestion des erreurs ?
		return 1;
	} //creer_bulletin
	function creer_feuilleroute($idSession)
	{ 
//		$typeModele = 'feuilleroute_odt:c:/dolibarr/dolibarr_documents/doctemplates/feuilleroute/feuilleroute.odt';
		$typeModele = 'feuilleroute_odt:'.DOL_DATA_ROOT.'/doctemplates/feuilleroute/feuilleroute.odt';
		cgl_feuilroute_create($this->db, $idSession,  $typeModele, $outputlangs, $file, $socid, $courrier='');
	} //creer_feuilleroute


	function MajRdv()
	{
		global $modactrdv, $bull, $ActPartIdRdv;
		
		if ( !empty($bull->lines)) {
			foreach ($bull->lines as $line)
			{
				if ($line->id_act == $modactrdv)
				{			
					$line->update_rdv($ActPartIdRdv);
				}
			}// foreach
		}
	} // MajRdv
	
	function MajRemise_old ()
	{
		global $actremgen, $bull, $RaisRemGen, $mttremisegen;
		
		if ( !empty($bull->lines)) {
			foreach ($bull->lines as $line)
			{
				if ($line->type_enr == 0 and $line->id_act == $actremgen or $actremgen == 0)				
					$line->MajLineRem($RaisRemGen, $mttremisegen,"");			
			} // Foreach
		}
	} //MajRemiseAnc
	

	function MajRemiseBonCommande_old ( $RaisRemGen, $mttremisegen)
	{
		global $actremgen, $bull, $tabrowid;
		
		if ( !empty($tabrowid)) {
			foreach ($tabrowid as $participation) {				
			if (!empty($bull->lines)) {
				foreach ($bull->lines as $line) {
						if ($line->id == $participation) {
							$line->MajLineRem($RaisRemGen, $mttremisegen);
							if ($ret< 0) setEventMessages("Erreur d'enregistrement", $line->PartNom . ' '. $line->PartPrenom, 'errors');
							break;
						}// if
					} // foreach line
				}
			} // Foreach participation
		}
	} //MajRemise
	
	

	function EnrInfoPriv()
	{
		global $bull, $InfoPrive, $ActionFuture;
		$ret = $bull->update_champs('ObsPriv', $InfoPrive);
		$bull->update_champs('ActionFuture', $ActionFuture);
	} // EnrInfoPriv
	

	/*
	* Interrogation : le départ est-il complet?
	*
	* @param int 		$idsession				Identifiant du départ
	* @param string 	$TypeRecherche	'Ajout' dans le cas d'un ajout prévisible: nb place inférieur au total des inscrits
	*									'Enregistre' dans le cas d'un ajout à réaliser: nb place inférieur au total des inscrits + nb participations  demandées
	* retour boolean*
	Note : la routine modifie l'objet lineajout
	*/	
	
	function is_session_complete ($idsession, $TypeRecherche)
	{
		
		global $ActPartQte, $lineajout;
		
		if (isset($lineajout)) $linesave = $lineajout;
		if (!isset($lineajout)) $lineajout=new BulletinLigne($db);
		
		$this->RecupAct($idsession, $lineajout); // renseigne lineajout
		// recherche premier ligne du bulletin 
		$flreg = false;
		$nbplace = $lineajout->activite_nbmax;
		$nbreserve = $lineajout->activite_nbinscrit +  $lineajout->activite_nbpreinscrit;
		$nbparticipationsession = $lineajout->activite_nbencrins;
		unset($lineajout);
		if (isset($linesave)) $lineajout  = $linesave;
		if ( $TypeRecherche == 'Ajoute' and $nbplace <= $nbreserve + $nbparticipationsession ) 
			return true;
		
		if ( $TypeRecherche == 'Enregistre' and $nbplace < (int)$nbreserve + (int)$nbparticipationsession +  (int) $ActPartQte) 
			return true;
		
		return false;
	} //is_session_complete

/*
	* recherche si la session est complète
	*	param	id		identifiant de la sesssion
	*	param	line	object contenant les informations  de la participation à cette session
	* 	param	typeRech 	indique le type de recherche
	*						- 'Ajoute' pour comparer le nombre de place  au nombre d'inscrits et preinscrit et Qte de la ligne (comparaison inférieure ou égale )
	*						- 'Enregistre' pour comparer le nombre de place au nombre d'inscrits et pre-inscrits  (comparaison strictement inférieure)
	*	param	bull	object contenant les informations du bulletin si typeRech = PlusParticipation
	*	retour	boolean	
	*/
	function demandeConfDepassementSession ($id, $faire = '' )
	{	
		global $lineajout, $bull, $ActPartQte, $CRE_PARTICIPATION, $ACT_SAISIEPARTICIPATION, $action, $FiltrPasse;
		global $NomPrenom, $PartTaille, $PartPoids, $PartAge, $ActPartObs, $ActPartPU;
		global $ActPartPT, $ActPartRem ;		
		
		if (!isset($lineajout)) $linesave = $lineajout;
		if ($this->is_session_complete($id, $faire)) {
			// recherche premier ligne du bulletin 
			$flreg = false;
			$nbplace = $lineajout->activite_nbmax;
			//recherche nbinscrit $nbreserve - 
			$nbreserve = $lineajout->activite_nbinscrit +  $lineajout->activite_nbpreinscrit;
			$nbparticipationsession = $lineajout->activite_nbencrins;
			if (empty($nbparticipationsession)) $nbparticipationsession = 0;
			if ($nbparticipationsession >0) $liste = $this->listebulletinactivite($id, $bull);

/*			print "<script> .marge {
			margin-left: 5em;
			font-weight:bold;
			}
			.important {
			color:red;
			font-weight:bold;
			}
			</script>";
*/			
			$arrayquestion = array();
			
			$text = 'Pour le départ '.$lineajout->activite_label.' (n° '.$lineajout->id_act.')';
			$question1='<font size =-1>Il est prévu '.$nbplace.' places</font><br>';
			$somme = $nbreserve + $nbparticipationsession;
			$question1.='<font size =-1 >'.$somme ." participants sont inscrits, préinscrits ou en cours d'inscription</font><br>";
	/*		$questionreserve.=$nbreserve .' participants sont inscrits,<br>';
			if ($bull->statut == $bull->BULL_ENCOURS) $suite = " ou le bulletin actuel ".$bull->ref;			
			$questionratses.=$nbparticipationsession." participants nouvellement inscrits sur un bulletin en cours (".$liste.')'.$suite."<br>";
			if ($action==$ACT_SAISIEPARTICIPATION) 	$questionqte.="Et vous voulez en rajouter. Ca le fera pas!!<br>";	
			elseif ($ActPartQte > 1) $questionqte.="Vous cherchez à inscrire  ". $ActPartQte . " participations de plus. Ca le fera pas!!<br>";
			elseif ($ActPartQte == 1) $questionqte.="Vous cherchez à inscrire  une participation de plus. Ca le fera pas!!<br>";
	*//*		if ($nbreserve  >= $nbplace){
				$flreg = true;
				if ($nbreserve > 0) 	$question.=$questionreserve;
				if (!empty($questionqte)) $question.=$questionqte;
			}
			elseif ($nbplace < $nbreserve  + $nbparticipationsession ) {
				$flreg = true;
				if ($nbreserve > 0) 	$question.=$questionreserve;
				if ($nbparticipationsession > 0) 	$question.=$questionratses.$questionqte;
			}		
			elseif ($nbplace < $nbreserve + $nbparticipationsession +   $ActPartQte) {
				$flreg = true;				
				if ($nbreserve > 0) 	$question.=$questionreserve;
				if ($nbparticipationsession > 0) 	$question.=$questionratses;
				if ($ActPartQte > 0) 	$question.=$questionqte;			
			}
	*/
			//if ($nbparticipationsession > 0) 	$question.=$questionratses;
			//if (!empty($questionqte)) 	$question.=$questionqte;
			$question.= '<b><span class="marge">Voulez-vous </span><span class="important"> depasser le seuil de places ?</span></b>';	
			$formCgl = new Form ($this->db);
			print '<form method="OOST" name="SelectActivite" action="'.$_SERVER['PHP_SELF'].'#AncreLstDetail">';
			$url = $_SERVER['PHP_SELF'];
			$url .= '?id_bull='.$bull->id;
			$url .= '&id_act='.$id;
			$url .= '&NomPrenom='.$NomPrenom;
			$url .= '&PartTaille='.$PartTaille;
			$url .= '&PartPoids='.$PartPoids;
			$url .= '&PartAge='.$PartAge;
			$url .= '&ActPartQte='.$ActPartQte;	
			$url .= '&ActPartPU='.$ActPartPU;			
			$url .= '&ActPartRem='.$ActPartRem;
			$url .= '&ActPartObs='.$ActPartObs;
			//Si on arrive de la sélection d'une activité
			$prochaineaction = $action;
			if ($action==$ACT_SAISIEPARTICIPATION  or $action == $CRE_PARTICIPATION or $action == "Participations") {
					$prochaineaction = $ACT_SAISIEPARTICIPATION;
					$action=$CRE_PARTICIPATION;					
					//$action=$prochaineaction;
			}
			//si on arrive de l'enregistrement d'une participation 
			elseif ($action=='ACT_ENR_ACTPART') {
					$url .= '&action='.$ACT_ENR_ACTPART;
					$action = $ACT_SAISIEPARTICIPATION;
					$prochaineaction = $ACT_ENR_ACTPART;
			}
			$url .= '&FiltrPasse='.$FiltrPasse;
			$arrayquestion[] = array('type'=>'other','label'=>$question1);
			$arrayquestion[] = array('type'=>'ancre','label'=>'AncreSaisieParticipation');// Ne fonctionne pas
			$formconfirm=$formCgl->formconfirm($url,$text,$question,$prochaineaction,$arrayquestion,'Yes',1,250,600, 1);
			print '</form>';
			unset ($formCgl);
			print $formconfirm;	
				
		}
		unset ($lineajout);
		$lineajout = $linesave;
		if ($nbplace == 0 or empty($nbplace) ) return FALSE;
		return true ;		
	} // demandeConfDepassementSession
	
	/** 
	* retourne sous forme de chaine, la liste des bulletin en cous avec une participation sur cette activité, sauf le bulletin courant
	*/
	function listebulletinactivite($id_act, $bull)
	{
		$rep = '';				
		$sql = "SELECT ref ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b,  ".MAIN_DB_PREFIX."cglinscription_bull_det as bds";
		$sql.= " WHERE bds.fk_bull = b.rowid  ";
		$sql.= "  AND ((bds.action = 'A' and b.statut > 0) or (bds.action not in ('S','X')  and b.statut = 0 ))";
		$sql.= " AND bds.fk_activite = ".$id_act;
		$sql.= " AND b.rowid <> ".$bull->id;
		dol_syslog(get_class($this)."::listebulletinactivite sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);	
		if ($resql)		{
			$num = $this->db->num_rows($resql);
			if ($num)			{
				$i=0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$rep = $obj->ref . '-';
					$i++;
				}
			}
			$this->db->free($resql);			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::listebulletinactivite ".$this->error, LOG_ERR);
			$res = -2;
		}
		
		return substr( $rep, 0, strlen($rep) - 1);
	} //listebulletinactivite
	function is_sessions_completes($bull) 
	{		
		global $langs;
		
		// Constitution de la table des sessions			
		$tabsession = array();					
		if ( !empty($bull->lines)) {
			foreach ($bull->lines as $linedata)
			{
				if ($linedata->type_enr == 0  and $linedata->action != 'X' and $linedata->action != 'S' )	{				
						$tabsession[$linedata->id_act] = new stdClass();			
						$tabsession[$linedata->id_act]->id_act = $linedata->id_act;						
						$tabsession[$linedata->id_act]->activite_label = $linedata->activite_label;	
				}
			} // foreach
		}		
		
					
		if ( !empty($tabsession)) {
			foreach ( $tabsession as $session)		{
				if ($this->is_session_complete($session->id_act, 'Rien'))
					setEventMessage($langs->trans("DepSession",$session->activite_label) ,'warnings');
			} // foreach
		}
	} //is_sessions_completes
	
	//  Si lundi : Violet, Mardi : Indigo, Marcredi : Bleu, Jeudi : Vert, Venrdredi:Jaune, Samedi: Orangé, Dimanche: Rouge. 
	function  color_jour_semaine($strdate)
	{
		$couleurjoursem = array('Tomato', 'Plum', 'RoyalBlue', 'SkyBlue', 'YellowGreen', 'PaleGoldenRod', 'SandyBrown');
		// extraction des jour, mois, an de la date
		list($jour, $mois, $annee) = explode('/', $strdate);
		// calcul du timestamp
		$timestamp = mktime (0, 0, 0, intval($mois), intval($jour), intval($annee));
		// affichage du jour de la semaine		
		return $couleurjoursem[date("w",$timestamp)];	
	}//color_jour_semaine
	/**
	 * Affiche un champs select contenant la liste des départs disponibles.
	 *
	 * @param int 		$selectid 		reselectionner
	 * @param string 	$htmlname 		select field
	 * @param string 	$sort 			Value to show/edit (not used in this function)
	 * @param int 		$showempty 		empty field
	 * @param int 		$forcecombo 		use combo box
	 * @param array 	$event
	 * @return string select field
	 */
	function select_session($selectid, $htmlname = 'Activite', $sort = 'date', $showempty = 0, $forcecombo = 0, $event = array(), $filters = array()) 
	{	

		global $conf, $user, $langs,  $bull, $id_act;
		$out = '';
		
		$wfcom =  new CglFonctionCommune ($this->db);
		
		if ($sort == 'intitule')
			$order = 'intitule_custo';
		elseif ($sort == 'passe' ) $order = 's.dated desc, heured asc';
		else	$order = 's.dated asc, heured asc' ;
	
		$sql = "SELECT s.rowid, intitule_custo, dated, heured, ref_interne, nb_place, lastname, firstname ";
		$sql .= " FROM ".MAIN_DB_PREFIX."agefodd_session_calendrier, ";
		$sql .= MAIN_DB_PREFIX."agefodd_session as s left join ".MAIN_DB_PREFIX."agefodd_place  as p on p.rowid = fk_session_place " ;
		$sql .= " left join ".MAIN_DB_PREFIX."agefodd_session_stagiaire as st on st.fk_session_agefodd = s.rowid ";
		$sql .= " left join ".MAIN_DB_PREFIX."agefodd_session_formateur as fs  on fs.fk_session = s.rowid";
		$sql .= " left join ".MAIN_DB_PREFIX."agefodd_formateur as f  on fs.fk_agefodd_formateur = f.rowid";
		$sql .= " left join ".MAIN_DB_PREFIX."socpeople as dolf  on f.fk_socpeople = dolf.rowid";		
		$sql .= " WHERE s.status <4 ";
		$sql .= " AND fk_agefodd_session = s.rowid";	
		$sql .= " AND s.entity =1 ";
		
		if (empty($bull->type_session_cgl)) 	$sql .= " AND ( isnull(s.type_session) or s.type_session = 1 ) ";
		else {
		$local_type_session_agf = $bull->type_session_cgl - 1;
			if ($local_type_session_agf >=0 )
			 		$sql .= " AND (s.type_session = ". $local_type_session_agf." or isnull(s.type_session)) ";
			else	$sql .= " AND s.type_session = ". $local_type_session_agf."  ";
		}
		if (count($filters)>0) {
			foreach($filters as $filter)
				$sql .= $filter;
		}	
		$sql .= " group by s.rowid, intitule_custo , dated,heured, nb_place";
		$sql .= " ORDER BY " . $order;
		
		
		dol_syslog ( get_class ( $this ) . "::select_session " );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			if ($conf->use_javascript_ajax && $conf->global->AGF_TRAINING_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox ( $htmlname, $event );
			}
			
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '"  style="width:100%">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			$jourSemAnc='';
			if ($num) {
				$wdep = new CglDepart ($this->db);
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $resql );
					//if ($i == 0)  	$id_act = $obj->rowid;
		
					$nbinscrit = $wdep->NbPartDep(2,$obj->rowid);
					$nbpreinscrit = $wdep->NbPartDep(1,$obj->rowid);
					if (empty($nbinscrit)) $nbinscrit	= 0;
					if (empty($nbpreinscrit)) $nbpreinscrit	= 0;

					if ($i == 0 and ( empty($selectid)  or $selectid ==0 ))  	$selectid = $obj->rowid;
					//$label  = $obj->intitule_custo.' - '.dol_print_date($obj->dated,'%D %d/%m').'-';
					//$label .= dol_print_date($obj->heured,'%H').' - '.$obj->ref_interne;
					$date_fr = $wfcom->transfDateFr($obj->dated);
					//$jourSem = substr($wf->transfDateJourSem($date_fr),0,3);
					$jourSem = $wfcom->transfDateJourSem($date_fr);
					$label  = ''. $obj->intitule_custo.' - '.$jourSem.' '.$wfcom->transfDateFrCourt($obj->dated).'-';
					$label .= $wfcom->transfHeureFr($obj->heured).' - '.$obj->ref_interne;
					$label .= ' avec ' . $obj->firstname.' ' . $obj->lastname;
					$som = $nbpreinscrit + $nbinscrit;
					$label .= ' - ('.$obj->nb_place.'/'.$som.')';
					//$label= $obj->intitule_custo.'_'.dol_print_date($obj->dated,'%D %d/%m').
				
					// Couleur 
					// si depart complet : gris
					if ($obj->nb_place <=$obj->nbinscrit ) $style='color:gray;"';
					else $style = 'color:'.$this->color_jour_semaine (  $date_fr).';"';
					// sinon Si lundi : Violet, Mardi : Indigo, Marcredi : Bleu, Jeudi : Vert, Venrdredi:Jaune, Samedi: Orangé, Dimanche: Rouge. 
					
					// une barre de separation au changement de jour si tri par date
					if ($order != 'intitule_custo') {
						if (!empty($jourSemAnc) and $jourSem != $jourSemAnc) {
							$jourSemAnc = $jourSem;
							$out .= '<option >------------------------------</option>';
							
						}
						elseif (empty($jourSemAnc)) $jourSemAnc = $jourSem;
					}
					
					if ($selectid > 0 && $selectid == $obj->rowid) {
						 $out .= '<option value="' . $obj->rowid . '" style='.$style.' selected="selected"   >'. $label . '</option>';
						$id_act = $obj->rowid;
					} else {
						$out .= '<option value="' . $obj->rowid . '"  style= '.$style.'  >'. $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
		} else {
			dol_print_error ( $db );
		}
		$this->db->free ( $resql );
		return $out;
	
	} /* select_session */
	/**
	 * Affiche un champs select contenant la liste des dépars du bulletin
	 *
	 * @param int 		$selectid 		reselectionner
	 * @param string 	$htmlname 		select field
	 * @param	int		$idbull			id du bulletin 
	 * @param string 	$sort 			Value to show/edit (not used in this function)
	 * @param int 		$showempty 		empty field
	 * @param int 		$forcecombo 		use combo box
	 * @param array 	$event
	 * @return string select field
	 */
	function select_sessionbybull($selectid, $htmlname = 'Activite', $idbull, $sort = 'date', $showempty = 0, $forcecombo = 0, $event = array(), $filters = array()) 
	{	

		global $conf, $user, $langs,  $bull, $id_act;
		$out = '';	
		$wfcom =  new CglFonctionCommune ($this->db);		
	
		$sql = "SELECT DISTINCT s.rowid, intitule_custo,  lastname, firstname ";
		$sql .= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd ON bd.fk_bull = b.rowid and bd.action not in ('X','S') and bd.type = 0 ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as s  ON s.rowid = bd.fk_activite";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as st on st.fk_session_agefodd = s.rowid ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_formateur as fs  on fs.fk_session = s.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formateur as f  on fs.fk_agefodd_formateur = f.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as dolf  on f.fk_socpeople = dolf.rowid";		
		$sql .= " WHERE s.status < 4 ";
		$sql .= " AND b.rowid  = '".$idbull."'" ;	
		$sql .= " AND s.entity =1 ";

		if (count($filters)>0) {
			foreach($filters as $filter)
				$sql .= $filter;
		}	
			
		dol_syslog ( get_class ( $this ) . "::select_sessionbybull " );
		$resql = $this->db->query ( $sql );

		if ($resql) {
			if ($conf->use_javascript_ajax && $conf->global->AGF_TRAINING_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox ( $htmlname, $event );
			}
			
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '"  style="width:100%" '.$event.'>';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			$jourSemAnc='';
			if ($num) {
				$wdep = new CglDepart ($this->db);
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $resql );
	
					$label  = ''. $obj->intitule_custo;
					$label .= ' avec ' . $obj->firstname.' ' . $obj->lastname;
					
					if ($selectid > 0 && $selectid == $obj->rowid) {
						 $out .= '<option value="' . $obj->rowid . '" style='.$style.' selected="selected"   >'. $label . '</option>';
						$id_act = $obj->rowid;
					} else {
						$out .= '<option value="' . $obj->rowid . '"  style= '.$style.'  >'. $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
		} else {
			dol_print_error ( $db );
		}
		$this->db->free ( $resql );
		return $out;
	
	} /* select_sessionbybull */
	
} // fin de classe

?>