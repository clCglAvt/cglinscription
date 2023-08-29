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
 
 
/**
 *   	\file       custom/cglinscription/listresa.php
 *		\ingroup    cglinscription
 *		\brief      Liste des demande de réservations d'activité
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
// Change this following line to use the correct relative path from htdocs
//dol_include_once ('/class/cgllocation.class.php');
require_once ('./class/cgllocation.class.php');
require_once ('./class/bulletin.class.php');
require_once ('../cglavt/class/cglFctCommune.class.php');	
require_once("./class/html.formcommun.class.php");

// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");

// Get parameters
if ('Get parameters' == 'Get parameters') {
	$id		= GETPOST('id','int');
	$action	= GETPOST('action','alpha');
	$myparam	= GETPOST('myparam','alpha');
	$page		= GETPOST("page",'int');
	$search_ref=(GETPOST("search_ref", 'int'));
	$search_tiers=trim(GETPOST("search_tiers", 'alpha'));
	$search_DateRetrait=trim(GETPOST("search_DateRetrait", 'date'));

	if (empty($sortfield)) $sortfield=" b.datec";
	if (empty($sortorder)) $sortorder=" DESC ";

	// Gestion des pages d'affichage des tiers
	if ($page == -1) { $page = 0 ; }
	if (empty($page)) $page = 0; 
	$offset = $conf->liste_limit * $page ;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	}
// Protection if external user
if ($user->societe_id > 0)
{
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

		if ($option == 'MAJResa')		{
			$result = '<a href="./reservation.php?id_resa='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifResa").'">';
		}					
		elseif ($option == 'Tiers'){
			 $result = '<a href="'.DOL_MAIN_URL_ROOT.'/comm/card.php?socid='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}	
       $result.=$lienfin;
       return $result;
	   
	}//getNomUrl
	
	function select_tiers($selected='',$htmlname='socid',$showempty=0, $forcecombo=0, $type ='')
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';
		$now = $db->idate(dol_now('tzuser'));
        // On recherche les societes
		$sql = "SELECT distinct T.rowid, T.nom ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
			$sql.= " left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull";	
		if ($type == 'exterieur'  ){
			$sql .= " left join ".MAIN_DB_PREFIX."societe as T on bd.fk_fournisseur = T.rowid";
			$sql.= " WHERE YEAR(bd.datedepose) = YEAR('".$now."') ";
			$sql .=' AND nom <> "'.$conf->global->MAIN_INFO_SOCIETE_NOM .'"';
		}
		else {	
			$sql.= " left join ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";		
			if (empty($type)  ) 		
				$sql.= " WHERE b.statut <  ".$bull->BULL_CLOS ;
			elseif ($type == 'retour') $sql.= " WHERE b.statut  < ".$bull->BULL_CLOS."  ";
			elseif ($type == 'caution') $sql.= " WHERE b.ret_caution = 0  and fk_caution > 0 ";
		}
		$sql .=" AND bd.action not in ('S','X') ";
		$sql .= " AND b.typebull = 'Resa'";		
		$sql .= " ORDER BY T.nom";	
		
        $resql=$db->query($sql);
        if ($resql)        {
            $out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
            if ($showempty) $out.= '<option value="-1"></option>';
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num)     {
                while ($i < $num)        {
                    $obj = $db->fetch_object($resql);
                    $label=$obj->nom;
                    if ($selected > 0 && $selected == $obj->rowid)        $out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
                    else                        $out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
                    $i++;
                }
            }
            $out.= '</select>';
        }
        else  dol_print_error($db);

        return $out;
		
    }/* select_tiers */

	function select_refresa($selected='',$htmlname='search_ref',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';

        // On recherche les societes

		$sql = "SELECT distinct b.rowid, b.ref ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull";	
		if (empty($type)  ) 		
			$sql.= "  WHERE b.statut <  ".$bull->BULL_CLOS;
		elseif ($type == 'retour') $sql.= " WHERE b.statut  < ".$bull->BULL_CLOS."  ";
		elseif ($type == 'caution') $sql.= " WHERE b.ret_caution = 0  and fk_caution > 0";
		$sql .=" AND bd.action not in ('S','X') ";
		$sql .= " AND b.typebull = 'Resa'";
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
                    $label=$obj->ref;
                    if ($selected >0 && $selected == $obj->rowid)
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
    }/* select_refresa */
	
	function select_statut($selected='',$htmlname='search_statut' )
    {
        global $conf,$user,$langs, $db, $bull;

 /*       $out='';

        // On recherche les societes
		$sql = "SELECT distinct b.statut ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on fk_bull = b.rowid ";
		if (empty($type)  ) 		
			$sql.= "  WHERE b.statut <  ".$bull->BULL_CLOS;
		elseif ($type == 'retour') $sql.= " WHERE b.statut  < ".$bull->BULL_CLOS."  ";
		elseif ($type == 'caution') $sql.= " WHERE b.ret_caution = 0  and fk_caution > 0";
		$sql .= " AND b.typebull = 'Resa' ORDER BY 1";
        $resql=$db->query($sql);
        if ($resql)
        {
            $out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
            $out.= '<option value="-1"></option>';
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
					if ($obj->statut == $selected) $sel = 'selected="selected"';
					else $sel = '';
					if ($obj->statut == $bull->BULL_ENCOURS) 
						$out.= '<option value="'.$obj->statut.'" '.$sel.'>'.$bull->LIB_CNT_ENCOURS.'</option>';
					elseif ($obj->statut == $bull->BULL_VAL)
						$out.= '<option value="'.$obj->statut.'" '.$sel.'>'.$bull->LIB_VAL.'</option>';
					elseif ($obj->statut == $bull->BULL_DEPART)
						$out.= '<option value="'.$obj->statut.'"'.$sel.'>'.$bull->LIB_DEPART.'</option>';
					elseif ($obj->statut == $bull->BULL_RETOUR)
						$out.= '<option value="'.$obj->statut.'" '.$sel.'>'.$bull->LIB_RETOUR.'</option>';
					elseif ($obj->statut == $bull->BULL_CLOS)
						$out.= '<option value="'.$obj->statut.'"'.$sel.'>'.$bull->LIB_CLOS.'</option>';					
                    $i++;
                }
            }
            $out.= '</select>';
        }
        else
        {
            dol_print_error($db);
        }
		unset ($bull);
        return $out;
		*/
    }/* select_statut */


/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('',$langs->trans('LcglreservationLst'));

$form=new Form($db);
$w = new CglFonctionCommune($db) ;
global $db, $bull;
$bull = new Bulletin($db);
$now = $db->idate(dol_now('tzuser'));


// Put here content of your page
/*******************************************************************
* TEST
*
********************************************************************/
$help_url='FR:Module_Inscription';

// Clique sur bouton Vider les filtres
if (GETPOST("button_removefilter_x", 'alpha'))
{
/*    $search_DateRetrait='';
    $search_DateDepose='';
	$search_mat="";
	$search_marque="";
	$search_refmat="";
	$search_statut="";
	$sortfield="";
	$sortorder="";
	$search_serv="";
*/
	$search_ref="";
	$search_tiers="";
}

// construction SQL de recherche
if ('OrdreSql' == 'OrdreSql') {
	$sql = "SELECT";
	$sql .= " distinct ";
	$sql .= " b.rowid as rowid,  T.nom, T.rowid as id_client, b.statut, b.regle ,  b.ref , b.ObsPriv, b.ActionFuture, b.PmtFutur ";
	$sql .= ", CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END  as  createur , dateretrait as heured";
	$sql .= ", case when TO_DAYS(dateretrait )>=TO_DAYS('".$now."') then 0 else 1 end  as datedepassee"; 
	$sql .= ", case when isnull(b.datec) or b.datec = 0 then 0 else b.datec end as DateOrdre";
	$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";
	$sql .= "  LEFT JOIN  llx_user as u  on u.rowid = fk_createur ";
	$sql .= "  WHERE  b.typebull = 'Resa' ";
	$sql .= " and ( (b.regle < '5' and b.statut< '4') or b.statut=0)   ";
	$sql .= "  and (isnull(b.dateretrait) or b.dateretrait >= now()  )";
	if ($sall) 
		$sql .=  natural_search('b.ref', $sall);
	else
	$sql .= " AND b.ref like concat(concat('RE',year('".$now."')),'%') ";

	if ($search_ref > 0)		$sql.= " AND b.rowid =".$db->escape($search_ref);
/*	if ($search_statut > 0)
		$sql.= " AND b.statut =".$db->escape($search_statut);
	*/
	if ($search_tiers and $search_tiers >= 0) 	$sql.= " AND T.rowid ='".$db->escape($search_tiers)."'";
	// Compte le nb total d'enregistrements
	$nbtotalofrecords = 0;

	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$result = $db->query($sql);
		if ($result	)   
		{
			$nbtotalofrecords = $db->num_rows($result);
		}
	}
	//$sql.= $db->order($sortfield,$sortorder);
	//$sql.= $db->order($sortfield,$sortorder);
	if ($sortfield == ' b.datec') {
		$sql.= ' ORDER BY DateOrdre '.$sortorder;
	}
	else $sql.= ' ORDER BY '.$sortfield. ' '.$sortorder;
	$sql.= $db->plimit($conf->liste_limit+1, $offset);
	$resql = $db->query($sql);
	if ($resql	)   {
		$num = $db->num_rows($resql);
	}
}

if ('Entete' == 'Entete') {
	// permet d'afficher le petit livre, le titre la succession des page et le numéro de la  page courante
	// paramètres a passer dans les boutons de page successives
	$params = "&type=".$type."&amp;search_DateRetrait=".$search_DateRetrait."&amp;search_DateDepose=".$search_DateDepose;
	$params.= "&amp;search_tiers=".$search_tiers."&amp;search_ref=".$search_ref."&amp;search_serv=".$search_serv."&amp;search_mat=".$search_mat;
	$params.="&amp;search_refmat=".$search_refmat."&amp;search_statut=".$search_statut;


	$title=$langs->trans("ListeResa");

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$nbtotalofrecords,$nbtotalofrecords);

	// affichage barre de sélection
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	// ? a quoi servent ces deux lignes ??
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="token" value="'.newtoken().'">';

	// début barre de selection
	print '<table class="liste" width="100%">';

    // affiche la barre grise de titres des filtres
	print '<tr class="liste_titre">';
	print_liste_field_titre("Datec",$_SERVER["PHP_SELF"],'',"",'','','','');
	print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"","",'',"",$sortfield,$sortorder);
	print_liste_field_titre("RefCnt",$_SERVER["PHP_SELF"],"","",'','',$sortfield,$sortorder);
	print "</tr>\n";

	/* pour la liste des location : date retrait/date depose/tiers/ref bull/Nb Velo
	* pour la liste de matériel : date retrait/date depose/tiers/ref mzt
	* pour la liste des retours : date retrait/date depose/tiers/ref bull
	* pour la liste des cautions : date retrait/date depose/tiers/montant caution
	*/
	print '<tr class="liste_titre">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<td class="liste_titre">';
	if (empty($search_DateRetrait) or $search_DateRetrait == 0) $temp = -1;
	else $temp =dol_stringtotime($search_DateRetrait); 
	$form->select_date($temp,'search_DateRetrait','','','',"add",1,1); 		
	print '</td>';
	print '	<td class="liste_titre">';
	
	print select_tiers($search_tiers,'search_tiers',1, 1, $type);
	 
	print '</td><td>';
	print select_refresa($search_ref,'search_ref',1,1);
	//print '</td><td>';
	//	print select_statut($search_statut,'search_statut');
	print '</td>';
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
 	print "<p>&nbsp;</p>\n";
 	print "<p>&nbsp;</p>\n";
	print '<table class="liste" width="100%">';
	
	// affiche la barre grise des champs affichés
	print '<tr class="liste_titre">';

	/* pour la liste des location : date retrait/date depose/tiers/ref bull/Nb Velo
	* pour la liste de matériel : date retrait/date depose/tiers/ref mzt
	* pour la liste des retours : date retrait/date depose/tiers/ref bull
	* pour la liste des cautions : date retrait/date depose/tiers/montant caution
	*/
	
	
	print_liste_field_titre("Date",$_SERVER["PHP_SELF"],"heured","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("TiAction",'','','','','','','');
	print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"nom","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre("Reference",$_SERVER["PHP_SELF"],"ref","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre("TiCreateur",$_SERVER["PHP_SELF"],"createur","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("TiObs",'',"","",'','','','');
	print_liste_field_titre("TiStatut",$_SERVER["PHP_SELF"],"statut","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre('','',"","",'','','','');
	
	print "</td/tr>\n";
}
	
	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$bull->lines[] = $obj;
		$gras = '';
		$fingras = '';
		$style='';
		if ($obj->statut == $bull->BULL_ENCOURS) {  // en gras les bulletins en cours
			$gras = '<b>';
			$fingras = '</b>';
		}
		else
		{  // retour normal
			$gras = '';
			$fingras = '';
		}		
		if ($obj->datedepassee) {
			$style='style="color:grey"';
		}
		else
		{  // retour normal
			$style='';
		}	
		
		print "<tr $bc[$var] $style>";
		print "<td>";
		print " ".$gras.dol_print_date($obj->heured,'day')."&nbsp&nbsp&nbsp;</td>\n";
				
		print "<td>";
		if (!empty($obj->ActionFuture)) {
			$texte = $obj->ActionFuture; 
			print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png">';
		}
		print "</td>";

		print "<td>".getNomUrl("object_company.png", 'Tiers',0,$obj->id_client)."&nbsp".$gras.$obj->nom."</td>";		
		print '<td '.$style.'>'.$gras;
		print getNomUrl("object_company.png", 'MAJResa',0,$obj->rowid)."&nbsp";
		print $gras.$obj->ref."</td>";
		print "<td ".$style.">".$gras.$obj->createur."</td>";
		print $fingras;
		
		print "<td>";
		if ( !empty($obj->ObsPriv )) {
			$text = $langs->trans("DefObsPriv").':'.$obj->ObsPriv;
			print info_admin($text,1);
		}	
		print "</td>";
		print '<td>';
		$wfrmcm = new FormCglCommun ($db);
		print $wfrmcm->AffichImgStatutBull($obj->statut, null, 'Insc', null);
		unset ($wfrmcm);
		print '</td>';	
/*
		if ($obj->statut == $bull->BULL_ENCOURS) { 	 $img=$bull->IMG_ENCOURS; $texte=$bull->LIB_CNT_ENCOURS;}
		elseif ($obj->statut == $bull->BULL_VAL ) { $img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
		elseif ($obj->statut == $bull->BULL_DEPART ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_DEPART;}
		elseif ($obj->statut == $bull->BULL_RETOUR ) { $img=$bull->IMG_RETOUR; $texte=$bull->LIB_RETOUR;}
		elseif ($obj->statut == $bull->BULL_CLOS ) { $img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
		elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}	
		print '<td ".$style."><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'"></td>';
		print "</td>\n";
*/
		print "</tr>\n";
		$var=!$var;
		$i++;
	}	
	
	print "</td/tr>\n";
	print '</table>';
	print '<br><br><br>';
// End of page
llxFooter();
$db->close();

?>
