<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 * *
 * Version CAV - 2.7 été 2022 	- Dolibarr Migration V15
 *								- normalisation du code : reprise fonction existante  setContactSession dans create_agefodd_session_contact
 *								- normalisation du code : reprise fonction existante   add_object_linked dans AjoutElement
 *								- correction bug 173
 *								- Passer les acomptes stripe à Impayé sur bulletin /contrat 
 * Version CAV - 2.7.1 automne 2022
 *					 - correction de variable $line->enr inexistante, remplacer par this->type ou line->type_enr suivant les cas
 *					 - fiabilisation des foreach
 * Version CAV - 2.8 hiver 2023
 *		- clôture des acomptes remboursés d'un BU archivé sans facturation
 *		- Séparation refmat en IdentMat et marque 
 * Version CAV - 2.8.3 printemps 2023 - première étape POST_ACTIVITE
 *		- le modèle proposé pour la facture issu d'un LO est TVA ( bug 271) * Version CAV - 2.8.4 - printemps 2023
 *		- PostActivité

 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
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
 *  \file       cglInscriptionDolibarr.php
 *  \ingroup    cglinscription.php
 *  \brief      Diffuse les infos des buleltins/Contratns dans dolibarr Coeur
 *				Put here some comments
 */


// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


require_once(DOL_DOCUMENT_ROOT."/custom/agefodd/class/agefodd_session_stagiaire.class.php");
require_once(DOL_DOCUMENT_ROOT."/custom/agefodd/class/agefodd_stagiaire.class.php");
require_once(DOL_DOCUMENT_ROOT."/custom/agefodd/class/agsession.class.php");
require_once(DOL_DOCUMENT_ROOT."/custom/cglavt/class/cglFctDolibarrRevues.class.php");
require_once(DOL_DOCUMENT_ROOT."/custom/cglavt/class/cglFctCommune.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/commande/modules_commande.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");

/**
 *	Put here description of your class
 */
 class cglInscDolibarr extends CommonObject
{

	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='skeleton';			//!< Id that identify managed objects
	var $table_element='skeleton';		//!< Name of table without prefix where object is stored

    var $id;
    var $prop1;
    var $prop2;
	//...


    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

	// TRANSFERT DONNES DE CGLINSCRIPTION VERS DOLIBARR ET AGEFODD
	/*
	*	flag		'Inscrit ou Pre_inscrit , desincrire pour information pour le statut des participants aux sessions
	*	Isinscrire	Inscrire pour provoquer vérification de la complétude des inscriptions
	*/

	function TransfertDataDolibarr($flag, $IsInscrire)
	{	
		global $bull, $langs,  $user;
	
		// Reprend dans bull tous les enregistrements du fichier
//		$bull->fetch_complet_filtre(99,$bull->id);
		// test est-ce que toutes les participations sont complètes :IsParticipationcomplete($line->id) seulement lors de l'inscription définitive
		// avant tout traitement si bulletin d'inscription vérifier que les departs ne sont pas surcharges		
		dol_syslog ( get_class ( $this ) . "::TransfertDataDolibarr - entree" , LOG_DEBUG );	
		$error = 0;
		if ($bull->type == 'Insc') {	
				// Constitution de la table des sessions			
				$tabsession = array();
				if (!empty($bull->lines)) {
					foreach ($bull->lines as $linedata)					{
						if ($linedata->type_enr == 0  and $linedata->action != 'X'
						//					and $linedata->action != 'S'
						)	{	
								$tabsession[$linedata->id_act] = new stdClass();			
								$tabsession[$linedata->id_act]->id_act = $linedata->id_act;						
								$tabsession[$linedata->id_act]->activite_label = $linedata->activite_label;		
								$tabsession[$linedata->id_act]->activite_heured = $linedata->activite_heured;	
								$tabsession[$linedata->id_act]->activite_heuref = $linedata->activite_heuref;	
						}
					} // foreach
				}				
		}
		
		if ($error == 0) {	
			// Si DEPART Vérifier si départ est complet
			if ($bull->type== 'Insc' and $IsInscrire == 'Inscrire')		{
				if (!empty($bull->lines)) {
					foreach ($bull->lines as $line)		{
						if ( $line->type_enr == 0  and $line->action != 'X'  and $line->action != 'S') {
							$ret = $line->IsParticipationcomplete($line->id);
							if ($ret == -2) 
									{ $t=''; }//{$error++; setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Prix sur chaque participation")),'errors');
							elseif ($ret <> 1) {
									$error++; 
									setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ErrPartDep")),'errors');
							}
						}
					} // foreach
				}
				if ($error > 0) 			{
					$mesgs[]='<div class="error">'.$object->error.'</div>';	
					$action = '';		
					return -9;
				}
			}
			
			// DEPART - PARTICIPANT
			if ($bull->type == 'Insc') {
				dol_syslog ( get_class ( $this ) . "::TransfertDataDolibarr - stagiaire" , LOG_DEBUG );
				//$langs->trans("TraineeSessionStatusProspect") : $inscrit = 0;
				//$langs->trans("TraineeSessionStatusConfirm": $inscrit =2);
				if ($flag == 'Inscrit') $agf_stagiaire_status  = 2;
				elseif   ($flag == 'Pre_Inscrit') $agf_stagiaire_status  = 0;
				elseif ($flag == 'desincrire') $agf_stagiaire_status  = 6;

//print "PB on désincrit tout les participations du bulletin, or ici, on doit désinscrire uniquement une ou n participation S - qui me semble avoir été fait avant<br>Par contre pour Annuler départ, il faut désinscrire toutes participations du départ sur le bulletin - <br>Voir setContactSession	la routine permettant une désinscription rapide (agefodd)";

				if (!empty($bull->lines)) {
					foreach ($bull->lines as $linedata)	{

						if ($linedata->type_enr == 0  and $linedata->action != 'X' and $linedata->action != 'S' )	{
						// vérifie que agefood connaît le participant
							$ret = $this->Traite_agefodd_stagiaire($linedata);
							// inscrit ou pre-inscrit les stagiaires	
							$this->Traite_agefodd_session_stagiaire($linedata, 'maj', $agf_stagiaire_status);
							}
						elseif ($linedata->type_enr == 0  and ($linedata->action == 'X' or $linedata->action == 'S' ))	{
							$this->Traite_agefodd_session_stagiaire($linedata, 'delete', $agf_stagiaire_status);
						}
					} // foreach	
				}
								
				if (!empty($tabsession)) {
					foreach ( $tabsession as $session)		{
						$this->Traite_agefodd_session($session->id_act);			
						// mis à jour de l'évènement correspondant par routine précédente
						//$ret = $this->Traite_actioncomm($line->id_act,$session->activite_heured, $session->activite_heuref);
					} // foreach
				}
			}
			if ($bull->facturable) 
			{				
				// COMMANDE				
				// Si bulletin groupe alors réduire $bull puis le remettree OK
				$flggoupe = false;
			
				IF ($bull->IsBullGroupe() ==  true) {
					$flggoupe = true;	
					$bull->fetch_bull_group_fact();
				}
				$ret = $this->Traite_cmd_fact_complete('Commande', $bull);
				IF ($flggoupe) {				
					$bull->fetch($bull->id);
				}
				// paiement
				$ptt=$bull->TotalPaimnt();
				if (!empty($ptt) and $ptt >0)  $ret = $this->Traite_paiement();
				// Mettre à jour toutes les clés étrangères dans le bulletin
				if ($bull->action == 'X' or $bull->action == 'S') $val = 'X';
				else $val = '';
				$bull->AjoutFkDolibarr($val);
				$this->db->commit();
				// SESSION AGEFODD
				// recharge bull normalement
				//$bull->fetch_complet_filtre(-1,$bull->id);
			}
		}
		return 1;
	}  // TransfertDataDolibarr
	
	/*
		Fait le transfert dans Dolibar pour une participation (création, Mise à jour )
	*/
	function DolibIndParticipation($flag)
	{
		global $id_bulldet, $bull, $langs;
		$bull->fetch_complet_filtre(99,$bull->id);
		$line = $bull->RechercheLign($id_bulldet);
		if (empty($line)) {
			$bull->fetch_complet_filtre(-1, $bull->id);
			$line = $bull->RechercheLign($id_bulldet);
		}
		// TEST PARTICIPATION COMPLETE SI INSCRIPTION (participant et départ renseigné)
		$error = 0;
		if ( $bull->statut == $bull->BULL_INS and $flg != 'Sup')		{
			$ret = $line->IsParticipationcomplete($line->id);
			if (  $line->type_enr == 0   and  $line->action != 'X'  and $line->action != 'S' and $ret<>1) { 
						if ($ret == -2) 							
							{ $t=''; }//{$error++; setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Prix sur chaque participation")),'errors');
						else
							{$error++; setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ErrPartDep")),'errors');}
			}
			if ($error > 0) 
			{
				$mesgs[]='<div class="error">'.$object->error.'</div>';	
				$action = '';		
				return;
			}
		}	
		// INtegre le participant dans agefodd si nécessaire
		$this->Traite_agefodd_stagiaire($line);	
		// CALCUL PARTICIPANT et INSCRIPTION en SESSION
		if ($bull->statut  == $bull->BULL_INS) $agf_stagiaire_status  = 2;
		elseif   ($bull->statut  == $bull->BULL_PRE_INS ) $agf_stagiaire_status  = 0;	
		$this->Traite_agefodd_session_stagiaire($line, ($line->action == 'S')?'delete':'maj', $agf_stagiaire_status);
		$this->Traite_agefodd_session($line->id_act);							
		// mise à jour l'évènement correspondant faite dans les deux routines précédente
		//$ret = $this->Traite_actioncomm($line->id_act,$line->activite_heured, $line->activite_heuref);

		// COMMANDE	 ou FACTURE
		
		$ret = $this->Traite_ligne_cmd_fact( $line, 'Commande', 'OrderLine',  'fk_commande', 'fk_line_commande', 'fk_linecmd', '', $bull, 1);		
		// traitement de la ligne de remise fixe
		$this->Traite_RemFix('Commande', 'OrderLine',  'fk_commande', 'fk_line_commande', 'fk_linecmd', '');
		
		$line->AjoutFkDolibarr("");	
		// MISE A JOUR DIFFUSION RELAISEE dans BULLETIN
		$bull->updateaction ('');	
		return 1;		
		
	} // DolibIndParticipation

	// traitement de la ligne de remise fixe
	function Traite_RemFix(  $nomclasse,$nomclassedet, $fk_objet, $fk_objetdet, $fk_champdet , $objet)
	{

		global $bull;
		$line=new Bulletin ($this->db);
		$line = $bull->RechercheRemFix();	
		if (!empty($line )){
			$tvaremfix = $bull->RechTvaRemFix();
			if ($tvaremfix == -1 or empty($tvaremfix)) return -1;// gestion de l'erreur'}
			$line->taux_tva = $tvaremfix;
			$line->update_champs ('taux_tva',  $tvaremfix );
			$ret = $this->Traite_ligne_cmd_fact( $line,  $nomclasse,$nomclassedet, $fk_objet, $fk_objetdet, $fk_champdet , $objet, $bull,1                  );	
			unset($line);
		}
		else return null;
	}
	
	/*
		Fait le transfert dans Dolibar pour une location (création, Mise à jour )
	*/
	function DolibIndLocation($flag)
	{
		global $id_contratdet, $bull;

		$bull->fetch_complet_filtre(99,$bull->id);
		if ($flag == 'RemFix') $line = $bull->RechercheRemFix();	
		else $line = $bull->RechercheLign($id_contratdet);
		// COMMANDE	 ou FACTURE	
		$ret = $this->Traite_ligne_cmd_fact( $line, 'Commande', 'OrderLine',  'fk_commande', 'fk_line_commande', 'fk_linecmd', '', $bull, 1);				
		// traitement de la ligne de remise fixe
		$this->Traite_RemFix('Commande', 'OrderLine',  'fk_commande', 'fk_line_commande', 'fk_linecmd', '');
		
	} // DolibIndLocation
		
	/* 
	* Metà J?our la session , spécialement le client d'un grupe constitué*/
	function Traite_agefodd_session($id_act)
	{
		global $bull, $user;
		// ajouter le tiers si on est sur un groupe constitué
		$objses= new Agsession ($this->db);
		$objses->id = $id_act;
		$objses->fetch($id_act);

		//$this->interface_agefodd_session($line, $objses);
		$objses->nb_stagiaire=count($objses->lines);
		if ($bull->type_session_cgl == 1) $objses->fk_soc = $bull->id_client;
		$res = $objses->update($user,0);
		unset($objses);
		return($res);
	} // Traite_agefodd_session
	
	// STAGIAIRES AGEFODD
	
	function Traite_agefodd_stagiaire($line)
	{
		global $db, $objstag, $user, $bull;
		
		// Traite ajout, supprime	
		$objstag = new Agefodd_stagiaire($db);
		$res = $this->fetch_agefodd_stagiaire($line->id_part) ;
		$objstag->fk_socpeople		= $line->id_part;
		$objstag->nom 		= $line->PartNom;
		$objstag->prenom 	= $line->PartPrenom;
		if (empty($line->PartCiv)) $objstag->civilite 	= 'MR'; else $objstag->civilite 	= $line->PartCiv;
		$objstag->fk_socpeople = $line->id_part;
		$line->id_ag_stagiaire		= $objstag->id;
		if (!( $objstag->tel2 == $line->PartTel  or $objstag->tel3 == $line->PartTel ))
				$objstag->tel1 = $line->PartTel;
				
		$dataCgl = new CglFonctionCommune ($db);
		$objstag->date_birth = $dataCgl->transfDateMysql($line->PartDateNaissance);
		unset ($dataCgl);
		if ($res == 0) {
			$objstag->update($user, 0); 
		}
		elseif ($res == 1)  
		{
			$objstag->socid = $bull->id_client;
			$objstag->civilite = 'M';
			
			$ret = $objstag->create($user, 0);
			$line->id_ag_stagiaire	= $objstag->id;
		}
		else 
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}			
	} //Traite_agefodd_stagiaire	
	function fetch_agefodd_stagiaire ($idsocpeople)
	{
		global $langs, $objstag;

		$sql = "SELECT";
		$sql.= " so.rowid as socid, so.nom as socname,";
		$sql.= " civ.code as civilite_code,";
		$sql.= " s.rowid, s.nom, s.prenom, s.civilite as civilite, s.fk_soc, s.fonction,";
		$sql.= " s.tel1, s.tel2, s.mail, s.note, s.fk_socpeople, s.date_birth, s.place_birth";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as so";
		$sql.= " ON s.fk_soc = so.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_civility as civ";
		$sql.= " ON s.civilite = civ.code";
		$sql.= " WHERE s.fk_socpeople = '".$idsocpeople."' ";
		$sql.= " AND s.entity IN (".getEntity('agsession').")";
		dol_syslog(get_class($this)."::fetch ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$objstag->id = $obj->rowid;
				$objstag->ref = $obj->rowid; // use for next prev refs
				$objstag->nom = $obj->nom;
				$objstag->prenom = $obj->prenom;
				$objstag->civilite = $obj->civilite;
				$objstag->socid = $obj->socid;
				$objstag->socname = $obj->socname;
				$objstag->fonction = $obj->fonction;
				$objstag->tel1 = $obj->tel1;
				$objstag->tel2 = $obj->tel2;
				$objstag->mail = $obj->mail;
				$objstag->note = $obj->note;
				$objstag->place_birth = $obj->place_birth;
				$objstag->fk_socpeople = 0;
				$objstag->date_birth = $this->db->jdate($obj->date_birth);		
				$ret = 0;
			}
			else $ret= 1;
			$this->db->free($resql);				
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			$ret=-1;
		}
		return $ret;
	} //fetch_agefodd_stagiaire
	function cherche_agefodd_stagiaire($nom, $prenom) 
	{
	
		$sql = "SELECT";
		$sql.= " st.rowid as id";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire as st";
		$sql.= " WHERE nom = '".$nom."' and prenom = '".$prenom."'";
		$sql.= " AND c.entity IN (".getEntity('agsession').")";
		dol_syslog(get_class($this)."::cherche_agefodd_stagiaire ", LOG_DEBUG);
		$resql=$this->db->query($sql);	
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$res = $obj->id;
			}
			else $res = -1;
			$this->db->free($resql);			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::cherche_agefodd_stagiaire ".$this->error, LOG_ERR);
			$res = -2;
		}
		return $res;
	} // cherche_agefodd_stagiaire
	
	// CONTACT AGEFODD
	
	function Traite_agefodd_contact($bull)
	{
		global $db, $objcont, $user;
		
		$objcont = new Agefodd_contact($db);
		$res = $this->fetch_agefodd_contact($bull->fk_persrec) ;
		
		if ($res == 1)  
		{
			$objcont->archive 	= 0 ;		
			$objcont->spid 		= $bull->fk_persrec;
			$ret = $objcont->create($user, 0);	
			$bull->id_ag_contact = $objcont->id;
			return ;
		}
		else 
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }		
	} //Traite_agefodd_contact	
	function fetch_agefodd_contact ($id)
	{	
		global $langs, $objcont, $bull;

		$sql = "SELECT";
		$sql.= " c.rowid as id, c.archive";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_contact as c";
		$sql.= " WHERE fk_socpeople = ".$id;
		$sql.= " AND c.entity IN (".getEntity('agsession').")";
		dol_syslog(get_class($this)."::fetch ", LOG_DEBUG);
		$resql=$this->db->query($sql);
	
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$bull->id_ag_contact = $obj->id;
				$res = 0;
			}
			else $res = 1;
			$this->db->free($resql);			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			$res = -1;
		}
		return $res;
	}//fetch_agefodd_contact
	
	// LIEN SESSION - CONTACT AGEFODD
	
	function Traite_agefodd_session_contact($line, $faire)
	{
		global $db,  $user, $objagsescont, $bull;
		
		if ($faire == 'delete')
		{	
			// si on n'a pas d'autre participant de ce bulletin sur cette activité		//$this->delete_agefodd_session_contact($bull->id_ag_contact,	$line->id_act, $user, 0);		
		}
		else
		{
			/* en cas de changement de personne, c'est Supprime et ajout */
			$objagsescont = new Agsession($db);
			$res = $this->fetch_agefodd_session_contact($bull->id_ag_contact, $line->id_act) ;
			if ($res == 1)  
			{
				$objagsescont->fk_session_agefodd 	=  $line->id_act;
				$this->create_agefodd_session_contact($objagsescont, $bull->id_ag_contact, $user, 0);		
			}
			else 
			{
				$this->error="Error ".$this->db->lasterror();
				dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
				return -1;
			}
        }
	} //Traite_agefodd_session_contact	
	function fetch_agefodd_session_contact($idpersec, $idact)
	{
		global $db,  $user,  $bull;
			
		$sql = 'SELECT  *  FROM  '.MAIN_DB_PREFIX . 'agefodd_session_contact';
		$sql .= ' WHERE fk_agefodd_contact = '.$idpersec;
		$sql .= ' AND  fk_session_agefodd = '.$idact;
		dol_syslog(get_class($this)."::fetch ", LOG_DEBUG);
		
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$res = 0;
			}
			else $res = 1;
			$this->db->free($resql);			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			$res = -1;
		}
		return $res;
	
	} 
	function update_agefodd_session_contact($user=0, $notrigger=0)
	{
	
	
		if (empty($contactid)) $contactid = 0;
	
		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_contact SET ';
		$sql .= ' fk_agefodd_contact=' . $this->db->escape ( $contactid ) . ',';
		$sql .= ' fk_user_mod=' . $this->db->escape ( $user->id );
		$sql .= ' WHERE rowid="' . $this->db->escape ( $fk_contact ).'"';
		
		$this->db->begin ();
		
		dol_syslog ( get_class ( $this ) . "::setContactSession update sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror ();
			$this->db->rollback();
		}
		else
			$this->db->commit();
	}			
	
	/*
	* Existe Agsession::setContactSession($contactid, $user) 
	* 
	*	@param 	objet	$objagsescont	Objet Lien Contact - session
	*	@param 	int		$contactid	identifiant du contact
	*	@param 	objet	$user		Objet user
	*	@param 	int		$notrigger	1 -pas de lanement de trigger, 0 - lancement des triggers
	*	
	*	@retour 	1 - OK,  -1*n si errur n étant nb erreurs
	*/
	function create_agefodd_session_contact($objagsescont, $contactid, $user, $notrigger=0)
	{
		$retour = $objagsescont->setContactSession($contactid, $user); 	
				
/*		// INSERT request
        $this->db->begin();
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'agefodd_session_contact(fk_session_agefodd, fk_agefodd_contact, fk_user_mod, fk_user_author, datec)';
		$sql .= ' VALUES ( ';
		$sql .= $this->db->escape ( $objagsescont->fk_session_agefodd  ) . ',';
		$sql .= $this->db->escape ( $objagsescont->fk_agefodd_contact ) . ',';
		$sql .= $this->db->escape ( $user->id ) . ',';
		$sql .= $this->db->escape ( $user->id ) . ',';
		$sql .= "'" . $this->db->idate ( dol_now ('tzuser') ) . "')";
		dol_syslog(get_class($this)."::setContactSession create ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			$this->db->free($resql);			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			$this->db->rollback();
			return  -1;
		}
*/
		return $retour;
	} 
	function delete_agefodd_session_contact($idpersec, $idact, $user=0, $notrigger=0)
	{
	global $db,  $user;
				
		// DELETE request
		$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'agefodd_session_contact';
		$sql .= ' WHERE fk_agefodd_contact = '.$idpersec;
		$sql .= ' AND  fk_session_agefodd = '.$idact;
		dol_syslog(get_class($this)."::setContactSession delete ", LOG_DEBUG);
        $this->db->begin();
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			$this->db->free($resql);			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			return  -1;
		}
		return ;
	} 

	// LIEN SESSION - STAGIAIRE
	
	function Traite_agefodd_session_stagiaire($line, $faire, $inscrit)
	{
		global $db, $user, $objagsestag, $bull, $conf;
		/* status_in_session
		$this->labelstatut[0]=$langs->trans("TraineeSessionStatusProspect");
		$this->labelstatut[1]=$langs->trans("TraineeSessionStatusVerbalAgreement");
		$this->labelstatut[2]=$langs->trans("TraineeSessionStatusConfirm");
		$this->labelstatut[3]=$langs->trans("TraineeSessionStatusPresent");
		$this->labelstatut[4]=$langs->trans("TraineeSessionStatusPartPresent");
		$this->labelstatut[5]=$langs->trans("TraineeSessionStatusNotPresent");
		$this->labelstatut[6]=$langs->trans("TraineeSessionStatusCancelled");
		*/
		
		if ($faire == 'delete')
		{
			$ret = $this->annule_agefodd_session_stagiaire($line->id_ag_stagiaire,	$line->id_act, $line->fk_agsessstag);	
			//$line->updateaction('X');
		}
		else
		{	
			if ($line->id_act == 0 or empty($line->id_act)) return (-1);
			$objagsestag = new Agefodd_session_stagiaire($db);
			//$objagsestag->status_in_session = $inscrit;		
			// Plus de pre_inscription
			// si pas de lien existant sur l'enregistrement 		
			if ((empty($line->fk_agsessstag) or $line->fk_agsessstag == 0))
			{
				$objagsestag->status_in_session = $inscrit;	
				$objagsestag->fk_session_agefodd 	=  $line->id_act;
				if (empty ($line->id_part) or $line->id_part == 0)
				{	
					// créer le lien avec Participant inconnu 
					$objagsestag->fk_stagiaire 	= $conf->global->CGL_STAG_INCONNU;
				}
				else {
					// créer le lien avec le participant connu
					$objagsestag->fk_stagiaire 	= $line->id_ag_stagiaire;
				}

				// créer et sauvegarder le rowid
				$ret = $objagsestag->create($user, 1);	
				$line->fk_agsessstag = $objagsestag->id;
			}
			else
			{
				
				// Modifier le lien
				// si on a un lien même participant/même activité/Même bulltin, on s'en sert
				if ($line->fk_agsessstag == 0 and $fk_ancagsessstag>0) {
					$line->fk_agsessstag = $fk_ancagsessstag;
					}			
				$ret = $this->fetch_agefodd_session_stagiaire($line->fk_agsessstag) ;
				if ($ret <0){
					$this->error="Error ".$this->db->lasterror();
					dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
					return -1;
				}
				$objagsestag->status_in_session = $inscrit;				
				$objagsestag->id = $line->fk_agsessstag;
				$objagsestag->fk_session_agefodd	= $line->id_act;
				$ret = $objagsestag->update($user, 1);	
			}
			// $this->labelstatut[2]=$langs->trans("TraineeSessionStatusConfirm");
			
			if ($ret < 0) 
			{
				$this->error="Error ".$this->db->lasterror();
				dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
				return -2;
			}
		}		
	} //	Traite_agefodd_session_stagiaire
	
	function fetch_agefodd_session_stagiaire( $id_stag_sess , $idpers = 0, $idact= 0)
	{
		global $db, $user, $objagsestag, $bull;
		
		$sql = "SELECT rowid, ";
		$sql.= " fk_session_agefodd, fk_stagiaire, fk_agefodd_stagiaire_type, fk_user_author,fk_user_mod, datec, status_in_session";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_stagiaire";
		if ($id_stag_sess == 0)
		{
			$sql.= " WHERE fk_session_agefodd = ".$idact;
			$sql.= " AND  fk_stagiaire = ".$idpers;
		}
		else
		{
			$sql.= " WHERE rowid = ".$id_stag_sess;
		}
		dol_syslog(get_class($this)."::fetch_agefodd_session_stagiaire ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{	
			if ($this->db->num_rows($resql))
			{		
				$obj = $this->db->fetch_object($resql);
				
				$objagsestag->fk_session_agefodd	= $obj->fk_session_agefodd;
				$objagsestag->fk_stagiaire			= $obj->fk_stagiaire;
				$objagsestag->fk_agefodd_stagiaire_type = $obj->fk_agefodd_stagiaire_type;
				$objagsestag->fk_user_author		= $obj->fk_user_author;
				$objagsestag->fk_user_mod			= $obj->fk_user_mod;
				$objagsestag->datec					= $this->db->jdate($obj->datec);
				$objagsestag->status_in_session		= $obj->status_in_session;
				$objagsestag->id					= $obj->rowid;
				$res = 0;
			}
			else $res = 1;
			$this->db->free($resql);		
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_stagiaire_per_session ".$this->error, LOG_ERR);
			$res =  -1;
		}
		return $res;
	} // fetch_agefodd_session_stagiaire 
	function cherche_agefodd_session_stagiaire($nom, $prenom, $id_act)
	{	
		$sql = "SELECT";
		$sql.= " s.rowid as id";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_stagiaire as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_stagiaire as st on st.rowid = s.fk_stagiaire";
		$sql.= " WHERE   s.fk_session_agefodd = ".$id_act;
		$sql.= " AND st.nom = '".$nom."' and st.prenom ='".$prenom."'";
		dol_syslog(get_class($this)."::cherche_agefodd_session_stagiaire ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$res =  $obj->id;
			}
			else $res = -1;
			$this->db->free($resql);			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::cherche_agefodd_session_stagiaire ".$this->error, LOG_ERR);
			$res = -2;
		}
		return $res;
		
	} // cherche_agefodd_session_stagiaire
	function annule_agefodd_session_stagiaire($idpers, $idact, $id_stag_sess = 0)
	{	
		// noter l'annulation
		// UPDATE request

		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire SET ';
		$sql .= ' status_in_session = 6';
		if ($id_stag_sess == 0)
		{
			$sql.= " WHERE fk_session_agefodd = '".$idact."'";
			$sql.= " AND  fk_stagiaire = '".$idpers."'";
		}
		else
		{
			$sql.= " WHERE rowid = ".$id_stag_sess;
		}
		dol_syslog(get_class($this)."::setContactSession annule ", LOG_DEBUG);
	    $this->db->begin();
		$resql=$this->db->query($sql);
		if ($resql)		{
			$this->db->commit();		
			$this->db->free($resql);			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::annule_agefodd_session_stagiaire ".$this->error, LOG_ERR);			
			$this->db->rollback();
			return  -1;
		}
		return ;
	}  //annule_agefodd_session_stagiaire
	
	function AbandonneStgSession ($bull)
	{
		$tabact= array();
		
		if (!empty($bull->lines)) {
			foreach ($bull->lines as $bullline) {
				//if ($bullline->type_enr  == 0 and (empty($bullline->action ) or !( $bullline->action == 'X' or $bullline->action  == 'S'))) {
				if ($bullline->type_enr  == 0 and  $bullline->action  == 'S') {
					$this->annule_agefodd_session_stagiaire ($bullline->id_ag_stagiaire,	$bullline->id_act, $bullline->fk_agsessstag);

					if (!isset($tabact[$bullline->id_act]) or  empty($tabact[$bullline->id_act])) {					
						// mis à jour de l'évènement correspondant par routine précédente
						$session = new Agsession($this->db);
						$session->fetch($bullline->id_act);
						$ret = $this->Traite_actioncomm($bullline->id_act, $session->activite_heured, $session->activite_heuref);
						$tabact[$bullline->id_act] = $bullline->id_act;
					}
					/*			// MAJ evenement
				$sesscal = new Agefodd_sesscalendar ($this->db);
				$sesscal->sessid = ;
				$sesscal->fk_actioncomm = ;
				$sesscal->updateAction($user);
	*/
				}
			} // foreach
		}			
		
	} //AbandonneStgSession
	
	// AGENDA
	
	function Traite_actioncomm($id_act, $datep , $datef )
	{	
		// note l'evenement a été crée, lors de la création du départ. Il suffit donc de le modifier
		global   $user, $objaction, $bull;
		// modifier libelle
		
		$this->db->begin();
		
		if (empty($id_act)) return;
		$ret = 0;
		$objaction = new ActionComm($this->db);
		$res = $this->fetch_actioncomm($id_act, 'agefodd_agsession') ;
		$objaction->label 	=  $this->LibAgenda($id_act);
		if ($datep) $objaction->datep = $datep;
		if ($datef) $objaction->datef = $datef;
		//$objaction->id = $id_act;
		$objaction->code = 'CGL_LOC';
		if ($res <> 1) 		{ 
			// Création
			$objaction->elementtype = 'agefodd_agsession';
			$objaction->fk_element = $id_act;
			$ret = $objaction->create($user, 0);
			dol_syslog(get_class($this)."::create ", LOG_WARNING);
		}
		else {
			// Modification
			$objaction->tms = dol_now('tzuser');	
			//var_dump($objaction);		
			$ret = $objaction->update($user, 0);
			dol_syslog(get_class($this)."::update ", LOG_WARNING);
			unset($objaction);
			if ($ret < 0)
				{
					$this->error="Error ".$this->db->lasterror();					
					dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
					$ret =  -2;
				} 
		}
		if ($ret < 0) $this->db->rollback();
		else $this->db->commit();
		
		return $ret;
	} //Traite_actioncomm

	function LibAgenda($id_act)
	{
		global  $langs;
		
		// aller cherche l'evenement concernant la session
		/*
		elementtype = agefodd_agsession
		fk_element = agefodd_session.rowid
		label ) label - (FOR...) + (total/inscrit/pre-inscrit)
		*/
		$this->db->commit();

		$wdep = new CglDepart ($this->db);
		$activite_nbinscrit = $wdep->NbPartDep(2,$id_act);
		$activite_nbpreinscrit = $wdep->NbPartDep(1,$id_act);
		if (empty($activite_nbinscrit)) $activite_nbinscrit	=0;	
		if (empty($activite_nbpreinscrit)) $activite_nbpreinscrit	=0;	
		$som = (int)$activite_nbinscrit + (int)$activite_nbpreinscrit;	
		//  repere nb total
		$sql= "SELECT ss.rowid as id, ss.nb_place,  lastname, firstname , ss.intitule_custo ";
		$sql.= "FROM ".MAIN_DB_PREFIX."agefodd_session AS ss ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_formateur AS sf ON sf.fk_session = ss.rowid ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formateur AS f ON sf.fk_agefodd_formateur = f.rowid ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople AS sp ON f.fk_socpeople = sp.rowid ";
		$sql.= " WHERE ss.rowid=".$id_act;
		$sql.= " GROUP BY ss.rowid, ss.nb_place";
        dol_syslog(get_class($this)."::LibAgenda ");
        $resql=$this->db->query($sql);
		
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
				$label = $obj->intitule_custo;
				$label .= ' - ('.$obj->nb_place.'/'.$som.') - '. $obj->firstname .' '. $obj->lastname;
           }
		   else {
			    $obj = $this->db->fetch_object($resql);
				$label = $obj->intitule_custo;
				$label .= ' - ('.$obj->nb_place.'/'.$som.') - '.$langs->trans('SsMoniteur');		   
		   }
            $this->db->free($resql);
       		dol_syslog(get_class($this)."::LibAgenda label=".$label);
				return $label;
        }
        else
        {			
            $this->error=$this->db->lasterror();
            return -1;
		}
		
		
	} //LibAgenda
	
	function fetch_actioncomm($idsess, $element)
	{
        global $langs, $objaction;

        $sql = "SELECT id, datec, fk_soc, ref_ext,";
        $sql.= " label, note, percent,fk_action, transparency, fk_user_mod,fk_user_action, fk_user_done, fk_user_author, punctual, ";
        $sql.= " fk_contact,datep,datep2, note,durationp, fk_project, priority,fulldayevent,location,";
        $sql.= " fk_element, elementtype";
        $sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as c";
        $sql.= " WHERE fk_element='".$idsess."' AND elementtype='".$element."'";
        dol_syslog(get_class($this)."::fetch ");
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $objaction->id        	= $obj->id;
                $objaction->ref_ext		= $obj->ref_ext;
                $objaction->ref			= $obj->ref;
				$objaction->code		= $obj->code;
                $objaction->label		= $obj->label;		
                $objaction->note		= $obj->note;
                $objaction->percentage	= $obj->percent;
				if (!is_object($objaction->author))  $objaction->author = new stdClass();
				if (!is_object($objaction->usermod)) $objaction->usermod = new stdClass();
				if (!is_object($objaction->usertodo)) $objaction->usertodo =new stdClass();
				if (!is_object($objaction->userdone)) $objaction->userdone =new stdClass();
				$objaction->authorid 		=  $obj->fk_user_author;		
                $objaction->usermodid		= $obj->fk_user_mod;
                $objaction->uuserownerid	= $obj->fk_user_action;
                $objaction->userdoneid		= $obj->fk_user_done;	
                $objaction->author->id		= $obj->fk_user_author;		
                $objaction->usermod->id		= $obj->fk_user_mod;
                $objaction->usertodo->id	= $obj->fk_user_action;
                $objaction->userdone->id	= $obj->fk_user_done;	
                $objaction->priority		= $obj->priority;
                $objaction->fulldayevent	= $obj->fulldayevent;
                $objaction->location		= $obj->location;
                $objaction->transparency	= $obj->transparency;	
				if (!is_object($objaction->societe)) $objaction->societe =new stdClass();
                $objaction->socid			= $obj->fk_soc;		
                $objaction->societe->id		= $obj->fk_soc;	
                $objaction->fk_action		= $obj->fk_action;
                $objaction->datep			= $this->db->jdate($obj->datep) ;
                $objaction->datef			= $this->db->jdate($obj->datep2);
                $objaction->datec			= $this->db->jdate($obj->datec) ;	
                $objaction->durationp		= $obj->durationp;	
                $objaction->societe->id		= $obj->fk_soc;	
                $objaction->fk_project		= $obj->fk_project;	
				if (!is_object($objaction->contact)) $objaction->contact =new stdClass();
                $objaction->contact->id		= $obj->fk_contact;	
                $objaction->punctual		= $obj->punctual;
                $objaction->fk_element		= $obj->fk_element;
                $objaction->elementtype		= $obj->elementtype;				
            }
            $this->db->free($resql);
            return 1;
        }
        else
        {
			$this->error="Error ".$this->db->lasterror();			
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
        }
	} //fecth_actioncomm
	
	/*
	* il existe une fonction $this->add_object_linked de la classe abstraite CommonObject
	*	@param $bull		Objet bull
	*	@param $idcmd		Id de l'objet lié facture ou commande
	*	@param $v_nomclasse	classev de  l'objet  lié facture ou commande
	*
	* 	@retour 	 - OK, 0 - non OK
	*/
		
	function AjoutElement($bull, $idcmd, $v_nomclasse)
	{
		$retour = $bull->add_object_linked($v_nomclasse, $idcmd, $user, 0);
/*        // Clean parameters
        // Mise a jour ligne en base
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (fk_source,sourcetype,fk_target,targettype) VALUES(";
        $sql.= $idbull.', "bulletin", '.$idcmd.', "'.$v_nomclasse.'")';

        dol_syslog("CglInscription::AjoutElement sql=$sql");

        $this->db->begin();
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("CglInscription::AjoutElement ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
*/		
	} //AjoutElement


		// LIEN COMMANDE et AGEFODD - fonction inutiliser, à supprimer 
	function Traite_agefodd_facture_1($fk_id,  $type)
	{
		global $user, $bull, $langs;
		$ag_fac = New agefodd_facture($this->db);
		$error = 0;
		if (!empty($bull->lines)) {
			foreach ($bull->lines as $line)
			{
				if ($line->type_enr ==0)
				{
					// recherche id de agefodd_facture par session  - 
					$ret = $ag_fac->fetch($line->id_act, $bull->id_client);
					if ($ret <0 ) { $error++; $mesg .= $langs->trans(Session).' : '.$line->id_act. ' --- Client:'.$bull->id_client;}
					else
					{
						if ( $type == 'facture') $ag_fac->facid = $fk_id;
						elseif ( $type == 'commande') $ag_fac->comid = $fk_id;
						if (empty($ag_fac->id))
						{
							$ag_fac->sessid = $line->id_act;
							$ag_fac->socid = $bull->id_client;
							$ret = $ag_fac->create($user, 0);
							if ($ret <0 ) { $error++; $mesg .= 'Creation impossible : '.$langs->trans(Session).' : '.$line->id_act. ' --- Client:'.$bull->id_client;;}
						}
						else
						{
							$ag_fac->update($user, 0);
							if ($ret <0 ) { $error++; $mesg .= 'MAJ impossible : '.$langs->trans(Session).' : '.$line->id_act. ' --- Client:'.$bull->id_client;;}
						}
					}
				}
			} // Foreach			
		}
		if ( $error >0 )
		{
			dol_syslog('cglinscription::Traite_agefodd_facture Erreur '.$ret. '--'.$mesg, LOG_ERR);
			return -1;
		} 
		else
		{
			return 1;
		}
	} //Traite_agefodd_facture
	function AbandonneCommande($id)
	{
		global $bull,$langs;
		
		$wf = new Commande ($this->db);
		$wf->fetch($id);
		$wf->cancel();
		// rajouter un trigger dans CglAvt por ORDER_CANCEL - voir le model Ageffod : dans triggers, dans core/module.modAgefodd...php
		return 0;
	} // AbandonneCommande
	
	/* 
	* pour abandonner un AC impayé et n'ayant pas servi, lors de la facturation terminée
	*
	*/
	function AbandonneFacture($id, $origine)
	{
		global $bull,$user, $langs ;
		$wf = new Facture ($this->db);
		$wf->fetch($id);
		if ($origine == 'Stripe') $temp = 'LibStripeAband';
		elseif ($origine == 'Loc') $temp = 'LibLoAband';
		elseif ($origine == 'Insc')  $temp = 'LibBuAband';
		$result = $wf->set_canceled($user, 'Abandon' , $langs->trans($temp));
		return 0;
	} //AbandonneFacture
			
	/* PAIMENT - ACOMPTE
	* permet de mettre à jour le paiement BU/LO dans Acompte
	* l'acompte doit décrire le détail des paiements
	*
	*/	
	function Traite_paiement($totalfac = '') 
	{
		global $bull, $user, $langs;
		
		dol_syslog(get_class($this)."::Traite_paiement ".$langs->trans((empty($bull->fk_acompte ) or $bull->fk_acompte == 0)?'LibCreatAcompte':'LibModAcompte'), LOG_DEBUG);

		// transformer les lignes de paiement en une facture d'acompte si paiement non null
		//	vérifier qu'une action au moins n'est pas vide  et	calcul montant total des paiements
		//	sinon sortir
		$actmaj == 0; 
		/* sur l'acompte des paiements, on ne prend que les paiements directs, pas ceux encaissé par Stripe*/
		$totalpmt = $bull->TotalPaimnt() -  $bull->TotalPaimntStripe();

		if (empty($bull->fk_acompte ) or $bull->fk_acompte == 0)		{
			$this->createAcompte($bull, $totalpmt, true,'',$bull->ref, false, true);
		}
		else	$this->updateAcompte($bull, $bull->fk_acompte, $totalpmt);
		// acienne version - la facture d'acompte est mise dans agefodd_facture, 
		//$this->Traite_agefodd_facture($bull->fk_acompte, 'facture');
		// paiement
		if (!empty($bull->lines)) {
			foreach($bull->lines as $bullline) 		{
				if ($bullline->type_enr == 1 and $bullline->organisme  <> 'Stripe') 			{
					if (  empty($bullline->fk_paiement) or $bullline->fk_paiement == 0 )	{			
						// pour chaque ligne A créer paiements et bank et remettre action à vide
						$ret =$this->create_paiement($bullline, $user, 0);
						if ($ret >= 0 ) {
							$bullline->action == '';
							$ret = $bullline->update_champs( "action", '', "fk_banque", $bullline->fk_banque, "fk_paiement", $bullline->fk_paiement);
						}			
					}
					elseif ($bullline->action == 'S' and $bullline->type_enr == 1 and $bullline->organisme  <> 'Stripe')	{	
						// pour chaque ligne S supprimer paiement et bank et remettre action à X	
						$ret = $this->delete_paiement($bullline, $bull->fk_acompte,  $bull->fk_soc_rem_execpt,  $user, 0);
						if ($ret >= 0 )  
							$ret = $bullline->update_champs( "action", 'X', "fk_banque", '', "fk_paiement", '');
					}
					elseif ( $bullline->organisme  <> 'Stripe') {
				// pour chaque ligne M modifier bank et remettre action ?ide	
						$ret = $this->update_paiement($bullline->fk_paiement,$bullline, $user, 0);
						if ($ret >= 0 )  { $bullline->action == ''; $bullline->updateaction('');	}			
					}
				}			
			}//foreach
		}		
		return 0;		
	} // Traite_paiement
	/*
	*	Transfert les informations de BU/LO dans Dolibarr (commande, stagiaire, bank, paiement, évènement...)
	*
	*	@retour	0
	*/	
	function DolibIndPaiement()
	{
		/* mettre à jour l'acompte, le paiement et l’écriture */
		global $bull;		
		$bull->fetch_complet_filtre(-1, $bull->id);
		// un paiement engage CAV, donc le bulletin/Contrat sera réputé pre-inscrit ou inscrit
//		if ($bull->statut > $bull->BULL_ENCOURS )// paiement
//		{
			// Acompte
			// ecriture
			//paiement	
			$ret = $this->Traite_paiement(0);
			$bull->AjoutFkDolibarr("");
			$this->db->commit();
			// Si paiement total ==> inscription à la session de tous les participants
			$bull->fetch($bull->id);
			$bull->updateregle($bull->CalculRegle());
			$solde = $bull->LireSolde();
			if ($solde < 0.01  and $solde > -0.01 ) $solde = 0;
			if ($bull->type == 'Insc' and $solde == 0 ) { // inscription
				$wl = new CglInscription($this->db);
				$wl->Inscrire();
				unset ($w1);
			}
			if ($bull->type == 'Loc' and $bull->IsLocPaimtReserv ()	) { // inscription
				$wl = new CglLocation($this->db);
				$wl->Reserver();
				unset ($w1);
			}
//		}
		return 0;
	} //DolibIndPaiement

	
	function createAcompte($bull, $total, $fl_paye=true, $desc_line='', $note ='',  $fl_conv_rem = true, $fl_acompte_princ = true)
	{
		global $user, $langs;
				
		$objacompte = new Facture($this->db);	
		//$objacompte->date = dol_stringtotime($bull->datec);	
		$objacompte->date = dol_now('tzuser');	
		$objacompte->socid = $bull->id_client;
		$objacompte->fac_rec  = 0;
		$objacompte->type = 3; // acompte 
		$objacompte->ref_client = $bull->ref_client;
		//$objacompte->model_pdf = 'COComIND';
		$objacompte->model_pdf = 'TVA';
		$objacomptedet = new FactureLigne ( $this->db);
		$objacompte->lines[0] = $objacomptedet;
		$objacompte->total_ht = $total;
		$objacompte->total_ht = $total;
		$objacompte->total_ttc = $total;
		$objacompte->import_key = 'cglInscription';
		if (empty( $desc_line)) $objacomptedet->desc = $langs->trans("AcompteBull").$bull->ref;
		else  $objacomptedet->desc =$desc_line;
		if (empty( $desc_line))$objacomptedet->label = $langs->trans("AcompteBull").$bull->ref;
		else $objacomptedet->label = $desc_line;
		$objacomptedet->fk_code_ventilation = 0;		
		$objacompte->note_private = $note;				

		$objacomptedet->qty = 1;
		$objacomptedet->vat_src_code = 0;
		$objacomptedet->tva = 0;
		$objacomptedet->subprice = $total;
		$objacomptedet->subprice = $total;
		$objacomptedet->total_ht = $total;
		$objacomptedet->total_tva = 0;
		$objacomptedet->import_key = 'cglInscription';
		$objacomptedet->total_ttc = $total;
		$objacomptedet->rang = 1;
		$objacomptedet->info_bits  = 0;
		
				
		$ret= $objacompte->create($user,0,dol_now('tzuser'));	
		// recuperer la clé de l'acompte
		if ($fl_acompte_princ) $bull->fk_acompte = $objacompte->id;
		// valider la facture
		$objacompte->fetch_lines();
		$objacompte->validate($user);
		if ($fl_paye == true) $objacompte->set_paid($user);	
		if ($fl_conv_rem == true and $ret > 0) {
			$ret1 = $this->create_soc_rem_exectp($objacompte);
			$bull->fk_soc_rem_execpt = $ret1;
		}		 
		
		// ajouter le code ventilation dans la ligne de l’acompte
		// ventilation acompte à supprimer puisque la facture prend en charge le CALCUL
		if ($nomclasse == 'Facture') $wc1->MajVentilationAcompteFact($bull->fk_acompte);	
		
		unset($objacomptedet);
		unset ($objacompte);
		return $ret;
	}//createAcompte
	
	function ChercheRefAcompteParId($id)
	{
		
		$sql = "SELECT ref";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as fac"  ;
		$sql.= " WHERE rowid='".$id."'";
		
        dol_syslog(get_class($this)."::ChercheRefAcompteParId ");
        $resql=$this->db->query($sql);
        if ($resql)        {			
            $num = $this->db->num_rows($resql);
			
            $obj = $this->db->fetch_object($resql);
			return $obj->ref;
			}		
	} //ChercheRefAcompteParId
	/*
	*
	*	@param 	Facture $acompte		Objet Facture
	*	@retour int		$ret			Id de l'enregistrement de soc_rem_execpt
	*/
	function create_soc_rem_exectp($acompte)
	{
		global $user, $bull;
		$rem = new DiscountAbsolute($this->db);
		$this->interface_rem($acompte, $rem);
		$ret = $rem->create($user);	
		$bull->fk_soc_rem_execpt = $ret;
		return $ret;		
	}//create_soc_rem_exectp
	
	function interface_rem($acompte, $rem)
	{      			
		$rem->datec = $acompte->date;
		$rem->fk_soc = $acompte->socid;
		$rem->description = '(DEPOSIT)';
		$rem->amount_ht = $acompte->total_ht;
		$rem->amount_tva = $acompte->total_tva;
		$rem->amount_ttc = $acompte->total_ttc;
		$rem->tva_tx = '0.00';
		$rem->fk_facture_source = $acompte->id;
	
	} //interface_rem
	
	function updateAcompte($bull,$fkacompte, $totalpmt)
	{
		global $user;	
		$objacompte = new Facture($this->db);
		$ret = $objacompte->fetch($fkacompte);	
		$objacompte->id = $fkacompte;
		$objacompte->total_ht=$totalpmt;
		$objacompte->total_ttc=$totalpmt;
		$objacompte->model_pdf = 'TVA';
		$objacomptedet = new FactureLigne ( $this->db);
		$objacomptedet = $objacompte->lines[0];
		$objacomptedet->subprice = $totalpmt;
		$objacomptedet->total_ht = $totalpmt;
		$objacomptedet->total_ttc = $totalpmt;		
		$ret = $objacompte->update($user, 0);	
		$ret = $objacomptedet->update($user, 0);
		unset($objacomptedet);
		$objacompte->set_paid($user);
		$ret = $this->update_soc_rem_exectp($objacompte);
		unset ($objacompte);			
	}//updateAcompte
	function update_soc_rem_exectp($objacompte)
	{
		global $user, $bull;
		
		$rem = new DiscountAbsolute($this->db);
		$ret = $rem->fetch(0, $objacompte->id);
		$this->interface_rem($objacompte, $rem);
		//$ret = $this->updateRemExcept($rem);
		dol_syslog(get_class($this)."::update_soc_rem_exectp Retour Apres fetch ".$ret.'---- pour l_acompte:'.$objacompte->id.'----', LOG_DEBUG);
		if ($ret == 0) {
			// il n'y a pas de remise exceptionnelle pour cette facture
			$this->create_soc_rem_exectp($objacompte);
		}
		else {
			$this->interface_rem($objacompte, $rem);
			$ret = $this->updateRemExcept($rem);
		}
	}//update_soc_rem_exectp

	function interface_paiement($line, $obj) 
	{
		global $bull;
		
		$w=new CglInscription ($this->db);
		$obj->id = 	$line->fk_paiement;
		$obj->totalamount = price2num($line->montant);
		$obj->paiementid = $line->id_mode_paiement;
		$obj->num_payment = $line->num_cheque;
		$obj->datepaye = dol_stringtotime($line->date_paiement);
		$obj->bank_account = $line->fk_accountCGL;
		$obj->fk_account = $line->fk_accountCGL;
		$obj->fk_bank = $line->fk_banque;
		$obj->datev = $line->date_paiement;
		$obj->datepaye = $obj->datev;
		$obj->dateo = $obj->datev ;
		
		$obj->amounts[$bull->fk_acompte] = $line->montant;
		unset ($w);
	}//interface_paiement

	function update_paiement($id, $line, $user=0, $notrigger=0)
	{
		global $user, $bull, $langs;
		$objpmt = new Paiement($this->db);	
		$objpmt->fetch($id);
		$this->interface_paiement($line, $objpmt) ;	

		$this->updatelignepaiement($objpmt,$bull->fk_acompte);

		$objpmt->update_date($objpmt->datepaye);


		if ($line->fk_banque == 0 or $line->fk_banque == -1 or empty($line->fk_banque)) {	
			if ($bull->type == 'Insc') $texte = 'LibPaiementBu';
			else $texte = 'LibPaiementLo';
			$ret=$objpmt->addPaymentToBank($user,'payment','('.$langs->trans($texte,$bull->ref).')',$objpmt->fk_account,$line->tireur,$line->organisme,0);
			$line->fk_banque = $ret;
		}
		else {
			$objbank = new AccountLine ($this->db);
			$objbank->rowid = $objpmt->bank_line;
			$objbank->dateo = $line->date_paiement;
			$objbank->datev = $objbank->datev ;
			$objbank->amount = price2num($line->montant); 
			$objbank->num_chq = $line->num_cheque;
			$objbank->fk_type = "'".$this->RecherCodeModPaiement($line->id_mode_paiement)."'";
			$objbank->fk_account = $line->fk_accountCGL;		
			$objbank->emetteur = $line->tireur;
			$objbank->banque = $line->organisme;
			// modif date et montant
			//$objbank->update ($user,0);
			$fctDolModif = new CglFonctionDolibarr ($this->db);	
			$ret = $fctDolModif->bankline_update($objbank, $user,0);
			unset($fctDolModif);
			unset ($objbank);
		}
		unset($objpmt);
	} //update_paiement
	function RecherCodeModPaiement($id)
	{
		global $langs;

		$sql = "SELECT code";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_paiement";
		$sql.= " WHERE id = '".$id."' ";
		dol_syslog(get_class($this)."::RecherCodeModPaiement - fetch ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->db->free($resql);
				return $obj->code;
			}			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}	 //RecherCodeModPaiement
	/*
	* Demande à création d'un acompte à Dolibarr
	*
	* @param 	line			Object	ligne de paiement (type 1) de Bulletin contenant les info pour création de l'acompte
	* @param	user			Object	utilisateur
	* @param	notrigger	flg			1 -> ne pas actionner les triggers
	*
	*/
	function create_paiement($line, $user, $notrigger=0)
	{
		global $user, $bull, $langs;
		
		$objpmt = new Paiement($this->db);		
		$this->interface_paiement($line, $objpmt) ;

		$ret = $objpmt->create ($user, 0);
		$line->fk_paiement = $ret;
		
		if ($bull->type == 'Insc') $texte = 'LibPaiementBu';
		else $texte = 'LibPaiementLo';
		$ret=$objpmt->addPaymentToBank($user,'payment','('.$langs->trans($texte,$bull->ref).')',$objpmt->fk_account,$line->tireur,$line->organisme,0);
		$line->fk_banque = $ret;
		unset ($objpmt);
	} //create_paiement

	function updatelignepaiement($objp,$fk_acompte)
	{	
		if (empty($objp->totalamount)) $objp->totalamount = 0;
		if (empty($objp->paiementid) or empty($objp->fk_bank)) 
		{
            dol_syslog(get_class($this).'::updatelignepaiement  - identifiants vides');
			return(-2);
		}
		
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'paiement set ';
		$sql .= "amount = '".$objp->totalamount."',";
		$sql .= "fk_paiement = '".$objp->paiementid."',";
		$sql .= "num_paiement = '".$objp->num_payment."',";
		$sql .= "fk_bank = '".$objp->fk_bank."'";
		$sql .= ", datep = '".$objp->datepaye."'";

		$sql.= ' WHERE rowid = "'.$objp->id.'"';
		dol_syslog(get_class($this).'::updatelignepaiement ');
        $this->db->begin();
		$result = $this->db->query($sql);
		if (!($result))	{
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this).'::updatelignepaiement '.$this->error);
			$this->db->rollback();
			return -1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'paiement_facture set ';
		$sql .= "amount = '".$objp->totalamount."'";
		$sql.= " WHERE 	fk_paiement = '".$objp->id."'";
		$sql .= " and fk_facture = '".$fk_acompte."'";
		dol_syslog(get_class($this).'::updatelignepaiement ');
		$result = $this->db->query($sql);
		if (!($result))	{
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this).'::updatelignepaiement '.$this->error);
            $this->db->rollback();
			return -1;
		}
		 $this->db->commit();
		
		
	} //updatelignepaiement 
	function delete_paiement($line,  $fk_facture,   $fk_soc_rem_execpt,  $user=0, $notrigger=0)
	{
		global $user, $bull;

		$objpmt = new Paiement($this->db);
		$objfct = New Facture ($this->db);
		$objpmt->fetch($line->fk_paiement);
		$objfct->id = $fk_facture	;	
		$objfct->set_unpaid($user)	;
		$ret = $objpmt->delete ($user, 0);
		if ($ret >=0)  	
			//* supression de la remise execptionnelle	
			$this->delete_paiem_rem_except($line->pt, $fk_soc_rem_execpt );
		unset ($objpmt);
		unset ($objfct);
		return $ret;
		
	} //delete_paiement
	function updateRemExcept($rem)
	{
	       // Clean parameters
		if (empty($rem->amount_ht)) $rem->amount_ht = 0;
		if (empty($rem->amount_tva)) $rem->amount_tva = 0;
		if (empty($rem->amount_ttc)) $rem->amount_ttc = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."societe_remise_except";
        $sql.= " SET   amount_ht = '".$rem->amount_ht."', amount_tva = '".$rem->amount_tva."', amount_ttc = '".$rem->amount_ttc."' ";
        $sql.= " WHERE rowid = '".$rem->id."'";
        dol_syslog(get_class($this)."::updateRemExcept ");
		$this->db->begin();
        $resql=$this->db->query($sql);
        if ($resql)        {

//            $this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."societe_remise_except");
			$this->db->commit();
           return $this->id;
        }
        else        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::updateRemExcept ".$this->error, LOG_ERR);
            $this->db->rollback();
             return -1;
		}
	} // updateRemExcept


	/**
	* Cherche les paiements d'une facture
	*
	* @param	int $id_fact 	identifant de la facture	
	*
	* @return int
	*/
	function Is_FacturePaye($IdFact, $filtre = '')
	{
		$sql = 'SELECT pf.fk_facture';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf'; // We keep link on invoice to allow use of some filters on invoice
		$sql .= ' WHERE pf.fk_facture = '.$IdFact;
		if ($filter) $sql .= ' AND '.$filter;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i = 0;
			return $this->db->num_rows($resql);
		}
		else
		{
			$this->error = $this->db->error();
			dol_syslog(get_class($this).'::Is_FacturePaye Error '.$this->error.' -', LOG_DEBUG);
			return -1;
		}
 
	}//CherchePmtparFacture
	
	/**
	* Modifier le montant du paiement supprimée de la remise exceptionnelle du client, pour payer la facture du bulletin/Contrat
	*
	* @param float 	$pt 							montant de la facture
	* @param int		$fk_soc_rem_execpt	identifiant de la remise exceptionnelle
	*/
	function delete_paiem_rem_except($pt, $fk_soc_rem_execpt )
	{
		global $bull;
		
		if (empty($pt)) $pt = 0;

		$sql="UPDATE  ".MAIN_DB_PREFIX."societe_remise_except";
		$sql.="  SET  amount_ttc = amount_ttc -".$pt ;
		$sql.=" WHERE rowid = '". $fk_soc_rem_execpt ."'" ;

		dol_syslog(get_class($this)."::delete_paiem_rem_except");
		$resql=$this->db->query($sql);
		if (!$resql)		{
		/*	$obj = $this->db->fetch_object($resql);
			if ($obj->nb > 0)			{
				$this->error='Pas de remise à cette adresse';
				return -2;
			}			
		}
		else	{
		*/
			dol_print_error($this->db);
			return -1;
		}
	
		$sql="DELETE FROM  ".MAIN_DB_PREFIX."societe_remise_except";
		$sql.=" WHERE amount_ttc between -0.009 and 0.009 and rowid = '". $fk_soc_rem_execpt."'" ;

		dol_syslog(get_class($this)."::delete_paiem_rem_except");
		$resql=$this->db->query($sql);
		if (!$resql)	{
		/*	$obj = $this->db->fetch_object($resql);
			if ($obj->nb > 0)	{
				$this->error='Pas de remise à cette adresse';
				return -4;
			}
		}
		else	{
		*/		
		dol_print_error($this->db);
			return -3;
		}		
		$ret = $bull->update_champs( "fk_soc_rem_execpt", '');
		return $ret;			
	} // delete_paiem_rem_except

	/*
	*	crée ou modifie la commande/facture  générée par le bulletin/Contrat
	*	Fonctions proches et donc regroupées dans une seule fonction, parammétrée par nomclasse
	*
	*	@param	string			$nomclasse		commande ou facture
	*	@param	objet bulletin	@bull				bulletin à facturer
	*
	*	@retour	int	-1 erreur sur création de facture/commande
	*						-3	erreur sur  validation de facture/commande
	*						>-900 récupère le retour de Traite_ligne_cmd_fact pour chaque ligne de bulletin 
	*						1 facture/commande correctement créée
	*						0 Traite_cmd_fact_complete avortée
	*/
	function Traite_cmd_fact_complete ($nomclasse, $bull)
	{
		global $user, $langs, $conf;
		global $gl_error; // utilise dans FactureBulletin
 
		$retour = 0;
		// Particularisation du traielent facture/commande
		if ($nomclasse == 'Facture') 
		{
				$nomclassedet = 'FactureLigne';
				$fk_champdet = 'fk_linefct';
				$fk_nom_champ = 'fk_facture' ;
		}
		elseif ($nomclasse == 'Commande') 
		{
			$nomclassedet = 'OrderLine';
			$fk_champdet = 'fk_linecmd';
			$fk_nom_champ = 'fk_cmd' ;
		}
		else $fk_nom_champ = '' ;
		
		
		$v_nomclasse=strtolower($nomclasse);
		$fk_objet = 'fk_'.$v_nomclasse;
		$fk_objetdet = 'fk_line_'.$v_nomclasse;
		$ref_objet = 'ref_'.$v_nomclasse;
		$tabledet = $v_nomclasse.'det';
		$flg_creation = false;
		
		// Création/maj Facture/Commande - récup des clés dans le bulletin
		$objet = new $nomclasse($this->db);	
		// facture ou commande à créer 
		if (empty($bull->{$fk_objet})  or $bull->{$fk_objet} <=0)		
		{
			$flg_creation = true;
			$this->interface_bull_cmd_fact($fk_objet,  $bull, $objet, $nomclasse, $nomclassedet);
			$ret = $objet->create($user, 1);
			if ($ret <0) 
			{
				$gl_error = $ret;
				return  -1;
			}
			// Récupération des clés étrangères
			$bull->{$fk_objet} = $objet->id;
			$bull->update_champs($fk_nom_champ,$objet->id);
			// recherche des identifiants des lignes de commandes/facture
			if (!empty($bull->lines)) {			
				foreach ($bull->lines as $bullline)
				{
					if ($bullline->action != 'X' and $bullline->action != 'S' and ($bullline->type_enr == $bullline->LINE_ACT or $bullline->type_enr == $bullline->LINE_BC ) )	
					{
						// activité ou matériel loué
						$idobjdet = $bullline->rechercheIdCmdFactDet ($tabledet, 'fk_'.$v_nomclasse, $objet->id);
						$bullline->{$fk_objetdet} = $idobjdet ;	
						if ($idobjdet > 0) $bullline->update_champs($fk_champdet,$idobjdet );
					}
				}//Foreach
			}
			// lien Dolibarr Commande-facture  Bulletin
			$this->AjoutElement($bull, $bull->{$fk_objet}, $v_nomclasse);				
		}
		else 
		// facture existante à mettre à jour
		{		
//CCA			
/*	 à revoir au moment ou on reprendra le bulletin de groupe
		if ($bull->flggoupe == true) 
					$objet->fetch_bull_group_fact();
			else
*/

			$objet->fetch($bull->{$fk_objet});
			$objbroullon = $objet->brouillon;
			$objet->brouillon = 1;
			$objet->statut = 0;
			//$objet->id = $bull->{$fk_objet};
			$nb = 0;	
				
			if (!empty($bull->lines)) {
				foreach ($bull->lines as $bullline)		
				{
					$nb++;	
					if (($bullline->action != 'X' and $bullline->action != 'S') and ( $bullline->type_enr == $bullline->LINE_ACT or $bullline->type_enr == $bullline->LINE_BC  ) )
					{
						$ret = $this->Traite_ligne_cmd_fact( $bullline, $nomclasse,$nomclassedet, $fk_objet, $fk_objetdet, $fk_champdet , $objet, $bull, $nb);
					}
					if ($ret < 0) return -1000+ (int)$ret;
				} //foreach
			}
			
			// traitement de la ligne de remise fixe
			$this->Traite_RemFix( $nomclasse,$nomclassedet, $fk_objet, $fk_objetdet, $fk_champdet , $objet);
			

			$objet->brouillon = $objbroullon ;
		}

		// GEstion Commande/Facture
		$retour = 1;
		$objbroullon = $objet->brouillon;
		$objet->brouillon = 1;
		$objet->statut = 0;
		// mettre à jour le total dans la facture/commande		
		$objet->update_price();
		//if ($ret < 0) return -2;	
		
		// refaire la facture si modification de modèle pdf_add_annotation
		if ($nomclasse == "Facture") $modelpdf = $bull->RechercheModelFactCmd("Facture");
		if ( $modelpdf  <> $objet->model_pdf )  {
			// mettre à jour le champ modele de la facture
			$objet->model_pdf = $modelpdf;
			$objet->update( $user);
			// refaire la facture papier
			$objet->generateDocument($modelpdf, $langs);
		}
		//('', $outputlangs);
		$this->db->commit();
		if ($nomclasse == 'Commande' ) 
		{
			if (stripos($_SERVER["PHP_SELF"] , 'paymentok.php') === false  and stripos($_SERVER["PHP_SELF"] , 'interface_99_modcglinscription_cglinscription.class.php' )=== false  )
				$ret = $objet->valid($user,0);
		}
		elseif ($nomclasse == 'Facture' ) 
		{
			$ret = $objet->validate($user,'', 0); 
			if ($ret < 0) return -3;
			// passer la commande correspondante à Facturée
			$cmd = new Commande($this->db);
			$cmd->fetch($bull->fk_commande);
			$cmd->classifyBilled( $user);
		}
		if ($ret > 0) 		
		{
			$bull->action = '' ;
			$bull->updateaction ('');
		}

		// RECUPERATION DES PAIEMENTS en cas de FACTURATION		
		$paiement = new DiscountAbsolute ($this->db);
		$flFactAvecPmt = false;
		if ($retour >= 0 and $nomclasse == 'Facture')
		{
			$error = 0;
			$retfac = $objet->fetch($bull->fk_facture);
			$objacpt = new Facture ($this->db);
			if (!empty($bull->fk_acompte)) {
				$ret = $objacpt->fetch($bull->fk_acompte);
				if ($ret <=0) {
					dol_syslog(get_class($this)."::". $nomclasse.": ErrFactAcpt ", LOG_ERR);
					$error++;
				}
			}
			// Récupération du paiement direct si existe
			if ($error == 0 and (!empty($bull->fk_acompte)))  
			{
				
				$flFactAvecPmt = true;
				// recherche de l'acompte et recup montant et lier à la facture
				// Lien entre Acompte et facture payée par l'acompte
				if ( $objacpt->total_ttc != 0 )
				{		
					/// Récupération du paiement direct si existe
					// Créer la remise pour pouvoir payé avec acompte
					if (empty($bull->fk_soc_rem_execpt)) 
					{
						$ret1 = $this->create_soc_rem_exectp($objacpt);
						$bull->fk_soc_rem_execpt = $ret1;
					}		

					if ($bull->fk_soc_rem_execpt > 0)  
					{
						$ret = $paiement->fetch('',$bull->fk_acompte);

						// créer l'en dans société_remise execptionelle et lier le paiment a la facture nouvellement créée
						if (empty($paiement->fk_facture) or $paiement->fk_facture == 0)  
						{
							$ret = $paiement->link_to_invoice('',$bull->fk_facture);
							if ($ret <=0) 
							{
								$error++;
								dol_syslog(get_class($this)."::". $nomclasse."ErrFactAcptRemExpNok ", LOG_ERR);
							}
						}
					}
				}
			}		
			/// Récupération du-des paiements Stripe si existent
			if ($conf->stripe->enabled and $error == 0) 
			{
				// Récupérer les paiements sur acomptes stripe				
				unset($bull->lines_stripe);
				$ret = $bull->fetch_lines_stripe ();
				if (!empty($bull->lines_stripe)) 
				{				
					foreach ($bull->lines_stripe as $bullstripe) 
					{				
						if ( $bullstripe->action <> 'X' and $bullstripe->action <> 'S' and !empty($bullstripe->fk_paiement) ) 
						{							
							// Créer la remise pour Acompte Stripe
							$ret = $objacpt->fetch($bullstripe->fk_acompte);						
							if (empty($bullstripe->fk_soc_rem_execpt)) 
							{
								$ret1 = $this->create_soc_rem_exectp($objacpt);
								$bullstripe->fk_soc_rem_execpt = $ret1;
								$bullstripe->update_champ ( 'fk_agsessstag',$bullstripe->fk_soc_rem_execpt);
							}		

							$ret = $paiement->fetch($bullstripe->fk_soc_rem_execpt);
							if ($ret >=0) 
							{
								if (empty($paiement->fk_facture) or $paiement->fk_facture == 0)  
								{
									$ret1 = $paiement->link_to_invoice('',$bull->fk_facture);
								}
							}
							if ($ret <0 or ($ret >=0 and $ret1 <0)) 
							{
								$error++;								
								dol_syslog(get_class($this)."::". $nomclasse.$langs->trans("ErrIdFactPerdu").':'.$langs->trans("IdentPaiemntStripe",$paiement->id), LOG_ERR);
							}
							$flFactAvecPmt = true;
						}
					} //foreach
				}
			}
		}
		unset ($objet);
		unset ($paiement);
		unset ($cmd);
		return $retour;			
	}//Traite_cmd_fact_complete

	/*
	*	crée ou modifie la facture  générée par le bulletin/Contrat Annulé par le Client
	*
	*	@param	objet bulletin	@bull				bulletin à facturer
	*
	*	@retour	int	-1 erreur sur création de facture
	*						-3	erreur sur  validation de facture
	*						>-900 récupère le retour de Traite_ligne_cmd_fact pour chaque ligne de bulletin 
	*						1 facture correctement créée
	*						0 Traite_fact_Produit_Frais_Annulation avortée
	*/
	function Traite_fact_Produit_Frais_Annulation ( $bulletin)
	{
		global  $user, $langs, $conf;	
		// global $tbrowid;
		global  $closeAbandon, $arrayreasons;					

		//if (!empty($bulletin-> fk_acompte)) return;
		$wf = new CglCommunLocInsc ($this->db);
		
		// Recherche si paiement
		$flAvecPaiement = false;
		if ($bulletin->TotalPaimnt() <> 0 ) $flAvecPaiement = true;
		if ($bulletin->TotalPaimnt() == 0 and $bulletin->ExistPmtNeg() ) $flAvecPaiement = true;
		$wacpt = new Facture($this->db);
		$MontPaiement = $bulletin->TotalPaimnt() ;

		// Supression demande stripe non payée	
		if (!empty($bulletin->lines_stripe))
		{		
			foreach($bulletin->lines_stripe as $LineBullStripe)
				{ 
					$wacpt->fetch($LineBullStripe->fk_acompte);
					if ($this->Is_FacturePaye($LineBullStripe->fk_acompte) == 0 and $LineBullStripe->action != 'X' and $LineBullStripe->action != 'S' ) 
					{
						$wacpt->set_unpaid($user);
						$wacpt->delete($user);
						$LineBullStripe->update_champ('fk_facture', 0, 'action', 'X');
						$LineBullStripe->fk_acompte = 0;
						$LineBullStripe->action = 'X';
					}
					else
					{
						$flAvecPaiement = true;
						// double le montant du paiement ==> erreur 
						// $MontPaiement += $wacpt->total_ttc;
					}
			}// foreach			
		}

		// Recherche si bulletin avec activité
		$flAvecActivite = false;
		if (!empty($bulletin->lines))		
		{		
			foreach($bulletin->lines as $LineBull)
			{ 
				if ($LineBull->type_enr == 0 and $LineBull->action != 'S' and $LineBull->action != 'X') 
				{
					$flAvecActivite = true;					
				}
			} //foreach
		}
		
		// SI  (avec  activité, avec  facture, avec paiement et Archive et Paiement = facturé )
		// Création d'une facture spécifique			
		//	supprimer les lignes d'activité du $bulletin en cas de modification de facture existante
		// Création d'une ligne LINE_ACT dans bull_line afin de créer la ligne de facture sur Acompte non remboursé et facturation 
		if (empty($bulletin->fk_facture) and $flAvecPaiement )
		{
			// Si montant paye positif créer Création d'une ligne LINE_ACT 
			$bulldet = new BulletinLigne ($this->db);
			$bulldet->type = $bulletin->type;
			$bulldet->type_enr = $bulldet->LINE_ACT;
			$bulldet->fk_bull = $bulletin->id;
			$bulldet->qte = 1;
			$bulldet->action = 'A';
			$bulldet->id_act = $conf->global->PROD_ACOMPTE_ACQUIS;
			$bulldet->fk_service = $conf->global->PROD_ACOMPTE_ACQUIS;
			$bulldet->fk_produit = $conf->global->PROD_ACOMPTE_ACQUIS;
			
			$bulldet->fk_code_ventilation = $wf->RechVentilationbyService($conf->global->PROD_ACOMPTE_ACQUIS, 'Loc');
			//$w1 = new CglFonctionDolibarr($this->db);
			//$bulldet->taux_tva = $w1->taux_TVAstandard();
			$wp = new Product($this->db);
			$wp->fetch($bulldet->id_act);
			$bulldet->taux_tva = $wp->tva_tx; 
			unset($wp);
			
			$bulldet->montant = $MontPaiement;
			$bulldet->pu =  $MontPaiement  ;
			
			// Trouver la description du produit spécifique
			$wprod = new Product ($this->db);
			$wprod->fetch($conf->global->PROD_ACOMPTE_ACQUIS);
			$bulldet->activite_label = $wprod->label;
			unset($wprod);
			
			// Inactiver les lignes d'activité qui ne viendront pas dans une facture de Acompte non remboursés
			if (!empty($bulletin->lines)) 
			{
				foreach ($bulletin->lines as $bullline)
				{
					if ($bullline->type_enr == $bullline->LINE_ACT) $bullline->action = 'X';
				}//foreach
			}

			$bulletin->lines[] = $bulldet;

			$retfac = $this->Traite_cmd_fact_complete('Facture', $bulletin);
			// Passage facture à payée puisque total facture Acompte non remboursé = total paiement acompte
			// Recherche objet facture FA nouvellement créée
			$wfct= new Facture($this->db);
			$ret = $wfct->fetch($bulletin->fk_facture);
			$ret = $wfct->set_paid ($user);
			$bulletin->regle_archive();

			// restitution du $bulletin dans l'état original
			$bulletin->fetch($bulletin->id);
		}
		
	/*
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
	}//Traite_fact_Produit_Frais_Annulation
	
	function interface_bull_cmd_fact($fk_objet, $bull, $objet, $nomclasse, $nomclassedet )
	{
		
		$this->interface_bull_cmd_fact_entete($fk_objet,  $bull, $objet, $nomclasse);
		$i=0;
		if (!empty($bull->lines)) {
			foreach ($bull->lines as $bullline)
			{
				if ($bullline->action != 'X' and $bullline->action != 'S' and ($bullline->type_enr == $bullline->LINE_ACT or $bullline->type_enr == $bullline->LINE_BC))
				{
					$objetline = new $nomclassedet ($this->db);
					$this->interface_bull_fact_det($bullline, $objetline,  $fk_objet, $bull);
					$objet->lines[$i] = $objetline;
					$i++;
				}
			}//foreach
		}
	
	} // interface_bull_cmd_fact
	function interface_bull_cmd_fact_entete($fk_objet,  $bull, $objet, $nomclasse)
	{
		global  $langs;	
		// entête de facture
		$objet->socid = $bull->id_client;		
		$objet->ref_ext =  $bull->ref;
		$objet->date = dol_now('tzuser');
		//$objet->date = dol_stringtotime($bull->datec);
		$objet->ref_client = $bull->ref_client;
		$objet->demand_reason_id = $bull->fk_origine;
		$objet->model_pdf = $bull->RechercheModelFactCmd($nomclasse);
		if ($bull->type == 'Insc') $objet->note_private = $langs->trans('Bulletin') ;
		else $objet->note_private = $langs->trans('Location') ;
		$objet->note_private .= ' : '.$bull->ref;		

		if ($bull->type == 'Insc') $note_public = $langs->trans('Bulletin');
		else  $note_public = $langs->trans('Contrat ');
		$objet->note_public.=':'.$bull->ref;
		
		$objet->brouillon 		=	1;
		$objet->statut 		=	0 ;
	} //interface_bull_cmd_fact_entete

	function interface_bull_fact_det($bullline, $objetline,  $fk_objet, $bull)
	{
		global  $langs, $conf;		
		
		$w1 = new CglFonctionDolibarr($this->db);		
		$wc1 = New CglCommunLocInsc($this->db);
		
		$objectid = $bull->{$fk_objet};
		$objetline->{$fk_objet}	= $objectid;

		if ($bullline->fk_produit == $conf->global->PROD_ACOMPTE_ACQUIS)
		{
			$wprod=new Product ($this->db);
			$wprod->fetch();
			
			$objetline->desc 	= $wprod->description;
			//$objetline->desc 	= $wprod->label;
		}
		else
		{
			if ($bull->statut == $bull->BULL_ANNULCLIENT) $objetline->desc = $this->LabelCmdFactProdSpe ($bullline);
			elseif ($bull->type == 'Loc') $objetline->desc 			= $this->LabelCmdFactDetLoc ($bullline);
			else  $objetline->desc 			= $this->LabelCmdFactDet  ($bullline);
		}
		
		$objetline->fk_product 	= $bullline->fk_produit;
		$objetline->product_type 	= 1;
		$objetline->qty 			= ($bullline->qte == 0)? 1 : $bullline->qte;
		$objetline->total_ht 		= ($bullline->type_enr == 0) ? $bullline->pu : 0 - (int)$bullline->mttremfixe;
		$objetline->remise_percent = $bullline->remise_percent;
		$objetline->tva_tx = ($bullline->type_enr == 0) ? $bullline->taux_tva :$bull->TauxTVARemiseFixe();

		$objetline->fk_code_ventilation = $wc1->RechIdVentilationbyCode($bullline->fk_code_ventilation,  $bull->type);
		if (empty($objetline->fk_code_ventilation)) $objetline->fk_code_ventilation = 0;
		unset($wc1);
		$ptht = $bullline->pu * (int)$bullline->qte * (100 - (int)$bullline->remise_percent) /100;

		if ($objetline->tva_tx == 0 ) {
			$objetline->subprice 		=   sprintf('%.2f',($bullline->type_enr == 0) ? $bullline->pu : 0 - (int)$bullline->mttremfixe);
			$objetline->total_ht 		=   sprintf('%.2f',($bullline->type_enr == 0) ? $ptht : 0 - (int)$bullline->mttremfixe);
			if (($bullline->type_enr == 0) ) $objetline->total_ttc 	=  sprintf('%.2f', $bullline->calulPtAct( $bull->type_session_cgl,$bullline->pu,$bullline->qte ,$bullline->remise_percent));
			else $objetline->total_ttc 	=   sprintf('%.2f', 0 - (int)$bullline->mttremfixe);
			$objetline->total_tva = 0;
		}
		else {
			// bullline->pu est du TTC	, subprice estle HT
			$objetline->subprice  =   sprintf('%.2f',($bullline->type_enr == 0) ? $bullline->pu : 0 - (int)$bullline->mttremfixe);
			$objetline->subprice  =    sprintf('%.2f',$objetline->subprice * 100 / (100 + (int)$objetline->tva_tx));
			$objetline->total_ht  =   sprintf('%.2f',($bullline->type_enr == 0) ? $bullline->calulPtAct( $bull->type_session_cgl,$objetline->subprice,$bullline->qte ,$bullline->remise_percent) : $objetline->subprice);
			
			$objetline->total_ttc 	=   sprintf('%.2f',($bullline->type_enr == 0) ? $bullline->calulPtAct( $bull->type_session_cgl,$bullline->pu,$bullline->qte ,$bullline->remise_percent): 0 - (int)$bullline->mttremfixe );
			$objetline->total_tva 	=  sprintf('%.2f', $objetline->total_ttc * $objetline->tva_tx /(100 + (int)$objetline->tva_tx) );
		}
			
		$objetline->rang 			= $bullline->rangdb;

	} //interface_bull_fact_det

	/*
	* Construction Libelle de la ligne de commande/facture
	*
	* @param object $line	ligne du bulletin concerné
	* retour string	label de la ligne de commande/facture
	*
	*/
	function LabelCmdFactDet($line)
	{	
		global  $langs;
		
		$data = new CglFonctionCommune ($this->db);
		if ($line->type_enr == 0) {
			$label =  $line->activite_label . ' du '.$data->transfDateFrCourt($line->activite_dated).' - lieu : '.$line->activite_lieu;	
			if (!empty($line->NomPrenom)) $label .= ' pour '.$line->NomPrenom;
			if (!empty($line->act_moniteur_prenom) or !empty($line->act_moniteur_nom))
				$label 	.=  ' - '.$langs->trans('LibEncReal').' '.$line->act_moniteur_prenom. ' '.$line->act_moniteur_nom ;
		}
		elseif ($line->type_enr == $line->LINE_BC) {
			 $label=$line->textnom.' '.$line->textremisegen;
			unset ($data);
		}
		return $label;
	} //LabelCmdFactDet
	function LabelCmdFactDetLoc($line)
	{	
		global  $langs;
		if ($line->type_enr ==  0 ) {
			if (!empty($line->materiel )){
				$label =  $langs->trans('Materiel',$line->materiel) ;
				if (!empty($line->marque )) $label .=  '  ('.$langs->trans('Marque',$line->marque).')' ;			
				else
					$label .=  '  ('.$langs->trans('Ref',$line->refmat).')' ;
				}
			elseif (empty($line->materiel)  )
			{		
				$label  = $langs->trans('RefMat',$line->refmat);
				//if ( !empty($line->marque )) $label .= '  ('.$langs->trans('Marque',$line->marque).')';
			}		
			if (!empty($line->NomPrenom)) $label .= ' '.$langs->trans('Pour',$line->NomPrenom);

			if ( substr($line->dateretrait, 4,1) == '-') $jourmois = substr($line->dateretrait, 8,2).'/'.substr($line->dateretrait, 5,2);
			else $jourmois = substr($line->dateretrait, 0,5);
			if (isset($line->dateretrait) and $line->dateretrait > 0 )  				
				$strdateretrait = $jourmois   ;

			if ( substr($line->datedepose, 4,1) == '-') $jourmois = substr($line->datedepose, 8,2).'/'.substr($line->datedepose, 5,2);
			else $jourmois = substr($line->datedepose, 0,5);
			if (isset($line->datedepose) and $line->datedepose > 0 ) 
				$strdatedepose =  $jourmois   ;

			if (empty($strdatedepose ) and empty($strdateretrait )) 
				{
					$label .= '';
					}
			elseif (empty($strdatedepose ) and !empty($strdateretrait )) {
				 if (!empty($label)) $label .= $langs->trans('le ');
				 else $label =  $langs->trans('Le ');
				 $label .= ' '.$strdateretrait;
				 }
			elseif (!empty($strdatedepose ) and empty($strdateretrait ))
				{
				 if (!empty($label)) $label .= $langs->trans('le ');
				 else $label .=  $langs->trans('Le ');
				 $label .= ' '.$strdatedepose;
				 }
			elseif ($strdatedepose == $strdateretrait )
				{
				 if (!empty($label)) $label .= ' '.$langs->trans('endate');
				 else $label =  $langs->trans('Endate');
				$label .= ' '.$strdateretrait;
				}
			else 
				{
				 if (!empty($label)) $label .= ' '.$langs->trans('retrait');
				 else $label = $langs->trans('Retrait');
				$label .= ' '.$strdateretrait;
				$label .= ' '.$langs->trans('depose').' '.$strdatedepose;
			}
		}
		elseif ($line->type_enr ==  2) {
			 $label=$line->textnom.' '.$line->textremisegen;
		}		
		return $label;
	} //LabelCmdFactDetLoc

	function LabelCmdFactProdSpe($line)
	{	
		global  $langs;
		if ($line->type_enr ==  0 ) {

			 $label=$line->activite_label;
		}		
		return $label;
	} //LabelCmdFactProdSpe
	
	 /*
	* param 	$nb 	indique le rang de la ligne
	*/ 	
	function Traite_ligne_cmd_fact( $bullline, $nomclasse, $nomclassedet,  $fk_objet, $fk_objetdet, $champdet, $objet, $bull, $nb)
	{
		global   $user, $langs;
		global $gl_error_fk; // Utiliser dans this->FactureBulletin 
		global $gl_facture; // Utiliser dans this->FactureBulletin 
		static $st_PasseLigSup = 0;

		$retour = 0;			
		$flnvobj = false;
		if (empty($objet)) {
			$flnvobj = true; 
			$objet  = new $nomclasse($this->db);
			$ret = $objet->fetch($bull->{$fk_objet});
			if ($ret < 0) {
				unset ($objet);
				$gl_error_fk = $bull->{$fk_objet};
				return -1;
			}
		}
		$gl_facture = $objet->ref;
		/* Mettre le statut de la commande à 0 pour pouvoir faire les delete, add et update de ligne */		
		if ($flnvobj) { 
			$objet->statut = 0;
			$brouiollonsav = $objet->brouillon;
			$objet->brouillon = 1;
		}		

		if ((($bullline->action == 'S' or $bullline->action == 'X')  and $nomclasse == 'Commande'  )  or ($bullline->action == 'X'  and $nomclasse == 'Facture' ))		{
			if ($nomclasse == 'Commande' and !empty($bull->fk_facture)) {
				if ($st_PasseLigSup == 0 ) { 
					if ($bull->type == 'Insc') setEventMessage($langs->trans('AlModifFactureInsc'), 'warnings');
					elseif  ($bull->type == 'Loc') setEventMessage($langs->trans('AlModifFactureLoc'), 'warnings');
					$st_PasseLigSup++;
				}
			}
			// supprime ligne commande/facture
			if (!empty($bullline->{$fk_objetdet})) $ret = $objet->deleteline($bullline->{$fk_objetdet});
			if ($ret <0 ) {			
				if ($flnvobj) unset ($objet);	
				$gl_error_fk = $bullline->id;
				return -2;
			}
			$bullline->{$fk_objetdet} = 0;
			$ret = $bullline->update_champs($champdet,0);
		}
		else 	{
			// CALCUL TOTAL et TVA
			if ($bullline->type_enr == 0) {
			
				//Calcul 
				// bullline->PU = P toute taxe hors remise
				//$pu qui va dans facture : PVHT sans remise
				// PT = prix hors taxe et avec remise PU*(100-rem)/(100+tx_TVA)

				$tva = $bullline->taux_tva;
				$pu =  sprintf('%.2f', $bullline->pu*100/(100 + (int)$tva) );// la location est toujours TTC
				$pt =  sprintf('%.2f',$bullline->pu * (int)$bullline->qte * (100- (int)$bullline->remise_percent)/(100 + (int)$tva));
				$ptht =  sprintf('%.2f',$bullline->pu * (int)$bullline->qte * (100- (int)$bullline->remise_percent)/(100));
 
				if ($tva == 0) 	$typetva='TTC';
				else 	$typetva='HT';
				$label = ($bull->type == 'Loc') ? $this->LabelCmdFactDetLoc ($bullline): $this->LabelCmdFactDet ($bullline);	 
			}
			elseif ($bullline->type_enr == $bullline->LINE_BC) 
			{ 
				if ($bullline->mttremfixe == 0 ) return;
				$tva = $bull->TauxTVARemiseFixe();
				if ($tva > 0) {
					$typetva='HT';
					$pt = (-1) * $bullline->mttremfixe;
					$pu = $pt *100 / (100 + (int)$tva);
					$pu =  sprintf('%.2f', $pu );					
				}
				else {	
					$typetva='TTC';
					$pt =(-1) * $bullline->mttremfixe;
					$pu =(-1) * $bullline->mttremfixe;
				}	
				//$label = $bull->lbremfixe;
				$label = ($bull->type == 'Loc') ? $this->LabelCmdFactDetLoc ($bullline): $this->LabelCmdFactDet ($bullline);	 	
				//$pt = $pu;
				$bullline->qte = 1;
				$bullline->rangdb = 99;				
			}
			if (empty($bullline->{$fk_objetdet}) )	{
					// CREATION LIGNE DE COMMANDE/FACTURE

				// transformer le code ventilation en rowid
				if ($bull->type == 'Loc') $fk_produit = $bullline->fk_produit ;
				else $fk_produit = $bullline->fk_activite;
				/* on ne lance les triggers que sur la première ligne */
				if ($nb == 1) $fltrigger = 1; else $fltrigger = 1;
				if ($nomclasse == 'Commande') {
					$ret = $objet->addline($label, $pu, $bullline->qte,$tva,0,0,$fk_produit,	$bullline->remise_percent,'','',$typetva, $ptht, '','',1,$bullline->rangdb,0,0,null,0,'',0, null, '',0,0,'',0,$fltrigger);
				}	
				elseif ($nomclasse == 'Facture') 
//					$ret = $objet->addline($label, $pu, $bullline->qte,$tva,0,0,$fk_produit,	$bullline->remise_percent,'','',$codeventilation,'', '',$typetva, $ptht,1, $bullline->rangdb,0,0,null,null, null, 0,'',0, 100,'', 0, $fltrigger);				
					$ret = $objet->addline($label, $ptht, $bullline->qte,$tva,0,0,$fk_produit,	$bullline->remise_percent,'','',$codeventilation,'', '',$typetva, $pu,1, $bullline->rangdb,0,0,null,null, null, 0,'',0, 100,'', 0, 0,'',$fltrigger);				
					if ($ret < 0) {
					if ($flnvobj) unset ($objet);
					$gl_error_fk = $bullline->id;
					return -100 + (int)$ret;
				}
				else {	
					$bullline->{$fk_objetdet} = $ret;
					/*$ret = $objet->fetch($bull->{$fk_objet});
					if ($ret < 0) {
						if ($flnvobj) unset ($objet);
						return -120 + $ret;
					}*/
					//$this->MajCodeVentilExtra($bullline->{$fk_objetdet}, $bullline->fk_code_ventilation);

					$ret = $bullline->update_champs($champdet,$bullline->{$fk_objetdet});
					
				}	 // Suite OK de addline
			} // Fin de création
			else {
				// MISE A JOUR LIGNE DE COMMANDE/FACTURE
/*
CMD 	function updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1=0,$txlocaltax2=0, $price_base_type='HT', $info_bits=0, $date_start='', $date_end='', $type=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_option=0, $notrigger = 0)
FACT 	function updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1=0, $txlocaltax2=0, $price_base_type='HT', $info_bits=0, $type= self::TYPE_STANDARD, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_option=0, $situation_percent=0, $fk_unit = null, $notrigger = 0)
*/

//taitement de la ventilation 
//if ($nomclasse == 'Facture') $ret = $wc1->updatefacventilation( $bullline->{$fk_objetdet} ,$codeventilation);

				/* on ne lance les trigger que sur la première ligne */
				if ($nb == 1) $fltrigger = 1; else $fltrigger = 1;

 
				if ($nomclasse == 'Commande') {	
					if ($bullline->type_enr == $bullline->LINE_BC) {
						$ret = $this->MajPrixCmdFactDet($bullline->{$fk_objetdet}, price2num($pu), price2num($ptht), price2num($pt), $label,   $nomclasse, $tva);
					}
					elseif ($bullline->type_enr == $bullline->LINE_ACT) 
						$ret = $objet->updateline($bullline->{$fk_objetdet}, $label, $pu,  $bullline->qte,$bullline->remise_percent,$tva,0,0,$typetva,0,'','',1,0,0, null,0,'',0, 0, $fltrigger);
						}
				elseif ($nomclasse == 'Facture')  {
					if ($bullline->type_enr == $bullline->LINE_BC) {
							$ret = $this->MajPrixCmdFactDet($bullline->{$fk_objetdet}, price2num($pu), price2num($ptht), price2num($pt), $label,   $nomclasse, $tva);
					
					}
					elseif($bullline->type_enr == $bullline->LINE_ACT) {
						$ret = $objet->updateline($bullline->{$fk_objetdet}, $label, $pu,  $bullline->qte, (int) $bullline->remise_percent,'','',$tva,0,0,$typetva,0,1,0,0, null,0,'',0,0, 100, null, 0, $fltrigger);
						$array_option = array();
						$array_option[] = 's_fk_ventil=>'.$codeventilation;
						// à l'arrache, car je ne connait pas la syntaxe pour lancer une insertion dans facturedet_extrafields
					}
				}
				if ($ret < 0) {
					if ($flnvobj) unset ($objet);
					$gl_error_fk = $bullline->id;
					return -140 + (int)$ret;
				}
				
			}// Fin de MAJ
			//$this->MajCodeVentilExtra($bullline->{$fk_objetdet}, $bullline->fk_code_ventilation);

			// dans Commande, le prix total ne se met pas à jour en standard
			//if ($nomclasse == 'Commande') {
			/*$ret = $this->MajPrixCmdFact($bullline->{$fk_objetdet}, $pu, $pt,   $nomclasse);
			if ($ret < 0)  {
				if ($flnvobj) unset ($objet);
				return -160 + $ret;
			}
			*/
				
			//}// Suite OK de updatetotalcmddet
		} // Fin de ligne de bulletin à traiter	
		if ($flnvobj) {		
			// Remettre à jour le total de la facture/commande
			$ret = $objet->update_price();
			if ($ret < 0)  {
				if ($flnvobj) unset ($objet);
				return -160;
			}
			$this->db->commit();
			// valider la commande/facture			
			if ($nomclasse == 'Commande' ) $ret = $objet->valid($user,0);
			elseif ($nomclasse == 'Facture ' )$ret = $objet->validate($user,'', 0);
			unset ($objet);
			if ($ret <= 0)  {
				if ($flnvobj) unset ($objet);
				return -180 ;
			}
		}
		if ($ret >= 0 ) {
			if ($nomclasse == 'Commande') {
				if ($bullline->action == 'S') $bullline->update_champs ('action', 'X');
				elseif ($bullline->action <> '' and $bullline->action <> 'X') $bullline->update_champs ('action', ''); 
			}
		}
		return $retour;		
	} // Traite_ligne_cmd_fact	
	
	function rechercheDerLigCmdFact ($objet)
	{
		$wline = null;
		$wid = 0;
		if (!empty($objet->lines)) {
			foreach ($objet->lines as $line)
			{
				if ($wid < $line->rowid) 
				{
					$wline = $line;
					$wid=$line->rowid;
				}
			}//Foreach
		}
		return $wline;
	} // rechercheDerLigCmdFact
	function MajPrixCmdFactDet($id, 	$pu,  $pt, $ptht, $label, $nomclasse, $tva)
	{		
        $this->db->begin();
		
		if ($nomclasse == 'Commande')  $table = 'commandedet';
		elseif ($nomclasse == 'Facture')   $table = 'facturedet';
		
		if (empty($pt)) $pt = 0;
		if (empty($ptht)) $ptht = 0;
		if (empty($pu)) $pu = 0;
		if (empty($tva)) $tva = 0;
		
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$table;
        $sql.= ' SET total_ttc ="'.$pt.'"';
        $sql.= ' , total_ht ="'.$ptht.'"';
        $sql.= ' , price ="'.$pu.'"';
        $sql.= ' , label ="'.$label.'"';
        $sql.= ' , tva_tx ="'.$tva.'"';
        $sql.= ' , subprice ="'.$pu.'"';
        $sql.= ' WHERE rowid =" '.$id.'"' ;
		
        dol_syslog(get_class($this)."::". $nomclasse."MajPrixCmdFactDet ");
        $resql = $this->db->query($sql);
        if ($resql) $this->db->commit();
        else
		{
            $error++;
            $this->error=$this->db->error();
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }	
		return 0;		
	}//MajPrixCmdFactDet
	
	function MajPrixCmdFact($id, 	$pu,  $pt, $nomclasse)
	{		
        $this->db->begin();
		
		$table = strtolower($nomclasse);
		
		if ($nomclasse == 'Commande') {
			$table = 'commande';
			$champ='total_ht';
		} 
		elseif ($nomclasse == 'Facture')  {
			$table = 'facture';
			$champ='total';
		}  		
		
		if (empty($pt)) $pt = 0;
		if (empty($pu)) $pu = 0;
		
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$table;
        $sql.= ' SET total_ttc ="'.$pt.'"';
        $sql.= ' , total ="'.$pu.'"';
        $sql.= ' WHERE rowid = "'.$id.'"';
		
        dol_syslog(get_class($this)."::". $nomclasse."MajPrixCmdFact ");
        $resql = $this->db->query($sql);
        if ($resql) $this->db->commit();
        else
		{
            $error++;
            $this->error=$this->db->error();
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }		
	}//MajPrixCmdFact
	
	function update_CmdFact($obj, $nomclasse)
	{    	

        $sql = 'UPDATE '.MAIN_DB_PREFIX.strtolower($nomclasse);
        $sql.= ' SET note_public="'.$obj->note_public.'"';
        $sql.= ' WHERE rowid = "'.$obj->id.'"';
		
        dol_syslog(get_class($this)."::update_CmdFact");
        $resql = $this->db->query($sql);
        if (!$resql)		{
            $error++;
            $this->error=$this->db->error();
            dol_print_error($this->db);
            return -1;
        }		
	} // update_CmdFact			

		 	 	
	function creer_bon_commande($idcmd)
	{
		global $bull, $conf, $langs, $_SERVER;	
		$objcmded = new Commande($this->db);
		$objcmded->id = $idcmd;
		$objcmded->fetch($idcmd);

		if (stripos($_SERVER["PHP_SELF"] , 'paymentok.php') === false  and stripos($_SERVER["PHP_SELF"] , 'interface_99_modcglinscription_cglinscription.class.php' === false  ))
			return;	
		
		$ref = dol_sanitizeFileName($objcmded->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->commande->dir_output . '/' . $ref, preg_quote($ref,'/'));
		$file=$fileparams['fullname'];
		// Construire le document s'il n'existe pas
		//if (! $file || ! is_readable($file))
		//{		
			// Define output language
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$objcmded->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}	
			$result= $objcmded->generateDocument('einstein', $outputlangs, 0, 0, 0);
				
			//$result=commande_pdf_create($this->db, $objcmded, 'einstein', $outputlangs);
			if ($result <= 0)
			{
				dol_print_error($this->db,$result);
				return;
			}
			$fileparams = dol_most_recent_file($conf->commande->dir_output . '/' . $ref, preg_quote($ref,'/'));
			$file=$fileparams['fullname'];
		//}
		/* sauvegarder le nom du fichier de commande créé*/
		// Obsolete - suppression champ
		//$res = $bull->updateFicCmd($file);

		unset($objcmded);

	}//creer_bon_commande
	function update_Cmd($obj)
	{    
        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
        $sql.= ' SET note_public="'.$obj->note_public.'"';
        $sql.= ' WHERE rowid = "'.$obj->id.'"';
		
        dol_syslog(get_class($this)."::update_Cmd ");
        $resql = $this->db->query($sql);
        if ($resql) $this->db->commit();
        else
		{
            $error++;
            $this->error=$this->db->error();
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }		
	} // update_Cmd			

	function MajPrixCmdDet($id, $pt)
	{		
        $this->db->begin();

		if (empty($pt)) $pt = 0;
		
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet';
        $sql.= ' SET total_ttc ="'.$pt.'"';
        $sql.= ' , total_ht ="'.$pt.'"';
        $sql.= ' , price ="'.$pt.'"';
        $sql.= ' , subprice ="'.$pt.'"';
        $sql.= ' WHERE rowid = "'.$id.'"';
		
        dol_syslog(get_class($this)."::MajPrixCmdDet ");
        $resql = $this->db->query($sql);
        if ($resql) $this->db->commit();
        else
		{
            $error++;
            $this->error=$this->db->error();
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }		
	}//MajPrixCmdDet
	
	/*
	* Archiver un accompte
	*
	* @param	$facid	Id de l'acompte
	*
	*	@retour 	$ret	0 OK, -1 erreir
	*/
	
	function ArchiveAcompte($facid, $codeclose, $libclose)
	{
		global $user;
		
		$fact = New Facture ($this->db);
		$ret = $fact->fetch($facid);
		if ($ret > 0) {
			$ret1 = setPaid($user, $codeclose, $libclose);
			if ($ret1 < 0){
				dol_syslog('',LOG_DEBUG);
				return -1;
			}
			else return 0;
		}
		else return -2;

	}
	function FactureBulletin($bull)
	{
		global $langs, $user, $conf; 	
		global $gl_error_fk; // Valorisée dans this->Traite_ligne_cmd_fact
		global $gl_facture;// Valorisée dans this->Traite_ligne_cmd_fact
		global $gl_error; // Valorisée dans this->Traite_cmd_fact_complete
		global $gl_activite; // Valorisée dans bull->IsMoniteurAbsent
		global $rapport; // Utilisé dans facturation.php
		global $PostActivité; // Utilisé dans doc_RapportFacturation_odt.modules.php pour PostActivité
	
	
		$objcmd = new Commande ($this->db);
		$objacpt = new Facture ($this->db);
		$objfac = new Facture ($this->db);
		$w = new CglInscription($this->db);
		$retour = 0;
		$error = 0;
		
		if ($bull->statut == $bull->BULL_ANNULCLIENT )  {
				$retfac = $this->Traite_fact_Produit_Frais_Annulation( $bull);	
		}
		elseif ( $bull->facturable and $bull->TotalFac() <> 0) 
		{
/*CCA à reprendre au moment du bulletin de groupe
				$bull->flggoupe = false;	
				// traite facture groupe				
				IF ($bull->IsBullGroupe() ==  true) {
					$bull->fetch_bull_group_fact();
					$bull->flggoupe = true;	
				}
*/
				// traite facture normale
				$retfac = $this->Traite_cmd_fact_complete('Facture', $bull);
		}
		
		// POST_ACTIVITE
		//if ( !empty($bull->lines))   {
			foreach ($bull->lines as $line) {
				
				if ($line->action == 'X' or $line->action == 'S' or $line-> type_enr != 0 ) continue;
				
				if ($bull->type == 'Insc' )   {

					$cle = $line->activite_dated.$line->activite_heured.$line->act_moniteur_nom.$line->act_moniteur_prenom.$bull->tiersNom;
				}
				elseif ($bull->type == 'Loc' ) $cle = $bull->id;

				if (!isset($PostActivité[$cle]))  $PostActivité[$cle]= new stdClass();
			
				//cle = date/heure/moniteur
				$PostActivité[$cle]->ref 			= $bull->ref;
				$PostActivité[$cle]->tiersNom 		= $bull->tiersNom;
				$PostActivité[$cle]->TiersMail 	= $bull->TiersMail;
				$PostActivité[$cle]->TiersMail2 	= $bull->TiersMail2;
				if ($bull->type == 'Insc' )   {
					$PostActivité[$cle]->moniteur = ucfirst(strtolower($line->act_moniteur_prenom)).' '.ucfirst(strtolower($line->act_moniteur_nom));
					$PostActivité[$cle]->datedeb 		= $line->activite_dated;
					$PostActivité[$cle]->heuredeb		= $line->activite_heured;
				}
				else   {
					$PostActivité[$cle]->moniteur = '';
					$PostActivité[$cle]->datedeb 		= '';
					$PostActivité[$cle]->heuredeb		= '';
				}
			}
		
	
		if ($bull->TotalFac() <> 0)		{
			// recherche acompte avec fk_acompte
			//$retacpt = $objacpt->fetch($bull->fk_acompte);

			if (!empty($retfac) and $retfac >= 0 ) {
				$retfac = $objfac->fetch($bull->fk_facture);	
				$bull->fk_facture = $objfac->id;
			
				/// Verification moniteur present
				if ($bull->type == 'Insc') {

				/// Récupération des paiements 
				/// Récupération du paiement direct si existe
					$moniteurabsent = $bull->IsMoniteurAbsent();
					if ($moniteurabsent and $bull->statut <> $bull->BULL_ANNULCLIENT)  {
						if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
						$rapport[$bull->id]->ref = $bull->ref;
						$rapport[$bull->id]->type = $bull->type;
						$rapport[$bull->id]->facture = $objfac->ref;
						$rapport[$bull->id]->note .= $langs->trans("ErrFactMonAbs",$gl_activite);
						$retour = -2000;
					}				
				/// Verification code ventilation present
					$codeventilationabsent = $bull->IsCodeVentilationAbsent();
					if ($codeventilationabsent)  {
						if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
						$rapport[$bull->id]->ref = $bull->ref;
						$rapport[$bull->id]->type = $bull->type;
						$rapport[$bull->id]->facture = $objfac->ref;
						$rapport[$bull->id]->note .=  $langs->trans("ErrFactVentilAbs",$gl_activite);
						$retour = -2002;
					}
				}
				$paiement = new DiscountAbsolute ($this->db);
				if (!empty($bull->fk_soc_rem_execpt))  {
					$ret = $paiement->fetch($bull->fk_soc_rem_execpt);
					if ($ret <=0) {
						// si montant total payé positif, l'accompte n'est pas connu comme remise
						if ($bull->TotalPaimnt() != 0)			{
							$error++;
							if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
							$rapport[$bull->id]->ref = $bull->ref;
							$rapport[$bull->id]->type = $bull->type;
							$rapport[$bull->id]->facture = $objfac->ref;
							$rapport[$bull->id]->msg .= $langs->trans("ErrFactAcptRemExpNok",$objacpt->ref);
						}
					}
					// Lien entre Acompte et facture payée par l'acompte
/*					if ($error == 0 and $bull->TotalPaimnt() != 0)			
					{		
						if (empty($paiement->fk_facture) or $paiement->fk_facture == 0) 
						{
							$error++;
							if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
							$rapport[$bull->id]->ref = $bull->ref;
							$rapport[$bull->id]->type = $bull->type;
							$rapport[$bull->id]->facture = $objfac->ref;
							$rapport[$bull->id]->etat = $bull->transStrRegle();
							$rapport[$bull->id]->msg .="ErrPaimtFact".$langs->trans(($ret == -2)?"ErrIdFactPerdu":($ret == -2)?"ErrSQL":'').'----'.$langs->trans("IdentPaiemnt",$paiement->id).'';

dol_syslog('CCA - paiement complet - erreur 2', LOG_DEBUG);	
						}
					}
*/				}
				/// Récupération du-des paiements Stripe si existent
/*				if ($conf->stripe->enabled and $error == 0 and $bull->TotalPaimnt() != 0) {
					// Récupérer les paiements sur acomptes stripe
					$ret = $bull->fetch_lines_stripe ();
					if (!empty($bull->lines_stripe)) 
					{						
						foreach ($bull->lines_stripe as $bullstripe) 
						{
							if ( $bullstripe->action <> 'X' and $bullstripe->action <> 'S' and !empty($bullstripe->fk_paiement) ) 
							{			
								$ret = $paiement->fetch($bullstripe->fk_soc_rem_execpt);		
								if ($ret <0 or ($ret >=0 and (empty($paiement->fk_facture) or $paiement->fk_facture == 0))) 
								{
									$error++;
									if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
									$rapport[$bull->id]->ref = $bull->ref;
									$rapport[$bull->id]->type = $bull->type;
									$rapport[$bull->id]->facture = $objfac->ref;
									$rapport[$bull->id]->etat = $bull->transStrRegle();
									$rapport[$bull->id]->msg .="ErrPaimtFact".$langs->trans(($ret == -2)?"ErrIdFactPerdu":($ret == -2)?"ErrSQL":'').'----'.$langs->trans("IdentPaiemntStripe",$paiement->id).'';

dol_syslog('CCA - paiement complet - erreur 1', LOG_DEBUG);	
								}		
							}
						} //foreach
					}
				}
*/			
				// Vérifier que le paiement est total exactement
				$regle = $bull->CalculRegle();
				$bull->regle = $regle;
				$bull->dt_facture = $this->db->idate(dol_now('tzuser'));
				$bull->update();
				if ($bull->regle <> $bull->BULL_REMB and  $bull->regle <> $bull->BULL_PAYE and $bull->regle <> $bull->BULL_FACTURE ) {
					if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
					$rapport[$bull->id]->ref = $bull->ref;
					$rapport[$bull->id]->facture = $objfac->ref;
					$rapport[$bull->id]->etat = $bull->transStrRegle();
					$retour = -2001;
				}		
				// ensuite, traitement statut du  bulletin au statut archive et la facture  archivée
				if ($error == 0 and $retour <> -2000  and $retour <> -2001  and $retour <> -2002)		
				{	
					if ($bull->fk_facture > $bull->BULL_ENCOURS and $bull->regle <> $bull->BULL_ARCHIVE)		
					{
						$fl_MajRegler=$bull->BULL_ARCHIVE;
					}

					if ($bull->regle == $bull->BULL_PAYE or ($bull->regle == $bull->BULL_FACTURE  ))	
					{
							$ret = $objfac->set_paid($user,'',1);
							if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
							if ($ret < 0) 
							{
								$error++;
								$rapport[$bull->id]->ref = $bull->ref;
								$rapport[$bull->id]->type = $bull->type;
								$rapport[$bull->id]->facture = $objfac->ref;
								$rapport[$bull->id]->etat = $bull->transStrRegle();
								$rapport[$bull->id]->msg.= ''.$langs->trans("ErrFactPaye").'';
							}
							else 
							{
								$rapport[$bull->id]->note = $langs->trans("FactBull");
								// passer la commande à facturee
								$cmd = new Commande($this->db);
								$cmd->fetch($bull->fk_commande);
								$cmd->classifyBilled($user);
								$fl_MajRegler=$bull->BULL_ARCHIVE; 
								
								// nettoyer les acomptes impayés associée à ce BU 
								// pour tous les demandes  Stripe non payées
								if ( !empty($bull->lines_stripe)) {
									foreach ($bull->lines_stripe as $lineStripe ){ 
									//			abandonner l'acompte : 
										if (empty( $lineStripe->fk_paiement)  )
											$this->AbandonneFacture($lineStripe->fk_acompte, 'Stripe');

									} // foreach
								}

								
								// rapport
								$rapport[$bull->id]->ref = $bull->ref;
								$rapport[$bull->id]->type = $bull->type;
								$rapport[$bull->id]->facture = $objfac->ref;
								$rapport[$bull->id]->etat = $bull->transStrRegle();
								$rapport[$bull->id]->note.= ''.$langs->trans("FacturationFinie").'';
							}
					}
					elseif ($bull->regle == $bull->BULL_SURPLUS) {
						// si trop payée faire avoir
							if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
							$rapport[$bull->id]->ref = $bull->ref;
							$rapport[$bull->id]->type = $bull->type;
							$rapport[$bull->id]->facture = $objfac->ref;
						$rapport[$bull->id]->note.=$langs->trans("BullAvoirFait");
							// creer_avoir($bull);  non testé car abandonné
							$rapport[$bull->id]->note=$langs->trans("BullAvoirFait");
					}
					elseif ($bull->regle == $bull->BULL_INCOMPLET)		{
							if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
							$rapport[$bull->id]->ref = $bull->ref;
							$rapport[$bull->id]->type = $bull->type;
							$rapport[$bull->id]->facture = $objfac->ref;
							$rapport[$bull->id]->note.=$langs->trans("BullNonPayeTot");
					}
					elseif ($bull->regle == $bull->BULL_NON_PAYE)		{
							if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
							$rapport[$bull->id]->ref = $bull->ref;
							$rapport[$bull->id]->type = $bull->type;
							$rapport[$bull->id]->facture = $objfac->ref;
							$rapport[$bull->id]->note.=$langs->trans("BullNonPaye");
					}
					if (!empty($fl_MajRegler)) $bull->updateregle ($fl_MajRegler);
				}				
				

				// edition de la facture
				//$objfac->fetch($bull->id);
				$objfac->fetch_thirdparty();

				// Define output language
				$outputlangs = $langs;
				$newlang='';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','int')) $newlang=GETPOST('lang_id','int');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$objfac->client->default_lang;
				if (! empty($newlang))		{
					$outputlangs = new Translate("",$conf);
					$outputlangs->setDefaultLang($newlang);
				}

				if (empty($objfac->model_pdf)) $objfac->model_pdf = $bull->RechercheModelFactCmd('Facture');				
				$result=$objfac->generateDocument('', $outputlangs);

				if ($result <= 0)		{
					$error++;
					if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
					$rapport[$bull->id]->ref = $bull->ref;
					$rapport[$bull->id]->facture = $objfac->ref;
					$rapport[$bull->id]->msg.=$langs->trans("ErrFactGenFact",$result);
				}
		
			}// fin de traitement facture OK
			else { // Gestion des erreur du traitements de la facture
				// Traitement de facture impossible 
				if (!isset($rapport[$bull->id]))  $rapport[$bull->id]= new stdClass();
				$rapport[$bull->id]->ref = $bull->ref;
				$rapport[$bull->id]->facture = $gl_facture;
				$error++;
				if ($retfac == -1) {
					// Facture non créée
					$rapport[$bull->id]->msg =$langs->trans('ErrFactCreate',$gl_error);
				}
				elseif ($retfac  == -2 or $retfac == -1160) {
					// probleme de mise a jour totaux
					$rapport[$bull->id]->msg =$langs->trans('ErrFactTotauxFaux');					
				}
				elseif ($retfac  == -3 or $retfac == -1180) {
					// probleme de validation
					$rapport[$bull->id]->msg =$langs->trans('ErrFactExistFact',$gl_error_fk);
				}
				elseif ($retfac  == -1001) {
					// erreur sur clé étrangère de la facture
					$rapport[$bull->id]->msg =$langs->trans("ErrFactValidNok", $bull->ref );	
				}
				elseif ($retfac  == -1002) {
					// erreur sur la suppression d'une ligne de facture - $
					$rapport[$bull->id]->msg =$langs->trans("ErrFactLigSup",$gl_error_fk);	
				}
				elseif ($retfac  <= -1100 and $retfac  > -1119  ) {
					// erreur sur l'ajout  d'une ligne de facture - $gl_error_fk
					$rapport[$bull->id]->msg =$langs->trans("ErrFactLigAj",$gl_error_fk);
				}
				elseif ($retfac  <= -1140 and $retfac  > -1159  ) {
					// erreur sur la mise à jour  d'une ligne de facture - $gl_error_fk
					$rapport[$bull->id]->msg =$langs->trans("ErrFactLigMod",$gl_error_fk);
				}
				else {
					// erreur sur la mise à jour  d'une ligne de facture - $gl_error_fk
					$rapport[$bull->id]->msg =$langs->trans("ErrFactIndeterminee",$retfac);	
				}
			}
		} // fin de facture facturé > 0//
		
		if (!empty($objcmd->lines)) {
			foreach ( $objcmd->lines as $line)
				unset ($line);
		}
		unset($objcmd);
		if (!empty($objacpt->lines)) {
			foreach ( $objacpt->lines as $line)
				unset ($line);
		}
		unset($objacpt);
		if (!empty($objacpt->lines)) {
			foreach ( $objfac->lines as $line)
				unset ($line);
		}
		unset($objfac);	
		unset($w);		 			
		return $retour;
	} // FactureBulletin

} // fin Class BulletinLigneRando
?>