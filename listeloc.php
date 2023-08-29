<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
 * Version CAV - 2.8 - hiver 2023
 *		 - Pagination (suppression Ajout)
 * 		 - vérification de la fiabilité des foreach
 *		- implémentation statut PréInscirt dans Select_statut
 *		- suppression de search_refmat, car filtre inexistant
 * Version CAV - 2.8.5 - printemps 2023
 *		- tri croissant des locations (evo 331)
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
 *   	\file       custom/cglinscription/listloc.php
 *		\ingroup    cglinscription
 *		\brief      Liste du matériel loués
 *							type = maeriel  ==> liste du matériel à sortir
 *									caution ==> liste des cautions non rendues
 *									retour ==> liste des bulletins où il reste du matériel à rentrer
 *									exterieur ==> liste des vélos sous-loués
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once ('./class/cgllocation.class.php');
require_once ('./class/bulletin.class.php');
require_once ('./class/html.formcommun.class.php');
require_once ('../cglavt/class/cglFctCommune.class.php');

// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");
$langs->load("cahiersuivi@CahierSuivi");
$langs->load("agenda");
$langs->load("commercial");
$langs->load("ecm");
$langs->load("cashdesk");


// Get parameters
	if ('Get parameters' == 'Get parameters') {
	$id		= GETPOST('id','int');
	$action	= GETPOST('action','alpha');
	$type=GETPOST("type",'alpha');
	$myparam	= GETPOST('myparam','alpha');
	$page		= GETPOST("page",'int');
	$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
	// récupération des paramètre de l'URL
	global $search_DateRetrait;
	$search_DateRetrait=trim(GETPOST("search_DateRetrait", 'date'));
	$search_DateDepose=trim(GETPOST("search_DateDepose", 'date'));
	$search_serv=trim(GETPOST("search_serv", 'int'	));
	$search_tiers=trim(GETPOST("search_tiers", 'alpha'));
	$search_mat=trim(GETPOST("search_mat", 'alpha'));
	$search_marque=trim(GETPOST("search_marque", 'alpha'));
	$search_fk_raisrem=trim(GETPOST("search_fk_raisrem"));
	$search_reslibelle=trim(GETPOST("search_reslibelle"));
	$search_ref=(GETPOST("search_ref", 'int'));
	$search_statut=(GETPOST("search_statut", 'decimal'));
	$sortfield=GETPOST("sortfield",'alpha');
	$sortorder=GETPOST("sortorder",'alpha');

	if (empty($sortfield)) $sortfield=" bd.dateretrait";

	// Gestion des pages d'affichage des tiers
	if ($page == -1) { $page = 0 ; }
	if (empty($page)) $page = 0; 
	$offset = $limit * $page ;
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
		$sql .= " AND b.typebull = 'Loc'";		
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

	function select_refcnt($selected='',$htmlname='search_ref',$showempty=0, $forcecombo=0)
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
		$sql .= " AND b.typebull = 'Loc'";
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
    }/* select_refcnt */
	
	function select_statut($selected='',$htmlname='search_statut' )
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';

        // On recherche les societes
		$sql = "SELECT distinct b.statut ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on fk_bull = b.rowid ";
		if (empty($type)  ) 		
			$sql.= "  WHERE b.statut <  ".$bull->BULL_CLOS;
		elseif ($type == 'retour') $sql.= " WHERE b.statut  < ".$bull->BULL_CLOS."  ";
		elseif ($type == 'caution') $sql.= " WHERE b.ret_caution = 0  and fk_caution > 0";
		$sql .= " AND b.typebull = 'Loc' ORDER BY 1";
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
					elseif ($obj->statut == $bull->BULL_PRE_INS)
						$out.= '<option value="'.$obj->statut.'" '.$sel.'>'.$bull->LIB_PRE_INS.'</option>';
					elseif ($obj->statut == $bull->BULL_VAL)
						$out.= '<option value="'.$obj->statut.'" '.$sel.'>'.$bull->LIB_VAL.'</option>';
					elseif ($obj->statut == $bull->BULL_DEPART)
						$out.= '<option value="'.$obj->statut.'"'.$sel.'>'.$bull->LIB_DEPART.'</option>';
					elseif ($obj->statut == $bull->BULL_RETOUR)
						$out.= '<option value="'.$obj->statut.'" '.$sel.'>'.$bull->LIB_RETOUR.'</option>';
					elseif ($obj->statut == $bull->BULL_CLOS)
						$out.= '<option value="'.$obj->statut.'"'.$sel.'>'.$bull->LIB_CLOS.'</option>';	
					elseif ($obj->statut == $bull->BULL_ABANDON ) 
							$out.= '<option value="'.$obj->statut.'" '.$sel.'>'.$bull->LIB_ABANDON.'</option>';
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

	function NbPmtNeg ($id) 
	{
		global $langs, $db;
		// si modification, penser à list, bulletin et facturation
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
/*a supprimer après V2.8
   $arrayofmassactions = array();
   $form=new Form($db);
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
	unset($form);

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
//	$morehtml .= '<input type="text" name="pageplusone" id="pageplusone" class="flat right width25 pageplusone" value="'.($page + 1).'">';
//	$morehtml .= '/'.$nbtotalofpages.' ';
	$morehtml .= '</div>';

//	$morehtml .= '<!-- Add New button -->'.$newcardbutton;

}


/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/


llxHeader('',$langs->trans('LcgllocationLst'));

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
    $search_DateRetrait='';
    $search_DateDepose='';
	$search_tiers="";
	$search_mat="";
	$search_ref="";
	$search_statut="";
	$sortfield="";
	$sortorder="";
	$search_serv="";
	$search_fk_raisrem="";
	$search_reslibelle="";
}

// construction SQL de recherche
if ('OrdreSql' == 'OrdreSql') {
/*

 SELECT distinct  b.rowid as rowid,  T.nom, T.rowid as id_client, b.statut, b.regle ,  b.ref , b.ObsPriv, b.ActionFuture, b.PmtFutur ,
	GROUP_CONCAT(DISTINCT (CONCAT(rem.libelle, CONCAT(' - ',bd1.reslibelle)))) as InfoRem ,
		CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END  as  createur , 
	min(bd.dateretrait) as dateretrait, max(bd.datedepose) as datedepose , 
	 SUM(bd.qte) as NbVelo  
 FROM llx_cglinscription_bull as b 
	LEFT JOIN llx_cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X') 
	 LEFT JOIN llx_societe as T on b.fk_soc = T.rowid  
	 LEFT JOIN  llx_user as u  on u.rowid = fk_createur
	 LEFT JOIN   llx_cglinscription_bull_det as bd1	 on bd1.fk_bull = b.rowid and bd1.type = 2 and bd1.action not in ('S','X')
	LEFT JOIN  llx_cgl_c_raison_remise as rem  on bd.fk_raisrem = rem.rowid	 
 WHERE  b.typebull = 'Loc' AND b.statut <  4 AND b.regle <  6 GROUP BY b.rowid,  T.nom, b.statut, b.regle, b.ref


*/
	$sql = "SELECT";
	$sql .= " distinct ";
	$sql .= " b.rowid as rowid,  T.nom, T.rowid as id_client, b.statut, b.regle ,  b.ref , b.ObsPriv, b.ActionFuture, b.PmtFutur ";
	$sql.= ", GROUP_CONCAT(DISTINCT (CONCAT(rem.libelle, CONCAT(' - ',bd1.reslibelle)))) as InfoRem  ";
	if ($type == 'exterieur') 
		$sql .= ", CONCAT(CONCAT(firstname,' '),lastname)  as  createur ";
	else 
		$sql .= ", CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END  as  createur ";
	if (empty($type) ) {
		$sql .= ", min(bd.dateretrait) as dateretrait, max(bd.datedepose) as datedepose ,  SUM(bd.qte) as NbVelo ";
	}

	elseif  ($type == 'caution') {
		$sql .= ",(select MIN(dateretrait) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bd where bd.fk_bull = b.rowid and bd.type = 0) as dateretrait ";
		$sql .= ", (select MAX(datedepose) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bd where bd.fk_bull = b.rowid and bd.type = 0) as datedepose ";
	}
	elseif  ($type == 'retour') {
		$sql .= ", bd.dateretrait, bd.datedepose,  SUM(bd.qte) as NbVelo ";
		$sql .= ", (select SUM(bmm.qte - case when isnull(bmm.qteret) then 0 else bmm.qteret end) from llx_cglinscription_bull_mat_mad as bmm  where  bmm.fk_bull = b.rowid ) as NbMatMad ";
		$sql .= ", (select SUM(br.qte - case when isnull(br.qteret) then 0 else br.qteret end) from llx_cglinscription_bull_rando as br  where  br.fk_bull = b.rowid ) as NbRando ";
		$sql .= ", (select SUM(bd.qte - case when isnull(bd.qteret) then 0 else bd.qteret end ) from llx_cglinscription_bull_det as bd  where  bd.fk_bull = b.rowid ) as NbVeloAtt ";
	}
	if ($type == 'exterieur' ) $sql .=' ,  SUM(bd.qte) as NbVelo , bd.dateretrait, bd.datedepose  , Tf.nom as fournisseur, bd.fk_fournisseur , Tf.tva_assuj, bd.taille ';	
	$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";

	if (empty($type)) {
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X')";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on bd.fk_activite = p.rowid";
	}

	elseif  ($type == 'caution') {
	}
	elseif  ($type == 'retour') {
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X') ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on bd.fk_activite = p.rowid ";
	}
	elseif ($type == 'exterieur')	
		$sql.= " LEFT  JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X')";
	if ($type == 'exterieur' )	
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as Tf on bd.fk_fournisseur = Tf.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";

	 $sql .= "  LEFT JOIN  ".MAIN_DB_PREFIX."user as u  on u.rowid = fk_createur ";

	$sql .= "   LEFT JOIN   ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1	 on bd1.fk_bull = b.rowid and bd1.type = 2 and bd1.action not in ('S','X')";
	$sql .= "  LEFT JOIN  ".MAIN_DB_PREFIX."cgl_c_raison_remise as rem  on bd.fk_raisrem = rem.rowid	 ";


	if ($type == 'exterieur') {
		$sql .= ' WHERE YEAR(b.dateretrait) = YEAR("'.$now.'") ';
		$sql .= ' AND Tf.nom <> "'.$conf->global->MAIN_INFO_SOCIETE_NOM.'"';	
	}
	else {
		$sql .= "  WHERE  b.typebull = 'Loc' AND b.statut <  ".$bull->BULL_CLOS ." AND b.regle <  ".$bull->BULL_ARCHIVE;
	}

	if  ($type == 'caution') {
		 $sql.= " AND b.ret_caution = 0  AND fk_caution > 0  ";	 
	}
	elseif  ($type == 'retour') {
		$sql.= " AND b.statut =  ".$bull->BULL_RETOUR ;
	}
	if ($type == 'exterieur')
	 $sqlgroup = " GROUP BY b.rowid,  T.nom, b.statut, b.regle, b.ref, Tf.nom , Tf.tva_assuj ";
	 else
		$sqlgroup = " GROUP BY b.rowid,  T.nom, b.statut, b.regle, b.ref";


	if (empty($type)) {
		 //$sqlgroup .= ", bd.dateretrait, bd.datedepose";
	}

	elseif  ($type == 'caution') {
		 $sql.= "  b.ret_caution = 0  and fk_caution > 0 AND ";
		 
	}
	elseif  ($type == 'retour') {
	}
	if ($search_DateRetrait and !($w->transfDateMysql(  $search_DateRetrait ) == dol_print_date( dol_now('tzuser'),'%Y-%m-%d')))
	{
		$sql.= " AND bd.dateretrait between '".$w->transfDateMysql($search_DateRetrait)."' and  ADDDATE('".$w->transfDateMysql($search_DateRetrait)."',INTERVAL 10 DAY)";
	}

	if ($search_DateDepose and !($w->transfDateMysql(  $search_DateDepose ) == dol_print_date( dol_now('tzuser'),'%Y-%m-%d')))
	{
		$sql.= " AND bd.datedepose between '".$w->transfDateMysql($search_DateDepose)."' and  ADDDATE('".$w->transfDateMysql($search_DateDepose)."',INTERVAL 10 DAY)";
	}
	if ($search_ref > 0)
	{
		$sql.= " AND b.rowid =".$db->escape($search_ref);
	}
	if (!empty($search_statut) and $search_statut >= 0)
	{
		if ($search_statut == 0) $sql.= " AND b.statut = 0";
		else $sql.= " AND b.statut =".$db->escape($search_statut);
	}
	if ($search_tiers and !($search_tiers == -1)) // search_tiers est à -1 quand on n'a pas fait de choix dur tiers à cause du select
	{
		if ($type == 'exterieur')	
			$sql.= " AND Tf.rowid ='".$db->escape($search_tiers)."'";
		else
			$sql.= " AND T.rowid ='".$db->escape($search_tiers)."'";
	}
	if (!empty($search_marque) and $search_marque!= -1) 
	{
		$sql.= " AND marque ='".$db->escape($search_marque)."'";
	}
	if (!empty($search_mat) and $search_mat != -1) 
	{
		$sql.= " AND materiel ='".$db->escape($search_mat)."'";
	}
	if ($search_serv >0) 
	{
		$sql.= " AND fk_activite ='".$db->escape($search_serv)."'";
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

/*
SELECT distinct b.rowid as rowid, T.nom, T.rowid as id_client, b.statut, b.regle , b.ref , b.ObsPriv, b.ActionFuture, b.PmtFutur , 
GROUP_CONCAT(DISTINCT (SELECT CONCAT(rem.libelle, CONCAT(' - ',bd1.reslibelle)) FROM llx_cglinscription_bull_det as bd1 LEFT JOIN llx_cgl_c_raison_remise as rem on bd1.fk_raisrem = rem.rowid WHERE bd1.fk_bull = b.rowid and bd1.type = 2 and bd1.action not in ('S','X')) SEPARATOR ' / ') as InfoRem ,
 CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END as createur , min(bd.dateretrait) as dateretrait, max(bd.datedepose) as datedepose , 
 SUM(bd.qte) as NbVelo 
 FROM llx_cglinscription_bull as b LEFT JOIN llx_cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X') 
 LEFT JOIN llx_product as p on bd.fk_activite = p.rowid LEFT JOIN llx_societe as T on b.fk_soc = T.rowid 
 LEFT JOIN llx_user as u on u.rowid = fk_createur

 WHERE
  !isnull(b.typebull) 
  and b.typebull = 
  'Loc' AND
  (
	  ( b.statut = 4 AND b.regle < 5)
	  or 
	  b.statut = 9.5
	  or 
	  (
		  substr( b.ref, 3,4) = YEAR('2022-03-04 21:03:57')
		  and
		  (select sum(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 0 and bd1.fk_bull = b.rowid) = 0
	  )
  ) 

GROUP BY b.rowid, T.nom, b.statut, b.regle, b.ref 
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
	dol_syslog('Liste des departs',LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql	)   {
		$num = $db->num_rows($resql);
	}
}

/* EDITION Fiche atelier*/
	if (isset($action) and $action=='builddoc'){
		$typeModele = 'atelier_odt:'.DOL_DATA_ROOT.'/doctemplates/bulletin/atelier.odt';

		cgl_atelier_create($db,  $typeModele, $langs, $file, $socid, $courrier='');
		
}

if ('Entete' == 'Entete') {
	// permet d'afficher le petit livre, le titre la succession des page et le numéro de la  page courante
	// paramètres a passer dans les boutons de page successives
	$params = "&type=".$type."&amp;search_DateRetrait=".$search_DateRetrait."&amp;search_DateDepose=".$search_DateDepose;
	$params.= "&amp;search_tiers=".$search_tiers."&amp;search_ref=".$search_ref."&amp;search_serv=".$search_serv."&amp;search_mat=".$search_mat;
	$params.="&amp;search_statut=".$search_statut;
	$params.="&amp;reslibelle=".$search_reslibelle."&amp;fk_raisrem=".$search_fk_raisrem;


	if (empty($type )) $title=$langs->trans("ListeLocation");
	elseif ($type == 'retour') $title=$langs->trans("ListeRet");
	elseif ($type == 'caution') $title=$langs->trans("ListeCaut");
	elseif ($type == 'exterieur') $title=$langs->trans("ListeVeloExt");
	// affichage barre de sélection
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	// ? a quoi servent ces deux lignes ??
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="token" value="'.newtoken().'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$num,$nbtotalofrecords, '', 0, $morehtml, '', $limit, 0, 0, 0);


	// début barre de selection
	print '<table class="liste" width="100%">';

/*	$moreforfilter='';
	$htmlother=new FormOther($db);
    $moreforfilter.=$htmlother->select_categories(2,$search_categ,'search_categ');
    $moreforfilter.=' &nbsp; &nbsp; &nbsp; ';*/
	
	print '<tr class="liste_titre">';
	print '<td></td>';
	print_liste_field_titre("LbTypeRemise",$_SERVER["PHP_SELF"],"","","",'',"","");
	print '<td class="liste_titre">';
	$wfc = new FormCglCommun ($db);
	print $wfc->select_nomremise($search_fk_raisrem,'search_fk_raisrem',1, '',0,1);
	unset($wfc );
	print_liste_field_titre("LbLibelRemise",$_SERVER["PHP_SELF"],"","","",'',"","");
	print '<td class="liste_titre">';
	print '<input type="flat" name="search_reslibelle" value="'.$search_reslibelle.'">';
	print '<td class="liste_titre">';
	print "</td/tr>\n";
 
    // affiche la barre grise de titres des filtres
	print '<tr class="liste_titre">';
	print_liste_field_titre("DateRetrait",$_SERVER["PHP_SELF"],'',"",'','','','');
	print_liste_field_titre("DateDepose",$_SERVER["PHP_SELF"],"","",'','',$sortfield,$sortorder);
	if ($type=='exterieur') 
		print_liste_field_titre("NomFournisseurs",$_SERVER["PHP_SELF"],"","",'',"",$sortfield,$sortorder);
	else 
		print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"","",'',"",$sortfield,$sortorder);
	if (empty($type)) {	
		print_liste_field_titre("RefCnt",$_SERVER["PHP_SELF"],"","",'','',$sortfield,$sortorder);
	}
	
	else {
		if ($type<>'exterieur')  print_liste_field_titre("RefCnt",$_SERVER["PHP_SELF"],"","",$params,'',$sortfield,$sortorder);		
		print_liste_field_titre('',$_SERVER["PHP_SELF"],'',"",$params,'',$sortfield,$sortorder);	
		print_liste_field_titre('',$_SERVER["PHP_SELF"],'',"",$params,'',$sortfield,$sortorder);	
		print_liste_field_titre('',$_SERVER["PHP_SELF"],'',"",$params,'',$sortfield,$sortorder);	
	}
	if ($type <> 'exterieur') 
		print_liste_field_titre("TiStatut",$_SERVER["PHP_SELF"],"","",$params,'',$sortfield,$sortorder);
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
	if (empty($search_DateRetrait) or $search_DateRetrait == 0) $temp = -1;
	else $temp =dol_stringtotime($search_DateRetrait); 
	$form->select_date($temp,'search_DateRetrait','','','',"add",1,1); 		
	print '</td><td>';

	if (empty($search_DateDepose) or $search_DateDepose == 0) $temp = -1;
	else $temp =dol_stringtotime($search_DateDepose); 
	$form->select_date($temp,'search_DateDepose','','','',"add",1,1);								
	print '</td>';
	print '	<td class="liste_titre">';
	
	print select_tiers($search_tiers,'search_tiers',1, 1, $type);
	 
	print '</td><td>';
	if ($type <> 'exterieur') print select_refcnt($search_ref,'search_ref',1,1);
	print '</td><td>';
	if ($type == 'exterieur') 
		print '</td><td></td>';
	else {
		print select_statut($search_statut,'search_statut');
		print '</td>';
	 }
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
	
	
	print_liste_field_titre("DateRetrait",$_SERVER["PHP_SELF"],"dateretrait","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("DateDepose",$_SERVER["PHP_SELF"],"datedepose","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("TiRegle",$_SERVER["PHP_SELF"],"regle","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre("TiPaiement",$_SERVER["PHP_SELF"],"regle","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre("TiAction",'','','','','','','');
	if ($type=='exterieur') {
		print_liste_field_titre("NomFournisseurs",$_SERVER["PHP_SELF"],"nom","",$params,"",$sortfield,$sortorder);
		print_liste_field_titre("StatutTVA",$_SERVER["PHP_SELF"],"tva_assuj","",$params,'',$sortfield,$sortorder);
	}
	else 
		print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"nom","",$params,"",$sortfield,$sortorder);
	if (!empty($type) and $type <> 'caution') print_liste_field_titre("NbVelo",'','',"",'','','','');
	
	if ($type == 'retour') print_liste_field_titre("NbRetour",'','',"",'','','','');
		print_liste_field_titre("RefCnt",$_SERVER["PHP_SELF"],"ref","",$params,'',$sortfield,$sortorder);
	if (empty($type))
		print_liste_field_titre("NbVelo",$_SERVER["PHP_SELF"],"NbVelo","",$params,'',$sortfield,$sortorder);
	elseif ($type == 'caution')
		print_liste_field_titre("Caution",$_SERVER["PHP_SELF"],"caution","",$params,'',$sortfield,$sortorder);
	elseif ($type == 'exterieur')
		print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"nom","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre("TiCreateur",$_SERVER["PHP_SELF"],"createur","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre("TiObs",'',"","",'','','','');
	if ($type <>'exterieur')  {
		print_liste_field_titre("TiStatut",$_SERVER["PHP_SELF"],"statut","",$params,'',$sortfield,$sortorder);
	}
	print_liste_field_titre('','',"","",'','','','');
/*
	print_liste_field_titre("TiRegle",$_SERVER["PHP_SELF"],"regle","",$params,'',$sortfield,$sortorder);
*/	
	
// amener le dessin gris jusqu'en fin de ligne  
	print "</td/tr>\n";
}
	
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);
		$bull->lines[] = $obj;
		print "<tr $bc[$var]>";
		if ($obj->statut == $bull->BULL_ENCOURS) {  // en gras les bulletins en cours
			$gras = '<b>';
			$fingras = '</b>';
		}	
		else	{  // retour normal
			$gras = '';
			$fingras = '';
		}	
		print "<td>";
		/* affiche l image pour la selection */		
		if (empty($obj->dateretrait) or $obj->dateretrait == 0) print " </td>\n";
			else print " ".$gras.$w->transfDateFr($obj->dateretrait).'&nbsp&nbsp&nbsp'.$w->transfHeureFr($obj->dateretrait)."</td>\n";
		if (empty($obj->datedepose) or $obj->datedepose == 0) print "  <td></td>\n";
			else print " <td>".$gras.$w->transfDateFr($obj->datedepose).'&nbsp&nbsp&nbsp'.$w->transfHeureFr($obj->datedepose)."</td>\n";
		
/*		$img = '';

			if ($obj->regle == $bull->BULL_NON_PAYE  and $obj->montantdu > 0) { $img=$bull->IMG_NON_PAYE; $texte=$bull->LIB_NON_PAYE;}
			elseif ($obj->regle == $bull->BULL_NON_PAYE  and $obj->montantdu <= 0) {$img=''; $texte='';}
			elseif ($obj->regle ==$bull->BULL_INCOMPLET) {$img=$bull->IMG_INCOMPLET; $texte=$bull->LIB_INCOMPLET;}
			elseif ($obj->regle ==$bull->BULL_PAYE) {$img=$bull->IMG_PAYE; $texte=$bull->LIB_PAYE;}
			elseif ($obj->regle ==$bull->BULL_SURPLUS) {$img=$bull->IMG_SURPLUS; $texte=$bull->LIB_SURPLUS;}
			elseif ($obj->regle ==$bull->BULL_REMB) {$img=$bull->IMG_REMB; $texte=$bull->LIB_REMB;}
			elseif ($obj->regle ==$bull->BULL_FACTURE) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACTURE;}
			elseif ($obj->regle ==$bull->BULL_ARCHIVE) {$img=''; $texte='';}
			else { $img = ''; $texte = 'inconnu '. $obj->regle;}
		print '<td>';
		if ($type <>'exterieur'  and !empty($texte)) {
			if ($img) print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
			print '&nbsp';
			if (!empty($obj->PmtFutur)) {	$texte =  $obj->PmtFutur; 		print info_admin($texte,1); }
		}
			print '</td>';
*/
		print '<td>';
		$wfrmcm = new FormCglCommun ($db);
		print $wfrmcm->AffichImgRegleBull( $obj->regle, 'Loc', $obj->statut, $obj->dated, $obj->fk_facture, $obj->abandon);
		unset ($wfrmcm);
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
		/*
		print "<td>";
		if (!empty($obj->ActionFuture)) {
			$texte = $obj->ActionFuture; 
			print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png">';
		}
		print "</td>";
		*/
		
		if ($type == 'exterieur') {
			print "<td>".getNomUrl("object_company.png", 'Tiers',0,$obj->fk_fournisseur)."&nbsp".$gras.$obj->fournisseur."</td>";			
			if ($obj->tva_assuj == 0) $temp = 'Non assujetti TVA';
			else $temp = 'Assujetti TVA';
			print "<td>".$gras.$temp."</td>";
		}
		else print "<td>".getNomUrl("object_company.png", 'Tiers',0,$obj->id_client)."&nbsp".$gras.$obj->nom."</td>";		
		if ($type == 'caution')
			print "<td>".$gras.$obj->caution."</td>";
		//$nbattendu = ($obj->NbMatMad)?$obj->NbMatMad:0;
		//$nbattendu += (empty($obj->NbRando))?0:$obj->NbRando;
		$nbattendu = (empty($obj->NbVeloAtt))?0:$obj->NbVeloAtt;
		$nbattendu += ($obj->NbMatMad)?$obj->NbMatMad:0;
		$nbattendu += (empty($obj->NbRando))?0:$obj->NbRando;
		if ($type == 'retour') print "<td>".$nbattendu."</td>";
		if ($type == 'exterieur') {
			print "<td>".$gras.$obj->NbVelo."</td>";
			//print "<td>".getNomUrl("object_company.png", 'Tiers',0,$obj->id_client)."&nbsp".$gras.$obj->nom."</td>";			
		}
				
		print "<td>".getNomUrl("object_company.png", 'MAJLoc',0,$obj->rowid)."&nbsp".$gras.$obj->ref;
		if (!empty($obj->InfoRem)) { $texte = $langs->trans("Remise").': '.$obj->InfoRem;  print info_admin($texte,1); }
		print "</td>";			

		
		if (empty($type)) print "<td>".$gras.$obj->NbVelo."</td>";
		if ($type == 'exterieur' ) 
			print "<td>".getNomUrl("object_company.png", 'Tiers',0,$obj->id_client)."&nbsp".$gras.$obj->nom."</td>";	
		print "<td>".$gras.$obj->createur."</td>";
		print $fingras;
		
		// Obervation privee - Info
		/*
		print "<td>";
		if ( !empty($obj->ObsPriv )) {
			$text = $langs->trans("DefObsPriv").':'.$obj->ObsPriv;
			print info_admin($text,1);
		}
		*/
/*		print "</td>";
			if ($obj->statut == $bull->BULL_ENCOURS) { 	 $img=$bull->IMG_ENCOURS; $texte=$bull->LIB_CNT_ENCOURS;}
			elseif ($obj->statut == $bull->BULL_PRE_INSCRIT) { $img=$bull->IMG_PRE_INS ; $texte=$bull->LIB_PRE_INS;}
			elseif ($obj->regle < $bull->BULL_FACTURE and $obj->statut == $bull->BULL_CLOS and !empty($obj->fk_facture)) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACT_INC;}
			elseif ($obj->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_CNT_FACT_INC; $texte=$bull->LIB_CNT_FACT_INC;}
			elseif ($obj->statut == $bull->BULL_VAL ) { $img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
			elseif ($obj->statut == $bull->BULL_DEPART ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_DEPART;}
			elseif ($obj->statut == $bull->BULL_RETOUR ) { $img=$bull->IMG_RETOUR; $texte=$bull->LIB_RETOUR;}
			elseif ($obj->statut == $bull->BULL_CLOS ) { $img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
			elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
			else { $img = ''; $texte = 'inconnu '. $obj->statut;}
*/
		if ($type <>'exterieur' ) 
			{
/*				print '<td><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'"></td>';
*/
				print '<td>';
				$wfrmcm = new FormCglCommun ($db);
				print $wfrmcm->AffichImgStatutBull($obj->statut, $obj->regle, 'Loc', $obj->fk_facture) ;
				unset ($wfrmcm);
				print '</td>';	
			}


		//print '<input type="image" src="../../theme/'.$conf->theme.'/img/edit.png" border="0" name="tiers_edit" alt="'.$langs->trans("Modif").'">';
		print "</td>\n";

		$nb=NbPmtNeg($obj->rowid);
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
	}	
	
	print "</td/tr>\n";
	print '</table>';
	print '<br><br><br>';
// End of page
llxFooter();
$db->close();

?>
