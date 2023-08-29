<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
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
 * *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
 * Version CAV-  2.7.1 - automne 2022 - Passer les acomptes stripe à Impayé sur bulletin /contrat abandonné 
 *										- fiabilisation des foreach
 *					 					- correction de variable $line->enr inexistante, remplacer par this->type ou line->type_enr suivant les cas
 * Version CAV - 2.8 - hiver 2023 -
 *			- contrat/bulletin technique
 *			- remise à plat des status BU/LO
 *
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       custum/cglinscription/class/cgllocation.class.php
 *		\ingroup    cglinscription
 *		\brief      Traitement des données
 */

 /**************************/
 
// Change this following line to use the correct relative path from htdocs
//dol_include_once('core/module/class/html.forminscription.class.php');
//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once  DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once (DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglInscDolibarr.class.php');
	
/**
 *	Put here description of your class
 */
class CglCommunLocInsc 
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
	
	function CreInstanceBull()
	{
		global $id_client, $action, $id_contrat, $db, $id_bull, $id_resa, $bull, $type, $TiersTel;
		global $fl_BullFacturable, $options_s_tel2, $user, $id_contactTiers;
		global $INDICATIF_TEL_FR;
		
		$bull=new Bulletin($db);	
		$bull->id_client=$id_client;
		$bull->datec=time();
		$bull->fk_user=$user->id;	
		
		$bull->statut=0;
		$bull->action='A';
		$bull->type=$type;
		$bull->regle=0;
		$bull->entity=1;
		if ($fl_BullFacturable == 'yes') $bull->facturable = 1;
		elseif ($fl_BullFacturable == 'no') $bull->facturable = 0;
		$bull->TiersTel = $TiersTel;
		if ($bull->TiersTel ==$INDICATIF_TEL_FR) $bull->TiersTel = '';
		$bull->id_contactTiers = $id_contactTiers;
		$bull->TiersTel2 = $TiersTel2;
		if ($bull->TiersTel2 ==$INDICATIF_TEL_FR) $bull->TiersTel2 = '';
		if (empty($type)) $type = 'Insc';
		$bull->ref=$bull->RechercheNouvRefBull($type);
		$ret = $bull->create($user, 0);
		
		if ($ret > 0 ) 	{
			$bull->id = $ret;
			if ($type == 'Loc')  $id_contrat = $ret; elseif ($type == 'Insc') $id_bull = $ret;  elseif ($type == "Resa") $id_resa = $ret;
		}
		else return (-1);	
		
		if (!empty($conf->global->CGL_LOC_RANDO_MAT) and $type == 'Loc') {
			$lineMatMad = new BulletinLigneMatMad($db);
			$lineMatMad->fk_bull = $id_contrat;
			$lineMatMad->insertall();
			
			
			
			$lineRando = new BulletinLigneRando($db);
			$lineRando->fk_bull = $id_contrat;
			$lineRando->insertall();
			return $id_contrat;
		}
		elseif ($type == 'Loc') return $id_contrat;
		elseif ($type == 'Insc')  return ($id_bull);
		elseif ($type == 'Resa')  return ($id_resa);
	} //CreInstanceBull	


	/*
	* Requete SQL pour trouver  les accomptes, facture, bulletin/contrat et propal d'un tiers
	*
	* @param int $idtiersidentifiant du tiers
	* @param string $demande 	string valeurs 
	*									'BULO ( BU, LO, RESA d'un client)',
	*									'ACOMPTE (tous les acomptes dispo d'un client)',
	*									'ACOMPTE_DISPO (tous les acomptes dispo d'un client non liés au bulletin courant)',
	*									'FACTURE',
	*									'PROPAL'
	* retour	int retour de l'ordre sql lancé
	*/
	function SqlChercheRelationTiers ($idtiers, $demande)
	{
		global $conf;
		// quatre recherches
		// les contrats ou bulletins
		// les acomptes inutilisés
		// les factures 
		// les propals
		$bull = new Bulletin ($this->db);
		if ($demande == 'BULO') {
			$sql = "SELECT  b.rowid , b.statut, b.ref, b.datec , b.regle, b.typebull as type, b.abandon ";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."cglinscription_bull as b";
			$sql.= " WHERE b.fk_soc = s.rowid ";
			$sql.= " AND s.rowid = '".$idtiers."'";
			$sql.= " ORDER BY b.datec DESC";
		}
		elseif ($demande == 'ACOMPTE') 		{			
				$sql = "SELECT f.ref ,  ";
			$sql .= "f.rowid as fid, datef, total_ttc, fk_statut, b.ref, b.rowid as bid , b.typebull as type,  rem.rowid as RemExcep ";
			$sql.= " , CASE WHEN ";	
				$sql .= " f.ref   ";
			$sql.= "  like 'AC%' THEN 'Acompte' ELSE 'Avoir' END as nature ";	
			$sql .= ", case when isnull(rem.fk_facture) and (isnull(b.rowid) or b.statut = ".$bull->BULL_ABANDON.") then true else false end as  acomptelibre  ";
			$sql.= " FROM  ".MAIN_DB_PREFIX."societe as s  ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on  s.rowid = f.fk_soc  ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b on b.fk_facture = f.rowid or b.fk_acompte = f.rowid and isnull(b.abandon) ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_remise_except as rem on f.rowid = rem.fk_facture_source  ";
			$sql.= " WHERE ((ISNULL(rem.fk_facture) 	AND ISNULL(fk_facture_line)) or isnull(rem.rowid))  ";
			$sql.= " AND total_ttc <> 0 ";
			if ($conf->global->MAIN_VERSION_LAST_UPGRADE <>'8.0.4')		
			$sql.= " AND ( f.ref like 'A_%')  ";
			else		
				$sql.= " AND ( f.facnumber like 'A_%')  ";
			$sql.= " AND s.rowid = '".$idtiers."'";
			$sql.= " ORDER BY f.date_valid DESC";
		}
		elseif ($demande == 'ACOMPTE_DISPO') 		{			
		/*	$sql = "SELECT f.ref as facnumber,  ";
			$sql .= "f.rowid as fid, datef, total_ttc, fk_statut, b.ref, b.rowid as bid , b.typebull as type,  rem.rowid as RemExcep ";
			$sql.= " , CASE WHEN ";	
				$sql .= " f.ref   ";
			$sql.= "  like 'AC%' THEN 'Acompte' ELSE 'Avoir' END as nature ";	
			$sql .= ", case when isnull(rem.fk_facture) and (isnull(b.rowid) or b.statut = ".$bull->BULL_ABANDON.") then true else false end as  acomptelibre  ";
			$sql.= " FROM  ".MAIN_DB_PREFIX."societe as s  ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on  s.rowid = f.fk_soc  ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b on b.fk_facture = f.rowid or b.fk_acompte = f.rowid and isnull(b.abandon) ";
			$sql.= " , ".MAIN_DB_PREFIX."societe_remise_except as rem   ";
			$sql.= " WHERE ((ISNULL(rem.fk_facture) 	AND ISNULL(fk_facture_line)) or isnull(rem.rowid))  ";
			$sql.= " AND f.rowid = rem.fk_facture_source ";
			$sql.= " AND total_ttc <> 0 ";	
			$sql.= " AND ( f.ref like 'A_%')  ";
			$sql.= " AND s.rowid = '".$idtiers."'";
			$sql.= " ORDER BY f.date_valid DESC";
		*/
		global $bull;
		$sql = "SELECT f.ref as ref,  ";
			$sql .= "f.rowid as fid,  datef, total_ttc, fk_statut ";
			$sql.= " , CASE WHEN f.ref like 'AC%' THEN 'Acompte' ELSE 'Avoir' END as nature ";	
			$sql .= ", case when isnull(rem.fk_facture)  then true else false end as  acomptelibre  ";
			$sql.= " FROM  ".MAIN_DB_PREFIX."societe as s  ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on  s.rowid = f.fk_soc  ";
			$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."societe_remise_except as rem     ON f.rowid = rem.fk_facture_source ";
			
			$sql.= " WHERE  s.rowid = '".$idtiers."'";
			$sql.= " AND (total_ttc <> 0  or f.paye=1)";	
			$sql.= " AND ( f.ref like 'A_%')  ";	
			$sql.= " AND (((ISNULL(rem.fk_facture) 	AND ISNULL(fk_facture_line)) or isnull(rem.rowid)) or ISNULL(rem.rowid)) ";
			$sql.= " AND NOT EXISTS (SELECT 1 FROM ";
			$sql.= " 	 ".MAIN_DB_PREFIX."cglinscription_bull as b ";
			$sql.= " 	, ".MAIN_DB_PREFIX."cglinscription_bull_det as bd   ";
			$sql.= " 	WHERE (b.fk_acompte = f.rowid or (bd.type = 5 and 	bd.fk_facture  = f.rowid))  ";
			$sql.= " 		AND bd.fk_bull = b.rowid ";	
			$sql.= " 		AND isnull(b.abandon) ";
			$sql.= " 		AND  b.rowid ='".$bull->id."')";
			$sql.= " ORDER BY f.date_valid DESC";
		}
		elseif ($demande == 'FACTURE') {
			$sql = "SELECT f.ref ,  ";
			$sql .= " f.rowid as fid, datef, total_ttc, fk_statut, b.ref, b.rowid as bid , b.typebull as type,  rem.rowid as RemExcep ";
				$sql.= " , CASE WHEN f.ref like 'AC%' THEN 'Acompte' ELSE 'Facture' END as nature ";
			$sql.= " , false as acomptelibre,  f.date_valid  as tri ";			
			$sql.= " FROM  ".MAIN_DB_PREFIX."societe as s  ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on  s.rowid = f.fk_soc  ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b on (b.fk_facture = f.rowid or b.fk_acompte = f.rowid ) ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_remise_except as rem on f.rowid = rem.fk_facture_source  ";
			$sql.= " WHERE   NOT ISNULL( b.rowid) AND f.total_ttc <> 00 ";
			$sql.= " AND ((f.ref like 'AC%' and ISNULL(b.fk_facture)) OR  f.ref like 'FA%' )  ";
			$sql.= " AND s.rowid = '".$idtiers."'";
			$sql .= " UNION ";
			$sql .= "SELECT f.ref as facnumber,  ";
			$sql .= " f.rowid as fid, datef, total_ttc, fk_statut, '', '' , '',  rem.rowid as RemExcep ";
			$sql.= " , CASE WHEN f.ref like 'AC%' THEN 'Acompte' ELSE 'Facture ou avoir' END as nature, false ,  f.date_valid ";
			$sql.= " FROM  ".MAIN_DB_PREFIX."societe as s  ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on  s.rowid = f.fk_soc  ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b on (b.fk_facture = f.rowid or b.fk_acompte = f.rowid ) ";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_remise_except as rem on f.rowid = rem.fk_facture_source  ";
			$sql.= " WHERE   ISNULL( b.rowid) AND  f.total_ttc <> 0";
			$sql.= " AND ((f.ref like 'AC%' and ISNULL(b.fk_facture)) OR  f.ref like 'FA%' )  ";	
			$sql.= " AND ( f.ref like 'FA%' or f.ref like '_PROV%' ) ";			
			$sql.= " AND s.rowid = '".$idtiers."'";
			$sql.= " ORDER BY tri DESC";
		}
		elseif ($demande == 'PROPAL') {
			$sql = "SELECT ref, datep, total_ttc, fk_statut ";
			$sql.= " FROM ".MAIN_DB_PREFIX."propal as p ";
			$sql.= " WHERE  p.fk_soc = '".$idtiers."'";
			$sql.= " AND  total_ttc <> 0 AND  fk_statut <3 AND  fk_statut >0 ";
			$sql.= " ORDER BY datep ASC ";	
		}	
		elseif ($demande == 'SUIVI') {
			$sql = "SELECT libelle, spri.label,  spri.color, d.rowid  ";
			$sql.= " FROM ".MAIN_DB_PREFIX."cglavt_dossier  as d  ";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "cglavt_c_priorite as spri on d.fk_priorite = spri.rowid";
			$sql.= " WHERE  d.fk_soc = '".$idtiers."'";
			$sql.= " ORDER BY fk_priorite DESC ";	
		}
		dol_syslog (get_class($this).'::SqlChercheRelationTiers ('.$demande.')='.$sql, LOG_DEBUG);
	
		$resql=$this->db->query($sql);
		return $resql;
		
	} //SqlChercheRelationTiers
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

	function SupPaiement($id, $id_det, $type, $texteid, $textedet)
	{
		global  $db, $langs, $bull, $confirm;
		$line = $bull->RechercheLign ($id_det);
		$text='Paiement de '.$line->tireur. ' du montant de '.$line->montant;
		
		$form = new Form($db);
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'.$texteid.'='.$bull->id.'&'.$textedet.'='.$id_det,$langs->trans('DeletePaiement'),$text,'ConfSUPActPaimt','','',1);
		unset ($form);
		print $formconfirm;
	} /* SupPaiement*/
	
	function ConfSupPaiement($id, $id_det, $type, $texteid, $textedet)
	{
		global  $bull, $db;
			
		if (!empty($id_det))  {
			$line = $bull->RechercheLign($id_det);
			if ($line->fk_paiement > 0)			{			
				// un paiement a déja été fait
				$line->updateaction( 'S') ;	
				$line->action = 'S';
				if ($bull->statut > $bull->BULL_ENCOURS )		{
					$objdata=  new cglInscDolibarr($this->db);
					$res =$objdata->Traite_paiement('') ;
					unset 	($objdata);				
				}
			}
			else $line->delete();
			return 0;
		}	
	} /*ConfSupPaiement()*/
	/*
	* Création ou mise à jour d'un paiement dans Inscription
	*
	*	@param int	$id		Identifiant du bulletin/contrat
	*	@param int	$id_det	Identifiant du paiement existant
	*
	*	@retour	-1 en cas d'erreur, 0 si OK
	*/
	function MajPaiement($id, $id_det)
	{
		global $db;
		global  $PaimtMode, $PaimtOrg, $PaimtNomTireur, $PaimtCheque, $PaimtMtt, $PaimtDate, $TypeSessionCli_Agf, $id_bulldet, $id_contratdet, $PaimtNeg;
		global $id_client, $action, $user, $bull, $db , $langs;	
				
		// saisie obligatoire
		$error=0;
		$flgreturn = false;
		if (empty($PaimtDate) or $PaimtDate == 0  )  { 
				$error++; setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date Paiement")),'errors');
				$flgreturn = true;
		}
		$fldateinvalide = !checkdate ((int)substr($PaimtDate, 3,2), (int)substr($PaimtDate, 0,2), (int)substr($PaimtDate, 6));
		if ( $fldateinvalide)  { 
				$error++; setEventMessage($langs->trans("ErrorFieldFormat",$langs->transnoentitiesnoconv("Date")).':'.$PaimtDate,'errors');
				$flgreturn = true;
		}
		if (empty($PaimtMtt) or $PaimtMtt == 0 )  { 
				$error++; setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Montant Paiement")),'errors');
				$flgreturn = true;
		}
		if (empty($PaimtMode) or $PaimtMtt == -1 )  { 
				$error++; setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Mode Paiement")),'errors');
				$flgreturn = true;
		}
		if ($flgreturn) return -1;

		if (!empty($id_det) and $id_det <> 0) $line = $bull->RechercheLign($id_det);
		else $line= new BulletinLigne($db, $bull->type);
		if (!empty($PaimtMode)) $line->id_mode_paiement = $PaimtMode; 
		if (!empty($PaimtOrg)) $line->organisme = $PaimtOrg; 
		if (!empty($PaimtNomTireur)) $line->tireur = $PaimtNomTireur; 
		if (!empty($PaimtCheque)) $line->num_cheque = $PaimtCheque; 
		if (!empty($PaimtNeg)) $line->pmt_neg = $PaimtNeg; 
		 $line->montant = price2num($PaimtMtt); 
		if (!empty($PaimtDate)) $line->date_paiement = $PaimtDate;

		$line->fk_bull = $id;
		$this->db->begin();
		if (!empty($TypeSessionCli_Agf))  $bull->type_session_cgl = $TypeSessionCli_Agf + 1;
		if (!empty($id_det) and $id_det <> 0) 	 {
			$ret = $line->updatePaiement($user, 0);
		}
		else		{
			$ret = $line->insertPaiement(0);	
			if ($ret > 0) { $id_bulldet = $ret; $id_contratdet = $ret;}
		}
		if ($ret >= 0) $this->db->commit();
/*		sera fait après l'insertion de l'écriture danss Couer de Dolibarr	
		// si bulletin non réservé, prévenir
		if (($bull->type == 'Insc' and $bull->statut < $bull->BULL_PRE_INS and $bull->TotalFac() <> $bull->TotalPaimnt()) 
			or ($bull->type == 'Loc' and  $bull->statut < $bull->BULL_PRE_INSCRIT and !$bull->IsLocPaimtReserv())  
			) { 
				setEventMessage($langs->trans("WarningPaiementReservation"),'warnings');
		}
*/	
		$PaimtDate = '';
		return 0;
	} /*MajPaiement*/

	/*
	* Positionne un paiement ultérieur --- Non utilisé
	*
	*/
	function EnrProcRegl()
	{
		global $bull, $PmtFutur;
		
		$ret = $bull->update_champs ('PmtFutur',$PmtFutur);
	} //EnrProcRegl

	/*
	*	fonction desarchivage afin de retrouver le flag regle cohérent avec etat du BU/Lo
	*
	*	@return	int		>=0 ok, <0 erreur		
	*/
	function Desarchiver()
	{	
		global $bull;		
				
		return $bull->updateregle ($bull->RecalculRegle());	 // Confirmation Desarchivage

	} // Desarchiver
	/*
	*	fonction de retour à l'état Pre-Réserver/Pre-inscrit du BU/LO
	*
	*	@return	int		>=0 ok, <0 erreur		
	*/
	
	function Dereservation()
	{	
		global $bull;
		
		$bull->updateStat ($bull->BULL_PRE_INS,'');	 

	} // Dereservation
	
		/*
	*	fonction de retour à l'état Réserver du BU et Depart Fait du /LO
	*
	*	@return	int		>=0 ok, <0 erreur		
	*/
	
	function Reouvrir()
	{	
		global $bull;
		
		if ($bull->type == 'Loc') $val = $bull->BULL_DEPART;
		elseif ($bull->type == 'Insc') $val = $bull->BULL_PRE_INS; 
		$bull->updateStat ($val, '');	
		$bull->updateregle ($bull->CalculRegle(), '');	  

	} // Reouvrir
	
	/*
	* supprimer le bulletin/contrat
	*
	* @retour $ret	0 si OK, -1 en cas d'erreur
	*/
	function Conf_Annuler()
	{
		global $bull;
		
		$ret = 0;
		if ( !empty($bull->lines)) {
			foreach($bull->lines as $line) 		{
				$ret = $line->delete();
				$error -= 1;
			} // foreach
		}
		if ( !empty($bull->lines_stripe)) {
			foreach($bull->lines_stripe as $line) 		{
				$ret = $line->delete();
				$error -= 1;
			} // foreach
		}
		$ret = $bull->delete();
		$error -= 1;
		return $ret;
		
	} /*Conf_Annuler*/

	/*
	* Confirme L'archivage du bulletin
	*
	* Règles
	
	Abandon : 
	Archive :
	Algorythme
		Si sans activité – Archive ou abandon
			Si pas de facture
				si paiement Stripe ou direct et archive
					Crée Facture avec produit AcptNonRemboursé avec paiement des acomptes
					si demande stripe sans paiement ⇒ Supprimer demande stripe
					sinon
						si demande stripe sans paiement ⇒ Supprimer demande stripe
				sinon
					si demande stripe sans paiement ⇒ Supprimer demande stripe
			SI facture (avec activité supprimée) et archive
				traitement manuel
				si demande stripe sans paiement ⇒ Supprimer demande stripe
			sinon
					si demande stripe sans paiement ⇒ Supprimer demande stripe
		Si  activité – Archive
			Si pas de facture
				si paiement Stripe ou direct
					Crée Facture avec produit AcptNonRemboursé avec paiement des acomptes
					si demande stripe sans paiement ⇒ Supprimer demande stripe
				sinon
					si demande stripe sans paiement ⇒ Supprimer demande stripe
			SI facture (avec activité supprimée)
				si pas de paiements
					abandon facture
					si demande stripe sans paiement ⇒ Supprimer demande stripe
				sinon
					traitement manuel					
					si demande stripe sans paiement ⇒ Supprimer demande stripe

	Algorythme
		SI  acompte Stripe non payé réellement 
			on supprime acompte et ligne de demande Stripe
		SI  (sans facture, avec paiement et Archive)
			Crée Facture avec produit AcptNonRemboursé avec paiement des acomptes
		SI  ( avec facture, sans paiement et Archive)
			abandon facture
		SI  (sans activité, avec  facture, avec paiement et Archive)
		ou 
		SI  (avec  activité, avec  facture, avec paiement et Archive et Paiement = facturé )
		ou
		SI (avec  activité, avec facture et paiement  et Paiement <> facturé ))
			traitement manuel
		Abandonné / Archivé BU/LO	
	*
	*/
	function Conf_Abandon($bulletin, $origine = '')
	{	
		global $bull;
		$bull->Statut_Abandon();
		
		$wf = New cglInscDolibarr($this->db);
		// abandonner l'acompte principal s'il existe, car il est forcément sans paiement		
		if ($bull->fk_acompte and $bull->ExistPmtNeg() == 0 ) $wf->AbandonneFacture($bull->fk_acompte, $bull->type);
		
		// abandonner aussi d'éventuels acompte crée lors d'une demande Stripe non aboutis
		// pour tous les demandes  Stripe non payées
		if ( !empty($bull->lines_stripe)) {
			foreach ($bull->lines_stripe as $lineStripe ){ 
				if (empty( $lineStripe->fk_paiement)  )
					$wf->AbandonneFacture($lineStripe->fk_acompte, 'Stripe');

			} // foreach
		}
		unset ($wf);


	} //Conf_Abandon
	
	// Prépare le BU à une facturation suite à une annulation par Client
	function Conf_AnnuleParClient ()
	{
		global $bull;
		$bull->Statut_AnnulClient();		
		
	} //Conf_AnnuleParClient
	/*
	* Abandonne un bu/LO
	* obsolete	*
	*/
	function Abandon1($bulletin)
	{		

		// Si CA = 0 et Paiement = 0 et Exist paiment négatif, alors positionner BUL_ANULCLIENT
			
		// abandon ou archive du bulletin			
		if ($origine == 'abandon') 
		{
			$bulletin->Abandon();	//participation/location : action = 'X'
			
		}
		if ($origine == 'Archive')
		{		 
			// Archiver le contrat
			if ($bulletin->type == 'Loc') 
			{
				$bulletin->Statut_Clos ();	 // Cloturé		
			}
			$bulletin->Archive();
		}
		
		
		// Facture sans paiement abandonnée ==NON
		/*
		if ($retour >= 0 and $nomclasse == 'Facture' and !empty($origine ) and $flFactAvecPmt == false)
		{
				// S'il y a une facture et qu'il n'y a pas de paiement ==> abandonnée la facture
				$objet->set_canceled($user, GETPOST("close_code",'alpha'), GETPOST ( 'close_note', 'alpha' ));
		}
		*/

		
		$this->db->commit();
		
		unset($wacpt);
		unset ($wf);
	} //Abandon
	
		/*
	* Prépare l'abandon direct d'un bulletin
	*
	*  @param	chaine		$origine			Si 'Archive*		
	*											Si vide
    *  @return	 néant
	*
	*/
	function Abandon( )
	{
		global $bull, $langs, $BUL_CONFABANDON;
		global $ecran, $type;
		//global $tbrowid;
		global  $close, $arrayreasons;
		$tbrowid = array();
		$tbrowid = GETPOST("rowid", 'array');
		/* tableau constitutifs des raison d'abandon */
		require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglGeneral.class.php';
		$ww = new General($db);
		$ww->init_close();
		unset ($ww);

		$form = new Form($db);
/*		if (empty($origine ) and $bull->TotalPaimnt() > 0 ) {
			$error++; setEventMessage($langs->trans("RefusAnul"),'errors');
		} 
		*/
		// confirmation d'abandon 
		$formquestion=array();
		if (empty($origine ) ) {
			$question =  $langs->trans('Abandon');
			$html_name = $BUL_CONFABANDON;
			if ($bull->type == 'Loc') {
				$text = $langs->trans('ConfirmAbnCnt').' '.$bull->ref;
				$lb_id = 'id_contrat';
			}
			elseif ($bull->type == 'Insc') {
				$text = $langs->trans('ConfirmAbnBull').' '.$bull->ref;
				$lb_id = 'id_bull';
			}
			elseif ($bull->type == 'Resa') {
				$text = $langs->trans('ConfirmAbnResa').' '.$bull->ref	;
				$lb_id = 'id_resa';
			}
			$url = $_SERVER['PHP_SELF'].'?'.$lb_id.'='.$bull->id.'&type='.$bull->type;
		}
		else if ($origine == 'Archive') 			{
			$question = $langs->trans('CancelBull');
			$url = $_SERVER['PHP_SELF'].'?ecran='.$ecran.'&type='.$type.'&action=conf_archibull';
			$html_name = 'conf_archibull';
		}
		$texthaut = $langs->trans("ConfirmCancelBullQuestion").' - ';		
		//$text = $langs->trans('ConfirmCancelBull')
			// Cree un tableau formulaire
		$formquestion = array(
			'text' => $texthaut,
			array('type' => 'radio','name' => 'close_code','label' => $langs->trans("Reason"),'values' => $arrayreasons),
			array('type' => 'text','name' => 'close_note','label' => $langs->trans("Comment"),'value' => '','size' => '100')
		);	
	
		if (!empty($tbrowid)) foreach ( $tbrowid as $row) { $url .= '&'; $url .= 'rowid['.$row.']='.$row; }
			
		$formconfirm = $form->formconfirm($url, $question, $text, $html_name , $formquestion, "yes");
			//$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'.$lb_id.'='.$bull->id.'&type='.$bull->type,$question,$text,$BUL_CONFABANDON,'','',1);

		print $formconfirm;		
	
	} //Abandon

	
	/*
	* Prépare l'archivage direct d'un bulletin
	*
	*  @param	chaine		$origine			Si 'Archive*		
	*											Si vide
    *  @return	 néant
	*
	*/
	function Archive( $origine = '')
	{
		global $bull, $langs, $BUL_CONFABANDON;
		global $ecran, $type;
		//global $tbrowid;
		global  $close, $arrayreasons;
		$tbrowid = array();
		$tbrowid = GETPOST("rowid", 'array');
		/* tableau constitutifs des raison d'abandon */
		require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglGeneral.class.php';
		$ww = new General($db);
		$ww->init_close();
		unset ($ww);


		$form = new Form($db);
		if (empty($origine ) and $bull->TotalPaimnt() > 0 ) {
			$error++; setEventMessage($langs->trans("RefusAnul"),'errors');
		} 
		// confirmation d'abandon 
		$formquestion=array();
		if (empty($origine ) ) {
			$question =  $langs->trans('Abandon');
			$html_name = $BUL_CONFABANDON;
			if ($bull->type == 'Loc') {
				$text = $langs->trans('ConfirmAbnCnt').' '.$bull->ref;
				$lb_id = 'id_contrat';
			}
			elseif ($bull->type == 'Insc') {
				$text = $langs->trans('ConfirmAbnBull').' '.$bull->ref;
				$lb_id = 'id_bull';
			}
			elseif ($bull->type == 'Resa') {
				$text = $langs->trans('ConfirmAbnResa').' '.$bull->ref	;
				$lb_id = 'id_resa';
			}
			$url = $_SERVER['PHP_SELF'].'?'.$lb_id.'='.$bull->id.'&type='.$bull->type;
		}
		else if ($origine == 'Archive') 			{
			$question = $langs->trans('CancelBull');
			$url = $_SERVER['PHP_SELF'].'?ecran='.$ecran.'&type='.$type.'&action=conf_archibull';
			$html_name = 'conf_archibull';
		}
		$texthaut = $langs->trans("ConfirmCancelBullQuestion").' - ';		
		//$text = $langs->trans('ConfirmCancelBull')
			// Cree un tableau formulaire
		$formquestion = array(
			'text' => $texthaut,
			array('type' => 'radio','name' => 'close_code','label' => $langs->trans("Reason"),'values' => $arrayreasons),
			array('type' => 'text','name' => 'close_note','label' => $langs->trans("Comment"),'value' => '','size' => '100')
		);	
	
		if (!empty($tbrowid)) foreach ( $tbrowid as $row) { $url .= '&'; $url .= 'rowid['.$row.']='.$row; }
			
		$formconfirm = $form->formconfirm($url, $question, $text, $html_name , $formquestion, "yes");
			//$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'.$lb_id.'='.$bull->id.'&type='.$bull->type,$question,$text,$BUL_CONFABANDON,'','',1);

		print $formconfirm;		
	
	} //Archive
	
	function CreerRemise ( $RaisRemGen, $mttremisegen, $textremisegen)
	{
		global $actremgen, $bull, $tabrowid, $langs;	

		$fl_type = $this->RechRemTypebyId ($RaisRemGen);
		if ($fl_type == 2) {
			// remise au pourcentage
			if (!empty($tabrowid) and !empty($bull->lines)) {
				foreach ($tabrowid as $ligneSelect) {
					foreach ($bull->lines as $line) {
						if ($line->id == $ligneSelect) {
							$ret = $line->MajLineRem($RaisRemGen, $mttremisegen, $textremisegen);
							if ($ret< 0) setEventMessages($langs->trans("ErrEnrRem", $line->PartNom . ' '. $line->PartPrenom), 'errors');
							break;
						}// if
					} // foreach line
				} // Foreach ligneSelect
			}
		}	
		elseif ($fl_type == 1) 
			// Remise fixe
			$ret = $bull->AjRemGen($mttremisegen, $RaisRemGen, $textremisegen);
		elseif ($fl_type == -2) setEventMessage ("Erreur SQL:".$sql, "error");
		elseif ($fl_type == -1) setEventMessage ($langs->trans("IncRem".$RaisRemGen), "error");	
	} //CreerRemise	
	
	function RechRemTypebyId($id)
	{
			$sql = "SELECT fl_type ";
			$sql.= " FROM ".MAIN_DB_PREFIX."cgl_c_raison_remise as crem  ";
			$sql.= " WHERE  crem.rowid = '".$id."'";
			
		dol_syslog (get_class($this).'::RechRemTypebyId', LOG_DEBUG);		
		$resql=$this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($result);
			if ($num) {
				$obj = $this->db->fetch_object($resql);	
				return $obj->fl_type;
			}
			else return -1;
		}
		else return -2;
		return $resql;

	} // RechRemTypebyId
	
		
	function SupRemFix()
	{
		global  $id_bulldet, $db, $langs, $bull;

		$line = $bull->RechercheLign ($id_bulldet);
		$text='Remise "'.$line->textnom.'" portant sur "'.$line->textremisegen.'"';
		
		$form = new Form($db);
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id_bull='.$bull->id.'&id_bulldet='.$id_bulldet,$langs->trans('DeleteRemise'),$text,'ConfSUPRemFix','','',1);

		print $formconfirm;
	}/*SupRemFixe()*/
		
	function ConfSupRemFix()
	{
		global $confirm, $bull, $id_bulldet;
		if ($confirm = 'yes')
		{
			$line = $bull->RechercheLign ($id_bulldet);
			// en cas de bulletin dejࠤiffuc頤ans Dolibarr, on met juste S
			if (!empty($line)) {
				if ($bull->statut != $bull->BULL_ENCOURS) 
				{
					$line->updateaction('S');
					$line->action = 'S';
				}
				else 
				{
					$line->delRemiseFixe();
				}
				$line->update_champs('action','X');
			}
else print '=============================NON TROUVE';	
		}
	} /*ConfSupRemFix()*/
	
	function MajRemiseFixe_old ()
	{
		global $actremgen, $bull, $RaisRemFix, $mttremisefix;

		$bull->MajRemFixe($mttremisefix, $RaisRemFix);
	} //MajRemiseFixe
	
	
	// tab au format tab[id] = id
	// retour tab[<id>]=<id>&tab[<id1>]=<id1>
	function TransfTabIdUrl($tab, $name)
	{
		$ret = '';
		if ( !empty($tab)) {
			foreach ($tab as $key => $value)
			{
				if (!empty($ret)) $ret .='&';
				$ret .= $name.'['.$key.']='.$value;				
			} // foreach
		}
		return ($ret);
	
	}//TransfTabIdUrl

	function SupActPartMulti()
	{
		global  $id_bulldet, $db, $langs, $bull, $confirm, $tabrowid;

//		print "<p>SUP ACTIVITE PARTICIPANT - Confirmation Suppression - id_bulldet:".$id_bulldet."</p>";
		
		$num = count($tabrowid);
		$arrayquestion = array();
		if (!empty($tabrowid)) $urlsuite = $this->TransfTabIdUrl($tabrowid, 'rowid');
		if ($bull->type == 'Insc') $id = 'id_bull';
		elseif ($bull->type == 'Loc') $id = 'id_contrat';
		elseif ($bull->type == 'Resa') $id = 'id_resa';
		$url = $_SERVER['PHP_SELF'].'?'.$id.'='.$bull->id.'&'.$urlsuite;
		if ($num > 1) {
				if ($bull->type == 'Insc') $text='Les '.$num.' participations sélectionnées';
				elseif ($bull->type == 'Loc') $text='Les '.$num.' contrats sélectionnés';
			}
		else if($num > 0) {
			if ($bull->type == 'Insc') $text='La participation sélectionnée';
			elseif ($bull->type == 'Loc') $text='Le contrat sélectionné';
		}
				
		if (! empty($bull->fk_facture)) $text .= '<br><p> Si oui, penser à relancer la facturation de ce bulletin';
		
		if ($bull->type == 'Insc') $titre=$langs->trans('DeleteParticipationMulti');
		elseif ($bull->type == 'Loc') $titre=$langs->trans('DeleteLocMulti');
		
		$arrayquestion[] = array('ancre'=>'#AncreLstDetail');
		$formCgl = new CglFonctionDolibarr ($db);
		$prochaineaction='ConfSUPActPartMulti';
		$formconfirm=$formCgl->formconfirm($url,$titre,$text,$prochaineaction,$arrayquestion,'yes',1,250,600, 1);
		unset($formCgl);
		print $formconfirm;	
	}/*SupActPartMulti()*/
	
	function ConfSupActPartMulti()
	{
	global $confirm, $bull,  $tabrowid;
		if ($confirm = 'yes')
		{
			// en cas de bulletin dejࠤiffuc頤ans Dolibarr, on met juste S
			if ( !empty($tabrowid)) {
				foreach ($tabrowid as $key => $value) {
					$line = $bull->RechercheLign ($key);
					if (!empty($line) and $bull->statut != $bull->BULL_ENCOURS) 	{
						$line->updateaction('S');
						$line->action='S';
					}
					elseif (!empty($line)) 			$line->delete();				
				} // foreach
			}
		}
		return 0;
	} /*ConfSupActPartMulti*/

	function NettoieText($string)
	{
			$string = str_replace ('"',"'",$string);
			$char = '\\';
			$out = array();
			for ($i=0; $i<strlen($string); $i++)
				if ($string[$i] <> '\\')  $out[] = $string[$i];
			elseif ($trouve) { $out[] =  $string[$i];   $out[] =  $string[$i]; $trouve == false;}
			else $trouve == true;
			return $out;
		
	} //NettoieText
	function updatefacventilation( $id,$fk_code_ventilation)
	{
		$error=0;
			
		if (empty($fk_code_ventilation)) $fk_code_ventilation= 0;
		
		$this->db->begin();
		
        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
 		$sql.=" fk_code_ventilation = '".$fk_code_ventilation ."'";
		$sql.=" where rowid = '".$id."'";
		 dol_syslog(get_class($this)."::updatefacventilation sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)		{
			$this->db->commit();
			return 1;
		}
		else		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::updatefacventilation Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}		
	} //updatefacventilation
			
	function RechVentilationbyService($id,  $bulltype)
	{	
		$sql='';
		if ($bulltype == 'Loc') { 
			$champ_ventilation = 'accountancy_code_sell';
			$table = MAIN_DB_PREFIX."product as t";
			$lien = '';
		}
		else {
			$champ_ventilation = 'ventilation_vente';
			$table = MAIN_DB_PREFIX."agefodd_session as t";
			$lien = " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_formateur as afs on afs.fk_session = t.rowid ";
			$lien .= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formateur as af on afs.fk_agefodd_formateur = af.rowid";
		}
		$where = " WHERE t.rowid = '".$id."'";
		
		$sql = "SELECT ".$champ_ventilation." as code_ventilation ";
		$sql .= " FROM ".$table.$lien.$where;
		
		dol_syslog(get_class($this).'::RechVentilationbyService sql='.$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) 
		{	
			$objp = $this->db->fetch_object($result);
			$code_ventilation = $objp->code_ventilation;
			$this->db->free($result);
			unset ($w);
			return $code_ventilation;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::RechVentilationbyService '.$this->error,LOG_ERR);
			unset ($w);
			return -1;
		}
		
	}// RechVentilationbyService
		
	function RechIdVentilationbyCode($code,  $bulltype)
	{			
		$sql = "SELECT cp.rowid as id from ".MAIN_DB_PREFIX."accounting_account as cp ";
		$sql .= "LEFT JOIN  ".MAIN_DB_PREFIX."accounting_system as cs ON cs.pcg_version = cp.fk_pcg_version and  cs.active = 1 ";
		$sql .= " WHERE account_number like '".$code."' ";
		dol_syslog(get_class($this).'::RechIdVentilationbyCode sql='.$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) 
		{	
			$objp = $this->db->fetch_object($result);
			$id_ventilation = $objp->id;
			$this->db->free($result);
			return $id_ventilation;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::RechIdVentilationbyCode '.$this->error,LOG_ERR);
			return -1;
		}
		
	}// RechIdVentilationbyCode
	
	// ajouter le code ventilation dans la ligne de l’acompte
	function MajVentilationAcompteFact($idacompte)
	{	
		global $conf;
		
		if ( $conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS <> 1) 			return 0;
		if (empty( $conf->global->CGL_VENTIL_ACOMPTE)) return -2;
		
		$this->db->begin();
		if ( $conf->global->CGL_VENTIL_ACOMPTE == -1) $textesql = $conf->global->CGL_VENTIL_ACOMPTE;			
		else {
			$textesql = "(select cp.rowid from  ".MAIN_DB_PREFIX."accounting_account as cp ";
			$textesql .= " LEFT JOIN   ".MAIN_DB_PREFIX."accounting_system as cs   ON cs.pcg_version = cp.fk_pcg_version and  cs.active = 1, ";
			$textesql .= MAIN_DB_PREFIX."cglinscription_bull_det as bd 
						where account_number =	'".$conf->global->CGL_VENTIL_ACOMPTE."' and
						".MAIN_DB_PREFIX."facturedet.rowid = bd.fk_linefct )";
		}
		
		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set fk_code_ventilation =".$textesql." 			
			where  exists (select 1 from ".MAIN_DB_PREFIX."societe_remise_except as rem 
								where  ".MAIN_DB_PREFIX."facturedet.fk_facture = rem.fk_facture_source and !isnull(rem.fk_facture))
			and fk_facture = ".$idacompte;
			dol_syslog(get_class($this).'::MajVentilationAcompteFact sql='.$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) 	{
			$this->db->commit();
			return 0;
		}
		else		{
			$this->db->rollback();
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::MajVentilationAcompteFact '.$this->error,LOG_ERR);
			return -1;
		}
	} //MajVentilationAcompteFact
		
		/*
	* Si type = 1 $id_bull valoriser - chercher dans la base
	* si type = 0 $bull déjà lu
	*/
	function TestCloture($bull, $type=0) 
	{			
		global $id_bull;
			/* si date départ ou date réservation de toutes lignes 0  dépassées, et ActionFuture et PmtFutur vide et Paiement complet et statut > BULL-En-CURS
			alors VRAI
			sinon 	 FALSE */
			if ($type == 1) {
					$bull = New Bulletin ($this->db);
					$ret = $bull->fetch_complet_filtre(-1, $id_bull);
			}	

	
		if ($bull->statut == $bull->BULL_ENCOURS) return 'En cours';

			$TestDateDep = '';
			// test des actions à réaliser
			if (!empty($bull->ActionFuture) ) 
					$TestCloture =  'SuiviAction';	
			if ( !empty($bull->PmtFutur)) 
					$TestCloture =  'SuiviPaiement';
			if ( !($bull->TotalPaimnt() > $bull->TotalFac()-0.005 and $bull->TotalPaimnt() < $bull->TotalFac() +0.005) ) 
					$TestCloture .= 'Totaux';
			// test d'un départ non dépassé
			if ( !empty($bull->lines)) {
				foreach ($bull->lines as $bullline) {
					if ($line->type_enr == 0 and $line->action <> 'S' and $line->action <> 'X') {
						if (($bull->type == 'Loc' and (empty($bullline->dateretrait) or $bullline->dateretrait > dol_print_date(time(), '%y-%m-%d')))
							or ( $bull->type == 'Insc' and ($bullline->activite_dated > dol_print_date(time(), '%y-%m-%d')) or (empty($bullline->activite_dated) )))		{
							$TestCloture .= 'Depart';
							break;
						}
					}
				} //foreach
			}
			if ($type == 1) unset ($bull);	
			return $TestCloture;	
		
	} //TestCloture
	/*
	* Si type = 1 $id_bull valoriser - chercher dans la base
	* si type = 0 $bull déjà lu
	*/
	function ClotureAuto($bull, $id_bull=0,$type=0) 
	{
		
		if ($type == 1) {
			$bullCl = New Bulletin ($this->db);
			$ret = $bullCl->fetch_complet_filtre(-1, $id_bull);
		}
		else $bullCl = $bull;
		if (empty($this->TestCloture($bullCl, 1))) {
			$bullCl->updateStat ($bullCl->BULL_CLOS,'');	 // Confirmation Cloture
			$bullCl->statut = $bullCl->BULL_CLOS;
		}
		
	} //ClotureAuto

	
	function rapatrie_pdf_odl($bull, $signalement = true)
	{
		global $conf, $langs;
		// pour un bulletin : recherche si PDF dans dolibarr_documents/_ pour pdf (paramétratage dans variabme CGL_DIR_TMP_PDF)
		//		si existe : transporter dans cglinscrition/bulletin/<num_bu> ou  cglinscrition/contrat/<num_lo> 
		// si demande par mail : alerte pour demande de création du pdf
		
		// construction répertoire dolibarr_documents/Cglinscription/bulletin/<ref>
		$rep_bull = DOL_DATA_ROOT.'\cglinscription\\';
		$rep_bull = str_replace('/','\\',$rep_bull);
		if ($bull->type == 'Loc') 		$rep_bull .= 'contratLoc\\';			
		elseif ($bull->type == 'Resa') 		$rep_bull .= 'reservation\\';	
		else if ($bull->type == 'Insc') 		$rep_bull .= 'bulletin\\';
		$rep_bull .= $bull->ref.'\\';
/*		if ($bull->type == 'Loc')  $fichodtcomplet = $rep_bull.$bull->ref.'_CONTRAT_LOC.odt';
		elseif ($bull->type == 'Insc') $fichodtcomplet = $rep_bull.$bull->ref.'_BULL_IND_'.$line->id_act;
		elseif ($bull->type == 'Resa') $fichodtcomplet = $rep_bull.$bull->ref.'_RESA_IND_'.$line->id_act;
		$fichodtcourt= basename($fichodtcomplet );
		$fichiersdef = array();	
		//$fichiersdef = glob($rep_bull.$bull->ref.'*.odt');
		$fichiersdef = glob($fichodtcomplet);		
		//if ($bull->type <> 'Loc' and $fichiersdef == false)  return; // ?? Pourquoi??
*/		
		
		// construction du nom du répertoire des fichiers pdf stocké par utilisateur
		$rep_tmp = DOL_DATA_ROOT.'\\_pdf\\';
		$rep_tmp = str_replace('/','\\',$rep_tmp);
		$rep_tmp .= '\\';	
		$fichierstemp = array();
		$fichierstemp = glob($rep_tmp.$bull->ref.'*.pdf');
		if (!is_array($fichierstemp) ) $flvide = true;
		else $flvide = false;
		if (!$flvide) {
			// pour chaque fichier
			// copier premier fichier dans répertoire ci-dessus
			// supprimer premier fichier
			if ( !empty($fichierstemp)) {
				foreach($fichierstemp as $fichier) {
					$varfich = $rep_bull.basename($fichier);
					$varfich1 = substr($fichier, 0, strlen ($fichier) - 4).'_1.pdf';
					// test si le fichier existe -  Si oui renommer le fichier temp
					if (file_exists($varfich)) { rename ($fichier, $varfich1); $fichier = $varfich1; }
					// copier
					copy($fichier, $rep_bull.basename($fichier));
					unlink ($fichier);	
				} // foreach
			}
		}
			return;
			
	} // rapatrie_pdf	

	// Rechercher le modèle de base du module
	function RechModelInit()
	{
		global $bull;
		
	// Cette fonctionnalité resulte de la liste dans emailElementlist de actions_cglinscription.php
	
		if ($bull->type == 'Loc') $module='cgllocation';
		elseif ($bull->type == 'Insc') $module='cglbulletin';
		elseif ($bull->type == 'Resa') $module='cglresa';

		
		$sql = "SELECT rowid as id from ".MAIN_DB_PREFIX."c_email_templates as c ";
		$sql .= " WHERE position = (select min(position) from  ".MAIN_DB_PREFIX."c_email_templates as c1  ";
		$sql .= " where c.entity = c1.entity and c.type_template = c1.type_template)  ";
		$sql .= " AND c.type_template =   '".$module."'";
		$sql .= " order by rowid desc";;
		dol_syslog(get_class($this).'::RechModelInit sql='.$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) 
		{	
			$objp = $this->db->fetch_object($result);
			$ModelInit = $objp->id;
			$this->db->free($result);
			return $ModelInit;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::RechModelInit '.$this->error,LOG_ERR);
			return -1;
		}
		
		
	}//RechModelInit

	function debug_cav()
	{
		$out = '';
/*		foreach($_POST as $key => $value)
			$out .= '=====POST['.$key.']='.$value;
		$out .= '<br>==================================';
		foreach($_GET as $key=>$value)
			$out .= '=====GET['.$key.']='.$value;
*/
		/*$out .= '<br>==================================';
		foreach($_SERVER as $key=>$value)
			$out .= '=====_SERVER['.$key.']='.$value;
		*/	
	}
	// Repérer le fichier pdf le plus récent et le renommer 
/*		
		cas 1 - pas de fichier pdf ==> ne rien faire
		cas 1 - un fichier pdf et aucun fichier *-D<n>.pdf  et aucun fichier *-<n>.pdf ==> copier dans *-D1.pdf
		cas 3 -  un fichier pdf et aucun fichier *-D<n>.pdf et un fichier *-<n>.pdf ==> renomer pdf en *-D1.pdf
		
		
		
		
		$fich_nomstocke ='';
		$fich_datestockee = null;
		$fichdef = array();
print 'fichdef = rep_bull.bull->ref.*.pdf:'.$rep_bull.$bull->ref.'*.pdf';
		$fichdef = glob ($rep_bull.$bull->ref.'*.pdf');		
		$datefichodt =filemtime($fichodtcomplet) ;
		$flfin = False;
print '<br>fichodtcomplet:'.$fichodtcomplet;
print '<br>fichodtcourt:'.$fichodtcourt;
		if (!empty($fichdef) )
				foreach($fichdef as $fichiercomplet) {
						$fichiercourt =  substr(basename($fichiercomplet),  0, strripos($fichiercomplet, '.pdf'));
print '<br>fichiercomplet:'.$fichiercomplet;;
						$datefichpdf =  filemtime($fichiercomplet );
print '<br>fichiercourt:'.$fichiercourt;
print '<br>datefichodt:'.$datefichodt;
print '<br>datefichpdf:'.$datefichpdf;
print '<br>COMPARAISON ';
print '<br>substr($fichodtcourt,0, strlen($fichodtcourt) - 4):'.substr($fichodtcourt, 0, strlen($fichodtcourt) - 4);
print '<br>substr(basename($fichiercomplet),  0, strlen($fichodtcourt) - 4):'.substr(basename($fichiercomplet),  0, strlen($fichodtcourt) - 4);							
				// si le nom du fichier correspond au nom de l'ODT et la date postérieure ==> break
						if ($fichodtcourt == $fichiercourt  and $datefichpdf > $datefichodt ) { $flfin = true; print '<br>SORTIE CAR TROUVE'; break;}
				// donc si nom fichier différent nom ODT 
						elseif (substr($fichodtcourt, 0,strlen($fichodtcourt) - 4)  == substr(basename($fichiercomplet),  0, strlen($fichodtcourt) - 4)) {
				//	si la date du fichier est postérieure  date stockée ou si date stockée vide, alors stocker date du fichier et stocker son nom	
print '<br>fich_datestockee:'.$fich_datestockee;
							if (empty($fich_datestockee) or $datefichpdf > $fich_datestockee) {
								 $fich_datestockee = $datefichpdf;
								 $fich_nomstocke = $fichiercomplet;	
print '<br>fich_datestockee:'.$fich_datestockee;
print 'fich_nomstocke:'.$fich_nomstocke;
							}
						}
		}
		
		// si le nom fichier stockeé non vide => renomer ce fichier en ne conservant que le nom de l'odt
		if (!$flfin and !empty($fich_nomstocke)) {
		$nvfich_nomstocke = substr($fichodtcourt,0, strripos($fichier, '.odt')).'.pdf';
			rename($fich_nomstocke, $nvfich_nomstocke);
print '<br>rename:'.$fich_nomstocke.' en '.$nvfich_nomstocke;
		}
		// Vérif pdf à jour
		$demndeconfirm = false;
		if (empty($fichiersdef) ) $demndeconfirm = true;
		else {
		// pour chaque fichier de bulltin/<num_ref>/*.odt		
			foreach ($fichiersdef as $fichier) {
				if (file_exists($fichier) ) {
					$mdateodt = filemtime ($fichier);
				}
				else {
					$mdateodt = false;
				}
				$fichierpdf = substr($fichier, 0, strripos($fichier, '.odt')).'.pdf';
				if (file_exists($fichierpdf)) {
						$mdatepdf = filemtime ($fichierpdf);
				}
				else {
					$mdatepdf = false;
				}
				// s'il existe vérifier date modification pdf >= date modification odt
					// si date modification pdf < date modification odt $demndeconfirm = true
				// s'il n'existe pas $demndeconfirm = true
				if ($mdateodt == false or $mdatepdf == false or $mdateodt > $mdatepdf ) {
					$demndeconfirm = true;
				}
			}	
		}*/
/*		if ($demndeconfirm and $signalement) {
			$formcgl = new CglFonctionDolibarr($this->db);
			if ($bull->type == 'Loc') $text = $langs->trans('LibPdfLoc');
			else  $text = $langs->trans('LibPdfInsc');
			$question = '';
			$formconfirm=$formcgl->formvalide('',$text,$question,'','',1,1);
			unset ($formcgl);
			print $formconfirm;
		}*/
			/*
						$text='Cr顴ion non enti貥ment aboutie n? error:'.$error;					
			$form = new Form($db);
			$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'],$text,'','','',1);			
			$formconfirm=$form->formconfirm($url,$text,$question,$prochaineaction,'','yes',1,170,500,$suiteurl);
			$text='Facturation demandée incorrectement aboutie';	
			$question="Voir le rapport de faturation et prevenir l'assistance";
			$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?type='.$type.'&ecran='.$ecran,$text,$question,'','',1,1);
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ClonePropal'), $langs->trans('ConfirmClonePropal', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
			$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id, $langs->trans('DeleteAccount'), $langs->trans('ConfirmDeleteAccount'), 'confirm_delete', '', 0, 1);
			unset ($form);
			print $formconfirm;
			
			
			if ($demndeconfirm) return false;
			else return true;
			*/	
	
} // fin de classe CglCommLocInsc
	/*	
		if ($bull->TotalPaimnt() > 0 ) {
					$error++; setEventMessage($langs->trans("Annulation impossible car des paiements ont �t� re�us",$langs->transnoentitiesnoconv("Service")),'errors');
					return;
		} 
		// confirmation d'abandon 	
		$form = new Form($db);
		$formquestion=array();
		$question =  $langs->trans('Abandon');
		if ($bull->type == 'Loc') {
			$text = $langs->trans('ConfirmAbnCnt').' '.$bull->ref;
			$lb_id = 'id_contrat';
		}
		else {
			$text = $langs->trans('ConfirmAbnBull').' '.$bull->ref	;
			$lb_id = 'id_bull';
		}
		
		
	*/	
?>