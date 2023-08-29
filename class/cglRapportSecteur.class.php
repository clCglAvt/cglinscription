<?php
/*
 * Claude Castellano	claude@cigaleaventure.com
 *
 * Version CAV - 2.8 - hiver 2023 -
 *			- creation
 * Version CAV - 2.8.4 - printemps 2023 -
 *			- amélioration interface.. Rapport d'activité.
 *			- calcul HT - TTC à réparer
 * Version CAV - 2.8.5 - printemps 2023
 *		- Prendre les factures non encore liées  (326)
 * 
 */

/**
 *	\file        htdocs/custom/cglinscription/class/cglProdSecteur.php
 *	\brief       Ogjet pour méthode du rapport
 */

class cglRapportSecteur
 {
	
	var $db	;// Acces à la base de données
	
	function __construct ($db) 
	{
			$this->db = $db;
	}
	/*
	* Permet de construite la table des secteurs
	*
	*	retourne tab des secteurs de niveau 1
	*/
	function ConstSecteur()
	{			
		$tabDonnees=array();
		
		$sql = 	"SELECT ac1.rowid as id,  `code`, `label` , `range_account`, `sens`,   `active`, `position`, `formula`,";
		$sql .= " (select count(rowid) from ".MAIN_DB_PREFIX."c_accounting_category as ac2
					where ac1.formula like concat(concat('%',ac2.code), '%') and active = 1 and ac1.rowid <> ac2.rowid) as NbEnfants ,";
		$sql .= " (select  count(rowid) from ".MAIN_DB_PREFIX."c_accounting_category as ac2 where ac2.formula like concat(concat('%',ac1.code), '%') 
					and active = 1 and ac1.rowid <> ac2.rowid) as NbParents ,";
		$sql .= " category_type as type ";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_accounting_category as ac1";
		$sql .= " WHERE active = 1  ";
		$sql .= " and entity = 1 ";
		$sql .= " and (select  count(rowid) from llx_c_accounting_category as ac2 
				where ac2.formula like concat(concat('%',ac1.code), '%') and active = 1 and ac1.rowid <> ac2.rowid) = 0";
		$sql .= ' ORDER BY position ';

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$line = array('id'=>$obj->id,'code'=>$obj->code, 'label'=>$obj->label, 'formula'=>$obj->formula) ;
				$tabDonnees[] = $line;
				$i++;
			} // While
		}
		return $tabDonnees;
	} //ConstSecteur
	/*
	* Construit la table des secteurs sélectionnés
	*
	*	@param array 	$tabSects	table de référence des secteurs de la base
	*	@param array 	$secteurs	table des secteurs demandés
	*
	* 	retour		Table des secteurs sélctionnes, avec code, libellé et formule
	*				-1 si la table de référence est vide
	*				-2 si aucun secteurs sélectionnés
	*/	
	function ConstSectSel($tabSects, $secteurs, $coloneComp = 'code')
	{		
		$tabResult = array();
		if (empty($secteurs)) return -1;
		if (empty($tabSects)) return -2;
		foreach($secteurs as $key => $val) {
			foreach ($tabSects as $SectDol) {
				if (($coloneComp == 'code' and $val == $SectDol['code'])
					or ($coloneComp == 'id' and $key == $SectDol['id']))
					{						
					$tabResult[] = array('id'=>$SectDol['id'], 'code'=>$SectDol['code'], 'label'=>$SectDol['label'], 'formula'=>'');
					break;
				}
			}
		}		

		if (count($tabResult) == 0) return null;
		return $tabResult;
	} //ConstSectSel
	
	/*
	* Recherches des montants et constitutions des tables
	* Les arguments doient avoir été vérifié avant
	*
	*	@param	string	$modecompta		Mode de comptabilité
	*	@param	string	$type			'achats','ventes'
	*	@param	string	$jour_start		n° jour dans le mois de départ du calcul
	*	@param	string	$mois_start		n° mois dans l'année de départ du calcul 
	*	@param	string	$annee_start	année de départ du calcul 
	*	@param	string	$jour_endt		n° jour dans le mois de fin du calcul 
	*	@param	string	$mois_end		n° mois dans l'année de fin du calcul  
	*	@param	string	$annee_end		année de fin du calcul  
	*	@param	array	$secteurs		tableau des secteurs sélectionnés	
	*	@param	array	$cum			modifiable, cumul TTC 
	*	@param	array	$cum_ht			modifiable, cumul HT
	*	@param	string	$minyearmonth	modifiable, AAAAMM de démarrage du calcul
	*	@param	string	$maxyearmonth	modifiable, AAAAMM de fin du calcul
	*	@param	string	$coeff			1 si les montants sont affichés en positifs, -1 si les montants des achats sont déduits du CA pour donner la marge.
	*
	*	retour	néant
	*/
	function ConstTabDonnes($modecompta, $type, $jour_start, $mois_start, $annee_start, $jour_end, $mois_end, $annee_end, $secteurs,&$cum,&$cum_ht,
		 &$minyearmonth,  &$maxyearmonth, $coeff)
	{
		global $conf;	
		$now_show_delta = 0;
		$minyear = substr($minyearmonth, 0, 4);
		$maxyear = substr($maxyearmonth, 0, 4);
		$nowyear = strftime("%Y", dol_now());
		$nowyearmonth = strftime("%Y-%m", dol_now());
		$maxyearmonth = max($maxyearmonth, $nowyearmonth);
		$now = dol_now();
		$casenow = dol_print_date($now, "%Y-%m");
		
		//$debyymm=max(2022,$annee_start).sprintf("%02d",$mois_start);

		$yearchgplancompt = $conf->global->CGL_ANNEE_CHG_PLANCOMPATABLE;
		if (empty($yearchgplancompt)) $yearchgplancompt = 0;
		$annee_deb = max($yearchgplancompt, $annee_start);
		$annee_fin = max($yearchgplancompt, $annee_end);
		

		
		for ($moisaff = 1; $moisaff <= 12; (int)$moisaff++) 
		{	
			for ($anneeaff = $annee_deb; $anneeaff <= (int)$annee_fin; (int)$anneeaff++)
			{	
				if ($anneeaff ==  $annee_deb and $moisaff < $mois_start)  break;
				if ($anneeaff ==  $annee_fin and $moisaff > $mois_end)  break;
/*



FROM llx_facture as f  
LEFT JOIN llx_facturedet as fd on f.rowid = fd.fk_facture 
LEFT JOIN llx_product as p  on  p.rowid = fd.fk_product , 
( llx_accounting_account as cp 
LEFT JOIN llx_c_accounting_category as cpcat  on cp.fk_accounting_category  = cpcat.rowid)  


WHERE   p.accountancy_code_sell = cp.account_number 
 AND f.fk_statut in (1,2) AND f.type IN (0,1,2,3,5) 
AND f.entity IN (1)  AND cp.rowid =  fd.fk_code_ventilation AND cpcat.code   in ('LOCATION_VELOS') 
 AND date_format(f.date_valid, "%Y-%m") = "2023-07"
 AND date_format(f.date_valid, "%d") > 1
 
 
 
*/				
				$sql = $sqlwheredate = $sqlwheresecteur = "";
				//extraite de la base les ligne de factures
				$sql = "SELECT date_format(f.date_valid,'%Y-%m') as dm, sum(fd.total_ht) as amount, sum(fd.total_ttc) as amount_ttc";
				if ($type == 'ventes') {
					$sql .= " FROM ".MAIN_DB_PREFIX."facture as f ";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facturedet as fd on f.rowid = fd.fk_facture  ";
					$sql .= " LEFT JOIN llx_product as p  on  p.rowid = fd.fk_product   ";
				}
				else {
					$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f ";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn_det as fd on f.rowid = fd.fk_facture_fourn  ";
				}

				if (!empty($secteurs)) {
					$sql .= ", ( ".MAIN_DB_PREFIX."accounting_account as cp ";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_accounting_category as cpcat  on cp.fk_accounting_category  = cpcat.rowid) ";
				}

				$sql .= " WHERE p.accountancy_code_sell = cp.account_number  ";
				$sql .= "  AND f.fk_statut in (1,2)";
				if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
					$sql .= " AND f.type IN (0,1,2,5)";
				} else {
					$sql .= " AND f.type IN (0,1,2,3,5)";
				}
				$sql .= " AND f.entity IN (".getEntity('invoice').") ";
				if (!empty($secteurs)) $sql .= " AND cp.rowid =  fd.fk_code_ventilation ";
				// concernant les comptes associés aux secteurs selectionnés,
				if (!empty($secteurs))
				{
					$sqlwheresecteur .= " AND cpcat.code   in (";
					$i=0;
					foreach ($secteurs as $secteur){
						if ($i>0) $sqlwheresecteur .= ',';
						$sqlwheresecteur .= "'".$secteur."',";
					}
					$sqlwheresecteur = substr($sqlwheresecteur, 1, strlen($sqlwheresecteur) - 2);
					$sqlwheresecteur .= ')';
				}
				// suivant les dats recherchées
				$moisaff = sprintf("%02d", $moisaff);
				$sqlwheredate .= ' AND date_format(f.date_valid, "%Y-%m") = "'.$anneeaff.'-'.$moisaff.'" ';
				//$sqlwheredate .= ' AND date_format(f.date_valid, "%Y-%m") = "2021-09" ';
				if ($moisaff == $mois_start)
					$sqlwheredate .= ' AND date_format(f.date_valid, "%d") > '.$jour_start.' ';
				if ($moisaff == $mois_end)
					$sqlwheredate .= ' AND date_format(f.date_valid, "%d") <= '.$jour_end.' ';
				$sql .= $sqlwheresecteur.' ' .$sqlwheredate;
				$sql .= 'GROUP BY dm';
				
				$result = $this->db->query($sql);
				if ($result) {
					$num = $this->db->num_rows($result);
					$i = 0;
					// constituer les tableaux	
					while ($i < $num) {
						$obj = $this->db->fetch_object($result);

						//$cum_ht[$obj->dm] += !empty($obj->amount) ? $obj->amount : 0;
						if ($coeff== 1)	$cum_ht[$obj->dm] += !empty($obj->amount) ? $obj->amount : 0;
						else 	$cum_ht[$obj->dm] -= !empty($obj->amount) ? $obj->amount : 0;

						if ($coeff== 1) $cum[$obj->dm] += !empty($obj->amount_ttc) ? $obj->amount_ttc : 0;	
						else  $cum[$obj->dm] -= !empty($obj->amount_ttc) ? $obj->amount_ttc : 0	;											
						if ($obj->amount_ttc) {
							$minyearmonth = ($minyearmonth ?min($minyearmonth, $obj->dm) : $obj->dm);
							$maxyearmonth = max($maxyearmonth, $obj->dm);
						}
						$i++;						
					} // while
				}
			}  // boucle annee
			
		} // boucle mois

	} //ConstTabDonnes

	function Tab2Url($tab, $htmlname)
	{	
		if (empty($tab)) return;
		$out = '';
		foreach ($tab as $element)
		{
			if (!empty($out)) $out .= '&';
			$out .= $htmlname.'[]='.$element;
		}
		return $out;
	} // Tab2Url

} // Class