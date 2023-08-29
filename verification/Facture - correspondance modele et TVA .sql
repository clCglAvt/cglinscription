--Verification Model Correspondant à la présence ou non de TVA
select ref
from llx_facture 
where model_pdf in ("crabe","TVA")
and (select SUM(tva) from llx_facturedet where fk_facture = llx_facture.rowid) = 0


select ref
from llx_facture 
where model_pdf in ("FAComm","FAmarge")
and (select SUM(tva) from llx_facturedet where fk_facture = llx_facture.rowid) = 0 > 0

