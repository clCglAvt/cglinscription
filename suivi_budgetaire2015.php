<?php
/* lancement http://localhost/dolibarr/custom/cglinscription/bilan2015.php
*/
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 
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
 *		\brief      Liste les réservations et modifications de leurt états
 */

 
 
 
 
 
 
 
// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';

require_once '../../core/lib/functions.lib.php';
require_once '../../core/class/html.form.class.php';
require_once '../../core/lib/date.lib.php';
// Change this following line to use the correct relative path from htdocs
//require_once ('./class/cglinscription.class.php');
//dol_include_once ('/class/cgllocation.class.php');
require_once ('/class/cgllocation.class.php');

// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");


$annee=2015;
$anneecourte=15;
$src_file = 'c:/tmp/'.$annee.'-bilanCAV.txt';

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
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifInsc").'"></img>';
		}	
		if ($option == 'MAJLoc')		{
			$result = '<a href="./location.php?id_contrat='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifLoc").'"></img>';
		}				
		elseif ($option == 'Tiers'){
			 $result = '<a href="/dolibarr/comm/card.php?socid='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="/dolibarr/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifTiers").'"></img>';
		}	
			   $result.=$lienfin;
			   return $result;
	}//getNomUrl
	
	

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','Lcglinscription');

$form=new Form($db);
$w = new Cgllocation($db) ;


// Put here content of your page
$help_url='FR:Bilan '.$annee;

// construction SQL de calcul

if (1 == 'Requete SQL ') {
/*
	select select pcg_type , c.label, account_number, sum( fd.total_ht ) as dette, '' as creance,'' as total,''as TVA ,'' as benefice 
	from llx_accountingaccount as c,  llx_facture_fourn_det AS fd, llx_facture_fourn AS f
	where  fk_facture_fourn = f.rowid and c.rowid = fk_code_ventilation
		AND f.ref LIKE 'SI15%'
	group by c.label, account_number, pcg_type
	-- pour le reste prendre HT
	union
	select pcg_type , c.label, account_number, '', 	sum(fd.total_ht) as creance,'','',''
	from llx_accountingaccount as c,  llx_facturedet AS fd, llx_facture AS f
	where  fk_facture = f.rowid and c.rowid = fk_code_ventilation
	AND f.facnumber LIKE 'FA15%'
	group by c.label, account_number, pcg_type
	union
	-- calcul somme par sous-type
	select pcg_type , 'total', '__'	, '','',case when total = 0 then '' else total end as total,		
			 case when pcg_type = 'A - 4 SAISONS'  or pcg_type = 'C - SEJOUR'  or pcg_type = 'D - ACTIVITE ACHETEE REVENDUE' then total * 0.2 else '' end as TVA,	
	 case when pcg_type = 'A - 4 SAISONS' or pcg_type = 'C - SEJOUR'  or pcg_type = 'D - ACTIVITE ACHETEE REVENDUE'  then total * 0.8 else total  end as Benefice
	from (select  case when isnull(creance) then 0 else creance end - case when isnull(dette) then 0 else dette end as total, pcg_type
		from (	select 
				sum((select  sum(fd.total_ht)
				from   llx_facturedet AS fd, llx_facture AS f
				where  fk_facture = f.rowid and fk_code_ventilation = c.rowid 
				AND f.facnumber LIKE 'FA15%'))  as creance,	
				sum((select   sum( fd.total_ht )
				from   llx_facture_fourn_det AS fd, llx_facture_fourn AS f
				where  fk_facture_fourn = f.rowid and fk_code_ventilation = C.rowid 
				AND f.ref LIKE 'SI15%')) as dette
		,  pcg_type 
		from  llx_accountingaccount as c
		where active = 1
		group by  pcg_type ) as tb1
		) as tb2
	union
	select 'Z_BENEFICE' ,'TOTAL', '__', '', '','','',sum(case when pcg_type = 'A - 4 SAISONS' or pcg_type = 'C - SEJOUR'  or pcg_type = 'D - ACTIVITE ACHETEE REVENDUE'  then total * 0.8 else total  end ) as Benefice
	from (select  case when isnull(creance) then 0 else creance end - case when isnull(dette) then 0 else dette end as total, pcg_type
		from (	select 
				sum((select  sum(fd.total_ht)
				from   llx_facturedet AS fd, llx_facture AS f
				where  fk_facture = f.rowid and fk_code_ventilation = c.rowid  
				AND f.facnumber LIKE 'FA15%'))  as creance,	
				sum((select   sum( fd.total_ht )
				from   llx_facture_fourn_det AS fd, llx_facture_fourn AS f
				where  fk_facture_fourn = f.rowid and fk_code_ventilation = C.rowid  
				AND f.ref LIKE 'SI15%')) as dette
		,  pcg_type 
		from  llx_accountingaccount as c
		where active = 1
		group by  pcg_type ) as tb1
		) as tb2

	order by 1, 3


*/
}

	//print "<p>OK Création table</p>";
	$sql = "select pcg_type , c.label, account_number, sum( fd.total_ht ) as dette, '' as creance,'' as total,''as TVA ,'' as benefice ";
	$sql .= "from llx_accountingaccount as c,  llx_facture_fourn_det AS fd, llx_facture_fourn AS f ";
	$sql .= "where  fk_facture_fourn = f.rowid and c.rowid = fk_code_ventilation ";
	$sql .= "	AND f.ref LIKE 'SI15%' ";
	$sql .= "group by c.label, account_number, pcg_type ";
	//-- pour le reste prendre HT
	$sql .= "	union ";
	$sql .= "select pcg_type , c.label, account_number, '', 	sum(fd.total_ht) as creance,'','','' ";
	$sql .= "from llx_accountingaccount as c,  llx_facturedet AS fd, llx_facture AS f ";
	$sql .= "where  fk_facture = f.rowid and c.rowid = fk_code_ventilation ";
	$sql .= "AND f.ref LIKE 'FA15%' ";
	$sql .= "group by c.label, account_number, pcg_type ";
	$sql .= "union ";
	//-- calcul somme par sous-type
	$sql .= "	select pcg_type , 'total', '__'	, '','',case when total = 0 then '' else total end as total,	 ";
	$sql .= "		 case when pcg_type = 'A - 4 SAISONS'  or pcg_type = 'C - SEJOUR'  or pcg_type = 'D - ACTIVITE ACHETEE REVENDUE' then total * 0.2 else '' end as TVA,	 ";
	$sql .= " case when pcg_type = 'A - 4 SAISONS' or pcg_type = 'C - SEJOUR'  or pcg_type = 'D - ACTIVITE ACHETEE REVENDUE'  then total * 0.8 else total  end as Benefice ";
	$sql .= "from (select  case when isnull(creance) then 0 else creance end - case when isnull(dette) then 0 else dette end as total, pcg_type ";
	$sql .= "	from (	select  ";
	$sql .= "			sum((select  sum(fd.total_ht) ";
	$sql .= "			from   llx_facturedet AS fd, llx_facture AS f ";
	$sql .= "			where  fk_facture = f.rowid and fk_code_ventilation = c.rowid  ";
	$sql .= "			AND f.ref LIKE 'FA15%'))  as creance,	 ";
	$sql .= "			sum((select   sum( fd.total_ht ) ";
	$sql .= "			from   llx_facture_fourn_det AS fd, llx_facture_fourn AS f ";
	$sql .= "			where  fk_facture_fourn = f.rowid and fk_code_ventilation = C.rowid  ";
	$sql .= "			AND f.ref LIKE 'SI15%')) as dette ";
	$sql .= "	,  pcg_type  ";
	$sql .= "	from  llx_accountingaccount as c ";
	$sql .= "	where active = 1 ";
	$sql .= "	group by  pcg_type ) as tb1 ";
	$sql .= "	) as tb2 ";
	$sql .= "union ";
	$sql .= "select 'Z_BENEFICE' ,'TOTAL', '__', '', '','','',sum(case when pcg_type = 'A - 4 SAISONS' or pcg_type = 'C - SEJOUR'  or pcg_type = 'D - ACTIVITE ACHETEE REVENDUE'  then total * 0.8 else total  end ) as Benefice ";
	$sql .= "from (select  case when isnull(creance) then 0 else creance end - case when isnull(dette) then 0 else dette end as total, pcg_type ";
	$sql .= "	from (	select  ";
	$sql .= "			sum((select  sum(fd.total_ht) ";
	$sql .= "			from   llx_facturedet AS fd, llx_facture AS f ";
	$sql .= "			where  fk_facture = f.rowid and fk_code_ventilation = c.rowid   ";
	$sql .= "			AND f.ref LIKE 'FA15%'))  as creance,	 ";
	$sql .= "			sum((select   sum( fd.total_ht ) ";
	$sql .= "			from   llx_facture_fourn_det AS fd, llx_facture_fourn AS f ";
	$sql .= "			where  fk_facture_fourn = f.rowid and fk_code_ventilation = C.rowid   ";
	$sql .= "			AND f.ref LIKE 'SI15%')) as dette ";
	$sql .= "	,  pcg_type  ";
	$sql .= "	from  llx_accountingaccount as c ";
	$sql .= "	where active = 1 ";
	$sql .= "	group by  pcg_type ) as tb1 ";
	$sql .= "	) as tb2 ";
	$sql .= "order by 1, 3 ";
	
	$resql = $db->query($sql);

	if ($resql	)   {
			//print '<p>Calcul OK</p>';
	}
	else {
		print '<p>Erreur dans le calcul</p>';
		exit();
		}


$title=$langs->trans("Suivi Budgetaire ".$annee);

//print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$nbtotalofrecords,$nbtotalofrecords);
print_barre_liste($title,0,'','','','','',0,0);

/// recherche des données à afficher


	
	$num = $db->num_rows($resql);
	print '<table>';
    // affiche la barre grise de titres des filtres
	print '<tr class="liste_titre">';
	print_liste_field_titre("Groupement",'','',"",'','','','');
	print_liste_field_titre("Compte",'','',"",'','','','');
	print_liste_field_titre("Numero Compte",'','',"",'','','','');
	print_liste_field_titre("Dettes",'','',"",'','','','');
	print_liste_field_titre("Créances",'','',"",'','','','');
	print_liste_field_titre("Total",'','',"",'','','','');
	print_liste_field_titre("TVA sur Marge",'','',"",'','','','');
	print_liste_field_titre("Benefice",'','',"",'','','','');
	print "</td></tr>";
	for ($i=0; $i<$num; $i++) {
		$obj = $db->fetch_object($resql);
		if (!(!empty($obj->benefice) and $obj->benefice == 0)) {
			print "<tr $bc[$var]";
			if ($obj->label == 'total') print 'style="background:#F2F5A9;font-weight: bold;"';
			elseif ($obj->label == 'TOTAL') print 'style="background:##FE9A2E;font-weight: bold;font-size:15px;"';
			print ">";
			if ($obj->pcg_type == 'Z_BENEFICE') {
				$obj->label = 'Benefice total';
				$obj->pcg_type = '';
			}			
			print '<td>'.$obj->pcg_type.'</td>';
			print '<td>'.$obj->label.'</td>';
			print '<td>'.$obj->account_number.'</td>';
			if (!empty($obj->dette) ) 
					print '<td>'.price2num($obj->dette, 'MT').'</td>';
				else print '<td></td>';
			if (!empty($obj->creance) ) 
					print '<td style="text-align: right;">'.price2num($obj->creance, 'MT').'</td>';
				else print '<td></td>';
			if (!empty($obj->total) )  print '<td>'.price2num($obj->total, 'MT').'</td>';
				else print '<td></td>';
			if (!empty($obj->TVA) ) print '<td style="text-align: right;">'.price2num($obj->TVA, 'MT').'</td>';
				else print '<td></td>';
			if (!empty($obj->benefice) ) print '<td style="text-align: right;">'.price2num($obj->benefice, 'MT').'</td>';
				else print '<td></td>';
		}
	}


	print '</table>';



// End of page
llxFooter();
$db->close();
?>
