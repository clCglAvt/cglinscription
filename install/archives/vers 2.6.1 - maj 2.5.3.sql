-- 25/03/2022 correction 

						
ALTER TABLE `llx_cglinscription_bull_det` CHANGE `fk_agsessstag` `fk_agsessstag` INT(11) NULL; 						
ALTER TABLE `llx_cglinscription_bull_det` CHANGE `fk_raisrem` `fk_raisrem` INT(11) NULL; 						
ALTER TABLE `llx_cglinscription_bull` CHANGE `filtrpass` `filtrpass` SMALLINT(6) NULL DEFAULT '0' 						
ALTER TABLE `llx_cglinscription_bull` CHANGE `top_doc` `top_doc` INT(11) NULL DEFAULT '0'; 						
ALTER TABLE `llx_cglinscription_bull` CHANGE `top_caution` `top_caution` INT(11) NULL DEFAULT '0'; 						
ALTER TABLE `llx_cglinscription_bull_det` CHANGE `tireur` `tireur` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL; 						


SELECT * FROM  llx_const  where name = 'CLICKTODIAL_USE_TEL_LINK_ON_PHONE_NUMBERS':
-- si cette variable existe et prend la valeur 0, alors passer la requete suivante
UPDATE llx_const set value = 0 where name = 'CLICKTODIAL_USE_TEL_LINK_ON_PHONE_NUMBERS':
-- si cette variable n'existe pas, alors passer la requete suivante
INSERT INTO `llx_const` (`rowid`, `name`, `entity`, `value`, `type`, `visible`, `note`, `tms`) 
	VALUES (NULL, 'CLICKTODIAL_USE_TEL_LINK_ON_PHONE_NUMBERS', '0', '0', 'chaine', '1', 
	'Permet d\'indiquer la présence du matériel pour lancement automatique d\'un appel téléphonique vers extérieur', '2013-12-27 15:40:11'); 


-- mettre à jour la société pour voir disparaître un message d'erreur réccurent 					
						
-- créer compte 'Acompte_non_remboursé' et mettre dans admin de 4 saisons

-- après livraison
ALTER TABLE `llx_agefodd_formateur` CHANGE `ventilation_vente` `ventilation_vente` VARCHAR(32) NULL DEFAULT NULL; 
