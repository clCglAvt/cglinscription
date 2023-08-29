-- ===================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
-- 
-- MOD CCA 26/12/2014 Creation table Pour cahier Accueil
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================


ALTER TABLE llx_cglavt_tiers_suivi
	ADD INDEX idx_fk_soc (fk_soc);
	
ALTER TABLE llx_cglavt_tiers_suivi
	ADD INDEX idx_datec (datec);
ALTER TABLE llx_cglavt_tiers_suivi
	ADD INDEX idx_date_action (date_action);
ALTER TABLE llx_cglavt_tiers_suivi
	ADD INDEX idx_action_realisee (action);
ALTER TABLE llx_cglavt_tiers_suivi
	ADD INDEX idx_urgence (urgence);
	
ALTER TABLE llx_cglavt_tiers_suivi ADD CONSTRAINT fk_cglavt_tiers_suivi_fk_socpeople
FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople (rowid);
ALTER TABLE llx_cglavt_tiers_suivi ADD CONSTRAINT fk_cglavt_tiers_suivi_fk_societe
FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);

ALTER TABLE `cglavt`.`llx_cglavt_dossierdet` ADD INDEX `idx_fk_dossier` ( `fk_dossier` ) 