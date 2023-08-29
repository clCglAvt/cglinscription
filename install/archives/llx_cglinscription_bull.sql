-- ===================================================================
-- Copyright (C) 2000-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
create table if not exists llx_cglinscription_bull
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  fk_createur	integer not null,
  tms             timestamp,
  entity	  integer,
  typebull	varchar(4) null CHARACTER SET utf8 COLLATE utf8_general_ci,
  action	  varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci null,
  ref	          varchar(13) CHARACTER SET utf8 COLLATE utf8_general_ci,
  statut	  integer default 0,   -- 0 en cours , 1 Inscrit , 2  Pre_inscrit
  abandon	 VARCHAR( 100 ) NULL,
  regle		  integer default 0,   -- 0 non payé  , 1 paiement incomplet - 2 payé
  fk_user	  integer null,
  fk_tiers        integer not null,
  TiersTel	  varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci,
  fk_ContactTiers	integer null,
  Villegiature	  varchar(75) CHARACTER SET utf8 COLLATE utf8_general_ci,
  fk_facture      integer null, 
  fk_persrec      integer null, 
  RecTel	  varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci  NULL,
  f_autori_parentale     varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci,
  f_condition_vente    varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci,
  f_autre    varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci,
  fk_cmd	  integer  null,
  ref_cmd	  varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci,
  fk_acompte	  integer null,
  ficcmd	  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci, -- Obsolete
  fk_origine	  int,
  fk_soc_rem_execpt int,
  fk_type_session int ,
  filtrpass SMALLINT NOT NULL DEFAULT '0',
  
 `fk_caution` int (11) NULL 
 top_doc integer not null,
 top_caution integer not null,
 ret_caution integer  null DEFAULT '0',
 ret_doc integer  null,
 `mttAcompte` decimal( 7,2 ) NULL DEFAULT '0',
 `mttcaution` decimal( 7,2 ) NULL DEFAULT '0',
 `datedepose` DATETIME NULL DEFAULT NULL  ,
 `dateretrait` DATETIME NULL DEFAULT NULL ,
 `lieuretrait` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
 `lieudepose` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
 `ObsReservation` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
 `fk_sttResa` INT(11) NULL ,
 `observation` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
 `obs_matmad` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
 `fk_modcaution` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
 `ObsCaution` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
 `obs_rando` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;
)ENGINE=innodb;


create table if not exists llx_cglinscription_bull_det
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  action	  varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci,
  type		  integer,		-- 1 pour activité/Contact - 2 pour paiements
  ordre	          integer,
  fk_bull	  integer not null,
  rang		  integer null,
  fk_activite     integer not null,
  fk_produit      integer null, 
  fk_contact      integer null,
  age		  integer null,
  taille	  varchar (10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL, 
  PartTel	  varchar(20),
  pu		  double(7,2), 
  rem		  double(5,2),
  qte		  integer,
  pt		  double(10,2),
  fk_code_ventilation	integer,
  fk_mode_pmt     integer null,
  organisme	  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci,
  fk_organisme	  integer null,
  tireur	  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci,
  num_cheque	  varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci,
  datepaiement	  date,
  fk_linecmd	  integer null,
  fk_paiement 	  integer null,
  fk_banque	  integer null,
  fk_facture 	  integer null,
  ficbull	  varchar(255),
  fk_rdv	  int NOT NULL DEFAULT '1',
  observation	  text
 )ENGINE=innodb;

REM Table dictionnaire de correspondance mode paiement et banque

create table if not exists llx_cgl_pmt_bank
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  code_paiement	  varchar (5),
  fk_code_bq	  varchar (12)
)ENGINE=innodb;

insert into llx_cgl_pmt_bank (code_paiement,fk_code_bq) values ('CHQ','BQ_CEP');

insert into llx_cgl_pmt_bank (code_paiement,fk_code_bq) values ('LIQ','CAISSE');

insert into llx_cgl_pmt_bank (code_paiement,fk_code_bq) values ('ANCV','ANCV');

insert into llx_cgl_pmt_bank (code_paiement,fk_code_bq) values ('VAD','BQ_CEP');

insert into llx_cgl_pmt_bank (code_paiement,fk_code_bq) values ('CB','BQ_CEP');

insert into llx_cgl_pmt_bank (code_paiement,fk_code_bq) values ('VIR','BQ_CEP');
