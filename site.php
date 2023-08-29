<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014 Florian Henry <florian.henry@open-concept.pro>
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15 et PHP7
 * Version CAV - 2.8.3 - printemps 2023
 *		- suppression des guillemets dans les textarea
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
 * \file cglinscription/site.php
 * \brief card of site
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('./class/site.class.php');
require_once ('../agefodd/lib/agefodd.lib.php');
require_once ('../agefodd/class/agsession.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once ('./class/cglinscription.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');


// Security check
// remplacer par droit cglinscription
//if (! $user->rights->agefodd->agefodd_place->lire)	accessforbidden();

$langs->load('agefodd@agefodd');
$langs->load('companies');
$langs->load('cglinscription@cglinsctription');

$action = GETPOST('action', 'alpha');
if ($action == 'setinfopublic') $action = 'update';
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$arch = GETPOST('arch', 'int');
$infopublic = GETPOST('infopublic', 'alpha');

$url_return = GETPOST('url_return', 'alpha');

$same_adress_customer = GETPOST('same_adress_customer', 'int');
global $conf;
/*
 * Actions delete
*/
//if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->agefodd_place->creer) {
if ($action == 'confirm_delete' && $confirm == "yes") {
	
	$agf = new Site($db);
	$agf->id = $id;
	$result = $agf->remove($user);
	
	if ($result > 0) {
		Header("Location: list.php");
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * Actions archive/active
*/
//if ($action == 'arch_confirm_delete' && $user->rights->agefodd->agefodd_place->creer) {
if ($action == 'arch_confirm_delete' ) {
	if ($confirm == "yes") {
		$agf = new Site($db);
		
		$result = $agf->fetch($id);
		
		$agf->archive = $arch;
		$result = $agf->update($user);
		
		if ($result > 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action update (Location)
*/
//if ($action == 'update' && $user->rights->agefodd->agefodd_place->creer) {
if ($action == 'update' ) {
	
	$error = 0;
		
	if (! $_POST["cancel"] && ! $_POST["importadress"]) {
		$agf = new Site($db);
			
		$societe = GETPOST('societe', 'int');
		if (empty($societe)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->trans('Company')), 'errors');
		}
		
		$label = GETPOST('ref_interne', 'alpha');
		if (empty($societe)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->trans('AgfSessPlaceCode')), 'errors');
			$error ++;
		}
		
		$result = $agf->fetch($id);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
			$error ++;
		}
		
		if (empty($error)) {
			$agf->ref_interne = $label;
			$agf->adresse = GETPOST('adresse', 'alpha');
			$agf->cp = GETPOST('zipcode', 'alpha');
			$agf->ville = GETPOST('town', 'alpha');
			$agf->fk_pays = GETPOST('country_id', 'int');
			$agf->tel = GETPOST('phone', 'alpha');
			$agf->fk_societe = $societe;
			$agf->notes = GETPOST('notes', 'alpha');
			$agf->acces_site = GETPOST('acces_site', 'alpha');
			$agf->note1 = GETPOST('note1', 'alpha');
			$agf->rdvPrinc = GETPOST('rdvPrinc', 'alpha');
			$agf->rdvAlter = GETPOST('rdvAlter', 'alpha');
			$agf->infopublic = GETPOST('infopublic', 'alpha');
			$agf->fic_infos = GETPOST('fic_infos', 'alpha');
			$agf->url_loc = GETPOST('url_loc', 'alpha');
			$result = $agf->update($user);
			
			if ($result > 0) {
				Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
				exit();
			} else {
				setEventMessage($agf->error, 'errors');
				$action = 'edit';
			}
		}
	} elseif (! $_POST["cancel"] && $_POST["importadress"]) {
		
		$agf = new Site($db);
		
		$result = $agf->fetch($id);
		$result = $agf->import_customer_adress($user);
		
		if ($result > 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action create (Location)
*/

//if ($action == 'create_confirm' && $user->rights->agefodd->agefodd_place->creer) {
if ($action == 'create_confirm') {
	
	$error = 0;
	
	if (! $_POST["cancel"]) {
		$agf = new Site($db);
		
		$societe = GETPOST('societe', 'int');
		if (empty($societe)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->trans('Company')), 'errors');
			$error ++;
		}
		
		$label = GETPOST('ref_interne', 'alpha');
		if (empty($societe)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->trans('AgfSessPlaceCode')), 'errors');
			$error ++;
		}
		
		if (empty($error)) {
			
			$agf->ref_interne = $label;
			$agf->fk_societe = $societe;
			$agf->notes = GETPOST('notes', 'alpha');
			$agf->acces_site = GETPOST('acces_site', 'alpha');
			$agf->note1 = GETPOST('note1', 'alpha');
			$agf->rdvPrinc = GETPOST('rdvPrinc', 'alpha');
			$agf->rdvAlter = GETPOST('rdvAlter', 'alpha');
			$agf->infopublic = GETPOST('infopublic', 'alpha');	
			$agf->fic_infos = GETPOST('fic_infos', 'alpha');
			$agf->url_loc = GETPOST('url_loc', 'alpha');
			
			if ($same_adress_customer == - 1) {
				$agf->adresse = GETPOST('adresse', 'alpha');
				$agf->cp = GETPOST('zipcode', 'alpha');
				$agf->ville = GETPOST('town', 'alpha');
				$agf->fk_pays = GETPOST('country_id', 'int');
				$agf->tel = GETPOST('phone', 'alpha');
				
				
			}
			$result = $agf->create($user);
			$idplace = $result;
			
			if ($result > 0) {
				if ($same_adress_customer == 1) {
					$result = $agf->fetch($idplace);
					$result = $agf->import_customer_adress($user);
					if ($result < 0) {
						setEventMessage($agf->error, 'errors');
						$error ++;
					}
				}
				
				if (empty($error)) {
					if ($url_return) {
						if (preg_match('/session\/card.php\?action=create$/', $url_return)) {
							$url_return .= '&place=' . $idplace;
							Header("Location: " . $url_return);
						} else {
							Header("Location: " . $url_return);
						}
					} else {
						Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $idplace);
					}
					exit();
				}
			} else {
				setEventMessage($agf->error, 'errors');
			}
		} else {
			Header("Location: listesite.php");
			exit();
		}
	}
}

/*
 * View
*/

$title = ($action == 'create' ? $langs->trans("AgfCreatePlace") : $langs->trans("AgfSessPlace"));
llxHeader('', $title);



// Gestion de la suppression des double guillements
print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/custom/cglavt/core/js/lib_filtre_car_saisie.js"></script>'."\n";
global $event_filtre_car_saisie;
$event_filtre_car_saisie = '';
$event_filtre_car_saisie .= " onchange='remplaceToutGuillements(this, event);return false;'";
// On privilégie le travail lors de la fin de la saisie, pour récupéré les copier/coller, plutot que le changement imédiat sur l'écran, pour lisibilité
//$event_filtre_car_saisie .= " onkeypress='remplaceGuillements(this, event);return false;'";




$form = new Form($db);

/*
 * Action create
*/
//if ($action == 'create' && $user->rights->agefodd->agefodd_place->creer) {
if ($action == 'create' ) {
	
	if ($conf->use_javascript_ajax) {
		print "\n" . '<script type="text/javascript">
		$(document).ready(function () {
	
			$(".specific_adress").hide();
	
			$("input[type=radio][name=same_adress_customer]").change(function() {
				if($(this).val()==1) {
					$(".specific_adress").hide();
				}else {
					$(".specific_adress").show();
				}
			});
		});
		';
		print "\n" . "</script>\n";
	}
	
	$formcompany = new FormCompany($db);
	print_fiche_titre($langs->trans("AgfCreatePlace"));
	
	print '<form name="create" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="action" value="create_confirm">' . "\n";
	
	print '<input type="hidden" name="url_return" value="' . $url_return . '">' . "\n";
	print '<input type="hidden" name="token" value="' . newtoken() . '">' . "\n";
	
	print '<table class="border" width="100%">' . "\n";
	
	print '<tr><td width="20%"><span class="fieldrequired">' . $langs->trans("AgfSessPlaceCode") . '</span></td>';
	print '<td><input name="ref_interne" class="flat" size="50" value=""></td></tr>';
	
	print '<tr><td><span class="fieldrequired">' . $langs->trans("Company") . '</span></td>';
	print '<td>' . $form->select_company('', 'societe', '((s.client IN (1,2,3)) OR (s.fournisseur=1))', 1, 1, 0) . '</td></tr>';
	
	print '<tr><td>' . $langs->trans('AgfImportCustomerAdress') . '</td><td>';
	print '<input type="radio" id="same_adress_customer_yes" name="same_adress_customer" value="1" checked="checked"/> <label for="same_adress_customer_yes">' . $langs->trans('Yes') . '</label>';
	print '<input type="radio" id="same_adress_customer_no" name="same_adress_customer" value="-1"/> <label for="same_adress_customer_no">' . $langs->trans('no') . '</label>';
	print '</td></tr>';
	
	print '<tr class="specific_adress"><td>' . $langs->trans("Address") . '</td>';
	print '<td><input name="adresse" class="flat" size="50" value="' . GETPOST('adresse', 'alpha') . '"></td></tr>';
	
	print '<tr class="specific_adress"><td>' . $langs->trans('Zip') . '</td><td>';
	print $formcompany->select_ziptown(GETPOST('zipcode', 'alpha'), 'zipcode', array (
			'town',
			'selectcountry_id' 
	), 6) . '</tr>';
	print '<tr class="specific_adress"><td>' . $langs->trans('Town') . '</td><td>';
	print $formcompany->select_ziptown(GETPOST('town', 'alpha'), 'town', array (
			'zipcode',
			'selectcountry_id' 
	)) . '</td></tr>';
	
	print '<tr class="specific_adress"><td>' . $langs->trans("Country") . '</td>';
	print '<td>' . $form->select_country(GETPOST('country_id', 'int'), 'country_id') . '</td></tr>';
	
	print '<tr class="specific_adress"><td>' . $langs->trans("Phone") . '</td>';
	print '<td><input name="phone" class="flat" size="50" value="' . GETPOST('phone', 'alpha') . '"></td></tr>';
	
	print '<tr><td valign="top">' . $langs->trans("AgfNote") . '</td>';
	print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' ></textarea></td></tr>';
	
	print '<tr><td valign="top">' . $langs->trans("AgfAccesSite") . '</td>';
	print '<td><textarea name="acces_site" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' ></textarea></td></tr>';
	
	print '<tr><td valign="top">' . $langs->trans("AgfPlaceNote1") . '</td>';
	print '<td><textarea name="note1" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' ></textarea></td></tr>';


		
	print '<tr><td valign="top">' . $langs->trans("RdvPrincipal") . '</td>';
	print '<td><textarea name="rdvPrinc" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' ></textarea></td></tr>';

	print '<tr><td valign="top">' . $langs->trans("AlterRdv") . '</td>';
	print '<td><textarea name="rdvAlter" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' ></textarea></td></tr>';	


//	print '<tr><td valign="top">' . $langs->trans("InfoPublic") . '</td>';	
//	print '<td>';		
//	$wfcom = new CglFonctionCommune ($db);
//	$conf->fckeditor->enabled = 1;
//	$wfcom->Affiche_zone_texte('infopublic', '', '', "95%", '', true);
//	unset($wfcom);
//	print '</td></tr>';
		
	print '<tr><td valign="top">' . $langs->trans("Url_Loc") . '</td>';
	print '<td><input name="url_loc" class="flat" size="50" "></td></tr>';
	
	print '<tr><td valign="top">' . $langs->trans("Fic_infos") . '</td>';
	print '<td><input name="fic_infos" class="flat" size="50" "></td></tr>';
	
	print '</table>';
	print '</div>';
	
	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" name="importadress" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
	print '</td></tr>';
	print '</table>';
	print '</form>';
} 
else {
	// Card
	if ($id) {
		$agf = new Site($db);
		$result = $agf->fetch($id);
		
		if ($result > 0) {
			$head = site_prepare_head($agf);
			
			$soc = New Societe($db);
			$retsoc = $soc->fetch($agf->fk_soc);
			dol_fiche_head($head, 'card', $langs->trans("AgfSessPlace"), 0, 'address');
			
			// Card in edit mode
			if ($action == 'edit') {
				
				$formcompany = new FormCompany($db);
				
				print '<form name="update" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
				print '<input type="hidden" name="action" value="update">' . "\n";
				print '<input type="hidden" name="id" value="' . $id . '">' . "\n";
				print '<input type="hidden" name="token" value="' . newtoken() . '">' . "\n";
				
				print '<table class="border" width="100%">' . "\n";
				print '<tr><td width="20%">' . $langs->trans("Id") . '</td>';
				print '<td>' . $agf->id . '</td></tr>';
				
				print '<tr><td>' . $langs->trans("AgfSessPlaceCode") . '</td>';
				print '<td><input name="ref_interne" class="flat" size="50" value="' . $agf->ref_interne . '"></td></tr>';
				
				print '<tr><td class="fieldrequired">' . $langs->trans("Company") . '</td>';
				print '<td>' . $form->select_company($agf->socid, 'societe', '((s.client IN (1,2,3)) OR (s.fournisseur=1))', 0, 1) . '</td></tr>';
				
				print '<tr><td>' . $langs->trans("Address") . '</td>';
				print '<td><input name="adresse" class="flat" size="50" value="' . $agf->adresse . '"></td></tr>';
				
				print '<tr><td>' . $langs->trans('Zip') . '</td><td>';
				print $formcompany->select_ziptown($agf->cp, 'zipcode', array (
						'town',
						'selectcountry_id' 
				), 6) . '</tr>';
				print '<tr></td><td>' . $langs->trans('Town') . '</td><td>';
				print $formcompany->select_ziptown($agf->ville, 'town', array (
						'zipcode',
						'selectcountry_id' 
				)) . '</tr>';
				
				print '<tr><td>' . $langs->trans("Country") . '</td>';
				print '<td>' . $form->select_country($agf->country, 'country_id') . '</td></tr>';
				
				print '<tr><td>' . $langs->trans("Phone") . '</td>';
				print '<td><input name="phone" class="flat" size="50" value="' . $agf->tel . '"></td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("AgfNote") . '</td>';
				print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' >' . $agf->notes . '</textarea></td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("AgfAccesSite") . '</td>';
				print '<td><textarea name="acces_site" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' >' . $agf->acces_site . '</textarea></td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("AgfPlaceNote1") . '</td>';
				print '<td><textarea name="note1" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' >' . $agf->note1 . '</textarea></td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("RdvPrincipal") . '</td>';
				print '<td><textarea name="rdvPrinc" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' >' . $agf->rdvPrinc . '</textarea></td></tr>';
				
				
				print '<tr><td valign="top">' . $langs->trans("AlterRdv") . '</td>';
				print '<td><textarea name="rdvAlter" rows="3" cols="0" class="flat" style="width:360px;" '.$event_filtre_car_saisie.' >' . $agf->rdvAlter . '</textarea></td></tr>';		

/*				print '<tr><td valign="top">' . $langs->trans("InfoPublic") . '</td>';		
				print '<td>';	;
				$conf->fckeditor->enabled = 1;
				$wfcom = new CglFonctionCommune ($db);
				$wfcom->Affiche_zone_texte('infopublic', $agf->infopublic, '', "95%", '', true);
				unset($wfcom);
				print '</td></tr>';
*/	
			
				print '<tr><td valign="top">' . $langs->trans("Url_Loc") . '</td>';
				print '<td><input name="url_loc" class="flat" size="50" value="' . $agf->url_loc . '"></td></tr>';
	
				print '<tr><td valign="top">' . $langs->trans("Fic_infos") . '</td>';
				print '<td><input name="fic_infos" class="flat" size="50" value="' . $agf->fic_infos . '"></td></tr>';
				
				print '</table>';
				//print '</div>';
				print '<table style=noborder align="right">';
				print '<tr><td align="center" colspan=2>';
				print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
				print '<input type="submit" name="importadress" class="butAction" value="' . $langs->trans("AgfImportCustomerAdress") . '"> &nbsp; ';
				print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
				print '</td></tr>';
				print '</table>';
				print '</form>';
				
				print '</div>' . "\n";
			} 
			else {
				// Display View mode
				
				/*
				 * Confirm delete
				*/
				if ($action == 'delete') {
					$ret = $form->form_confirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfDeletePlace"), $langs->trans("AgfConfirmDeletePlace"), "confirm_delete", '', '', 1);
					if ($ret == 'html')
						print '<br>';
				}
				/*
				 * Confirm archive
				*/
				if ($action == 'archive' || $action == 'active') {
					if ($action == 'archive')
						$value = 1;
					if ($action == 'active')
						$value = 0;
					
					$ret = $form->form_confirm($_SERVER['PHP_SELF'] . "?arch=" . $value . "&id=" . $id, $langs->trans("AgfFormationArchiveChange"), $langs->trans("AgfConfirmArchiveChange"), "arch_confirm_delete", '', '', 1);
					if ($ret == 'html')
						print '<br>';
				}
				
				print '<table class="border" width="100%">';
				
				print '<tr><td width="20%">' . $langs->trans("Id") . '</td>';
				print '<td>' . $form->showrefnav($agf, 'id	', '', 1, 'rowid', 'id') . '</td></tr>';
				
				print '<tr><td>' . $langs->trans("AgfSessPlaceCode") . '</td>';
				print '<td>' . $agf->ref_interne . '</td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("Company") . '</td><td>';
				if ($agf->socid) {
					print '<a href="' . DOL_MAIN_URL_ROOT . '/comm/card.php?socid=' . $agf->socid . '">';
					print img_object($langs->trans("ShowCompany"), "company") . ' ' . dol_trunc($agf->socname, 20) . '</a>';
				} else {
					print '&nbsp;';
				}
				print '</tr>';
				
				print '<tr><td rowspan=3 valign="top">' . $langs->trans("Address") . '</td>';
				print '<td>' . $agf->adresse . '</td></tr>';
				
				print '<tr>';
				print '<td>' . $agf->cp . ' - ' . $agf->ville . '</td></tr>';
				
				print '<tr>';
				print '<td>';
				$img = picto_from_langcode($agf->country_code);		
				if ($retsoc >= 0 and isInEEC($soc))
					print $form->textwithpicto(($img ? $img . ' ' : '') . $agf->country, $langs->trans("CountryIsInEEC"), 1, 0);
				else
					print ($img ? $img . ' ' : '') . $agf->country;
				print '</td></tr>';
				
				print '</td></tr>';
				
				print '<tr><td>' . $langs->trans("Phone") . '</td>';
				print '<td>' . dol_print_phone($agf->tel) . '</td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("AgfNotes") . '</td>';
				print '<td>' . nl2br($agf->notes) . '</td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("AgfAccesSite") . '</td>';
				print '<td>' . nl2br($agf->acces_site) . '</td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("AgfPlaceNote1") . '</td>';
				print '<td>' . nl2br($agf->note1) . '</td></tr>';
								
				print '<tr><td valign="top">' . $langs->trans("RdvPrincipal") . '</td>';
				print '<td>' . nl2br($agf->rdvPrinc) . '</td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("AlterRdv") . '</td>';
				print '<td>' . nl2br($agf->rdvAlter) . '</td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("InfoPublic") . '</td>';
				print '<td>' . $agf->infopublic . '</td></tr>';						
				
				print '<tr><td valign="top">' . $langs->trans("Fic_infos") . '</td>';
				print '<td>' . $agf->fic_infos . '</td></tr>';
				
				print '<tr><td valign="top">' . $langs->trans("Url_Loc") . '</td>';
				print '<td>' . $agf->url_loc . '</td></tr>';
				
				
				print "</table>";
				
				print '</div>';
			}
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}
}

/*
 * Actions tabs
*
*/

 print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && $action != 'nfcontact') {
	//if ($user->rights->agefodd->agefodd_place->creer) {
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&id=' . $id . '">' . $langs->trans('Modify') . '</a>';
	//} else {		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';	}
	//if ($user->rights->agefodd->agefodd_place->creer) {
		print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
	//} else {		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';	}
	//if ($user->rights->agefodd->agefodd_place->creer) {
		if ($agf->archive == 0) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=archive&id=' . $id . '">' . $langs->trans('AgfArchiver') . '</a>';
		} else {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=active&id=' . $id . '">' . $langs->trans('AgfActiver') . '</a>';
		}
	//} else {		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfArchiver') . '/' . $langs->trans('AgfActiver') . '</a>';	}
}

 print '</div>';

llxFooter();
$db->close();