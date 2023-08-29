<?php
/*
 * Claude Castellano	claude@cigaleaventure.com
 *
 * Version CAV - 2.8 - hiver 2023 -
 *			- creation
 * Version CAV - 2.8.4 - printemps 2023 -
 *			- amélioration interface.. Rapport d'activité.
 *
 
 */

/**
 *	\file        htdocs/custom/cglinscription/class/html_formRapportSecteur.php
 *	\brief       Ogjet pour méthode du rapport
 */

class FormRapportSecteur
 {
	
		/*
	*	Préparation du js d'animation AfficheListeNiv2 (non utilisé actuellement)
	*	Préparation du js de réaffichage de la liste des secteurs sélectionnés
	*/
	function html_PrepScript()
	{
		$out = "";
		$out .= '<script> ';
		$out .= 'function AfficheListeNiv2(o)
				{
					document.getElementById("BtNiv1").style.visibility = "hidden";
					document.getElementById("lbsecteurs").innerText= "Sélectionnez les soussecteurs :";
					document.getElementById("secteurs").style.visibility = "hidden"
					
					let resultat=""
					let secteurs = document.getElementById("secteurs");
					let i = 0;
					if (secteurs == null) {
						alert("vide");
						return;
					};
					while (document.getElementById("secteurs").options[i])
					{
						if(secteurs.options[i].selected){
							
							let secteur_code =  secteurs.options[i].value;
							let secteur_label =  secteurs.options[i].text;
							
							}
						i++;
					}
					document.getElementById("BtNiv2").style.visibility = "visible";	
					document.getElementById("BtNiv2").type = "";	
					document.getElementById("BtNiv1").addEventListener("click", stopEvent, false);				
		}
		function stopEvent(ev)
		{
			ev.stopPropagation();
		}
		function AfficheListSecteurs(o)
		{
			let val = o.value;		
			// rechercher les secteurs sélectionnés dans  l_objet html de html_selectSecteur  -- pur js
			var tabSectSel = [];
			var param = "";
			for (let index in o.options) {
				if (index < 200 && o.options[index].selected) {
					param = param.concat(o.options[index].id).concat(" = ").concat(index);
					param = param.concat("&");
				}
			}
			// passer les secteurs sélectionnés en paramètre de l_URL			
			url="'.DOL_URL_ROOT.'/custom/cglinscription/ReqRapportSecteursSelects.php?".concat(param);
			var	Retour = creerobjet(url);	
			document.getElementById("ListeSectSelect").innerHTML = Retour;	
		}
		function creerobjet(fichier) 
		{  
				if(window.XMLHttpRequest)  
					xhr_object = new XMLHttpRequest();  
				else if(window.ActiveXObject) // IE  
					xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
				else 
						return(false);
				xhr_object.open("GET", fichier, false);
				xhr_object.send(null); 
				if(xhr_object.readyState == 4)
					return(xhr_object.responseText);
				else
				return(false); 
		} 
		
		"\n"';
		$out .= ' </script>';
		return $out ;
	} //html_PrepScript 

	/*
	* Prépare le style css des objets utilisés, pour la sélection multiple
	*
	* @param	int	$nbEleNiv1	Nombre de Secteurs Premier niveau affichés, pour tailler la fenêtre
	*
	*	return  script html à afficher
	*/
	function html_PrepStyle($nbEleNiv1)
	{
		$taille = $nbEleNiv1*25;
		$out =  '<style>
			select[multiple] {
				height: 2em;
				vertical-align: top;
			}
			select[multiple]:focus,
			select[multiple]:active {
				height: '.$taille.'px;
				
			}
			</style>';
		return $out;
	}//html_PrepStyle

	/*
	*	Renvoie le code HTML d'une boite de sélection sur le tableau d'entrée
	*
	*	@param	array	$tabSelect	Tableau ('label'=> valeur, ...)
	*	@param	string	$flgOrigine	Req si provient d'une Requete, pour réaffichage, donc sans entete
	*					vide si entete
	*	retour	string 	code HTML d'une boite de sélection remplie
	*/
	function  html_ListSelectionnes($tabSelect, $flgOrigine = "")
	{
		global $langs;

		if (empty( $flgOrigine)) $out = '<br ><b>'.$langs->trans("Sectorisation").'</b></br>';
			$out .=  '<span id=ListeSectSelect>';

			if (empty($tabSelect) or $tabSelect == -1) 
				$out .=  '<span style="display:inline-block;">liste vide</span>';
			else {
				foreach ($tabSelect as $SectSel){
					$out .=  $SectSel['label'].'<br>';
				} // foreach
			}
			$out .=  '</span><br>';
			return $out;
	} //html_ListSelectionnes

	/*
	*
	*	Construire le select avec la liste des secteurs de niveau 1 et les secteurs déjà sélectionnés
	*
	*	@param	array	$TabSects 	tableau des secteurs array()
	*	@param	array	$SectSels	Tableau des seceurs sélectionnés array(code)
	*
	*	@retourcode html de balise select
	*
	*/
	function  html_selectSecteur($TabSects, $SectSels)
	{	
		global $langs;
		
		if (empty($TabSects)) return -1;
		print '<label id="lbsecteurs" for="secteurs">Sélectionnez les secteurs :</label><br>';
		print ' <select multiple="oui" name="secteurs[]" id="secteurs" style="font_size:13px;" ';
		print ' onfocusout=AfficheListSecteurs(this) ';
		print '>';
		$i = 0;
		foreach ($TabSects as $Secteur) {
			$selected = '';
			if (!empty($SectSels)) {
				foreach ($SectSels as $SectSel) {
					if ($SectSel == $Secteur['code']){
						$selected = 'selected="selected"';
						break;
					}
				} // foreach
			}
			print ' <option id="secteurs['.$Secteur['id'].']" value='.$Secteur['code'].' '.$selected.' >'.$Secteur['label'].'</option>';
		}
		print '   </select>';
		print '</label>';
	} //html_selectSecteur
	 
	 /*
	*	html de la boite de sélection TypeRapport
	*
	*	param	$selected	Type de rapport pré-sélectionné
	*	
	*	retour 	code html de la boite de sélection
	*/
	function html_selectTypeRapport($selected)
	{
		global $langs;
		$out = "";
		$out .= '<label for="TypeRapport">'.$langs->trans('TypeRapport').' :</label><br>';
		$out .= ' <select  name="TypeRapport" id="secteurs" style="font_size:13px;" ">';

		$selectedCA = $selectedCHRG = $selectedMRG = '';
		if ($selected == 'CA') $selectedCA = 'selected';
		$out .= ' <option  value="CA"  '.$selectedCA.'  >'.$langs->trans('TypeRapCA').'</option>';
		if ($selected == 'CHRG') $selectedCHRG = 'selected';
		$out .= ' <option  value="CHRG" '.$selectedCHRG.' >'.$langs->trans('TypeRapChrg').'</option>';
		if ($selected == 'MRG') $selectedMRG = 'selected';
		$out .= ' <option  value="MRG" '.$selectedMRG.' >'.$langs->trans('TypeRapM').'</option>';

		$out .= '   </select>';
		$out .= '</label>';
		return $out;
	} //html_selectTypeRapport

	/*
	*
	*	Calcul les années et affiche les titres des colonnes
	*
	*	@param	string	$modecompta 	(CA, MRG ou CHRG)
	*	@param	$year_start				année de début
	*	@param	$year_end				année de fin
	*
	*	retour
	*/
	function Entete_rapport($modecompta, $year_start,$year_end )
	{
		global $conf, $langs;
		
		print '<tr class="liste_titre"><td>&nbsp;</td>';
		for ($annee = max(2022,$year_start); $annee <= $year_end; $annee++) {
			if ($modecompta == 'CREANCES-DETTES') {
				print '<td align="center" width="10%" colspan="3">';
			} else {
				print '<td align="center" width="10%" colspan="2" class="borderrightlight">';
			}
			
			if ($modecompta != 'BOOKKEEPING') {
				print '<a href="'.DOL_MAIN_URL_ROOT.'/compta/stats/casoc.php?year='.$annee.'">';
			}
			print $annee;
			if ($conf->global->SOCIETE_FISCAL_MONTH_START > 1) {
				print '-'.($annee + 1);
			}
			if ($modecompta != 'BOOKKEEPING') {
				print '</a>';
			}
			print '</td>';
			if ($annee != $year_end) {
				print '<td width="15">&nbsp;</td>';
			}
		}
		print '</tr>';

		print '<tr class="liste_titre"><td class="liste_titre">'.$langs->trans("Month").'</td>';
		for ($annee = max(2022,$year_start); $annee <= $year_end; $annee++) {
			if ($modecompta == 'CREANCES-DETTES') {
				print '<td class="liste_titre right">'.$langs->trans("AmountHT").'</td>';
			}
			print '<td class="liste_titre right">';
			if ($modecompta == "BOOKKEEPING") {
				print $langs->trans("Amount");
			} else {
				print $langs->trans("AmountTTC");
			}
			print '</td>';
			print '<td class="liste_titre right borderrightlight">'.$langs->trans("Delta").'</td>';
			if ($annee != $year_end) {
				print '<td class="liste_titre" width="15">&nbsp;</td>';
			}
		}
		print '</tr>';
	} //Entete_rapport

	/*
	* Recherches des montants et constitutions des tables
	* Les arguments doient avoir été vérifié avant
	*
	*/

	/*
	*
	*
	*/
	function Affichage($cum, $cum_ht, $year_start, $year_end, $month_end, $date_startyear, $date_endmonth, $date_endyear, 
		$minyearmonth, $maxyearmonth, $modecompta)
	{
		global $conf, $langs;
		
		
		$now_show_delta = 0;
		$minyear = substr($minyearmonth, 0, 4);
		$maxyear = substr($maxyearmonth, 0, 4);
		$nowyear = strftime("%Y", dol_now());
		$nowyearmonth = strftime("%Y-%m", dol_now());
		//$maxyearmonth = max($maxyearmonth, $nowyearmonth);
		$now = dol_now();
		$casenow = dol_print_date($now, "%Y-%m");
		
		// Loop on each month
		$date_startmonth = GETPOSTISSET('date_startmonth', 'int');
		$nb_mois_decalage = $date_startmonth ? ($date_startmonth - 1) : (empty($conf->global->SOCIETE_FISCAL_MONTH_START) ? 0 : ($conf->global->SOCIETE_FISCAL_MONTH_START - 1));
		for ($mois = 1 + $nb_mois_decalage; $mois <= 12 + $nb_mois_decalage; $mois++) {
			$mois_modulo = $mois; // ajout
			if ($mois > 12) {
				$mois_modulo = $mois - 12;
			} // ajout

			if ($year_start == $year_end) {
				// If we show only one year or one month, we do not show month before the selected month
				if ($mois < $date_startmonth && $year_start <= $date_startyear) {
					continue;
				}
				// If we show only one year or one month, we do not show month after the selected month
				if ($mois > $date_endmonth && $year_end >= $date_endyear) {
					break;
				}
			}

			print '<tr class="oddeven">';

			// Month
			print "<td>".dol_print_date(dol_mktime(12, 0, 0, $mois_modulo, 1, 2000), "%B")."</td>";

			for ($annee = max(2022,(int)$year_start - 1); $annee <= $year_end; $annee++) {	// We start one year before to have data to be able to make delta
				$annee_decalage = $annee;
				if ($mois > 12) {
					$annee_decalage = $annee + 1;
				}
				$case = dol_print_date(dol_mktime(1, 1, 1, $mois_modulo, 1, $annee_decalage), "%Y-%m");
				$caseprev = dol_print_date(dol_mktime(1, 1, 1, $mois_modulo, 1, $annee_decalage - 1), "%Y-%m");

				if ($annee >= $year_start) {	// We ignore $annee < $year_start, we loop on it to be able to make delta, nothing is output.
					if ($modecompta == 'CREANCES-DETTES') {
						// Value turnover of month w/o VAT
						print '<td class="right">';
						if ($annee < $year_end || ($annee == $year_end && $mois <= $month_end)) {
							if ($cum_ht[$case]) {
								$now_show_delta = 1; // On a trouve le premier mois de la premiere annee generant du chiffre.

								print '<a href="'.DOL_MAIN_URL_ROOT.'/compta/stats/casoc.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">'.price($cum_ht[$case], 1).'</a>';
							} else {
								if ($minyearmonth < $case && $case <= max($maxyearmonth, $nowyearmonth)) {
									print '0';
								} else {
									print '&nbsp;';
								}
							}
						}
						print "</td>";
					}

					// Value turnover of month
					print '<td class="right">';
					if ($annee < $year_end || ($annee == $year_end && $mois <= $month_end)) {
						if ($cum[$case]) {
							$now_show_delta = 1; // On a trouve le premier mois de la premiere annee generant du chiffre.
							if ($modecompta != 'BOOKKEEPING') {

								print '<a href="'.DOL_MAIN_URL_ROOT.'/compta/stats/casoc.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">';
							}
							print price($cum[$case], 1);
							if ($modecompta != 'BOOKKEEPING') {
								print '</a>';
							}
						} else {
							if ($minyearmonth < $case && $case <= max($maxyearmonth, $nowyearmonth)) {
								print '0';
							} else {
								print '&nbsp;';
							}
						}
					}
					print "</td>";

					// Percentage of month
					print '<td class="borderrightlight right">';
					//var_dump($annee.' '.$year_end.' '.$mois.' '.$month_end);
					if ($annee < $year_end || ($annee == $year_end && $mois <= $month_end)) {
						if ($annee_decalage > $minyear && $case <= $casenow) {
							if ($cum[$caseprev] && $cum[$case]) {
								$percent = (round(($cum[$case] - $cum[$caseprev]) / $cum[$caseprev], 4) * 100);
								//print "X $cum[$case] - $cum[$caseprev] - $cum[$caseprev] - $percent X";
								print ($percent >= 0 ? "+$percent" : "$percent").'%';
							}
							if ($cum[$caseprev] && !$cum[$case]) {
								print '-100%';
							}
							if (!$cum[$caseprev] && $cum[$case]) {
								//print '<td class="right">+Inf%</td>';
								print '-';
							}
							if (isset($cum[$caseprev]) && !$cum[$caseprev] && !$cum[$case]) {
								print '+0%';
							}
							if (!isset($cum[$caseprev]) && !$cum[$case]) {
								print '-';
							}
						} else {
							if ($minyearmonth <= $case && $case <= $maxyearmonth) {
								print '-';
							} else {
								print '&nbsp;';
							}
						}
					}
					print '</td>';

					if ($annee_decalage < $year_end || ($annee_decalage == $year_end && $mois > 12 && $annee < $year_end)) {
						print '<td width="15">&nbsp;</td>';
					}
				}

				if ($annee < $year_end || ($annee == $year_end && $mois <= $month_end)) {
					$total_ht[$annee] += ((!empty($cum_ht[$case])) ? $cum_ht[$case] : 0);
					$total[$annee] += $cum[$case];
				}
			}

			print '</tr>';
		}

	} //Affichage

} // Class