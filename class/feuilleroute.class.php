<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com--->
 *
 * CAV Version 2.7.1 - automne 2022 - Le solde doit tenir compte des remises fixes
 * CAV Version 2.8 - hiver 2023 - Ajout d'une variable depart_note dans la feuille de route
 * Version CAV - 2.8.5 - printemps 2023
 *			- absence des bulletin d'un départ si celui-ci n'a pas de moniteur (bug 325)
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
 *
 *
 */

/**
 *	\file       htdocs/custum/cglinscription/class/bull.class.php
 *	\ingroup    societe
 *	\brief      File for third party class
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once './core/modules/cglinscription/doc/doc_feuilleroute_odt.modules.php';
require_once '../cglavt/class/cglFctCommune.class.php';
require_once 'bulletin.class.php';;
//Constantes

/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */
class FeuilleRoute extends CommonObject
{

	public $element='feuilleroute';
	public $table_element='cglinscription_bull';
	public $table_element_line = 'cglinscription_bull_det';
	public $fk_element = 'fk_bull';
	
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	
    var $id;	
    var $fk_user;
	var $ref; // libelle actiite+mois+jour+rowid
	var $ficsess;  // stocke le fichier de feuille de route
	
	// Depuis llx_agefodd_session
	var $id_act;       // // Id of activite concernÃ©e
	var $activite_dated;       // Activite date Debut
	var $activite_lieu;       // Activite date Debut
	var $activite_label;     // Activite label
	var $activite_nbmax;  	// Nb participant max Activite
	var $activite_nbplcIns;  	// Nb participant inscrits
	var $activite_nbplcPins;  	// Nb participant pre-inscrits
	var $activite_heured;
	var $activite_heuref;
	var $lib_rdvPrinc;
	var $notes;
	var $activite_rdvPrinc;
	var $depart_nb_rdv1;
	var $lib_rdvSec;
	var $activite_rdvSec;
	var $depart_nb_rdv2;
	var $depart_label_RDV1;
	var $moniteur1;
	var $moniteur2;	
	var $moniteur3;	
	var $lines=array();
	var $depart_etq_pour;
	var $depart_etq_personnes1;
	var $depart_etq_personnes2;
	var $depart_label_RDV2;
	var $lib_grp;
	var $depart_pugrp;
	var $lib_enf;
	var $depart_puenf;
	var $lib_adl;
	var $depart_puadl;
	var $lib_euro;
	var $duree;
	
    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
		global $langs, $db;
        $this->db = $db;
		$langs->load('cglinscription@cglinscription');
		
		$bull0 = new Bulletin ($this->db);
		//valeur d'état
		$this->BULL_ENCOURS=$bull0->BULL_ENCOURS;
		$this->BULL_INS=$bull0->BULL_INS;	
		$this->BULL_PRE_INS=$bull0->BULL_PRE_INS;		
		
		//valeur de reglement	
		$this->BULL_NON_PAYE=$bull0->BULL_NON_PAYE;
		$this->BULL_INCOMPLET=$bull0->BULL_INCOMPLET;	
		$this->BULL_PAYE=$bull0->BULL_PAYE;
		$this->BULL_SURPLUS=$bull0->BULL_SURPLUS;
		$this->BULL_REMB=$bull0->BULL_REMB;	
		$this->BULL_ARCHIVE=$bull0->BULL_ARCHIVE;
		unset ($bull0);
			
        return 1;
    }/* __construct */
 
    /**
     *  Load object in memory from the database avec filtre univoque : statuts = 0 ou rowid = valeur
     *
     *  @status	int		$id     ==> bulletin en cours, -1 ==> avec rowid precis
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch_complet_filtre($status = 0, $idSess )
    {
    	global $langs;
		$w = new CglFonctionCommune ($this->db);
		
		/* recherche tete du bulletin */
        $sql = "SELECT s.rowid as id, intitule_custo, nb_place,  dated, heured , heuref, s_rdvPrinc, s_rdvAlter, p.ref_interne as lieu, s.notes, ";
		$sql.="	concat(concat(concat(concat(intitule_custo , '-'), concat(month(dated),'-')),concat(concat(day(dated),'-'),concat(hour(heured) ,'_'))), s.rowid) as ref, ";
		$sql.= " (select count(distinct rowid)  from  ".MAIN_DB_PREFIX."agefodd_session_stagiaire where fk_session_agefodd = s.rowid and status_in_session = 2) as Nbinscrit, ";
		$sql.= " (select count(distinct rowid)  from  ".MAIN_DB_PREFIX."agefodd_session_stagiaire where fk_session_agefodd = s.rowid and status_in_session = 0) as NbPreinscrit , ";
		$sql.= " (select count(rowid) from ".MAIN_DB_PREFIX."cglinscription_bull_det where (fk_rdv = 1 or fk_rdv = 0 or fk_rdv='') and fk_activite = s.rowid and action not in ('S','X')) as NbRdv1,";
		$sql.= " (select count(rowid) from ".MAIN_DB_PREFIX."cglinscription_bull_det where fk_rdv = 2 and fk_activite = s.rowid and action not in ('S','X'))  as NbRdv2,";
		$sql.= " se.s_PVIndAdl as puAdl, se.s_PVIndEnf as puEnf, se.s_pvgroupe as puGrp,s_ficsess, s.type_session , se.s_duree_act";
        $sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s ";
		$sql.=" left join ".MAIN_DB_PREFIX."agefodd_session_extrafields as se on se.fk_object=s.rowid";
		$sql.=" left join ".MAIN_DB_PREFIX."agefodd_place as p on fk_session_place=p.rowid";
		$sql.=" left join ".MAIN_DB_PREFIX."agefodd_session_calendrier as sc on sc.fk_agefodd_session = s.rowid";
        $sql.= " WHERE s.rowid =  '".$idSess."'";
    	dol_syslog(get_class($this)."::fetch activite sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);		
		if ($resql)
        {	
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                $this->id_act   	= $obj->id;
                $this->activite_dated   = $w->transfDateFr($obj->dated);
                $this->activite_lieu   	= $obj->lieu;
                $this->activite_label   = $obj->intitule_custo;
                $this->activite_heured   	= $w->transfHeureFr($obj->heured);
                $this->activite_heuref   = $w->transfHeureFr($obj->heuref);
                $this->activite_nbmax   = $obj->nb_place;
                $this->activite_nbplcIns    = $obj->Nbinscrit;
                $this->activite_nbplcPins  	= $obj->NbPreinscrit;
                $this->lib_rdvPrinc   	= $obj->s_rdvPrinc;
                $this->notes   	= $obj->notes;
				$this->activite_rdvPrinc	= 1;
				$this->depart_nb_rdv1		= $obj->NbRdv1;
				$this->depart_etq_personnes1 .= ($obj->NbRdv1>1)?'personnes':'personne';
				
				$this->depart_label_RDV2 = ($obj->NbRdv2>0)?'RDV secondaire:':'';
				$this->lib_rdvSec		= ($obj->NbRdv2>0)?$obj->s_rdvAlter:'';
				$this->activite_rdvSec	= 2;
				$this->depart_etq_pour  = ($obj->NbRdv2>0)?'pour':'';
				$this->depart_nb_rdv2		= ($obj->NbRdv2>0)?$obj->NbRdv2:' ';
				if ($obj->NbRdv2 == 0 )$this->depart_etq_personnes2 = '';
				elseif ($obj->NbRdv2 == 1 )$this->depart_etq_personnes2 = 'personne';
				else $this->depart_etq_personnes2 = 'personnes';
				$this->ref			= $obj->ref;
				$this->type_session_agf		= $obj->type_session;
				if (empty($obj->type_session)) $this->type_session = 1;
				if ($obj->type_session_agf == 0)
				{ // groupe constitue
					$this->lib_grp = 'Groupe :';
					if (empty($obj->puGrp)) $this->depart_pugrp = 0;
					else $this->depart_pugrp		= $obj->puGrp;
				}
				else 
				{ // individuel
					$this->lib_adl = 'Adulte :';
					$this->depart_puadl		= $obj->puAdl;
					if ($obj->puEnf != 0)
					{
						$this->lib_enf = 'Enfant :';
						$this->depart_puenf		= $obj->puEnf;
						$this->lib_euro			='euros';
					}
				}
				
				
				$this->duree		= $obj->s_duree_act;
				
				unset($obj);
				$id_act   = $this->id_act ;				
				$this->ficsess = $obj->s_ficsess;	
				/* moniteur */
				$sql=" select case when isnull(cf.lastname) then cu.lastname else cf.lastname end as Nom, ";
				$sql.="  case when isnull(cf.firstname) then cu.firstname else cf.firstname end as Prenom";
				$sql.=" FROM ".MAIN_DB_PREFIX."agefodd_session as s ";
				$sql.=" left join ".MAIN_DB_PREFIX."agefodd_session_formateur as sf on sf.fk_session = s.rowid";
				$sql.=" left join ".MAIN_DB_PREFIX."agefodd_formateur as f on sf.fk_agefodd_formateur = f.rowid";
				$sql.=" left join ".MAIN_DB_PREFIX."socpeople as cf on cf.rowid = f.fk_socpeople";
				$sql.=" left join ".MAIN_DB_PREFIX."user as cu on cu.rowid = f.fk_user";
				$sql.= " WHERE s.rowid =  ".$idSess;
				dol_syslog(get_class($this)."::fetch moniteur sql=".$sql, LOG_DEBUG);
				$resql1=$this->db->query($sql);
				if ($resql1)
				{
					$num = $this->db->num_rows($resql1);
					if ($num >0)
					{
						$i=0;
						while ($i < 3 and $i < $num)
						{
						$i++;
						$obj = $this->db->fetch_object($resql1);
						if ($i==1) $this->moniteur1   = $obj->Prenom.' '.$obj->Nom;
						elseif ($i==2) $this->moniteur2   = $obj->Prenom.' '.$obj->Nom;
						elseif ($i==3) $this->moniteur3   = $obj->Prenom.' '.$obj->Nom;
						}
					}
				}
				else
				{
					$this->error="Error ".$this->db->lasterror();
					dol_syslog(get_class($this)."::fetch moniteur ".$this->error, LOG_ERR);
					return -4;
				}	
				/* ligne bulletin */
				
				//$this->lines  = array();
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					$this->error=$this->db->error();
					dol_syslog(get_class($this)."::fetch Error ".$this->error, LOG_ERR);					
					return -3;
				}
			}
			else
			{
				$bull->id = '';
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
    } /* fetch complet*/
	/*
	*	Recupère les données d'une ligne de bulletin issu de la lecture de la base
	* necessite que objetclass->id ait été renseigné
	*   @param 	variant	$obj	resultat de la requete
	*/
	function fetch_lines()
	{
		global $langs, $conf;
		
		$bulltemp = new Bulletin($this->db);
		
		// si "Attention" pour un bulletin, ne pas le remettre pour le même bulletin
		// lors premier Attention : mettre dans tableau  SuiviBullDefautPaiement[id_bull] = 10
		// test avant de réaliser phrase Attentino : si SuiviBullDefautPaiement[id_bull] = 10 ne rien faire
		
		$SuiviBullDefautPaiement = array();
		//$this->lines=array();
		$sql='';

		$sql="SELECT distinct bd.rowid as rowid,  ";
	//	$sql.=" s.rowid as fk_activite, fk_contact,c.lastname as PartNom, c.firstname as PartPrenom, type_session,  dp.libelle as poids , ";
		$sql.=" s.rowid as fk_activite, fk_contact,c.lastname as PartNom, c.firstname as PartPrenom, type_session,   bd.poids as poids , ";
		//$sql.=" bd.age as s_age, bd.taille as s_taille ,  bd.PartTel, c.civility as civilite, bd.fk_bull, soc.nom as TiersNom, bd.NomPrenom, ";		
		$sql.=" bd.age as s_age, bd.taille as s_taille ,  soc.phone as PartTel, c.civility as civilite, bd.fk_bull, soc.nom as TiersNom, bd.NomPrenom, ";						
		$sql.=" bd.fk_rdv, bd.observation,";
		$sql .= " CASE WHEN bd.action in ('X','S') THEN 6 ELSE CASE WHEN b.statut = 1 THEN 2 ELSE 0 END  END as status_in_session ,";
		$sql .= "b.ref,b.ActionFuture, b.PmtFutur,  bd.pu* (100- bd.rem)/100 as PartPt,bd.rem, pu, ";
		$sql.=" fk_persrec, b.TiersTel , rec.lastname as RecNom, rec.firstname as RecPreNom, rec.civility as RecCiv, b.RecTel, ";		
		$sql.="(select sum(bddep.pu* (100- bddep.rem)/100) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bddep ";
		$sql.=" left join ".MAIN_DB_PREFIX."agefodd_session as Ags on Ags.rowid = fk_activite";
			$sql.=" where bddep.fk_bull = b.rowid and bddep.type=0  and bddep.action not in('S','X')  and type_session = 0 ) as totaldepGrp ,";
		
		$sql.="(select sum(bddep.pu* bddep.qte*(100- bddep.rem)/100) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bddep ";
		$sql.=" left join ".MAIN_DB_PREFIX."agefodd_session as Ags on Ags.rowid = fk_activite";
		$sql.=" where bddep.fk_bull = b.rowid and bddep.type=0  and bddep.action not in('S','X')  and (type_session = 1 or isnull(type_session)) ) as totaldepInd ,";
	
		$sql.="(select sum(bdrec.pt ) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bdrec where bdrec.fk_bull = b.rowid and bdrec.type = 1 ";
			$sql.=" and bdrec.action not in('S','X') ) as totalrec , ";
				
		$sql.="(select sum(bdrec.pt ) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bdrec where bdrec.fk_bull = b.rowid and bdrec.type = 2 ";
			$sql.=" and bdrec.action not in('S','X') ) as totalboncad , PmtFutur";

		$sql.=" FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd ";
		$sql.="  left join ".MAIN_DB_PREFIX."socpeople as c on c.rowid = bd.fk_contact  ";
		if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND c.statut <> 0 ";
		//$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."cgl_c_poids as dp on dp.rowid = bd.poids ";
		$sql .= "left join ".MAIN_DB_PREFIX."socpeople_extrafields as ce on c.rowid = ce.fk_object ";
		$sql.=" left join ".MAIN_DB_PREFIX.'agefodd_session as s on s.rowid = bd.fk_activite';
		$sql.=" left join ".MAIN_DB_PREFIX.'agefodd_stagiaire as sta on c.rowid = sta.fk_socpeople';
		$sql.= ' left join '.MAIN_DB_PREFIX.'agefodd_session_calendrier as cal on cal.fk_agefodd_session = s.rowid ';
		$sql.= ' left join '.MAIN_DB_PREFIX.'cglinscription_bull as b on bd.fk_bull = b.rowid ';
		$sql.=" left join ".MAIN_DB_PREFIX."socpeople as rec on rec.rowid = b.fk_persrec ";
		if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND rec.statut <> 0 ";
		$sql.=" left join ".MAIN_DB_PREFIX."societe as soc on b.fk_soc = soc.rowid";		
		$sql.=" left join ".MAIN_DB_PREFIX."societe_extrafields as soce on soce.fk_object = soc.rowid";
		$sql.=" WHERE type = 0  and fk_activite = '".$this->id_act."' ";
		$sql.=" and b.statut  > " . $bulltemp->BULL_ENCOURS ."  and b.statut  < " . $bulltemp->BULL_ABANDON ; 
		$sql.= " AND bd.action not in ('X','S') ";
		$sql.=" ORDER BY  fk_rdv, b.fk_soc, bd.age" ; 
		
		dol_syslog(get_class($this).'::fetch_lines sql='.$sql, LOG_DEBUG);
		
		$result = $this->db->query($sql);
		if ($result)
		{	
			$num = $this->db->num_rows($result);
		
			$i = 0; 
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$line = new FeuilleRoute($this->db);					
				$line->id					= $objp->rowid;	;
				$line->status_in_session	= $objp->status_in_session;	
				if ( $objp->status_in_session == 2)
					$line->inscrit				= 'I';
				elseif		( $objp->status_in_session == 0)			
					$line->inscrit				= 'P';
				elseif		( $objp->status_in_session == 6)			
					$line->inscrit				= 'A';	
				$line->TiersNom 				= $objp->TiersNom;			
/*				if (empty($objp->PartNom))
					$line->PartNom				= $objp->TiersNom;
				else
*/
					$line->PartNom				= $objp->PartNom;
				$line->PartPrenom			= (!empty($objp->PartCivLib))?$objp->PartCivLib. ' ':''.$objp->PartPrenom;		
/*				if (empty($objp->NomPrenom))
					$line->NomPrenom				= $objp->TiersNom;
				else
*/
					$line->NomPrenom				= $objp->NomPrenom;

				$line->PartCivLib			= $objp->civilite;
				if (!empty($objp->PartTel)) $line->PartTel =$objp->PartTel;
				else $line->PartTel 	=$objp->TiersTel;
				$line->bullref				= $objp->ref;
				$line->ActionFuture				= $objp->ActionFuture;
				$line->PmtFutur				= $objp->PmtFutur;
				$line->PartPoids				= $objp->poids;
				
				if ($objp->s_age == 99) $line->PartAge	= 'Adulte';
				elseif ($objp->s_age == 100) $line->PartAge	= 'Enfant';
				else if ($objp->s_age == -1 or empty($objp->s_age ) ) $line->PartAge	= '';
				else   $line->PartAge	= $objp->s_age. ' ans';	
				if (!($objp->s_taille == 'inutile' or $objp->s_taille == 'Inutile')) $line->PartTaille			= $objp->s_taille;
				if ( !empty($objp->fk_rdv)) {
					if ($line->inscrit	== 'A') $line->ActPartRdv = '';
					else 	$line->ActPartRdv	= $objp->fk_rdv ;
				}
				else 				$line->ActPartRdv	= '1';
				// personne recours  
				if (!empty($objp->fk_persrec))				{
					$line->pers_civ_lib			= $objp->RecCiv;
					if (!empty($objp->RecCiv)) 
							$line->pers_nom	= $objp->RecCiv.' ';
					else $line->pers_nom = '';
					if (!empty($objp->RecPreNom))
							$line->pers_nom	.= $objp->RecPreNom.' ';
					$line->pers_nom	.= $objp->RecNom;
				}
				if (!empty($objp->RecTel) and $objp->RecTel != 'null')  $line->pers_nom	.= ' - '.$objp->RecTel;
				$line->pers_prenom			= $objp->RecPreNom;
				
				$line->pu 				= $objp->pu;		
				if ($objp->rem != 0) $line->rem				= $objp->rem; ;
				// si Solde = pt alors : reste à payer = solde sinon ATTENTION
				$line->observation			= $objp->observation;
/*				if ($objp->type_session == 0) $pt = $objp->totaldepGrp ;
				elseif ($objp->type_session == 1) $pt = $objp->totaldepInd ;
				elseif (empty($objp->type_session )) $pt = $objp->totaldepInd ;
				else */ 
				$pt = $objp->totaldepInd ;
				if (empty($objp->totalrec)) $objp->totalrec = 0;
				$solde = price2num($pt, 'MT') - price2num($objp->totalrec, 'MT') - price2num($objp->totalboncad, 'MT');
				$strsolde = $solde;
				//if ( ($line->status_in_session != 6)) {
				if ($objp->PartPt != 0)  $line->pt = $objp->PartPt;
				if ($objp->pu == 0)  $line->rem	 = '';
				if (($solde > 0 or !empty($PmtFutur)) and empty($SuiviBullDefautPaiement[$objp->fk_bull] ))   					{
					if ($solde == $objp->PartPt) $line->observation .= 'Reste a payer :'.$solde.' euros';
					else $line->observation	.= ' - ATTENTION paiement a collecter au depart :'.$solde.' euros';
					if (!empty($objp->PmtFutur)) $line->observation	.= ' - Remarque :'. $objp->PmtFutur;
					$SuiviBullDefautPaiement[$objp->fk_bull] = 10;
				}	
				if ($line->pt == 0 ) 	$line->pt = '';
					
				$this->lines[$i] = $line;
				$i++;
				}			
			$this->db->free($result);
			return $i;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::fetch_lines '.$this->error,LOG_ERR);
			return -3;
		}
		unset ($SuiviBullDefautPaiement);
	} /* fetch_lines */

	/* stocke le nom du fichier Feuille de route dans agefodd_session_extrafields.s_feuilleroute 
	*/
	function updateFic($fichier)
	{		
		$this->ficsess = $fichier;
	
		$i=0;
        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session_extrafields SET ";
		$sql.= '  s_ficsess = "'.$fichier.'"';
		$sql.= " Where fk_object =  '".$this->id_act."'";
		$this->db->begin();
	   	dol_syslog(get_class($this)."::updateFic sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        
        // Commit or rollback
        if ($error)
		{
			dol_syslog(get_class($this)."::updateFic ".$errmsg, LOG_ERR);
	        $this->error.=($this->error?', '.$errmsg:$errmsg);
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id_act;
		}
	} //updateFic

}// Class FeuilleRoute

?>