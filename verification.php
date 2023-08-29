<?php
/* lancement http://localhost/dolibarr/custom/cglinscription/bilan2015.php
*/
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
  *
 * Version CAV - 2.7 - été 2022
 *					 - Migration Dolibarr V15 - chg nom champ f.total
 *					  - correction requete TestFactErrTVA
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
/*
A FAIRE
Taille des barres de sélections
*/
/**
 *   	\file       custom/cglinscription/verification.php
 *		\ingroup    cglinscription
 *		\brief      donne les résultats de vérifications
 *					argument 'TestGeneralPresenceEcriture' pour vérifier que touts les paiements des bulletins d'une année saisie ont les écritues correspondant aux paiements
 */

 
// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
require_once ('./class/cglinscription.class.php');
require_once ('./class/bulletin.class.php');

// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");

$annee = GETPOST ("annee", 'integer');
if (empty($annee)) $annee =  strftime('%Y',dol_now('tzuser'));
$test = GETPOST ("test", 'alpha');

// Protection if external user
if ($user->societe_id > 0)
{
	accessforbidden();
}

/* Debug 
debug_backtrace() - g"nère le contexte (liste des includes?)
debug_print_backtrace - pile des fonctions
*/

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
if ($test == 'TestGeneralPresenceEcriture') {
	$titre = $langs->trans('LibVerifTestPresenceEcr');
	if (!empty($annee)) $titre .= ' '.$annee;
	print_fiche_titre($titre); 	

	// si année saisie : 
	if (!empty($annee)) 
		TestGeneralPresenceEcriture($annee);
}
if ($test =='TestFactErrTVA') {	
	$titre = $langs->trans('LibVerifFactTVAOK');
	print_fiche_titre($titre); 	

		TestFactErrTVA($annee);
	
}
if ($test =='TestFactDetErrTVA') {	
	$titre = $langs->trans('LibVerifDetFactTVAOK');
	print_fiche_titre($titre); 	

		TestFactDetErrTVA($annee);
	
}


if ($test =='TestLOErrTVA') {	
	$titre = $langs->trans('LibTestLOErrTVA');
	print_fiche_titre($titre); 	

		TestLOErrTVA($annee);
	
}


if ($test =='TestErrTVACompteGestion') {	
	$titre = $langs->trans('LibTestErrTVACompteGestion');
	print_fiche_titre($titre); 	
	print '<br><i>'.$langs->trans("LibTestErrTVACompteGestion1").'</i><br>';
	print '<br><i>'.$langs->trans("LibTestErrTVACompteGestion2").'</i><br>';
	

		TestErrTVACompteGestion($annee);
	
}



if ($test =='TestDepCodeVentilation' or $test == 'TestBUCodeVentilation') {	
	$titre = $langs->trans('LibTestDepCodeVentilation');
	print_fiche_titre($titre); 	

		TestDepCodeVentilation($annee);	
}

if ($test =='TestEcrWZRapp') {	
	$titre = $langs->trans('LibTestEcrWZRapp');
	print_fiche_titre($titre); 	

		TestTestEcrWZRapp($annee);	
}

if ($test =='TestEcrANCVRapp') {	
	$titre = $langs->trans('LibTestEcrANCVRapp');
	print_fiche_titre($titre); 	

		TestTestEcrANCVRapp($annee);	
}

if ($test =='TestFactPayee') {	
	$titre = $langs->trans('LibTestFactPayee');
	print_fiche_titre($titre); 	

		TestFactPayee($annee);	
}


print '<form method="POST" action="'. $_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="test" value="'.$test.'">';
print '<input type="hidden" name="token" value="'.newtoken().'">';
if (!empty($annee ))  {  print '<br><br><br><br>'; print '<b>Annee a verifier </b>';  } 
else print '<br><br><br><br>Autre annee a verifier ';
print '<input class="flat" value="'.$annee.'" type="text" name="annee"  id="annee" ">';
print '<input type="submit" class="button" value="' . $langs->trans('Valider') . '">';
print '</form>';

	/*
	*	retourne 		-1 un bulletin-contrat de l'année passée en argument a un paiement sans écriture 
	*
	*/
	function TestGeneralPresenceEcriture($annee)
	{
		global $db;
		
		$lsterr = array();	
		$sql = "select rowid, ref from  ".MAIN_DB_PREFIX ."cglinscription_bull where year(datec) = ". $annee;
		$resql=$db->query($sql);
		dol_syslog("Verification::TestGeneralPreenceEcriture sql=".$sql);
		if ($resql ) {
			 $i = 0;
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Bulletin/Contrat</b></td><td><b>Tireur </b></td><td><b>Montant</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				$bulltmp = new Bulletin ($db);
				$bulltmp->fetch($obj->rowid);
				if ($bulltmp->statut > 0) {
					$lsterr = $bulltmp->TestPresenceEcriture(1);
					foreach($lsterr as $lgerr)	{
						print '<tr>';
						print '<td ><a href="./inscription.php?id_bull='.$bulltmp->id.'" >'.$bulltmp->ref.'</a></td>';
						print '<td>'.$lgerr->tireur.'</td>';
						print '<td align=right>'.price2num($lgerr->montant).'</td>';
						if ($lgerr->cause == 0) {
							print "<td>Ecriture absente </td>";
						}
						else  {
								print "<td>Ecriture supprimee </td>";		
						}
						print '</tr>';
					}
				}
				$i++;					
			 }
			 print '</tbody></table>';
		}				
	} //TestGeneralPresenceEcriture

	function TestFactErrTVA($annee)
	{
		global $db;	
		$lsterr = array();	
		
		$sql = '';
		if (empty($annee)) {
			$sql = "select distinct f.rowid, f.ref as facnumber, f.total_tva, sum(fd.total_tva) as Sumtva, sum(fd.total_tva) - f.total_tva as Diff ";
			$sql .= " from ".MAIN_DB_PREFIX ."facture as f";
			$sql .= " left join ".MAIN_DB_PREFIX ."facturedet as fd on fd.fk_facture = f.rowid";
			$sql .= " left join ".MAIN_DB_PREFIX ."product as p on fk_product = p.rowid";
			$anneecour = strftime('%y',dol_now('tzuser'));
			$anneeprec = strftime('%y',dol_now('tzuser'))-1;
			$sql .= " where p.tva_tx <> fd.tva_tx and (f.ref like 'FA".$anneeprec."%' or f.ref like 'FA".$anneecour."%')";
			$sql .= " group by f.ref, f.total_tva";
			$sql .= " having  f.total_tva <> sum(fd.total_tva)";
		}
		else {
			$sql = "select distinct f.rowid, f.ref as facnumber, f.total_tva, sum(fd.total_tva) as Sumtva, sum(fd.total_tva) - f.total_tva as Diff ";
			$sql .= " from ".MAIN_DB_PREFIX ."facture as f";
			$sql .= " left join ".MAIN_DB_PREFIX ."facturedet as fd on fd.fk_facture = f.rowid";
			$sql .= " left join ".MAIN_DB_PREFIX ."product as p on fk_product = p.rowid";
			$sql .= " where year(f.datec) = ".$annee."  " ;	
			$sql .= " group by f.ref, f.total_tva";
			$sql .= " having  f.total_tva <> sum(fd.total_tva)";
		}
			
		$sql .= ' order by 2';
		//$sql = "select rowid, ref from  ".MAIN_DB_PREFIX ."cglinscription_bull where year(datec) = ". $annee;
		$resql=$db->query($sql);
		dol_syslog("Verification::TestFactErrTVA sql=".$sql);
		if ($resql ) {
			 $i = 0;
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Facture</b></td><td><b>Total TVA</b></td><td><b>Total TVA des lignes</b></td><td><b>Difference</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				print '<td ><a href="../../compta/facture/card.php?facid='.$obj->rowid.'" >'.$obj->facnumber.'</a></td>';
				print '<td  align=right>'.price2num($obj->tva).'</td>';
				print '<td  align=right>'.price2num($obj->Sumtva).'</td>';
				print '<td  align=right>'.price2num($obj->Diff).'</td>';
				print '</tr>';
				$i++;					
			 }
		}
		 print '</tbody></table>';
	} // TestFactErrTVA


	function TestFactDetErrTVA($annee)
	{
		global $db;	
		$lsterr = array();	
		$sql = "select distinct f.rowid, f.ref as facnumber, fd.description, p.ref as RefProduit, fd.tva_tx  as TVAFacture, p.tva_tx  as TVAProduit  ";
		$sql .= " from ".MAIN_DB_PREFIX ."facture as f";
		$sql .= " left join ".MAIN_DB_PREFIX ."facturedet as fd on fd.fk_facture = f.rowid";
		$sql .= " left join ".MAIN_DB_PREFIX ."product as p on fk_product = p.rowid";
		$anneecour = strftime('%y',dol_now('tzuser'));
		$anneeprec = strftime('%y',dol_now('tzuser'))-1;
		if (!empty($annee)) $sql .= " WHERE year(f.datec) = ".$annee;
		else 
					$sql .= " where p.tva_tx <> fd.tva_tx and (f.ref like 'FA".$anneeprec."%' or f.ref like 'FA".$anneecour."%')";
		$sql .= ' and  fd.tva_tx  <> p.tva_tx  ';
		$sql .= ' order by f.ref';
		//$sql = "select rowid, ref from  ".MAIN_DB_PREFIX ."cglinscription_bull where year(datec) = ". $annee;
		$resql=$db->query($sql);
		dol_syslog("Verification::TestFactDetErrTVA sql=".$sql);
		if ($resql ) {
			 $i = 0;
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Facture</b></td><td><b>Description sur facture</b></td><td><b>Produit concerne</b></td><td><b>TVA sur facture</b></td><td><b>TVA du produit</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				print '<td ><a href="../../compta/facture/card.php?facid='.$obj->rowid.'" >'.$obj->facnumber.'</a></td>';
				print '<td  align=right>'.$obj->description.'</td>';
				print '<td  align=right>'.$obj->RefProduit.'</td>';
				print '<td  align=right>'.price2num($obj->TVAFacture).'%</td>';
				print '<td  align=right>'.price2num($obj->TVAProduit).'%</td>';
				print '</tr>';
				$i++;					
			 }
		}
		 print '</tbody></table>';
	} // TestFactErrTVA

	function TestLOErrTVA($annee)
	{
		global $db;
		
		$lsterr = array();	
		
		$anneecour = strftime('%y',dol_now('tzuser'));
		$anneeprec = strftime('%y',dol_now('tzuser'))-1;
		$sql = "select distinct f.rowid, f.ref as facnumber ";
		$sql .= " FROM ".MAIN_DB_PREFIX ."facture as f ";
		$sql .= " left join ".MAIN_DB_PREFIX ."facturedet as fd on f.rowid = fd.fk_facture, ";
		$sql .= " ".MAIN_DB_PREFIX ."cglinscription_bull as b ";
		$sql .= " where b.fk_facture = f.rowid ";
		if (!empty($annee)) $sql .= " and year(f.datec) = ".$annee;
		else  $sql .= "  and (f.ref like 'FA".$anneeprec."%' or f.ref like 'FA".$anneecour."%')";
		$sql .= " and fd.total_tva = 0 and fd.total_ht <> 0 ";
		$sql .= " and b.ref like 'LO%' ";
		$sql .= ' order by ref';


		$sql .= " where p.tva_tx <> fd.tva_tx and (f.ref like 'FA".$annee."%' or f.ref like 'FA".$anneecour."%')";
		
		//$sql = "select rowid, ref from  ".MAIN_DB_PREFIX ."cglinscription_bull where year(datec) = ". $annee;
		$resql=$db->query($sql);
		dol_syslog("Verification::TestLOErrTVA sql=".$sql);
		if ($resql ) {
			 $i = 0;
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Facture</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				print '<td ><a href="../../compta/facture/card.php?facid='.$obj->rowid.'" >'.$obj->facnumber.'</a></td>';
				print '</tr>';
				$i++;					
			 }
		}
		 print '</tbody></table>';
	} // TestLOErrTVA
	function TestErrTVACompteGestion($annee)
	{
		global $db, $langs;
		
		$lsterr = array();	
		
		$anneecour = strftime('%y',dol_now('tzuser'));
		$anneeprec = strftime('%y',dol_now('tzuser'))-1;
				
		$sql = "select fL.ref, fdL.description, TBListeErr.label, fdL.tva_tx ";
		$sql .= " from  ";
		$sql .= MAIN_DB_PREFIX ."facture as fL ";
		$sql .= " left join  ".MAIN_DB_PREFIX ."facturedet as fdL on fL.rowid = fdL.fk_facture,	 ";
		$sql .= " ( ";
		$sql .= " 	select label, fk_code_ventilation, substring_index(TVANbErr, '-', 1) as Nb, substring_index(TVANbErr, '-', -1) as TVA ";
		$sql .= " 	from ( ";
		$sql .= " 		select label, fk_code_ventilation, substring_index(TVANb, ',', 1),  substring_index(TVANb, ',', -1) as TVANbErr, ";
		$sql .= " 		case when substring_index(TVANb, ',', 2) <>  substring_index(TVANb, ',', 3) then  substring_index(TVANb, ',', 3) else '' end as TVANbErr1 ";
		$sql .= " 		from (select label, fk_code_ventilation,  GROUP_CONCAT(Duo ORDER BY Duo desc) as TVANb ";
		$sql .= " 		from (select c.label, fd.fk_code_ventilation , concat(concat(convert(count(fd.rowid), char), '-'),  convert(fd.tva_tx, char)) as Duo ";
		$sql .= " 			from  ".MAIN_DB_PREFIX ."facture as f ";
		$sql .= " 			left join  ".MAIN_DB_PREFIX ."facturedet as fd on f.rowid = fd.fk_facture ";
		$sql .= " 			left join  ".MAIN_DB_PREFIX ."accounting_account as c on fd.fk_code_ventilation = c.rowid ";
		$sql .= " 			where  isnull(fd.fk_product) and fd.total_ttc  <> 0 ";
		if (!empty($annee)) $sql .= " 			and year(f.datec) = ".$annee;
		else  	$sql .= " 		 and (f.ref like 'FA".$anneeprec."%' or f.ref like 'FA".$anneecour."%') ";
		$sql .= " 			and f.ref not like 'AC%' ";
		$sql .= " 			group by   c.label, fd.tva_tx ";
		$sql .= " 		 ) as TB ";
		$sql .= " 		group by label, fk_code_ventilation ";
		$sql .= " 		having count(label) > 1) as TB1		 ";
		$sql .= " 	) as TBB ";
		$sql .= " 	union  ";
		$sql .= " 	select label, fk_code_ventilation, substring_index(TVANbErr1, '-', 1) as Nb, substring_index(TVANbErr1, '-', -1) as TVA ";
		$sql .= " 	from ( ";
		$sql .= " 		select label,  fk_code_ventilation, substring_index(TVANb, ',', 1),  substring_index(TVANb, ',', -1) as TVANbErr, ";
		$sql .= " 		case when substring_index(TVANb, ',', 2) <>  substring_index(TVANb, ',', 3) then  substring_index(TVANb, ',', 3) else '' end as TVANbErr1 ";
		$sql .= " 		from (select label, fk_code_ventilation, GROUP_CONCAT(Duo ORDER BY Duo desc) as TVANb ";
		$sql .= " 		from (select cA.label, fdA.fk_code_ventilation, concat(concat(convert(count(fdA.rowid), char), '-'),  convert(fdA.tva_tx, char)) as Duo ";
		$sql .= " 			from  ".MAIN_DB_PREFIX ."facture as fA ";
		$sql .= " 			left join  ".MAIN_DB_PREFIX ."facturedet as fdA on fA.rowid = fdA.fk_facture ";
		$sql .= " 			left join  ".MAIN_DB_PREFIX ."accounting_account as cA on fdA.fk_code_ventilation = cA.rowid ";
		$sql .= " 			where  isnull(fdA.fk_product) and fdA.total_ttc  <> 0 ";
		if (!empty($annee)) $sql .= " 			and year(fA.datec) = ".$annee;
		else  $sql .= "  						and (fA.ref like 'FA".$anneeprec."%' or fA.ref like 'FA".$anneecour."%') ";
		$sql .= " 			and fA.ref not like 'AC%' ";
		$sql .= " 			group by   cA.label, fdA.tva_tx ";
		$sql .= " 		 ) as TBA ";
		$sql .= " 		group by label, fk_code_ventilation ";
		$sql .= " 		having count(label) > 1) as TB1A ";
		$sql .= " 		 where substring_index(TVANb, ',', 2) <>  substring_index(TVANb, ',', 3)	 ";
		$sql .= " 	) as TBBA ";
		$sql .= " ) as TBListeErr ";
		$sql .= " WHERE fdL.fk_code_ventilation = TBListeErr.fk_code_ventilation ";
		$sql .= " And  isnull(fdL.fk_product) and fdL.total_ttc  <> 0 ";
		if (!empty($annee)) $sql .= " and year(fL.datec) = ".$annee;
		else  $sql .= "  and (fL.ref like 'FA".$anneeprec."%' or fL.ref like 'FA".$anneecour."%') ";
		$sql .= " and fL.ref not like 'AC%' ";
		$sql .= " 		and TBListeErr.TVA = fdL.tva_tx ";		
		
		//$sql = "select rowid, ref from  ".MAIN_DB_PREFIX ."cglinscription_bull where year(datec) = ". $annee;
		$resql=$db->query($sql);
		dol_syslog("Verification::TestErrTVACompteGestion sql=".$sql);
		if ($resql ) {
			 $i = 0;
			 print '<br><br><table id=resultat border="1"><tbody >';		
			 print '<tr><td> <b>Facture</td><td>'.$langs->trans('TxTVAFacture').'</td><td>Compte de ventilation</td><td>'.$langs->trans('TxTVAFacture').'</b></td></tr>';
			 if ($db->num_rows($resql) == 0) print '<tr><td colspan=4>'.$langs->trans('EnrVide').'</td></tr>';
			 else {
				 while ($i < $db->num_rows($resql))  {
					$obj = $db->fetch_object($resql);				
					print '<tr>';
					print '<td ><a href="../../compta/facture/card.php?facid='.$obj->rowid.'" >'.$obj->ref.'</a></td>';
					print '<td >'.$obj->description.'</td>';
					print '<td >'.$obj->label.'</td>';
					print '<td >'.$obj->tva_tx.'</td>';
					print '</tr>';
					$i++;					
				 }
			 }
			print '</tbody></table>';
		}
		else print '<br><b>ERRUER INTERROGATION</b></br>';
	} // TestErrTVACompteGestion



	function TestTestEcrWZRapp($annee)
	{
		global $db, $langs;
		
		$lsterr = array();	
		
		$anneecour = strftime('%y',$time);
	// NON MIS EN OEUVRE Voir en bas, les diverses requetes	
		$sql = "select bk.datec, bk.label, st.nom, bk.amount,  concat(concat(bdcq.ref,'_' ), ";
		$sql .= " substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq))) as LabelVirem_Attendu, bk.rowid as rowid, bdcq.ref, bdcq.rowid as bdcq_id ";
		$sql .= "FROM ".MAIN_DB_PREFIX ."bank as bk, ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq, ".MAIN_DB_PREFIX ."societe as st, ".MAIN_DB_PREFIX ."paiement as p, ".MAIN_DB_PREFIX ."paiement_facture as pf, ".MAIN_DB_PREFIX ."facture as f ";
		$sql .= "where bk.fk_account = 4  ";
		$sql .= "	and st.rowid = f.fk_soc ";
		$sql .= "	and bk.fk_bordereau = bdcq.rowid ";
		$sql .= "	and p.fk_bank = bk.rowid ";
		$sql .= "	and pf.fk_paiement = p.rowid  ";
		$sql .= "	and pf.fk_facture = f.rowid	 ";
		$sql .= "	and not exists (select (1)  ";
		$sql .= "		from ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien  ";
		$sql .= "		where lien.type = 'banktransfert'  ";
		$sql .= "		and lien.fk_bank = vir.rowid  ";
		$sql .= "		and  substring_index(vir.label, '_',-1) = substr(bk.num_chq, locate('E',bk.num_chq),locate('O',bk.num_chq)- locate('E',bk.num_chq))  ";
		$sql .= "		)  ";
		if (!empty($annee)) $sql .= " and year(bk.datec) = ".$annee;
		else $sql .= " and year(bk.datec) = ".$anneecour;
		$sql .= "		order by bk.datec desc";	
		$resql=$db->query($sql);
		dol_syslog("Verification::LibTestTestEcrWZRapp1 sql=".$sql);
		print '<br><br><b><i><u>'.$langs->trans('LibTestTestEcrWZRapp1').'</u></i><b></br>';
		if ($resql ) {
			 $i = 0;			 
			print '<p></p><p>';
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Ecriture</b></td>';
			 print '<td> <b>Date</b></td>';
			 print '<td> <b>Tiers</b></td>';
			 print '<td> <b>'.$langs->trans("TiTestTestEcrWZRapp1").'</b></td>';
			 print '<td> <b>Bordereau</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				print '<td ><a href="../../compta/bank/ligne.php?rowid='.$obj->rowid.'&account=4&page=0</a>'.$obj->rowid.'</td>';
				print '<td >'.$obj->datec.'</td>';
				print '<td >'.$obj->nom.'</td>';
				print '<td >'.$obj->LabelVirem_Attendu.'</td>';
				print '<td ><a href="../../compta/paiement/deposit/card.php?id='.$obj->bdcq_id.'</a> '.$obj->ref.'</td>';
				print '</tr>';
				$i++;					
			 }
			print '</tbody></table>';
		}
		else print 'Erreur de requete';
		 
		 
		 $sql = "select vir.datec, vir.label ";
		$sql .= " FROM ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien";
		$sql .= " where lien.type = 'banktransfert'";
		$sql .= " 	and lien.fk_bank = vir.rowid";
		$sql .= " 	and not exists (select (1)";
		$sql .= " 		from ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq ";
		$sql .= " 		where vir.label like concat(concat('%',bdcq.ref),'%'  )";
		$sql .= " 	)";
		$sql .= " 	and vir.label like 'W%'";
		if (!empty($annee)) $sql .= " and year(vir.datec) = ".$annee;
		else $sql .= " and year(vir.datec) = ".$anneecour;
		$sql .= " order by vir.datec desc ";


		$resql=$db->query($sql);
		print '<br><br><b><i><u>'.$langs->trans('LibTestTestEcrWZRapp2').'</u></i><b></br>';
		dol_syslog("Verification::LibTestTestEcrWZRapp2 sql=".$sql);
		if ($resql ) {
			 $i = 0;
			print '<p></p><p>';
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Ecriture</b></td>';
			 print '<td> <b>Date</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				print '<td ><a href="../../compta/paiement/deposit/card.php?id='.$obj->rowid.'" >'.$obj->label.'</a></td>';
				print '<td >'.$obj->datec.'</td>';
				print '</tr>';
				$i++;					
			 }
			print '</tbody></table>';
		}
		else print 'Erreur de requete:'.$sql;
		 
		 	
		$sql = "	select bdcq.ref, bdcq.rowid  ";
		$sql .= " FROM ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq  ";
		$sql .= " where ref like 'W%'  ";
		$sql .= " and not exists (select (1)  ";
		$sql .= " 	from ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien  ";
		$sql .= " 	where lien.type = 'banktransfert'  ";
		$sql .= " 		and lien.fk_bank = vir.rowid  ";
		$sql .= " 		and vir.label like concat(concat('%',bdcq.ref),'%'  )  ";
		$sql .= " 		)";
		if (!empty($annee)) $sql .= " and year(bdcq.datec) = ".$annee;
		else $sql .= " and year(bdcq.datec) = ".$anneecour;
		$sql .= " order by bdcq.ref desc ";	

		$resql=$db->query($sql);
		print '<br><br><b><i><u>'.$langs->trans('LibTestTestEcrWZRapp3').'</u></i><b></br>';
		dol_syslog("Verification::LibTestTestEcrWZRapp3 sql=".$sql);
		if ($resql ) {
			 $i = 0;
			 
			print '<p></p><p>';
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Bordereau</b></td>';
			 print '<td> <b>Date</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				print '<td ><a href="../../compta/paiement/deposit/card.php?id='.$obj->rowid.'" >'.$obj->ref.'</a></td>';
				print '<td >'.$obj->datec.'</td>';
				print '</tr>';
				$i++;					
			 }
			print '</tbody></table>';
		}
		else print 'Erreur de requete:'.$sql;
		 
	} // TestTestEcrWZRapp
	function TestTestEcrANCVRapp($annee)
	{
		global $db, $langs;
		
		$lsterr = array();	
		
		$anneecour = strftime('%y',$time);
		
		$sql = "select ref ";
		$sql .= " FROM ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq  ";		
		if (!empty($annee)) $sql .= " WHERE year(bdcq.datec) = ".$annee;
		else $sql .= " WHERE year(bdcq.datec) = ".$anneecour;
		$sql .= " and fk_bank_account = 3 ";
		$sql .= " and not exists (select (1) from ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien ";
		$sql .= " 			where lien.type = 'banktransfert' ";
		$sql .= " 				and lien.fk_bank = vir.rowid ";
		$sql .= " 				and vir.label like concat(concat('%',bdcq.ref),'%'  ) ";
		$sql .= " 				) ";
		$sql .= " and ref <> 'ANCV161231' ";
		$sql .= " order by ref ";

		$resql=$db->query($sql);
		dol_syslog("Verification::TestTestEcrANCVRapp1 sql=".$sql);
		print '<br><br><b><i><u>'.$langs->trans('TestTestEcrANCVRapp1').'</u></i><b></br>';
		if ($resql ) {
			 $i = 0;
			 
			print '<p></p><p>';
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Bordereau</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				print '<td ><a href="../../compta/paiement/deposit/card.php?id='.$obj->rowid.'</a>'.$obj->ref.'</td>';
				print '</tr>';
				$i++;					
			 }
			print '</tbody></table>';
		}
		else print 'Erreur de requete';
		 
		 $sql = "select vir.datec, vir.label ";
		$sql .= " FROM ".MAIN_DB_PREFIX ."bank as vir, ".MAIN_DB_PREFIX ."bank_url as lien, ".MAIN_DB_PREFIX ."bank as virorig ";
		$sql .= " WHERE lien.type = 'banktransfert' ";
		$sql .= " 	and lien.fk_bank = vir.rowid  ";
		if (!empty($annee)) $sql .= " and year(vir.datec) = ".$annee;
		else $sql .= " and year(vir.datec) = ".$anneecour;
		$sql .= " 	and lien.url_id = virorig.rowid ";
		$sql .= " 	and virorig.fk_account = 3 ";
		$sql .= " 	and not exists (select (1)  from ".MAIN_DB_PREFIX ."bordereau_cheque as bdcq  where vir.label like concat(concat('%',bdcq.ref),'%'  ) ";
		$sql .= " 	) ";
		$sql .= " order by vir.datec desc ";

		$resql=$db->query($sql);
		dol_syslog("Verification::TestTestEcrANCVRapp2 sql=".$sql);
		print '<br><br><i><u>'.$langs->trans('TestTestEcrANCVRapp2').'</u></i></br>';
		if ($resql ) {
			 $i = 0;
			print '<p></p><p>';
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Ecriture</b></td>';
			 print '<td> <b>Date</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				print '<td ><a href="../../compta/paiement/deposit/card.php?id='.$obj->rowid.'" >'.$obj->label.'</a></td>';
				print '<td >'.$obj->datec.'</td>';
				print '</tr>';
				$i++;					
			 }
			print '</tbody></table>';
		}
		else print 'Erreur de requete'.$sql;
		 		 
	} // TestTestEcrANCVRapp




	function TestFactPayee($annee)
	{
		global $db, $langs;
		
		$lsterr = array();	
		$anneecour = strftime('%y',$time);	
		
// Facture à 0
		$sql = "SELECT ref as facnumber , rowid ";
		$sql .= " FROM  ".MAIN_DB_PREFIX ."facture as f ";		
		if (!empty($annee)) $sql .= " WHERE year(datec) = ".$annee;
		else $sql .= " WHERE year(datec) = ".$anneecour;
		$sql .= " and  fk_statut < 2 and f.total_ht = 0";
		$sql .= " order by ref ";

		$resql=$db->query($sql);
		dol_syslog("Verification::TestFactPayee3 ");
		print '<br><br><b><i><u>'.$langs->trans('TestFactPayee3').'</u></i><b></br>';
		if ($resql ) {
			 $i = 0;
			 
			print '<p></p><p>';
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Facture</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				 print '<td ><a href="inscription.php?id_bull='.$obj->rowid.'"</a>'.$obj->facnumber.'</td>';
				print '</tr>';
				$i++;					
			 }
			print '</tbody></table>';
		}
		else print 'Erreur de requete';

// Bulletin non archivé alors que la facture et Payée - close
		$sql = "SELECT ref, rowid , typebull ";
		$sql .= " FROM  ".MAIN_DB_PREFIX ."cglinscription_bull as b ";		
		if (!empty($annee)) $sql .= " WHERE year(datec) = ".$annee;
		else $sql .= " WHERE year(datec) = ".$anneecour;
		$sql .= " and  statut <> 4 and regle <> 6";
		$sql .= " and exists (SELECT 1 from ".MAIN_DB_PREFIX ."facture as f  where b.fk_facture = f.rowid and fk_statut = 2 ) ";
		$sql .= " order by ref ";

		$resql=$db->query($sql);
		dol_syslog("Verification::TestFactPayee ");
		print '<br><br><b><i><u>'.$langs->trans('TestFactPayee').'</u></i><b></br>';
		if ($resql ) {
			 $i = 0;
			 
			print '<p></p><p>';
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Bulletin/Contrat</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				if ($obj->typebull == 'Insc')  print '<td ><a href="inscription.php?id_bull='.$obj->rowid.'"</a>'.$obj->ref.'</td>';
				else  print '<td ><a href="location.php?id_contrat='.$obj->rowid.'</a>'.$obj->ref.'</td>';
				print '</tr>';
				$i++;					
			 }
			print '</tbody></table>';
		}
		else print 'Erreur de requete';
 

// Facture impayée alors que le bulletin est archivée

		$sql = "SELECT f.ref as facnumber, fk_statut, b.ref, statut  ,b.rowid as IbBull, f.rowid as IdFct ";
		$sql .= " FROM  ".MAIN_DB_PREFIX ."facture as f ,".MAIN_DB_PREFIX ."cglinscription_bull as b ";		
		if (!empty($annee)) $sql .= " WHERE year(b.datec) = ".$annee;
		else $sql .= " WHERE year(b.datec) = ".$anneecour;
		$sql .= " and  fk_statut <> 2 and (statut = 4 or regle = 6)";
		$sql .= " and b.fk_facture = f.rowid ";
		$sql .= " order by ref ";

		$resql=$db->query($sql);
		dol_syslog("Verification::TestFactPayee1 ");
		print '<br><br><b><i><u>'.$langs->trans('TestFactPayee1').'</u></i><b></br>';
		if ($resql ) {
			 $i = 0;
			 
			print '<p></p><p>';
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Bulletin/Contrat</b></td>';
			 print '<td> <b>Facture</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				if ($obj->typebull == 'Insc')  print '<td ><a href="inscription.php?id_bull='.$obj->IbBull.'"</a>'.$obj->ref.'</td>';
				else  print '<td ><a href="location.php?id_contrat='.$obj->IbBull.'"</a>'.$obj->ref.'</td>';
				print '<td ><a href="../../compta/facture/card.php?facid='.$obj->IdFct.'"</a>'.$obj->facnumber.'</td>';
				print '</tr>';
				$i++;					
			 }
			print '</tbody></table>';
		}
		else print 'Erreur de requete';

	 		 
// Facture impayée alors que le montant des encaissements est égal au facturé

		$sql = "SELECT   rowid,  facnumber ";
		$sql .= " FROM  ";
		$sql .= "  (select  rowid, facnumber ,   total_ttc, fk_statut, ";
		$sql .= " 	  	case when isnull(PaiementDirect) then 0 else PaiementDirect end  as PaiementDirect,  ";
		$sql .= " 		case when isnull(PaiementAvoir) then 0 else PaiementAvoir end  as PaiementAvoir,   ";
		$sql .= " 		case when isnull(PaiementAcompte) then 0 else PaiementAcompte end  as PaiementAcompte";
		$sql .= " 		from  ";
		$sql .= " 			 (select rowid, ref as facnumber ,   total_ttc, fk_statut, ";
		$sql .= " 				(SELECT sum(amount)  FROM ".MAIN_DB_PREFIX ."paiement_facture WHERE ".MAIN_DB_PREFIX ."paiement_facture.fk_facture = f.rowid) as PaiementDirect,";
		$sql .= " 				(SELECT sum(rc.amount_ttc)  FROM ".MAIN_DB_PREFIX ."societe_remise_except as rc, ".MAIN_DB_PREFIX ."facture as f1 WHERE rc.fk_facture_source=f1.rowid ";
		$sql .= " 			 			and rc.fk_facture = f.rowid  AND (f1.type = 2 OR f1.type = 0)) as PaiementAvoir,";
		$sql .= " 				(SELECT sum(rc.amount_ttc)  FROM ".MAIN_DB_PREFIX ."societe_remise_except as rc , ".MAIN_DB_PREFIX ."facture as f1 WHERE rc.fk_facture_source=f1.rowid  ";
		$sql .= " 			 			and rc.fk_facture = f.rowid  AND f1.type = 3) as PaiementAcompte";
		$sql .= " 			from ".MAIN_DB_PREFIX ."facture as f";
		$sql .= "  			where fk_statut < 2 and f.total_ht = 0.0 ";
		if (!empty($annee)) $sql .= " AND year(f.datec) = ".$annee;
		else $sql .= " AND year(f.datec) = ".$anneecour;
		$sql .= " 			group by rowid, ref ,   total_ttc, fk_statut";
		$sql .= "  			)as Tb1";
		$sql .= "  ) as TB";
		$sql .= "  where total_ttc = PaiementDirect+PaiementAvoir+PaiementAcompte ";

		$resql=$db->query($sql);
		dol_syslog("Verification::TestFactPayee2 ");
		print '<br><br><b><i><u>'.$langs->trans('TestFactPayee2').'</u></i><b></br>';
		if ($resql ) {
			 $i = 0;
			 
			print '<p></p><p>';
			 print '<table id=resultat border="1"><tbody >';
			 print '<tr><td> <b>Facture</b></td></tr>';
			 while ($i < $db->num_rows($resql))  {
				$obj = $db->fetch_object($resql);
				print '<tr>';
				print '<td ><a href="../../compta/facture/card.php?facid='.$obj->rowid.'"</a>'.$obj->facnumber.'</td>';
				print '</tr>';
				$i++;					
			 }
			print '</tbody></table>';
		}
		else print 'Erreur de requete';





	} // TestTestEcrANCVRapp
	

// End of page
llxFooter();
$db->close();
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


