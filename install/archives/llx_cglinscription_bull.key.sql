-- ===================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================


ALTER TABLE llx_cglinscription_bull
	ADD INDEX idx_fk_facture (fk_facture);
	
	
ALTER TABLE llx_cglinscription_bull
	ADD INDEX idx_fk_tiers (fk_tiers);
ALTER TABLE llx_cglinscription_bull
	ADD CONSTRAINT fk_cglinscription_bull_fk_tiers
	FOREIGN KEY (fk_tiers) REFERENCES llx_societe (rowid);
	
	

ALTER TABLE llx_cglinscription_bull_det
	ADD INDEX idx_fk_bull (fk_bull);
ALTER TABLE llx_cglinscription_bull_det
	ADD CONSTRAINT fk_cglinscription_bull_det_fk_bull
	FOREIGN KEY (fk_bull) REFERENCES llx_cglinscription_bull (rowid);
	
	
ALTER TABLE llx_cglinscription_bull_det
	ADD INDEX idx_fk_activite (fk_activite);