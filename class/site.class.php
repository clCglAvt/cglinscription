<?php
/*
 * Copyright (C) 2007-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2014  Florian Henry <florian.henry@open-concept.pro>
 *
 * Version CAV - 2.7 - été 2022 - Migration V15
 * Version CAV - 2.8 - hiver 2023 - Pagination
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
 * \file agefodd/class/agefodd_place.class.php
 * \ingroup agefodd
 * \brief Manage location object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT . '/custom/agefodd/class/agefodd_reginterieur.class.php');
require_once("../cglavt/class/cglFctCommune.class.php");

/**
 * Location Class
 */
class Site extends CommonObject {
	var $db;
	var $error;
	var $errors = array ();
	var $element = 'agefodd_place';
	var $table_element = 'agefodd_place';
	protected $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	var $id;
	var $entity;
	var $ref_interne;
	var $adresse;
	var $cp;
	var $ville;
	var $fk_pays;
	var $tel;
	var $fk_societe;
	var $notes;
	var $acces_site;
	var $note1;
	var $rdvPrinc;
	var $rdvAlter;
	var $infopublic;
	var $fic_infos;
	var $url_loc;
	var $archive;
	var $fk_reg_interieur;
	var $fk_user_author;
	var $datec = '';
	var $fk_user_mod;
	var $tms = '';
	var $lines = array ();
	
	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($db) {
		$this->db = $db;
		return 1;
	}
	
	/**
	 * Create object into database
	 *
	 * @param User $user that create
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		
		if (isset($this->ref_interne))
			$this->ref_interne = trim($this->ref_interne);
		if (isset($this->adresse))
			$this->adresse = trim($this->adresse);
		if (isset($this->cp))
			$this->cp = trim($this->cp);
		if (isset($this->ville))
			$this->ville = trim($this->ville);
		if (isset($this->fk_pays))
			$this->fk_pays = trim($this->fk_pays);
		if (isset($this->tel))
			$this->tel = trim($this->tel);
		if (isset($this->fk_societe))
			$this->fk_societe = trim($this->fk_societe);
		if (isset($this->notes))
			$this->notes = trim($this->notes);
		if (isset($this->acces_site))
			$this->acces_site = trim($this->acces_site);
		if (isset($this->note1))
			$this->note1 = trim($this->note1);
		if (isset($this->archive))
			$this->archive = trim($this->archive);
		if (isset($this->fk_reg_interieur))
			$this->fk_reg_interieur = trim($this->fk_reg_interieur);
		if (isset($this->rdvPrinc))
			$this->note1 = trim($this->rdvPrinc);
		if (isset($this->rdvAlter))
			$this->note1 = trim($this->rdvAlter);
		if (isset($this->fic_infos))
			$this->fic_infos = trim($this->fic_infos);
		if (isset($this->url_loc))
			$this->url_loc = trim($this->url_loc);
		if (isset($this->infopublic))
			$this->infopublic = trim($this->infopublic);
		
			// Check parameters
			// Put here code to add control on parameters value
			
		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_place(";
		
		$sql .= "entity,";
		$sql .= "ref_interne,";
		$sql .= "adresse,";
		$sql .= "cp,";
		$sql .= "ville,";
		$sql .= "fk_pays,";
		$sql .= "tel,";
		$sql .= "fk_societe,";
		$sql .= "notes,";
		$sql .= "acces_site,";
		$sql .= "note1,";
		$sql .= "rdvPrinc,";
		$sql .= "rdvAlter,";
		$sql .= "InfoPublic,";		
		$sql .= "fic_infos,";			
		$sql .= "url_loc,";		
		$sql .= "fk_user_author,";
		$sql .= "fk_user_mod,";
		$sql .= "datec";
				
		$sql .= ") VALUES (";
		
		$sql .= " " . $conf->entity . ",";
		$sql .= " " . (! isset($this->ref_interne) ? ' ' : "'" . $this->db->escape($this->ref_interne) . "'") . ",";
		$sql .= " " . (! isset($this->adresse) ? 'NULL' : "'" . $this->db->escape($this->adresse) . "'") . ",";
		$sql .= " " . (! isset($this->cp) ? 'NULL' : "'" . $this->db->escape($this->cp) . "'") . ",";
		$sql .= " " . (! isset($this->ville) ? 'NULL' : "'" . $this->db->escape($this->ville) . "'") . ",";
		$sql .= " " . (! isset($this->fk_pays) ? 0 : "'" . $this->fk_pays . "'") . ",";
		$sql .= " " . (! isset($this->tel) ? 'NULL' : "'" . $this->db->escape($this->tel) . "'") . ",";
		$sql .= " " . (! isset($this->fk_societe) ? 0 : "'" . $this->fk_societe . "'") . ",";
		$sql .= " " . (! isset($this->notes) ? 'NULL' : "'" . $this->db->escape($this->notes) . "'") . ",";
		$sql .= " " . (! isset($this->acces_site) ? 'NULL' : "'" . $this->db->escape($this->acces_site) . "'") . ",";
		$sql .= " " . (! isset($this->note1) ? 'NULL' : "'" . $this->db->escape($this->note1) . "'") . ",";		
		$sql .= " " . (! isset($this->rdvPrinc) ? 'NULL' : "'" . $this->db->escape($this->rdvPrinc) . "'") . ",";
		$sql .= " " . (! isset($this->rdvAlter) ? 'NULL' : "'" . $this->db->escape($this->rdvAlter) . "'") . ",";
		$sql .= " " . (! isset($this->infopublic) ? 'NULL' : "'" . $this->db->escape($this->infopublic) . "'") . ",";	
		$sql .= " " . (! isset($this->fic_infos) ? 'NULL' : "'" . $this->db->escape($this->fic_infos) . "'") . ",";		
		$sql .= " " . (! isset($this->url_loc) ? 'NULL' : "'" . $this->db->escape($this->url_loc) . "'") . ",";		
		$sql .= " " . $user->id . ",";
		$sql .= " " . $user->id . ",";
		$sql .= "'" . $this->db->idate(dol_now('tzuser')) . "'";
		$sql .= ")";
		
		// Insert request
		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}
		
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_place");
			
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.
				
				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}
	
	/**
	 * Load object in memory from database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {
		global $langs;
			
		
		$sql = "SELECT";
		$sql .= " p.rowid, p.ref_interne, p.adresse, p.cp, p.ville, p.fk_pays, pays.code as country_code, pays.label  as country, p.tel, p.fk_societe, p.notes, p.archive,";
		$sql .= " s.rowid as socid, s.nom as socname, p.acces_site, p.note1, p.fk_reg_interieur, p.rdvPrinc, p.InfoPublic, p.rdvAlter , p.fic_infos, p.url_loc ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON p.fk_societe = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as pays ON pays.rowid = p.fk_pays";
		$sql .= " WHERE p.rowid = " . $id;
		$sql .= " AND p.entity IN (" . getEntity('agsession') . ")";
		
		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->ref = $obj->rowid; // Use for next prev control
				$this->ref_interne = $obj->ref_interne;
				$this->adresse = stripslashes($obj->adresse);
				$this->cp = $obj->cp;
				$this->ville = stripslashes($obj->ville);
				$this->fk_pays = $obj->fk_pays;
				$this->country = $obj->country;
				$this->country_code = $obj->country_code;
				$this->tel = stripslashes($obj->tel);
				$this->fk_societe = $obj->fk_societe;
				$this->notes = stripslashes($obj->notes);
				$this->socid = $obj->socid;
				$this->socname = stripslashes($obj->socname);
				$this->archive = $obj->archive;
				$this->acces_site = $obj->acces_site;
				$this->note1 = $obj->note1;
				$this->rdvPrinc = $obj->rdvPrinc;
				$this->rdvAlter = $obj->rdvAlter;				
				$this->infopublic = $obj->InfoPublic;				
				$this->fic_infos = $obj->fic_infos;				
				$this->url_loc = $obj->url_loc;
				$this->fk_reg_interieur = $obj->fk_reg_interieur;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load object in memory from database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param array $filter filter array
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_all($sortorder, $sortfield, $limit, $offset, $filter = array()) {
		global $langs;
		
		$sql = "SELECT";
		$sql .= " p.rowid, p.ref_interne, p.adresse, p.cp, p.ville, p.fk_pays, pays.code as country_code, pays.label as  country, p.tel, p.fk_societe, p.notes, p.archive,";
		$sql .= " s.rowid as socid, s.nom as socname, p.acces_site, p.note1, p.rdvPrinc, p.rdvAlter, p.InfoPublic, p.fic_infos, p.url_loc";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON p.fk_societe = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as pays ON pays.rowid = p.fk_pays";
		$sql .= " WHERE p.entity IN (" . getEntity('agsession') . ")";
		
		// Manage filter
		if (count($filter)>0) {
			foreach ( $filter as $key => $value ) {
				$sql .= ' AND '. $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
			}
		}
		$sql .= " ORDER BY " . $sortfield . " " . $sortorder . " " . $this->db->plimit((int)$limit + 1, $offset);
		
		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			
			$this->line = array ();
			$num = $this->db->num_rows($resql);
			
			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);
				
				$line = new SiteLine();
				
				$line->id = $obj->rowid;
				$line->ref_interne = stripslashes($obj->ref_interne);
				$line->adresse = stripslashes($obj->adresse);
				$line->cp = $obj->cp;
				$line->ville = stripslashes($obj->ville);
				$line->pays_id = $obj->fk_pays;
				$line->country = $obj->country;
				$line->country_code = $obj->country_code;
				$line->tel = stripslashes($obj->tel);
				$line->fk_societe = $obj->fk_societe;
				$line->notes = stripslashes($obj->notes);
				$line->socid = $obj->socid;
				$line->socname = stripslashes($obj->socname);
				$line->archive = $obj->archive;
				$line->acces_site = $obj->acces_site;
				$line->note1 = $obj->note1;
				$line->rdvPrinc = $obj->rdvPrinc;
				$line->rdvAlter = $obj->rdvAlter;
				$line->infopublic = $obj->InfoPublic;	
				$line->fic_infos = $obj->fic_infos;		
				$line->url_loc = $obj->url_loc;				
				$this->lines [$i] = $line;
					
				$i ++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Give information on the object
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function info($id) {
		global $langs;
		
		$sql = "SELECT";
		$sql .= " p.rowid, p.datec, p.tms, p.fk_user_mod, p.fk_user_author";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " WHERE p.rowid = " . $id;
		
		dol_syslog(get_class($this) . "::info sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
				$this->user_modification = $obj->fk_user_mod;
				$this->user_creation = $obj->fk_user_author;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::info " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Update object into database
	 *
	 * @param User $user that modify
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		if (isset($this->ref_interne))
			$this->ref_interne = trim($this->ref_interne);
		else $this->ref_interne='sobj';
		if (isset($this->adresse))
			$this->adresse = trim($this->adresse);
		if (isset($this->cp))
			$this->cp = trim($this->cp);
		if (isset($this->ville))
			$this->ville = trim($this->ville);
		if (isset($this->fk_pays))
			$this->fk_pays = trim($this->fk_pays);
		if (empty($this->fk_pays)) $this->fk_pays = 0;
		if (isset($this->tel))
			$this->tel = trim($this->tel);
		if (isset($this->fk_societe))
			$this->fk_societe = trim($this->fk_societe);
		else $this->fk_societe = 0;
		if (isset($this->notes))
			$this->notes = trim($this->notes);
		if (isset($this->acces_site))
			$this->acces_site = trim($this->acces_site);
		if (isset($this->note1))
			$this->note1 = trim($this->note1);
		if (isset($this->archive))
			$this->archive = trim($this->archive);
		else $this->archive = 0;
		if (isset($this->fk_reg_interieur))
			$this->fk_reg_interieur = trim($this->fk_reg_interieur);		
		else $this->fk_reg_interieur = 0;
		if (isset($this->rdvPrinc))
			$this->rdvPrinc = trim($this->rdvPrinc);
		if (isset($this->rdvAlter))
			$this->rdvAlter = trim($this->rdvAlter);
		if (isset($this->infopublic))
			$this->infopublic = trim($this->infopublic);	
		if (isset($this->fic_infos))
			$this->fic_infos = trim($this->fic_infos);
		if (isset($this->url_loc))
			$this->url_loc = trim($this->url_loc);
			
			// Check parameters
			// Put here code to add control on parameters values
			
		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_place SET";
		$sql .= " ref_interne=" . (isset($this->ref_interne) ? "'" . $this->db->escape($this->ref_interne) . "'" : "null") . ",";
		$sql .= " adresse=" . (isset($this->adresse) ? "'" . $this->db->escape($this->adresse) . "'" : "null") . ",";
		$sql .= " cp=" . (isset($this->cp) ? "'" . $this->db->escape($this->cp) . "'" : "null") . ",";
		$sql .= " ville=" . (isset($this->ville) ? "'" . $this->db->escape($this->ville) . "'" : "null") . ",";
		$sql .= " fk_pays='" . (isset($this->fk_pays) ? $this->fk_pays : "null") . "',";
		$sql .= " tel=" . (isset($this->tel) ? "'" . $this->db->escape($this->tel) . "'" : "null") . ",";
		$sql .= " fk_societe=" . (isset($this->fk_societe) ? $this->fk_societe : "null") . ",";
		$sql .= " notes=" . (isset($this->notes) ? "'" . $this->db->escape($this->notes) . "'" : "null") . ",";
		$sql .= " acces_site=" . (isset($this->acces_site) ? "'" . $this->db->escape($this->acces_site) . "'" : "null") . ",";
		$sql .= " note1=" . (isset($this->note1) ? "'" . $this->db->escape($this->note1) . "'" : "null") . ",";
		$sql .= " rdvPrinc=" . (isset($this->rdvPrinc) ? "'" . $this->db->escape($this->rdvPrinc) . "'" : "null") . ",";
		$sql .= " rdvAlter=" . (isset($this->rdvAlter) ? "'" . $this->db->escape($this->rdvAlter) . "'" : "null") . ",";
		$sql .= " InfoPublic=" . (isset($this->infopublic) ? "'" . $this->db->escape($this->infopublic) . "'" : "null") . ",";
		$sql .= " fic_infos=" . (isset($this->fic_infos) ? "'" . $this->db->escape($this->fic_infos) . "'" : "null") . ",";
		$sql .= " url_loc=" . (isset($this->url_loc) ? "'" . $this->db->escape($this->url_loc) . "'" : "null") . ",";
		
		$sql .= " archive=" . $this->archive . ",";
		if (! empty($this->fk_reg_interieur)) {
			$sql .= " fk_reg_interieur=" . (isset($this->fk_reg_interieur) ? $this->fk_reg_interieur : "null") . ",";
		}
		$sql .= " fk_user_mod=" . $user->id;
		$sql .= " WHERE rowid=" . $this->id;
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.
				
				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
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
	}
	
	/**
	 * Delete object in database
	 *
	 * @param User $user that delete
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function remove($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.
				
				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		if (! $error) {
			$fk_reg_interieur = 0;
			
			$sql = "SELECT";
			$sql .= " p.rowid, p.fk_reg_interieur";
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_place as p";
			$sql .= " WHERE p.rowid = " . $this->id;
			
			$this->db->begin();
			
			dol_syslog(get_class($this) . "::remove sql=" . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors [] = "Error " . $this->db->lasterror();
			}
			
			if (! $error) {
				if ($this->db->num_rows($resql)) {
					$obj = $this->db->fetch_object($resql);
					$fk_reg_interieur = $obj->fk_reg_interieur;
				}
				
				$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_place";
				$sql .= " WHERE rowid = " . $this->id;
				
				dol_syslog(get_class($this) . "::remove sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$error ++;
					$this->errors [] = "Error " . $this->db->lasterror();
				}
				
				if ((! $error) && ! (empty($fk_reg_interieur))) {
					$agf_regint = new Agefodd_reg_interieur($this->db);
					$agf_regint->id = $fk_reg_interieur;
					$result = $agf_regint->delete($user);
					
					if ($result < 0) {
						$error ++;
						$this->errors [] = "Error " . $agf_regint->errors;
					}
				}
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 * Update reg int to null for this place
	 *
	 * @param User $user that delete
	 * @return int <0 if KO, >0 if OK
	 */
	function remove_reg_int($user) {
		global $conf, $langs;
		$error = 0;
		
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_place as p SET fk_reg_interieur=0, fk_user_mod=\'' . $user->id . '\'';
		$sql .= " WHERE rowid = " . $this->id;
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::remove_reg_int sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::remove_reg_int " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 * Import customer adress
	 *
	 * @param User $user that ask request
	 * @return int <0 if KO, Id of created object if OK
	 */
	function import_customer_adress($user) {
		global $conf, $langs;
		$error = 0;
		
		if (!empty($this->fk_societe)) {
			$sql = "SELECT";
			$sql .= " s.address, s.zip, s.phone, s.town, s.fk_departement, s.fk_pays";
			$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
			$sql .= " WHERE s.rowid = " . $this->fk_societe;
			
			dol_syslog(get_class($this) . "::import_customer_adress sql=" . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					$obj = $this->db->fetch_object($resql);
					$this->adresse = $obj->address;
					$this->cp = $obj->zip;
					$this->fk_pays = $obj->fk_pays;
					$this->ville = $obj->town;
					$this->tel = $obj->phone;
					$result = $this->update($user);
					if ($result<0) {
						$this->error = "Error " . $this->db->lasterror();
						dol_syslog(get_class($this) . "::import_customer_adress::update error=" . $agf->error, LOG_ERR);
						return - 1;
					}
				}
				$this->db->free($resql);
			
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::import_customer_adress " . $this->error, LOG_ERR);
				return - 1;
			}
		}

		return 1;
	}
	
	/**
	 * Return information of Place
	 *
	 * @return void
	 */
	function printPlaceInfo() {
		global $langs, $form;
		
		print '<table class="border" width="100%">';
		
		print '<tr><td width="20%">' . $langs->trans("Id") . '</td>';
		print '<td>' . $this->ref . '</td></tr>';
		
		print '<tr><td>' . $langs->trans("AgfSessPlaceCode") . '</td>';
		print '<td>' . $this->ref_interne . '</td></tr>';
		
		print '<tr><td valign="top">' . $langs->trans("Company") . '</td><td>';
		if ($this->socid) {
			print '<a href="' . DOL_MAIN_URL_ROOT . '/comm/card.php?socid=' . $this->socid . '">';
			print img_object($langs->trans("ShowCompany"), "company") . ' ' . dol_trunc($this->socname, 20) . '</a>';
		} else {
			print '&nbsp;';
		}
		print '</tr>';
		print '</table>';
	}
}
class SiteLine {
	var $id;
	var $ref_interne;
	var $adresse;
	var $cp;
	var $ville;
	var $pays_id;
	var $country;
	var $country_code;
	var $tel;
	var $fk_societe;
	var $notes;
	var $socid;
	var $socname;
	var $archive;
	var $acces_site;
	var $note1;
	function __construct() {
		return 1;
	}
}// Fin classe
?>