-- Creation Table dictionnaire POIDS en Version 1.1
Voir les deux constantes Type Tiers Particulier  et Stagiaire Inconnu (dans le fichier Module de CGL


CREATE TABLE `llx_cgl_c_poids` (
`code` VARCHAR( 2 ) NOT NULL ,
active smallint default '1',
`libelle` VARCHAR( 20 ) NOT NULL ,
`ordre` smallint NOT NULL ,
UNIQUE (
`code`,
rowid  INT( 11 ) NOT NULL AUTO_INCREMENT 
)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `llx_cglinscription_bull` ADD `filtrpass` SMALLINT NOT NULL DEFAULT '0';

INSERT INTO `llx_cgl_c_poids` (`code` ,`libelle`) VALUES ('XL', 'Très large');
INSERT INTO `llx_cgl_c_poids` (`code` ,`libelle`) VALUES ('S', 'Petit');

Créer 4 champs dans socpeople_extrafields (attributs supplémentaires de COntacts
taille  - s_taille - chaine 20
Poids - s_poids - venant de  table llx_cgl_c_poids:code
Age - s_age - chaine 10
année dernière info - s_dateinfo - numérique entier 4

-- reprendre les données taille - age de cet été
-- les participants qui ont déjà un extrafields

update  llx_socpeople_extrafields as ce
set s_taille = (select max(taille) from llx_cglinscription_bull_det where fk_contact = ce.fk_object),
 s_age = (select max(age) from llx_cglinscription_bull_det where fk_contact = ce.fk_object),
 s_dateinfo = 2014,
 tms = now()
where exists (select(1) from llx_cglinscription_bull_det as bd where  fk_contact = ce.fk_object)

-- les participants qui n'ont pas de extrafields
insert into llx_socpeople_extrafields ( tms, fk_object, s_taille, s_age, s_dateinfo)
select now(), fk_contact, max(taille), max(age), 2014
from llx_cglinscription_bull_det as bd 
where not exists (select(1) from llx_socpeople_extrafields as ce  where  fk_contact = ce.fk_object)
group by fk_contact


select  s_taille , (select max(taille) from llx_cglinscription_bull_det where fk_contact = ce.fk_object),
 s_age , (select max(age) from llx_cglinscription_bull_det where fk_contact = ce.fk_object),
 s_dateinfo , 2014,
 tms , now()
from  llx_socpeople_extrafields as ce
where exists (select(1) from llx_cglinscription_bull_det as bd where  fk_contact = ce.fk_object)


-- raison des remises
CREATE TABLE `llx_cgl_c_raison_remise` (
`rowid` INT NOT NULL AUTO_INCREMENT ,
`libelle` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
active smallint default '1',
`ordre` smallint NOT NULL ,
PRIMARY KEY ( `rowid` ) ,
UNIQUE (`rowid`)
) ENGINE = InnoDB COMMENT = 'Dictionnaire concernant les raisons pour remise';

ALTER TABLE `llx_cglinscription_bull_det` ADD `fk_raisrem` INT( 11 ) NOT NULL AFTER `fk_rdv` ,
ADD INDEX ( `fk_raisrem` ); 
ALTER TABLE `llx_cglinscription_bull_det` ADD `fk_agsessstag` INT( 11 ) NOT NULL AFTER `fk_facture` ;
ALTER TABLE `llx_cglinscription_bull_det` ADD `fk_user` INT( 11 ) NOT NULL AFTER fk_bull;
-- Les clients CglInscription sont des particuliers
UPDATE  `llx_societe`  SET  fk_typent = 103
where rowid in (select fk_tiers from llx_cglinscription_bull where fk_type_session = 1)
and fk_typent = 0   

-- mettre un département à tous les tiers ayant un code postal
update  llx_societe
set fk_departement = (select dep.rowid  FROM `llx_c_departements` as dep, llx_c_regions as reg  
			WHERE fk_pays = 1 and fk_region = reg.code_region and `code_departement` = substring(zip,1,2))
where   fk_departement = 0 or isnull( fk_departement)

--Test de validité 
SELECT nom, town, fk_departement
FROM `llx_societe`
WHERE (zip = '' OR isnull( zip ) ) AND ! isnull( town ) AND town != '' AND isnull( fk_departement );
-- herault
UPDATE `llx_societe`
set fk_departement = 36
WHERE town in ( 'Villeneuve les Maguelone','MONTPELLIER', 'Saint Gely du Fesc','LODEVE','LAROQUE','SAINT SERIES','SAINT MARTINde LONDRES', 'Vacquières','Saint Jean de la Blaquière','FONTANES','BRISSAC','SETE','les Matelles','PEROLS');

--gard
UPDATE `llx_societe`
set fk_departement = 32
WHERE town in ( 'MONTFRIN', 'MONOBLET', 'MONTOULIEU', 'Le VIGAN', 'VALLERAUGUE', 'ALES','NIMES', 'MOLIERES CAVAILLAC');

UPDATE `llx_societe`
set fk_departement = 77
WHERE town like  '%PARIS%';

--Bouche du rhone
UPDATE `llx_societe`
SET fk_departement = 14
WHERE town = 'MARSEILLE';

UPDATE `llx_societe`
SET  fk_departement = 36
WHERE nom = 'ESCAPEO';

-- aveyron
UPDATE `llx_societe`
SET fk_departement = 13
WHERE town = 'MILLAU';

-- drome

UPDATE `llx_societe`
SET fk_departement = 28
WHERE town = 'DIE';

---LOZERE
UPDATE `llx_societe`
SET   fk_departement = 50
WHERE nom = 'GUILLAUME';

UPDATE `llx_societe`
SET   fk_departement = 50, town = 'MARVEJOLS'
WHERE town = 'MARJEVOLS';


UPDATE `llx_societe`
SET   town = 'PEZENAS', fk_departement = 36, zip = 34120, nom = 'ESPACE JEUNES DE PEZENAS'
WHERE nom = 'ESPACE JEUNES DE PEZENNAS';

-- Ajout participant inconnu
INSERT INTO llx_agefodd_stagiaire(nom, prenom, civilite, fk_user_author,fk_user_mod, datec, fk_soc, fonction, tel1, tel2, mail, note,fk_socpeople,entity,date_birth,place_birth) VALUES ( 'PARTICIPANT',  'Inconnu',  'MME',  1,  1, '20141222180448',  -1,  '',  '',  '',  '',  "Stagiaire permettant d\'inscrire des participants non encore connus",  null,  1, NULL,  '')

-- Valoriser le fk_user de bull

update llx_cglinscription_bull as bull
set fk_user =(select commande.fk_user_author from llx_commande as commande
where fk_cmd = commande.rowid)
