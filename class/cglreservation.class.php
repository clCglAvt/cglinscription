<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
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
 *   	\file       custum/cglinscription/class/cglreservation.class.php
 *		\ingroup    cglinscription
 *		\brief      Objet permettant le rapatriement des données de Dolibarr vers Réservation
 */

require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once "./core/modules/cglinscription/modules_cglinscription.php";
require_once "../cglavt/class/cglFctCommune.class.php";

	
/**
 *	Put here description of your class
 */
class CglReservation
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
		global $langs, $bull, $idmenu;	

		// Load traductions files requiredby by page
		//$langs->load("companies");
		//$langs->load("other");
		// Variables globales
		global $ENR_RESAINFO, $RESA_NVLIGNE, $RESA_ENR, $CONF_SUP_ACTPART, $ACT_SUP_ACTPART, $ENR_LOCINFO, $CRE_TIERS_BULL, $CRE_BULL ;
		global $ENR_TIERS, $VIDE_TIERS, $SEL_TIERS, $CREE_BULL, $CREE_TIERS_BULL_DOSS, $CREE_BULL_DOSS ;
		global $CNT_RESERVER, $RESA_REOUVRIR, $RESA_CLOS,  $BUL_ANNULER, $MAJ_TIERS, $EDT_BULL, $BUL_ABANDON  , $BUL_CONFANNULER, $BUL_CONFABANDON, $BUL_CONFABANDON;
		global $PREP_MAIL,$PREP_SMS, $SEND_SMS,  $SEND_SMS, $SEND_MAIL	;
		
		global $ResaActivite, $activite_dated,  $place,  $Refdossier, $nvdossier, $rdnvdoss, $priorite, $prioritedossier;
		global $tiersNom, $TiersVille, $TiersIdPays, $TiersTel,$infos_s_tel2, $TiersMail, $TiersMail2, $AuthMail, $TiersAdresse, $TiersCP, $TiersOrig, $Villegiature;

		global $id_resa, $id_resadet, $type, $action, $id_client, $bull, $tbNomPrenom, $tbobservation, $tbprix, $tbqte, $activite_dateheured;
		global  $NomPrenom, $observation, $prix;
			
		$wfcom =  new CglFonctionCommune ($this->db);
		$type = 'Resa';
		// Get parameters
		$action	= GETPOST('action','alpha');
		
		// Constantes
		// action de l'écran
		$VIDE_TIERS='VidTiers';
		$SEL_TIERS='SelTiers';
		$CREE_BULL_DOSS='CreeBullDos';
		$CREE_BULL='CreeBull';
		$CREE_TIERS_BULL_DOSS='CreeTiersBullDos';
		$MAJ_TIERS='MajTiers';	
		$ENR_TIERS='EnrTiers';
		$FILTRDEPART="FiltreDepart";
		$MOD_LOCINFO='ModLocInfo';
		$ENR_LOCINFO='EnrLocInfo';
		$BUL_CONFANNULER='Conf_Annuler';
		
		$ENR_RESAINFO='resainfo';
		$RESA_NVLIGNE='nvligne';
		$RESA_ENR='resaenr';
		$CNT_RESERVER='Reserver';
		$BUL_ANNULER='Annuler';	
		$RESA_REOUVRIR='Reouvrir'; 
		$RESA_CLOS='Clore';
		$BUL_ABANDON='Abandonner';		
		$BUL_CONFABANDON='Conf_Abandonner';
		$CONF_SUP_ACTPART='ConfSUPActPart';
		$ACT_ENR_ACTPART='EnrActPart';	
		$ACT_SUP_ACTPART='SUPActPart';
		$EDT_BULL = 'builddoc';
		
		$PREP_MAIL='presend';
		$PREP_SMS='presendsms';
		$SEND_MAIL='send';	
		$SEND_SMS='sendSMS';	

		
		$TiersTel	= GETPOST('TiersTel','alpha');
		$TiersMail	= GETPOST('TiersMail','alpha');
		$tiersNom	= GETPOST('tiersNom','alpha');
		$TiersVille	= GETPOST('TiersVille','alpha');
		$TiersAdresse = GETPOST('TiersAdresse','alpha');
		$Villegiature = GETPOST('Villegiature','alpha');
		$TiersCP 	= GETPOST('TiersCP','alpha');
		$TiersOrig = GETPOST('TiersOrig','int');
		$TiersIdPays	= GETPOST('TiersIdPays','int');	
		$infos_s_tel2 = GETPOST('infos_s_tel2','alpha');
		
		
	
		if ($action == $CREE_BULL) {// Arrivée par Suivi Dossier  - dossier connu
				$Refdossier = GETPOST('dossier','int'); 
		}
		//
		if ($action == $CREE_BULL_DOSS) { // Arrivée par 1ere page Inscription ou Suivi Tiers		- dossier ppouvant êtreconnu
			$tbrowid = array();
			$tbrowid = GETPOST("rowid", 'array');
			$nvdossier = GETPOST('nvdossier','alpha');
			$rdnvdoss = GETPOST('rdnvdoss','alpha');
			$priorite = GETPOST('priorite','int');  // variablble URL revenant de la demande création Dossier dans page Initiale Tiers/dossier de  BU/LO/RESA
			$Refdossier =  GETPOST('rdselectdoss','int'); 
		}
		if ($action == $CREE_TIERS_BULL_DOSS) { // Arrivée par 1ere page Inscription - Nouveau Tiers - dossier nouveau
			$nvdossier = GETPOST('nvdossier','alpha');
			$rdnvdoss = GETPOST('rdnvdoss','alpha');
			$priorite = GETPOST('priorite','int');  // variablble URL revenant de la demande création Dossier dans page Initiale Tiers/dossier de  BU/LO/RESA
		}
	

		$SEND_MAIL='send';
		$id_resa	= GETPOST('id_resa','int');
		$action	= GETPOST('action','alpha');	
		if (GETPOST('modelselected')) $action = $SLECTMODELMAIL;
		$id_resadet	= GETPOST('id_resadet','int');
		$ResaActivite	= GETPOST('ResaActivite','alpha');
		$ResaActivite	= $wfcom->cglencode($ResaActivite);
		$activite_dated	= GETPOST('activite_dated','date');
		if (strlen(substr($activite_dated,6)) == 2) $activite_dated = substr($activite_dated,0,6).(int)'20'.substr($activite_dated,6,2);

		$activite_datedhour = GETPOST('activite_datedhour','int');
		if (strlen($activite_datedhour) == 1) $activite_datedhour = '0'.$activite_datedhour;
		$activite_datedmin = GETPOST('activite_datedmin','int');
		if (strlen($activite_datedmin) == 1) $activite_datedmin = '0'.$activite_datedmin;
		$wc = new CglFonctionCommune($this->db);
		if (! empty($activite_dated)) $activite_dateheured = $wc->transfDateMysql($activite_dated).' '.$activite_datedhour.':'.$activite_datedmin;
		unset ($wc);
		$place	= GETPOST('place','int');
		$id_client	= GETPOST('id_client','int');
		$NomPrenom = GETPOST("NomPrenom", 'alpha');	
		$observation = GETPOST("observation", 'alpha');	
		$prix = GETPOST("prix", 'decimel');			
		$params = "&amp;search_client=".$id_client."&amp;id_resa=".$id_resa."&amp;ActionFuture=".$ActionFuture;
		$paramsTiers = "&amp;action=".$action."&amp;search_client=".$id_client."&amp;tiersNom=".$tiersNom."&amp;TiersVille=".$TiersVille."&amp;TiersIdPays=".$TiersIdPays;
		$paramsTiers .= "&amp;TiersTel=".$TiersTel."&amp;TiersTel2=".$TiersTel2."&amp;TiersMail=".$TiersMail."&amp;TiersAdresse=".$TiersAdresse."&amp;infos_s_tel2=".$infos_s_tel2;

		$bull=new Bulletin($db);
		if ($id_resa ) 
		{
			$bull->fetch_complet_filtre(-1, $id_resa);
		}	
		
		unset ($wfcom);
	} /*  init */	
	
	function SupActPart()
	{
		global  $id_resadet, $db, $langs, $bull, $confirm;
		
		$line = $bull->RechercheLign ($id_resadet);
		$text='Participation  de '.$line->NomPrenom.' a cette activite '.$line->activite_label;
		
		$form = new Form($db);
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id_resa='.$bull->id.'&id_resadet='.$id_resadet,$langs->trans('DeleteParticipation'),$text,'ConfSUPActPart','','',1);

		print $formconfirm;
	}/*SupActPart()*/

	// Supprime les lignes de reservations déja enregistrées et efface les lignes juste saisies, si le premier champ est vide
	function DeleteLigneResa() 			
	{
		global $langs, $bull, $tbNomPrenom, $tbobservation, $tbprix, $tbqte ;
		foreach( $tbNomPrenom as $NomPrenom => $valeur) {
			if ( empty($valeur)) {
				if  (substr($NomPrenom,0,1) == 'C' and (!(empty($tbNomPrenom[$NomPrenom]) and empty($tbobservation[$NomPrenom]) and empty($tbprix[$NomPrenom]) and empty($tbqte[$NomPrenom])))) {
					$tbNomPrenom[$NomPrenom] = '';
					$tbobservation[$NomPrenom] = "";
					$tbprix[$NomPrenom] = "";
					$tbqte[$NomPrenom] = "";
				}
				elseif (substr($NomPrenom,0,1) <> 'C')  	{
					$line = $bull->RechercheLign($NomPrenom);
					$line->delete(); 
				}
			}
		}
	} //DeleteLigneResa
	
	function ConfSupActPart()
	{
		global $confirm, $bull, $id_resadet;
		if ($confirm = 'yes')
		{
			$line = $bull->RechercheLign ($id_resadet);
			// en cas de bulletin dejࠤiffuc頤ans Dolibarr, on met juste S
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
	
	function EnrActPart()
	{
		
		global  $bull, $db, $user, $id_resadet, $id_part, $id_resa, $id_act, $langs;
		global $ACT_SAISIEPARTICIPATION;
		global  $NomPrenom, $observation, $prix;
		global $action ;

		$form =  new Form ($this->db);
		$error=0; 
		/* gestion des vérifications */
		if ($error > 0) {
			$action  = $ACT_SAISIEPARTICIPATION;
			return -1;
		}
				if (count($bull->lines)) $line =  $bull->lines[0];
				else 	$line = new BulletinLigne($this->db);			
				$line->fk_bull  = $bull->id;
				$line->NomPrenom = $NomPrenom;
				$line->prix  = $prix;			
				$line->observation  = $observation;
				
				$line->resa_activite =  $bull->ResaActivite;
				$line->resa_place =  $bull->place;
				$line->activite_heured =  $bull->heured;
				$line->activite_heuref =  $bull->heuref;
				if (empty($id_resadet)) 
					// nouvelle lignes]
					$ret = $line->insertReservation($user,0);
				else
					 $ret = $line->updateReservation($user,0);	
			return $ret;
	} /* EnrActPart */
		

	function creer_resa($idSession)
	{
		global $langs;
		
		$model = GETPOST('model', 'alpha');
		if (empty($model)) $model = 'ane';
		$typeModele = $model.'_odt:'.DOL_DATA_ROOT.'/doctemplates/bulletin/'.$model.'.odt';
		cgl_reservation_create($this->db,  $typeModele, $langs, $file, $socid, $courrier='');
	} //creer_resa
	

	function EnrInfoGene()
	{
		global $langs;
		global $bull, $ResaActivite,  $rplace, $ActionFuture, $place, $activite_dated, $activite_dateheured;
			
		// Tset  sur date mal saisies	
		$fldateinvalide = !checkdate (substr($activite_dated, 3,2), substr($activite_dated, 0,2), substr($activite_dated, 6));
		if ( $fldateinvalide)  { 
				$error++; setEventMessage($langs->trans("ErrorFieldFormat",$langs->transnoentitiesnoconv("DateDep")).':'.$activite_dated,'errors');
				$flreturn =true;
		}
		if ($flreturn)		return -1;
	
		$wf = new CglFonctionCommune ($this->db);
		//$mysql_activite_dated = $wf->transfDateMysql($activite_dateheured);
		$ret = $bull->update_champs('lieuretrait', $ResaActivite, 'fk_type_session', $place, 'dateretrait', $activite_dateheured);
		unset ($wf);
		if (count($bull->lines) ){
			// mettre à jour les lignes
			foreach ($bull->lines as $line)
			$line->update_champs('lieuretrait', $ResaActivite, 'qteret', $place, 'dateretrait', $mysql_activite_dated);
		}	
		$activite_dated = '';
	} // EnrInfoGene

	function EnrInfoPriv()
	{
		global $bull, $InfoPrive, $ActionFuture;
		$ret = $bull->update_champs('ObsPriv', $InfoPrive);
		$bull->update_champs('ActionFuture', $ActionFuture);
	} // EnrInfoPriv
			
	function Reserver()
	{	
		global $bull;
		$bull->updateStat ($bull->BULL_VAL,'');		
	} /*Reserver*/
	
	function Clore()
	{	
		global $confirm, $action, $langs;
		global $bull,  $RESA_CLOS;
	
		$bull->updateStat ($bull->BULL_CLOS,'');
	
	} // Clore
	
	function Reouvrir()
	{	
		global $confirm, $action, $langs;
		global $bull,  $RESA_REOUVRIR;
		
		$bull->updateStat ($bull->BULL_VAL,'');	 // Confirmation Reouverture

	} // Reouvrir


} // fin de classe

?>