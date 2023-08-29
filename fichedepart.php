<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014	Florian Henry	<florian.henry@open-concept.pro>
 * Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
 *
 * Version CAV - 2.7 - été 2022
 *					 	- Migration Dolibarr V15
 *						- reprise code de ventilation Moniteur en cas de création départ depuis inscription
 *						- fermeture d'une transaction pendante 
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file cglinscription/fichedepart.php issu de agefodd/session/card.php
 * \ingroup agefodd
 * \brief card of session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../agefodd/class/agsession.class.php');
//require_once ('../class/agefodd_sessadm.class.php');
//require_once ('../class/agefodd_session_admlevel.class.php');
//require_once ('../class/html.formagefodd.class.php');
//require_once ('../class/agefodd_session_calendrier.class.php');
//require_once ('../class/agefodd_calendrier.class.php');
//require_once ('../class/agefodd_session_formateur.class.php');
require_once (DOL_DOCUMENT_ROOT . '/custom/agefodd/class/agefodd_session_stagiaire.class.php');
//require_once ('../class/agefodd_session_element.class.php');
//require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../agefodd/lib/agefodd.lib.php');
require_once("../agefodd/class/agefodd_formation_catalogue.class.php");
//require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');
//require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
//require_once ('../class/agefodd_opca.class.php');
require_once ('./class/html.formdepart.class.php');
require_once ('./class/cgldepart.class.php');
require_once ('./class/cglinscription.class.php');
require_once ('./class/bulletin.class.php');
require_once ('./class/html.formcommun.class.php');
require_once (DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");

		global $place, $formation, $intitule_custo, $TypeSessionDep_Agf, $session_status, $DepartDate, $nb_place, $notes, $rdvprinc;
		global $PrixAdulte, $PrixEnfant, $PrixExclusif, $PrixGroupe, $DureeAct;
		global $code_ventil, $id_client;
		global $moniteur_id, $alterrdv, $HeureFin, $type_tva, $HeureDeb, $CRE_DEPART, $MAJ_DEPART, $ANUL_DEPART;
		global $ENR_DEPART;
		global $user, $conf, $bull, $langs, $id_depart; $action;

// Security check
//if (! $user->rights->agefodd->lire)	accessforbidden();
$action = GETPOST('action', 'alpha');
if (empty($action)) $action = 'edit';
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id_depart', 'int');

$id_depart = GETPOST('id_depart', 'int');
if (empty($id_depart)) {
	
	$id_depart = GETPOST('id', 'int');
	$id = $id_depart;
	$paramtotal = 'oui';
}
$arch = GETPOST('arch', 'int');
$ENR_DEPART = 'enregistrerdepart'; // en commeun avec cglinscription.php
$ANUL_DEPART = 'annulerdepart';
$MAJ_DEPART = 'maj';
if (GETPOST($MAJ_DEPART, 'alpha')) $action = 'edit';
if (empty($paramtotal)) $paramtotal = GETPOST('total', 'alpha');	
if (empty($paramtotal)) $paramtotal = 'non'; // s'affiche pas en présence du public, donc ne doit pas afficher les infos comptables et financière
if (GETPOST('type', 'alpha') == 'passe') $passe='&type=passe';

$agf = new Agsession($db);
$cgldep = new CglDepart($db);
$bull = new Bulletin ($db);
$form = new Form ($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element);
// ACTIONS
if ('ACTIONS' =='ACTIONS') {
	/*
	 * Actions delete session
	*/

	if ($action == 'confirm_annule' && $confirm == "yes") {
		//$agf = new Agsession($db);
		//$result = $agf->remove($id);
		$result = $cgldep->Annuler($id);
		if ($result >= 0) {
			//Header("Location: listedepart.php?total=".$paramtotal.$passe);
			//exit();
		} else {
			setEventMessage($langs->trans("AgfDeleteErr") . ':' . $result, 'errors');
			$action = 'edit';
		}


	}
	/*
	 * Actions archive/active
	*/

	if ($action == 'arch_confirm_delete' ) {
//	if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer) {
		if ($confirm == "yes") {
			//$agf = new Agsession($db);
			
			$result = $agf->fetch($id);
			$arch = GETPOST("arch", 'int');
			
			if (empty($arch)) {
				$agf->status = 1;
			} else {
				$agf->status = 4;
			}
			
			$result = $agf->updateArchive($user);
			
			if ($result > 0) {
				// If update are OK we delete related files
				foreach ( glob($conf->agefodd->dir_output . "/*_" . $id . "_*.pdf") as $filename ) {
					if (is_file($filename))
						unlink("$filename");
				}
				
				Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $id.'&total='.$total);
				exit();
			} else {
				setEventMessage($agf->error, 'errors');
			}
		} else {
			Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $id.'&total='.$total);
			exit();
		}
	}

	/*
	 * Action update (fiche session)
	*/
//if ($action ==  $ENR_DEPART  && $user->rights->agefodd->creer ) {
//if (GETPOST( $ENR_DEPART, 'alpha') and ! $_POST ["cancel"] ) {
if (GETPOST( $ENR_DEPART, 'alpha') and ! GETPOST("cancel", 'alpha') ) {
		$place = GETPOST('place', 'int');
		/*if (($place == - 1) || (empty($place))) {
			$error ++;
			setEventMessage($langs->trans('AgfPlaceMandatory'), 'errors');
		}
*/			
		$formation = GETPOST('formation','int');	
		if (($formation == - 1) || (empty($formation))) {
			$error ++;
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AgfFormIntitule")), 'errors');
		}			

		$intitule_custo = GETPOST('intitule_custo','alpha');
		$TypeSessionDep_Agf = GETPOST('TypeSessionDep_Agf','int');
		$session_status = GETPOST('session_status','int');
		$nb_place = GETPOST('nb_place','int');
		$notes = GETPOST('notes','alpha');
		$id_client = GETPOST('id_client', 'int');
		$rdvprinc = GETPOST('rdvprinc','alpha');
		$alterrdv = GETPOST('alterrdv','alpha');
		$PrixAdulte = GETPOST('PrixAdulte','decimal');
		$PrixEnfant = GETPOST('PrixEnfant','decimal');
		$PrixExclusif = GETPOST('PrixExclusif','decimal');
		$PrixGroupe = GETPOST('PrixGroupe','decimal');
		$DureeAct = GETPOST('DureeAct','decimal');					
		$code_ventil = GETPOST('code_ventil','alpha');
		$moniteur_id = GETPOST('moniteur_id','int');
		if ( $paramtotal <> 'oui' and !empty($moniteur_id))  {
			// Recherche du code de ventilation du moniteur
			$agsession =  new Agsession($this->db);
			CglDepart::RechInfoMont($moniteur_id, $agsession);
		}
		$type_tva = GETPOST('type_tva','int');	
		if ($type_tva == -1) $type_tva = '';	


		// Gestion des heures
		$DepartDate 	= GETPOST('HeureDeb','alpha'); // format JJ/MM/AAAA
		if (strlen(substr($DepartDate,6)) == 2) $DepartDate = substr($DepartDate,0,6).(int)'20'.substr($DepartDate,6,2);

		$HeureDeb = GETPOST('HeureDebhour','alpha').':'.GETPOST('HeureDebmin','alpha');
		$HeureFin = GETPOST('HeureFinhour','alpha').':'.GETPOST('HeureFinmin','alpha');
		$MtFixe = GETPOST('MtFixe','decimal');
		$Pourcent = GETPOST('Pourcent','decimal');
		$DateNego = GETPOST('DateNego','alpha');
		if (strlen(substr($DateNego,6)) == 2) $DateNego = substr($DateNego,0,6).(int)'20'.substr($DateNego,6,2);
		$ret = $cgldep->Maj_depart();		
		if ($ret < 0) $action = 'edit';
		else $action = '';	
		$id = $id_depart;	
//		exit ();		
	}

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes') {
		/*$clone_content = GETPOST ( 'clone_content' );
		print 'clone_content='.$clone_content;
		if (empty ( $clone_content )) {
			setEventMessage ( $langs->trans ( "NoCloneOptionsSpecified" ), 'errors' );
		} else {*/
		if ($agf->fetch($id) > 0) {
			$result = $agf->createFromClone($id, $hookmanager);

			if ($result > 0) {
				$db->commit;
				if (GETPOST('clone_calendar', 'alpha')) {
					// clone calendar information
					$calendrierstat = new Agefodd_sesscalendar($db);
					$calendrier = new Agefodd_sesscalendar($db);
					$calendrier->fetch_all($id);
					$blocNumber = count($calendrier->lines);
					if ($blocNumber > 0) {
						$old_date = 0;
						$duree = 0;
						for($i = 0; $i < $blocNumber; $i ++) {
							$calendrierstat->sessid = $result;
							$calendrierstat->date_session = $calendrier->lines [$i]->date_session;
							$calendrierstat->heured = $calendrier->lines [$i]->heured;
							$calendrierstat->heuref = $calendrier->lines [$i]->heuref;
							
							$result1 = $calendrierstat->create($user);
						}
					}
				}
				if (GETPOST('clone_trainee', 'alpha')) {
					// Clone trainee information
					$traineestat = new Agefodd_session_stagiaire($db);
					$session_trainee = new Agefodd_session_stagiaire($db);
					$session_trainee->fetch_stagiaire_per_session($id);
					$blocNumber = count($session_trainee->lines);
					if ($blocNumber > 0) {
						foreach ( $session_trainee->lines as $line ) {
							$traineestat->fk_session_agefodd = $result;
							$traineestat->fk_stagiaire = $line->id;
							$traineestat->fk_agefodd_stagiaire_type = $line->fk_agefodd_stagiaire_type;
							
							$result1 = $traineestat->create($user);
						}
					}
				}
				
				if (GETPOST('clone_trainer', 'alpha')) {
					// Clone trainer information
					$trainerstat = new Agefodd_session_formateur($db);
					$session_trainer = new Agefodd_session_formateur($db);
					$session_trainer->fetch_formateur_per_session($id);
					$blocNumber = count($session_trainer->lines);
					if ($blocNumber > 0) {
						foreach ( $session_trainer->lines as $line ) {
							$trainerstat->sessid = $result;
							$trainerstat->formid = $line->formid;
							
							$result1 = $trainerstat->create($user);
						}
					}
				}
				header("Location: " . $_SERVER ['PHP_SELF'] . '?id=' . $result.'&total='.$total);
				exit();
			} else {
				$db->rollback;
				setEventMessage($agf->error, 'errors');
				$action = '';
			}
		}
		// }
	}
}


/*
 * View
*/

llxHeader('', $langs->trans("AgfSessionDetail"), '', '', '', '', array (
		'/agefodd/includes/jquery/plugins/colorpicker/js/colorpicker.js',
		'/agefodd/includes/lib.js' 
), array (
		'/agefodd/includes/jquery/plugins/colorpicker/css/colorpicker.css',
		'/agefodd/includes/lib.js' 
));


// Gestion de la suppression des double guillements
print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/custom/cglavt/core/js/lib_filtre_car_saisie.js"></script>'."\n";
global $event_filtre_car_saisie;
$event_filtre_car_saisie = '';
$event_filtre_car_saisie .= " onchange='remplaceToutGuillements(this, event);return false;'";
// On privilégie le travail lors de la fin de la saisie, pour récupéré les copier/coller, plutot que le changement imédiat sur l'écran, pour lisibilité
//$event_filtre_car_saisie .= " onkeypress='remplaceGuillements(this, event);return false;'";




$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

/* Action Annulation */
	if (GETPOST ($ANUL_DEPART, 'alpha')) { 		
		$result = $agf->fetch($id);			

		/* Test si un bulletin a été facturé, le signaler avant confirmation*/
		if ( !empty($id_depart)) {
			$listBull = array();
			$depart = new CglDepart($db);
			$listBull = $depart->fetchbullbysession($id_depart);
		}

		if (!empty($listBull) and !empty($id_depart)) {
				$retgen=0;
				$texteBullFact = '<br>';
				foreach ($listBull as $bull) {
					if ($bull->regle >= $bull->BULL_FACTURE) 
					{	
						if (!empty($texteBullFact)) $texteBullFact.= '<br>';
						$texteBullFact.= 'Le bulletin '.$bull->ref . ' a déjà été facturé';
					}					
				}
			}

		if ($texteBullFact == '<br>') $texteBullFact ='';
		
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id_depart='.$id.'&total='.$paramtotal.'&'.$passe,$langs->trans('AnnuleDepart'),$langs->trans('QuestionDepart', $agf->intitule_custo, dol_print_date($db->idate($agf->dated),'day')).$texteBullFact,'confirm_annule','','',1);			
		print $formconfirm;

		}
		
/*
 * Action create
*/
 $wformdep = new FormCglDepart ($db);
 
if ('CONFIRM' == 'CONFIRM') {
	/*
	* Confirm delete
	*/
	if ($action == 'delete') {
		$ret = $form->form_confirm($_SERVER ['PHP_SELF'] . "?id=" . $id.'&total='.$total, $langs->trans("AgfDeleteOps"), $langs->trans("AgfConfirmDeleteSession"), "confirm_delete", '', '', 1);
		if ($ret == 'html')
			print '<br>';
	}					
	/*
	 * confirm archive update status
	*/
	if (isset($_GET ["arch"])) {
		$ret = $form->form_confirm($_SERVER ['PHP_SELF'] . "?arch=" . $_GET ["arch"] . "&id=" . $id.'&total='.$total, $langs->trans("AgfFormationArchiveChange"), $langs->trans("AgfConfirmArchiveChange"), "arch_confirm_delete", '', '', 1);
		if ($ret == 'html')
			print '<br>';
	}
					
	// Confirm clone
	if ($action == 'clone') {
		$formquestion = array (
				'text' => $langs->trans("ConfirmClone"),
				array (
						'type' => 'checkbox',
						'name' => 'clone_calendar',
						'label' => $langs->trans("AgfCloneSessionCalendar"),
						'value' => 1 
				),
				array (
						'type' => 'checkbox',
						'name' => 'clone_trainee',
						'label' => $langs->trans("AgfCloneSessionTrainee"),
						'value' => 1 
				),
				array (
						'type' => 'checkbox',
						'name' => 'clone_trainer',
						'label' => $langs->trans("AgfCloneSessionTrainer"),
						'value' => 1 
				) 
		);
		$ret = $form->form_confirm($_SERVER ['PHP_SELF'] . "?id=" . $id.'&total='.$total, $langs->trans("CloneSession"), $langs->trans("ConfirmCloneSession"), "confirm_clone", $formquestion, '', 1);
		if ($ret == 'html')
			print '<br>';
	}

}

// CREATION*/
//if ($action == 'create' && $user->rights->agefodd->creer) {
if ($action == 'create' ) {
	
	//$fk_soc_crea = GETPOST('fk_soc', 'int');
	
	print_fiche_titre($langs->trans("AgfMenuSessNew"));
	
	$wformdep->SaisieDepart('fiche','', $paramtotal);
}
 else {
	// AFFICHAGE ou MODIFICATIOn DEPART
	if ($id) {
		$result = $agf->fetch($id);
		if ($result > 0) {
			if (! (empty($agf->id))) {
				//$head = session_prepare_head($agf);
				
				if ($agf->type_session == 1)
					$styledisplay = ' style="display:none" ';
				
				dol_fiche_head($head, 'card', $langs->trans("AgfSessionDetail"), 0, 'calendarday');
						
				/*
				 * 
				// MODIFICATION
				 * 
				 */
				if ($action ==  'edit'  ) 
						$wformdep->SaisieDepart('fiche',$id, $paramtotal);
				else {
					// AFFICHAGE
					
					print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
					print 'Statut Départ :'.$agf->statuslib;
					print '</div>';
					
					// Print session card
					//$agf->printSessionInfo();					
					$wformdep->AfficheDepart('fiche',$id, $paramtotal);
					print '&nbsp';
					

				}
			} else {
				print $langs->trans('AgfNoSession');
			}
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}
	else $wformdep->SaisieDepart('fiche','', $paramtotal);
}

/*
 * Action tabs
*
*/
/*
	print '<div class="tabsAction">';

if ($action != 'create' && $action !=   'edit'   && (! empty($agf->id))) {

	//if ($user->rights->agefodd->creer) {
		print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?type=info&action=edit&id=' . $id . '&total=oui">' . $langs->trans('Modify') . '</a>';
	//} else {		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';	}


	//	if ($user->rights->agefodd->creer) {
		print '<a class="butActionDelete" href="' . $_SERVER ['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
	//} else {		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';	}

	if ($agf->status != 4) {
		$button = $langs->trans('AgfArchiver');
		$arch = 1;
	} else {
		$button = $langs->trans('AgfActiver');
		$arch = 0;
	}
	//if ($user->rights->agefodd->modifier) {
		print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?action=clone&id=' . $id . '">' . $langs->trans('ToClone') . '</a>';
		print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?arch=' . $arch . '&id=' . $id . '">' . $button . '</a>';
	//} else {		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $button . '</a>';	}

}
*/

	print '</div>';

llxFooter();
$db->close();
