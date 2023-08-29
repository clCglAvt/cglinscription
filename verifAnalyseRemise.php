<?php
/* Copyright (C) 2018-2019  Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 *
 * Version CAV - 2.7 - été 2022 - Création
 * Version CAV - 2.8 - hiver 2023 - ajout des total par année 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/*
*
* liste  des acomptes avec un  paiement et une écriture négtive ou positif sur autre que Stripe
*
*/
// Put here all includes required by your class file

require '../../main.inc.php';
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
//require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/stripe.lib.php';
//require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
//require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
//require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
//require_once DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctDolibarrRevues.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/html.formcommun.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta',  'stripe', 'cglinscription'));

/* PARAMETRES URL */
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;

$search_annee = GETPOST('search_annee', 'string');
if (empty($search_annee)) $search_annee = dol_print_date(dol_now(), '%Y');
$search_fk_raisrem = GETPOST('search_fk_raisrem', 'string');

if (empty($conf->global->CGL_FIN_ANNEE) ) $conf->global->CGL_FIN_ANNEE = 4;

/* VARIABLE */
/* Securité*/
$result = restrictedArea($user, 'banque');


/*
 * AFFICHAGE
 */

/* Affichage Cadre Dolibarr */
llxHeader('', $langs->trans("AnalyseRemise"));



// Clique sur bouton Vider les filtres
if (GETPOST("button_removefilter_x")) {
	$search_fk_raisrem = '';
    $search_annee='';
}
	

/* VIEW */

$title = $langs->trans("AnalyseRemise");
print_barre_liste($title, "", '', "", '', '', '', 0, 0, "", 0, '', '',"");

AfficheSolde($search_annee);
/* LECTURE DONNEES */
$resultsql = Lecture_Donnees($search_annee, $search_fk_raisrem);


//Affiche boite selection Remise

//Filtres
AfficheFiltre($search_annee,$search_fk_raisrem);

/* entete*/
AfficheEnteteDet();

// Affichage des virements Stripe dans l'ordre décroissant des dates

		$num = $db->num_rows($resultsql);
	
		$i = 0; 
		while ($i < $num)
		{		
			$objp = $db->fetch_object($resultsql);
			AfficherLigne ( $objp);
			$i++;
		}
print '<tr><td>';
	print '</table>';



print '</div>';
print '</form>';
	
// End of page
llxFooter();
$db->close();


function AfficheFiltre($annee,$remise)
{
	global $langs, $conf;
		
		
	$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;

	print '<table id="ListeRemise" class="liste" width="100%">';
	print '<tr  class="liste_titre" >';	
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'">';
			print '<input type="hidden" name="token" value="'.newtoken().'">';
			print '<input type="hidden" name="limit" value="'.$limit.'">';
	print '<td class="liste_titre">';
	print '<td class="liste_titre">'.$langs->trans('Annee').'</td>';
	print '<td class="liste_titre">';
	print select_annee($annee,'search_annee', '',0,1);
	print '</td>';	
	print_liste_field_titre("Remise",$_SERVER["PHP_SELF"],"","","",'',"","");
	print '<td class="liste_titre">';
	$wfc1 = new FormCglCommun ($db);
	print $wfc1->select_nomremise($remise,'search_fk_raisrem','', '',0,1);
	unset($wfc1 );
	print '</td>';	
		
	print '<td class="liste_titre"></td>';
	// boutons de validation et suppression du filtre
	print '<td><td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '</form>';
	print "</tr>\n";
} //AfficheFiltre

function AfficheEnteteDet() 
{
	global $langs;
	
	print '<tr class="liste_titre">';
	print_liste_field_titre("Facture ",$_SERVER["PHP_SELF"],"","","","","", "");
	print_liste_field_titre("Tiers", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("BU", $_SERVER["PHP_SELF"], "", "", "", '', "", "", 'left ');
	print_liste_field_titre("Remise", $_SERVER["PHP_SELF"], "", "", "", '', "", "", 'center ');
	print_liste_field_titre("NbRemise", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("MttRemise", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("FactHT", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("FactTT", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	
	print "</tr>";
	
}	//AfficheEnteteDet
function AfficheEnteteSolde($tabAnnee) 
{
	global $langs;
	
	$anneemin = min($tabAnnee);
	$anneemax = max($tabAnnee);
	print '<tr class="liste_titre">';
	print_liste_field_titre("Annee",$_SERVER["PHP_SELF"],"","","","","", "");
	for ($i=$anneemin; $i<=$anneemax;$i++) 
		print_liste_field_titre((string)$i, $_SERVER["PHP_SELF"], "", "", "", "align='center' colspan=3", "", "");
	
	print "</tr>";
	print '<tr class="liste_titre">';
	print_liste_field_titre("",$_SERVER["PHP_SELF"],"","","","","", "");
	for ($i=$anneemin; $i<=$anneemax;$i++) {
		print_liste_field_titre($langs->trans('MttRem'), $_SERVER["PHP_SELF"], "", "", "", "", "", "");
		print_liste_field_titre($langs->trans('FactHT'), $_SERVER["PHP_SELF"], "", "", "", "", "", "");
		print_liste_field_titre($langs->trans('FactTT'), $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	}
	print "</tr>";
}	//AfficheEnteteSolde

function Lecture_Donnees($annee, $id_rem)
{
	global $conf, $langs, $db;	
	
	if ($conf->global->MAIN_VERSION_LAST_UPGRADE == "15.0.1") $champTotalHT='total_ht';
	else $champTotalHT='total';	
	
	$sql="
		select f.ref as FctRef, f.rowid as IdFact, st.nom, st.rowid as IdTiers, b.ref as BuRef, b.rowid as IdBu, count(fd.rowid) as NbRem, 
			sum(fd.remise_percent  * fd.subprice/100) as MttRem, f.".$champTotalHT." as total_htFactHT , f.total_ttc as total_htFactTT,
			case when isnull(cr.libelle) then 'Non renseigne' else cr.libelle end as label

		from ".MAIN_DB_PREFIX."facture as f 
			LEFT JOIN ".MAIN_DB_PREFIX."facturedet as fd on fd.fk_facture = f.rowid and fd.remise_percent > 0
			LEFT JOIN ".MAIN_DB_PREFIX."societe as st on st.rowid = f.fk_soc
			LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b on b.fk_facture = f.rowid
			LEFT JOIN  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid = bd.fk_bull and bd.action not in ('X','S') and bd.type in (0,2) and bd.rem > 0
			LEFT JOIN  ".MAIN_DB_PREFIX."cgl_c_raison_remise as cr on cr.rowid = bd.fk_raisrem

		where    bd.fk_linefct = fd.rowid   
		";
		if (!empty($annee)) $sql.=" and  year(b.datec) = '".$annee."'";
		if (!empty($id_rem) and $id_rem <> -1) $sql.=" and bd.fk_raisrem = '".$id_rem."'";

		$sql.=" group by f.ref , f.rowid , st.nom, st.rowid , b.ref , b.rowid , cr.libelle ";
	
		$result = $db->query($sql);
		if (!$result){
				dol_syslog('Lecture_Donnees:: ', LOG_ERR);
				return NULL;
			}	
			
	return $result;
} //Lecture_Donnees

function Lecture_Donnees_Solde()
{
	global $conf, $langs, $db;	
	
	if ($conf->global->MAIN_VERSION_LAST_UPGRADE == "15.0.1") $champTotalHT='total_ht';
	else $champTotalHT='total';	

	$sql="
		select year(b.datec) as annee, cr.libelle as label, 
			case when isnull(cr.libelle) then 'Non renseigne' else cr.libelle end as label,  
			sum(fd.remise_percent  * fd.subprice/100) as MttRem, sum(f.".$champTotalHT.") as total_htFactHT , sum(f.total_ttc) as total_htFactTT

		from ".MAIN_DB_PREFIX."facture as f 
			LEFT JOIN ".MAIN_DB_PREFIX."facturedet as fd on fd.fk_facture = f.rowid and fd.remise_percent > 0
			LEFT JOIN ".MAIN_DB_PREFIX."societe as st on st.rowid = f.fk_soc
			LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b on b.fk_facture = f.rowid
			LEFT JOIN  ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid = bd.fk_bull and bd.action not in ('X','S') and bd.type in (0,2) and bd.rem > 0
			LEFT JOIN  ".MAIN_DB_PREFIX."cgl_c_raison_remise as cr on cr.rowid = bd.fk_raisrem

		where    bd.fk_linefct = fd.rowid  "; 
	
	$sql.=" and  year(f.datec) between year(now()) - ".$conf->global->CGL_FIN_ANNEE. "  and year(now())";
	$sql.=" group by cr.libelle, year(b.datec)
		order by cr.libelle, year(b.datec)
		";
		$result = $db->query($sql);
		if (!$result){
				dol_syslog('Lecture_Donnees_Solde:: ', LOG_ERR);
				return NULL;
			}	
			
	return $result;
} //Lecture_Donnees_Solde

function AfficherLigne ( $obj)
{	
	global $langs;		    

	print "<tr>";

	// Facture	
	$url = 'href="' . DOL_MAIN_URL_ROOT . '/compta/facture/card.php?ref='.$obj->FctRef.'"';
	print "<td  class='center' ><a ".$url." >".$obj->FctRef."</a></td>\n";
	
	// Tiers
	$url = 'href="' . DOL_MAIN_URL_ROOT . '/societe/card.php?socid='.$obj->IdTiers.'"';
	print "<td  class='center' ><a ".$url." >".$obj->nom."</a></td>\n";
	
	// Bulletin
	if (!(strpos($obj->BuRef,  "BU") === false)) $url = 'href="' . DOL_MAIN_URL_ROOT . '/custom/cglinscription/inscription.php?bull='.$obj->IdBu.'"';
	elseif (!(strpos($obj->BuRef,  "LO") === false)) $url = 'href="' . DOL_MAIN_URL_ROOT . '/custom/cglinscription/location.php?bull='.$obj->IdBu.'"';
	print "<td  class='center' ><a ".$url." >".$obj->BuRef."</a></td>\n";
	
	// Remise
	print "<td   >".$obj->label."</td>\n";
	
	// NombreRemise
	print "<td   >".$obj->NbRem."</td>\n";
	
	// total_ht remise
	print '<td class="right">';
	print price($obj->MttRem , 0, '', 1, -1, -1, '€');
	print "</td>";
	
	// total_ht facture
	print '<td class="right">';
	print price($obj->total_htFactHT , 0, '', 1, -1, -1, '€');
	print "</td>";
	
	// total_ht facture
	print '<td class="right">';
	print price($obj->total_htFactTT , 0, '', 1, -1, -1, '€');
	print "</td>";
	
	print "</tr>";
} // Afficher

function AfficheSolde($annee)
{
		global $db, $langs;
		
	print '<table id="AnalyseRemise" class="liste" width="100%">';
	$resultsql = Lecture_Donnees_Solde();
	/* entete*/
	$num = $db->num_rows($resultsql);

	$tabSolde = array();
	$tabAnnee = array();

	// Affichage des virements Stripe dans l'ordre décroissant des dates
			$i = 0; 
			while ($i < $num) {
				$objp = $db->fetch_object($resultsql);
				$tabSolde[$objp->label][$objp->annee] = ["MttRem"=>$objp->MttRem,"total_htFactHT"=>$objp->total_htFactHT,"total_htFactTT"=>$objp->total_htFactTT ];
				$tabAnnee[$objp->annee] = $objp->annee;
				$i++;
			} // While $i < $num
			
		//	var_dump ($tabSolde);
	AfficheEnteteSolde($tabAnnee);

	// Affichage des différentes remises
			foreach($tabSolde as $key => $tablabel )
			{
				$ancannee = min($tabAnnee);
				print "<tr>";
				// Remise
				print "<td  class='center' >".$key."</td>\n";
				foreach($tablabel as $key1 => $labelannee) {
					for ($i = $ancannee;$i <= max($tabAnnee)+1; $i++){ 
						if ($i == $key1) {
							AfficherLigneSolde ($labelannee);
							$ancannee=$key1;
							break;
						}
						else {
							if (!($ancannee +1 == $key1 )) {
									AfficherLigneSolde ("");							
							}
						}
					$tabtotalHT[$i] += $labelannee['MttRem'] ;
					} // for
				} // foreach
			print "</tr><tr>";
			}
			// Ligne des totaux
			print '<tr><td  align="right">Total';
			print '</td>';
			$i=min($tabAnnee);
			$max = max($tabAnnee);
			while (	$i < $max) 
			{
				print '<td>';
				print price($tabtotalHT[$i] , 0, '', 1, -1, -1, '€');
				print '</td>';
				print '<td></td>';
				print '<td></td>';
				$i++;
			}
			
			print '</td></tr>';
		print '</table>';

	print '</div>';
	print '</form>';
} //Affiche	Solde

function AfficherLigneSolde($tab)
{
	global $langs;
	// total_ht remise
	print '<td >';
	if (!empty($tab)) print price($tab['MttRem'] , 0, '', 1, -1, -1, '€');
	print "</td>";
	
	// total_ht facture
	print '<td >';
	if (!empty($tab)) print price($tab['total_htFactHT'] , 0, '', 1, -1, -1, '€');
	print "</td>";
	
	// total_ht facture
	print '<td >';
	if (!empty($tab)) print price($tab['total_htFactTT'] , 0, '', 1, -1, -1, '€');
	print "</td>";
	
} //AfficherLigneSolde

function select_annee($selected='',$htmlname, $option='',$disabled=false,$useempty='')
{
	global $langs, $db;

		$disabled = ($disabled ? ' disabled' : '');	  
        $out='';
        $TabActPart=array();
        $label=array();
        $sql .= "SELECT distinct year(datec) as annee ";
        $sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull ";
        dol_syslog("verifAnalyseRemise::select_annee ");
		
		
        $resql=$db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" '.$disabled.' '.$option.' '.$htmloption.'>';			
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
				if ($useempty) $out .= '<option value="-1"'.(($value < 0)?' selected':'').'></option>'."\n";
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
					if ($selected == $obj->annee && $selected != '-1' )
                    {
                        $out.= '<option value="'.$obj->annee.'" selected="selected">';
                    }
                    else
					{
                        $out.= '<option value="'.$obj->annee.'">';
                    }
                    $out.= $obj->annee;
                    $out.= '</option>';
					$i++;
                }
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($db);
        }
        return $out;
} //select_annee

?>