<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
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
 *   	\file       custum/cglinscription/class/cglFunctionAgefodd.class.php
 *		\ingroup    cglinscription
 *		\brief      Fonctions reprise de Agefodd et modifiées
 */

 require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/bulletin.class.php";
require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/cglInscDolibarr.class.php";
require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/class/cglcommunlocInsc.class.php";
require_once DOL_DOCUMENT_ROOT."/custom/cglinscription/core/modules/cglinscription/modules_cglinscription.php";
require_once DOL_DOCUMENT_ROOT."/custom/cglavt/class/cglFctDolibarrRevues.class.php";
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agsession.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agefodd_session_formateur.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agefodd_session_calendrier.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/agefodd/class/agefodd_session_formateur_calendrier.class.php';

	
/**
 *	Put here description of your class
 */
class CglFonctionAgeFodd
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='skeleton';			//!< Id that identify managed objects
	var $table_element='skeleton';		//!< Name of table without prefix where object is stored

    var $id;
    var $prop1;
    var $prop2;
	var $FormInscription;
	//...

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
		$this->type_session_cgl = 2;
        return 1;
    }

	/*
	* repris dans session/card paragraphe  Manage trainees - inutilisé
	*/
	function   listParticipants_old ($agf){
		global $langs, $conf;
		print '&nbsp';
		print '<table class="border" width="100%">';
		
		$stagiaires = new Agefodd_session_stagiaire($this->db);
		$resulttrainee = $stagiaires->fetch_stagiaire_per_session($agf->id);
		if ($resulttrainee < 0) {
			setEventMessage($stagiaires->error, 'errors');
		}
		$nbstag = count($stagiaires->lines);
		print '<tr><td  width="20%" valign="top" ';
		if ($nbstag < 1) {
			print '>' . $langs->trans("AgfParticipants") . '</td>';
			print '<td style="text-decoration: blink;">' . $langs->trans("AgfNobody") . '</td></tr>';
		} else {
			print ' rowspan=' . ($nbstag) . '>' . $langs->trans("AgfParticipants");
			if ($nbstag > 1)
				print ' (' . $nbstag . ')';
			print '</td>';
			
			for($i = 0; $i < $nbstag; $i ++) {
				print '<td witdth="20px" align="center">' . ($i + 1) . '</td>';
				print '<td width="400px"style="border-right: 0px;">';
				// Infos trainee
				if (strtolower($stagiaires->lines [$i]->nom) == "undefined") {
					print $langs->trans("AgfUndefinedStagiaire");
				} else {
					$trainee_info = '<a href="' . dol_buildpath('/agefodd/trainee/card.php', 1) . '?id=' . $stagiaires->lines [$i]->id . '">';
					$trainee_info .= img_object($langs->trans("ShowContact"), "contact") . ' ';
					$trainee_info .= strtoupper($stagiaires->lines [$i]->nom) . ' ' . ucfirst($stagiaires->lines [$i]->prenom) . '</a>';
					$contact_static = new Contact($db);
					$contact_static->civility_id = $stagiaires->lines [$i]->civilite;
					$trainee_info .= ' (' . $contact_static->getCivilityLabel() . ')';
					
					if ($agf->type_session == 1 && ! empty($conf->global->AGF_MANAGE_OPCA)) {
						print '<table class="nobordernopadding" width="100%"><tr><td colspan="2">';
						print $trainee_info . ' ' . $stagiaires->LibStatut($stagiaires->lines [$i]->status_in_session, 4);
						print '</td></tr>';
						$opca=new Agefodd_opca($db);
						
						$opca->getOpcaForTraineeInSession($stagiaires->lines [$i]->socid, $agf->id, $stagiaires->lines[$i]->stagerowid );
						print '<tr><td width="45%">' . $langs->trans("AgfSubrocation") . '</td>';
						if ($opca->is_OPCA == 1) {
							$chckisOPCA = 'checked="checked"';
						} else {
							$chckisOPCA = '';
						}
						print '<td><input type="checkbox" class="flat" name="isOPCA" value="1" ' . $chckisOPCA . '" disabled="disabled" readonly="readonly"/></td></tr>';
						
						print '<tr><td>' . $langs->trans("AgfOPCAName") . '</td>';
						print '	<td>';
						print '<a href="' . dol_buildpath('/societe/soc.php', 1) . '?socid=' . $opca->fk_soc_OPCA . '">' . $opca->soc_OPCA_name . '</a>';
						print '</td></tr>';
						
						print '<tr><td>' . $langs->trans("AgfOPCAContact") . '</td>';
						print '	<td>';
						print '<a href="' . dol_buildpath('/contact/fiche.php', 1) . '?id=' . $opca->fk_socpeople_OPCA . '">' . $opca->contact_name_OPCA . '</a>';
						print '</td></tr>';
						
						print '<tr><td width="20%">' . $langs->trans("AgfOPCANumClient") . '</td>';
						print '<td>' . $opca->num_OPCA_soc . '</td></tr>';
						
						print '<tr><td width="20%">' . $langs->trans("AgfOPCADateDemande") . '</td>';
						if ($opca->is_date_ask_OPCA == 1) {
							$chckisDtOPCA = 'checked="checked"';
						} else {
							$chckisDtOPCA = '';
						}
						print '<td><table class="nobordernopadding"><tr><td>';
						print '<input type="checkbox" class="flat" name="isdateaskOPCA" disabled="disabled" readonly="readonly" value="1" ' . $chckisDtOPCA . ' /></td>';
						print '<td>';
						print dol_print_date($opca->date_ask_OPCA, 'daytext');
						print '</td><td>';
						print '</td></tr></table>';
						print '</td></tr>';
						
						print '<tr><td width="20%">' . $langs->trans("AgfOPCANumFile") . '</td>';
						print '<td>' . $opca->num_OPCA_file . '</td></tr>';
						
						print '</table>';
					} else {
						print $trainee_info . ' ' . $stagiaires->LibStatut($stagiaires->lines [$i]->status_in_session, 4);
					}
				}
				print '</td>';
				print '<td style="border-left: 0px; border-right: 0px;">';
				// Info funding company
				if ($stagiaires->lines [$i]->socid) {
					print '<a href="' . DOL_MAIN_URL_ROOT . '/comm/card.php?socid=' . $stagiaires->lines [$i]->socid . '">';
					print img_object($langs->trans("ShowCompany"), "company");
					if (! empty($stagiaires->lines [$i]->soccode))
						print ' ' . $stagiaires->lines [$i]->soccode . '-';
					print ' ' . dol_trunc($stagiaires->lines [$i]->socname, 20) . '</a>';
				} else {
					print '&nbsp;';
				}
				print '</td>';
				print '<td style="border-left: 0px;">';
				// Info funding type
				if ($stagiaires->lines [$i]->type && (! empty($conf->global->AGF_USE_STAGIAIRE_TYPE))) {
					print '<div class=adminaction>';
					print $langs->trans("AgfStagiaireModeFinancement");
					print '-<span>' . stripslashes($stagiaires->lines [$i]->type) . '</span></div>';
				} else {
					print '&nbsp;';
				}
				print '</td>';
				print "</tr>\n";
			}
		}
	} // listParticipants

	function remove_une_animation($id_session, $id_formateur) {
		global $conf;
		$this->db->begin();
		
		if ($conf->global->AGF_DOL_TRAINER_AGENDA) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'actioncomm WHERE id IN ';
			$sql .= '(SELECT fk_actioncomm FROM ' . MAIN_DB_PREFIX . 'agefodd_session_formateur_calendrier ';
			$sql .= 'WHERE fk_agefodd_session_formateur=' . $id_formateur ;
			$sql .= ' and fk_agefodd_session=' . $id_session . ')';
			dol_syslog(get_class($this) . "::remove sql=" . $sql, LOG_DEBUG);
			/*$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors [] = "Error " . $this->db->lasterror();
			}
			*/
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'agefodd_session_formateur_calendrier ';
			$sql .= 'WHERE fk_agefodd_session_formateur=' .  $id_formateur ;
			$sql .= ' and fk_agefodd_session=' . $id_session . ')';
			
			dol_syslog(get_class($this) . "::remove sql=" . $sql, LOG_DEBUG);
			/*$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors [] = "Error " . $this->db->lasterror();
			}
			*/
		}
		
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_formateur";
		$sql .= " WHERE fk_agefodd_formateur = '" . $id_formateur. '"';
			$sql .= ' and fk_session="' . $id_session .'"';
		dol_syslog(get_class($this) . "::remove sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
		
	} //remove_une_session

} // fin de classe

?>
