REM - vérification chaque bulletin facturé a le même nombrre de départs achetés que la facture correspondante a de lignes de factures

select T1.fk_facture
from (
SELECT Tb.fk_facture , COUNT( Tbd.rowid)  as NbDepart FROM llx_cglInscription_bull AS Tb, llx_cglInscription_bull_det as  Tbd
where  Tb.rowid = Tbd.fk_bull  and tbd.action not in ('X','S') and tbd.type = 0 GROUP BY Tb.fk_facture) as T1, 

(SELECT Tbd.fk_facture, COUNT( Tbd.rowid) as NbLigne FROM llx_facturedet AS Tbd GROUP BY Tbd.fk_facture) as T2
where T1.fk_facture = T2.fk_facture
and NbDepart <> NbLigne