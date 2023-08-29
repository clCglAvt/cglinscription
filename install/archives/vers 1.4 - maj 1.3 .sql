


-- ATTENTION MISE  A JOUR Pour version 1.3



-- reprendr tous les ajouts de champs fait

// Ajout d_une constante, afin de parametrer le comtpe de ventilation des accomptes Juste mpaiements
INSERT INTO `dolibarr`.`llx_const` (`rowid`, `name`, `entity`, `value`, `type`, `visible`, `note`, `tms`) VALUES (NULL, 'CGL_VENTIL_ACOMPTE', '1', '-1', 'entier', '1', 'Compte de ventilation des acomptes une fois la facture payée, car devenu inutile dans le CA', CURRENT_TIMESTAMP);
 
 
 // INSTALLATION VERSION DOLIBARR 4.0 et AGEFODD 2.1.15
 //Avant de lancer l''installation, protection du compte comptable
 
create table llx_accountingaccount_sav3_6
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_pcg_version  varchar(12)  NOT NULL,
  pcg_type        varchar(20)  NOT NULL,
  pcg_subtype     varchar(20)  NOT NULL,
  account_number  varchar(20)  NOT NULL,
  account_parent  varchar(20),
  label           varchar(255) NOT NULL,
  active     	  tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

ALTER TABLE `llx_accountingaccount_sav3_6` ADD `rowid_anc` INT( 11 ) NOT NULL AFTER `rowid` 
insert into llx_accountingaccount_sav3_6 (rowid_anc, fk_pcg_version, pcg_type, pcg_subtype, account_number,  account_parent, label, active )
select rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number,  account_parent, label, active from llx_accountingaccount where rowid > 1500


-- Acces aux échange des dossiers
ALTER TABLE `cglavt`.`llx_cglavt_dossierdet` ADD INDEX `idx_fk_dossier` ( `fk_dossier` ) 

-- Transfere les civilité de c_civilite à c_civility
insert into llx_c_civility select * from llx_civilite where rowid > 8

Etape 1
renommer le répertoire alias, afin de laisser '.0 en faire un. On verra ensuite à remettre ls raccourci
Etape 20
Lancer le doliwamp4.0.0.exe

J'ai ignoré les erruers SQL de la migration 23.6 en 3.7 (cela semble le plan comptable espagnol)
