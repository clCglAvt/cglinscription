<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
 * Version CAV - 2.8 hiver 2023
 *		- Séparation refmat en IdentMat et marque 
 *		- vérification des foreach
 *		- vérification des conflits pour planning vélo
 *		- contrat technique
 *		- Fenêtre modale pour modif pour echange
 *		- reassociation BU/LO à un autre contrat
 *		- Bouton pour édition en un tableau des poids/taille/age/prenom
 * Version CAV - 2.8.3 - printemps 2023
 *		- suppression des guillemets dans les textarea
 *		- formatage des référence des vélos en conflit (gras, image d'info)( bug 269)
 *		- récupérer la remise lors de la saisie ultérieur du numéro de vélo (bug 284)
 * Version CAV - 2.8.5 - printemps 2023
 *		- vérification à la volée des conflit de vélo  (308b)
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
  *   	\file       custum/cglinscription/class/html.formlocation.class.php
 *		\ingroup    cglinscription
 *		\brief      Interface utilisateur pour la saisie location
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once("./class/cgllocation.class.php");
require_once("../agefodd/class/html.formagefodd.class.php");
require_once("../agefodd/class/agsession.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/html.formcommun.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
require_once DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctDolibarrRevues.class.php';
require_once('../cglavt/class/html.cglFctCommune.class.php');
require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');

 class FormCglLocation extends Form
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

		
	function SaisieLocGlobal()
	{
		global $id_contrat, $langs, $conf;
		
		if (empty($id_contrat)) $id_contrat = $bull->id;
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id_contrat='.$id_contrat.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="id_contrat" value="'.$id_contrat.'">';

		$wf = new FormCglCommun($this->db);
		print '<table  id=Niv1_infoLoc  style="border:1px solid black;font-size:13px;background-color:'.$langs->trans("ClPaveSaisie").';"  width="100%"> <tbody><tr>';
		print '<td colspan=4>';
		$this->PaveRetDep();
		print '</td></tr>';
		//print '<tr><td width="20%">';
		//$this->PaveBulletin();
		//print '</td><td>';
		// suppression temporaire de l'affichage 
		//print '<td>';
		//$this->PaveResa();
		//print '</td></tr>';
		//print '<tr><td>';
		//$this->PaveObservGene();
		//print '</td></tr>';
		print '<tr><td>';
		if (empty($conf->cahiersuivi) )		$wf->PaveObservPriv();
		print '</td></tr>';
		print '<tr><td align="right">';
		$this->BtModEntete();
		print '</td></tr>';
		print '</tbody></table id=Niv1_infoLoc>';
		print '</form>';
	} //SaisieLocGlobal
	function PaveObservGene()
	{
		global $langs, $MOD_LOCINFO, $action, $bull, $event_filtre_car_saisie;
		global  $LocGlbObs;
		
		if (!empty($LocGlbObs)) $bull->locObs = $LocGlbObs;
	
		$wf = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		print '<tr>';	
		$wfctcomm->AfficheParagraphe("InfoLocation", 4);
		//print info_admin($langs->trans("LocObsAdresseTransfert"),1);
		print '</tr><tr><td colspan=2>';	
		print '<div class="tabBar">';
//		if ($action == $MOD_LOCINFO) 			{			
			print '<textarea  cols="150"  rows="'.ROWS_1.'" wrap="soft" name="LocGlbObs" '.$event_filtre_car_saisie.' >';
			print $bull->locObs.'</textarea>';
//		}
//		else
//			print 	$bull->locObs;
		
		print '</div>';
		print '</td></tr>';
		unset ($wf);	
	}//PaveObservGene
	function PaveResa1() // Suppresion temporaire
	{
		global $langs, $bull, $user;
		
		$wf = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$objet = new CglLocation($this->db);
		print '<table  id=Niv2_infoLoc width=100%> <tbody><tr>';
		//style="border:1px solid"
		$afflib = info_admin($langs->trans("DefLibelle"),1);
		$wfctcomm->AfficheParagraphe("InfoLocationResa", 4, $afflib );
		print '<tr><td>';
		print '<div class="tabBar">';
		$this->PaveResa_det();
		print '</div>';

		print '</td></tr></tbody></table id=Niv2_InfoLoc>';
		//print '<td>';
		// paragraphe de saisie d'un nouveau départ
		unset ($objet);
		unset ($wf);
	}//PaveResa
	function PaveResa_det1()
	{
		global $langs, $bull, $user, $event_filtre_car_saisie;
		global $action;
		global $LocResaObs, $LocStResa;
//		global $MOD_LOCINFO;
		
		$objet = new CglLocation($this->db);
		
		if (!empty($LocResaObs)) $bull->fk_sttResa = $LocResaObs;
		if (!empty($LocStResa)) $bull->fk_sttResa = $LocStResa;
		
		print '<table id=Niv3_infoLoc width=100%><tbody><tr><td colspan=4>';

		print '<table id=Niv5_infoLoc width=100%  class="border" ><tbody><tr>';
		//print '<td width="30%">';
		//print $langs->trans("LocResa");
		//print info_admin($langs->trans("DefLibelle"),1);
		//print '</td>';	
		print '<td align="left" colspan=3 >';	
//		if ($action == $MOD_LOCINFO) 	{			
			if (empty($bull->locResa)) //$temp = '<span style="color:#C0C0C0">'.$langs->trans("LocObsResaModele").'</span>';
				$temp = $langs->trans("LocObsResaModele");
			else $temp = $bull->locResa;
			
			print '<textarea cols="120" rows="'.ROWS_3.'" wrap="soft" name="LocResaObs" '.$event_filtre_car_saisie.' >';
			print $temp.'</textarea>';
//		}
//		else
//			print  $bull->locResa;
		print '</td><tr><td  width="100" colspan=2>'.$langs->trans("LocSttResa").'&nbsp';	
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '</td>';
		print '<td>';
//		if ($action == $MOD_LOCINFO) 						
			print $objet->select_StResa($bull->fk_sttResa,'LocStResa');	
//		else
//			print $bull->SttResa;		
//		print '</td>';			
			
		print '</td></tr></tbody></table id=Niv4_infoLoc>';		
		print '</td></tr></tbody></table id=Niv3_infoLoc>';
		unset ($objet);
		} //PaveResa_1
	function BtModEntete()
	{
		global $bull, $action, $ENR_LOCINFO, $langs;
	//	global $MOD_LOCINFO;
		
	//	if ($action == $MOD_LOCINFO) 			{		
			print '<input type="hidden" name="action" value="'.$ENR_LOCINFO.'">';		
			print '<input class="button" name="BtEnrInfo" type="submit" value="'.$langs->trans("BtEnregistrer").'">';	
	//	}
	//	else
	//	{
	//		if ($bull->regle < $bull->BULL_ARCHIVE) {
	//			print '<input type="hidden" name="action" value="'.$MOD_LOCINFO.'">';		
	//			print '<input class="button"  type="submit" value="'.$langs->trans("BtModifier").'">';	
	//		}
	//	}
	} //BtModEntete
	
	function PaveRetDep()
	{
		global $langs, $bull, $user, $conf, $event_filtre_car_saisie;
		global $action;
		global $LocDateHeureRet, $LocDateHeureDepose,  $LocLieuRetrait, $LocLieuDepose;
		global $ENR_LOCINFO, $CRE_BULL, $CRE_TIERS_BULL;
		//global $MOD_LOCINFO;
		global $extrafields, $agsession;
		global $bc;
		$var=true;
	
		$objet = new CglLocation($this->db);
		$w1 = new CglFonctionDolibarr($this->db);	
		
		if (!empty($LocDateHeureRet)) $bull->locdateretrait = $LocDateHeureRet;
		if (!empty($LocDateHeureDepose)) $bull->locdatedepose = $LocDateHeureDepose;
		if (!empty($LocLieuRetrait)) $bull->loclieuretrait = $LocLieuRetrait;
		if (!empty($LocLieuDepose)) $bull->loclieudepose = $LocLieuDepose;
		

		
		$wf = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		print '<table id=Niv1_RetDep width=100%   ><tbody>';
		print '<tr>';	
		$wfctcomm->AfficheParagraphe("Retrait et Retour", 1);	
		print '</tr><tr>';	
		print '<td  >';
		
		print '<div class="tabBar">';
		print '<table id=Niv2_RetDep width=100%  class="border" ><tbody>';
		
		print '<tr><td></td>';
		print '<td><i>'.$langs->trans("LocRetrait").'</i></td >';	
		print '<td><i>'.$langs->trans("LocDepose").'</i></td></tr>';	
		print '<tr  $bc[$var]>';
		print '<td><i>'.$langs->trans("LocDates").'</i></td>';
		print '<td>';
		$now=dol_now('tzuser');


		if (empty($bull->locdateretrait) ) {$temp = new DateTime("now", new DateTimeZone('Europe/Paris')); $bull->locdateretrait = $temp->format('Y-m-d H:i');}
			print $w1->select_date($bull->locdateretrait,'LocDateRet',1,1,'',"add",1,1,0,0,'','','',6,22,30);
 
			print '<script type="text/javascript" language="javascript">
			$(document).ready(function() {
				$("#LocDateRet").change( function () {
						document.getElementById("LocDateDepose").value = document.getElementById("LocDateRet").value;
						dpChangeDay("LocDateRet", "dd/MM/yyyy")
					})
				});
			</script>';	

			print '</td>';
		print '<td>';
		if (empty($bull->locdatedepose)){$temp = new DateTime("now", new DateTimeZone('Europe/Paris')); $bull->locdatedepose = $temp->format('Y-m-d H:i');}
		$w1->select_date($bull->locdatedepose,'LocDateDepose',1,1	,'',"add",1,1,0,0,'','','',6,22,30);
		print '</td>';
		print '</tr>';
		print '<tr $bc[$var]>';
		print '<td  ><i>'.$langs->trans("LocLieux").'</i>';print info_admin($langs->trans("DefLibelle"),1); print '</td>';
		print '<td>';
		
		//if ($action == $MOD_LOCINFO) 		{			
			print '<textarea cols="30" rows="'.ROWS_3.'" wrap="soft" name="LocLieuRetrait"  '.$event_filtre_car_saisie.' >';
			print $bull->loclieuretrait.'</textarea>';
		//}
		//else 
		//	print $bull->loclieuretrait;
		print '</td ><td>';		
		//if ($action == $MOD_LOCINFO) {			
			print '<textarea cols="30" rows="'.ROWS_3.'" wrap="soft" name="LocLieuDepose" '.$event_filtre_car_saisie.' >';
			print $bull->loclieudepose.'</textarea>';
		//}
		//else 
		//	print $bull->loclieudepose;
		print '</td ></tr>';
		
		print '</tbody></table id=Niv2_RetDep  >';
		print '</div>';
		print '</td></tr>';
		print '</tbody></table id=Niv1_RetDep>';
		
		unset ($objet);		
		unset ($wf);
	}//	PaveRetDep
	
	function Form_lig_rem_deb ()
	{
		global $bull, $id_act;	
		
		print '<form method="POST" name="LocMat_Rando_Cond" action="'.$_SERVER["PHP_SELF"].'#AncreLstDetail">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="id_contrat" value="'.$bull->id.'">';
		print '<input type="hidden" name="id_act" value="'.$id_act.'">';
		print '<input type="hidden" name="token" value="'.newtoken().'">';
} //Form_lig_rem_deb

	function Form_lig_rem_fin()
	{
		print '</form></td>';
	}//Form_lig_rem_fin

	function AffLocDetRemCaution()
	{
		global $langs, $bull;
		// si non edit 
		$this->Form_lig_rem_deb ();
		$this->AfficheLocDet();
		
		//if (!empty($action) and ($action == $CRE_LIGLOC   or $action == $ACT_SEL_ACTPART ))
		if (!empty(GETPOST("BtNvLigne", 'alpha')) or  !empty(GETPOST("BtSelLocDet", 'alpha')))
		{
			$this->Form_lig_rem_fin ();
			$this->Form_lig_rem_deb ();
			//if ($bull->regle <$bull->BULL_FACTURE) $this->AfficheBtNellLocDet();
			print '<table  id=Niv2_ChoixParticip width="100%" ><tbody><tr><td>';
			$this->SaisieLigne();
			print '</td></tr></tbody></table  id=Niv2_ChoixParticip >';
			$this->Form_lig_rem_fin ();
			$this->Form_lig_rem_deb ();
		}
		print '<table  id=Niv1_AfficheTravail width=100% border=1><tbody>';
		print '<tr>';
		print '<td width=30%>';
		$this->AfficheNvMateriel();
		print '</td>';
		print '<td width=30%>';
		$this->AfficheTabCaution();
		print '</td >';
		if ($bull->facturable) {
			print '<td rowspan=2 width=40%>';
			$this->AfficheTabRemise();
			print '</td>';
		}
		print '</tr>';

		$wf = new FormCglCommun ($this->db);
		print '<tr>';
		print '<td align=center>';
		$wf->AffichePoidsTaille();
		print '</td>';
		print '<td >';
		//$this->AfficheTabSupPart();		
		$wf->AfficheDelLigneDetail();		
		print '</td>';
		print '</tr>';
		unset($wf);
		print '</tbody></table  id=Niv1_AfficheTravail >';
		$this->Form_lig_rem_fin ();
		print '</tr>';				
	} //AffLocDetRemCaution
	
	function AfficheNvMateriel()
	{
	global $langs, $bull;
	
		print '<table  id=Niv2_AfficheNvMateriel width=100%><tbody><tr>';
		$wfc = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$wfctcomm->AfficheParagraphe("TiNvMateriel", 2	);
		unset ($wfc);
		print '</tr><tr><td align=center>';
		print '<input class="button" name="BtNvLigne" type="submit" value="'.$langs->trans("BtNelLigne").'">';	
		print '</td></tr>';

	print '</tbody></table  id=Niv2_AfficheNvMateriel >';


} // AfficheNvMateriel

	/*
	* Affiche le pavé Matériel mis à dispo, rando, Remise générale et Conditons de ventes
	*/

	
	function BtLocDet()
	{
		global $action, $bull, $langs, $MOD_LOC_RET, $RETGENMAT, $UPD_LOC_RET, $RETGENMATPART;
		
		// bouton Retour Complet afficher  dï¿½s que action = retour
		// bouton retour partiel affichï¿½  dï¿½s que action = retour
		// bouton enregistrer en lieu et place de bouton retour partiel avec sup de bouton Retour Complet
			print '<td colspan=12 align="right">';
			if ($action == $MOD_LOC_RET) 	{
					print '<td colspan=3 align="right">';
				//print '<a class="butActionRefused" href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$RETGENMATPART.'">'.$langs->trans("Enregistrer").'</a>';	
				print '<input type="hidden" name="action" value="'.$UPD_LOC_RET.'">';			
				print '<input class="button" action="UPD_LOC_RET" type="submit" value="'.$langs->trans("BtEnregistrer").'">';	
			}
			elseif ($bull->regle <$bull->BULL_ARCHIVE) {
				if ($bull->statut >= $bull->BULL_RETOUR) { 
					print '<td colspan=3 align="right">';
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$MOD_LOC_RET.'">'.$langs->trans("BtRetMatPart").'</a>';	
				}
			}
			print '</td>';
	} //BtLocDet
	
	function AfficheLocDet()
	{
		global  $ACT_SEL_ACTPART, $ACT_SUP_LOCDET,  $CONF_SUP_LOCDET, $CRE_LIGLOC,  $MOD_LOC_RET, $UPD_LOC_RET;
		global $TYPE_SESSION, $RETGENMAT;
		global $PartNom, $PartPrenom, $PartCiv, $PartAdresse, $PartDateNaissance, $PartTaille, $PartPoids,$PartENF,  $PartAge, $PartDateInfo;
		global $PU, $PT, $Rem , $ActPartQte, $FacTotal, $ActPartObs, $ActPartIdRdv, $TiersOrig;
		global $id_client, $action, $langs, $db, $conf;
		global $id_act, $id_part, $id_rang , $id_contrat, $bull, $id_contratdet, $lineajout, $bc, $tabrowid;
					
		$numline= count($bull->lines);
		if ($numline >10) $flgancremilieu = true; else $flgancremilieu= false ;
		if (!$flgancremilieu) 		print '<a name="AncreLstDetail">';

		// PrÃ©pare le js pour mettre toutes les checkbox d'un bulletin  Ã  Actif ou / Non Actif
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

		
		$wf = new FormCglCommun ($this->db);
		
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$url = $_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$RETGENMAT;
		if ($bull->statut >= $bull->BULL_VAL and $bull->regle <$bull->BULL_ARCHIVE)	$this->AfficheParagrapheBouton("TitrDetLoc", 2, "TitreRetGen",$url,  "AidBtRetGenMat");
		else $wfctcomm->AfficheParagraphe("TitrDetLoc", 3 );
		print '<a name="AncreLstDetail id = "AncreLstDetail"">&nbsp&nbsp;</a>';
		$w=new CglLocation ($this->db);
		/* TABLEAU DESLOCATION - PATICIPANTS */
		print '<table border="1" id="Niv1AffichActPart" width="100%"><tbody><tr><td width="100%">';
			print '<table class="liste" bgcolor="#f0f0f0" id="Niv2_ListeParticip" width="100%"><tbody>';
			print '<tr class="liste_titre">';
			print '<td>&nbsp;</td>';
			print '<td>'.$langs->trans("Service").'</td>';
			//print '<td>'.$langs->trans("Materiel").'</td>';
			print '<td>'.$langs->trans("RefVelo").'</td>';
			print '<td>'.$langs->trans("NomPrenomLoc").'</td>';
			print '<td>'.$langs->trans("Taille").'</td>';
			print '<td  align=center>'.$langs->trans("DateDeb").'</td>';
			print '<td  align=center>'.$langs->trans("DateFin").'</td>';
			print '<td>'.$langs->trans("ObsMat").'</td>';
			print '<td>'.$langs->trans("Duree").'</td>';
			if ($bull->facturable) {
				print '<td>'.$langs->trans("PuTtc").'</td>';
				print '<td>'.$langs->trans("Rem").'</td>';
				print '<td>'.$langs->trans("PtTtc").'</td>';
			}				
			//if ($bull->statut > $bull->BULL_ENCOURS) 
			//		print '<td>'.$langs->trans("Retour").'</td>';
			print '<tr><td>';
	
		if ($bull->nblignecontratLoc)
		{		
		$formaide = new Form ($this->db);
		//pour chaque ligne ,  zones de saisies ou affichage
			// Boucle sur chaque ligne 			
			$i=1;$j=1;		
			$var=True;	
			$wform=new form ($this->db);
			if (!empty($bull->lines)) 
			{				
				foreach ($bull->lines as $line )
				{
					if (($line->type_enr ==  $line->LINE_ACT or $line->type_enr ==  $line->LINE_BC)  and !($line->action == 'S')and !($line->action == 'X'))
					{
						$var=!$var;
						if ( $line->type_enr ==  $line->LINE_BC ) $style = "style='color:red;'";
						else $style = "";
						print "<tr  $bc[$var] ".$style.">";
						$line->rangecran=$j;
						
						print '<td>';

						if ($bull->regle != $bull->BULL_ARCHIVE)
						{
							if ($line->type_enr == $line->LINE_ACT  ) print '<a href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&BtSelLocDet=SelLocDet&id_contratdet='.$line->id.'&id_act='.$line->id_act.'#AncreSaisieLocation">'.img_edit().'</a>&nbsp;';
							if ($bull->regle < $bull->BULL_FACTURE)
								print '<a href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$ACT_SUP_LOCDET.'&BtSupLocDet=SupActPart&id_contratdet='.$line->id.'">'.img_delete().'</a>';
						}			
						if ( $line->type_enr == $line->LINE_ACT  ) print '<td>'.$wf->getNomUrl("object_company.png", 'Produit',0,$line->fk_service, 'Loc')."&nbsp".$line->refservice.'</td>';
						else print  
							//print '<td>'.$bull->InfoRemFixe().'</td>';
							print '<td>'.$line->textremisegen.'</td>';
						if ($line->type_enr == $line->LINE_ACT )  {
							// print '<td>'.$line->materiel.'</td>';
							
							if ($line->fl_conflitIdentmat == true) $style = 'style="color:red;font-weight:bold;"  ';
							else $style = '';							
							print '<td '.$style.'>'.$line->refmat;
							
							if ($line->fl_conflitIdentmat == true)  
								print info_admin($langs->trans("VelosEnConflit", $line->lstCntConflit),1);
							print '</td>';
							// cas général d'une location : prendre le nom-prenom le tout dans le champ NomTrajet de bull_det de bulletin.class
							print '<td>'.$line->NomTrajet.' ';
							//if (!empty($line->observation)) print $wf->info_bulle($line->observation, 'info', ' id="img_info" name="img_info" ');
							print '</td>';					
							print '<td>'.$line->PartTaille.'</td>';
						}
						else print '<td colspan=3>'.$line->observation.'</td>';
						if (!empty($line->dateretrait)) 	print '<td align=center>'.$line->dateretrait .'</td>';
						else 	print '<td>&nbsp;</td>';
						if (!empty($line->datedepose)) 	print '<td  align=center>'.$line->datedepose .'</td>';
						else 	print '<td></td>';
						if (!empty($line->observation) and $line->type_enr == $line->LINE_ACT ) {
							$s=$line->observation;
							print '<td>'.$formaide->textwithpicto('',$s,1).'</td>';
						}
						else
							print '<td></td>';	
						if (empty($line->duree) or $line->duree == -1)  print '<td>&nbsp;</td>';
						elseif ($line->duree == 0.00 or $line->duree == 0.5) print '<td>1/2 j</td>';
						elseif ($line->duree > 0 )print '<td>'.$line->duree.'</td>';
						
						
						if ($bull->facturable) {
							print '<td>'.price2num($line->pu).'</td>';
							print '<td>'.$line->remise_percent.'</td>';
							//print '<td align=center>'.$line->qte.'</td>';
							$qte = $line->qte;
							if ($qte ==0) $qte=1;
							$rem = $line->remise_percent;
							$pu=$line->pu;
							if ($line->type_enr == $line->LINE_ACT ) $pt=$line->calulPtAct($bull->type_session_cgl,$pu,$qte,$rem);
							else $pt = -1*$line->mttremfixe ;
							$pt = price2num($pt);
							print '<td>'.$pt.' </td>';	
						}

						if ($bull->statut > $bull->BULL_DEPART and $bull->statut < $bull->BULL_CLOS) {
							print '<td>';	
							if ($action == $MOD_LOC_RET)
									print $wform->selectyesno('matret['.$line->id.']',$line->qteret,1,false);
							else
									print ($line->qteret == 1) ?'Oui':'Non';
									//print $wform->selectyesno('matret['.$line->id.']',$line->qteret,1,true );
							print '</td>';	
							}
									
						if (isset ($tabrowid) and !empty($tabrowid)) 
							foreach ($tabrowid as $row) { 
								if ($row == $line->id) { $flgcheked = true; break; }
								else $flgcheked = false; 
							}	
						print '<td>';
						print '<input class="flat checkselection_" name="rowid['.$line->id.']" type="checkbox" value="'.$line->id.'" size="1"'.($flgcheked?' checked="checked"':'').'>';
						print '</td>';

						
						print '</tr>';	
						$j++;
						if ($flgancremilieu and $j < $numline ) 	{
							print '<a name="AncreLstDetail">';
							$flgancremilieu = false;
						}
					}
					$i++;
				} /* Fin de boucle */
			}
			unset ($wform);
			// Bouton Selectionner - deselectionner les boites checkbox					
			if ($conf->use_javascript_ajax) print '<tr><td colspan=9 ></td><td colspan=4 align=right><a href="#AncreLstDetail" id="checkall_'.$bid.'">'.$langs->trans("All").'</a> / <a href="#AncreLstDetail" id="checknone_'.$bid.'">'.$langs->trans("None").'</a></td></tr>';

			if ($bull->statut > $bull->BULL_DEPART and $bull->statut > $bull->BULL_CLOS) {
				print '<tr>';
				$this->BtLocDet();
				print '</tr>';
			}	
			
			// Ligne TOTAL			
			if ($bull->facturable) {
				print '<tr><td colspan=13>';
				if (empty($id_act)  and $action != $ACT_SEL_ACTPART and empty(GETPOST("BtSelLocDet", 'alpha'))) $id_act = $line->id_act;
				print '<table class="liste" id=Niv2_LigneFact width="100%">';
				$moreforfilter='';
				$moreforfilter.=$langs->trans('TotalFact');
			
				print '<tr class="liste_titre" >';
				print '<td class="liste_titre"  width="10%">';
			
				print $moreforfilter;
				print '</td>';
				$ptt=$bull->TotalFac();
				print '<td  width="91%" align="right" ><font size=4>'.$ptt.'&nbsp&nbspeuros</font></td>';
				print '</tr>';
			}
				
		print '</table id=Niv2_LigneFact >';
		}/* il y a des activites*/	
		if (!empty($action) and ($action == $CRE_LIGLOC   or $action == $ACT_SEL_ACTPART or !empty(GETPOST("BtSelLocDet", 'alpha')))) print "</form>";
		print "</table id=Niv2_ListeParticip>";
	
		print '&nbsp&nbsp&nbsp';

		if (!empty($action) and ($action == $CRE_LIGLOC   or $action == $ACT_SEL_ACTPART or !empty(GETPOST("BtSelLocDet", 'alpha'))))
		{
			if ($bull->regle <$bull->BULL_FACTURE) $this->AfficheBtNellLocDet();		
		}
		unset ($wf);
		unset ($w);
		print '</tbody></table id=Niv1AffichActPart>';

	}//AfficheLocDet
	/*
	* Affiche le pav� Mat�riel mis � dispo, rando, Remise g�n�rale et Conditons de ventes
	*/

	function AfficheLocMat_Rando_Cond1()
	{
		global $action, $langs, $bull;
		global $MOD_MATMAD, $MOD_MATMAD_RET, $MOD_RANDO_RET, $MOD_RANDO;
	
		$EtatCnt='';	
		/*on a supprimer les états Depart et retour 
		if ($action == $MOD_MATMAD) {
			if ($bull->statut  < $bull->BULL_RETOUR	) 
				$EtatCnt='Init';
	
			elseif ($bull->statut == $bull->BULL_RETOUR)  
				$EtatCnt='Ret';
		}*/
		$EtatCnt='Ret';

		
		print '<form method="POST" name="LocMat_Rando_Cond" action="'.$_SERVER["PHP_SELF"].'#AncreLstDetail">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="id_contrat" value="'.$bull->id.'">';
		print '<input type="hidden" name="id_act" value="'.$MOD_RANDO.'">';
		print '<table  id=Niv1_AfficheLocMat_Rando width=100%><tbody>';
		print '<tr><td  width=50%>';		
		// Pavé supprimé pour 2017
		if ("TOTO" == "TITI") {
			print '<table  id=Niv2_AfficheLocMat width=100%><tbody ><tr>';
					//	$this->AfficheSaisieLocmatMad($EtatCnt);
			print '</tr></tbody></table  id=Niv2_AfficheLocMat>';
			print '</td>';
			print '<td  width=30%>';
			print '<table  id=Niv2_AfficheLocRando  width=100%><tbody><tr>';
			//	$this->AfficheSaisieLocRando($EtatCnt);	
			 print '</tr></tbody></table  id=Niv2_AfficheLocRando>';	 
				
			print '</td><td ';		
			print '<td  width=30%>';
		}
		// Fin pavé supprimé
				if  ($action <> $MOD_MATMAD) {
					//print '<td  >';
					print '<table  id=Niv3_AfficheRem   style="border:1px solid black;font-size:13px;background-color:'.$langs->trans("ClPaveSaisie").';"  width="100%"><tbody><tr>';
						$this->AfficheRemise();
					print '</tr></tbody></table  id=Niv3_AfficheRem>';	
					print '</td>';
				}
					//print '</tr>';
					//print '<tr><td>';
					print '<td>';
					print '<table  id=Niv3_AfficheCaution  width=100%  style="border:1px solid black;font-size:13px;background-color:'.$langs->trans("ClPaveSaisie").';" ><tbody><tr>';
						$this->AfficheCaution();
					print '</tr></tbody></table  id=Niv3_AfficheCaution>';	
				print '</td  >';
						
		//print '</td></tr><tr>';
		print '</tr>';
		print '</tbody></table  id=Niv1_AfficheLocMat_Rando >';
		print '</form></tr>';
// Info CCA
		//$this->BtModifEnr_MatRando_Caut($EtatCnt);
	/*
				divnav {
			  max-width: 20%;
			  Height: auto;
			  background-color: pink;
			  overflow: auto;
			  float: left;
			  padding: 10px;
			  margin: 10px;
			  border-right: solid 2px;
			}
				 
			<divnav>
			  <p>
			   $this->AfficheLocmatMad()</p>
				</div>
				  <div>
			  <p>
				$this->AfficheLocRando</p>
				</div>
				   <div class="bold">
			  <p><b>
				En troisi�eme colonne</b></p>
				<p>Et en plusisurs lignes
				</b></p>
				</divnav>
	*/
	}//AfficheLocMat_Rando

	function AfficheTabRemise()
	{
		global $action, $langs, $bull;
		global $MOD_MATMAD, $MOD_MATMAD_RET,   $CRE_LIGLOC;
	
		$EtatCnt='';	
		/*on a supprimer les états Depart et retour 
		if ($action == $MOD_MATMAD) {
			if ($bull->statut  < $bull->BULL_RETOUR	) 
				$EtatCnt='Init';
	
			elseif ($bull->statut == $bull->BULL_RETOUR)  
				$EtatCnt='Ret';
		}*/
		$EtatCnt='Ret';

// Form_Rem_Deb		
	
		/* print '<tr><td  width=40%>';*/
				if  ($action <> $MOD_MATMAD) {
					$wcom = new FormCglCommun ($this->db);
					print '<table  id=Niv3_AfficheRem   style="border:1px solid black;font-size:13px;background-color:'.$langs->trans("ClPaveSaisie").';"  width="100%"><tbody><tr>';
					
			$wfc = new FormCglCommun ($this->db);
			$wfctcomm = new FormCglFonctionCommune($this->db);
			// table remise générale
				$wfctcomm->AfficheParagraphe($langs->trans("TiRemiseInsc"), 2	);
			unset ($wfc);	
		print '<tr>';
					$wcom->AfficheRemise();
					print '</tr></tbody></table  id=Niv3_AfficheRem>';	
					print '</td>';
					unset($wcom);
				}
				
	}//AfficheTabRemise

	
	function AfficheTabCaution()
	{
		global $langs, $bull;		
		global $action, $SAIS_CAUTACC, $UPD_CAUTACC,$UPD_MATMAD,  $CAL_ACPT ;	
		

		print '<table  id=Niv3_AfficheCaution  width=100%  style="border:1px solid black;font-size:13px;background-color:'.$langs->trans("ClPaveSaisie").';" ><tbody><tr>';
//		$this->AfficheCaution();
									
		if ($bull->nblignecontratLoc)		{
			$w=new CglLocation ($this->db);	
			$wf = new form ($this->db);
			$wfc = new FormCglCommun ($this->db);
			$wfctcomm = new FormCglFonctionCommune($this->db);
			$wdet = new BulletinLigne($this->db, $bull->type);
			// table remise gï¿½nï¿½rale
				$wfctcomm->AfficheParagraphe("CautAcompt", 2	);
				print '<tr><td>';
			print '<table id="Niv2_CautionAccompte"  width=100% border =1><tbody><tr><td>';
		
			print '<table id="Niv3_CautionAccompte" width=100% ><tbody><tr>';

			// titre Caution
						
			// montant caution	
			print '<td width=70% colspan=1><i>'.$langs->trans('Mttcautionexigee').'</i></td>';
			print '<td>';
	/*		if ($action <> $SAIS_CAUTACC) 
				print $bull->mttcaution.'</td>';	
			else	*/		
				print '<input class="flat" type="text" name="mttcaution"  value="'.$bull->mttcaution.'"></td>';
			if ($bull->statut >= $bull->BULL_VAL) {

			
				// PavÃ© supprimÃ© pour 2018
				print '</tr>';
				if ("TOTO" == "TITI") {


					// top caution reï¿½ue
						print '<tr>';
						print '<td colspan=1><i>'.$langs->trans('LbTopCaution').'</i></td><td>';
						if ($action == $SAIS_CAUTACC)
							print $wf->selectyesno( 'topcautionrecue', $bull->top_caution,1, false);
						else
							print $wf->selectyesno( 'topcautionrecue', $bull->top_caution,1, true);
						print '</td><tr>';	
					
						
						// mode de paiement de la caution	
						print '<tr>';
						print '<td width=70% colspan><i>'.$langs->trans("ModCaution").'</i></td>';
						print '<td colspan>';
		//				if ($action == $SAIS_CAUTACC) 	
									$moreforfilter.=$wf->select_types_paiements($bull->fk_modcaution,"modcaution",'',0, 0, 0,0);
		//				else print $bull->lb_modcaution;
						print '</td>';
						
						print '<tr>';
						if ($bull->statut > $bull->BULL_ENCOURS) {
							print '<td width=70% colspan><i>'.$langs->trans("CautRendu").'</i></td>';			
							print '<td  colspan>';
		//					if ($action == $SAIS_CAUTACC)
							if ($bull->top_caution)
								   print $wf->selectyesno( 'retcaution', $bull->ret_caution,1, false);
		//					else	 print $wf->selectyesno( 'retcaution', $bull->ret_caution,1, true);
							print '</td>'; 
						}	
						
						print '<tr><td>&nbsp;</td></tr>';
						
						// top document recu
						
						// top caution reï¿½ue
						print '<tr>';
						print '<td colspan=1><i>'.$langs->trans('LbTopdocrecu').'</i></td><td>';
		//				if ($action == $SAIS_CAUTACC)
							print $wf->selectyesno( 'topdocrecu', $bull->top_doc,1, false);
		/*				else
							print $wf->selectyesno( 'topdocrecu', $bull->top_doc,1, true);
		*/
						print '</td><tr>';	
						// type docume nt
						print '<tr>';
						print '<td width=70% colspan=1><i>'.$langs->trans("DocCaution").'</i></td>';
						if (empty($bull->fk_caution)) $temp = 'neant';
						else $temp = $bull->lb_caution;
						print '<td colspan=1>';
		//				if ($action == $SAIS_CAUTACC) 	 
								print $w->select_caution($bull->fk_caution, 'caution');			
		//				else print $temp;
						print '</td>';
						// top document rendu
						print '</tr>';
							
						print '<tr>';
						if ($bull->statut > $bull->BULL_ENCOURS) {
							print '<td width=70% colspan=1><i>'.$langs->trans("DocRendu").'</i></td>';			
							print '<td  colspan=1>';
		//					if ($action == $SAIS_CAUTACC)
								   print $wf->selectyesno( 'retdoccaution', $bull->ret_doc,1, false);
		/*					else	 print $wf->selectyesno( 'retdoccaution', $bull->ret_doc,1, true);
		*/
							print '</td>'; 
						}	
						
						print '</tr>';
				} // Fin pavÃ© supprimÃ© 2018
				print '<tr>';
				
				print '<td  ><i>'.$langs->trans("ObsCaution").'</i></td>';
				print '<td>';
//				if ($action == $SAIS_CAUTACC) {	
				print '<input class="flat" type="text" name="ObsCaution"  value="'.$bull->obscaution.'">';

				
//				}
//				else print $bull->obscaution;
				print '</td>';	
			}
			print '</tr><tr><td  colspan=1></td><td align=center>';
		
			print '<input type="hidden" name="action" value="'.$UPD_MATMAD.'">';
			print 	'<input class="button"  name="BtEnrCaution" type="submit" value="'.$langs->trans("BtEnregistrer").'"></td>';		
			print '</td></tr>';
			print '</td></tr></tbody></table id="Niv3_CautionAccompte">';
			//print '</form>';

			// fin ligne rendez-vous et 
			//print '</td></tr>';
			unset ($w);
			unset ($wdet);
			unset ($wf);
			
			print '</td></tr></tbody></table id="Niv2_CautionAccompte">';
			print '</td></tr>';	
		}
		
		print '</tr></tbody></table  id=Niv3_AfficheCaution>';	

	}//AfficheTabCaution

	function BtModifEnr_MatRando_Caut($faire ='')
	{	
		global $bull, $langs, $MOD_MATMAD, $MOD_MATMAD_RET, $UPD_MATMAD, $action, $RETGENCAUT, $RETGENMAT, $RETGENMAD, $RETGEN, $RETGENRAND; 
		
		$lbbutActionRet = 'butAction';
				if ($action <> $MOD_MATMAD)	{
					
					if ($bull->regle <$bull->BULL_ARCHIVE) {
						if ($bull->statut > $bull->BULL_DEPART) {
							$lbbutActionRet = 'butActionRefused';
						}
						if ($bull->statut >= $bull->BULL_RETOUR) {
							$lbbutActionRet = 'butAction';						
						}
						else 		$lbbutActionRet = 'butActionRefused';
						
						print '<td align="left">';							
						print '<input type="hidden" name="action" value="'.$MOD_MATMAD.'">';
						print '<input class="button"  type="submit" value="'.$langs->trans("BtModifier").'"></td>';					
					}	
					else 		$lbbutActionRet = 'butActionRefused';					
				}
				else {	
						//print '</td>';	
						print '<td align="left">';							
						print '<input type="hidden" name="action" value="'.$UPD_MATMAD.'">';
						print '<input class="button"  type="submit" value="'.$langs->trans("BtEnregistrer").'"></td>';	
						$lbbutActionRet = 'butActionRefused';
				}
				if ($bull->statut >= $bull->BULL_RETOUR) { 
					print '<div class="tabsAction">';
					print '</td><td>';			
					print '<a class="'.$lbbutActionRet.'" href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$RETGEN.'">'.$langs->trans("BtRetCpl").'</a>';	

					print '</td><td>';
					print '<div class="inline-block divButAction"><a class="'.$lbbutActionRet.'" href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$RETGENMAT.'#AncreLstDetail">'.$langs->trans("BtRetComplMat").'</a></div>';
	
					print '</td><td>';
					print '<div class="inline-block divButAction"><a class="'.$lbbutActionRet.'" href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$RETGENMAD.'#AncreLstDetail">'.$langs->trans("BtRetComplMad").'</a></div>';
				
					print '</td><td>';
					print '<div class="inline-block divButAction"><a class="'.$lbbutActionRet.'" href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$RETGENRAND.'#AncreLstDetail">'.$langs->trans("BtRetComplRando").'</a></div>';
				
					print '</td><td>';
					print '<div class="inline-block divButAction"><a class="'.$lbbutActionRet.'" href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$RETGENCAUT.'#AncreLstDetail">'.$langs->trans("BtRetComplCaut").'</a></div>';
			
				print '</div>';
				}
	} //BtModifEnr_MatRando_Caut

	function AfficheSaisieLocmatMad1($faire ='')
	{
		global $langs, $conf, $bc, $event_filtre_car_saisie;
		global $bull, $action;
		global $UPD_MATMAD, $UPD_MATMAD_RET;
		global $MOD_MATMAD, $MOD_MATMAD_RET, $RETGENMAD;
		
		$wf = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$url = $_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$RETGENMAD.'#AncreLstDetail';
		if ($bull->statut > $bull->BULL_DEPART and $bull->regle <$bull->BULL_ARCHIVE)	$this->AfficheParagrapheBouton("TitrMatMad", 2, "TitreRetGen",$url,  "AidBtRetGenMad");
		else $wfctcomm->AfficheParagraphe("TitrMatMad", 3 );
	
		print '<tr><td colspan>';
		print '<table  id=Niv1_MatMad border=1  width=100% ><tbody><tr><td width="100%">';
		print '<table class="liste" id=Niv2_MatMad width=100%><tbody>';

			print '<tr><td>';		
			// affiche la barre grise des champs affiche
			print '<tr  class="liste_titre">';
			print '<td colspan=1>'.$langs->trans("Materiel").'</td>';
			print '<td align="center">'.$langs->trans("Qte").'</td>';
			if ($bull->statut >= $bull->BULL_DEPART) 
				print '<td align="center">'.$langs->trans("Retour").'</td>';
			print '<td>&nbsp;</td>';
			print "</tr>\n";
			print "<tr><td>&nbsp;</td></tr>\n";
			$now =  dol_now();
			$i=0;
			$old_fk_service='';
			if (!empty($bull->lines_mat_mad)) 
			{				
				foreach ($bull->lines_mat_mad as $lineMatMad)
				{
					if (!isset($old_fk_service)) $old_fk_service = $lineMatMad->fk_service;
					if ($lineMatMad->fk_service != $old_fk_service)				{				
						print "<tr $bc[$var]>";
						print '<td>----</td><td></td><td></td>';
						print '</tr>';
						$var=!$var;
						$old_fk_service=$lineMatMad->fk_service;
					}
					print "<tr $bc[$var]>";
			
					print '<td colspan=1 >'.$lineMatMad->lb_mat_mad."</td>";	
					print '<td align="center" class="nowrap">';
					if ($lineMatMad->qte == 0) $temp = ''; else $temp = $lineMatMad->qte;
					if (($action ==$MOD_MATMAD) and ($faire == 'Init' or $faire == 'Ret'))
						print '<input class="flat" value="'.$temp.'" size="3px" type="int" name="matmad['.$lineMatMad->id.']" >';
					else print $temp;
					print "</td>";		
					print '<td align="center" class="nowrap">';
					if ( $lineMatMad->qteret == 0) $temp = ''; else $temp = $lineMatMad->qteret;				
					if ($faire == 'Ret' and $bull->statut > $bull->BULL_DEPART) {	
						print '<input class="flat" value="'.$temp.'" size="3px" type="int" name="retmatmad['.$lineMatMad->id.']" >';
					}
					elseif ($bull->statut > $bull->BULL_DEPART)
						print $temp.'';
					print "</td>";	
								
					
					print "</tr>";
					$var=!$var;
					$i++;
				} // Foreach
			}
			print '<tr><td colspan=2></td><td  align="center"> ';
					print '</td></tr>';
					print '<tr><td width="100%" colspan=4><i>';
					Print $langs->trans("ObsMatMad");					
					print '</i></td></tr><tr><td colspan=3 >';
			if ($faire == '')				{	
					print $bull->obs_matmad;
				}
				else{	
					print '<textarea cols=50 rows="'.ROWS_3.'" wrap="soft" name="LocMatObs" '.$event_filtre_car_saisie.' >';
					print $bull->obs_matmad.'</textarea>';
				}
				
			print "</td></tr>\n";
			print "</tbody></table id=Niv2_MatMad>";
			
			//print '</form>';
		print '</td ></tr></tbody></table id=Niv1_MatMad>';
		
	}//AfficheSaisieLocmatMad
	/*
	*	Affiche ou permet la saisie des rando au d�part ou au retour
	*	faire	Init (pour saisir la colonne init et afficher la colonne retour uniquement dans bull->BULL_ENCOURS
	*			Ret (pour saisir la colonne retour et afficher  la colonneinit
	*			'' pour un affichage des deux colonnes
	*/

	function AfficheSaisieLocRando1($faire = '')
	{
		global $langs, $event_filtre_car_saisie, $bull, $action;
		global 		$UPD_RANDO, $UPD_RANDO_RET,$MOD_RANDO, $MOD_RANDO_RET, $RETGENRAND;
		global		$bc;	
		$var=true;		
		
		$w=new CglLocation ($this->db);
		
		$wf = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		if ($action == $UPD_RANDO) $actionfuture = '';
		elseif ($action == $MOD_RANDO) $actionfuture = $UPD_RANDO;
		elseif ($action == $UPD_RANDO_RET) $actionfuture = '';
		elseif ($action == $MOD_RANDO_RET) $actionfuture = $UPD_RANDO_RET;
		$duree='';
		// table Randonnees			
		$url = $_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&action='.$RETGENRAND;
		if ($bull->statut > $bull->BULL_DEPART and $bull->regle <$bull->BULL_ARCHIVE) $this->AfficheParagrapheBouton("TitrRando", 2, "TitreRetGen",$url,  "AidBtRetGenRand");
		else $wfctcomm->AfficheParagraphe("TitrRando", 3 );
			print '<tr><td>';
		/*print '<form name="obj_update_'.$i.'" action="'.$_SERVER['PHP_SELF'].'?id_contrat='.$bull->id.'#AncreLstDetail"  method="POST">'."\n";
		print '<input type="hidden" name="token" value="'.newtoken().'">'."\n";
		print '<input type="hidden" name="action" value="'.$actionfuture.'">';*/
		
		print '<table  id=Niv1_Rando border=1  width=100% ><tbody><tr><td width="100%">';
		print '<table class="liste" id=Niv2_Rando width=100%><tbody>';

			print '<tr><td>';		
			// affiche la barre grise des champs affiche
			print '<tr  class="liste_titre">';
			print '<td colspan=1 >'.$langs->trans("Rando").'</td>';
			print '<td  align="center">'.$langs->trans("Qte").'</td>';
			if ($bull->statut > $bull->BULL_DEPART) 
				print '<td  align="center">'.$langs->trans("Retour").'</td>';
			print "</tr>";
			$old_fk_service='';
			print "<tr><td>&nbsp;</td></tr>\n";
			if (!empty($bull->lines_rando)) 
			{				
				foreach ($bull->lines_rando as $lineRando)
				{
					if (!isset($old_fk_service)) $old_fk_service = $lineRando->fk_service;
					if ($lineRando->fk_service != $old_fk_service)
					{
						print "<tr $bc[$var]>";
						print '<td>----</td><td></td><td></td>';
						print '</tr>';
						//print '<tr><td>&nbsp;</td></tr>';
						$var=!$var;
						$old_fk_service=$lineRando->fk_service;
					}
					print "<tr $bc[$var]>";
					print "<td colspan=1>".$lineRando->lb_rando."</td>";			
					print '<td align="center" class="nowrap">';
					if ($lineRando->qte == 0) $temp = ''; else $temp = $lineRando->qte;
					if (($action ==$MOD_RANDO) and ($faire == 'Init' or $faire == 'Ret'))
						print '<input class="flat" value="'.$temp.'" size="3px" type="int" name="rando['.$lineRando->id.']" >';
					else print $temp;
					print "</td>";			
					print '<td align="center" class="nowrap">';
					if ($lineRando->qteret == 0) $temp = ''; else $temp = $lineRando->qteret;				
					if ($faire == 'Ret' and $bull->statut > $bull->BULL_DEPART) {
						print '<input class="flat" value="'.$temp.'" size="3px" type="int" name="retrando['.$lineRando->id.']" >';
					}
					elseif ($bull->statut > $bull->BULL_DEPART)
						print $temp.'';
					print "</td>";		
							
					print "</tr>";
					$var=!$var;
					$i++;
				}			//foreach
			}
			
			//print '<tr><td colspan=2></td><td   align="center"> ';
			print '<tr>';
			if ($faire == '')		{			
					print '<td width="100%" colspan=3><i>';
					Print $langs->trans("ObsRand");					
					print '</i></td></tr><tr><td colspan=3>';
					print $bull->obs_rando;
				}
				else{
					print '<td width="100%" colspan=3><i>';
					Print $langs->trans("ObsRand");					
					print '</i></td></tr><tr><td colspan=3 >';
					print '<textarea cols=50 rows="'.ROWS_3.'" wrap="soft" name="LocRandoObs" '.$event_filtre_car_saisie.' >';
					print $bull->obs_rando.'</textarea>';
				}
			print "</td></tr>\n";
			print "</tbody></table id=Niv2_Rando>";
		print '</td ></tr></tbody></table id=Niv1_Rando>';
			//print '</form>';
				
				

		unset ($w);	
	
	} //AfficheSaisieLocRando
	function AfficheBtNellLocDet()
	{
		global $CRE_LIGLOC, $langs, $bull, $event_filtre_car_saisie;
		
		print '<input type="hidden" name="action" value="'.$CRE_LIGLOC.'">';		
	}//AfficheBtNellLocDet	
	function SaisieLigne()
	{	
		global 	$ACT_ANNULPART, $event_filtre_car_saisie;
		global  $ACT_SEL_ACTPART,   $LOC_DUP, $CRE_LIGLOC;
		global $fk_service, $materiel, $fk_fournisseur, $marque, $identmat, $nomprenom, $taille, $dt_dateretrait, $st_dateretrait,$dt_datedepose,$st_datedepose, $duree, $observation;
		
		global $PartNom, $PartPrenom, $PartCiv, $PartAdresse, $PartDateNaissance, $PartTaille, $PartPoids,$PartENF, $PartTel, $PartMail, $PartAge, $PartDateInfo;
		global $PU, $PT, $Rem , $ActPartQte, $FacTotal, $ActPartObs, $ActPartIdRdv, $TiersOrig;
		global $id_client, $action, $langs;
		global $id_act, $id_part, $id_rang , $id_contrat, $bull, $id_contratdet, $lineajout;
//var_dump($bull);	


		print '<a id="AncreSaisieLocation" name="AncreSaisieLocation"></a>';

		
		// RECUPE DONNEES
		$w1 = new CglFonctionDolibarr($this->db);
		$w=new CglLocation ($this->db);
/*		if (!isset ($action) or empty($action) or $action == $ENR_LOCDET ) // mise à vide des champs de liens 
		{	
			$id_contratdet = 0;  $id_part = 0;
			$nomprenom = ''; $PartPrenom = ''; $PartIdCivilite = '';  $PartAdresse = ''; $PartDateNaissance = ''; $PartTaille = ''; $PartPoids = '';
			$PartENF = '';  $PartTel = '';  $PartMail = ''; $PU = '';  $PT = '';  $Rem = ''; $ActPartObs = ''; 
			$ActPartQte = ''; $ActPartIdRdv = ''; $TiersOrig = ''; 
		}	
*/
		if ($action == $ACT_SEL_ACTPART  or  $action == $LOC_DUP or !empty(GETPOST("BtSelLocDet", 'alpha')))
		{
			unset ($lineajout);
			$lineajout=$bull->RechercheLign($id_contratdet); // récupére aussi id_part et id_act
			if ($id_act <> $lineajout->id_act) $id_act = $lineajout->id_act;
			$id_part = $lineajout->id_part;
		}
		else	{ 
			$lineajout = new BulletinLigne($this->db, $bull->type) ;
			$lineajout->dateretrait	= $bull->calcul_date_defaut_location_retrait();	
			$lineajout->datedepose	= $bull->calcul_date_defaut_location_depose();
			$lineajout->lieuretrait	= $bull->calcul_lieu_defaut_location_retrait();	
			$lineajout->lieudepose	= $bull->calcul_lieu_defaut_location_depose();
		}
				
		if ( $action == $ACT_SEL_ACTPART or !empty(GETPOST("BtSelLocDet", 'alpha')) ) 
		{	
			if (!empty($id_contratdet)) $lineajout=$bull->RechercheLign($id_contratdet);
			//if (!empty($lineajout->id)) $lineajout->extract_spec_participation();
			if (empty($id_contrat)) $id_contrat		= $lineajout->fk_bull;
			$duree='';
		}		
		$line = $lineajout;
		
		// on r�cup�re ce qu'il y a dans les variables GETPOST si elles ne sont pas vides
		if (!empty($nomprenom))			$line->NomPrenom			=$nomprenom;
		//else if (empty($line->NomPrenom)) $line->NomPrenom	= $bull->tiersNom;

		if (!empty($PartTaille))		$line->PartTaille		=$PartTaille ;
		if (!empty($fk_service))	$line->fk_service	=$fk_service ;
		if (!empty($fk_fournisseur))	$line->fk_fournisseur	=$fk_fournisseur ;
		
		if (!empty($materiel))		$line->materiel		=$materiel ;
		if (!empty($marque))		$line->marque		=$marque ;
		if (!empty($identmat))		$line->identmat		=$identmat ;
		if (!empty($dateretrait))	$line->dateretrait	=$dt_dateretrait ;
		if (!empty($datedepose))	$line->datedepose	=$dt_datedepose ;
		if (!empty($observation))	$line->observation	=$observation ;
		if (!empty($duree))			$line->duree		=$duree;
		if (!empty($PU))			$line->pu			= $PU ;
		if (!empty($Rem))			$line->rem			= $Rem ;
		if (!empty($ActPartQte))		$line->qte		= $ActPartQte ;
		// Fin r�cup donn�es
				print '<form method="POST" name="SelectActivite" action="'.$_SERVER["PHP_SELF"].'">';
			print '<table  id=Niv1SaisPart_FormGen  style="border:1px solid black;font-size:13px;background-color:'.$langs->trans("ClPaveSaisie").';"  width="100%"><tbody >';				
				print '<input type="hidden" name="token" value="'.newToken().'">';					
				print '<input type="hidden" name="id_contrat" value="'.$id_contrat.'">';
			//	print '<input type="hidden" name="action" value="'.$ENR_LOCDET.'">';
				print '<input type="hidden" name="fk_service" value="'.$fk_service.'">';
				print '<input type="hidden" name="fk_fournisseur" value="'.$fk_fournisseur.'">';					
			//	print '<input type="hidden" name="materiel" value="'.$materiel.'">';
			//	print '<input type="hidden" name="marque" value="'.$marque.'">';
				//print '<input type="hidden" name="refvelo" value="'.$refvelo.'">';
				//print '<input type="hidden" name="nomprenom" value="'.$nomprenom.'">';	
				//print '<input type="hidden" name="PartTaille" value="'.$PartTaille.'">';
			//	print '<input type="hidden" name="dateretrait" value="'.$dateretrait.'">';		
			//	print '<input type="hidden" name="datedepose" value="'.$datedepose.'">';		
				//print '<input type="hidden" name="observation" value="'.$observation.'">';	
				//print '<input type="hidden" name="duree" value="'.$duree.'">';			
				//print '<input type="hidden" name="PU" value="'.$PU.'">';		
				//print '<input type="hidden" name="PT" value="'.$PT.'">';		
				//print '<input type="hidden" name="Rem" value="'.$Rem.'">';	
		//		print '<input type="hidden" name="Qte" value="'.$Qte.'">';	
				print '<tr><td>';
			$url = DOL_MAIN_URL_ROOT.'/custom/cglinscription/ajaxconflitvelo.php';

print  '<script>
	let tabAncColor = [];
	$(document).ready(function() {
	  var autoselect = 0;
	  var options = [];
	  $("#identmat").change(function(event) {
		var idElemVelo = event.currentTarget.id;
		var elemMateriel = document.getElementById ("selectfk_service");
		if (elemMateriel.length == 0)
		  var elemMateriel = document.getElementById ("fk_service");
		elemVelo = document.	getElementById (idElemVelo);
		var argRefMateriel=elemMateriel.value;
		var argRefVelo=elemVelo.value;
		var argidBullDet="'.GETPOST('id_contratdet','int').'";		

		var obj = [{"method":"get",
					"url":"'.$url.'",
					"htmlname":".idElemVelo.",
					"params":{"refVelo":argRefVelo, 
								  "RefMat":argRefMateriel, 
								  "idbulldet":argidBullDet,
								}}];

		$.each(obj, function(key,values) {
		  if (values.method.length) {
			runJsCodeForEventarg_NumVelo(values, idElemVelo);
		  }
		});
	  }); 

	  $("#identmat").blur(function(event) {
		var iddiv= "divInfoConflitVelo";
		let idElemVelo = event.currentTarget.id;
		if (tabAncColor[event.currentTarget.id] == "red") {
		  document.getElementById (idElemVelo).style.color="red";
		  document.getElementById (idElemVelo).style.fontWeight="bold";
		  document.getElementById(iddiv).style.display = "block";
		tabAncColor[event.currentTarget.id] = "";
		}
		else if (event.currentTarget.value.length != 3)  {
		  document.getElementById (event.currentTarget.id).style.background="red";
		}
	  });

	  $("#identmat").click(function() {
		var iddiv= "divInfoConflitVelo";
		document.getElementById (event.currentTarget.id).style.color="black";
		document.getElementById (event.currentTarget.id).style.fontWeight="normal";
		document.getElementById(iddiv).style.display="none";	
		document.getElementById (event.currentTarget.id).style.background="white";							
		tabAncColor[event.currentTarget.id] = document.getElementById (event.currentTarget.id).style.color;

	  });
	});
			
		function runJsCodeForEventarg_NumVelo(obj, idElemVelo) {
			let method = obj.method;
			let url = obj.url;		
			let id = obj.id;
			let htmlname = obj.htmlname;
			let response = "";
			
			$.getJSON("'.$url.'",
				obj.params,
				function(response) {
					if (response.length > 0) {
							document.getElementById (idElemVelo).style.color="red";
							document.getElementById (idElemVelo).style.fontWeight="bold";
							document.getElementById("divInfoConflitVelo").style.display = "block";
							document.getElementById ("divInfoConflitVelo").childNodes[0].title = "'.$langs->trans("VelosEnConflit").'".concat(response);	
				}
						else {
							document.getElementById (idElemVelo).style.color="black";
							document.getElementById (idElemVelo).style.fontWeight="normal";
							document.getElementById("divInfoConflitVelo").style.display="none";								
							document.getElementById ("divInfoConflitVelo").childNodes[0].title = "";	

						}
					},
					function(error) {								
						  console.log(error.status);
						  console.log(error.statusText);
						  console.log(error.headers);
					});
		}
		
		function VerifIdentmat (o) {
			if (o.value.length != 3) {
				 o.style.background="red";
				 document.getElementById("divInfoConflitVelo").style.display="none";							

				alert ("ATTENTION - 3 caracteres obligatoires");
			}
			else  o.style.background="white";
		}
			
		function EffaceChamp(o) {
			o.value = "";
			o.style.color = "black";
		}

		function RemetVide(o) {
			if (o.value == "") {
				o.style.color = "grey";
				o.value = o.defaultValue;
			}
		}
		
		</script>
		<style>
		#identMat {
			background-color: white;
			}
		.hidden {display:none}
		.FlexIdentVelo {
            display: flex;
          }
		.FlexIdentVeloItem {
            display: flex;
          }
		</style>
		';

//					print '<tr><td width="30%">';
				/* PAVE A - AFFICHER MATERIEL */
			print '<table class="liste" id=Niv2_Activite><tbody>';
					if (!empty($line->id))
 						print '<input type="hidden" name ="idbulldet" id="idbulldet" value="'.$line->id.'">';
					print '<tr class="liste_titre" >';
						print '<td colspan=5>';
						print $langs->trans('Materiel').' - '.$langs->trans('Location');			
					print '</td></tr>';
					//print '<tr bgcolor="white">';
					print '<tr>';
						print '<td>'.$langs->trans('Service').'</td><td colspan=3 >';
						if (!empty($id_contratdet) and !empty($bull->fk_facture)){
							print $line->refservice;
							print '<input type="hidden" name ="fk_service" id="fk_service" value="'.$line->fk_service.'">';
						}
						else print $w->select_service($line->fk_service,'fk_service');
					print '</td></tr><tr>';
						//print '<td>'.$langs->trans('Materiel').'</td><td colspan=3 ><input class="flat"  value="'.$line->materiel.'" type="text" name="materiel" ></td>';
					//print '</tr><tr>';
					// Non utilisé dons mis en attente 
					/* print '<td>'.$langs->trans('Fournisseur').'</td><td colspan=5>';
						print $w->select_fournisseur($line->fk_fournisseur,'fk_fournisseur','',1, 1, 0, '', '', 0, 0);
					//print '</td></tr><tr>';	
					//	print '<td>'.$langs->trans('Marque').'</td><td><input class="flat"  value="'.$line->marque.'" type="text" name="marque" ></td>';
					print '</tr><tr>';
					*/
					print '<td>';					
					if ($line->fl_conflitIdentmat == true) {
							$styleconflit="style='color:red;font-weight:bold;'";
							$styleinfoadmin = 'style="display:block;"';
					}
					else {
						$styleconflit = "style=''";
							$styleinfoadmin = 'style="display:none;"';
					}		

					print $langs->trans('RefVelo').'</td><td colspan=3 >';
					print '<div class="FlexIdentVelo">';
					print '<div class="FlexIdentVeloItem" style="width:10%" ><input class="flat"  value="'.$line->identmat;
						print '" type="text" id="identmat" name="identmat"  '.$styleconflit;
						print '" onchange="VerifIdentmat(this)"></div>';
					print  '<div class="FlexIdentVeloItem" id="divInfoConflitVelo" '.$styleinfoadmin.' >';
						print info_admin($langs->trans("VelosEnConflit", $line->lstCntConflit),1);
						print '&nbsp</div>';

						print '<div class="FlexIdentVeloItem" >&nbsp-&nbsp'.'<input class="flat"  value="'.$line->marque.'" type="text" id="marque" name="marque" ></div>';
					print '</div>'; //fin de FlexIdentVelo
					print '</td>';
						print '</tr><tr>';		
				
					print '</tr><tr>';
					$ret =  0;		
					if (!is_null($line->dateretrait) and isset($line->dateretrait))			
						$temp = $line->dateretrait;
					elseif (!isset($line->id) and empty( $line->id))  		{
						if (!is_null($bull->locdateretrait)) $temp=$bull->locdateretrait;
						else $temp =DateTime::format('Y-m-d H:i');
					}
					
//					else $ret = -1;
					print '<td size="1"><i>'.$langs->trans('DateDeb').'</i></td>';
					print '<td width="37%">';
					//if ($ret == -1) print $w1->select_date(-1,'dateretrait',1,1,0,"add",1,1,0,0,'',6,22,30);
					//else  print $w1->select_date($temp->format('Y-m-d H:i'),'dateretrait',1,1,0,"add",1,1,0,0,'',6,22,30);
					print $temp;
					print '</td>';  
					$formaide = new Form ($this->db);
					print '<td size="1"><i>'.$langs->trans('LieuRetrait').'</i></td>';					
					//print '<td width="53%"><input class="flat" size="70" value="'.$line->lieuretrait.'" type="text" name="lieuretrait">'; 
					print '<td>'.$line->lieuretrait;
					print info_admin($langs->trans("LBLieuRetrait"),1);
					print '</td>';
					print '</tr><tr>';
					$ret = 0;
					if ((!is_null($line->datedepose) and isset($line->datedepose)))				
						$temp = $line->datedepose;
					elseif (!isset($line->id) and empty( $line->id)){
						if (!is_null($bull->locdatedepose)) $temp=$bull->locdatedepose;
						else $temp =DateTime::format('Y-m-d H:i');
					}
					else $ret = -1;
					print '<td size="1"><i>'.$langs->trans('DateFin').'</i></td>';
					print '<td>';
					//if ($ret == -1) print $w1->select_date(-1,'datedepose',1,1,0,"add",1,1,0,0,'',6,22,30);
					//else print $w1->select_date($temp->format('Y-m-d H:i'),'datedepose',1,1,0,"add",1,1,0,0,'',6,22,30);

					print $temp;
					print '</td>';
					print '<td size="1"><i>'.$langs->trans('LieuDepose').'</i></td>';
					//print '<td width="45%"><input class="flat" size="70" value="'.$line->lieudepose.'" type="text" name="lieudepose">'; 
					print '<td>'.$line->lieudepose;
					print info_admin($langs->trans("LBLieuDepose"),1);
					unset ($formaide);
					print '</td>';
					print '</tr>';										
				print "</tbody></table id=Niv2_Activite>";	
//L3 Col 2
//				print '</td><td width="50%">';
			print '</td><td>';
		/* Modifier PARTICIPANT + PRIX*/	
					print '<table id=Niv2_Participant class="liste"><tbody>';
					$moreforfilter='';
					// PAvé participation
					$moreforfilter.=$langs->trans('ParticipantLoc');			
						//Nom Prenom
						print '<tr class="liste_titre" >';
						print '<td class="liste_titre"  colspan=4>';
							print $moreforfilter;			
						print '</td></tr>';	
							print '<tr>';
							$NomPrenom = $line->NomPrenom;
							if (empty($line->NomPrenom) or $line->NomPrenom == $bull->tiersNom) {
								$style = 'style="color:grey"';
								if (empty($NomPrenom)) 	{
									$NomPrenom = $bull->tiersNom;
								}
//								$fctclick = "EffaceChamp(this)";	$fctchange = "RemetVide(this)";
								$fctclick = "";	$fctchange = "";
								$fctchange = "RemetVide(this);";
							}
							else {
								$style ='';
							}
							print '<td width="20%" >'.$langs->trans("NomPrenomLoc").'</td>';
							print '<td width="80%" colspan=2 ><input class="flat"  value="'.$NomPrenom.'" type="text" name="nomprenom" '.$style.' onclick="'.$fctclick.'" onchange="'.$fctchange.' size="60%" ></td>';
						print '</tr>';	
						//Taille
						print '<tr>';
							print '<td>'.'  '.$langs->trans("Taille").'</td>';
							print '<td colspan=2>';							
						print '<input class="flat" size="20" type="text" value="'.$line->PartTaille.'"   name="PartTaille" >';
							print '</td>';
						print '</tr>';

						print '<tr><td>&nbsp;</td></tr>';	
						$moreforfilter='';							
						// Pavé Facturation
						if ($bull->facturable) 	$moreforfilter.=$langs->trans('Facturation');
						else 	$moreforfilter.=$langs->trans('Plannification');
						print '<tr class="liste_titre" >';
							print '<td class="liste_titre" width="100%" colspan=3>';
							print $moreforfilter;
							print '</tr><tr>';
							// Quantité
							print '<td>'.'  '.$langs->trans("Qte").'</td>';
							if (empty($line->qte) or $line->qte == 0) $line->qte = 1;
							//if ($action == $CRE_LIGLOC)
							if (!empty(GETPOST("BtNvLigne", 'alpha')))
								print '<td><input class="flat" value="'.$line->qte.'" type="text" name="ActPartQte"  id="ActPartQte" onchange="CalculPUbyQte(this);"></td>';
							// DUREE
							print '</td></tr><tr>';			
							//print '<td>'.$langs->trans('Durée').'</td><td><input class="flat" size="5" value="'.$line->duree.'" type="text" name="duree" id="duree"  onchange="CalculPUbyDuree(this);" >';
							if ($line->duree == 0.5) $wduree = 0;
							else $wduree =  $line->duree;
							print '<td>'.$langs->trans('Duree').'</td><td>'.$w->select_duree($wduree,'duree');
							print '<input  class="flat" size="5" value="'.$line->duree.'" type="text" name="saisieduree" id="saisieduree"  style="visibility:hidden" onchange="CalculPUbySaisDuree(this);" >';
							print '&nbspjournee(s)</td>';
			
						if ($line->PUjour) $visibilite = '';
							else 		$visibilite = 'style="visibility:hidden"';	
						print '<td><span name="TIPUJ" id="TIPUJ" '. $visibilite.'>'.$langs->trans("TIPrixJour");						 
						print info_admin($langs->trans("LBPrixjournee"),1);
						print '</span></td>';
						print '</tr><tr>';
						if ($bull->facturable) {
							print '<td>'.'  '.$langs->trans("PuTtc").'</td>';
							if ((!isset($line->pu))  and  $id_part > 0  and ($line->id))	{
								//$line->pu = $line->CherchePu();
							}								
							
							// Prix total pour enregistrement
							print '<input  type="hidden" size=10 class="flat" value="';
							print($line->pu)?$line->pu:'';
							print '" type="text" name="PU" id="PU">';
							// Prix total affiché
							print '<td><span  name="PUAff" id="PUAff">';
							print ($line->pu)?$line->pu:'';
							print '</span>';
							// Mention Euros
							if ($line->pu)  $visibilite1 = '';
							else 		$visibilite1 = 'style="visibility:hidden"';
							print '&nbsp;&nbsp;<span name="AffEuros" id="AffEuros" '.$visibilite1.' > euros</span>';
							//if ($bull->type_session_cgl != 1)
								//print '<img class="hideonsmartphone" border="0" title="Obligatoire" alt="Obligatoire" src="'.DOL_URL_ROOT.'/theme/eldy/img/star.png">';
							print '</td>';
							
						// Prix journée
							print '<td><input  class="flat"  name="journee"  id="journee" value="'.price2num($line->PUjour).'" '.$visibilite .' onchange="CalculPU(1);" ><td>';
							print '</tr><tr>';	
							

							print '<td></td>';
							if ($line->PUjoursup) $visibilite = '';
								else 		$visibilite = 'style="visibility:hidden"'; // necessaire pour PUjoursup aussi
							print '<td><span name="TIPUDJ" id="TIPUDJ" '. $visibilite.'>'.$langs->trans("TIPrixDJour");	
							print info_admin($langs->trans("LBPrixjournee"),1);	
							print '</span></td>';
							
							// Prix demi-journée ou journée supplémentaire
							$qte = $line->qte;
							if ($qte ==0) $qte=1;
							$rem = $line->remise_percent;
							$pu=$line->pu;
							$pt=$line->calulPtAct($bull->type_session_cgl,$pu,$qte,$rem);

							print '<td><input class="flat" name="joursup"  id="joursup"  value="'.price2num($line->PUjoursup).'"  '.$visibilite .' onchange="CalculPU(1);">';					 
							print '<td>';
							print '</tr>';
							
														/* REMISE
							print '<td>'.'  '.$langs->trans("RemPourc").'</td>';
							print '<td></td>';
							print '<td><input class="flat" value="'.$line->remise_percent.'" type="text" name="Rem"  id="Rem" onchange="CalculPU(1);"  type="hidden" ></td>';
							print '<td>';	
							*/
						if (!empty($line->remise_percent)) {
								print '<tr>';
								print '<input type="hidden" name="Rem" value="'.$line->remise_percent.'">';	
								print '<input type="hidden" name="ActPartRem" value="'.$line->remise_percent.'">';	
								print '<td>'.'  '.$langs->trans("RemPourc").'</td><td>';
								print $line->remise_percent;
								print '&nbsp;%  soit total de ';
								print (100-$line->remise_percent)* $line->pu/100;
								print '&nbsp;&nbsp;euros';
								print '</td>';
								print '</tr>';
							}
						} // facturable
						//if ($pu)	print '<td>Prix </td><td>'.$pt.'&nbsp;euros</td>';
						//else	print '<td>Prix </td><td></td>';
						//print '</tr><tr>';
					print '</tbody></table id=Niv2_Participant>';
//				print '</td><td width="30%">';
				print '</td><td>';
			/* OBSERVATION */
//L3 Col 3		
					print '<table class="liste"  id=Niv2_Facturation><tbody>';
						$moreforfilter=$langs->trans('Observation');			
						print '<tr class="liste_titre" >';
							print '<td class="liste_titre" width="100%" colspan=2>';
							print $moreforfilter;			
						print '</tr><tr>';							
							print '<td align="left"><textarea cols="40" rows="'.ROWS_8.'" wrap="soft" name="observation" '.$event_filtre_car_saisie.' >';
							print $line->observation.'</textarea>';		
						print '</tr>';
						
				print '<tr><td colspan=2>';		
				/* Enregistrement ou Ajout*/
					print '<table width="100%" id=Niv3_Bouton><tbody>';
						print '<tr align=center>';
							if ($bull->regle < $bull->BULL_ARCHIVE)
							{
								//if ($id_contratdet)
								//{
									print '<input type="hidden" name="id_contratdet" value="'.$id_contratdet.'">';
									print '<td><input class="button" name="BtEnrLigne" type="submit" size="5" value="Enregistrer" align="center"></td>';	
								//}
								//else
								//{
								//	print '<td><input class="button" name="BtAjoutLigne" type="submit" size="5" value="Ajouter" align="center"></td>';	
								//}
								//print '<td><input class="button" action="ACT_ANNULPART" type="submit" size="5" value="Annuler" align="center"></td>';	
							}

						print '</tr>';
			print "</tbody></table id=Niv3_Bouton>";
						
						
						print '</td></tr>';
						
						
						
					print '<tbody></table id=Niv2_Facturation></form>';
				print '</td></tr>';
//L4 Col 1&2&3&4
		
			print "</td>";
			print '</tr>';			
			print '</tbody></table id=Niv1SaisPart_FormGen>';
		//}
//CCA - bug 269
					 // mettre en gras
					 // ajouter une infobulle avec un texte 'ce vélo est réservé dans un autre contrat, voir écran "conflits de location'
	} // SaisieLigne

	function AfficheCaution_old($faire = '')
	{		
		global $langs, $bull, $event_filtre_car_saisie;		
		global $action, $SAIS_CAUTACC, $UPD_CAUTACC,$UPD_MATMAD,  $CAL_ACPT ;	

		$w=new CglLocation ($this->db);	
		$wf = new form ($this->db);
		$wfc = new FormCglCommun ($this->db);		
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$wdet = new BulletinLigne($this->db, $bull->type);
		// table remise générale
			$wfctcomm->AfficheParagraphe("CautAcompt", 2	);
			print '<tr><td>';
		print '<table id="Niv2_CautionAccompte"  width=100% border =1><tbody><tr><td>';
	
		print '<table id="Niv3_CautionAccompte" width=100% ><tbody><tr>';

		// titre Caution
		
		// montant caution	
		print '<td width=70% colspan=1><i>'.$langs->trans('Mttcautionexigee').'</i></td>';
		print '<td>';
/*		if ($action <> $SAIS_CAUTACC) 
			print $bull->mttcaution.'</td>';	
		else	*/		
			print '<input class="flat" type="text" name="mttcaution"  value="'.$bull->mttcaution.'"></td>';
		if ($bull->statut >= $bull->BULL_VAL) {
			// Pavé supprimé pour 2018
			print '</tr>';
			if ("TOTO" == "TITI") {


				// top caution re�ue
					print '<tr>';
					print '<td colspan=1><i>'.$langs->trans('LbTopCaution').'</i></td><td>';
					if ($action == $SAIS_CAUTACC)
						print $wf->selectyesno( 'topcautionrecue', $bull->top_caution,1, false);
					else
						print $wf->selectyesno( 'topcautionrecue', $bull->top_caution,1, true);
					print '</td><tr>';	
				
					
					// mode de paiement de la caution	
					print '<tr>';
					print '<td width=70% colspan><i>'.$langs->trans("ModCaution").'</i></td>';
					print '<td colspan>';
	//				if ($action == $SAIS_CAUTACC) 	
								$moreforfilter.=$wf->select_types_paiements($bull->fk_modcaution,"modcaution",'',0, 0, 0,0);
	//				else print $bull->lb_modcaution;
					print '</td>';
					
					print '<tr>';
					if ($bull->statut > $bull->BULL_ENCOURS) {
						print '<td width=70% colspan><i>'.$langs->trans("CautRendu").'</i></td>';			
						print '<td  colspan>';
	//					if ($action == $SAIS_CAUTACC)
						if ($bull->top_caution)
							   print $wf->selectyesno( 'retcaution', $bull->ret_caution,1, false);
	//					else	 print $wf->selectyesno( 'retcaution', $bull->ret_caution,1, true);
						print '</td>'; 
					}	
					
					print '<tr><td>&nbsp;</td></tr>';
					
					// top document recu
					
					// top caution re�ue
					print '<tr>';
					print '<td colspan=1><i>'.$langs->trans('LbTopdocrecu').'</i></td><td>';
	//				if ($action == $SAIS_CAUTACC)
						print $wf->selectyesno( 'topdocrecu', $bull->top_doc,1, false);
	/*				else
						print $wf->selectyesno( 'topdocrecu', $bull->top_doc,1, true);
	*/
					print '</td><tr>';	
					// type docume nt
					print '<tr>';
					print '<td width=70% colspan=1><i>'.$langs->trans("DocCaution").'</i></td>';
					if (empty($bull->fk_caution)) $temp = 'neant';
					else $temp = $bull->lb_caution;
					print '<td colspan=1>';
	//				if ($action == $SAIS_CAUTACC) 	 
							print $w->select_caution($bull->fk_caution, 'caution');			
	//				else print $temp;
					print '</td>';
					// top document rendu
					print '</tr>';
						
					print '<tr>';
					if ($bull->statut > $bull->BULL_ENCOURS) {
						print '<td width=70% colspan=1><i>'.$langs->trans("DocRendu").'</i></td>';			
						print '<td  colspan=1>';
	//					if ($action == $SAIS_CAUTACC)
							   print $wf->selectyesno( 'retdoccaution', $bull->ret_doc,1, false);
	/*					else	 print $wf->selectyesno( 'retdoccaution', $bull->ret_doc,1, true);
	*/
						print '</td>'; 
					}	
					
					print '</tr>';
			} // Fin pavé supprimé 2018
			print '<tr>';
			
			print '<td  colspan=2><i>'.$langs->trans("ObsCaution").'</i></td>';
			print '</tr>';
			print '<tr><td align="left" colspan=2>';
//				if ($action == $SAIS_CAUTACC) {
				print '<textarea  rows="'.ROWS_2.'" cols="80"  wrap="soft" name="ObsCaution" '.$event_filtre_car_saisie.' >';
				print $bull->obscaution.'</textarea>';		
//				}
//				else print $bull->obscaution;
			print '</td>';	
		}
		print '</tr><tr><td colspan=1></td><td align=center>';
	
		print '<input type="hidden" name="action" value="'.$UPD_MATMAD.'">';
		print '<input class="button"  name="BtEnrCaution" type="submit" value="'.$langs->trans("BtEnregistrer").'"></td>';		
		print '</td></tr>';
		print '</td></tr></tbody></table id="Niv3_CautionAccompte">';
		//print '</form>';

		// fin ligne rendez-vous et 
		//print '</td></tr>';
		unset ($w);
		unset ($wdet);
		unset ($wf);
		
		print '</td></tr></tbody></table id="Niv2_CautionAccompte">';
		print '</td></tr>';	
	}//AfficheCaution	

	
		
	/*
	*	param Titre			string		titre du paragraphe
	*	param colspan		int			Nombre de colonne � joindre
	*	param Libboutton	string		nom du bouton
	* 	param url			string		url 
	*	param alt			string		Aide
	*/
	function AfficheParagrapheBouton($Titre, $colspan, $Libboutton, $url, $alt)
	{
		global $bull, $langs;
		
		print '<td width=100%><table witdh=100%><tbody><tr>';
		if ($Titre) 
		{
			print '<td class="nobordernopadding hideonsmartphone" width=65% align="left" valign="middle" ';
			if ($colspan > 1) print 'colspan='.$colspan;
			print '>'.img_picto('','title.png', '', 0);
			print '<span style="font-size:12px; font-weight:bold">'.$langs->trans($Titre).'</span></td>';
		}
		/*
		print '<td width=35%>';
		print $langs->trans($Libboutton)."     ";		
	
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id_contrat='.$bull->id.'" title="' . $langs->trans("CntLbRetour") . '">' . img_edit($langs->trans($alt)) . '</a></div>';
			
		//print '<a href="'.$url.'">'.img_edit($langs->trans($alt)).'</a>';
		print '</td>';*/
		print '</tr></table></td>';
	} //AfficheParagrapheRetour
	function teteTable()
	{
		print '<table><tr>';
	}/*		teteTable */
	function finTable()
	{
		print '</tr></table>';
	}/*		finTable */
 
 }//Class
 
?>