select ref 
from  llx_cglinscription_bull as b
where 
(select SUM(pu*(100-rem)*qte/100) from llx_cglinscription_bull_det as bd where bd.fk_bull = b.rowid and bd.type = 0 and bd.action not in ('S','X')) >0
AND
 not exists (select (1) from  llx_cglinscription_bull_det as bd, llx_agefodd_session_formateur as sf where fk_bull = b.rowid and sf.fk_session = fk_activite)
and regle <=5