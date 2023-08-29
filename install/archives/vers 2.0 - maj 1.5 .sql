

-- Version 2.0 pour une migration en Dolibarr 8.0.3

-- Supprimer le module IMPRAP
 
 delete FROM `llx_const` WHERE name like '%imprap%'
 
-- changer la colonne fk_tiers pour la rendre compatible Dolibarr 
ALTER TABLE llx_cglinscription_bull DROP FOREIGN KEY fk_cglinscription_bull_fk_tiers;
DROP INDEX idx_fk_tiers ON llx_cglinscription_bull;

ALTER TABLE llx_cglinscription_bull CHANGE fk_tiers fk_soc  INTEGER NOT  NULL;
CREATE INDEX idx_fk_tiers ON  llx_cglinscription_bull (fk_soc);

ALTER TABLE llx_cglinscription_bull ADD CONSTRAINT fk_cglinscription_bull_fk_tiers FOREIGN KEY (fk_soc)     REFERENCES llx_societe(rowid);

-- Mettre à Clôturer les factures 2018 payées de montant 0
select fk_statut = 2 from llx_facture as f  where f.facnumber LIKE '%%18__-%%' and f.total = 0 and fk_statut = 1
update llx_facture as f set fk_statut = 2 where f.facnumber LIKE '%%18__-%%' and f.total = 0 and fk_statut = 1
select fk_statut = 2 from llx_facture as f  where f.facnumber LIKE '%%18__-%%' and f.total = 0 and fk_statut = 1

-- Mettre à Clôturer  les bulletins dont la facture est clôturée
select  statut , 4    from llx_cglinscription_bull as b   
  where 
   statut <> 4
  and exists (select 1 from llx_facture as f  where b.fk_facture = f.rowid and fk_statut = 2 )
 
update llx_cglinscription_bull as b   set  statut = 4  
  where 
   statut <> 4
  and exists (select 1 from llx_facture as f  where b.fk_facture = f.rowid and fk_statut = 2 )
  
select  statut , 4    from llx_cglinscription_bull as b   
  where 
   statut <> 4
  and exists (select 1 from llx_facture as f  where b.fk_facture = f.rowid and fk_statut = 2 )
  
-- Mettre à Clôturer  les factures dont la bulletin est archivé  
select  facnumber, fk_statut, ref, statut   from llx_facture as f   
  where , llx_cglinscription_bull as b 
   fk_statut <> 2 and b.fk_facture = f.rowid and statut = 4
   
   update  llx_facture as f  set  fk_statut = 2
  where 
   fk_statut <> 2
  and exists (select 1 from llx_cglinscription_bull as b  where b.fk_facture = f.rowid and statut = 4 )
 
select  fk_statut   from llx_facture as f   
  where 
   fk_statut <> 2 
  and exists (select 1 from llx_cglinscription_bull as b    where b.fk_facture = f.rowid and statut = 4)
   
-- mettre à Clôturer toutes les factures dont les paiements correspondent au total de la facture
 
update llx_facture as f2 set fk_statut = 2 
where rowid in (select rowid from
			 (select  rowid, facnumber ,   total_ttc, fk_statut, 
		  case when isnull(PaiementDirect) then 0 else PaiementDirect end  as PaiementDirect,  
		case when isnull(PaiementAvoir) then 0 else PaiementAvoir end  as PaiementAvoir,   
		case when isnull(PaiementAcompte) then 0 else PaiementAcompte end  as PaiementAcompte
			 
			from  
			 (select rowid, facnumber ,   total_ttc, fk_statut, 
				(SELECT sum(amount)  FROM llx_paiement_facture WHERE llx_paiement_facture.fk_facture = f.rowid) as PaiementDirect,
				(SELECT sum(rc.amount_ttc)  FROM llx_societe_remise_except as rc, llx_facture as f1 WHERE rc.fk_facture_source=f1.rowid and rc.fk_facture = f.rowid  AND (f1.type = 2 OR f1.type = 0)) as PaiementAvoir,
				(SELECT sum(rc.amount_ttc)  FROM llx_societe_remise_except as rc , llx_facture as f1 WHERE rc.fk_facture_source=f1.rowid  and rc.fk_facture = f.rowid  AND f1.type = 3) as PaiementAcompte
				from llx_facture as f
			 where fk_statut <2 
				group by rowid, facnumber ,   total_ttc, fk_statut
			 )as Tb1
			 ) as TB
where total_ttc = PaiementDirect+PaiementAvoir+PaiementAcompte )


-- desactiver les harges sociales des pays étrangers
 update llx_c_chargesociales set active = 0 where fk_pays <> 1 
 update llx_c_revenuestamp set active = 0

-- compta - vérifier la suite
begin;
UPDATE `llx_accounting_account` set `pcg_type` = 'INCOME' WHERE `fk_pcg_version` LIKE 'PLAN ANALYT'  and `pcg_type` ='PRODUITS';
UPDATE `llx_accounting_account` set `pcg_type` = 'EXPENSE' WHERE `fk_pcg_version` LIKE 'PLAN ANALYT'  and `pcg_type` ='CHARGES';
Commit;
SELECT * FROM `llx_accounting_account` WHERE `fk_pcg_version` LIKE 'PLAN ANALYT'  and `pcg_type` in ('PRODUITS','CHARGES')

-- Ajouter les code Client et Fournisseur aux tiers

update llx_societe set code_client = concat('CL',concat(concat(convert(year(datec), char),  '-'),lpad(convert(rowid, char) ,8,0))) where client >0 and isnull(code_client)	 ;
update llx_societe set code_fournisseur = concat('FO',concat(concat(convert(year(datec), char),  '-'),lpad(convert(rowid, char) ,6,0)))  
 where  fournisseur = 1 ;
  
select code_client, code_fournisseur, datec, concat('CL',concat(concat(convert(year(datec), char),  '-'),lpad(convert(rowid, char) ,8,0))), code_compta, code_compta_fournisseur, nom , fournisseur, client from llx_societe where client = 1 and not isnull(code_client)				

select code_client, code_fournisseur,code_compta, code_compta_fournisseur,datec, concat('CL',concat(concat(convert(year(datec), char),  '-'),lpad(convert(rowid, char) ,6,0))), nom , fournisseur, client from llx_societe where  fournisseur = 1  and not isnull(code_fournisseur)

			 
			 
-- changer la colonne colonne pour la rendre compatible Dolibarr
