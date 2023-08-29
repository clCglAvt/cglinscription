<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
 * Version CAV - 2.8 - hiver 2023 - Pagination (suppression Ajout)
 * 								  - vérification de la fiabilité des foreach
 *								   - refonte de l'écran matériel loue
 *								- vérification des conflits pour planning vélo 
 * Version CAV - 2.8.3 - printemps 2023 - Couleur des lignes Vélos Sortis et vélos en retour ce jour - bug 270
 *		- formatage des référence des vélos en conflit (gras, image d'info)( bug 269)
 * Version CAV - 2.8.4 - printemps 2023
 *		- ajout liste des contrats en conflit de lication (304)
 * Version CAV - 2.8.5 - printemps 2023
 *		- vérification à la volée des conflit de vélo  (308b)
 *		- tri croissant des vélos de retour ce jour (evo 329)
 *
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
// Change this following line to use the correct relative path from htdocs
//dol_include_once ('/class/cgllocation.class.php');
require_once ('./class/atelier.class.php');
require_once ('./class/bulletin.class.php');
require_once ('./class/cgllocation.class.php');
require_once ('./class/cglinscription.class.php');
require_once ('../cglavt/class/cglFctCommune.class.php');
require_once ('./class/html.formcommun.class.php');
require_once ('./core/modules/cglinscription/modules_cglinscription.php');
if ($conf->cahiersuivi) {
	require_once ('../CahierSuivi/class/html.suivi_client.class.php');
	require_once ('../CahierSuivi/class/suivi_client.class.php');
}
// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");
// Get parameters
if ('Get parameters' == 'Get parameters') {
	$id		= GETPOST('id','int');
	$action	= GETPOST('action','alpha');
	$myparam	= GETPOST('myparam','alpha');
	$page		= GETPOST("page",'int');
	$search_Date= GETPOST("search_Date",'alpha');
	$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
	// récupération des paramètre de l'URL
	global $search_Date;
	
	$dt_now = dol_now('tzuser');
	$search_date_aujourdhui = GETPOST("aujourd_hui",'alpha');
	$search_date_demain = GETPOST("demain",'alpha');
	$search_date_apresdemain = GETPOST("apres_demain",'alpha');	
	$now = dol_print_date($dt_now, '%Y%m%d');
	if (!empty($search_date_aujourdhui)) 
			$search_Date = dol_print_date($dt_now, '%d/%m/%Y') ;
	elseif (!empty($search_date_demain))   {
			$search_Date =  dol_print_date($dt_now + (3600*24), '%d/%m/%Y');
	}
	elseif (!empty($search_date_apresdemain))  {
			$search_Date =  dol_print_date($dt_now+ (2*3600*24), '%d/%m/%Y'); 
	}
	elseif (GETPOST("search_Date", 'alpha'))
		$search_Date=trim(GETPOST("search_Date", 'date'));
	else $search_Date = dol_print_date($dt_now, '%d/%m/%Y') ;
	// Si on demande une date antérieur, les matériels loués seront tous les matériels
	if (strtotime(str_replace(array('/'),'-',$search_Date)) < strtotime(dol_print_date($dt_now, '%d-%m-%Y') ))
			$search_touteslocations=true;
	else $search_touteslocations=false;
		
	$search_serv=trim(GETPOST("search_serv", 'int'	));
	$search_tiers=trim(GETPOST("search_tiers", 'alpha'));
	$search_mat=trim(GETPOST("search_mat", 'alpha'));
	$search_refmat=trim(GETPOST("search_refmat", 'alpha'));
	$search_ref=(GETPOST("search_ref", 'int'));
	$search_statut=(GETPOST("search_statut", 'int'));
	$sortfield=GETPOST("sortfield",'alpha');
	$sortorder=GETPOST("sortorder",'alpha');
			
	if (empty($sortfield)) $sortfield="fldate,ChpTridate ";

	// Gestion des pages d'affichage des tiers
	if ($page == -1 or empty($page)) $page = 0; 
	$offset = (int)$limit * $page ;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	}
	
global $dossiers;
$dossiers = array();	

$form=new Form($db);
$w = new CglFonctionCommune($db) ;
$bull = new Bulletin($db);
$atelier = new AtelierPrep($db);
global $db, $bull , $atelier, $conf, $langs;

	
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

     function getNomUrl($withpicto=0,$option='',$maxlen=0, $id, $label = "")
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
		elseif ($option == 'dossier'){
			 $result = '<a href="'.DOL_MAIN_URL_ROOT.'/custom/CahierSuivi/suivi_client/list_dossier.php?typeliste=dossier&Refdossier='.$id.'" >' ;
			$result .= '<img border = 0 title="'.$langs->trans("AideModifDossier",$label).'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifDossier",$label).'">';
		}
		elseif ($option == 'Contrat' and empty($id)){
			 $result = '<a href="'.DOL_MAIN_URL_ROOT.'/custom/cglinscription/location.php?ref_contrat='.$label.'" >' ;
			$result .= '<img border = 0 title="'.$langs->trans("Contrat",$label).'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Contrat",$label).'">';
		}	
       $result.=$lienfin;
       return $result;
	}//getNomUrl
	
	function select_tiers($selected='',$htmlname='socid',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';
        // On recherche les societes
		$sql = "SELECT distinct T.rowid, T.nom ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull";	

		$sql.= " left join ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";	
		$sql.= " WHERE b.statut <  ".$bull->BULL_CLOS ;
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
	function select_serviceinscrit($selected='',$htmlname='search_serv',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull, $search_Date;

        // On recherche les services
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
    }/* select_serviceinscrit */
/*	function select_materielinscrit($selected='',$htmlname='search_mat',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';

        // On recherche les materiels

		$sql = "SELECT distinct bd.materiel, bd.materiel ";
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
            if ($num)   {
                while ($i < $num)   {
                    $obj = $db->fetch_object($resql);
                    $label=$obj->materiel;
                    if (!empty($selected ) && $selected == $obj->materiel)     {
                        $out.= '<option value="'.$obj->materiel.'" selected="selected">'.$label.'</option>';
                    }
                    else     {
                        $out.= '<option value="'.$obj->materiel.'">'.$label.'</option>';
                    }
                    $i++;
                }
            }
            $out.= '</select>';
        }
        else  dol_print_error($db);

        return $out;
    }// select_materielinscrit 
*/
	function select_refmatinscrit($selected='',$htmlname='search_refmat',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';

        // On recherche les societes

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
    }/* select_refmatinscrit */
	function select_refcnt($selected='',$htmlname='search_ref',$showempty=0, $forcecombo=0)
    {
        global $conf,$user,$langs, $db, $bull;

        $out='';

        // On recherche les societes

		$sql = "SELECT distinct b.rowid, b.ref ";
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
			$sql.= "  WHERE b.statut <  ".$bull->BULL_CLOS;
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
	/*
	* Renvoie le code HTML style='color:....', avec la couleur asscoié au stade de location
	*
	* @param int $num voir fonction AfficheRuptureListe
	* @retour	le code HTML
	*/
	function ColorRuptureListe($num)
	{
		/*1 ==> départ du jour ==> blanc
		2 ==> retour du jour ==> bleu clair
		3 ==> dehors en ce moment ==> gris
		*/
		$out = 'style="color:';
		if ($num == 1) { $out .= 'Brown';}
		elseif ($num == 2) { $out .= 'blue';}
		elseif ($num == 3) { $out .= 'black';}
		elseif ($num ==4) { $out .= 'grey';}
		elseif ($num ==5) { $out .= 'brass';}
		$out .= '"';
		return $out;
		
		
	} //ColorRuptureListe

	function PrepScript() 
	{

		$out = '';
		$out.= '<script > '."\n";	

		$out.= 'function creerobjet(fichier)  '; 
		$out.= '{  '; 
			$out.= '	if(window.XMLHttpRequest) ';  
			$out.= '		xhr_object = new XMLHttpRequest();  '; 
			$out.= '	else if(window.ActiveXObject)';  // IE  
			$out.= '		xhr_object = new ActiveXObject("Microsoft.XMLHTTP"); '; 
			$out.= '	else '; 
			$out.= '			return(false); ';
			$out.= '	xhr_object.open("GET", fichier, false);'; 
			$out.= '	xhr_object.send(null); ';
			$out.= '	if(xhr_object.readyState == 4)'; 
			$out.= '		return(xhr_object.responseText); '; 
			$out.= '	else'; 
			$out.=  '		return(false); '; 
		$out.= '	}'; 
		$out.=  "\n";	
		
		// Enregistrer l'action comme réalisée	
		$out.=  'function ModLocStatut(o, id_bull, origine) '; 
		$out.=  '{ '; 		
			$out.=  '  url="./ReqEnrBullStatut.php?ID=".concat(id_bull);';
			$out.=  " if (origine == 1) url = url.concat('&demande=DepartFait');
					else if (origine == 2)  url = url.concat('&demande=BullClos');";
			$out.=  "		var	Retour = creerobjet(url); ";
			$out.=  '}';
			$out.=  "\n";
		$out.= '</script > '."\n";	
					
		return ($out);	
	} //PrepScript

// construction SQL de recherche
if ('OrdreSql' == 'OrdreSql') {
	$atelier->fetch_lines($search_touteslocations);
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
 /*
 $arrayofmassactions = array();
    $massactionbutton = $form->selectMassAction('', $arrayofmassactions);
*/
/* à supprimer après V2.8
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

// Put here content of your page
/*******************************************************************
* TEST
*
********************************************************************/
$help_url='FR:Module_Inscription';

// Clique sur bouton Vider les filtres
if (GETPOST("button_removefilter_x", 'alpha'))
{
    $search_Date='';
	$search_tiers="";
	$search_mat="";
	$search_refmat="";
	$search_ref="";
	$search_statut="";
	$sortfield="";
	$sortorder="";
	$search_serv="";
}

if ('Entete' == 'Entete') {
	// permet d'afficher le petit livre, le titre la succession des page et le numéro de la  page courante
	// paramètres a passer dans les boutons de page successives
	$params = "&type=".$type."&amp;search_Date=".$search_Date;
	$params.= "&amp;search_tiers=".$search_tiers."&amp;search_ref=".$search_ref."&amp;search_serv=".$search_serv."&amp;search_mat=".$search_mat;
	$params.="&amp;search_refmat=".$search_refmat."&amp;search_statut=".$search_statut;

	/* titre */
	switch (true) { 
		case  (!empty(GETPOST("aujourd_hui",'alpha'))): 
			$title=$langs->trans("ListeMatAuj"); 
			break;
		case (!empty(GETPOST("demain", 'alpha'))):
			$title=$langs->trans("ListeMatHier", GETPOST("demain", 'alpha')  );
			break;
		case (!empty(GETPOST("apres_demain", 'alpha') )):
			$title=$langs->trans("ListeMatHier", GETPOST("apres_demain", 'alpha') );;
			break;
		case (strtotime(str_replace(array('/'),'-',$search_Date)) < strtotime(dol_print_date($dt_now, '%d-%m-%Y') )): 
			$title=$langs->trans("ListeMatHier", date('l d M Y', strtotime(str_replace(array('/'),'-',GETPOST("search_Date", 'alpha'))) ) );
			break;
		case (strtotime(str_replace(array('/'),'-',$search_Date)) > strtotime(dol_print_date($dt_now, '%d-%m-%Y') )):
			$title=$langs->trans("ListeMatUltr", date('l d M Y', strtotime(str_replace(array('/'),'-',GETPOST("search_Date", 'alpha'))) ) );
			break;
		default : 
			$title=$langs->trans("ListeMat"); 
		}	
	
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
	print_liste_field_titre("RefCnt",$_SERVER["PHP_SELF"],"","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre('',$_SERVER["PHP_SELF"],'',"",'','','','');

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
	if (empty($search_Date) or $search_Date == 0) $temp = -1;
	else $temp =dol_stringtotime($search_Date); 
	$form->select_date($temp,'search_Date','','','',"add",1,1); 		
	print '</td><td>';
	
	print select_tiers($search_tiers,'search_tiers',1, 1);
	print '</td><td class="liste_titre">';
	print select_serviceinscrit($search_serv,'search_serv',1, 1);
	print '</td><td>';
//	print select_materielinscrit($search_mat,'search_mat',1,1);
//	print '</td><td>';		
	print select_refmatinscrit($search_refmat,'search_refmat',1,1);
	print '</td><td>';
	
	print select_refcnt($search_ref,'search_ref',1,1);
	print '</td><td>';
	print select_statut($search_statut,'search_statut');
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
	
	
	// affiche la barre grise des champs affichés
	print '<tr class="liste_titre">';

	/* pour la liste des location : date retrait/date depose/tiers/ref bull/Nb Velo
	* pour la liste de matériel : date retrait/date depose/tiers/ref mzt
	* pour la liste des retours : date retrait/date depose/tiers/ref bull
	* pour la liste des cautions : date retrait/date depose/tiers/montant caution
	*/
	
	
	print_liste_field_titre("DateRetrait",$_SERVER["PHP_SELF"],"dateretrait","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("DateDepose",$_SERVER["PHP_SELF"],"datedepose","",$params,'',$sortfield,$sortorder);

	print_liste_field_titre("NomTiers",$_SERVER["PHP_SELF"],"nom","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre("Service",$_SERVER["PHP_SELF"],"label","",$params,'',$sortfield,$sortorder);
//	print_liste_field_titre("Materiel",$_SERVER["PHP_SELF"],"materiel","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("RefVelo",$_SERVER["PHP_SELF"],"refmat","",$params,'',$sortfield,$sortorder);
	
	print_liste_field_titre("Contrat",$_SERVER["PHP_SELF"],"browid","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre("TiCreateur",'',"","",'','','','');
	if ($conf->cahiersuivi)
		print_liste_field_titre("TiDosActionEch",'',"","",'','','','');
	print_liste_field_titre("TiDepart",'',"","",'','','','');
	print_liste_field_titre("TiCLoture",'',"","",'','','','');
		print_liste_field_titre("TiStatut",$_SERVER["PHP_SELF"],"statut","",$params,'',$sortfield,$sortorder);
		print_liste_field_titre("TiRegle",$_SERVER["PHP_SELF"],"regle","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre('','',"","",'','','','');
	
	// amener le dessin gris jusqu'en fin de ligne  
	print "</tr>\n";
	 
	if ($conf->cahiersuivi) {
		 // préparation du js
		$wformsuivi = new FormCglSuivi($db);
		print $wformsuivi->PrepScript('materiel');
		$wcglsuivi = new cgl_dossier ($db);
	}
	print PrepScript();
		
 	print "<p>&nbsp;</p>\n";
 	print "<p>&nbsp;</p>\n";
}	
	$ancdate = '';
	//if ( count($atelier->lines) > 0) {
	if (empty($search_Date))		
		$fldate_sav = 4;
	else $fldate_sav = 0;
	
/*	
		print '<tr><td colspan=6>';
		print AfficheRuptureListe(1);
		print '</td></tr>';
		$fldate_sav = 1;
		$style = ColorRuptureListe (1);
	}
	*/
	// conserver les bulletin déjà affichés pour ne pas surcharger l'écran avec les boutons DepartFait et Clore
	$bulls = array();

	foreach ($atelier->lines as $obj)
	{	
		// Entête des groupes de matériels
		if ($obj->fldate <> $fldate_sav and !empty($search_Date)) {
			$var = 0;
			print '<tr '.$bc[$var].'><td>&nbsp;</td></tr><tr><td  colspan=12>';
			print AfficheRuptureListe($obj->fldate);
			print '</td></tr>';
			$fldate_sav = $obj->fldate;
			$style = ColorRuptureListe ($obj->fldate);
			$var=1;
		}
/*	
		else {
			if (empty($ancdate)) $ancdate = $obj->dateretrait;
			else if ($ancdate <> $obj->dateretrait) {
				$var = 0;
				$ancdate = $obj->dateretrait;
				print '<tr '.$bc[$var].'><td> ======</td></tr>';
				$var=1;
			}
		}		
*/		
		// tracé deslignes pour lisibilité
		print "<tr $bc[$var]>";
		
		// Signaler les contrats non encore validés, ie en cours
		if ($obj->statut == $bull->BULL_ENCOURS) {  
			$gras = '<b>';
			$fingras = '</b>';
		}	
		else	{  // retour normal
			$gras = '';
			$fingras = '';
		}

		/* affiche l image pour la selection */		
		print "<td ".$style." width='40px'	>";
		print " ".$gras.$w->transfDateFr($obj->dateretrait).'&nbsp&nbsp&nbsp'.$w->transfHeureFr($obj->dateretrait);
		print "</td>\n";
		print "<td ".$style."	>";
		print " ".$gras.$w->transfDateFr($obj->datedepose).'&nbsp&nbsp&nbsp'.$w->transfHeureFr($obj->datedepose);
		print "</td>\n";
		print "<td ".$style."	>".getNomUrl("object_company.png", 'Tiers',0,$obj->id_client)."&nbsp".$gras.$obj->nom."</td>";
		print "<td ".$style."	>".$gras.$obj->label."</td>";
//		print "<td ".$style.$styleVelo."	>".$gras.$obj->materiel."</td>";
		$styleMat=$style;
		if ($obj->fl_conflitIdentmat == true) {
			$styleMat='style="color:red;font-weight:bold;"';
		}
		print "<td ".$styleMat."	>".$gras.$obj->refmat;
		if ($obj->fl_conflitIdentmat == true)	{	
			$styleMatList='style="color:red;font-weight:normal;"';	
			print  '<br ><span '.$styleMatList.' >';
			print lienContrat($obj->lstCntConflit);
			print '</span>';
		}
		print "</td>";
		print "<td ".$style."	>".$gras;
		print getNomUrl("object_company.png", 'MAJLoc',0,$obj->id)."&nbsp";
		print $gras.$obj->ref."</td>";
	//	print "<td ".$style."	>".$gras.$obj->createur."</td>";
		print $fingras;
		
/*		// Observation privée		
		print "<td ".$style."	>";
		if ( !empty($obj->ObsPriv )) {
			$text = $langs->trans("DefObsPriv").':'.$obj->ObsPriv;
			print info_admin($text,1);
		}	
		print "</td>";
*/

		// Dossier - Action avec image
		if ($conf->cahiersuivi) {
			print "<td ".$style."	id='IdListeAction".$obj->fk_dossier."'>";
			if (!empty($obj->fk_dossier)) {
				print getNomUrl("object_company.png",'dossier',$maxlen=0, $obj->fk_dossier, $obj->nomdossier);
				$ret = $wcglsuivi->IsActionDossier($obj->fk_dossier);
				if ($ret) { 
					$texte = $wformsuivi->ConstructAction($obj->fk_dossier, 1);
					print $texte;
				}
			}
	//		print info_admin($text,1);
			print '</td>';	
		}
		// Depart Fait/a faire
		print '<td id="DepartFait'.$obj->fk_dossier.'">';
		
		// <a href="action.do?param=5" onclick="javascript:return sauvegarder();">lien</a>
		//		print '<div class="inline-block divButAction">';
		//print '<a class="butAction"  href="#" onclick="DepartFait(this, "'.$obj->browid.'"); return true;">';
		// C'est var_dump($obj) qui m'a permis de savoir que browid du SQL de atelier était stocké dans id
		$urlreaff = $_SERVER['PHP_SELF'] . '?'.$params.'&sortfield='.$sortfield;
		
		if (empty($bulls[$obj->id])) {
			if ($obj->fldate < 4 and $obj->statut < $bull->BULL_DEPART) {
				print '<a class="butAction"  href="'.$urlreaff.'" onclick="ModLocStatut(this, '.$obj->id.', 1);" alt='.$langs->trans("CntLbDepartfait").'>';
				print $langs->trans('CntDepartFait').'</a></div>';
			}
			else  {
				print '<a class="butActionRefused"  href="'.$urlreaff.'" onclick="ModLocStatut(this, '.$obj->id.', 1);" alt='.$langs->trans("CntLbDepartfait").'>';
				print $langs->trans('CntDepartFait').'</a></div>';
			}
		}	
		print '</td>';	
		// LO CLoturé/A cloturer
			print '<td id="BullClos'.$obj->fk_dossier.'">';
		if ($obj->fldate < 4 and empty($bulls[$obj->id])) {
			if ($obj->fldate <> 2 and$obj->statut >= $bull->BULL_DEPART) {
				print '<a class="butAction"  href="'.$urlreaff.'" onclick="ModLocStatut(this, '.$obj->id.', 2);">';
				print $langs->trans('CntClore').'</a></div>';
			}
			else  {
				print '<a class="butActionRefused"  href="'.$urlreaff.'" onclick="ModLocStatut(this, '.$obj->id.', 2);">';
				print $langs->trans('CntClore').'</a></div>';
			}
		}	
		$bulls[$obj->id] = $obj->id;

		
		//butActionRefused
		print '</td>';

		$dossiers[$obj->fk_dossier] = $obj->fk_dossier;
/*		print "</td>";
			if ($obj->statut == $bull->BULL_ENCOURS) { 	 $img=$bull->IMG_ENCOURS; $texte=$bull->LIB_CNT_ENCOURS;}
			elseif ($obj->regle < $bull->BULL_FACTURE and $obj->statut == $bull->BULL_CLOS and !empty($obj->fk_facture)) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACT_INC;}
			elseif ($obj->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_CNT_FACT_INC; $texte=$bull->LIB_CNT_FACT_INC;}
			elseif ($obj->statut == $bull->BULL_VAL ) { $img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
			elseif ($obj->statut == $bull->BULL_DEPART ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_DEPART;}
			elseif ($obj->statut == $bull->BULL_RETOUR ) { $img=$bull->IMG_RETOUR; $texte=$bull->LIB_RETOUR;}
			elseif ($obj->statut == $bull->BULL_CLOS ) { $img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
			elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
			else { $img = ''; $texte = 'inconnu '. $obj->statut;}

		if ($type <>'exterieur' and !empty($texte))  print '<td '.$style.'	><img border="0" title="'.$texte.'"  src="../../theme/eldy/img/'.$img.'" alt="'.$texte.'">';
		print '</td>';
		$texte='';
		$img = '';
		if ($obj->statut == $bull->BULL_ENCOURS) { $img=$bull->IMG_ENCOURS ; $texte=$bull->LIB_CNT_ENCOURS;}
		if ($obj->statut == $bull->BULL_PRE_INS) { $img=$bull->IMG_PRE_INS ; $texte=$bull->LIB_PRE_INS;}
		elseif ($bull->BULL_FACTURE and $obj->statut == $bull->BULL_CLOS and !empty($obj->fk_facture)) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACT_INC;}
		elseif ($obj->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_CNT_FACT_INC; $texte=$bull->LIB_CNT_FACT_INC;}
		elseif ($obj->statut == $bull->BULL_VAL) {$img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
		elseif ($obj->statut == $bull->BULL_CLOS) {$img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
		elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
		if (empty($img) and !empty($texte)) 
			print info_admin($texte,1);		
		elseif (!empty($texte))
			print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
		else print $obj->statut;	
*/

		print '<td	 align="center">';
		$wfrmcm = new FormCglCommun ($db);
		print $wfrmcm->AffichImgStatutBull($obj->statut, $obj->regle, 'Loc',$obj->fk_facture);
		print '</td>';	
		print '<td>';


		print $wfrmcm->AffichImgRegleBull( $obj->regle, 'Loc', $obj->statut, $obj->dated, $obj->fk_facture, $obj->abandon);		
		unset ($wfrmcm);
/*		print '</td>';	


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

		print '<td  '.$style.'	>';
		if ($type <>'exterieur' and !empty($texte)) 
			print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="../..//theme/eldy/img/'.$img.'">';
		//print '<input type="image" src="../../theme/'.$conf->theme.'/img/edit.png" border="0" name="tiers_edit" alt="'.$langs->trans("Modif").'">';
*/	
		$nb=NbPmtNeg($obj->id);
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
	
	if ($conf->cahiersuivi) {
		 // préparation du js
		unset($wformsuivi);
		unset($wcglsuivi);
	}

	print "</td/tr>\n";
	print '</table>';
	print '<br><br><br>';	
// Bouton Edition pour la liste de matériel
if (!empty($search_Date))	{

		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre
		// Documents generes
		//$filedir = $conf->cgllocation->dir_output .'/'. dol_sanitizeFileName($bull->ref);

		$filedir = $conf->cglinscription->dir_output ;
		
		$wf = new CglFonctionCommune($db);
		$wdate = $wf->transfDateMysql($search_Date);
		unset ($wf);
		
		$datejour = $wdate;
			
		$filedir .= '/atelier/'.$datejour;
		//$modulesubdir = 'atelier/'.dol_sanitizeFileName($search_DateRetrait);
		
		$wf = new CglFonctionCommune($db);
		$wdate = $wf->transfDateMysql($search_Date);
		unset ($wf);
		$modulesubdir = 'atelier/'.$wdate;
		$urlsource = $_SERVER['PHP_SELF'] . '?'.$params.'&'.$sortfield;
		
		//$genallowed = $user->rights->cglinscription->creer;
		$genallowed = 1;
		//$delallowed = $user->rights->cglinscription->supprimer;
		//$delallowed = $user->rights->cgllocation->supprimer;
//		print showdocuments('cglinscription',$modulesubdir, $filedir, $urlsource, $genallowed, $delallowed, 'atelier', 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);
		$wff = new FormFile($db);
		print $wff->showdocuments('cglinscription',$modulesubdir, $filedir, $urlsource, $genallowed, $delallowed, 'atelier', 1, 0, 0, 0, 0, '', '', '', $soc->default_lang);
		//print $this->show_bull( $file, $line);
		print '</div></div>';	
}//AfficheEdition
// End of page
llxFooter();
$db->close();

function creer_ficheatelier($atelier)
{
	global $db, $langs;
	
	$typeModele = 'atelier_odt:'.DOL_DATA_ROOT.'/doctemplates/bulletin/atelier.odt';
	cgl_atelier_create($db, $typeModele,   $langs, $file);
} //creer_ficheatelier

function lienContrat($chaine)
{
	if (empty($chaine)) return null;
	$out = "";
	$tab = array();
	$tab = explode(' ',$chaine);
	foreach ($tab as $tabelem) {
		$out .= getNomUrl("object_company.png", 'Contrat',0,'', $tabelem).$tabelem.'<br>';
	} // Foreach
	return $out;
} //lienContrat
?>
