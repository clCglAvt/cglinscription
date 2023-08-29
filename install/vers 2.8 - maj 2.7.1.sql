-- 12/12/2022 correction 

- Modification de la table c_paiement pour ajouter le compte bancaire associé

	- revoir au moment de TEST
		- ajouter un champ dans c_paiement : fk_cpt_bk 
				ALTER TABLE `llx_c_paiement` ADD `fk_cpt_bq` INT(11) NULL AFTER `fl_regroupneg`; 
		- transferer les données de cgl_pmt_bank dans c_paiement
				UPDATE `llx_c_paiement` 
				SET `fk_cpt_bq` = (select b.rowid from llx_cgl_pmt_bank as pb 
							left join llx_bank_account as b on fk_code_bq = b.ref 
							where code_paiement = code);
				select code, `fk_cpt_bq`, 
					(select b.rowid from llx_cgl_pmt_bank as pb 
							left join llx_bank_account as b on fk_code_bq = b.ref 
							where code_paiement = code) as AA ,
					 (select fk_code_bq  from llx_cgl_pmt_bank as pb 						
							where code_paiement = code) as BB
					from llx_c_paiement as pc
		- supprimer la table aprè-s verification
			drop table llx_cgl_pmt_bank
	

- modif liées à la montée de version Mysql
	ALTER TABLE `llx_c_paiement` CHANGE `fl_regroupneg` `fl_regroupneg` TINYINT(4) NULL DEFAULT '0' COMMENT ' indique si les montants négatifs sont succeptibles d\'etre dans un bordereau (ex : un remboursement client en paiement en ligne)'; 

	ALTER TABLE `llx_c_paiement` CHANGE `fl_regroup` `fl_regroup` TINYINT(4) NULL DEFAULT '0' COMMENT 'indique si les écritures de ce mode de paiement sont regroupées en une seule ligne sur le relevé bancaire, suite à un bordereau de dépot ou une liste de télécollecte'; 
		


- Ajouter un champ Facturable à la table bulletin		
	ALTER TABLE `llx_cglinscription_bull` ADD `facturable` INT(1) NULL DEFAULT NULL AFTER `fk_dossier`; 
