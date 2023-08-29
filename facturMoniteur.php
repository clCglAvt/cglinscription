<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
* Copyright (C) 2012-2013       Florian Henry   <florian.henry@open-concept.pro>
 * Version CAV - 2.7 - été 2022
 *					 - Remplacer method="GET" par method="POST"
 *					 - Migration Dolibarr V15 et PHP7
 *
 * Version CAV - 2.7.1 automne 2022 - Enregistrement de la SI Montieur et élément de négonégo
 *									- amélioration des filtes date de la liste et sur site
 * Version CAV - 2.8 - hiver 2023 - Pagination (suppression Ajout)
 * 								  - vérification de la fiabilité des foreach
 *								  - bulletin technique
 *								  - filtre Prestation facturée
 *								  - affichage colonne à discretion - déplacement méthode
 *	Version CAV - 2.8.4 - automne 2023 - cohérence entre requete et filtre après utilisationloupe RAZ (bug 233)
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
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

/**
 * \file cglinscription/facturMoniteur.php
 * \ingroup copie de agefodd
 * \brief list of session
 */

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die ( "Include of main fails" );
require_once ('../agefodd/class/agsession.class.php');
require_once (DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php');
require_once ('../agefodd/lib/agefodd.lib.php');
require_once ('./class/cgldepart.class.php');
require_once ('./class/html.formdepart.class.php');
require_once (DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctDolibarrRevues.class.php');
require_once (DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctCommune.class.php');
require_once (DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php');

/*

require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
require_once ('../agefodd/class/agefodd_formation_catalogue.class.php');
//require_once ('../agefodd/class/agefodd_place.class.php');
require_once ('./class/site.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once ('../agefodd/class/agefodd_formateur.class.php');
*/

$langs->load("cglinscription@cglinscription");


global $prix_venteTTC0 ;
global $MargeHT0 ;
global $NbPart0 ;
global $NbEnfant0 ;
global $NbAdulte0 ;
global $NbPlace0 ;
global $PrixMoyen0 ;
		
// Security check
// à remplacer par un acces cglinscription
//if (! $user->rights->agefodd->lire) 	accessforbidden ();

$sortorder = GETPOST ( 'sortorder', 'alpha' );
$sortfield = GETPOST ( 'sortfield', 'alpha' );
$page = GETPOST ( 'page', 'int' );
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;


// Search criteria
$search_moniteur_id = GETPOST ( "search_moniteur_id" , 'int');
if ($search_moniteur_id == -1) $search_moniteur_id = '';
$search_annee = GETPOST ( 'search_annee', 'alpha' );
if (empty($search_annee  )) $search_annee = dol_print_date(dol_now('tzuser'), '%Y');
$search_mois =  GETPOST ( 'search_mois', 'alpha' );
$search_semaine =  GETPOST ( 'search_semaine', 'int' );
$search_date_startday = GETPOST('search_date_startday', 'int');
$search_date_startmonth = GETPOST('search_date_startmonth', 'int');
$search_date_startyear = GETPOST('search_date_startyear', 'int');
$search_date_endday = GETPOST('search_date_endday', 'int');
$search_date_endmonth = GETPOST('search_date_endmonth', 'int');
$search_date_endyear = GETPOST('search_date_endyear', 'int');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_site =  GETPOST ( 'search_site', 'alpha' );
$action =  GETPOST ( 'action', 'alpha' );
$search_prest_facture = GETPOST("search_prest_facture");

$contextpage='facturMoniteur';

// Do we click on purge search criteria ?
if (GETPOST ( "button_removefilter_x" , 'alpha')) {
	$search_moniteur_id = "";
	$search_annee =   dol_print_date(dol_now('tzuser'), '%Y');;
	$search_mois = "";
	$search_semaine = "";
	$search_date_startday = '';
	$search_date_startmonth = '';
	$search_date_startyear = '';
	$search_date_endday = '';
	$search_date_endmonth = '';
	$search_date_endyear = '';
	$search_date_start = '';
	$search_date_end = '';
	$search_site = "";
	$search_prest_facture = "";
	
}

// COLONNES A DISCRETION UTILISATEUR
// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'Site'=>"Site",
	'intitule_custo'=>'NomDepart',
	'intitule'=>'NomProduit',
	'dated'=>'dated',
	'heured'=>'HeureD',
	'duree'=>'duree',
	's_TypeTVA'=>"StatutFiscal",
	'partmonitBrute'=>"PartMonitBrute",
	'MargeBrute'=>"MargeBrute",
    "MargeHT"=>"margeHT",
	'ListBull'=>'ListBull',
	'FactureTTC'=>"FctTTC",
    'FactureHT'=>"FctHT",
    'FacturePayee'=>"FctPay",
    'TVACollectee'=>"TVAColl",
    'TVACollecteeSurCommissionnement'=>"TVACollecteeSurCommissionnement",
	'nb_stagiaire'=>'Nbs',
	'NbEnfant'=>'NbEnf',
	'Statut'=>'Statut'
);

$arrayfields=array(
    'Site'=>array('label'=>$langs->trans("SITE"), 'checked'=>0),
	'intitule_custo'=>array('label'=>$langs->trans("NomDepart"), 'checked'=>0),
	'intitule'=>array('label'=>$langs->trans("NomProduit"), 'checked'=>0),
	'heured'=>array('label'=>$langs->trans("HeureD"), 'checked'=>0),
	'dated'=>array('label'=>$langs->trans("dated"), 'checked'=>0),
	'duree'=>array('label'=>$langs->trans("Duree"), 'checked'=>1),
    's_TypeTVA'=>array('label'=>$langs->trans("StatutFiscal"), 'checked'=>1),
    'partmonitBrute'=>array('label'=>$langs->trans("PartMonitBrute"), 'checked'=>1),
    'MargeBrute'=>array('label'=>$langs->trans("MargeBrute"), 'checked'=>0),
    'MargeHT'=>array('label'=>$langs->trans("MargeHT"), 'checked'=>1),
	'listBull'=>array('label'=>$langs->trans("ListBull"), 'checked'=>0),
    'FactureTTC'=>array('label'=>$langs->trans("FctTTC"), 'checked'=>0),
    'FactureHT'=>array('label'=>$langs->trans("FctHT"), 'checked'=>0),
    'FacturePayee'=>array('label'=>$langs->trans("FctPay"), 'checked'=>0), 
    'TVACollectee'=>array('label'=>$langs->trans("TVAColl"), 'checked'=>1),
    'TVACollecteeSurCommissionnement'=>array('label'=>$langs->trans("TVACollecteeSurCommissionnement"), 'checked'=>1),
	'nb_stagiaire'=>array('label'=>$langs->trans("Participants"), 'checked'=>1),
	'NbEnfant'=>array('label'=>$langs->trans("NbEnf"), 'checked'=>0),	
	'Statut'=>array('label'=>$langs->trans("Statut"), 'checked'=>0)
);



include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

/*	
if ($action == 'enregistre') {
	//print '<br><br><br>>br><br><br><br><br>ENREGISTER*********************************************************<br>';
	// récuprer s_partmonit_nego et s_pourcent_nego et Facture et id sessiin
	// enregistrer
	$partmonit = array();
	$pourcent = array();
	$facture = array();
	$partmonit = GETPOST('s_partmonit_nego', 'array');
	$pourcent = GETPOST('s_pourcent_nego', 'array');
	$facture = GETPOST('Facture', 'array');
	$rowid = GETPOST('rowid', 'array');
	
 	require_once('class/cgldepart.class.php');
	$w= new CglDepart($db);
	$erreur =0;
	foreach ($rowid as $key=>$id) {	
		$ret = $w->EnrNego($id, $partmonit[$key], $pourcent[$key], $facture[$key], 1 );
		if ($ret == -1) $erreur--;
	}	
	unset($w);	
	return $ret;
}
*/
	function fetch_all( $sortorder, $sortfield, $limit, $offset, $filter = array()) {

		global $langs, $type, $agf, $search_annee, $search_mois, $search_semaine;		
		
		global $prix_venteTTC0 ;
		global $MargeHT0 ;
		global $NbPart0 ;
		global $NbEnfant0 ;
		global $NbAdulte0 ;
		global $NbPlace0 ;	
		global $PrixMoyen0 ;		
		
		// REQUETE POUR CALCUL FACTURE Moniteur	
		$sql = "select  s.rowid as IdSession, st.rowid as IdTiers, p.ref_interne as Site,s.rowid as id_act, 	s.type_session,  s.nb_place,";
		$sql .= "   nb_stagiaire, s.intitule_custo, s.status,  NbAdulte, NbEnfant, PrixVente as prix_venteTTC , s.dated ,nom, se.s_pourcent, se.s_partmonit, ";
		$sql .= " m.rowid as IdMonit,	NomMoniteur,	 semaine,  pr.intitule,  "."\n";
		
		$sql .= "  ( select min(heured) from     ".MAIN_DB_PREFIX."agefodd_session_calendrier as cal where  cal.fk_agefodd_session = s.rowid ) as heured , "."\n";
/*		$sql .= "  	case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
				else 
					case when s_partmonit > 0   then  s_partmonit 
						else 
							case when (isnull(s_partmonit) or s_partmonit = 0)  and tva_assuj = 1 then  PrixVente * (100/120) *  s_pourcent /100 
							else 
								case when (isnull(s_partmonit) or s_partmonit = 0)  and tva_assuj = 0  then PrixVente *  s_pourcent  / 100								 
				end  end end end as CoutMonitHT,
			  "."\n";
*/
		$sql .= " case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
			else 
				case when s_partmonit > 0   then  s_partmonit 
					else 
						case when (isnull(s_partmonit) or s_partmonit = 0)  then PrixVente *  s_pourcent  / 100
							 
			end   end end as partmonitBrute,  "."\n";
		$sql .= " PrixVente -  case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
			else 
				case when s_partmonit > 0   then  s_partmonit 
					else 
						case when (isnull(s_partmonit) or s_partmonit = 0)  then PrixVente *  s_pourcent  / 100							 
			end   end end as MargeBrute,   "."\n";
		$sql .= "  case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
			else 
				case when s_partmonit > 0    and tva_assuj = 1 then  s_partmonit 
						else 
							case when s_partmonit > 0   and tva_assuj = 0  then  s_partmonit
					else 
						case when (isnull(s_partmonit) or s_partmonit = 0)  and tva_assuj = 1 then  PrixVente  *  s_pourcent / 100 
						else 
							case when (isnull(s_partmonit) or s_partmonit = 0)  and tva_assuj = 0  then PrixVente *  s_pourcent  / 100							 
			end  end end end end as CoutMonitHT,  "."\n";
		$sql .= "  case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
			else 
				case when s_partmonit > 0  and tva_assuj = 1  then PrixVente /1.2 - s_partmonit
				else 
					case when s_partmonit > 0 and tva_assuj = 0  then (PrixVente - s_partmonit )/1.2 
					else 
						case when (isnull(s_partmonit) or s_partmonit = 0 )  and tva_assuj = 0  then  PrixVente * 	(1  -  s_pourcent/100) / 1.2
				else 
				case when (isnull(s_partmonit) or s_partmonit = 0)   and tva_assuj = 1   then PrixVente / 1.2 - PrixVente *  s_pourcent/100 					 
				end	end end end end  as MargeHT,	 "."\n";
			
		$sql .= " case when not isnull(f.ref) then f.ref
			else case when not isnull(fLib.ref) then fLib.ref
			else se.s_ref_facture
			end end as Facture,  "."\n";
	
		
		
		$sql .= "  case when  not isnull(f.ref) then f.total_ttc
			else case when not isnull(fLib.ref) then fLib.total_ttc
			else 0		
			end end as FactureTTC ,  "."\n";
		$sql .= "  case when  not isnull(f.ref) then f.total_ht
			else case when not isnull(fLib.ref) then fLib.total_ht
			else 0	
		end end as FactureHT , "."\n";
		
		
		
		$sql .= "  case when  not isnull(f.ref) then f.rowid
			else case when not isnull(fLib.ref) then fLib.rowid
			else 0		
			end end as FactureID ,  "."\n";
		
		
		$sql .= " case when  not isnull(f.ref) then f.total_tva
			else case when not isnull(fLib.ref) then fLib.total_tva
			else ''			
		end end as FactureTVA,   "."\n";
		$sql .= " case when  not isnull(f.ref) then f.paye
			else case when not isnull(fLib.ref) then fLib.paye
			else ''			
		end end as FacturePayee,   "."\n";
		$sql .= "  st.tva_assuj, se.s_TypeTVA , se.s_duree_act  "."\n";
		$sqltable = '';
		$sqltable .= "  FROM  ".MAIN_DB_PREFIX."agefodd_formateur  as m "."\n";
				
		$sqltable .= "  	LEFT JOIN 	 (select mi.rowid	, concat(concat(mi.lastname, ' '),mi.firstname) as NomMoniteur	
				from  ".MAIN_DB_PREFIX."socpeople as mi  ) as mic on mic.rowid = m.fk_socpeople  "."\n";
		$sqltable .= " LEFT JOIN    ".MAIN_DB_PREFIX."agefodd_session_formateur as sm on m.rowid = sm.fk_agefodd_formateur  "."\n";
		$sqltable .= "  LEFT JOIN 	 ".MAIN_DB_PREFIX."societe as st on st.rowid = m.fk_soc   "."\n";
		$sqltable .= '  LEFT JOIN	(
			
			select  s1.rowid, s1.intitule_custo, s1.fk_formation_catalogue, s1.type_session, s1.dated,s1.fk_session_place,  	s1.nb_place, s1.nb_stagiaire, s1.status, DATE_FORMAT(s1.dated,"%u") as semaine ,  (select sum(qte) from  	 '.MAIN_DB_PREFIX.'cglinscription_bull as b 
				LEFT JOIN	 '.MAIN_DB_PREFIX.'cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ("S","X") and bd.type = 0 
				where  bd.fk_activite = s1.rowid and  (bd.age <= 12 or bd.age = 100) and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )))			as NbEnfant,
			(select sum(qte) from  	 '.MAIN_DB_PREFIX.'cglinscription_bull as b 
				LEFT JOIN	 '.MAIN_DB_PREFIX.'cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ("S","X") and bd.type = 0
				where  bd.fk_activite = s1.rowid and  (bd.age > 12 or bd.age = 99) and age <> 100 and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )))
				as NbAdulte,
			(select sum(qte * pu * (100-bd.rem)/100 ) from  	 '.MAIN_DB_PREFIX.'cglinscription_bull as b 
				LEFT JOIN	 '.MAIN_DB_PREFIX.'cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ("S","X") and bd.type = 0
				where  bd.fk_activite = s1.rowid  and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )) )			as PrixVente			
			from  '.MAIN_DB_PREFIX.'agefodd_session as s1) as s  on s.rowid = sm.fk_session '."\n";
		$sqltable .= "  LEFT JOIN	 ".MAIN_DB_PREFIX."agefodd_place as p on s.fk_session_place = p.rowid "."\n";
		$sqltable .= "  LEFT JOIN    ".MAIN_DB_PREFIX."agefodd_session_extrafields as se on se.fk_object = s.rowid "."\n";
		$sqltable .= "  LEFT JOIN    ".MAIN_DB_PREFIX."agefodd_formation_catalogue as pr on pr.rowid = s.fk_formation_catalogue "."\n";
		$sqltable .= " LEFT JOIN 	 ".MAIN_DB_PREFIX."facture_fourn as f on f.rowid = se.s_fk_facture  "."\n";
		$sqltable .= " LEFT JOIN 	 ".MAIN_DB_PREFIX."facture_fourn as fLib on fLib.ref = se.s_ref_facture  "."\n";
		$sqltable .= " WHERE 1=1 	";
		$sql .=$sqltable;
 		// Manage filter
		if (count ( $filter ) > 0) {
			foreach ( $filter as $key => $value ) {
				if (strpos ( $key, 'date' )) 				// To allow $filter['YEAR(s.dated)']=>$year
				{
					$sqlfilter .= ' AND ' . $key . ' = \'' . $value . '\'';
				} elseif ( ($key == 'm.rowid')  || ($key == 's.status') || ($key == 'p.rowid')) {
					$sqlfilter .= ' AND ' . $key . ' = ' . $value;
				} elseif  ($key == 's.type_session') {
					if ($value == 1) $sqlfilter .= ' AND ( s.type_session = 1 or isnull(s.type_session))';
						else $sqlfilter .= ' AND ' . $key . ' = ' . $value;
				} elseif  ($key == 'plage') {
					$sqlfilter .= $value;
				} elseif  ($key == 'facture') {
					if ($value == 1) 
						$sqlfilter .= ' AND f.rowid is not null';
					if ($value == 2) 
						$sqlfilter .= ' AND f.rowid is null';
				} else {
					$sqlfilter .= ' AND ' . $key . ' LIKE \'%' . $agf->db->escape ( $value ) . '%\'';
				}
				
			}
		}
		$sql .= $sqlfilter;

		if ( empty($sortfield)) $sql .= " ORDER BY dated desc";
		$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		
		if (! empty ( $limit )) {
			$sql .= ' ' . $agf->db->plimit ( $limit + 1, $offset );
		}
		dol_syslog ( 'factureMoniteur::fetch_all' , LOG_DEBUG );
		$resql = $agf->db->query ( $sql );
		if ($resql) {
			$agf->lines = array ();
			
			$num = $agf->db->num_rows ( $resql );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {					
					$obj = $agf->db->fetch_object ( $resql );	
					if ('transfert' == 'transfert') {
					$line = new AgfSessionLine ();	
					$line->rowid = $obj->IdSession;
					$line->SocMoniteur = $obj->IdTiers;
					$line->activite_lieu = $obj->Site ;
					$line->id_act = $obj->id_act ;
					$line->type_session = $obj->type_session;
					
					$line->s_TypeTVA = $obj->tva_assuj;					
					if ( $obj->tva_assuj == 1) $line->valeurTauxTva="TVA";
					elseif ( $obj->tva_assuj == 0) $line->valeurTauxTva="Com";
					else $line->valeurTauxTva="";	
					if (empty($line->nb_stagiaire)) $line->nb_stagiaire = 0;
					$dated = new DateTime($obj->dated);
					$st_dated = $dated->format('d/m/Y');
					$line->dated = $agf->db->jdate ( $obj->dated )
					;
					$line->duree = $obj->s_duree_act;
					$line->s_pourcent_nego = $obj->s_pourcent;
					if ($line->s_pourcent_nego == 0 ) $line->s_pourcent_nego = '';
					$line->s_partmonit_nego = $obj->s_partmonit;
					if ($line->s_partmonit_nego == 0 ) $line->s_partmonit_nego = '';
					
					$line->prix_venteTTC = $obj->prix_venteTTC;
					if ($line->prix_venteTTC == 0 ) $line->prix_venteTTC = '';
					$line->partmonitBrute = $obj->partmonitBrute;
					if ($line->partmonitBrute == 0 ) $line->partmonitBrute = '';

					$line->MargeBrute = $obj->MargeBrute;
					if ($line->MargeBrute == 0 ) $line->MargeBrute = '';
					$line->CoutMonitHT = $obj->CoutMonitHT;
					if ($line->CoutMonitHT == 0 ) $line->CoutMonitHT = '';
					$line->MargeHT = $obj->MargeHT;
					if ($line->MargeHT == 0 ) $line->MargeHT = '';
					$line->FactureID = $obj->FactureID;
					$line->FactureTTC = $obj->FactureTTC;
					if ($line->FactureTTC == 0 ) $line->FactureTTC = '';
					$line->FactureHT = $obj->FactureHT;
					if ($line->FactureHT == 0 ) $line->FactureHT = '';
					$line->FactureTVA = $obj->FactureTVA;
					if ($line->FactureTVA == 0 ) $line->FactureTVA = '';
					if ($obj->FacturePayee == 1) $line->FacturePayee = 'oui';
					elseif ($obj->FacturePayee == 0 and !empty($line->FactureID ))   $line->FacturePayee = 'non';
					else  $line->FacturePayee = '';						
					
					$line->status = $obj->status;					
					//$line->intitule = $obj->intitule;
					$line->intitule = $obj->intitule_custo;
					$line->intitule_custo = $obj->intitule_custo;	
					$line->heured = $obj->heured;										
					$line->tva_assuj = $obj->tva_assuj;
					
					$line->trainerrowid = $obj->IdMonit;
					$line->fullname_contact = $obj->NomMoniteur;
														
					$line->nb_place = $obj->nb_place;
					if (empty($line->nb_place)) $line->nb_place = 0;
					//$line->nb_stagiaire = $obj->nb_stagiaire;			
					$line->NbEnfant = $obj->NbEnfant;	
					if (empty($line->NbEnfant)) $line->NbEnfant = 0;					
					$line->NbAdulte = $obj->NbAdulte;
					if (empty($line->NbAdulte)) $line->NbAdulte = 0;		
					$line->nb_stagiaire = $line->NbEnfant + $line->NbAdulte;
										
/*
					$timelimite = dol_time_plus_duree(dol_now(),-1,'d');
					$now = new DateTime();
					$d = $now->format('d');
					$d = $d-1;
					$l = strlen( '0'.$d) - 2;
					$d = substr ('0'.$d, $l);
					$st_timelimite  = $now->format('Y').$now->format('m').$d;
*/					
				} // transfert
					$agf->lines [$i] = $line;
					$i ++;
				}
			}
			
			// TOTAUX
			if (empty( $offset)) {
				$sql = 'SELECT  sum(PrixVente) as prix_venteTTC , sum(nb_place) as NbPlace ,sum(NbAdulte) as NbAdulte, sum(NbEnfant) as NbEnfant, ';		
				$sql .= "  sum(case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
							else 
								case when s_partmonit > 0  and tva_assuj = 1  then PrixVente /1.2 - s_partmonit
								else 
									case when s_partmonit > 0 and tva_assuj = 0  then (PrixVente - s_partmonit )/1.2 
									else 
										case when (isnull(s_partmonit) or s_partmonit = 0 )  and tva_assuj = 0  then  PrixVente * 	(1  -  s_pourcent/100) / 1.2
								else 
								case when (isnull(s_partmonit) or s_partmonit = 0)   and s_TypeTVA = 1   then PrixVente / 1.2 - PrixVente *  s_pourcent/100 					 
								end	end end end end)  as MargeHT	 "."\n";
				$sql .= $sqltable;
				$sql .= $sqlfilter	;
				dol_syslog ( get_class ( $agf ) . "::fetch_all - totaux " , LOG_DEBUG );

				$resql = $agf->db->query ( $sql );
				if ($resql) {
					$obj = $agf->db->fetch_object ( $resql );					
					$prix_venteTTC0 = $obj->prix_venteTTC;
					$MargeHT0 = $obj->MargeHT;
					$NbEnfant0  = $obj->NbEnfant;
					$NbAdulte0  = $obj->NbAdulte;
					$NbPart0 = $NbEnfant0 + $NbAdulte0;
					$NbPlace0 = $obj->NbPlace;
					if (!empty($NbPart0)) $PrixMoyen0 = $prix_venteTTC0 / $NbPart0;
					else $PrixMoyen0 = '';
				}
			}
			$agf->db->free ( $resql );
			return $num;
				
		} else {
			$agf->error = "Error " . $agf->db->lasterror ();
			dol_syslog ( get_class ( $agf ) . "::fetch_all " . $agf->error, LOG_ERR );
			return - 1;
		}
	} //fetch_all
	
	function select_moniteur ($agf,  $selectid, $htmlname, $filter1 = '', $showempty = 0, $forcecombo = 0, $event = array())
	{
		global $langs, $db, $conf;
	
		// On recherche les societes
		$sql = "SELECT distinct m.rowid,  concat(concat(mi.lastname, ' '),mi.firstname) as nom";
		$sql.= " FROM ".MAIN_DB_PREFIX ."agefodd_formateur  as m";
		$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX ."socpeople as mi  ON  mi.rowid = m.fk_socpeople  ";		
		$sql.= " LEFT JOIN    ".MAIN_DB_PREFIX."agefodd_session_formateur as sm on m.rowid = sm.fk_agefodd_formateur ";
		$sql.= " LEFT JOIN    ".MAIN_DB_PREFIX."agefodd_session as s on s.rowid = sm.fk_session ";		
		$sql.= " LEFT JOIN	  ".MAIN_DB_PREFIX."agefodd_place as p on s.fk_session_place = p.rowid ";
	
		$sql.= " WHERE  m.entity IN (".getEntity('societe', 1).")";
		if ($filter1) $sql.= " AND (".$filter1.")";
		
		$sql.=$db->order("nom","ASC");
        dol_syslog("::select_moniteur ", LOG_DEBUG);
        $resql=$db->query($sql);
		
		$out = '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '" '.$event.' >';
		if ($showempty)
			$out .= '<option value="-1"></option>';
		$listmonit= array();
		
        if ($resql)       {
			 $num = $db->num_rows($resql);
            $i = 0;
            if ($num)            {
                while ($i < $num)                {
                    $obj = $db->fetch_object($resql);   
		
					$label = $obj->nom;
					$ftrouve=false;
					for($i=0; $i<count($listmonit);$i++)					
						if ($listmonit[$i] == $label) { $fltrouve = true; break; }
					//if ( $fltrouve == false) 
					if ($i == count($listmonit))
					{
						$listmonit[] = 	$label	;	
						if ($selectid > 0 && $selectid == $obj->rowid) {
								$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
						} else {
								$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
						}
					}
				}
			}
		$out .= '</select>';
		}
		
        else
        {
            dol_print_error($db);
        }
		return $out;
				
		
	} //select_moniteur

	/**
	 * affiche un champ select contenant la liste des sites de formation impactés par les filtres demandés
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */

	function select_site($selectid, $htmlname = 'place', $showempty = 0, $forcecombo = 0, $filtre = '')
	{
		global $conf, $langs, $db;
		
		$sql = "SELECT distinct p.rowid, p.ref_interne";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql.= " LEFT JOIN    ".MAIN_DB_PREFIX."agefodd_session as s on s.fk_session_place = p.rowid ";	
		$sql.= " LEFT JOIN    ".MAIN_DB_PREFIX."agefodd_session_formateur as sm on s.rowid = sm.fk_session ";
		$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX ."agefodd_formateur  as m on m.rowid = sm.fk_agefodd_formateur";		
		$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX ."socpeople as mi  ON  mi.rowid = m.fk_socpeople ";			
		$sql .= " WHERE p.archive = 0";
		$sql .= " AND p.entity IN (" . getEntity('agsession') . ")";
		if (!empty($filtre)) $sql .= ' AND ' . $filtre;
		$sql .= " ORDER BY p.ref_interne";

		dol_syslog("::select_site " , LOG_DEBUG);
		$result = $db->query($sql);
		
		if ($result) {
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '" ';
			if(!empty($event)) $out .= $event;
			$out .= '>';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $db->fetch_object($result);
					$label = $obj->ref_interne;
					
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$db->free($result);
			return $out;
		} else {
			$error = "Error " . $db->lasterror();
			dol_syslog( "::select_site_forma " . $error, LOG_ERR);
			return - 1;
		}
	}

	function select_facture_moniteur ( $selectid, $htmlname, $htmlid, $filter1 = '', $showempty = 0, $forcecombo = 0, $event = array())
	{
		global $langs, $db, $conf;
	
		// On recherche les societes
		$sql = "SELECT distinct f.rowid,  ref, ref_supplier, paye ";
		$sql.= " FROM ".MAIN_DB_PREFIX ."facture_fourn as f";	
		
		$sql.= " WHERE  f.entity IN (".getEntity('societe', 1).")";
		if ($filter1) $sql.= " AND (".$filter1.")";
		$sql.= " ORDER BY paye, ref DESC";
		//$sql.=$db->order("ref","desc");
        dol_syslog("::select_facture_moniteur ", LOG_DEBUG);
        $resql=$db->query($sql);
		
		$out = '<select id="' . $htmlid . '" class="flat" name="' . $htmlname . '" '.$event.' >';
		if ($showempty)
			$out .= '<option value="-1"></option>';
		$listmonit= array();
		
        if ($resql)       {
			 $num = $db->num_rows($resql);
            $i = 0;
            if ($num)            {
                while ($i < $num)                {
                    $obj = $db->fetch_object($resql);   
					if ($obj->paye == 1) $strpaye = '(payée)'; else $strpaye = '';
					$label = $obj->ref . ' -- ' . $obj->ref_supplier . ' '.$strpaye.'';
					if ($selectid > 0 && $selectid == $obj->rowid) {
							$out .= '<option value="' . $obj->rowid . '" selected="selected" >' . $label . '</option>';
					} else {
							$out .= '<option value="' . $obj->rowid . '" >' . $label . '  </option>';
					}	
					$i++;					
				}
			}
		$out .= '</select>';
		}
		
        else
        {
            dol_print_error($db);
        }
		return $out;
				
		
	} //select_facture_moniteur

    function getNomUrl($withpicto=0,$option='',$maxlen=0, $id, $label = '', $paramborder = '')
    {
        global $conf,$langs;

        $result='';
		$lienfin='</a>';

		if ($option == 'Depart')		{
			$result = '<a href="./fichedepart.php?id='.$id.'&total=oui" >' ;
			$result .= '<img border = 0 title="Depart" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Voir").'">';		}	
		elseif ($option == 'Bulletin')		{
			$result = '<a href="./inscription.php?id_bull='.$id.'" >' ;
			if (empty($paramborder)) 
					$result .= '<img border = 0 title="'.$langs->trans($label).'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans($label).'">';	
				else 
					$result .= '<img '.$paramborder.' title="'.$langs->trans($label).'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans($label).'">';
				}	
		elseif ($option == 'Contrat')		{
				$result = '<a href="./location.php?id_bull='.$id.'" >' ;
			if (empty($paramborder)) 
				$result .= '<img border = 0 title="'.$langs->trans($label).'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans($label).'">';
			else 
				$result .= '<img '.$paramborder.' title="'.$langs->trans($label).'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans($label).'">';
		}	 
		elseif ($option == 'Moniteur')		{
				$result = '<a href="./fiche_moniteur.php?id='.$id.'" >' ;
				$result .= '<img border = 0 title="'.$langs->trans($label).'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans($label).'">';
		}	 
       $result.=$lienfin;
       return $result;
	}//getNomUrl
	
	function CorrDonnesTVA()
	{
		global $db;
		
		$sql = "UPDATE llx_agefodd_session_extrafields as se "; 
		$sql .= " SET se.s_TypeTVA =  ";
		$sql .= " (select tva_assuj  ";
		$sql .= " 	FROM  ";
		$sql .= " 	 ".MAIN_DB_PREFIX ."agefodd_formateur  as m   ";
		$sql .= " 		LEFT JOIN     ".MAIN_DB_PREFIX ."agefodd_session_formateur as sm on m.rowid = sm.fk_agefodd_formateur  ";
		$sql .= " 		LEFT JOIN  ".MAIN_DB_PREFIX ."socpeople as cf on cf.rowid = m.fk_socpeople ";
		$sql .= " 		LEFT JOIN  ".MAIN_DB_PREFIX ."societe as socf on socf.rowid = cf.fk_soc  ";
		$sql .= " 	WHERE  se.fk_object = sm.fk_session  ";
			 
			 
		$sql .= " ) ";
		$sql .= " WHERE ( isnull(s_TypeTVA)  or s_TypeTVA = '')  ";
		$sql .= " and  exists (select (1)  ";
		$sql .= " 	FROM   llx_agefodd_session_formateur as sm    ";
		$sql .= " 	WHERE  se.fk_object = sm.fk_session  ";
		$sql .= " ) ";		
        
        $resql=$db->query($sql);
		$num = $db->affected_rows($resql);
		if ($num > 0) dol_syslog("::CorrDonnesTVA nb TVA valorisées=".$num, LOG_DEBUG);
			
	} //CorrDonnesTVA
	
	function select_prest_facture($selected, $htmlname)
	{
		global $db; 
		
		$option = array();
		$option = array("1"=>"facturee", "2"=>"non facturee");
		$wfct = new CglFonctionCommune($db);
		return $wfct->select_($selected , $htmlname , $option, 0, 1);
	} // select_prest_facture
	
	
$filter = array ();
$filterbull = array ();
if (! empty ( $search_moniteur_id )) {
	$filter ['m.rowid'] = $search_moniteur_id;
	$filterbull ['ms.fk_agefodd_formateur'] = $search_moniteur_id;
}

if (! empty ( $search_prest_facture )) {
		$filter ['facture']= $search_prest_facture;
		$filterbull ['facture']= $search_prest_facture;	
}

if (! empty ( $search_annee )) {
	$filter ['year(dated)'] =  $search_annee ;
	$filterbull ['year(dated)'] =  $search_annee ;
}

if (! empty ( $search_mois )) {
	$filter ['month(dated)'] = $search_mois ;
	$filterbull ['month(dated)'] = $search_mois ;
}
if (! empty ( $search_semaine )) {
	$filter ['week(dated)'] = $search_semaine ;
	$filterbull ['week(dated)'] = $search_semaine ;
}
if ($search_date_start  and $search_date_end) {
	$filter ['plage']= " AND dated between '". $db->idate($search_date_start)."' AND '".$db->idate($search_date_end)."' ";
	$filterbull ['plage']= " AND dated between '". $db->idate($search_date_start)."' AND '".$db->idate($search_date_end)."' ";
}
if ($search_site  and $search_site <> -1 ) {
	$filter ['p.rowid']= $search_site;
	$filterbull ['p.rowid']= $search_site;
}

/*
if (! empty ( $status_view )) {
	$filter ['s.status'] = $status_view;
}
*/
if (empty ( $sortorder ))
	$sortorder = "DESC";
if (empty ( $sortfield ))
	$sortfield = "dated";
if (empty ( $arch ))
	$arch = 0;

if ($page == - 1) {
	$page = 0;
}

$offset = $limit * (int)$page;

$pageprev = (int)$page - 1;
$pagenext = (int)$page + 1;

$form = new Form ( $db );
$wfdep = new FormCglDepart($db);


/* préparation de la navigation */
if ('navigation' == 'navigation')  
{
/*
   $arrayofmassactions = array();
    $massactionbutton = $form->selectMassAction('', $arrayofmassactions);
*/

/* a supprimer après V2.8
	$newcardbutton = '';
	if ($action != 'addline' && $action != 'reconcile')
	{
		if (empty($conf->global->BANK_DISABLE_DIRECT_INPUT))
		{
			if (empty($conf->global->BANK_USE_OLD_VARIOUS_PAYMENT))	// If direct entries is done using miscellaneous payments
			{
			    $newcardbutton = dolGetButtonTitle($langs->trans('AddBankRecord'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create&accountid='.$search_account.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.urlencode($search_account)), '', $user->rights->banque->modifier);
			}
			else												// If direct entries is not done using miscellaneous payments
			{
                $newcardbutton = dolGetButtonTitle($langs->trans('AddBankRecord'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=addline&page='.$page.$param, '', $user->rights->banque->modifier);
			}
		}
		else
		{
            $newcardbutton = dolGetButtonTitle($langs->trans('AddBankRecord'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=addline&page='.$page.$param, '', -1);
		}
	}
*/
	$morehtml = '<div class="inline-block '.(($buttonreconcile || $newcardbutton) ? 'marginrightonly' : '').'">';
//	$morehtml .= '<label for="pageplusone">'.$langs->trans("Page")."</label> "; // ' Page ';
//	$morehtml .= '<input type="text" name="pageplusone" id="pageplusone" class="flat right width25 pageplusone" value="'.((int)$page + 1).'">';
//	$morehtml .= '/'.$nbtotalofpages.' ';
	$morehtml .= '</div>';

//	$morehtml .= '<!-- Add New button -->'.$newcardbutton;

}


// Correction des données - Pourra être supprimée ensuite

CorrDonnesTVA();

	$title = $langs->trans ( "FacturationMoniteur" );
llxHeader ( '', $title );

$agf = new Agsession ( $db );
global $agf;

// Count total nb of records
$nbtotalofrecords = 0;
if (empty ( $conf->global->MAIN_DISABLE_FULL_SCANLIST )) {
	$nbtotalofrecords = fetch_all ( $sortorder, $sortfield, 99999, 0,  $filter );
}
unset($agf);
$agf = new Agsession ( $db );
$resql = fetch_all (  $sortorder, $sortfield, $limit, $offset, $filter );


if ($resql != - 1) {
	$num = $resql;
	
	$menu = $langs->trans ( "AgfMenuSessFact" );
	$params = '&search_moniteur_id=' . $search_moniteur_id .'&search_start_date=' . $search_start_date . '&search_annee=' . $search_annee ;
	$params .= '&search_mois=' . $search_mois.'&search_semaine=' . $search_semaine;

	if ($search_date_startday) {
		$params .= '&search_date_startday='.urlencode($search_date_startday);
	}
	if ($search_date_startmonth) {
		$params .= '&search_date_startmonth='.urlencode($search_date_startmonth);
	}
	if ($search_date_startyear) {
		$params .= '&search_date_startyear='.urlencode($search_date_startyear);
	}
	if ($search_date_endday) {
		$params .= '&search_date_endday='.urlencode($search_date_endday);
	}
	if ($search_date_endmonth) {
		$params .= '&search_date_endmonth='.urlencode($search_date_endmonth);
	}
	if ($search_date_endyear) {
		$params .= '&search_date_endyear='.urlencode($search_date_endyear);
	}	
	$params .='&amp;limit='.$limit;

	$i = 0;
	
$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields


	print '<form method="POST" action="' . $url_form . '" name="search_form">' . "\n";
	print '<input type="hidden" name="action" value="recherche" >';
    print '<input type="hidden" name="token" value="'.newtoken().'">';
	if ($search_moniteur_id > 0) print '<input type="hidden" name="search_moniteur_id" value="'.$search_moniteur_id.'" >';
	
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';

	print_barre_liste ( $menu, (int)$page, $_SERVER ['PHP_SELF'], $params, $sortfield, $sortorder, '', $num, $nbtotalofrecords  , '', 0, $morehtml, '', $limit, 0, 0, 0);
	print '<table><body>';

	print '<tr class="liste_titre">';
	print '<td>&nbsp';
	
if ('BarreSelection' ==  'BarreSelection') {
	print '<table class="noborder" width="100%" name=cadre>';
	print '<tr class="liste_titre">';
	print '<td  style="width:100%;"><table id=barreSelection style="width:100%;"><tbody>';
	$arg_url = '&page=' . $page . '&search_moniteur_id=' . $search_moniteur_id. '&search_start_date=' .'&search_annee=' . $search_annee ;
	$arg_url .= '&search_mois=' . $search_mois.'&search_semaine=' . $search_semaine ;

	// BARRE DE FILTRE
	//if (empty($search_moniteur_id)) print_liste_field_titre ( $langs->trans ( "AgfFormateur" ), $_SERVER ['PHP_SELF'], "", "", $arg_url, '', $sortfield, $sortorder );
	print '<tr><td>';
	// moniteur
	print 'Moniteurs              ';
	print '</td><td>';
	if (empty($search_annee)) $search_annee =  dol_print_date(dol_now('tzuser'), '%Y');
	$filter1 = ' year(dated) = '.$search_annee;
	if (!empty($search_semaine)) $filter1 .= ' AND week(dated) = '.$search_semaine;
	if (!empty($search_mois)) $filter1.= ' AND month(dated) = '.$search_mois; 
	if (!empty($search_date_start) and !empty($search_date_end)) $filter1.= " AND dated between '". $db->idate($search_date_start)."' AND '".$db->idate($search_date_end)."' ";
	if (!empty($search_site)  and $search_site <> -1) $filter1.= " AND p.rowid = ".$search_site;
	print '</td><td>';
	print select_moniteur ($agf,  $search_moniteur_id, 'search_moniteur_id',$filter1 , 1 );
	
	// site
	print '</td><td>';
	if (empty($search_annee)) $search_annee =  dol_print_date(dol_now('tzuser'), '%Y');
	$filter1 = ' year(s.dated) = '.$search_annee;
	if (!empty($search_semaine)) $filter1 .= ' AND week(s.dated) = '.$search_semaine;
	if (!empty($search_mois)) $filter1.= ' AND month(s.dated) = '.$search_mois; 
	if (!empty($search_date_start) and !empty($search_date_end)) $filter1.= " AND s.dated between '". $db->idate($search_date_start)."' AND '".$db->idate($search_date_end)."' ";
	if (!empty($search_moniteur_id) and $search_moniteur_id <> -1) $filter1.= " AND m.rowid = ".$search_moniteur_id;
	print 'Site              ';
	print '</td><td>'; 

	$w1 = new CglFonctionDolibarr($db);
	print select_site ($search_site,'search_site',1, 1,  $filter1);
	unset ($w1);
	print '</td>';	
	
	// Prestation facturée ?
	print '</td><td>';
	print $langs->trans("PrestFacture")."               ";
	print '</td><td>'; 
	$w1 = new CglFonctionDolibarr($db);
	print select_prest_facture ($search_prest_facture,'search_prest_facture');
	unset ($w1);
	print '</td>';	
	
	print '</tr><tr>';
	// Filtre sur date
	// annee - mois
	print '</td>';
	print '<td class="liste_titre" colspan=3>';
	print '<div class="nowrap">';
	print 'Annee              ';
	print '<input type="text" class="flat" id=search_annee name=search_annee value='.$search_annee.'>';
	print '</div>';
	print '<div class="nowrap">';
	print 'Mois    '; 
	$wformOther = new FormOther($db);
	print $wformOther->select_month ($search_mois, 'search_mois',1, 1, 'maxwidth=20' );
	unset ($wformOther);
		// filtre semaine
	print '&nbsp&nbsp&nbsp&nbsp&nbspou&nbsp&nbsp&nbsp&nbspSemaine   n°'; 
	print '<input type="text" class="flat" id=search_semaine name=search_semaine value='.$search_semaine.'>';
	print '</div>';
	print '</td>';
	
	//filtre plage de date
	//print '<td class="liste_titre center">';
	print '</td><td>';
	print '<td class="liste_titre">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
	print '<td colspan=2></td>';

	
	print '<td class="liste_titre" align="right" colspan=1><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag ( $langs->trans ( "Search" ) ) . '" title="' . dol_escape_htmltag ( $langs->trans ( "Search" ) ) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag ( $langs->trans ( "RemoveFilter" ) ) . '" title="' . dol_escape_htmltag ( $langs->trans ( "RemoveFilter" ) ) . '">';
	print '</td>';
	
	if ( $arrayfields['partmonitBrute']['checked']) print '</td><td>';
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print '</tr></tbody></table name = BarreSelection></td>';
	print '</tr></tbody></table name=cadre></td>';
} // Barre selection
	print "</tr>\n";
	
	// Calcul des colonnes demandées
	$nbcoldepart =  $arrayfields['Site']['checked'] + $arrayfields['intitule_custo']['checked'] + $arrayfields['intitule']['checked'] + $arrayfields['dated']['checked'] + $arrayfields['heured']['checked']  + $arrayfields['s_TypeTVA']['checked']+$arrayfields['statut']['checked'] +  $arrayfields['duree']['checked'];
	$nbcolnego = 2;
	$nbcolTTC = 1 +  $arrayfields['partmonitBrute']['checked'] +  $arrayfields['MargeBrute']['checked'] ;
	$nbcolHT = $arrayfields['MargeHT']['checked'] ;
	$nbcolFacture = 1 + $arrayfields['FactureTTC']['checked'] + $arrayfields['FactureHT']['checked'] + $arrayfields['FacturePayee']['checked']  ;
	$nbcolTVA =  $arrayfields['TVACollectee']['checked'] + $arrayfields['TVACollecteeSurCommissionnement']['checked'] ;
	$nbcolPart =  $arrayfields['nb_stagiaire']['checked'] + $arrayfields['NbEnfant']['checked'] ;
	$nbcolBulletin = $arrayfields['listBull']['checked']  + 1 ;
		
	// BARRE DES TOTAUX - Afficher uniquement sur la colonne MargeHT est affichée  pour protéger les infos de marge lors des discussions avec le moniteur
if (! empty($arrayfields['MargeHT']['checked'])) {
	print '<table class="noborder" width="100%" name=cadre1>';
	print '<tr class="liste_titre"  >';
	print '<td  style="width:100%;"><table id=BarreTotaux style="width:100%;"><tbody><tr style="background-color:ivory;"><td>';
			
	print 'Totaux de la selection</td>';
	print '<td "   ></td>';
	print '<td class="liste_titre" style="font-weight:normal"> Vente </td>';
	print '<td class="liste" style="font-weight:normal">'.price ( $prix_venteTTC0, 0, '', 0, -1, 2, '' ).' €</td>'; // pour la colone Prix de vente
	print '<td "   ></td>';
	if (! empty($arrayfields['MargeHT']['checked'])) {			
		print '<td class="liste_titre"   style="color:red;font-weight:normal;" >Marge</td>';
		print '<td class="liste" style="color:red;font-weight:normal">'.price ( $MargeHT0, 0, '', 0, -1, 2, '' ) .' € </td>';
	}
	print '<td "></td>';
	if (! empty($arrayfields['nb_stagiaire']['checked'])){	
		print '<td class="liste_titre"  style="font-weight:normal" >Remplissage (Places/Participants)</td>';
		print '<td class="liste" style="font-weight:normal">'.$NbPlace0.' / '.$NbPart0.'</td>';
	}
	if (! empty($arrayfields['NbEnfant']['checked']))	{	
		print '<td class="liste_titre"  style="font-weight:normal" >Participants (Enfants/Adultes)</td>';
		print '<td style="font-weight:normal " ><b></b>'.$NbEnfant0.' / '.$NbAdulte0.'<b></td>';
	}
	if ( $arrayfields['partmonitBrute']['checked']) {			
		print '<td class="liste_titre"   style="color:red;font-weight:normal;" >Prix Vente Moyen</td>';
		print '<td class="liste" style="color:red;font-weight:normal">'.price ( $PrixMoyen0, 0, '', 0, -1, 2, '' ) .' € </td>';
	}
	print '</tr></tbody></table name = BarreTotaux></td>';
	print '</tr></tbody></table name=cadre1></td>';	
	
}	
	print '<table class="noborder" width="100%">';
	// BARRE DE TITRES GENERAUX
if ('BarreTitreGen' ==  'BarreTitreGen') {	
	print '<tr class="liste_titre">';
	// Partie Départ
	if (empty($search_moniteur_id)) print '<td></td>';
	$colspan = $nbcoldepart;
	print_liste_field_titre ( $langs->trans ( "Depart" ),0,'','','',"colspan=".$colspan." align=center" );	
	// Partie Négociation
	$colspan = $nbcolnego;
	print_liste_field_titre ( '<font color="blue">'.$langs->trans ( "Nego" ).'</font>',0,'','','',"colspan=".$colspan." align=center" );
	// Partie Economie TTC
	$colspan = $nbcolTTC;
	if ($colspan > 0)  print_liste_field_titre ( $langs->trans ( "Prix commercial" ),0,'','','',"colspan=".$colspan."  align=center" );
	// Partie Economie HT	
	$colspan = $nbcolHT;
	if ($colspan > 0) print_liste_field_titre ( '<font color="red">'.$langs->trans ( "HT" ).'</font>',0,'','','',"colspan=".$colspan." align=center");
	// Partie Lien Bulletins
	//print '<td></td>';
	//$colspan = $nbcolBulletin;
	$colspan = $nbcolBulletin - 1;	
	if ($colspan > 0) print_liste_field_titre ( '<font color="blue">'.$langs->trans ( "Bulletins" ).'</font>',0,'','','',"colspan=".$colspan." align=center");
	// Partie Facture
	$colspan = $nbcolFacture;
	print_liste_field_titre ( '<font color="green">'.$langs->trans ( "FactMont" ).'</font>',0,'','','',"colspan=".$colspan." align=center");	
	// Partie  TVA
	$colspan = $nbcolTVA;
	if ($colspan > 0) print_liste_field_titre ( '<font color="coral">'.$langs->trans ( "TVA" ).'</font>',0,'','','',"colspan=".$colspan." align=center");
	// Partie Participation
	$colspan = $nbcolPart;	
	if ($colspan > 0) print_liste_field_titre ($langs->trans ( "Particip" ),0,'','','',"colspan=".$colspan." align=center");
	print_liste_field_titre ( "",0,'','','',"colspan=".($colspan+2)." align=center" );	
	print "</tr>\n";		
}	
	// BARRE DE TITRES SPECIFIQUES

if ('BarreTitre' ==  'BarreTitre') {
	print '<tr class="liste_titre">';
	//print '<td>&nbsp;</td>';
	
	if (empty($search_moniteur_id)) print_liste_field_titre ( $langs->trans ( "Moniteur" ), $_SERVEUR ['PHP_SELF'], "NomMoniteur", "", $arg_url, '', $sortfield, $sortorder );
	// Partie Départ
	if (! empty($arrayfields['Site']['checked'])) print_liste_field_titre ( $langs->trans ( "Site" ), $_SERVEUR ['PHP_SELF'], "p.ref_interne", "", $arg_url, 'align=center', $sortfield, $sortorder );
	if (! empty($arrayfields['intitule_custo']['checked'])) print_liste_field_titre ( $langs->trans ( "intitule_custo" ), $_SERVEUR ['PHP_SELF'], "intitule_custo", "", $arg_url, 'align=center', $sortfield, $sortorder );
	if (! empty($arrayfields['intitule']['checked'])) print_liste_field_titre ( $langs->trans ( "intitule" ), $_SERVEUR ['PHP_SELF'], "intitule", "", $arg_url, 'align=center', $sortfield, $sortorder );
	if (! empty($arrayfields['dated']['checked'])) print_liste_field_titre ( $langs->trans ( "AgfDate" ), $_SERVEUR ['PHP_SELF'], "dated", "", $arg_url, 'align=center', $sortfield, $sortorder );
	if (! empty($arrayfields['heured']['checked'])) print_liste_field_titre ( $langs->trans ( "heured" ), $_SERVEUR ['PHP_SELF'], "heured", "", $arg_url, 'align=center', $sortfield, $sortorder );
	if (! empty($arrayfields['duree']['checked'])) print_liste_field_titre ( $langs->trans ( "duree" ), $_SERVEUR ['PHP_SELF'], "duree", "", $arg_url, 'align=center', $sortfield, $sortorder );	 
	if (! empty($arrayfields['s_TypeTVA']['checked'])) print_liste_field_titre (  $langs->trans ( "StatutFiscal" ), $_SERVEUR ['PHP_SELF'], "s_TypeTVA", "", $arg_url, 'align=center', $sortfield, $sortorder );
	// Partie Négociation
	print_liste_field_titre (  '<font color="blue">'.$langs->trans ( "Fixe" ).'</font>', $_SERVEUR ['PHP_SELF'], "s_partmonit", "", $arg_url, 'align=center', $sortfield, $sortorder );
	print_liste_field_titre (  '<font color="blue">'.$langs->trans ( "%" ).'</font>', $_SERVEUR ['PHP_SELF'], "s_pourcent", "", $arg_url, 'align=center', $sortfield, $sortorder );
	// Partie Economie TTC
	print_liste_field_titre (  $langs->trans ( "PrixVenteTTC" ), $_SERVEUR ['PHP_SELF'], "prix_venteTTC", "", $arg_url, '', $sortfield, $sortorder );
	if (! empty($arrayfields['partmonitBrute']['checked']))   print_liste_field_titre ( $langs->trans ( "PartMonitBrute" ), $_SERVEUR ['PHP_SELF'], "partmonitBrute", "", $arg_url, '', $sortfield, $sortorder );
	if (! empty($arrayfields['MargeBrute']['checked'])) print_liste_field_titre ( $langs->trans ( "MargeBrute" ), $_SERVEUR ['PHP_SELF'], "MargeBrute", "", $arg_url, '', $sortfield, $sortorder );
	// Partie Economie HT
    if (! empty($arrayfields['MargeHT']['checked']))		print_liste_field_titre (  '<font color="red">'.$langs->trans ( "margeHT" ).'</font>', $_SERVEUR ['PHP_SELF'], "margeHT", "", $arg_url, ' ', $sortfield, $sortorder );		
	if (! empty($arrayfields['listBull']['checked']))  print_liste_field_titre ('<font color="blue">'. $langs->trans ( "ListBull" ).'</font>', '', "", "", '', '', '', '' );	
	// Partie Facture
	print_liste_field_titre (  '<font color="green">'.$langs->trans ( "Facture" ).'</font>', $_SERVEUR ['PHP_SELF'], "Facture", "", $arg_url, '', $sortfield, $sortorder );
	if (! empty($arrayfields['FactureTTC']['checked'])) print_liste_field_titre (  '<font color="green">'.$langs->trans ( "FctTTC" ).'</font>', $_SERVEUR ['PHP_SELF'], "FactureTTC", "", $arg_url, '', $sortfield, $sortorder );
	if (! empty($arrayfields['FactureHT']['checked'])) print_liste_field_titre (  '<font color="green">'.$langs->trans ( "FctHC" ).'</font>', $_SERVEUR ['PHP_SELF'], "FactureHT", "", $arg_url, '', $sortfield, $sortorder );
	if (! empty($arrayfields['FacturePayee']['checked'])) print_liste_field_titre (  '<font color="green">'.$langs->trans ( "FctPay" ).'</font>', $_SERVEUR ['PHP_SELF'], "FacturePayee", "", $arg_url, '', $sortfield, $sortorder );	
	// Partie  TVA
	if (! empty($arrayfields['TVACollectee']['checked'])) print_liste_field_titre ( '<font color="coral">'.$langs->trans ( "TVAColl" ),'', "", "", '', '', '', '' );
	//print_liste_field_titre ( '<font color="coral">'.$langs->trans ( "TVARecuperable" ),'', "", "", '', '', '', '' );
	if (! empty($arrayfields['TVACollecteeSurCommissionnement']['checked'])) print_liste_field_titre ( '<font color="coral">'.$langs->trans ( "TVACollecteeSurCommissionnement" ),'', "", "", '', '', '', '' );	
	// Partie Participation
	if (! empty($arrayfields['nb_stagiaire']['checked']))  print_liste_field_titre ( $langs->trans ( "Participation" ).'</font>', $_SERVEUR ['PHP_SELF'], '', '', $arg_url, '', $sortfield, $sortorder );
	if (! empty($arrayfields['NbEnfant']['checked']))  print_liste_field_titre ( $langs->trans ( "nbEnf" ).'/'.$langs->trans ( "nbAdulte" ).'</font>', '', "", "", '', '', '', '' );	
	// Partie Lien Bulletins
	print_liste_field_titre ( $langs->trans ( "Id" ),0,'','','',"colspan=".($colspan+1)." align=center" );	
	print '<td></td>';
	print "</tr>\n";
} // BarreTitre
if ('BarreInfo' ==  'BarreInfo') {
	
	//print '</td>';
	print '<tr class="liste_titre">'."\n";
	
	if (empty($search_moniteur_id)) 	print '<td  ></td>';
	// Partie Départ
	$colspan = $nbcoldepart;
	if ($colspan > 0)print '<td class="liste_titre" colspan = '.$colspan.' ></td>';
	// Partie Négociation
	print '<td class="liste_titre" > '. info_admin($langs->trans('AideSaisieFixeMoniteur'),1).'  </td>';
	print '<td class="liste_titre" > '. info_admin($langs->trans('AideSaisiePourcentMoniteur'),1).'  </td>';
	// Partie Economie TTC
	print '<td  ></td>'; // pour la colone Prix de vente
	if (! empty($arrayfields['partmonitBrute']['checked'])) 	 print '<td class="liste_titre" >'. info_admin($langs->trans('AidePartMoniteurACT'),1).'</td>';
	if ($colspan - $arrayfields['partmonitBrute']['checked']  > 0) print '<td class="liste_titre"  ></td>';
	// Partie Economie HT
	$colspan =  $nbcolHT; 
	if ($colspan > 0) print '<td class="liste_titre" colspan = '.$colspan.' ></td>';
	// Partie Lien Bulletins
	//$colspan = $nbcolBulletin;
	//if ($colspan > 0)print '<td class="liste_titre" colspan = '.$colspan.' ></td>';
	// Partie Facture
	print '<td  ></td>'; // pour la colone Facture
	$colspan = $nbcolFacture - 1; 
	print '<td class="liste_titre" colspan = '.$colspan.' ></td>';
	// Partie  TVA
	if (! empty($arrayfields['TVACollectee']['checked']))	print '<td class="liste_titre" >'. info_admin($langs->trans('AideTVACollectee'),1).'</td>';
	if (! empty($arrayfields['TVACollecteeSurCommissionnement']['checked']))	print '<td class="liste_titre" >'. info_admin($langs->trans('AideTVACommission'),1).'</td>';
	// Partie Participation
	$colspan = $nbcolPart;
	if ($colspan > 0)print '<td class="liste_titre" colspan = '.$colspan.' ></td>';
	print '<td  ></td>'; 
	print '<td  ></td>'; 

	print "</tr>\n";
} // BarreInfo
	// Search bar
	$url_form = $_SERVER ["PHP_SELF"];
	$addcriteria = false;
	if (! empty ( $sortorder )) {
		$url_form .= '?sortorder=' . $sortorder;
		$addcriteria = true;
	}
	if (! empty ( $sortfield )) {
		if ($addcriteria) {
			$url_form .= '&sortfield=' . $sortfield;
		} else {
			$url_form .= '?sortfield=' . $sortfield;
		}
		$addcriteria = true;
	}
	if (! empty ( $page )) {
		if ($addcriteria) {
			$url_form .= '&page=' . $page;
		} else {
			$url_form .= '?page=' . $page;
		}
		$addcriteria = true;
	
	}


	print '</form>';
	print '<form method="POST" action="' . $url_form . '" name="saisie_form">' . "\n";
	print '<input type="hidden" name="action" value="enregistre" >';
    print '<input type="hidden" name="token" value="'.newtoken().'">';
	if ($search_moniteur_id > 0) print '<input type="hidden" name="search_moniteur_id" value="'.$search_moniteur_id.'" >';
if ('Script' == 'Script') 	{
	$out =  '<script> function SaisieExclusive(type, id_input, id_depart, assujetti) {
		if (type == 2)  { document.getElementById("s_partmonit_nego".concat(id_input)).value = ""  ;}
		if (type == 1)  {  document.getElementById("s_pourcent_nego".concat(id_input)).value = ""  ; };	
		CalculLigne(id_input, assujetti);	
		id =  document.getElementById("rowid".concat(id_input)).value;
		pourcent =  document.getElementById("s_pourcent_nego".concat(id_input)).value;
		partmonit =  document.getElementById("s_partmonit_nego".concat(id_input)).value;
		var facture = document.getElementById("Facture[".concat(id_input).concat("]"));
		EnrNego (id_input,id, partmonit, pourcent, "");
		}';
		
	$out .= '	function CalculLigne(id_input, assujetti) {			
			var ElmPartMonitNegoFixe = "s_partmonit_nego".concat(id_input);
			var ElmPartMonitNegoPourct = "s_pourcent_nego".concat(id_input);			
			var strFixe = document.getElementById(ElmPartMonitNegoFixe).value;
			var strPourcent = document.getElementById(ElmPartMonitNegoPourct).value;
			var ElmPV = "s_prix_vente".concat(id_input);
			Elt = document.getElementById(ElmPV).innerHTML;
			
			strPV = Elt.substr(0,Elt.length - 1);
			strPV = strPV.replace(",",".",);
			flPV = parseFloat(strPV);
			
			strFixe = strFixe.replace(",",".",);
			flFixe = parseFloat(strFixe);
			strPourcent = strPourcent.replace(",",".",);
			flPourcent = parseFloat(strPourcent);			
		
		
		
			var ElmpartmonitBrute = "partmonitBrute".concat(id_input);
			var ElmMargeBrute = "MargeBrute".concat(id_input);
			ElmCoutMonitHT  = "CoutMonitHT".concat(id_input);
			ElmMargeHT = "MargeHT".concat(id_input);
			ElmTVACollectee = "TVACollectee".concat(id_input);
			ElmTVACommission = "TVACommission".concat(id_input);
			ElmTypeTVA = "TypeTVA".concat(id_input);		';	
		if (! empty($arrayfields['ElmTypeTVA']['checked']))
			$out .='var strTypeTVA = document.getElementById(ElmTypeTVA).innerHTML;';
			$out .='if (flFixe > 0 || flPourcent > 0) {
				
					if (flFixe > 0 ) flpartmonitBrute = flFixe;
					else if (flPourcent > 0 )  flpartmonitBrute = flPV * flPourcent/100;';
		if (! empty($arrayfields['partmonitBrute']['checked']))
			 $out .='	
						strpartmonitBrute = flpartmonitBrute.toString().concat(" €");
						document.getElementById(ElmpartmonitBrute).innerHTML = strpartmonitBrute;';
				
		if (! empty($arrayfields['MargeBrute']['checked']))
			 $out .='	flMargeBrute = flPV - flpartmonitBrute;
						strMargeBrute = flMargeBrute.toString().concat(" €");
						document.getElementById(ElmMargeBrute).innerHTML = strMargeBrute;';
								
			 $out .='	flCoutMonitHT = flpartmonitBrute;';
		if (! empty($arrayfields['CoutMonitHT']['checked']))
			 $out .='	strCoutMonitHT = flCoutMonitHT.toString().concat(" €");
						document.getElementById(ElmCoutMonitHT).innerHTML = strCoutMonitHT;	';
										
			 $out .='	if (assujetti == "1" ) flMargeHT =Math.round(((flPV / 1.2) -  flCoutMonitHT)*100)/100 ;
						else flMargeHT =Math.round(((flPV  -  flCoutMonitHT)/1.2)*100)/100 ;';
						
		if (! empty($arrayfields['MargeHT']['checked']))
			 $out .='	strMargeHT = flMargeHT.toString().concat(" €");
						document.getElementById(ElmMargeHT).innerHTML = strMargeHT;	';	
			
		if (! empty($arrayfields['TVACollectee']['checked']))
			 $out .='	flTVACollectee =Math.round(((flPV *0.2 / 1.2) )*100)/100 ;
						strTVACollectee = flTVACollectee.toString().concat(" €");
						if (assujetti == 1 ) 
						document.getElementById(ElmTVACollectee).innerHTML = strTVACollectee;	';			
						
		if (! empty($arrayfields['TVACollectee']['checked']))
			$out .='	flTVACommission =Math.round(((flMargeHT *0.2) )*100)/100 ;
						strTVACommission = flTVACommission.toString().concat(" €");
						if (assujetti == 0 ) 
						document.getElementById(ElmTVACommission).innerHTML = strTVACommission;';
	 $out .='				}
			else {	';	
	

if (! empty($arrayfields['partmonitBrute']['checked']))
	 $out .='	document.getElementById(ElmpartmonitBrute).innerHTML =  "";	';		

if (! empty($arrayfields['MargeBrute']['checked']))
	 $out .='	document.getElementById(ElmMargeBrute).innerHTML =  "";	';		

if (! empty($arrayfields['MargeHT']['checked']))
	 $out .='	document.getElementById(ElmMargeHT).innerHTML=  "";	';		

if (! empty($arrayfields['CoutMonitHT']['checked']))
	 $out .='	document.getElementById(ElmCoutMonitHT).innerHTML = "";	';		

if (! empty($arrayfields['TVACollectee']['checked']))
	 $out .='	document.getElementById(ElmTVACollectee).innerHTML = "";	';		

if (! empty($arrayfields['TVACollectee']['checked']))
	 $out .='	document.getElementById(ElmTVACommission).innerHTML = "";';
	$out .='			}
		}		'."\n";
// fin CalculLigne		
		
	$out .= 'function EnrNego(id_input, id, fixe, pourcent,  facture ) {	
	
				url="ReqEnrNego.php?ID=".concat(id);
				url=url.concat("&PartMon=");
				url=url.concat(fixe);
				url=url.concat("&Pourcent=");
				url=url.concat(pourcent);
				url=url.concat("&Facture=");
				url=url.concat(facture);
				url=url.concat("&type=0");
				var	Retour = creerobjet(url); 
				var ElmPartMonitNegoFixe = "s_partmonit_nego".concat(id_input);
					if (Retour  == "Erreur") 
						document.getElementById(ElmPartMonitNegoFixe).value = "Erreur";
	}';
				
	$out .= 'function EnrFact(id_input, id, fixe, pourcent,  facture ) {	
	
				url="ReqEnrNego.php?ID=".concat(id);
				url=url.concat("&PartMon=");
				url=url.concat(fixe);
				url=url.concat("&Pourcent=");
				url=url.concat(pourcent);
				url=url.concat("&Facture=");
				url=url.concat(facture);
				url=url.concat("&type=1");
				var	Retour = creerobjet(url);
				var ElmFacture = "Facture".concat(id_input);
					if (Retour  == "Erreur") 
						document.getElementById(ElmFacture).value = "Erreur";
	}';
				
	$out .= 'function RechinfoFact(o, id_input) {
			val = o.value;
			if (val != "") { 
				url="ReqInfoFacture.php?Ref=".concat(val);
				var	Retour = creerobjet(url); 
				var tableau = Retour.split("?",4); 
				val= tableau[1];
				val = Math.round((val*100)/100);
				strval = val.toString().concat(" €");';		

if (! empty($arrayfields['FactureTTC']['checked']))
	$out .='	document.getElementById("FactureTTC".concat(id_input)).innerHTML = strval;';
	$out .= '	val= tableau[2];
				val = Math.round((val*100)/100);
				strval = val.toString().concat(" €");';		

if (! empty($arrayfields['FactureHT']['checked']))
	 $out .='	document.getElementById("FactureHT".concat(id_input)).innerHTML =  strval;';		

if (! empty($arrayfields['FacturePayee']['checked']))
	 $out .='	document.getElementById("FacturePayee".concat(id_input)).innerHTML  = tableau[0];';
	$out .= '	}
			else {';
if (! empty($arrayfields['FactureTTC']['checked']))
	$out .='	document.getElementById("FactureTTC".concat(id_input)).innerHTML = "";';	
if (! empty($arrayfields['FactureHT']['checked']))
	 $out .='	document.getElementById("FactureHT".concat(id_input)).innerHTML =  "";';
if (! empty($arrayfields['FacturePayee']['checked']))
	 $out .='	document.getElementById("FacturePayee".concat(id_input)).innerHTML  = tableau[0];';	
 
	$out .='	}
		
			id =  document.getElementById("rowid".concat(id_input)).value;
			facture = document.getElementById("Facture".concat(id_input)).value ;
			EnrFact(id_input, id, "", "",  facture) 
		}'."\n";
		
	$out .= 'function creerobjet(fichier)  		{ 
			if(window.XMLHttpRequest)	xhr_object = new XMLHttpRequest();  
			else if(window.ActiveXObject)	xhr_object = new ActiveXObject("Microsoft.XMLHTTP"); 
			else return(false);
			xhr_object.open("GET", fichier, false);
			xhr_object.send(null);
			if(xhr_object.readyState == 4)	return(xhr_object.responseText); 
			else	return(false); 
	} ';
	
	$out .= '</script>';
	/*
	$out .= ' <style type="text/css" media="screen"> 
		td.nonaffichee { visibility: collapse;}
	</style>';*/
	print $out;
}
	$var = true;
	$i=1;
	
if ('ListeDonnees' ==  'ListeDonnees') {	
	foreach ( $agf->lines as $line ) {			
			// Colorer en gris tous les départs à venir Jour j+1
			if ($line->dated > dol_now('tzuser')) $colorligne='gray';
			if ($line->status == 3) $colorligne='green';
			else $colorligne='';
			$StDepartAnulAff = false;
			// Affichage tableau des sessions
			$var = ! $var;
			print "<tr $bc[$var]   style='color:".$colorligne."'>";
			// Moniteur
			if ( empty($search_moniteur_id) and !empty($line->trainerrowid)) {				
				print '<td  style="color='.$colorligne.'">';
				print getNomUrl("object_company.png", 'Moniteur',0,$line->trainerrowid)."&nbsp".ucfirst ( $line->fullname_contact)."</td>";

				//print ucfirst ( $line->fullname_contact);
				print '</td>';
			}
			// Partie Départ
			if ('depart' == 'depart') {
			// Départ
				//print '<td>' . stripslashes ( dol_trunc ( $line->intitule_custo, 60 ) ) . '</td>';			
				if (! empty($arrayfields['Site']['checked'])) 
					print '<td>'.$line->activite_lieu."</td>";
				
				
				if (! empty($arrayfields['intitule_custo']['checked'])) {
					print '<td>' ;
					if ($line->status == 3 and $StDepartAnulAff == false) {
						$texte = $langs->trans('DepartAnn');
						print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut6.png">'.'&nbsp';
						$StDepartAnulAff = true;
					}
					// Lien vers départ	
					print getNomUrl("object_company.png", 'Depart',0,$line->id_act)."&nbsp".$line->intitule_custo."</td>";
				}
				
				if (! empty($arrayfields['intitule']['checked'])) {
					print '<td>' ;
					if ($line->status == 3 and $StDepartAnulAff == false) {
						$texte = $langs->trans('DepartAnn');
						print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut6.png">'.'&nbsp';
						$StDepartAnulAff = true;					}

					print $line->intitule."</td>";
				}
				
				if (! empty($arrayfields['dated']['checked'])) {
					print '<td>' ;
					if ($line->status == 3 and $StDepartAnulAff == false) {
						$texte = $langs->trans('DepartAnn');
						print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut6.png">'.'&nbsp';
						$StDepartAnulAff = true;
					}
					// date départ
					print dol_print_date ( $line->dated, '%d/%m/%y' ) . ' ';
					print dol_print_date ( $line->heured, '%Hh' ) . '</td>';
				}
				
				if (! empty($arrayfields['heured']['checked'])) {
					print '<td>' ;
					if ($line->status == 3 and $StDepartAnulAff == false) {
						$texte = $langs->trans('DepartAnn');
						print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut6.png">'.'&nbsp';
						$StDepartAnulAff = true;
					}
					// date départ
					print dol_print_date ( $line->heured, '%H' ) . 'h';
				}
				if (! empty($arrayfields['duree']['checked'])) {
					print '<td>' ;
					if ($line->status == 3 and $StDepartAnulAff == false) {
						$texte = $langs->trans('DepartAnn');
						print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut6.png">'.'&nbsp';
						$StDepartAnulAff = true;
					}
					// duree départ
					if ($line->duree == 0.5) print '<td>1/2</td>';
						else print price2num ( $line->duree ) . '</td>';
				}
				
				if (! empty($arrayfields['s_TypeTVA']['checked'])) {
					print '<td>' ;
					if ($line->status == 3 and $StDepartAnulAff == false) {
						$texte = $langs->trans('DepartAnn');
						print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut6.png">'.'&nbsp';
						$StDepartAnulAff = true;
					}
					// Statut Fiscal du départ
					if (empty($colorligne)) $color = 'blue';
					else $color =  $colorligne;
					print '<span id="TypeTVA'.$i.'">' . $line->valeurTauxTva .'</font></td>';
				}
			} // depart
			
			if ('Nego' == 'Nego') {
					
				// Partie Négociation
				//Prix 
				print '<td>';
				/*if (!empty($line->FactureID) and $line->FacturePayee == 'oui') {
					print  price ( $line->s_partmonit_nego, 0, '', 0, -1, 2, '' )   ;
					if (!empty($line->s_partmonit_nego)) print '€';
					print '</td>';
					print '<td>' . price ( $line->s_pourcent_nego, 0, '', 0, -1, 2, '' )   ;
					if (!empty($line->s_pourcent_nego)) print '%';

				}
				else  {*/
					print '<input size="3" type="text" class="flat" id="s_partmonit_nego'.$i.'" name="s_partmonit_nego['.$i.']" value="'. price ( $line->s_partmonit_nego, 0, '', 0, -1, 2, '' )   .'" style="color:'.$color.'" 	
							onchange="SaisieExclusive(1, '.$i.', '.$line->rowid.', '.$line->s_TypeTVA.') " />';	
					print '</td>';
					print '<td><input size="1" type="text" class="flat" id="s_pourcent_nego'.$i.'" name="s_pourcent_nego['.$i.']" value="'. price ( $line->s_pourcent_nego, 0, '', 0, -1, 2, '' )   .'"   style="color:'.$color.'" 	
							onchange="SaisieExclusive(2, '.$i.', '.$line->rowid.', '.$line->s_TypeTVA.')"/>';		
				//}				
			} // Négo
			// Partie Economie TTC	
			if ('TTC' == 'TTC') {	
				print '</td>';			
				// PRIX DE VENTE
				print '<td style="color:'.$color.'">';
				print '<span id="s_prix_vente'.$i.'">';
				print price ( $line->prix_venteTTC, 0, '', 0, -1, 2, '' )   ;
				print '€</span>';
				print '</td>';
				
				if (! empty($arrayfields['partmonitBrute']['checked']))  {
					print '<td>';
					print '<span id="partmonitBrute'.$i.'">';
					if (!(empty($line->s_partmonit_nego) and empty($line->s_pourcent_nego)))
					{
						print price ( $line->partmonitBrute, 0, '', 0, -1, 2, '' )   ;
						if (!empty($line->partmonitBrute)) print '€';				
					}
					print '</span>';
					print '</td>';
				}			
				
				if (! empty($arrayfields['MargeBrute']['checked']))  {
					print '<td>';
					print '<span id="MargeBrute'.$i.'">';
					if (!(empty($line->s_partmonit_nego) and empty($line->s_pourcent_nego)))
					{ 
						print  price ( $line->MargeBrute, 0, '', 0, -1, 2, '' )   ;
						if (!empty($line->MargeBrute)) print '€';
					}
					print '</span>';
					print '</td>';
				}
			} // TTC
			// Partie Economie HT	
			if ('HT' == 'HT') {
			if (empty($colorligne)) $color = 'red';
				else $color =  $colorligne;			
				if (! empty($arrayfields['MargeHT']['checked']))  {
					print '<td style="color:'.$color.'">';
					print '<span id="MargeHT'.$i.'">';
					if (!(empty($line->s_partmonit_nego) and empty($line->s_pourcent_nego)))
					{ 		
			
						print price ( $line->MargeHT, 0, '', 0, -1, 2, '' )  ;
						if (!empty($line->MargeHT)) print '€';
					}
				}
			} // HT
			print '</span>';
			print '</td>';
			// Partie bulletins			
			if (! empty($arrayfields['listBull']['checked']))  {
					$listBull = $wfdep->html_chercheBullDepart($line->rowid, $filterbull);
					print '<td ' . $style . '>' .  $listBull . '</td>';
				}
					
			// Partie Facture
			if ('Facture' == 'Facture') {
			print '<td>';
				//print '<input size="11" type="text" class="flat" id="Facture['.$i.']" name="Facture['.$i.']" value="'. $line->Facture.'"  style="color:green" onchange="RechinfoFact(this, '.$i.')" /></td>';	
				print '<input type="hidden" id=rowid'.$i.' name="rowid['.$i.']" value="' . $line->rowid . '" >';
				if (!empty($line->SocMoniteur)) {
					$htmlname = 'Facture['.$i.']';
					$htmlid = 'Facture'.$i;
					if (!empty($search_moniteur )) 			$filter2 = " AND fk_soc =". $search_moniteur;
					else $filter2 = " fk_soc =". $line->SocMoniteur;
					$filter2 .= " AND year(datef) = ".$search_annee;
					$event = 'style="color:'.$color.'" onchange="RechinfoFact(this, '.$i.')"';
					print select_facture_moniteur ( $line->FactureID, $htmlname, $htmlid, $filter2,  1,  0, $event);

					}
				print '</td>';
				if (empty($colorligne)) $color = 'green';
				else $color =  $colorligne;	
				if (! empty($arrayfields['FactureTTC']['checked']))  {
					
					print '<td  style="color:'.$color.'">' ;
					print '<span id="FactureTTC'.$i.'">';
					if (!empty($line->FactureTTC)) {
						print	price ( $line->FactureTTC, 0, '', 0, -1, 2, '' )   ;
						print '€';
					}
					print '</span>';
					print '</td>';
				}
				if (! empty($arrayfields['FactureHT']['checked']))  {
					print '<td  style="color:'.$color.'">' ;
					print '<span id="FactureHT'.$i.'">';
					if (!empty($line->FactureHT)) {
						print	price ( $line->FactureHT, 0, '', 0, -1, 2, '' )   ;
						print '€';
					}
					print '</span>';
					print '</td>';
				}
				if (! empty($arrayfields['FacturePayee']['checked']))  {
					print '<td  style="color:'.$color.'">';
					print '<span id="FacturePayee'.$i.'">';
					print $line->FacturePayee  . '</span></td>';
				}
			} // Facture
			// Partie  TVA
			if ('TVA' == 'TVA') {
				if (empty($colorligne)) $color = 'coral';
				else $color =  $colorligne;	
				if (! empty($arrayfields['TVACollectee']['checked']))  {
					print '<td style="color:'.$color.'">';
					print '<span id="TVACollectee'.$i.'">';
					if (!empty($line->prix_venteTTC) and $line->s_TypeTVA == 1)
					{
						$TVACollectee = $line->prix_venteTTC * 0.2 /1.2;			
						print  price ( $TVACollectee , 0, '', 0, -1, 2, '' ) ;
						if (!empty($TVACollectee)) print '€';
					}
					print '</span>';
					print '</td>';
				}
				if (! empty($arrayfields['TVACollecteeSurCommissionnement']['checked']))  {
					print '<td style="color:'.$color.'">';
					print '<span id="TVACommission'.$i.'">';
					if (!(empty($line->s_partmonit_nego) and empty($line->s_pourcent_nego)) and $line->s_TypeTVA == 0)
					{ 
						$TVACollecteeSurCommissionnement = ((float)$line->prix_venteTTC - (float)$line->CoutMonitHT -  (float)$line->MargeHT);
						print  price ( $TVACollecteeSurCommissionnement, 0, '', 0, -1, 2, '' ) ;
						if (!empty($TVACollecteeSurCommissionnement)) print '€';
					}
					print '</span>';
				print '</td>';			
				}
			} // TVA
			// Partie Participation
			if ('Participation' == 'Participation') {
				if (! empty($arrayfields['nb_stagiaire']['checked']))  {
					print '<td ' . $style . '>' . $line->nb_place . '/' . $line->nb_stagiaire . '</td>';
				}				
			if (! empty($arrayfields['NbEnfant']['checked']))  {
				print '<td ' . $style . '>' ;
				if ( $line->NbEnfant <> 0 or  $line->NbAdulte <> 0) print  $line->NbEnfant .'/' . $line->NbAdulte;
				print  '</td>';
				}
			} // Participation
	
			// Calcul de la couleur du lien en fonction de la couleur définie sur la session
			// http://www.w3.org/TR/AERT#color-contrast
			// SI ((Red value X 299) + (Green value X 587) + (Blue value X 114)) / 1000 < 125 ALORS
			// AFFICHER DU BLANC (#FFF)
			$couleur_rgb = agf_hex2rgb ( $line->color );
			$color_a = '';
			if ($line->color && ((($couleur_rgb [0] * 299) + ($couleur_rgb [1] * 587) + ($couleur_rgb [2] * 114)) / 1000) < 125)
				$color_a = ' style="color: #FFFFFF;"';
			// Partie Lien Bulletins
			if ('Liens' == 'Liens') {
				if ( empty($arrayfields['intitule_custo']['checked'])) {
					 print '<td><a href="./fichedepart.php?id='.$line->rowid.'&total=oui" >'.$line->rowid.'</a>';			
				}
	
			} // Liens
			print "</tr>\n";
			$i++;		
	}
} // ListeDonnées	
	print "</table>";
	// Bouton Enregistrer
	//	print '<br align=right><input class="button"  type="submit" value="'.$langs->trans("Enregistrer").'">';	
	print '</form>';
} else {
	setEventMessage ( $agf->error, 'errors' );
}

llxFooter ();
$db->close ();

/*

select  s.rowid as IdSession, st.rowid as IdTiers, 	
		p.ref_interne as Site,	s.type_session,  s.nb_place, s.nb_stagiaire, NbAdulte,NbEnfant,PrixVente as prix_venteTTC , s.dated ,nom, se.s_pourcent, se.s_partmonit, 
 m.rowid as IdMonit,	NomMoniteur,	 semaine,

case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
	else 
		case when s_partmonit > 0   then  s_partmonit 
			else 
				case when (isnull(s_partmonit) or s_partmonit = 0)  then PrixVente *  s_pourcent  / 100
					 
	end   end end as partmonitBrute,

PrixVente -  case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
	else 
		case when s_partmonit > 0   then  s_partmonit 
			else 
				case when (isnull(s_partmonit) or s_partmonit = 0)  then PrixVente *  s_pourcent  / 100					 
	end   end end as MargeBrute, 

case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
	else 
		case when s_partmonit > 0    and tva_assuj = 1 then  s_partmonit 
				else 
					case when s_partmonit > 0   and tva_assuj = 0  then  s_partmonit
			else 
				case when (isnull(s_partmonit) or s_partmonit = 0)  and tva_assuj = 1 
					then  PrixVente *  s_pourcent * 100 
				else 
					case when (isnull(s_partmonit) or s_partmonit = 0)  and tva_assuj = 0 
						then PrixVente *  s_pourcent  / 100					 
	end  end end end end as CoutMonitHT, 
	
case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
	else 
		case when s_partmonit > 0  and tva_assuj = 1  then PrixVente /1.2 - s_partmonit
		else 
			case when s_partmonit > 0 and tva_assuj = 0  then (PrixVente - s_partmonit )/1.2 
			else 
				case when (isnull(s_partmonit) or s_partmonit = 0)   and tva_assuj = 0   
						then PrixVente * (1 - s_pourcent/100 ) / 1.2
				else 
				case when (isnull(s_partmonit) or s_partmonit = 0)   and tva_assuj = 1   
						then PrixVente *  / 1.2 - PrixVente *  s_pourcent/100 					 
	end end end end end   as MargeHT,	
			
case when se.s_partmonit > 0 
				then se.s_partmonit
			else
				case when se.s_pourcent > 0
					then
						se.s_pourcent*(100/120)*(select sum(qte * pu * (100-bd.rem)/100 ) from  	llx_cglinscription_bull as b 
						LEFT JOIN	llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0
						where  bd.fk_activite = s.rowid  )/100
					end 
			end   as partmonitBruteAct,
			
case when not isnull(f.ref) then f.ref
			else case when not isnull(fLib.ref) then fLib.ref
			else se.s_ref_facture 
			end end as Facture,
	
		
		case when  not isnull(f.ref) then f.total_ttc
			else case when not isnull(fLib.ref) then fLib.total_ttc
			else 0		
			end end as FactureTTC , 
	case when  not isnull(f.ref) then f.total_ht
			else case when not isnull(fLib.ref) then fLib.total_ht
			else 0	
		end end as FactureHT
	
		
	case when  not isnull(f.ref) then f.total_tva
			else case when not isnull(fLib.ref) then fLib.total_tva
			else ''			
		end end as FactureTVA, 
	case when  not isnull(f.ref) then f.paye
			else case when not isnull(fLib.ref) then fLib.paye
			else ''			
		end end as FacturePayee, 
		st.tva_assuj, se.s_TypeTVA 
FROM llx_agefodd_formateur  as m
			LEFT JOIN 	 (select mi.rowid	, concat(concat(mi.lastname, ' '),mi.firstname) as NomMoniteur			
				from llx_socpeople as mi  ) as mic on mic.rowid = m.fk_socpeople
				
				
			LEFT JOIN   llx_agefodd_session_formateur as sm on m.rowid = sm.fk_agefodd_formateur
			LEFT JOIN 	llx_societe as st on st.rowid = m.fk_soc  
			LEFT JOIN	(
			
			select  s1.rowid, s1.type_session, s1.dated,s1.fk_session_place,  	s1.nb_place, s1.nb_stagiaire, DATE_FORMAT(s1.dated,"%u") as semaine ,  (select sum(qte) from  	llx_cglinscription_bull as b 
			LEFT JOIN	llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0 
			where  bd.fk_activite = s1.rowid and bd.age <= 12  )
			as NbEnfant,
			(select sum(qte) from  	llx_cglinscription_bull as b 
				LEFT JOIN	llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0
				where  bd.fk_activite = s1.rowid  and bd.age > 12 )
				as NbAdulte,
			(select sum(qte * pu * (100-bd.rem)/100 ) from  	llx_cglinscription_bull as b 
				LEFT JOIN	llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0
				where  bd.fk_activite = s1.rowid  )
			as PrixVente
					
			from llx_agefodd_session as s1) as s  on s.rowid = sm.fk_session 
			
			LEFT JOIN	llx_agefodd_place as p on s.fk_session_place = p.rowid
			LEFT JOIN   llx_agefodd_session_extrafields as se on se.fk_object = s.rowid
			LEFT JOIN 	llx_facture_fourn as f on f.rowid = se.s_fk_facture  
			LEFT JOIN 	llx_facture_fourn as fLib on fLib.ref = se.s_ref_facture
		where year(dated) = 2017	
		*/

		
/* REQUETE PREPARATOIRE POUR ETABLISSEMENT FACTURE

	
select  s.rowid as IdSession, st.rowid as IdTiers,	p.ref_interne as Site, s.rowid as id_act, s.type_session, '' as nb_place, '' as nb_stagiaire, s.intitule_custo, 
	NbAdulte,NbEnfant,PrixVente as prix_venteTTC, s.dated ,st.nom, '' as s_pourcent, '' as s_partmonit, '' as IdMonit, '' as NomMoniteur, semaine, '' as intitulecatalogue,
	 ( select min(heured) from     llx_agefodd_session_calendrier as cal where  cal.fk_agefodd_session = s.rowid ) as heured , '' as partmonitBrute, '' as MargeBrute, '' as CoutMonitHT,
	 '' as MargeHT, 
 case when not isnull(f.ref) then f.ref
			else case when not isnull(fLib.ref) then fLib.ref
			else se.s_ref_facture
			end end as Facture,  
  case when  not isnull(f.ref) then f.total_ttc
			else case when not isnull(fLib.ref) then fLib.total_ttc
			else 0		
			end end as FactureTTC ,  
  case when  not isnull(f.ref) then f.total_ht
			else case when not isnull(fLib.ref) then fLib.total_ht
			else 0	
		end end as FactureHT , 
  case when  not isnull(f.ref) then f.rowid
			else case when not isnull(fLib.ref) then fLib.rowid
			else 0		
			end end as FactureID ,  
 case when  not isnull(f.ref) then f.total_tva
			else case when not isnull(fLib.ref) then fLib.total_tva
			else ''			
		end end as FactureTVA,   
 case when  not isnull(f.ref) then f.paye
			else case when not isnull(fLib.ref) then fLib.paye
			else ''			
		end end as FacturePayee,   
  st.tva_assuj, se.s_TypeTVA , se.s_duree_act , 
		  
		s.rem, pu, qte
		
		FROM llx_agefodd_formateur  as m
			LEFT JOIN   llx_agefodd_session_formateur as sm on m.rowid = sm.fk_agefodd_formateur
			LEFT JOIN 	llx_societe as st on st.rowid = m.fk_soc  
			LEFT JOIN	(
		
					select  s1.rowid, s1.intitule_custo, s1.type_session, s1.dated,s1.fk_session_place, DATE_FORMAT(s1.dated,"%u") as semaine , 
				
				sum(case when  bd.fk_activite = s1.rowid  and (bd.age <=  12  or bd.age = 100) then qte  else 0 end )
				as NbEnfant,
					
				sum(case when  bd.fk_activite = s1.rowid  and (bd.age > 12  or bd.age = 99) then qte  else 0 end )
					as NbAdulte,
				sum(case when   bd.fk_activite = s1.rowid   then qte * pu * (100-bd.rem)/100 else 0 end )
				as PrixVente,
				sum(bd.qte) as qte, bd.rem, pu,  TypeParticipant
						
				from llx_agefodd_session as s1
				
					LEFT JOIN (	 
						select fk_activite, fk_bull, age , rem, pu, qte, case when   (age <=  12  or age = 100) then 'E'  else case when (age > 12  or age = 99) then 'A' end end as TypeParticipant from llx_cglinscription_bull_det
						where  action not in ('S','X') and type = 0 
						)as bd on bd.fk_activite = s1.rowid
					LEFT JOIN 	 llx_cglinscription_bull as b on bd.fk_bull = b.rowid
					where  (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" ))
					 
					group by s1.rowid, s1.type_session, s1.dated,s1.fk_session_place, semaine,  rem, pu, TypeParticipant
			) as s  on s.rowid = sm.fk_session
			LEFT JOIN	llx_agefodd_place as p on s.fk_session_place = p.rowid
			LEFT JOIN   llx_agefodd_session_extrafields as se on se.fk_object = s.rowid
			LEFT JOIN 	llx_facture_fourn as f on f.rowid = se.s_fk_facture  
			LEFT JOIN 	 llx_facture_fourn as fLib on fLib.ref = se.s_ref_facture  
			WHERE year(dated) = 2018
			and s.rowid = 841
;

select  s.rowid as IdSession, st.rowid as IdTiers, p.ref_interne as Site,s.rowid as id_act, 	s.type_session,  s.nb_place,   nb_stagiaire, s.intitule_custo, NbAdulte, NbEnfant, PrixVente as prix_venteTTC , s.dated ,nom, se.s_pourcent, se.s_partmonit,  m.rowid as IdMonit,	NomMoniteur,	 semaine,  pr.intitule,  
  ( select min(heured) from     llx_agefodd_session_calendrier as cal where  cal.fk_agefodd_session = s.rowid ) as heured , 
 case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
			else 
				case when s_partmonit > 0   then  s_partmonit 
					else 
						case when (isnull(s_partmonit) or s_partmonit = 0)  then PrixVente *  s_pourcent  / 100
							 
			end   end end as partmonitBrute,  
 PrixVente -  case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
			else 
				case when s_partmonit > 0   then  s_partmonit 
					else 
						case when (isnull(s_partmonit) or s_partmonit = 0)  then PrixVente *  s_pourcent  / 100							 
			end   end end as MargeBrute,   
  case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
			else 
				case when s_partmonit > 0    and tva_assuj = 1 then  s_partmonit 
						else 
							case when s_partmonit > 0   and tva_assuj = 0  then  s_partmonit
					else 
						case when (isnull(s_partmonit) or s_partmonit = 0)  and tva_assuj = 1 then  PrixVente  *  s_pourcent / 100 
						else 
							case when (isnull(s_partmonit) or s_partmonit = 0)  and tva_assuj = 0  then PrixVente *  s_pourcent  / 100							 
			end  end end end end as CoutMonitHT,  
  case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0
			else 
				case when s_partmonit > 0  and tva_assuj = 1  then PrixVente /1.2 - s_partmonit
				else 
					case when s_partmonit > 0 and tva_assuj = 0  then (PrixVente - s_partmonit )/1.2 
					else 
						case when (isnull(s_partmonit) or s_partmonit = 0 )  and tva_assuj = 0  then  PrixVente * 	(1  -  s_pourcent/100) / 1.2
				else 
				case when (isnull(s_partmonit) or s_partmonit = 0)   and tva_assuj = 1   then PrixVente / 1.2 - PrixVente *  s_pourcent/100 					 
				end	end end end end  as MargeHT,	 
 case when not isnull(f.ref) then f.ref
			else case when not isnull(fLib.ref) then fLib.ref
			else se.s_ref_facture
			end end as Facture,  
  case when  not isnull(f.ref) then f.total_ttc
			else case when not isnull(fLib.ref) then fLib.total_ttc
			else 0		
			end end as FactureTTC ,  
  case when  not isnull(f.ref) then f.total_ht
			else case when not isnull(fLib.ref) then fLib.total_ht
			else 0	
		end end as FactureHT , 
  case when  not isnull(f.ref) then f.rowid
			else case when not isnull(fLib.ref) then fLib.rowid
			else 0		
			end end as FactureID ,  
 case when  not isnull(f.ref) then f.total_tva
			else case when not isnull(fLib.ref) then fLib.total_tva
			else ''			
		end end as FactureTVA,   
 case when  not isnull(f.ref) then f.paye
			else case when not isnull(fLib.ref) then fLib.paye
			else ''			
		end end as FacturePayee,   
  st.tva_assuj, se.s_TypeTVA , se.s_duree_act  ,
  '' as rem, '' as pu, '' as qte
  FROM  llx_agefodd_formateur  as m 
  	LEFT JOIN 	 (select mi.rowid	, concat(concat(mi.lastname, ' '),mi.firstname) as NomMoniteur	
				from  llx_socpeople as mi  ) as mic on mic.rowid = m.fk_socpeople  
 LEFT JOIN    llx_agefodd_session_formateur as sm on m.rowid = sm.fk_agefodd_formateur  
  LEFT JOIN 	 llx_societe as st on st.rowid = m.fk_soc   
  LEFT JOIN	(
			
			select  s1.rowid, s1.intitule_custo, s1.fk_formation_catalogue, s1.type_session, s1.dated,s1.fk_session_place,  	s1.nb_place, s1.nb_stagiaire, DATE_FORMAT(s1.dated,"%u") as semaine ,  (select sum(qte) from  	 llx_cglinscription_bull as b 
				LEFT JOIN	 llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ("S","X") and bd.type = 0 
				where  bd.fk_activite = s1.rowid and  (bd.age <= 12 or bd.age = 100) and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )))			as NbEnfant,
			(select sum(qte) from  	 llx_cglinscription_bull as b 
				LEFT JOIN	 llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ("S","X") and bd.type = 0
				where  bd.fk_activite = s1.rowid and  (bd.age > 12 or bd.age = 99) and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )))
				as NbAdulte,
			(select sum(qte * pu * (100-bd.rem)/100 ) from  	 llx_cglinscription_bull as b 
				LEFT JOIN	 llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ("S","X") and bd.type = 0
				where  bd.fk_activite = s1.rowid  and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )) )			as PrixVente			
			from  llx_agefodd_session as s1) as s  on s.rowid = sm.fk_session 
  LEFT JOIN	 llx_agefodd_place as p on s.fk_session_place = p.rowid 
  LEFT JOIN    llx_agefodd_session_extrafields as se on se.fk_object = s.rowid 
  LEFT JOIN    llx_agefodd_formation_catalogue as pr on pr.rowid = s.fk_formation_catalogue 
 LEFT JOIN 	 llx_facture_fourn as f on f.rowid = se.s_fk_facture  
 LEFT JOIN 	 llx_facture_fourn as fLib on fLib.ref = se.s_ref_facture  
 WHERE 1=1 	 AND year(dated) = '2018' ORDER BY dated DESC  LIMIT 51



		*/