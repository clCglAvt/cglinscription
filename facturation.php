<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr 
 *					 	- Remplacer method="GET" par method="POST"
 *						- correction requete principale d'affichage
 * Version CAV - 2.8 - hiver 2023
 *		 - Pagination (suppression Ajout)
 * 		- vérification de la fiabilité des 
 *		- Séparation refmat en IdentMat et marque 
 *		- contrat/bulletin technique
 * Version CAV - 2.8.3 - printemps 2023
 *		- le modèle proposé pour la facture doit être cohérent avec LO/BU/moniteurs et le même que cemlui utilisé pour la facture ( bug 271)
 * Version CAV - 2.8.4 - printemps 2023
 *		- PostActivité
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
 *   	\file       custom/cglinscription/list.php
 *		\ingroup    cglinscription
 *		\brief      Liste les inscriptions
 */
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
require_once('./class/bulletin.class.php');
require_once('./class/cglcommunlocInsc.class.php');

require_once('./class/cglinscription.class.php');
require_once('./class/cgllocation.class.php');
require_once('./class/cglInscDolibarr.class.php');
require_once('./class/cgldepart.class.php');
require_once('../cglavt/class/cglFctCommune.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';	
require_once("./class/html.formcommun.class.php");

global $db, $conf, $langs;
global $ecran;
$BUL_CONFABANDON = 'confArchiveBull';
global  $BUL_CONFABANDON;
$bull = new Bulletin ($db);
$w = new CglFonctionCommune($db);
$w1 = new cglInscDolibarr($db);
$TraitCommun = new CglCommunLocInsc($db);
$form = new Form($db);
// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");

/* Reprise des parametres passés  */
if ('GETPOST_PARAM' =='GETPOST_PARAM') {
	$id		= GETPOST('id','int');
	global $type;
	$type		= GETPOST('type','alpha');
	if (empty($type)) $type = 'Insc';
	$action	= GETPOST('action','alpha');
	$file	= GETPOST('file','alpha');
	$confirm	= GETPOST('confirm','alpha');
	$myparam	= GETPOST('myparam','alpha');
	$page		= GETPOST("page",'int');	
	$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
	$search_tiers		= GETPOST("search_tiers",'alpha');
	$search_moniteur		= GETPOST("search_moniteur",'int');
	$search_ref		= GETPOST("search_ref",'alpha');
	$sortorder = GETPOST ( 'sortorder', 'alpha' );
	$sortfield = GETPOST ( 'sortfield', 'alpha' );
	$ecran = GETPOST ( 'ecran', 'alpha' );
	$tbrowid = array();
	$tbrowid = GETPOST("rowid", 'array');
	$search_fk_raisrem=trim(GETPOST("search_fk_raisrem"));
	$search_reslibelle=trim(GETPOST("search_reslibelle"));
//CCA DOUBLE
	$sall = GETPOST('sall', 'alpha');
//CCA DOUBLE
	
	// param?es a passer dans les boutons de page successives
//CCA Double
	$params = "&type=".$type."&search_tiers=".$search_tiers."&search_ref=".$search_ref."&search_moniteur=".$search_moniteur;
	$params.="&amp;reslibelle=".$search_reslibelle."&amp;fk_raisrem=".$search_fk_raisrem;
	$params.="&amp;sall=".$all;
	$params.="&amp;limit=".$limit;
	if ($sall) $params.="&amp;ecran=archivestock";
	else $params .= "&ecran=".$ecran;
//CCA DOUBLE
	
}

// Protection if external us// Tri standard
if ($ecran == 'archive' ) {
	/* demande d'ordonnancement identique pour BU et LO 
	if ($type == 'Loc') {
		if (empty($sortfield)) 	 $sortfield.="f.date_valid";;
		if (empty($sortorder)) $sortorder.=" DESC";
	}
	else {	*/
		if (empty($sortfield)) 	 $sortfield.=" b.regle  , b.statut ";
		if (empty($sortorder)) $sortorder = "ASC"; 
	//}
}	
elseif ( $ecran == 'archivestock') {
	if (empty($sortfield)) 	 $sortfield.="  f.date_valid ";
	if (empty($sortorder)) $sortorder = "DESC"; 
}
$sqlorder=$db->order($sortfield,$sortorder);

// Clique sur bouton Vider les filtres
if (GETPOST("button_removefilter_x")){
    $search_DateRetrait='';
    $search_DateDepose='';
	$search_tiers="";
	$search_moniteur = "";
	$search_mat="";
	$search_marque="";
	$search_ref="";
	$search_statut="";
	$sortfield="";
	$sortorder="";
	$search_serv="";
	$search_fk_raisrem="";
	$search_reslibelle="";
}


// Gespageprevtion des pages d'affichage des tiers
if ($page == -1 or empty($page) ) { $page = 0 ; }
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Protection if external user
if ($user->societe_id > 0)
{
	accessforbidden();
}
 /* filtre SQL */
if ("SQLWHERE" =="SQLWHERE") {
	global $sqlwhere;
	$sqlwhere = " !isnull(b.typebull) and b.typebull = '".$type."' ";
	if ($ecran == 'facture')  $sqlwhere .= " and b.statut != ".$bull->BULL_ENCOURS .' and b.facturable = 1 ';
	elseif ($ecran == 'archive' and $type == 'Loc')  $sqlwhere .= " AND substr( b.ref, 3,4) = YEAR(now()) ";
	elseif ($ecran == 'archive' and $type == 'Insc')  $sqlwhere .= " and  substr( b.ref, 3,4) = YEAR(now()) ";
//	$sqlwhere .= " AND ( ";

	$now = $db->idate(dol_now('tzuser'));
	if ($ecran == 'archivestock' and $type == 'Resa' )
		$sqlwhere .= " AND YEAR('".$now."') > substr( b.ref, 3,4) ";
	elseif ($ecran == 'archivestock' and $type <> 'Resa' ){
		if (empty($sall) and ($ecran == 'archivestock'))
			$sqlwhere .= " AND  YEAR('".$now."') > YEAR (b.datec)";
		elseif ($ecran == 'archivestock') $sqlwhere .= " AND b.ref LIKE '%".$sall."%'"; 
	}
	if ($ecran == 'archivestock') $champdate = 'f.date_valid';
		elseif ($type == 'Insc') $champdate = 'ASCl.heured';
		elseif ($type == 'Loc') $champdate = 'b.dateretrait';
	// MDUo	
		elseif ($type == 'Resa') $champdate = 'b.dateretrait';
// MDUf		
	if ($ecran == 'archive' and $type == 'Loc')  {
	 
		 // bulletin facturé non archivé
		$sqlwhere .= " AND ( ";
		$sqlwhere .= " 	( b.statut >= ".$bull->BULL_CLOS." AND (b.regle =  ".$bull->BULL_FACTURE."   or b.fk_facture >0 ))";
		$sqlwhere .= "  OR (";
		// bulletin non facturé, non annulé par client, paiement >0";
		$sqlwhere .= "	 b.statut >= ".$bull->BULL_CLOS." AND b.regle <  ".$bull->BULL_FACTURE."  and b.statut <>  ".$bull->BULL_ANNULCLIENT."";
		$sqlwhere .= "	AND substr( b.ref, 3,4) = YEAR(now()) ";
		$sqlwhere .= '	and (select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1 and bd1.fk_bull = b.rowid) > 0 ';
		$sqlwhere .= "  ) OR (";
		// bulletin archivé ";
		$sqlwhere .= " 	 b.statut >= ".$bull->BULL_CLOS." AND b.regle =  ".$bull->BULL_ARCHIVE."   ";
		$sqlwhere .= "  ) OR (";
		// bulletin facturé ";
		$sqlwhere .= " 	 b.statut >= ".$bull->BULL_CLOS." AND b.regle =  ".$bull->BULL_FACTURE."   ";
		$sqlwhere .= "  ) OR (";
		// bulletin non facturé  , paiement = 0 et n'existe pas paiement négatif ";
		$sqlwhere .= "  	 b.regle <  ".$bull->BULL_FACTURE."  ";
		$sqlwhere .= "  	AND b.statut <>  ".$bull->BULL_ABANDON."  ";
		$sqlwhere .= "  	 ";
		$sqlwhere .= "  	and  ";
		$sqlwhere .= "  	(				 ";
		$sqlwhere .= '  			( b.statut >= '.$bull->BULL_CLOS.' AND (select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1 and bd1.fk_bull = b.rowid) = 0   )';
		$sqlwhere .= "  			or ";
		$sqlwhere .= '  			( b.statut >= '.$bull->BULL_CLOS.' AND (select count(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1 and bd1.fk_bull = b.rowid) = 0  ) ';
		$sqlwhere .= "  		) ";
		$sqlwhere .= '  		and (select count(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1 and  bd1.pt < 0 and bd1.fk_bull = b.rowid) = 0 ';
		$sqlwhere .= "  			)			 	 ";
		$sqlwhere .= '  or	(		b.statut = '.$bull->BULL_ANNULCLIENT." ";
		$sqlwhere .= '  		AND (select sum(pt) from llx_cglinscription_bull_det as bd1  					where bd1.action not in ("S","X") and bd1.type = 1 and bd1.fk_bull = b.rowid) = 0 ';
		$sqlwhere .= "  )			 	 ";
//		$sqlwhere .= "  	and 1=1		 	 ";
		$sqlwhere .= " )";


	} 	
	 
	elseif ($ecran == 'archive' and $type == 'Insc') {
	// les bulletins non archivés et non abandonnés des années précédentes et les bulletins facturé, les bulletin sans facture avec  paiement  =0 et sans paiement négatif. 

	 
		 // bulletin facturé non archivé
		$sqlwhere .= " AND ( ";
		$sqlwhere .= " 	(b.regle =  ".$bull->BULL_FACTURE."   or b.fk_facture >0 )";
		$sqlwhere .= "  OR (";
		// bulletin non facturé, non annulé par client, paiement >0, realisé";
		$sqlwhere .= "	b.regle <  ".$bull->BULL_FACTURE."  and b.statut <>  ".$bull->BULL_ANNULCLIENT."";
		$sqlwhere .= "	AND substr( b.ref, 3,4) = YEAR(now()) ";
		$sqlwhere .= '	and (select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1 and bd1.fk_bull = b.rowid) > 0 ';
		$sqlwhere .= "  ) OR (";
		// bulletin archivé ";
		$sqlwhere .= " 	b.regle = ".$bull->BULL_ARCHIVE."     ";
		$sqlwhere .= "  ) OR (";
		// bulletin non facturé  , paiement = 0 et n'existe pas paiement négatif , realisé";
		$sqlwhere .= "  	 b.regle <  ".$bull->BULL_FACTURE."  ";
		$sqlwhere .= "  	AND (b.statut =  ".$bull->BULL_ABANDON." or facturable = 0 ) ";
		//$sqlwhere .= "	AND YEAR(now()) > YEAR(b.datec) ";
		$sqlwhere .= "  	and  ";
		$sqlwhere .= "  	(				 ";
		$sqlwhere .= '  			(select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1 and bd1.fk_bull = b.rowid) = 0   ';
		$sqlwhere .= "  			or ";
		$sqlwhere .= '  			(select count(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1 and bd1.fk_bull = b.rowid) = 0  ';
		$sqlwhere .= "  		) ";
		$sqlwhere .= '  		and (select count(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1 and  bd1.pt < 0 and bd1.fk_bull = b.rowid) = 0 ';
		$sqlwhere .= "  )			 	 ";
		// Bulletin Annulé par CLient et remboursé intégralement
		$sqlwhere .= '  or	(		b.statut = '.$bull->BULL_ANNULCLIENT." ";
		$sqlwhere .= '  		AND (select sum(pt) from llx_cglinscription_bull_det as bd1  					where bd1.action not in ("S","X") and bd1.type = 1 and bd1.fk_bull = b.rowid) = 0 ';
		$sqlwhere .= "  )			 	 ";
		$sqlwhere .= " )";

	/*
		$sqlwhere .= "  (((";
		$sqlwhere .= " b.regle < ".$bull->BULL_ARCHIVE." AND b.statut > ".$bull->BULL_ENCOURS ." AND (isnull(".$champdate." ) or ".$champdate." < DATE_ADD('".$now."', INTERVAL -12 HOUR) )   "; 
		$sqlwhere .= "and (b.statut <>  9 or (b.statut = 9  and  b.ref like concat(concat('BU',year('".$now."')),'%')))";
		$sqlwhere .= ") OR (";
		$sqlwhere .= " b.regle = ".$bull->BULL_ARCHIVE." AND  b.ref like concat(concat('BU',year('".$now."')),'%'))";  
		$sqlwhere .= ") OR (";
		$sqlwhere .= " b.statut = ".$bull->BULL_ABANDON ."  and  b.ref like concat(concat('BU',year('".$now."')),'%')";
		$sqlwhere .= "))";
		$sqlwhere .= " AND  b.statut != ".$bull->BULL_ANNULCLIENT." ";
*/
	}

// MDUo	
	elseif ($ecran == 'archive' and $type == 'Resa')  {
	// pour resa tous les contrats clos de l'année et les contrats  non archivé des années précédentes et non abandonnés et les contrats archivés de l'année 
		$sqlwhere .= " AND ( ";
		$sqlwhere .= "  (( ";
		$sqlwhere .= " b.regle = ".$bull->BULL_ARCHIVE." AND (YEAR('".$now."') = YEAR(b.dateretrait) or YEAR('".$now."') = YEAR(b.tms))";  
		$sqlwhere .= ") OR (";
		$sqlwhere .= " b.statut = ".$bull->BULL_CLOS." AND b.regle < ".$bull->BULL_ARCHIVE;
		$sqlwhere .= ") OR (";
		$sqlwhere .= " b.statut = ".$bull->BULL_VAL." AND b.dateretrait <= now()";
		$sqlwhere .= ") OR (";
		$sqlwhere .= " b.statut = ".$bull->BULL_ABANDON ."  and  b.ref like concat(concat('RE',year('".$now."')),'%')";
		$sqlwhere .= ") OR (";
		$sqlwhere .= ' (select sum(pt) from '.MAIN_DB_PREFIX.'cglinscription_bull_det as bd1  where bd1.action not in ("S","X") and bd1.type = 1 and bd1.fk_bull = b.rowid) <> 0';	
		$sqlwhere .= ' )) AND 1=1';
		$sqlwhere .= ' AND b.ref like concat(concat("RE",year("'.$now.'")),"%")';
		$sqlwhere .= " )";
	}
// MDUf 	
	elseif ($ecran == 'facture') {;
		if ($type == 'Loc') {;
		
			$sqlwhere .= " AND ( ";
 			$sqlwhere .=  '  b.regle < '.$bull->BULL_ARCHIVE;
 			$sqlwhere .=  ' and ((';
 			$sqlwhere .=  ' 	 b.statut = '.$bull->BULL_CLOS ;
 			$sqlwhere .=  ' 		and '; 
			$sqlwhere .= ' 		!((select count(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 0 and bd1.fk_bull = b.rowid) = 0';
			$sqlwhere .= ' 		and !(select count(rowid) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1 and !isnull(bd1.pt) and bd1.pt < 0 and bd1.fk_bull = b.rowid) > 0 ';
			$sqlwhere .= ' 		and (select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1  and bd1.fk_bull = b.rowid) = 0 ';
			$sqlwhere .= " 		))";
			// pour loc tous les contrats clos non archivé de l'année et les contrats  non archivé des années précédentes 
			$sqlwhere .= "        or (";
				$sqlwhere .= "        	b.statut = ".$bull->BULL_ANNULCLIENT." and substr( b.ref, 3,4) = YEAR('".$now."') ";
				$sqlwhere .= " 			and (isnull(b.fk_facture)  or b.fk_facture = 0) "; // annulé par le client
				$sqlwhere .= "        	and (select sum(pt) 	from llx_cglinscription_bull_det as bd1 
												where fk_bull = b.rowid and  bd1.action not in ('S','X') and bd1.type = 1) <> 0  "; // annulé par le client
			$sqlwhere .= "			)";
			$sqlwhere .= "	)";
		$sqlwhere .= " )";

		} 
		elseif ($type == 'Insc') {
			$sqlwhere .= " AND ( ";
			// pour les Insc les bulletins de l'année non archivé dont l'activité est terminée 
			$sqlwhere .= " b.regle < ".$bull->BULL_ARCHIVE."  and ( ";
			$sqlwhere .= "   (  ".$champdate."  < '".$now."' ) "; // réalisé
			$sqlwhere .= "        or ";
			// pour les Insc les bulletins Annulé par client non facturé
			$sqlwhere .= "        	( b.statut = ".$bull->BULL_ANNULCLIENT." and substr( b.ref, 3,4) = YEAR('".$now."')  and (isnull(b.fk_facture) or b.fk_facture = 0) "; 
				$sqlwhere .= "        	and (select sum(pt) 	from llx_cglinscription_bull_det as bd1 
												where fk_bull = b.rowid and  bd1.action not in ('S','X') and bd1.type = 1) <> 0  "; // annulé par le client
				$sqlwhere .= ' 			and (select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1  and bd1.fk_bull = b.rowid) = 0 ';
			$sqlwhere .= " 		)";
			$sqlwhere .= " 		  or ";
			$sqlwhere .= " 		  	 (b.statut <> ".$bull->BULL_ANNULCLIENT."  and substr( b.ref, 3,4) = YEAR('".$now."')   "; // Sur l'année
			$sqlwhere .= " 				AND (";
											// CA  = 0 et pmt = 0 et existe paiement négatif
			$sqlwhere .= " 					(select count(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 0 and bd1.fk_bull = b.rowid)  = 0";
			$sqlwhere .= " 		 			and ";
			$sqlwhere .= "					( (select count(rowid) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 1 and !isnull(bd1.pt) and bd1.pt < 0 and bd1.fk_bull = b.rowid) > 0 ";
				$sqlwhere .= " 		 				 and ";
			$sqlwhere .= "						(select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 1 and  bd1.fk_bull = b.rowid) = 0 ";
		$sqlwhere .= "						)";
			$sqlwhere .= "				)";
			$sqlwhere .= "			)";
			$sqlwhere .= "			)";
		$sqlwhere .= " )";
		} 
	}
//	$sqlwhere .= " )";

 
	/*	$sql .= " AND ( ((YEAR(now()) = YEAR(b.datec)) AND b.regle = 6) or ( YEAR(now()) > YEAR(b.datec) AND b.regle <  ".$bull->BULL_ARCHIVE.")) ";	
		if ($type == 'Loc') $sql .= " and b.statut = ".$bull->BULL_CLOS;
		if ($ecran == 'facture') $sql.= " and b.regle <".$bull->BULL_ARCHIVE;
	} 
	*/
	if (!empty($search_ref))
		$sqlwhere.= " AND b.ref like '%".$db->escape($search_ref)."%'";

	if (!empty($search_tiers)) // search_tiers est à -1 quand on n'a pas fait de choix dur tiers à cause du select
			$sqlwhere.= " AND s.nom like '%".$db->escape($search_tiers)."%'";
			
	if (!empty($search_moniteur) and $search_moniteur > 0) // search_moniteur est à -1 quand on n'a pas fait de choix sur tiers à cause du select
			$sqlwhere.= " AND  form.rowid = ". $search_moniteur;
	if ( $search_moniteur == -1)  // search_moniteur est à 0 si on cherche les bulletin sans mmoniteur
			$sqlwhere.= " AND  isnull(form.rowid ) ";

} // SQLWHERE			
    /**
     *    	Return a link on thirdparty (with picto)
     *
     *		@param	int	$withpicto	Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
     *		@param	string	$option		Target of link ('', 'customer', 'prospect', 'supplier')
     *		@param	int	$maxlen		Max length of text
     *		@param	int	$id		Identifiant de l'objet
     *		@return	string				String with URL
     */
    function getNomUrl($withpicto=0,$option='',$maxlen=0, $id, $label = null)
    {
        global $conf,$langs;
        $result='';
		$lienfin='</a>';
		
		if ($option == 'MAJInscritp')		{
			$result = '<a href="./inscription.php?id_bull='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifInsc").'">';
		}	
			
		if ($option == 'Moniteur')		{
			$result = '<a href="./fiche_moniteur.php?id='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifMon").'">';
			//$result .= $label.'</a>';
		}	
		if ($option == 'MAJLoc')		{
			$result = '<a href="./location.php?id_contrat='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifLoc").'">';
		}	
		if ($option == 'MAJResa')		{
			$result = '<a href="./reservation.php?id_resa='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifResa").'">';
		}			
		if ($option == 'MAJFacture')		{
			$result = '<a href="../../compta/facture/card.php?facid='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifFct").'">';
		}				
		elseif ($option == 'Tiers'){
			 $result = '<a href="../../comm/card.php?socid='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifTiers").'">';
		}	
			   $result.=$lienfin;
			   return $result;
	}//getNomUrl
	function NbPmtNeg ($id) 
	{
		global $langs, $db;
		// si modification, penser à list, bulletin et listloc
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
	function select_moniteur($selected='',$htmlname='search_moniteur',$showempty=0, $forcecombo=0, $type ='', $ecran='')
    {
        global $conf,$user,$langs, $db, $bull;
		global $sqlwhere;
		$resql = chercheMoniteur('', $sqlwhere, '1 = 1') ;
				
        if (!($resql === false) )       {
       /*   if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)         {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            	$out.= ajax_combobox($htmlname, $events, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
			}
        */
			$out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
            if ($showempty) $out.= '<option value="0"></option>';
            if ($showempty) {
				if ($selected == -1) $out.= '<option value="-1" selected="selected">sans moniteur</option>';
				else $out.= '<option value="-1">sans moniteur</option>';
			}
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num)     {
                while ($i < $num)        {
                    $obj = $db->fetch_object($resql);
                    $label = $obj->PrenomMon.' '.$obj->NomMon;
                    if ($selected > 0 && $selected == $obj->IdMon)   
						$out.= '<option value="'.$obj->IdMon.'" selected="selected">'.$label.'</option>';
                    else  
						$out.= '<option value="'.$obj->IdMon.'">'.$label.'</option>';
                    $i++;
                }
            }
            $out.= '</select>';
        }
        else  dol_print_error($db);

        return $out;
    }/* select_tiers */
	function select_tiers($selected='',$htmlname='socid',$showempty=0, $forcecombo=0, $type ='', $ecran='')
    {
        global $conf,$user,$langs, $db, $bull;
		global $sqlwhere;

        $out='';
        // On recherche les societes
		$sql = "SELECT distinct T.rowid, T.nom ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull and bd.action not in ('S', 'X') ";	
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";
		if ($type == 'Insc' ){
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as AgS on bd.fk_activite = AgS.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_calendrier as ASCl on AgS.rowid = ASCl.fk_agefodd_session";
		}
		$sql.= " WHERE ".$sqlwhere 	;	
		$sql .= " ORDER BY T.nom";	
		
        $resql=$db->query($sql);
        if ($resql)        {
          if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)         {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            	$out.= ajax_combobox($htmlname, $events, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
			}
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
	function select_refcnt($selected='',$htmlname='search_ref',$showempty=0, $forcecombo=0, $type, $ecran='')
    {
        global $conf,$user,$langs, $db, $bull;
		global $sqlwhere;
        $out='';

        // On recherche les societes
		$sql = "SELECT distinct b.rowid, b.ref ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull and bd.action not in ('S', 'X') ";	
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";
		if ($type == 'Insc' ){
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as AgS on bd.fk_activite = AgS.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_calendrier as ASCl on AgS.rowid = ASCl.fk_agefodd_session";
		}
		$sql.= " WHERE ".$sqlwhere;
        $resql=$db->query($sql);
        if ($resql)
        {
          if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)         {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            	$out.= ajax_combobox($htmlname, $events, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
			}
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

	// $filtres uniquement pour la sélection d'un moniteur
	function chercheMoniteur($idbull=NULL, $sqlwhereecran, $filtres = NULL) 
	{
		global $db;		

		$sql = 'SELECT distinct case when isnull(form.fk_user)  or form.fk_user = 0  then sp.lastname else su.firstname end as PrenomMon, ';
		$sql .= ' case when isnull(form.fk_user)  or form.fk_user = 0  then sp.firstname  else su.firstname end as NomMon, ';
		$sql .= '  form.rowid  as IdMon';
		if (empty($filtres)) $sql .= ',sf.fk_agefodd_formateur ';
		$sql .= ' FROM  '.MAIN_DB_PREFIX.'cglinscription_bull_det as bd  ';
		$sql .= ' LEFT JOIN   '.MAIN_DB_PREFIX.'agefodd_session as AgS  ON bd.fk_activite = AgS.rowid   ';
		$sql .= ' LEFT JOIN  '.MAIN_DB_PREFIX.'agefodd_session_formateur as sf ON bd.fk_activite =  sf.fk_session  ';
		$sql .= ' LEFT JOIN    '.MAIN_DB_PREFIX.'agefodd_formateur as form  ON sf.fk_agefodd_formateur = form.rowid ';
		$sql .= ' LEFT JOIN  '.MAIN_DB_PREFIX.'socpeople as sp ON form.fk_socpeople = sp.rowid';
		$sql .= ' LEFT JOIN  '.MAIN_DB_PREFIX.'user as su ON form.fk_user = su.rowid';		
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b ON bd.fk_bull = b.rowid ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f  on b.fk_facture =  f.rowid ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s  on b.fk_soc =  s.rowid ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_calendrier as ASCl on AgS.rowid = ASCl.fk_agefodd_session ";
		
		$sql .= "  WHERE   bd.action not in ('S', 'X') and bd.type = 0 and not isnull(form.rowid) ";
		if ($idbull) $sql .= ' AND  bd.fk_bull = "'.$idbull.'"';
		elseif ($filtres) $sql .= ' AND  '.$filtres;
		if (!empty($sqlwhereecran)) $sql .= ' AND '.$sqlwhereecran;
		if ($filtres) $sql .= ' ORDER BY  1,2';
		dol_syslog ( 'ListBulletin' . "::chercheMoniteur ", LOG_DEBUG );
		$resql = $db->query ( $sql );
		if ($resql) 
			return $resql;
		else
			return false;
				
	
	} //chercheMoniteur
	function creer_avoir($bull)
	{
		global $user, $langs;
		
		$objavoir = new Facture ($db);
		$objavoir->type = 2;
		$objavoir->socid = $bull->id_client;
		$objavoir->note_private = $langs->trans("AvBukll").$bull->ref;
		$objavoir->fk_facture_source = $bull->fk_facture;
		$objavoir->modelpdf = 'FAComIND';
		$line = $objavoir->lines[0];
		$line->desc = $langs->trans("RembTropPercu");
		$line->product_type = 1;
		$line->qty = 1;
		$solde = $bull->CalculSolde();
		$line->subprice = $solde;
		$line->total_ht = $solde;
		$line->total_ttc = $solde;	
		
		$retfac = $objavoir->create($user);
		$retfac = $objavoir->validate($user);
		$retfac = $objavoir->set_paid($user,'',1);
	} //creer_avoir
	function ClotureEnMasse ()	
	{
		global $db; 
		
		// lister tous les bulletins/contrats non clos ou plus et proposer une cloture automatique
		$bull = new Bulletin ($db);
		$wtravail = new CglCommunLocInsc($db);
		
		$sql='';
		$sql.='SELECT rowid ';
		$sql.=' FROM '.MAIN_DB_PREFIX.'cglinscription_bull as b';
		$sql.=" where statut < '".$bull->BULL_CLOS."'";
		$sql.=" and bulltype < '".$bull->type."'";
		dol_syslog('Facturation.php::ClotureEnMasse -  sql='.$sql, LOG_DEBUG);
		$result = $db->query($sql);
		
		if ($result)		{	
			$num = $db->num_rows($result);
			$i=0;
			while ($i < $num) {
				$objp = $db->fetch_object($result);
				$bull->fetch_complet_filtre(-1, $objp->rowid);
				if ($bull->TotalPaimnt() == 0 and $bull->TotalFac <> 0)
				if ($bull->statut < $bull->BULL_CLOS ) $wtravail->ClotureAuto($bull);
				$i++;
			}
		}
	} //ClotureEnMasse
/***************************************************
* Action de l'automate 
****************************************************/

	if ($bull->type == 'Loc') ClotureEnMasse();
//print '<p>DEBUG ecran :'.$ecran.'<---- action:'.$action.'<</p>';
// FACTURATION 
if ($ecran == 'facture' and $action =='facturer'){
	global $rapport;
	$rapport = array();
	global $PostActivité;
	$PostActivité = array();
//	if (isset($_POST['rowid']) && is_array($_POST['rowid']))	{
	if (isset($tbrowid) and is_array($tbrowid)) {
		$error = 0;
//print '<p>DEBUG ';
//		$tbrowid = array();
//		$tbrowid = GETPOST("rowid");
		
		foreach ( $tbrowid as $row)
		{
			if($row > 0)			{
				// recherche bulletin avec row
				$bullfact = new Bulletin ($db);
				$retbull = $bullfact->fetch_complet_filtre(100,$row);	
				if ($bullfact->type == 'Insc' and $bullfact->IsBullGroupe() == true) {			
					$flggoupe = true;	
					$bullfact->fetch_bull_group_fact();
				}			
				$ret = $w1->FactureBulletin($bullfact); // rempli $rapport  si erreur et PostActivité pour Post_Activité
				if ($flggoupe) {				
					$bullfact->fetch($bullfact->id);
				}
				if ($ret < 0) $error = $error + $ret;
			}
			$params .= '&rowid['.$row.']='.$row;
			
		} // boucle bulletin à facturer
		// traitement du rapport
		// gérer le doc
		$flHeader = true;


		if ( ( count($PostActivité) > 0 or  count($rapport) > 0)  and stripos( $_SERVER ['PHP_SELF'], 'dolibarrCAVV10') === false ) {
			$typeModele = 'RapportFacturation_odt:'.DOL_DATA_ROOT.'/doctemplates/bulletin/RapportFacturation.odt';
			$cgl_RapFact_create = new stdClass();
			 $ret = cgl_RapFact_create($db, $typeModele, $langs, $rapport, $PostActivité);
		
			if ($error < 0 )	{
				$text='Facturation demandee incorrectement aboutie - ';	
				$question="Voir le rapport de facturation s'il est necessaire de prevenir l'assistance";
				setEventMessage($text.$question, 'warnings');	
				$flHeader = false;			
			}
		}
	 }

	if ($flHeader) Header('Location: '  . $_SERVER ['PHP_SELF'] . "?".$params);
}

if ($action =='conf_archibull' and $confirm == 'yes') {
		$tbrowid = array();
		$tbrowid = GETPOST("rowid", 'array');
		
		$code_close = 1;
		$lib_close = 'Clos car acompte remboursé sur BU/LO non exécuté ';
		if (!empty($tbrowid)) 	{		
			$bullarch = new Bulletin ($db);
			foreach ( $tbrowid as $row)		{
				if($row > 0)			{		
					$retbull = $bullarch->fetch_complet_filtre(-1,$row);
					if (!empty($bullarch->fk_accompte) 
						and  $bullarch->TotalFac() == 0 
						and $bullarch->ExistPmtNeg() > 0
						and $bullarch->TotalPaimnt() == 0
					) 
						$TraitCommun->ArchiveAcompte($bullarch->fk_accompte, $code_close, $lib_close );
					$bullarch->regle_archive();
				}				
			}
			unset($bullarch);
		}
}

/* archivage des rapports */
if ($action =='archive') {	
		$text='Rapport de facturation courant '.$file;
		
		$url = $_SERVER['PHP_SELF'].'?file='.$file.'&ecran='.$ecran.'&type='.$type;
		$formconfirm=$form->formconfirm($url ,$langs->trans('Archivage'),$text,'conf_archiver','','',1);
		print $formconfirm;

	
}
if ($action =='conf_archiver' and $confirm == 'yes') {
	$rep =DOL_DATA_ROOT.'/cglinscription/';
//recherche année dans la date du fichier
	$reparchi = $rep.'archive/'.YEAR(dol_now('tzuser')).'/';
	if (!file_exists ($reparchi))  dol_mkdir($reparchi);
	if (!file_exists ($reparchi.'/RapportFacturation')) $ret = dol_mkdir($reparchi.'/RapportFacturation');
	
//créer répertoire si nécessaire
	$dest = 'archive/'.YEAR(dol_now('tzuser')).'/'.$file;
	$ret = dol_move($rep.$file, $rep.$dest);
}


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
	$morehtml = '<div class="inline-block '.(($buttonreconcile || $newcardbutton) ? 'marginrightonly' : '').'">';
	$morehtml .= '<label for="pageplusone">'.$langs->trans("Page")."</label> "; // ' Page ';
	$morehtml .= '<input type="text" name="pageplusone" id="pageplusone" class="flat right width25 pageplusone" value="'.($page + 1).'">';
	$morehtml .= '/'.$nbtotalofpages.' ';
	$morehtml .= '</div>';
*/
	//$morehtml .= '<!-- Add New button -->'.$newcardbutton;

}


/***************************************************
* Affichage
*
* Put here all code to build page
****************************************************/

// TITRES ECRAN
if ($ecran == 'facture' )  {
	if ($type == 'Loc') $title=$langs->trans("LgFactListeCnt"); 
	else  $title=$langs->trans("LgFactListeInsc"); 
}
elseif ($ecran == 'archive')  {	
	if ($type == 'Loc' ) $title=$langs->trans("ArchivagLoc");
	elseif ($type == 'Insc') $title=$langs->trans("ArchivageIns");
	elseif ($type == 'Resa') $title=$langs->trans("ArchivagResa"); 
}
elseif ($ecran == 'archivestock')
	$title=$langs->trans("ListeDesAnneesPasses");


if ('OrdreSql' == 'OrdreSql') {// construction SQL de recherche
	$sql ='';
	$sql .= "SELECT distinct b.rowid, b.ref, b.datec, b.ObsPriv, b.abandon, b.fk_facture, f.date_valid, b.typebull, b.typebull , ";
	if ($conf->global->MAIN_VERSION_LAST_UPGRADE <>'8.0.4')
			$sql .= " f.ref as facnumber, ";
	else
			$sql .= " f.facnumber, ";	

	$sql .= " b.dt_facture, b.tms , ";
	if ($ecran == 'archivestock') $sql .= " f.date_valid as DateAff, ";
	elseif ($type == 'Insc') $sql .= " ASCl.heured as DateAff,  ";
	elseif ($type == 'Loc')  $sql .= " b.dateretrait as DateAff, b.dateretrait, ";
	$sql .= " (select sum(pt) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 ";
	$sql .= "            where bd1.action not in ('S','X') and bd1.type = 1 and bd1.fk_bull = b.rowid) as montantpaye, ";
	$sql .= " (select sum(pt) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 ";
	$sql .= "            where bd1.action not in ('S','X') and bd1.type = 1 and bd1.pt < 0 and bd1.fk_bull = b.rowid) as montantneg, ";
	$sql .= " (select sum(pt) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 ";
	$sql .= "            where bd1.action not in ('S','X')  and bd1.type = 2 and bd1.fk_bull = b.rowid) as montantremise ,  ";
	$sql .= " (select sum(pu*qte*(100-rem)/100) from ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 ";
	$sql .= "            where bd1.action not in ('S','X')  and bd1.type = 0 and bd1.fk_bull = b.rowid) as montantdu, regle, b.statut, s.nom , s.rowid as id_client ";
	$sql .= ", (select GROUP_CONCAT(DISTINCT CONCAT(rem.libelle, CONCAT(' - ',bd1.reslibelle))  ORDER BY rem.libelle SEPARATOR ' / ') ";
	$sql .= " FROM  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 ";	
	$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."cgl_c_raison_remise as rem  on bd1.fk_raisrem = rem.rowid   ";
	$sql .= " WHERE bd1.fk_bull = b.rowid and bd1.type = 2 and bd1.action not in ('S','X')) as InfoRem ";
	if ($type == 'Insc') {
		$sql .= ", case when isnull(form.fk_user)  or form.fk_user = 0  then sp.lastname else su.firstname end as PrenomMon,  ";
		$sql .= " case when isnull(form.fk_user)  or form.fk_user = 0  then sp.firstname  else su.firstname end as NomMon ,  ";
		$sql .= " form.rowid as IdMon ";
	}
	$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f  on b.fk_facture =  f.rowid ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s  on b.fk_soc =  s.rowid ";
	$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd   on bd.fk_bull = b.rowid  and bd.action not in ('S', 'X')  and bd.type = 0 ";
	if ($type == 'Insc') {
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as AgS  on bd.fk_activite =  AgS.rowid ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_calendrier as ASCl on AgS.rowid = ASCl.fk_agefodd_session ";
		$sql.= " LEFT JOIN  llx_agefodd_session_formateur as sf ON bd.fk_activite =  sf.fk_session ";
	$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."agefodd_formateur as form  ON sf.fk_agefodd_formateur = form.rowid ";
	$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."socpeople as sp ON form.fk_socpeople = sp.rowid  ";
	$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."user as su ON form.fk_user = su.rowid    ";

	}
	 $sql.= " WHERE ".$sqlwhere;
	if ($search_fk_raisrem and $search_fk_raisrem > -1)
	{
		//$sql.= " AND EXISTS (SELECT (1) FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 WHERE type = 2 and bd1.fk_bull = b.rowid and bd1.fk_raisrem = ".$search_fk_raisrem." )";	 


		$sql.= " AND EXISTS (SELECT (1) FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1   LEFT JOIN  ".MAIN_DB_PREFIX."cgl_c_raison_remise as cr on cr.rowid = bd1.fk_raisrem
		where  b.rowid = bd1.fk_bull and 
						bd1.action not in ('S','X') and bd1.type in (0,2) 
						  and 
						 ( 
							(cr.fl_type = 2 and bd1.type = 	0)
						 or
							(cr.fl_type = 1 and bd1.type = 2)
						 )
						 and  bd1.fk_raisrem = ".$search_fk_raisrem."
				)";
	}
	if ($search_reslibelle)
	{
		$sql.= " AND EXISTS (SELECT (1) FROM ".MAIN_DB_PREFIX."cglinscription_bull_det as bd1 WHERE type = 2 and bd1.fk_bull = b.rowid and bd1.reslibelle LIKE '%".$search_reslibelle."%' )";	 
	}
	dol_syslog('Facturation CAV');	
	// Compte le nb total d'enregistrements
	$nbtotalofrecords = 0;
	$sql.= $sqlorder;
// REPRENDRE ARCHIVAGE LOC ET INSC

/* requete facturation Loc
SELECT distinct b.rowid, b.ref, b.datec, b.ObsPriv, b.abandon, b.fk_facture, f.date_valid, b.typebull, f.ref as facnumber, b.dt_facture, b.tms , b.dateretrait as DateAff, b.dateretrait, 
(select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 1 and bd1.fk_bull = b.rowid) as montantpaye, 
(select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 1 and bd1.pt < 0 and bd1.fk_bull = b.rowid) as montantneg, 
(select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 2 and bd1.fk_bull = b.rowid) as montantremise ,
 (select sum(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 0 and bd1.fk_bull = b.rowid) as montantdu, 
 regle, b.statut, s.nom , s.rowid as id_client , 
 (select GROUP_CONCAT(DISTINCT CONCAT(rem.libelle, CONCAT(' - ',bd1.reslibelle)) ORDER BY rem.libelle SEPARATOR ' / ') FROM llx_cglinscription_bull_det as bd1 LEFT JOIN llx_cgl_c_raison_remise as rem on bd1.fk_raisrem = rem.rowid WHERE bd1.fk_bull = b.rowid and bd1.type = 2 and bd1.action not in ('S','X')) as InfoRem 

 FROM llx_cglinscription_bull as b LEFT JOIN llx_facture as f on b.fk_facture = f.rowid LEFT JOIN llx_societe as s on b.fk_soc = s.rowid
 LEFT JOIN llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S', 'X') and bd.type = 0

WHERE    !isnull(b.typebull)
 and b.typebull = 'Loc'  
 and b.statut != 0 
 and b.regle < 6   
 
AND (
  	(
		b.statut = 4  
		and   	!(
				(select count(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 0 and bd1.fk_bull = b.rowid) = 0
			and 
				!(select count(rowid)  from llx_cglinscription_bull_det as bd1 
						where bd1.action not in ("S","X") and bd1.type = 1 and !isnull(bd1.pt) and bd1.pt < 0 and bd1.fk_bull = b.rowid) > 0  		
			and 
				(select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ("S","X") and bd1.type = 1  and bd1.fk_bull = b.rowid) = 0 
		) 
		
	) 	
	or   	(
		b.statut = 9.5 and  substr( b.ref, 3,4) = YEAR('2022-06-17 14:25:29') 
		and (isnull(b.fk_facture)  or b.fk_facture = 0) 
		and (select sum(pt) 	from llx_cglinscription_bull_det as bd1 where fk_bull = b.rowid and  bd1.action not in ('S','X') and bd1.type = 1) <> 0 
	)
) */
 /* requete facturation Insc
 SELECT distinct b.rowid, b.ref, b.datec, b.ObsPriv, b.abandon, b.fk_facture, f.date_valid, b.typebull, f.ref as facnumber, b.dt_facture, b.tms , ASCl.heured as DateAff, 
 (select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 1 and bd1.fk_bull = b.rowid) as montantpaye,
 (select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 1 and bd1.pt < 0 and bd1.fk_bull = b.rowid) as montantneg,
 (select sum(pt) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 2 and bd1.fk_bull = b.rowid) as montantremise ,
 (select sum(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 0 and bd1.fk_bull = b.rowid) as montantdu, 
 regle, b.statut, s.nom , s.rowid as id_client ,
 (select GROUP_CONCAT(DISTINCT CONCAT(rem.libelle, CONCAT(' - ',bd1.reslibelle)) ORDER BY rem.libelle SEPARATOR ' / ') FROM llx_cglinscription_bull_det as bd1 LEFT JOIN llx_cgl_c_raison_remise as rem on bd1.fk_raisrem = rem.rowid WHERE bd1.fk_bull = b.rowid and bd1.type = 2 and bd1.action not in ('S','X')) as InfoRem , case when isnull(form.fk_user)  or form.fk_user = 0  then sp.lastname else su.firstname end as PrenomMon, case when isnull(form.fk_user)  or form.fk_user = 0  then sp.firstname else su.firstname end as NomMon , form.rowid as IdMon 
 
 FROM llx_cglinscription_bull as b LEFT JOIN llx_facture as f on b.fk_facture = f.rowid LEFT JOIN llx_societe as s on b.fk_soc = s.rowid 
 LEFT JOIN llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S', 'X') and bd.type = 0 LEFT JOIN llx_agefodd_session as AgS on bd.fk_activite = AgS.rowid 
 LEFT JOIN llx_agefodd_session_calendrier as ASCl on AgS.rowid = ASCl.fk_agefodd_session LEFT JOIN llx_agefodd_session_formateur as sf ON bd.fk_activite = sf.fk_session
 LEFT JOIN llx_agefodd_formateur as form ON sf.fk_agefodd_formateur = form.rowid LEFT JOIN llx_socpeople as sp ON form.fk_socpeople = sp.rowid 
 LEFT JOIN llx_user as su ON form.fk_user = su.rowid

  WHERE 
 !isnull(b.typebull) and b.typebull = 'Insc' and b.statut != 0  and 
	! (
		(select count(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 0 and bd1.fk_bull = b.rowid) = 0
		and (select count(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 0 and !isnull(bd1.pt) and bd1.pt < 0 and bd1.fk_bull = b.rowid) > 0 
		and (select count(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 0  and bd1.fk_bull = b.rowid) = 0 
	)
 AND (
		(b.regle < 6 AND ASCl.heured < '2022-03-07 14:39:57' ) 
	 or (b.statut = 9.5 and substr( b.ref, 3,4) = YEAR('2022-03-07 14:39:57')) 
	 or 
	 (
		 substr( b.ref, 3,4) = YEAR('2022-03-07 14:39:57') 
		 AND
		 (
			 (select count(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 0 and bd1.fk_bull = b.rowid) = 0
			 or (
				(isnull(ASCl.heured ) or ASCl.heured < now()) 
				and
				(select count(pu*qte*(100-rem)/100) from llx_cglinscription_bull_det as bd1 where bd1.action not in ('S','X') and bd1.type = 0 and !isnull(bd1.pt) and bd1.pt < 0 and bd1.fk_bull = b.rowid) > 0 
			)
		 )
	 ) 
 ) 
 */
 /* requete aarchivage loc
 
 */
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$result = $db->query($sql);

		dol_syslog('facturation::'.$sql,LOG_DEBUG);
		if ($result	)   
		{
			$nbtotalofrecords = $db->num_rows($result);
			$num = $nbtotalofrecords;
		}
	}
//CCA Double	
	// Si la liste est unique, envoyer directement la fiche_moniteur
	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && !empty($sall) ) {
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		if ($type == 'Insc') {
				header("Location: ".DOL_URL_ROOT.'/custom/cglinscription/inscription.php?id_bull='.$id);
				exit;
		}
		if ($type == 'Loc') {
				header("Location: ".DOL_URL_ROOT.'/custom/cglinscription/location.php?id_contrat='.$id);
				exit;
		}
	}


	llxHeader('',$langs->trans('LcglinscriptionFct'));
	$help_url='FR:Module_Inscription';


	if ($ecran == 'archive' and $action =='facturer'){
		$TraitCommun->Archive('Archive');	
	}
//CCA Double
	if ($num > $limit) {
		$sql.= $db->plimit($limit+1, $offset);
		$resql1 = $db->query($sql);
		if ($resql1	)   {
			$num = $db->num_rows($resql1);
		}
	}
	else $resql1 = $result;
}



	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="ecran" value="'.$ecran.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="id_menu" value="160">';
	print '<input type="hidden" name="token" value="'.newtoken().'">';
	print '<input type="hidden" name="limit" value="'.$limit.'">';
	if (!is_null($acct->id)) print '<input type="hidden" name="account" value="'.$acct->id.'">';
	//print '<input type="hidden" name="rowid" value="'.$rowid.'">';

if ($ecran == 'archive') { 
	if ($type == 'Loc') print '<p><font color="red">'.$langs->trans("AlertEcranLoc").'</font></p><p>&nbsp;</p>';
	elseif ($type == 'Resa') print '<p></p>';
	else print '<p><font color="red">'.$langs->trans("AlertEcranInsc").'</font></p><p>&nbsp;</p>';	
}
	// permet d'afficher le petit livre, le titre la succession des page et le num? de la  page courante
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder, '', $num,$nbtotalofrecords,'','', $morehtml, '',$limit, 0, 0, 0);

if ('BarreSelection'=='BarreSelection') {
// début barre de selection
	print '<table class="liste" width="100%">';
	print '<tr  class="liste_titre" >';		
	print '<td class="liste_titre">';
	print_liste_field_titre("LbTypeRemise",$_SERVER["PHP_SELF"],"","","",'',"","");
	print '<td class="liste_titre">';
	$wfc1 = new FormCglCommun ($db);
	print $wfc1->select_nomremise($search_fk_raisrem,'search_fk_raisrem','', '',0,1);
	unset($wfc1 );
	print_liste_field_titre("LbLibelRemise",$_SERVER["PHP_SELF"],"","","",'',"","");
	print '<td class="liste_titre">';
	print '<input type="flat" name="search_reslibelle" value="'.$search_reslibelle.'">';
	print '<td class="liste_titre">';
	print "</td/tr>\n";
	
	print '<tr class="liste_titre">';

	if ($ecran == 'archive' or $ecran == 'facture') {
		if ($type == 'Loc')
			print_liste_field_titre("Contrat",'','',"",'','','','');
		else 
			print_liste_field_titre("Bulletin",'','',"",'','','','');
		}
	else
		print_liste_field_titre("Tous",'','',"",'','','','');
	if ($type == 'Insc')
		print_liste_field_titre("Moniteur",'','',"",'','','','');
	if ($type <> 'Insc') print_liste_field_titre("",'','',"",'','','','');
	print_liste_field_titre("Client",'','',"",'','','','');
	print '	<td class="liste_titre" colspan=3>';

// affichage barre de sélection

    // affiche la barre grise de titres des filtres
	print '<tr class="liste_titre">';

	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '	<td class="liste_titre">';
	//if ($ecran == 'archivestock') $type = 'Insc';
 	print '<input  class="flat"  value = "'.$search_ref.'" type="text" name="search_ref" id="search_ref" >';
//	 print select_refcnt($search_ref,'search_ref',1,0, $type, $ecran);	
	print '</td><td>';
	if ($type == 'Insc') print select_moniteur($search_moniteur,'search_moniteur',1, 0, $type, $ecran);
 	//print '<input  class="flat"  value = "'.$search_moniteur.'" type="text" name="search_moniteur" id="search_moniteur" >';						
	print '</td><td>';
//	print select_tiers($search_tiers,'search_tiers',1, 0, $type, $ecran);
 	print '<input  class="flat"  value = "'.$search_tiers.'" type="text" name="search_tiers" id="search_tiers" >';
	print_liste_field_titre("",'','',"",'','','','');
 
	// boutons de validation et suppression du filtre
	print '<td><td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print "</tr>\n";
	print "</table>\n";
}
	print '<p>&nbsp;</p>';
	
	
    print "</form>\n";	
	
	
	// Prépare le js pour mettre toutes les checkbox d'un bulletin  à Actif ou / Non Actif
		print '
        <script language="javascript" type="text/javascript">
        jQuery(document).ready(function()
        {
            jQuery("#checkall_'.$bid.'").click(function()
            {
                jQuery(".checkselection_'.$bid.'").prop(\'checked\', true);
            });
            jQuery("#checknone_'.$bid.'").click(function()
            {
                jQuery(".checkselection_'.$bid.'").prop(\'checked\', false);
            });
        });
        </script>
        ';
	
	
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="ecran" value="'.$ecran.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="id_menu" value="160">';
	print '<input type="hidden" name="action" value="facturer">';
	print '<input type="hidden" name="search_tiers" value="'.$search_tiers.'">';
	print '<input type="hidden" name="search_moniteur" value="'.$search_moniteur.'">';	
	print '<input type="hidden" name="search_ref" value="'.$search_ref.'">';
	print '<input type="hidden" name="token" value="'.newtoken().'">';
	$var=True;
	$i=0;
	print "<p>    </p>\n";
	print '<table class="liste" width="100%">';

	// affiche la barre grise des champs affich?
	print '<tr class="liste_titre">';
	if ($ecran == 'facture') print_liste_field_titre("ColFacture","","","",'','','','');
	else print_liste_field_titre("ColArchive","","","",'','','','');

	if ($ecran =='archive'  or $ecran == 'archivestock') {
		$lbtemp = $langs->trans("DateFacturation");
	}
	else {
		$lbtemp = $langs->trans("Date");
	}
	
	print_liste_field_titre( $lbtemp,$url,"DateAff","",$params,'',$sortfield,$sortorder);

	if ($ecran == 'archive' or $ecran == 'facture') {
		if ($type == 'Loc')
			print_liste_field_titre("Contrat",$url,"b.ref","",$params,'',$sortfield,$sortorder);
		else
			print_liste_field_titre("Bulletin",$url,"b.ref","",$params,'',$sortfield,$sortorder);
	}
	else
			print_liste_field_titre("Tous",$url,"b.ref","",$params,'',$sortfield,$sortorder);
	if ($type == 'Insc')
		print_liste_field_titre("Moniteur",'',"","",'','','','');
	print_liste_field_titre("Client",$url,"s.nom","",$params,'',$sortfield,$sortorder);
	if ($conf->global->MAIN_VERSION_LAST_UPGRADE <>'8.0.4')
		print_liste_field_titre("Facture",$url,"f.ref","",$params,'',$sortfield,$sortorder);
	else
		print_liste_field_titre("Facture",$url,"f.facnumber","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("FacDu",$url,"montantpaye","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre('','',"","",'','','','');
	print_liste_field_titre("FacPaye",$url,"montantdu","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("Statut",$url,"b.statut","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre("FacRegle",$url,"regle","",$params,'',$sortfield,$sortorder);
	if ($ecran == 'archivestock' or $ecran == 'archive') print_liste_field_titre("Abandon",$url,"abandon","",$params,'',$sortfield,$sortorder);
	print "</td></tr>\n";
	/* Liste des bulletins/Contrats */
	// Bouton Selectionner - deselectionner les boites checkbox
		print '<tr><td align=right><a href="#AncreLstDetail" id="checkall_'.$bid.'">';
		print $langs->trans("All").'</a> / <a href="#AncreLstDetail" id="checknone_'.$bid.'">'.$langs->trans("None").'</a></td></tr>';

/*		print '<script>[type="checkselection_"]:not(:checked),
		[type="checkbox"]:checked
		{ 
		border-color: #bbb;
		}</scrript>'
		;
*/
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql1);
		if (isset ($tbrowid) and !empty($tbrowid)) 
			foreach ($tbrowid as $row) { 
				if ($row == $obj->rowid) $flgcheked = true; else $flgcheked = false; 
			}
		// recherche si ce bulletin a été modifé depuis la dernière facturation
		if ($obj->regle < $bull->BULL_ARCHIVE  and $ecran == 'facture' and !empty($obj->dt_facture) and dol_stringtotime($obj->dt_facture) < dol_stringtotime($obj->tms)) print '<tr BGCOLOR=Salmon >';
		else 		print "<tr $bc[$var]>";

		print '<td align="center" class="nowrap">';
			
	
		if (($ecran == 'facture' and $obj->regle <= $bull->BULL_FACTURE and $obj->statut > $bull->BULL_ENCOURS) 
					or ($ecran <> 'facture' and( $obj->regle <= $bull->BULL_FACTURE and ($obj->statut < $bull->BULL_ABANDON or $obj->statut == $bull->BULL_ANNULCLIENT) 	and (($obj->typebull == 'Loc' and $obj->statut >= $bull->BULL_CLOS) OR ($obj->typebull == 'Insc' and $obj->statut >= $bull->BULL_INS)))) 
					or ($ecran == 'facture' and $obj->statut == $bull->BULL_ANNULCLIENT and $obj->regle < $bull->BULL_FACTURE ))
		{
			if (($ecran == 'facture' and 
						((!($obj->statut == $bull->BULL_ANNULCLIENT and   $obj->montantpaye == 0 ))
						or (
						!($obj->statut <> $bull->BULL_ANNULCLIENT and $obj->montantdu - $obj->montantremise == 0))
						))
				or 
				($ecran <> 'facture' and !($obj->statut == $bull->BULL_ANNULCLIENT and  $obj->montantpaye <> 0 ))) 
					print '<input class="flat checkselection_" title = "'.$titre.'"name="rowid['.$obj->rowid.']" type="checkbox" value="'.$obj->rowid.'" size="1"'.($flgcheked?' checked="checked"':'').'>';
			if ($obj->statut == $bull->BULL_ANNULCLIENT) 
					print '<img border="0" title="'.$bull->LIB_ANNULCLIENT.'" alt="'.$texte.'" src="../../theme/eldy/img/'.$bull->IMG_ANNULCLIENT.'">';
		}		
		print "</td>";
		
		// Couleur et non d'une checksum
		//if ($obj->statut == $bull->BULL_ANNULCLIENT) print 'Inof';
		// Date
		$strtemp=$w->transfDateFr($obj->DateAff);
		print "<td>".$strtemp."</td>\n";
		
		// BU/LO/RE
		if ($obj->typebull == 'Loc') $option = 'MAJLoc';
		elseif ($obj->typebull == 'Insc') $option = 'MAJInscritp';
		elseif ($obj->typebull == 'Resa') $option = 'MAJResa';
		print "<td>".getNomUrl("object_company.png", $option,0,$obj->rowid)."&nbsp";		
		print $obj->ref;
		if ( !empty($obj->ObsPriv )) {
			$text = $langs->trans("DefObsPriv").':'.$obj->ObsPriv;
			print info_admin($text,1);
		}
		print "</td>";		
		
		// Moniteur
		if ($type == 'Insc')
			// donner le/les moniteurs et un lien vers leurs fiches sur le nom
		if ($obj->typebull == 'Insc')  {
			if (empty($obj->PrenomMon) and empty ($obj->NomMon)) {
				print '<td style="color:red"><b>';
				print $langs->trans("SsMoniteur");
				print "</b></td>";
			}
			else {
				print "<td>";
				print getNomUrl("object_company.png",'Moniteur',0, $obj->IdMon);
				print $obj->PrenomMon.' '.$obj->NomMon."</td>";
			}
		}
		else print "<td></td>";
		// Client
		print "<td>".getNomUrl("object_company.png",'Tiers',0, $obj->id_client)."&nbsp".$obj->nom."</td>";
		print "</td>";
		print "<td>";		
		// Facture
		if (!empty($obj->facnumber)) 	print	getNomUrl("object_company.png", 'MAJFacture',0,$obj->fk_facture)."&nbsp";
		print $obj->facnumber;
		print "</td>";
	
		print "<td>".number_format ( $obj->montantdu - $obj->montantremise, 2 , ',' , ' ' );
		//print "<td>".number_format ( $obj->montantdu, 2 , ',' , ' ' );
		if (!empty($obj->InfoRem)) { $texte = $langs->trans("Remise").': '.$obj->InfoRem;   print info_admin($texte,1); }
		print "</td>";	
		// Montant réglé
		$nb=NbPmtNeg($obj->rowid);
		print "<td>";
		if ($nb >0) {
			if ($nb > 1) $text = $langs->trans("DefPmtNegs");
			else $text = $langs->trans("DefPmtNeg");
			print info_admin($text,1);
		}		if (!empty($obj->textremisesfixes)) info_admin($obj->textremisesfixes,1);		
		print "</td>";
		// Paiement
		print "<td>".number_format ( $obj->montantpaye , 2 , ',' , ' ' )."</td>";
		//print "<td>".$obj->NbFam."</td>";
		// Statut
/*		$texte='';
		if ($type == 'Loc'){
			if ($obj->statut == $bull->BULL_ENCOURS) { 	 $img=$bull->IMG_ENCOURS; $texte=$bull->LIB_CNT_ENCOURS;}
			elseif ($obj->regle < $bull->BULL_FACTURE and $obj->statut == $bull->BULL_CLOS and !empty($obj->fk_facture)) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACT_INC;}
			elseif ($obj->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_CNT_FACT_INC; $texte=$bull->LIB_CNT_FACT_INC;}
			elseif ($obj->statut == $bull->BULL_VAL ) { $img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
			elseif ($obj->statut == $bull->BULL_DEPART ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_DEPART;}
			elseif ($obj->statut == $bull->BULL_RETOUR ) { $img=$bull->IMG_RETOUR; $texte=$bull->LIB_RETOUR;}
			elseif ($obj->statut == $bull->BULL_CLOS ) { $img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
			elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
			else { $img = ''; $texte = 'inconnu '. $obj->statut;}
		}
		else {
			if ($obj->statut == $bull->BULL_ENCOURS ) { 	 $img=$bull->IMG_ENCOURS; $texte=$bull->LIB_ENCOURS;}
			elseif ($obj->regle ==0 and $obj->statut ==1 and !empty($obj->fk_facture)) {$img=$bull->IMG_FACT_INC; $texte=$bull->LIB_FACT_INC;}
			elseif ($obj->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_FACTURE; $texte=$bull->LIB_FACT_INC;}
			elseif  ($obj->statut == $bull->BULL_INS)  {$img=$bull->IMG_INS; $texte=$bull->LIB_INS;}
			elseif ($obj->statut == $bull->BULL_PRE_INS) {$img=$bull->IMG_PRE_INS; $texte=$bull->LIB_PRE_INS;}
			elseif ($obj->statut == $bull->BULL_CLOS ) { $img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
			elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
			else { $img = ''; $texte = 'inconnu '. $obj->statut;}
		}
		print '<td>';
		if (!empty($texte))  print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="../../theme/eldy/img/'.$img.'">';	
		print '</td>';	
*/
		print '<td>';
		$wfrmcm = new FormCglCommun ($db);
		print $wfrmcm->AffichImgStatutBull($obj->statut, $obj->regle, $type, $obj->fk_facture);
		unset ($wfrmcm);
		print '</td>';	


		$img = '';
		if ($obj->regle == $bull->BULL_NON_PAYE  and $obj->montantdu > 0) { $img=$bull->IMG_NON_PAYE; $texte=$bull->LIB_NON_PAYE;}
		elseif ($obj->regle == $bull->BULL_NON_PAYE  and $obj->montantdu <= 0) {$img=''; $texte='';}
		elseif ($obj->regle ==$bull->BULL_INCOMPLET) {$img=$bull->IMG_INCOMPLET; $texte=$bull->LIB_INCOMPLET;}
		elseif ($obj->regle ==$bull->BULL_PAYE) {$img=$bull->IMG_PAYE; $texte=$bull->LIB_PAYE;}
		elseif ($obj->regle ==$bull->BULL_SURPLUS) {$img=$bull->IMG_SURPLUS; $texte=$bull->LIB_SURPLUS;}
		elseif ($obj->regle ==$bull->BULL_REMB) {$img=$bull->IMG_REMB; $texte=$bull->LIB_REMB;}
		else { $img = ''; $texte = 'inconnu '. $obj->regle;}
		if ($type == 'Loc') { 
			if ($obj->regle ==$bull->BULL_FACTURE) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACTURE;}
			elseif ($obj->regle ==$bull->BULL_ARCHIVE) {$img=''; $texte='';}
		}
		else{
			if ($obj->regle ==$bull->BULL_FACTURE) {$img=''; $texte='';}
			elseif ($obj->regle ==$bull->BULL_ARCHIVE) {$img=$bull->IMG_ARCHIVE; $texte=$bull->LIB_ARCHIVE;}
		}
		print '<td>';
		if (!empty($texte))  print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="../../theme/eldy/img/'.$img.'">';
		if ($ecran == 'archivestock' or $ecran == 'archive') {
			print '</td><td>';
			if ( $obj->statut == $bull->BULL_ABANDON  or ($obj->statut == $bull->BULL_CLOS and !empty($obj->abandon))) {
				$text = $langs->trans("DefAbandon").':'.$obj->abandon;
				print info_admin($text,1);
			}	
			print '</td>';
		}
		print "</tr>\n";
		$var=!$var;
		$i++;
	}// While
	
	print "</table><br>\n";




    if ($ecran == 'facture') print '<div align="right"><input class="button" type="submit" value="'.$langs->trans("AFacturer".$type).'"></div><br>';
    else print '<div align="right"><input class="button" type="submit" value="'.$langs->trans("AArchivBull").'"></div><br>';
				
    print "</form>\n";

	if ($ecran == 'facture')
	{
		 // affichage de la liste des fichiers rapport de facturation d? edit? le plus r?nt en haut.
		print '<table width="100%"><tr><td width="50%">';

		if (! is_dir($conf->cglinscription->RapportFacturation)) dol_mkdir($conf->cglinscription->RapportFacturation);

		$formfile = new FormFile($db);
		// Affiche liste des documents
		$fil_dir = DOL_DATA_ROOT.'/cglinscription'.'/'.'RapportFacturation';
		print 	showdocuments($type, 'cglInscription',$fil_dir,$_SERVER["PHP_SELF"].'?type='.$type.'&',1,'',0,'','Liste des rapports de facturation précédents');

		print '</td><td width="50%">&nbsp;</td></tr>';
		print '</table>';
	}



llxFooter();
$db->close();


    /**
     *      Return a string to show the box with list of available documents for object.
     *      This also set the property $this->numoffiles
     *
     *      @param      string				$modulepart         propal, facture, facture_fourn, ...
     *      @param      string				$filedir            Directory to scan
     *      @param      string				$urlsource          Url of origin page (for return)
     *      @param      int					$delallowed         Remove is allowed (1/0)
	* 		@param		int					$maxfilenamelength	Max length for filename shown
     * 		@param		string				$noform				Do not output html form tags
     * 		@param		string				$title				Title to show on top of form
     *      @param      boolean             $printer            Printer Icon
     * 		@return		string              					Output string with HTML array of documents (might be empty string)
     */
    function showdocuments($type, $modulepart,$filedir,$urlsource,$delallowed=0,$maxfilenamelength=28,$noform=0,$param='',$title='')
    {
        include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		global  $ecran, $ecran;
        global $langs,$conf,$hookmanager;
        global $bc;

        $forname='builddoc';
        $out='';
        $var=true;

        $headershown=0;
        $showempty=0;
        $i=0;

        $titletoshow=$langs->trans("Documents");
        if (! empty($title)) $titletoshow=$title;

        $out.= "\n".'<!-- Start show_document -->'."\n";


        // Get list of files
        if (! empty($filedir))
        {
		    if (empty($noform)) $out.= '<form action="'.$urlsource.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc').'" name="'.$forname.'" id="'.$forname.'_form" method="POST">';
            $out.= '<input type="hidden" name="action" value="builddoc">';
            $out.= '<input type="hidden" name="token" value="'.newtoken().'">';
			$out.= '<input type="hidden" name="acran" value="'.$ecran.'">';
       
            $out.= '<div class="titre">'.$titletoshow.'</div>';
            $out.= '<table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">';

            $out.= '<tr class="liste_titre">';

			
           // $file_list=dol_dir_list($filedir,'files',1,'','\.meta$','date',SORT_DESC);
            $file_list=dol_dir_list($filedir,'files',1,'','','date',SORT_DESC);
            // Loop on each file found
			if (is_array($file_list))
			{
				foreach($file_list as $file)
				{
					if (substr($file["name"],0,strlen($type)) == $type ){
						$var=!$var;

						// Define relative path for download link (depends on module)
						$relativepath=$file["name"];								// Cas general
						$relativepath='RapportFacturation/'.$relativepath;							
						$out.= "<tr ".$bc[$var].">";

						// Show file name with link to download
						$out.= '<td class="nowrap">';
										// Cas general
						$out.= '<a data-ajax="false" href="'.DOL_MAIN_URL_ROOT . '/document.php?modulepart=cglinscription&file='.urlencode($relativepath).'"';
						$mime=dol_mimetype($relativepath,'',0);
						if (preg_match('/text/',$mime)) $out.= ' target="_blank"';
						$out.= ' target="_blank">';
						$out.= img_mime($file["name"],$langs->trans("File").': '.$file["name"]).' '.dol_trunc($file["name"],$maxfilenamelength);
						$out.= '</a>'."\n";
						$out.= '</td>';

						// Show file size
						$size=(! empty($file['size'])?$file['size']:dol_filesize($filedir."/".$file["name"]));
						$out.= '<td align="right" class="nowrap">'.dol_print_size($size).'</td>';

						// Show file date
						$date=(! empty($file['date'])?$file['date']:dol_filemtime($filedir."/".$file["name"]));
						$out.= '<td align="right" class="nowrap">'.dol_print_date($date, 'dayhour', 'tzuser').'</td>';

						if ($delallowed)
						{
							$out.= '<td align="right">';
							$out.= '<a href="'.$urlsource.(strpos($urlsource,'?')?'&':'?').'action=archive&type='.$type.'&ecran='.$ecran.'&file='.urlencode($relativepath);
							$out.= ($param?'&'.$param:'');
							//$out.= '&modulepart='.$modulepart; // TODO obsolete ?
							//$out.= '&urlsource='.urlencode($urlsource); // TODO obsolete ?
							$out.= '">'.img_object($langs->trans("AArchiveRap"), 'service').'</a></td>';
						}
						$out.= '</tr>';
					}
				}
                $numoffiles++;
            }
        }
        if ($headershown)
        {
            // Affiche pied du tableau
            $out.= "</table>\n";
            if ($genallowed)
            {
                if (empty($noform)) $out.= '</form>'."\n";
            }
        }
        $out.= '<!-- End show_document -->'."\n";
        //return ($i?$i:$headershown);
        return $out;
    }// showdocuments

?>