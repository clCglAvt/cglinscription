<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com--->
 *
 * Version CAV 2.6.1.3
 * Version CAV - 2.7 été 2022
 *					 - vérification des conflit pour planning vélo (NbLocationParMateriel)
 * Version CAV - 2.8 - hiver 2023   - refonte de l'écran materiel loué
 *									- vérification de la  fiabilite des foreach
 *								- vérification des conflits pour planning vélo  (fl_conflitIdentmat)
 * Version CAV - 2.8.4 - printemps 2023
 *		- correction conflit location vélo (300)
 *		- ajout liste des contrats en conflit de location (304)
 * Version CAV - 2.8.5 - printemps 2023
 *		- vérification à la volée des conflit de vélo  (308b)
 *		- tri croissant des vélos de retour ce jour (evo 329)
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
 *	\file       htdocs/custum/cglinscription/class/bull.class.php
 *	\ingroup    societe
 *	\brief      File for third party class
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once './core/modules/cglinscription/doc/doc_feuilleroute_odt.modules.php';
require_once '../cglavt/class/cglFctCommune.class.php';

//Constantes

/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */
class AtelierPrep extends CommonObject
{
	public $element='atelier';
	public $table_element='cglinscription_bull';
	public $table_element_line = 'cglinscription_bull_det';
	public $fk_element = 'fk_bull';
	
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	
	var $lines = array();
	var $dateloc;
	
	
    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
		global $langs, $db, $search_Date;
        $this->db = $db;
        $this->dateloc = $search_Date;
		
        return 1;
    }/* __construct */
 
    /**
     *  Cherche les lignes du tableau du materiel loué
	 *
     *	$param bool $flTous	Vrai - liste tous les matériel loué - Faux, se limite aux matériels des LO non clos
     *  @return int          	<0 if KO, >0 if OK
	 *	renseigne le tableau AtelierPrep::lines
     */
    function fetch_lines($flTous=false )
    {
    	global $langs, $bull;
		global  $search_Date, $search_ref, $search_statut, $search_tiers, $search_mat, $search_serv, $search_refmat, $sortfield, $sortorder;
		global 	$search_date_aujourdhui ,	$search_date_demain,	$search_date_apresdemain;

		$w = new CglFonctionCommune($this->db);
		$now = $this->db->idate(dol_now('tzuser'));
/* requete simplifié pour l'obtention des jours )		
SELECT fk_materiel, datedeb, 
datefin, ADDDATE(datedeb, INTERVAL jour+1 DAY)  FROM 
(select fk_materiel , datedeb, datefin,  DATEDIFF(datefin, datedeb) as nbjour from locaton  ) as TB
, jour
 WHERE nbjour > jour
*/ 	
	
		
$sql ='';
	$sql .= 'SELECT * FROM ( ';
// Tous les matériels loués sur  jour courant
if (empty($search_Date))
{
	$sql .= 'SELECT  b.rowid as browid, bd.rowid as bdId, T.nom, T.rowid as id_client, Tf.nom as fournisseur, b.statut, bd.duree, b.regle , b.ref , b.ActionFuture as ObsPriv  ,';
	$sql .= "CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END as createur , b.dateretrait, b.datedepose , b.lieuretrait, b.lieudepose, materiel,  ";
	$sql .= " CONCAT(refmat, CONCAT(' - ', marque)) as refmat, refmat as identmat,marque, ";
 	$sql .= " p.label, bd.observation, bd.fk_activite, bd.NomPrenom, bd.taille ,";
	$sql .= '5 as fldate   , bd.fk_bull , b.dateretrait as ChpTridate , d.rowid as fk_dossier,  d.libelle as nomdossier  ';
	$sql .= 'FROM ' . MAIN_DB_PREFIX . 'cglinscription_bull as b   ';
	$sql .= '	LEFT JOIN ' . MAIN_DB_PREFIX . "cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X')  ";
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p on bd.fk_activite = p.rowid ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as T on b.fk_soc = T.rowid ';  
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as Tf on bd.fk_fournisseur = Tf.rowid   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u on u.rowid = fk_createur ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'cglavt_dossier as d on d.rowid = b.fk_dossier ';
	$sql .= " WHERE b.typebull = 'Loc'   ";
	$sql .= 'AND b.statut <  '.$bull->BULL_CLOS.' ';
	$sql .= 'AND !isnull(bd.rowid )  ';
	$sql .= 'AND    SUBSTRING(b.datedepose, 1, 10) >=  date("'.$now.'") ';
	
}
else {
// Tous les matériels en départ sur jour saisi
	$sql .= 'SELECT  b.rowid as browid,  bd.rowid  as bdId, T.nom, T.rowid as id_client, Tf.nom as fournisseur, b.statut, ';
	$sql .= ' bd.duree, b.regle , b.ref , b.ObsPriv  ,';
	$sql .= " CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END as createur ,";
	$sql .= '	b.dateretrait, b.datedepose , b.lieuretrait, b.lieudepose, materiel, CONCAT(refmat, ';
	$sql .= ' CONCAT(" - ", marque)) as refmat, refmat as identmat,marque,  p.label, bd.observation, fk_activite, ';
	$sql .= '   bd.NomPrenom, bd.taille , 2 as fldate , bd.fk_bull  , b.dateretrait as ChpTridate , d.rowid as fk_dossier,  ';
	$sql .= ' d.libelle as nomdossier  ';
	$sql .= 'FROM ' . MAIN_DB_PREFIX . 'cglinscription_bull as b   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . "cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X')   ";
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p on bd.fk_activite = p.rowid ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as T on b.fk_soc = T.rowid   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as Tf on bd.fk_fournisseur = Tf.rowid   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u on u.rowid = fk_createur ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'cglavt_dossier as d on d.rowid = b.fk_dossier ';
	$sql .= "WHERE b.typebull = 'Loc' ";
	if (!$flTous)	$sql .= 'AND b.statut <  '.$bull->BULL_CLOS.' ';
	$sql .= 'AND !isnull(bd.rowid )  ';
	$sql .= "AND (b.statut <> ".$bull->BULL_DEPART." ";
	$sql .= "AND SUBSTRING(b.dateretrait, 1, 10) = '".$w->transfDateMysql($search_Date)."' )" ;
	$sql .= " UNION \n";


// Tous les matériels sur contrat antérieur non clôs		
	$sql .= 'SELECT  b.rowid as browid, bd.rowid, T.nom, T.rowid as id_client, Tf.nom as fournisseur, b.statut, bd.duree, b.regle , ';
	$sql .= ' b.ref , b.ObsPriv  ,';
	$sql .= " CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END as createur ,";
	$sql .= '	b.dateretrait, b.datedepose , b.lieuretrait, b.lieudepose, materiel, CONCAT(refmat, CONCAT(" - ", marque)) as refmat, ';
	$sql .= ' refmat as identmat,marque,p.label, bd.observation, fk_activite, ';
	$sql .= '  bd.NomPrenom, bd.taille , 1 as fldate , bd.fk_bull   , b.datedepose as ChpTridate , d.rowid as fk_dossier , ';
	$sql .= '  d.libelle as nomdossier ';
	$sql .= 'FROM ' . MAIN_DB_PREFIX . 'cglinscription_bull as b   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . "cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X')   ";
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p on bd.fk_activite = p.rowid ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as T on b.fk_soc = T.rowid   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as Tf on bd.fk_fournisseur = Tf.rowid   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u on u.rowid = fk_createur ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'cglavt_dossier as d on d.rowid = b.fk_dossier ';
	$sql .= "WHERE b.typebull = 'Loc' ";
	$sql .= 'AND b.statut <  '.$bull->BULL_CLOS.' ';
	$sql .= 'AND !isnull(bd.rowid )  ';
	$sql .= "AND   SUBSTRING(b.datedepose, 1, 10) < '".$w->transfDateMysql($search_Date)."' ";
	$sql .= " UNION \n";
	
	
// Tous les matériels en retour sur jour saisi		
	$sql .= 'SELECT  b.rowid as browid, bd.rowid, T.nom, T.rowid as id_client, Tf.nom as fournisseur, b.statut, bd.duree, b.regle , ';
	$sql .= ' b.ref , b.ObsPriv  ,';
	$sql .= " CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END as createur ,";
	$sql .= '	b.dateretrait, b.datedepose , b.lieuretrait, b.lieudepose, materiel, CONCAT(refmat, CONCAT(" - ", marque)) as refmat, ';
	$sql .= ' refmat as identmat,marque,p.label, bd.observation, fk_activite, ';
	$sql .= '  bd.NomPrenom, bd.taille , 3 as fldate , bd.fk_bull   , b.datedepose as ChpTridate , d.rowid as fk_dossier,  ';
	$sql .= ' d.libelle as nomdossier ';
	$sql .= 'FROM ' . MAIN_DB_PREFIX . 'cglinscription_bull as b   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . "cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X')   ";
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p on bd.fk_activite = p.rowid ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as T on b.fk_soc = T.rowid   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as Tf on bd.fk_fournisseur = Tf.rowid   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u on u.rowid = fk_createur ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'cglavt_dossier as d on d.rowid = b.fk_dossier ';
	$sql .= "WHERE b.typebull = 'Loc' ";
	if (!$flTous)	$sql .= 'AND b.statut <  '.$bull->BULL_CLOS.' ';
	$sql .= 'AND !isnull(bd.rowid )  ';
	$sql .= "AND   SUBSTRING(b.datedepose, 1, 10) = '".$w->transfDateMysql($search_Date)."' ";
	$sql .= " UNION \n";
	
// Tous les matériels sortis sur jour saisi	
	$sql .= 'SELECT  b.rowid as browid, bd.rowid, T.nom, T.rowid as id_client, Tf.nom as fournisseur, b.statut, bd.duree, b.regle ,';
	$sql .= '  b.ref , b.ObsPriv  ,';
	$sql .= " CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END as createur ,";
	$sql .= '	b.dateretrait, b.datedepose , b.lieuretrait, b.lieudepose, materiel, CONCAT(refmat, CONCAT(" - ", marque)) as refmat, ';
	$sql .= ' refmat as identmat,marque,p.label, bd.observation, fk_activite, ';
	$sql .= '  bd.NomPrenom, bd.taille , 4 as fldate , bd.fk_bull  , b.datedepose as ChpTridate , d.rowid as fk_dossier ,';
	$sql .= '  d.libelle as nomdossier ';
	$sql .= 'FROM ' . MAIN_DB_PREFIX . 'cglinscription_bull as b   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . "cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X')  " ;
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p on bd.fk_activite = p.rowid ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as T on b.fk_soc = T.rowid   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as Tf on bd.fk_fournisseur = Tf.rowid   ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u on u.rowid = fk_createur ';
	$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'cglavt_dossier as d on d.rowid = b.fk_dossier ';
	$sql .= "WHERE b.typebull = 'Loc' ";
	if (!$flTous)	$sql .= 'AND b.statut <  '.$bull->BULL_CLOS.' ';
	$sql .= 'AND !isnull(bd.rowid )  ';
	$sql .= " AND   '".$w->transfDateMysql($search_Date)."'  between SUBSTRING(b.dateretrait, 1, 10)  and  SUBSTRING(b.datedepose, 1, 10) ";
	$sql .= "AND ((SUBSTRING(b.datedepose, 1, 10) <>  '".$w->transfDateMysql($search_Date)."' "; 
	$sql .= "AND SUBSTRING(b.dateretrait, 1, 10) <> '".$w->transfDateMysql($search_Date)."' )";
	$sql .= "OR (b.statut = ".$bull->BULL_DEPART." ";
	$sql .= "AND SUBSTRING(b.dateretrait, 1, 10) = '".$w->transfDateMysql($search_Date)."' ))" ;

}
	$sql .= ") as TB WHERE 1=1 ";
	if ($search_ref > 0){		
		$sql.= " AND browid =".$this->db->escape($search_ref);
	}
	if ($search_statut > 0){
		$sql.= " AND statut =".$this->db->escape($search_statut);
	}
	if ($search_tiers and !($search_tiers == -1)) { // search_tiers est à -1 quand on n'a pas fait de choix dur tiers à cause du select{
			$sql.= " AND id_client ='".$this->db->escape($search_tiers)."'";
	}
	
	if (!empty($search_mat) and $search_mat != -1) {			
		$sql.= " AND materiel ='".$this->db->escape($search_mat)."'";
	}
	if ($search_serv >0) {
		$sql.= " AND fk_activite ='".$this->db->escape($search_serv)."'";
	}
	if (!empty($search_refmat) and$search_refmat!= -1) {
		$sql.= " AND CONCAT(identmat, CONCAT(' - ', marque)) ='".$this->db->escape($search_refmat)."'";
	}
	
	if (empty($sortfield)) $sql .= ' ORDER BY fldate, ChpTridate,  nom, ref  '; 
	else {
		$sql.= $this->db->order($sortfield,$sortorder);
	}

	$resql = $this->db->query($sql);
	if ($resql	)  	{
		$num = $this->db->num_rows($resql);
		$i = 0;
		while ($i < $num){
             $obj = $this->db->fetch_object($resql);
			$line = New AtelierLines($this->db);
                $line->id   	= $obj->browid;
                $line->nom   = $obj->nom;
                $line->id_client   	= $obj->id_client;
                $line->statut   = $obj->statut;
                $line->regle   	= $obj->regle;
                $line->ref   = $obj->ref;
                $line->ObsPriv   = $obj->ObsPriv;
                $line->label   = $obj->label;
                $line->createur    = $obj->createur;
                $line->dateretrait  	= $obj->dateretrait;
                $line->heure  	=substr( $obj->dateretrait, 10,6);
                $line->duree  	= $obj->duree;				
                $line->datedepose   	= $obj->datedepose;
				$line->materiel	= $obj->materiel;
				$line->refmat		= $obj->refmat;
				$line->marque		= $obj->marque;
				$line->identmat		= $obj->identmat;
				$line->fk_service	= $obj->fk_activite;
				if (!empty($line->identmat)) { 
					$wloc =  new CglLocation($this->db);			
					$line->fl_conflitIdentmat	= $wloc->IsMatDejaLoue($line->fk_service, $line->identmat,
								$obj->	bdId, $line->dateretrait, $line->datedepose, $line->lstCntConflit);
					unset($wloc);
				}
				$line->fournisseur = $obj->fournisseur;		
				$line->observation = $obj->observation;

				$line->NomPrenom = $obj->NomPrenom;		
				$line->taille		= $obj->taille;
				$line->lieuretrait  = $obj->lieuretrait;
				$line->lieudepose		= $obj->lieudepose;
				$line->fldate = $obj->fldate;
				$line->fk_dossier = $obj->fk_dossier;
				$line->nomdossier = $obj->nomdossier;				
				$this->lines[] = $line;
				$j=1;
				$dynamique = 'obj->D'.$j;
				$i++; 
		} // while
        $this->db->free($resql);
		return $num;
	}
	else  {
		$this->error="Error ".$this->db->lasterror();
		dol_syslog(get_class($this)."::fetch lignes ".$this->error, LOG_ERR);
		return -1;
	}	
			
    } /* fetch*/

}// Class FeuilleRoute
class AtelierLines
{
	
	var $id  ;
	var $nom  ;
	var $id_client   ;
	var $statut  ;
	var $regle ;
	var $ref  ;
	var $ObsPriv   ;
	var $label;
	var $createur   ;
	var $dateretrait  ;
	var $datedepose   ;
	var $heure	;
	var $materiel	;
	var $refmat	;
	var $identmat;
	var $marque;
	var $observation ;
	var $duree; 
	var $fournisseur;
	
	var $NomPrenom ;
	var $taille	;
	var $activite_rdvSec	;
	var $lieuretrait ;
	var $lieudepose	;
	var $fldate;
	
	var $fk_dossier;	// lien au dossier de suivi
	var $nomdossier ;
				
	 function __construct($db)
    {
		global $langs, $db;
        $this->db = $db;
        return 1;
    }/* __construct */
 

} //AtelierLines

?>