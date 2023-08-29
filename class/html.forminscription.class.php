<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 * *
 * Version CAV - 2.7 - été 2022
 *					 - Saisie participation - différencier Enregistrer et création
 *					 - Migration Dolibarr V15
 *
 * Version CAV - 2.7.1 - automne 2022
 *					- Mise en place ancre AncreChoixPart sur Bouton départ futur/passée
 *					- correction de variable $line->enr inexistante, remplacer par this->type ou line->type_enr suivant les cas
 *					 - fiabilisation des foreach* Version CAV - 2.8 - hiver 2023 - - diminution du pavé PaveSuivi de BU/LO
 * Version CAV - 2.8 - hiver 2023 - - diminution du pavé PaveSuivi de BU/LO
 *					- bulletin technique
 *					 - Installation popup Modif/creation Suivi pour Inscription/Location
 *					- reassociation BU/LO à un autre contrat
 *					- remise à plat des statuts BU/LO
 *					- affichage poids dans liste des participations du BU
 *					- supprimer la saisie de la remise dans la participation individuelle
 *					- Bouton pour édition en un tableau des poids/taille/age/prenom
 * Version CAV - 2.8.3 - printemps 2023
 *		- suppression des guillemets dans les textarea
 *		- récupérer la remise lors de la saisie ultérieur du numéro de vélo (bug 284)
 * Version CAV - 2.8.5 - printemps 2023
 *		- vérification à la volée des conflit de vélo  (308b)
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
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
 *  \file       custum/cglinscription/class/html.forminscription.class.php
 *  \ingroup    cglinscription
 *  \brief      Interface utilisateur pour la saisie bulletin
 */

 
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once("../agefodd/class/html.formagefodd.class.php");
require_once("../agefodd/class/agsession.class.php");
require_once("../cglavt/class/cglFctCommune.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
require_once('../cglavt/class/html.cglFctCommune.class.php');

 class FormCglInscription extends Form
 { 	
    /**
     *    	Return a link on thirdparty (with picto)
     *
     *		@param	int	$withpicto	Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
     *		@param	string	$option		Target of link ('', 'customer', 'prospect', 'supplier')
     *		@param	int	$maxlen		Max length of text
     *		@param	int	$id		Identifiant de l'objet
     *		@return	string				String with URL
     */

	function AfficheTiersBullDepart() 
	{
		global $action, $id_contrat, $langs, $bull, $id_depart, $conf;
		global $TYPE_SESSION, $CNTLOC_ANNULER, $CRE_DEPART, $MAJ_DEPART;
		global $tiersNom, $TiersVille, $TiersMail, $TiersAdresse, $TiersCP, $Villegiature, $Refdossier;
		global $TypeSessionCli_Agf;
	
		$w = new CglLocation($this->db);
		$wf = new FormCglCommun ($this->db);
		/*print ' <STYLE type="text/css">';
		print "div {   display:table }";
		print ".colonne {";
		
		print "	  display:table-cell;";
		print "	  width:150px;";
		print "	  padding:25px; }";
		print "</style>";
		print "<p>";*/
				print '<table bgcolor="#EDEDED" id="AfficheTiersBullDepart_Niv1" width=100%><tbody><tr width=100%><td colspan=2>';
		print '<table id=Niv1AffTiersInfo width=100%><tbody><tr><td>';
			print '<table  id=Niv2AffTiers  width=100%><tbody><tr>';
			print '<td width=30%>';
			$wf->AfficheTiers();
			print '</td>';
			print '<td width=30% > ';
			$wf->AfficheBulletin();
			print '</td>';		

		$wfc = New FormCglDepart($this->db);
		print '<td width="40%" align="center">';
		if (isset($action) and !empty($action) and ($action == $CRE_DEPART or $action == $MAJ_DEPART)) {
			if ($id_depart) $wid	= $id_depart;
			else  $wid	= '';
			$wfc->SaisieDepart('',$wid);
		}
		else	$this->BtCreationDepart();
		print '</td></tr></tbody></table>'; // Fermeture table Niv2AffTiers
		print '</tbody></table>'; // Fermeture table Niv1AffTiersInfo
		print '</td></tr>';
		print '</tbody></table>';// Fermeture table AfficheTiersBullDepart_Niv1
		if ($conf->cahiersuivi) {			
			require_once(DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/html.suivi_client.class.php');
			$wfDossier= new FormCglSuivi ($this->db);
			print $wfDossier->PrepScript('4saisons'); // la gestion de l'affichage est du type du suivi-dossier

			/* Fenetre modale de modification */
			$wsuivi = new cgl_echange ($this->db);
			[$line, $line_echange] = $wsuivi->Chargement(0,$id_dossier);
			$listchampVal = $wsuivi->ConstitueModlEchg_Mod( $line, $line_echange);
			$wsuivi->AfficheModaleEchg($listchampVal);
			$wsuivi->ScriptModale();
			$wsuivid = new cgl_dossier ($this->db);
			$wsuivid->ScriptModale();
			$listchampValDos = $wsuivid->ConstitueModChgDos( $line, $line_echange);
			$wsuivi->AfficheModaleChgDoss($listchampValDos);
			print $wfDossier->html_PaveSuivi($bull->fk_dossier, $bull->type);
			unset ($wfDossier);
		}
		else 
		$wf->PaveObservPriv();
		unset ($wfDossier);
		//print "</p>";
	} // AfficheTiersBullDepart
	
	function BtCreationDepart()
	{
		global $langs, $bull;
		global $CRE_DEPART;			
	
		//print '<td width="41%" align="center">';	 
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'">';
		print '<input type="hidden" name="token" value="'.newtoken().'">';
		print '<input type="hidden" name="action" value="'.$CRE_DEPART.'">';		
		print '<input class="button" action="CRE_DEPART" type="submit" value="'.$langs->trans("BtNouveauDepart").'" sytle ="background-color:'.$langs->trans("ClBoutonDepart").'">';			
		print '</form>';

		//print '</td>';
//		print '<p></p>';
	} //BtCreationDepart

	function AfficheActivite_Participant()
	{
		global  $ACT_SEL_ACTPART, $ACT_SUP_ACTPART,  $ACT_SAISIEPARTICIPATION, $ACT_SUP_REMFIX;
		global $FILTRDEPART, $TYPE_SESSION;
		global $PartNom, $PartPrenom, $PartCiv, $PartAdresse, $PartDateNaissance, $PartTaille, $PartPoids,$PartENF, $PartTel, $PartMail, $PartAge, $PartDateInfo;
		global $ActPartPU, $ActPartPT, $ActPartRem , $ActPartQte, $FacTotal, $ActPartObs, $ActPartIdRdv;
		global $id_client, $action, $langs, $db;
		global $id_act, $id_part, $id_rang , $id_bull, $bull, $id_bulldet, $lineajout, $FiltrPasse, $tabrowid, $conf, $bc;
	
		$wf = new FormCglCommun ($this->db);
//		print '<p>&nbsp</p>';
			$wf->AfficheLigParagraphe("TitrInesAct", 1);
			
		$numline= count($bull->lines);
		/* gestion des pages très grandes - A revoir 
		if ($numline >10) $flgancremilieu = true; else $flgancremilieu= false ;
		//if (!$flgancremilieu) 		print '<a name="AncreLstDetail">';
		*/

		print '<a name="AncreLstDetail" id="AncreLstDetail"></a>';
		// Prépare le js pour mettre toutes les checkbox d'un bulletin  à Actif ou / Non Actif
		print '
        <script language="javascript" type="text/javascript">
        jQuery(document).ready(function()
        {
            jQuery("#checkall_'.$bid.'").click(function()
            {
                jQuery(".checkselection_'.$bid.'").prop(\'checked\', true);
            });
            jQuery("#checknone_'.$bid.'").click(function()
            {
                jQuery(".checkselection_'.$bid.'").prop(\'checked\', false);
            });
        });
        </script>
        ';
		
		
		$w=new CglFonctionCommune ($this->db);
		/* TABLEAU DES ACTIVITES - PATICIPANTS */
		print '<table   border="1" id="Niv1AffichActPart" width="100%"><tbody>';
		print '<tr><td width="100%">';
		print '<form method="GET" name="Participations" action="#AncreSaisieParticipation">';		
		print '<input type="hidden" name="id_bull" value="'.$id_bull.'">';	
		print '<input type="hidden" name="id_bulldet" value="'.$id_bulldet.'">';
		print '<input type="hidden" name="FiltrPasse" value="'.$FiltrPasse.'">';
		print '<input type="hidden" name="action" value="Participations">';
		print '<input type="hidden" name="ancre" value="#AncreSaisieParticipation">';

			print '<input type="hidden" name="token" value="'.newtoken().'">';
			
		print '<div class="tabBar" style="background-color:'.$langs->trans("ClPaveParticipationAff").'">';
			print '<table class="liste" id="Niv2_ListeParticip" width="100%"><tbody>';
			// affiche la barre grise des champs affichés
			
			print '<tr class="liste_titre">';			
			print '<td class="liste_titre">'.$langs->trans(" ").'</td>';
			print_liste_field_titre("Date",'','','','','','','');
			print_liste_field_titre("Depart",'','','','','','','');
			print_liste_field_titre("Lieu",'','','','','','','');
			print_liste_field_titre("Date",'','','','','','','');
			print_liste_field_titre("Nom_Prenom",'','','','','','','');
			print_liste_field_titre("Age",'','','','','','','');
			print_liste_field_titre("TiTaillePoids",'','','','','','','');
			if ($bull->facturable) {
				print_liste_field_titre("PU",'','','','','','','');
				print_liste_field_titre("Rem",'','','','','','','');
				print_liste_field_titre("PT",'','','colspan=2 align=left','','','','');
			}
			print_liste_field_titre('','','','','','','','');
			print '</tr>';

		if ($bull->nblignebulletin)
		{
		//pour chaque ligne ,  zones de saisies ou affichage
			// Boucle sur chaque ligne 			
			$i=1;$j=1;	
			$var=True;	
			
			if (!empty($bull->lines)){
				foreach ($bull->lines as $line )
				{
					if ($line->type_enr == 0 and !($line->action == 'S') and !($line->action == 'X'))
					{
						$var=!$var;
						print "<tr  $bc[$var] >";
						$line->rangecran=$j;				
						print '<td>';

						if ($bull->regle != $bull->BULL_ARCHIVE)
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'&action='.$ACT_SEL_ACTPART.'&id_bulldet='.$line->id.'&id_act='.$line->id_act.'&FiltrPasse='.$FiltrPasse.'#AncreSaisieParticipation">'.img_edit().'</a>&nbsp;';
							if ($bull->regle < $bull->BULL_FACTURE)						
								print '<a href="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'&action='.$ACT_SUP_ACTPART.'&id_bulldet='.$line->id.'&FiltrPasse='.$FiltrPasse.'#AncreLstDetail">'.img_delete().'</a>';
						}
						print '<td>'.$w->transfDateFr( $line->activite_dated) .'</td>';
						print '<td>'.$wf->getNomUrl("object_company.png", 'Depart',0,$line->id_act,'', $bull->id, '&FiltrPasse='.$FiltrPasse).'  '.$line->activite_label.'</td>';
						print '<td>'.$line->activite_lieu.'</td>';
						print '<td>'.$w->transfHeureFr($line->activite_heured).'</td>';
						print '<td>'.$line->NomPrenom.' ';
						if (!empty($line->observation)) print $wf->info_bulle($line->observation, 'info', ' id="img_info" name="img_info" ');
						print '</td>';
						if ($line->PartAge == 99)  print '<td>Ad</td>';
						elseif ($line->PartAge == 100 ) print '<td>Ef</td>';
						elseif ( $line->PartAge == -1) print '<td>0</td>';
						else print '<td>'.$line->PartAge.'</td>';
						
						print '<td>';
						//print $line->PartTaille;
						if (!empty($line->PartTaille)) print $wf->info_bulle('Taille:'.$line->PartTaille, 'info', ' id="img_info" name="img_info" ');						
						if (!empty($line->PartPoids)) print $wf->info_bulle('Poids:'.$line->PartPoids, 'info', ' id="img_info" name="img_info" ');
						print '</td>';
						if ($bull->facturable) {

							//if ( $line->PartAge == -1) print '<td></td>';
							//else  print '<td>'.$line->PartENF.'</td>';
							
							print '<td>'.price2num($line->pu).'</td>';
							print '<td>'.$line->remise_percent;
							if (!empty($line->textremisegen) or !empty($line->textnom)) print $wf->info_bulle($line->textnom.' - '.$line->textremisegen, 'info', ' id="img_info" name="img_info" ');

							// lieu de stokage du libellé de la remise pourcentage
							print '</td>';
							//print '<td>'.$line->qte.'</td>';
						
							$qte = $line->qte;
							if ($qte ==0) $qte=1;
							$rem = $line->remise_percent;
							$pu=$line->pu;
							$pt=$line->calulPtAct($bull->type_session_cgl,$pu,$qte,$rem);
							print '<td>'.$pt.'</td>';
						}
						if (isset ($tabrowid) and !empty($tabrowid))
							foreach ($tabrowid as $row) { 
								if ($row == $line->id) { $flgcheked = true; break; }
								else $flgcheked = false; 
							}//foreach				
				
						print '<td>';
						print '<input class="flat checkselection_" name="rowid['.$line->id.']" type="checkbox" value="'.$line->id.'" size="1"'.($flgcheked?' checked="checked"':'').'>';
						print '</td>';
					//print '<td>'.$PartTel.'</td>';
					//print '<td>'.$Partmail.'</td>';				
						print '</tr>';	
						$j++;
					/* gestion des pages très grandes - A  revoir
					if ($flgancremilieu and $j < $numline ) 	{
							print '<a name="AncreLstDetail" id="AncreLstDetail">';
							$flgancremilieu = false;
						}
						*/
					}
					elseif ($line->type_enr == $line->LINE_BC and !($line->action == 'S') and !($line->action == 'X')) {
						
						$var=!$var;
						print "<tr  $bc[$var] style='color:red;' >";
						print '<td>';

						if ($bull->regle != $bull->BULL_ARCHIVE and empty($bull->fk_facture))
						{
							//print '<a href="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'&action='.$ACT_SEL_ACTPART.'&id_bulldet='.$line->id.'&id_act='.$line->id_act.'#AncreLstDetail">'.img_edit().'</a>&nbsp;';
							if ($bull->regle < $bull->BULL_FACTURE)							
								print '<a href="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'&action='.$ACT_SUP_REMFIX.'&id_bulldet='.$line->id.'#AncreLstDetail">'.img_delete().'</a>';

						}				
						print '<td>'.$w->transfDateFr( $line->activite_datec) .'</td>';
						//print '<td>'.$wf->getNomUrl("object_company.png", 'Remise',0,$line->fk_raisrem,'', $bull->id).'  '.$line->textnom.'</td>';
						print '<td>'.$line->textremisegen.'</td>';
						print '<td></td>';
						print '<td></td>';
						print '<td></td>';					
						print '<td></td>';					
						print '<td></td>';
						print '<td></td>';				
						print '<td></td>';
						print '<td>'.(-1*$line->mttremfixe).'</td>';					
						print '<td></td>';			
						print '</tr>';						
					}
					$i++;
				} /* Fin de boucle */
			}
			
			// Bouton Selectionner - deselectionner les boites checkbox					
			if ($conf->use_javascript_ajax) {
				print '<tr><td colspan=8 ></td><td colspan=4 align=right><a href="#AncreLstDetail" id="checkall_'.$bid.'">';
				print $langs->trans("All").'</a> / <a href="#AncreLstDetail" id="checknone_'.$bid.'">'.$langs->trans("None").'</a></td></tr>';
			}
			// Ligne TOTAL affichable que si le bull est facturable
			if ($bull->facturable) {
				if (empty($id_act)  and $action != $ACT_SEL_ACTPART) $id_act = $line->id_act;
				$moreforfilter='';
				//$moreforfilter.=$langs->trans('TotalFact');
				$moreforfilter .='Total</td><td class="liste_titre" >de la facture';
				print '<tr class="liste_titre" >';
				print '<td class="liste_titre"  width="3%" >';
			
				print $moreforfilter;
				print '</td>';
				$ptt=$bull->TotalFac();
				print '<td  colspan=11 align="right" ><font size=4>'.$ptt.' '.$langs->trans('Euro').'</font></td>';
			//	print '<td align="right">euros</td>';
				print '</tr>';
			}

		}/* il y a des activites*/	
		print "</table>";/* id=Niv2_ListeParticip*/
		print '</div>';
		print '</td></tr><tr><td>';

		$this->AfficheTravailParticipation($id_bulldet);
		if ( $action == $ACT_SAISIEPARTICIPATION or $action == $ACT_SEL_ACTPART)
		{
			$this->SaisieParticipation();
		}
		unset ($w);
		print '</form></td></tr></tbody></table>';/* id=Niv1AffichActPart*/


	}//AfficheActivite_Participant
		
	function AfficheTravailParticipation ($id_bulldet)
	{
		global $langs, $action, $bull;
		
		
print '<style>
#AfficheTravailParticipation1{background:#ff0;width:800px;padding:1px; style="background-color:'.$langs->trans("ClPaveSaisie").'"}  
#AfficheChoixParticipation{float:left;width:40%;border-right: 1px solid black; style="background-color:'.$langs->trans("ClPaveSaisie").'"}  
#AfficheChoixParticipationMod{float:left;margin-left:15px;;border-right: 1px solid black; style="background-color:'.$langs->trans("ClPaveSaisie").'"}
#AfficheChoixParticipationCree{float:left;margin-left:15px;;border-right: 1px solid black; style="background-color:'.$langs->trans("ClPaveFinalisation").'"}
#AfficheRemParticipation{float:left;margin-left:15px;}
</style>';
		//print '<div class="tabBar" id="AfficheTravailParticipation" style="background-color:'.$langs->trans("ClPaveSaisie").'">';
		// Nouvelle participation$wd=new BulletinLigne($db);
		/*if (!empty($action) 
		//	and ($action == $CRE_PARTICIPATION   
					or ($action == $ACT_SEL_ACTPART and !$wd->IsParticipationcomplete($id_bulldet,false))))		{
			$this->AfficheChoixParticipation();
		}		
		elseif ($bull->regle < $bull->BULL_FACTURE)
		{
			print '&nbsp&nbsp&nbsp';
			//$this->AfficheBtPNellParticipation();	
		}*/
		print '<a id="AncreChoixPart" name="AncreChoixPart"></a>';
		print '<table  id="AfficheTravailParticipation" style="border:1px solid black;font-size:13px;background-color:'.$langs->trans("ClPaveSaisie").' "  width="100%">';
		print '<tbody><tr><td width="33%" style="border:1px solid black;background-color:'.$langs->trans("ClPaveFinalisation").';" >';

		$this->AfficheChoixParticipationCree();
		print '</td><td  width="33%" style="border:1px solid black;" >';
		// modifier les participations
		
		$this->AfficheChoixParticipationMod();
		print '</td>';
		if ($bull->facturable) {
			print '<td rowspan=2>';
			// modifier les participations
			$this->AfficheRemDelParticipation();
			print '</td>';
		}
		print '</tr>';
		print '<tr><td colspan=2>';
		$wf = new FormCglCommun ($this->db);
		print '<table id="MassDel" width="100%"  style="border:1px solid black;"><tr>';
		print '<td align=center>';		
		$wf->AffichePoidsTaille();	
		print '</td>';
		print '<td >';
		// Suppression en masse
		$wf->AfficheDelLigneDetail();
		print '</td></tr></body></table id=MassDel>';
		unset($wf);

		print '</td></tr></tbody></table>';/*  id="AfficheTravailParticipation"*/
		unset ($wd);

	}	//	 AfficheTravailParticipation
	
	function AfficheBtPNellParticipation()	
	{
		global  $langs, $bull;
		
/*		
		print '<input type="hidden" name="action" value="'.$CRE_PARTICIPATION.'">';	
		print '</form>';
*/
		print '<input class="button"  type="submit" value="'.$langs->trans("BtParticipation").'">';	
		//print '</form>';
	}//AfficheBtPNellParticipation	
	function AfficheRemDelParticipation()
	{
		global $langs;
		$wcom = new FormCglCommun ($this->db);
		
		$wf = new FormCglCommun ($this->db);
		print '<table id="Rem" width="100%"><body>';
		
		print '<tr>';
		$wf->AfficheParagrapheCol($langs->trans("TiRemiseInsc"));
		print '</tr>';
		unset ($wf);
		
		$wcom->AfficheRemise();
		unset ($wcom);
		print '</td></tr></table id="Rem">';
		
	} // AfficheRemDelParticipation
	function AfficheChoixParticipationCree()
	{
		global $bull, $langs;
		
		$wf = new FormCglCommun ($this->db);
		$this->AfficheChoixParticipation('Creer');

		unset ($wf);
	} //AfficheChoixParticipation
	function AfficheChoixParticipationMod()
	{
		$wf = new FormCglCommun ($this->db);
		$this->AfficheChoixParticipation('Mod');
		unset ($wf);
	} //AfficheChoixParticipationMod

	function AfficheChoixParticipation($env='Creer')
	{
		global  $ACT_SEL_ACTPART, $ACT_SAISIEPARTICIPATION;
		global $FILTRDEPART;
		global  $action, $langs, $db;
		global $id_act, $id_part, $id_bull, $bull, $id_bulldet, $FiltrPasse;

		

		if ($env == 'Creer') {
			$valueform = 'SelectNlleActivite';
			$title=$langs->trans('BtChoixParticipationCree');
			$titlepave='TiParticipationCree';
			$htmlname='id_act';
			$functionBouton='FctBtCreer';
		}
		else {		
			$valueform = 'SelectMoodActivite';		
			$title=$langs->trans('BtChoixParticipationMod');
			$titlepave='TiModParticipation';
			$htmlname='id_actMod';
			$functionBouton='FctBtMod';
		}
		$wf = new FormCglCommun ($this->db);
		$wf->AfficheLigParagraphe($titlepave, 3);
		
/*		
		
		print '<input type="hidden" name="ActPartPU" value="'.$ActPartPU.'">';
			print '<input type="hidden" name="action" value="'.$valueaction.'">';
		print '<input type="hidden" name="FiltrPasse" value="'.$FiltrPasse.'">';
		print '<input type="hidden" name="" value="#AncreLstDetail">';
*/	
		$moreforfilter='';
		$wd=new CglInscription($db);
		//$moreforfilter.=$langs->trans('Depart'). ' :&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ';
		$now = $this->db->idate(dol_now('tzuser'));
		if ($FiltrPasse == 0) { $arrayFiltre[0] = ' AND TO_DAYS(heured) >= TO_DAYS("'.$now.'") '; $order = 'date';$forcecombo = 1;}
		else { $arrayFiltre[0] = ' AND TO_DAYS(heured) < TO_DAYS("'.$now.'") AND YEAR(heured) = YEAR("'.$now.'")'; $order = 'passe';$forcecombo = 0;}	
		if ($bull->type_session == 1) $arrayFiltre[1] = " and (s.fk_soc is null or s.fk_soc ='".$bull->id_client."') "; 
		$temp = 0; 
		if ( !empty($id_act))	 $temp = $id_act;
		$forcecombo = 1;
		$moreforfilter.=$wd->select_session($temp, $htmlname, $order, 1, $forcecombo, "", $arrayFiltre);
		unset ($wd);
		if (($bull->regle < $bull->BULL_ARCHIVE) ) {
			print '<div align="center">';
			print $moreforfilter;
			print '</div>';
		}
/*
		$line = $bull->RechercheLign($id_bulldet);
		if ($bull->regle < $bull->BULL_ARCHIVE 
				and ($action == $CRE_PARTICIPATION 
						or $action == $FILTRDEPART ))
		{	
			$title = "BtSaiseParticipationPART";
		}
		elseif ($bull->regle < $bull->BULL_ARCHIVE 
				and $action == $ACT_SEL_ACTPART and ($line->id_part == 0 or empty($line->id_part)))
		{	
			$title = "BtSaiseParticipationACT";
		}
		elseif ($bull->regle < $bull->BULL_ARCHIVE 
				and $action == $ACT_SEL_ACTPART and ($line->id_act == 0 or empty($line->id_act)))
		{
			$title = "BtSaiseParticipationPART";
		}*/
		if ( $action != $FILTRDEPART_ACT ) 
		{ 
			if ($FiltrPasse == 1 ) 
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'&amp;action='.$FILTRDEPART.'&amp;FiltrPasse=0'.'&amp;id_bulldet='.$id_bulldet.'#AncreLstDetail">';
				print img_picto($langs->trans("Activated"),'switch_on');
			}
			else 
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'&amp;action='.$FILTRDEPART.'&amp;FiltrPasse=1#AncreLstDetail">';
				print img_picto($langs->trans("Desctivated"),'switch_off');
			}	
		}
		else { 
			if ($FiltrPasse == 1 ) 
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'&amp;action='.$FILTRDEPART_ACT.'&amp;FiltrPasse=0#AncreLstDetail">';
				print img_picto($langs->trans("Activated"),'switch_on');
			}
			else 
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'&amp;action='.$FILTRDEPART_ACT.'&amp;FiltrPasse=1#AncreLstDetail">';
				print img_picto($langs->trans("Desctivated"),'switch_off');
			}								
		}
	
		print '</a>';
		print  $langs->trans('FiltreSessionspassees');	
		
		print '<div align="center"><br >';
		print '<input class="button" type="submit" name="'.$functionBouton.'" value="'.$langs->trans($title).'" align="right" >';
		print '</div>';
						
		//print "</form>";	;	
		unset ($w);
	} //AfficheChoixParticipation
	function AfficheRemParticipation_old ()
	{
			global $ACT_MODREMPART, $langs;
			
		$wf = new FormCglCommun ($this->db);
		$w=new CglInscription ($this->db);	
		$wf->AfficheLigParagraphe("TiRemParticipation", 3);
		print '<br><b>'.$langs->trans('RemPourc').'</b>';
		//print '<div align="center"><br><input class="flat"  value="" type="text" name="mttremisegen" ></div>';	
		print '<input class="flat"  value="" type="text" name="mttremisegen" >';	
		print '<br><b>'.$langs->trans('RemRaison').'</b>';
		// raison	
		//print '<div align="center">'.$w->select_raison_remise('','RaisRemGen').'</div>';
		print $w->select_raison_remise('','RaisRemGen');
		print '<br>';	
		print '<div align="center"><br ><input class="button" name="FctBtRemParticipation" type="submit" value="'.$langs->trans('BtModRemPart').'" align="right" ></div>';

				
		unset ($wf);
	}//AfficheRemParticipation

	function SaisieParticipation()
	{
		global  $ACT_SEL_ACTPART,  $ACT_SUP_ACTPART,  $ACT_ANNULPART, $ACT_ENR_ACTPART, $ACT_SAISIEPARTICIPATION, $FctBtCreer;
		global $event_filtre_car_saisie;
		global $PartNom, $PartPrenom, $PartCiv, $PartAdresse, $PartDateNaissance, $PartTaille, $PartPoids,$PartENF, $PartTel, $PartMail, $PartAge, $PartDateInfo;
		global $ActPartPU, $ActPartPT, $ActPartRem , $ActPartQte, $FacTotal, $ActPartObs, $ActPartIdRdv, $NomPrenom, $FiltrPasse;
		global $id_client, $action, $langs, $db;
		global $id_act, $id_part, $id_rang , $id_bull, $bull, $id_bulldet, $lineajout;
	
		print '<a id="AncreSaisieParticipation" name="AncreSaisieParticipation"></a>';
		// RECUPE DONNEES
		if ('RECUPE DONNEES' == 'RECUPE DONNEES' ) {
			$w=new CglFonctionCommune ($this->db);
			/* SAISIE DES PARTICIPATIONS - ACTIVITE + PARTICIPANT */
			if (!isset($lineajout)) $lineajout=new BulletinLigne($db);
	/*			if (!isset ($action) or empty($action) or $action == $ACT_ENR_ACTPART or $action ==  $ACT_SUP_ACTPART ) // mise à vide des champs de liens 		{	
					$id_bulldet = 0;  //$id_part = 0;
					$PartNom = ''; $PartPrenom = ''; $PartIdCivilite = '';  $PartAdresse = ''; $PartDateNaissance = ''; $PartTaille = ''; $PartPoids = '';
					$PartENF = '';  $PartTel = '';  $PartMail = ''; $ActPartPU = '';  $ActPartPT = '';  $ActPartRem = ''; $ActPartObs = ''; 
					$ActPartQte = ''; $ActPartIdRdv = ''; $BullOrig = ''; 
				}
	*/			
			if ($action == $ACT_SEL_ACTPART   )				{
				unset ($lineajout);				
				$lineajout=$bull->RechercheLign($id_bulldet); // récupère aussi id_part et id_act
				if ($id_act <> $lineajout->id_act) $id_act = $lineajout->id_act;
				$id_part = $bull->id_contactTiers;
			}	

			// si un groupe constitué et la session déjà choisie : récup Act et supprime boite sélection
			if ($bull->type_session_cgl == 1 and ($bull->derniere_activite) and empty($id_act))	{
				if (!isset($datainscription)) $datainscription = new CglInscription($db);
				$datainscription->RecupAct($bull->derniere_activite, $lineajout);
				$id_act =$bull->derniere_activite;	
			}
				
				if ( $action == $ACT_SEL_ACTPART    or $action == $ACT_SAISIEPARTICIPATION) 
						if (!empty($id_bulldet)) $lineajout=$bull->RechercheLign($id_bulldet);
				if (!isset($datainscription)) $datainscription = new CglInscription($db);
				if (!empty($id_act)) $wid_act = $id_act;
				else  $wid_act = $lineajout->id_act;
				$datainscription->RecupAct($wid_act, $lineajout); // renseigne lineAjout
				if ( $action == $ACT_SEL_ACTPART    or $action == $ACT_SAISIEPARTICIPATION) 	{	
					if (empty($id_bull)) $id_bull		= $lineajout->fk_bull;
					unset (	$datainscription);				
				}
				$line = $lineajout;
				// on récupère ce qu'il y a dans les variables GETPOST si elles ne sont pas vides
				if (!empty($NomPrenom))			$line->NomPrenom			=$NomPrenom;
				else if (empty($line->NomPrenom) ) $line->NomPrenom	= $bull->tiersNom;
				if (!empty($PartPrenom))		$line->PartPrenom		=$PartPrenom;
				if (!empty($PartIdCivilite))	$line->PartIdCivilite	=$PartIdCivilite;
				if (!empty($PartDateNaissance))	$line->PartDateNaissance	=$PartDateNaissance ;
				if (!empty($PartTaille))		$line->PartTaille		=$PartTaille ;
				if (!empty($PartPoids))			$line->PartPoids		=$PartPoids ;
				if (!empty($PartAge))			$line->PartAge			=$PartAge ;
				if (!empty($PartDateInfo))		$line->PartDateInfo		=$PartDateInfo ;
				if (!empty($PartENF))			$line->PartENF			=$PartENF ;
				if (!empty($PartTel))			$line->PartTel			=$PartTel ;
				if (!empty($PartCiv))			$line->PartCiv			=$PartCiv;
				
				if (!empty($PartMail))			$line->PartMail			=$PartMail ;
				if (!empty($ActPartPU))			$line->pu				= $ActPartPU ;
				if (!empty($ActPartRem))		$line->remise_percent	= $ActPartRem ;
				if (!empty($ActPartObs))		$line->observation		= $ActPartObs ; 
				if (!empty($ActPartIdRdv))		$line->activite_rdv		= $ActPartIdRdv ; 
				if (!empty($ActPartQte))		$line->qte		= $ActPartQte ;
				
				// Fin récup données
		}
		if ('ACTIVITE'=='ACTIVITE') {
 				print '<table  id=Niv1SaisPart_FormGen style="border:1px solid black;font-size:13px;background-color:'.$langs->trans("ClPaveSaisie").' "  width="100%"><tbody >';
				
					print '<form method="GET " name="SelectActivite" action="#AncreLstDetail">';
					print '<input type="hidden" name="id_bull" value="'.$id_bull.'">';
					print '<input type="hidden" name="FiltrPasse" value="'.$FiltrPasse.'">';
					//print '<input type="hidden" name="action" value="'.$ACT_ENR_ACTPART.'">';
					//print '<input type="hidden" name="id_part" value="'.$id_part.'">';
					print '<input type="hidden" name="id_act" value="'.$id_act.'">';
					print '<input type="hidden" name="id_bulldet" value="'.$id_bulldet.'">';
					print '<input type="hidden" name="token" value="'.newtoken().'">';					
					print '<tr><td>';
	
					if ( $action != $ACT_SEL_ACTPART and 
								$action != $ACT_SAISIEPARTICIPATION  )
						{ 	
						$line = new BulletinLigne($db) ;}
						
					print '<table class="liste" id=Niv2_Activite><tbody>';	
						print '<tr class="liste_titre" >';
							print '<td colspan=2>';
							print $langs->trans('Depart');			
						print '</td></tr>';
						print '<tr bgcolor="white">';
							print '<td>'.$langs->trans('institulecusto').'</td><td %>'.$line->activite_label.'</td>';
						print '</tr><tr bgcolor="white">';
							print '<td>'.$langs->trans('lieu').'</td><td>'.$line->activite_lieu.'</td>';
						print '</tr><tr bgcolor="white">';				
						print '</tr><tr bgcolor="white">';
						print "<p></p>";
						if ((!is_null($line->activite_dated) and isset($line->activite_dated)))
						{
							$date_act = $line->activite_dated;
							$date_act_fr=$w->transfDateFr($date_act);
							// tableau des jours de la semaine
							$joursem =  $w->transfDateJourSem($date_act_fr);
							print '<td size="1">'.$langs->trans('Date').'</td><td>'. $joursem.' '.$date_act_fr.'</td>';
						}
						else
							print '<td>'.$langs->trans('Date').'</td><td></td>';
						print '</tr><tr bgcolor="white">';	
						if ((!is_null($line->activite_dated) and isset($line->activite_dated)))
						{
							$heure_act=$line->activite_heured;	
							//$heure_act_fr=substr($heure_act,11,2).'h'.substr($heure_act,14,2);transfHeureFr
							$heure_act_fr=$w->transfHeureFr($heure_act);
							print '<td>'.$langs->trans('heured').'</td><td>'.$heure_act_fr.'</td>';
						}
						else	print '<td>'.$langs->trans('heured').'</td><td></td>';
						print '</tr><tr bgcolor="white">';	
						if ( (!is_null($line->activite_dated) and isset($line->activite_dated)))
						{
							$heure_act=$line->activite_heuref;	
							$heure_act_fr=substr($heure_act,11,2).'h'.substr($heure_act,14,2);
							print '<td>'.$langs->trans('heuref').'</td><td>'.$heure_act_fr.'</td>';
						}
						else	print '<td>'.$langs->trans('heuref').'</td><td></td>';
	
						// Nombre de places offertes, réservées ou attendues
						// rouge si le nbr places inférieur au total Inscrit/Pre-inscrit + participation du bulletin en cours et nbr participation du bulletin 
						//		et le nb participation est supérieur au nb préinscrit (on ne pourra de toute facon pas prendre autant de monde
						// orange  si le nbr places inférieur au total Inscrit/Pre-inscrit + participation du bulletin en cours et nbr participation du bulletin 
						//		et le nb participation est inférieur  au nb préinscrit (on  pourrait eventuellement les prendre si les pre-inscrits sont peu sÃ»rs)
						// blanc sinon
						if ($line->activite_nbmax <= $line->activite_nbinscrit + $line->activite_nbpreinscrit +  $line->activite_nbencrins ) {
							if (  $line->activite_nbpreinscrit >= $line->activite_nbencrins) 
								$color = "orange"; 
							else $color = "red"; 
						}
						else $color = "white";
						print '</tr><tr bgcolor="'.$color.'">';									
							print '<td>'.$langs->trans('nbplace').'</td><td>'.$line->activite_nbmax.'</td>';
						print '</tr><tr bgcolor="'.$color.'">';			
							print '<td>'.$langs->trans('nbInscrit').'</td><td>'.$line->activite_nbinscrit.'</td>';
						print '</tr><tr bgcolor="'.$color.'">';				
							print '<td>'.$langs->trans('nbpreInscrit').'</td><td>'.$line->activite_nbpreinscrit.'</td>';
						print '</tr><tr bgcolor="'.$color.'">';				
							print '<td>'.$langs->trans('nbEncoursInscrit').'</td><td>'.$line->activite_nbencrins.'</td>';
						print '</tr><tr bgcolor="'.$white.'">';	
							print '<td>'.$langs->trans('UNAgfFormateur').'</td><td>'.$line->act_moniteur_prenom.' '.$line->act_moniteur_nom.'</td>';
						print '</tr><tr bgcolor="white">';	
							print '<td>'.$langs->trans('Montelephone').'</td><td>'.$line->act_moniteur_tel.'</td>';
						print '</tr><tr bgcolor="white">';	
							print '<td>'.$langs->trans('Monemail').'</td><td>'.$line->act_moniteur_email.'</td>';
						print '</tr>';										
					print "</tbody></table id=Niv2_Activité>";	
//L3 Col 2
//				print '</td><td width="50%">';
				print '</td><td>';
		}
		if ('PARTICIPANT' =='PARTICIPANT') { 
					print '<table id=Niv2_Participant><tbody>';

					$moreforfilter='';
					$moreforfilter.=$langs->trans('Participant');			
						print '<tr class="liste_titre">';
						print '<td class="liste_titre"  colspan=4>';
							print $moreforfilter;			
						print '</td></tr>';					
print '<script>
		function EffaceChamp(o) {				
				o.value = "";
				o.style.color="black";
				o.style.background="white";
			}
		function RemetVide(o) {
				if (o.value == "" ) {
					o.style.color="grey";
					o.value=o.defaultValue;
				}					
			}

</script>';
							print '<td>'.$langs->trans("Nom").'</td>';
								// Si NomPrenom Vide ou egal au nom du tiers, le mettre en grise
								// L'effacer lors du click dans la zones
								// S'il reste egal au nom du tiers, remettre en noir à la sortie
								$NomPrenom = $line->NomPrenom;
								if (empty($line->NomPrenom) or $line->NomPrenom == $bull->tiersNom) {
									$style = 'style="color:grey"';
									if (empty($NomPrenom)) 	{
										$NomPrenom = $bull->tiersNom;
									}
//									$fctclick = "EffaceChamp(this)";	$fctchange = "RemetVide(this)";
									$fctclick = "";	$fctchange = "";
								}
								else {
									$style ='';
								}
							print '<td colspan=2><input class="flat" size="55" value="'.$NomPrenom.'" type="text" name="NomPrenom"  '.$style.' onclick="'.$fctclick.'" onblur="'.$fctchange.'" onchange="'.$fctchange.'" ></td>';
							print '</tr><tr>';

						print '</tr><tr>';
						
							print '<td>'.$langs->trans("Taille").'</td>';
							print '<td><input class="flat"  value="'.$line->PartTaille.'" type="text" name="PartTaille" >';
							if ($bull->type_session_cgl == 2) // type session individuel
								print '<img class="hideonsmartphone" border="0" title="Obligatoire" alt="Obligatoire" src="'.DOL_URL_ROOT.'/theme/eldy/img/star.png">';
							print '</td>';	
							print '</tr><tr>';			
							print '<td>'.$langs->trans("Poids").'</td>';	
							$objet = new CglInscription($this->db);
							print '<td><input class="flat"  value="'.$line->PartPoids.'" type="text" name="PartPoids" >';
							//print '<td>'.$objet->select_poids($line->PartPoids,'PartPoids');
							if ($bull->type_session_cgl == 2) // type session individuel							
								print '<img class="hideonsmartphone" border="0" title="Obligatoire" alt="Obligatoire" src="'.DOL_URL_ROOT.'/theme/eldy/img/star.png">';
							print '</td>';	
							
							print '</tr><tr>';
							print '<td>'.$langs->trans("Age").'</td>';
							if (empty($line->PartAge) or $line->PartAge == -1 ) $temp = 99;
							else $temp =$line->PartAge;
						$moreforfilter = $objet->select_age($temp, 'PartAge') ;	
							unset ($objet);
							print '<td>'.$moreforfilter.'</td>';
						/*	print '<td>'.$line->PartDateInfo.'</td>';*/
						print '</tr><tr>';
							
						/*	
						print '</tr><tr>';
							print '<td>'.$langs->trans("Phone").'</td>';
							print '<td><input class="flat" size="20" value="'.$line->PartTel.'" type="text" name="PartTel" ></td>';
						print '</tr><tr>';
							print '<td>'.$langs->trans("Mail").'</td>';
							print '<td colspan="2"><input class="flat" size="50" value="'.$line->PartMail.'" type="text" name="PartMail" ></td>';
							*/
//				print '</td><td width="30%">';
		}
		if ('FACTURATION'=='FACTURATION') {
			if ($bull->facturable) {
				print '</tr>';
				print '</tbody></table id=Niv2_Participant>';
				print '</td><td>';
	//L3 Col 3		
						print '<table class="liste"  id=Niv2_Facturation><tbody>';
						$moreforfilter='';
						$moreforfilter.=$langs->trans('Facturation');			
							print '<tr class="liste_titre" >';
								print '<td class="liste_titre" width="100%" colspan=2>';
								print $moreforfilter;			
							print '</tr><tr>';
							print '</tr><tr>';
							//print '<td width="30%">'.'Tarif </td><td>'.$line->PartENF.' </td>';
							print '</td></tr>';
								print '</tr><tr>';
								if ($bull->type_session_cgl == 2)
								{
									print '<td>'.'  '.$langs->trans("PU Adulte").'</td><td>';
									print ($line->pu_adlt)?$line->pu_adlt.'&nbsp;euros':'';
									print '</td>';
									print '</tr><tr>';
									print '</tr><tr>';
									print '<td>'.'  '.$langs->trans("PU Enfant").'</td><td>';
									print ($line->pu_enf)?$line->pu_enf.'&nbsp;euros':'';
								}
								else
								{
									print '<td>'.'  '.$langs->trans("PU Groupe").'</td><td>';
									print ($line->pu_grp)?$line->pu_grp.'&nbsp;euros':'';
									print '</td>';
									print '</tr><tr>';
									print '<td>'.'  '.$langs->trans("PU Exclu").'</td><td>';
									print ($line->pu_excl)?$line->pu_excl.'&nbsp;euros':'';
								}
								print '</td>';
								print '</tr><tr>';
								
	
								print '<td>'.'  '.$langs->trans("PU").'</td>';
								/*if ((!isset($line->pu))  and  $id_part > 0  and ($line->id))
								{
									//$line->pu = $line->CherchePu();
								}
								*/
								if (empty($line->pu)) {
										if ($bull->type_session_cgl == 2 ) $line->pu = empty($line->pu_adlt)? $line->pu_enf:$line->pu_adlt;
										else $line->pu = $line->pu_grp;
								}
								print '<td><input size=10 class="flat" value="';
								print  $line->pu;
								print '" type="text" name="ActPartPU" >';
								if ($line->pu) 
									print '&nbsp;&nbsp;euros';
								if ($bull->type_session_cgl != 1)
									print '<img class="hideonsmartphone" border="0" title="Obligatoire" alt="Obligatoire" src="'.DOL_URL_ROOT.'/theme/eldy/img/star.png">';
								print '</td>';
								// REMISE
								
								if (!empty($line->remise_percent)) {
										print '</tr><tr>';
										print '<input type="hidden" name="ActPartRem" value="'.$line->remise_percent.'">';	
										print '<td>'.'  '.$langs->trans("RemPourc").'</td><td>';
										print $line->remise_percent;
										print '&nbsp;%  soit total de ';
										print (100-$line->remise_percent)* $line->pu/100;
										print '&nbsp;&nbsp;euros';
								}

							
			}
			else {
						
						print '<tr  ><td>&nbsp</td></tr>';
						print '<tr  ><td>&nbsp</td></tr>';
						print '<tr class="liste_titre" >';
						print '<td class="liste_titre" width="100%" colspan=2>';
						$moreforfilter='';
						$moreforfilter.=$langs->trans('Plannification');	
						print $moreforfilter;	
			}
			
			//QUANTITEE
			if (empty($id_bulldet) or ! empty($FctBtCreer)){ 	
				print '</tr><tr>';
				print '<td>'.'  '.$langs->trans("Qte").'</td>';
				
				if (empty($line->qte) or $line->qte == 0) $line->qte = 1;
				print '<td><input class="flat" value="'.$line->qte.'" type="text" name="ActPartQte" ></td>';
			}	
			print '</tr><tr>';
	/*	
			REMISE
			print '<td>'.'  '.$langs->trans("RemPourc").'</td>';
			print '<td><input class="flat" value="'.$line->remise_percent.'" type="text" name="ActPartRem" ></td>';
			print '</tr><tr>';
	*/

			$qte = $line->qte;
			if ($qte ==0) $qte=1;
			$rem = $line->remise_percent;
			$pu=$line->pu;
			$pt=$line->calulPtAct($bull->type_session_cgl,$pu,$qte,$rem);
			//if ($pu)	print '<td>Prix </td><td>'.$pt.'&nbsp;euros</td>';
			//else	print '<td>Prix </td><td></td>';
			//print '</tr><tr>';

			//OBESERVATION
			print '<td>'.'  '.$langs->trans("Observation").'</td>';				
			print '<td align="left"><textarea cols="40" rows="'.ROWS_3.'" wrap="soft" name="ActPartObs" '.$event_filtre_car_saisie.' >';
			print $line->observation.'</textarea>';		
			print '</tr>';
			if (!$bull->facturable) {
				
						print '</tr>';
					print '</tbody></table id=Niv2_Participant>';	
			}
			if ('BOUTON'=='BOUTON') {		
				print '<tr><td colspan=2>';		
				/* Enregistrement ou Ajout*/
					print '<table width="100%" id=Niv3_Bouton><tbody>';
						print '<tr align=center>';
							if ($bull->regle < $bull->BULL_ARCHIVE)
							{
								print '<input type="hidden" name="action" value="'.$ACT_ENR_ACTPART.'">';
								if (!empty($id_bulldet) and $id_bulldet)
								{
									print '<input type="hidden" name="id_bulldet" value="'.$id_bulldet.'">';
									print '<td><input class="button" name = "EnrPart" action="'.$ACT_ENR_ACTPART.'" type="submit" size="5" value="Enregistrer" align="center"></td>';	
								}
								else
								{
									print '<td><input class="button" name = "EnrPart"  action="'.$ACT_ENR_ACTPART.'" type="submit" size="5" value="Ajouter" align="center"></td>';	
								}
								//print '<td><input class="button" action="ACT_ANNULPART" type="submit" size="5" value="Annuler" align="center"></td>';	
							}
						print '</tr>';
				print "</tbody></table id=Niv3_Bouton>";
						
						
						print '</td></tr>';
						
						
						
					print '<tbody></table id=Niv2_Facturation>';
				print '</td></tr>';
//L4 Col 1&2&3&4
		
			print "</td>";
			print '</tr>';			
			print '</form></tbody></table id=Niv1SaisPart_FormGen>';
			}
		
		}
	
	} // SaisieParticipation
		
	function AffichePersonneRecours()
	{
		global $ACT_CRE_PERS_RESP, $ACT_SEL_PERS_RESP, $ACT_MAJ_PERS_RESP;
		global $id_client, $action,$id_bull, $langs, $bull, $id_bulldet, $db, $id_persrec;
		global $PersNom, $PersPrenom, $PersTel, $PersParent, $PersCiv;
		
		$PersNom =''; $PersPrenom =''; $PersTel =''; $PersParent =''; $PersCiv ='';
		
		//print '<a name="AncreRecours" id=name="AncreRecours">';	
		print '<a name="AncreRecours" ></a>';	
		$wf = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		if ($action == $ACT_CRE_PERS_RESP) 
		{ 
			$bull->fk_persrec = '';
			$id_persrec='';
			$bull->pers_nom = '';
			$bull->pers_prenom = '';
			$bull->pers_tel = '';
			$bull->pers_parente = '';
		}

		if ($action == $ACT_SEL_PERS_RESP) 
		{
			$datainscription = new CglInscription ($db);
			$datainscription->RecupContact($id_persrec);
			unset($datainscription);
		}				
		print '<p>&nbsp;</p>';
			// table contenant Personne Recours m Remise et Rendez-vous			
	print '<table id="Niv1RecoursRemRdv" width="100%"><tbody><tr><td width="40%">';
			// table PersonneRecours
	print '<table id="Niv2_Recours" width="100%" border="1"><tbody><tr><td>';
		print '<table id="Niv3_Recours" width="100%"><tbody><tr>';
				$wfctcomm->AfficheParagraphe("PersRec", 3);
			//print '</td></tr>';
			print '</tr>';
			print '<tr><td width="40%">';
			/* MODIFICATION/CREATION/AFFICHAGE*/
				print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'#AncreRecours">';
					print '<input type="hidden" name="token" value="'.newtoken().'">';
								print '<input type="hidden" name="id_bull" value="'.$id_bull.'">';
								print '<input type="hidden" name="id_persrec" value="'.$bull->fk_persrec.'">';
								print '<input type="hidden" name="action" value="'.$ACT_MAJ_PERS_RESP.'">';
			if ($action == $ACT_SEL_PERS_RESP or  $action == $ACT_CRE_PERS_RESP) 	 print '<div class="tabBar" style="background-color:'.$langs->trans("ClPaveSaisie").'">';
			$this->AffPersonneRecoursSaisie()	;

			if ($action == $ACT_SEL_PERS_RESP or  $action == $ACT_CRE_PERS_RESP) 			print '</div>';
					print '</form>';

			print '</td><td width="60%">';	
	/*				/* Table Selection  d'un contact*/
				$this->AffPersonneRecoursSel();
			// table PersonneRecours
			print '</td></tr></body></table id="Niv3_Recours" >';
			print '</td></tr></tbody></table id="Niv2_Recours">';
			print '</td></tr></tbody></table Niv1RecoursRemRdv>';
			
			// colonne sur table contenant Personne Recous et Rendez-vous			
			print '</td>';
		/* fin table generale */
	} /*AffichePersonneRecours*/
	function AffPersonneRecoursSel()
	{		
		global $bull, $id_bull, $langs;
		global $ACT_CRE_PERS_RESP, $ACT_SEL_PERS_RESP;
 			print '<table id="Niv4_Selection" class="liste"><tbody><tr>';
					$moreforfilter='';
					$datacglfctdol=new CglFonctionDolibarr($db);
//					$moreforfilter.=$datainscription->select_contact(0,$bull->fk_persrec,'id_persrec',1,'','','','','',1,0,array(array('autocomplete'=>'on','width=100')));
					$moreforfilter.=$datacglfctdol->select_contact(0,$bull->fk_persrec,'id_persrec',1,'','','','','',1);
					unset($datacglfctdol);
					print '<td>';
								print '<form method="GET" name="CherchePersRec" action="'.$_SERVER["PHP_SELF"].'#AncreRecours">';
								print '<input type="hidden" name="id_bull" value="'.$id_bull.'">';
								print '<input type="hidden" name="action" value="'.$ACT_SEL_PERS_RESP.'">';
								print '<input type="hidden" name="token" value="'.newtoken().'">';
					print '<table id=Niv5_SelectionPart" width="100%"><tbody><tr  class="liste_titre"><td class="liste_titre" width="80%">';
						print $langs->trans('PersRecours'). ' : </td></tr><tr><td>';
								print $moreforfilter;
							print '</td>';
								if ($bull->regle < $bull->BULL_ARCHIVE) {									
									print '<td  align="left">';								
									print '	<input class="button"  type="submit" value="Selectionner" align="right" >';	
									print '</td>';
								}
								print '</tr></tbody></table id="Niv5_SelectionPart"></form>';
							print '</td></tr><tr><td  align="center" >';
								if ($bull->regle < $bull->BULL_ARCHIVE)   {
									print '<form method="POST" name="CherchePersRec" action="'.$_SERVER["PHP_SELF"].'#AncreRecours">';
									print '<input type="hidden" name="action" value="'.$ACT_CRE_PERS_RESP.'">';
									print '<input type="hidden" name="id_bull" value="'.$id_bull.'">';
									print '<input type="hidden" name="token" value="'.newtoken().'">';
									print '	<input class="button" type="submit" value="'.$langs->trans("CreerContact").'" align="right" ></form>';
								}			
					print '</td></tr></tbody></table id="Niv4_Selection">';/* fin Table Selection  */
	} //AffPersonneRecoursSel
	function AffPersonneRecoursSaisie()
	{
	global $ACT_SEL_PERS_RESP, $ACT_CRE_PERS_RESP; 
	global $action, $bull, $langs;
	
	print '<table class="liste id="Niv4_Saisie" width="100%" ><tbody>';
					if (isset($action) and !($action == '' ) and $action == $ACT_SEL_PERS_RESP) 		
						print    '<tr class="liste_titre"><td class="liste_titre" colspan=4><b>'.$langs->trans('Modification').'</b></td></tr>';
					elseif 	(isset($action) and $action == $ACT_CRE_PERS_RESP ) 
						print    '<tr class="liste_titre"><td class="liste_titre" colspan=4><b>'.$langs->trans('Creation').'</b></td></tr>';
					else print '<tr class="liste_titre"><td class="liste_titre" colspan=4><b>'.$langs->trans('Affichage').'</b></td></tr>';
						print '<tr>';								
							$formcompany = new FormCompany($db);	
							if ( $action == $ACT_SEL_PERS_RESP or $action == $ACT_CRE_PERS_RESP) {
								print    '<td>'.$langs->trans("Lastname").'</td><td colspan=2><input class="flat" size="40" type="text" name="PersNom" value="'.$bull->pers_nom.'"></td>';
								print    '</tr>';
								print    '<tr>';
								print    '<td>'.$langs->trans("Firstname").'</td><td colspan=2><input class="flat" size="20" type="text" name="PersPrenom" value="'.$bull->pers_prenom.'"></td>';
								print    '</tr>';
								print    '<tr>';
								print    '<td>'.$langs->trans("Phone").'</td><td colspan=2><input class="flat" size="20" type="text" value="'.$bull->pers_tel.'" name="PersTel" >';
								print '<img class="hideonsmartphone" border="0" title="Obligatoire" alt="Obligatoire" src="'.DOL_URL_ROOT.'/theme/eldy/img/star.png"></td>';
							}
							else {
								print    '<td  >'.$langs->trans("Lastname").'</td><td colspan=2>'.$bull->pers_nom.'</td>';
								print    '</tr>';
								print    '<tr>';
								print    '<td>'.$langs->trans("Firstname").'</td><td colspan=2>'.$bull->pers_prenom.'</td>';
								print    '</tr>';
								print    '<tr>';
								print    '<td>'.$langs->trans("Phone").'</td><td colspan=2>'.$bull->pers_tel.'</td>';
							}
					
							if ($bull->regle < $bull->BULL_ARCHIVE)								{
									print '   <td>';
									if (isset($action) and !($action == '' ) and $action == $ACT_CRE_PERS_RESP)
										print '  <input class="button" action="ACT_MAJ_PERS_RESP" type="submit" value="Ajouter" align="center">';	
									elseif ($action == $ACT_SEL_PERS_RESP)
										print '<input class="button" action="ACT_MAJ_PERS_RESP"  type="submit" value="Enregistrer" align="center">';
									print '</td>';	
								}
							print    '</tr>';
					print    '</tbody></table id="Niv4_Saisie">';/* fin table creation/modification*/
}//AffPersonneRecoursSaisie
	// Obsolette - Attention, la modif pour le 295 n'a pas été reportée	
	function AfficheRdv()
	{		
		global $langs, $bull, $conf;
		global $action, $SAIS_RDV, $modactrdv;
		
		$w=new CglFonctionCommune ($this->db);	
		$wf = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		// colonne 2 table contenant en colone 1 Personne Recours et et ligne 2 contenant Rendez-vous	
	
		print '</td><td>';
		if ($bull->nblignebulletin)
		{

			// table rendez-vous
			print '<table border="1"><tbody><tr><td width="100%">';
			print '<table><tbody><tr>';
				$wfctcomm->AfficheParagraphe("RendezVous", 5	);
			print '</tr>';						
			print '<tr class="liste_titre"><td class="liste_titre" ><b>'.$langs->trans('Session').'</b></td>';
			print '<td class="liste_titre" ><b>'.$langs->trans('Date').'</b></td>';
			print '<td class="liste_titre"><b>'.$langs->trans('RendezVousCourt').'</b></td>';
			print '<td class="liste_titre">&nbsp;</td></tr>';

			$tabsession = array();
			 
			if (!empty($bull->lines)){
				foreach ($bull->lines as $line )
				{
					if ($line->type_enr == 0 and !($line->action == 'S') and !($line->action == 'X') and $tabsession[$line->id_act] != 1)
					{
						$tabsession[$line->id_act] = 1;
						print '<tr>'."\n";
						print '<form name="obj_update_'.$i.'"  method="POST" action="'.$_SERVER['PHP_SELF'].'?id_bull='.$bull->id.'"  method="POST">'."\n";
						print '<input type="hidden" name="action" value="'.$SAIS_RDV.'">'."\n";
						print '<input type="hidden" name="modactrdv" value="'.$line->id_act.'">'."\n";
						print '<input type="hidden" name="token" value="'.newtoken().'">'."\n";
						print '<td>'.$line->activite_label.'</td>';
						print '<td>'.$w->transfDateFr( $line->activite_heured).'&nbsp;'.$w->transfHeureFr( $line->activite_heured).'</td><td>';		
						$objet = new CglInscription($this->db);
						if (empty ($line->activite_rdv)) $line->activite_rdv= 1;
						$moreforfilter = $objet->select_rdv($line->activite_rdv, 'ActPartIdRdv', '', 1, $line->id_act) ;	
						unset ($objet);				
						// si saisie 
						if ($bull->regle < $bull->BULL_ARCHIVE and $action == $SAIS_RDV and $modactrdv == $line->id_act and empty( $_POST["rdv_update"])) 
						{
							print $moreforfilter;
							print '</td><td>';
							// bouton enregistrement
							print '<input type="hidden" name="rdv_update" value="1">'."\n";
							print '<input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="rdv_update" alt="'.$langs->trans("Enregistrer").'" ">';								
						}	
						elseif ( $bull->regle < $bull->BULL_ARCHIVE and ($action != $SAIS_RDV or $modactrdv != $line->id_act or ($action == $SAIS_RDV and $modactrdv == $line->id_act and $_POST["rdv_update"])))
						{
							// si affichage
							print $line->rdv_lib;
							print '</td><td>';
							// bouton Modification
							if (empty($conf->theme)) $conf->theme = 'eldy';
							print '<input type="hidden" name="rdv_edit" value="1">'."\n";
							print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="rdv_edit" alt="'.$langs->trans("Enregistrer").'">';
						}
						print '</td></form></tr>';
					}
				}	 //foreach
			}			
			print '</tr></tbody></table>';
			// fin table remise et rendez-vous
			print '</td></tr></tbody></table>';

		}
		unset ($w);
		// table contenant Personne Recous et Rendez-vous			
		print '</td></tr></tbody></table ></tr>';
	}//AfficheRdv
		
		
	
	function AfficheEdition()
	{
		global $id_client, $action,$id_bull, $langs, $conf, $user, $db, $bull, $bc;
		
		$formfile = new FormFile($db);				
		print '</tr></tbody></table >';
			print '</td><td>';
				// TABLE DES EDITIONS  
			print '<table class="liste"><tbody>';
			// BOUTON LIG 2	- BULLETIN
			$stcksession = array();
			$var = false;
			if (!empty($bull->lines)){
				foreach ($bull->lines as $line)	{
					if ($line->type_enr == 0)	{
						$rienfaire = 0;
						// Voir si la session a déjà été traitée dans une participation précédente
						if (!empty($stcksession)){
							foreach ($stcksession as $idsess)		{
								if ($idsess == $line->id_act) $rienfaire = 1;
							} //foreach
						}
						if ($rienfaire == 0) {
							$var=!$var;
							print "<tr  $bc[$var] >";
							$this->document_line($langs->trans("DocBull"),  'bulletin', $line);
							print '</tr>';
							$stcksession[] = $line->id_act;
						}
					}
				}//foreach
			}
			$docsite = array(); $nomsite = array();
			// BOUTON LIG 2b - AUTORISATION MINEUR
			$ExtMineur = 0;
			// on cherche au passage les doc concernant les sites concerné par le bulletin
			if (!empty($bull->lines)){
				foreach ($bull->lines as $line)
				{
						$trouve = false;
					if ($line->type_enr == 0 and $line->action != 'X' and $line->action != 'S'){
						if ($line->PartAge <18 or $line->PartAge  == 100) $ExtMineur = 1;
						if (!empty($docsite)) {
							foreach($docsite as $site) {
								if ($site == $line->ficsite and !empty($line->ficsite)) $trouve = true;
							} // foreach
						}
					}
					if ($trouve == false and !empty($line->ficsite)) {
						$docsite[] = $line->ficsite;
						$nomsite[] = $line->activite_lieu;
					}
				}//foreach
			}
			if ($ExtMineur == 1)
			{
					$var=!$var;
					print "<tr  $bc[$var] >";
					$this->document_line($langs->trans("DocAutoPar"), 'Autorisation', '','autorisation.pdf');
					print '</tr>';
			}
			// BOUTON LIG 3	 - CGV
			//if (! isset($line)) $line = New BulletinLigne($this->db);
			if ($type == 'Loc') $docCGV = 'CGVLoc.pdf'; else $docCGV = 'CGVInsc.pdf';
			$var=!$var;
			print "<tr  $bc[$var] >";
			$this->document_line($langs->trans("CGV"),  'CGV', '', $docCGV);
			print '</tr>';
			// BOUTON LIG 4	 - Doc ROUTE
			//if (! isset($line)) $line = New BulletinLigne($this->db);
			$i = 0;
			if (!empty($docsite)) {
				foreach ($docsite as $site) {
					$var=!$var;
					print "<tr  $bc[$var] >";
				   $this->document_line($langs->trans("DocSite", $nomsite[$i] ), 'site', '', $site);
					print '</tr>';	
					$i++;
				}//foreach
			}					
	} /*AfficheEdition*/
	
	function teteTable()
	{
		print '<table><tr>';
	}/*		teteTable */
	function finTable()
	{
		print '</tr></table>';
	}/*		finTable */
	function document_line($titre,  $modele, $line , $file = '')
	{
		global $conf, $bull, $langs;
		
		if ($modele == 'commande' )
		{
			//Obsolette suppression ficcmd
			//print '<td style="border-left:1px; width:75px"  align="left">'.$this->show_cmd($modele).'</td>'."\n";
			print '<td></td>'."\n";
			print '<td>'.$titre.'</td>'."\n";
		}
		elseif ($modele == 'bulletin' ) 
		{
			print '<td style="border-left:1px; width:75px"  align="left">'.$this->show_bull($modele, $line).'</td>'."\n";
			$obj = new CglFonctionCommune ($this->db);
			print '<td>'.$titre.'('.$langs->trans($Depart).' '.$line->activite_label.' du '.$obj->transfDateFrCourt($line->activite_dated).')'.'</td>'."\n";
			unset ($obj);
		}
		else
		{
			print '<td style="border-left:1px; width:75px"  align="left">'.$this->show_doc($modele, $file).'</td>'."\n";
			print '<td>'.$titre.'</td>'."\n";
		}
	}//document_line
	function show_doc($type, $file)
	{
		global $conf;
		
		$mess = '';
		
		$PDFfile=substr( $file, 0, strlen($file)-3).'pdf';
		if ($PDFfile == $file) {
			$legende = 'Afficher le document PDF';
			$img=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf2.png';
		}
		else {
			$legende = 'Ouvrir le document ODT';
			$img=DOL_URL_ROOT.'/theme/common/mime/ooffice.png';
		}
		
		$mess = '<a href="'.DOL_MAIN_URL_ROOT.'/document.php?modulepart=cglinscription&file='.$type.'%2F'.$file.'" data-ajax="false" alt="'.$legende.'" title="'.$legende.'" target="_blank">';
		$mess.= '<img src="'.$img.'" border="0" align="absmiddle" hspace="2px" ></a>';
		
		return $mess;
	}//show_doc
	
	function show_bull($file, $line)
	{
		global $conf, $bull;
		
		$mess = '';			
			$legende = 'Generer le document';
			if ( $bull->regle < $bull->BULL_ARCHIVE)
			{
				$mess = '<a href="'.$_SERVER['PHP_SELF'].'?id_bull='.$bull->id.'&action=CreerBulletin&session='.$line->id_act.'" alt="'.$legende.'" title="'.$legende.'">';
//				$mess = '<a href="'.'./documentInscLoc.php?modulepart=cglinscription&file='.$type.'%2F'.$file.'&id_bull='.$bull->id.'&action=CreerBulletin&session='.$line->id_act.'&perm=inscrire" data-ajax="false" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>&nbsp;';
			}
			$mess .= $this->AfficheODTouPDF($line->ficbull, 'cglinscription');
	
		return $mess;
	}//show_bull
	
	/*
	* Afficher l’icône du fichier bulletin, suivant son type
	*
	* @param 	file	nom du fichier recherché
	* @param	domaine	nom du module Dolibarr
	*
	* @retour code html permettant l'affichage de l'icône voulue
	*/
	function AfficheODTouPDF($file, $domaine)
	{
		global $conf;
		// afficher le PDF sinon ODT s'il existe
		$PDFfile=substr( $file, 0, strlen($file)-3).'pdf';
		$ODTfile=substr( $file, 0, strlen($file)-3).'odt';
		if (file_exists($ODTfile))  {
			$afffile=$ODTfile;
			$legende = 'Ouvrir le document ODT';
			$img=DOL_URL_ROOT.'/theme/common/mime/ooffice.png';
			$mess.=$this->AfficheFichier($afffile, $legende, $img) ;
		}
		if (file_exists($PDFfile) and filemtime($ODTfile) <= filemtime($PDFfile)) {
			$afffile= $PDFfile;
			$legende = 'Afficher le document PDF';	
			$img=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf2.png';
			$mess.=$this->AfficheFichier($afffile, $legende, $img) ;
		}
		return $mess;
	} // 	AfficheODTouPDF
	
	function AfficheFichier($file, $legende, $img) 
	{
		//formatter chemin bulletin fic2Ffichier.odt
		if (file_exists($file) )			{
			$ret1 = strlen('cglinscription');
			$ret = strpos($file, 'cglinscription');
			$str = ' target="_blank"';
			$fichier=substr($file, $ret+$ret1+1);
			// remplacer / par %2F
			$fich1 = str_replace('/','%2F', $fichier);
			$mess .= '<a href="'.DOL_MAIN_URL_ROOT.'/document.php?modulepart=cglinscription&file='.$fich1.'" alt="'.$legende.'" title="'.$legende.'"'.$str.'>';
			$mess.= '<img src="'.$img.'" border="0" align="absmiddle" hspace="2px" ></a>';
		}
		return $mess;

	} //AfficheFichier
	
	function AbandonArchive()
	{
		global $bull, $langs, $BUL_CONFABANDON;;
		
		// confirmation d'abandon 	
		$form = new Form($db);
		$formquestion=array();
		$question =  $langs->trans('Abandon');
		$text = $langs->trans ('ConfirmRefusAnul');
		if ($bull->type == 'Loc') {
			$text .= $langs->trans('ConfirmAbnCnt').' '.$bull->ref;
			$lb_id = 'id_contrat';
		}
		else {
			$text .= $langs->trans('ConfirmAbnBull').' '.$bull->ref	;
			$lb_id = 'id_bull';
		}
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'.$lb_id.'='.$bull->id.'&type='.$bull->type,$question,$text,$BUL_CONFABANDON,'','',1);
		print $formconfirm;
	} //AbandonArchive
	


 }//Class
 
?>