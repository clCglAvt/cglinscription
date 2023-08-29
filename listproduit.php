<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014  Florian Henry <florian.henry@open-concept.pro>
 *
 * MODIF CCA 26/1/17 pour supprimer agefodd, comme module, il faut remplacer les froits par ceux de cglinscription
 * Version CAV - 2.7 - été 2022
 *					 - Remplacer method="GET" par method="POST"
 *					 - Migration Dolibarr V15 et PHP7
 * Version CAV - 2.8 - hiver 2023 - Pagination (suppression Ajout)
 * 								  - vérification de la fiabilité des foreach
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/training/list.php
 * \ingroup agefodd
 * \brief list of training
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../agefodd/class/agefodd_formation_catalogue.class.php');
require_once ('../agefodd/class/html.formagefodd.class.php');

require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');

// Security check
// MODIF CCA 26/1/17 pour supprimer agefodd
//if (! $user->rights->agefodd->agefodd_formation_catalogue->lire)	accessforbidden();
// Fin Modif cca

$langs->load('agefodd@agefodd');

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$arch = GETPOST('arch', 'int');
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "c.rowid";

if ($page == - 1 or empty($page)) {
	$page = 0;
}

$offset = $limit * (int)$page;
$pageprev = (int)$page - 1;
$pagenext = (int)$page + 1;

if (empty($arch))
	$arch = 0;
	
	// Search criteria
$search_intitule = GETPOST("search_intitule", 'alpha');
$search_ref = GETPOST("search_ref", 'alpha');
//$search_ref_interne = GETPOST("search_ref_interne");
$search_datec = dol_mktime(0, 0, 0, GETPOST('search_datecmonth', 'int'), GETPOST('search_datecday', 'int'), GETPOST('search_datecyear', 'int'));
$search_duree = GETPOST('search_duree', 'int');
// $search_dated = dol_mktime ( 0, 0, 0, GETPOST ( 'search_datedmonth', 'int' ), GETPOST ( 'search_datedday', 'int' ), GETPOST ( 'search_datedyear',
// 'int' ) );
$search_id = GETPOST('search_id', 'int');
//$search_categ = GETPOST('search_categ', 'int');
//if ($search_categ == - 1)
//	$search_categ = '';
	
	// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'alpha')) {
	$search_intitule = '';
	$search_ref = '';
	//$search_ref_interne = "";
	$search_datec = '';
	$search_duree = "";
	// $search_dated = "";
	$search_id = '';
	//$search_categ = '';
}


/* préparation de la pagination */
if ('pagination' == 'pagination')  
{

/*   $arrayofmassactions = array();
	$form=new Form($db);
    $massactionbutton = $form->selectMassAction('', $arrayofmassactions);
	unset ($form);
*/

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

/* a supprimer après V2.8
	$morehtml = '<div class="inline-block '.(($buttonreconcile || $newcardbutton) ? 'marginrightonly' : '').'">';
	$morehtml .= '<label for="pageplusone">'.$langs->trans("Page")."</label> "; // ' Page ';
	$morehtml .= '<input type="text" name="pageplusone" id="pageplusone" class="flat right width25 pageplusone" value="'.((int)$page + 1).'">';
	$morehtml .= '/'.$nbtotalofpages.' ';
	$morehtml .= '</div>';

	$morehtml .= '<!-- Add New button -->'.$newcardbutton;
*/
}

llxHeader('', $langs->trans('AgfMenuCat'));

$agf = new Agefodd($db);
$form = new Form($db);
$formagefodd = new FormAgefodd($db);

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element, true);

$filter = array ();
if (! empty($search_intitule)) {
	$filter ['c.intitule'] = $db->escape($search_intitule);
}
if (! empty($search_ref)) {
	$filter ['c.ref'] = $search_ref;
}
//if (! empty($search_ref_interne)) {
//	$filter ['c.ref_interne'] = $search_ref_interne;
//}
if (! empty($search_datec)) {
	$filter ['c.datec'] = $db->idate($search_datec);
}
if (! empty($search_duree)) {
	$filter ['c.duree'] = $search_duree;
}
if (! empty($search_id)) {
	$filter ['c.rowid'] = $search_id;
}
//if (! empty($search_categ)) {
//	$filter ['c.fk_c_category'] = $search_categ;
//}


$resql = $agf->fetch_all($sortorder, $sortfield, 90000, '', $arch, $filter );
$nbtotalofrecords = count($agf->lines);
unset ($agf);
$agf = new Agefodd($db);  
$resql = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $arch, $filter );
$num = count($agf->lines);

$params ='&arch=' . $arch;
$params .='&search_id=' . $search_id;
$params .='&search_duree=' . $search_duree;
$params .='&search_datec=' . $search_datec;
$params .='&search_ref=' . $search_ref;
$params .='&search_intitule=' . $search_intitule;
$params .='&amp;limit='.$limit;

print '<form method="POST" action="' . $url_form . '" name="search_form">' . "\n";
print '<input type="hidden" name="arch" value="' . $arch . '" >';
print '<input type="hidden" name="limit" value="'.$limit.'">';
print '<input type="hidden" name="token" value="'.newtoken().'">';
print '<tr class="liste_titre">';


$title=$langs->trans("AgfMenuCat");
print_barre_liste($title, $page, $_SERVER ['PHP_SELF'], $params, $sortfield, $sortorder, '', $num, $nbtotalofrecords, $picto, '', $morehtml, '', $limit, 0, 0, 0);


$i = 0;
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print_liste_field_titre("Id", $_SERVER ['PHP_SELF'], "c.rowid", "", '&arch=' . $arch, '', $sortfield, $sortorder);
print_liste_field_titre("AgfIntitule", $_SERVER ['PHP_SELF'], "c.intitule", "", '&arch=' . $arch, '', $sortfield, $sortorder);
print_liste_field_titre("Ref", $_SERVER ['PHP_SELF'], "c.ref", "", '&arch=' . $arch, '', $sortfield, $sortorder);
//print_liste_field_titre("AgfRefInterne", $_SERVER ['PHP_SELF'], "c.ref_interne", "", '&arch=' . $arch, '', $sortfield, $sortorder);
//print_liste_field_titre("AgfTrainingCateg", $_SERVER ['PHP_SELF'], "dictcat.code", "", '&arch=' . $arch, '', $sortfield, $sortorder);
print_liste_field_titre("AgfDateC", $_SERVER ['PHP_SELF'], "c.datec", "", '&arch=' . $arch, '', $sortfield, $sortorder);
print_liste_field_titre("AgfDuree", $_SERVER ['PHP_SELF'], "c.duree", "", '&arch=' . $arch, '', $sortfield, $sortorder);
print_liste_field_titre("AgfDateLastAction", $_SERVER ['PHP_SELF'], "a.dated", "", '&arch=' . $arch, '', $sortfield, $sortorder);
print_liste_field_titre("AgfNbreAction", $_SERVER ['PHP_SELF'], '', '&arch=' . $arch, '', $sortfield, $sortorder);
print "</tr>\n";


print "<tr class=\"liste_titre\">";
print '<td><input type="text" class="flat" name="search_id" value="' . $search_id . '" size="2"></td>';

print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_intitule" value="' . $search_intitule . '" size="20">';
print '</td>';

print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_ref" value="' . $search_ref . '" size="20">';
print '</td>';

/*print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_ref_interne" value="' . $search_ref_interne . '" size="20">';
print '</td>';

print '<td class="liste_titre">';
print $formagefodd->select_training_categ($search_categ, 'search_categ', 't.active=1');
print '</td>';
*/
print '<td class="liste_titre">';
print $form->select_date($search_datec, 'search_datec', 0, 0, 1, 'search_form');
print '</td>';

print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_duree" value="' . $search_duree . '" size="5">';
print '</td>';

print '<td class="liste_titre">';
print '</td>';

print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
print '&nbsp; ';
print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
print '</td>';

print "</tr>\n";
print '</form>';

$var = true;
if ($resql > 0) {
	foreach ( $agf->lines as $line ) {
		
		// Affichage tableau des formations
		$var = ! $var;
		print "<tr $bc[$var]>";
		print '<td><a href="fiche_produit.php?id=' . $line->rowid . '">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->rowid . '</a></td>';
		print '<td>' . stripslashes($line->intitule) . '</td>';
		print '<td>' . $line->ref . '</td>';
		//print '<td>' . $line->ref_interne . '</td>';
		//print '<td>' . $line->category_lib . '</td>';
		print '<td>' . dol_print_date($line->datec, 'daytext') . '</td>';
		print '<td>' . $line->duree . '</td>';
		print '<td>' . dol_print_date($line->lastsession, 'daytext') . '</td>';
		print '<td>' . $line->nbsession . '</td>';
		print "</tr>\n";
		
		$i ++;
	}
} else {
	setEventMessage($agf->error, 'errors');
}

print "</table>";

llxFooter();
$db->close();