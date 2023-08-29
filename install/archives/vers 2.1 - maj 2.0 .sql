

-- Version 2.1 pour evolution bug
-- ajout champ url_loc pour localisation du site
ALTER TABLE `llx_agefodd_place` ADD `url_loc` VARCHAR(255) NOT NULL COMMENT 'url de localisation google' ;

-- Mise en place relevé de bnaque antérieur
INSERT INTO `llx_const` (`rowid`, `name`, `entity`, `value`, `type`, `visible`, `note`, `tms`) VALUES (NULL, 'BANK_REPORT_LAST_NUM_RELEVE', '1', '1', 'string', '1', 'Pour récupérer le dernier numéro de relevé bancaire, lors du rapprochement', CURRENT_TIMESTAMP);

