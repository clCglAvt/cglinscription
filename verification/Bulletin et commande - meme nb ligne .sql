select ref 
from  llx_cglinscription_bull as b
where   (select COUNT(rowid)  from llx_cglinscription_bull_det where fk_bull = b.rowid and type = 0 and action not in ('X','S')) <>
(select COUNT(rowid) from llx_commandedet where b.fk_cmd = fk_commande)
and (select SUM(pu*(100-rem)*qte/100) from llx_cglinscription_bull_det as bd where bd.fk_bull = b.rowid and bd.type = 0 and bd.action not in ('S','X')) > 0
 