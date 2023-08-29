<?php
/* Copyright (C) 2018-2019  Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 *
 * CCA 06/2022
 *
 * Version CAV - 2.6.1.3 - Création
 * Version CAV - 2.7 - été 2022 - Intégration dans CAV et dans menu Comptablilité Stripe
 * Version CAV - 2.8 - hiver 2023 - Ajout de l'argument token dans url
 *								  - fiabilisation des foreach
 *								  - correction appel fonction creervirement
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
* Intègre à Dolibarr les virements Stripe et les frais des opérations
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
$tabcheckvir =  array();
$tabcheckfr =  array();
$tabcheckvir = GETPOST("tabcheckvir", 'array');
$tabcheckfr = GETPOST("tabcheckfr", 'array');
$str_datedebsais = GETPOST('datedeb', 'string');
$datedebsai = dol_mktime(0, 0, 0,  GETPOST( 'datedebmonth', 'int'), GETPOST('datedebday', 'int'),GETPOST('datedebyear', 'int'));
$str_datefinsai = GETPOST('datefin', 'string');
$datefinsai = dol_mktime(0, 0, 0, GETPOST('datefinmonth', 'int'), GETPOST('datefinday', 'int'), GETPOST('datefinyear', 'int'));
// On affiche le mois entier

$wcglFctCom = new CglFonctionCommune($db);

if (empty($str_datedebsais)) 
{
	
	$datedeb = $wcglFctCom->DebutMois(dol_now('tzuser'));
}
else 
{
	$datedeb = $wcglFctCom->DebutMois($datedebsai);
}
$str_datedeb =  date( "d/m/Y", $datedeb);

if (empty($str_datefinsai)) 
{
	$datefin = $wcglFctCom->FinMois(dol_now('tzuser'));
}
else 
{
	$datefin = $wcglFctCom->FinMois($datefinsai);
}

$str_datefin =  date( "d/m/Y", $datefin);


if (empty($conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS)) setEventMessages("LibCptePmtStripe", null, 'warnings');
if (empty($conf->global->STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS)) setEventMessages("LibCpteVirStripe", null, 'warnings');
if (empty($conf->global->STRIPE_MAX_TRANSAC_RECUP)) setEventMessages("LibNbMaxLigStripe",null, 'warnings');
//if (empty($conf->global->STRIPE_ACCOUNT_POUR_FRAIS)) setEventMessages("LibCodeComptableFraisStripe",null, 'warnings');
/*

INSERT INTO `llx_const` (`rowid`, `name`, `entity`, `value`, `type`, `visible`, `note`, `tms`) VALUES (NULL, 'STRIPE_MAX_TRANSAC_RECUP', '0', '100', 'chaine', '1', 'Nombre maximum de lignes que Stripe renvoie lors des interrogations', '2013-12-27 15:40:11'); 

*/
/* VARIABLE */
//$societestatic = new Societe($db);
$stripe = new Stripe($db); 



/* Securité*/
$result = restrictedArea($user, 'banque');

/* ACTION */
// Initialisation du flag pour renvoyer l'URL simplifiée , on limitera les possibilités de créer des doublons
$flMAJBase = false;

/* LECTURE DONNEES */

$payout = Lecture_DonneesVir($stripe, $datedeb, $datefin, $stripeaccount ='');
$frais = Lecture_DonneesFrais($stripe, $datedeb, $datefin, $stripeaccount ='');

/* CEATION ECRITURES */

if ( GETPOST('action', 'alpha') == "confirm_creervirfr" and GETPOST("confirm",'alpha' ) == 'yes'){

	if (!empty($tabcheckvir)) {
		foreach ($tabcheckvir as $key => $value) {
			// Créer virement
			
			if (!empty($payout->data)) {
				foreach ($payout->data as $payoutElem) 
					if ($payoutElem->id == $key) break;
				$ret =  CreerVirDol($payoutElem->id , $payoutElem);
			}//foreach payout->data
		} //foreach tabcheckvir
	}


	if (!empty($tabcheckfr)) {
		foreach ($tabcheckfr as $key => $value) {
			// Créer ecriture
			if (!empty($frais)) {
				foreach ($frais as $moisannee => $amount) {
					if ($moisannee == $key) {
						break;
					}
				}// foreach frais
				$ret =  CreerFrDol($key, $amount );
			}
		} // foreach tabcheckfr
	}
	
} // Fin CreerVirfr


$paramurl = 'datedeb='.$str_datedebsais.'&datedebmonth='.GETPOST( 'datedebmonth', 'int').'&datedebday='.GETPOST('datedebday', 'int').'&datedebyear='.GETPOST('datedebyear', 'int') ;
$paramurl .= '&datefin='.$str_datefinsai.'&datefinmonth='.GETPOST('datefinmonth', 'int').'&datefinday='.GETPOST('datefinday', 'int').'&datefinyear='.GETPOST('datefinyear', 'int');

// protection afin de ne pas relancer deux fois le programme
if ($flMAJBase == true) {
	Header('Location: '  . $_SERVER ['PHP_SELF'].$paramurl);
	exit();
} 


/*
 * AFFICHAGE
 */

/* Affichage Cadre Dolibarr */
llxHeader('', $langs->trans(""));



/* ACTION */
/* Demande Confirmation Creation d'ecritures */


if (!empty(GETPOST('creer')) and empty(GETPOST("confirm",'alpha' )) and (!empty($tabcheckfr) or !empty($tabcheckvir))) 
{
	if (count($tabcheckvir) > 1) $s1 = "s"; else $s1 ="";
	if (count($tabcheckfr) > 1) $s2 = "s"; else $s2 ="";
	$question = $langs->trans('QuestCreerVirmentFrasStripe', count($tabcheckvir), $s1, count($tabcheckfr), $s2);
	$titre = $langs->trans('TitCreerVirmentFrasStripe');

	$param =$paramurl.'&'.$wcglFctCom->TransfTabIdUrl($tabcheckfr, 'tabcheckfr');
	if (! empty($tabcheckvir)) $param .='&'.$wcglFctCom->TransfTabIdUrl($tabcheckvir, 'tabcheckvir');
	$url = $_SERVER['PHP_SELF'].'?'.$param ;
	$formconfirm=$form->formconfirm( $url,$langs->trans($titre),$question,'confirm_creervirfr','','',1);			
	print $formconfirm;
}

/* VIEW */
//print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
//	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="token" value="'.newToken().'">';
 
	$title = $langs->trans("StripeVirDOlibarr");
	$title .= ($stripeaccount ? ' (Stripe connection with Stripe OAuth Connect account '.$stripeacc.')' : ' (Stripe connection with keys from Stripe module setup)');

//	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_accountancy.png', 0, '', '', $limit);

/* entete*/
	$nom = $langs->trans("RapStripe");

	$description = $langs->trans("StripeVirDOlibarr");
	$description .= ($stripeaccount ? ' (Stripe connection with Stripe OAuth Connect account '.$stripeacc.')' : ' (Stripe connection with keys from Stripe module setup)');

	//$varlink = 'id_journal='.$id_journal;

AfficheSaisieDate($nom, $datedeb, $datefin,  $description,   $moreparam = array(), $varlink = '');
//print '</form>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';	

print '<input type="hidden" name="datedeb" value="'.$str_datedebsais.'">';
print '<input type="hidden" name="datefin" value="'.$str_datefinsai.'">';
print '<input type="hidden" name="datedebmonth" value="'.GETPOST( 'datedebmonth', 'int').'">';
print '<input type="hidden" name="datedebday" value="'.GETPOST( 'datedebday', 'int').'">';
print '<input type="hidden" name="datedebyear" value="'.GETPOST( 'datedebyear', 'int').'">';
print '<input type="hidden" name="datefinmonth" value="'.GETPOST( 'datefinmonth', 'int').'">';
print '<input type="hidden" name="datefinday" value="'.GETPOST( 'datefinday', 'int').'">';
print '<input type="hidden" name="datefinyear" value="'.GETPOST( 'datefinyear', 'int').'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

// VIREMENT	

//Afficher Bouton Creer Virement et ecriture frais
print '<div align="right"><input class="button" type="submit" name="creer" value="'.$langs->trans("CreerVir").'"></div><br>';

print_barre_liste($langs->trans("Virements Stripe" ), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_bank.png', '', $morehtml, '',$limit, 0, 0, 1);
		$moreforfilter='';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";
	

AfficheEnteteVir(false	);
print '<p>Pour inbfo, en attendant  la necesseite de creer une facture';	

// Affichage des virements Stripe dans l'ordre décroissant des dates
if (!empty($payout->data)) {
	foreach ($payout->data as $payoutElem) {
		// Recherche Ecriture Virement Dolibarr	
		$amount = 0; 		$rowid = 0;		$LibErrVir = '';
		$ret =  ExisteVirDol($payoutElem->id , $amount, $rowid, $LibErrVir);

		print '<tr class="oddeven">';
		AfficherLigneVir ($payoutElem->id, $payoutElem, $amount, $rowid, $libErrVir);
		print "</tr>\n";
	} // foreach
}

print "</table>";
			//FRAIS 

			print_barre_liste($langs->trans("Frais Stripe" ), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_bank.png', '', $morehtml, '',$limit, 0, 0, 1);
					$moreforfilter='';

			print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

			AfficheEnteteFrais(false);



			$frais = Lecture_DonneesFrais($stripe, $datedeb, $datefin, $stripeaccount ='');


			if (!empty($frais)) {
				foreach ($frais as $key => $Mtt) {
					// Mois des frais 

					$label = LibEcrFraisStripe($key);
					
					// recherche écriture frais
					$amount = 0;		$rowid = 0;		$LibErrFr = '';
					$ret =  ExisteFrDol($label , $amount, $rowid, $LibErrFr);
					
					print '<tr class="oddeven">';	
					AfficherLigneFrais ($label,  $Mtt, $amount, $rowid, $LibErrFr, $key);
					print "</tr>\n";
				} //Foreach
			}
			if (!empty($frais[$DerFrais])) {
				print '<p>recup derniere ligne';
			}


			print "</table>";

			//Afficher Bouton Creer Virement et ecriture frais
			print '<div align="right"><input class="button" type="submit" name="creer" value="'.$langs->trans("CreerVir").'"></div><br>';

			print '</div>';

print '</form>';

javascript();

// End of page
llxFooter();
$db->close();


/**
 *	sur le modèle de journalHead de core/lib/accounting.lib.php
 *	Affiche entete de page utilise lors de l'intégration des mouvents Stripe dans Dolibarr
 *
 *	@param	string				$nom            Name of report
 *	@param	string				$str_datedeb        Date de début du traitement
 *	@param	string				$str_datefin        Date de fin du traitement
 *	@param	string				$description     description of report
 
 *	@param	array				$moreparam		Array with list of params to add into form
 *  @param  string              $varlink        Add a variable into the address of the page
 *	@return	void
 */
 
function AfficheSaisieDate($nom, $datedeb, $datefin,  $description,   $moreparam = array(), $varlink = '')
{
	global $langs, $db;

	$formDol = new CglFonctionDolibarr ($db);
	print "\n\n<!-- debut entete Virement Stripe -->\n";

	if (!(empty($varlink))) {
		$varlink = '?'.$varlink;
	}

	$head = array();
	$h = 0;
	$head[$h][0] = $_SERVER["PHP_SELF"].$varlink;
	//$head[$h][1] = $langs->trans("IntegrationStripe");
	$head[$h][2] = 'strpevir';

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].$varlink.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print dol_get_fiche_head($head, 'StripeVir');

	if (!empty($moreparam)) {
		foreach ($moreparam as $key => $value) {
			print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
		} // foreach
	}
	print '<table class="border centpercent tableforfield">';

	// Ligne de titre
	print '<tr>';
	print '<td class="titlefieldcreate">'.$langs->trans("Name").'</td>';
	print '<td colspan="3">';
	print $nom;
	print '</td>';
	print '</tr>';

	// Ligne de la periode d'analyse du rapport
	print '<tr>';
	print '<td>'.$langs->trans("ReportPeriod").'</td>';
	if (!$periodlink) {
		print '<td colspan="3">';
	} else {
		print '<td>';
	}

	print $formDol->select_date($datedeb, 'datedeb', 0, 0, 0, '', 1, 0).'  -  '.$formDol->select_date($datefin, 'datefin', 0, 0, 0, '', 1, 0);

	if ($periodlink) {
		print '</td><td colspan="2">'.$periodlink;
	}
	print '</td>';
	print '</tr>';

	// Ligne de description
	print '<tr>';
	print '<td>'.$langs->trans("ReportDescription").'</td>';
	print '<td colspan="3">'.$description.'</td>';
	print '</tr>';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("Refresh").'"></div>';

	print '</form>';

	print "\n<!-- end entete Virement Stripe -->\n\n";

} //AfficheSaisieDate
function AfficherLigneVir ($id, $payoutElem, $MttEcr, $rowidEcr, $LibErrVir)
{	
	global $langs;
	// Ref Stripe
	if (!empty($stripeacc)) {
		$connect = $stripeacc.'/';
	}

	// Identifiant Stripe
	$url = 'https://dashboard.stripe.com/'.$connect.'test/payouts/'.$id;
	if ($servicestatus) {
		$url = 'https://dashboard.stripe.com/'.$connect.'payouts/'.$id;
	}
	print "<td><a href='".$url."' target='_stripe'>".img_picto($langs->trans('ShowInStripe'), 'globe')." ".$id."</a></td>\n";

	// Date payment
	print '<td class="center">'.dol_print_date($payoutElem->created, '%d/%m/%Y %H:%M')."</td>\n";

	// Date payment
	print '<td class="center">'.dol_print_date($payoutElem->arrival_date, '%d/%m/%Y %H:%M')."</td>\n";
	
	// Amount
	print '<td class="right">';
	print price($payoutElem->amount / 100, 0, '', 1, -1, -1, strtoupper($payoutElem->currency));
	print "</td>";

	// Status
	print "<td class='right'>";
	if ($payoutElem->status == 'paid') {
		print img_picto($langs->trans("".$payoutElem->status.""), 'statut4');
	} elseif ($payoutElem->status == 'pending') {
		print img_picto($langs->trans("".$payoutElem->status.""), 'statut7');
	} elseif ($payoutElem->status == 'in_transit') {
		print img_picto($langs->trans("".$payoutElem->status.""), 'statut7');
	} elseif ($payoutElem->status == 'failed') {
		print img_picto($langs->trans("".$payoutElem->status.""), 'statut7');
	} elseif ($payoutElem->status == 'canceled') {
		print img_picto($langs->trans("".$payoutElem->status.""), 'statut8');
	}
	
	// ExisteVirement Dolibarr
	print '<td class="center">';	
	if (!empty($rowidEcr)) 		 {
		if ($MttEcr <> (-1) * $payoutElem->amount/100 ) $color="style='color:Red'"; // Comparaison avec le débit du virement
		else $color='';
		$url = DOL_URL_ROOT.'/compta/bank/line.php?rowid='.$rowidEcr.'&save_lastsearch_values=1';;

		print "<a href='".$url." ".$text."'fas fa-receipt infobox-bank_account' ";
		$textTitre = $langs->trans('Mtt',price((-1)*$MttEcr , 0, '', 1, -1, -1, '€'));
		if (!empty($LibErrVir)) $textTitre .= $langs->trans("LibErrStripeDol",$LibErrVir);
		if (!empty($color))  $textTitre .= $langs->trans('MttDif');
		print "  title='".$textTitre ."' ".$color.">";
		print $rowidEcr;
		print " ".img_picto('','account')." ";
		print "</a>\n";

//			if (!empty( $rowid )) print info_admin($tabtexteEcrInf[$id], 1);
	}
	print "</td>";

	// CheckBox pour choix virement à créer
	print "<td>";
	if (empty($rowidEcr)) {
		if (isset ($tabcheckvir) and !empty($tabcheckvir)) 
			foreach ($tabcheckvir as $row) { 
			if ($row == $payoutElem->id) $flgcheked = true; else $flgcheked = false; 
		}// Foreach
		print '<input class="flat checkselection_" title = "'.$titre.'"name="tabcheckvir['.$payoutElem->id.']" type="checkbox"  value="'.$payoutElem->id.'" size="1"'.($flgcheked?' checked="checked"':'').'>';
	}
	print '</td>';
} // Afficher
function AfficherLigneFrais ($label,  $montantStp, $montantFr, $rowidFr, $libErrFr, $anneemois)
{	
	global $langs, $db;
	global $i;
	
	$i = 0;
	print '<td>'.$label.'</td>';
	// Amount
	print '<td class="right">';
	print price($montantStp , 0, '', 1, -1, -1, '€');
	print "</td>";
	// Ecritude Dolibarr
	print '<td class="center">';
	if (!empty($rowidFr)) 		 {
		if ((float)$montantStp - (-1)*(float)$montantFr < -0.001 or (float)$montantStp - (-1)*(float)$montantFr > 0.001) $color="style='color:Red'";
		else $color='';
		$url = DOL_URL_ROOT.'/compta/bank/line.php?rowid='.$rowidFr.'&save_lastsearch_values=1';;
		print "<a href='".$url." ".$text."'fas fa-receipt infobox-bank_account' ";
		$textTitre =  $langs->trans('Mtt',price($montantFr , 0, '', 1, -1, -1, '€'));
		if (!empty($LibErrVir)) $textTitre .= $langs->trans("LibErrStripeDol",$LibErrVir);
		if (!empty($color))  $textTitre .= $langs->trans('MttDif');
		print "  title='".$textTitre ."' ".$color.">";
		print $rowidFr;
		print " ".img_picto('','account')." ";
		print "</a>\n";
//			if (!empty( $rowid )) print info_admin($tabtexteEcrInf[$id], 1);
	}
	print "</td>";
	print "<td>";


if ('TOTO' == 'TITI') {
	if (empty($rowidFr)){
		if (isset ($tabcheckfr) and !empty($tabcheckfr)) 
			foreach ($tabcheckfr as $row) { 
				if ($row == $anneemois) $flgcheked = true; else $flgcheked = false; 
			}// Foreach
		print '<input class="flat checkselection_F" title = "'.$titre.'"name="tabcheckfr['.$anneemois.']" type="checkbox"  value="'.$montantStp.'" size="1" '.($flgcheked?' checked="checked"':'').'>';
	}
}
	print '</td>'; 
} // AfficherLigneFrais

function AfficheEnteteVir($flListeVide) 
{
	global $langs;
	
	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "", "", "", '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("DateOperation", $_SERVER["PHP_SELF"], "", "", "", '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("Paid", $_SERVER["PHP_SELF"], "", "", "", '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "", "", "", '', '', '', 'right ');
	print_liste_field_titre("EcritureDolibarr", $_SERVER["PHP_SELF"], "", "", "", '','', '', 'center ');
	print_liste_field_titre("", "", "", "", "", '','', '', '');


	if (!$flListeVide) {
		print "</tr>\n";
		print '<tr><td colspan=10 align=right><a href="#AncreLstDetail" id="checkall_'.$bid.'">';
		print $langs->trans("All").'</a> / <a href="#AncreLstDetail" id="checknone_'.$bid.'">'.$langs->trans("None").'</a></td></tr>';
		print "\n";
	}
}	//AfficheEnteteVir

function AfficheEnteteFrais($flListeVide) 
{
	global $langs;
	
	
	print '<tr class="liste_titre">';
	print_liste_field_titre("Mois", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("Montant",$_SERVER["PHP_SELF"],"","","","","", "");
	print_liste_field_titre("EcritureDolibarr", $_SERVER["PHP_SELF"], "", "", "", '','', '', 'center ');
	print_liste_field_titre("", "", "", "", "", '','', '', '');


	if (!$flListeVide) {
		print "</tr>\n";
		print '<tr><td colspan=10 align=right><a href="#AncreLstDetail" id="checkall_F">';
		print $langs->trans("All").'</a> / <a href="#AncreLstDetail" id="checknone_F">'.$langs->trans("None").'</a></td></tr>';
		print "\n";
	}
}	//AfficheEnteteFrais

function javascript()
{
	// Prépare le js pour mettre toutes les checkbox des virements  à Actif ou / Non Actif
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
	// Prépare le js pour mettre toutes les checkbox des frais  à Actif ou / Non Actif
		print '
        <script language="javascript" type="text/javascript">
        jQuery(document).ready(function()
        {
            jQuery("#checkall_F").click(function()
            {
                jQuery(".checkselection_F").prop(\'checked\', true);
            });
            jQuery("#checknone_F").click(function()
            {
                jQuery(".checkselection_F").prop(\'checked\', false);
            });
        });
        </script>
        ';
}	

function Lecture_DonneesVir($stripe, $datedeb, $datefin, $stripeaccount ='')
{
	global $conf, $langs;	
	
	/* recherche des info compte Stripe */
	$stripeacc = $stripe->getStripeAccount($service);
	if (empty($stripeaccount))
	{
		//setEventMessages( $langs->trans('ErrorStripeAccountNotDefined'), '', 'warning');
	}

	if (!empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'alpha'))) {
		$service = 'StripeTest';
		$servicestatus = '0';
		setEventMessage($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'),  'warning');
	} else {
		$service = 'StripeLive';
		$servicestatus = '1';
	}
	$payout = array();
	
	try {

		if ($stripeacc) {
			$payout = \Stripe\Payout::all(array("limit" => $conf->global->STRIPE_MAX_TRANSAC_RECUP, "created" => array("gt" => $datedeb, "lt" => $datefin)), array("stripe_account" => $stripeacc));
		} else {
			$payout = \Stripe\Payout::all(array("limit" => $conf->global->STRIPE_MAX_TRANSAC_RECUP, "created" => array("gt" => $datedeb, "lt" => $datefin)));
		}
	} catch (Exception $e) {
		print '<tr><td colspan="6">'.$langs->trans('ErreurRecupVirementStripe').$e->getMessage().'</td></td>';
	}



	return $payout;
} //Lecture_DonneesVir

function Lecture_DonneesFrais($stripe, $datedeb, $datefin, $stripeaccount ='')
{
	global $conf, $langs;
	/* recherche des info compte Stripe */
	$stripeacc = $stripe->getStripeAccount($service);
	if (empty($stripeaccount))
	{
		//setEventMessage( $langs->trans('ErrorStripeAccountNotDefined'),  'warning');
	}

	if (!empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'alpha'))) {
		$service = 'StripeTest';
		$servicestatus = '0';
		setEventMessage($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), 'warning');
	} else {
		$service = 'StripeLive';
		$servicestatus = '1';
	}
	
	
// Boucle afin de récuper toutes les op entre deux dates ($num 100 )	
	$listFrais = array();	
	$dt_deb = $datedeb;
	$dt_fin = $datefin;
	do {			
		$listOp = array();
			
		try {
			if ($stripeacc) {
				$listOp = \Stripe\BalanceTransaction::all(array("limit" => $conf->global->STRIPE_MAX_TRANSAC_RECUP, "type" => "charge", "created" => array("gt" => $dt_deb, "lt" => $dt_fin)), array("stripe_account" => $stripeacc));
			} else {
				$listOp = \Stripe\BalanceTransaction::all(array("limit" => $conf->global->STRIPE_MAX_TRANSAC_RECUP, "type" => "charge", "created" => array("gt" => $dt_deb, "lt" => $dt_fin)));
			}
		} catch (Exception $e) {
			$num_Op = 0;
			break;
		}
		
		if (!empty($listOp)) {
			$num_Op = count($listOp->data);
			if ($num_Op <> 0) {					
				foreach ($listOp->data as $Op) {
					// regroupement par mois					
					if (!isset($listFrais[dol_print_date($Op->created, "%Y%m")]) ) 
						$listFrais[dol_print_date($Op->created, "%Y%m")] = 0;
					$listFrais[dol_print_date($Op->created, "%Y%m")] += (float) $Op->fee/100;
				} // foreach
				$dt_fin =  $Op->created;
			}
		}	
	} while ($num_Op > 0);
	return $listFrais;
} //Lecture_DonneesFrais

/*
* Cherche si Virment déjà intégré dans Dolibarr
*	*
*	@param	string	$IdStripe	 Identifiant Stripe
*	@retour	flag  1 si trouve , <-100-nberr si erreur pour nb ecriture> 1, 0 si non trouvé, -2 erreur sql
*/
function ExisteVirDol($IdStripe, &$amount, &$rowid , &$libErrVir)
{
	global $conf, $db, $langs;
	
		$sql = "SELECT b.rowid, amount, bu.url_id ";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON  b.rowid = bu.fk_bank and bu.type = 'banktransfert'  ";
		$sql .= "WHERE b.label like '%" .$IdStripe ."%'";
		$sql .= " AND fk_account = ".$conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS;

		$rowid=''; $amount = 0;
        dol_syslog("StripeVir.php::ExisteVirDol ");
        $resql=$db->query($sql);
		if ($resql)
        {
             $num = $db->num_rows($resql);
            if ($num == 1 )
            {			
                $obj = $db->fetch_object($resql);
				if (!empty($obj->url_id) ) {
					$amount = $obj->amount;
				}
				else $libErrVir = 'Ce n"est pas un transfert';
				$rowid = $obj->rowid;
			}	
			elseif ($num == 0) 		{	
				//on n'a pas de virment correspondante dans Dolibarr
				$libErrVir='';
			}
			elseif ($num > 1){
				$libErrVir = $num.' virements';
			}
		}
		else {
			dol_syslog(":sql=".$sql, LOG_ERR);
			return -2;
		}	
		return 1;		
} //ExisteVirDol
function ExisteFrDol( $label, &$amount, &$rowid, &$libFrDol )
{
	global $conf, $db, $langs;
	
		$sql = "SELECT b.rowid, amount ";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b ";
		$sql .= "WHERE b.label like '%" .$label ."%'";
		$sql .= " AND fk_account = ".$conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS;

		$libFrDol = '' ; $amount = 0.00; 	$rowid='';
        dol_syslog("StripeVir.php::ExisteFraisDol");
        $resql=$db->query($sql);
		if ($resql)
        {
             $num = $db->num_rows($resql);
            if ($num == 1)
            {	
                $obj = $db->fetch_object($resql);		
				$amount = $obj->amount;	
				$rowid = $obj->rowid;
			}
			elseif ($num > 1) 		{	
				$libFrDol = $num.' ecritures de frais';
			}
		}
		else {
			dol_syslog(":sql=".$sql, LOG_ERR);
			return -2;
		}
		return 1;		
} //ExisteFraisDol


function LibEcrFraisStripe($id)
{
	global $langs;
	static $MoisFR = array();
	$MoisFR = array('',$langs->trans('Janvier'), $langs->trans('Février'),  $langs->trans('Mars'), $langs->trans('Avril'), $langs->trans('Mai'), $langs->trans('Juin'),
			$langs->trans('Juillet'), $langs->trans('Août'), $langs->trans('Septembre'), $langs->trans('Octocbre'), $langs->trans('Novembre'), $langs->trans('Décembre'));

	$mois = $id - ((int)($id/100))*100;
	$annee = ((int)($id/100));	
	$label ='Frais Stripe '.$MoisFR[(int)$mois].' '.$annee;

	return ($label);
} //LibEcrFraisStripe

function CreerFrDol ($anneemois, $amount)
{	
	global $conf, $db;

	$mois = $anneemois - ((int)($anneemois/100))*100;
	$annee = ((int)($anneemois/100));	
	$label = LibEcrFraisStripe($anneemois);
	
	$datep = $datev = dol_mktime(0, 0, 0, $mois, 28 ,$annee);
	$datec =  dol_now('tzuser');
	$accountid = $conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS;
	$paymenttype = 'PRE';
	$accountancy_code = $conf->global->STRIPE_ACCOUNT_POUR_FRAIS;

	$wcglavtDolrevue = new CglFonctionDolibarr ($db);
	$wcglavtDolrevue->creerFrais  ($datep, $datev, $accountid, $amount, $label, $paymenttype, $accountancy_code);
	unset($wcglavtDolrevue);
	
}//CreerFrDol

function CreerVirDol ($idStripe , $payoutElem)
{
	global $conf, $db;
	
	$wcglavtDolrevue = new CglFonctionDolibarr ($db);
	$wcglavtDolrevue->creervirement($payoutElem->created, $idStripe, $payoutElem->amount/100, $conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS, $conf->global->STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS) ;
	unset($wcglavtDolrevue);

}//CreerVirDol


