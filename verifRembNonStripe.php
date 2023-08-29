<?php
/* Copyright (C) 2018-2019  Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 *
 * CCA 06/2022
 *
 * Version CAV - 2.6.1.3 - Création
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/*
*
* liste  des acomptes avec un  paiement et une écriture négtive ou positif sur autre que Stripe
*
*/
// Put here all includes required by your class file

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctDolibarrRevues.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctCommune.class.php';
if (!empty($conf->accounting->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('compta',  'stripe', 'cglinscription'));

// Security check
$socid = GETPOST("socid", "int");
if ($user->socid) {
	$socid = $user->socid;
}
/* PARAMETRES URL */
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;

$str_datedebsais = GETPOST('datedeb', 'string');
$datedebsai = dol_mktime(0, 0, 0,  GETPOST( 'datedebmonth', 'int'), GETPOST('datedebday', 'int'),GETPOST('datedebyear', 'int'));
$str_datefinsai = GETPOST('datefin', 'string');
$datefinsai = dol_mktime(0, 0, 0, GETPOST('datefinmonth', 'int'), GETPOST('datefinday', 'int'), GETPOST('datefinyear', 'int'));
// On affiche le mois entier


if (empty($conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS)) setEventMessages("LibCptePmtStripe", null, 'warnings');
if (empty($conf->global->STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS)) setEventMessages("LibCpteVirStripe", null, 'warnings');
if (empty($conf->global->STRIPE_MAX_TRANSAC_RECUP)) setEventMessages("LibNbMaxLigStripe",null, 'warnings');
/*

INSERT INTO `llx_const` (`rowid`, `name`, `entity`, `value`, `type`, `visible`, `note`, `tms`) VALUES (NULL, 'STRIPE_MAX_TRANSAC_RECUP', '0', '100', 'chaine', '1', 'Nombre maximum de lignes que Stripe renvoie lors des interrogations', '2013-12-27 15:40:11'); 

*/
/* VARIABLE */
//$societestatic = new Societe($db);
$stripe = new Stripe($db); 

/* Securité*/
$result = restrictedArea($user, 'banque');


/*
 * AFFICHAGE
 */

/* Affichage Cadre Dolibarr */
llxHeader('', $langs->trans("StripeDolVir"));


/* LECTURE DONNEES */
$resultsql = Lecture_Donnees();

/* VIEW */
 
$title = $langs->trans("Accomptes avec remboursement hors Stripe");
print_barre_liste($title, "", $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', 0, 0, "", 0, '', '',"");


	print '<table>';
/* entete*/
AfficheEntete();

// Affichage des virements Stripe dans l'ordre décroissant des dates

		$num = $db->num_rows($resultsql);
	
		$i = 0; 
		while ($i < $num)
		{		
			$objp = $db->fetch_object($resultsql);
			AfficherLigne ( $objp);
			$i++;
		}


	print '</table>';



print '</div>';
print '</form>';
	
// End of page
llxFooter();
$db->close();



function	AfficherLigne ( $Op)
{	
	global $tabfrais, $tabcheck , $tabMttEcr ,  $tabtexteEcrInf, $tabExisteVir, $tabrowid;
	global $langs;

	print "<tr>";
	// Ref


	// Accompte	
	$url = 'href="' . DOL_MAIN_URL_ROOT . '/compta/facture/card.php?ref='.$Op->ref.'"';
	print "<td  class='center' ><a ".$url." >".$Op->ref."</a></td>\n";
	
	// Tiers
	$url = 'href="' . DOL_MAIN_URL_ROOT . '/societe/card.php?socid='.$Op->IdTiers.'"';
	print "<td  class='center' ><a ".$url." >".$Op->nom."</a></td>\n";
	
	// Amount
	print '<td class="right">';
	print price($Op->amount , 0, '', 1, -1, -1, '€');
	print "</td>";
	
	// Compte bancaire

	$url = 'href="' . DOL_MAIN_URL_ROOT . '/compta/bank/card.php?id='.$Op->IdCompte.'"';
	print "<td  class='center' ><a ".$url." >".$Op->label."</a></td>\n";
	
	print "</tr>";
} // Afficher

function AfficheEntete() 
{
	global $langs;
	
	print '<tr class="liste_titre">';
	print_liste_field_titre("Acompte ",$_SERVER["PHP_SELF"],"","","","","", "");
	print_liste_field_titre("Tiers", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("Montant", $_SERVER["PHP_SELF"], "", "", "", '', "", "", 'left ');
	print_liste_field_titre("Compte bancaire", $_SERVER["PHP_SELF"], "", "", "", '', "", "", 'center ');
	
	print "</tr>";
	
}	//AfficheEnteteVir

function Lecture_Donnees()
{
	global $conf, $langs, $db;	

	$annee =  strftime('%Y',dol_now('tzuser'));
	$anneecourte = substr($annee,2,2);
	$sql="
		select f.ref,  s.nom, s.rowid as IdTiers, p.amount, cpt.ref as label, cpt.rowid as IdCompte
		from llx_facture  as f , llx_paiement_facture as pf, llx_paiement as p, llx_bank as b, llx_societe as s, 
			llx_bank_account as cpt
		 
		where pf.fk_facture = f.rowid and p.rowid = pf.fk_paiement and p.fk_bank = b.rowid and f.fk_soc = s.rowid and cpt.rowid = b.fk_account
		and  f.ref like 'AC".$anneecourte."%'  and f.amount = 0 
		and p.amount < 0
		and b.fk_account <> ".$conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS;

		$result = $db->query($sql);
		if (!$result){
				//dol_syslog('verifEncaissementStripe::Lien Stripe-Dol - sql:'.$sql, LOG_ERR);
				dol_syslog('verifAccompetAvecRemb:: ', LOG_ERR);
				return NULL;
			}			
	return $result;
} //Lecture_Donnees
?>