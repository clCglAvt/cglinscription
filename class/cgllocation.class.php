<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 été  2022 	- Migration Dolibarr V15
 *								- replacement method="GET" par method="POST"
 *	Version CAV - 2.8 hiver  2023 
 *		 - suppression de showdocuments1 obsolete
 *		- fiabilisation des foreach
 *		- Séparation refmat en IdentMat et marque  et taille IdentMat obligatoire à 3
 *		- vérification des conflit pour planning vélo (NbLocationParMateriel)
 *		- contrat technique
 *			- remise à plat des status BU/LO
 * Version CAV - 2.8.3 - printemps 2023
 *		- suppression des guillemets dans les textarea
 *		- ajout suppression echange dans pavesuivi
 *		- formatage des référence des vélos en conflit (gras, image d'info)( bug 269)
 * Version CAV - 2.8.4 - printemps 2023
 *		- PostActivité 
 *		- correction conflit location vélo (300)
 *		- ajout liste des contrats en conflit de lication (304)
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
 *   	\file       custum/cglinscription/class/cgllocation.class.php
 *		\ingroup    cglinscription
 *		\brief      Traitement des données
 */

require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once  DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctDolibarrRevues.class.php';
	
/**
 *	Put here description of your class
 */
class CglLocation 
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='skeleton';			//!< Id that identify managed objects
	var $table_element='skeleton';		//!< Name of table without prefix where object is stored

    var $id;
    var $prop1;
    var $prop2;
	var $FormLocation;
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
		global $CRE_LIGLOC ;
		global $ACT_SEL_ACTPART, $LOC_DUP ,  $BtEncais, $BtStripeMail, $BtStripeSMS, $BtStripmail , $ACT_ANNULPART;
		global $ENR_LOCDET, $ENR_LOCINFO, $MOD_LOCINFO, $ACT_SUP_PAIMT, $ACT_CRE_PERS_RESP, $ACT_SEL_PERS_RESP, $ACT_CRE_PAIMT, $ACT_MAJ_PAIMT ,$CONF_MAJ_PAIMT, $ACT_SEL_PAIMT, $CRE_ENCAISS;
		global   $MAJ_TIERS,  $TYPE_SESSION,$CRE_PMTLIGNE, $ENR_PROCREGL;		
		global $ENR_TIERS,$VIDE_TIERS, $SEL_TIERS, $CREE_BULL, $CREE_TIERS_BULL_DOSS , $CREE_BULL_DOSS;

	    global $SAIS_REMISEGENREALE, $mttremisegen, $servremgen, $modactrdv, $RaisRemGen, $textremisegen;

		global $BUL_ANNULER, $BUL_CONFANNULER, $CNTLOC_CLOS, $CNTLOC_DEPARTFAIT, $CNTLOC_DEPARTNONFAIT, $CNTLOC_REOUVRIR, $CNTLOC_DESARCHIVER, $BUL_ABANDON;
		global 		$BUL_ANULCLIENT, $BUL_CONFANULCLIENT;
		global $BUL_CONFABANDON, $CNT_RESERVER,  $CNT_PRE_RESERVER,$CNT_NONRESERVER, $CNT_DEPART, $CNT_RETOUR, $EDT_CMD, $EDIT_CNTLOC;
		global $ACT_SUP_LOCDET, $CONF_SUP_LOCDET, $CONF_SUP_PAIMT;
		global $PAIE_CONFNEGATIF, $PAIE_NEGATIF;
		global $PREP_MAIL,$PREP_SMS, $SEND_SMS,  $SEND_SMS, $SEND_MAIL	;
		
		global $UPD_MATMAD,$UPD_MATMAD_RET, $UPD_RANDO, $UPD_RANDO_RET, $UPD_REMGEN, $UPD_REMGENMOD, $MOD_REMGEN,  $UPD_REMFIX, $MOD_REMFIX ;		
		global $MOD_MATMAD, $MOD_MATMAD_RET, $RETGENMAT, $RETGEN, $RETGENMAD, $RETGENRAND, $RETGENCAUT, $RETGENMATPART;	
		global $MOD_RANDO, $MOD_RANDO_RET, $UPD_CAUTION, $MOD_CAUTION, $MOD_LOC_RET, $UPD_LOC_RET;
		global $SAIS_CAUTACC,  $UPD_CAUTACC, $CAL_ACPT ;
		global $ACT_STRIPESUPP, $CONF_STRIPESUPP ,$ACT_STRIPEREMB, $ACT_STRIPERELMAIL, $ACT_STRIPERELSMS;
		global $BULLNonFacturable, $BULLFacturable;
		
		global $confirm,  $Refdossier, $nvdossier, $rdnvdoss, $priorite, $prioritedossier;
		global $id_client, $action, $id_contrat, $id_contratdet, $fl_BullFacturable, $Session,$db, $type, $InfoPrive, $ActionFuture, $FctBtDelParticipation;

		global $FctBtRemParticipation,$ACT_SUP_REMFIX, $ConfSUPRemFix, $tabrowid ;
		global $LocDateRet	, $LocDateDepose,  $LocLieuRetrait, $LocLieuDepose, $LocMatObs, $LocRandoObs,   $LocDateHeureDepose, $LocDateHeureRet;
		global $LocResaObs, $LocStResa, $LocGlbObs, $caution, $retcaution, $retdoccaution, $topcautionrecue, $topdocrecu, $mttAccompte, $mttcaution;				
		
		global $tiersNom, $TiersVille, $TiersIdPays, $TiersTel, $TiersTel2, $infos_s_tel2, $TiersMail, $TiersMail2, $AuthMail, $TiersAdresse, $TiersCP, $TiersOrig, $Villegiature;
		global $firstname, $civility_id;
		global $PU, $PT, $Rem , $ActPartQte, $ActPartObs, $ActPartIdRdv;		
		global $fk_service, $materiel, $marque, $identmat,  $refmat, $nomprenom, $taille, $PartTaille, $st_dateretrait, $dt_dateretrait, $st_datedepose, $dt_datedepose,  $duree, $observation;
		global $lieuretrait, $lieudepose;
		global $AdrTransf, $modcaution, $ObsCaution, $fk_fournisseur;
		
		global $TypeSessionCli_Agf;
		global $PaimtMode, $PaimtOrg, $PaimtNomTireur, $PaimtMtt, $PaimtDate, $PaimtCheque,$id_paimt, $PaimtNeg, $PmtFutur;
		global  $TypeSessionDep_Agf;
		
		global $StripeMailPayeur, $StripeMtt, $StripeNomPayeur, $libelleCarteStripe, $id_stripe, $modelmailchoisi, $StripeSmsPayeur;
		
		$type = 'Loc';
		// Constantes				
		// action de l'écran
		$VIDE_TIERS='VidTiers';
		$SEL_TIERS='SelTiers';
		$CREE_BULL='CreeBull';
		$MAJ_TIERS='MajTiers';	
		$ENR_TIERS='EnrTiers';	
		$ACT_SEL_ACTPART='SelActPart';
		$CREE_TIERS_BULL_DOSS='CreBullTiersDoss';
		$CREE_BULL_DOSS='CreeBullDos';	
	
		$CRE_LIGLOC='Cre_LigLoc';
		$LOC_DUP='DUPLocMat';
		//		Regroupement de diff�rentes validation en une seule 
		$UPD_MATMAD='UPDSupl';
		$UPD_MATMAD_RET='UPDSupl';
		$UPD_RANDO='UPDSupl';
		$UPD_RANDO_RET='UPDSupl';
		$UPD_CAUTACC='UPDSupl';
		//		Regroupement de diff�rentes validation en une seule 
		$MOD_MATMAD='ModSupl';
		$MOD_MATMAD_RET='ModSupl';
		$MOD_RANDO='ModSupl';
		$MOD_RANDO_RET='ModSupl';
		$SAIS_CAUTACC='ModSupl';
		$MOD_REMGEN='ModSupl';	
		$MOD_REMFIC='ModRemFix';	

		$UPD_REMFIX='RemFix';
		
		$UPD_REMGEN='RemGen';
		$UPD_REMGENMOD='RemGenMod';	
		//if (isset($temp ) and !empty($temp)) $action = $UPD_REMGEN;		
		
		$RETGEN="RetourGeneral";
		$RETGENMAT='RetGenMat';
		$RETGENMAD='RetGenMad';
		$RETGENRAND='RetGenRando';	
		$RETGENCAUT='RetGenCaut';
		$RETGENMATPART='RetGenMatPart';
		
		
		$UPD_LOC_RET='UPDLocRet';
		$MOD_LOC_RET='ModLocRet';
		
		$MOD_CAUTION='ModDocCaution';
		$UPD_CAUTION='UdpCaution';
		$CAL_ACPT='CalculAcompte';
		
		$ACT_SUP_LOCDET='SUPLocDet';
		/*$ACT_ANNULPART='AnnulPart';
		$ACT_SAISIELOCLIGNE='SaisieLigne';*/
		
		$CONF_SUP_LOCDET='ConfSUPLocDet';	
		$ENR_LOCDET='BtEnrLigne';
		$ENR_LOCINFO='EnrLocInfo';
		$MOD_LOCINFO='ModLocInfo';
		
		$CMPL_CAUTION_RET='CompletRtCaut';

		$SAIS_REMISEGENREALE='RemiseGlobale'; 
		
		$tabrowid = array(); 
		$tabrowid = GETPOST("rowid", 'array');
	
		$ACT_SUP_REMFIX='SupRemFix';
		$ConfSUPRemFix='ConfSUPRemFix';
		$ACT_CRE_PAIMT='CrePaimt';
		$ACT_MAJ_PAIMT='MajPaimt';
		$CONF_MAJ_PAIMT='ConfMajPaimt';
		$ACT_SUP_PAIMT='SupPaimt';
		$CONF_SUP_PAIMT='ConfSUPActPaimt';	
		$ACT_SEL_PAIMT='SelPaimt';
		$CRE_ENCAISS='nvPaiemt';
		$CRE_PMTLIGNE='PmtLigne';	
		$ENR_PROCREGL='MajProcRegl';
		$CNT_PRE_RESERVER='PreReserver';
		$CNT_RESERVER='Reserver';
		$CNT_DEPART='depart';
		$CNT_RETOUR='retour';
		$BUL_ANNULER='Annuler';
		$BUL_CONFANNULER='Conf_annuler';

		$CNTLOC_CLOS='Clore';
		$CNTLOC_DEPARTFAIT='DepartFait';
		$CNTLOC_DEPARTNONFAIT='DepartNonFait';
		$CNT_NONRESERVER="DeReservation";
		
		$CNTLOC_REOUVRIR='Reouvrir';
		$CNTLOC_DESARCHIVER='Desarchiver';
		$BUL_ABANDON='Archiver';
		$BUL_ANULCLIENT='AnnulClient';
		$BUL_CONFANULCLIENT='Conf_AnnulClient';
		$BUL_CONFABANDON='Conf_Archiver';
		$PAIE_CONFNEGATIF='RaisonPaiementNeg';
		$PAIE_NEGATIF='DemRaisonPaiementNeg';
		
		$BULLNonFacturable='NonFacturable';
		$BULLFacturable='Facturable';
		

		$EDT_CMD = 'CreerCommande';
		$EDIT_CNTLOC = 'builddoc';
		$TYPE_SESSION = 'EnrType_Session';		
		
		// récupération des paramètres de l'URL	
		$action	= GETPOST('action','alpha');
		if ( GETPOST('BtRemise','alpha') == 'Enregistrer')
			$action = $UPD_REMGEN;
		if ( GETPOST('BtRemFix','alpha') == 'Enregistrer')	
			$action = $UPD_REMFIX;	

		$fl_BullFacturable=GETPOST('BullFacturable');		
		
		// Stripe
		$ACT_STRIPESUPP='StripeSupp';
		$ACT_STRIPEREMB='StripeRemb';
		$ACT_STRIPERELMAIL='StripeMail';
		$ACT_STRIPERELSMS='StripeSms';
		$CONF_STRIPESUPP='ConfStripeSupp';
		
		
		// MAil ET SMS
		$PREP_MAIL='presend';
		$PREP_SMS='presendsms';;
		$SEND_MAIL='send';	
		$SEND_SMS='sendSMS';
		$FctBtMod	= GETPOST('FctBtMod','alpha');
		$FctBtRemParticipation	= GETPOST('FctBtRemParticipation','alpha');
		$FctBtDelParticipation	= GETPOST('FctBtDelParticipation','alpha');
		$BtEncais= GETPOST('BtEncais','alpha');
		$BtStripeMail= GETPOST('BtStripeMail','alpha');
		$BtStripeSMS= GETPOST('BtStripeSMS','alpha');
		//$BtStripmail=GETPOST("BtStripmail", 'alpha');
		$id_stripe=GETPOST('id_stripe','int');			
	
		$StripeNomPayeur=GETPOST('StripeNomPayeur','alpha');
		$StripeMailPayeur=GETPOST('StripeMailPayeur','alpha');
			if (!empty($StripeMailPayeur)) $_POST['sendto'] = $StripeMailPayeur;
		$StripeSmsPayeur=GETPOST('StripeSmsPayeur','alpha');
		$StripeMtt=GETPOST('StripeMtt','decimal');	
		
		$modelmailchoisi=GETPOST('modelmailstripe','alpha');
		$libelleCarteStripe=GETPOST('libelleCarteStripe','alpha');	
		if (empty($modelmailchoisi) or $modelmailchoisi == -1) $modelmailchoisi =GETPOST('modelmailselected','alpha');	 

		if (!empty(GETPOST('sendmail','alpha')) or !empty(GETPOST('sendSMS','alpha'))) // on va envoyer le message, on récupère les valeurs de _SESSION
		{
			 $StripeNomPayeur  =	$_SESSION['StripeNomPayeur'];
			 $libelleCarteStripe  =	$_SESSION['libelleCarteStripe'];
			  $StripeMailPayeur  =	$_SESSION['StripeMailPayeur'];
			  $StripeSmsPayeur  =	$_SESSION['StripeSmsPayeur'];
			  $StripeMtt  =	$_SESSION['StripeMtt'];
			  $modelmailchoisi  =	$_SESSION['modelmailchoisi'];
			  if (empty($id_stripe)) $id_stripe  =	$_SESSION['id_stripe'];
		}
		
		if (( $action == 'presend' and empty(GETPOST('modelselected','alpha')))//Préparer le mail
			or !empty(GETPOST('sendmail','alpha') )  or !empty(GETPOST('sendSMS','alpha') )// Envoi mail prêt
			or ( ( !empty($BtStripeSMS) or !empty($BtStripeMail) or $action == $ACT_STRIPERELMAIL ) and empty(GETPOST('modelselected','alpha')) and empty(GETPOST('sendmail','alpha') )))		 // Preparer Mail de demande Stripe ou relance	
			// vider les valeurs de _SESSION
			{
				unset($_SESSION['StripeNomPayeur']); 
				unset($_SESSION['StripeMailPayeur']);
				unset($_SESSION['StripeSmsPayeur']);   
				unset($_SESSION['libelleCarteStripe']); 
				unset($_SESSION['StripeMtt']); 
				unset($_SESSION['modelmailchoisi']);
			}
		$id_client	= GETPOST('id_client','int');
		$id_contrat	= GETPOST('id_contrat','int');
		$ref_contrat	= GETPOST('ref_contrat','alpha');
		$idbull	= GETPOST('idbull','int');
		$btaction	= GETPOST('btaction','alpha');
		if ($conf->cahiersuivi) {
			if (empty($id_contrat) and !empty(GETPOST('idbull','int')) and $btaction == 'Supprime') // défini dans html_pavé de html_suivi_client.class.php
			{
				$id_contrat = $idbull;
				$action = 'SupEchange'; // défini dans actions_gestionSuivi.inc.php
			}
		}
		
		// tiers	
		$TiersOrig = GETPOST('TiersOrig','int');
		$tiersNom	= GETPOST('tiersNom','alpha');
		$TiersVille	= GETPOST('TiersVille','alpha');
		$TiersIdPays	= GETPOST('TiersIdPays','int');		
		$firstname	= GETPOST('firstname','alpha');
		$civility_id	= GETPOST('civility_id','int');
		$TiersTel	= GETPOST('TiersTel','alpha');
		$TiersTel2	= GETPOST('options_s_tel2','alpha');
		$TiersMail	= GETPOST('TiersMail','alpha');
		$AuthMail	= GETPOST('AuthMail','int');
		$TiersAdresse = GETPOST('TiersAdresse','alpha');
		$Villegiature = GETPOST('Villegiature','alpha');
		$TiersCP 	= GETPOST('TiersCP','alpha');

	
		if ($action == $CREE_BULL) {// Arrivée par Suivi Dossier - dossier connu
				$Refdossier = GETPOST('dossier','int'); 
		}

		// Info location
		
		$mttcaution		= GETPOST('mttcaution','decimal');
		$mttAccompte		= GETPOST('mttAccompte','decimal');
		
		$LocLieuRetrait		= GETPOST('LocLieuRetrait','alpha');
		$LocLieuDepose		= GETPOST('LocLieuDepose','alpha');
		$LocMatObs			= GETPOST('LocMatObs','alpha');
		$LocRandoObs			= GETPOST('LocRandoObs','alpha');
		$LocResaObs			= GETPOST('LocResaObs','alpha');
		if ($LocResaObs  == $langs->trans("LocObsResaModele")) $LocResaObs = '';
		$LocStResa			= GETPOST('LocStResa','int');
		$LocGlbObs		= GETPOST('LocGlbObs','alpha');
		$matret 	=GETPOST('matret','int');
		
		// loc materiel
		$id_contratdet	= GETPOST('id_contratdet','int');
		$fk_service	= GETPOST('fk_service','int');
		
		$fk_fournisseur	= GETPOST('fk_fournisseur','int');		
		$materiel 	= GETPOST('materiel','alpha');
		$marque 	= GETPOST('marque','alpha');
		$refmat = GETPOST('refmat','alpha');
		$identmat = GETPOST('identmat','alpha');
		
		$nomprenom	= GETPOST('nomprenom','alpha');
		$taille 	= GETPOST('taille','alpha');
		$PartTaille 	= GETPOST('PartTaille','alpha');
		
		
		$lieuretrait	= GETPOST('lieuretrait','alpha');
		$lieudepose	= GETPOST('lieudepose','alpha');

		// on doit prendre la date YYYMMDD dans 	LocDateRet au format jj/mm/AAAA
		$LocDateRet			= GETPOST('LocDateRet','alpha');	
		$LocDateDepose		= GETPOST('LocDateDepose','alpha');
		$LocDateRetmin 	= GETPOST('LocDateRetmin','alpha');
		$LocDateRethour 	= GETPOST('LocDateRethour','alpha');
		$LocDateDeposemin 	= GETPOST('LocDateDeposemin','alpha');
		$LocDateDeposehour 	= GETPOST('LocDateDeposehour','alpha');	
	
		if (strlen(substr($LocDateDepose,6)) == 2) $LocDateDepose = substr($LocDateDepose,0,6).(int)'20'.substr($LocDateDepose,6,2);

		$wc = new CglFonctionCommune($this->db);
		if (!empty($LocDateDepose)) $LocDateHeureDepose		=  $wc->transfDateMysql($LocDateDepose).' '.$LocDateDeposehour.':'.$LocDateDeposemin;
		if (!empty($LocDateDepose)) 
			$fldateinvalide = !checkdate (substr($LocDateDepose, 3,2), substr($LocDateDepose, 0,2), substr($LocDateDepose, 6));
		if (strlen(substr($LocDateRet,6)) == 2) $LocDateRet = substr($LocDateRet,0,6).(int)'20'.substr($LocDateRet,6,2);
		if (!empty($LocDateRet)) $LocDateHeureRet		=  $wc->transfDateMysql($LocDateRet).' '.$LocDateRethour.':'.$LocDateRetmin;
		if (!empty($LocDateRet)) 
			$fldateinvalide = !checkdate (substr($LocDateRet, 3,2), substr($LocDateRet, 0,2), substr($LocDateRet, 6));
		unset ($wc);
		$duree 	= GETPOST('duree','int');
		if ($duree == 0) $duree = 0.5;
		if (empty($duree) or $duree == 999) $duree 	= GETPOST('saisieduree','int');
		
		$PU	= GETPOST('PU','decimal');
		$PT	= GETPOST('PT','decimal');
		$Rem	= GETPOST('Rem','decimal');
		$ActPartQte	= GETPOST('ActPartQte','int');
		if (! empty($id_contratdet)) $ActPartQte	= 1;
		$observation	= GETPOST('observation','alpha');
		$infos_s_tel2 = GETPOST('infos_s_tel2','alpha');
		
		global $wfcom;
		$wfcom =  new CglFonctionCommune ($this->db);
		$InfoPrive	= GETPOST('InfoPrive','alpha');
		$InfoPrive	= $wfcom->cglencode($InfoPrive);
		$ActionFuture	= GETPOST('ActionFuture','alpha');
		$ActionFuture	= $wfcom->cglencode($ActionFuture);
		$PmtFutur	= GETPOST('PmtFutur','alpha');
		$PmtFutur	= $wfcom->cglencode($PmtFutur);
		
		$AdrTransf	= GETPOST('AdrTransf','alpha');
		$modcaution = GETPOST('modcaution','int');
		$ObsCaution = GETPOST('ObsCaution','alpha');
		
		$caution	= GETPOST('caution','int');
		$retcaution	= GETPOST('retcaution','int');	
		$retdoccaution	= GETPOST('retdoccaution','int');	
		
		$topcautionrecue	= GETPOST('topcautionrecue','int');	
		$topdocrecu	= GETPOST('topdocrecu','int');			
		
		$mttremisegen= GETPOST('mttremisegen','decimal');
		$textremisegen= GETPOST('textremisegen','alpha');		
		$servremgen= GETPOST('servremgen','int');
		$RaisRemGen	= GETPOST('RaisRemGen','alpha');
		$confirm	= GETPOST('confirm','alpha');
		$idmenu	= GETPOST('idmenu','int');
		if (empty($idmenu)) $idmenu = 163;

		// paiement
		$id_paimt	= GETPOST('id_paimt','int');
		$PaimtMode	= GETPOST('PaimtMode','alpha');
		$PaimtCheque	= GETPOST('PaimtCheque','alpha');
		$PaimtOrg	= GETPOST('PaimtOrg','alpha');
		$PaimtNomTireur	= GETPOST('PaimtNomTireur','alpha');
		$PaimtMtt 	= GETPOST('PaimtMtt','decimal');
		$PaimtDate 	= GETPOST('PaimtDate','date');
		if (strlen(substr($PaimtDate,6)) == 2) $PaimtDate = substr($PaimtDate,0,6).(int)'20'.substr($PaimtDate,6,2);
		$Session 	= GETPOST('session','int');
		$PaimtNeg	= GETPOST('PaimtNeg','alpha');
		
		$FiltrPasse= GETPOST('FiltrPasse','int');
		if (empty($FiltrPasse)) $FiltrPasse = 0;
		
		$paramsBull = "&amp;search_client=".$id_client."&amp;id_contrat=".$id_contrat."&amp;ActionFuture=".$ActionFuture;
		$paramsTiers = "&amp;action=".$action."&amp;search_client=".$id_client."&amp;tiersNom=".$tiersNom."&amp;TiersVille=".$TiersVille."&amp;TiersIdPays=".$TiersIdPays;
		$paramsTiers .= "&amp;TiersTel=".$TiersTel."&amp;TiersTel2=".$TiersTel2."&amp;infos_s_tel2=".$infos_s_tel2."&amp;TiersMail=".$TiersMail."&amp;TiersAdresse=".$TiersAdresse;
		$paramsActPart = "&amp;id_act=".$id_act."&amp;id_part=".$id_part;
		$paramsPart = "&amp;id_part=".$id_part."&amp;PartNom=".$PartNom."&amp;PartPrenom=".$PartPrenom."&amp;PartDateNaissance=".$PartDateNaissance;
		$paramsPart .= "&amp;id_contratdet=".$id_contratdet."&amp;taille=".$taille."&amp;PartPoids=".$PartPoids."&amp;PartENF=".$PartENF."&amp;PartTel=".$PartTel."&amp;PU=".$PU."&amp;PT=".$PT;
		$paramremise = "&amp;RaisRemGen=".$RaisRemGen;
		$paramsPart .= "&amp;PT=".$PT."&amp;Rem=".$Rem."&amp;Qte=".$ActPartQte."&amp;ActPartRang=".$ActPartRang."&amp;ActPartRang=".$PartTel;
		$paramsPersRec = "&amp;id_persrec=".$id_persrec."&amp;PersNom=".$PersNom."&amp;PersPrenom=".$PersPrenom."&amp;id_client=".$id_client;
		$paramsPersRec .= "&amp;PersTel=".$PersTel."&amp;PersParent=".$PersParent."&amp;id_age_contact=".$id_age_contact;
		$paramsPaiement = "&amp;PaimtMode=".$PaimtMode."&amp;PaimtOrg=".$PaimtOrg."&amp;PaimtNomTireur=".$PaimtNomTireur;
		$paramsPaiement .= "&amp;PaimtMtt=".$PaimtMtt."&amp;Paimtdate=".$PaimtDate."&amp;id_paimt=".$id_paimt."&amp;InfoPrive=".$InfoPrive;
		$paramsPaiement .= "&amp;PaimtNeg=".$PaimtNeg."&amp;PmtFutur=".$PmtFutur;
					
		$TypeSessionDep_Agf	= GETPOST('TypeSessionDep_Agf','int');
		$rdvprinc	= GETPOST('rdvprinc','alpha');
		$alterrdv	= GETPOST('alterrdv','alpha');
		$bull=new Bulletin($db);
		if ($id_contrat or $ref_contrat ) 
		{
			$bull->fetch_complet_filtre(-1, $id_contrat, $ref_contrat);
			if (empty($id_contrat)) $id_contrat = $bull->id; 
		}
/*
		else {
			$bull->fetch_complet_filtre( 0, 0);  // Chargement du contratLoc en cours de constitution
			$id_contrat = $bull->id;
		}
*/
		unset ($wfcom);
	} /*  init */	

	function EnrTypeSession()
	{
		global $bull, $TypeSessionCli_Agf;			
		//$bull->type_session_cgl = $TypeSessionCli_Agf + 1;
		if ($bull->type_session_cgl == 1) $bull->type_session_cgl =2;
		else $bull->type_session_cgl = 1;

		$bull->updateTypesession();
	} //EnrTypeSession
	/* INFO GENERALE */
	function recherchelb_statResa($id)
	{
        global $conf,$langs;
        $langs->load("dict");

        $out='';
        //$StResa=array();
        //$label=array();
		
        $sql = "SELECT rowid , libelle ";
        $sql.= " FROM ".MAIN_DB_PREFIX."cgl_c_stresa";
        $sql.= " WHERE active = 1";
        $sql.= ' and rowid = "'.$id.'"';
		
        dol_syslog(get_class($this)."::recherchelb_statResa sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            if ($num)
            {
                    $obj = $this->db->fetch_object($resql);
                    $lb	= $obj->libelle;
            }
        }
        else
		{
            dol_print_error($this->db);
        }
        return $lb;
		
	} //recherchelb_statResa
	function select_StResa($selected='',$htmlname='LocStResa',$htmloption='',$maxlength=0)
    {
        global $conf,$langs;
        $langs->load("dict");

        $out='';
        $StResa=array();
        $label=array();
		
        $sql = "SELECT rowid , libelle ";
        $sql.= " FROM ".MAIN_DB_PREFIX."cgl_c_stresa";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY ordre ASC";
		
        dol_syslog(get_class($this)."::select_StResa sql=".$sql);
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
                    $StResa[$i]['rowid'] 	= $obj->rowid;
                    $StResa[$i]['label']	= $obj->libelle;
                    $label[$i] = dol_string_unaccent($StResa[$i]['label']);
                    $i++;
                }
				if (!empty($StResa)) {
					foreach ($StResa as $row)
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
					} //foreach
				}
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
    }// select_StResa
	
	function select_caution($selected='',$htmlname='Caution',$htmloption='',$maxlength=0)
    {
		
        global $conf,$langs;
        $langs->load("dict");

        $out='';
        $StResa=array();
        $label=array();

        $sql = "SELECT rowid , libelle , code";
        $sql.= " FROM ".MAIN_DB_PREFIX."cgl_c_caution";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY ordre ASC";
		
        dol_syslog(get_class($this)."::select_caution sql=".$sql);
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
                    $StResa[$i]['rowid'] 	= $obj->rowid;
                    $StResa[$i]['label']	= $obj->libelle;
                    $label[$i] = dol_string_unaccent($StResa[$i]['label']);
                    $i++;
                }
				if (!empty($StResa)) {
					foreach ($StResa as $row)
					{
						//print 'rr'.$selected.'-'.$row['label'].'-'.$row['code_iso'].'<br>';
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
					} // Foreach
				}
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
    }// select_caution
	function EnrInfoLoc() 
	{
		global $LocDateRet, $LocDateHeureDepose, $LocDateHeureRet,  $LocDateDepose, $LocLieuRetrait, $LocLieuDepose, $InfoPrive, $ActionFuture;
		global $LocResaObs, $LocStResa, $LocGlbObs, $MOD_LOCINFO;
		global $action, $id_contrat, $db, $bull, $langs, $wfcom;
		
		$wfcom =  new CglFonctionCommune ($this->db);
		
		// TEst de date valide
		$flreturn =false;
		$fldateinvalide = !checkdate (substr($LocDateDepose, 3,2), substr($LocDateDepose, 0,2), substr($LocDateDepose, 6));
		if ( $fldateinvalide)  { 
				$error++; setEventMessage($langs->trans("ErrorFieldFormat",$langs->transnoentitiesnoconv("Depose")).':'.$LocDateDepose,'errors');
				$flreturn =true;
		}

		$fldateinvalide = !checkdate (substr($LocDateRet, 3,2), substr($LocDateRet, 0,2), substr($LocDateRet, 6));
		if ( $fldateinvalide)  { 
				$error++; setEventMessage($langs->trans("ErrorFieldFormat",$langs->transnoentitiesnoconv("Retrait")).':'.$LocDateRet,'errors');
				$flreturn =true;
		}
		if ($flreturn)		return -1;
		
		// Reprise des données saisies
		if (!empty($LocDateHeureRet ) ) 
		{
			$bull->locdateretrait =$LocDateHeureRet;
			$modif=1;
		}		
		
		if (!empty($LocDateHeureDepose ))
		{
			$bull->locdatedepose =$LocDateHeureDepose;
			$modif=1;
		}
		if ($LocDateHeureRet > $LocDateHeureDepose ) {
			$error++; setEventMessage($langs->trans("ErrDateErronne"),'errors');
			$action = $MOD_LOCINFO;
			return -9;
		}
		if (!empty($LocLieuRetrait ))
		{
			$bull->loclieuretrait =$LocLieuRetrait;
			$bull->loclieuretrait =$wfcom->cglencode ($bull->loclieuretrait );
			$modif=1;
		}
		if (!empty($LocLieuDepose ))
		{
			$bull->loclieudepose  =$LocLieuDepose;
			$bull->loclieudepose =$wfcom->cglencode ($bull->loclieudepose );
			$modif=1;
		}
		if (!empty($LocResaObs ))
		{
			$bull->locResa =$LocResaObs;
			$bull->locResa =$wfcom->cglencode ($bull->locResa );
			$modif=1;
		}
		if (!empty($LocStResa))
		{
			$bull->fk_sttResa =$LocStResa;
			$modif=1;
		}		
		$bull->locObs =$LocGlbObs;	
		$bull->locObs =$wfcom->cglencode ($bull->locObs );	
		$bull->ObsPriv =$InfoPrive;		
		$bull->ObsPriv =$wfcom->cglencode ($bull->ObsPriv );	
		$bull->ActionFuture =$ActionFuture;	
		$bull->ActionFuture =$wfcom->cglencode ($bull->ActionFuture );	
		;
		$ret = $bull->update();
		$ret = $bull->updatelineDate();
		
		$LocDateHeureDepose = '';
		$LocDateHeureRet = '';
		$LocDateDepose = '';
		$LocDateRet = '';
		
		unset($wfcom);
	} // EnrInfoLoc
	function script_service($htmlname)
	{
			global $DefRechService;
		
		if (empty($DefRechService )) {
			$DefRechService = 1;
			$out.= '<script> '."\n";
					
			$out.= 'function creerobjet(fichier)  '; 
			$out.= '{  '; 
				$out.= '	if(window.XMLHttpRequest) ';  // FIREFOX 
				$out.= '		xhr_object = new XMLHttpRequest();  '; 
				$out.= '	else if(window.ActiveXObject)';  // IE  
				$out.= '		xhr_object = new ActiveXObject("Microsoft.XMLHTTP"); '; 
				$out.= '		else '; 
				$out.= '			return(false); '; 
				$out.= '	xhr_object.open("GET", fichier, false);'; 
				$out.= '	xhr_object.send(null); '; 
				$out.= '	if(xhr_object.readyState == 4)'; 
				$out.= '		return(xhr_object.responseText); '; 
				$out.= '	else'; 
				$out.= '		return(false); '; 
			$out.= '	}'; 
						
							  
			$out.= 'function RechInfoService(o) '; 
				$out.= '{ '; 	
				$out .= '  val = o.value;'; 
				$out.= '	if (val > -1) { ';
				// Interrogation info prix du service
				$out .=' 		url="ReqInfoService.php?ID=".concat(val);'."\n";
				$out.= "		var	Retour = creerobjet(url); ";
				$out .= '   	var tableau = Retour.split("?",2);';
					
				$out .= ' 		document.getElementById("journee").style.visibility = "visible";';
				$out .= ' 		document.getElementById("joursup").style.visibility = "visible";';
				$out .= '		document.getElementById("TIPUJ").style.visibility  = "visible" ;';
				$out .= '		document.getElementById("TIPUDJ").style.visibility  = "visible" ;';
				$out .= '	v_journee=parseFloat(tableau[0],10);';
				$out .= '	document.getElementById("journee").value = v_journee ;';
				$out .= '	v_joursup=parseFloat(tableau[1],10);';
				$out .= '	document.getElementById("joursup").value = v_joursup; ';				
				$out .= '	CalculPU(1);'."\n";
				$out.= '} '; 
			$out.= '} '; 
			
			$out.= 'function CalculPU(orig) '."\n"; 
				$out.= '{ ';
				// Calcul du prix par nouvelle saisie de prix
				$out .= 'if (orig == 1) v_duree = parseFloat(document.getElementById("selectduree").value,10);'."\n";
				// calcul du prix par nouvelle saisie de durée - durée longue - A traiter, il faudrait pouvoir saisir un prix total
				$out .= 'else if (parseFloat(document.getElementById("saisieduree").value,10) == 999) v_duree = parseFloat(document.getElementById("selectduree").value,10);'."\n";	
				// calcul du prix par nouvelle saisie de durée - durée < 16 jours
				$out .= 'else v_duree = parseFloat(document.getElementById("saisieduree").value,10);'."\n";	
				$out .= 'v_journee=parseFloat(document.getElementById("journee").value,10);'."\n";
				$out .= 'v_joursup=parseFloat(document.getElementById("joursup").value,10);'."\n";
				$out .= 'v_pu = parseFloat(document.getElementById("PU").value,10) ;'."\n";
				$out .= 'v_Rem = 1 ;';
				//$out .= 'if (document.getElementById("Rem").value == "") v_Rem = 1; ';
				//$out .= ' else  v_Rem = (100 - document.getElementById("Rem").value)/100;';
				$out .= 'if ( v_joursup == "") v_joursup =0 ;';
				$out .= 'if (v_duree == null) {';
				$out .= '	v_pu = "" ;';;
				$out .= '	}';
				$out .= 'if ( v_duree == 0 )  {'; // cas de la demi-journée
				$out .= '	 v_pu = v_joursup ;';
				$out .= '	}';
				$out .= 'if (v_duree >= 1) {';
				$out .= '	v_pu = v_journee + ((v_duree -1 )* v_joursup );';
				$out .= '	}';

/* a valider avc Mathieu avant de le mettre en place
// Pas utilisé cette année, car , pour cette année, le prix de la journée supplémentaire est le prix d'une demi-journée, donc calcul OK
// on laisse le code en commentaire, en prévision d'un changement de calcul en 2019

				$out .= 'if (v_duree >= 1) {';
				$out .= '	document.getElementById("PU").value =  ((v_duree )* v_journee );';
				$out .= '	document.getElementById("PUAff").innerHTML = ((v_duree )* v_journee ) ;';
				$out .= '	document.getElementById("AffEuros").style.visibility  = "visible" ;';
				$out .= '	}';
*/
		/*		$out .= 'else {';
				$out.= '		 document.getElementById("PU").value = null;	'; 
				$out.= '		 document.getElementById("PUAff").innerHTML = null;	'; 
				$out .= '	 document.getElementById("AffEuros").style.visibility  = "hidden" ;';
				$out .= '	}';				
		*/		
				$out .= ';';
				$out .= 'v_pu = parseFloat(v_pu * v_Rem,2);';
				$out .= ' document.getElementById("PU").value = v_pu;';
				$out .= ' document.getElementById("PUAff").innerHTML  = v_pu;';
				$out .= '	document.getElementById("AffEuros").style.visibility  = "hidden" ;'."\n";
				$out.= '} '."\n"; 
				
// calcul du tarif si la saisie est faite dans le select_durée							   
				$out.= 'function CalculPUbyDuree(o) '."\n"; 
				$out.= '{ '; 			
				$out .= 'CalculPU(1);';
					$out.= '} '; 
			
// Calcul du tarif en cas de dépassement du choix de select_duree			
				$out.= 'function CalculPUbySaisDuree(o) '; 
				$out.= '{ '; 	
				$out .= '  v_duree = parseFloat(o.value,10);';
				//$out .= 'document.getElementById("duree").value = v_duree; ';	
				$out .= 'CalculPU(2);';
			$out.= '} '; 				


// calcul du tarif si la saisie est faite dans la sasie du prix d'une demi-journée						   
				$out.= 'function CalculPUbyDemiJournee(o) '."\n"; 
				$out.= '{ '; 			
				$out .= 'CalculPU(2);';
					$out.= '} '; 
					
					
// calcul du tarif si la saisie est faite dans la sasie du prix d'une -journée						   
				$out.= 'function CalculPUbyJournee(o) '."\n"; 
				$out.= '{ '; 			
				$out .= 'CalculPU(2);';
					$out.= '} '; 
					
			$out.= '</script> '."\n";			
			
			return $out;
		}
	}//script_service
	function select_service($selected='',$htmlname='select_service',$htmloption='',$maxlength=0)
    {
        global $conf,$langs;
        $langs->load("dict");

        $out='';
        $StResa=array();
        $label=array();
		$out .= $this->script_service($htmlname);

        $sql = "SELECT p.rowid , ref";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " , ".MAIN_DB_PREFIX."product_extrafields";
        $sql.= " WHERE fk_object = p.rowid and fk_product_type = 1 ";
        $sql.= " AND  tosell = 1 and s_ordre > 0";
        $sql.= " ORDER BY s_ordre ASC";
		
        dol_syslog(get_class($this)."::select_service sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" '.$htmloption;
			$out .=  ' onchange="RechInfoService(this)" ';
			$out .= '>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
				$out.= '<option value="-1">';
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $StResa[$i]['rowid'] 	= $obj->rowid;
                    $StResa[$i]['label']	= $obj->ref;
                    $label[$i] = dol_string_unaccent($StResa[$i]['label']);
                    $i++;
                }
				if (!empty($StResa)) {
                foreach ($StResa as $row)
					{
						//print 'rr'.$selected.'-'.$row['label'].'-'.$row['code_iso'].'<br>';
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
					} //foreach
				}
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
    }// select_service
	function select_jour($selected='',$htmlname='select_jour',$htmloption='',$maxlength=0)
    {
        global $conf,$langs;
        $langs->load("dict");

        $out='';
        //$StResa=array();
        //$label=array();
		$out .= $this->script_service($htmlname);

        $sql = "SELECT jour ";
        $sql.= " FROM ".MAIN_DB_PREFIX."cgl_jour ";
		
        dol_syslog(get_class($this)."::select_jour sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql) {
            $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" ';
			$out .=  ' onchange="CalculPUbyDuree(this)" > ';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
				$out.= '<option value="-1">';
                while ($i<$num)                 {					
                    $obj = $this->db->fetch_object($resql);					
					$jour = $obj ->jour;
					if ($jour == 0) $aff_jour = '0,5';
					elseif ($jour == 999) $aff_jour = 'autre';
					else  $aff_jour = $jour;
                    //print 'rr'.$selected.'-'.$row['label'].'-'.$row['code_iso'].'<br>';
                    if ($selected && $selected != '-1' && ($selected == $row['rowid'] ) )
                    {
                        $foundselected=true;
                        $out.= '<option value="'.$jour.'" selected="selected">'.$aff_jour;
                    }
                    else
					{
                        $out.= '<option value="'.$jour.'">'.$aff_jour;
                    }
                    $out.= dol_trunc($row['label'],$maxlength,'middle');
                    $out.= '</option>';
					$i++;
                }
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
    }// select_jour
	function select_duree($selected='',$htmlname='select_jour',$htmloption='',$maxlength=0)
    {
        global $conf,$langs;
        $langs->load("dict");
        $out='';
        //$StResa=array();
        //$label=array();
		$out .= $this->script_service($htmlname);

		$sql = "SELECT jour ";
        $sql.= " FROM ".MAIN_DB_PREFIX."cgl_jour ";

		$selected = intval($selected);
        dol_syslog(get_class($this)."::select_duree sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql) {
            $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" ';
			$out .=  ' onchange="CalculPUbyDuree(this)" > ';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
				$out.= '<option value="-1">';
                while ($i<$num)   {	
					if (empty($jouranc)) $jouranc = 0;
                    $obj = $this->db->fetch_object($resql);					
					$jour = $obj->jour;						
	/*				if (($selected ) and  $selected < $jour and $selected >= $jouranc  ) {	
						$out.= '<option value="'.$selected.'"  selected="selected">'.$selected;
						$out.= '</option>';                
                        $foundselected=true;
					}
*/						
					$jouranc = $jour;	
					if ($jour == 0) $aff_jour = '0,5';
					elseif ($jour == 999) $aff_jour = 'autre';
					else  $aff_jour = $jour;
                    //print 'rr'.$selected.'-'.$row['label'].'-'.$row['code_iso'].'<br>'
					if ( $selected != '-1' && ($selected == $jour ) )                    {
                        $foundselected=true;
                        $out.= '<option value="'.$jour.'" selected="selected">'.$aff_jour;
                    }
                    else 					{
                        $out.= '<option value="'.$jour.'">'.$aff_jour;
                    }
                    $out.= '</option>';
					$i++;
                }
            }
			if (!$foundselected and $selected && $selected != '-1') {
				 $out.= '<option value="'.$selected.'"  selected="selected">'.$selected;
                    $out.= '</option>';
			}
            $out.= '</select>';
			
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
    }// select_duree

  /**
     *  Output html form to select a third party
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form 	
     *  @param  string	$filter         optional filters criteras (example: 's.rowid <> x')
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *  @param	string	$filterkey		Filter on key value
     *  @param	int		$outputmode		0=HTML select string, 1=Array
     *  @param	int		$limit			Limit number of answers
     * 	@return	string					HTML string with
     */
    function select_fournisseur($selected='',$htmlname='socid',$filter='',$showempty=0, $showtype=0, $forcecombo=0, $events=array(), $filterkey='', $outputmode=0, $limit=0)
    {  
		global $conf,$user,$langs, $socid; 
		global $DefCreObj, $DefLienTiers, $DefRechTiers, $DefEffTiers;
		
		if (empty($DefLienTiers)) $DefLienTiers=1;
		if (empty($DefRechTiers)) $DefRechTiers=1;
		if (empty($DefCreObj)) $DefCreObj=1;
		if (empty($DefEffTiers)) $DefEffTiers=1;
		
		$out=''; $num=0;
        $outarray=array();
		
        $sql = "SELECT rowid , nom , town";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe";
        $sql.= " WHERE  fournisseur = true";
        $sql.= " ORDER BY  nom, town ";			 
		if ($limit > 0) $sql.=$this->db->plimit($limit);

        dol_syslog(get_class($this)."::select_fournisseur ");
        $resql=$this->db->query($sql);
			
        if ($resql)        {
          if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)         {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            	$out.= ajax_combobox($htmlname, $events, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
			}
						
			// Construct $out and $outarray
			$out.= "\n".'<select id="'.$htmlname.'" name="'.$htmlname.'"';
			$out .= '>';
            if ($showempty) $out.= '<option value="-1"></option>'."\n";
			
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)    {
                    $obj = $this->db->fetch_object($resql);
                    
                    $label=$obj->nom;
					if (!empty($obj->town)) $label.= ' ('.$obj->town.')';
                    if ($showtype)                    {
                        if ($obj->client || $obj->fournisseur) $label.=' (';
                        if ($obj->client == 1 || $obj->client == 3) $label.=$langs->trans("Customer");
                        if ($obj->client == 2 || $obj->client == 3) $label.=($obj->client==3?', ':'').$langs->trans("Prospect");
                        if ($obj->fournisseur) $label.=($obj->client?', ':'').$langs->trans("Supplier");
                        if ($obj->client || $obj->fournisseur) $label.=')';
                    }
                    if ($selected > 0 && $selected == $obj->rowid)                    {
                        $out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
                    }
                    else					{
                        $out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
                    }
                    array_push($outarray, array('key'=>$obj->rowid, 'value'=>$obj->name, 'label'=>$obj->name));
                    $i++;
                    if (($i % 10) == 0) $out.="\n";
                }
            }
            $out.= '</select>'."\n"; 							
		}
        else        {   
			dol_print_error($this->db);
        }

        $this->result=array('nbofthirdparties'=>$num);

        if ($outputmode) return $outarray;
        return $out;
    } // select_fournisseur

	
	
	function EnrCautAccptt()
	{	
		global $bull, $id_contrat, $mttcaution,  $mttAccompte;
		$bull->id = $id_contrat;
		$mttAccompte = price2num($mttAccompte) ;
		$bull->update_champs('mttCaution', $mttcaution, 'mttAcompte', $mttAccompte); 
	} // EnrCautAccptt
	
	
	function DupLocDet_1()
	{
		global $bull, $id_contratdet, $action, $LOC_DUP, $user;
		
		$linedup = new BulletinLigne ($this->db, $bull->type);
		$line_orig = $bull->RechercheLign($id_contratdet);		
		// Pour les enr de type 0 - activité
		$linedup->qte =$line_orig->qte;				// Quantity (example 2)
		$linedup->pu_enf =$line_orig->pu_enf;      	// prix enfant du départ
		$linedup->pu_grp =$line_orig->pu_grp;      	// prix groupe du départ
		$linedup->pu_adlt =$line_orig->pu_adlt;     	// prix adlt du départ
		$linedup->pu =$line_orig->pu;      	// P.U. HT (example 100)
		$linedup->remise_percent =$line_orig->remise_percent;	// % de la remise ligne (example 20%)
		$linedup->rangdb = 0 ;
		$linedup->rangecran = 0 ;

		if ( $action == $LOC_DUP)
		{
			// Depuis llx_agefodd_session
			$linedup->id_act =$line_orig->id_act;       // // Id of activite concernÃ©e
			$linedup->activite_dated =$line_orig->activite_dated;       // Activite date Debut
			$linedup->activite_lieu =$line_orig->activite_lieu;       // Activite date Debut
			$linedup->activite_label =$line_orig->activite_label;     // Activite label
			$linedup->activite_nbmax =$line_orig->activite_nbmax;  	// Nb participant max Activite
			$linedup->activite_nbinscrit =$line_orig->activite_nbinscrit;  	// Nb Inscrit Activite
			$linedup->activite_nbpreinscrit =$line_orig->activite_nbpreinscrit;  	// Nb Preinscrit Activite
			$linedup->activite_nbencrins =$line_orig->activite_nbencrins;  	// Nb en cours d'Inscription
			$linedup->activite_heured =$line_orig->activite_heured;
			$linedup->activite_heuref =$line_orig->activite_heuref;
			$linedup->activite_rdv =$line_orig->activite_rdv;
			$linedup->act_moniteur_nom =$line_orig->act_moniteur_nom;
			$linedup->act_moniteur_prenom =$line_orig->act_moniteur_prenom;
			$linedup->act_moniteur_tel =$line_orig->act_moniteur_tel;
			$linedup->act_moniteur_email =$line_orig->act_moniteur_email;
			$linedup->id_part = '';
			
			// Depuis llx_contact
			$linedup->id_part =$line_orig->id_part;		// Id of participant concernée
			$linedup->PartNom =$bull->tiersNom;
			$linedup->PartPrenom =$line_orig->PartPrenom;
			$linedup->PartDateNaissance =$line_orig->PartDateNaissance ;
			$linedup->PartAge =$line_orig->PartAge;
			$linedup->PartENF =$line_orig->PartENF ;
			$linedup->PartMail =$line_orig->PartMail;
			$linedup->PartTel =$line_orig->PartTel;
			$linedup->PartCP =$line_orig->PartCP;
			$linedup->PartVille =$line_orig->PartVille;		
			$linedup->PartAdresse =$line_orig->PartAdresse;
			$linedup->PartCiv =$line_orig->PartCiv;
			$linedup->PartTaille =$line_orig->PartTaille;
			$linedup->PartPoids =$line_orig->PartPoids;
			$linedup->PartDateInfo =$line_orig->PartDateInfo; 
			$linedup->pu = '';      	
			$linedup->qte = 1 ;				// Quantity (example 2)
			$linedup->pu_enf = '';      	// prix enfant du départ
			$linedup->pu_grp = '';      	// prix groupe du départ
			$linedup->pu_adlt = '';     	// prix adlt du départ
			$linedup->remise_percent = '';	// % de la remise ligne (example 20%)
			
		}
		$linedup->observation =$line_orig->observation;
		$linedup->type_TVA =$line_orig->type_TVA; // $langs->trans("TVACommissionnement") ==> 0%
		
		// Diffusion dans la base
		$linedup->action ='A'; // A pour Ajout, M pour Modifier, S pour Supprimer, X pour Ne plus sÃ©lectionner
		$linedup->id_ag_stagiaire =$line_orig->id_ag_stagiaire;
		$linedup->id_ag_session =$line_orig->id_ag_session;
		$linedup->fk_produit =$line_orig->fk_produit;
		$linedup->duree =$line_orig->duree;
		$linedup->fk_code_ventilation = 0 ;
		$linedup->product_type = 1;	// Type  1 = Service	
		 $ret = $linedup->insertLocMat($user,0);
		unset ($linedup);
		return $ret;
	} //DupLocDet	
		
	function EnrLoDet()
	{
		global  $bull, $db, $user, $id_contratdet, $id_part, $id_contrat,   $langs;
		global $ACT_SEL_ACTPART;
		global $fk_service, $materiel,  $fk_fournisseur, $marque, $refmat,$identmat, $nomprenom, $taille, $dt_dateretrait,$st_dateretrait, $dt_datedepose,  $st_datedepose;
		global $duree, $observation, $lieudepose, $lieuretrait;
		
		global $PU, $Rem ,  $ActPartQte, $ActPartIdRdv, $ActPartObs, $TypeSessionCli_Agf;
		global $PartNom, $PartPrenom, $PartIdCivilite, $PartAdresse, $PartDateNaissance, $PartTaille, $PartPoids, $PartCiv, $PartENF, $PartTel, $PartMail, $PartAge, $PartDateInfo;
		global $action ;
		$error=0;
		if ((empty($fk_service) or $fk_service == 0) and $bull->statut >= $bull->BULL_VAL ) {
					$error++; setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Service")),'errors');
		}
		if (!empty($identmat) and strlen($identmat)<>3 and strlen($identmat)>0)  {
					$error++; setEventMessage($langs->trans("ErrorTailleRequired",$langs->transnoentitiesnoconv("RefMat"),3),'errors');
		}
		$line=new BulletinLigne ($db, $bull->type);

		/* champ non obligatoire - bug 245
		if ($bull->facturable  and ($bull->type_session_cgl == 2 and $bull->statut >= $bull->BULL_VAL)) { // type session Individuel, champs obligatoire : pr鮯m, 
			if (empty($PU) and $Rem <> 100) { 
				$error++; 
				$champ = (empty($PU))?"PU":"Remise";
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv($champ)),'errors');
			}
		}
		*/
		if ($st_dateretrait > $st_datedepose) { // date d�part post�rieure date retour
			 $error++; setEventMessage($langs->trans("ErrDateErronne"),'errors');
		}

		if ($error > 0) 		{
			$mesgs[]='<div class="error">'.$object->error.'</div>';	
			$action = $ACT_SEL_ACTPART;		
			return;
		}
		
		if ($id_contratdet) {
				$line = $bull->RechercheLign($id_contratdet);
		}
		if (isset($line->$type_enr) and  $line->$type_enr <> 0)	 return;
		else $line->type_enr  = 0;
		$line->fk_bull  = $bull->id;
		$line->fk_service  = $fk_service;
		$line->fk_fournisseur  = $fk_fournisseur;		
		$line->product_type  = 1;
		$line->qte  = $ActPartQte;
		if (empty($ActPartQte)) $line->qte  = 1;
		$line->materiel  = $materiel;
		$line->marque  = $marque;
		$line->pu  = price2num($PU,'');
		$line->remise_percent  = $Rem;
		$line->identmat  = $identmat;
		$line->NomPrenom  = $nomprenom;
		$line->observation  = $observation;
		$line->taille  = $taille;
		$line->PartTaille  = $PartTaille;
		$line->dateretrait  = $bull->locdateretrait;	
		$line->datedepose  = $bull->locdatedepose;	
		$line->duree  = price2num($duree, '');	
		$line->lieudepose = $bull->loclieudepose;
		$line->lieuretrait = $bull->loclieuretrait;

		if ($id_contratdet) {
				$line->updateLocMat($user,0);
		}
		else 	{	
			//autant de line identique qu'il y a de Qte, avec un Qte = 1
			if ( $bull->STATUT == $bull->BULL_ENCOURS  )  
			{
				$nbligne = $line->qte ;
				$line->qte  = 1;
				$ret = $line->insertLocMat($user,0);	
				if ($ret >0) 	{
					$id_contratdet = $ret;	
					$line->materiel  = '';
					$line->marque  = '';
					// en cas de groupe et pour un bulletin/contrat en cours , le PU est sur la première ligne 
					if ($bull->type_session_cgl <> 2)  $line->pu  = 0;	
					$line->identmat  = '';
					$line->NomPrenom  = '';
					$line->observation  = '';
					$line->taille  = '';	
					$line->PartTaille  = '';
					for ($i=1; $i<$nbligne; $i++)
						$line->insertLocMat($user,0);
				}
			}
		}	
		if (!empty($TypeSessionCli_Agf))  $bull->type_session_cgl = $TypeSessionCli_Agf + 1;
		
	} /* EnrLoDet */
	function SupLocDet()
	{
			global  $id_contratdet, $db, $langs, $bull, $confirm, $CONF_SUP_LOCDET;

		//print "<p>SUP Loc Materiel - Confirmation Suppression - id_bulldet:".$id_bulldet."</p>";
		
		$line = $bull->RechercheLign ($id_contratdet);
		$text='Location '.$line->service.' (Materiel : '.$line->materiel.')';
		if (!empty($line->NomPrenom) ) $text.=' pour '.$line->NomPrenom;
		
		$form = new Form($db);
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id_contrat='.$bull->id.'&id_contratdet='.$id_contratdet,$langs->trans('DeleteParticipation'),$text,$CONF_SUP_LOCDET,'','',1);
	
		print $formconfirm;

	} // SupLocDet
	
	
	function ConfLocDet()
	{
		global $confirm, $bull, $id_contratdet;
		if ($confirm = 'yes')
		{
			$line = $bull->RechercheLign ($id_contratdet);
			// en cas de bulletin dej�diffus�dans Dolibarr, on met juste S
			if ($bull->statut != $bull->BULL_ENCOURS) 
			{
				$line->updateaction('S');
			}
			else 
			{
				$line->delete();
			}			
		}
	} /*ConfSupActPart()*/
		/* Met le retour pour tous les enregistrements de bull_det de type location sauf tranfert
	*/

	
	function RetGenMat()
	{
		global $bull, $langs, $user;
		
		if (!empty($bull->lines)) {
			foreach ($bull->lines as $line)
			{	
				if ($line->type_enr == 0 and !empty($line->service) ) 
					if (stripos('TRANSFERT', $line->service ) === false) 
								$line->update_champs("qteret",$line->qte);
			} //foreach
		}
	} // RetGenMat
	
	
	
	function RetGenMatPart()
	{
		global $bull, $langs, $user;
		
		if (isset($_POST['matret']) && is_array($_POST['matret']))		{		
			$tab = array();
			$tab = GETPOST("matret",'alpha');
		}
		
		if (isset($tab) && is_array($tab))		{
			if (!empty($bull->lines)) {		
				foreach ($bull->lines as $line)		{
					if ($line->type_enr == 0 and stripos('TRANSFERT',$line->service ) === false) {
						if ($tab[$line->id] == false) $line->qteret = 0;
						else $line->qteret = $line->qte;
						$line->update_champs("qteret",$line->qteret);
					}
				}// foreach
			}
		}
	} // RetGenMat
	/*
	* Met à jour la table entière des matériel mis à disposition
	*/
	function EnrMatMad($faire)
	{				
		global $bull, $UPD_MATMAD, $UPD_MATMAD_RET, $LocMatObs;

		$error = 0;
		$flgqte=0;
		//$rapport = array();	
		if (!empty($LocMatObs)) {
			$bull->obs_matmad = $LocMatObs;
			$bull->update();
		}		
		if (isset($_POST['retmatmad']) && is_array($_POST['retmatmad'])) {	
			$tab_ret = array();
			$tab_ret = GETPOST("retmatmad",'alpha');
		}
		if (isset($_POST['matmad']) && is_array($_POST['matmad']))	{	
			$tab = array();
			$tab = GETPOST("matmad",'alpha');
		}
		
		if (isset($tab) && is_array($tab))		{
			if (!empty($bull->lines_mat_mad)) {		
				foreach($bull->lines_mat_mad as $lineMatMad)			{		
					$lineMatMad->qte = $tab[$lineMatMad->id]; 
					$lineMatMad->update_un();
				} // Foreach
			}
		}
		if (isset($tab_ret) && is_array($tab_ret))		{
			if (!empty($bull->lines_mat_mad)) {		
				foreach($bull->lines_mat_mad as $lineMatMad)			{		
					$lineMatMad->qteret = $tab_ret[$lineMatMad->id]; 
				$lineMatMad->update_un();
				} // Foreach
			}
		}
		
	}//EnrMatMad
	/* Met le retour pour tous les enregistrements de bull_det de type location sauf tranfert
	*/
	function RetGenMad()
	{
		global $bull, $langs, $user;
		
		if (!empty($bull->lines_mat_mad)) {		
			foreach ($bull->lines_mat_mad as $line)
			{
				if ($line->qte > 0) {
					$line->update_champs("qteret",$line->qte);
				}
			}// foreach
		}
	} // RetGenMad
		/* Met le retour pour tous les enregistrements de bull_det de type location sauf tranfert
	*/
	function RetGenCaut()
	{
		global $bull, $langs, $user, $retcaution, $retdoccaution;
		
		$bull->update_champs('ret_caution', 1, 'ret_doc', 1);
	} // RetGenCaut

	
	/*
	* Met à jour la table entière des matériel mis à disposition
	*/
	function EnrLocRet()
	{				
		global $bull, $user;

		$error = 0;
		$flgqte=0;
		//$rapport = array();	

		
		if (isset($_POST['matret']) && is_array($_POST['matret']))
		{
		
			$tab = array();
			$tab = GETPOST("matret",'alpha');
		}
		
		if (isset($tab) && is_array($tab))
		{
			if (!empty($bull->lines)) {		
				foreach($bull->lines as $lineLoc)
				{			
					$lineLoc->qteret = $tab[$lineLoc->id]; 
					$lineLoc->update_retour($user);
				} // foreach
			}
		}
	}//EnrLocRet
		/* Met le retour pour tous les enregistrements de bull_det de type location sauf tranfert
	*/

	function RetGenRand()
	{
		global $bull, $langs, $user;
		
		if (!empty($bull->lines_rando)) {	
			foreach ($bull->lines_rando as $line)
			{
				if ($line->qte > 0) {
					$line->update_champs("qteret",$line->qte);
				}
			} // foreach
		}
	} // RetGenRand
	/*
	* Met à jour la table entière des randos possibles
	*/
	function EnrLocRando($faire)
	{				
		global $bull, $UPD_RANDO, $LocRandoObs;

		$error = 0;
		$flgqte=0;
		//$rapport = array();	

		if (!empty($LocRandoObs)) {
			$bull->obs_rando = $LocRandoObs;
			$bull->update();
		}
		if (isset($_POST['retrando']) && is_array($_POST['retrando']))
		{		
			$tab_ret = array();
			$tab_ret = GETPOST("retrando",'alpha');
		}
		if (isset($_POST['rando']) && is_array($_POST['rando']))
		{
		
			$tab = array();
			$tab = GETPOST("rando",'alpha');
		}
		
		if (isset($tab) && is_array($tab))
		{
			if (!empty($bull->lines_rando)) {	
				foreach($bull->lines_rando as $linerando)
				{			
					$linerando->qte = $tab[$linerando->id]; 
					$linerando->update_un();
				} // foreach
			}
		}
		if (isset($tab) && is_array($tab))
		{
			if (!empty($bull->lines_rando)) {	
				foreach($bull->lines_rando as $linerando)
				{			
					$linerando->qteret = $tab_ret[$linerando->id]; 
					$linerando->update_un();
				} // foreach
			}
		}
	}//EnrLocRando
	function EnrLocCaution()
	{		
		global $bull, $caution, $retcaution, $retdoccaution, $modcaution, $ObsCaution, $topcautionrecue, $topdocrecu;
		if (!empty($retcaution) or $retcaution==0)  	$bull->update_caution('ret_caution', $retcaution); 
		
		if (!empty($retdoccaution) or $retdoccaution==0)  	$bull->update_caution('ret_doc', $retdoccaution); 	
		if (!empty($caution))    $bull->update_caution('fk_caution', $caution); 
		if (!empty($modcaution))    $bull->update_caution('fk_modcaution', $modcaution); 
		if (!empty($ObsCaution))    $bull->update_caution('ObsCaution', $ObsCaution); 
		if (!empty($topcautionrecue) or $topcautionrecue == 0)    $bull->update_caution('top_caution', $topcautionrecue); 
		if (!empty($topdocrecu) or $topdocrecu == 0)    $bull->update_caution('top_doc', $topdocrecu); 		
	} // EnrLocCaution

	function Calc_Acpte()
	{
		global $bull;
		
		$bull->update_champs('mttAcompte',$bull->CalculAcompte());
	} // Calc_Acpte

	/* 
	*  Permet de connaitre le nombre de matériel de cette référence loués dans la plage donnée
	* @param	int			$idservice		identifiant du service 
	* @param	strint (3)	$identmat		référence du matériel 
	* @param	date		$dateretrait	date de retrait du matériel
	* @param	date		$datedepose		date du dépose du matériel
	*
	* @retour	nombre de vélo trouvé  ou -1 si erreur accès à la base*
	*/
	function NbLocationParMateriel($idservice, $identmat, $IdbullDet, $dateretrait, $datedepose) 
	{
		global $bull;		
		$sql = "SELECT  bdConflit.fk_produit, bdConflit.refmat, bdConflit.rowid, count(bdConflit.rowid) as Nb, GROUP_CONCAT(distinct bConflit.ref SEPARATOR ' ') as Contrats ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "cglinscription_bull_det 		as bdConflit   ";
		$sql .= " 	LEFT JOIN " . MAIN_DB_PREFIX . "cglinscription_bull  	as bConflit	on bConflit.rowid = bdConflit.fk_bull   ";
		if (!empty($IdbullDet) and empty($dateretrait) and empty($datedepose))
			$sql .= " 	LEFT JOIN " . MAIN_DB_PREFIX . "cglinscription_bull_det as bdOrigine  	on bdOrigine.refmat = '".$identmat."' and bdOrigine.fk_produit = '".$idservice ."' ";
			$sql .= " WHERE bdConflit.type = 0  ";
			$sql .= " 	AND bdConflit.action not in ('X','S')  ";
			$sql .= " 	AND bdConflit.refmat = ".$identmat;
			$sql .= " 	AND bdConflit.fk_produit = ".$idservice;
		if (!empty($IdbullDet) )
			$sql .= " 	AND bdConflit.rowid <> ".$IdbullDet;
		else
			$sql .= " 	AND bdConflit.rowid <> bdOrigine.rowid  " ;
		if (empty($dateretrait) and empty($datedepose)){
			$sql .= " 	AND ( ";
			$sql .= " 			( date(bdOrigine.dateretrait) <= date(bdConflit.datedepose) AND date(bdConflit.dateretrait) <= date(bdOrigine.datedepose)) ";
			$sql .= " 		or ( date(bdConflit.dateretrait) <= date(bdOrigine.datedepose) AND date(bdOrigine.dateretrait) <= date(bdConflit.datedepose)) ";
			$sql .= " 		)";
		}
		elseif (!empty($dateretrait) and !empty($datedepose)){
			$sql .= " 	AND ( ";
			$sql .= " 			( date('".$dateretrait."') <= date(bdConflit.datedepose) AND date(bdConflit.dateretrait) <= date('".$datedepose."')) ";
			$sql .= " 		or ( date(bdConflit.dateretrait) <= date('".$datedepose."') AND date('".$dateretrait."') <= date(bdConflit.datedepose)) ";
			$sql .= " 		)";
		}
		$sql .= " GROUP BY bdConflit.fk_produit, bdConflit.refmat, bdConflit.rowid";

		dol_syslog(get_class($this)."::NbLocationParMateriel ");
        $resql=$this->db->query($sql);
			
        if ($resql)  { 						
	         $obj = $this->db->fetch_object($resql);
			 $Nb = $obj->Nb;
			 $Contrats = $obj->Contrats;
		}
		else {
			$Nb = -1;
			$Contrats = '';
		}
		return $Contrats ;
       
	} // NbLocationParMateriel
	/*
	* Ce vélo est-il déjà loué?
	*
	* @param 	int	$fk_service	Type de vélo
	* @param 	int	$identmat	Identifiant de vélo	
	* @param 	int	$idBull		Identifiant du LO courant
	* @param 	date	$dateretrait	Date début contrat
	* @param 	date	$datedepose		Date fin de contrat
	*
	*	@retour 	booelan true - Il est en conflit
	*						false - il n'est pas en conflit
	*/
	function IsMatDejaLoue ($fk_service, $identmat, $idBulldet, $dateretrait , $datedepose, &$listCntConflit = '', $htmlmode = false) 
	{
		$listCntConflit = array();
		$fl_conflitIdentmat	= false;
		if ( !empty($identmat)) {				
			$listCntConflit = $this->NbLocationParMateriel($fk_service, $identmat, $idBulldet, $dateretrait, $datedepose);					
			if (!empty($listCntConflit)) {
				$fl_conflitIdentmat	= true;
			}
		}
		return $fl_conflitIdentmat;
	} //IsMatDejaLoue
	
	function Reserver()
	{	
		global $bull;		
		$objdata=  new cglInscDolibarr($this->db);
		$res =$objdata->TransfertDataDolibarr('Inscrit', 'Inscrire');
		if ($res != -9) {	
			$bull->updateStat ($bull->BULL_VAL,'');	 
			if ($bull->facturable)$objdata->creer_bon_commande($bull->fk_commande);
		}
		unset ($objdata);
	
	} /*Reserver*/
	
	function PreReserver()
	{
		global $bull;		
		$objdata=  new cglInscDolibarr($this->db);
		$res =$objdata->TransfertDataDolibarr('Pre_Inscrit', 'Pre_Inscrire');
		if ($res != -9) {	
			$bull->updateStat ($bull->BULL_PRE_INSCRIT,'');	 
			if ($bull->facturable) $objdata->creer_bon_commande($bull->fk_commande);
		}
		unset ($objdata);
	}
	function Depart()
	{	
		global $bull;			
		$bull->updateStat ($bull->BULL_DEPART,'');	 // D�part	
	
	} /*Depart*/
	function Retour()
	{	
		global $bull;			
		$bull->updateStat ($bull->BULL_RETOUR,'');	 // Retour	
	
	} /*Retour*/
	function Clore()
	{	
		global $confirm, $action, $langs;
		global $bull,  $CNTLOC_CLOS;
		
		$bull->updateStat ($bull->BULL_CLOS,'');	 // Confirmation Cloture
		if ($bull->facturable == 0) 
			$bull->updateRegle ($bull->BULL_ARCHIVE,'');	 // Archivage automatique
		/*		
		$wtravail =  new CglCommunLocInsc($this->db);
		// test le paiement est-il identique au facturé et ActionFuture non vide et PmtFutur non vide et date Départ récent et TotalPaimnt est à 0
		if (empty($confirm) and $action == $CNTLOC_CLOS) {
			if ( $bull->TotalPaimnt() > 0 and !($bull->TotalPaimnt() > $bull->TotalFac()-0.005 and $bull->TotalPaimnt() < $bull->TotalFac() +0.005)){
				$bull->updateregle ($bull->BULL_ARCHIVE);	 // Archivé, car facturé dans Dolibarr
				$bull->updateStat ($bull->BULL_CLOS,'');	 // Confirmation Cloture
			}
				//setEventMessage($langs->trans("Un paiement existe et ne couvre pas la facture"),'errors');
			else {
				$Test = $wtravail->TestCloture($bull);
				$titre = $langs->trans('QstCntaFact');
				if ( !empty($bull->ActionFuture)) $text='<br>'.$langs->trans("TxtInfoActionFuture",$bull->ActionFuture);
				if ( !empty($bull->PmtFutur)) $text.='<br>'.$langs->trans("TxtProcRegl",$bull->PmtFutur);
				if (!empty($Test)  and  preg_match('/Totaux/', $Test)) $text.='<br>'.$langs->trans("FactImpossible");
				if (!empty($Test)  and  preg_match('/Depart/', $Test)) $text.='<br>'.$langs->trans("Activite a venir");
				if ( $bull->CalculRegle() <> $bull->BULL_PAYE)		$text .= '<br>'.$langs->trans ('FactImpossible');	
				if (!empty($Test)) {
					$texte = $langs->trans ('Qst').$text;
					$form = new Form($db);
					$url = $_SERVER['PHP_SELF'].'?id_contrat='.$bull->id.'&action='.$CNTLOC_CLOS;
					//$prochaineaction = $action;
					$formconfirm=$form->formconfirm($url,$titre,$texte,$prochaineaction,'','no',2);
					print $formconfirm;
				}
				else $bull->updateStat ($bull->BULL_CLOS,'');	 // Confirmation Cloture	
			}
		 }
		elseif ($confirm == 'yes'  and $action == $CNTLOC_CLOS) {
			$bull->updateregle ($bull->BULL_ARCHIVE);	 // Archivé, car facturé dans Dolibarr
			$bull->updateStat ($bull->BULL_CLOS,'');	 // Confirmation Cloture	
		}
		unset ($wtravail);
		*/
	} // Clore



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
				if (!empty($TabRaisRem)) {	
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
		
	
	function creer_contratLoc()
	{
		global $langs;
//		$typeModele = 'bulletin_odt:c:/dolibarr/dolibarr_documents/doctemplates/bulletin/BULL_IND.odt';
		$typeModele = 'location_odt:'.DOL_DATA_ROOT.'/doctemplates/bulletin/CONTRAT_LOC.odt';
		//$typeModele = 'location_odt';

		cgl_cnt_create($this->db,  $typeModele, $langs, $file, $socid, $courrier='');
		return 1;
		} //creer_contratLoc

	function MajRdv()
	{
		global $modactrdv, $bull, $ActPartIdRdv;
		
		if (!empty($bull->lines)) {
			foreach ($bull->lines as $line)
			{
				if ($line->id_act == $modactrdv)
				{			
					$line->update_rdv($ActPartIdRdv);
				}
			} // foreach
		}
	} // MajRdv
	
	function MajRemise ()
	{
		global $servremgen, $bull, $RaisRemGen, $mttremisegen;
		if (empty($servremgen)) $servremgen = 0;
		
		if (!empty($bull->lines)) {
			foreach ($bull->lines as $line)
			{
				if ($line->type_enr == 0 and $line->id_act == $servremgen or $servremgen == 0) 						
					$line->MajLineRem($RaisRemGen, $mttremisegen);	
			} // foreach		
		}
	} //MajRemise
	
	
	function updateFacModel($id_facture, $modelpdf)
	{
			// Update request
			$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET";
			$sql.= " model_pdf='".$modelpdf."'" ;
			$sql.= " WHERE rowid='".$id_facture."'";		
			$this->db->begin();
			dol_syslog("Location Facturation ::update Facture Modele sql=".$sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				return -1;
			}
			else
			{
				$this->db->commit();
				return 1;
			}
	} //updateFacModel
	
    /**
     * Output val field for an editable field
     *
     * @param	string	$htmlname		Name of select field
     * @param	string	$value			Value to show/edit
     * @param	object	$object			Object
     * @param	boolean	$perm			Permission to allow button to edit parameter
     * @param	string	$typeofdata		Type of data ('string' by default, 'amount', 'email', 'numeric:99', 'text' or 'textarea:rows:cols', 'day' or 'datepicker', 'ckeditor:dolibarr_zzz:width:height:savemethod:toolbarstartexpanded:rows:cols', 'select:xxx'...)
     * @param	string	$editvalue		When in edit mode, use this value as $value instead of value (for example, you can provide here a formated price instead of value). Use '' to use same than $value
     * @return  string					HTML edit field
     */
    function editfieldval($action, $htmlname, $object, $perm, $typeofdata='string', $editvalue='',  $moreparam='')
    {
        global $conf,$langs,$db ;

        $ret='';
        // Check parameters
        if (empty($typeofdata)) return 'ErrorBadParameter';

        // When option to edit inline is activated
        if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && ! preg_match('/^select;|datehourpicker/',$typeofdata)) // TODO add jquery timepicker
        {
            $ret.=$this->editInPlace($object, '', $htmlname, $perm, $typeofdata, $editvalue, null, nulls);
        }
        else
        {
            if ($action == 'edit'.$htmlname)
            {
                $ret.="\n";
                $ret.='<form method="POST" action="'.$_SERVER["PHP_SELF"].'?">';
                $ret.='<input type="hidden" name="action" value="set'.$htmlname.'">';
                $ret.='<input type="hidden" name="token" value="'.newtoken().'">';
                $ret.='<input type="hidden" name="id" value="'.$object->id.'">';
                $ret.='<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
                $ret.='<tr><td>';
                if (preg_match('/^text/',$typeofdata) || preg_match('/^note/',$typeofdata))
                {
                    $tmp=explode(':',$typeofdata);
                    $ret.='<textarea id="'.$htmlname.'" name="'.$htmlname.'" wrap="soft" rows="'.($tmp[1]?$tmp[1]:'20').'" cols="'.($tmp[2]?$tmp[2]:'100').'" '.$event_filtre_car_saisie.' >'.($editvalue?$editvalue:$value).'</textarea>';
                }
                else if (preg_match('/^ckeditor/',$typeofdata))
                {
                    $tmp=explode(':',$typeofdata);
                    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                     $doleditor=new DolEditor($htmlname, $editvalue, ($tmp[2]?$tmp[2]:''), ($tmp[3]?$tmp[3]:'100'), ($tmp[1]?$tmp[1]:'dolibarr_notes'), 'In', ($tmp[5]?$tmp[5]:0), true, true, ($tmp[6]?$tmp[6]:'20'), ($tmp[7]?$tmp[7]:'100'));
                    $ret.=$doleditor->Create(1);

 
              }
                $ret.='</td>';
                $ret.='</tr></table>'."\n";
                $ret.='</form>'."\n";
            }
            else
			{
				if (preg_match('/^text/',$typeofdata) || preg_match('/^note/',$typeofdata))  $ret.=dol_htmlentitiesbr($editvalue);
                else if (preg_match('/^ckeditor/',$typeofdata))
                {
                    $tmpcontent=dol_htmlentitiesbr($editvalue);
                    if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
                    {
                        $firstline=preg_replace('/<br>.*/','',$tmpcontent);
                        $firstline=preg_replace('/[\n\r].*/','',$firstline);
                        $tmpcontent=$firstline.((strlen($firstline) != strlen($tmpcontent))?'...':'');
                    }
                    $ret.=$tmpcontent;
                }
                else $ret.=$editvalue;
            }
        }
        return $ret;
    }// editfieldval
} // fin de classe CglLocation
?>