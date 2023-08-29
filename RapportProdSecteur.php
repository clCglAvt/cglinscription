<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2017       Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
 *
 * Version CAV - 2.8 - hiver 2023 -
 *			- creation
 * Version CAV - 2.8.4 - printemps 2023 -
 *			- amélioration interface.. Rapport d'activité.
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

/**
 *	\file        htdocs/custom/cglinscription/RapportProdSecteur.php
 *	\brief       Page reporting Chiffre d'affaire, Charge et Marge pour les secteurs sélectionnés
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php'; 
require_once DOL_DOCUMENT_ROOT."/custom/cglavt/class/cglFctDolibarrRevues.class.php";
require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/cglRapportSecteur.class.php";
require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/html_formRapportSecteur.class.php";

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'donation', 'salaries'));
/*
Deux modèles : modeleEcranRapport_a_supp.php  pour la construction d'écran 
		et model_calcul_grp_perso_a_sup.php pour le calcul avec groupes personnalisés 
*/
if ('VARIABLES' == 'VARIABLES') {
	
	$nbofyear = 4;
	$RapportMethodes = new cglRapportSecteur ($db);	
	$FormRapport = new FormRapportSecteur ();	

	$userid = GETPOST('userid', 'int');
	$socid = GETPOST('socid', 'int');

	$TypeRapport = GETPOST('TypeRapport', 'alpha');	
	// Date 
	$year = GETPOST('year', 'int');
	if (empty($year)) {
		$year_current = dol_print_date(dol_now(), "%Y");
		$month_current = dol_print_date(dol_now(), "%m");
		$year_start = $year_current - ($nbofyear - 1);
	} else {
		$year_current = $year;
		$month_current = dol_print_date(dol_now(), "%m");
		$year_start = $year - ($nbofyear - 1);
	}

	// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES' or 'BOOKKEEPING')
	$modecompta = $conf->global->ACCOUNTING_MODE;
	if (!empty($conf->accounting->enabled)) {
		$modecompta = 'CREANCES-DETTES';
	}
	if (GETPOST("modecompta")) {
		$modecompta = GETPOST("modecompta", 'alpha');
	}
	if (empty($modecompta)) $modecompta='RECETTES-DEPENSES';

	$date_startday = GETPOST('date_startday', 'int');
	$date_startmonth = GETPOST('date_startmonth', 'int');
	$date_startyear = GETPOST('date_startyear', 'int');
	$date_endday = GETPOST('date_endday', 'int');
	$date_endmonth = GETPOST('date_endmonth', 'int');
	$date_endyear = GETPOST('date_endyear', 'int');


	$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear, 'tzserver');
	$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear, 'tzserver');

	$secteurs=array();
	$secteurs = GETPOST("secteurs", "alpha");


	// Plage de traitement du rapport
	if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
		$q = GETPOST("q") ? GETPOST("q") : 0;
		if ($q == 0) {
			// We define date_start and date_end
			$year_end = $year_start + ($nbofyear - 1);
			$month_start = GETPOSTISSET("month") ? GETPOST("month", 'int') : ($conf->global->SOCIETE_FISCAL_MONTH_START ? $conf->global->SOCIETE_FISCAL_MONTH_START : 1);
			if (!GETPOST('month')) {
				if (!GETPOST("year") && $month_start > $month_current) {
					$year_start--;
					$year_end--;
				}
				$month_end = $month_start - 1;
				if ($month_end < 1) {
					$month_end = 12;
				}
			} else {
				$month_end = $month_start;
			}
			$date_start = dol_get_first_day($year_start, $month_start, false);
			$date_end = dol_get_last_day($year_end, $month_end, false);
		}
		if ($q == 1) {
			$date_start = dol_get_first_day($year_start, 1, false);
			$date_end = dol_get_last_day($year_start, 3, false);
		}
		if ($q == 2) {
			$date_start = dol_get_first_day($year_start, 4, false);
			$date_end = dol_get_last_day($year_start, 6, false);
		}
		if ($q == 3) {
			$date_start = dol_get_first_day($year_start, 7, false);
			$date_end = dol_get_last_day($year_start, 9, false);
		}
		if ($q == 4) {
			$date_start = dol_get_first_day($year_start, 10, false);
			$date_end = dol_get_last_day($year_start, 12, false);
		}
	}

	if (!empty($secteurs) ) $fl_soussecteurs = true;

	$tmps = dol_getdate($date_start);
	$month_start = $tmps['mon'];
	$tmpe = dol_getdate($date_end);
	$month_end = $tmpe['mon'];
	$year_end = $tmpe['year'];
	$year_start = $tmps['year'];
	$nbofyear = ($year_end - $year_start) + 1;
}
// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
if (!empty($conf->comptabilite->enabled)) {
	$result = restrictedArea($user, 'compta', '', '', 'resultat');
}
if (!empty($conf->accounting->enabled)) {
	$result = restrictedArea($user, 'accounting', '', '', 'comptarapport');
}


// Construction table des secteur niv 1
$tabSects = $tabSectSels = array();
$tabSects = $RapportMethodes->ConstSecteur();
$tabSectSels = $RapportMethodes->ConstSectSel($tabSects, $secteurs);

global $tabSects, $secteurs, $tabSectSels;
/*
 * View
 */

// LIENS
$date_startday = $tmps['mday'];
$date_endday = $tmpe['mday'];

$param = '';
if ($date_startday && $month_start && $year_start) {
	$param .= '&date_startday='.$date_startday.'&date_startmonth='.$date_startmonth.'&date_startyear='.$year_start;
}
if ($date_endday && $month_end && $year_end) {
	$param .= '&date_endday='.$date_endday.'&date_endmonth='.$date_endmonth.'&date_endyear='.$year_end;
}





llxHeader();


print $FormRapport->html_PrepScript();
print $FormRapport->html_PrepStyle(count($tabSects));

// Affiche en-tete du rapport
$form = new Form($db);
if ('ENTETE' == 'ENTETE') {
	// Préparation des argument pour l'entête du rapport
	$name = $langs->trans("TiRpportCAEncaisSecteur");
	$calcmode = $langs->trans("CglCalcModeEngagement");
	$urlsecteurs=$RapportMethodes->Tab2Url($secteurs, 'secteurs');

	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."&".$urlsecteurs.$param."&TypeRapport=".$TypeRapport."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."&".$urlsecteurs."&TypeRapport=".$TypeRapport."'>".img_next()."</a>" : "");
	$description = $langs->trans("CglRulesCAIn");
	if ( empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS )) 
		$description .= $langs->trans("DepositsAreIncluded");
	else $description .= $langs->trans("DepositsAreNotIncluded");
	$builddate = dol_now('tzuser');

	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
	$period .= ' - ';
	$period .= $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');

	$moreparam = array();
	if (!empty($modecompta)) {
		$moreparam['modecompta'] = $modecompta;
	}
	$wfdol = new CglFonctionDolibarr($db);
	$wfdol->report_header($name, $namelink, $period, $periodlink, $description, $builddate, '', $moreparam, $calcmode,'', 0, $TypeRapport);

	if (!empty($conf->accounting->enabled) && $modecompta != 'BOOKKEEPING') {
		print info_admin($langs->trans("CglWarningReport"), 0, 0, 1);
	}
}

// tableau de données 
	
print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

$FormRapport->Entete_rapport($modecompta, $year_start,$year_end );

$cum = array();
$cum_ht = array();

if ($TypeRapport <> 'CHRG')
	$RapportMethodes->ConstTabDonnes($modecompta, 'ventes',  $date_startday, $date_startmonth, $date_startyear, $date_endday, $date_endmonth, $date_endyear, $secteurs, $cum, 
			$cum_ht, $minyearmonth, $maxyearmonth ,1);

if ($TypeRapport <> 'CA')  {
	if ($TypeRapport == 'MRG') $coeff = -1; else  $coeff = 1;
	$RapportMethodes->ConstTabDonnes($modecompta, 'achats',  $date_startday, $date_startmonth, $date_startyear, $date_endday, $date_endmonth, $date_endyear, $secteurs, $cum, 
			$cum_ht, $minyearmonth, $maxyearmonth , $coeff);
}

$FormRapport->Affichage($cum, $cum_ht, $year_start, $year_end, $month_end, $date_startyear, $date_endmonth, $date_endyear, 
	$minyearmonth, $maxyearmonth, $modecompta);

/*
 * En mode recettes/depenses, on complete avec les montants factures non regles
 * et les propales signees mais pas facturees. En effet, en recettes-depenses,
 * on comptabilise lorsque le montant est sur le compte donc il est interessant
 * d'avoir une vision de ce qui va arriver.
 */

/*
 Je commente toute cette partie car les chiffres affichees sont faux - Eldy.
 En attendant correction.

 if ($modecompta != 'CREANCES-DETTES')
 {

 print '<br><table width="100%" class="noborder">';

 // Factures non reglees
 // Y a bug ici. Il faut prendre le reste a payer et non le total des factures non reglees !

 $sql = "SELECT f.ref, f.rowid, s.nom, s.rowid as socid, f.total_ttc, sum(pf.amount) as am";
 $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
 $sql .= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
 if ($socid)
 {
 $sql .= " AND f.fk_soc = $socid";
 }
 $sql .= " GROUP BY f.ref,f.rowid,s.nom, s.rowid, f.total_ttc";

 $resql=$db->query($sql);
 if ($resql)
 {
 $num = $db->num_rows($resql);
 $i = 0;

 if ($num)
 {
 $total_ttc_Rac = $totalam_Rac = $total_Rac = 0;
 while ($i < $num)
 {
 $obj = $db->fetch_object($resql);
 $total_ttc_Rac +=  $obj->total_ttc;
 $totalam_Rac +=  $obj->am;
 $i++;
 }

 print "<tr class="oddeven"><td class=\"right\" colspan=\"5\"><i>Facture a encaisser : </i></td><td class=\"right\"><i>".price($total_ttc_Rac)."</i></td><td colspan=\"5\"><-- bug ici car n'exclut pas le deja r?gl? des factures partiellement r?gl?es</td></tr>";
 }
 $db->free($resql);
 }
 else
 {
 dol_print_error($db);
 }
 */

/*
 *
 * Propales signees, et non facturees
 *
 */

/*
 Je commente toute cette partie car les chiffres affichees sont faux - Eldy.
 En attendant correction.

 $sql = "SELECT sum(f.total_ht) as tot_fht,sum(f.total_ttc) as tot_fttc, p.rowid, p.ref, s.nom, s.rowid as socid, p.total_ht, p.total_ttc
 FROM ".MAIN_DB_PREFIX."commande AS p, ".MAIN_DB_PREFIX."societe AS s
 LEFT JOIN ".MAIN_DB_PREFIX."co_fa AS co_fa ON co_fa.fk_commande = p.rowid
 LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON co_fa.fk_facture = f.rowid
 WHERE p.fk_soc = s.rowid
 AND p.fk_statut >=1
 AND p.facture =0";
 if ($socid)
 {
 $sql .= " AND f.fk_soc = ".((int) $socid);
 }
 $sql .= " GROUP BY p.rowid";

 $resql=$db->query($sql);
 if ($resql)
 {
 $num = $db->num_rows($resql);
 $i = 0;

 if ($num)
 {
 $total_pr = 0;
 while ($i < $num)
 {
 $obj = $db->fetch_object($resql);
 $total_pr +=  $obj->total_ttc-$obj->tot_fttc;
 $i++;
 }

 print "<tr class="oddeven"><td class=\"right\" colspan=\"5\"><i>Signe et non facture:</i></td><td class=\"right\"><i>".price($total_pr)."</i></td><td colspan=\"5\"><-- bug ici, ca devrait exclure le deja facture</td></tr>";
 }
 $db->free($resql);
 }
 else
 {
 dol_print_error($db);
 }
 print "<tr class="oddeven"><td class=\"right\" colspan=\"5\"><i>Total CA previsionnel : </i></td><td class=\"right\"><i>".price($total_CA)."</i></td><td colspan=\"3\"><-- bug ici car bug sur les 2 precedents</td></tr>";
 }
 print "</table>";

 */

// End of page
llxFooter();
$db->close();



/* INfo pour select multiple avec regroupement
	
print '	<label>Veuillez choisir un ou plusieurs animaux :
  <select name="pets" multiple size="4">
   <optgroup label="Animaux volants">
      <option value="perroquet">Perroquet</option>
      <option value="macaw">Macaw</option>
      <option value="albatros">Albatros</option>
      <option value="singe">Singe Volant</option>
    </optgroup>
	<optgroup label="Animaux marchants">
      <option value="Chien">Chien</option>
      <option value="chat">chat</option>
      <option value="Hamster" disabled >Hamster</option>
    </optgroup>
  </select>
</label> ';
*/