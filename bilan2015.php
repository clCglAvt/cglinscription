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

 
 
 
 
 
 /*
 UPDATE `cglavtdev`.`llx_cglinscription_bull` SET `regle` = '4' WHERE `llx_cglinscription_bull`.`rowid` =168 LIMIT 1 ;
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
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Modif").'"></img>';
		}	
		if ($option == 'MAJLoc')		{
			$result = '<a href="./location.php?id_contrat='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Modif").'"></img>';
		}				
		elseif ($option == 'Tiers'){
			 $result = '<a href="/dolibarr/comm/card.php?socid='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="/dolibarr/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'"></img>';
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

-- total sur facture
select p.label, sum(fd.total_ttc), MONTH(f.datec) 
from  llx_facture as f , llx_facturedet as fd left join    llx_product as p on fk_product = p.rowid
where 
 fk_facture = f.rowid
and f.facnumber like "FA15%"
group by p.label, MONTH(datec) 


select p.label, f.facnumber, fd.total_ttc, MONTH(f.datec) 
from  llx_facture as f , llx_facturedet as fd left join    llx_product as p on fk_product = p.rowid
where 
 fk_facture = f.rowid
and f.facnumber like "FA15%"
and MONTH(f.datec)  = 1

-- recherche facture non CglInscription contenant le mot Velo

select  'total des factures Velo sans contrat par mois ', sum( f.total_ttc) ,  COUNT(DISTINCT f.rowid) as NbCommande_Contrat, MONTH(f.datec)  as Mois,
'Velo' as Secteur 

from  llx_facture as f 
where   note_private not like '%LO%'  and note_private not like '%BU%'
 and exists (select * from  llx_facturedet as fd where 
 fk_facture = f.rowid  and
( upper(fd.description) like "%Velo%" or upper(fd.description) like upper("%Vélo%") 
or upper(fd.description) like "%VTT%" or upper(fd.description) like "%VAE%"
or upper(fd.description) like "%ROUTE%" or upper(fd.description) like "%GTN%" ))
and f.facnumber like "FA15%"
group by  MONTH(f.datec) 



-- recherche facture non CglInscription ne contenant pas le mot Velo

-- total par secteur à partir des paiements
CREATE TABLE IF NOT EXISTS  _cgltab (Libelle  varchar (100), CA  decimal (10,2), NbAction  integer , Mois  integer, Secteur  varchar (15)) ; 
Insert into _cgltab
select  'total par secteur à partir des paiements', sum(pt) ,  COUNT(DISTINCT b.rowid) as NbCommande_Contrat, '' as Mois,
case when ref like 'LO%' then 'Velo' else '4 saisons' end as Secteur 
FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b
WHERE bd.type =1
AND bd.fk_bull = b.rowid
and bd.action not in ('X','S')
and ref like '__2015%'
group by secteur
union
-- Traitement des activités

SELECT 'total par mois et par secteur', sum( pu * ( 1 - rem /100 ) * qte ) , COUNT(DISTINCT b.rowid) as nbContrat, MONTH( bd.datedepose ), 'Velo'
FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b
WHERE bd.type =0
and bd.action not in ('X','S')
and ref like 'LO%'
and year(bd.datedepose) = 2015
and fk_bull = b.rowid
 
GROUP BY MONTH( bd.datedepose )
union
select 'total par mois et par secteur',sum( pu * ( 1 - rem /100 ) * qte)   ,   COUNT(DISTINCT b.rowid)  as NbCommande,
MONTH(ses.dated), '4 saisons'
FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b, llx_agefodd_session as ses
WHERE bd.type =0
and year(ses.dated) = 2015
and ref like 'BU%'
and bd.action not in ('X','S')
and fk_bull = b.rowid
and fk_activite = ses.rowid
group by   MONTH(ses.dated) 

union
-- total par secteur à partir des lignes de bulletin/contrat 
SELECT 'total par secteur à partir des lignes de bulletin/contrat ', sum( pu * ( 1 - rem /100 ) * qte ) , COUNT(DISTINCT b.rowid) as nbContrat, '', 'Velo'
FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b
WHERE bd.type =0
and bd.action not in ('X','S')
and ref like 'LO%'
and year(bd.datedepose) = 2015
and fk_bull = b.rowid
 
union
select 'total par secteur à partir des lignes de bulletin/contrat ',sum( pu * ( 1 - rem /100 ) * qte)   ,   COUNT(DISTINCT b.rowid) NbCommande,'', '4 saisons'
FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b, llx_agefodd_session as ses
WHERE bd.type =0
and year(ses.dated) = 2015
and ref like 'BU%'
and bd.action not in ('X','S')
and fk_bull = b.rowid
and fk_activite = ses.rowid
union

-- recherche facture non CglInscription contenant le mot Velo

select  'total des factures Velo sans contrat par mois ', sum( f.total_ttc) ,  COUNT(DISTINCT f.rowid) as NbCommande_Contrat, MONTH(f.datec)  as Mois,
'Velo' as Secteur 

from  llx_facture as f 
where   note_private not like '%LO%'  and note_private not like '%BU%'
 and exists (select * from  llx_facturedet as fd where 
 fk_facture = f.rowid  and
( upper(fd.description) like "%Velo%" or upper(fd.description) like upper("%Vélo%") 
or upper(fd.description) like "%VTT%" or upper(fd.description) like "%VAE%"
or upper(fd.description) like "%ROUTE%" or upper(fd.description) like "%GTN%" ))
and f.facnumber like "FA15%"
group by  MONTH(f.datec) 
union
select  'total des factures Velo sans contrat ', sum( f.total_ttc) ,  COUNT(DISTINCT f.rowid) as NbCommande_Contrat, ''  as Mois,
'Velo' as Secteur 

from  llx_facture as f 
where   note_private not like '%LO%'  and note_private not like '%BU%'
 and exists (select * from  llx_facturedet as fd where 
 fk_facture = f.rowid  and
( upper(fd.description) like "%Velo%" or upper(fd.description) like upper("%Vélo%") 
or upper(fd.description) like "%VTT%" or upper(fd.description) like "%VAE%"
or upper(fd.description) like "%ROUTE%" or upper(fd.description) like "%GTN%" ))
and f.facnumber like "FA15%"


-- recherche facture non CglInscription ne contanant pas le mot Velo
union

select  'total des factures autre que Velo sans contrat ni bulletin  par mois ', sum( f.total_ttc) ,  COUNT(DISTINCT f.rowid) as NbCommande_Contrat, MONTH(f.datec)  as Mois,
'????' as Secteur 

from  llx_facture as f 
where   ((note_private not like '%LO%'  and note_private not like '%BU%') or isnull(note_private))
 and not exists (select * from  llx_facturedet as fd where 
 fk_facture = f.rowid  and
( upper(fd.description) like "%Velo%" or upper(fd.description) like upper("%Vélo%") 
or upper(fd.description) like "%VTT%" or upper(fd.description) like "%VAE%"
or upper(fd.description) like "%ROUTE%" or upper(fd.description) like "%GTN%" ))
and f.facnumber like "FA15%"
group by  MONTH(f.datec) 
union
select  'total des factures autre que Velo sans contrat ni bulletin  par mois ', sum(f.total_ttc) ,  MONTH(f.datec)  as Mois, '???' as Secteur 

from  llx_facture as f 
where   ((note_private not like '%LO%'  and note_private not like '%BU%' ) or isnull(note_private ))
 and not exists (select * from  llx_facturedet as fd where 
 fk_facture = f.rowid  and
( upper(fd.description) like "%Velo%" or upper(fd.description) like upper("%Vélo%") 
or upper(fd.description) like "%VTT%" or upper(fd.description) like "%VAE%"
or upper(fd.description) like "%ROUTE%" or upper(fd.description) like "%GTN%" ))
and f.facnumber like "FA15%"
group by  MONTH(f.datec) 


union

SELECT 'total des contrats par type location ', sum( pu * ( 1 - rem /100 ) * qte ) , COUNT(DISTINCT b.rowid) as nbContrat, '' , label 
FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b, llx_product as p 
WHERE bd.type =0
and bd.action not in ('X','S')
and b.ref like 'LO%'
and year(bd.datedepose) = 2015
and fk_produit = p.rowid
and fk_bull = b.rowid
and fk_produit <> 14 -- supprimer moniteur
 
GROUP BY fk_produit ,label 

union
---Pb de décalage entre paiement et facture
 select 'contrat avec Facturé et Paiement différent', '', ref, concat('Facture =', Facture), concat('Paiement = ', case when isnull(paiement) then 'est nul' else paiement end)
 from (
 SELECT b.rowid as rowid,   ref, (select sum( pu * ( 1 - rem /100 ) * qte ) FROM llx_cglinscription_bull_det AS bd where fk_bull = b.rowid and action not in ('S','X')) as Facture,
(select sum( pt ) FROM llx_cglinscription_bull_det AS bd where fk_bull = b.rowid  and action not in ('S','X') ) as paiement
FROM  llx_cglinscription_bull AS b
WHERE ref like '__2015%')
as TB 
where Facture <> paiement
or (isnull(paiement) and Facture<>0)
or (isnull(Facture) and paiement<>0)
order by ref

union
SELECT 'Stats Nb bulletin par origine client', '' , COUNT(DISTINCT b.rowid) , b.typebull , label 

from llx_cglinscription_bull as b left join  llx_c_input_reason as cr on  fk_origine = cr.rowid
group by b.typebull,code, label 
order by b.typebull ,3 desc

union
SELECT 'Stats Nb bulletin par villegiature client', '' , COUNT(DISTINCT b.rowid) , b.typebull , Villegiature   
from llx_cglinscription_bull as b
group by b.typebull,Villegiature




select pu, rowid, fk_bull, (select ref from llx_cglinscription_bull AS b where fk_bull = b.rowid ) as Bulletin, 
	(select c.ref from llx_cglinscription_bull AS b  join llx_commande as c on fk_commande =  c.rowid 
	where fk_bull = b.rowid ) as Commande, 	(select c.fk_statut from llx_cglinscription_bull AS b  join llx_commande as c on fk_commande =  c.rowid 
	where fk_bull = b.rowid ) as StCommande
FROM llx_cglinscription_bull_det as bd
where bd.type =0
and bd.action not in ('X','S')
and exists (select (1) 
	from llx_cglinscription_bull as b1 left join llx_commande as c on fk_commande =  c.rowid
	where b1.ref like 'LO2015%' and fk_bull = b1.rowid and fk_statut = -1)
	 



update  llx_cglinscription_bull_det as bd
set  pu = 0, rem = 0, pt = 0
where bd.type =0
and bd.action not in ('X','S')
and exists (select (1) 
	from llx_cglinscription_bull as b1 left join llx_commande as c on fk_commande =  c.rowid
	where b1.ref like 'LO2015%' and fk_bull = b1.rowid and fk_statut = -1)

update  llx_cglinscription_bull_det as bd
set  action = 'X'
where bd.type =0
and bd.action not in ('X','S')
and exists (select (1) 
	from llx_cglinscription_bull as b1 left join llx_commande as c on fk_commande =  c.rowid
	where b1.ref like 'LO2015%' and fk_bull = b1.rowid and fk_statut = -1)
	 	 
	 
;

select *  into outfile 'c:/toto.txt' from _cgltab;
drop table _cgltab
*/
}

$sql0 = "CREATE TABLE _cgltab (Libelle  varchar (100), CA  decimal (10,2), NbCommande  varchar(15) , Mois  varchar(50), Secteur  varchar (50)) ; ";

//print $sql0;
$resql = $db->query($sql0);
$sql0 = "  DELETE FROM    _cgltab where 1;";
//print $sql0;
$resql = $db->query($sql0);

if ($resql	)   {
	//print "<p>OK Création table</p>";

	$sql .="Insert into _cgltab  ";
	$sql .="select  'total par secteur a partir des paiements', sum(pt) ,  COUNT(DISTINCT b.rowid) as NbCommande_Contrat, '' as Mois, ";
	$sql .="case when ref like 'LO%' then 'Velo' else '4 saisons' end as Secteur  ";
	$sql .="FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b ";
	$sql .="WHERE bd.type =1 ";
	$sql .="AND bd.fk_bull = b.rowid ";
	$sql .="and bd.action not in ('X','S') ";
	$sql .="and ref like '__".$annee."%' ";
	$sql .="group by secteur ";
	$sql .="union ";
	$sql .="SELECT 'total des activites par mois et par secteur', sum( pu * ( 1 - rem /100 ) * qte ) , COUNT(DISTINCT b.rowid) as nbContrat, MONTH( bd.datedepose ), 'Velo' ";
	$sql .="FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b ";
	$sql .="WHERE bd.type =0 ";
	$sql .="and bd.action not in ('X','S') ";
	$sql .="and ref like 'LO%' ";
	$sql .="and year(bd.datedepose) = ".$annee;
	$sql .=" and fk_bull = b.rowid ";
	$sql .="GROUP BY MONTH( bd.datedepose ) ";
	$sql .="union ";
	$sql .="select 'total des activites par mois et par secteur',sum( pu * ( 1 - rem /100 ) * qte)   ,   COUNT(DISTINCT b.rowid)  as NbCommande, ";
	$sql .="MONTH(ses.dated), '4 saisons' ";
	$sql .="FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b, llx_agefodd_session as ses ";
	$sql .="WHERE bd.type =0 ";
	$sql .="and year(ses.dated) = ".$annee;
	$sql .=" and ref like 'BU%' ";
	$sql .="and bd.action not in ('X','S') ";
	$sql .="and fk_bull = b.rowid ";
	$sql .="and fk_activite = ses.rowid ";
	$sql .="group by   MONTH(ses.dated)  ";
	$sql .="union ";
	$sql .="SELECT 'total par secteur a partir des lignes de bulletin/contrat ', sum( pu * ( 1 - rem /100 ) * qte ) , COUNT(DISTINCT b.rowid) as nbContrat, '', 'Velo' ";
	$sql .="FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b ";
	$sql .="WHERE bd.type =0 ";
	$sql .="and bd.action not in ('X','S') ";
	$sql .="and ref like 'LO%' ";
	$sql .="and year(bd.datedepose) = ".$annee;
	$sql .=" and fk_bull = b.rowid ";
	$sql .="  ";
	$sql .="union ";
	$sql .="select 'total par secteur a partir des lignes de bulletin/contrat ',sum( pu * ( 1 - rem /100 ) * qte)   ,   COUNT(DISTINCT b.rowid) NbCommande,'', '4 saisons' ";
	$sql .="FROM llx_cglinscription_bull_det AS bd, llx_cglinscription_bull AS b, llx_agefodd_session as ses ";
	$sql .="WHERE bd.type =0 ";
	$sql .="and year(ses.dated) = ".$annee;
	$sql .=" and ref like 'BU%' ";
	$sql .="and bd.action not in ('X','S') ";
	$sql .="and fk_bull = b.rowid ";
	$sql .="and fk_activite = ses.rowid ";
	$sql .="union ";
	
	$sql .="select  'total des factures Velo sans contrat par mois ', sum( f.total_ttc) ,  COUNT(DISTINCT f.rowid) as NbCommande_Contrat, MONTH(f.datec)  as Mois,
'Velo' as Secteur  ";
	$sql .="from  llx_facture as f  ";
	$sql .="where   note_private not like '%LO%'  and note_private not like '%BU%' ";
	$sql .=" and exists (select * from  llx_facturedet as fd where  ";
	$sql .=" fk_facture = f.rowid  and ";
	$sql .="( upper(fd.description) like '%Velo%' or upper(fd.description) like upper('%Vélo%')  ";
	$sql .="or upper(fd.description) like '%VTT%' or upper(fd.description) like '%VAE%' ";
	$sql .="or upper(fd.description) like '%ROUTE%' or upper(fd.description) like '%GTN%' )) ";
	$sql .="and f.facnumber like 'FA".$anneecourte."%' ";
	$sql .="group by  MONTH(f.datec)  ";
	$sql .="union  ";
	$sql .="select  'total des factures Velo sans contrat ', sum( f.total_ttc) ,  COUNT(DISTINCT f.rowid) as NbCommande_Contrat, ''  as Mois,
'Velo' as Secteur   ";
	$sql .="from  llx_facture as f   ";
	$sql .="where   note_private not like '%LO%'  and note_private not like '%BU%'  ";
	$sql .=" and exists (select * from  llx_facturedet as fd where   ";
	$sql .=" fk_facture = f.rowid  and  ";
	$sql .="( upper(fd.description) like '%Velo%' or upper(fd.description) like upper('%Vélo%')  ";
	$sql .="or upper(fd.description) like '%VTT%' or upper(fd.description) like '%VAE%' ";
	$sql .="or upper(fd.description) like '%ROUTE%' or upper(fd.description) like '%GTN%' )) ";
	$sql .="and f.facnumber like 'FA".$anneecourte."%' ";
	$sql .="union ";
	$sql .="select  'total des factures autre que Velo sans contrat ni bulletin  par mois ', sum( f.total_ttc) ,  COUNT(DISTINCT f.rowid) as NbCommande_Contrat, MONTH(f.datec)  as Mois,
'???' as Secteur  ";
	$sql .="from  llx_facture as f  ";
	$sql .="where   ((note_private not like '%LO%'  and note_private not like '%BU%') or isnull(note_private)) ";
	$sql .=" and not exists (select * from  llx_facturedet as fd where  ";
	$sql .=" fk_facture = f.rowid  and ";
	$sql .="( upper(fd.description) like '%Velo%' or upper(fd.description) like upper('%Vélo%')  ";
	$sql .="or upper(fd.description) like '%VTT%' or upper(fd.description) like '%VAE%' ";
	$sql .="or upper(fd.description) like '%ROUTE%' or upper(fd.description) like '%GTN%' )) ";
	$sql .="and f.facnumber like 'FA".$anneecourte."%' ";
	$sql .="group by  MONTH(f.datec)  ";
	$sql .="union ";
	$sql .="select  'total general des factures ".$annee."', sum( f.total_ttc) ,  COUNT(DISTINCT f.rowid) as NbCommande_Contrat, '',
'' as Secteur  ";
	$sql .="from  llx_facture as f ";
	$sql .="where    f.facnumber like 'FA".$anneecourte."%' ";
	$sql .="union ";
	
	$sql .=" SELECT 'total des contrats par type location ', sum( pu * ( 1 - rem /100 ) * qte ) , COUNT(DISTINCT b.rowid) as nbContrat, '' , label  ";
	$sql .=" FROM llx_cglinscription_bull_det AS bd left join  llx_product as p  on fk_produit = p.rowid , llx_cglinscription_bull AS b  ";
	$sql .="WHERE bd.type =0 and bd.action not in ('X','S') and b.ref like 'LO%' ";
	$sql .="and year(bd.datedepose) =  ".$annee." and fk_bull = b.rowid ";
	$sql .=" GROUP BY fk_produit ,label ";
	$sql .="union ";
	$sql .="select 'contrat avec Facture et Paiement different', '', ref, concat('Facture =', Facture), concat('Paiement = ', case when isnull(paiement) then 'est nul' else paiement end) ";
	$sql .=" from ( ";
	$sql .=" SELECT b.rowid as rowid,   ref, (select sum( pu * ( 1 - rem /100 ) * qte ) FROM llx_cglinscription_bull_det AS bd where fk_bull = b.rowid and action not in ('S','X')) as Facture, ";
	$sql .="(select sum( pt ) FROM llx_cglinscription_bull_det AS bd where fk_bull = b.rowid  and action not in ('S','X') ) as paiement ";
	$sql .="FROM  llx_cglinscription_bull AS b ";
	$sql .="WHERE ref like '__2015%' order by ref) as TB  ";
	$sql .="where Facture <> paiement or (isnull(paiement) and Facture<>0) ";
	$sql .="or (isnull(Facture) and paiement<>0) ";
	$sql .="union ";
	
	$sql .=" SELECT 'Stats Nb bulletin par origine client', '' , COUNT(DISTINCT b.rowid) , b.typebull , label ";
	$sql .="from llx_cglinscription_bull as b left join  llx_c_input_reason as cr on  fk_origine = cr.rowid ";
	$sql.= " WHERE b.ref like '__2015%' ";
	$sql .="group by b.typebull, label  ";
	$sql .="union ";
	
	$sql .=" SELECT 'Stats Nb bulletin par villegiature client', '' , COUNT(DISTINCT b.rowid) , b.typebull , Villegiature  ";
	$sql .="from llx_cglinscription_bull as b ";
	$sql.= " WHERE b.ref like '__2015%' ";
	$sql .="group by b.typebull,Villegiature ";
	
	$resql = $db->query($sql);

	if ($resql	)   {
			//print '<p>Calcul OK</p>';
	}
	else {
		print '<p>Erreur dans le calcul</p>';
		exit();
		}
}
else print '<p>Erreur dans creation table</p>';

$title=$langs->trans("Bilan ".$annee);

//print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$nbtotalofrecords,$nbtotalofrecords);
print_barre_liste($title,0,'','','','','',0,0);

/// recherche des données à afficher

$sql = "SELECT  * from _cgltab";

	//print $sql;
	$resql = $db->query($sql);

	if (!$resql	)   {
		print "Erreur d'affichage";
		exit();
		}
	
	$num = $db->num_rows($resql);
	print '<table>';
    // affiche la barre grise de titres des filtres
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Mode Calcul"),'','',"",'','','','');
	print_liste_field_titre($langs->trans("Montant Chiffre affaire"),'','',"",'','','','');
	print_liste_field_titre($langs->trans("Nombre action (commande, contrat, facture)"),'','',"",'','','','');
	print_liste_field_titre($langs->trans("Mois/Complet annuel"),'','',"",'','','','');
	print_liste_field_titre($langs->trans("Secteur"),'','',"",'','','','');
	print "</td></tr>";
	for ($i=0; $i<$num; $i++) {
		
		$obj = $db->fetch_object($resql);
		print "<tr $bc[$var]>";
		print '<td>'.$obj->Libelle.'</td>';
		print '<td>'.$obj->CA.'</td>';
		print '<td>'.$obj->NbCommande.'</td>';
		print '<td>'.$obj->Mois.'</td>';
		print '<td>'.$obj->Secteur.'</td>';
	}


	print '</table>';



// End of page
llxFooter();
$db->close();
?>
