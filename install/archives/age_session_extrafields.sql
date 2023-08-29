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


ALTER TABLE llx_agefodd_session_extrafields
	ADD COLUMN s_rdvPrinc varchar(255);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_rdvPrinc", 3, "session_extrafields", now(), "Rendez-vous principal", "double", "255",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}")



ALTER TABLE llx_agefodd_session_extrafields
	ADD COLUMN s_rdvAlter varchar(255);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_rdvAlter", 4, "session_extrafields", now(), "Autre rendez-vous possible", "double", "255",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}")

