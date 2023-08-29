<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014 Florian Henry <florian.henry@open-concept.pro>
 *
 *  Appropriation CglInscription 15/4/2017
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
 * \file agefodd/traineer/card.php
 * \ingroup agefodd
 * \brief card of traineer
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../agefodd/class/agefodd_formateur.class.php');
require_once ('../agefodd/class/html.formagefodd.class.php');
require_once ('../agefodd/lib/agefodd.lib.php');
require_once ('../agefodd/class/agsession.class.php');
require_once ('../agefodd/class/agefodd_session_formateur.class.php');
require_once ('class/html.formdepart.class.php');
require_once("class/cgldepart.class.php");
require_once ('../cglavt/class/cglFctDolibarrRevues.class.php');
require_once ('class/html.formcommun.class.php');
require_once('../cglavt/class/html.cglFctCommune.class.php');

$action = GETPOST('action', 'alpha');
if (empty($action)) $action = 'affiche';
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$arch = GETPOST('arch', 'int');


// Security check
// MODIF CCA 26/1/17 pour supprimer agefodd
//if (! $user->rights->agefodd->agefodd->lire)	accessforbidden();
// Fin Modif cca

$agf = new Agefodd_teacher($db);
if (!empty($id)) 	$agf->fetch($id);



/*
 * Actions delete
*/
// MODIF CCA 26/1/17 pour supprimer agefodd
//if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer) {
if ($action == 'confirm_delete' && $confirm == "yes" ) {
// Fin Modif cca
	$result = $agf->remove($id);
	
	if ($result > 0) {
		Header("Location: ".dol_buildpath('/agefodd/trainer/list.php', 1));
		exit();
	} else {
		setEventMessage($langs->trans("AgfDeleteFormErr") . ':' . $agf->error, 'errors');
	}
}
//var_dump ($_GET);
/*
* action de sauvegarde*/
if ($action ==  'save') {
	$agf->ventilation_vente =GETPOST('ventilation', 'alpha');
	$agf->fk_socpeople =  GETPOST('cid', 'int');
	$agf->fk_user =GETPOST('fk_user', 'int');
	$agf->cost_trainer = GETPOST('MtFixe');
	$agf->cost_trip =GETPOST('Pourcent');
	$agf->date_nego =GETPOST('DateNego', 'time');
	$agf->fk_soc =GETPOST('tiers', 'int');
		if (empty($agf->ventilation_vente) or $agf->ventilation_vente == -1) {
			setEventMessage ( 'Code Ventilation obligatoire', 'errors' );
			if (empty( $id) ) $action = 'create';
			else $action = 'affiche';
	}
	elseif ((empty($agf->fk_socpeople) or $agf->fk_socpeople == -1) and (empty($agf->fk_user) or $agf->fk_user == -1)) {
		setEventMessage ( 'Le contact est obligatorie', 'errors' );
		if (empty( $id) ) $action = 'create';
		else $action = 'affiche';
	}
	else {
			
		if ($agf->fk_socpeople>0) $agf->type_trainer = $agf->type_trainer_def[1];
		if ($agf->fk_user>0) $agf->type_trainer = $agf->type_trainer_def[0];
		if (empty( $id) ) {
			$agf->entity = $conf->entity;
			$agf->archive = 0;
			$agf->fk_user_author = $user->id;
			$agf->fk_user_mod = $user->id;
			$ret = $agf->create($user,  0);
			if ($ret < 0) {	
				setEventMessage($agf->error, 'errors');  
			}
			//$id = $ret;	
			$id = $agf->id;	
			Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $id);
			exit(0);
		}
		else {
			$agf->fk_user_mod = $user->id;
			$ret = $agf->update($user,  0);	
		}
		if ($ret > 0) $agf->fetch($id);	
		$action = 'affiche';
	}
}
/*
 * Actions archive/active
*/
// MODIF CCA 26/1/17 pour supprimer agefodd
//if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer && $confirm == "yes") {
if ($action == 'arch_confirm_delete' && $confirm == "yes") {
// Fin Modif cca
	$agf->archive = $arch;
	$result = $agf->update($user);
	
	if ($result > 0) {
		Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $result);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * Action create from contact (card trainer : CARREFULL, Dolibarr contact must exists)
*/
// MODIF CCA 26/1/17 pour supprimer agefodd
//if ($action == 'create_confirm_contact' && $user->rights->agefodd->creer) {
if ($action == 'create_confirm_contact') {
// Fin Modif cca

	if (! $_POST ["cancel"]) {
		$agf = new Agefodd_teacher($db);
		
		$agf->spid = GETPOST('spid');
		$agf->type_trainer = $agf->type_trainer_def [1];
		$result = $agf->create($user);
		
		if ($result > 0) {
			Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $result);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: list.php");
		exit();
	}
}

/*
 * Action create from users (card trainer : CARREFULL, Dolibarr users must exists)
*/

// MODIF CCA 26/1/17 pour supprimer agefodd
//if ($action == 'create_confirm_user' && $user->rights->agefodd->creer) {
if ($action == 'create_confirm_user') {
// Fin Modif cca
	if (! $_POST ["cancel"]) {
		$agf = new Agefodd_teacher($db);
		
		$agf->fk_user = GETPOST('fk_user', 'int');
		$agf->type_trainer = $agf->type_trainer_def [0];
		$result = $agf->create($user);
		
		if ($result > 0) {
			Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $result);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: ../agefodd/trainer/list.php");
		exit();
	}
}

/*
 * View
*/
$title = ($action == 'create' ? $langs->trans("AgfFormateurAdd") : $langs->trans("AgfTeacher"));
llxHeader('', $title);

$formAgefodd = new FormAgefodd($db);

/*
 * Action create
*/

// MODIF CCA 26/1/17 pour supprimer agefodd
//if ($action == 'create' && $user->rights->agefodd->creer) {
if ($action == 'create' ) {
// Fin Modif cca
	Ecran($agf, $action );
	
} 
else {
	// Display trainer card
	if ($id) {
		if ($result) {
				
			// View mode
			
			$head = trainer_prepare_head($agf);			
			dol_fiche_head($head, 'card', $langs->trans("AgfTeacher"), 0, 'user');
			
			/*
			 * Delete confirm
			*/
			if ($action == 'delete') {
				$ret = $form->form_confirm($_SERVER ['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfDeleteTeacher"), $langs->trans("AgfConfirmDeleteTeacher"), "confirm_delete", '', '', 1);
				if ($ret == 'html')
					print '<br>';
			}		
			/*
			 * Confirm archive status change
			*/
			if ($action == 'archive' || $action == 'active') {
				if ($action == 'archive')
					$value = 1;
				if ($action == 'active')
					$value = 0;
				
				$ret = $form->form_confirm($_SERVER ['PHP_SELF'] . "?arch=" . $value . "&id=" . $id, $langs->trans("AgfFormationArchiveChange"), $langs->trans("AgfConfirmArchiveChange"), "arch_confirm_delete", '', '', 1);
				if ($ret == 'html')
					print '<br>';
			}
			Ecran ($agf, $action);
			
			//print "</table>";
			
			//print '</div>';
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}
}

/*
 * Actions tabs
*
*/
Boutons($agf, $action);


llxFooter();
$db->close();


function Ecran($agf, $action)
{
	global $langs, $db, $id;
	
	$form = new Form($db);
	$wf = new FormCglCommun ($db);
	$wfctcomm = new FormCglFonctionCommune($db);		
		print '<form name="create_contact" action="' . $_SERVER ['PHP_SELF'] . '" method="GET">' . "\n";
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
		print '<input type="hidden" name="action" value="save">' . "\n";
		print '<input type="hidden" name="id" value="'.$id.'">' . "\n";

	if ($action == 'create' ) $texte = $langs->trans("AgfFormateurAdd");
	elseif ($action == 'edit' )  $texte =$langs->trans("AgfFormateurEdit");
	else  $texte =$langs->trans("AgfFormateurAff");
	$wfctcomm->AfficheParagraphe($texte, 2);
	// Nom
	if ('Nom' == 'Nom') {
	print '<table class="border" width="100%">';
		if ($action != 'create') {
			print '<tr><td width="20%" colspan=2>' . $langs->trans("Ref") . '</td>';
			print '<td>' . $agf->id ;
			print '</td></tr>';
			print '<tr><td  colspan=2>' . $langs->trans("Name") . '</td>';
			print '<td>' . ucfirst(strtolower($agf->civilite)) . ' ' . strtoupper($agf->name) . ' ' . ucfirst(strtolower($agf->firstname));
		}
	}
	
	// Association avec Tiers
	if ('Tiers' == 'Tiers') {
		print '<tr><td colspan = 2>' . $langs->trans("AgfTiers") . '</td>';
		print '<td>';
		// Icone pour aller à Tiers
		if ($action != 'affiche') {
			//$form->select_societe(0, '', 'spid', 1,  '', 1, '', 1);
			$w = new CglFonctionDolibarr($db);
			print  $w->select_company($agf->fk_soc,'tiers','fournisseur=1',0, 0, 0,'', 1);
			unset ($w);
		}
		else print $agf->nomtiers;
		print '</td></tr>';
	}	
	// Association avec Contact et/ou user
	if ('Contact' =='Contact') {
	print '<tr><td colspan=5>';
		if ($action != 'affiche') {
			print '<div class="warning">' . $langs->trans("AgfFormateurAddContactHelp");
			print '<br>OU<br>' ;
			print $langs->trans("AgfFormateurAddUserHelp");
			print '</div>';
		}
	
		print '</td></tr>';
		print '<tr><td colspan=2>' . $langs->trans("AgfContactUn") . '</td>';
		print '<td>';
		if ($action != 'affiche') {
// Icone pour aller à Contacts		
			$form->select_contacts($agf->fk_soc, $agf->fk_socpeople,  'cid', 1,  '', 2, '', '',0,0);
		}
		else {
			print $agf->contact;
		}
		print '</td></tr>';
		
		if ($action != 'affiche') {
			print '<tr><td colspan=2>' . $langs->trans("AgfUserUn") . '</td>';
			print '<td>';
			
			$agf_static = new Agefodd_teacher($db);
			$agf_static->fetch_all('ASC', 's.lastname, s.firstname', '', 0);
			$exclude_array = array ();
			if (is_array($agf_static->lines) && count($agf_static->lines) > 0) {
				foreach ( $agf_static->lines as $line ) {
					if ((! empty($line->fk_user)) && (! in_array($line->fk_user, $exclude_array))) {
						$exclude_array [] = $line->fk_user;
					}
				}
			}
	// Icone pour aller à User
			$form->select_users('', 'fk_user', 1, $exclude_array);
			print '</td></tr>';
		}
	}	
	print '</td></tr><tr><td>';
	print '</td></tr><tr><td>';
	print '</td></tr><tr>';
	$wfctcomm->AfficheParagraphe('Comptabilité budgétaire', 2);
	print '</td></tr><tr><td colspan=2>';
	
	// Code Ventilation 
	if ('Ventilation' == 'Ventilation') {
		print  $langs->trans("LbCdVentil") ;
		print	'</td><td >'; 
		if ($action != 'affiche') {
			$depart = new FormCglDepart ($db);
			print $depart->select_Ventil ( $agf->ventilation_vente, 'ventilation', '',2 );	
			unset ($depart);
		}
		else print $agf->ventilation_vente;
	}
	print '</td></tr><tr>';	
	$wfctcomm->AfficheParagraphe($langs->trans('NegActuelle'), 2);	
	print '</td></tr><tr><td colspan=2>';
	print $langs->trans('LbCoutMoniteur');
	print '</td><td>';	
	if ($action != 'affiche') 
		print '<input class="flat" name="MtFixe" value="'.$agf->cost_trainer .'">';
	else  {
		print $agf->cost_trainer;
		if ($agf->cost_trainer and $agf->cost_trainer > 0 )
			print' €'; 
	}
	print '</td></tr><tr><td colspan=2>';
	print $langs->trans('LbPartMoniteur');	
	print '</td><td>';	
	if ($action != 'affiche') 
		print '<input class="flat" name="Pourcent" value="'.$agf->cost_trip .'">';
	else  {
		print $agf->cost_trip;
		if ($agf->cost_trip and $agf->cost_trip > 0 )
			print' %'; 
	}
	print '</td></tr><tr><td colspan=2>';
	print $langs->trans('LbdateNego');
	print '</td><td>';		
	if ($action != 'affiche') {
		if (empty($agf->date_nego)) $wtdate = dol_now('tzuser'); 
		else $wtdate = $agf->date_nego;
		$w1 = new CglFonctionDolibarr($db);
		print $w1->select_date($wtdate,'DateNego',0,0,1,"",1,1);
		unset ($w1);
	}
	else print dol_print_date($agf->date_nego, '%d/%m/%y');
	print '</td></tr><tr><td>';
	print '</table>';
		
	unset ($wf);
	
} // Ecran

function Boutons($agf, $action)
{
	global $langs, $id;

	print '<div class="tabsAction">';

		if ($action != 'affiche') {
			//print '<a class="butActionDelete" href="' . $_SERVER ['PHP_SELF'] . '?action=save&id=' . $id . '">' . $langs->trans('Enregistrer') . '</a>';
			print '<input class="button" type="submit"  value="'.$langs->trans("Enregistrer").'">';
			print '</form>';
		} 
		else print '<a class="butActionDelete" href="' . $_SERVER ['PHP_SELF'] . '?action=edit&id=' . $id . '">' . $langs->trans('Modifier') . '</a>';
		if ($action != 'create') print '<a class="butActionDelete" href="' . $_SERVER ['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';

		if ($action != 'create') {
			if ($agf->archive == 0) {
				print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?action=archive&id=' . $id . '">' . $langs->trans('AgfArchiver') . '</a>';
			} elseif ($agf->archive == 1) {
				print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?action=active&id=' . $id . '">' . $langs->trans('AgfActiver') . '</a>';		}
		}
	print '</div>';
}// Boutons