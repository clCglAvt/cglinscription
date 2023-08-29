select ref
from  llx_cglinscription_bull as b
where (select SUM(pu*(100-rem)*qte/100) from llx_cglinscription_bull_det as bd where bd.fk_bull = b.rowid and bd.type = 0 and bd.action not in ('S','X')) < (select SUM(pt) from llx_cglinscription_bull_det as bd where bd.fk_bull = b.rowid and bd.type = 1 and bd.action not in ('S','X'))