<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.8 - hiver 2023 - création 
 * Version CAV - 2.8.2  - hiver 2023 - ajout titre, du tiers 
 * Version CAV - 2.8.3  - printemps 2023 - formatage des référence des vélos en conflit (gras, image d'info)( bug 269)
 * Version CAV - 2.8.4 - printemps 2023	- correction conflit location vélo (300)
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
 *   	\file       custom/cglinscription/listeconflitlocvelo.php
 *		\ingroup    cglinscription
 *		\brief      Liste du matériel loué en double sur une journée
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
require_once ('./class/bulletin.class.php');
/*require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
// Change this following line to use the correct relative path from htdocs
//dol_include_once ('/class/cgllocation.class.php');
require_once ('./class/atelier.class.php');
require_once ('./class/cgllocation.class.php');
require_once ('./class/cglinscription.class.php');
require_once ('../cglavt/class/cglFctCommune.class.php');
require_once ('./class/html.formcommun.class.php');
require_once ('./core/modules/cglinscription/modules_cglinscription.php');

*/
// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");
global $search_Date;

// Get parameters
if ('Get parameters' == 'Get parameters') {
	$page		= GETPOST("page",'int');
	$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
	$search_Date= GETPOST("search_Date",'alpha');
	// récupération des paramètre de l'URL	
	$search_serv=trim(GETPOST("search_serv", 'int'	));
	$search_mat=trim(GETPOST("search_mat", 'alpha'));
	$sortfield=GETPOST("sortfield",'alpha');
	$sortorder=GETPOST("sortorder",'alpha');
			
	if (empty($sortfield)) $sortfield="p.label,bd.refmat ";

	// Gestion des pages d'affichage des tiers
	if ($page == -1 or empty($page)) $page = 0; 
	$offset = (int)$limit * $page ;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	}
/*	
global $dossiers;
$dossiers = array();	

$form=new Form($db);
$w = new CglFonctionCommune($db) ;
$bull = new Bulletin($db);
$atelier = new AtelierPrep($db);
global $db, $bull , $atelier, $conf, $langs;
*/
	
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

		if ($option == 'MAJLoc')		{
			$result = '<a href="./location.php?id_contrat='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifLoc").'">';
		}					
 
		if ($option == 'Tiers')		{
			$result = '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifLoc").'">';
		}					
      $result.=$lienfin;
       return $result;
	}//getNomUrl
	function select_serviceinscrit($selected='',$htmlname='search_serv',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull, $search_Date;

 /*       // On recherche les services
		$sql = "SELECT distinct P.rowid, P.description, P.label ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull";		
		$sql.= " left join ".MAIN_DB_PREFIX."product as P on bd.fk_activite = P.rowid";		
		$sql.= " WHERE b.statut <  ".$bull->BULL_CLOS;
		$sql .= " AND b.typebull = 'Loc'";
		$sql .= " ORDER BY description";	
		
        $resql=$db->query($sql);
        if ($resql)      {
            $out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
            if ($showempty) $out.= '<option value="-1"></option>';
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num)          {
                while ($i < $num)              {
                    $obj = $db->fetch_object($resql);
                    $label=$obj->label;
                    if ($selected > 0 && $selected == $obj->rowid) 
                       $out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
                    else    
                        $out.=  '<option value="'.$obj->rowid.'">'.$label.'</option>';             
                    $i++;
                }
            }
            $out.= '</select>';
        }
        else      dol_print_error($db);
        return $out;
*/
    }/* select_serviceinscrit */
	function select_refmatinscrit($selected='',$htmlname='search_refmat',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';

        // On recherche les societes
/*
		$sql = "SELECT distinct bd.refmat as IdentMat, bd.marque ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull";	
			$sql.= "  WHERE b.statut <  ".$bull->BULL_CLOS;
		$sql .=" AND bd.action not in ('S','X') ";
		$sql .= " AND b.typebull = 'Loc'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
            if ($showempty) $out.= '<option value="-1"></option>';
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num)     {
                while ($i < $num)    {
                    $obj = $db->fetch_object($resql);
                    $label=$obj->ident. ' - '.$obj->marque;
                    if (!empty($selected ) && $selected == $obj->refmat)    {
                        $out.= '<option value="'.$label.'" selected="selected">'.$label.'</option>';
                    }
                    else     {
                        $out.= '<option value="'.$obj->refmat.'">'.$label.'</option>';
                    }
                    $i++;
                }
            }
            $out.= '</select>';
        }
        else  dol_print_error($db);

        return $out;
*/
    }/* select_refmatinscrit */
	function AfficheRuptureListe($num)
	{
		global $langs;
			
		/*
		1 ==> devant être rentré la veille sur contrat non cloturé
		2 ==> départ du jour
		3 ==> retour du jour
		4 ==> dehors en ce moment
		5 ==> sans date
		*/
		$out = "============================ ";
		
		if ($num == 1) { $texte .= $langs->trans('LbMatDehors');}
		if ($num == 2) { $texte .= $langs->trans('LbMatDepart');}
		elseif ($num == 3) { $texte .= $langs->trans('LbMatRetour');}
		elseif ($num == 4) { $texte .= $langs->trans('LbMatSorti');}
		$out .= $texte." ";
		for ($i=strlen($texte); $i<120;$i++) $out .= "=";

		return $out;
	}//AfficheRuptureListe
	function ColorRuptureListe($num)
	{
		/*1 ==> départ du jour ==> blanc
		2 ==> retour du jour ==> bleu clair
		3 ==> dehors en ce moment ==> gris
		*/
		$out = 'style="color:';
		if ($num == 1) { $out .= '';}
		elseif ($num == 2) { $out .= 'blue';}
		elseif ($num == 3) { $out .= 'grey';}
		$out .= '"';
		return $out;
		
		
	} //ColorRuptureListe

// construction SQL de recherche
if ('OrdreSql' == 'OrdreSql') {
	
	// Lister les produit/refmat loués au moins deux fois
	
	$sql = "";
	$conf->global->CGL_PLANNINGVELO = 21;
	$dt_now = dol_now('tzuser');
	$now = dol_print_date($dt_now, '%Y-%m-%d');
	$date_search = $now;
}
// ACTION

/* EDITION CONTRATS de LOCATION*/
	if (isset($action) and $action=='builddoc')
			$ret = creer_ficheatelier($atelier);



/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('',$langs->trans('LcgllocationMatLoue'));

/* préparation de la navigation */
if ('navigation' == 'navigation')  
{ 
	$morehtml = '<div class="inline-block '.(($buttonreconcile || $newcardbutton) ? 'marginrightonly' : '').'">';
	$morehtml .= '</div>';
}

// Put here content of your page
/*******************************************************************
* TEST
*
********************************************************************/
$help_url='FR:Module_Inscription';

// Clique sur bouton Vider les filtres
if (GETPOST("button_removefilter_x", 'alpha'))
{
    $search_date='';
	$search_mat="";
	$search_refmat="";
	$sortfield="";
	$sortorder="";
	$search_serv="";
}

//	print_barre_liste('TiConflitLocVelo', $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$num,$nbtotalofrecords, '', 0, $morehtml, '', $limit, 0, 0, 0);
	$titre = $langs->trans('TiConflitLocVelo');
	$lbDelai = '+ '.$conf->global->CGL_PLANNINGVELO.' days';
	$date_searchendstart =  DATE("d-M-Y", strtotime($date_search));
	$date_searchend =  DATE("d-M-Y", strtotime($date_search.$lbDelai));
	$titre .= ' ( '.$date_searchendstart.' et '.$date_searchend.' soit '.$lbDelai.' jours ) ';
	print_barre_liste($titre, '', '','','','','',0,0, '', 0, '', '', 0, 0, 0, 0);


if ('Filtr1e' == 'Filtre') {
	// permet d'afficher le petit livre, le titre la succession des page et le numéro de la  page courante
	// paramètres a passer dans les boutons de page successives
	$params = "&type=".$type."&amp;search_Date=".$search_Date;
	$params.= "&amp;search_tiers=".$search_tiers."&amp;search_ref=".$search_ref."&amp;search_serv=".$search_serv."&amp;search_mat=".$search_mat;
	$params.="&amp;search_refmat=".$search_refmat."&amp;search_statut=".$search_statut;

	/* titre */
	
	// affichage barre de sélection
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="token" value="'.newtoken().'">';
	print '<input type="hidden" name="limit" value="'.$limit.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$num,$nbtotalofrecords, '', 0, $morehtml, '', $limit, 0, 0, 0);

	$params .= '&limit='.$limit;
	$params .= '&page='.$page;
	
	// début barre de selection
	print '<table class="liste" width="100%">';

/*	$moreforfilter='';
	$htmlother=new FormOther($db);
    $moreforfilter.=$htmlother->select_categories(2,$search_categ,'search_categ');
    $moreforfilter.=' &nbsp; &nbsp; &nbsp; ';*/

    // affiche la barre grise de titres des filtres
	print '<tr class="liste_titre">';
	print_liste_field_titre("DateRetrait",$_SERVER["PHP_SELF"],'',"",'','','','');
	print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"","",'',"",$sortfield,$sortorder);

	print_liste_field_titre("Service",$_SERVER["PHP_SELF"],"","",'','',$sortfield,$sortorder);
//	print_liste_field_titre("materiel",$_SERVER["PHP_SELF"],"","",'','',$sortfield,$sortorder);
	print_liste_field_titre("RefVelo",$_SERVER["PHP_SELF"],"","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre('',$_SERVER["PHP_SELF"],'',"",'','','','');
	print_liste_field_titre('',$_SERVER["PHP_SELF"],'',"",$params,'',$sortfield,$sortorder);		
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
	if (empty($search_Date) or $search_Date == 0) $temp = -1;
	else $temp =dol_stringtotime($search_Date); 
	$form->select_date($temp,'search_Date','','','',"add",1,1); 		
	print '</td><td>';
	
	print '</td><td class="liste_titre">';
	print select_serviceinscrit($search_serv,'search_serv',1, 1);
	print '</td><td>';
//	print select_materielinscrit($search_mat,'search_mat',1,1);
//	print '</td><td>';		
	print select_refmatinscrit($search_refmat,'search_refmat',1,1);
	print '</td><td>';
	
	print '</td><td>';
	print '</td>';
	//print '</td>';
	// boutons de validation et suppression du filtre
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print "</tr>\n";
	print "</table>\n";



//	print '<tr>';
//	print '<td>';
	print '<span align="left"><input class="button" name="aujourd_hui" type="submit" value="'.$langs->trans("Aujourd_hui").'"></span>';
//	print '</td><td colspan = 5>';
	print '<span >&nbsp</span>';
	print '<span align="left"><input class="button" name="demain" type="submit" value="'.$langs->trans("Demain").'"></span>';
	print '<span >&nbsp</span>';
	print '<span align="left"><input class="button" name="apres_demain" type="submit" value="'.$langs->trans("Apres_demain").'"></span>';
//	print '</td><td >';
	for ($i=0; $i < 46; $i++) print '<span >&nbsp&nbsp&nbsp&nbsp&nbsp</span>';
//	print '<span align="right"><input class="button" name="nouv_contrat" type="submit" value="'.$langs->trans("NvLocation").'"></span>';

	print '<div class="inline-block divButAction"><a class="butAction"  href="./location.php?idmenu=16925&mainmenu=CglLocation&token='.newtoken().'">';
	print $langs->trans('NvLocation').'</a></div>';	
//	print '</td>';

//	print '</tr>';

	$var=True;
	$i=0;
	print '<table class="liste" name="entetetab" width="100%">';

}
if ("Entete" == "Entete") {	
	print "<table>";
	// affiche la barre grise des champs affichés
	print '<tr class="liste_titre">';

	/* pour la liste des location : date retrait/date depose/tiers/ref bull/Nb Velo
	* pour la liste de matériel : date retrait/date depose/tiers/ref mzt
	* pour la liste des retours : date retrait/date depose/tiers/ref bull
	* pour la liste des cautions : date retrait/date depose/tiers/montant caution
	*/
	
	print_liste_field_titre("Date",$_SERVER["PHP_SELF"],"DateJour","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("Service",$_SERVER["PHP_SELF"],"fk_produt","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("Reference",$_SERVER["PHP_SELF"],"refmat","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("Contrat",$_SERVER["PHP_SELF"],"","",'','',$sortfield,$sortorder);
	print_liste_field_titre("DateRetrait",$_SERVER["PHP_SELF"],"","",'','','','');
	print_liste_field_titre("DateDepose",$_SERVER["PHP_SELF"],"","",'','','','');
	print_liste_field_titre("Tiers",$_SERVER["PHP_SELF"],"","",'','','','');
	
	// amener le dessin gris jusqu'en fin de ligne  
	print_liste_field_titre('','',"","",'','','','');
	print "</tr>\n";
	
}	

// sur 21 jours;
	$bull =  new Bulletin($db);
	$StatutBullAbandon = $bull->BULL_ABANDON;
	for ($jour=0; $jour<$conf->global->CGL_PLANNINGVELO; $jour++) {
		//if (!empty($sql)) $sql .= " UNION ";
		$sql = "";
		$sql .= "SELECT fk_produit, refmat, p.label, b.ref, bd.datedepose, bd.dateretrait, '".$date_search ."' as DateJour ";
		$sql .= ", b.statut, b.rowid as IdBull , b.fk_soc as IdTiers, s.nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b on b.rowid = bd.fk_bull ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on bd.fk_produit = p.rowid ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on b.fk_soc = s.rowid ";
		$sql .= " WHERE bd.type = 0 AND bd.action not in ('X','S') AND not isnull(bd.refmat) and bd.refmat <> '' AND b.typebull = 'Loc' ";
		$sql .= " AND '".$date_search."' BETWEEN date(bd.dateretrait) AND date(bd.datedepose) " ;
		$sql .= " AND b.statut < ".$bull->BULL_ABANDON;
		$sql .= " AND b.regle <> ".$bull->BULL_ARCHIVE;
		$sql .= " AND EXISTS (SELECT (1) FROM  ".MAIN_DB_PREFIX."cglinscription_bull_det as pl 
				LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b1 on b1.rowid = pl.fk_bull 
			WHERE pl.fk_produit = bd.fk_produit  AND pl.refmat = bd.refmat
			AND pl.type = 0 AND pl.action not in ('X','S') AND not isnull(pl.refmat)  and pl.refmat <> ''
			AND '".$date_search."' BETWEEN date(pl.dateretrait) AND  date(pl.datedepose) ";
		$sql .= " AND b1.statut < ".$bull->BULL_ABANDON;
		$sql .= " AND b1.regle <> ".$bull->BULL_ARCHIVE;
		$sql .= "  AND pl.rowid <> bd.rowid )";
		$sql .= 'ORDER BY DateJour asc, label asc, refmat asc' ;
		$date_search =  DATE("Y-m-d", strtotime($date_search.'+ 1 days'));
		$resql=$db->query($sql);
		if ($resql)     
			$num = $db->num_rows($resql);
		if ($resql) {
			for ($i=0; $i<$num; $i++)
			{
			
				 $obj = $db->fetch_object($resql);
				// tracé deslignes pour lisibilité
				print "<tr $bc[$var]>";
				
				if ($i>0 and $ancdate != $obj->DateJour)
					 print "<td colspan=6	>=================================</td><tr $bc[$var]>";
				if ($i>0 and $ancdate == $obj->DateJour and $ancSerRef != $obj->label.$obj->refmat)
					 print "<td colspan=6	>---------------------------</td><tr $bc[$var]>";
				
				print "<td 	>".$obj->DateJour."</td>";
				print "<td 	>".$obj->label."</td>";
				print "<td 	style='color:red;font-weight:bold;'><b>".$obj->refmat;
				 print info_admin($langs->trans("VeloEnConflit"),1);
				print "</b></td>";
				print "<td >";
				print getNomUrl("object_company.png", 'MAJLoc',0,$obj->IdBull)."&nbsp".$obj->ref;
				print "</td>";
				print "<td 	>".$obj->dateretrait."</td>";
				print "<td 	>".$obj->datedepose."</td>";
				print "<td >";
				print getNomUrl("object_company.png", 'Tiers',0,$obj->IdTiers)."&nbsp".$obj->nom;
				print "</td>";
				
				print "</tr>\n";
				$ancdate = $obj->DateJour;
				$ancSerRef = $obj->label.$obj->refmat;	

				$var=!$var;

			} // foreach	
		}

	}//for recherche par jour	

print "</td/tr>\n";
print '</table>';
print '<br><br><br>';	

// End of page
llxFooter();
$db->close();

?>
