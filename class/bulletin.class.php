<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com--->
 *
 * Version CAV - 2.7 été 2022
 *					  - Ajout propriété pour mail
 *					 - Fiabiliser transaction BD dans UpdateVentilbySess
 *					 - Migration Dolibarr V15 - PHP7
 *					 - Message avertissement si dates de retrait/dépose non saisies
 *					 - vérification des conflit pour planning vélo (NbLocationParMateriel)
 *
 * Version CAV - 2.7.1 automne 2022
 *		- une remise à 0 doit etre enregistrer en modification - updateparticipation)
 *		- suppression du message d'avertissement 'Penser à modifier le départ pour une mise à jour dans GoogleAgenda' devenu inutile
 *		- fiabilisation des boucle foreach
 *		- le champ Prénom peut contenir un '
 *		- correction de variable $line->enr inexistante, remplacer par this->type ou line->type_enr suivant les cas, et line->rem par line->remise_percent
 *
 * Version CAV - 2.8 hiver 2023
 *		- fiabilisation de 'enr d'une ligne de contrat (caractère ' accepté)
 *		- suppression de la table cgl_pmt_bank
 *		- Ajout champ dep_notes dans fetch_lines
 *		- Séparation refmat en IdentMat et marque
 *		- contrat/bulletin technique
 *		- remise à plat des status BU/LO
 * 		- ajout méthode bulletin IsLocPaimtReserv
 * Version CAV - 2.8.3 printemps 2023
 *		- le modèle proposé pour la facture issu d'un LO est TVA, celui de BU est TVA ou CommTVA(bug 271)
 * Version CAV - 2.8.4 printemps 2023
 *		- Modification du RDV2 en CONSEIL(bug 295)
 *		- correction conflit location vélo (300)
 *		- ajout liste des contrats en conflit de lication (304)
 * Version CAV - 2.8.5 - printemps 2023
 *		- vérification à la volée des conflit de vélo  (308b)
 *
 * This program is free 
 software; you can redistribute it and/or modify
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
 *	\file       htdocs/custum/cglinscription/class/bulletin.class.php
 *	\ingroup    cglinscription
 *	\brief      Fichier Interface Donn&és cgl_ins_bull et cgliins_bulldet
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctDolibarrRevues.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cgldepart.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cgllocation.class.php';

//Constantes

/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */
class Bulletin extends CommonObject
{

	//public $element='cglinscription' ;  -- modifier pour la V15
	public $element='bulletin';
	public $table_element='cglinscription_bull';
	public $table_element_line = 'cglinscription_bull_det';
	public $fk_element = 'fk_bull';
	
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $nblignebulletin;
	
 	var $type;							// Loc ou Insc
   var $id;
    var $datec;
    var $tms ;
    var $ref;
	var $facturable;					// 0 bulletin non facturable, soit sans données financières
										// 1 - facturable
	
    var $statut;   // 0 en cours , 1=>Inscrit 0.5=>Pre_inscrit 2 => départ 4 => clos9 Abandonné
    var $regle;   // 0 non payé , 1 paiement incomplet  , 2 paye, 3 surplus, 4 remboursé, 5 facture, 6 archivé
	var $abandon; // Texte précisant l'origine de l'abandon
    var $fk_user;
	var $ObsPriv; // observations non reprises sur la factures - obsolette
	var $ActionFuture; 
	var $PmtFutur;
    var $fk_persrec;  
    var $f_autori_parentale;
    var $f_condition_vente;
    var $f_autre;
    var $fk_origine;
    var $lb_origine;
	
	var $id_client;
	var $tiersNom;
	var $TiersAdresse;
	var $TiersTel;
	var $TiersTel2; 
	var $TiersCP;
	var $TiersVille;
	var $TiersIdPays;
	var $TiersPays;
	var $Tierscode;
	var $TiersMail;
	var $TiersMail2;
	var $civility_id;
	var $firstname;	
	var $id_contactTiers;
	var $Villegiature;
	// ligne facturation et inscription 4 saisons et locat materiel
	var $lines=array();
	// ligne spécifique Demande Stripe
	var $lines_stripe=array();
	// ligne location materiel mis à disposition
	var $lines_mat_mad=array();
	
	// ligne location rando choisies
	var $lines_rando=array();
	
	// personne recours
	var $pers_civ;
	var $pers_nom;
	var $pers_prenom;
	var $pers_tel;
	
	// pour groupe constitue
	var $derniere_activite;
	
	// pour insertion dans Dolibarr
	var $action; // A pour Ajout, M pour Modifier, S pour Supprimer
	var $id_ag_contact; // identifiant de l'enregistrement image de contact dans agefodd
	var $fk_commande; 
	var $ref_commande ; 
	var $fac_type; //0=Standard, 1=Facture de replacement, 2=Acompte, 3=Deposit invoice, 4=Facture Pro-forma
	var $ref_client;
	var $ref_int;
    var $fk_facture ;
	var $ref_facture ; // utilise  dans facturation
	var $dt_facture; // date du dernier passage en facturation
	var $pt;
	var $fk_acompte;
	var $type_session_cgl; // type de session de Agefodd + 1
	var $fk_soc_rem_execpt; // identifiant de la table societe_remise_except
	// edition contrat 
	var $titre_fac; 
	var $titre_resa; 	
	
	// Lien suivi
	var $fk_dossier;
	var $fk_DosPriorite;
	
	// remise
	var $remfixe;
	var $lbremfixe;	// raison remise fixe
	var $fk_remfixe;	// remise fixe
	
	// pour edition du bulletin
	var $solde;
	// Obsolette - suppression du champ
	//var $ficcmd;
	var $paye;  //total des paiements du BU/LO
	var $nbPmt; // nombre de paiements du BU/LO
	var $ptrem; // total des remises fixes
	var $textremisesfixes;
	
	// pour traitement location
	var $filtrpass;
	
	var $locdateretrait;
	var $locdatedepose;
	var $loclieuretrait;
	var $loclieudepose;
	var $locResa;
	var $fk_sttResa;
	var $SttResa;
	var $locObs;
	var $fk_caution;
	var $lb_caution;
	var $lbedi_caution;
	var $fk_modcaution;
	var $lb_modcaution;
	var $ret_caution;
	var $ret_doc;
	var $top_caution;
	var $top_doc;	
	var $mttcaution;
	var $obscaution;
	var $mttAcompte;
	var $modes_paiement;
	var $obs_matmad;
	var $obs_rando;
	
	// Pour variables mail
	var $ptavecrem;
	
	// pour Réservation
	var $ResaActivite;	// lieu temporaire de stokage de l'info Activité de la résa avant création d'une ligne de détail ==> dans base lieu de retait
	var $place;	// lieu temporaire de stokage de l'info Place de la résa avant création d'une ligne de détail ==> dans base 
	var $heured;	// lieu temporaire de stokage de l'info Date debut de la résa avant création d'une ligne de détail ==> dans base date retrait
	var $heuref;	// lieu temporaire de stokage de l'info Date fin de la résa avant création d'une ligne de détail ==> dans base date depose
	var $lb_place; 
	
	
	
	// edition du contrat 
	var $titre_contrat;
	var $facnumber;
	var $Acomptenumber;
	
	
	
	//valeur d'états commun
	var $BULL_ENCOURS = 0;	// en cours
	var $BULL_ABANDON = 9;	// Abandonné
	var $BULL_ANNULCLIENT = 9.5; // Annulé par le client
	//valeur d'Ã©tat Inscription
	var $BULL_INS = 1;	// inscrit a payÃ© en partie
	var $BULL_PRE_INS =  0.5;	// pre-inscrit
	//valeur d'Ã©tat Location
	var $BULL_PRE_INSCRIT = 0.5;	// contrat réservé
	var $BULL_VAL = 1;	// contrat réservé
	var $BULL_DEPART = 2;	// contrat départ
	var $BULL_RETOUR = 3;	// contrat retour
	var $BULL_CLOS = 4;	// contrat clos
	
	
	//valeur de reglement
	var $BULL_NON_PAYE = 0;	// 
	var $BULL_INCOMPLET = 1;	// n'a pas tout payé
	var $BULL_PAYE = 2;	// a tout payÃ©
	var $BULL_SURPLUS = 3;	// a payé plus suite à ennulation
	var $BULL_REMB = 4;	// a été remboursé
	var $BULL_FACTURE = 5;	// bulletin a été facture
	var $BULL_ARCHIVE = 6;	// bulletin a été archivé
	
	
	//Libellé d'Ã©tat Inscription
	var $LIB_ENCOURS;	// en cours
	var $LIB_PRE_INS;	// pre-inscrit
	var $LIB_INS;	// inscrit a payÃ© en partie
	var $LIB_ABANDON;	// Abandonné
	var $LIB_ANNULCLIENT ; // Annulé par le client
	//Libellé d'Ã©tat Location	
	var $LIB_CNT_ENCOURS;	// en construction
	var $LIB_VAL;	//  contrat réservé
	var $LIB_DEPART;	// contrat départ
	var $LIB_RETOUR;	// contrat retour
	var $LIB_CLOS;	//  contrat clos
	
	
	// libelle des états du règlement
	var $LIB_NON_PAYE;	// 
	var $LIB_INCOMPLET;	// n'a pas tout payé
	var $LIB_PAYE;	// a tout payé
	var $LIB_SURPLUS;	// a payé plus suite à ennulation
	var $LIB_REMB;	// a été remboursé
	
	var $LIB_FACTURE;	// bulletin a été facture	
	var $LIB_FACT_INC;	// bulletin avec facturation non terminée
	var $LIB_ARCHIVE;	// bulletin a été archivé
	var $LIB_CNT_ARCHIVE ; // contrat a ete archive	
	var $LIB_CNT_FACTURE;	// contrat a été facture
	var $LIB_CNT_FACT_INC;	// contrat avec facturation non terminée
	

	
	// Image des statuts

	//image des états Inscription
	var $IMG_ENCOURS = 'statut8.png';	// en cours
	var $IMG_INS = 'statut4.png';	// inscrit a payÃ© en partie
	var $IMG_PRE_INS = 'statut3.png';	// pre-inscrit
	var $IMG_ABANDON = 'statut6.png'; // Abandonné
	var $IMG_ANNULCLIENT = 'statut1.png'; // Annulé par le client
	//image des états Inscription
	var $IMG_VAL = 'statut4.png';	// contrat réservé
	var $IMG_DEPART = 'statut9.png';	// contrat départ
	var $IMG_RETOUR = 'statut0.png';	// contrat retour
	var $IMG_CLOS = 'statut5.png';	// contrat clos
	

	// Image de l'état des réglements
	var $IMG_NON_PAYE = 'statut8.png';	// 
	var $IMG_INCOMPLET = 'statut1.png';	// n'a pas tout payé
	var $IMG_PAYE = 'statut4.png';	// a tout payÃ©
	var $IMG_SURPLUS = 'statut3.png';	// a payé plus suite à ennulation
	var $IMG_REMB= 'statut5.png';	// a été remboursé
	var $IMG_FACTURE = 'statut1.png';	// bulletin a été facture et archivé
	var $IMG_FACT_INC = 'statut7.png';	// bulletin a été facture en cours de construction
	var $IMG_ARCHIVE = 'statut9.png';	// bulletin a été archivé

	var $IMG_CNT_ARCHIVE = 'statut9.png' ; // contrat a ete archive	
	var $IMG_CNT_FACTURE = 'statut4.png';	// contrat a été facture
	var $IMG_CNT_FACT_INC = 'statut7.png';	// contrat a été facture

// ajout variable pour passer à la subsittution des Mail demande Stripe pour UNE demande
	var $stripeModelMail;
	var $stripeNomPayeur;
	var $stripeMtt;
	var $stripeMailPayeur;
	var $stripeSmsPayeur;
	var $stripeUrl;
	var $libelleCarteStripe;

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
		global $langs;
        $this->db = $db;
		$langs->load('cglinscription@cglinscription');
				
		//libellé des  d'état
		$this->LIB_ENCOURS=$langs->trans('STT_STR_ENCOURS');
		$this->LIB_CNT_ENCOURS=$langs->trans('STT_STR_ENCOURS');
		$this->LIB_PRE_INS=$langs->trans('STT_STR_PRE_INS');			
		$this->LIB_INS=$langs->trans('STT_STR_VAL');
		$this->LIB_VAL=$langs->trans('STT_STR_VAL');
		$this->LIB_PRE_RES=$langs->trans('STT_STR_RES_RES');
		
		$this->LIB_CLOS=$langs->trans('STT_STR_CLOS');	
		$this->LIB_DEPART=$langs->trans('CNT_STR_DEPART');	
		$this->LIB_RETOUR=$langs->trans('CNT_STR_RETOUR');	
		$this->LIB_ABANDON=$langs->trans('STT_STR_ABANDON');	
		$this->LIB_ANNULCLIENT=$langs->trans('STT_STR_ANNULCLIENT');	
		
		
		//valeur de reglement	
		$this->LIB_NON_PAYE=$langs->trans('BULL_STR_NON_PAYE');
		$this->LIB_INCOMPLET=$langs->trans('BULL_STR_INCOMPLET');	
		$this->LIB_PAYE=$langs->trans('BULL_STR_PAYE');
		$this->LIB_SURPLUS=$langs->trans('BULL_STR_SURPLUS');
		$this->LIB_REMB=$langs->trans('BULL_STR_REMB');	

		$this->LIB_FACTURE=$langs->trans('BULL_STR_FACTURE');
		$this->LIB_FACT_INC=$langs->trans('BULL_STR_FACT_INC');
		$this->LIB_CNT_FACTURE=$langs->trans('CNT_STR_FACTURE');
		$this->LIB_CNT_FACT_INC=$langs->trans('CNT_STR_FACT_INC');
		
		$this->LIB_ARCHIVE=$langs->trans('BULL_STR_ARCHIVE');
		$this->LIB_CNT_ARCHIVE=$langs->trans('CNT_STR_ARCHIVE');
								
			
        return 1;
    }/* __construct */
    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs, $user;
		$error=0;
	
		// Clean parameters
        if (isset($this->datec)) $this->datec=trim($this->datec);
        if (isset($this->tms)) $this->tms=trim($this->tms);
        if (isset($this->ref)) $this->ref=trim($this->ref);
        if (isset($this->entity)) $this->entity=trim($this->entity);
        if (isset($this->statut)) $this->statut=trim($this->statut);
        if (isset($this->regle)) $this->regle=trim($this->regle);
        if (isset($this->id_client)) $this->id_client=trim($this->id_client);
        if (isset($this->fk_user)) $this->fk_user=trim($this->fk_user);
        if (isset($this->ObsPriv)) $this->ObsPriv=trim($this->ObsPriv);
        if (isset($this->ActionFuture)) $this->ActionFuture=trim($this->ActionFuture);
        if (isset($this->PmtFutur)) $this->PmtFutur=trim($this->PmtFutur);
		
        if (isset($this->fk_facture)) $this->fk_facture=trim($this->fk_facture);
        if (isset($this->fk_persrec)) $this->fk_persrec=trim($this->fk_persrec);
        if (isset($this->f_autori_parentale)) $this->f_autori_parentale=trim($this->f_autori_parentale);
        if (isset($this->f_condition_vente)) $this->f_condition_vente=trim($this->f_condition_vente);
        if (isset($this->f_autre)) $this->f_autre=trim($this->f_autre);
        if (isset($this->TiersTel)) $this->TiersTel=trim($this->TiersTel);
        if (isset($this->TiersTel2)) $this->TiersTel2=trim($this->TiersTel2);
        if (isset($this->pers_tel)) $this->pers_tel=trim($this->pers_tel);		
		if ($this->type == 'Loc' and empty($this->loclieuretrait)) $this->loclieuretrait = $conf->global->MAIN_INFO_SOCIETE_NOM. ' '.$langs->trans('LibBoutiqueCav');
		if ($this->type == 'Loc' and empty($this->loclieudepose)) $this->loclieudepose = trim($this->loclieuretrait);
		if (empty($this->id_contactTiers)) $this->id_contactTiers = trim($this->id_contactTiers);
		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."cglinscription_bull(";
		$sql.= " datec,";
		$sql.= " fk_createur, ";
		$sql.= " tms,";
		$sql.= " ref,";
		$sql.= " typebull,";		
		$sql.= " action,";
		$sql.= " entity,";
		$sql.= " statut,";
		$sql.= " regle,";
		$sql.= " fk_soc,";
		$sql.= " fk_user,";
		$sql.= " fk_facture,";
		$sql.= " fk_persrec,";
		$sql.= " f_autori_parentale,";
		$sql.= " f_condition_vente,";
		$sql.= " fk_origine,";
		$sql.= " f_autre,";
		$sql.= " TiersTel,";
		$sql.= " fk_type_session,";
		$sql.= " RecTel,";
		$sql.= " filtrpass, ";	
		$sql.= " lieuretrait, ";	
		$sql.= " lieudepose,";		
		$sql.= " fk_ContactTiers, ";	
		$sql.= " ObsPriv, ";	
		$sql.= " ActionFuture, ";	
		$sql.= " facturable, ";				
		$sql.= " PmtFutur";						
        
		$sql.= ") VALUES (";
		$now=dol_now('tzuser');		
		$sql.= "'".$this->db->idate($now)."', "	;
		$sql .= "'".$user->id."', ";
		$sql.= "'".$this->db->idate($now)."', ";
        $sql.= " '".(! empty($this->ref) ? $this->ref:"null") ."',";
        $sql.= " '".(! empty($this->type) ? $this->type:"null") ."',";
        $sql.= " '".(! empty($this->action) ? $this->action:"null") ."',";
        $sql.= " '".(! empty($this->entity) ? $conf->entity:0) ."',";		
        $sql.= " '".(! empty($this->statut) ? $this->statut:0) ."',";	
        $sql.= " '".(! empty($this->regle) ? $this->regle:0) ."',";
        $sql.= " '".(! empty($this->id_client) ? $this->id_client:0) ."',";
        $sql.= " '".(! empty($this->fk_user) ? $user->id:0) ."',";
        $sql.= " '".(! empty($this->fk_facture) ? $this->fk_facture:0) ."',";
        $sql.= " '".(! empty($this->fk_persrec) ? $this->fk_persrec:0) ."',";
        $sql.= " '".(! empty($this->f_autori_parentale) ? $this->f_autori_parentale:"null") ."',";
        $sql.= " '".(! empty($this->f_condition_vente) ? $this->f_condition_vente:"null") ."',";
        $sql.= " '".(! empty($this->fk_origine) ? $this->fk_origine:0) ."',";
        $sql.= " '".(! empty($this->f_autre) ? $this->f_autre:"null") ."',";
        $sql.= " '".(! empty($this->TiersTel) ?$this->TiersTel:"null")."',";
        $sql.= " 1,";		
        $sql.= " '".(! empty($this->pers_tel) ?$this->pers_tel:"null")."', ";			
        $sql.= " 0,";
        $sql.= '  "'.$this->loclieuretrait.'" ,';		
        $sql.= '  "'.$this->loclieudepose.'" ,';	
        $sql.= " '".(! empty($this->id_contactTiers) ?$this->id_contactTiers:0)."',";	
        $sql.= "  '".$this->ObsPriv."' , ";			
        $sql.= "  '".$this->ActionFuture."' ,";		
        $sql.= "  '".$this->facturable."' ,";					
        $sql.= "  '".$this->PmtFutur."' ";					 
		$sql.= ")";
		
		$this->db->begin();
	   	dol_syslog(get_class($this)."::create ", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."cglinscription_bull");		
		}
        
        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }/* create */  
		/**
	 * 	Sur bulletin non intégré dans Dolibarr
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function delete()	
	{
		global $conf,$langs,$user;

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM  ".MAIN_DB_PREFIX."cglinscription_bull  WHERE rowid = ".$this->id;
		dol_syslog(get_class($this)."::delete ", LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error().get_class($this)."::delete Error ".$this->error;
			dol_syslog(get_class($this)."::delete Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}//delete
	function deletelignes()
	{
		global $conf,$langs,$user;

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM  ".MAIN_DB_PREFIX."cglinscription_bull_det  WHERE fk_bull = ".$this->id;
		dol_syslog(get_class($this)."::deletelignes ", LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($this->dt_facture)) $this->update_tms();
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog(get_class($this)."::delete Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}
 	/*
	*	Recupère les données des lignes de bulletin issu de la lecture de la base
	* necessite que objetclass->id ait été renseigné
	
	*   @param 	variant	$obj	resultat de la requete
	* Met a jour une variable $gId_act en cas de Inscription
	*/
	function fetch_lines($statut )
	{
		global $langs, $gId_act, $conf;
		
		$w = new CglFonctionCommune($this->db);
		$w1 = new CglFonctionDolibarr($this->db);
		$now = $this->db->idate(dol_now('tzuser'));
		//$this->lines=array();
		$sql='';
				
		$sql="SELECT distinct bd.rowid as rowid, bd.datec, bd.type, fk_banque, fk_bull, bd.fk_contact, qte, pu, rem, rang, bd.NomPrenom ,";
		$sql .= " bd.taille as s_taille, crem.rowid as idrem, crem.libelle as textnom, crem.fl_type as remtype, crem.fk_produit as remprod , bd.reslibelle as textremisegen, ";
		$sql .= " case when bd.type = 2 then (select accountancy_code_sell from ".MAIN_DB_PREFIX."product as remp where remp.rowid = crem.fk_produit) else '' end as rem_code_ventilation, "; 
		if ($this->type == 'Loc')
		{
			$sql .= ' sv.label as Service, sv.ref as RefService, ';
			$sql .= " case when bd.type = 2 then '' else sv.customcode end  as customcode,";
			$sql .= ' bd.materiel, bd.marque, bd.refmat,  bd.qteret, tva_tx, fk_fournisseur, socf.nom as NomFourn, sv.accountancy_code_sell, ';
			$sql .= ' price_ttc as PUjour, price_min_ttc as PUjoursup  , datedepose, dateretrait, lieudepose, lieuretrait, duree,   ';
		}
		elseif ($this->type == 'Insc')
		{
			$sql.="s.rowid as fk_activite,c.lastname as PartNom, c.firstname as PartPrenom, c.birthday, c.email as PartMail, PartTel, InfoPublic, ";
			$sql.="c.zip, c.town,  dp.code as s_poids , bd.poids, bd.age , c.address, c.civility, ";
			$sql.=" case when bd.type = 2 then '' else s.intitule_custo end  as  intitule_custo, s.notes, p.url_loc as url_loc_site, ";
			$sql .= "nb_place, nb_stagiaire,   p.ref_interne, p.fic_infos, p.rowid as id_site, se.s_rdvPrinc, se.s_rdvAlter,dated, heured , heuref,  se.s_TypeTVA, se.s_code_ventil, ";
/*			$sql .= "(select s_duree_act from  ".MAIN_DB_PREFIX."agefodd_session_extrafields as AgSe  where AgSe.fk_object = s.rowid ) as s_duree, ";
			$sql .= "(select COUNT(rowid) from  ".MAIN_DB_PREFIX."agefodd_session_stagiaire as sst  where sst.fk_session_agefodd = s.rowid and  status_in_session  = 0) as nb_preinscrit, ";
voir fetch_line_groupe
			$sql .= "(select COUNT(rowid) from  ".MAIN_DB_PREFIX."agefodd_session_stagiaire as sst  where sst.fk_session_agefodd = s.rowid and  status_in_session  = 2) as nb_inscrit, ";
			$sql .= "(select COUNT(bds.rowid) from llx_cglinscription_bull as b, ".MAIN_DB_PREFIX."cglinscription_bull_det as bds where bds.fk_bull = b.rowid   ";

			$sql .= " and bds.type = 0  and bds.fk_activite = s.rowid ";
			$sql .= " and ((bds.action = 'A' and b.statut > 0) or (bds.action not in ('S','X')  and b.statut = 0 ))) as activite_nbencrins, ";
*/	
			$sql.=" case when isnull(cf.rowid) then cu.rowid else cf.rowid end as MonId,  ";
			$sql.=" case when isnull(cf.rowid) then cu.lastname else cf.lastname end as MonNom,  ";
			$sql.=" case when isnull(cf.rowid) then cu.firstname else cf.firstname end as MonPrenom, ";
			$sql.=" case when isnull(cf.rowid) then cu.user_mobile else cf.phone_mobile end as MonTel, ";
			$sql.=" case when isnull(cf.rowid) then cu.office_phone else cf.phone end as Monperso, ";
			$sql.=" case when isnull(cf.rowid) then cu.email else cf.email end as MonMail, ";
		}
		elseif ($this->type == 'Resa') {
			$sql .= 'lieuretrait as resa_activite , lieudepose as prix, qteret as resa_place , dateretrait as heured , datedepose as heuref ,  p.ref_interne, p.fic_infos, p.rowid as id_site, ';
		}
		$sql .= " '".$now."' as Maintenant,  cp.libelle as mode_pmt, ";
		$sql .= "bd.ficbull , ";
		$sql.="fk_mode_pmt, organisme, tireur, num_cheque, pt, datepaiement, bd.fk_facture, bd.fk_paiement, bd.lb_pmt_neg, bd.fk_rdv, bd.observation, ";
		$sql.="action, fk_linecmd as fk_line_commande ,fk_linefct,  bd.fk_activite, bd.fk_produit, b.rowid as fk_banqueCGL, bk.rappro , bk.fk_bordereau, bcq.ref as refbordereau , bd.fk_agsessstag ";
		$sql .= ", (SELECT max(1)	  	FROM  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd5 		WHERE  bd5.type = 5 and bd5.action not in ('X','S') 
			and bd.fk_bull = bd5.fk_bull  			AND ( bd5.fk_raisrem = bd.rowid) 			and bd.fk_mode_pmt = 56 and bd.type = 1 and bd.action not in ('X','S')) 
				as pmt_StripeAut, bd.fk_raisrem ";
		$sql.=" FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd  ";
		
		if ($this->type == 'Loc')
		{
			$sql.="  LEFT JOIN ".MAIN_DB_PREFIX."product as sv on sv.rowid = bd.fk_activite  and fk_product_type = 1";
			$sql.="  LEFT JOIN ".MAIN_DB_PREFIX."societe as socf on socf.rowid = bd.fk_fournisseur ";
		}
		if ($this->type == 'Insc')
		{
			$sql.="  LEFT JOIN ".MAIN_DB_PREFIX."socpeople as c on c.rowid = bd.fk_contact ";
			if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND c.statut <> 0 ";	
			//$sql.="  LEFT JOIN ".MAIN_DB_PREFIX."socpeople_extrafields as ce on c.rowid = ce.fk_object ";
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."cgl_c_poids as dp on dp.rowid = bd.poids";
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX.'agefodd_session as s on s.rowid = bd.fk_activite ';
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX.'agefodd_session_extrafields as se on s.rowid = se.fk_object ';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_place as p on fk_session_place=p.rowid ';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_session_calendrier on fk_agefodd_session = s.rowid ';
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_formateur as sf on sf.fk_session = s.rowid";
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formateur as f on sf.fk_agefodd_formateur = 	f.rowid";
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."socpeople as cf on cf.rowid = f.fk_socpeople";
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."user as cu on cu.rowid = f.fk_user";
			$sql.="  LEFT JOIN ".MAIN_DB_PREFIX."product as sv on sv.rowid = s.fk_product  and fk_product_type = 1";
		}
		if ($this->type == 'Resa')
		{
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_place as p on qteret = p.rowid ';
		}
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp on cp.id = fk_mode_pmt";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."cgl_c_raison_remise as crem on crem.rowid = fk_raisrem";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."bank as bk on bk.rowid = bd.fk_banque";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."bordereau_cheque as bcq on bcq.rowid = bk.fk_bordereau";
		
		//$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."cgl_pmt_bank as cpb on cpb.code_paiement = cp.code";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."bank_account as b on cp.fk_cpt_bq = b.rowid";
		
		$sql.=" WHERE ";
		if ($this->type == 'Insc')
		{
			$sql .= " (isnull(sf.rowid ) or  sf.rowid = (select min(rowid) from ".MAIN_DB_PREFIX."agefodd_session_formateur where fk_session = s.rowid)) and "; 
		}
		
		$wbudet = new BulletinLigne($this->db, $this->type);	
		$sql.="  bd.type != ".$wbudet->LINE_STRIPE." and ";
		unset($wbudet);
		if ($statut == 99) 		$sql.="  bd.action != 'X' and";
		elseif ($statut != 100) $sql.="  bd.action not in ('X','S') and "; 
		$sql.="  fk_bull = '".$this->id."'" ;
		
		$sql .= ' order by ';	
		if ($this->type == 'Loc') 			$sql .=  'bd.type, customcode, bd.refmat , ';
		//if ($this->type == 'Insc')			$sql .=  'bd.type, intitule_custo, dated, ';
// MDUo		
		if ($this->type == 'Insc')			$sql .=  'dated asc, bd.type, p.ref_interne, ';
// MDUf		
		$sql .= 'rang';

		dol_syslog(get_class($this).'::fetch_lines ', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{	
			$wdep = new CglDepart ($this->db);
			$num = $this->db->num_rows($result);
			$i = 0; $j=0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$line = new BulletinLigne($this->db, $this->type);					
				$line->id					= $objp->rowid;							
				$line->type_enr				= $objp->type;
				$line->fk_bull				= $objp->fk_bull;
				$line->id_act				= $objp->fk_activite;
				if ($this->type == 'Insc' and $i == 0) $gPremId_act = $objp->gId_act;

				if ($line->type_enr == $line->LINE_ACT  ) {
					if ($this->type  == 'Insc') 	$line->taux_tva	=$w1->taux_TVAstandard() * $objp->s_TypeTVA;
					else  	$line->taux_tva	=$w1->taux_TVAstandard();
				}
				else $line->taux_tva = '';				
				$this->derniere_activite	= $objp->fk_activite;			
				$line->qte					= $objp->qte;
				$line->pu					= $objp->pu;
				$line->remise_percent		= $objp->rem;
				if 	($objp->rem == 0) 	$line->remise_percent = '';	
				elseif ((int)$line->remise_percent == $line->remise_percent) 
					$line->remise_percent = (int)$line->remise_percent;	
				$line->rangdb				= $objp->rang;
				$line->action				= $objp->action;
				$line->id_part				= $objp->fk_contact;
				$line->PartNom				= $objp->PartNom;
				$line->PartPrenom			= $objp->PartPrenom;
				//$dataCglInscription = new CglInscription ($this->db);
				//$line->PartDateNaissance	= $dataCglInscription->transfDateFr($objp->birthday);
				$line->PartDateNaissance	= $w->transfDateFr($objp->birthday);	
				//unset ($dataCglInscription);
				$line->PartAge				= intval($objp->age );		
				if (empty($objp->age)) 	$line->PartENF		='Adulte';
				else 					$line->PartENF		= ($line->PartAge <=12)? 'Enfant':'Adulte';
				$line->PartMail				= $objp->PartMail;			
				$line->PartTel				= $objp->PartTel;			
				$line->PartCP				= $objp->zip;			
				$line->PartVille			= $objp->town;		
				$line->PartTaille			= $objp->s_taille;
				$line->PartPoids			= $objp->poids;
				//$line->PartAge				= $objp->s_age;		
				$line->PartAdresse			= $objp->address;	
				$line->PartCiv				= $objp->civility;	
				$line->fk_produit			=$objp->fk_produit;		
				$line->activite_label		=$objp->intitule_custo;
				$line->dep_notes			=$objp->notes;
				$line->url_loc_site			=$objp->url_loc_site;
				$line->activite_nbmax		=$objp->nb_place;

				$line->activite_nbpreinscrit = 0;
				if (empty($line->id_act))$TabAct[$line->id_act] = $line->id_act;
				//$line->activite_nbencrins = $wdep->NbPartDep(0,$line->id_act);
				//$line->activite_nbinscrit = $wdep->NbPartDep(1,$line->id_act);
				//if (empty($line->activite_nbinscrit)) $line->activite_nbinscrit	=0;
				//if (empty($line->activite_nbencrins)) $line->activite_nbencrins	=0;
/*
				$line->activite_nbinscrit	=$objp->nb_inscrit;
				$line->activite_nbpreinscrit=$objp->nb_preinscrit;	
				$line->activite_nbencrins   = $objp->activite_nbencrins;
				
*/				
				
				$line->activite_lieu		=$objp->ref_interne;
				$line->id_site			=$objp->id_site;
				$line->infopublic			=$objp->InfoPublic;				
				$line->ficsite =			 $objp->fic_infos;
				$line->activite_dated		=$objp->dated;
				$line->activite_heured		=$objp->heured;
				$line->activite_heuref		=$objp->heuref;
				if ( $this->type ==  'Loc') 				$line->fk_code_ventilation = $objp->accountancy_code_sell;
				elseif ( $this->type ==  'Insc') $line->fk_code_ventilation = $objp->s_code_ventil ;
				if ($line->type_enr == $line->LINE_BC  ) $line->fk_code_ventilation = $objp->rem_code_ventilation;	
				$line->activite_rdv			=$objp->fk_rdv;
				if (empty($objp->fk_rdv))	$line->activite_rdv = 1;
				//$line->rdv_lib				=($line->activite_rdv == 1)?$objp->s_rdvPrinc:$objp->s_rdvAlter;
				$line->rdv_lib				=$objp->s_rdvPrinc;				
				$line->rdv2_lib				=$objp->s_rdvAlter;
				$line->observation			=$objp->observation;	
				$line->act_moniteur_nom		=$objp->MonNom;
				$line->act_moniteur_id		=$objp->MonId;
				$line->act_moniteur_prenom	=$objp->MonPrenom;
				if ($objp->MonTel) 	$line->act_moniteur_tel	=$objp->MonTel;
				else 				$line->act_moniteur_tel	=$objp->Monperso;
				$line->act_moniteur_email	=$objp->MonMail;
				/*
				if ($objp->DepartPV_Adlt) $line->pu_adlt = $objp->DepartPV_Adlt;
				elseif ($objp->ActivitePV_Adlt) $line->pu_adlt = $objp->ActivitePV_Adlt ;  
				elseif ($objp->ProduitPV_AdltV) $line->pu_adlt = $objp->ProduitPV_Adlt ;  
				else	$line->pu_adlt = 0;	
				if ($objp->DepartPV_Enf) $line->pu_enf = $objp->DepartPV_Enf;
				elseif ($objp->ActivitePV_Enf) $line->pu_enf = $objp->ActivitePV_Enf ;  
				elseif ($objp->ProduitPV_Enf) $line->pu_enf = $objp->ProduitPV_Enf ;  
				else	$line->pu_enf = 0;


				if ($objp->DepartPV_Enf) $line->pu_enf = $objp->DepartPV_Enf;
				elseif ($objp->ActivitePV_Enf) $line->pu_enf = $objp->ActivitePV_Enf ;  
				elseif ($objp->ProduitPV_Enf) $line->pu_enf = $objp->ProduitPV_Enf ;  
				else	$line->pu_enf = 0;
				*/
				// location
				$line->fk_service	=$objp->fk_activite;
				$line->service		=$objp->Service;
				$line->refservice	=$objp->RefService;
				$line->materiel		=$objp->materiel;
				$line->fk_fournisseur	=$objp->fk_fournisseur;
				$line->NomFourn		= $objp->NomFourn;
				$line->marque		=$objp->marque;
				$line->refmat		=$objp->refmat .' - '.$objp->marque;
				$line->identmat		=$objp->refmat;
				if ($objp->datedepose > 0) $line->datedepose	=$objp->datedepose;
				if ($objp->dateretrait > 0) $line->dateretrait	=$objp->dateretrait;
				if ($this->type == 'Loc' and !empty($line->identmat)) { 
					$wloc =  new CglLocation($this->db);
					$line->fl_conflitIdentmat	= $wloc->IsMatDejaLoue($line->fk_service, $line->identmat, $line->id, $line->dateretrait, $line->datedepose, $line->lstCntConflit);
					unset($wloc);
				}

				$line->taille		=$objp->taille;
				$line->NomPrenom	=$objp->NomPrenom;
				$line->PUjour		=$objp->PUjour;
				$line->PUjoursup	=$objp->PUjoursup;
				
				$line->qteret		=$objp->qteret;
				$line->duree			=$objp->duree;
				/*if ($line->duree == 0) $line->duree = '';
				elseif ((int)$line->duree == $line->duree) $line->duree = (int)$line->duree;*/
				$line->lieudepose			=$objp->lieudepose;
				$line->lieuretrait			=$objp->lieuretrait;	

				if ($this->type <> 'Loc') $line->duree				= $objp->s_duree;					
				/* champ pour nomprenom de la liste des location et pour le contrat papier
				 si service = Transfert ==> si un lieu vide  nontrajet = lieu rempli
										sinon si 2 lieu identique, nomtrajet = lieudepsoe
										sinon nomtrajet = concaténation des deux lieux
				sinon nomtrajet = nomprenom
				*/
				$temp = strstr($line->service , 'TRANSFERT');
				if (empty($temp)) {		
					$line->NomTrajet = $line->NomPrenom;			
				}
				else {
					if (empty($line->lieudepose))  $line->NomTrajet = $line->lieuretrait;
					elseif (empty($line->lieuretrait)) $line->NomTrajet = $line->lieudepose;
					else $line->NomTrajet = $line->lieuretrait.' - ' . $line->lieudepose;
				}
				/*if ($line->pt == 0) $line->pt = '';
				else*/
				if ((int)$line->pt == $line->pt) $line->pt = (int)$line->pt;
				$line->ficsite = $objp->fic_infos;
			
				// paiement
				if ($this->type <> 'Resa') {
					$line->id_mode_paiement		=$objp->fk_mode_pmt;
					$line->mode_paiement		=$objp->mode_pmt;
					if (!empty ($this->modes_paiement)  and !empty($objp->mode_pmt))	$this->modes_paiement .= ' - ';
					$this->modes_paiement		.=$objp->mode_pmt;
					$line->organisme			=$objp->organisme;
					$line->tireur				=$objp->tireur;
					$line->num_cheque			=$objp->num_cheque;
					$line->montant				=$objp->pt;
					$line->pmt_neg  		    =$objp->lb_pmt_neg;		
					 
					$line->mttremfixe = $objp->pt;							
					$line->fk_remgen	= $objp->idrem;	
					$line->textnom		= $objp->textnom;						
					if ($line->type_enr  ==  $line->LINE_BC ) $this->textremisesfixes .= '"'.$line->textnom.'" montant :'.$line->mttremfixe. '  ';
					$line->fl_type		= $objp->fl_type;		
					$line->fk_remprod	= $objp->remprod;	
					$line->textremisegen= $objp->textremisegen;					
		
					$line->date_paiement		=$objp->datepaiement;
					$line->fk_line_commande			=$objp->fk_line_commande;
					
					$line->fk_line_facture		=$objp->fk_linefct;
					
					
					$line->fk_banque			=$objp->fk_banque;
					$line->fk_paiement			=$objp->fk_paiement;
					$line->fk_facture			=$objp->fk_facture;
					$line->fk_accountCGL		=$objp->fk_banqueCGL;	
					$line->pmt_rappro			=$objp->rappro;	
					$line->pmt_depose			=$objp->fk_bordereau;	
					$line->pmt_StripeAut		=$objp->pmt_StripeAut;	
					$line->pmt_refbordereau			=$objp->refbordereau;
					$line->fk_raisrem		=$objp->fk_raisrem;		
					
					
				}
				$line->ficbull				= $objp->ficbull;	
				$line->fk_agsessstag		=$objp->fk_agsessstag;
				
				// réservation				
				$line->resa_activite		=$objp->resa_activite;
				$line->prix					=$objp->prix;
				$line->qteret				=$objp->qteret;
				$line->resa_place			=$objp->resa_place;
				
				$this->lines[$i] = $line;
				$j++;
				$i++;
			}	
				
			// Gestion Nombre participants
			// Un tableau est constitué dans la lectures des lignes, pour récupérer la iste des départs de ce BU
			//Ensuite, on envoir NbPartDep (
			if ($bull->type == 'Insc' and !empty($TabAct))  {
				foreach ($TabAct as $key => $val) {
					if (!empty($key)) {
						$nbencrins = $wdep->NbPartDep(0,$key);
						$nbpreinscrit = $wdep->NbPartDep(1,$key);
						$nbinscrit = $wdep->NbPartDep(2,$key);
						if (empty($nbencrins)) $nbencrins	=0;				
						if (empty($nbinscrit)) $nbinscrit	=0;			
						if (empty($nbpreinscrit)) $nbpreinscrit	=0;
						if ( !empty($this->lines)) {
							foreach ($this->lines as $line) {
								$line->activite_nbencrins = $nbencrins;
								$line->activite_nbpreinscrit = $nbpreinscrit;
								$line->activite_nbinscrit = $nbinscrit;					
							} //	 foreach
						}
					}
				} //	 foreach
			}
		
			$this->nblignecontratLoc = $j;
			$this->nblignebulletin = $result;
			$this->db->free($result);
			unset ($w);
			return $j;	

		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::fetch_lines '.$this->error,LOG_ERR);
			unset ($w);
			return -3;
		}
	
	} /* fetch_lines */
	

 	/*
	*	Recupère les données d'une ligne de bulletin issu de la lecture de la base
	* necessite que objetclass->id ait été renseigné
	*   @param 	variant	$obj	resultat de la requete
	*/
	function fetch_lines_MatMad($statut )
	{
		global $bull;
		$w = new CglInscription($this->db);
		$now = $this->db->idate(dol_now('tzuser'));
		//$this->lines_mat_mad=array();
		$sql='';
		$sql="SELECT distinct bm.rowid as rowid, fk_bull, fk_mat_mad,   qte, qteret, datedepose, dateretrait, bm.ordre, cm.libelle as lb_mat_mad, cm.code,";
		$sql .= "p.rowid as fk_service ,p.ref  , p.label as lb_service,";	
		$sql .= " '".$now."' as Maintenant ";
		
		$sql.=" FROM ".MAIN_DB_PREFIX."cglinscription_bull_mat_mad as bm  ";
		$sql.="  LEFT JOIN ".MAIN_DB_PREFIX."cgl_c_mat_mad as cm on cm.rowid = bm.fk_mat_mad";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on p.rowid = cm.fk_service';
		$sql.=" WHERE  fk_bull = '".$bull->id."'" ;
		$sql .= ' order by p.ref, bm.ordre ASC';
		
		dol_syslog(get_class($this).'::fetch_lines_MatMad ', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{	
			$num = $this->db->num_rows($result);
		
			$i = 0; $j=0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$line = new BulletinLigneMatMad($this->db);					
				$line->id					= $objp->rowid;	
				$line->fk_bull				= $objp->fk_bull;
				$line->fk_mat_mad				= $objp->fk_mat_mad;
				$line->lb_mat_mad				= $objp->lb_mat_mad;		
				if (substr($objp->lb_mat_mad, 0, 5) == 'Autre' and substr($objp->lb_mat_mad, strlen($objp->lb_mat_mad)-4) == 'note' )
							$line->lb_mat_mad_tot = $bull->obs_matmad;
				else $line->lb_mat_mad_tot			= $objp->lb_mat_mad;				
				$line->qte					= $objp->qte;
				
				$line->qteret					= $objp->qteret;
				
				if (!empty($line->qteret) or $line->qteret > 0) 
					$line->lb_ret_mat = '(Retour :'.$line->qteret.' elements rendus)';
				$line->datedepose				= new DateTime($objp->datedepose);	
				$line->dateretrait				= new DateTime($objp->dateretrait);	
				$line->ordre					= $objp->ordre;
				$line->lb_service				= $objp->lb_service;
				if (!empty($objp->ref)) $line->lb_service .= ' ( '. $objp->ref.' )';
				$line->fk_service				= $objp->fk_service;					
				$this->lines_mat_mad[$i] = $line;
				$j++;
				$i++;
			}			
			$this->db->free($result);
			unset ($w);
			return $j;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::fetch_lines_MatMad '.$this->error,LOG_ERR);
			unset ($w);
			return -3;
		}
	} /* fetch_lines_MatMad */

	function fetch_lines_stripe($statut =0)
	{
		global $langs, $gId_act, $conf;
		
		$sql='';				
		$sql = "SELECT distinct bd.rowid as id, bd.NomPrenom as Nompayeur, bd.pt as montant, bd.lieudepose as mailpayeur,  bd.tireur as smspayeur, ";
		$sql .= "bd.fk_facture as fk_acompte, fac.ref as RefAcompte, bd.datepaiement as date_paiement,  bd.fk_bull  , ";
		$sql .= "bd.dateretrait as datederniereRelance, bd.datedepose as dateenvoi, bd.qteret as nbRelance, bd.fk_activite   as ModelMail, bd.action, ";
		$sql .= " bd.reslibelle  as stripeUrl, bd.ficbull as libelleCarteStripe , bd.fk_agsessstag as fk_soc_rem_execpt, bd.fk_banque as fk_bank, bd.fk_raisrem as fk_bulldet, ";
		$sql .= "bd.fk_paiement , bd.marque as derRelMailSms ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd LEFT JOIN ".MAIN_DB_PREFIX."facture as fac on fac.rowid = bd.fk_facture"  ;
		$sql.=" WHERE ";		
		$wbudet = new BulletinLigne($this->db, $this->type);	
		$sql.="  bd.type = ".$wbudet->LINE_STRIPE." and ";
		unset($wbudet);
		if ($statut == 99) 		$sql.="  bd.action != 'X' and";
		elseif ($statut != 100) $sql.="  bd.action not in ('X','S') and "; 
		$sql.="  fk_bull = '".$this->id."'" ;
		
		$sql .= ' order by ';	
		$sql .= 'rang';

		dol_syslog(get_class($this).'::fetch_lines_stripe ', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{	
			$wdep = new CglDepart ($this->db);
			$num = $this->db->num_rows($result);
			$i = 0; $j=0;
			while ($i < $num)
			{
				$line = new BulletinDemandeStripe($this->db, $this->type);					
				
                $obj = $this->db->fetch_object($resql);
				$line->id = $obj->id;
				$line->montant = $obj->montant;
				$line->Nompayeur = $obj->Nompayeur;
				$line->mailpayeur = $obj->mailpayeur;
				$line->smspayeur = $obj->smspayeur;
				$line->fk_acompte	 = $obj->fk_acompte;			
				$line->RefAcompte =$obj->RefAcompte;	
				$line->ModelMail	 = $obj->ModelMail;
				$line->dateenvoi	 = $obj->dateenvoi;
				$line->dateDerniereRelance	 = $obj->datederniereRelance;	
				$line->fk_bull	 = $obj->fk_bull;
				$line->fk_bank	 = $obj->fk_bank;
				$line->fk_bulldet	 = $obj->fk_bulldet;
				$line->date_paiement	 = $obj->date_paiement;
				$line->nbRelance	 = $obj->nbRelance;
				$line->fk_paiement	 = $obj->fk_paiement;
				$line->fk_soc_rem_execpt	 = $obj->fk_soc_rem_execpt;
				$line->action	 = $obj->action;
				$line->stripeUrl	 = $obj->stripeUrl;
				$line->libelleCarteStripe	 = $obj->libelleCarteStripe;
				$line->derRelMailSms	 = $obj->derRelMailSms;				
				$this->lines_stripe[] = $line;
				$i++;
			}			
			$this->db->free($result);
			return $i;	
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::fetch_lines_stripe '.$this->error,LOG_ERR);
			return -3;
		}
	
	}//fetch_lines_stripe
 	/*
	*	Recupère les données d'une ligne de bulletin issu de la lecture de la base
	* necessite que objetclass->id ait été renseigné
	*   @param 	variant	$obj	resultat de la requete
	*/
	function fetch_lines_rando($statut )
	{
		$w = new CglInscription($this->db);
		$now = $this->db->idate(dol_now('tzuser'));
		//$this->lines_rando=array();
		$sql='';
		$sql="SELECT distinct bm.rowid as rowid, fk_bull, fk_rando,   qte,qteret,   bm.ordre, cm.libelle as lb_rando, cm.code,";
		$sql .= "p.rowid as fk_service ,p.ref  , p.label as lb_service, p.description as desc_service, ";	
		$sql .= "'".$now."' as Maintenant ";
		
		$sql.=" FROM ".MAIN_DB_PREFIX."cglinscription_bull_rando as bm  ";
		$sql.="  LEFT JOIN ".MAIN_DB_PREFIX."cgl_c_rando as cm on cm.rowid = bm.fk_rando";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on p.rowid = cm.fk_service';
		$sql.=" WHERE  fk_bull = '".$this->id."'" ;
		$sql .= ' order by cm.fk_service, bm.ordre ASC';
		dol_syslog(get_class($this).'::fetch_lines_rando ', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{	
			$num = $this->db->num_rows($result);
		
			$i = 0; $j=0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$line = new BulletinLigneRando($this->db);					
				$line->id					= $objp->rowid;
				$line->fk_bull				= $objp->fk_bull;
				$line->fk_rando				= $objp->fk_rando;
				$line->lb_rando				= $objp->lb_rando;
				$line->qte					= $objp->qte;
				$line->qteret					= $objp->qteret;
				if (!empty($line->qteret) or $line->qteret > 0) $line->lb_ret_rando = '(Retour :'.$line->qteret.' topos rendus)';
				$line->ordre					= $objp->ordre;
				$line->lb_service				= $objp->lb_service;
				if (!empty($objp->ref)) $line->lb_service .= ' ( '. $objp->ref.' )';
				$line->fk_service				= $objp->fk_service;
				$line->desc_service				= $objp->desc_service;
								
				$this->lines_rando[$i] = $line;

				$j++;
				$i++;
			}			
			$this->db->free($result);
			unset ($w);
			return $j;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::fetch_lines_rando '.$this->error,LOG_ERR);
			unset ($w);
			return -3;
		}
	} /* fetch_lines_rando */
	
	/**
     *  Charge le bulletin complet  avec filtre univoque : statuts = 0 ou rowid = valeur
     *
     *  @status	int			0  ==> BULLETIN EN COURS, chargemment de toutes les lignes sauf ligne S et X
	 *						1 ==> ?? voir actions_sendmails.inc.php
	 *						-1 ==> avec rowid precis, chargemment de toutes les lignes sauf ligne S et X
	 *						99 ==>  chargement de toutes les lignes sauf X 
	 *						100 ==>  chargement de toutes les lignes 
	 *	$id     				==> identifiant bulletin ,
     *  @return int          	<0 if KO, >0 if OK
     */
	function fetch_complet_filtre($status = 0, $id, $label = '' )
	{
		$ret_entete = $this->fetch_entete($status, $id , $label);
		if ($ret_entete) $ret = $this->fetch_lines($status );
		if ($ret >= 0) $ret1 = $this->fetch_lines_stripe($status );
	} //fetch_complet_filtre
	/*
	* Charge un bulletin comme bulletin de départ de groupe (avec regroupement des participations sur la participation qui  porte le prix)	
	*/
	function fetch_bull_group_fact( )
	{
		$ret_entete = $this->fetch_entete(1, $this->id );
		$this->fetch_lines_groupes();
	} //fetch_bull_group_fact

	/*
	* Charge l'entete d' bulletin/contrat
	*
	*	$param int status	=0 charge le bulletin/contrat en cours, =1 charge le bulletin/contrat id
	*	$param int id		= '' charge le bulletin/contrat en cours, >0 identifiant bulletin à charger
	* 	retour int  1 = OK, -1 = ERR
	*/
	function fetch_entete($status = 0, $id , $label ="")
	{ 
	   	global $langs, $conf;	
		
		if (empty($id ) and !empty($label)) {
			// recherche de l'id
			$sql = "SELECT distinct rowid ";	
			$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull ";				
			$sql.= " WHERE ref = '".$label."'";
			
			
			dol_syslog(get_class($this)."::fetch_entete sur label", LOG_DEBUG);
			$resql=$this->db->query($sql); 

			if ($resql)
			{		
				if ($this->db->num_rows($resql))
				{
					$obj = $this->db->fetch_object($resql);
					$this->id   		= $obj->rowid;
					$id   		= $obj->rowid;
				}
			}
		}
		$sql = '';
			
		/* recherche tete du bulletin */
        $sql = "SELECT distinct t.rowid, t.fk_dossier, d.fk_priorite as fk_DosPriorite, d.libelle as DosLib,  t.ref, typebull, t.datec, nom, s.email, s.town, s.fk_pays,c.label as country, c.code as country_code, ";
		$sql .= " s.address, s.zip, s.phone as  TiersTel,se.s_tel2 , se.s_email2 , ";
		$sql.= "  t.action, t.fk_soc as fk_tiers,t.statut, t.regle, t.fk_facture, t.fk_user, t.fk_persrec,";
		$sql.= " f_condition_vente, f_autre, pc.lastname as PersNom, pc.firstname as PersPrenom, pc.phone_perso as Persperso, pc.phone_mobile as PersTel, t.fk_ContactTiers as id_contactTiers, ";
		$sql.= " pc.civility as PersCiv, poste as parente , t.fk_acompte, t.fk_cmd as fk_commande, t.ref_cmd as ref_commande, t.facturable, ";
		// - obsolette suppresion champ 
		//$sql.= "ficcmd,";
		$sql.= " t.fk_origine, cr.label as origine ,  ";
		$sql.= " t.Villegiature, fk_soc_rem_execpt, sre.rowid as id_soc_rem_execpt, t.obs_matmad, t.obs_rando, t.fk_modcaution, t.obscaution, cp.libelle as lb_modcaution,  ";
		/*
		*
			var $LINE_ACT = 0;				// ligne d'activité ou matériel loué
			var $LINE_PMT = 1;				// Ligne de paiement
			var $LINE_BC = 2;				// ligne pour Bon cadeau et remise fixe
			var $LINE_RANDO = 3;
			var $LINE_ACC = 4;
			var $LINE_STRIPE = 5;
		*
		*/
			$sql.= "  t.fk_type_session, ";
			$sql.= " sum(case when bd.type=0 and bd.action not in('S','X') and fk_type_session = 1  then bd.pu* bd.qte*(100- bd.rem)/100 else 0 END ) as ptind,  ";
			$sql.= " sum(case when bd.type=2 and bd.action not in('S','X')   then bd.pt END ) as ptrem,  ";
			$sql.= " sum(case when bd.type=1 and bd.action not in ('S','X')  then bd.pt else 0 END) as paye, ";
			$sql.= " sum(case when bd.type=1 and bd.action not in ('S','X')  then 1 else 0 END) as nbPmt, ";		
			$sql.= " t.datedepose, t.dateretrait, t.lieuretrait, t.lieudepose,  ObsReservation, fk_sttResa, t.observation,";

			$sql.= " t.lieuretrait as ResaActivite,t.datedepose as heuref, t.dateretrait as heured, t.fk_type_session as place, pl.ref_interne as lb_place, ";
	
		$sql.= "   sr.libelle as SttResa, fk_caution, cc.libelle as lb_caution, ret_caution, ret_doc, top_caution,top_doc,  mttCaution, mttAcompte ";
        $sql.= ", filtrpass ";
		$sql .= ", f.ref as facnumber, fa.ref as Acomptenumber ";

		$sql .= ", t.abandon, ObsPriv,  ActionFuture, PmtFutur";	
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as t";
		$sql.=" left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on  bd.fk_bull = t.rowid ";
		if ($conf->cahiersuivi) 
			$sql.=" left join ".MAIN_DB_PREFIX."cglavt_dossier as d on d.rowid = t.fk_dossier ";	
		$sql.=" left join ".MAIN_DB_PREFIX."socpeople as pc on pc.rowid = t.fk_persrec";
		$sql.=" left join ".MAIN_DB_PREFIX."cgl_c_stresa as sr on sr.rowid = fk_sttResa";
		$sql.=" left join ".MAIN_DB_PREFIX."facture as f on f.rowid = t.fk_facture";
		$sql.=" left join ".MAIN_DB_PREFIX."facture as fa on f.rowid = t.fk_acompte";
		$sql.=" left join ".MAIN_DB_PREFIX."cgl_c_caution as cc on cc.rowid = fk_caution";
		$sql.=" left join ".MAIN_DB_PREFIX."facture as facc on facc.rowid = t.fk_acompte";
		$sql.=" left join ".MAIN_DB_PREFIX."societe_remise_except as sre on sre.fk_soc = t.fk_soc and sre.fk_facture_source = facc.rowid";
		$sql.=" left join ".MAIN_DB_PREFIX."c_input_reason as cr on cr.rowid = t.fk_origine";	
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp on cp.id = fk_modcaution";
		$sql.=" left join ".MAIN_DB_PREFIX."agefodd_place as pl on pl.rowid = t.fk_type_session ";	// uniquement pour resa
        $sql.= " , (".MAIN_DB_PREFIX."societe as s";		
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON s.fk_pays = c.rowid";
		$sql.=" left join ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object )";	
	
        $sql.= " WHERE s.rowid = t.fk_soc ";
		if ($status == 0 or $id=='')
			$sql.= " and t.statut = ".$this->BULL_ENCOURS;
		else     $sql.= " and t.rowid = ".$id;
		$sql.=" 		GROUP BY t.rowid, ref, typebull, t.datec, nom, s.email, s.town,  s.fk_pays,c.label , s.address,s.zip, s.phone ,se.s_tel2 , se.s_email2 , t.action, t.fk_soc,t.statut, t.regle, t.fk_facture, t.fk_user, ";
        $sql.= "t.fk_persrec, f_condition_vente, f_autre, pc.lastname , pc.firstname , pc.phone_perso, pc.phone_mobile , t.fk_ContactTiers ,";
        $sql.= " pc.civility , poste  , t.fk_acompte, t.fk_cmd , t.ref_cmd,";
		// - obsolette suppresion champ 
		//$sql.= "ficcmd,";
		$sql.= "  t.fk_origine, cr.label ,   t.Villegiature, t.fk_type_session,";
        $sql.= " fk_soc_rem_execpt,  sre.rowid, t.obs_matmad, t.obs_rando, t.fk_modcaution, t.obscaution, cp.libelle ,";
        $sql.= " t.datedepose, t.dateretrait, t.lieuretrait, t.lieudepose,  t.ObsReservation, fk_sttResa, t.observation,   sr.libelle , fk_caution, cc.libelle , ret_caution, ret_doc, top_caution,";
        $sql.= "  top_doc,  mttCaution, mttAcompte, filtrpass ,  t.abandon, ObsPriv ,  ActionFuture, PmtFutur";
		$sql .= ", f.ref, fa.ref ";

    	dol_syslog(get_class($this)."::fetch_entete", LOG_DEBUG);
        $resql=$this->db->query($sql); 

		if ($resql)
        {		
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                $this->id   		= $obj->rowid;
                $this->ref   		= $obj->ref;
                $this->facturable   = $obj->facturable;				
                $this->fk_dossier   		= $obj->fk_dossier;	
                $this->fk_DosPriorite   		= $obj->fk_DosPriorite;	
                $this->DosLib   		= $obj->DosLib;		
				
                $this->type   		= $obj->typebull;
				if (empty($obj->typebull) or $obj->typebull == 'null')
				{		
					if (substr($obj->ref, 0, 2) == 'BU' ) $this->type = 'Insc';
					elseif (substr($obj->ref, 0, 2) == 'LO' ) $this->type = 'Loc';
					elseif (substr($obj->ref, 0, 2) == 'RE' ) $this->type = 'Resa';
				}
                $this->datec   		= $obj->datec;
                $this->action   	= $obj->action;
                $this->tiersNom   	= $obj->nom;
                $this->TiersVille   = $obj->town;
                $this->TiersIdPays   = $obj->fk_pays;
                $this->TiersPays   = $obj->country;
				$this->Tierscode  = $obj->country_code;
                $this->TiersTel  	= $obj->TiersTel;
                $this->TiersTel2  	= $obj->s_tel2;
                $this->TiersMail   	= $obj->email;
                $this->TiersMail2 	= $obj->s_email2;
                $this->TiersAdresse   = $obj->address;
                $this->TiersCP   	= $obj->zip;
				$this->id_client	= $obj->fk_tiers;
                $this->Villegiature   	= $obj->Villegiature;
				$this->statut		= $obj->statut;
				$this->abandon		= $obj->abandon;
				$this->ObsPriv		= $obj->ObsPriv;
				$this->PmtFutur		= $obj->PmtFutur;
				$this->ActionFuture		= $obj->ActionFuture;	
				
				$this->regle		= $obj->regle;
				$this->id_contactTiers = $obj->id_contactTiers;
				
				if ($this->fk_sttResa) $this->titre_resa=$langs->trans('TiResa');
				else $this->titre_resa='';
				
				$this->fk_facture	= $obj->fk_facture;
				$this->fk_user		= $obj->fk_user;
				$this->fk_persrec	= $obj->fk_persrec;
				$this->f_condition_vente=$obj->f_condition_vente;
				$this->f_autre		= $obj->f_autre;
				$this->fk_acompte	= $obj->fk_acompte;
				$this->fk_commande		= $obj->fk_commande;	
				$this->ref_commande		= $obj->ref_commande;
				$this->rem		= $obj->rem;
				$this->lbrem		= $obj->lbrem;

				$this->locdateretrait	= $obj->dateretrait;
				$this->locdatedepose	= $obj->datedepose;
				// calcul de l'heure de dépose et retrait
				$this->loclieuretrait	= $obj->lieuretrait;
				$this->loclieudepose	= $obj->lieudepose;
				$this->obs_matmad		= $obj->obs_matmad;
				$this->obs_rando		= $obj->obs_rando;
				
				
				$this->locResa	= $obj->ObsReservation;
				$this->fk_sttResa	= $obj->fk_sttResa;
				$this->SttResa	= $obj->SttResa;
				//$this->observation	= $obj->observation;
				$this->locObs	= $obj->observation;
				$this->fk_caution	= $obj->fk_caution;	
				$this->top_doc	= $obj->top_doc;				
				$this->lb_caution	= $obj->lb_caution;
				$this->ret_caution	= $obj->ret_caution;	
				$this->ret_doc	= $obj->ret_doc;			
				
				$this->top_caution	= $obj->top_caution;	
				$this->mttcaution	= $obj->mttCaution;		
				$this->mttAcompte	= $obj->mttAcompte;	
				$this->fk_modcaution	= $obj->fk_modcaution;	
				$this->lb_modcaution	= $obj->lb_modcaution;
				$this->obscaution	= $obj->obscaution;		
				$this->lbedi_caution	= $obj->lb_caution;				
				if (!empty($this->lb_caution)) 
						$this->lbedi_caution = $this->lbedi_caution.' - ';
				$this->lbedi_caution = $this->lbedi_caution.$obj->obscaution;
				
				if ($obj->fk_type_session == 1)
					{
					if (empty($obj->ptind)) 
						$this->pt	= 0;
					else 
					$this->pt	= $obj->ptind;
					}
				elseif ($obj->fk_type_session == 0)
					{
						if (empty($obj->ptgrp)) $this->pt	= 0; 
						else $this->pt	= $obj->ptgrp;
					}
				$this->ptrem	= $obj->ptrem; // total des remises fixes	
				$this->textremisesfixes = '';				
				if (empty($obj->paye)) $this->paye	= 0; else $this->paye	= $obj->paye + $obj->ptrem;
				$this->acc_paye 	= ($obj->paye)?$obj->paye:$obj->mttAcompte;
				$this->lb_acc_paye = ($obj->paye)?$langs->trans("LbAccPaye"):$langs->trans("LbAccNonPaye");				
				
				$this->ptavecrem		= $this->pt - $this->ptrem ;
				$this->solde		= $this->pt - $this->paye ;
				$this->nbPmt		= $obj->nbPmt;
				// - obsolette suppresion champ 
				//$this->ficcmd		= $obj->ficcmd;
				$this->fk_origine		= $obj->fk_origine;
				$this->lb_origine		= $obj->origine;
				$this->type_session_cgl	= $obj->fk_type_session + 1;
				if (empty($obj->fk_type_session)) $this->type_session_cgl	= 2;
				$this->pers_civ		= $obj->PersCiv;
				$this->pers_nom		= $obj->PersNom;
				$this->pers_prenom	=$obj->PersPrenom;
				$this->pers_tel	=$obj->Persperso;
				if (empty($this->pers_tel)) $this->pers_tel	=$obj->PersTel;
				$this->pers_parente	= $obj->parente;
						
				$this->fk_soc_rem_execpt		= $obj->fk_soc_rem_execpt;
				if (empty($this->fk_soc_rem_execpt)) $this->fk_soc_rem_execpt = $obj->id_soc_rem_execpt;
				$this->filtrpass  = $obj->filtrpass;

				if ( $type == 'Insc')				$id_bull   = $this->id ;
				elseif ( $type == 'Loc')				$id_contrat   = $this->id ;
				elseif ( $type == 'Resa')				$id_resa   = $this->id ;
				
				
				
				//* edition du contrat
				$this->titre_contrat = 'VTT - VAE - RT - VTC';
				$this->facnumber = $obj->facnumber;		
				if (empty($this->facnumber)) $this->titre_fac = '';
				else $this->titre_fac =$langs->trans('Facture');
				$this->Acomptenumber = $obj->Acomptenumber;	
				
				// réservation
				$this->ResaActivite	= $obj->ResaActivite;
				$this->place	= $obj->place;
				$this->heured	= $obj->heured;
				$this->heuref	= $obj->heuref;
				$this->lb_place	= $obj->lb_place;
				
		
				/* ligne bulletin */		
				unset($obj);
				return 1;
			}
		}
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch_entete ".$this->error, LOG_ERR);
            return -1;
        }
	}//fetch_entete
	/*
	* Charge les lignes d'un bulletin de groupe, c'est à dire en regroupant les lignes concernant la même activité
	*/
	function fetch_lines_groupes()
	{
		global $langs;
		
		$w = new CglFonctionCommune($this->db);
		$w1 = new CglFonctionDolibarr($this->db);
		$wdep = new CglDepart ($this->db);
		$now = $this->db->idate(dol_now('tzuser'));
		//$this->lines=array();
		$sql='';
				
		$sql ="SELECT distinct   min(case when (bd.type =  0 and  (pu <> 0 or rem > 0)  ) or bd.type <> 0 then bd.rowid  end) as rowid, bd.type, fk_banque, fk_bull,  MAX(qte) as qte, SUM(pu) as pu, MAX(rem) as rem, ";
		$sql .=" concat(concat( convert(count( bd.rowid),char)   , ' ') , case when count( bd.rowid) = 1 then '".$langs->trans('LbParticipants')."' else '".$langs->trans('LbParticipants','s')."' end ) as libellePart, ";
		$sql .= "  crem.rowid as idrem, crem.libelle as textnom, crem.fl_type as remtype, crem.fk_produit as remprod ,  ";
		$sql.="s.rowid as fk_activite,  PartTel, InfoPublic, ";
		$sql.="  s.intitule_custo   as  intitule_custo, ";
		$sql .= "nb_place, nb_stagiaire,   p.ref_interne, p.rowid as id_site, se.s_rdvPrinc, se.s_rdvAlter, dated, heured , heuref,  se.s_TypeTVA, se.s_code_ventil, ";
		$sql .= "(select s_duree_act from  ".MAIN_DB_PREFIX."agefodd_session_extrafields as AgSe  where AgSe.fk_object = s.rowid ) as s_duree, ";
/*		$sql .= "(select COUNT(rowid) from  ".MAIN_DB_PREFIX."agefodd_session_stagiaire as sst  where sst.fk_session_agefodd = s.rowid and  status_in_session  = 0) as nb_preinscrit, ";
		$sql .= "(select COUNT(rowid) from  ".MAIN_DB_PREFIX."agefodd_session_stagiaire as sst  where sst.fk_session_agefodd = s.rowid and  status_in_session  = 2) as nb_inscrit, ";
		$sql .= "(select COUNT(bds.rowid) from llx_cglinscription_bull as b, ".MAIN_DB_PREFIX."cglinscription_bull_det as bds where bds.fk_bull = b.rowid   ";
		$sql .= " and bds.type = 0  and bds.fk_activite = s.rowid ";
		$sql .= " and ((bds.action = 'A' and b.statut > 0) or (bds.action not in ('S','X')  and b.statut = 0 ))) as activite_nbencrins, ";
*/
		$sql.=" case when isnull(cf.rowid) then cu.rowid else cf.rowid end as MonId,  ";
		$sql.=" case when isnull(cf.rowid) then cu.lastname else cf.lastname end as MonNom,  ";
		$sql.=" case when isnull(cf.rowid) then cu.firstname else cf.firstname end as MonPrenom, ";
		$sql.=" case when isnull(cf.rowid) then cu.user_mobile else cf.phone_mobile end as MonTel, ";
		$sql.=" case when isnull(cf.rowid) then cu.office_phone else cf.phone end as Monperso, ";
		$sql.=" case when isnull(cf.rowid) then cu.email else cf.email end as MonMail, ";
		$sql .= " '".$now."' as Maintenant,  cp.libelle as mode_pmt, ";
		$sql.="fk_mode_pmt, organisme, tireur, num_cheque, pt, datepaiement, bd.fk_facture, bd.fk_paiement, bd.lb_pmt_neg, bd.fk_rdv, bd.observation, ";
		$sql.="  max(case when (bd.type =  0 and  (pu <> 0 or rem > 0)  ) or bd.type <> 0  then  action end) as action,     ";
		$sql.=" min(case when (bd.type =  0 and  (pu <> 0 or rem > 0)  ) or bd.type <> 0   then bd.fk_linecmd  end) as fk_line_commande,   ";
		$sql.=" min(case when (bd.type =  0 and  (pu <> 0 or rem > 0)  ) or bd.type <> 0 then bd.fk_linefct  end) as fk_linefct,   ";
		$sql.=" bd.fk_produit, b.rowid as fk_banqueCGL, bk.rappro , bk.fk_bordereau, bcq.ref as refbordereau ";
		$sql .= ",  bd.dateretrait, bd.datedepose, bd.duree ";

 
		$sql .= ",  bd.dateretrait, bd.datedepose, bd.duree ";
		$sql .= ", (SELECT max(1)	  	FROM  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd5 		WHERE  bd5.type = 5 and bd5.action not in ('X','S') 
			and bd.fk_bull = bd5.fk_bull  			AND ( bd5.fk_raisrem = bd.rowid) 			and bd.fk_mode_pmt = 56 and bd.type = 1 and bd.action not in ('X','S')) 
				as pmt_StripeAut";
		
		$sql.=" FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd  ";		
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX.'agefodd_session as s on s.rowid = bd.fk_activite ';
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX.'agefodd_session_extrafields as se on s.rowid = se.fk_object ';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_place as p on fk_session_place=p.rowid ';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_session_calendrier on fk_agefodd_session = s.rowid ';
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_formateur as sf on sf.fk_session = s.rowid";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formateur as f on sf.fk_agefodd_formateur = 	f.rowid";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."socpeople as cf on cf.rowid = f.fk_socpeople";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."user as cu on cu.rowid = f.fk_user";
		$sql.="  LEFT JOIN ".MAIN_DB_PREFIX."product as sv on sv.rowid = s.fk_product  and fk_product_type = 1";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp on cp.id = fk_mode_pmt";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."cgl_c_raison_remise as crem on crem.rowid = fk_raisrem";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."bank as bk on bk.rowid = bd.fk_banque";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."bordereau_cheque as bcq on bcq.rowid = bk.fk_bordereau";		
		//$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."cgl_pmt_bank as cpb on cpb.code_paiement = cp.code";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."bank_account as b on cp.fk_cpt_bq = b.rowid";
		
		$sql.=" WHERE ";
			$sql .= " (isnull(sf.rowid ) or  sf.rowid = (select min(rowid) from ".MAIN_DB_PREFIX."agefodd_session_formateur where fk_session = s.rowid)) and "; 
		$sql.="  bd.action not in ('X','S') and ";
		$sql.="  fk_bull = '".$this->id."'" ;
		$sql.="  GROUP BY bd.type, fk_activite, fk_banque, fk_bull,   idrem,  textnom, ";
		$sql.=" remtype,  remprod, fk_activite,  PartTel, InfoPublic, s.intitule_custo ,  ";
		$sql .= "nb_place, nb_stagiaire,   p.ref_interne, id_site, se.s_rdvPrinc, se.s_rdvAlter, dated, heured , heuref,  se.s_TypeTVA, se.s_code_ventil,  ";
		$sql .= " s_duree, ";
		$sql.="  MonNom,   MonPrenom,  MonTel,  Monperso,  MonMail, mode_pmt, ";
		$sql.="fk_mode_pmt, organisme, tireur, num_cheque, pt, datepaiement, bd.fk_facture, bd.fk_paiement, bd.lb_pmt_neg, bd.fk_rdv, bd.observation, ";
		$sql.=" bd.fk_produit, fk_banqueCGL, bk.rappro , bk.fk_bordereau, ";
		$sql .= "refbordereau ,  bd.dateretrait, bd.datedepose, bd.duree ";
		
		$sql .= ' ORDER BY bd.type, intitule_custo, dated, pu desc ';	

		dol_syslog(get_class($this).'::fetch_lines_groupes ', LOG_DEBUG);
		$result = $this->db->query($sql);
//
		if ($result)
		{	
			$num = $this->db->num_rows($result);
			$i = 0; $j=0;
			unset($this->lines);
			$this->lines = array();
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$line = new BulletinLigne($this->db, $this->type);					
				$line->id					= $objp->rowid;						
				$line->type_enr				= $objp->type;
				$line->fk_bull				= $objp->fk_bull;
				$line->id_act				= $objp->fk_activite;
				if ($line->type_enr == $line->LINE_ACT  ) {
					$line->taux_tva	=$w1->taux_TVAstandard() * $objp->s_TypeTVA;
				}
				else $line->taux_tva = '';		
				$line->fk_code_ventilation = $objp->s_code_ventil ;				
				$this->derniere_activite	= $objp->fk_activite;			
				$line->qte					= $objp->qte;
				$line->pu					= $objp->pu;
				$line->remise_percent		= $objp->rem;
				$line->action				= $objp->action;		
				$line->PartTel				= $objp->PartTel;
				$line->fk_produit			=$objp->fk_produit;		
				$line->activite_label		=$objp->intitule_custo;
				$line->activite_nbmax		=$objp->nb_place;
				
				$line->activite_nbpreinscrit = 0;
				if (empty($line->id_act))$TabAct[$line->id_act] = $line->id_act;
				//$line->activite_nbencrins = $wdep->NbPartDep(0,$line->id_act);
				//$line->activite_nbinscrit = $wdep->NbPartDep(1,$line->id_act);
				//if (empty($line->activite_nbinscrit)) $line->activite_nbinscrit	=0;
				//if (empty($line->activite_nbencrins)) $line->activite_nbencrins	=0;
/*
				$line->activite_nbinscrit	=$objp->nb_inscrit;
				$line->activite_nbpreinscrit=$objp->nb_preinscrit;	
				$line->activite_nbencrins   = $objp->activite_nbencrins;
				
*/				
				$line->activite_lieu		=$objp->ref_interne;
				$line->id_site				=$objp->id_site;
				$line->infopublic			=$objp->InfoPublic;
				$line->activite_dated		=$objp->dated;
				$line->activite_heured		=$objp->heured;
				$line->activite_heuref		=$objp->heuref;	
				$line->activite_rdv			=$objp->fk_rdv;
				if (empty($objp->fk_rdv))	$line->activite_rdv = 1;
				$line->rdv_lib				=($line->activite_rdv == 1)?$objp->s_rdvPrinc:$objp->s_rdvAlter;
				//$line->rdv_lib				=($line->activite_rdv == 1)?$objp->s_rdvPrinc:$objp->s_rdvAlter;
				$line->rdv_lib				=$objp->s_rdvPrinc;				
				$line->rdv2_lib				=$objp->s_rdvAlter;

				$line->observation			=$objp->observation;	
				$line->act_moniteur_id		=$objp->MonId;	
				$line->act_moniteur_nom		=$objp->MonNom;
				$line->act_moniteur_prenom	=$objp->MonPrenom;
				if ($objp->MonTel) 	$line->act_moniteur_tel	=$objp->MonTel;
				else 				$line->act_moniteur_tel	=$objp->Monperso;
				$line->act_moniteur_email	=$objp->MonMail;

				// location
				$line->fk_service	=$objp->fk_activite;
				$line->service		=$objp->Service;
				$line->refservice	=$objp->RefService;
				$line->materiel		=$objp->materiel;
				$line->fk_fournisseur	=$objp->fk_fournisseur;
				$line->NomFourn		= $objp->NomFourn;
				$line->marque		=$objp->marque;
				$line->refmat		=$objp->refmat .' - '.$objp->marque;
				$line->identmat		=$objp->refmat;
				$line->NomPrenom	=$objp->libellePart;
				$line->PUjour		=$objp->PUjour;
				$line->PUjoursup	=$objp->PUjoursup;

				if ((int)$line->pt <> $line->pt) $line->pt = (int)$line->pt;
			
				// paiement
					$line->id_mode_paiement		=$objp->fk_mode_pmt;
					$line->mode_paiement		=$objp->mode_pmt;
					if (!empty ($this->modes_paiement)  and !empty($objp->mode_pmt))	$this->modes_paiement .= ' - ';
					$this->modes_paiement		.=$objp->mode_pmt;
					$line->organisme			=$objp->organisme;
					$line->tireur				=$objp->tireur;
					$line->num_cheque			=$objp->num_cheque;
					$line->montant				=$objp->pt;
					$line->pmt_neg  		    =$objp->lb_pmt_neg;		
					 
					$line->mttremfixe = $objp->pt;							
					$line->fk_remgen	= $objp->idrem;	
					$line->textnom		= $objp->textnom;						
					if ($line->type_enr  ==  $line->LINE_BC ) $this->textremisesfixes .= '"'.$line->textnom.'" montant :'.$line->mttremfixe. '  ';
					$line->fl_type		= $objp->fl_type;		
					$line->fk_remprod	= $objp->remprod;	
					$line->textremisegen= $objp->textremisegen;					
		
					$line->date_paiement		=$objp->datepaiement;
					$line->fk_line_commande			=$objp->fk_line_commande;
					
					$line->fk_line_facture		=$objp->fk_linefct;
					
					
					$line->fk_banque			=$objp->fk_banque;
					$line->fk_paiement			=$objp->fk_paiement;
					$line->fk_facture			=$objp->fk_facture;
					$line->fk_accountCGL		=$objp->fk_banqueCGL;	
					$line->pmt_rappro			=$objp->rappro;	
					$line->pmt_depose			=$objp->fk_bordereau;	
					$line->pmt_StripeAut		=$objp->pmt_StripeAut;	
					$line->pmt_refbordereau			=$objp->refbordereau;	
				
				$line->fk_agsessstag		=$objp->fk_agsessstag;
				
				// réservation				
				$line->resa_activite		=$objp->resa_activite;
				$line->prix					=$objp->prix;
				$line->qteret				=$objp->qteret;
				$line->resa_place			=$objp->resa_place;
				
				$this->lines[$i] = $line;
				$j++;
				$i++;
			}
			// Gestion Nombre participants
			// Un tableau est constitué dans la lectures des lignes, pour récupérer la liste des départs de ce BU
			//Ensuite, on envoie NbPartDep (
			if (is_array($TabAct )) {
				foreach ($TabAct as $key => $val) {
					if (!empty($key)) {
						$nbencrins = $wdep->NbPartDep(0,$key);
						$nbpreinscrit = $wdep->NbPartDep(1,$key);
						$nbinscrit = $wdep->NbPartDep(2,$key);
						if (empty($nbencrins)) $nbencrins	=0;				
						if (empty($nbinscrit)) $nbinscrit	=0;			
						if (empty($nbpreinscrit)) $nbpreinscrit	=0;
						if ( !empty($this->lines)) {
							foreach ($this->lines as $line) {
								$line->activite_nbencrins = $nbencrins;
								$line->activite_nbpreinscrit = $nbpreinscrit;
								$line->activite_nbinscrit = $nbinscrit;					
							}// foreach
						}
					}
				} // foreach
			}
			
			$this->db->free($result);
			unset ($w);
			return $j;	

		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::fetch_lines_groupes '.$this->error,LOG_ERR);
			unset ($w);
			return -3;
		}
				
	}//fetch_lines_groupes
	
    function fetch_complet_filtre_V4($status = 0, $id )
    {
    	global $langs, $conf;		
		/* recherche tete du bulletin */
        $sql = "SELECT distinct t.rowid, ref, typebull, t.datec, nom, s.email, s.town, s.fk_pays,c.label as country, c.code as country_code, s.address, s.zip, s.phone as  TiersTel,se.s_tel2 ,  se.s_email2, t.action,  ";
		$sql.= "  t.fk_soc as fk_tiers,t.statut, t.regle, t.fk_facture, t.fk_user, t.fk_persrec,";
		$sql.= " f_condition_vente, f_autre, pc.lastname as PersNom, pc.firstname as PersPrenom, pc.phone_perso as Persperso, pc.phone_mobile as PersTel, t.fk_ContactTiers as id_contactTiers, ";
		$sql.= " pc.civility as PersCiv, poste as parente , t.fk_acompte, t.fk_cmd as fk_commande, t.ref_cmd as ref_commande,";
		// - obsolette suppresion champ 
		//$sql.= " ficcmd,  ";
		$sql.= " t.fk_origine, cr.label as origine ,  ";
		$sql.= " t.Villegiature, fk_soc_rem_execpt, sre.rowid as id_soc_rem_execpt, t.obs_matmad, t.obs_rando, t.fk_modcaution, t.obscaution, cp.libelle as lb_modcaution,  ";
	
			$sql.= "  t.fk_type_session, sum(case when bd.type=0 and bd.action not in('S','X') and fk_type_session = 1  then bd.pu* bd.qte*(100- bd.rem)/100 else 0 END ) as ptind,  ";
			$sql.= " sum(case when bd.type=0 and bd.action not in('S','X') and fk_type_session = 1  then bd.pu* bd.qte*(100- bd.rem)/100 else 0 END ) as ptind,  ";
			$sql.= " sum(case when bd.type=2 and bd.action not in('S','X')   then bd.pt END ) as ptrem,  ";
			$sql.= " sum(case when bd.type=1 and bd.action not in ('S','X')  then bd.pt else 0 END) as paye, ";		
			$sql.= " t.datedepose, t.dateretrait, t.lieuretrait, t.lieudepose,  ObsReservation, fk_sttResa, t.observation,";

			$sql.= " t.lieuretrait as ResaActivite,t.datedepose as heuref, t.dateretrait as heured, t.fk_type_session as place, pl.ref_interne as lb_place, ";
	
		$sql.= "   sr.libelle as SttResa, fk_caution, cc.libelle as lb_caution, ret_caution, ret_doc, top_caution,top_doc,  mttCaution, mttAcompte ";
        $sql.= ", filtrpass ";
		$sql .= ", f.ref as facnumber, t.abandon, ObsPriv,  ActionFuture, PmtFutur";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as t";
		$sql.=" left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on  bd.fk_bull = t.rowid ";
		$sql.=" left join ".MAIN_DB_PREFIX."socpeople as pc on pc.rowid = t.fk_persrec";
		$sql.=" left join ".MAIN_DB_PREFIX."cgl_c_stresa as sr on sr.rowid = fk_sttResa";
		$sql.=" left join ".MAIN_DB_PREFIX."facture as f on f.rowid = t.fk_facture";
		$sql.=" left join ".MAIN_DB_PREFIX."cgl_c_caution as cc on cc.rowid = fk_caution";
		$sql.=" left join ".MAIN_DB_PREFIX."facture as facc on facc.rowid = t.fk_acompte";
		$sql.=" left join ".MAIN_DB_PREFIX."societe_remise_except as sre on sre.fk_soc = t.fk_soc and sre.fk_facture_source = facc.rowid";
		$sql.=" left join ".MAIN_DB_PREFIX."c_input_reason as cr on cr.rowid = fk_origine";	
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp on cp.id = fk_modcaution";
		$sql.=" left join ".MAIN_DB_PREFIX."agefodd_place as pl on pl.rowid = t.fk_type_session ";	// uniquement pour resa
        $sql.= " , ".MAIN_DB_PREFIX."societe as s";		
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON s.fk_pays = c.rowid";
		$sql.=" left join ".MAIN_DB_PREFIX."societe_extrafields as se on s.rowid = se.fk_object ";	
		
        $sql.= " WHERE s.rowid = t.fk_soc ";
		if ($status == 0 or $id=='')      $sql.= " and t.statut = ".$this->BULL_ENCOURS;
		else     $sql.= " and t.rowid = ".$id;
		$sql.=" 		GROUP BY t.rowid, ref, typebull, t.datec, nom, s.email, s.town,  s.fk_pays,c.label , s.address,s.zip, s.phone ,se.s_tel2 ,  se.s_email2, t.action, t.fk_soc,t.statut, t.regle, t.fk_facture, t.fk_user, ";
        $sql.= "t.fk_persrec, f_condition_vente, f_autre, pc.lastname , pc.firstname , pc.phone_perso, pc.phone_mobile , t.fk_ContactTiers ,";
        $sql.= " pc.civility , poste  , t.fk_acompte, t.fk_cmd , t.ref_cmd ";
		// - obsolette suppresion champ 
		//$sql.= " ficcmd,  ";
		$sql.= ", t.fk_origine, cr.label ,   t.Villegiature, t.fk_type_session,";
        $sql.= " fk_soc_rem_execpt,  sre.rowid, t.obs_matmad, t.obs_rando, t.fk_modcaution, t.obscaution, cp.libelle ,";
        $sql.= " t.datedepose, t.dateretrait, t.lieuretrait, t.lieudepose,  t.ObsReservation, fk_sttResa, t.observation,   sr.libelle , fk_caution, cc.libelle , ret_caution, ret_doc, top_caution,";
        $sql.= "  top_doc,  mttCaution, mttAcompte, filtrpass ,  t.abandon, ObsPriv ,  ActionFuture, PmtFutur";
if ($conf->global->MAIN_VERSION_LAST_UPGRADE <>'8.0.4')
		$sql .= ", f.ref ";
else	$sql .= ", f.facnumber";

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql); 

		if ($resql)
        {		
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                $this->id   		= $obj->rowid;
                $this->ref   		= $obj->ref;
                $this->type   		= $obj->typebull;
				if (empty($obj->typebull) or $obj->typebull == 'null')
				{		
					if (substr($obj->ref, 0, 2) == 'BU' ) $this->type = 'Insc';
					elseif (substr($obj->ref, 0, 2) == 'LO' ) $this->type = 'Loc';
					elseif (substr($obj->ref, 0, 2) == 'RE' ) $this->type = 'Resa';
				}
                $this->datec   		= $obj->datec;
                $this->action   	= $obj->action;
                $this->tiersNom   	= $obj->nom;
                $this->TiersVille   = $obj->town;
                $this->TiersIdPays   = $obj->fk_pays;
                $this->TiersPays   = $obj->country;
				$this->Tierscode  = $obj->country_code;
                $this->TiersTel  	= $obj->TiersTel;
                $this->TiersTel2  	= $obj->s_tel2;
                $this->TiersMail   	= $obj->email;				
                $this->TiersMail   	= $obj->s_email2;
                $this->TiersAdresse   = $obj->address;
                $this->TiersCP   	= $obj->zip;
				$this->id_client	= $obj->fk_tiers;
                $this->Villegiature   	= $obj->Villegiature;
				$this->statut		= $obj->statut;
				$this->abandon		= $obj->abandon;
				$this->ObsPriv		= $obj->ObsPriv;
				$this->PmtFutur		= $obj->PmtFutur;
				$this->ActionFuture		= $obj->ActionFuture;	
				
				$this->regle		= $obj->regle;
				$this->id_contactTiers = $obj->id_contactTiers;
				
				$this->fk_facture	= $obj->fk_facture;
				$this->fk_user		= $obj->fk_user;
				$this->fk_persrec	= $obj->fk_persrec;
				$this->f_condition_vente=$obj->f_condition_vente;
				$this->f_autre		= $obj->f_autre;
				$this->fk_acompte	= $obj->fk_acompte;
				$this->fk_commande		= $obj->fk_commande;	
				$this->ref_commande		= $obj->ref_commande;
				$this->rem		= $obj->rem;
				$this->lbrem		= $obj->lbrem;
				
//				$this->locdateretrait	= new DateTime($obj->dateretrait);
//				$this->locdatedepose	= new DateTime($obj->datedepose);
				$this->locdateretrait	= $obj->dateretrait;
				$this->locdatedepose	= $obj->datedepose;
				// calcul de l'heure de dépose et retrait
				$this->loclieuretrait	= $obj->lieuretrait;
				$this->loclieudepose	= $obj->lieudepose;
				$this->obs_matmad		= $obj->obs_matmad;
				$this->obs_rando		= $obj->obs_rando;
				
				
				$this->locResa	= $obj->ObsReservation;
				$this->fk_sttResa	= $obj->fk_sttResa;
				$this->SttResa	= $obj->SttResa;
				//$this->observation	= $obj->observation;
				$this->locObs	= $obj->observation;
				$this->fk_caution	= $obj->fk_caution;	
				$this->top_doc	= $obj->top_doc;				
				$this->lb_caution	= $obj->lb_caution;
				$this->ret_caution	= $obj->ret_caution;	
				$this->ret_doc	= $obj->ret_doc;			
				
				$this->top_caution	= $obj->top_caution;	
				$this->mttcaution	= $obj->mttCaution;		
				$this->mttAcompte	= $obj->mttAcompte;	
				$this->fk_modcaution	= $obj->fk_modcaution;	
				$this->lb_modcaution	= $obj->lb_modcaution;
				$this->obscaution	= $obj->obscaution;		
				$this->lbedi_caution	= $obj->lb_caution;				
				if (!empty($this->lb_caution)) 
						$this->lbedi_caution = $this->lbedi_caution.' - ';
				$this->lbedi_caution = $this->lbedi_caution.$obj->obscaution;
				
				if ($obj->fk_type_session == 1)
					{
					if (empty($obj->ptind)) 
						$this->pt	= 0;
					else 
					$this->pt	= $obj->ptind;
					}
				elseif ($obj->fk_type_session == 0)
					{
						if (empty($obj->ptgrp)) $this->pt	= 0; 
						else $this->pt	= $obj->ptgrp;
					}
				$this->ptrem	= $obj->ptrem; // total des remises fixes	
				$this->textremisesfixes = '';				
				if (empty($obj->paye)) $this->paye	= 0; else $this->paye	= $obj->paye + $obj->ptrem;
				$this->acc_paye 	= ($obj->paye)?$obj->paye:$obj->mttAcompte;
				$this->lb_acc_paye = ($obj->paye)?$langs->trans("LbAccPaye"):$langs->trans("LbAccNonPaye");				
				
				$this->solde		= $this->pt - $this->paye ;
				// - obsolette suppresion champ 				
				//$this->ficcmd		= $obj->ficcmd;
				$this->fk_origine		= $obj->fk_origine;
				$this->lb_origine		= $obj->origine;
				$this->type_session_cgl	= $obj->fk_type_session + 1;
				if (empty($obj->fk_type_session)) $this->type_session_cgl	= 2;
				$this->pers_civ		= $obj->PersCiv;
				$this->pers_nom		= $obj->PersNom;
				$this->pers_prenom	=$obj->PersPrenom;
				$this->pers_tel	=$obj->Persperso;
				if (empty($this->pers_tel)) $this->pers_tel	=$obj->PersTel;
				$this->pers_parente	= $obj->parente;
						
				$this->fk_soc_rem_execpt		= $obj->fk_soc_rem_execpt;
				if (empty($this->fk_soc_rem_execpt)) $this->fk_soc_rem_execpt = $obj->id_soc_rem_execpt;
				$this->filtrpass  = $obj->filtrpass;

				if ( $type == 'Insc')				$id_bull   = $this->id ;
				elseif ( $type == 'Loc')				$id_contrat   = $this->id ;
				elseif ( $type == 'Resa')				$id_resa   = $this->id ;
				
				
				
				//* edition du contrat
				$this->titre_contrat = 'VTT - VAE - RT - VTC';
				$this->facnumber = $obj->facnumber;		
				if (empty($this->facnumber)) $this->titre_fac = '';
				else $this->titre_fac =$langs->trans('Facture');
				
				// réservation
				if ($this->fk_sttResa) $this->titre_resa=$langs->trans('TiResa');
				else $this->titre_resa='';
				$this->ResaActivite	= $obj->ResaActivite;
				$this->place	= $obj->place;
				$this->heured	= $obj->heured;
				$this->heuref	= $obj->heuref;
				$this->lb_place	= $obj->lb_place;	
				unset($obj);
				
		
				/* ligne bulletin */	
				//$this->lines  = array();
				$result=$this->fetch_lines($status);
				if ($result < 0)
				{
					$this->error=$this->db->error();
					dol_syslog(get_class($this)."::fetch_ligne Error ".$this->error, LOG_ERR);					
					return -3;
				}
				else $this->nblignebulletin = $result;
				//$this->regle=$this->RecupReglement();
				
				
				/* ligne demande stripe */	
				$result=$this->fetch_lines_stripe($status);
				if ($result < 0)
				{
					$this->error=$this->db->error();
					dol_syslog(get_class($this)."::fetch_ligne_stripe Error ".$this->error, LOG_ERR);					
					return -4;
				}		
	
				/* ligne materiel à disposition */				
				if (!empty($conf->global->CGL_LOC_RANDO_MAT)) {
					$this->lines_mat_mad  = array();
					$result=$this->fetch_lines_MatMad($status);
					if ($result < 0)
					{
						$this->error=$this->db->error();
						dol_syslog(get_class($this)."::fetch fetch_lines_MatMad ".$this->error, LOG_ERR);					
						return -3;
					} 
					/* ligne rando */				
					//$this->lines_rando  = array();
					$result=$this->fetch_lines_rando($status);
					if ($result < 0)
					{
						$this->error=$this->db->error();
						dol_syslog(get_class($this)."::fetch fetch_lines_rando ".$this->error, LOG_ERR);					
						return -3;
					}
				}
				
			}
			else
			{
				$this->id = '';
				//	pas de bulletin */
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
   } /* fetch_complet_filtre*/

	/* recherche des lignes de demandes Stripe par Id_bull
	*/
	function fetchDemandesStripe ( $id_bull, $id_acompte) 
	{
		$bullline = new BulletinLigne ($this->db);
		$sql = "SELECT distinct bd.rowid as id, bd.NomPrenom as Nompayeur, bd.pt as montant, bd.lieudepose as mailpayeur,  bd.tireur as smspayeur, ";
		$sql .= "bd.fk_facture as fk_acompte, fac.ref as RefAcompte, bd.datepaiement as date_paiement, bd.fk_activite   as ModelMail, bd.fk_bull  , ";
		$sql .= "bd.dateretrait as datederniereRelance, bd.datedepose as dateenvoi, bd.qteret as nbRelance, bd.fk_activite   as ModelMail, bd.action, ";
		$sql .= " bd.reslibelle  as stripeUrl, bd.ficbull as libelleCarteStripe , bd.fk_agsessstag as fk_soc_rem_execpt, bd.fk_banque as fk_bank, bd.fk_raisrem as fk_bulldet, ";
		$sql .= "bd.fk_paiement ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd LEFT JOIN ".MAIN_DB_PREFIX."facture as fac on fac.rowid = bd.fk_facture"  ;
		$sql.= " WHERE bd.type = ".$bullline->LINE_STRIPE." and bd.action not in ('X','S') ";
		if (!empty($id_bull)) $sql .= " AND bd.fk_bull ='".$id_bull."'";
		if (!empty($id_acompte)) $sql .= " AND bd.fk_facture ='".$id_acompte."'";


        dol_syslog(get_class($this)."::fetchDemandesStripe ");
        $resql=$this->db->query($sql);
        if ($resql)
        {			
            $num = $this->db->num_rows($resql);
			if ($num > 0){
				for ($i=0; $i<$num;$i++) {
					$linestripe = new BulletinDemandeStripe ($this->db);
					$obj = $this->db->fetch_object($resql);
					$linestripe->id = $obj->id;
					$linestripe->montant = $obj->montant;
					$linestripe->Nompayeur = $obj->Nompayeur;
					$linestripe->mailpayeur = $obj->mailpayeur;
					$linestripe->smspayeur = $obj->smspayeur;
					$linestripe->fk_acompte	 = $obj->fk_acompte;			
					$linestripe->RefAcompte =$obj->RefAcompte;	
					$linestripe->ModelMail	 = $obj->ModelMail;
					$linestripe->dateenvoi	 = $obj->dateenvoi;
					$linestripe->dateDerniereRelance	 = $obj->datederniereRelance;	
					$linestripe->fk_bull	 = $obj->fk_bull;
					$linestripe->fk_bank	 = $obj->fk_bank;
					$linestripe->fk_bulldet	 = $obj->fk_bulldet;
					$linestripe->date_paiement	 = $obj->date_paiement;
					$linestripe->nbRelance	 = $obj->nbRelance;
					$linestripe->fk_paiement	 = $obj->fk_paiement;
					$linestripe->fk_soc_rem_execpt	 = $obj->fk_soc_rem_execpt;
					$linestripe->action	 = $obj->action;
					$linestripe->stripeUrl	 = $obj->stripeUrl;
					$linestripe->libelleCarteStripe	 = $obj->libelleCarteStripe;
					$linestripe->lines_stripe[] = $linestripe;
				}// For
				return $this->lines_stripe;
			}
			else return 0;
		}	
		else return -1;		
	} //fetchDemandesStripe

	/*
	* utile pour l'envoi de mail dans actions_sendmails.inc.php
	*/
	function fetch($id)
	{
		return $this->fetch_complet_filtre(1, $id );
	}
	
	function update()
	{	
    	global $conf, $langs, $user, $bull;
		$error=0;
		
		// Clean parameters
        if (isset($this->datec)) $this->datec=trim($this->datec);
        if (isset($this->tms)) $this->tms=trim($this->tms);
        if (isset($this->ref)) $this->ref=trim($this->ref);
        if (isset($this->entity)) $this->entity=trim($this->entity);
        if (isset($this->statut)) $this->statut=trim($this->statut);
        if (isset($this->regle)) $this->regle=trim($this->regle);
        if (isset($this->id_client)) $this->id_client=trim($this->id_client);
        if (isset($this->fk_user)) $this->fk_user=trim($this->fk_user);
        if (isset($this->fk_facture)) $this->fk_facture=trim($this->fk_facture);
        if (isset($this->dt_facture)) $this->datec=trim($this->dt_facture);
        if (isset($this->fk_persrec)) $this->fk_persrec=trim($this->fk_persrec);
        if (isset($this->f_autori_parentale)) $this->f_autori_parentale=trim($this->f_autori_parentale);
        if (isset($this->f_condition_vente)) $this->f_condition_vente=trim($this->f_condition_vente);
        if (isset($this->f_autre)) $this->f_autre=trim($this->f_autre);
        if (isset($this->TiersTel)) $this->TiersTel=trim($this->TiersTel);
        if (isset($this->TiersTel2)) $this->TiersTel2=trim($this->TiersTel2);
        if (isset($this->pers_tel)) $this->pers_tel=trim($this->pers_tel);
        if (isset($this->fk_origine)) $this->fk_origine=trim($this->fk_origine);
        if (isset($this->Villegiature)) $this->Villegiature=trim($this->Villegiature);
        if (isset($this->type_session_cgl)) $this->type_session_cgl=trim($this->type_session_cgl);
        if (isset($this->ObsPriv)) $this->ObsPriv=trim($this->ObsPriv);
        if (isset($this->ActionFuture)) $this->ActionFuture=trim($this->ActionFuture);
        if (isset($this->PmtFutur)) $this->PmtFutur=trim($this->PmtFutur);
		
		// parametres locations	
	    if (isset($this->loclieuretrait)) 	$this->loclieuretrait 	=trim($this->loclieuretrait);
	    if (isset($this->loclieudepose)) 	$this->loclieudepose 	=trim($this->loclieudepose);
	    if (isset($this->locResa)) 			$this->locResa 			=trim($this->locResa);
	    if (isset($this->fk_sttResa)) 		$this->fk_sttResa 		=trim($this->fk_sttResa);
	    if (isset($this->observation)) 		$this->observation 		=trim($this->observation);
	    if (isset($this->locObs)) 		$this->locObs 		=trim($this->locObs);
	    if (isset($this->obs_matmad)) 	$this->obs_matmad 	=trim($this->obs_matmad);
	    if (isset($this->obs_rando)) 	$this->obs_rando 	=trim($this->obs_rando);
		
		// Check parameters
		// Put here code to add control on parameters values
		// le champ action est mis Ã  jour en mÃªme temps que les clÃ©s Ã©trangÃ¨res : AjoutFkDolibarr
		$i=0;

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull SET ";
		$now=dol_now('tzuser');
		$sql.= " tms = '".$this->db->idate($now)."'";
		if ($this->ref) $sql.= " , ref= '".$this->ref."'";
		if ($this->fk_origine) $sql.= " , fk_origine= ".$this->fk_origine;
		if ($this->statut) $sql.= " , statut= ".$this->statut;
		if ($this->regle) $sql.= " , regle= ".$this->regle;
		//if ($this->id_client) $sql.= ", fk_soc = ".$this->id_client;
		if ($this->fk_persrec) $sql.= " , fk_persrec= ".$this->fk_persrec."";
		if ($this->f_autori_parentale) $sql.= " , f_autori_parentale= '".$this->f_autori_parentale."'";
		if ($this->TiersTel) $sql.= " , TiersTel= '".$this->TiersTel."'";
		if ($this->pers_tel) $sql.= " , RecTel= '".$this->pers_tel."'";
		if ($this->Villegiature) $sql.= ' , Villegiature= "'.$this->Villegiature.'"';
		if ($this->f_condition_vente) $sql.= " , f_condition_vente= '".$this->f_condition_vente."'";
		$local_type_session_agf = $this->type_session_cgl -1;
		if ($local_type_session_agf >=0) $sql.= ", fk_type_session= '".$local_type_session_agf."'";
		if ($this->f_autre) $sql.= ", f_autre= '".$this->f_autre."'"	;
		if (!empty($this->rem))  $sql.= ", rem= '".$this->rem."'"	; // non utilisé
		if ($this->lbrem) $sql.= ", lbrem= '".$this->lbrem."'"	;
				
		$sql.= " , ObsPriv= '".$this->ObsPriv."'";
		$sql.= " , ActionFuture= '".$this->ActionFuture."'";
		$sql.= " , PmtFutur= '".$this->PmtFutur."'";
		if (isset($this->locdateretrait)) 
			$sql.= " , dateretrait= '".$this->locdateretrait."'";
		if (isset($this->locdatedepose ))
			$sql.= " , datedepose= '".$this->locdatedepose."'";
		if ($this->loclieuretrait) $sql.= ' , lieuretrait= "'.$this->loclieuretrait.'"';
		if ($this->loclieudepose) $sql.= ', lieudepose = "'.$this->loclieudepose.'"';		
		if ($this->locResa) $sql.= ' , ObsReservation= "'.$this->locResa.'"';
		if ($this->fk_sttResa) $sql.= " , fk_sttResa= '".$this->fk_sttResa."'";
		//$sql.= ' , observation= "'.$this->observation.'"';
		$sql.= ' , observation= "'.$this->locObs.'"';
		
		if (!empty($this->obs_matmad)) $sql.= ', obs_matmad = "'.$this->obs_matmad.'"';
		if (!empty($this->obs_rando)) $sql.= ', obs_rando = "'.$this->obs_rando.'"';
		
		
		if (!empty($this->fk_facture)) $sql.= ', fk_facture = "'.$this->fk_facture.'"';
		
		$sql.= " Where rowid =  ".$this->id;
		$this->db->begin();
	   	dol_syslog(get_class($this)."::Update ", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
  // Commit or rollback
  
        if ($error)
		{
			dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{			
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($this->dt_facture)) $this->update_tms();
			$this->db->commit();
            return $this->id;
		}
		
	}/* update */
	function update_tel($champ, $val)
	{    	
		$this->update_champs($champ, $val);
	} // update_tel
	function update_caution($champ, $val)
	{	
		$this->update_champs($champ, $val);
	} //update_caution
	function update_champs($champ1, $val1, $champ2='', $val2='', $champ3='', $val3='', $champ4='', $val4='' )
	{
    	global $conf, $langs, $user, $bull;
		$error=0;					
		// parametres location
	    if (isset($val1))	 	$val1		=trim($val1);
		if (empty($val1)) 		$val1		= 0;
	    if (isset($val2))	 	$val2		=trim($val2);
	    if (isset($val3))	 	$val3		=trim($val3);
	    if (isset($val4))	 	$val4		=trim($val4);
	
		// Put here code to add control on parameters values
		// le champ action est mis Ã  jour en mÃªme temps que les clÃ©s Ã©trangÃ¨res : AjoutFkDolibarr
		$i=0;

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull SET  ";
		$sql.= $champ1."= '".$val1."' ";
		if (!empty($champ2) ) $sql.= ',  '.$champ2.'= "'.$val2.'" ';
		if (!empty($champ3)) $sql.=  ',  '.$champ3.'= "'.$val3.'" ';
		if (!empty($champ4) ) $sql.=  ',  '.$champ4.'= "'.$val4.'" ';
		$sql.= "  Where rowid =  ".$this->id;
		$this->db->begin();
		// liste champ mis à jours
		if (!empty($champ1))  $lb = "champs:".$champ1;
		if (!empty($champ2) ) $lb .= "---".$champ2;
		if (!empty($champ3) ) $lb .= "---".$champ3;
		if (!empty($champ4) ) $lb .= "---".$champ4;
	   	dol_syslog(get_class($this)."::update_".$lb, LOG_DEBUG);
		
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::update_champs ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
	} // update_champs
	function update_champs_filtre($sqlwhere,  $champ1, $val1, $champ2='', $val2='', $champ3='', $val3='', $champ4='', $val4='' )
	{
    	global $conf, $langs, $user, $bull;
		$error=0;
		if (empty($sqlwhere)) return -3;
		
		// parametres location
	    if (isset($val1))	 	$val1		=trim($val1);
	    if (isset($val2))	 	$val2		=trim($val2);
	    if (isset($val3))	 	$val3		=trim($val3);
	    if (isset($val4))	 	$val4		=trim($val4);
		// Put here code to add control on parameters values
		// le champ action est mis Ã  jour en mÃªme temps que les clÃ©s Ã©trangÃ¨res : AjoutFkDolibarr
		$i=0;

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull SET  ";
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull SET  ";
		$sql.= $champ1."= '".$val1."' ";
		if (!empty($champ2) ) $sql.= ' , '.$champ2.'= "'.$val2.'" ';
		if (!empty($champ3) ) $sql.=  ',  '.$champ3.'= "'.$val3.'" ';
		if (!empty($champ4) ) $sql.=  ',  '.$champ4.'= "'.$val4.'" ';
		$sql.= "  Where ".$sqlwhere;
		$this->db->begin();
		// liste champ mis à jours
		if (!empty($champ1) )  $lb = "champs:".$champ1;
		if (!empty($champ2) ) $lb .= "---".$champ2;
		if (!empty($champ3) ) $lb .= "---".$champ3;
		if (!empty($champ4) ) $lb .= "---".$champ4;
		if (!empty($champwhere)) $lb .= "--- pour ".$sqlwhere;
		
	   	dol_syslog(get_class($this)."::update_".$lb, LOG_DEBUG);		
	
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::update_champs_filtre ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
	} // update_champs_filtre
	function updatelineDate() 
	{
		global $user;
		
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line ) {
				if ($line->type_enr == $line->LINE_ACT  and $line->action <> 'X' and $line->action <> 'S') {
					$line->dateretrait = $this->locdateretrait ;
					$line->lieuretrait = $this->loclieuretrait ;
					$line->datedepose = $this->locdatedepose ;
					$line->lieudepose = $this->loclieudepose ;
					$line->updateLocMat($user);				
				}
			} // foreach
		}
 	}// updatelineDate
	/*
	* Met à jour le statut du bulletin dans la base et en mémoire
	*
	*	@param 	int		$statut		Valeur du statut
	*	@param	char	$action		Valeur du champ action
	*/
	function updateStat ($statut,$action)
	{		
		global $user,$langs,$conf;

		$error=0;
		$ret = $this->update_champs( 'statut', $statut, 'action', $action);
		if ($ret >= 0)
		{
			$this->statut = $statut;
			$this->action = $action;
			return 1;
		}
		else
		{
			return -2;
		}
	}//	updateStat
	/*
	*
	*	Mise à jour du flag Facturable
	*
	*	@param int $facturable	 ou 1
	*
	*	@retour	1 OK, <0 non OK
	*/
	function updatefacturable ($facturable)
	{				
		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull SET  ";
		$sql.= 'facturable = "'.$facturable.' "';
		$sql.= "  Where rowid =  ".$this->id;
		$this->db->begin();
	   	dol_syslog(get_class($this)."::updatefacturable".$facturable, LOG_DEBUG);
		
        $resql=$this->db->query($sql);
    	if (! $resql) { 			
			$this->db->rollback();
			return -2; 
		}
  		$this->db->commit();
        return $this->id;
	}//	updatefacturable
	

	function updateregle($regle)
	{		
		$error = 0;
		$now = dol_now ('tzuser');

        // Update request
		$ret = $this->update_champs('regle', $regle, 'tms', $this->db->idate($now));
		if ($ret  < 0) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		
		if ($error)
		{
			dol_syslog(get_class($this)."::updateregle ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			return -1;
		}
		return 1;
	
	} //updateregle
	
	/*				Modifier Societe  (PartTel dans phone sur clé 'Principale' et $this->id_client)
		//				Modifier Societe_extrafields  (PartTel dans s_tel2 sur clé 'Supplementaire' fk_object =  $this->id_client)
		//				Modifier  socpeople (PartTel dans phone dans socpeople sur clé '*pro' et fk_contact)
		//				Modifier  socpeople (PartTel dans phone_mobile dans socpeople sur clé '*mobile' et fk_contact)
	*/
	function update_tel_tiers($lig)
	{			
			// Cas Nom = Principale		
		if ($lig['Nom'] == 'Principal' )
		{
			$sql = 'UPDATE  '.MAIN_DB_PREFIX.'societe SET';
			$sql .=  ' phone = "'. $lig['Tel'].'" ' ;
			$sql .=  ' WHERE rowid = "'. $lig['id'].'"';
		}
			// Cas Nom = Supplementaire
		if ($lig['Nom'] == 'Supplementaire' )
		{
			$sql = 'UPDATE  '.MAIN_DB_PREFIX.'societe_extrafields SET';
			$sql .=  ' s_tel2= "'. $lig['Tel'].'" ' ;
			$sql .=  ' WHERE fk_object = "'. $lig['id'].'"';
		}
			// Cas Nom like %pro			
		if (substr ($lig['Nom'], strlen($lig['Nom'])- 3, 3) == 'pro')
		{
		$sql = 'UPDATE  '.MAIN_DB_PREFIX.'socpeople SET';
		$sql .=  ' phone = "'. $lig['Tel'].'" ' ;
		$sql .=  ' WHERE rowid = "'. $lig['id'].'"';
		}
			// Cas Nom like %mobile
		if (substr ($lig['Nom'], strlen($lig['Nom'])- 6, 6) == 'mobile')
		{
			$sql = 'UPDATE  '.MAIN_DB_PREFIX.'socpeople SET';
			$sql .=  ' phone_mobile = "'. $lig['Tel'].'" ' ;
			$sql .=  ' WHERE rowid = "'. $lig['id'].'"';
		}
			
		$this->db->begin();
		
		 dol_syslog(get_class($this)."::update_tel_tiers ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)	{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::update_tel_bull_det Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}//update_tel_tiers

	
	function calculAge ($birthday, $Maintenant)
	{	
		$tDeb = explode("-", $birthday);
		$tFin = explode("-", $Maintenant);
		$diff = mktime(0, 0, 0, $tFin[1], $tFin[2], intval($tFin[0])) - 
			  mktime(0, 0, 0, $tDeb[1], $tDeb[2], intval($tDeb[0]));
		  
		$nbjours=($diff / 86400)+1;
		$Age=intval($nbjours/365);
		return($Age);
	} //calculAge
	function RechercheNouvRefBull($type='Insc')
	{
		global $conf;		
		// D'abord on recupere la valeur max
		// Format BUAAAAMM-NNNN

		$posindice=10;
		$sql = "SELECT SUBSTRING(ref FROM ".$posindice.") as max";
		$sql .= ", SUBSTRING(ref , 7,2) as mois";
		$sql .= ", SUBSTRING(ref , 3,4) as annee";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull";
		$sql.= " WHERE entity = ".$conf->entity;
		if ($type == 'Insc') $temp = 'BU'; 
		elseif ($type == 'Loc') $temp = 'LO'; 
		elseif ($type == 'Resa')   $temp = 'RE';
		$sql .=" AND ref like '".$temp."______-%'";
		$sql .= " AND ref = (SELECT  MAX(ref) from ".MAIN_DB_PREFIX."cglinscription_bull where  ref like '".$temp."______-%')";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj) 
			{
				$max = intval($obj->max);
				$moisbul=intval($obj->mois);
				$anneebul=intval($obj->annee);
			}
			else $max=0;
		}
		else
		{
			dol_syslog("mod_commande_marbre::RechercheNouvRefBull ");
			return -1;
		}
		$date=dol_now('tzuser');
		$yyyy = intval(strftime("%Y",$date));
		$mm =  intval(strftime("%m",$date));
		$num=sprintf("%04s",1);
		$mois= sprintf("%02s",$mm);
		$annee=sprintf("%04s",$yyyy);
		if (($anneebul == $yyyy) and ($mm == $moisbul) )
		{
				$num = sprintf("%04s",$max+1);
		}
		if ($type == 'Insc') $ref='BU'.$annee.$mois."-".$num;
		elseif ($type == 'Loc') $ref="LO".$annee.$mois."-".$num;
		elseif ($type == 'Resa') $ref="RE".$annee.$mois."-".$num;
		dol_syslog("mod_commande_marbre::getNextValue return ".$ref);
		return $ref;
	}/*RechercheNouvRefBull*/
	/* 
	*  Recupère la ligne du bulletin qui porte le rang d'écran rang 
	* Retourne la ligne
	* Retourne null si non trouvé
	*/
	function RechercheLign ($idbulldet) 
	{
		global $id_act, $id_part;		
		$line = null;
		$result=null;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line )		{	
				if ($line->id == $idbulldet) {	
					$result = $line;
					break;
				}
			} /* foreach */
		}
		if ($bull->type == 'Insc') {
			if ( !empty($result->id_act) and $result->id_act > 0 ) $id_act = $result->id_act;
			if (! empty ($result->id_part) and $result->id_part > 0)$id_part = $result->id_part;	
		}
	return $result;
	} /* RechercheLign */
	
	function RecherchePremLignBySess ($idSes) 
	{
		global $id_act, $id_part;		
		$line = null;
		$result=null;
		if ( !empty($this->lines)) {	
			foreach ($this->lines as $line )		{	
				if ($line->id_act == $idSes)	
					$result = $line;
			} /* foreach */
		}
	return $result;
	} /* RecherchePremLignBySess */
	
	/* *
	*  Recupère la ligne du bulletin qui porte le type 2 
	* Retourne la ligne
	* Retourne null si non trouvé
	*/
	function RechercheRemFix () 
	{
		global $id_act, $id_part;		
		$line = null;
		$result=null;
	
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line )		{	
				if ($line->type_enr == $line->LINE_BC )	
					$result = $line;
			} /* foreach */	
		}		
		return $result;
	} /* RechercheRemFix */
	/*
	* recherche si un reglement a été fait
	*
	* return 1 si oui, 0 si non
	*/
	function RecupReglement()
	{	
		$regle=0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line)	{
//				if ($line->type_enr == $line->LINE_PMT and !empty($line->pt )) 		$regle = 1;
				if ($line->type_enr == $line->LINE_PMT and !empty($line->montant )) 		$regle = 1;
			} /* foreach */	
		}
		return $regle;
	} /*RecupReglement*/
	function TotalFacssRem()
	{	
		$total=0.0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line )		{	
				if ($line->type_enr == $line->LINE_ACT   and $line->action != 'X' and $line->action != 'S')			{
					$total += $line->calulPtAct($this->type_session_cgl,$line->pu,$line->qte,$line->remise_percent);
				}
			} /* foreach */
		}
		return $total;		
	} /*TotalFac*/
	function TotalRemFix()
	{	
		$total=0.0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line )		{	
				if ($line->type_enr == $line->LINE_BC  and $line->action != 'X' and $line->action != 'S')			{
					$total += $line->mttremfixe;
				}
			} /* foreach */
		}
		return $total;		
	} /*TotalRemFix*/
	/*
	* DEPRECIATION - Ne devrait plus être utile car la saisie d'une remise fixe devient impossible
	* Si toutes les lignes sont en TVA à 20% alors on peux mettre une TVA récupérable sur une remise fixe
	*/
	function TauxTVARemiseFixe()
	{
		$w1 = new CglFonctionDolibarr($this->db);
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line) {
				if ($line->action <> 'X' and $line->action <> 'S' and $line->type_enr == $line->LINE_ACT ) 
					if ($line->taux_tva == 0) return 0;
			} //foreach
		}
		return $w1->taux_TVAstandard();
	}// TauxTVARemiseFixe
	
	function TotalFac()
	{			
		$total = $this->TotalFacssRem() - $this->TotalRemFix() ;
		if ($total < 0.01 and $total > -0.01) $total=0; 
		return round ($total,2);		
	} /*TotalFac*/
	
	/* 
	* renvoie le total payé du bulletin
	* retour	total
	*/
	function TotalPaimnt()
	{
		$total=0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line )
			{
				if ( $line->action != 'X' and $line->action != 'S' and $line->type_enr == $line->LINE_PMT  )
					  $total += $line->montant;
			} /* foreach */
		}
		return $total;
		
	} /*TotalPaimnt*/

	/* 
	* renvoie le total  du bulletin encaissé par Stripe
	* retour	total
	*/
	function TotalPaimntStripe()
	{
		$total=0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line )
			{
				if ( $line->action != 'X' and $line->action != 'S' and $line->type_enr == $line->LINE_PMT  )
					if ($line->organisme == 'Stripe') 
						 $total += $line->montant;
			} /* foreach */
		}
		return $total;
		
	} /*TotalPaimntStripe*/

	/* 
	* renvoie le total  du bulletin encaissé par Stripe
	* retour	total
	*/
	function TotalPaimntStripeNonEncaisse()
	{
		$total=0;
		if ( !empty($this->lines_stripe)) {
			foreach ($this->lines_stripe as $line )
			{
				if ( $line->action != 'X' and $line->action != 'S' and $line->type_enr == $line->LINE_STRIPE  and  empty($line->date_paiement) )			
						 $total += $line->montant;				
			} /* foreach */
		}
		return $total;
		
	} /*TotalPaimntStripeNonEncaisse*/

	function LireSolde()
	{				
		// recherche le prochain rang dans la base,du type d'enregistrement concerné
// CCA - on doit reprendre le calcul ddu total en fonction d'une remise fixe ou variable		
		$sql = 'SELECT SUM(CASE WHEN type = 1 THEN pt ELSE 0 END )  ';
		$sql.= ' - SUM(CASE WHEN type = 0 THEN  pu * qte * (100-rem) / 100 ELSE 0 END) as solde';
		$sql.= ' FROM  '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' WHERE fk_bull = '.$this->id;
		
		$sql.= " AND action not in ('X','S') ";	
		
		dol_syslog(get_class($this)."::LireSolde ");
		$resql=$this->db->query($sql);		
        if ($resql)        {	
            if ($this->db->num_rows($resql))            {
                $obj = $this->db->fetch_object($resql);
				$solde = $obj->solde;
			}
			else			{
				dol_syslog(get_class($this)."::LireSolde 99998");
				return -99998;
			}	
		}
		else		{
			dol_syslog(get_class($this)."::LireSolde  99999");
			return -99999;
		}	
		
		return $solde;		
	} /*LireSolde*/
	/*
	*
	* Calcul du montant de l'acompte demandé pour une location afin de réserver celle-ci
	*
	* @retour double montant de l'acompte exigible
	*/
	function CalculAcompte()
	{
		return round(price2num((0.3 * $this->TotalFac())+1));
	}//CalculAcompte
	/*
	*	Fonction permettant de récupérer l'état du flag réglé (état du paiement du BU/LO)
	* 
	*	@return	int		valeur du flag réglé recalculé du BU/LO
	*/
	function RecalculRegle ()
	{
		if (!empty($this->fk_facture))
		{
			return $this->BULL_FACTURE;
		}
		if ( $this->solde > 0 )
		{
			return $this->BULL_INCOMPLET;
		}
		if ( $this->solde == 0 )
		{
			return $this->BULL_PAYE;
		}
		if ( $this->solde < 0 )
		{
			return $this->BULL_SURPLUS;
		}
		if ( $this->paye == 0 and $this->nbPmt > 0 )
		{
			return $this->BULL_REMB;
		}
		if ( $this->paye == 0 and $this->nbPmt == 0 )
		{
			return $this->BULL_NON_PAYE;
		}
	} // RecalculRegle
		/*
	* Position de le statut du contrat à Cloturé (MAJ Base)
	*
	*	@retour int 0==> OK, <0 pour erreur
	*/	
	function Statut_Clos ()
	{		
		$error=0;
		return $ret = $this->update_champs('statut', $this->BULL_CLOS);		
	}//	Statut_Clos
	function Statut_DepartFait ()
	{		
		$error=0;
		return $ret = $this->update_champs('statut', $this->BULL_DEPART);		
	}//	Statut_DepartFait
	
	function Statut_Reserver ()
	{		
		$error=0;
		return $ret = $this->update_champs('statut', $this->BULL_VAL );		
	}//	Statut_Reserver

	function regle_archive()
	{	
        return $this->update_champs('regle',$this->BULL_ARCHIVE); 		
	} //regle_archive


	/*
	* Position de le statut du bulletin à Abandonné (MAJ Base)
	*
	*	@retour int 0==> OK, <0 pour erreur
	*/	
		
	function Statut_Abandon ()
	{	
		$error=0;
		$ret = $this->update_champs('statut', $this->BULL_ABANDON);	
		if ($ret >0) $this->statut = $this->BUL_ABANDON;	
		return $ret;
	}//	Statut_Abandon
	/*
	* Position de le statut du bulletin à Annulée par le client (MAJ Base)
	*
	*	@retour int 0==> OK, <0 pour erreur
	*/	
	function Statut_AnnulClient ()
	{		
		global $user,$langs,$conf;

		$error=0;
		$ret = $this->update_champs('statut', $this->BULL_ANNULCLIENT);
		if ($ret >= 0) $this->statut = $this->BULL_ANNULCLIENT;
		return $ret;
		
	}//	Statut_AnnulClient

	function transRegle()
	{
		if ($this->regle == $this->BULL_NON_PAYE) $statut = 'Non payé';
		elseif ($this->regle == $this->BULL_INCOMPLET) $statut = 'Incomplet';
		elseif ($this->regle == $this->BULL_PAYE) $statut = 'Paye';
		elseif ($this->regle == $this->BULL_SURPLUS) $statut = 'Trop paye';
		elseif ($this->regle == $this->BULL_REMB) $statut = 'Rembourse';
	
	}/*transRegle*/
	/*
	*	Renvoie le libellé correspondant au statur
	*
	*
	*/
	function transStrStatut()
	{ 
		global $langs;
		if ($this->statut == $this->BULL_ENCOURS) $temp = $this->LIB_ENCOURS;
		if ($this->type == 'Insc' or empty($this->type)) {
			if ($this->statut == $this->BULL_INS) $temp = $this->LIB_INS;
			elseif ($this->statut == $this->BULL_PRE_INS) $temp = $this->LIB_PRE_INS;
			if ($this->statut == $this->BULL_CLOS) $temp = $this->LIB_ARCHIVE;
			elseif ($this->statut == $this->BULL_ABANDON) $temp =$this->LIB_ABANDON;
			elseif ($this->statut == $this->BULL_ANNULCLIENT) $temp =$this->LIB_ANNULCLIENT;
		}
		else
		{
		if ($this->statut == $this->BULL_CLOS) $temp = $this->LIB_CLOS;
			if ($this->statut == $this->BULL_VAL) $temp = $this->LIB_VAL;
			if ($this->statut == $this->BULL_PRE_INSCRIT) $temp = $this->LIB_PRE_RES;			
			elseif ($this->statut == $this->BULL_DEPART ) $temp =$this->LIB_DEPART;
			elseif ($this->statut == $this->BULL_RETOUR) $temp =$this->LIB_RETOUR;
			elseif ($this->statut == $this->BULL_ABANDON) $temp =$this->LIB_ABANDON;
			elseif ($this->statut == $this->BULL_ANNULCLIENT) $temp =$this->LIB_ANNULCLIENT;			
		}
		return $temp;
				
	}/*transStrStatut*/
	function transStrRegle()
	{
		global $langs, $bull;		
		if ($this->regle == $this->BULL_NON_PAYE) $regle = $this->LIB_NON_PAYE;
		elseif ($this->regle == $this->BULL_INCOMPLET) $regle = $this->LIB_INCOMPLET;
		elseif ($this->regle == $this->BULL_PAYE) $regle = $this->LIB_PAYE;
		elseif ($this->regle == $this->BULL_SURPLUS) $regle = $this->LIB_SURPLUS;
		elseif ($this->regle == $this->BULL_REMB) $regle = $this->LIB_REMB;
		if ($this->type == 'Loc') {
			if ($this->regle == $this->BULL_FACTURE) $regle = $this->LIB_CNT_FACTURE;
			elseif ($this->regle == $this->BULL_ARCHIVE) $regle = $this->LIB_CNT_ARCHIVE;
		}
		else 		{
			if ($this->regle == $this->BULL_FACTURE) $regle = $this->LIB_FACTURE;
			elseif ($this->regle == $this->BULL_ARCHIVE) $regle = $this->LIB_ARCHIVE;
		}
		
		return $regle;
	
	}/*transStrRegle*/		
	function updateFicBull($fichier, $idses = '')
	{
		$this->ficbull = $fichier;
	
		$i=0;
        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_det SET ";
		$sql.= "  ficbull = '".$fichier."'";
		$sql.= " Where fk_bull =  ".$this->id ;
		if (!empty($idses))  $sql.= " and fk_activite = ". $idses;
		$sql.= " and action not in ('X','S')";
		$sql.= " and type = 0";
			
		$this->db->begin();
	   	dol_syslog(get_class($this)."::updateFicBull ", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
	        
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::updateFicBull ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		
		if ($this->type == 'Insc') {
			// pour chaque ligne de la session, mettre le nom du fichier
			
			if ( !empty($this->lines)) {
				foreach($this->lines as $line) {
					if ($line->type_enr == $line->LINE_ACT  and $line->action <> 'X' and $line->action <> 'S' and $line->id_act == $idses) {
						$line->ficbull = $fichier;
					}
				}	// foreach		
			}
		}			
		
		$this->db->commit();
		return $this->id;
	} //updateFicBull
		/*
	* param $type 	 'fichier': demande de nom de fichier bulletin seulement  - 
	*				 'modele': nom de modèle 
	*/
	function NommageEditionBulletin($type, $session='')
	{
		global $conf;
			
		if (empty($session)) {
			if ( !empty($this->lines)) {
				foreach ($this->lines as $line){
					if ($line->type_enr == $line->LINE_ACT   and $line->action != 'X' and $line->action != 'S') {
						$linetmp=$this->lines[0];
						break;
					}
				} // foreach
			}
		}
		else $linetmp=$this->RecherchePremLignBySess($session);
		if ($type == 'fichier' or $type == 'chemin_fichier') {
			if (empty($session )) {
				$ret = substr($linetmp->ficbull,1,strlen($linetmp->ficbull)-4);
			}
			elseif ($linetmp->activite_lieu <> 'AUTRES Site à définir')
				$ret = $conf->cglinscription->dir_output.'/bulletin/'.$this->ref.'/'.$this->ref.'_BULL_IND_'.$linetmp->id_site.'_'.$linetmp->id_act;
			else 
				$ret = $conf->cglinscription->dir_output.'/bulletin/'.$this->ref.'_BULL_IND__'.$linetmp->id_act;
			}
		elseif	($type == 'modele')	{
			$trtnomsite = str_replace(' ','_',$linetmp->activite_lieu);
			if ($linetmp->activite_lieu <> 'AUTRES Site à définir')
				$fich = DOL_DATA_ROOT.'/doctemplates/bulletin/BULL_IND_'.$trtnomsite.'.odt';
			else 	
				$fich = DOL_DATA_ROOT.'/doctemplates/bulletin/BULL_IND.odt';	
			$ret = 'bulletin_odt:'.$fich;
			if (!file_exists($fich))		$ret = 'bulletin_odt:'.DOL_DATA_ROOT.'/doctemplates/bulletin/BULL_IND.odt';		
		}
			return $ret;	
	}//NommageEditionBulletin



	function AjRemGen ($MttRemFixe, $RaisRemGen, $textremisegen) 
	{
	global $user, $langs;	
	
		$this->db->begin();
		if  ( !(price2num($MttRemFixe) ==  0.00))  {	
			$line = New BulletinLigne ($this->db);
			$line->mttremfixe = price2num($MttRemFixe);
			$line->fk_remgen = $RaisRemGen;
			$line->textremisegen = $textremisegen;
			$line->qte = 1;
			$line->rang = 99;
			$line->fk_bull = $this->id;	
			$line->insertRemiseFixe($user);	
			unset ($line);
			$this->updateregle($this->CalculRegle());		
			$this->db->commit();
		}
	} // AjRemGen
	
	
	function CalculRegle()
	{	
		global $conf, $langs, $user;
		
		/* si aucun paiemnt 				==> non payé (0)
			somme paiement < total 			==> imcomplet (1)
			somme paiement = total 			==> 
					si existe paiement negatif ==> remboursé (4)
					sinon 					==>payé (2)
			somme paiement > total 			==> surplus (3)
			
			si achive (7 ou facture 6 ==> ne rien faire
		*/
		//if ($this->regle >4) return $this->regle;
		
		// CALCUL
		$remb = 0;
		$totalfact = 0;
		$totalpaimt = 0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line)		{
				if ($line->type_enr == $line->LINE_PMT  and $line->action != 'S' and $line->action != 'X')			{
					$totalpaimt +=  $line->montant;
				}
				elseif ($line->type_enr == $line->LINE_ACT  and $line->action != 'S' and $line->action != 'X')			{
					if ($line->qte  == 0) $qte = 1; else $qte = $line->qte ;
					$totalfact += $line->calulPtAct($this->type_session_cgl,$line->pu,$line->qte,$line->remise_percent);
					if ($line->pu <0 or $line->qte  < 0) $remb = 1;	
				}			
				elseif ($line->type_enr == $line->LINE_BC  and $line->action != 'S' and $line->action != 'X')			
					$totalfact -=  $line->mttremfixe;
			} /* foreach*/
		}
		$regle = '';	
		// approximation
		$ptt2=$totalpaimt - $totalfact;
		if ($ptt2 < 0.01 and $ptt2 > -0.01) $totalfact = $totalpaimt;
		if ($totalpaimt == 0) $regle = $this->BULL_NON_PAYE;
		elseif ($totalpaimt < $totalfact )  {
			$regle = $this->BULL_INCOMPLET;
}
		elseif ($totalpaimt > $totalfact )  $regle = $this->BULL_SURPLUS;
		elseif ($totalpaimt == $totalfact )  			
			if ($remb == 1) $regle = $this->BULL_REMB;
			else $regle = $this->BULL_PAYE;		
		return $regle;
	} //CalculRegle
	
	function AjoutFkDolibarr ($valaction ='')
	{
	// maj acton et fk_commande dans bulletin
		$error = 0;
        // Update request
		if (empty($this->fk_commande)) $this->fk_commande = 0;
		if (empty($this->fk_facture)) $this->fk_facture = 0;
		if (empty($this->fk_acompte)) $this->fk_acompte = 0;
		$ret = $this->update_champs( 'fk_cmd', $this->fk_commande,'action', $valaction,'fk_facture',$this->fk_facture,'fk_acompte',$this->fk_acompte);	
		if (!empty($this->ref_commande) or !empty($this->fk_soc_rem_execpt)) 
				$ret1 = $this->update_champs('ref_cmd', $this->ref_commande,'fk_soc_rem_execpt',$this->fk_soc_rem_execpt);		
		
		if ($ret+$ret1<0) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
 
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line)
			{		
				$error = $line->AjoutFkDolibarr($valaction);
			} // foreach
		}
		if ($error)
		{
			dol_syslog(get_class($this)."::AjoutFkDolibarr ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			return -1*$error;
		}
		return 1;
	} // AjoutFkDolibarr
	// obsolete - suppression du champ 
	function updateFicCmd_old ($fichier)
	{	
		$i=0;
        // Update request
		
		$ret = $this->update_champs('ficcmd', $fichier);	
    	if (! $ret) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
	        
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::updateFicCmd ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			return -1*$error;
		}
		else
		{
            return $this->id;
		}
	} //updateFicCmd
	// Mettre à jour modif de bulletin si une ligne a fait l'objet d'une modification impactant la facture
	function update_tms()
	{
		$this->update_champs ('tms', $this->db->idate(dol_now('tzuser')) );
	} // update_tms	

	function updateaction($val)
	{
		global $user,$langs,$conf;

		$error=0;
        // Mise a jour ligne en base
		$ret = $this->update_champs('action', $val);	
		if ($ret > 0)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::updateaction Error ".$this->error, LOG_ERR);
			return -2;
		}
	}//updateaction
	
	/*
	*	Recherche le modèle de facture concerné*
	*
	*	Sont à la TVA les LO et les BU ayant un moniteur à la TVACommissionnement
	*
	*	@param string	$nomclasse	'facture' et  cherche la TVA, 'Commande' et on retourne rien
	*	@retour	string 				nom du modèle
	*/
	function RechercheModelFactCmd($nomclasse)
	{
		global $langs, $bull;	

		$modelComm = 0;
		$modelTVA = 0;
		if ($nomclasse == 'Commande') $model = '';
		elseif ($this->type == 'Loc') $model = "TVA";
		elseif ($this->type == 'Insc') {
			if ( !empty($this->lines)) {
				foreach ($this->lines as $line)
				{
					if ($line->action == 'X' OR $line->action == 'S') continue;
					if ( $line->type_enr == $line->LINE_ACT  and $line->taux_tva == 0)		$modelComm ++;
					else if ( $line->type_enr == $line->LINE_ACT ) $modelTVA++;
				} // foreach
			
				if ($modelComm > 0 and 	$modelTVA > 0) $model = "FAComTVA";
				elseif ($modelComm > 0  and empty($modelTVA > 0)) $model = "FAcomm";
				elseif ($modelComm == 0 and 		$modelTVA > 0) $model = "TVA";
				else $model = 'TVA';
			}
		}		
		return $model;
	} //RechercheModelFact
	
	function updateTypesession()
	{	
		global $user,$langs,$conf;

		$error=0;;
        // Mise a jour ligne en base
		$ret = $this->update_champs('fk_type_session', $type_session);	
 		 
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::updateTypesession Error ".$this->error, LOG_ERR);
			return -2;
		}
	} // updateTypesession
	function CalculJH ($date, $heure)
	{
		
		return dol_now('tzuser');
	} // CalculJH
	/*
	*	 Si une line existe déjà, on prend les dates qui sont saisies
	*	  sinon si une date et saisie dans bull->locDateRetrait on la prend
	*	  sinon date du jour
	*/
	function calcul_date_defaut_location_retrait()
	{		
		if ($this->count_lg_spec( '0')>0)
		{
			$line = $this->Recherche_derniere_ligne();
			$date = $line->dateretrait;
		}
		elseif (!empty ($this->locdateretrait))
			$date = $this->locdateretrait;
		else  {
			$wdate = new DateTime ();
			$dat = $wdate->format("Y-m-d H:i:00");
		}
		return($date);		
	} // calcul_date_defaut_location_retrait
	function calcul_date_defaut_location_depose()
	{
		if ($this->count_lg_spec( '0')>0)
			$date = $this->Recherche_derniere_ligne()->datedepose;
		elseif (!empty ($this->locdatedepose))
			$date = $this->locdatedepose;
		else { 
			$wdate = new DateTime ();
			$dat = $wdate->format("Y-m-d H:i:00");
		}
		return($date);	
	} // calcul_date_defaut_location_depose
	
	function calcul_lieu_defaut_location_depose()
	{
		if ($this->count_lg_spec( '0')>0)
			$lieu = $this->Recherche_derniere_ligne()->lieudepose;
		elseif (!empty ($this->loclieudepose))
			$lieu = $this->loclieudepose;
		return($lieu);	
	} // calcul_lieu_defaut_location_depose

	function calcul_lieu_defaut_location_retrait()
	{
		if ($this->count_lg_spec( '0')>0)
			$lieu = $this->Recherche_derniere_ligne()->lieuretrait;
		elseif (!empty ($this->loclieuretrait))
			$lieu = $this->loclieuretrait;
		return($lieu);	
	} // calcul_lieu_defaut_location_retrait
	
	function Recherche_derniere_ligne()
	{			
		$max=0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line)
			{
				if ($line->type_enr ==  $line->LINE_ACT  and $line->id > $max) {
					$max = $line->id ;
					$linemax = $line ;
				}
			} // foreach
		}
		return $linemax;
	} // Recherche_derniere_ligne
	/* compte le nombre d'enregistrements de type 0 (participation ou location) ou 1 (reglement)
	*/
	function count_lg_spec($type = '0')
	{
		$nbpart = 0;
		$nbpaie=0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line)
			{
				if ($line->type_enr ==  $line->LINE_ACT  ) $nbpart++;
				elseif ($line->type_enr ==  $line->LINE_PMT  ) $nbpaie++;
			} // foreach
		}
		if ($type ==0) return $nbpart;		
		elseif ($type ==1) return $nbpaie;
	} //count_lg_spec

	/*
	*
	*	Passer le statut du bulletin/contrat à BULL_ABANDON 
	*	et positionner l'action des lignes de détail à S pour indiquer leur non-pertiences
	*/
	function Abandon()
	{
		$this->Statut_Abandon();
	} //Abandon

	/*
	*
	*	Passer le statut du bulletin/contrat à BULL_ANNULCLIENT
	*	
	*/
	function AnullClient()
	{
		global $user,$langs,$conf;
		$error=0;		
		$this->db->begin();
		 
        // Mise a jour de l'entête en base
		// Obsolette car champ observation a disparu de l'écran
		$nom = $user->lastname.' '.$user->firstname;
 		$sqlwhere =" rowid = ".$id;
		$sqlwhere.=" and type = 0";
		$text = 'concat(observation, " "';
		$text .= $langs->trans("LibObsAnnullClient");
		$text .= '" ")'.$nom;
		$ret = $this->update_champs_filtre($sqlwhere, 'observation',$text, 'statut', $BULL_ABANDON);
		if ($ret)
		{				 
			$this->db->commit();
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::AnnulClient Error ".$this->error, LOG_ERR);
			return -2;
		}

		$ret = $this->Statut_AnnulClient ();	
		if ($ret <0) 
		{
			setEventMessage($langs->trans("LibErrBulAnnulClient"), 'warnings');
		}
		else
		{
			if ($this->type == 'Insc') 
			{
				//setEventMessage($langs->trans('LibNoteSessionAband'), 'warnings');
			}
		}

		$this->statut = $this->BULL_ANNULCLIENT;
	} //Abandon
	
	
		
	/*
	*	Passer le statut de reglement du bulletin/contrat à BULL_ARCHIVE
	*/	
	function Archive()
	{
		global $user,$langs,$conf;
		$error=0;
		$this->db->begin();
			 
        // Objet de l'archivage		
		$ww = New CglCommunLocInsc($this->db);
		global  $closeAbandon, $arrayreasons, $langs;
		require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglGeneral.class.php';
		$ww = new General($db);
		$ww->init_close();
		unset ($ww);
		unset  ($ww);			
		$close_code =GETPOST("close_code",'alpha');
		$close_note=GETPOST ( 'close_note', 'alpha' );

		for ($i=1;$i<4; $i++) 
		{
			if ($close_code == $closeAbandon [$i]['code'] ) break;
		}
		if ($i <4) 
		{
			$text = $closeAbandon[$i]['origine'];
			$text .= ' - <b>Commentaire</b> : ';
			$text .= $close_note;
		}	
		$this->update_champs('abandon', $text);
		$ret = $this->regle_archive ();	
		if ($ret <0) 
		{
			if ($bull->type == 'Loc') $text1 = 'LibErrLoArchiv';
			else  $text1 = 'LibErrBulArchive';
			setEventMessage($langs->trans($text1), 'warnings');
		}
		else
		{
			if ($this->type == 'Insc') 
			{
				setEventMessage($langs->trans('LibNoteSessionAband'), 'warnings');
			}
		}
			
		$this->updateregle($this->BULL_ARCHIVE);
	} //Archive
	
	function UpdateVentilbySess( $id_depart, $fk_ventil)
	{	
		$error=0;
	
		if (empty($fk_ventil)) return 0;;
	
		$this->db->begin();
        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_det SET  ";
 		$sql.="  fk_code_ventilation = '".$fk_ventil ."'";
		$sql.=" where fk_activite = '".$id_depart."'";		
		 dol_syslog(get_class($this)."::UpdateVentilbySess ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($this->dt_facture)) $this->update_tms();
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::UpdateVentilbySess Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
		
	} //UpdateVentilbySess


	function UpdateBullFactbySess( $id_depart)
	{		
		$error=0;
		$sqlwhere =" exists (select (1) from ".MAIN_DB_PREFIX."cglinscription_bull_det where fk_activite = '".$id_depart. "')"  ;
		$sqlwhere.=" AND  statut > '".$this->BULL_FACTURE."'";

		$ret = $this->update_champs_filtre($sqlwhere, 'statut', $this->BULL_FACTURE);	

		if ($ret)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::UpdateBullFactbySess Error ".$this->error, LOG_ERR);
			return -2;
		}
		
	} //UpdateBullFactbySess
	

	function InfoRemFixe ()
	{
		$retour ='';
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line) {
				if ($line->type_enr == $bullline->LINE_BC and $line ->action != 'S' and $line ->action != 'X') {
					if (!empty($retour)) $retour .= ' / ';
					$retour .= $line->textnom.' - '.$line->textremisegen;	
				}				
			}	 //foreach
		}		
		return $retour;
			
	} //InfoRemFixe

	function desincrire($id_act)
	{		
		$flmodif=false;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line) {
				if ($line->id_act == $id_act) {
					if ($this->statut >= $BULL_INS ) $val = 'S';
				else $val = 'X';
				$line->updateaction ($val);
				$flmodif=true;
				}
			} //foreach		 
		}
		if ($flmodif and $this->statut >= $BULL_INS) {
			unset($objdata);
			$this->update_tms();		
		}
		return 1;
	} //desincrit
	
	function nbparticipantdepart($id)
	{
		$nbparticipantsession = 0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $wline) {
					if ($wline->type_enr ==  $wline->LINE_ACT and  $wline->action != 'X' and  $wline->action != 'S' and $wline->id_act = $id)
						$nbparticipantsession +=  $wline->qte ;			
			} // foreach
		}
		return $nbparticipantsession;
	} // nbparticipantdepart
	function IsMoniteurAbsent()
	{ 	
		global $gl_activite; // Utilisée dans FactureBulletin
		$gl_activite = '';		
		if ($this->type == 'Loc') return false;
		$moniteurabsent = false; 
		if ( !empty($this->lines)) {
			foreach ($this->lines as $bulline) 
				if ($bulline->type_enr ==  $bullline->LINE_ACT and $bulline->action <> 'X' and $bulline->action <> 'S')
					if ( !empty($bulline->id_act) and empty($bulline->act_moniteur_nom )) {
						if ($moniteurabsent == true) $gl_activite .= ' - ';
						$gl_activite .= $bulline->activite_label.'('.$bulline->id_act.')';
						$moniteurabsent = true;	
					}
		}
		return $moniteurabsent ;
	}//IsMoniteurAbsent
	function IsCodeVentilationAbsent ()
	{ 	
		global $gl_activite; // Utilisée dans FactureBulletin
		$gl_activite = '';
		if ($this->type == 'Loc') return false;
		$codeventilationabsent = false; 
		if ( !empty($this->lines)) {
			foreach ($this->lines as $bulline) 
				if ($bulline->type_enr ==  $bullline->LINE_ACT and $bulline->action <> 'X' and $bulline->action <> 'S')
					if ( !empty($bulline->id_act) and (empty($bulline->fk_code_ventilation ) or $bulline->fk_code_ventilation== 0 )) {
						if ($codeventilationabsent == true) $gl_activite .= ' - ';
						$gl_activite .= $bulline->activite_label.'('.$bulline->id_act.')';
						$codeventilationabsent = true;	
					}
		}
		return $codeventilationabsent ;
	}//IsMoniteurAbsent

	// Recherche des taux de TVA des lignes de type 0
	function RechTvaRemFix()
	{
	/* TVA non récupérable sur remise fixe	$tva = '';
		foreach ($this->lines as $bulline) {
			if ($bullline->type_enr == 0) {
				if (empty($tva))
					$tva = $bulline->taux_tva;
				elseif ($tva <> $bulline->taux_tva) return (-1);
			}
		}
		return $tva;
		*/
		return 0;
	} // RechTvaRemFix
	
	function IsMineur() 
	{
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line) {
				if ($line->type_enr == 0 and $line->action != 'S' and $line->action != 'X'  ) {
				if ($line->PartAge < 18 or $line->PartAge == 100) return True;
				}
			} //foreach
		}
		return False;		
	}//IsMineur
	/*
	*	 Détermination d'un bulletin sur départs de groupes reconnaissables par nb de ligne où pu = 0 est supérieur à 3
	*/
	function IsBullGroupe()
	{	
		$nblignevide = 0;
		$nblignefact = 0;
		$nblignebull = 0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $bullline)			{
				if ($bullline->action != 'X' and $bullline->action != 'S' and ($bullline->type_enr == $bullline->LINE_ACT or $bullline->type_enr == $bullline->LINE_BC ) )	{
				if ((int)$bullline->pu + (int)$bullline->remise_percent == 0 )$nblignevide++;
					else $nblignefact++;
					$nblignebull++;					
				}
			} //foreach
		}
		if ($nblignevide > 3) return true;
		else return false;
			
	}// IsBullGroupe
	
	/*
	* Permet de savoir si le bulletin est réalisé ou non
	*
	* retour false sinon, true si oui
	*/
	function IsBullRealise ()
	{
		if ($this->type == 'Loc')
		{
			if ($this->statut >= $this->BULL_PRE_INSCRIT) return true;
			else return false;
		}
		else if ($this->type == 'Insc')
		{	
			$Isrealise = true;
			$now = $this->db->idate(dol_now('tzuser'));			
			if ( !empty($this->lines)) {
				foreach ($this->lines as $line)
				{
					if ($line->action <> 'X' and $line->action <> 'S' and $line->type_enr == $line->LINE_ACT and $line->activite_heuref > $now) 
					{
						$Isrealise = false;
					}
				} //foreach
			}
			return $Isrealise;
		}
	}//IsBullRealise
	
	/* 
	* 	Teste si le paiement correspond aux 30% du facturé
	*
	*	Retrun True si Montant paiement dépasse 30% montant facturé
	*/
	function IsLocPaimtReserv	()
	{
		$Mttfac = $this->TotalFac();
		$Mttpaimt = $this->TotalPaimnt();
		if ($Mttpaimt >= $Mttfac * 30 /100) return true;
		else return false;
		
	} //IsLocPaimtReserv


	/*
	*	retourne 		-1 si fk_banque null ou ecriture id=fk_banque inexistantefunction
	*
	*/
	function TestPresenceEcriture ($gesterr = 0)
	{
		global $langs;
		$msgerros = array();
		$error = 0;	
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line)
			{
				if ($line->type_enr == $line->LINE_PMT  and $line->action != 'X' and $line->action != 'S') {
					if (empty($line->fk_banque ) or !$line->fk_banque) {	
						$msgerros[$line->id] = new stdClass();				
						$msgerros[$line->id]->montant = $line->montant;
						$msgerros[$line->id]->tireur = $line->tireur;
						$msgerros[$line->id]->cause = 0;
						$error++;
					}
					else {
						$sql = "select * from  ".MAIN_DB_PREFIX ."bank where rowid = ".$line->fk_banque;       
						dol_syslog(get_class($this)."::TestPresenceEcriture");
						$resql=$this->db->query($sql);
						if ($resql and  $this->db->num_rows($resql) == 0) {
							$msgerros[$line->id] = new stdClass();
							$msgerros[$line->id]->montant = $line->montant;
							$msgerros[$line->id]->tireur = $line->tireur;
							$msgerros[$line->id]->cause = 1;	
							$error++;	
						}
					}
				}
			} // foreach
		}
		if ($gesterr == 0) {
			foreach ($msgerros as $ligerror) {
					if ($ligerror->cause == 0) {
						$lberror = "L'ecriture du paiement de ";
						$lberror .=  price2num($ligerror->montant);
						$lberror .=   ' euros, paye par ';
						$lberror .=   $ligerror->tireur;
						$lberror .=    " est absente ";
					}
					else $lberror = "L'ecriture du paiement de ". price2num($ligerror->montant). ' euros, paye par'.$ligerror->tireur. "n'existe plus  ";
					setEventMessage($lberror,'errors');
			} // foreach
		}
		else return $msgerros;
	
	} // TestPresenceEcriture
		
	/* 
	* Recherche si des remboursement ont été fait sur ce bulletin
	*
		retour int Nb de paiements négatifs
	*/
	function ExistPmtNeg()
	{
		global $langs;

			$i=0;
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line ) {
				if ($line->type_enr == $line->LINE_PMT  and $line->action <> 'X' and  $line->action <>'S' and $line->montant <0) {
					$i++;
				}
			} // foreach
		}
		return $i;		
	} // ExistPmtNeg

	/*
	* Charger un bulletin group, en regroupant les lignes sur l'activité - le libelle de l'enregistrement par activité comporte le nombre de participants
	*/
	
	/*
	*	Indique qu'il existe une remise fixe
	*
	*/
	function ExistRemFixe ()
	{		
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line)
				if ($line->action <> 'X' and $line->action <> 'S' and $line->type_enr == $line->LINE_BC ) 
					return true;
		}
		return false;
	}//ExistRemFixe
	/*
	*	Indique qu'il existe au moins une ligne de type 0
	*
	*/	
	function ExistActivite ()
	{
		if ( !empty($this->lines)) {
			foreach ($this->lines as $line)
				if ($line->action <> 'X' and $line->action <> 'S' and $line->type_enr == $line->LINE_ACT ) 
					return true;
		}
		return false;
	}//ExistActivite
	function update_tel_sms($tabtel)
	{
		// Enregistrer dans  cglinscription_bull_det type 9, le nom, le téléphone et le flg
		// Modifier les téléphones dans la base societe et socpeuple en fonction des nom dorigine
		
		// recherche dans cglinscription_bull_det et type = 9 - mettre dans une table $tableAnc
		$tableAnc = $this->fetch_tel();
		//		Si  tableAnc vide

		if (empty($tableAnc)) {
			$ret = $this->insert_tel_bull_det_all($tabtel);
			if ($ret < 0) {
				dol_syslog(get_class($this)."::update_tem_sms - Insertions pour prochain SMS non faite", LOG_ERR);
				return  -1;
			}
			
			$ret = $this->update_tel_tiers_all($tabtel);
			if ($ret < 0) {
				dol_syslog(get_class($this)."::update_tem_sms - Modification nouveau tel dans tiers non faite", LOG_ERR);
				return  -1;
			}
			
			else return 1;
		}
		
		// comparer les tables $tabtel et $tableAnc sur Nom		
		if ( !empty($tabtel)) {
			foreach ($tabtel as $key => $tabtelligne)
			{	
				$fl_trouve = false;
				$error=0;	
				if ( !empty($tableAnc)) {	
					foreach ($tableAnc as $keyAnc => $tabtelAncligne)
					{
						if ($tabtelAncligne['Nom'] == $tabtelligne['Nom'] and $tabtelAncligne['id'] == $tabtelligne['id']) 
						{
							$fl_trouve = true;
							break;
						}				
					} // foreach $tableAnc
				}
				if ($fl_trouve) { 
				
					//		Si le Tel  ou le flag ont été modifiés, répercuter dans cglinscription_bull_det
					if($tabtelAncligne['Tel'] <> $tabtelligne['Tel'] or $tabtelAncligne['flg_slct'] <> $tabtelligne['flg_slct']) {
							$ret = $this->update_tel_bull_det($tabtelligne);
							if ($ret < 0) {
								dol_syslog(get_class($this)."::update_tem_sms - Mise à jour Tel ou Flag de Sélection non faite ", LOG_ERR);
								$error++;
							}
					}
					//		Si le Tel  a été modifié, répercuter dans société ou socpeople
					if($tabtelAncligne['Tel'] <> $tabtelligne['Tel']) {
							$ret = $this->update_tel_tiers($tabtelligne);
							if ($ret < 0) {
								dol_syslog(get_class($this)."::update_tem_sms - Mise à jour Tel du tiers non faite ", LOG_ERR);
								$error++;
							}
					}			
				}
				else // Non trouvé dans la base
				{
					$this->insert_tel_bull_det($tabtelligne);
				}
			} // foreach $tabtel
		}
		if ($error== 0) return 1;
		else return -1;
	} //update_tel_sms
	
	/*
	*	 Lecture des téléphones dans societe et cglinscription_bull
	*
	*	@param	$fl			'un' pour juste lecture cglinscription_bull_det
	*						'deux' pour juste lecture societe et socpeople
	*						'tout' pour  lecture complète
	*	 
	*	@retour  array(array('nom'=>$nom, 'tel'=>$tel, 'fl'=>$flg_slct)	nom : origine du téléphone : principal, secondaire, supplémentaaire, nom-prénom contact
	*													tel : téléphone
	*													flg_slct : vrai si sélectionné au précédente envoi SMS, faux sinon
	*/
		
	function fetch_tel_sms($fl = 'tout')
	{
		// Si ligne de type 9 absente de cglinscription_bull_det, lire dans société   avec $bull->id_client et dans socpeople avec fk_soc = $bull->id_client
		// sinon récupérer dans cglinscription_bull_det type 9
		if ($fl == 'tout' or $fl == 'un')
		{
			$tabrettelsms = $this->fetch_tel();
			if (!empty($tabrettelsms) and !is_array($tabrettelsms))		return -1;
			if (!empty($tabrettelsms)) return $tabrettelsms;				
		}

		if ($fl == 'tout' or $fl == 'deux')
		{
			$tabrettelsms = $this->fetch_tel_tiers();
			if (!empty($tabrettelsms) and  !is_array($tabrettelsms))		return -1;
			return $tabrettelsms;
		}
	} //fetch_tel_sms	
	/*
	*	 Lecture des téléphones dans cglinscription_bull
	*	@retour  array(array('Nom'=>$nom, 'Tel'=>$tel, 'fl'=>$flg_slct)	nom : origine du téléphone : Principal,  Supplementaaire, nom-prénom contact suivi de mobile ou pro
	*													tel : téléphone
	*													flg_slct : vrai si sélectionné au précédente envoi SMS, faux sinon
	*													id : le rowid de table en base, à modifier en cas de changement de telephone
	*/

	function fetch_tel()
	{
		
		$table = array();
		
		$sql = 'SELECT PartTel as Tel, organisme as Nom_Origine, type as EnrTelSMS, qte as flg_slct, fk_contact as fk_id FROM  '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' WHERE fk_bull = '.$this->id.' and type = 9';

		dol_syslog(get_class($this)."::fetch_tel ");
		$resql=$this->db->query($sql);	
        if ($resql)
        {	
            if ($num = $this->db->num_rows($resql))
            {
					for ($i=0; $i<$num;$i++){
						$obj = $this->db->fetch_object($resql);
						$tblligne = array();
						$tblligne['Tel'] = $obj->Tel;
						$tblligne['Nom'] = $obj->Nom_Origine;
						$tblligne['flg_slct'] = $obj->flg_slct;	
						$tblligne['id'] = $obj->fk_id;	
						$table[$i] = $tblligne ;					
					}
				return $table;					
			}
			else return '';
		}
		else  {			
			dol_syslog(get_class($this)."::fetch_tel",  LOG_ERR);
			return -1;
		}
	
	} //fetch_tel
	
	function fetch_tel_tiers()
	{
		$table = array();
		
		$sql = 'SELECT s.rowid as Sid, s.phone as tel, se.s_tel2 as TelSup, c.rowid as Cid, c.firstname , c.lastname , c.phone_mobile as PartTel, c.phone as PartTelPro';
		$sql.= ' FROM  '.MAIN_DB_PREFIX.'societe as s LEFT JOIN  '.MAIN_DB_PREFIX.'societe_extrafields as se ON se.fk_object = s.rowid LEFT JOIN '.MAIN_DB_PREFIX.'socpeople as c ON c.fk_soc = s.rowid';
		$sql.= ' WHERE s.rowid = '.$this->id_client;
		
		dol_syslog(get_class($this)."::fetch_tel_tiers ");
		$resql=$this->db->query($sql);		

		if (!$resql)
			{
				dol_syslog(get_class($this)."::fetch_tel_tiers  - Erreur SQL ",  LOG_ERR);
				return -1;
			}
		
			$num = $this->db->num_rows($resql) ;
			if ($num == 0 )	 			{
				dol_syslog(get_class($this)."::fetch_tel_tiers  - Erreur Tiers non trouvé", LOG_ERR);
				return -1;
			}
			$obj = $this->db->fetch_object($resql);
			$tblligne = array();
			$tblligne['Nom'] = 'Principal';
			$tblligne['Tel'] = $obj->tel;
			$tblligne['flg_slct'] = 0;
			$tblligne['id'] = $obj->Sid;
			$table[0] = $tblligne ;		

			$tblligne = array();		
			$tblligne['Nom'] = 'Supplementaire';
			$tblligne['Tel'] =  $obj->TelSup;
			$tblligne['flg_slct'] = 0;
			$tblligne['id'] = $obj->Sid;
			$table[1] = $tblligne ;			

			$j=2;
			for ($i=0; $i<$num; $i++, $j++)
			{					
				if ($i>0)  $obj = $this->db->fetch_object($resql);	
				if ($i>0 or ($i==0 and !empty($obj->Cid)))
				{
					$tblligne = array();
					$tblligne['Nom'] = 'Contact - '.$obj->firstname.' '.$obj->lastname.' - mobile';
					$tblligne['Tel'] = $obj->PartTel;
					$tblligne['flg_slct'] = 0;
					$tblligne['id'] = $obj->Cid;
					$table[$j++] = $tblligne ;		
					$tblligne = array();
					$tblligne['Nom'] = 'Contact - '.$obj->firstname.' '.$obj->lastname.' - pro';
					$tblligne['Tel'] = $obj->PartTelPro;
					$tblligne['flg_slct'] = 0;
					$tblligne['id'] = $obj->Cid;
					$table[$j] = $tblligne ;			
				}
			}
			return($table);
	} //fetch_tel_tiers
		//			pour chaque ligne de $tabtel, inserer dans cglinscription_bull_det 

	function insert_tel_bull_det_all($table)
	{
		$error = 0;
		if ( !empty($table)) {
			foreach($table as $lig)
			{
				$ret = $this->insert_tel_bull_det($lig);
				if ($ret < 0) 		$error++;
			} // foreach
		}		
		if ($error == 0) return 1;
		else return -1;
		
	}//insert_tel_bull_det
	
	function insert_tel_bull_det($tablig)
	{
		global $user;
	
		if (empty($tablig['flg_slct'])) $tablig['flg_slct'] = 0;
		if (empty($tablig['id'])) $tablig['id'] = 0;
		
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cglinscription_bull_det ';
		$sql .= ' ( type, fk_bull, organisme, qte, fk_contact, PartTel ,fk_user , fk_activite , fk_agsessstag , fk_raisrem  ) ';
		$sql .= 'VALUES (';
		$sql .= '9, '.$this->id.', "'. $tablig['Nom'].'", ';
		if (empty($tablig['flg_slct'])) $sql .= ' 0 ,'; else  $sql .= $tablig['flg_slct'].', ';
		if (empty($tablig['id'])) $sql .= ' 0 ,'; else  $sql .= $tablig['id'].', "';
		$sql .= $tablig['Tel'].'",  '. $user->id.',  0,  0,  0)';		
		
		$this->db->begin();
		
		 dol_syslog(get_class($this)."::insert_tel_bull_det ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)	{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::insert_tel_bull_det Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}//insert_tel_bull_det

	
	function update_tel_bull_det($tablig)
	{		
		if (empty($tablig['flg_slct'])) $tablig['flg_slct'] = 0;
		
		$sql = 'UPDATE  '.MAIN_DB_PREFIX.'cglinscription_bull_det SET';
		$sql .=  ' PartTel = "'. $tablig['Tel'].'" ';
		if (empty($tablig['flg_slct'])) $sql .=  ', 0 '; else $sql .=  ', fk_contact = '. $tablig['flg_slct'];
		$sql .=  ' WHERE organisme = "'. $tablig['Nom'].'"';
		$sql .=  ' AND fk_contact = "'. $tablig['id'].'"';
			
		$this->db->begin();
		
		 dol_syslog(get_class($this)."::update_tel_bull_det ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)	{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::update_tel_bull_det Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}//update_tel_bull_det
	
	function update_tel_tiers_all($table)
	{
		$error = 0;
		if ( !empty($table)) {
			foreach ($table as $lig) {
				if (!empty($lig['id'])) 
				{
					$ret = $this->update_tel_tiers($lig);
					if ($ret < 0) 		$error++;
				}
			}//foreach
		}		
		if ($error == 0) return 1;
		else return -1;
	}//update_tel_tiers_all
	function NbStripeinBull($type)
	{
		if ( !empty($this->lines_stripe)) {
			foreach ($this->lines_stripe as $line)		{
				if ( $line->action != 'S' and $line->action != 'X')			{
					$nbStripe ++ ;
					// dans datedepose nous avons la date de paiement d'une demande stripe
					if (!empty($line->date_paiement)) $nbStripeEnc ++;
				}
			} // foreach
		}
		if ($type == 'total' ) return $nbStripe;
		if ($type == 'Encaisse' ) return $nbStripeEnc;
		if ($type == 'NonEncaisse' ) return $nbStripe - $nbStripeEnc;
	} //NbStripeinBull

	/*
	* Liste avec ; les email des payeurs Stripe du bulletin
	*
	*  @param type string	'total' pour toutes les demandes valides, 'Sup' pour les payeurs dont la demande a été supprimée, 'paye' pour les payeurs ayant réglé, 'att' pour les demandes encore en attente de réglement 
	*/
	function ListPayeurStripe($type) 
	{
		global $bull;
		$MailsPayeurSup = '';
		$MailsPayeurPaye = '';
		$MailsPayeur = '';
		$MailsPayeurAtt = '';
		$tbMailsPayeurSup = array();
		$tbMailsPayeurPaye = array();
		$tbMailsPayeur = array();
		$tbMailsPayeurAtt = array();
		if (!empty($bull->lines_stripe)){			
			foreach ($bull->lines_stripe as $wbulldet5) {
					if (  $wbulldet5->action == 'S' or $wbulldet5->action  == 'X' and !isset($tbMailsPayeurSup[$wbulldet5->mailpayeur])) {
						if (strlen($MailsPayeurSup) > 0) $MailsPayeurSup .=';';
						$MailsPayeurSup .= $wbulldet5->mailpayeur;	
						$tbMailsPayeurSup[$wbulldet5->mailpayeur] = 	$wbulldet5->mailpayeur;				
					}
					else	{
						if (!isset($tbMailsPayeur[$wbulldet5->mailpayeur])) {
							if (strlen($MailsPayeur) > 0) $MailsPayeur .=';';
							$MailsPayeur .= $wbulldet5->mailpayeur;
						$tbMailsPayeur[$wbulldet5->mailpayeur] = 	$wbulldet5->mailpayeur;	
						}
						if (!empty($wbulldet5->date_paiement)) {
							if (!isset($tbMailsPayeurPaye[$wbulldet5->mailpayeur])) {
								if (strlen($MailsPayeurPaye) > 0) $MailsPayeurPaye .=';';
								$MailsPayeurPaye .= $wbulldet5->mailpayeur;
								$tbMailsPayeurPaye[$wbulldet5->mailpayeur] = 	$wbulldet5->mailpayeur;	
							}							
						}
						elseif (empty($wbulldet5->date_paiement) and !isset($tbMailsPayeurAtt[$wbulldet5->mailpayeur])) {
							if (strlen($MailsPayeurAtt) > 0) $MailsPayeurAtt .=';';
							$MailsPayeurAtt .= $wbulldet5->mailpayeur;	
							$tbMailsPayeurAtt[$wbulldet5->mailpayeur] = 	$wbulldet5->mailpayeur;	
						}
					}	
			} // Foreach
		}
		if ($type == 'total') return $MailsPayeur;
		elseif ($type == 'paye') return $MailsPayeurPaye;
		elseif ($type == 'Att') return $MailsPayeurAtt;
		elseif ($type == 'Sup') return $MailsPayeurSup;
	} // ListPayeurStripe

} // fin Classe bulletin


/**
 *	\class      	BulletinLigne
 *	\brief      	Classe permettant la gestion des lignes de bulletin
 *					Gere des lignes de la table llx_cglinscription_bull_det
 */
class BulletinLigne
{

	var $db;
	var $error;

	var $oldline;
	var $type;		// Insc pour le traitement sépecifique à Inscription 4 saisons, Loc pour traitement spécifique aux locatinos
	//! From llx_cglinscription_bul
	var $id;
	var $type_enr;		// 0 pour les activités, 1 pour les paiements, 2 pour la remise fixe, 5 pour les demandes Stripe
	var $fk_bull; 	//! Id bulletin
	
	// Pour les enr de type 0 - activité
	var $qte;				// Quantity (example 2)
	var $pu_enf;      	// prix enfant du départ
	var $pu_grp;      	// prix groupe du départ
	var $pu_adlt;      	// prix adlt du départ
	var $pu;      	// P.U. HT (example 100) de l'activité 
	var $remise_percent;	// % de la remise ligne (example 20%)
	
	// Pour les enr de type 2 - remise fixe
	var $fk_remgen;		
	var $fl_type; // type de la remise
	var $mttremfixe;	// monant de la remsie en euros
	var $fk_remprod;	//id produit rde remise fixe pour la ventilation de la facture
	var $textnom;	// Libelle de la remise
	var $rangdb = 0;
	var $rangecran = 0;
	var $textremisegen; 
	var $taux_tva; 

	

	// Depuis llx_contact
	var $id_part;		// Id of participant concernée
	var $PartNom;
	var $PartPrenom;
	var $PartDateNaissance;
	var $PartAge;
	var $PartENF;
	var $PartMail;
	var $PartTel;
	var $PartCP;
	var $PartVille;
	var $PartAdresse;
	var $PartCiv;	
	var $PartTaille; 
	var $PartPoids; 
	var $PartDateInfo; 
	
	// Depuis llx_agefodd_session
	var $id_act;       // // Id of activite concernÃ©e
	var $activite_dated;       // Activite date Debut
	var $activite_lieu;       // Activite site de pratique
	var $id_site;				// Id du site de pratique
	var $url_loc_site;				// url maps de localistation du site
	var $activite_label;     // Activite label
	var $activite_nbmax;  	// Nb participant max Activite
	var $activite_nbinscrit;  	// Nb Inscrit Activite
	var $activite_nbpreinscrit;  	// Nb Preinscrit Activite
	var $activite_nbencrins; 	// Nb Participation pour cette activite sur ce bulletin non encore inscrit (action = A)
	var $activite_heured;
	var $activite_heuref;
	var $activite_rdv;
	var $act_moniteur_id;
	var $act_moniteur_nom;
	var $act_moniteur_prenom;
	var $act_moniteur_tel;
	var $act_moniteur_email;
	var $observation;
	var $type_TVA; // $langs->trans("TVACommissionnement") ==> 0%

	// Pour les enr de type 1 - paiements	
	var $id_mode_paiement;
	var $mode_paiement;		
	var $organisme;
	var $tireur;
	var $num_cheque;
	var $montant; 
	var $date_paiement;
	var $datec;
	var $pmt_neg;
	

	
	// location
	var $fk_service;
	var $refservice;
	var $service;
	var $materiel;
	var $fk_fournisseur;
	var $NomFourn;
	var $marque;
	var $refmat;
	var $identmat;
	var $fl_conflitIdentmat;		// Signale si c vélo a été loué plusieurs fois pour la même date
	var $taille;
	var $NomPrenom;
	var $dateretrait;
	var $lieuretrait;
	var $datedepose;
	var $lieudepose;
	var $duree;	
	var $qteret;
	var $lb_service;
	var $desc_service;
	var $PUjour; // prix journée 
	var $PUjoursup;// prix journée supplementaire ou demi-journee
	var $pmt_rappro;
	var $pmt_depose; //> 0 ==>  le paiement est dépose
	var $pmt_StripeAut;
	var $pmt_refbordereau;
	
	// remise fixevar 
	var $fk_raisrem;
	
	// Diffusion dans la base
	var $action; // A pour Ajout, M pour Modifier, S pour Supprimer, X pour Ne plus sÃ©lectionner
	var $id_ag_stagiaire;
	var $id_ag_session;
	var $fk_produit;
	var $fk_code_ventilation;  // Code de ventilation de l'activité/location
	var $product_type = 1;	// Type  1 = Service
	var $cmdrang ; // rang de la ligne du bulletin dans la commande
	var $fk_line_commande; // identifiant de la ligne de commande pour facturation
	var $fk_line_facture; // identifiant de la ligne de commande pour facturation
	var $fk_paiement; // identifiant de l'enregistrement paiement
	var $fk_banque; // identifiant de l'enregistrement d'insertion en banque
	var $fk_accountCGL; // identifiant de la banque correspondant au mode de paiement
	var $fk_facture; // facture accompte
	var $fk_ligneacompte; // facture accompte
	var $fk_agsessstag; // lien avec table llx_agefodd_session_stagiaire
	
	// pour edition	
	var $ficbull;
	var $rdv_lib;
	var $rdv2_lib; // Qui sert maintenant pour Notre Conseil
	
	// pour mail
	var $ficsite; // fichier de description du site
	
	
	// Réservation
	var $resa_activite;// provenant de lieuretrait dans la base
	var $resa_place; // provenant de  qteret dans la base
	/* 
	var $activite_heured;
	var $activite_heuref;
	var $observation;
	var $NomPrenom;
	var $qte;
	*/
	var $prix ; // provenant de lb_neg_pmt dans la base

	//
	var $LINE_ACT = 0;				// ligne d'activité ou matériel loué
	var $LINE_PMT = 1;				// Ligne de paiement
	var $LINE_BC = 2;				// ligne pour Bon cadeau et remise fixe
	var $LINE_RANDO = 3;
	var $LINE_ACC = 4;
	var $LINE_STRIPE = 5;

	
	
	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db		Database handler
	 */
	function __construct($db, $type='Insc')
	{
		$this->db = $db;
		$this->type = $type;
	}
	
	function rechercheRangSuiv ($type_enr)
	{
		global $bull;
		// recherche le prochain rang dans la base,du type d'enregistrement concerné
		
		$sql = 'SELECT MAX(rang) as maxrang FROM  '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' WHERE fk_bull = '.$bull->id.' and type = '.$type_enr;
		dol_syslog(get_class($this)."::rechercheRangSuiv ");
		$resql=$this->db->query($sql);
        if ($resql)
        {	
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
				$rang = $obj->maxrang + 1;
			}
			else if ($type_enr == $this->LINE_ACT ) $rang = 1; else $rang = 1000;
		}
		else
		{
			dol_syslog(get_class($this)."::rechercheRangSuiv ");
			return -1;
		}	
		return $rang;
	} // rechercheRangSuiv
	/**
	 *	Insert line in database
	 *
	 *	@param      int		$notrigger		1 no triggers
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function insertPaiement($notrigger=0)
	{
		global $langs,$user,$conf, $id_contrat, $bull;

		$error=0;
		$now = $this->db->idate(dol_now('tzuser'));

		dol_syslog(get_class($this)."::insert rang=".$this->rangdb, LOG_DEBUG);

		if (empty($this->montant)) $this->montant=0;

		$this->db->begin();
		$w = new CglFonctionCommune ($this->db);
		$datepai = $w->transfDateMysql($this->date_paiement);
		if (empty($datepai))  $datepai = $this->db->idate($this->date_paiement);
		unset ($w);
		// recherche du rang du paiement 
		$rang = $this->rechercheRangSuiv (1);
		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' (fk_bull, fk_mode_pmt, organisme, tireur, num_cheque, pt, datec, type, datepaiement, action, rang, fk_banque, fk_facture, lb_pmt_neg';
		if (!empty($this->fk_paiement)) $sql .= ', fk_paiement ';
		$sql .= ', fk_user, fk_activite,  fk_agsessstag, fk_rdv, fk_raisrem)';
	
		$sql.= " VALUES (".$this->fk_bull.",";
		$sql.= " ".$this->id_mode_paiement.",";
		$sql.= ' "'.$this->organisme.'",';
		$sql.= ' "'.$this->tireur.'",';
		$sql.= " '".$this->num_cheque."',";
		$sql.= " '".$this->montant."' ";
		$sql.= ", '".$now."', 1, '".$datepai."' ";
		$sql.= ', "A" ,"'.$rang.'",';
		if (empty($this->fk_banque)) $sql .= " 0 ,"; else $sql.= " '".$this->fk_banque."', ";
		if (empty($this->fk_facture)) $sql .= " 0 ,"; else $sql.= " '".$this->fk_facture."', ";
		if (empty($this->pmt_neg)) $sql .= " 0 "; else $sql.= " '".$this->pmt_neg."' ";
		if (!empty($this->fk_paiement)) $sql .= " ,'".$this->fk_paiement."' ";
		$sql.= ", '".$user->id."' ";
		$sql.= ", 0, 0 , 1 , 0 ";
		$sql.= ')';
		dol_syslog(get_class($this)."::insertPaiement ");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'cglinscription_bull_det');
			// suppression appel triggers
			$this->db->commit();
			$bull->line[] = $this;	
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();		
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	} /* insertPaiement */	
	function update_champs($champ1, $val1, $champ2='', $val2='', $champ3='', $val3='', $champ4='', $val4='' )
	{
    	global $conf, $langs, $user, $bull;
		$error=0;					
		// parametres location
	    if (isset($val1))	 	$val1		=trim($val1);
	    if (isset($val2))	 	$val2		=trim($val2);
	    if (isset($val3))	 	$val3		=trim($val3);
	    if (isset($val4))	 	$val4		=trim($val4);
		// Put here code to add control on parameters values
		$i=0;

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_det SET ";
		 $sql.= $champ1.'= "'.$val1.'" ';
		if (!empty($champ2)) $sql.= ' , '.$champ2.'= "'.$val2.'" ';
		if (!empty($champ3) ) $sql.=  ' , '.$champ3.'= "'.$val3.'" ';
		if (!empty($champ4) ) $sql.=  ' , '.$champ4.'= "'.$val4.'"';
		$sql.= "  Where rowid =  '".$this->id."'";
		$this->db->begin();
		// liste champ mis à jours
		if (!empty($champ1) ) $lb = "champs:".$champ1;
		if (!empty($champ2)) $lb .= "---".$champ2;
		if (!empty($champ3) ) $lb .= "---".$champ3;
		if (!empty($champ4) ) $lb .= "---".$champ4;
		
	   	dol_syslog(get_class($this)."::update_".$lb." ", LOG_DEBUG);
        $resql=$this->db->query($sql);
		
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}		
	} // update_champs
	function update_champs_filtre($sqlwhere, $champ1, $val1, $champ2='', $val2='', $champ3='', $val3='', $champ4='', $val4='' )
	{
    	global $conf, $langs, $user, $bull;
		$error=0;	
		if (empty( $sqlwhere ))	 return -3;
			
		// parametres location
	    if (isset($val1))	 	$val1		=trim($val1);
	    if (isset($val2))	 	$val2		=trim($val2);
	    if (isset($val3))	 	$val3		=trim($val3);
	    if (isset($val4))	 	$val4		=trim($val4);
		// Put here code to add control on parameters values
		$i=0;

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_det SET  ";
		 $sql.= $champ1.'= "'.$val1.'" ';
		if (!empty($champ2) ) $sql.= ', '.$champ2.'= "'.$val2.'" ';
		if (!empty($champ3) ) $sql.=  ' , '.$champ3.'= "'.$val3.'" ';
		if (!empty($champ4) ) $sql.=  ' , '.$champ4.'= "'.$val4.'"';
		$sql.= "  Where ".$sqlwhere;
		$this->db->begin();
		// liste champ mis à jours
		if (!empty($champ1) ) $lb = "champs:".$champ1;
		if (!empty($champ2) ) $lb .= "---".$champ2;
		if (!empty($champ3) ) $lb .= "---".$champ3;
		if (!empty($champ4) ) $lb .= "---".$champ4;
		if (!empty($champwhere)) $lb .= "--- pour ".$sqlwhere;

		
	   	dol_syslog(get_class($this)."::update_champs_filtre".$lb." ", LOG_DEBUG);
        $resql=$this->db->query($sql);
		
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::update_champs_filtre ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}		
	} // update_champs_filtre
	function insertReservation($notrigger=0)
	{
		global $langs,$user,$conf, $id_contrat, $bull;
		$error=0;


		$line = new BulletinLigne($this->db);
		$this->db->begin();
		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' (fk_bull,   NomPrenom, lieudepose, observation, lieuretrait, qteret, dateretrait, datedepose, action, rang, type)';
		
		$sql.= " VALUES (".$this->fk_bull.",";
		$sql.= ' "'.$this->NomPrenom.'",';
		$sql.= ' "'.$this->prix.'",';
		$sql.= " '".$this->observation."',";
		$sql.= " '".$this->resa_activite."', ";
		if (empty($this->resa_place)) $sql.= " 0, "; else $sql.= " '".$this->resa_place."', ";
		$sql.= " '".$this->activite_heured."', ";
		$sql.= " '".$this->activite_heuref."' ";
		$sql.= ', "A" ,"'.$rang.'"';
		$sql.= ',0)';
		dol_syslog(get_class($this)."::insertRéservation ");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'cglinscription_bull_det');
			// suppression appel triggers			
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	
			$this->db->commit();
			$bull->line[] = $this;			
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::insertRéservation Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	
	}//insertReservation
	function updateReservation()
	{
		global $langs,$user,$conf, $id_contrat, $bull;

		dol_syslog(get_class($this)."::insert rang=".$this->rangdb, LOG_DEBUG);
		$this->db->begin();
		// Insertion dans base de la ligne
		$sql = 'UPDATE  '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' SET fk_bull ='.$this->fk_bull.",";
		$sql.= ' NomPrenom = "'.$this->NomPrenom.'",';
		$sql.= ' lieudepose="'.$this->prix.'",';
		$sql.= " observation ='".$this->observation."',";
		$sql.= " lieuretrait='".$this->resa_activite."', ";
		if (!empty($this->resa_place)) $sql.= " qteret='".$this->resa_place."', ";
		$sql.= " dateretrait='".$this->activite_heured."', ";
		$sql.= " datedepose='".$this->activite_heuref."', ";
		$sql.= ' action="M" ';
		$sql .= ' where rowid = '.$this->id;
		dol_syslog(get_class($this)."::updateReservation ");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'cglinscription_bull_det');
			// suppression appel triggers
			$this->db->commit();	
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::updateReservation Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	} //updateReservation
	/**
	 *	Update line into database
	 *
	 *	@param		User	$user		User object
	 *	@param		int		$notrigger	Disable triggers
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function updatePaiement($user='',$notrigger=0)
	{
		global $user,$langs,$conf, $bull;
		$error=0;
		$this->db->begin();
		$w = new CglFonctionCommune ($this->db);
		$datepai = $w->transfDateFormatIdate($this->date_paiement);
		//$datepai = $w->transfDateMysql($this->date_paiement);
		// $datepai = $this->date_paiement;
		//$datepai = dol_stringtotime($this->date_paiement,1);
		unset ($w);
		
        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_det SET ";
		$sql.= 'fk_mode_pmt =';
		if (empty($this->id_mode_paiement))  $sql .= '0 '; else $sql .= $this->id_mode_paiement;
        $sql.=' , organisme="'.$this->organisme.'"';
 		$sql.=' , tireur="'.$this->tireur.'"';
 		$sql.=" , num_cheque='".$this->num_cheque	."'";
 		$sql.=" , pt=";
		if (empty($this->montant))  $sql .= '0 '; else $sql .= $this->montant;
 		$sql.=" , datepaiement='".$datepai."'";	
 		$sql.=" , fk_banque=";
		if (empty($this->fk_banque))  $sql .= '0 '; else $sql .= $this->fk_banque;
 		$sql.=" , fk_facture=";
		if (empty($this->fk_facture))  $sql .= '0 '; else $sql .= $this->fk_facture;
 		$sql.=" , lb_pmt_neg='".$this->pmt_neg."'";
		
 		$sql.=" , action = 'M' ";
		$sql.=" where rowid = ".$this->id;
		 dol_syslog(get_class($this)."::update ", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}/* updatePaiement */
	function update_retour($user='',$notrigger=0)
	{
		global $user,$langs,$conf;

		$error=0;
		$this->db->begin();
		$w = new CglFonctionCommune ($this->db);
		$datepai = $w->transfDateFormatIdate($this->date_paiement);
		//$datepai = $w->transfDateMysql($this->date_paiement);
		// $datepai = $this->date_paiement;
		//$datepai = dol_stringtotime($this->date_paiement,1);
		unset ($w);
		$ret = $this->update_champs('qteret', $this->qteret);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
			return -2;
		}
	}/* update_retour */
	
	function insertParticipation($user='',$notrigger=0)
	{
		
		global  $id_bull, $bull;
		$error=0;
		$now = $this->db->idate(dol_now('tzuser'));
		dol_syslog(get_class($this)."::insertParticipation rang=".$this->rangdb, LOG_DEBUG);
		$rang = $this->rechercheRangSuiv(0);
		$this->db->begin();
		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		//$sql.= ' (fk_bull, fk_activite, fk_contact,fk_produit, pu, rem, qte,observation,tms, type, datec, action, rang, age, fk_rdv, PartTel)';
		$sql.= ' (fk_bull, fk_activite, NomPrenom,fk_produit,  pu, rem, qte,observation,tms, type, datec, action, rang, age, fk_rdv, PartTel, ';
		$sql .= 'fk_contact, taille, fk_code_ventilation, poids, duree, fk_user, fk_agsessstag, fk_raisrem)';
		
		$sql.= " VALUES (".$id_bull.",";
		$sql.= " '".$this->id_act."',";
		$sql.= ' "'.$this->NomPrenom.'",';
		if (empty($this->fk_produit)) $sql.= " 0,"; else $sql.= " '".$this->fk_produit."',";
		if (empty($this->pu)) $sql.= " 0,"; else $sql.= " '".$this->pu."',";
		if (empty($this->remise_percent)) $sql.= " 0,"; else $sql.= " ".$this->remise_percent.",";
		if (empty($this->qte)) $sql.= " 1,"; else $sql.= " ".$this->qte.",";
		if (empty($this->observation)) $sql.= " '',"; else $sql.= ' "'.$this->observation.'",';
		$sql.= ' "'.$now.'", 0, "'.$now.'"	, "A" ,'.$rang.',';
		if (empty($this->PartAge)) $sql.= "0, " ; else $sql.= "'".$this->PartAge."',";
		$sql.= "'".$this->activite_rdv."',";
		$sql.= "'".$this->PartTel."',";
		$sql.= "'".$bull->id_contactTiers."', ";
		$sql.= "'".$this->PartTaille."',";
		$w = New CglCommunLocInsc($this->db);
		// on ne stocke pas dans cglinscription_bull_det, le code de ventilation, qui est lié au départ.
		//$ventilation = $w->RechVentilationbyService(	$this->id_act, 	$bull->type);
		//$sql .= " '".$ventilation ."' ,";
		$sql .= "0, ";
		unset ($w);
		$sql.= "'".$this->PartPoids."'  , ";
		if (empty($this->duree)) $sql.= " 0,"; else $sql.= "'".$this->duree."' ,";
		$sql.= "'".$user->id."' ";
		$sql .= ", 0, 0 ";
		$sql .=")";
		dol_syslog(get_class($this)."::insertParticipation ");
		$resql=$this->db->query($sql);
/*
		$w = New CglCommunLocInsc($this->db);
		$ventilation = $w->RechVentilationbyService($this->fk_service, $bull->type);
		$sql.=' , fk_code_ventilation = '.$ventilation ;
		unset($w);
		*/
		if ($resql)		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'cglinscription_bull_det');
			// suppression appel triggers
			
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();				
			$this->db->commit();
			
			return $this->id;
		}
		else		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::insertParticipation Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}/* insertParticipation*/

	function insertRemiseFixe($user='',$notrigger=0)
	{
		global  $bull,$user;
			
		$error=0;
		$now = $this->db->idate(dol_now('tzuser'));
		dol_syslog(get_class($this)."::insertRemiseFixe rang=".$this->rangdb, LOG_DEBUG);

		$this->db->begin();
		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' (fk_bull,   pt, reslibelle, fk_raisrem, tms, type, datec, action, rang, fk_user, fk_activite,fk_agsessstag , fk_rdv  ) ';
		
		$sql.= " VALUES (".$this->fk_bull.",";
		if (empty($this->mttremfixe)) $sql.= " 0, ";  else $sql.= " '".$this->mttremfixe."',";
		$sql.= ' "'.$this->textremisegen.'",';
		if (empty($this->fk_remgen)) $sql.= " 0, "; else $sql.= " '".$this->fk_remgen."',";
		$sql.= ' "'.$now.'", 2, "'.$now.'"	, "A" ,'.$this->rang;
		$sql.= ', '.$user->id.',0 , 0 ,1 ';
		$sql .=" )";
		dol_syslog(get_class($this)."::insertRemiseFixe ");
		$resql=$this->db->query($sql);
		if ($resql)		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'cglinscription_bull_det');
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	
			// suppression appel triggers			
			$this->db->commit();
			
			return $this->id;
		}
		else		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::insertRemiseFixe Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}/* insertRemiseFixe*/

	function delRemiseFixe() 
	{
			$error=0;
		dol_syslog(get_class($this)."::delRemiseFixe rang=".$this->rangdb, LOG_DEBUG);

		$this->db->begin();
		// Insertion dans base de la ligne
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' WHERE rowid = "'.$this->id.'"';
		
		$resql=$this->db->query($sql);
		if (!$resql)		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::delRemiseFixe Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
		if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	

		$this->db->commit();
		return 0;
	}//delRemiseFixe
	
	function select_activite_participation($selected='',$htmlname='ActRemGen',$htmloption='',$maxlength=0)
	{
        global $conf,$langs, $bull;
        $langs->load("dict");

        $out='';
        $TabActPart=array();
        $label=array();
		$sql = 'SELECT 0 as rowid, "Tous les departs" as libelle ';
		$sql .= " UNION ";
        $sql .= "SELECT distinct s.rowid as rowid , intitule_custo as libelle ";
        $sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd, ".MAIN_DB_PREFIX."agefodd_session as s";
        $sql.= " WHERE bd.fk_activite = s.rowid and bd.action not in ('X','S') ";
		$sql .= " AND bd.fk_bull ='".$bull->id."'";

        dol_syslog(get_class($this)."::select_activite_participation ");
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
                    $TabActPart[$i]['rowid'] 	= $obj->rowid;
                    $TabActPart[$i]['label']	= $obj->libelle;
                    $label[$i] = dol_string_unaccent($TabActPart[$i]['label']);
                    $i++;
                }
				if ( !empty($TabActPart)) {
					foreach ($TabActPart as $row)
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
	} // select_activite_participation
	function select_service($selected='',$htmlname='servremgen',$htmloption='',$maxlength=0, $tous=true)
	{
        global $conf,$langs, $bull;
        $langs->load("dict");

        $out='';
        $TabActPart=array();
        $label=array();
		$sql = 'SELECT 0 as rowid, "Tous les services" as libelle ';
		
		if ($tous == true) 
		{
			$sql .= " UNION ";
			$sql .= "SELECT distinct p.rowid as rowid , label as libelle ";
			$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd, ".MAIN_DB_PREFIX."product as p";
			$sql.= " WHERE bd.fk_activite = p.rowid and bd.action not in ('X','S') ";
			$sql .= " AND bd.fk_bull ='".$bull->id."'";
		
		}
        dol_syslog(get_class($this)."::select_service ");
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" '.$htmloption.'>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
				if ($tous == true ) $out.= '<option value="">';
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $TabActPart[$i]['rowid'] 	= $obj->rowid;
                    $TabActPart[$i]['label']	= $obj->libelle;
                    $label[$i] = dol_string_unaccent($TabActPart[$i]['label']);
                    $i++;
                }
				if ( !empty($TabActPart)) {
					foreach ($TabActPart as $row)
					{
						if (($tous == true && $selected && $selected != '-1' && ($selected == $row['rowid'] ))  or ($tous == false && i==0))
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
					}//  foreach
				}
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
	} // select_service
/*	
	function updateVentilation()
	{
		//code ventilation non sotcké dans bulletin det car lié au départ ou à la location
		//$this->update_champs("fk_code_ventilation",$this->fk_code_ventilation);
	}//updateVentilation
*/	
	function updateParticipation($user='',$notrigger=0)
	{	
		global $bull;
		if (empty($this->action) or $this->action == '') $action = 'M'; else $action  = $this->action;
		$error=0;
		$this->db->begin();
        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_det SET ";
		if (!empty($this->id_act)) $sql		.= 'fk_activite ='. $this->id_act;
		if (!empty($this->id_part)) $sql	.= ', fk_contact ='. $this->id_part;
		$sql								.= ', NomPrenom ="'. $this->NomPrenom.'"';
		if (!empty($this->fk_produit)) $sql	.= ', fk_produit ='. $this->fk_produit;
		if (empty($this->pu)) $sql .= ', pu = 0 '; else $sql .= ", pu ='". $this->pu."'";
		if (empty($this->remise_percent))   $sql.= ", rem =0"; else $sql.= ", rem ='". $this->remise_percent."'";
		$sql								.= ', observation ="'. $this->observation.'"';
		if (empty($this->qte))$sql .= ', qte = 0 '; else  $sql	.= ', qte ='. $this->qte;
 		$sql								.=",  action = '".$action."'";
 		$sql								.=",  PartTel = '".$this->PartTel."'";
 		if (empty($this->PartAge)) $sql .= ', age = 0 '; else  $sql	.=",  age = '".$this->PartAge."'";

 		$sql								.=",  taille = '".$this->PartTaille."'";
 		$sql								.=",  poids = '".$this->PartPoids."'";
 		if (empty($this->duree)) $sql .= ', duree = 0 '; else  $sql		.=",  duree = '".$this->duree."'";

		
 		if (empty($this->qteret))  $sql .= ', qteret = 0 '; else  $sql.=" , qteret = '".$this->qteret."'";
		$sql.=" where rowid = ".$this->id;
		 dol_syslog(get_class($this)."::updateParticipation ", LOG_DEBUG);
		$resql=$this->db->query($sql);

		if ($resql)
		{
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::updateParticipation Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}/* updateParticipation */
	function 	RechActPart_1()
	{
		$sql='';
		$sql="SELECT distinct bd.fk_agsessstag  ";
		$sql.=" FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd  ";
		$sql.=" WHERE action in ('S','X') "; 
		$sql.=" AND  fk_activite ='".$this->id_act."' "; 
		$sql.=" AND  fk_contact = '".$this->id_part."'"; 
		$sql.=" AND fk_bull = '".$this->fk_bull."'"; 
		dol_syslog(get_class($this).'::RechActPart sql='.$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) 
		{	
			$objp = $this->db->fetch_object($result);
			$fk_agsessstag = $objp->fk_agsessstag;
			$this->db->free($result);
			unset ($w);
			return $fk_agsessstag;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::RechActPart '.$this->error,LOG_ERR);
			unset ($w);
			return -1;
		}		
	} // RechActPart
			
	function AjoutFkDolibarr($valaction)
	{
		$error = 0;
			// Mise a jour ligne en base
			if ($this->action == 'A' or $this->action == 'M') $val = '';
			elseif ($this->action == 'S') $val ='X' ;
			else $val =$valaction;
		if (empty($this->fk_line_commande))  $this->fk_line_commande = 0;
		if (empty($this->fk_line_facture))  $this->fk_line_facture = 0;
		if (empty($this->fk_banque))  $this->fk_banque = 0;
		$ret = $this->update_champs( 'fk_linecmd', $this->fk_line_commande, 'fk_linefct', $this->fk_line_facture, 'fk_banque', $this->fk_banque);	
		if (empty($this->fk_agsessstag))  $this->fk_agsessstag = 0;
		if (empty($this->fk_paiement))  $this->fk_paiement = 0;
		$ret = $this->update_champs( 'fk_agsessstag', $this->fk_agsessstag, 'fk_paiement', $this->fk_paiement);	

		if ( $ret<0) { $error++; dol_syslog(get_class($this)."::AjoutFkDolibarr ".$this->db->lasterror(), LOG_ERR); }	
			return ($error);
		}//	AjoutFkDolibarr
		
	function update_rdv($Rdv)	
	{
		global $user,$langs,$conf;
		global $id_contratdet,  $PaimtMode, $PaimtOrg, $PaimtNomTireur, $PaimtCheque, $PaimtMtt, $PartAge;

		$error=0;		
		$ret = $this->update_champs( 'fk_rdv', $Rdv);	

		if ($ret)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::update rdv Error ".$this->error, LOG_ERR);
			return -2;
		}
	}//update_rdv
	function CherchePu()
	{
	/*
		$pu=0;
	
		if ($this->pu == 0 or empty($this->pu))	
		{
				if (empty($this->PartENF )) $this->pu =$this->pu_adlt;
				else
					if ($this->PartENF == 'Enfant') $this->pu = $this->pu_enf;
					else 	$this->pu = $this->pu_adlt;	
		}
		return $pu;
		*/
		return($this->pu);
	} /* CherchePu */
	/**
	 * 	Supprime une participation
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */

	
	/**
	 * 	Pointe  une participation comme supprimée
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function updateaction($val)
	{
	global $user,$langs,$conf;

		$error=0;
		$this->db->begin();
		$ret = $this->update_champs( 'action', $val);	


		if ($ret > 0)
		{
			$this->action = $val;			
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::updateaction Error ".$this->error, LOG_ERR);
			return -2;
		}
	}//updateaction
	
	
	/**
	 * 	Sur bulletin non intégré dans Dolibarr
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function delete()
	{
		global $conf,$langs,$user, $bull;

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM  ".MAIN_DB_PREFIX."cglinscription_bull_det  WHERE rowid = ".$this->id;
		dol_syslog(get_class($this)."::delete ", LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			$this->action = 'X';
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error().get_class($this)."::delete Error ".$this->error;
			dol_syslog(get_class($this)."::delete Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}//delete
	function rechercheIdCmdFactDet($table, $champ, $bullid)
	{
		global $objcmd, $bull;
		
				
		$id = null;
		$sql = 'SELECT rowid FROM  '.MAIN_DB_PREFIX.$table;
		$sql.= ' WHERE '.$champ.'= '.$bullid.' and rang = '.$this->rangdb;
		dol_syslog(get_class($this)."::rechercheIdCmdFactDet ");
		$resql=$this->db->query($sql);		
        if ($resql)
        {	
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
				$id = $obj->rowid ;
			}
		}
		else
		{
			dol_syslog(get_class($this)."::rechercheIdCmdFactDet ");
			return -1;
		}	
		return $id;

		
	} //rechercheIdCmdFactDet
	
	/*
	* Calcul le Prix total d'une participation
	*	Si type_session == 2  ==> pu*qte*(100-rem)/100 // individuel 
	*   Si type_session == 1  ==> pu*(100-rem) // groupe constitué 
	*/
	function calulPtAct($type_session_cgl,$pu,$qte,$rem)
	{
// CCA - on doit reprendre le calcul ddu total en fonction d'une remise fixe ou variable	
		$tqte= intval($qte);
		if ($type_session_cgl == 2)
		{	
			return $pu*intval($qte)*(100-(int)$rem)/100;
		}
		else 
		{
			return $pu*(100-(int)$rem)/100;
		}
	}//calulPtAct
	
	function MajLineRem($RaisRemGen, $mttremisegen, $textremisegen)
	{
		global $bull;
		$error=0;
		
		$ret = $this->update_champs( 'fk_raisrem', $RaisRemGen, 'rem', $mttremisegen, 'reslibelle', $textremisegen, 'action',  'M');

		if ($ret)
		{
			$this->remise_percent = $mttremisegen;
			$this->fk_remgen = $RaisRemGen;
			$this->textremisegen = $textremisegen;
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::update MajLineRem Error ".$this->error, LOG_ERR);
			return -2;
		}		
	} //MajLineRem
	
	function IsParticipationcomplete($id_bulldet,$flpu = true)
	{
		global $bull;
		
		if ($flpu== true)
			if ($bull->type_session_cgl == 2 and  $line->pu == 0) return(-2);
		if (empty($line->id_act) or $line->id_act == 0 ) return(0);
		//if (empty($line->id_part) or $line->id_part == 0) return(0);
		return(1);
	}//IsParticipationcomplete
	
	function insertLocMat($user='',$notrigger=0)
	{
		global $langs,$user,$conf, $id_contrat, $bull;
		$error=0;

		$rang = $this->rechercheRangSuiv(0);
		
		dol_syslog(get_class($this)."::insertLocMat rang=".$this->rangdb, LOG_DEBUG);
		
		// test sur date dépose/retrait non renseignées
		if (empty($this->datedepose) or empty($this->dateretrait)) {
			setEventMessage ('DateLONonRenseigne', 'errors');
			return -1;
		}
		
		if (empty($this->fk_service)) $this->fk_service = 0;
		$this->db->begin();
		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' (fk_bull,  fk_activite, fk_produit, fk_fournisseur, pu, rem, qte,observation,tms, type, datec, action, rang, materiel, marque, refmat, taille, ';
		$sql .= ' NomPrenom, dateretrait,datedepose, duree, lieuretrait, fk_code_ventilation, lieudepose, fk_user, fk_agsessstag , fk_raisrem)';
		
		$sql.= " VALUES (".$id_contrat.",";
		$sql.= " '".$this->fk_service."',";
		$sql.= " '".$this->fk_service."',";
		if (empty($this->fk_fournisseur)) $sql.= " 0,"; else $sql.= " '".$this->fk_fournisseur."',";
		if (empty($this->pu)) $sql.= " 0,"; else $sql.= " '".$this->pu."',";
		if (empty($this->remise_percent)) $sql.= " 0,"; else $sql.= " ".$this->remise_percent.",";
		if (empty($this->qte)) $sql.= " 1,"; else $sql.= " ".$this->qte.",";
		if (empty($this->observation)) $sql.= " '',"; else $sql.= ' "'.$this->observation.'",';
		$sql.= ' now(), 0, now()	, "A" ,"'.$rang.'","'.$this->materiel.'","'.$this->marque.'",';
		$sql.= '"'.$this->identmat.'", "'.$this->PartTaille.'" , "'.$this->NomPrenom.'" , ';
		if (empty($this->dateretrait)) $sql .= "'".dol_now('tzuser')."',";
//		else	$sql .= "'".$this->dateretrait->format("Y-m-d H:i:00")."',";
		else	$sql .= "'".$this->dateretrait."',";
		if (empty($this->datedepose)) $sql .= "'".dol_now('tzuser')."',";
//		else	$sql .= "'".$this->datedepose->format("Y-m-d H:i:00")."',";
		else	$sql .= "'".$this->datedepose."',";
		$sql .= " '".price2num($this->duree)."' ,";
		$sql .= ' "'.$this->lieuretrait.'" ,';
		$w = New CglCommunLocInsc($this->db);
		//code ventilation non sotcké dans bulletin det car lié au départ ou à la location
		//$ventilation = $w->RechVentilationbyService(	$this->fk_service, 	$bull->type);
		//$sql .= " '".$ventilation ."' ,";
		$sql .= " 0 ,";
		unset ($w);
		$sql .= ' "'.$this->lieudepose.'" , ';
		$sql .= ' "'.$user->id.'" ';
		$sql .=", 0, 0)";
		dol_syslog(get_class($this)."::insertLocMat ");
		$resql=$this->db->query($sql);

		if ($resql)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'cglinscription_bull_det');
			// suppression appel triggers
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	
			$this->db->commit();
			return $this->id;

		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::insertLocMat Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}/* insertLocMat*/
	
	function updateLocMat($user='',$notrigger=0)
	{
		global $user,$langs,$conf, $bull;
		global $id_contratdet,  $PaimtMode, $PaimtOrg, $PaimtNomTireur, $PaimtCheque, $PaimtMtt, $PartAge;
				
		if (empty($this->action) or $this->action == '') $action = 'M'; else $action  = $this->action;
		$error=0;
		$this->db->begin();
        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_det SET ";
		$sql.= " fk_activite ='". $this->fk_service."'";
		$sql.= ", fk_produit ='". $this->fk_service."'";
		if (!empty($this->fk_fournisseur) ) $sql.= ", fk_fournisseur ='". $this->fk_fournisseur."'";	else 	$sql.= ", fk_fournisseur = 0";
		if (empty($this->pu)) $sql.= ", pu = 0 "; else $sql.= ", pu ='". $this->pu."' "; 
		if (!empty($this->remise_percent) )  $sql.= ", rem ='". $this->remise_percent."' ";	else 	$sql.= ", rem = 0 ";
		$sql.= ', observation ="'. $this->observation.'"';
		$sql.= ', qte ='. $this->qte;
		if (!empty($this->qteret) ) $sql.= ', qteret ='. $this->qteret;	else 	$sql.= ", qteret = 0";
 		$sql.=" , action = '".$action."'";
 		$sql.=' , materiel = "'.$this->materiel.'"';
 		$sql.=' , marque = "'.$this->marque.'"';
 		$sql.=' , refmat = "'.$this->identmat.'"';
 		$sql.=" , taille = '".$this->PartTaille."'";
 		$sql.=' , lieudepose = "'.$this->lieudepose.'"';
 		$sql.=' , lieuretrait = "'.$this->lieuretrait.'"';
 		$sql.=' , NomPrenom = "'.$this->NomPrenom.'"';
		$w = New CglCommunLocInsc($this->db);
		$ventilation = $w->RechVentilationbyService($this->fk_service, $bull->type);
		// Obsolete car code ventialtion non stocké dans bulleint_det, car lié au départ ou à la location
		//$sql.=' , fk_code_ventilation = '.$ventilation ;
		unset($w);
		$sql.=" , dateretrait = ";		
		if (empty($this->dateretrait)) $sql .= "'',";
		else	$sql .= "'".$this->dateretrait."', ";
		$sql.=" datedepose = ";
		if (empty($this->datedepose)) $sql .= "'',";
		else	$sql .= "'".$this->datedepose."',";			
 		$sql.=" duree = '".price2num($this->duree)."'";
		$sql.=" where rowid = ".$this->id;
		
		 dol_syslog(get_class($this)."::updateLocMat ", LOG_DEBUG); 
		$resql=$this->db->query($sql);
		
		if ($resql)
		{
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}/* updateLocMat */
	function MajPaimtNeg($id)
	{
		global $PaimtNeg, $bull, $langs;
		
		if (empty($PaimtNeg)) $PaimtNeg = $langs->trans("NonRensg");
		$sqlwhere = "  type = 1 ";
		$sqlwhere .= " AND fk_bull = ".$bull->id; 
		$sqlwhere .= " and rowid = '".$id."'";
		$sqlwhere .= " AND pt < 0 ";

		$ret = $this->update_champs_filtre($sqlwhere,  'lb_pmt_neg', $PaimtNeg);
		if ($ret)
		{
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::paiement negatif Error ".$this->error, LOG_ERR);
			return -2;
		}		
	} //MajPaimtNeg
	function isMoniteur( )
	{
		if ( !empty($this->id_act) and !empty($this->act_moniteur_nom ))
			return true;
		return false;
	} //isMoniteur
	
	
} // fin de classe BulletinLigne


class BulletinDemandeStripe extends CommonObject
{
	

	public $element='cglinscription';
	public $table_element='cglinscription_bull_det';
	public $fk_element = 'fk_bull';
	
	var $db;							//!< To store db handler
	
	var $id;
	var $fk_bull;// BU/LO de rettachement
	var $montant;
	var $Nompayeur;
	var $mailpayeur;
	var $smspayeur;
	var $fk_acompte;
	var $RefAcompte; 
	var $ModelMail;
	var $dateenvoi;
	var $date_paiement;
	//var $fk_bullpaiement;	// BU/LO de rettachement
	var $dateDerniereRelance;
	var $nbRelance;
	var $derRelMailSms;
	var $fk_bulldet; // Id de la ligne bulldet 1 correspondante
	var $fk_bank; // Id  ecriture bancaire
	var $fk_soc_rem_execpt;
	var $fk_paiement;		// Paiement de l'acompte
	var $action;
	var $user;	
	var $stripeUrl;
	var $libelleCarteStripe;
	
	var $LINE_STRIPE;
	
	function __construct($db)
	{
		$this->db = $db;
		$this->LINE_STRIPE = 5;
	} //__construct
	/**
	 * 	Sur bulletin non intégré dans Dolibarr
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function delete()
	{
		global $conf,$langs,$user, $bull;

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM  ".MAIN_DB_PREFIX."cglinscription_bull_det  WHERE rowid = ".$this->id;
		dol_syslog(get_class($this)."::delete ", LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			$this->action = 'X';
			if ($bull->regle < $bull->BULL_ARCHIVE and !empty($bull->dt_facture)) $bull->update_tms();	
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error().get_class($this)."::delete Error ".$this->error;
			dol_syslog(get_class($this)."::delete Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}//delete
	
	/* recherche par Id, acompte id ou ref
	* retour : une seule ligne 
	*/
	function fetchDemandeStripe ( $id_det ='', $fk_acompte='', $ref_acompte ='') 
	{
		
		$sql = "SELECT distinct bd.rowid as id, bd.NomPrenom as Nompayeur, bd.pt as montant, bd.lieudepose as mailpayeur,  bd.tireur as smspayeur, ";
		$sql .= "bd.fk_facture as fk_acompte, fac.ref as RefAcompte, bd.datepaiement as date_paiement, bd.fk_activite   as ModelMail, bd.fk_bull  , ";
		$sql .= "bd.dateretrait as datederniereRelance, bd.datedepose as dateenvoi, bd.qteret as nbRelance, bd.fk_activite   as ModelMail, bd.action, ";
		$sql .= " bd.reslibelle  as stripeUrl, bd.ficbull as libelleCarteStripe , bd.fk_agsessstag as fk_soc_rem_execpt, bd.fk_banque as fk_bank, bd.fk_raisrem as fk_bulldet, ";
		$sql .= "bd.fk_paiement , bd.marque as derRelMailSms ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd LEFT JOIN ".MAIN_DB_PREFIX."facture as fac on fac.rowid = bd.fk_facture"  ;
		$sql.= " WHERE bd.type = ".$this->LINE_STRIPE."  and bd.action not in ('X','S') ";
		if (!empty($id_det)) $sql .= " AND bd.rowid ='".$id_det."'";
		if (!empty($fk_acompte)) $sql .= " AND bd.fk_facture ='".$fk_acompte."'";
		if (!empty($ref_acompte)) $sql .= " AND fac.ref ='".$ref_acompte."'";

        dol_syslog(get_class($this)."::fetchDemandeStripe ");
        $resql=$this->db->query($sql);
        if ($resql)
        {			
            $num = $this->db->num_rows($resql);
			if ($num > 0)
			{		
                $obj = $this->db->fetch_object($resql);
				$this->id = $obj->id;
				$this->montant = $obj->montant;
				$this->Nompayeur = $obj->Nompayeur;
				$this->mailpayeur = $obj->mailpayeur;
				$this->smspayeur = $obj->smspayeur;
				$this->fk_acompte	 = $obj->fk_acompte;			
				$this->RefAcompte =$obj->RefAcompte;	
				$this->ModelMail	 = $obj->ModelMail;
				$this->dateenvoi	 = $obj->dateenvoi;
				$this->dateDerniereRelance	 = $obj->datederniereRelance;	
				$this->fk_bull	 = $obj->fk_bull;
				$this->fk_bank	 = $obj->fk_bank;
				$this->fk_bulldet	 = $obj->fk_bulldet;
				$this->date_paiement	 = $obj->date_paiement;
				$this->nbRelance	 = $obj->nbRelance;
				$this->fk_paiement	 = $obj->fk_paiement;
				$this->fk_soc_rem_execpt	 = $obj->fk_soc_rem_execpt;
				$this->action	 = $obj->action;
				$this->stripeUrl	 = $obj->stripeUrl;
				$this->libelleCarteStripe	 = $obj->libelleCarteStripe;
				$this->derRelMailSms	 = $obj->derRelMailSms;	
			
				return $this->id;
			}
			else return 0;
		}	
		else return -1;		
	} //fetchDemandeStripe
	/*
	*
	* Modification des champs spécifiés de la ligne de demande Stripe*
	*	@param 	string 	$champ1	nom du champ dont la valeur suit
	*	@param 	tous 	$val1	valeur du champ dont le nom précède
	* 	répéter 3 fois
	*	retour	int	id de la ligne si OK
	*				-1 si erreur
	*
	*/
	function update_champ($champ1, $val1, $champ2='', $val2='', $champ3='', $val3='', $champ4='', $val4='' )
	{
    	global $conf, $langs, $user, $bull;
		$error=0;

					
		// parametres location
	    if (isset($val1) or $champ1 == 'fk_facture')	 	$val1		=trim($val1);
	    if (isset($val2) or $champ1 == 'fk_facture')	 	$val2		=trim($val2);
	    if (isset($val3) or $champ1 == 'fk_facture')	 	$val3		=trim($val3);
	    if (isset($val4) or $champ1 == 'fk_facture')	 	$val4		=trim($val4);
		// Put here code to add control on parameters values
		// le champ action est mis Ã  jour en mÃªme temps que les clÃ©s Ã©trangÃ¨res : AjoutFkDolibarr
		$i=0;

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_det SET  ";
		 $sql.= $champ1."= '".$val1."'";
		if (!empty($champ2)) $sql.= ', '.$champ2.'= "'.$val2.'"';
		if (!empty($champ3)) $sql.=  ', '.$champ3.'= "'.$val3.'"';
		if (!empty($champ4)) $sql.=  ', '.$champ4.'= "'.$val4.'"';
		$sql.= "  Where rowid =  '".$this->id."'";
		$this->db->begin();

		// liste champ mis à jourséé
		$lb = "champs:".$champ1;
		if (!empty($champ2)) $lb .= "---".$champ2;
		if (!empty($champ3)) $lb .= "---".$champ3;
		if (!empty($champ4)) $lb .= "---".$champ4;
	   	dol_syslog(get_class($this)."::update_".$lb." ", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
		
	} // update_champs

	
	
	function InsertDemandeStripe($user)
	{
		global $bull;
		
		if (empty($this->montant)) $this->montant=0;

		$this->db->begin();
		$w = new CglFonctionCommune ($this->db);
		$now = $this->db->idate(dol_now('tzuser'));
		//$dateenv = substr($this->db->idate($this->dateenvoi),0,10);		
		
		unset ($w);
		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cglinscription_bull_det';
		$sql.= ' (fk_bull, NomPrenom, pt, lieudepose, tireur, fk_facture, fk_agsessstag,  datedepose, fk_activite,  reslibelle, ';
		$sql.= ' ficbull, marque, datec, action, fk_user, type)';
		
		$sql.= " VALUES (".$this->fk_bull.",";
		$sql.= ' "'.$this->Nompayeur.'",';
		if (empty($this->montant)) $sql.= ' 0, ';  else $sql.= ' "'.$this->montant.'",';
		$sql.= ' "'.$this->mailpayeur.'",';
		$sql.= " '".$this->smspayeur."',";
		if (empty($this->fk_acompte)) $sql.= ' 0, ';  else $sql.= " '".$this->fk_acompte."', ";
		if (empty($this->fk_soc_rem_execpt)) $sql.= ' 0, ';  else $sql.= " '".$this->fk_soc_rem_execpt."', ";		
		$sql.= " '".$this->dateenvoi."', ";
		if (empty($this->ModelMail)) $sql.= ' 0, ';  else $sql.= " '".$this->ModelMail."', ";
		$sql.= " '".$this->stripeUrl."', ";
		$sql.= " '".$this->libelleCarteStripe."', ";
		$sql.= " '".$this->derRelMailSms."', ";
		$sql.= " '".$now."',  'A' , '".$user->id."', ";
		$sql.= " 5 ";
		$sql.= ')';

		dol_syslog(get_class($this)."::InsertDemandeStripe ");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'cglinscription_bull_det');
			// suppression appel triggers
			$this->db->commit();
			$bull->line[] = $this;	
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::InsertDemandeStripe Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	} //InsertDemandeStripe
	/*
	* Relance la demande Stripe
	*
	*	@param $user	object	Utisilateur connecté
	*
	* retour - 0 si OK, -n si erreur
	*/
	function RelanceDemandeStripe($user)
	{
		global $libelleCarteStripe;
			
		$sqlIncNbRelance = ' qteret = case when isnull(qteret) then 1 else qteret+1 end ';
		$this->libelleCarteStripe = $libelleCarteStripe;
		
		$ret = $this->UpdateDemandeStripe($user, $sqlIncNbRelance, true);
		return $ret;
	} //RelanceDemandeStripe
	function UpdateDemandeStripe($user, $sqlIncNbRelance = '', $IsRelance = false, $Ispaiement = false)
	{
		global $bull;
		
		if (empty($this->montant)) $this->montant=0;

		$this->db->begin();
		//$daterel = substr($this->db->idate(dol_now()),0,10);
		//$daterel= dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S', 'tzuser');
		$daterel= dol_print_date(dol_now('tzuser'), '%Y-%m-%d %H:%M:%S');
		
		// Insertion dans base de la ligne
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'cglinscription_bull_det SET ';
		
		//if (!empty($this->Nompayeur)$sql.= " NomPrenom = '".$this->Nompayeur."', ";
		//if (!empty($this->montant)) $sql.= 'pt = "'.$this->montant.'", ';
		//if (!empty($this->mailpayeur)) $sql.= 'lieudepose = "'.$this->mailpayeur.'", ';
		//if (!empty($this->smspayeur)) $sql.= "tireur = '".$this->smspayeur."', ";
		if (!empty($this->libelleCarteStripe)) $sql.= "ficbull = '".$this->libelleCarteStripe."', ";
		//if (!empty($this->fk_acompte)) $sql.= "fk_facture = '".$this->fk_acompte."', ";
		//if (!empty($this->ModelMail) and $this->ModelMail > 0) $sql.= "fk_activite = '".$this->ModelMail."', ";
		if (!empty($sqlIncNbRelance) ) $sql .= $sqlIncNbRelance. ',';
		if ( $IsRelance ) $sql.= " dateretrait = '".$daterel."', ";
		if ( $Ispaiement ) $sql.= " datepaiement = '".$daterel."', ";
		if ( $Ispaiement ) 
		{
			if ($this->fk_paiement) $sql.= " fk_paiement = '".$this->fk_paiement."', ";	
			else 
			$sql.= " fk_paiement = 0, ";	
		}
		//if (!empty($this->fk_soc_rem_execpt) ) $sql.= " fk_agsessstag = '".$this->fk_soc_rem_execpt."', ";	
		if (!empty($this->fk_bulldet) ) $sql.= " fk_raisrem = '".$this->fk_bulldet."', ";		
		//if (!empty($this->fk_bank) ) $sql.= " fk_banque = '".$this->fk_bank."', ";		
		//if (!empty($this->derRelMailSms) ) $sql.= " marque = '".$this->derRelMailSms."', ";	

		$sql.= " action = 'M'   ";
		$sql.= ' WHERE rowid ='. $this->id;
	
		dol_syslog(get_class($this)."::UpdateDemandeStripe ");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			
			$this->db->commit();	
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::UpdateDemandeStripe Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	} //InsertDemandeStripe
	function UpdateUrlStripe($user, $id, $wurl, $derRelMailSms)
	{
		global $bull;		

		$this->db->begin();
		
		// Insertion dans base de la ligne
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'cglinscription_bull_det SET ';
		
		$sql.= ' NomPrenom = "'.$this->Nompayeur.'" ';
		if (!empty($wurl)) $sql.= ', reslibelle = "'.$wurl.'" ';
		if (!empty($derRelMailSms)) $sql.= ', marque = "'.$derRelMailSms.'" ';
		$sql.= ' WHERE rowid ='. $id;
		
		dol_syslog(get_class($this)."::UpdateUrlStripe ");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			
			$this->db->commit();	
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::UpdateUrlStripe Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	} //InsertDemandeStripe

	function CopieStripePaiement($lineStripe, $payeur_carte, $idTransaction, &$idbank, &$idpaiement)
	{
		global $conf;
			
		/*$w = new CglFonctionCommune ($this->db);
		$datepai = $this->db->idate(dol_now());
		unset ($w);
*/
		// Aller rechercher  $id_bank, $id_paiement de l'acompte précédement payé		
		require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglstripe.class.php';
		$wstripe =  new CglStripe ($this->db);
		$tbret = $wstripe->fetchPaiementBanquebyIdAcompte ($lineStripe->fk_acompte);
		if (is_array($tbret) and !empty($tbret)) {
			$id_paiement = $tbret[0];
			$id_bank = $tbret[1];
		}	
		$line = new BulletinLigne ($this->db);
		$line->fk_bull = $lineStripe->fk_bull;
		$line->montant = $lineStripe->montant;
		$line->fk_user = $lineStripe->fk_user;
		$line->tireur = $payeur_carte;
		$line->organisme = 'Stripe';
		$line->fk_banque =  $id_bank;
		$line->fk_paiement =  $id_paiement;
		$idbank =  $id_bank;
		$idpaiement = $id_paiement;
		
		$line->id_mode_paiement =  $conf->global->STRIPE_PAYMENT_MODE_FOR_PAYMENTS;
		$line->fk_facture = $lineStripe->fk_acompte;
		$line->action = '';
		//$line->date_paiement = dol_now();
		//$line->date_paiement = dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S', 'tzuser');
		$line->date_paiement = dol_print_date(dol_now('tzuser'), '%Y-%m-%d %H:%M:%S');
		$line->datec = $line->date_paiement;
		$line->num_cheque = $idTransaction;

		$ret = $line->insertPaiement();		

		if ($ret>0)
		{
				return $ret;
		}
		else
		{
			return -2;
		}		
	} //CopieStripePaiement


}// Fin de class BulletinDemandeStripe

/**
 *	\class      	BulletinLigneMatMad
 *	\brief      	Classe permettant la gestion des lignes de bulletin
 *					Gere des lignes de la table llx_cglinscription_bull_mat_mad
 */
class BulletinLigneMatMad extends CommonObject
{
	
	public $element='bulletin_matMad';
	public $table_element='cglinscription_bull';
	public $table_element_line = 'llx_cglinscription_bull_mat_mad';
	public $fk_element = 'fk_bull';
	
	var $db;
	var $error;

	var $oldline;
	
	// info
	var $id;
	var $fk_mat_mad; 
	var $lb_mat_mad;
	var $lb_mat_mad_tot;	// sert sur le contrat papier à ajouter un matériel supplémentaire, mis en obsersavtion matériel
	var $lb_service;
	var $fk_service;
	var $fk_bull;
	var $qte;
	var $qteret;
	var $lb_ret_mat;
	var $datedepose;
	var $dateretrait;

	var $is_retour;	
	
	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}
	/*
	*	Insere les lignes de matériel mis à dispo suivant le type de service déjà loués
	*/
	function insertall()
	{
		global $langs,$user,$conf, $id_contrat;

		$error=0;
		dol_syslog(get_class($this)."::insertall rang=".$this->rangdb, LOG_DEBUG);

		$this->db->begin();
		//$now = dol_now();
		//$now = dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S', 'tzuser');
		$now = dol_print_date(dol_now('tzuser'), '%Y-%m-%d %H:%M:%S');
		// recherche du rang du paiement 
		$rang = $this->rechercheRangSuiv (1);
		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cglinscription_bull_mat_mad';
		$sql.= ' (datec, fk_bull, fk_mat_mad,   ordre)';
		$sql .= " SELECT '".$this->db->idate($now)."', '". $this->fk_bull."', mad.rowid , ordre";
		$sql .= " FROM ".MAIN_DB_PREFIX."cgl_c_mat_mad as mad WHERE mad.active = 1 ";
		dol_syslog(get_class($this)."::insertall ");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'cglinscription_bull_det');
			// suppression appel triggers
			$this->db->commit();			
			return $this->rowid;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::insertall Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	} /* insertall */	
	function update_un()
	{	
    	global $conf, $langs, $user, $bull;
		$error=0;
		
		// Clean parameters
        if (isset($this->qte)) $this->qte=trim($this->qte);
        if (isset($this->qteret)) $this->qteret=trim($this->qteret);
        if (isset($this->ordre)) $this->ordre=trim($this->ordre);
		
		// Check parameters
		// Put here code to add control on parameters values
		// le champ action est mis Ã  jour en mÃªme temps que les clÃ©s Ã©trangÃ¨res : AjoutFkDolibarr
		$i=0;

		
        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_mat_mad SET ";
		//$now=dol_now();
		//$now=dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S', 'tzuser');
		$now=dol_print_date(dol_now('tzuser'), '%Y-%m-%d %H:%M:%S');
		$sql.= " tms = '".$this->db->idate($now)."'";
		$sql.= " , qte= '".$this->qte."'";	
		$sql.= " , qteret= '".$this->qteret."'";	
		
		if ($this->ordre) $sql.= " , ordre= '".$this->ordre."'";
		$sql.= " Where rowid =  ".$this->id;
		
		$this->db->begin();
	   	dol_syslog(get_class($this)."::update_un ", LOG_DEBUG);
        $resql=$this->db->query($sql);
		
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::update_un ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
		
	}/* update_un */
	function rechercheRangSuiv ($type_enr)
	{
		global $bull;
		// recherche le prochain rang dans la base,du type d'enregistrement concerné
		
		$sql = 'SELECT MAX(ordre) as maxrang FROM  '.MAIN_DB_PREFIX.'cglinscription_bull_mat_mad';
		$sql.= ' WHERE fk_bull = '.$bull->id;
		
		dol_syslog(get_class($this)."::rechercheRangSuiv ");
		$resql=$this->db->query($sql);		
        if ($resql)
        {	
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
				$rang = $obj->maxrang + 1;
			}
			else if ($type_enr == 0 ) $rang = 1; else $rang = 1000;
		}
		else
		{
			dol_syslog(get_class($this)."::rechercheRangSuiv ");
			return -1;
		}	
		return $rang;
	} // rechercheRangSuiv
	function update_champs($champ1, $val1, $champ2='', $val2='', $champ3='', $val3='', $champ4='', $val4='' )
	{
    	global $conf, $langs, $user, $bull;
		$error=0;

					
		// parametres location
	    if (isset($val1))	 	$val1		=trim($val1);
	    if (isset($val2))	 	$val2		=trim($val2);
	    if (isset($val3))	 	$val3		=trim($val3);
	    if (isset($val4))	 	$val4		=trim($val4);
		// Put here code to add control on parameters values
		// le champ action est mis Ã  jour en mÃªme temps que les clÃ©s Ã©trangÃ¨res : AjoutFkDolibarr
		$i=0;

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_mat_mad SET  ";
		 $sql.= $champ1."= '".$val1."'";
		if (!empty($champ2)) $sql.= ', '.$champ2.'= "'.$val2.'"';
		if (!empty($champ3)) $sql.=  ', '.$champ3.'= "'.$val3.'"';
		if (!empty($champ4)) $sql.=  ', '.$champ4.'= "'.$val4.'"';
		$sql.= "  Where rowid =  ".$this->id;
		$this->db->begin();

		// liste champ mis à jourséé
		$lb = "champs:".$champ1;
		if (!empty($champ2)) $lb .= "---".$champ2;
		if (!empty($champ3)) $lb .= "---".$champ3;
		if (!empty($champ4)) $lb .= "---".$champ4;
	   	dol_syslog(get_class($this)."::update_".$lb." ", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
		
	} // update_champs

	
} // fin BulletinLigneMatMad


/**
 *	\class      	BulletinLigneRando
 *	\brief      	Classe permettant la gestion des lignes de bulletin
 *					Gere des lignes de la table llx_cglinscription_bull_mat_mad
 */
class BulletinLigneRando extends CommonObject
{
	
	public $element='bulletin_rando';
	public $table_element='cglinscription_bull';
	public $table_element_line = 'llx_cglinscription_bull_rando';
	public $fk_element = 'fk_bull';
	
	var $db;
	var $error;

	var $oldline;
	
	// info
	var $id;
	var $fk_rando; 
	var $lb_rando;
	var $lb_service;
	var $fk_service;
	var $fk_bull;
	var $prevue;

	var $qte;
	var $qteret;
	var $lb_ret_rando;
	
	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}
	/*
	*	Insere les lignes de matériel mis à dispo suivant le type de service déjà loués
	*/
	function insertall()
	{
		global $langs,$user,$conf, $id_contrat; 
		$error=0;
		dol_syslog(get_class($this)."::insertall rang=".$this->rangdb, LOG_DEBUG);

		$this->db->begin();
		
		//$now = dol_now();
		//$now = dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S', 'tzuser');
		$now = dol_print_date(dol_now('tzuser'), '%Y-%m-%d %H:%M:%S');

		// recherche du rang du paiement 
		$rang = $this->rechercheRangSuiv (1);
		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cglinscription_bull_rando';
		$sql.= ' (datec, fk_bull, fk_rando,   qte,  ordre)';
		$sql .= "SELECT '".$this->db->idate($now)."', '". $this->fk_bull."', mad.rowid, '".$this->qte."'";
		$sql .=", ordre ";
		$sql .= "FROM ".MAIN_DB_PREFIX."cgl_c_rando as mad WHERE mad.active = 1 ";
		
		dol_syslog(get_class($this)."::insertall ");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'cglinscription_bull_det');
			// suppression appel triggers
			$this->db->commit();			
			return $this->rowid;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::insertall Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	} /* insertall */	
	function update_un()
	{	
    	global $conf, $langs, $user, $bull;
		$error=0;
		
		// Clean parameters
        if (isset($this->prevue)) $this->prevue=trim($this->prevue);
        if (isset($this->ordre)) $this->ordre=trim($this->ordre);
		
		// Check parameters
		// Put here code to add control on parameters values
		// le champ action est mis Ã  jour en mÃªme temps que les clÃ©s Ã©trangÃ¨res : AjoutFkDolibarr
		$i=0;

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_rando SET ";
		
		//$now = dol_now();
		//$now = dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S', 'tzuser');
		$now = dol_print_date(dol_now('tzuser'), '%Y-%m-%d %H:%M:%S');
		$sql.= " tms = '".$this->db->idate($now)."'";
		$sql.= " , qte= '".$this->qte."'";	
		$sql.= " , qteret= '".$this->qteret."'";	
		
		if ($this->ordre) $sql.= " , ordre= '".$this->ordre."'";
		$sql.= " Where rowid =  ".$this->id;
		$this->db->begin();
	   	dol_syslog(get_class($this)."::update_un ", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::update_un ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
		
	}/* update_un */
	function rechercheRangSuiv ($type_enr)
	{
		global $bull;
		// recherche le prochain rang dans la base,du type d'enregistrement concerné
		
		$sql = 'SELECT MAX(ordre) as maxrang FROM  '.MAIN_DB_PREFIX.'cglinscription_bull_rando';
		$sql.= ' WHERE fk_bull = '.$bull->id.' and type = '.$type_enr;
		
		dol_syslog(get_class($this)."::rechercheRangSuiv ");
		$resql=$this->db->query($sql);		
        if ($resql)
        {	
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
				$rang = $obj->maxrang + 1;
			}
			elseif ($type_enr == $this->LINE_ACT  ) $rang = 1; 
			else $rang = 1000;
		}
		else
		{
			dol_syslog(get_class($this)."::rechercheRangSuiv ");
			return -1;
		}	
		return $rang;
	} // rechercheRangSuiv
	function update_champs($champ1, $val1, $champ2='', $val2='', $champ3='', $val3='', $champ4='', $val4='' )
	{
    	global $conf, $langs, $user, $bull;
		$error=0;
					
		// parametres location
	    if (isset($val1))	 	$val1		=trim($val1);
	    if (isset($val2))	 	$val2		=trim($val2);
	    if (isset($val3))	 	$val3		=trim($val3);
	    if (isset($val4))	 	$val4		=trim($val4);
		// Put here code to add control on parameters values
		// le champ action est mis Ã  jour en mÃªme temps que les clÃ©s Ã©trangÃ¨res : AjoutFkDolibarr
		$i=0;

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cglinscription_bull_rando SET  ";
		 $sql.= $champ1."= '".$val1."'";
		if (!empty($champ2)) $sql.= ', '.$champ2."= '".$val2."'";
		if (!empty($champ3)) $sql.=  ', '.$champ3."= '".$val3."'";
		if (!empty($champ4)) $sql.=  ', '.$champ4."= '".$val4."'";
		$sql.= "  Where rowid =  ".$this->id;
		$this->db->begin();

		// liste champ mis à jours
		$lb = "champs:".$champ1;
		if (!empty($champ2)) $lb .= "---".$champ2;
		if (!empty($champ3)) $lb .= "---".$champ3;
		if (!empty($champ4)) $lb .= "---".$champ4;
	   	dol_syslog(get_class($this)."::update_".$lb, LOG_DEBUG);
		
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
        if ($error > 0)
		{
			dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}		
	} // update_champs

} // fin Class BulletinLigneRando
?>