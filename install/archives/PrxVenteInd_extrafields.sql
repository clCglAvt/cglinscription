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


à faire par paramétrage dans la base

A refaire pour tarig Enfant, Adulte et de groupe avant installation prod

ALTER TABLE llx_product_extrafields
	ADD COLUMN s_pvgroupe decimal (5,2);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_pvgroupe", 1, "product", now(), "Prix  de Vente Individuel", "double", "5,2",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}");


ALTER TABLE  llx_agefodd_session_extrafields
	ADD COLUMN s_pvgroupe decimal (5,2);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_pvgroupe", 1, "agefodd_session", now(), "Prix  de Vente Individuel(non obligatoire)", "double", "5,2",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}");


ALTER TABLE  llx_agefodd_formation_catalogue_extrafields
	ADD COLUMN s_pvgroupe decimal (5,2);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_pvgroupe", 1, "agefodd_formation_catalogue", now(), "Prix  de Vente Individuel", "double", "5,2",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}");







ALTER TABLE llx_product_extrafields
	ADD COLUMN s_PVIndEnf decimal (5,2);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_PVIndEnf", 1, "product", now(), "Prix  de Vente Individuel Tarif Enfant", "double", "5,2",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}");


ALTER TABLE  llx_agefodd_session_extrafields
	ADD COLUMN s_PVIndEnf decimal (5,2);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_PVIndEnf", 1, "agefodd_session", now(), "Prix  de Vente Individuel Tarif Enfant(non obligatoire)", "double", "5,2",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}");


ALTER TABLE  llx_agefodd_formation_catalogue_extrafields
	ADD COLUMN s_PVIndEnf decimal (5,2);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_PVIndEnf", 1, "agefodd_formation_catalogue", now(), "Prix  de Vente Individuel Tarif Enfant", "double", "5,2",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}");







ALTER TABLE llx_product_extrafields
	ADD COLUMN s_PVIndAdl decimal (5,2);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_PVIndAdl", 1, "product", now(), "Prix  de Vente Individuel Tarif Enfant", "double", "5,2",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}");


ALTER TABLE  llx_agefodd_session_extrafields
	ADD COLUMN s_PVIndAdl decimal (5,2);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_PVIndAdl", 1, "agefodd_session", now(), "Prix  de Vente Individuel Tarif Enfant(non obligatoire)", "double", "5,2",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}");


ALTER TABLE  llx_agefodd_formation_catalogue_extrafields
	ADD COLUMN s_PVIndAdl decimal (5,2);

insert into llx_extrafields (name , entity , elementtype , tms , label , size , fieldunique , fieldrequired , pos , param)
values ("s_PVIndAdl", 1, "agefodd_formation_catalogue", now(), "Prix  de Vente Individuel Tarif Enfant", "double", "5,2",0,0,4, "a:1:{s:7:"options";a:1:{s:0:"";N;}}");
