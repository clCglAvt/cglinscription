<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022
 *					 - Remplacer method="GET" par method="POST"
 *					 - Migration Dolibarr V15 et PHP7 
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
 *   	\file       custom/cglinscription/resa.php
 *		\ingroup    cglinscription
 *		\brief      Liste les réservations de restaurants et modifications de leurt états, dans Location --> obsoletes
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
// Change this following line to use the correct relative path from htdocs
//require_once ('./class/cglinscription.class.php');
//dol_include_once ('/class/cgllocation.class.php');
require_once ('/class/bulletin.class.php');
require_once ('/class/cgllocation.class.php');
require_once ('../Cglavt/class/cglFctCommune.class.php');

// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");

global $MOD_RESA ;
$MOD_RESA= 'editResa';
global $UPD_RESA ;
$UPD_RESA= 'setResa';
// Get parameters
$id		= GETPOST('id','int');
$action	= GETPOST('action','alpha');
//$type=GETPOST("type",'alpha');
$myparam	= GETPOST('myparam','alpha');
$page		= GETPOST("page",'int');
//$ObsReservation=GETPOST("ObsReservation",'alpha');
$ObsReservation=GETPOST("Resa",'alpha');

$LocStResa=GETPOST("LocStResa",'alpha');


// récupération des paramètre de l'URL
$search_tiers=trim(GETPOST("search_tiers", 'alpha'));
$search_ref=(GETPOST("search_ref"));
$search_obsresa=(GETPOST("search_obsresa"));
$search_sttresa=(GETPOST("search_sttresa"));
$sortfield=GETPOST("sortfield",'alpha');
$sortorder=GETPOST("sortorder",'alpha');
$search_serv== GETPOST("search_serv",'int');
$search_statut== GETPOST("search_statut",'int');
$search_DateRetrait=trim(GETPOST("search_DateRetrait", 'date'));



if (empty($sortfield)) $sortfield=" dateretrait";
if (empty($sortorder)) $sortorder=" DESC ";

// Gestion des pages d'affichage des tiers
if ($page == -1) { $page = 0 ; }
if (empty($page)) $page = 0; 
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

global $db;
$bull =  new Bulletin ($db);
$TraitCglLocation = new CglLocation($db);
$form = new Form($db);
$bull = new Bulletin($db);
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
        global $conf,$langs,  $type;

        $result='';
		$lienfin='</a>';
		
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
        global $conf,$user,$langs, $db, $bull;

        $out='';

        // On recherche les societes

		$sql = "SELECT distinct T.rowid, T.nom ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " left join ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";	
		$sql .=" WHERE  b.typebull = 'Loc' AND !isnull(fk_sttResa)  AND  b.statut <=".$bull->BULL_DEPART." ";
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
	function select_refcnt($selected='',$htmlname='search_ref',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';

        // On recherche les référence 

		$sql = "SELECT distinct b.ref, b.ref ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql .= " WHERE b.typebull = 'Loc' AND !isnull(fk_sttResa)  AND  b.statut <=".$bull->BULL_DEPART." ";
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
                    if (!empty($selected ) && $selected == $obj->ref)
                    {
                        $out.= '<option value="'.$obj->ref.'" selected="selected">'.$label.'</option>';
                    }
                    else
                    {
                        $out.= '<option value="'.$obj->ref.'">'.$label.'</option>';
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
    }/* select_refcnt */
	function select_sttresa($selected='',$htmlname='search_sttresa',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';

        // On recherche les référence 

		$sql = "SELECT distinct fk_sttresa as rowid, libelle ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b,  ";
		$sql.= "  ".MAIN_DB_PREFIX."cgl_c_stresa as r ";
		
		$sql .= " WHERE r.rowid = fk_sttresa and b.typebull = 'Loc' AND r.active=1 AND  b.statut <=".$bull->BULL_DEPART."  ";
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
                    $label=$obj->libelle;
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
    }/* select_refcnt */
	function select_statut($selected='',$htmlname='search_statut' )
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';
		$now = $db->idate(dol_now('tzuser'));

        // On recherche les societes
		$sql = "SELECT distinct b.statut ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on fk_bull = b.rowid ";
		$sql.= " left join ".MAIN_DB_PREFIX."cgl_c_stresa as sr on b.fk_sttResa = sr.rowid";
		if (empty($type)  or ($type == 'materiel') ) 		
			$sql.= " WHERE 	ADDDATE('".$now."',INTERVAL -1 DAY)  <=  (select MAX(dateretrait) from llx_cglinscription_bull_det as bd1 where bd1.fk_bull = b.rowid and bd1.type = 0 and bd1.action not in ('S','X'))";
		elseif ($type == 'retour') $sql.= " WHERE b.statut < 2 ";
		elseif ($type == 'caution') $sql.= " WHERE b.ret_caution = 0  and fk_caution > 0";
		$sql .= " AND b.typebull = 'Loc' and bd.type = 0 and bd.action not in ('S','X')  AND !isnull(fk_sttResa) AND  b.statut <=".$bull->BULL_DEPART."  ORDER BY 1";
		
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
					elseif ($obj->statut == $bull->BULL_ABANDON ) 
						$out.= '<option value="'.$obj->statut.'"'.$sel.'>'.$bull->LIB_ABANDON.'</option>';
				
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
    }/* select_statut */

	
/***************************************************
* ACTION
*
****************************************************/
if ($action == $UPD_RESA) {
	$bull->id = $id;
	

	// Si changement d'état : Nouvel Etat : à la date du 
	$bull->fetch_complet_filtre(-1, $id);
	
	if (!empty($LocStResa) and $bull->fk_sttResa <> $LocStResa)  {
		$temp = "<strong>".$langs->trans ("LbChgEtatResaStatut"). "</strong>: ";
		$temp .= $TraitCglLocation->recherchelb_statResa ($LocStResa). ' ';
		$temp .= $langs->trans ("LbChgEtatResaDate"). ' ';
		$datet = new DateTime;
		$temp .= $datet->format("d/m/y");
		$temp1=$temp."</br>".$ObsReservation;
		$ObsReservation = $temp1;
	}
	$bull->update_champs('fk_sttresa',$LocStResa, 'Obsreservation', $ObsReservation);
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','Lcglinscription');

$form=new Form($db);
$w = new Cgllocation($db) ;
$wfcom = new CglFonctionCommune($db) ;
$now = $db->idate(dol_now('tzuser'));


// Put here content of your page
$help_url='FR:Module_Inscription';

// Clique sur bouton Vider les filtres
if (GETPOST("button_removefilter_x", 'alpha'))
{
    $search_DateRetrait='';
	$search_tiers="";
	$search_ref="";
	$search_statut="";
	$search_obsresa="";
	$search_sttresa="";
	
	$sortfield="";
	$sortorder="";
	$search_serv="";	
}

// construction SQL de recherche
 
$sql = "SELECT  b.rowid as rowid,  ObsReservation, fk_sttResa, ";
$sql .= "(select MAX(dateretrait) from llx_cglinscription_bull_det as bd1";
$sql .= " where bd1.fk_bull = b.rowid and bd1.type = 0 and bd1.action not in ('S','X')) as dateretrait, ";
$sql .= "  b.ref, sr.libelle as statut , b.statut as bullstatut";
$sql .= ", T.nom, T.rowid as id_client ";
$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
$sql.= " left join ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";
$sql.= " left join ".MAIN_DB_PREFIX."cgl_c_stresa as sr on b.fk_sttResa = sr.rowid";
$sql.= " WHERE 	(isnull(dateretrait) or  ADDDATE('".$now."',INTERVAL -1 DAY) <= 	b.dateretrait) ";
$sql .= " AND b.typebull = 'Loc'   ";
$sql.= " AND  b.statut <= ".$bull->BULL_DEPART;
$sql.= " AND  b.regle < ".$bull->BULL_FACTURE;
$sqlgroup = " group by   b.rowid ,  ObsReservation, fk_sttResa,   b.ref, sr.libelle, T.nom";
if ($search_dateretrait and !($wfcom->transfDateMysql(  $search_dateretrait ) == dol_print_date( dol_now('tzuser'),'%Y-%m-%d')))
{
	$sql.= " AND dateretrait between '".$wfcom->transfDateMysql($search_dateretrait)."' and  ADDDATE('".$wfcom->transfDateMysql($search_dateretrait)."',INTERVAL 1 DAY)";
}
if (!empty($search_ref ))
{
	if ($db->escape($search_ref) <> -1) $sql.= " AND ref ='".$db->escape($search_ref)."'";
}
if (!empty($search_statut ))
{
	$sql.= " AND statut ='".$db->escape($search_statut)."'";
}
if (!empty($search_sttresa) and  $search_sttresa <> -1)
{
	$sql .= " AND fk_sttresa ='".$db->escape($search_sttresa)."'";
}
if ($search_tiers and !($search_tiers == -1)) // search_tiers est à -1 quand on n'a pas fait de choix dur tiers à cause du select
{
	$sql.= " AND T.rowid ='".$db->escape($search_tiers)."'";
}
$sql .= $sqlgroup;
// Compte le nb total d'enregistrements
$nbtotalofrecords = 0;


//print $sql;



if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	if ($result	)   
	{
		$nbtotalofrecords = $db->num_rows($result);
	}
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);

if ($resql	)   {
	$num = $db->num_rows($resql);
}

// permet d'afficher le petit livre, le titre la succession des page et le numéro de la  page courante
// paramètres a passer dans les boutons de page successives
//$params = "&type=".$type;
$params = "&amp;search_DateRetrait=".$search_DateRetrait."&amp;search_DateDepose=".$search_DateDepose;
$params.= "&amp;search_tiers=".$search_tiers."&amp;search_ref=".$search_ref."&amp;search_serv=".$search_serv."&amp;search_mat=".$search_mat;
$params.="&amp;search_refmat=".$search_refmat."&amp;search_ref=".$search_statut;


$title=$langs->trans("ListeReservation");

print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$nbtotalofrecords,$nbtotalofrecords);
  
// affichage barre de sélection
//rint '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?type='.$type.'" name="formfilter">';
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'? name="formfilter">';
print '<input type="hidden" name="token" value="'.newtoken().'">';

// début barre de selection
print '<table class="liste" width="100%">';

    // affiche la barre grise de titres des filtres
	print '<tr class="liste_titre">';
	print_liste_field_titre("DateRetrait",$_SERVER["PHP_SELF"],"b.dateretrait","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"T.nom","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre("RefCnt",$_SERVER["PHP_SELF"],"ref","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("Observation",'','',"",'','','','');
	print_liste_field_titre("LocSttResa",'','',"",'','','','');
	print_liste_field_titre("TiStatutCnt",'','',"",'','','','');
	print_liste_field_titre("TiRegle",'','',"",'','','','');
	print_liste_field_titre('','','',"",'','','','');
	print "</td>";
		
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
	print select_tiersinscrit($search_tiers,'search_tiers',1, 1);
	 
	print '</td><td>';
	print select_refcnt($search_ref,'search_ref',1,1);

	print '</td>';
	print "<td></td>\n";
	print "<td>";
	print select_sttresa($search_sttresa,'search_sttresa',1,1);
	print '</td><td>';
	print select_statut($search_statut,'search_statut');
	print '</td>';
	print "<td></td>\n";
	// boutons de validation et suppression du filtre
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print "</tr>\n";
print '</form>';
	$var=True;
	$i=0;
 	print "<p>    </p>\n";
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?name="formfilter">';
print '<input type="hidden" name="token" value="'.newtoken().'">';
print '<input type="hidden" name="action" value="'.$UPD_RESA.'">';

	
// amener le dessin gris jusqu'en fin de ligne  
	print "</td/tr>\n";

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		print "<tr $bc[$var]>";

		print "<td>";	
	
		print " ".$wfcom->transfDateFr($obj->dateretrait)."</td>\n";
		print "<td>".getNomUrl("object_company.png", 'Tiers',0,$obj->id_client)."&nbsp".$obj->nom."</td>";
		print "<td>";
		/* affiche l'image pour la selection */
		print getNomUrl("object_company.png", 'MAJLoc',0,$obj->rowid)."&nbsp";
		print $obj->ref."</td>";
		if ($action == $MOD_RESA and $id == $obj->rowid) {
			print '<td>';		
			if (empty($obj->ObsReservation)) //$temp = '<span style="color:#C0C0C0">'.$langs->trans("LocObsResaModele").'</span>';
				$temp = $langs->trans("LocObsResaModele");
			else $temp = $obj->ObsReservation;
		
			if (! empty($conf->global->FCKEDITOR_ENABLE_SOCIETE)) $typeofdata='ckeditor:dolibarr_notes:100%:200::1:12:100';
			else $typeofdata='textarea:12:100';
			?>
			<!-- BEGIN PHP TEMPLATE NOTES -->
			<div class="border table-border centpercent">
				<div class="table-border-row">	
				<div class="table-val-border-col"><?php print $TraitCglLocation->editfieldval($action, 'Resa',  $bull, 1, $typeofdata, $temp) ?></div>
				</div>
			<!-- END PHP TEMPLATE NOTES-->
<?php
//	dol_fiche_end();

/*			print '<td align="left"><textarea cols="40" rows="'.ROWS_8.'" wrap="soft" name="ObsReservation">';
			print $temp.'</textarea>';*/
			
			
			print '<input type="hidden" name="id" value="'.$obj->rowid.'">';	
			print '</td>';
			print '<td>';
			print '<input type="hidden" name="id" value="'.$obj->rowid.'">';
			print $w->select_StResa($obj->fk_sttResa,'LocStResa');	
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
			print '</td>';			
		}
		else {
			print "<td>".$obj->ObsReservation."</td>";
			print "<td>".$obj->statut."</td>";
		}
if ($obj->bullstatut == $bull->BULL_ENCOURS) { $img='statut0.png'; $texte=$bull->LIB_CNT_ENCOURS;}
elseif ($obj->bullstatut == $bull->BULL_VAL) {$img='statut6.png'; $texte=$bull->LIB_VAL;}
elseif ($obj->bullstatut == $bull->BULL_DEPART) {$img='statut5.png'; $texte=$bull->LIB_DEPART;}
print '<td><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'"></td>';
if ($obj->regle == $bull->BULL_NON_PAYE) { $img='statut8.png'; $texte='Non payé';}
elseif ($obj->regle ==$bull->BULL_INCOMPLET) {$img='statut3.png';; $texte='Paiement incomplet';}
elseif ($obj->regle ==$bull->BULL_PAYE) {$img='statut4.png';; $texte='Payé';}
elseif ($obj->regle ==$bull->BULL_SURPLUS) {$img='statut7.png';; $texte='Paiement superieur';}
elseif ($obj->regle ==$bull->BULL_REMB) {$img='statut1.png';; $texte='Remboursement fait';}
print '<td><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'"></td>';
//print '<input type="image" src="../../theme/'.$conf->theme.'/img/edit.png" border="0" name="tiers_edit" alt="'.$langs->trans("Modif").'">';
print "</td>\n";

		print "<td>";
					if ($bull->statut < $bull->BULL_CLOS) {
				if ($action == $MOD_RESA and $id == $obj->rowid)		
					print '<input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="UDP_RESA"  alt="'.$langs->trans("Enregistrer").'"">';					
							else
print '<a  href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action='.$MOD_RESA.'">'.img_edit().'</a>';
							print '</td>';
				}			
		print "</tr>\n";
		$var=!$var;
		$i++;
	}
	print "</table>\n";					
print "</form>";	
	




// End of page
llxFooter();
$db->close();
?>
