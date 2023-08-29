<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 * *
 * Version CAV - 2.7 été 2022 - Migration Dolibarr V15
 *					 - Recuperer code ventilation du minoteur lors saisie dans BU
 *
 * Version CAV - 2.7.1 automne 2022 - Réécriture de la fonction EnrNego
 * 									- formatage de la date négociation à la création du moniteur
 *									- fiabilisation des foreach
 * Version CAV - 2.8 - hiver 2023 -
 *			- correction erreur dans nbPartDep
 * 			- affichage colonne à discretion - déplacement méthode 
 * Version CAV - 2.8.5 - printemps 2023
 *			- absence des bulletin d'un départ si celui-ci n'a pas de moniteur (bug 325)
 *
 * ATTENTION, la gestion des action d'un formateur-moniteur n'est pas valide
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
 
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

require_once  DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once  DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once  DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once  DOL_DOCUMENT_ROOT."/custom/cglinscription/core/modules/cglinscription/modules_cglinscription.php";
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agsession.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agefodd_session_formateur.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agefodd_session_calendrier.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agefodd_session_formateur_calendrier.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglFonctionAgefodd.class.php';
//require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/bulletin.class.php';

	
/**
 *	Put here description of your class
 */
class CglDepart
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
        return 1;
    }

	function RechInfoMont($id, $agsession)
	{				
		$sql="SELECT f.rowid, socf.tva_assuj, ventilation_vente, cost_trainer, cost_trip, date_nego";		
		$sql.=" FROM ".MAIN_DB_PREFIX."agefodd_formateur as f ";			
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."socpeople as cf on cf.rowid = f.fk_socpeople";
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."societe as socf on socf.rowid = cf.fk_soc";			
		
		$sql.=" WHERE f.rowid ='".$id."'";
		dol_syslog (  get_class ( $this ) ."::RechInfoMont sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );

		if ($resql) {			
			$num = $this->db->num_rows ( $resql );
			if ($num) {			
				$obj = $this->db->fetch_object ( $resql );	
				$agsession->array_options['options_s_TypeTVA']  	= $obj->tva_assuj;		
				$agsession->array_options['options_s_code_ventil']  = $obj->ventilation_vente;
				// Si  en création et le mode n'est pas 'total', il n'y a rien eu de saisi, il faut donc aller le cherche dans le moniteur.
				
				if ($obj->cost_trainer and $obj->cost_trainer <> 0 )  $agsession->array_options['options_s_partmonit']  	= price2num($obj->cost_trainer);
				if ($obj->cost_trip and $obj->cost_trip <> 0) $agsession->array_options['options_s_pourcent']  	= price2num($obj->cost_trip);
				$agsession->array_options['options_s_date_nego']  	=  dol_print_date($obj->date_nego, '%d/%m/%y');
				//  dol_stringtotime( $DateNego);		 pourrait remplacer dol_print_date car déprécié deprecated	
				$agsession->type_tva 	=  $obj->tva_assuj;						
			}
		}			
	}//RechInfoMont

	function Maj_depart()
	{
		global $place, $formation, $intitule_custo, $TypeSessionDep_Agf, $session_status, $DepartDate, $nb_place, $notes, $rdvprinc;
		global $PrixAdulte, $PrixEnfant, $PrixExclusif, $PrixGroupe, $DureeAct;
		global $code_ventil, $id_client, $MtFixe, $Pourcent, $DateNego;
		global $moniteur_id, $alterrdv, $HeureFin, $type_tva, $HeureDeb, $CRE_DEPART;
		global $user, $conf, $bull, $langs, $id_depart;
		global $extrafields, $paramtotal ;
		
		dol_syslog ( get_class ( $this ) . "::maj_depart - session" , LOG_DEBUG );		
		$msg=array();
		$error=0;
		
		// Test sur dates valide s
		$flreturn =false;
		$fldateinvalide = !checkdate (substr($DepartDate, 3,2), substr($DepartDate, 0,2), substr($DepartDate, 6));
		if ($fldateinvalide) {
			$error++; setEventMessage($langs->trans("ErrorFieldFormat",$langs->transnoentitiesnoconv("Date du depart")).':'.$DepartDate,'errors');
			$flreturn =true;
		}
		if (!empty($DateNego)) {
			$fldateinvalide = !checkdate (substr($DateNego, 3,2), substr($DateNego, 0,2), substr($DateNego, 6));
			if ($fldateinvalide  and GETPOST('total', 'alpha') == 'oui') {
				$error++; 
				setEventMessage($langs->trans("ErrorFieldFormat",$langs->transnoentitiesnoconv("Date de Negociation")).':'.$DateNego,'errors');
				$flreturn = true;
			}
		}
		//TEST Champs obligatoires - formation
		
		if (empty ($formation) or $formation <0)		{
			$error++;$flreturn = true;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("AgfFormIntitule")),'errors');
		}
	/*	if (empty ($place)  or $place <0 )		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("AgfLieu")),'errors');
		}
		*/
//		$action = $CRE_DEPART;	
		if ($flreturn)		return -1;

		// SESSION	
		
		// récup des données en base  pour modification session
		$agsession =  new Agsession($this->db);
		if (!empty($id_depart)) {
			$agsession->fetch($id_depart);		
			$sesform = new Agefodd_session_formateur ($this->db);	
			$numret = $sesform->fetch_formateur_per_session($id_depart);
		}
		else {
			$agsession->id = $id_depart;
			$agsession->sessid = $id_depart;
		}				
		
		// récup des données saisie et tranférée 	
		$agsession->fk_formation_catalogue 	= $formation;
		$agsession->fk_product 	= $formation;
		$agsession->fk_session_place 		= $place;
		$agsession->nb_place 				= $nb_place;
		$agsession->type_session 			= $TypeSessionDep_Agf;
		//$agsession->dated 					= dol_stringtotime($DepartDate);
		//$agsession->datef 					= dol_stringtotime($DepartDate);

		$agsession->notes 					= $notes;
		$agsession->status 					= 1;
		$agsession->duree_session 			= $duree_session;
		$agsession->intitule_custo 			= $intitule_custo;
		if ($agsession->type_session == 0) $agsession->fk_soc 	= $bull->id_client;

		preg_match('/^([0-9]+)\/([0-9]+)\/([0-9]+)/',$DepartDate, $reg);
		$annee = $reg[3];	
		$now = dol_now('tzuser');	
		$annecourant = substr($this->db->idate($now),0,4);	
		if ($annee < $annecourant -1 or   $annee > $annecourant + 1 ) $annee = $annecourant;

		//$annee  = substr($DepartDate, dol_strlen($DepartDate)-4);
		$mois =	substr($DepartDate, 3,2);
		$jour =	substr($DepartDate, 0,2);
		$heure =	substr($HeureDeb, 0,2);
		$min =	substr($HeureDeb, 3,2);

		$heured = dol_mktime($heure,$min,0,$mois,$jour,$annee);
				
		$heure =	substr($HeureFin, 0,2);
		$min =	substr($HeureFin, 3,2);

		$heuref = dol_mktime($heure,$min,0,$mois,$jour,$annee);
		$agsession->dated 					= $heured;
		$agsession->datef 					= $heuref;
		
		$duree_session = ($heuref - $heured);
		$agsession->duree_session 	= $duree_session;
		$agsession->rdvprinc	= $rdvprinc;
		$agsession->alterrdv 	= $alterrdv;
	
		$agsession->array_options['options_s_pvgroupe'] 	= price2num($PrixGroupe);
		$agsession->array_options['options_s_pvexclu'] 		= price2num($PrixExclusif);
		$agsession->array_options['options_s_PVIndAdl'] 	= price2num($PrixAdulte);
		$agsession->array_options['options_s_PVIndEnf'] 	= price2num($PrixEnfant);
		$agsession->array_options['options_s_rdvPrinc'] 	= $rdvprinc;
		$agsession->array_options['options_s_rdvAlter']  	= $alterrdv;
		$agsession->array_options['options_s_duree_act'] 	= price2num($DureeAct);
		
		if ( $paramtotal == 'oui')      {
		// Si mode total on reprend la saisie
			$agsession->array_options['options_s_TypeTVA']  	= $type_tva;
			$agsession->array_options['options_s_code_ventil']  = $code_ventil;		
			$agsession->array_options['options_s_partmonit']  	= price2num($MtFixe);
			$agsession->array_options['options_s_pourcent']  	= price2num($Pourcent);

			preg_match('/^([0-9]+)\/([0-9]+)\/([0-9]+)/',$DateNego, $reg);
			$jour = $reg[1];
			$mois = $reg[2];
			$annee = $reg[3];
			$wDateNego = dol_mktime(0,0,0,$mois,$jour,$annee);

			$agsession->type_tva 	= $type_tva;	
			$agsession->array_options['options_s_date_nego']  	= $wDateNego;
		}
		else
		{	
			if (!empty($moniteur_id) and !empty($code_ventil)) $this->RechInfoMont($moniteur_id, $agsession);				
			// Si non mode total	création ou  changement moniteur	alors on reprend les données du  moniteur	saisi		
			if ((empty($id_depart) ) or (!empty($id_depart) and $agsession->formagefoddid <> $moniteur_id and !empty($moniteur_id) )) {
				$this->RechInfoMont($moniteur_id, $agsession);				
			}
			elseif (! empty($id_depart) and empty($moniteur_id)) {
				if ($numret) $sesform->remove($sesform->id);
				$agsession->array_options['options_s_TypeTVA']  	= '';
				$agsession->array_options['options_s_code_ventil']  = '';		
				$agsession->array_options['options_s_partmonit']  	= '';
				$agsession->array_options['options_s_pourcent']  	= '';
				$agsession->array_options['options_s_date_nego']  	= '';
				$agsession->type_tva 	= '';	
			}
		}


		if (empty($id_depart)) {
			//if (empty($agsession->nb_place) ) $agsession->nb_place  = 10;
			$ret = $agsession->create($user, 1) ;
			if ($ret) { $agsession->id = $ret; $id_depart = $ret;}
		}
		else {	
		
			// Si pas de moniteur saisi , supprimer le lien de l'ancien moniteur
			if (empty($moniteur_id)) {				
				$num = $sesform->fetch_formateur_per_session($id_depart);
				if ($num) {
					$wfct = new CglFonctionAgeFodd ($this->db);
					$wfct->remove_une_animation($id_depart, $sesform->lines[0]->formid );
					unset ($wfct);
				}
			}
			
			$agsession->dated=$heured;
			$agsession->datef=$heuref;
			$ret = $agsession->update($user, 0) ;
		}	
	if ($ret <= 0) {
				$error=1;
				if (empty($id_depart)) setEventMessage($langs->trans("ErrorCreate",$agsession->intitule_custo),'errors');
				else setEventMessage($langs->trans("ErrorMod",$agsession->intitule_custo),'errors');
				}
		else		{	

			//$agsession->sessid = $ret;
			//$this->setExtrafieldsAgsession($agsession);
			// LIEN SESSION - MONITEUR
			if (! empty($moniteur_id))			{
				dol_syslog ( get_class ( $this ) . "::Maj_depart - moniteur" , LOG_DEBUG );
				$agsessform = new Agefodd_session_formateur($this->db);
				if (!empty($id_depart)) {					
					$agsessform->id = $agsessform->lines[0]->opsid;
					$ret = $agsessform->fetch_formateur_per_session($id_depart);
					$agsessform->formid =$moniteur_id;
					$agsessform->sessid =$id_depart;
				// si session connu et formateur déjà renseigné modifié
					if ($ret > 0 ) {
						$agsessform->opsid =  $agsessform->lines[0]->opsid ;
						//$agsessform->id = $agsessform->lines[0]->opsid ;
						//$agsessform->opsid = $agsessform->lines[0]->opsid ;
						$ret = $agsessform->update($user, 0) ;
						if ($ret > 0) $fk_agf_session_formateur = $agsessform->id;	
						else {
							$error++;
							setEventMessage($langs->trans('IncForm'),'errors');							
						}						
					}
					else {
						// Il n'y a pas de formateur, il faut le créer
						$ret = $agsessform->create($user, 0) ;
						if ($ret > 0) {
								$fk_agf_session_formateur = $ret ;
								$agsessform->id = $ret	;
						}
						else						
						{
							$error++;
							setEventMessage($langs->trans('IncForm'),'errors');							
						}							
					}
				}	
				else {
					$agsessform->formid =$moniteur_id;
					$agsessform->sessid =$agsession->id;
					$ret = $agsessform->create($user, 0) ;
					if ($ret > 0) {
							$fk_agf_session_formateur = $ret ;
							$agsessform->id = $ret	;
					}		
					else						
					{
						$error++;
						setEventMessage($langs->trans('IncForm'),'errors');							
					}												
				}				
				//if (!empty($$error)) {$error+=10;setEventMessage($langs->trans("ErrorMoniteurDepart",$langs->transnoentitiesnoconv("Depart")),'errors');}
			}		
			dol_syslog ( get_class ( $this ) . "::Maj_depart - calendrier" , LOG_DEBUG );			
 
			// LIEN SESSION - CALENDRIER
			$agsesscal= new Agefodd_sesscalendar($this->db);
			if (!empty($id_depart)) {
				$retcal = $agsesscal->fetch_all($id_depart);
				$agsesscal->id = $agsesscal->lines[0]->id;
				//$agsesscal->id = $id_depart;
			}
			$agsesscal->sessid=$agsession->id;
			$agsesscal->date_session=$agsession->dated;
			$agsesscal->heured=$heured;
			$agsesscal->heuref=$heuref;

			if ($retcal > 0) {	
				$ret = $agsesscal->update($user, 0) ;
			}
			else $ret = $agsesscal->create($user, 0) ;
			if ($ret <= 0) {$error+=100;setEventMessage($langs->trans("ErrorCal",$langs->transnoentitiesnoconv("Depart")),'errors');}
			if (!empty($id_depart) or $ret > 0) {
				// modifie le libelle de l'action de l'agenda
				$line =  new  BulletinLigne ($this->db);
				$wk = new cglInscDolibarr($this->db);
				$line->id_act = $agsession->id;
				// Doublon avec create action
				//$ret = $wk->Traite_actioncomm($line->id_act, $heured , $heuref );				
				if ($ret < 0 ) {$error+=10000;setEventMessage($langs->trans("ErrorLiblleEvent",$langs->transnoentitiesnoconv("Depart")),'errors');}						
				else
					// pas fait car non utile maintenant
					// LIEN SESSION - CALENDRIER MONITEUR
					if ("CCATOTO" == "CCATITI") 
					{
						if (! empty($moniteur_id)) 
						{
							// But - mettre à jour le lien calendrier - formateur
							// si  moniteur en argument  
							//		si moniter arguement = moniteur dans la base : modifier le lien et l'action (fonction des heures)
							//		si moniteur dans la base est vide
							//			créer le lien action - moniteur et l'action 
						
	
									// ACTION ave LIEN SESSION		
							$agformcal = New Agefoddsessionformateurcalendrier($this->db);
							if (isset($agsessform) and !empty($agsessform->id) and $agsessform->formid == $moniteur_id)  {
								// Update
								//$agformcal->fetch($agsessform->id);
								$agformcal->fetch_by_action($agsesscal->fk_actioncomm);
								print '<br>';
									if (!empty($agformcal->id )) {
										$agformcal->date_session = dol_stringtotime($DepartDate);
										$agformcal->heured = $heured ;
										$agformcal->heuref = $heuref ;



										$agformcal->fk_user_author = $user->id;
										$agformcal->update($user, 0) ;		
									}							
								//$agformcal->fk_agefodd_session_formateur = $agsessform->id;	
							}
							elseif ( isset($agsessform) and !empty($agsessform->id) and $agsessform->formid <> $moniteur_id) {
								$agformcal->fetch_by_action($agsesscal->fk_actioncomm);
								$agformcal->remove(TOTO);
							// supprimer calendrier ancien moniteur - mettre nouveau
								if (empty($id_depart) or empty($agformcal->id )) {
									$agformcal->date_session = dol_stringtotime($DepartDate);
									$agformcal->heured = $heured ;
									$agformcal->heuref = $heuref ;
									$agformcal->fk_actioncomm = $agsesscal->id;
									$agformcal->fk_user_author = $user->id;
									$ret = $agformcal->create($user, 0) ;				
									}							
							}
							elseif ( isset($agsessform) and empty($agsessform->id)) {
							// Creer nouveauif (empty($id_depart) or empty($agformcal->id )) {
								$agformcal->date_session = dol_stringtotime($DepartDate);
								$agformcal->heured = $heured ;
								$agformcal->heuref = $heuref ;
								$agformcal->fk_actioncomm = $agsesscal->id;
								$agformcal->fk_user_author = $user->id;
								$ret = $agformcal->create($user, 0) ;			
								}						
							elseif  (empty($moniteur_id)) {
								if (! empty($agsessform->id)) {
									$agformcal->fetch_by_action($agsesscal->fk_actioncomm);
									// supprimer calendrier ancien moniteur 
									$agformcal->remove(TOTO);
								}					
							}
						}	
					}
				unset ($wk);
				if (empty($id_depart)) $id_act = $line->id_act;
				unset ($line);
				
			}
			unset($agsesscal);

			unset($agsessform);

		}
		
		// modifier les bulletins de cette activité pour tva, ventilation, rendez-vous, moniteur, produit
		
		$bull->UpdateVentilbySess( $id_depart, $agsession->array_options['options_s_code_ventil']);

		// Supprimer en juin 2019 car incompréhensible 
		// tous les bulletins doivent être au maximum à facturer
		//$bull->UpdateBullFactbySess( $id_depart);	
	
		if (!empty($error) and $error >0)
		{	
			$mesgs[]='<div class="error">'.$object->error.'</div>';	
			if ($agsession->id > 0) $id_depart = $agsession->id;
			if ($agsession->id > 0) $action = 'edit';
			else $action = $CRE_DEPART;			
//			$_GET["origin"]=$_POST["origin"];
//			$_GET["originid"]=$_POST["originid"];
//			$_GET["PartPrenom"]=$_POST["PartPrenom"];
//			dol_print_error($db,$object->error);
			unset($agsession);
			return -1;		
			/*
			$text='Cr?ion non enti?ment aboutie n? error:'.$error;					
			$form = new Form($db);
			$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'],$text,'','','',1);
			unset ($form);
			print $formconfirm;
			exit;
			*/			
		}
		else {
			unset($agsession);
			$DepartDate = '';
			$DateNego = '';
			return 1;
			
		}
			// renvoie sur liste des départ

	
	}	//Maj_depart
	/*
	*	Annule un départ 
	*		1 mettre statut du départ à 3 (Non réalisé)
	*		2 - Mettre action='X' dans toutes les participations de ce départs
	*		3 - Ajouter ' - Non rréalisé au titre du départ
	*		4 Dans les bulletins impactés par le munéro 2, dans le champ suivi : <x> participations ont été annulé car leur départ a été annulé
	*/
	function Annuler($id_act)
	{	
		global $langs, $listbull, $bull;	
		$wbull = new Bulletin ($this->db);

		// Mettre statut de départ à 3 (non réalisé)
		if ($this->UpdateStatut($id_act, 3) <0) return -1;
		// ajouter AgfStatusSession_NOT à la suite du nom de l'activité
		if ($this->UpdateTitre($id_act, $langs->trans('AgfStatusSession_NOT')) < 0 ) return -1;
		// Supprimer le calendrier de cette session
		$agscal = new Agefodd_sesscalendar($this->db);
		$agscal->fetch_all($id_act);
		
		$ret = $agscal->remove($agscal->lines[0]->id);
		if ($ret < 0) setEventMessage($langs->trans('EvntNonSup'), 'errors');		
		
		if (empty($listbull)  and !empty($id_act)) {
			$listBull = array();
			$depart = new Agsession($this->db);
			$depart->fetch($id_act);
			$listBull = $this->fetchbullbysession($id_act);
		}
		
		if (!empty($listBull)  ) {
			 if (!empty($id_act)) {
				$cglInscDolibarr  = new cglInscDolibarr($this->db); 
				$bull_inscription = $bull;  // pour respecter le bulletin en cours, lors de l'appel du départ dans Inscription
				$retgen=0;
				if ( !empty($listBull)) {
					foreach ($listBull as $bull) {
						$ret = $bull->desincrire($id_act);

						if ($ret < 0) $retgen+=$ret;
						if ($bull->statut != $bull->BULL_ENCOURS) 
						{						
							$cglInscDolibarr->TransfertDataDolibarr('desincrire','');
						}	
						// Ajouter info dans champ suivi de bull - dol_print_date($depart->dated, 'dayhourtext')
						$texte= 'Depart : "'. $depart->intitule_custo. '" prevu le '. dol_print_date($depart->dated, 'dayhourtext');	
						$this->UpdateBullActionFuture($bull->id, $bull->ActionFuture. ' - '.$texte);
						if ($ret < 0) $retgen+=$ret;
						
					} // foreach
				}
				$bull = $bull_inscription;
				unset($cglInscDolibarr);
			}
		}	
	return ($retgen > 0) ? -1 : 1;
	} //Annuler
		
	function UpdateStatut($id, $val)
	{
		if (empty($id)) return;
		if (empty($val)) return;

		if (empty($val)) $val = 0;
		$sql = "UPDATE  " . MAIN_DB_PREFIX . "agefodd_session" ;
		$sql .= " SET status = ".$val;
		$sql .= " WHERE rowid = '".$id."'";
		dol_syslog(get_class($this) . "::UpdateStatut", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)  return 1;
		else return -1;
		
	} //UpdateStatut
	
			
	function UpdateTitre($id, $val)
	{
		if (empty($id)) return;
		if (empty($val)) return;
		
		$sql = "UPDATE  " . MAIN_DB_PREFIX . "agefodd_session" ;
		$sql .= " SET intitule_custo  = concat(concat(intitule_custo,' - '),'".$val."')";
		$sql .= " WHERE rowid = '".$id."'";
		$sql .= " AND intitule_custo not like  '%Non r%ali%'";

		dol_syslog(get_class($this) . "::UpdateTitre", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)  return 1;
		else return -1;
		
	} //UpdateStatut
		
	function UpdateBullActionFuture($id, $val)
	{
		if (empty($id)) return;
		if (empty($val)) return;	
		$sql = "UPDATE  " . MAIN_DB_PREFIX . "cglinscription_bull" ;
		$sql .= " set ActionFuture  = '".$val."'";
		$sql .= " WHERE rowid = '".$id."'";
		dol_syslog(get_class($this) . "::UpdateBullActionFuture", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)  return 1;
		else return -1;
		
	} //UpdateStatut



	/* Liste les bulletins dont une participation au moins est pour ce départ
	* retour liste bulletins
	*/
	function fetchbullbysession($id_session)
	{
		global $langs;
		$bulls = array();
			
		$sql = "SELECT";
		$sql .= " b.rowid, ActionFuture";
		$sql .= ", sum(qte) as NbPart ";
		$sql .= " , case when b.observation like '%Bulletin abandon%' then 1 else 0 end as FlAbandon";
		$sql .= " FROM " . MAIN_DB_PREFIX . "cglinscription_bull as b, ";
		$sql .=   MAIN_DB_PREFIX . "cglinscription_bull_det as bd";
		$sql .= " WHERE b.rowid = bd.fk_bull and bd.fk_activite = '" . $id_session ."'";
		$sql .= " AND  typebull = 'Insc'  ";
		$sql .= " GROUP BY b.ref , b.rowid, ActionFuture, FlAbandon ";
		//$sql .= " AND bd.action not in ('S','X')";
		
			
		$sql .= " ORDER BY b.ref ASC";
		dol_syslog(get_class($this) . "::fetchbullbysession sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)	 {
				$obj = $this->db->fetch_object($resql);	
				$objbull = New Bulletin ($this->db);	
				$objbull->fetch($obj->rowid);
				$bulls [] = $objbull;
				$i++;
			}
		}
		return $bulls;		
	}// fetchbullbysession
	
	function CalculTotDepartBull($bull, $id_depart)
	{
		$ret = 0;
		if ( !empty($bull->lines)) {
			foreach ($bull->lines as $line) {
				if ($line->action != 'X' and $line->action != "S" and $line->type_enr == 0 and $line->id_act == $id_depart) {
					$ret += $line->pu* $line->qte*(100- (int)$line->remise_percent)/100;
				}
			} //foreach
		}
		return $ret;
		
	} //CalculTotDepartBull

	function fetchSessionsExtrafields ($id)
	{
	  global $conf,$langs, $id_act;
	
		if (empty ($id_act)) {
			$sql = 'select s_pvexclu, s_pvgroupe, s_PVIndAdl, s_PVIndEnf, s_rdvPrinc, s_rdvAlter, s_ficsess, s_TypeTVA, ';
			$sql .= ' s_code_ventil, s_partmonit, s_pourcent, s_date_nego, s_ref_facture  ';
			$sql .= ' FROM '.MAIN_DB_PREFIX .'agefodd_session_extrafields as se WHERE  se.fk_object="'.$id.'"';
			dol_syslog(get_class($this)."::fetchSessionsExtrafields sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)			{
				$obj = $this->db->fetch_object ( $resql );
				$agf->array_options['options_s_pvexclu'] = $obj->s_pvexclu ;
				$agf->array_options['options_s_pvgroupe'] = $obj->s_pvgroupe ;
				$agf->array_options['options_s_PVIndAdl'] = $obj->s_PVIndAdl ;
				$agf->array_options['options_s_PVIndEnf'] = $obj->s_PVIndEnf ;
				$agf->array_options['options_s_rdvPrinc'] = $obj->s_rdvPrinc ;
				$agf->array_options['options_s_rdvAlter'] = $obj->s_rdvAlter ;
				$agf->array_options['options_s_ficsess'] = $obj->s_ficsess ;
				$agf->array_options['options_s_TypeTVA'] = $obj->s_TypeTVA ;
				$agf->array_options['options_s_code_ventil'] = $obj->s_code_ventil ;
				$agf->array_options['options_s_partmonit'] = $obj->s_partmonit ;
				$agf->array_options['options_s_pourcent'] = $obj->s_pourcent ;
				$agf->array_options['options_s_date_nego'] = $obj->s_date_nego ;
				$agf->array_options['options_s_ref_facture'] = $obj->s_ref_facture ;
			}
			else {
				dol_print_error($this->db);
				//$this->error = "Error " . $this->db->lasterror();
				//dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
				return -1;
			}
		}	
				
	} //fetchSessionsExtrafields
	
	function setExtrafieldsAgsession ($agf)
	{
	  global $conf,$langs,  $id_act, $user;
	
		$idtemp = $id_act;
		if (empty($id_act) and !empty($agf->id)) $idtemp = $agf->id;
		if (empty($agf->array_options['options_s_pvgroupe'])) $agf->array_options['options_s_pvgroupe'] = 0;
		if (empty($agf->array_options['options_s_pvexclu'])) $agf->array_options['options_s_pvexclu'] = 0;
		if (empty($agf->array_options['options_s_PVIndAdl'])) $agf->array_options['options_s_PVIndAdl'] = 0;
		if (empty($agf->array_options['options_s_PVIndEnf'])) $agf->array_options['options_s_PVIndEnf'] = 0;
		if (empty($agf->array_options['options_s_partmonit'])) $agf->array_options['options_s_partmonit'] = 0;
		if (empty($agf->array_options['options_s_pourcent'])) $agf->array_options['options_s_pourcent'] = 0;

		if (empty ($agf->id)) {
		$sql = "INSERT INTO ".MAIN_DB_PREFIX ."agefodd_session_extrafields ";
			$sql .= "( fk_object, s_pvgroupe, s_pvexclu, s_PVIndAdl, s_PVIndEnf, s_rdvPrinc, s_rdvAlter, s_ficsess, s_TypeTVA, s_code_ventil, s_partmonit, s_pourcent, s_date_nego, s_ref_facture) ";
			$sql .= 'VALUES ("'.$agf->id.' "';
			$sql .= ', "'.$agf->array_options['options_s_pvgroupe'] .'"';
			$sql .= ', "'. $agf->array_options['options_s_pvexclu'].' "';
			$sql .= ', "'.$agf->array_options['options_s_PVIndAdl'].'"';
			$sql .= ', "'.$agf->array_options['options_s_PVIndEnf'] .'"';
			$sql .= ', "'.$agf->array_options['options_s_rdvPrinc'] .'"';
			$sql .= ', "'.$agf->array_options['options_s_rdvAlter'] .'"';
			$sql .= ', "'.$agf->array_options['options_s_ficsess'] .'"';
			$sql .= ', "'.$agf->array_options['options_s_TypeTVA'] .'"';
			$sql .= ', "'.$agf->array_options['options_s_code_ventil'] .'"';
			$sql .= ', "'.$agf->array_options['options_s_partmonit'] .'"';
			$sql .= ', "'.$agf->array_options['options_s_pourcent'] .'"';
			$sql .= ', "'.$agf->array_options['options_s_date_nego'] .'"';
			$sql .= ', "'.$agf->array_options['options_s_ref_facture'] .'")';
			
			dol_syslog(get_class($this)."::setExtrafieldsAgsession sql=".$sql);
			$resql=$this->db->query($sql);
			if (!$resql)			{
				dol_print_error($this->db);
				return -1;
			}
		}	
		else {
			$sql = "UPDATE ".MAIN_DB_PREFIX ."agefodd_session_extrafields SET ";
			$sql .= ' s_pvgroupe = "'.$agf->array_options['options_s_pvgroupe'].'"';
			$sql .= ', s_pvexclu = "'.$agf->array_options['options_s_pvexclu'].'"';
			$sql .= ', s_PVIndAdl = "'.$agf->array_options['options_s_PVIndAdl'].'"';
			$sql .= ', s_PVIndEnf = "'.$agf->array_options['options_s_PVIndEnf'] .'"';			
			$sql .= ', s_rdvPrinc = "'.$agf->array_options['options_s_rdvPrinc'] .'"';			
			$sql .= ', s_rdvAlter = "'.$agf->array_options['options_s_rdvAlter'] .'"';			
			$sql .= ', s_ficsess = "'.$agf->array_options['options_s_ficsess'] .'"';			
			$sql .= ', s_TypeTVA = "'.$agf->array_options['options_s_TypeTVA'] .'"';			
			$sql .= ', s_code_ventil = "'.$agf->array_options['options_s_code_ventil'] .'"';
			$sql .= ', s_partmonit = "'.$agf->array_options['options_s_partmonit'] .'"';			
			$sql .= ', s_pourcent = "'.$agf->array_options['options_s_pourcent'] .'"';
			$sql .= ', s_date_nego = "'.$agf->array_options['options_s_date_nego'] .'"';
			$sql .= ', s_ref_facture = "'.$agf->array_options['options_s_ref_facture'] .'"';
			$sql .= ' WHERE fk_object = "'.$agf->id.'"';
			$resql=$db->query($sql);
			if (!$resql)			{
				dol_print_error($this->db);
				return -1;
			}
		}			
	} //setExtrafieldsAgsession


//  
	/*
	* Mise à jour des infos de négociations et facture moniteur d'un départ 
	*
	*
	* @param	int 		$id			Identifiant de départ
	* @param	decimal 	$partmonit	Coût du moniteur négocié au fixe
	* @param	int 		$pourcent	Coût du moniteur négocié au pourcentage
	* @param	int 		$facture	Identifiant de la facture du moniteur payant cette prestation
	* @param	int 		$flNegoFac	indicateur : == 1 si enregistre facture uniquement, 0 si enregistre nego, 2 si enregistre les deux
	*/
	function EnrNego($id, $partmonit, $pourcent, $facture, $flNegoFac )
	{
		// Recherche facture
		$FlExistFact = false;
		$flFctPaye = 0;
		if (!empty($facture)) {
			$sql1 = "SELECT s_ref_facture, s_fk_facture, f.ref , f.paye";
			$sql1 .=  " FROM  ".MAIN_DB_PREFIX ."agefodd_session_extrafields as se";
			$sql1 .=  " LEFT JOIN  ".MAIN_DB_PREFIX ."facture_fourn as f on s_fk_facture = f.rowid";
			$sql1 .= ' WHERE fk_object = "'.$facture.'"';
			$resql1=$this->db->query($sql1);
			if ($resql1){
				$FlExistFact = true;
				$obj = $this->db->fetch_object($resql1);
				$flFctPaye =  $obj->paye;
//				if (empty($flFctPaye)) setEventMessage ('Facture deja payee', 'warnings');
			}
			else {
				setEventMessage ('Facture inexistante', 'warnings');
			}
		}		 
		if (empty($s_partmonit)) $s_partmonit = 0;
		if (empty($pourcent)) $pourcent = 0;
		$wdate = 	dol_print_date(dol_now('tzuser'),"%Y-%m-%d");
	
		$this->db->begin();
		
		// Si flNegoFac == 2 ou 0 - mise à jour ou création ligne avec pourcentage, partmoniteur et date et facture	 et id en cas de création	  
		// Info Si facture existe  et est-elle payée 
		if ($flNegoFac <> 1) {
			$sqlupd = "UPDATE ".MAIN_DB_PREFIX ."agefodd_session_extrafields SET ";
			$sqlupd .= ' s_partmonit = "'.$partmonit .'", ';			
			$sqlupd .= ' s_pourcent = "'.$pourcent .'", ';
			$sqlupd .= ' s_date_nego = "'.$wdate.'"';	
			if ($flNegoFac  == 2 ) {			
				if (empty($pourcent)) $sqlupd .= ', s_fk_facture = 0, '; else $sqlupd .= ', s_fk_facture = "'.$facture.'" ';		
			}
			$sqlupd .= ' WHERE fk_object = "'.$id.'"';
			$resqlupd=$this->db->query($sqlupd);
			if (!$resqlupd)	{
				$sqlins = "INSERT INTO  ".MAIN_DB_PREFIX ."agefodd_session_extrafields (fk_object, s_partmonit, s_pourcent ,s_date_nego";
				if ($flNegoFac  ) {			
					$sqlins .= ', s_fk_facture ';		
				}
				$sqlins .= ' ) VALUES (';
				$sqlins .= '"'.$id .'", ';
				if (empty($partmonit)) $sqlins .= '0, '; else $sqlins .= '"'.$partmonit .'", ';			
				if (empty($pourcent)) $sqlins .= '0, '; else $sqlins .= '"'.$pourcent .'", ';
				$sqlins .= '"'.$wdate.'" ';	
				if ($flNegoFac  ) {			
					if (empty($facture)) $sqlins .= ',0 '; else $sqlins .= ', "'.$facture.'" ';	
					$sqlins .= ' ) ';	
					$resqlins=$this->db->query($sqlins);
					if (!$resqlins)	{
						dol_print_error($this->db);
						$this->db->rollback();					
					} 
					else {
						$this->db->commit();
//						if ($flFctPaye) setEventMessage('Facture déjà payée, donc négo déjà faite - Modification demandée et réalisée', 'warnings');
					} 
				}	
			}
		}			
		// Si flNegoFac 1 - mise à jour ou création ligne avec  facture	 et id en cas de création
		if ($flNegoFac == 1) {
			$sqlupd = "UPDATE ".MAIN_DB_PREFIX ."agefodd_session_extrafields SET ";
			if (empty($facture)) $sqlupd .= 's_fk_facture = 0, '; else $sqlupd .= 's_fk_facture = "'.$facture.'" ';	
			$sqlupd .= ' WHERE fk_object = "'.$id.'"';
			$resqlupd=$this->db->query($sqlupd);
			if (!$resqlupd)	{
				$sqlins = "INSERT INTO  ".MAIN_DB_PREFIX ."agefodd_session_extrafields (fk_object,  s_fk_facture";
				$sqlins .= ' ) VALUES (';
				$sqlins .= '"'.$id .'", ';
				if (empty($facture)) $sqlins .= ', 0 '; else $sqlins .= ', "'.$facture.'" ';	
				$sqlins .= ' ) ';	
				$resqlins=$this->db->query($sqlins);
				if (!$resqlins)	{
					dol_print_error($this->db);
					$this->db->rollback();					
				} 
				else $this->db->commit();
			} 
		}	
		$this->db->commit();		
			
		return(0);
	} //EnrNego
		
	function InfoVentilNego ($id)
	{	
		$sql="SELECT f.rowid, socf.tva_assuj, ventilation_vente, cost_trainer, cost_trip, date_nego";		
		$sql.=" FROM ".MAIN_DB_PREFIX."agefodd_formateur as f ";			
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."socpeople as cf on cf.rowid = f.fk_socpeople";
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."societe as socf on socf.rowid = cf.fk_soc";			
		
		$sql.=" WHERE f.rowid =".$id;
		dol_syslog ( get_class ( $this ) . "::ReqInfoForm sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );

		if ($resql) {			
			$num = $this->db->num_rows ( $resql );
			if ($num) {
				$obj = $this->db->fetch_object ( $resql );						
				$this->tva_assuj =  $obj->tva_assuj;	
				$this->ventilation_vente =  $obj->ventilation_vente;	
				if ($obj->cost_trainer and $obj->cost_trainer <> 0 ) $this->cost_trainer =  $obj->cost_trainer;	
				if ($obj->cost_trip and $obj->cost_trip <> 0) $this->cost_trip =  $obj->cost_trip; 
				$this->date_nego =  $obj->date_nego;
				return $num;
			}
			else {	
				return 0;
			}
		}
		elseif (!$resql) {			
			return -2;
		}
	} //EnrVentilNego

// Récupérer de Agefodd, afin de mettre en oeuvre les triggers
	function remove($id, $notrigger = 0) {
		global $conf, $langs, $user;
		$error = 0;
		
		$action = new ActionComm ($this->db);
		$action->id = $id;
		$action->type_code = 'AC_AGF_SESS';
		
		$ret = $action->delete();
	
		if (! $error) {
			$this->db->commit();
		} else {
			$this->db->rollback();
		}
		
		if (! $error) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return - 1;
		}
	} // remove 

	function RechercheActionBySession($idsess)
	{   
		global $langs;
	
		$tabret = array();
        $sql = "SELECT a.id";
        $sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a ";
        $sql.= " WHERE elementtype='agefodd_agsession' AND fk_element = '".$idsess."'";
 
        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
        	$num=$this->db->num_rows($resql);
			$i=0;
            while ($i<$num)
            {
 
				$obj = $this->db->fetch_object($resql);

                $tabret[]    = $obj->id;
				$i++;
            }
            $this->db->free($resql);
        }
        else
        {
            $this->error=$this->db->lasterror();
            return "";
        }

        return $tabret;
		
	} //RecheActionBySession
	
	/*
	* Nombre de participant au départ
	*
	* @param $statut 	0 pour le nombre de en cours, 1 pour le nombre pré-inscrits, 2 pour le nombre d'inscrits 
	*/
	function NbPartDep($statut, $id_act)
	{
		global $bull;
		$fl_bullajout = false;
		if (!isset($bull)) { $fl_bullajout = true; $bull = new Bulletin ($this->db); }
		$nb = 0;		
		
		if ($statut == 2) $statutBull =  $bull->BULL_INS; 
		if ($statut == 1) $statutBull =  $bull->BULL_PRE_INS; 
		if (empty($statut) or $statut == 0) $statutBull = $bull->BULL_ENCOURS; // bulletin en cours
		$sql="SELECT count(*) as nb";		
		$sql.=" FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";			
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on bd.fk_bull = b.rowid";
			$sql.=" WHERE bd.action not in ('S','X') and bd.type = 0 ";
			$sql.=" AND b.statut = ".$statutBull;
			$sql.=" AND bd.fk_activite = '".$id_act."'";

		dol_syslog ( get_class ( $this ) . "::NbPartDep ", LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($fl_bullajout) unset ($bull);
		if ($resql) {			
			$num = $this->db->num_rows ( $resql );
			if ($num) {			
				$obj = $this->db->fetch_object ( $resql );	
				return ($obj->nb)	;	
			}
			return;
		}
		return -1;
	
	} //NbPartDep

	
	/*
	*	Cherche les bulletins des départs affichés dans la liste conrante
	*
	*	@param	array()	$filters	(champ=> value,...) 
	*	@retour array()	(par groupe de 9 pour décrire chaque bull 
	*/
	function chercheBullDepart($filters)
	{
		global $db;
		$ret=array();
			
		$sql .= 'SELECT distinct s.rowid as iddep, ref as refbull,b.rowid as idbull, b.abandon, st.nom as client, facturable ';
		$sql .= ', sum(case when bd.age between  16 and 99 then 1 end) as nbadlt';
		$sql .= ', sum(case when bd.age < 16 or bd.age =  100  then 1 end ) as nbenf';
		$sql .= ', sum(bd.rem  ) as remPourc';
		$sql .= ', (select count(bdrem.rowid ) from llx_cglinscription_bull_det as bdrem ';
		$sql .= '    where bdrem.fk_bull = b.rowid and bdrem.type = 2 and  bdrem.action not in ("S","X")  ) as remFixe ';

		$sql .= ' FROM  '.MAIN_DB_PREFIX.'agefodd_session as s  ';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'cglinscription_bull_det as bd ON  s.rowid = fk_activite and bd.type = 0 and bd.action not in ("S","X")';
		$sql .= ' LEFT JOIN  '.MAIN_DB_PREFIX.'cglinscription_bull as b ON fk_bull = b.rowid  ';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_session_formateur as ms ON ms.fk_session = s.rowid ';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as st ON  st.rowid = b.fk_soc';
		$sql .= ' WHERE (1=1) ';
		
		// Manage filter
		if (!empty($filters) and count ( $filters ) > 0) {
			foreach ( $filters as $key => $value ) {
				if  ($key == 'facture') {
					if ($value == 1) 
						$sqlfilter .= ' AND f.rowid is not null';
					if ($value == 2) 
						$sqlfilter .= ' AND f.rowid is null';
				}
				elseif (strpos ( $key, 'date' )) 				// To allow $filter['YEAR(s.dated)']=>$year
				{
					$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
				} elseif ( ($key == 'ms.fk_agefodd_formateur')  || ($key == 's.status')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif  ($key == 's.type_session') {
					if ($value == 1) $sql .= ' AND ( s.type_session = 1 or isnull(s.type_session))';
						else $sql .= ' AND ' . $key . ' = ' . $value;
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $db->escape ( $value ) . '%\'';
				}
			}
		}
		$sql .= ' group by  s.rowid , ref ,b.rowid , b.abandon, st.nom ';

		dol_syslog ( 'FacturMoniteur' . "::chercheBullDepart ", LOG_DEBUG );
		$resql = $db->query ( $sql );
		if ($resql) {
			$num = $db->num_rows ( $resql );
			$i = 0;
			while($i<$num) 		{
				$obj = $db->fetch_object ( $resql );
				$ret[] = $obj->iddep;
				$ret[] = $obj->refbull;
				$ret[] = $obj->idbull;
				$ret[] = $obj->abandon;				
				$ret[] = $obj->client;
				$ret[] = $obj->nbadlt;
				$ret[] = $obj->nbenf;
				if ($obj->remFixe >  1) $InfoRem = $obj->remFixe;
				if (!empty($InfoRem) and $obj->remPourc > 0)  $InfoRem .=  ' - ';
				if ($obj->remPourc > 0)$InfoRem .= $obj->remPourc;
				$ret[] = $InfoRem;
				$ret[] = $obj->facturable;
				$i++;
			}
		return $ret;
		}
		else return;
	} //chercheBullDepart


} // fin de classe

?>
