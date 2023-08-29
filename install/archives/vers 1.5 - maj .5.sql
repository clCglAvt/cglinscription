-- Date Janv 

-- par CCA

-- Gestion du suivi des bulletin/Contrat

ALTER TABLE `llx_cglinscription_bull` ADD `ActionFuture` TEXT NULL AFTER `ObsPriv`;
ALTER TABLE `llx_cglinscription_bull` ADD `PmtFutur` TEXT NULL AFTER `ref_cmd`;



-- Ajout d'un champ image du code de ventilation fournisseur
-- créer un champ extrafield dans facture_fourn_det 's_fk_ventil', 'Code Ventilation', liste issue d'une table -  accounting_account:concat(concat(account_number,' - '),label):rowid::fk_pcg_version = 'PLAN ANALYT' and account_number like '9%'


-- Ajout d'un champ image du code de ventilation facture client 
-- créer un champ extrafield dans llx_facturedet 's_fk_ventil', 'Code Ventilation', liste issue d'une table -  accounting_account:concat(concat(account_number,' - '),label):rowid::fk_pcg_version = 'PLAN ANALYT' and account_number like '9%'

-- Ajout trigger pour une ventilation à la saisie de la facture
delimiter |
CREATE TRIGGER after_insert_facture_fourn_det_extrafields AFTER INSERT
ON llx_facture_fourn_det_extrafields FOR EACH ROW
BEGIN
		UPDATE llx_facture_fourn_det
        SET fk_code_ventilation = NEW.s_fk_ventil
        WHERE rowid = NEW.fk_object;
END |

CREATE TRIGGER after_update_facture_fourn_det_extrafields AFTER UPDATE
ON llx_facture_fourn_det_extrafields FOR EACH ROW
BEGIN
		UPDATE llx_facture_fourn_det
        SET fk_code_ventilation = NEW.s_fk_ventil
        WHERE rowid = NEW.fk_object;
END |

CREATE TRIGGER after_insert_facturedet_extrafields AFTER INSERT
ON llx_facturedet_extrafields FOR EACH ROW
BEGIN
		UPDATE llx_facturedet
        SET fk_code_ventilation = NEW.s_fk_ventil
        WHERE rowid = NEW.fk_object;
END |

CREATE TRIGGER after_update_facturedet_extrafields AFTER UPDATE
ON llx_facturedet_extrafields FOR EACH ROW
BEGIN
		UPDATE llx_facturedet
        SET fk_code_ventilation = NEW.s_fk_ventil
        WHERE rowid = NEW.fk_object;
END |
delimiter ;




INSERT INTO llx_const (name, entity, value, type, visible ) values ('MAIN_EMAIL_USECCC', 1, 'commercial@cigaleaventure.com', 'chaine', 1)


-- Ajout variable dolibarr
nom : WIN_ODT2PDF_ENV_SOFFICE, valeur : file:///D:/OOoEcoute/CAV , commentaire : User Windows pour la mise en écoute de soffice

-- ajout d'un champ pour le fichier descriptif des activités du site
ALTER TABLE `llx_agefodd_place` 
ADD `fic_infos` VARCHAR(30) NULL COMMENT 'Fichier des informations à destination des participants aux activités de ce site' ;



-- Ajout des donnes pour facturation moniteur

ALTER TABLE `llx_agefodd_formateur` ADD `fk_soc` INT(11) NULL AFTER `fk_user`, ADD INDEX (`fk_soc`) ;
ALTER TABLE `llx_agefodd_formateur` ADD `cost_trainer` DOUBLE(24,8) NULL ;
ALTER TABLE `llx_agefodd_formateur` ADD `cost_trip` DOUBLE(24,8) NULL ;
ALTER TABLE `llx_agefodd_formateur` ADD `date_nego` DATE NULL ;

ALTER TABLE llx_agefodd_session_extrafields ADD `s_partmonit` double(24,8) NULL;
ALTER TABLE llx_agefodd_session_extrafields ADD `s_pourcent` double(24,8) NULL;
ALTER TABLE llx_agefodd_session_extrafields ADD `s_date_nego` date NULL;



INSERT INTO llx_extrafields(name, label, type, pos, size, entity, elementtype, fieldunique, fieldrequired, param, alwayseditable, perms, list, ishidden)
 VALUES('s_partmonit', 'Paiement Moniteur', 'double', '90', '24,8', 1, 'agefodd_session', '0', '0', 'a:1:{s:7:\"options\";a:1:{s:0:\"\";N;}}', '1', null, 0, 0);

INSERT INTO llx_extrafields(name, label, type, pos, size, entity, elementtype, fieldunique, fieldrequired, param, alwayseditable, perms, list, ishidden)
 VALUES('s_pourcent', 'Paiement Moniteur', 'double', '91', '24,8', 1, 'agefodd_session', '0', '0', 'a:1:{s:7:\"options\";a:1:{s:0:\"\";N;}}', '1', null, 0, 0);

INSERT INTO llx_extrafields(name, label, type, pos, size, entity, elementtype, fieldunique, fieldrequired, param, alwayseditable, perms, list, ishidden)
 VALUES('s_date_nego', 'date de négociation', 'date', '92', '', 1, 'agefodd_session', '0', '0', 'a:1:{s:7:\"options\";a:1:{s:0:\"\";N;}}', '1', null, 0, 0);


ALTER TABLE llx_agefodd_session_extrafields ADD `s_fk_facture` text NULL;
INSERT INTO llx_extrafields(name, label, type, pos, size, entity, elementtype, fieldunique, fieldrequired, param, alwayseditable, perms, list, ishidden) 
VALUES('s_fk_facture', 'Facture payant le moniteur', 'sellist', '93', '', 1, 'agefodd_session', '0', '0', 'a:1:{s:7:\"options\";a:1:{s:23:\"llx_facture:ref:rowid::\";N;}}', '1', null, 0, 0)

ALTER TABLE llx_agefodd_sesion_extrafields ADD `s_ref_facture` varchar(255) NULL;
INSERT INTO llx_extrafields(name, label, type, pos, size, entity, elementtype, fieldunique, fieldrequired, param, alwayseditable, perms, list, ishidden) 
 VALUES('s_ref_facture', 'Référence facture fournisseur ', 'varchar', '94', '255', 1, 'agefodd_session', '0', '0', 'a:1:{s:7:\"options\";a:1:{s:0:\"\";N;}}', '1', null, 0, 0)




