


-- ATTENTION avant la mise à jour de la table, passer les requête d'installation Agefodd., en tout cas celle de 
--		update 2.1.7 - 2.1.8 (fk_soc_requester)
--- et  update 2.1.9-2.1.10 (les deux update)


-- Creation Table dictionnaire Statut Reservationen Version 1.1


ALTER TABLE `llx_cglinscription_bull` 
ADD `typebull` VARCHAR( 4 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'Insc' AFTER `entity` ,
ADD `fk_caution` int (11) NULL ,
ADD `ret_caution` INT( 1 ) NULL DEFAULT '0',
ADD `mttAcompte` decimal( 7,2 ) NULL DEFAULT '0',
ADD `mttcaution` decimal( 7,2 ) NULL DEFAULT '0',
ADD fk_locheure int(11) null,
ADD `datedepose` DATETIME NULL DEFAULT NULL  ,
ADD `dateretrait` DATETIME NULL DEFAULT NULL ,
ADD `lieuretrait` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD `lieudepose` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD `ObsReservation` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD `fk_sttResa` INT(11) NULL ,
ADD `observation` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD `obs_matmad` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD `fk_modcaution` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD `ObsCaution` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD `fk_ContactTiers` INT( 11 ) NULL AFTER `TiersTel` ,
ADD `obs_rando` TEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;


ALTER TABLE `llx_cglinscription_bull_det`
ADD materiel VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD marque VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD refmat VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD NomPrenom VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD dateretrait DATE NULL ,
ADD datedepose DATE NULL ,
ADD duree decimal (5,2) NULL,
ADD qteret int(3) null,
ADD `lieuretrait` VARCHAR( 100 ) NULL AFTER `datedepose` ,
ADD `lieudepose` VARCHAR( 100 ) NULL AFTER `lieuretrait` ,
ADD `poids` VARCHAR( 5 ) NULL AFTER `age` 


UPDATE llx_cglinscription_bull set typebull = 'Insc';

DROP TABLE IF EXISTS `llx_cgl_c_stresa`;
CREATE TABLE `llx_cgl_c_stresa` (
  `rowid` int(11) NOT NULL auto_increment,
  `active` smallint(6) NOT NULL DEFAULT '1',
  `libelle` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
  `ordre` smallint(2) NOT NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `libelle` (`libelle`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;


INSERT INTO `llx_cgl_c_stresa` (`rowid` ,`active` ,`libelle` ,`ordre`)
VALUES (NULL , '1', 'A réserver', '1');

INSERT INTO `llx_cgl_c_stresa` (`rowid` ,`active` ,`libelle` ,`ordre`)
VALUES (NULL , '1', 'En attente de réponse', '2');

INSERT INTO `llx_cgl_c_stresa` (`rowid` ,`active` ,`libelle` ,`ordre`)
VALUES (NULL , '1', 'Réservé', '3');


DROP TABLE IF EXISTS `llx_cgl_c_locheure`;
CREATE TABLE `llx_cgl_c_locheure` (
  `rowid` int(11) NOT NULL auto_increment,
  code  varchar(6) CHARACTER SET utf8 COLLATE utf8_general_ci,
  `active` smallint(6) NOT NULL default '1',
  `libelle` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
  hdeb DECIMAL( 4, 2 ) NULL DEFAULT NULL ,
  hfin DECIMAL( 4, 2 ) NULL DEFAULT NULL ,
  `ordre` smallint(2) NOT NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `llx_cgl_c_locheure` (`rowid` ,`active` ,code, `libelle` ,hdeb, hfin, `ordre`)
VALUES (NULL , '1', '9-12','9 heures - 12 heures',9,12, '1');
INSERT INTO `llx_cgl_c_locheure` (`rowid` ,`active` ,code, `libelle` ,hdeb, hfin, `ordre`)
VALUES (NULL , '1', '9-18','9 heures - 18 heures', 9,18,'2');
INSERT INTO `llx_cgl_c_locheure` (`rowid` ,`active` ,code, `libelle` ,hdeb, hfin, `ordre`)
VALUES (NULL , '1', '14-18','14 heures - 18 heures',14,18, '3');
INSERT INTO `llx_cgl_c_locheure` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'Autre','Autres horaires', '3');



DROP TABLE IF EXISTS `llx_cgl_c_rando`;
CREATE TABLE  llx_cgl_c_rando(
  `rowid` int(11) NOT NULL auto_increment,
  code  varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci,
  `active` smallint(6)  NOT NULL default '1',
  `libelle` varchar(50)   CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  fk_service int(11) NULL,
  `ordre` smallint(2)  NOT NULL DEFAULT '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'NAV_55_VAE','Navacelles 55 km - VAE','1');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'NAV_45_VAE','Navacelles 45 km - VAE','5');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'AUM_36_VAE','Aumessas 36 km - VAE','10');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'ESP_24_VAE','Esparon 24 km - VAE','15');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'VV_VAE','Voix Verte 17 km - VAE','20');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'ROQ_BRE','Roquedure Saint Bresson - VAE','25');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'ST_LAUR','Saint Larurent Le Minier - VAE','30');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'ARRE_17_VTT','Voix Verte Arre - 17 km - VTT','100');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'BEZ_17_VTT','Voix Verte Bez - 17 km - VTT','105');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', '3PT_17_VTT','Voix Verte 2 Ponts - VTT','110');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'MIN_80_RT','Minier - Aigoual - 80 km - RT','200');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'LUZET_RT','La Luzette - Aigoual - RT','205');
INSERT INTO `llx_cgl_c_rando` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'AUTRE','Autre voir note','299');


DROP TABLE IF EXISTS `llx_cgl_c_mat_mad`;
CREATE TABLE  llx_cgl_c_mat_mad(
  `rowid` int(11) NOT NULL auto_increment,
  code  varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci,
  `active` smallint(6) NOT NULL default '1',
  `libelle` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
  fk_service int(11),
  `ordre` smallint(2) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'CASQ','Casque','1');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'SAC','Sacoche','5');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'KIT','Kit de réparation','15');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'CH_AIR','Chambre à air','20');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'BIDON','Bidon','25');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'PORT_CART','Porte Carte','30');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'POMPE','Pompe','35');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'ANTI_VOL','Antivol','40');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'CHARGEUR_VAE','Chargeur VAE','100');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'PRISE_VAE','MultiPrise VAE','105');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'CLE_VAE','Clés 15 VAE','110');
INSERT INTO `llx_cgl_c_mat_mad` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'AUTRE','Autre voir note','200');

DROP TABLE IF EXISTS `llx_cglinscription_bull_mat_mad`;
CREATE TABLE  llx_cglinscription_bull_mat_mad(
  `rowid` int(11) NOT NULL auto_increment,
  datec date not null,
  tms timestamp null,
  fk_mat_mad  int(11)  NOT NULL ,
  fk_bull  int(11)  NOT NULL ,
  qte int(3)  NULL ,
 `datedepose` DATE NULL ,
 `dateretrait` DATE NULL ,
  qteret int (3) NULL ,
  `ordre` smallint(2) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `llx_cglinscription_bull_rando`;
CREATE TABLE  llx_cglinscription_bull_rando(
  `rowid` int(11) NOT NULL auto_increment,
  datec date not null,
  tms timestamp null,
  fk_rando  int(11)  NOT NULL ,
  fk_bull  int(11)  NOT NULL ,
  fk_service int(11)  NULL DEFAULT '1',
  qte int (3) null,
  qteret int (3) NULL ,
  `ordre` smallint(2) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


DROP TABLE IF EXISTS `llx_cgl_c_caution`;
CREATE TABLE `llx_cgl_c_caution` (
  `rowid` int(11) NOT NULL auto_increment,
  `active` smallint(6) NOT NULL default '1',
  code  varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci null,
  `libelle` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
  `ordre` smallint(2) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `libelle` (`libelle`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;


INSERT INTO `llx_cgl_c_caution` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'CNI','Carte Identité','001');

INSERT INTO `llx_cgl_c_caution` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'PC','Permis de conduire','002');
INSERT INTO `llx_cgl_c_caution` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'CHQ','Chèque','003');
INSERT INTO `llx_cgl_c_caution` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'PAS','Passeport','004');
INSERT INTO `llx_cgl_c_caution` (`rowid` ,`active` ,code, `libelle` ,`ordre`)
VALUES (NULL , '1', 'xxx','Autre, voir Observation','005');


/*========================*/
/* Deuxième part */
ALTER TABLE `llx_cglinscription_bull_det` ADD `fk_fournisseur` INT( 11 ) NULL AFTER `materiel` 

/* Insere le lien pour déterminer le taux de TVA standard applicable aux locations ou inscription */
/*INSERT INTO `llx_const` (`rowid`, `name`, `entity`, `value`, `type`, `visible`, `note`, `tms`) VALUES (NULL, 'CGL_TAUX_TVA_DEFAUT', '1', '11', NULL, '1', 'L''identifiant de c_tva qui est le taux standard français', CURRENT_TIMESTAMP);
non utilisé */
/* s_typeTVA  obsolette */
/* récupération des données avant suppression 
Vérification
select  distinct s.nom , sp.lastname, sp.firstname, s_TypeTVA  , tva_assuj
FROM  llx_agefodd_session_extrafields as se 
LEFT JOIN llx_agefodd_session_formateur as sf on sf.fk_session = se.fk_object 
LEFT JOIN llx_agefodd_formateur as f on sf.fk_agefodd_formateur = f.rowid
LEFT JOIN  llx_socpeople as sp on sp.rowid = f.fk_socpeople
LEFT JOIN  llx_societe as s on sp.fk_soc = s.rowid
where ((s_TypeTVA  = 'Commissionnement' and tva_assuj = 1)
or (s_TypeTVA  = 'TVA 20%' and tva_assuj = 0)
or s_TypeTVA  not in ( 'TVA 20%','Commissionnement'))


Résultat
Tous ces moniteurs ont une information erronée dans leur société. Leus activités sont au commissionnement, et ils sont assujétti TVA 
Voir avec Mathieu avant installation
 CIGALE AVENTURE	CASTELLANO 	Marti 	Commissionnement 	1
CAMIN CEVENNES 	Loup 	Stéphane 	Commissionnement 	1
CAMIN CEVENNES 	BAUM 	Cédric 	Commissionnement 	1
ALTEO NATURE 	VAUBAILLON 	Cedric 	Commissionnement 	1
ALTEO NATURE 	NICOLAS 	Julien 	Commissionnement 	1
Hendrick LEQUEMENER 	LEQUEMENER 	Hendrick 	TVA sur la commission 	0
AMOZ RANDO 	POINSOT 	Caroline 	Commissionnement 	1
CENTRE EQUESTRE LA MOULINE 	Forge Equitation Randonnée 	  	Commissionnement 	1
AMOZ RANDO 	SARL Amoz Rando 	  	Commissionnement 	1
*/

/* livraison correction */
/* suppression dans agefodd/Configuration/Champs supplémentaires de session */
/*s_TVA
vérifier si les moniteurs sont non assujetti TVA*/


/* MultiUtilisateur */
ALTER TABLE `llx_cglinscription_bull` ADD `fk_createur` INT( 11 ) NOT NULL AFTER `datec` ;
UPDATE llx_cglinscription_bull set fk_createur = 3 where ref like 'BU%';

/* Date et heures remplage plage */
ALTER TABLE `llx_cglinscription_bull_det` CHANGE `dateretrait` `dateretrait` DATETIME NULL DEFAULT NULL ;
ALTER TABLE `llx_cglinscription_bull_det` CHANGE `dateretrait` `dateretrait` DATETIME NULL DEFAULT NULL ;

ALTER TABLE `llx_cglinscription_bull` CHANGE `datedepose` `datedepose` DATETIME NULL DEFAULT NULL ,
CHANGE `dateretrait` `dateretrait` DATETIME NULL DEFAULT NULL ;


UPDATE llx_cglinscription_bull set fk_createur = 2 where ref like 'LO%';

DROP TABLE `llx_cgl_c_locheure`;

ALTER TABLE `llx_cglinscription_bull` DROP `fk_locheure` ;

ALTER TABLE `llx_cglinscription_bull` ADD `top_caution` INT NOT NULL DEFAULT '0' AFTER `fk_caution` 

ALTER TABLE `llx_cglinscription_bull` ADD `top_doc` INT NOT NULL DEFAULT '0' AFTER `fk_caution` 
/* vérifier qu'il n'y a pas de contrat LO*/
UPDATE llx_cglinscription_bull set typebull = 'Insc'  

/* Positionner le Tiers Cigale Aventure comme Fournisseur*/
UPDATE `llx_societe` SET `fournisseur` = '1' WHERE `llx_societe`.`rowid` =3446


/* reprise info contact dans bulletin */
update  llx_cglinscription_bull_det as bd
set  NomPrenom = (select  concat(concat(lastname, ' '), case when firstname like '%?%' then '' else firstname end )
					from   llx_socpeople as ct where  fk_contact = ct.rowid) 
where bd.type = 0 


	update  llx_cglinscription_bull_det as bd
	set  taille = (select  taille 	from   llx_socpeople_extrafields as ct where  fk_contact = ct.fk_object) 
	where bd.type = 0 
	and taille <> (select  taille	from   llx_socpeople_extrafields as ct where  fk_contact = ct.fk_object) 

					
update   llx_cglinscription_bull_det as bd				
set   age = (select  s_age 	from   llx_socpeople_extrafields as ct where  fk_contact = ct.fk_object) 
where bd.type = 0 
and not exists (	select  ct2.s_age					from   llx_socpeople_extrafields as ct2 where  fk_contact = ct2.fk_object and  ct2.s_age like '%?%' or ct2.s_age ='inutile' ) 
and age <> 		(select  s_age 		from   llx_socpeople_extrafields as ct where  fk_contact = ct.fk_object)	

/* voir s'il y a quelque chiose à faire sur poids */
select  s_poids
		from   llx_socpeople_extrafields as ct where   isnull(s_poids) = false and s_poids > 0
	

	
/* verifier que le Bouche à oreille est bien traduit */
SELECT rowid, code, label  FROM llx_c_input_reason
 WHERE active=1  ORDER BY rowid
 
 /* mettre un ContactTiers dans chaque bulletin de cette année */
 		  
UPDATE  llx_cglinscription_bull  as b 
SET  b.fk_ContactTiers = 		(SELECT min(sp.rowid) as rowid 
					FROM  llx_societe as s ,llx_socpeople as sp
					 WHERE s.entity =1 
					 AND sp.fk_soc = s.rowid AND b.fk_tiers = s.rowid   )
 WHERE  (fk_ContactTiers = 0 or isnull(fk_ContactTiers)) AND ref like 'BU2015%'
 
 /* verifier que tous les lignes de bulletin ont un contact */
  select ref, bd.rowid, b.rowid
 from llx_cglinscription_bull as b, llx_cglinscription_bull_det as bd
 where fk_bull = b.rowid
 and (fk_contact = 0 or isnull(fk_contact))
 and ref like 'BU2015%'
-- sinon
   
 update llx_cglinscription_bull_det as bd
 set fk_contact =(select  fk_ContactTiers from llx_cglinscription_bull as b  where fk_bull = b.rowid)

 where  (fk_contact = 0 or isnull(fk_contact))
 and exists (select b1.rowid from  llx_cglinscription_bull as b1 where b1.ref  like 'BU2015%' and bd.fk_bull = b1.rowid)
 
 --ou
 
update    llx_cglinscription_bull_det as bd
  set fk_contact=(select fk_ContactTiers from llx_cglinscription_bull as b where fk_bull = b.rowid)
 where   (fk_contact = 0 or isnull(fk_contact))
 and  (select ref from llx_cglinscription_bull as b where fk_bull = b.rowid)  like 'BU2015%'
 
 -- commenter l'utilisation de customcode
 ALTER TABLE `llx_product` CHANGE `customcode` `customcode` VARCHAR( 32 ) CHARACTER 
		SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL 
		COMMENT 'Sert pour l''ordre d''affichage des services dans Contrat'
	-- trier les services */
	 UPDATE `llx_product` SET `customcode` = '2' WHERE `llx_product`.`rowid` =17 LIMIT 1 ; -- E-BIKE
	 UPDATE `llx_product` SET `customcode` = '1' WHERE `llx_product`.`rowid` =9 LIMIT 1 ; -- VTT
	 UPDATE `llx_product` SET `customcode` = '99' WHERE `llx_product`.`rowid` =14 LIMIT 1 ; -- MONITEUR
	 UPDATE `llx_product` SET `customcode` = '3' WHERE `llx_product`.`rowid` =16 LIMIT 1 ; -- ROUTE
	 UPDATE `llx_product` SET `customcode` = '9' WHERE `llx_product`.`rowid` =15 LIMIT 1 ; -- AUTRE
	 UPDATE `llx_product` SET `customcode` = '50' WHERE `llx_product`.`rowid` =18 LIMIT 1 ; -- TRANSPORT
	 
--- ajouter un champ s_pvexclu dans Agefodd/produit et agefodd/départ - libelle :Prix de vente pour un groupe en exclusion 

---- ajouter ahmp retour caution
ALTER TABLE `llx_cglinscription_bull` ADD `ret_doc` INT( 1 ) NULL DEFAULT '0' AFTER `ret_caution` 

SELECT  ref, statut, regle, ret_doc, ret_caution from llx_cglinscription_bull where  ret_doc <> ret_caution
UPDATE llx_cglinscription_bull set ret_doc = ret_caution  where  ret_doc <> ret_caution

--- pour le paiement par carte bleue
ALTER TABLE `llx_cglinscription_bull_det` CHANGE `num_cheque` `num_cheque` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL 
 
 -- pour l'abandon des bulletin
 ALTER TABLE `llx_cglinscription_bull` ADD `abandon` VARCHAR( 100 ) NULL AFTER `statut` 
 