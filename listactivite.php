<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
  *
 * Version CAV - 2.7 - été 2022
 *					 - Remplacer GET par POST
 *					 - Migration Dolibarr V15
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       custom/cglinscription/listactivite.php
 *		\ingroup    cglinscription
 *		\brief      Cahier d'accueil - Liste les activités faites par un client
  */
ini_set('magic_quotes_gpc', 1);
if ('Include' == 'Include') {
	// Change this following line to use the correct relative path (../, ../../, etc)
	$res=0;

	if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	// Change this following line to use the correct relative path from htdocs
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once './class/cglinscription.class.php';
	require_once './class/bulletin.class.php';
	require_once './class/html.formcommun.class.php';
	require_once '../cglavt/class/cglFctCommune.class.php';
	
	// Load traductions files requiredby by page
	$langs->load("other");
	$langs->load("companies");
	$langs->load("cglinscription@cglinscription");
}

if ('GETPOST' == 'GETPOST') {
	// Get parameters
//	$page		= GETPOST("page",'int');

	// récupération des paramètre de l'URL - paramètre
	$id=trim(GETPOST("id", 'int'));

}	
// Protection if external user
if ($user->societe_id > 0)  {
	accessforbidden();
}


if ('Variable'=='Variable') {
	$help_url='FR:Module_Inscription';	
	$w = new CglFonctionCommune($db) ;
	global $w;
	
	$bull = new Bulletin ($bd);
	global $bull;
}
				
/***************************************************
* ENTETE
****************************************************/ 
 llxHeader('',$langs->trans('LTiersActivite'));

/***************************************************
* AFFICHAGE 
****************************************************/ 
$url=$_SERVER["PHP_SELF"];
		
if ('Affichage' == 'Affichage') {
	// permet d'afficher le petit livre, le titre la succession des page et le numéro de la  page courante
	// paramètres a passer dans les boutons de page successives

	$params = "&amp;sortfield=".$sortfield."&amp;sortorder=".$sortorder;
	$params.= "&amp;socid=".$id;
	 // ENTETE
	 AfficheEntete($id);
	// TITRE
	
			 
	$titre = 'LstinscriptionsClient';
	$title=$langs->trans($titre);
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$nbtotalofrecords,$nbtotalofrecords);
	AfficheInscription($id);
	
	$titre = 'LstContratClient';
	$title=$langs->trans($titre);
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$nbtotalofrecords,$nbtotalofrecords);	
	AfficheLocation($id);
	
	$titre = 'LstResaClient';
	$title=$langs->trans($titre);
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$nbtotalofrecords,$nbtotalofrecords);
	AfficheResa($id);
	
	
		$titre = 'LstAvoirAcomptet';
	$title=$langs->trans($titre);
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$nbtotalofrecords,$nbtotalofrecords);
	
	AfficheAvoirAcompte($id);	
}

 print '</div>';	
 print '</form>';

// End of page
llxFooter();
 print '</div>'; // fin de la page entière
$db->close();
	ini_set('magic_quotes_gpc', 0);
	
	
function getNomUrl($withpicto=0,$option='',$maxlen=0, $id)
{
        global $conf,$langs;
        $result='';
		$lienfin='</a>';

		if ($option == 'MAJInscritp')		{
			$result = '<a href="./inscription.php?id_bull='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifInsc").'">';
		}	
		elseif ($option == 'MAJLoc')		{
			$result = '<a href="./location.php?id_contrat='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifLoc").'">';
		}	

		elseif ($option == 'MAJResa')		{
			$result = '<a href="./reservation.php?id_resa='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifResa").'">';
		}			
		elseif ($option == 'Tiers'){
			 $result = '<a href="'.DOL_MAIN_URL_ROOT.'/comm/card.php?socid='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("AideModifTiers").'">';
		}	
       $result.=$lienfin;
       return $result;
	}//getNomUrl
	
function AfficheEntete($id)
{
	global $db, $langs, $conf; 
	
		 	
	$form=new Form($db);
	$tiers= new Societe($db);	
	$tiers->fetch($id);;
	
	if ($tiers->id <= 0) {
		setEventMessage($tiers->error, 'errors');
	}

	$head = societe_prepare_head($tiers);
		 
	dol_fiche_head($head, 'tabactivite', $langs->trans("ThirdParty"), 0, 'company');
		
	print '<table class="border" width="100%">';

	print '<tr><td width="25%">' . $langs->trans("ThirdPartyName") . '</td><td colspan="3">';
	print $form->showrefnav($tiers, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');
	print '</td></tr>';

	if (! empty($conf->global->SOCIETE_USEPREFIX)) // Old not used prefix field
	{
		print '<tr><td>' . $langs->trans('Prefix') . '</td><td colspan="3">' . $tiers->prefix_comm . '</td></tr>';
	}
	 
	// EMail
	print '<tr><td>' . $langs->trans('EMail') . '</td><td colspan="3">';
	print dol_print_email($tiers->email, 0, $tiers->id, 'AC_EMAIL');
	print '</td></tr>';
		 
	// Web
	print '<tr><td>' . $langs->trans('Web') . '</td><td colspan="3">';
	print dol_print_url($tiers->url);
	print '</td></tr>';
		 
	// Phone / Fax
	print '<tr><td>' . $langs->trans('Phone') . '</td><td>' . dol_print_phone($tiers->phone, $tiers->country_code, 0, $tiers->id, 'AC_TEL') . '</td>';
	print '<td>' . $langs->trans('Fax') . '</td><td>' . dol_print_phone($tiers->fax, $tiers->country_code, 0, $tiers->id, 'AC_FAX') . '</td></tr>';

	print '</table>';
	
	print '</div>';
	
}// AfficheEntete

function NbPmtNeg ($id) 
{
		global $langs, $db;

		// si modification, penser à bulletin, listloc et facturation
		
			$sql = "SELECT count(bd.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull";
		$sql.= " WHERE bd.type = 1 and pt  <0 and bd.action not in ('X','S') ";
		$sql.= " and b.rowid = '".$id."'";	
        $resql=$db->query($sql);
        if ($resql)   {
            if ($db->num_rows($resql) > 0) {
                $obj = $db->fetch_object($resql);
				return(  $obj->nb);
			}
			else return 0;
		}
		else return -1;		
	} // NbPmtNeg
	
function AfficheInscription($idTiers)
{	
	global $langs, $conf, $db ,$w, $bull;

	if ('OrdreSql'=='OrdreSql') {

		// pour Tiers, il faut aller chercher les info dans un SQL spécifique dépendant de arg_idtiers
			
		// Recherche des dossiers du tiers passé en argument - tiers du dosseir ou interlocuteur d'un echange du dossier
		$sql = "SELECT distinct b.rowid as rowid, AgS.intitule_custo, AgS.rowid as id_act, T.nom, T.rowid as id_client, b.statut, b.regle, b.ref, b.ObsPriv, b.typebull, b.fk_facture, b.datec, b.abandon, ";
		$sql .= "CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(u.firstname,' '),u.lastname) ELSE '' END  as  createur, AM.rowid as id_moniteur,  ";
		$sql.= "case when isnull(UM.lastname) then concat(CM.lastname ,' ',CM.firstname ) else concat(UM.lastname,' ',UM.firstname ) end AS Moniteur, ";
		$sql.= "AgS.dated as dated,ASCl.heured,ASCl.heuref, AgS.type_session  ";
		$sql.= ",  sum(bd.qte) as NbFam , ActionFuture, PmtFutur ";
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";
		$sql.= " left join ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull and (isnull(bd.action) or bd.action not in ('S','X')) and bd.type = 0 ";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_session as AgS  on bd.fk_activite =  AgS.rowid ";
		$sql.= " left join ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_session_formateur as ASM on AgS.rowid = ASM.fk_session";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_formateur as AM on AM.rowid = ASM.fk_agefodd_formateur ";
		$sql.= " left join ".MAIN_DB_PREFIX."socpeople as CM on AM.fk_socpeople = CM.rowid ";
		$sql.= " left join ".MAIN_DB_PREFIX."user as UM on AM.fk_user = UM.rowid ";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_formation_catalogue as AP on AgS.fk_formation_catalogue = AP.rowid ";
		$sql.= " left join ".MAIN_DB_PREFIX."agefodd_session_calendrier as ASCl on AgS.rowid = ASCl.fk_agefodd_session ";
		$sql .= " left join ".MAIN_DB_PREFIX."user as u on u.rowid = b.fk_user " ;
		$sql .= "WHERE T.rowid = '".$idTiers."'  and b.typebull = 'Insc'  ";	
		$sqlgroup = " GROUP BY b.rowid, AgS.intitule_custo, T.nom, UM.lastname,CM.lastname,CM.firstname,UM.lastname, AgS.dated,ASCl.heured,ASCl.heuref";		
		$sql .=$sqlgroup;
		$sql.= " ORDER  BY b.ref DESC";
		
		//******************* LECTURE
		$sql.= $db->plimit($conf->liste_limit+1, $offset);
		$resql = $db->query($sql);
		if ($resql	)   	$num = $db->num_rows($resql);
	}
	if ($num) {
		
		// affichage barre de sélection
	print '<table class="liste" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("DateDepart",$_SERVER["PHP_SELF"],"dated","",$params,'',$sortfield,$sortorder);
		print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"Ref","",$params,'',$sortfield,$sortorder);
		print_liste_field_titre("TiRegle",$_SERVER["PHP_SELF"],"regle","",$params,'',$sortfield,$sortorder);
		print_liste_field_titre("TiAction",'','','','','','','');
		print_liste_field_titre("Session",$_SERVER["PHP_SELF"],"AgS.intitule_custo","",$params,'',$sortfield,$sortorder);
		print_liste_field_titre("UNAgfFormateur",$_SERVER["PHP_SELF"],"Moniteur","",$params,'',$sortfield,$sortorder);
		print_liste_field_titre("NbFam",$_SERVER["PHP_SELF"],"NbFam","",$params,'',$sortfield,$sortorder);
		print_liste_field_titre("HeureDebut",$_SERVER["PHP_SELF"],"heured","",$params,'',$sortfield,$sortorder);
		print_liste_field_titre("TiObs",'',"","",'','','','');
		print_liste_field_titre("",'',"","",'','','','');
		print_liste_field_titre("TiStatut",$_SERVER["PHP_SELF"],"statut","",$params,'',$sortfield,$sortorder);
		print "</tr>\n";

		// affiche la barre grise de titres des filtres
		// affiche la barre grise des filtres		
		print "<tr>\n";

		$var=True;
		$i=0;
		$var = 0;
		while ($i < $num)	{
				$obj = $db->fetch_object($resql); // dans le cas dossier, cela a déjà été lu 	
				print "<tr $bc[$var]>";
				if ($obj->statut == $bull->BULL_ENCOURS) {  // en gras 
					$gras = '<b>';
					$fingras = '</b>';
				}	
				else	{  // retour normal
					$gras = '';
					$fingras = '';
				}		

				print "<td>";
				/* affiche l'image pour la selection */
				
				print " ".$gras.$w->transfDateFr($obj->dated)."</td>\n";	
				if ($obj->typebull == 'Insc') $arg = 'MAJInscritp';
				elseif ($obj->typebull == 'Loc') $arg = 'MAJLoc';
				elseif ($obj->typebull == 'Resa') $arg = 'MAJResa';
				print "<td>".getNomUrl("object_company.png", $arg,0,$obj->rowid)."&nbsp".$gras.$obj->ref."</td>";	
				print '<td>';
				//if (!empty($obj->PmtFutur)) {	$texte =  $obj->PmtFutur; 		print info_admin($texte,1); }
				
				// Paiement futur
				if (!empty($obj->PmtFutur)) {
					$texte =  $obj->PmtFutur; 
					print '<td><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png"></td>';
				}
				
				print '</td>';
				print "<td>";
				if (!empty($obj->ActionFuture)) {
					$texte = $obj->ActionFuture; 
					print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png">';
				}
				print "</td>";
				print '<td>'.getNomUrl("object_company.png", 'DepartEcran',0,$obj->id_act,'');
				print "   ".$gras.$obj->intitule_custo."</td>";
				print '<td>'.getNomUrl("object_company.png", 'Moniteur',0,$obj->id_moniteur,'');
				print " ".$gras.$obj->Moniteur."</td>";
				print "<td>".$gras.$obj->NbFam."</td>";
				print "<td>".$gras.$w->transfHeureFr($obj->heured)."</td>";	
			//	print "<td>".$gras.number_format ( $obj->PTT , 2 , ',' , ' ' )."</td>";
			//	print "<td>".$obj->price_ttc."</td>\n";
				print "<td>";
				if ( !empty($obj->ObsPriv )) {
					$text = $langs->trans("DefObsPriv").':'.$obj->ObsPriv;
					print info_admin($text,1);
				}
					print "</td>";

		//print '<input type="image" src="../../theme/'.$conf->theme.'/img/edit.png" border="0" name="tiers_edit" alt="'.$langs->trans("Modif").'">';
				print "</td>\n";
		print "</td>\n";
		print '<td>';
		$wfrmcm = new FormCglCommun ($db);
		print $wfrmcm->AffichImgStatutBull($obj->statut, $obj->regle, 'Insc', $obj->fk_facture) ;
		print '</td>';	
/*
		print '<td>';
		$texte='';
		$img = '';
		if ($obj->statut == $bull->BULL_ENCOURS) { $img=$bull->IMG_ENCOURS ; $texte=$bull->LIB_ENCOURS;}
		if ($obj->statut == $bull->BULL_PRE_INS) { $img=$bull->IMG_PRE_INS ; $texte=$bull->LIB_PRE_INS;}
		elseif ($obj->regle ==0 and $obj->statut ==1 and !empty($obj->fk_facture)) {$img=$bull->IMG_FACT_INC; $texte=$bull->LIB_FACT_INC;}
		elseif ($obj->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_FACTURE; $texte=$bull->LIB_FACT_INC;}
		elseif ($obj->statut == $bull->BULL_INS) {$img=$bull->IMG_INS; $texte=$bull->LIB_INS;}
		elseif ($obj->statut == $bull->BULL_CLOS) {$img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
		elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
		if (empty($img) and !empty($texte)) 
			print info_admin($texte,1);		
		elseif (!empty($texte))
			print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
		else print $obj->statut;	
		print '</td>';
*/
/*			if ($obj->regle == $bull->BULL_NON_PAYE  and ($obj->montantdu > 0 or ($obj->statut == $bull->BULL_INS  and !empty($obj->dated)))) { $img=$bull->IMG_NON_PAYE; $texte = $bull->LIB_NON_PAYE;}
				elseif ($obj->regle == $bull->BULL_NON_PAYE){ $img=''; $texte = '';}
				elseif ($obj->regle ==$bull->BULL_INCOMPLET) {$img=$bull->IMG_INCOMPLET; $texte=$bull->LIB_INCOMPLET;}
				elseif ($obj->regle ==$bull->BULL_PAYE or ($obj->regle == $bull->BULL_ARCHIVE and !empty($obj->fk_facture))) { $img=$bull->IMG_PAYE; $texte=$bull->LIB_PAYE;}
				elseif ($obj->regle ==$bull->BULL_SURPLUS) {$img=$bull->IMG_SURPLUS; $texte=$bull->LIB_SURPLUS;}
				elseif ($obj->regle ==$bull->BULL_REMB) {$img=$bull->IMG_REMB; $texte=$bull->LIB_REMB;}
				elseif (!empty($obj->abandon)) { $img=''; $texte = '';}
				else { $img = ''; $texte = 'inconnu '. $obj->regle;}
				print '<td>';
				if (!empty($texte)) print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
				print "</td>";	
*/
	
		print '<td>';
		print $wfrmcm->AffichImgRegleBull( $obj->regle, 'Insc', $obj->statut, $obj->dated, $obj->fk_facture, $obj->abandon );
		unset ($wfrmcm);
		print '</td>';	

				$nb = NbPmtNeg($obj->rowid);
				print "<td>";
				if ($nb >0) {
					if ($nb > 1) $text = $langs->trans("DefPmtNegs");
					else $text = $langs->trans("DefPmtNeg");
					print info_admin($text,1);
				}
				print "</td>";
				print "</tr>\n";
				$var=!$var;
				$i++;				
		}	
		print "</tr></table>\n";
		unset($bull);
	}
	AfficheNvBouton ('Inscription', $idTiers);
}//AfficheInscription

function AfficheLocation($idTiers)
{	
	global $langs, $conf, $db ,$w, $bull;
		// pour Tiers, il faut aller chercher les info dans un SQL spécifique dépendant de arg_idtiers			
		// Recherche des dossiers du tiers passé en argument - tiers du dosseir ou interlocuteur d'un echange du dossier
	if ('OrdreSql'=='OrdreSql') {
		$sql = "SELECT";
		$sql .= " distinct ";
		$sql .= " b.rowid as rowid,  T.nom, T.rowid as id_client, b.statut, b.regle ,  b.ref , b.ObsPriv, b.ActionFuture, b.PmtFutur, b.fk_facture ";
		$sql .= ", CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END  as  createur ";
		$sql .= ", min(bd.dateretrait) as dateretrait, max(bd.datedepose) as datedepose ,  SUM(bd.qte) as NbVelo ";	
		$sql .= ", (select SUM(bd.qte - case when isnull(bd.qteret) then 0 else bd.qteret end ) from llx_cglinscription_bull_det as bd  where  bd.fk_bull = b.rowid ) as NbVeloAtt ";		
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";

			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X')";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on bd.fk_activite = p.rowid";
		
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";

		 $sql .= "  LEFT JOIN  llx_user as u  on u.rowid = fk_createur ";
			$sql .= "WHERE T.rowid = '".$idTiers."' AND  b.typebull = 'Loc' ";	
			
			$sql .= " GROUP BY b.rowid,  T.nom, b.statut, b.regle, b.ref";
			$sql.= " ORDER BY  b.ref DESC";
			//******************* LECTURE
			$sql.= $db->plimit($conf->liste_limit+1, $offset);
			$resql = $db->query($sql);
		if ($resql	)   	$num = $db->num_rows($resql);
	}	
	if ($num) {			
			// affichage barre de sélection
		print '<table class="liste" width="100%">';
			print '<tr class="liste_titre">';
			print_liste_field_titre("DateRetrait",$_SERVER["PHP_SELF"],"dateretrait","",$params,'',$sortfield,$sortorder);
			print_liste_field_titre("RefCnt",$_SERVER["PHP_SELF"],"ref","",$params,'',$sortfield,$sortorder);
			print_liste_field_titre("TiRegle",$_SERVER["PHP_SELF"],"regle","",$params,'',$sortfield,$sortorder);
			print_liste_field_titre("TiAction",'','','','','','','');
			print_liste_field_titre("NbVelo",'','',"",'','','','');
			print_liste_field_titre("TiObs",'',"","",'','','','');
			print_liste_field_titre("TiStatut",$_SERVER["PHP_SELF"],"statut","",$params,'',$sortfield,$sortorder);
			print_liste_field_titre("",'',"","",'','','','');
			print_liste_field_titre('','',"","",'','','','');
			print "</tr>\n";

			// affiche la barre grise de titres des filtres
			// affiche la barre grise des filtres	
			$var=True;
			$i=0;
			$var = 0;
			while ($i < $num)	{
				print "<tr ".$bc[$var].">";
				$obj = $db->fetch_object($resql); // dans le cas dossier, cela a déjà été lu 	
		
				if ($obj->statut == $bull->BULL_ENCOURS) {  // en gras les bulletins en cours
					$gras = '<b>';
					$fingras = '</b>';
				}	
				else	{  // retour normal
					$gras = '';
					$fingras = '';
				}	
				print "<td>";
				/* affiche l image pour la selection */	
				if (!empty($obj->dateretrait) and  $obj->dateretrait <> 0) 				
					print " ".$gras.$w->transfDateFr($obj->dateretrait).'&nbsp&nbsp&nbsp'.$w->transfHeureFr($obj->dateretrait)."</td>\n";
				
				print "<td>".$gras;
				print getNomUrl("object_company.png", 'MAJLoc',0,$obj->rowid)."&nbsp";
				print $gras.$obj->ref."</td>";
						
				print "<td>";
				// Paiement futur
				if (!empty($obj->PmtFutur)) {
					$texte =  $obj->PmtFutur; 
					print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png">';
				}
				print '</td>';
				
				print "<td>";
				if (!empty($obj->ActionFuture)) {
					$texte = $obj->ActionFuture; 
					print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png">';
				}
				print "</td>";

				if (empty($type)) print "<td>".$gras.$obj->NbVelo."</td>";
				print $fingras;
				
				print "<td>";
				if ( !empty($obj->ObsPriv )) {
					$text = $langs->trans("DefObsPriv").':'.$obj->ObsPriv;
					print info_admin($text,1);
				}	
				print "</td>";
	
/*				if ($obj->statut == $bull->BULL_ENCOURS) { 	 $img=$bull->IMG_ENCOURS; $texte=$bull->LIB_CNT_ENCOURS;}
				elseif ($obj->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_CNT_FACT_INC; $texte=$bull->LIB_CNT_FACT_INC;}
				elseif ($obj->regle < $bull->BULL_FACTURE and $obj->statut == $bull->BULL_CLOS and !empty($obj->fk_facture)) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACT_INC;}
				elseif ($obj->regle == $bull->BULL_ARCHIVE) {$img=$bull->IMG_CNT_ARCHIVE; $texte=$bull->LIB_CNT_ARCHIVE;}
				elseif ($obj->statut == $bull->BULL_VAL ) { $img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
				elseif ($obj->statut == $bull->BULL_DEPART ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_DEPART;}
				elseif ($obj->statut == $bull->BULL_RETOUR ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_RETOUR;}
				elseif ($obj->statut == $bull->BULL_CLOS ) { $img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
				elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
				else { $img = ''; $texte = 'inconnu '. $obj->statut;}
				 print '<td><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'"></td>';

		print '<td>';
		$texte='';
		$img = '';
		if ($obj->statut == $bull->BULL_ENCOURS) { $img=$bull->IMG_ENCOURS ; $texte=$bull->LIB_CNT_ENCOURS;}
		if ($obj->statut == $bull->BULL_PRE_INS) { $img=$bull->IMG_PRE_INS ; $texte=$bull->LIB_PRE_INS;}
		elseif ($bull->BULL_FACTURE and $obj->statut == $bull->BULL_CLOS and !empty($obj->fk_facture)) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACT_INC;}
		elseif ($obj->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_CNT_FACT_INC; $texte=$bull->LIB_CNT_FACT_INC;}
		elseif ($obj->statut == $bull->BULL_VAL) {$img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
		elseif ($obj->statut == $bull->BULL_CLOS) {$img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
		elseif ($obj->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
		if (empty($img) and !empty($texte)) 
			print info_admin($texte,1);		
		elseif (!empty($texte))
			print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
		else print $obj->statut;	
*/
		print "</td>\n";
		print '<td>';
		$wfrmcm = new FormCglCommun ($db);
		print $wfrmcm->AffichImgStatutBull($obj->statut, $obj->regle, 'Loc', $obj->fk_facture) ;
		print '</td>';	

/*		if ($obj->regle == $bull->BULL_NON_PAYE  and ($obj->montantdu > 0 or $obj->statut == $bull->BULL_VAL )) { $img=$bull->IMG_NON_PAYE; $texte=$bull->LIB_NON_PAYE;}
				elseif ($obj->regle == $bull->BULL_NON_PAYE){ $img=''; $texte = '';}
				elseif ($obj->regle ==$bull->BULL_INCOMPLET) {$img=$bull->IMG_INCOMPLET; $texte=$bull->LIB_INCOMPLET;}
				elseif ($obj->regle ==$bull->BULL_PAYE or ($obj->regle == $bull->BULL_ARCHIVE and !empty($obj->fk_facture))) { $img=$bull->IMG_PAYE;; $texte=$bull->LIB_PAYE;}
				elseif ($obj->regle ==$bull->BULL_SURPLUS) {$img=$bull->IMG_SURPLUS; $texte=$bull->LIB_SURPLUS;}
				elseif ($obj->regle ==$bull->BULL_REMB) {$img=$bull->IMG_REMB; $texte=$bull->LIB_REMB;}
				elseif ($obj->regle == $bull->BULL_ARCHIVE) {$img=''; $texte='';}
				else { $img = ''; $texte = 'inconnu '. $obj->regle;}
				if (!empty($texte)) print '<td><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
				print "</td>";	
*/
		print '<td>';
		print $wfrmcm->AffichImgRegleBull( $obj->regle, 'Loc', $obj->statut, $obj->dated, $obj->fk_facture, $obj->abandon);
		unset ($wfrmcm);
		print '</td>';	


				$nb=NbPmtNeg($obj->rowid);
				print "<td>";
				if ($nb >0) {
					if ($nb > 1) $text = $langs->trans("DefPmtNegs");
					else $text = $langs->trans("DefPmtNeg");
					print info_admin($text,1);
				}
				print "</td>";
				print "</tr>\n";
				
			
					$var=!$var;
					$i++;				
			}	
			print "</table>\n";
		unset($bull);
	}
	AfficheNvBouton ('Location', $idTiers);
}//AfficheLocation


function AfficheResa($idTiers)
{	
	global $langs, $conf, $db ,$w, $bull;
	
	$now = $db->idate(dol_now('tzuser'));

		// pour Tiers, il faut aller chercher les info dans un SQL spécifique dépendant de arg_idtiers			
		// Recherche des dossiers du tiers passé en argument - tiers du dosseir ou interlocuteur d'un echange du dossier
	if ('OrdreSql'=='OrdreSql') {
		$sql = "SELECT";
		$sql .= " distinct ";
		$sql .= " b.rowid as rowid,  T.nom, T.rowid as id_client, b.statut, b.regle ,  b.ref , b.ObsPriv, b.ActionFuture, b.PmtFutur, bd.NomPrenom, b.lieuretrait as ResaAvtivite, bd.observation";
		$sql .= " , case when TO_DAYS(b.dateretrait)>=TO_DAYS('".$now."') then 0 else 1 end  as datedepassee"; 
		$sql .= ", CASE WHEN b.statut = 0 THEN CONCAT(CONCAT(firstname,' '),lastname) ELSE '' END  as  createur ";
		$sql .= ",b.dateretrait as dateretrait, max(bd.datedepose) as datedepose ,  SUM(bd.qte) as NbVelo ";	
		$sql .= ", (select SUM(bd.qte - case when isnull(bd.qteret) then 0 else bd.qteret end ) from llx_cglinscription_bull_det as bd  where  bd.fk_bull = b.rowid ) as NbVeloAtt ";		
		$sql.= " FROM ".MAIN_DB_PREFIX."cglinscription_bull as b ";

			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull_det as bd on b.rowid=bd.fk_bull AND bd.type = 0 AND bd.action not in ('S','X')";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on bd.fk_activite = p.rowid";
		
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as T on b.fk_soc = T.rowid";

		 $sql .= "  LEFT JOIN  llx_user as u  on u.rowid = fk_createur ";
			$sql .= "WHERE T.rowid = '".$idTiers."' AND  b.typebull = 'Resa' ";	
			
			$sql .= " GROUP BY b.rowid,  T.nom, b.statut, b.regle, b.ref";
			$sql.= " ORDER BY  b.ref DESC";
			//******************* LECTURE
			$sql.= $db->plimit($conf->liste_limit+1, $offset);
			$resql = $db->query($sql);
		if ($resql	)   	$num = $db->num_rows($resql);
	}	
	if ($num) {
			
			// affichage barre de sélection
		print '<table class="liste" width="100%">';
			print '<tr class="liste_titre">';
			print_liste_field_titre("DateActivite",$_SERVER["PHP_SELF"],"dateretrait","",$params,'',$sortfield,$sortorder);
			print_liste_field_titre("RefResa",$_SERVER["PHP_SELF"],"ref","",$params,'',$sortfield,$sortorder);
			print_liste_field_titre("NomActivité",$_SERVER["PHP_SELF"],"ref","",$params,'',$sortfield,$sortorder);
			print_liste_field_titre("NbPart",'','',"",'','','','');
			print_liste_field_titre("TiObs",'',"","",'','','','');
			print_liste_field_titre("TiStatut",$_SERVER["PHP_SELF"],"statut","",$params,'',$sortfield,$sortorder);
			print_liste_field_titre('','',"","",'','','','');
			print "</tr>\n";

			// affiche la barre grise de titres des filtres
			// affiche la barre grise des filtres	
			$var=True;
			$i=0;
			$var = 0;
			while ($i < $num)	{
				$obj = $db->fetch_object($resql); // dans le cas dossier, cela a déjà été lu 	
		
				if ($obj->statut == $bull->BULL_ENCOURS) {  // en gras les bulletins en cours
					$gras = '<b>';
					$fingras = '</b>';
				}	
				else
					{  // retour normal
					$gras = '';
					$fingras = '';
				}	
				
				if ($obj->datedepassee) {
					// Gris si date dépassée
					$style='style="color:grey"';
				}
				else
					{  // retour normal
					$style='';
				}	
				print "<tr ".$bc[$var]."  ".$style." >";
				print "<td>";
				/* affiche l image pour la selection */	

				if (!empty($obj->dateretrait) and  $obj->dateretrait <> 0) 								
					print " ".$gras.$w->transfDateFr($obj->dateretrait).'&nbsp&nbsp&nbsp'.$w->transfHeureFr($obj->dateretrait)."</td>\n";
				
				print "<td>".$gras;
				print getNomUrl("object_company.png", 'MAJResa',0,$obj->rowid)."&nbsp";
				print $gras.$obj->ref."</td>";

				print "<td>";
				// Activite
				print  $obj->ResaAvtivite; 
				print '</td>';
						
				/*
				print "<td>";
				if (!empty($obj->ActionFuture)) {
					$texte = $obj->ActionFuture; 
					print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png">';
				}
				print "</td>";
				*/
				if (empty($type)) print "<td>".$gras.$obj->NomPrenom."</td>";
				print $fingras;
				
				print "<td>";
				print $obj->observation;
				print "</td>";
				print '<td>';
				$wfrmcm = new FormCglCommun ($db);
				print $wfrmcm->AffichImgStatutBull($obj->statut, null, 'Resa', null) ;
				print '</td>';	
			
/*				if ($obj->statut == $bull->BULL_ENCOURS) { 	 $img=$bull->IMG_ENCOURS; $texte=$bull->LIB_CNT_ENCOURS;}
				elseif ($obj->statut == $bull->BULL_VAL ) { $img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
				elseif ($obj->statut == $bull->BULL_DEPART ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_DEPART;}
				elseif ($obj->statut == $bull->BULL_RETOUR ) { $img=$bull->IMG_RETOUR; $texte=$bull->LIB_RETOUR;}
				elseif ($obj->statut == $bull->BULL_CLOS ) { $img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
				 print '<td><img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'"></td>';
				print "</td>\n";
*/
				print "</tr>\n";
			
					$var=!$var;
					$i++;				
			}	
			print "</tr></table>\n";
			unset($bull);
	}
	AfficheNvBouton ('Reservation', $idTiers);
}//AfficheResa


	
function AfficheAvoirAcompte($idTiers)
{	
	global $langs, $conf, $db ,$w;
		$whtml = new FormCglCommun ($db);
		$whtml->BoiteAccompteClient( $idTiers);
		unset($whtml);
		
}//AfficheAvoirAcompte

// Affiche les bouton de création de bO/LO/RESA
function AfficheNvBouton($type, $IdTiers)
{
	global $langs, $SEL_TIERS;
	$SEL_TIERS = 'SelTiers';
	print '<div class="tabsAction">';
	// Suite au problème : la création nouveau BU/LO/REDA dans listactivite efface l'adresse tiers$action = 'butAction';
	// A traiter plus à fond. Il faut pouvoir créer 
	$waction = $SEL_TIERS;
	if ($type == 'Inscription') {	
			print '<div class="inline-block divButAction"><a class="butAction"  href="../cglinscription/inscription.php?id_client='.$IdTiers.'&action='.$waction.'">';
				print $langs->trans('NvInscription').'</a></div>';
	}
	elseif ($type == 'Location') {
			print '<div class="inline-block divButAction"><a class="butAction"  href="../cglinscription/location.php?id_client='.$IdTiers.'&action='.$waction.'">';
				print $langs->trans('NvLocation').'</a></div>';	
	}
	elseif ($type == 'Reservation') {
			print '<div class="inline-block divButAction"><a class="butAction"  href="../cglinscription/reservation.php?id_client='.$IdTiers.'&action='.$waction.'">';
				print $langs->trans('NvReservation').'</a></div>';
		
	}
	print '</div>';
} //AfficheNvBouton

?>
