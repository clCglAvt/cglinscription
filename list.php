<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
 * Version CAV - 2.8 - hiver 2023 - Pagination (suppression Ajout)
 * 								  - vérification de la fiabilité des foreach
 * Version CAV - 2.8.5 - printemps 2023
 *		- tri croissant des inscriptions (evo 331)
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
/*
A FAIRE
Taille des barres de sélections
*/
/**
 *   	\file       custom/cglinscription/list.php
 *		\ingroup    cglinscription
 *		\brief      Liste les inscriptions
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
require_once ('./class/bulletin.class.php');
require_once ('./class/cglinscription.class.php');
require_once('../agefodd/class/html.formagefodd.class.php');
require_once("./../cglavt/class/cglFctDolibarrRevues.class.php");	
require_once("./class/html.formcommun.class.php");
require_once("./class/cgldepart.class.php");

// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");

// Get parameters
$id		= GETPOST('id','int');
$action	= GETPOST('action','alpha');
$myparam	= GETPOST('myparam','alpha');
$page		= GETPOST("page",'int');
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;

// récupération des paramètre de l'URL
$search_datesession=trim(GETPOST("search_datesession",'date'));
$search_session=trim(GETPOST("search_session",'alpha'));
$search_tiers=trim(GETPOST("search_tiers", 'alpha'));
$search_formateur=trim(GETPOST("search_formateur",'alpha'));
$search_fk_raisrem=trim(GETPOST("search_fk_raisrem",'alpha'));
$search_reslibelle=trim(GETPOST("search_reslibelle",'alpha'));
$search_prix=(GETPOST("search_prix",'decimal'));
$sortfield=GETPOST("sortfield",'alpha');
$sortorder=GETPOST("sortorder",'alpha');



if (empty($sortfield)) $sortfield=" AgS.dated";


// Gestion des pages d'affichage des tiers
if ($page == -1) { $page = 0 ; }
if (empty($page)) $page = 0; 
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

global $db;
$bull =  new Bulletin ($db);
global $bull;
$wfc =  new FormCglCommun ($db);
// Protection if external user
if ($user->societe_id > 0) {
	accessforbidden();
}

    /**
     *    	Return a link on thirdparty (with picto)
     *
     *		@param	int	$withpicto	Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
     *		@param	string	$option		Target of link ('', 'customer', 'prospect', 'supplier')
     *		@param	int	$maxlen		Max length of text
     *		@param	int	$id		Identifiant de l'objet
     *		@return	string				String with URL
     */

     function getNomUrl($withpicto=0,$option='',$maxlen=0, $id)
    {
        global $conf,$langs;

        $result='';
		$lienfin='</a>';

		if (empty($id)) return '';
		if ($option == 'MAJInscritp')		{
			$result = '<a href="./inscription.php?id_bull='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifInsc").'">';
		}	
		if ($option == 'MAJLoc')		{
			$result = '<a href="./location.php?id_contrat='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifLoc").'">';
		}				
		elseif ($option == 'Tiers'){
			 $result = '<a href="'.DOL_MAIN_URL_ROOT.'/comm/card.php?socid='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifTiers").'">';
		}	
       $result.=$lienfin;
       return $result;
	}//getNomUrl
		
	function select_tiersinscrit($selected='',$htmlname='socid',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db;

        $out='';
		$now = $db->idate(dol_now('tzuser'));

        // On recherche les societes

		$sql = "SELECT distinct T.rowid, T.nom ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_session as AgS  on bd.fk_activite =  AgS.rowid ";
		$sql.= " left join ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_session_formateur as ASM on AgS.rowid = ASM.fk_session";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_formateur as AM on AM.rowid = ASM.fk_agefodd_formateur ";
		$sql.= " left join ".MAIN_DB_PREFIX."socpeople as CM on AM.fk_socpeople = CM.rowid ";
		$sql.= " left join ".MAIN_DB_PREFIX."user as UM on AM.fk_user = UM.rowid ";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_formation_catalogue as AP on AgS.fk_formation_catalogue = AP.rowid ";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_session_calendrier as ASCl on AgS.rowid = ASCl.fk_agefodd_session ";
		$sql.= " WHERE (isnull(heured) or heured  >= '".$now."' ) and b.ref like 'BU".dol_print_date($now,"%Y")."%' ";
		$sql .=" order by T.nom ";
        $resql=$db->query($sql);
        if ($resql)
        {
            $out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
            if ($showempty) $out.= '<option value="-1"></option>';
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    $label=$obj->nom;
                    if ($selected > 0 && $selected == $obj->rowid)
                    {
                        $out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
                    }
                    else
                    {
                        $out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
                    }
                    $i++;
                }
            }
            $out.= '</select>';
        }
        else
        {
            dol_print_error($db);
        }

        return $out;
    }/* select_tiersinscrit */
	
	function NbPmtNeg ($id) 
	{
		global $langs, $db;

		// si modification, penser à bulletin, listloc et facturation
		
			$sql = "SELECT count(bd.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull";
		$sql.= " WHERE bd.type = 1 and pt  <0 and bd.action not in ('X','S') ";
		$sql.= " and b.rowid = '".$id."'";	
        $resql=$db->query($sql);
        if ($resql)   {
            if ($db->num_rows($resql) > 0) {
                $obj = $db->fetch_object($resql);
				return(  $obj->nb);
			}
			else return 0;
		}
		else return -1;		
	} // NbPmtNeg
	

/* préparation de la navigation */
if ('navigation' == 'navigation')  
{
 /*
 $arrayofmassactions = array();
 	$form = new Form($db);
    $massactionbutton = $form->selectMassAction('', $arrayofmassactions);
	unset($form);
*/

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

/* a supprimer après V2.8
	$morehtml = '<div class="inline-block '.(($buttonreconcile || $newcardbutton) ? 'marginrightonly' : '').'">';
	$morehtml .= '<label for="pageplusone">'.$langs->trans("Page")."</label> "; // ' Page ';
	$morehtml .= '<input type="text" name="pageplusone" id="pageplusone" class="flat right width25 pageplusone" value="'.($page + 1).'">';
	$morehtml .= '/'.$nbtotalofpages.' ';
	$morehtml .= '</div>';

	$morehtml .= '<!-- Add New button -->'.$newcardbutton;
*/
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('',$langs->trans('Lcglinscription'));

$form=new Form($db);
$w = new CglInscription($db) ;
$wfcom = new CglFonctionCommune($db) ;


// Put here content of your page
/*******************************************************************
* TEST
*
********************************************************************/
$help_url='FR:Module_Inscription';

$title=$langs->trans("ListeDesInscriptions");

// Clique sur bouton Vider les filtres
if (GETPOST("button_removefilter_x",'alpha'))
{
    $search_datesession='';
	$search_session="";
	$search_tiers="";
	$search_prix="";
	$search_formateur="";
	$search_fk_raisrem="";
	$search_reslibelle="";
	$sortfield="";
	$sortorder="";
}
		
// construction SQL de recherche

$now = $db->idate(dol_now('tzuser'));

if ('OrdreSql' =='OrdreSql') {
	$sql = "SELECT distinct b.rowid as rowid, AgS.intitule_custo, AgS.rowid as id_act, T.nom, T.rowid as id_client, b.statut, b.regle, b.ref, b.ObsPriv, ";
	$sql .= "CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(u.firstname,' '),u.lastname) ELSE '' END  as  createur, AM.rowid as id_moniteur,  ";
	$sql.= "case when isnull(UM.lastname) then concat(CM.lastname ,' ',CM.firstname ) else concat(UM.lastname,' ',UM.firstname ) end AS Moniteur, ";
	$sql.= "AgS.dated,ASCl.heured,ASCl.heuref, AgS.type_session  ";
	$sql.= ",  sum(bd.qte) as NbFam , ActionFuture, PmtFutur ";
	$sql.= ", GROUP_CONCAT(DISTINCT (SELECT  CONCAT(rem.libelle, CONCAT(' - ',bd1.reslibelle))   FROM  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 ";
	$sql.= " left join  ".MAIN_DB_PREFIX."cgl_c_raison_remise as rem  on bd1.fk_raisrem = rem.rowid  WHERE bd1.fk_bull = b.rowid and bd1.type = 2 and bd1.action not in ('S','X')) SEPARATOR ' / ') as InfoRem ";
	$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull and (isnull(bd.action) or bd.action not in ('S','X')) and bd.type = 0 ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as AgS  on bd.fk_activite =  AgS.rowid ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_formateur as ASM on AgS.rowid = ASM.fk_session";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formateur as AM on AM.rowid = ASM.fk_agefodd_formateur ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as CM on AM.fk_socpeople = CM.rowid ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as UM on AM.fk_user = UM.rowid ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as AP on AgS.fk_formation_catalogue = AP.rowid ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_calendrier as ASCl on AgS.rowid = ASCl.fk_agefodd_session ";
	$sql .= " , llx_user as u ";
	$sql .=" WHERE u.rowid = fk_createur AND b.typebull = 'Insc'  ";
	$sql.= "  AND (isnull( ASCl.heured) or  ADDDATE(ASCl.heured,INTERVAL 1 DAY) >= '".$now."') ";
	$sql .= " AND substr( b.ref, 3,4) = year('".$now."') ";
	$sql .= " AND b.regle < '".$bull->BULL_FACTURE."' and b.statut<".$bull->BULL_CLOS."  ";
	$sqlgroup = " GROUP BY b.rowid, AgS.intitule_custo, T.nom, UM.lastname,CM.lastname,CM.firstname,UM.lastname, AgS.dated,ASCl.heured,ASCl.heuref";

	if ($search_datesession and !($wfcom->transfDateMysql(  $search_datesession ) == dol_print_date( dol_now('tzuser'),'%Y-%m-%d')))
	{
		$sql.= " AND ASCl.heured between '".$wfcom->transfDateMysql($search_datesession)."' and  ADDDATE('".$wfcom->transfDateMysql($search_datesession)."',INTERVAL 1 DAY)";
	}


	if ($search_session > 0)
	{
		$sql.= " AND AgS.rowid =".$db->escape($search_session);
	}
	if ($search_tiers and !($search_tiers == -1)) // search_tiers est à -1 quand on n'a pas fait de choix dur tiers à cause du select
	{
		$sql.= " AND T.rowid ='".$db->escape($search_tiers)."'";
	}
	if ($search_formateur)
	{
		$sql.= " AND AM.rowid = ".$db->escape($search_formateur);	 
	}
	if ($search_fk_raisrem and $search_fk_raisrem > -1)
	{
		$sql.= " AND EXISTS (SELECT (1) FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 WHERE type = 2 and bd1.fk_bull = b.rowid and bd1.fk_raisrem = ".$search_fk_raisrem." )";	 
	}	
	if ($search_reslibelle)
	{
		$sql.= " AND EXISTS (SELECT (1) FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 WHERE type = 2 and bd1.fk_bull = b.rowid and bd1.reslibelle LIKE '%".$search_reslibelle."%' )";	 
	}
	$sql .=$sqlgroup;

	// Compte le nb total d'enregistrements
	$nbtotalofrecords = 0;
	
/* requete
SELECT distinct b.rowid as rowid, AgS.intitule_custo, AgS.rowid as id_act, T.nom, T.rowid as id_client, b.statut, b.regle, b.ref, b.ObsPriv, 
CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(u.firstname,' '),u.lastname) ELSE '' END as createur, AM.rowid as id_moniteur, 
case when isnull(UM.lastname) then concat(CM.lastname ,' ',CM.firstname ) else concat(UM.lastname,' ',UM.firstname ) end AS Moniteur, 
AgS.dated,ASCl.heured,ASCl.heuref, AgS.type_session , sum(bd.qte) as NbFam , ActionFuture, PmtFutur , 
GROUP_CONCAT(DISTINCT (SELECT CONCAT(rem.libelle, CONCAT(' - ',bd1.reslibelle)) FROM llx_cglinscription_bull_det as bd1 left join llx_cgl_c_raison_remise as rem on bd1.fk_raisrem = rem.rowid WHERE bd1.fk_bull = b.rowid and bd1.type = 2 and bd1.action not in ('S','X')) SEPARATOR ' / ') as InfoRem 

FROM llx_cglinscription_bull as b LEFT JOIN llx_cglinscription_bull_det as bd on b.rowid=bd.fk_bull and (isnull(bd.action) or bd.action not in ('S','X')) and bd.type = 0 
LEFT JOIN llx_agefodd_session as AgS on bd.fk_activite = AgS.rowid LEFT JOIN llx_societe as T on b.fk_soc = T.rowid 
LEFT JOIN llx_agefodd_session_formateur as ASM on AgS.rowid = ASM.fk_session LEFT JOIN llx_agefodd_formateur as AM on AM.rowid = ASM.fk_agefodd_formateur 
LEFT JOIN llx_socpeople as CM on AM.fk_socpeople = CM.rowid LEFT JOIN llx_user as UM on AM.fk_user = UM.rowid 
LEFT JOIN llx_agefodd_formation_catalogue as AP on AgS.fk_formation_catalogue = AP.rowid 
LEFT JOIN llx_agefodd_session_calendrier as ASCl on AgS.rowid = ASCl.fk_agefodd_session , 
llx_user as u 

WHERE u.rowid = fk_createur AND

 b.typebull = 'Insc' AND
 (isnull( ASCl.heured) or ADDDATE(ASCl.heured,INTERVAL 1 DAY) >= '2022-03-04 20:24:50') 
AND substr( b.ref, 3,4) = year('2022-03-04 20:24:50') 
AND		b.regle < '5' and b.statut<4 
 

GROUP BY b.rowid, AgS.intitule_custo, T.nom, UM.lastname,CM.lastname,CM.firstname,UM.lastname, AgS.dated,ASCl.heured,ASCl.heuref 
*/	
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$result = $db->query($sql);
		if ($result	)   
		{
			$nbtotalofrecords = $db->num_rows($result);
		}
	}

	$sql.= $db->order($sortfield,$sortorder);
	$sql.= $db->plimit($limit+1, $offset);
	dol_syslog('Liste des bulletins',LOG_DEBUG);
	$resql = $db->query($sql);

	if ($resql	)   {
		$num = $db->num_rows($resql);
	}

}

 
// affichage barre de sélection
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
// ? a quoi servent ces deux lignes ??
print '<input type="hidden" name="token" value="'.newtoken().'">';

// permet d'afficher le petit livre, le titre la succession des page et le numéro de la  page courante
// paramètres a passer dans les boutons de page successives
$params = "&amp;search_datesession=".$search_datesession."&amp;search_session=".$search_session;
$params.= "&amp;search_tiers=".$search_tiers."&amp;search_formateur=".$search_formateur;
$params.="&amp;reslibelle=".$search_reslibelle."&amp;fk_raisrem=".$search_fk_raisrem;

print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$num,$nbtotalofrecords, '', 0, $morehtml, '', $limit, 0, 0, 0);
 
// début barre de selection
print '<table class="liste" width="100%">';

/*	$moreforfilter='';
	$htmlother=new FormOther($db);
    $moreforfilter.=$htmlother->select_categories(2,$search_categ,'search_categ');
    $moreforfilter.=' &nbsp; &nbsp; &nbsp; ';*/

    // affiche la barre grise de titres des filtres

if ('BarreTitre' =='BarreTitre') {
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print_liste_field_titre("LbTypeRemise",$_SERVER["PHP_SELF"],"","","",'',"","");
	print '<td class="liste_titre">';
	$wfc1 = new FormCglCommun ($db);
	print $wfc1->select_nomremise($search_fk_raisrem,'search_fk_raisrem',1, '',0,1);
	unset($wfc1 );
	print_liste_field_titre("LbLibelRemise",$_SERVER["PHP_SELF"],"","","",'',"","");
	print '<td class="liste_titre">';
	print '<input type="flat" name="search_reslibelle" value="'.$search_reslibelle.'">';
	print '<td class="liste_titre">';
	print "</td/tr>\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("DateDepart",$_SERVER["PHP_SELF"],"AgS.dated","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"T.nom","",$params,"",$sortfield,$sortorder);
	//print_liste_field_titre("NbPart",$_SERVER["PHP_SELF"],"NbPart","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("Session",$_SERVER["PHP_SELF"],"AgS.intitule_custo","",$params,'colpsan=3',$sortfield,$sortorder);
	print_liste_field_titre('','','',"",'','','','');
	print_liste_field_titre("UNAgfFormateur",$_SERVER["PHP_SELF"],"Moniteur","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre('','','',"",'','','','');
	print "</td></tr>\n";

  
	print '<tr class="liste_titre">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<td class="liste_titre">';
	//print '<input class="flat" type="text" name="search_datesession" value="'.$search_datesession.'">';
	
	if (empty($search_datesession) or $search_datesession == 0) $temp = -1;
	else $temp =dol_stringtotime($search_datesession); 							
	$form->select_date($temp,'search_datesession','','','',"add",1,1); 	
							
	print '</td>';
	print '	<td class="liste_titre">';
	
	print select_tiersinscrit('','search_tiers',1, 1);
	 
	print '</td><td class="liste_titre" colspan=2>';
	//* Choix  des départs*/
	$formInscription = new CglFonctionDolibarr($db);
	$now = $db->idate(dol_now('tzuser'));
	print $formInscription->select_session($search_session, 'search_session', "intitule",1,0,array(), array('and s.dated >= "'.$now.'"')) ;
	unset($formInscription);
	print '</td><td class="liste_titre">';
	//* Choix des moniteurs*/
	$formAgefodd = new FormAgefodd($db);	
	print $formAgefodd->select_formateur($search_formateur, 'search_formateur',"",1);
	print '	</td>';
 
	// boutons de validation et suppression du filtre
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print "</tr>\n";
	print "</table>\n";

	$var=True;
	$i=0;
 	print "<p>    </p>\n";
	print '<table class="liste" width="100%">';
	
	// affiche la barre grise des champs affichés
	print '<tr class="liste_titre">';
	print_liste_field_titre("DateDepart",$_SERVER["PHP_SELF"],"AgS.dated","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("TiRegle",$_SERVER["PHP_SELF"],"regle","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre("TiPaiement",$_SERVER["PHP_SELF"],"regle","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre("TiAction",'','','','','','','');
	print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"T.nom","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre("Session",$_SERVER["PHP_SELF"],"AgS.intitule_custo","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("NbFam",$_SERVER["PHP_SELF"],"NbFam","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre("Type",$_SERVER["PHP_SELF"],"type_session","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("UNAgfFormateur",$_SERVER["PHP_SELF"],"Moniteur","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("HeureDebut",$_SERVER["PHP_SELF"],"heured","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"ref","",$params,'',$sortfield,$sortorder);
//	print_liste_field_titre("TTC",$_SERVER["PHP_SELF"],"price_ttc","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("TiCreateur",$_SERVER["PHP_SELF"],"createur","",$params,'',$sortfield,$sortorder);
//	print_liste_field_titre("TiObs",'',"","",'','','','');
	print_liste_field_titre("TiStatut",$_SERVER["PHP_SELF"],"statut","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre('','',"","",'','','','');
// amener le dessin gris jusqu'en fin de ligne  
	print "</td/tr>\n";
}
	$objformsession = new FormAgefodd($db);
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);
		print "<tr $bc[$var]>";
		if ($obj->statut == $bull->BULL_ENCOURS) {  // en gras 
			$gras = '<b>';
			$fingras = '</b>';
		}	
		else	{  // retour normal
			$gras = '';
			$fingras = '';
		}		

		print "<td>";
		/* affiche l'image pour la selection */
		
		print " ".$gras.$wfcom->transfDateFr($obj->dated)."</td>\n";	
		$img = '';
/*
		if ($obj->regle == $bull->BULL_NON_PAYE  and ($obj->montantdu > 0 or $obj->statut == $bull->BULL_INS )) { $img=''; $texte='';}
		elseif ($obj->regle ==$bull->BULL_INCOMPLET) {$img=$bull->IMG_INCOMPLET; $texte=$bull->LIB_INCOMPLET;}
		elseif ($obj->regle ==$bull->BULL_PAYE) {$img=$bull->IMG_PAYE; $texte=$bull->LIB_PAYE;}
		elseif ($obj->regle ==$bull->BULL_SURPLUS) {$img=$bull->IMG_SURPLUS; $texte=$bull->LIB_SURPLUS;}
		elseif ($obj->regle ==$bull->BULL_REMB) {$img=$bull->IMG_REMB; $texte=$bull->LIB_REMB;}
		else  {$img=''; $texte='Autre:'.$obj->regle;}
		if ($img) print '<td><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
		else print '<td>';
		if (!empty($obj->PmtFutur)) {	$texte =  $obj->PmtFutur; 		print info_admin($texte,1); }
		print '</td>';
*/
		print '<td>';
		$wfrmcm = new FormCglCommun ($db);
		print $wfrmcm->AffichImgRegleBull( $obj->regle, 'Insc',$obj->statut, $obj->dated, $obj->fk_facture, $obj->abandon);
		print '</td>';	
		
		// Paiement futur
		/*if (!empty($obj->PmtFutur)) {
			$texte =  $obj->PmtFutur; 
			print '<td><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png"></td>';
		}		
		else print '<td>';
		print '</td>';
		*/
		// Action Future
		/*print "<td>";
		if (!empty($obj->ActionFuture)) {
			$texte = $obj->ActionFuture; 
			print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png">';
		}
		print "</td>";
		*/
		print "<td>".getNomUrl("object_company.png", 'Tiers',0,$obj->id_client)."&nbsp".$gras.$obj->nom."</td>";
		print '<td>'.$wfc->getNomUrl("object_company.png", 'DepartEcran',0,$obj->id_act,'');
		print "   ".$gras.$obj->intitule_custo."</td>";
		print "<td>".$gras.$obj->NbFam."</td>";
		if ( empty($obj->intitule_custo)) $type_session = '';
		else 		$type_session = $w->CherchStrTypeSession($objformsession->type_session_def,$obj->type_session);
		//print "<td>".$gras.$type_session."</td>";
		print '<td>';
		if (!empty($obj->Moniteur)) {
			print $wfc->getNomUrl("object_company.png", 'Moniteur',0,$obj->id_moniteur,'');
			print " ".$gras.$obj->Moniteur;
		}
		print "</td>";
		print '<td>';
		if (!empty($obj->heured)) print $gras.$wfcom->transfHeureFr($obj->heured);	
		print "</td>";
		print "<td>".getNomUrl("object_company.png", 'MAJInscritp',0,$obj->rowid)."&nbsp".$gras.$obj->ref;
		if (!empty($obj->InfoRem)) { $texte = $langs->trans("Remise").': '.$obj->InfoRem;  print info_admin($texte,1); }
		print "</td>";	
	//	print "<td>".$gras.number_format ( $obj->PTT , 2 , ',' , ' ' )."</td>";
	//	print "<td>".$obj->price_ttc."</td>\n";
		print "<td>".$gras.$obj->createur.$fingras."</td>";
// Obervation privee - Info
		/*
		print "<td>";
		if ( !empty($obj->ObsPriv )) {
			$text = $langs->trans("DefObsPriv").':'.$obj->ObsPriv;
		print info_admin($text,1);
		}
			print "</td>";
*/
//print '<input type="image" src="../../theme/'.$conf->theme.'/img/edit.png" border="0" name="tiers_edit" alt="'.$langs->trans("Modif").'">';
		print "</td>\n";
/*		print '<td>';
		$texte='';
		$img = '';
		if ($obj->statut == $bull->BULL_ENCOURS) { $img=$bull->IMG_ENCOURS ; $texte=$bull->LIB_ENCOURS;}
		if ($obj->statut == $bull->BULL_PRE_INS) { $img=$bull->IMG_PRE_INS ; $texte=$bull->LIB_PRE_INS;}
		elseif ($obj->regle ==0 and $obj->statut ==1 and !empty($obj->fk_facture)) {$img=$bull->IMG_FACT_INC; $texte=$bull->LIB_FACT_INC;}
		elseif ($obj->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_FACTURE; $texte=$bull->LIB_FACT_INC;}
		elseif ($obj->statut == $bull->BULL_INS) {$img=$bull->IMG_INS; $texte=$bull->LIB_INS;}
		elseif ($obj->statut == $bull->BULL_CLOS) {$img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
		elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
		if (empty($img) and !empty($texte)) 
			print info_admin($texte,1);		
		elseif (!empty($texte))
			print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
		else print $obj->statut;
		print '</td>';
*/
		print '<td>';
		print $wfrmcm->AffichImgStatutBull($obj->statut, $obj->regle, 'Insc', $obj->fk_facture);
		unset ($wfrmcm);
		print '</td>';	

		$nb = NbPmtNeg($obj->rowid);
		print "<td>";
		if ($nb >0) {
			if ($nb > 1) $text = $langs->trans("DefPmtNegs");
			else $text = $langs->trans("DefPmtNeg");
			print info_admin($text,1);
		}
		print "</td>";
		print "</tr>\n";
		$var=!$var;
		$i++;
	}	// while
	
	
	unset ($w);


// End of page
llxFooter();
$db->close();

?>
