<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022
 *					 - Remplacer method="GET" par method="POST"
 *					 - Migration Dolibarr V15
 * Version CAV - 2.8.3 - printemps 2023
 *		- suppression des guillemets dans les textarea
 *
 * *					
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
 *  \file       custum/cglinscription/class/html.formreservation.class.php
 *  \ingroup    cglinscription
 *  \brief      Interface utilisateur pour la saisie des réservations
 */

 
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once("../agefodd/class/html.formagefodd.class.php");
require_once("../agefodd/class/agsession.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
require_once('../cglavt/class/html.cglFctCommune.class.php');

 class FormCglReservation extends Form
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


	function Activite_Participant()
	{
		global  $conf, $bc, $langs,  $event_filtre_car_saisie ;
		global $id_client, $action, $bull;
		global $tbNomPrenom, $tbobservation, $tbprix, $tbqte;

		
		
		global  $id_resa, $bull, $id_resadet;
	
		$wf = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$wfctcomm->AfficheParagraphe("TitrInesAct", 3);
			
		$numline= count($bull->lines);
		if ($numline) $id_resadet = $bull->lines[0]->id;
		$w=new CglInscription ($this->db);
		/* TABLEAU DES ACTIVITES - PATICIPANTS */
		print '<form method="POST" name="Participations" action="#AncreLstDetail">';
		
		print '<input type="hidden" name="id_resa" value="'.$id_resa.'">';	
		print '<input type="hidden" name="type" value="Resa">';	
		print '<input type="hidden" name="id_resadet" value="'.$bull->lines[0]->id.'">';
		print '<input type="hidden" name="action" value="Participations">';
		print '<input type="hidden" name="" value="#AncreLstDetail">';
		print '<input type="hidden" name="token" value="'.newtoken().'">';
		print '<table   border="1" id="Niv1AffichActPart" width="100%"><tbody>';
	

		print '<tr><td width="100%" >';
		print '<div class="tabBar" style="background-color:'.$langs->trans("ClPaveSaisie").'">';
			print '<table class="liste" id="Niv2_ListeParticip" width="100%" style="background-color:'.$langs->trans("ClPaveSaisie").'"><tbody>';
			// affiche la barre grise des champs affichés
			
			$titre0 = $langs->trans("Nom_Prenom");
			print_liste_field_titre($titre0,'','','','','','','');			
			$titre1 = $langs->trans("observation").info_admin($langs->trans("DefResObservation"),1);	
			print_liste_field_titre($titre1,'','','','','','','');	
			$titre2 = $langs->trans("Prix").info_admin($langs->trans("DefResaPrix"),1);
			print_liste_field_titre($titre2,'','','','','','','');	
			$var=True;				

			print "<tr  $bc[$var] style='background-color:".$langs->trans("ClPaveSaisie")."'>";
						
			print '<td align="left" ><textarea cols="45" rows="'.ROWS_1.'" wrap="soft" name="NomPrenom" '.$event_filtre_car_saisie.' >';
					print $bull->lines[0]->NomPrenom.'</textarea>';
			print '</td>';	
			print '<td align="left"><textarea cols="45" rows="'.ROWS_1.'" wrap="soft" name="observation"  '.$event_filtre_car_saisie.' >';
					print $bull->lines[0]->observation.'</textarea>';								
			print '</td>';	
			print '<td align="left"><textarea cols="45" rows="'.ROWS_1.'" wrap="soft" name="prix"  '.$event_filtre_car_saisie.' >'; //line->lb_pmt_neg
					print $bull->lines[0]->prix.'</textarea>';		
			print '</td>';								
			print '</tr>';	
	
			print "<tr >";
			$this->BtNvResa();			;
			print '</tr>';
			

		print "</table id=Niv2_ListeParticip>";
		print '</div>';
		print '<a name="AncreSaisieParticipation">';
		unset ($w);
		print '</td></tr></tbody></table id=Niv1AffichActPart>';
		print '</form>';


	}//Activite_Participant
	

	function BtNvResa()
	{
		global $action, $bull, $langs, $RESA_NVLIGNE, $RESA_ENR;
		
		// bouton Nvresa pour ajouter ligne
		// bouton enregistrer
			print '<td colspan=3></td ><td align=center colspan=2>';

			//	print '<input type="hidden" name="action" value="'.$RESA_NVLIGNE.'">';
			//print '<input class="button"  type="submit" value="'.$langs->trans("Nvligne").'">';
			print '<input type="hidden" name="action" value="'.$RESA_ENR.'">';
			print '<input class="button"  type="submit" value="'.$langs->trans("Enregistrer").'">';
			print '</td>';
	} //BtNvResa
	function SaisieResaGlobal() {
		
		global $id_resa, $langs, $langs, $bull, $user, $action,  $event_filtre_car_saisie ;
		
		global $ENR_RESAINFO;		
		global $ResaActivite, $activite_dated,  $place, $activite_dateheured;
		global $tbNomPrenom, $tbobservation, $tbprix, $tbqte ;
		
		if (empty($ResaActivite)) $ResaActivite = $bull->ResaActivite;
		if (empty($activite_dated)) $activite_dated = $bull->heured;
		if (empty($place)) $place = $bull->place;
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id_resa='.$bull->id.'">';
		print '<input type="hidden" name="id_resa" value="'.$id_resa.'">';
		print '<input type="hidden" name="token" value="'.newtoken().'">';

		$wf = new FormCglCommun ($this->db);
		$w1 = new CglFonctionDolibarr($this->db);
		$wc = new CglFonctionCommune($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);

		print '<table  id=Niv1_inforesa  style=" solid black;font-size:13px;background-color:'.$langs->trans("ClPaveSaisie").';"  width="100%"> <tbody><tr>';

		print '<td>';
		print '<table  id=Niv2_InfoResa width=100%> <tbody><tr>';
		$afflib = info_admin($langs->trans("DefLibelle"),1);
		$wfctcomm->AfficheParagraphe("InfoResa", 4, $afflib );
		print '<tr><td>';
		print '<textarea cols="60" rows="'.ROWS_3.'" wrap="soft" name="ResaActivite"  '.$event_filtre_car_saisie.' >';
		if ($ResaActivite )  print $ResaActivite;
		else print $bull->ResaActivite;
		print '</textarea>';
		print '</td><tr><td  width="100" colspan=2>'.$langs->trans("DateResaDeb").'&nbsp';
		if (!empty($activite_dateheured)) $aff_activite_dated = $activite_dateheured;
		else $aff_activite_dated = $bull->heured;
		if (empty($aff_activite_dated)){ $temp = new DateTime();  $aff_activite_dated = 	$temp->format('Y-m-d H:i');}
		print $w1->select_date($aff_activite_dated,'activite_dated',1,1,'',"add",1,1,0,0,'','','',6,22,30);	
		
		print '</td></tr></tbody></table id=Niv2_InfoResa>';
		//print '<td >';
		// paragraphe de saisie d'un nouveau départ
		unset ($wf);
		unset ($w1);
		unset ($wc);
		print '</td></tr>';
		
		print '<tr><td align="right">';
			
		print '<input type="hidden" name="action" value="'.$ENR_RESAINFO.'">';		
		print '<input class="button"  type="submit" value="'.$langs->trans("BtEnregistrer").'">';	
	
		print '</td></tr>';
		print '</tbody></table id=Niv1_inforesa>';
		
		print '</form>';
	}//SaisieResaGlobal
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
		if ($bull->type == 'Loc') {
			$text = $langs->trans('ConfirmAbnCnt').' '.$bull->ref;
			$lb_id = 'id_contrat';
		}
		else {
			$text = $langs->trans('ConfirmAbnBull').' '.$bull->ref	;
			$lb_id = 'id_resa';
		}
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'.$lb_id.'='.$bull->id.'&type='.$bull->type,$question,$text,$BUL_CONFABANDON,'','',1);
		print $formconfirm;
	} //AbandonArchive

 }//Class
 
?>