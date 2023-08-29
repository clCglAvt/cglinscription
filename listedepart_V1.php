<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
* Copyright (C) 2012-2013       Florian Henry   <florian.henry@open-concept.pro>
 *
 * Version CAV - 2.7 - été 2022
 *				 	 - Remplacer GET par POST
 *					 - Migration Dolibarr V15
 * Version CAV - 2.8 - hiver 2023 - Pagination (suppression Ajout)
 * 								  - vérification de la fiabilité des foreach
 *
* EMPLATRE A TRAITER CAUSE HEBERGEMENT SITE GROUND
* Utiliser $now=dol_now('tzuser') pour récupérer la date actuelle ATTENTION tous les affichages seront ok sans l'emplatre précédemment mis en place
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
 * \file cglinscription/listdepart.php
 * \ingroup copie de agefodd
 * \brief list of session
 */

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die ( "Include of main fails" );
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
require_once ('../agefodd/class/agsession.class.php');
require_once ('../agefodd/class/agefodd_formation_catalogue.class.php');
//require_once ('../agefodd/class/agefodd_place.class.php');
require_once ('./class/site.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../agefodd/lib/agefodd.lib.php');
require_once ('../agefodd/class/html.formagefodd.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once ('../agefodd/class/agefodd_formateur.class.php');
require_once ('../../core/lib/date.lib.php');
require_once ('./class/cglinscription.class.php');
require_once('./class/feuilleroute.class.php');
require_once('./class/cgldepart.class.php');

// Security check
// à remplacer par un acces cglinscription
//if (! $user->rights->agefodd->lire) 	accessforbidden ();

$sortorder = GETPOST ( 'sortorder', 'alpha' );
$sortfield = GETPOST ( 'sortfield', 'alpha' );
$page = GETPOST ( 'page', 'int' );
$arch = GETPOST ( 'arch', 'int' );
$type = GETPOST ( 'type', 'alpha' );
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;

// Search criteria
$search_trainning_name = GETPOST ( "search_trainning_name", 'alpha' );
$search_soc = GETPOST ( "search_soc" , 'alpha');
$search_teacher_id = GETPOST ( "search_teacher_id", 'int' );
$search_training_ref = GETPOST ( "search_training_ref", 'alpha' );
$search_start_date = dol_mktime ( 0, 0, 0, GETPOST ( 'search_start_datemonth', 'int' ), GETPOST ( 'search_start_dateday', 'int' ), GETPOST ( 'search_start_dateyear', 'int' ) );
if (empty( GETPOST ( 'search_start_datemonth'))) $search_start_date= GETPOST ( "search_start_date", 'date' );
$search_site = GETPOST ( "search_site", 'int' );
$search_training_ref_interne = GETPOST('search_training_ref_interne','alpha');
$search_type_session=GETPOST ( "search_type_session",'int' );
$training_view = GETPOST ( "training_view", 'int' );
$site_view = GETPOST ( 'site_view', 'int' );
$status_view = GETPOST('status','int');

$contextpage='listedepart.php';

// COLONNES A DISCRETION UTILISATEUR
// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'Id_depart'=>"Départ",
	'Duree'=>'Duree',
	'AgfDateDebut'=>'DateDebut',
	'HeureDebut'=>'HeureDebut',
	'HeureFin'=>'HeureFin',
	'AgfFormateur'=>"Formateur",
	'Intitule'=>"AgfIntitule",
	'Site'=>"AgLieu",
	'Type'=>"TypeDepart",
    "Statut"=>"Statut",
	'LstDepInsc'=>'LstDepInsc',
	'NbEnfant'=>'NbEnf',
	'NbAdulte'=>'NbAdulte'
);

$arrayfields=array(
    'Id_depart'=>array('label'=>$langs->trans("IdDepart"), 'checked'=>1),
	'Duree'=>array('label'=>$langs->trans("Duree"), 'checked'=>1),
	'Intitule'=>array('label'=>$langs->trans("NomDepart"), 'checked'=>1),
	'AgfDateDebut'=>array('label'=>$langs->trans("dated"), 'checked'=>1),
	'HeureDebut'=>array('label'=>$langs->trans("HeureD"), 'checked'=>1),
	'HeureFin'=>array('label'=>$langs->trans("HeureF"), 'checked'=>1),
	'AgfFormateur'=>array('label'=>$langs->trans("AgfFormateur"), 'checked'=>1),
	'Site'=>array('label'=>$langs->trans("Site"), 'checked'=>1),
	'Type'=>array('label'=>$langs->trans("TypeDepart"), 'checked'=>1),	
	'Statut'=>array('label'=>$langs->trans("Statut"), 'checked'=>1),
	'LstDepInsc'=>array('label'=>$langs->trans("LstDepInsc"), 'checked'=>1),
	'NbEnfant'=>array('label'=>$langs->trans("NbEnf"), 'checked'=>1),
	'NbAdulte'=>array('label'=>$langs->trans("NbAdulte"), 'checked'=>1)
);


include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

$wdep= new FeuilleRoute ($db);
// Do we click on purge search criteria ?
if (GETPOST ( "button_removefilter_x" , 'alpha')) {
	$search_trainning_name = '';
	$search_soc = '';
	$search_teacher_id = "";
	$search_training_ref = '';
	$search_start_date = "";
	$search_end_date = "";
	$search_site = "";
	$search_training_ref_interne="";
	$search_type_session="";
}

if (GETPOST("action", 'alpha')=='genere')
{
	$objfeuille = new FeuilleRoute($db);
	$idSess = GETPOST("id_session", 'int');
	$objfeuille->fetch_complet_filtre(0, $idSess );
	$typeModele = 'feuilleroute_odt:'.DOL_DATA_ROOT.'/doctemplates/bulletin/feuilleroute.odt';
	$ret = cgl_feuille_create($db, $idSess,  $typeModele, $langs, $file, $objfeuille);
	
}

	function fetch_all( $sortorder, $sortfield, $limit, $offset, $filter = array()) {

		global $langs, $type, $agf, $db;
		
		$wses = new CglDepart ($db);
		
		$sql = "SELECT s.rowid, s.fk_soc, s.fk_session_place, s.type_session, s.dated, s.datef, s.status, dictstatus.intitule as statuslib, dictstatus.code as statuscode, ";
		$sql .= " s.is_date_res_site, s.is_date_res_trainer, s.date_res_trainer, s.color, s_ficsess, ";
		$sql .= " s.force_nb_stagiaire, s.nb_stagiaire,s.notes, AgSe.s_duree_act as duree, ";
		$sql .= " c.intitule, s.intitule_custo, c.ref,c.ref_interne as trainingrefinterne,s.nb_subscribe_min,";
		$sql .= " p.ref_interne, s.nb_place ";
		$sql .= " ,so.nom as socname";
		$sql .= " ,f.rowid as trainerrowid";
		$sql .= " ,s.duree_session, heured, heuref, ";
		$sql .= " (select  count(case when b.statut = 0 then b.rowid end) from ".MAIN_DB_PREFIX."cglinscription_bull as b left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on  b.rowid=bd.fk_bull 
						where bd.fk_activite =  s.rowid ) as nbencours , ";
		$sql .= " (select  count(b.rowid)  from ".MAIN_DB_PREFIX."cglinscription_bull as b left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on  b.rowid=bd.fk_bull 
						where bd.fk_activite =  s.rowid ) as nbbull ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " ON p.rowid = s.fk_session_place";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
		$sql .= " ON s.rowid = sa.fk_agefodd_session";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
		$sql .= " ON sf.fk_session = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
		$sql .= " ON s.status = dictstatus.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as calendrier";
		$sql .= " ON s.rowid =  	calendrier.fk_agefodd_session";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_session_extrafields as AgSe  on s.rowid =  AgSe.fk_object ";
		$sql .= " WHERE s.entity IN (" . getEntity ( 'agsession' ) . ")";
		$sql .= " and s.status not in ( 4,5)";
		
		$now = $db->idate(dol_now('tzuser'));
		
		if ($type == 'passe') $sql .= " and TO_DAYS(s.dated )< TO_DAYS('".$now."' ) ";
		elseif ($type <> 'all')  $sql .= " and TO_DAYS(s.dated )>=TO_DAYS('".$now." ') ";
		
		// Manage filter
		if (count ( $filter ) > 0) {
			foreach ( $filter as $key => $value ) {
				if (strpos ( $key, 'date' )) 				// To allow $filter['YEAR(s.dated)']=>$year
				{
					$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
				} elseif (($key == 's.fk_session_place') || ($key == 'f.rowid')  || ($key == 's.status')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif ($key == 's.type_session')  {
					$sql .= ' AND ( isnull(s.type_session) or s.type_session = 1 ) ';
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $agf->db->escape ( $value ) . '%\'';
				}
			}
		}
		$sql .= " GROUP BY s.rowid, s.fk_soc, s.fk_session_place, s.type_session, s.dated, s.status, dictstatus.intitule , dictstatus.code, s.is_date_res_site, s.is_date_res_trainer, s.date_res_trainer, s.color, s.force_nb_stagiaire, s.nb_stagiaire,s.notes,";
		$sql .= " p.ref_interne, c.intitule, s.intitule_custo, c.ref,c.ref_interne, so.nom, f.rowid";
		if ( empty($sortfield)) $sql .= " ORDER BY dated desc";
		$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
	
		if (! empty ( $limit )) {
			$sql .= ' ' . $agf->db->plimit ( $limit + 1, $offset );
		}
		dol_syslog ( get_class ( $agf ) . "::fetch_all sql=" . $sql, LOG_DEBUG );
		$resql = $agf->db->query ( $sql );
		if ($resql) {
			$agf->lines = array ();
			
			$num = $agf->db->num_rows ( $resql );
			$i = 0;
			
			if ($num) {
				while ( $i < $num ) {
					$obj = $agf->db->fetch_object ( $resql );
					
					$timelimite = dol_time_plus_duree(dol_now('tzuser'),-1,'d');
					$now = new DateTime();
					$d = $now->format('d');
					$d = $d-1;
					$l = strlen( '0'.$d) - 2;
					$d = substr ('0'.$d, $l);
					$st_timelimite  = $now->format('Y').$now->format('m').$d;
					$dated = new DateTime($obj->dated);
					$st_dated = $dated->format('Ymd');

					$line = new AgfSessionLine ();					
					$line->rowid = $obj->rowid;
					$line->socid = $obj->fk_soc;
					$line->socname = $obj->socname;
					$line->trainerrowid = $obj->trainerrowid;
					$line->type_session = $obj->type_session;
					if (empty($obj->type_session)) $line->type_session = 1;
					$line->heured =  $obj->heured;
					$line->heuref =  $obj->heuref;
					$line->nbencours =  $obj->nbencours;
					$line->nbbull =  $obj->nbbull;
					$line->is_date_res_site = $obj->is_date_res_site;
					$line->is_date_res_trainer = $obj->is_date_res_trainer;
					$line->date_res_trainer = $agf->db->jdate ( $obj->date_res_trainer );
					$line->fk_session_place = $obj->fk_session_place;
					$line->dated = $agf->db->jdate ( $obj->dated );
					$line->datef = $agf->db->jdate ( $obj->datef );
					$line->intitule = $obj->intitule;
					$line->intitule = $obj->intitule_custo;
					$line->ref = $obj->ref;
					$line->training_ref_interne = $obj->trainingrefinterne;
					$line->ref_interne = $obj->ref_interne;
					$line->color = $obj->color;
					$line->s_ficsess = $obj->s_ficsess;					
					$line->nb_stagiaire = $obj->nb_stagiaire;
					$line->nb_place = $obj->nb_place;
					$line->force_nb_stagiaire = $obj->force_nb_stagiaire;
					$line->notes = $obj->notes;
					$line->duree_session = $obj->duree;					
					$line->nb_subscribe_min = $obj->nb_subscribe_min;
					$line->nb_confirm = $wses->NbPartDep (2,$obj->rowid );
					$line->nb_prospect = $wses->NbPartDep (1,$obj->rowid );
					$line->intitule_custo = $obj->intitule_custo;
					$line->status = $obj->status;				
					if ($obj->statuslib == $langs->trans ( 'AgfStatusSession_' . $obj->code )) {
						$label = stripslashes ( $obj->statuslib );
					} else {
						$label = $langs->trans ( 'AgfStatusSession_' . $obj->code );
					}
					$line->status_lib = $obj->statuscode . ' - ' . $label;
					
					$agf->lines [$i] = $line;
					$i ++;
				}
			}
			$agf->db->free ( $resql );
			return $num;
		} else {
			$agf->error = "Error " . $agf->db->lasterror ();
			dol_syslog ( get_class ( $agf ) . "::fetch_all " . $agf->error, LOG_ERR );
			return - 1;
		}
	} //Fetch_all

	
$filter = array ();
if (! empty ( $search_trainning_name )) {
	$filter ['s.intitule_custo'] = $search_trainning_name;
}
if (! empty ( $search_soc )) {
	$filter ['so.nom'] = $search_soc;
}
if (! empty ( $search_teacher_id )) {
	$filter ['f.rowid'] = $search_teacher_id;
}
if (! empty ( $search_training_ref )) {
	$filter ['c.ref'] = $search_training_ref;
}
if (! empty ( $search_start_date )) {
	$filter ['s.dated'] = $db->idate ( $search_start_date );
}
if (! empty ( $search_end_date )) {
	$filter ['s.datef'] = $db->idate ( $search_end_date );
}
if (! empty ( $search_site ) && $search_site != - 1) {
	$filter ['s.fk_session_place'] = $search_site;
}
if (! empty ( $search_training_ref_interne )) {
	$filter ['c.ref_interne'] = $search_training_ref_interne;
}
if ($search_type_session!='' && $search_type_session != - 1) {
	$filter ['s.type_session'] = $search_type_session;
}
if (! empty ( $status_view )) {
	$filter ['s.status'] = $status_view;
}

if (empty ( $sortorder ))
	$sortorder = "DESC";
if (empty ( $sortfield ))
	$sortfield = "s.dated";
if (empty ( $arch ))
	$arch = 0;

if ($page == - 1) {
	$page = 0;
}

$offset = (int)$limit * (int)$page;
$pageprev = (int)$page - 1;
$pagenext = (int)$page + 1;

$form = new Form ( $db );
$formAgefodd = new FormAgefodd ( $db );



/* préparation de la navigation */
if ('navigation' == 'navigation')  
{
/*a supprimer après V2.8
   $arrayofmassactions = array();
    $massactionbutton = $form->selectMassAction('', $arrayofmassactions);

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
/*	$morehtml .= '<label for="pageplusone">'.$langs->trans("Page")."</label> "; // ' Page ';
	$morehtml .= '<input type="text" name="pageplusone" id="pageplusone" class="flat right width25 pageplusone" value="'.((int)$page + 1).'">';
	$morehtml .= '/'.$nbtotalofpages.' ';
*/
	$morehtml .= '</div>';

//	$morehtml .= '<!-- Add New button -->'.$newcardbutton;

}


if (empty ( $arch ))
	$title = $langs->trans ( "AgfMenuSessAct" );
elseif ($arch == 2)
	$title = $langs->trans ( "AgfMenuSessArchReady" );
else
	$title = $langs->trans ( "AgfMenuSessArch" );
llxHeader ( '', $title );

if ($training_view && ! empty ( $search_training_ref )) {
	$agf = new Agefodd ( $db );
	$result = $agf->fetch ( '', $search_training_ref );
	
	$head = training_prepare_head ( $agf );
	
	dol_fiche_head ( $head, 'sessions', $langs->trans ( "AgfCatalogDetail" ), 0, 'label' );
	
	$agf->printFormationInfo ();
	print '</div>';
}

if ($site_view) {
	$agf = new Site ( $db );
	$result = $agf->fetch ( $search_site );
	
	if ($result) {
		$head = site_prepare_head ( $agf );
		
		dol_fiche_head ( $head, 'sessions', $langs->trans ( "AgfSessPlace" ), 0, 'address' );
	}
	
	$agf->printPlaceInfo ();
	print '</div>';
}

$agf = new Agsession ( $db );
global $agf;

// Count total nb of records
$nbtotalofrecords = 0;
if (empty ( $conf->global->MAIN_DISABLE_FULL_SCANLIST )) {
	$nbtotalofrecords = fetch_all ( $sortorder, $sortfield, 0, 0,  $filter );
}
unset($agf);
$agf = new Agsession ( $db );
$resql = fetch_all (  $sortorder, $sortfield, $limit, $offset, $filter );


if ($resql != - 1) {
	$num = $resql;
	
	if (empty ( $arch ) and $type <> 'passe')
		$menu = $langs->trans ( "AgfMenuSessAct" );
	elseif  (empty ( $arch ) and $type == 'passe')
		$menu = $langs->trans ( "AgfMenuSessPasse" );
	elseif ($arch == 2)
		$menu = $langs->trans ( "AgfMenuSessArchReady" );
	else
		$menu = $langs->trans ( "AgfMenuSessArch" );

// COLONNES A DISCRETION UTILISATEUR
$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

	
	print '<form method="POST" action="' . $url_form . '" name="search_form">' . "\n";
	print '<input type="hidden" name="arch" value="' . $arch . '" >';
	print '<input type="hidden" name="type" value="' . $type . '" >';
    print '<input type="hidden" name="token" value="'.newtoken().'">';
	print '<input type="hidden" name="limit" value="'.$limit.'">';

	$params = '&type='.$type. '&arch=' . $arch ;
	$params .= 	'&search_trainning_name='. $search_trainning_name  ;
	$params .= '&search_soc=' . $search_soc ;
	$params .=  '&search_teacher_name=' . $search_teacher_name ;
	$params .=  '&search_training_ref=' . $search_training_ref ;
	$params .=  '&search_start_date=' . $search_start_date ;
	$params .=  '&search_start_end=' . $search_start_end ;
	$params .=  '&search_site=' . $search_site;
	$params .='&amp;limit='.$limit;
	print_barre_liste ( $menu, (int)$page, $_SERVEUR ['PHP_SELF'], $params, $sortfield, $sortorder, '', $num, $nbtotalofrecords , '', 0, $morehtml, '', $limit, 0, 0, 0);


	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	$arg_url = '&page=' . $page . '&type='.$type. '&arch=' . $arch . '&search_trainning_name=' . $search_trainning_name . '&search_soc=' . $search_soc . '&search_teacher_name=' . $search_teacher_name . '&search_training_ref=' . $search_training_ref . '&search_start_date=' . $search_start_date . '&search_start_end=' . $search_start_end . '&search_site=' . $search_site;

if (! empty($arrayfields['Id_depart']['checked']))		
	print_liste_field_titre ( $langs->trans ( "Id" ), $_SERVEUR ['PHP_SELF'], "s.rowid", "", $arg_url, 'colspan=2' , $sortfield, $sortorder );
if (! empty($arrayfields['AgfDateDebut']['checked']))		
	print_liste_field_titre ( $langs->trans ( "AgfDateDebut" ), $_SERVEUR ['PHP_SELF'], "s.dated", "", $arg_url, '', $sortfield, $sortorder );
if (! empty($arrayfields['HeureDebut']['checked']))		
	print_liste_field_titre ( $langs->trans ( "Heure debut" ) );
if (! empty($arrayfields['HeureFin']['checked']))		
	print_liste_field_titre ( $langs->trans ( "Heure  Fin" ));
if (! empty($arrayfields['Duree']['checked']))		
	print_liste_field_titre ( $langs->trans ( "Duree" ), "", "", "", '' , "", "" );
if (! empty($arrayfields['AgfFormateur']['checked']))		
	if ($type <> 'all') print_liste_field_titre ( $langs->trans ( "AgfFormateur" ), $_SERVER ['PHP_SELF'], "", "", $arg_url, '', $sortfield, $sortorder );
if (! empty($arrayfields['AgfIntitule']['checked']))		
	print_liste_field_titre ( $langs->trans ( "AgfIntitule" ), $_SERVEUR ['PHP_SELF'], "s.intitule_custo", "", $arg_url, '', $sortfield, $sortorder );
if (! empty($arrayfields['Intitule']['checked']))
	print_liste_field_titre ( $langs->trans ( "Intitule" ), $_SERVEUR ['PHP_SELF'], "p.ref_interne", "", $arg_url, '', $sortfield, $sortorder );
if (! empty($arrayfields['Site']['checked']))
	print_liste_field_titre ( $langs->trans ( "AgfLieu" ), $_SERVEUR ['PHP_SELF'], "p.ref_interne", "", $arg_url, '', $sortfield, $sortorder );
if (! empty($arrayfields['Type']['checked']))		
	print_liste_field_titre ( $langs->trans ( "Type" ), $_SERVEUR ['PHP_SELF'], "p.ref_interne", "", $arg_url, '', $sortfield, $sortorder );
if (! empty($arrayfields['LstDepInsc']['checked']))		
	print_liste_field_titre ( $langs->trans ( "LstDepInsc" ), $_SERVEUR ['PHP_SELF'], '', '', $arg_url, '', $sortfield, $sortorder );
if (! empty($arrayfields['Statut']['checked']))		
	print_liste_field_titre ( $langs->trans ( "Statut" ), $_SERVEUR ['PHP_SELF'], "s.status", "", $arg_url, '', $sortfield, $sortorder );
if (! empty($arrayfields['NbEnfant']['checked']))		
	print_liste_field_titre ( $langs->trans ( "NbEnfant" ), $_SERVEUR ['PHP_SELF'], '', '', $arg_url, '', $sortfield, $sortorder );
if (! empty($arrayfields['NbAdulte']['checked']))		
	print_liste_field_titre ( $langs->trans ( "NbAdulte" ), $_SERVEUR ['PHP_SELF'], '', '', $arg_url, '', $sortfield, $sortorder );
	print '<td></td>';
	print "</tr>\n";
	
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
	if (! empty ( $arch )) {
		if ($addcriteria) {
			$url_form .= '&arch=' . $arch;
		} else {
			$url_form .= '?arch=' . $arch;
		}
		$addcriteria = true;
	}
	
	print '<tr class="liste_titre">';
	
	
if (! empty($arrayfields['Id_depart']['checked']))	{	
	print '<td class="liste_titre" colspan=2>';
	print ' ';
	print '</td>';
}
if (! empty($arrayfields['AgfDateDebut']['checked']))	{
	print '<td class="liste_titre">';
	print $form->select_date ( $search_start_date, 'search_start_date', 0, 0, 1, 'search_form' );
	print '</td>';
}
if (! empty($arrayfields['HeureDebut']['checked']))	{	
	print '<td class="liste_titre">';
	print '&nbsp&nbsp';
	print '</td>';
}
if (! empty($arrayfields['HeureFin']['checked']))	{	
	print '<td class="liste_titre">';
	print '&nbsp&nbsp';
	print '</td>';
}
if (! empty($arrayfields['Duree']['checked']))	{	
	print '<td class="liste_titre">';
	print '&nbsp&nbsp';
	print '</td>';
}
if (! empty($arrayfields['AgfFormateur']['checked']))	{	
	print '<td class="liste_titre">';
	if ($type <> 'all') print $formAgefodd->select_formateur ( $search_teacher_id, 'search_teacher_id', '', 1 );
	print '</td>';
	}
if (! empty($arrayfields['Intitule']['checked']))	{			
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_trainning_name" value="' . $search_trainning_name . '" size="20">';
	print '</td>';
}
if (! empty($arrayfields['Site']['checked']))	{			
	print '<td class="liste_titre">';
	print $formAgefodd->select_site_forma ( $search_site, 'search_site', 1 );
	print '</td>';
}
if (! empty($arrayfields['Type']['checked']))	{		
	print '<td class="liste_titre">';
	print $formAgefodd->select_type_session('search_type_session',$search_type_session ,1);
	print '</td>';
}
if (! empty($arrayfields['LstDepInsc']['checked']))	{		
	print '<td class="liste_titre">';
	print info_admin($langs->trans('AideCouleurDepart', $conf->global->CGL_DEPART_SEUIL_RENTABILITE),1);
	print '</td>';
}
if (! empty($arrayfields['Statut']['checked']))	{		
	print '<td class="liste_titre">';
	print ' ';
	print '</td>';
}
if (! empty($arrayfields['NbEnfant']['checked']))	{			
	print '<td class="liste_titre">';
	print ' ';
	print '</td>';
}
	
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag ( $langs->trans ( "Search" ) ) . '" title="' . dol_escape_htmltag ( $langs->trans ( "Search" ) ) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag ( $langs->trans ( "RemoveFilter" ) ) . '" title="' . dol_escape_htmltag ( $langs->trans ( "RemoveFilter" ) ) . '">';

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	
	print "</tr>\n";
	print '</form>';
	
	$var = true;
	
	// recherche seuil de rentbilité
	$min_place = $conf->global->CGL_DEPART_SEUIL_RENTABILITE;
	
	foreach ( $agf->lines as $line ) {
		
		if ($line->rowid != $oldid) {
			//Griser les départs annulés
			if ($line->status == 3) $colortexte='style = "color:#A4A4A4"';
			else $colortexte = '';
			// Affichage tableau des sessions
			$var = ! $var;
			print "<tr $bc[$var]  >";
			// Calcul de la couleur du lien en fonction de la couleur définie sur la session
			// http://www.w3.org/TR/AERT#color-contrast
			// SI ((Red value X 299) + (Green value X 587) + (Blue value X 114)) / 1000 < 125 ALORS
			// AFFICHER DU BLANC (#FFF)
			$couleur_rgb = agf_hex2rgb ( $line->color );
			$color_a = '';
			if ($line->color && ((($couleur_rgb [0] * 299) + ($couleur_rgb [1] * 587) + ($couleur_rgb [2] * 114)) / 1000) < 125)
				$color_a = ' style="color: #FFFFFF;"';

			if (! empty($arrayfields['Id_depart']['checked']))	{
				print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="fichedepart.php?id_depart=' . $line->rowid.'&total=oui&type='.$type.'">' . img_object ( $langs->trans ( "AgfShowDetails" ), "service" ) . ' ' . $line->rowid . '</a></td>';
			
				// Edition 
				print '<td> <font '.$colortexte.">";
									
				// si tous bulletins en cours - supprimer l'icone
				if ($type != "passe" and ($line->nbencours == 0 or $line->nbbull > $line->nbencours ) ) {
					$img='filenew.png'; $texte=$langs->trans('GenFeuilleRoute');
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=genere&id_session='.$line->rowid.'&'.$params.'&page='.$page.'&sortfield='.$sortfield.'&sortorder='.$sortorder.'" >';
					print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/'.$img.'">';
					print '&nbsp&nbsp';
				}	
				
				$ret1 = strlen('cglinscription');
				if ( ($line->nbencours == 0 or $line->nbbull > $line->nbencours  ) and !empty($line->s_ficsess) and file_exists($line->s_ficsess)) {
					$ret1 = strlen('cglinscription');
					$ret = strpos($line->s_ficsess, 'cglinscription');
					$fichier=substr($line->s_ficsess, $ret+$ret1+1);
					// remplacer / par %2F
					//$fich1 = str_replace('/','%2F', $fichier);
					// ODT			
					$fichODT = substr( $fichier, 0, strlen($fichier)-3).'odt';
					$cplfichODT = substr( $line->s_ficsess, 0, strlen($line->s_ficsess)-3).'odt';
					$img = '';
					if ( file_exists ($cplfichODT)) {
						$img=DOL_URL_ROOT.'/theme/common/mime/ooffice.png';
						$texte=$langs->trans('VisFeuilleRoute');
						print'<a href="'.DOL_MAIN_URL_ROOT.'/document.php?modulepart=cglinscription&file='.$fichODT.'" alt="'.$texte.'" title="'.$texte.'">';
						print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.$img.'">';
					}
					// PDF
					$fichPDF = substr( $fichier, 0, strlen($fichier)-3).'pdf';	
					$cplfichPDF = substr( $line->s_ficsess, 0, strlen($line->s_ficsess)-3).'pdf';
					if ( file_exists ($cplfichPDF)) {
						$img=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf2.png';
						$texte=$langs->trans('VisFeuilleRoute');
						print'<a href="'.DOL_MAIN_URL_ROOT.'/document.php?modulepart=cglinscription&file='.$fichPDF.'" alt="'.$texte.'" title="'.$texte.'">';
						print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.$img.'">';
					}			
				}		
				// si 1 ou plusiers bulletin en cours mettre bulle info
				
				if ( $line->nbbull > $line->nbencours and $line->nbencours > 0)
				{		
					print info_admin($langs->trans("AideSesBullEnCours"),1);
				}
				
				print '</td>';
			}
			// date départ			
			if (! empty($arrayfields['AgfDateDebut']['checked']))	{
				print '<td ><font '.$colortexte.'>' . dol_print_date ( $line->dated, 'daytext' ) . '</td>';
			}
			if (! empty($arrayfields['HeureDebut']['checked']))	{			
				print '<td><font '.$colortexte.'>' . dol_print_date ( $line->heured, '%H:%M' ) . '</td>';
			}
			if (! empty($arrayfields['HeureFin']['checked']))	{
				print '<td><font '.$colortexte.'>' . dol_print_date ( $line->heuref, '%H:%M' ) . '</td>';
			}
			if (! empty($arrayfields['Duree']['checked']))	{			// Durée 			
				print '<td>' .price2num($line->duree_session) . '</td>';
						// Moniteur
			}
			if (! empty($arrayfields['AgfFormateur']['checked']))	{
				print '<td><font '.$colortexte.'>' ;
				$trainer = new Agefodd_teacher ( $db );
				if (! empty ( $line->trainerrowid )) {
					$trainer->fetch ( $line->trainerrowid );
				}
				
				if (! empty ( $trainer->id ) and $type <> 'all') {
					print ucfirst ( strtolower ( $trainer->civilite ) ) . ' ' . strtoupper ( $trainer->name ) . ' ' . ucfirst ( strtolower ( $trainer->firstname ) );
				} else {
					print '&nbsp;';
				}
				print '</td>';
			}
			if (! empty($arrayfields['Intitule']['checked']))	{
				// Intitule départ
				print '<td><font '.$colortexte.'>'  . stripslashes ( dol_trunc ( $line->intitule, 60 ) ) . '</td>';
			}
			if (! empty($arrayfields['Site']['checked']))	{
				// Site de pratique
				print '<td><font '.$colortexte.'>'  . stripslashes ( $line->ref_interne ) . '</td>';
			}
			if (! empty($arrayfields['LstDepInsc']['checked']))	{	
				// remplisage			
				if ($line->nb_confirm >= $line->nb_place) {
					$style = 'style="background: white"';
				} elseif   ($line->nb_confirm  < $line->nb_place and $line->nb_confirm  >= $min_place) {
					$style = 'style="background: aqua"';
				} else {
					$style = 'style="background: tomato"';
				}
			}
			if (! empty($arrayfields['Type']['checked']))	{
					print '<td></td>';
			}
			if (! empty($arrayfields['LstDepInsc']['checked']))	{		
	//			print '<td>' .($line->type_session ? $langs->trans ( 'AgfFormTypeSessionInter' ) : $langs->trans ( 'AgfFormTypeSessionIntra' )). '</td>';
				//print '<td ' . $style . '>' . $line->nb_place . '/' . $line->nb_confirm . '/' . $line->nb_prospect. '</td>';
				print '<td ' . $style . '><font '.$colortexte.'>'  . $line->nb_place . ' / ' . $line->nb_confirm .' / '.$line->nb_prospect.'</td>';
				print "</font>\n";
			}
			if (! empty($arrayfields['Statut']['checked']))	{
				// Status 
				if ($line->status == 3 ) $texte = $langs->trans('Annule');
				else $texte='';
				print  '<td><font '.$colortexte.'>'  .$texte . '</td>';
			}

			if (! empty($arrayfields['NbEnfant']['checked']))	{
					print '<td>NbEnf</td>';
			}
			if (! empty($arrayfields['NbAdulte']['checked']))	{
					print '<td>NbAdulte</td>';
			}
			print '</tr>';
		} 
		else {
			print "<tr $bc[$var]>";
			print '<td></td>';
			print '<td></td>';
			print '<td>';
			$trainer = new Agefodd_teacher ( $db );
			if (! empty ( $line->trainerrowid )) {
				$trainer->fetch ( $line->trainerrowid );
			}
			if (! empty ( $trainer->id )) {
				print ucfirst ( strtolower ( $trainer->civilite ) ) . ' ' . strtoupper ( $trainer->name ) . ' ' . ucfirst ( strtolower ( $trainer->firstname ) );
			} else {
				print '&nbsp;';
			}
			print '</td>';
			print '<td></td>';
			print '</tr>';
		}
		
		$oldid = $line->rowid;
		
		$i ++;
	}
	
	print "</table>";
} else {
	setEventMessage ( $agf->error, 'errors' );
}

llxFooter ();
$db->close ();