<?php
/* lancement http://localhost/dolibarr/custom/cglinscription/bilan2015.php
*/
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 * Modif Marie - 30/11/2020 - vérification avant rapprochement stripe
 *
 * Version CAV - 2.7 - été 2022
 *					 	- Remplacer method="GET" par method="POST"
 *					 	- Migration Dolibarr V15
 *					  	- correction date dans requete
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
 *   	\file       custom/cglinscription/verificationstripe.php
 *		\ingroup    cglinscription
 *		\brief      vérification avant rapprochement stripe
 */

 
// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';   // RECUPERATION DE TOUS LES PARAMETRES DOLIBARR DONT BASE DE DONNEES
require_once ('./class/cglinscription.class.php');   // CLASSE TRAITEMENT
require_once ('./class/bulletin.class.php');         // CLASSE OBJET

// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");

$datedeb = GETPOST ("datedeb", 'date');
$datefin = GETPOST ("datefin", 'date');

// if (empty($annee)) $annee =  strftime('%Y',dol_now());
$test = GETPOST ("test", 'alpha');

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
			 $result = '<a href="../../comm/card.php?socid='.$id.'" >' ;
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

$bullfct=new Bulletin($db);
if ($test == 'TestRecupEcrituresStripe') {
	if (empty ($datedeb))
		$titre = $langs->trans ('LibRecupEcritureStripe');
	else
		$titre = $langs->trans('LibRecupEcritureStripe2', $datedeb, $datefin);
	// if (!empty($annee)) $titre .= ' '.$annee;  // . signifie concatener soit mis a coté et .= signifie concatène puis affecte le résultat
	print_fiche_titre($titre); 	

	// si datedeb saisie : 
	if (!empty($datedeb)) // ! signifie n'est pas ou contraire 
		TestRecupEcrituresStripe($datedeb, $datefin);
}

// print_r ($conf);  // OBJET QUI CONCENTRE TOUS LES PARAMETRAGES DOLIBARR 

 print '<br>';
 print '<br>';

 print '<form method="POST" action="'. $_SERVER["PHP_SELF"].'">';  // JE VOUDRAIS AVOIR UN FORMULAIRE, ACTION = ACTION APRES VALIDATION DU FORMULAIRE
print '<input type="hidden" name="test" value="'.$test.'">'; // relatif URL
print '<input type="hidden" name="token" value="'.newtoken().'">';
print $langs->trans('LibDateDeb');
print '<input class="flat" value="'.$datedeb.'" type="text" name="datedeb"  id="datedeb" ">';
print '<br>';
print $langs->trans('LibDateFin');
print '<input class="flat" value="'.$datefin.'" type="text" name="datefin"  id="datefin" ">';
print '<input type="submit" class="button" value="' . $langs->trans('Valider') . '">';
print '</form>';

	/*
	*	retourne 		-1 un bulletin-contrat de l'année passée en argument a un paiement sans écriture 
	*
	*/
	function TestRecupEcrituresStripe($deb, $fin)
	{
		global $db;		
		$lsterr = array();	
		// $sql = "select rowid, ref from  ".MAIN_DB_PREFIX ."cglinscription_bull where year(datec) = ". $annee;
		
		$debmysql = substr($deb,8,2).'-'.substr($deb,3,2).'-'.substr($deb,0,2);
		$finmysql = substr($fin,8,2).'-'.substr($fin,3,2).'-'.substr($fin,0,2);

		$sql=   "SELECT datev, num_cheque, pt, b.amount, ref, b.rowid as BUID, bd.fk_bull, f.rowid as facId ";
		$sql .= "FROM ".MAIN_DB_PREFIX ."bank as b ";
		$sql .= 	"LEFT JOIN `".MAIN_DB_PREFIX ."cglinscription_bull_det` as bd on bd.fk_banque = b.rowid and bd.type = 1 ";
		$sql .=		"LEFT JOIN  ".MAIN_DB_PREFIX ."facture as f on bd.fk_facture = f.rowid ";
		$sql .= "WHERE b.fk_account=7 "; 
		$sql .= 	"AND datev between '".$debmysql."' "; 
		$sql .= 	"AND '".$finmysql."' "; 
		$sql .= 	"AND b.amount  > 0 ";
		$sql .= "ORDER BY 2 ";
		$sql .= "LIMIT 500 ";
		
		$resql=$db->query($sql);
		dol_syslog("Verification::TestRecupEcrituresStripe");
		if ($resql ) {
			 $i = 0;
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Date</b></td>';
			 print '<td><b>Identifiant Stripe </b></td>';
			 print '<td><b>Montant ecr</b></td>';
			 print '<td><b>Montant BU</b></td>';
 			 print '<td><b>Bulletin/Contrat</b></td>';
			 print '<td><b>ID ecriture</b></td>';
			 print '<td><b>ID bulletin</b></td>';
			 print '<td><b>ID facture</b></td>';
			 print '</tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				//print '<td ><a href="./inscription.php?id_bull='.$bulltmp->id.'" >'.$bulltmp->ref.'</a></td>';
				print '<td>'.$obj->datev.'</td>';
				print '<td>'.$obj->num_cheque.'</td>';
				print '<td align=right>'.price2num($obj->pt).'€</td>';
				print '<td align=right>'.price2num($obj->amount).'€</td>';
				print '<td>'.$obj->ref.'</td>';
				print '<td>'.$obj->BUID.'</td>';
				print '<td>'.$obj->fk_bull.'</td>';
				print '<td>'.$obj->facId.'</td>';
				print '</tr>';
				$i++;					
			 }
			 print '</tbody></table>';
		}				
	} //TestRecupEcrituresStripe

	

// End of page
llxFooter();
$db->close();   //FERMETURE BASE DE DONNEES
/*

select bk.datec, bk.label, st.nom, bk.amount,  bk.num_chq, concat(concat(bdcq.ref,'_' ),  
  substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq))) as LabelVirem_Attendu, bk.rowid as rowid, bdcq.ref, bdcq.rowid as bdcq_id  
 FROM ".MAIN_DB_PREFIX ."bank as bk, ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq, ".MAIN_DB_PREFIX ."societe as st, ".MAIN_DB_PREFIX ."paiement as p, ".MAIN_DB_PREFIX ."paiement_facture as pf, ".MAIN_DB_PREFIX ."facture as f  
 where bk.fk_account = 4   
 	and st.rowid = f.fk_soc  
 	and bk.fk_bordereau = bdcq.rowid  
 	and p.fk_bank = bk.rowid  
 	and pf.fk_paiement = p.rowid   
 	and pf.fk_facture = f.rowid	  
 	and not exists (select (1)   
 		from ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien   
 		where lien.type = 'banktransfert'   
 		and lien.fk_bank = vir.rowid   
 		and  substring_index(vir.label, '_',-1) = substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq))   
 		)   
and year(bk.datec) = 2017

 		order by bk.datec desc 	
		
select bk.datec, bk.label, st.nom, bk.amount,  bk.num_chq,concat(concat(bdcq.ref,'_' ),  
  substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq))) as LabelVirem_Attendu, bk.rowid as rowid, bdcq.ref, bdcq.rowid as bdcq_id  
 FROM ".MAIN_DB_PREFIX ."bank as bk, ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq, ".MAIN_DB_PREFIX ."societe as st, ".MAIN_DB_PREFIX ."paiement as p, ".MAIN_DB_PREFIX ."paiement_facture as pf, ".MAIN_DB_PREFIX ."facture as f  
 where bk.fk_account = 4   
 	and st.rowid = f.fk_soc  
 	and bk.fk_bordereau = bdcq.rowid  
 	and p.fk_bank = bk.rowid  
 	and pf.fk_paiement = p.rowid   
 	and pf.fk_facture = f.rowid 
and year(bk.datec) = 2017
 		order by bk.datec desc 	
		
Lies des virement qui n'ont pas de écriture qui pourrat leur correspondre		
select vir.label   
 	from ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien   
 		where lien.type = 'banktransfert'   
 		and lien.fk_bank = vir.rowid   
		and not exists (select (1) FROM ".MAIN_DB_PREFIX ."bank as bk
			 where bk.fk_account = 4  
			and year(bk.datec) = 2017
 		and  substring_index(vir.label, '_',-1) = substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq)))   
and year(datec) = 2017
and vir.label like 'WZ%'

différence enter montant virement e tle montant des ecritures qui lui correspondent
select vir.label, vir.amount, sum(bk.amount)
from ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien   ,".MAIN_DB_PREFIX ."bank as bk
where lien.type = 'banktransfert'   
 		and lien.fk_bank = vir.rowid   
		and  bk.fk_account = 4  
			and year(bk.datec) = 2017
 		and  substring_index(vir.label, '_',-1) = substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq)) 
and year(vir.datec) = 2017
and vir.label like 'WZ%'
group by  vir.label




select vir.label, vir.amount, bk.amount, bk.num_chq, bk.fk_bordereau
from ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien   ,".MAIN_DB_PREFIX ."bank as bk, ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq
where lien.type = 'banktransfert'   
 		and lien.fk_bank = vir.rowid   
		and  bk.fk_account = 4  
		and  bk.fk_bordereau = bdcq.rowid
 		and  vir.label = concat(concat(bdcq.ref,'_' ),  
  substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq)))
and year(vir.datec) = 2017
and vir.label like 'WZ%'
and vir.label ="WZ_Février2R_E231836"


select vir.label, vir.amount, bk.amount, bk.num_chq, bk.fk_bordereau, vir.label  ,bdcq.ref, 
 substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq))
 ,concat(concat(bdcq.ref,'_' ),  
  substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq)))
from ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien   ,".MAIN_DB_PREFIX ."bank as bk, ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq
where lien.type = 'banktransfert'   
 		and lien.fk_bank = vir.rowid   
		and  bk.fk_account = 4  
		and  bk.fk_bordereau = bdcq.rowid
 		and  substring_index(vir.label, '_',-1) = substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq)) 
and year(vir.datec) = 2017
and vir.label like 'WZ%'
and vir.label ="WZ_Février2R_E231836"



select vir.label, vir.amount, bk.amount, bk.num_chq, bk.fk_bordereau, vir.label  ,bdcq.ref, 
 substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq))
 ,concat(concat(bdcq.ref,'_' ),  
  substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq)))
from ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien   ,".MAIN_DB_PREFIX ."bank as bk, ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq
where lien.type = 'banktransfert'   
 		and lien.fk_bank = vir.rowid   
		and  bk.fk_account = 4  
		and  bk.fk_bordereau = bdcq.rowid
 		and  substring_index(vir.label, '_',-1) = substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq)) 
and year(vir.datec) = 2017
and vir.label like 'WZ%'
and vir.label ="WZ_JUILLET2R_E223309"












		
		
		
		
		*/
?>


