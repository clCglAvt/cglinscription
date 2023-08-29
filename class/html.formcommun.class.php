<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - V2.6.1.4 sept 2022 - Boite sélection totale des remise dans archivage de l'année et archivage global
 * Version CAV - 2.7 - juin 2022 - Migration Dolibarr V15
 *								- POuvoir modifier le montant du paiement Stripe
 * Version CAV - 2.7.1 - automne 2022 - dans la fonction Abandon, réserver le message ConfirmRefusAnul aux annulation avec paiement différent de 0
 * Version CAV - 2.8 - hiver 2023 - - réorganisation du pavé PaveSuivi de BU/LO
 *									- contrat/bulletin technique
 *								    - Installation popup Modif/creation Suivi pour Inscription/Location
 *									- fiabilisation des foreach
 *									- reassociation BU/LO à un autre contrat
 *									- remise à plat des statuts BU/LO
 *									- bouton enregistrement modif tiers dépendant de la saisie d'un tel ou mail
 *									- Saisie en un tableau des poids/taille/age/prenom
 *	Version CAV - 2.8.1 - hiver 2023	- permettre le choix d'un autre modèle de mail lors de la relance Stripe
 * Version CAV - 2.8.3 - printemps 2023
 *		- suppression des guillemets dans les textarea
 * Version CAV - 2.8.4 - printemps 2023 - 
 *		- Ajouter le champ Complément de référence matériel 
 * 	et vérification de la saisie de 3 caractèes pour la référence matériel  
 *		dans Taille-Poids et Age (308)
 *	et faire disparaitre le nom du tiers sur le champ prénom en aide à la saisie (267.3) - annulé
 * Version CAV - 2.8.5 - printemps 2023
 *		- vérification à la volée des conflit de vélo  (308b)
 *		- afficher le prix pour modification dans Taille, Poids Age  (334)
 *		- Indiquer que le prix est avant remise   (337)
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
 *  \file       dev/skeletons/skeleton_class.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Put here some comments
 */

 
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT."/custom/agefodd/class/html.formagefodd.class.php";
require_once DOL_DOCUMENT_ROOT."/custom/agefodd/class/agsession.class.php";
require_once(DOL_DOCUMENT_ROOT."/custom/cglavt/class/html.cglFctCommune.class.php");
require_once(DOL_DOCUMENT_ROOT."/custom/cglavt/class/cglFctDolibarrRevues.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

 class FormCglCommun extends Form  {
 
	
    function __construct($db)
    {
		global $langs, $db;
        $this->db = $db;
		$langs->load('cglinscription@cglinscription');
	}
	
    function getNomUrl($withpicto=0,$option='',$maxlen=0, $id, $type ='', $id1='', $autrearg='')
    {
        global $conf,$langs, $MAJ_DEPART;
        $result='';
		$lienfin='</a>';
		if (empty($id)) return '';
		if ($option == 'Tiers') 	{
			$result = '<a href="'.DOL_MAIN_URL_ROOT.'/custom/CahierSuivi/suivi_client/list_tiers.php?typeliste=tiers&Reftiers='.$id.'&token='.newToken().'" >' ;
			// $result = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$id.'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_MAIN_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}			
		elseif ($option == 'Facture') {
			$result = '<a href="'.DOL_MAIN_URL_ROOT.'/compta/facture/card.php?facid='.$id.'&token='.newToken().'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}
		elseif ($option == 'MAJInscritp') {
			$result = '<a href="./inscription.php?id_bull='.$id.'&type='.$type.'&token='.newToken().'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}
		elseif ($option == 'MAJLocation') {
			$result = '<a href="./location.php?id_contrat='.$id.'&type='.$type.'&token='.newToken().'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}
		
		elseif ($option == 'MAJResa') {
			$result = '<a href="./reservation.php?id_resa='.$id.'&type='.$type.'&token='.newToken().'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}
		elseif ($option == 'Depart') {
			//$result = '<a href="'.DOL_URL_ROOT.'/custom/agefodd/session/card.php?id='.$id.'&token='.newToken().'" >' ;
			$result = '<a href="'.$_SERVER['PHP_SELF'].'?type=Insc&id_bull='.$id1.'&action='.$MAJ_DEPART.'&id_depart='.$id.$autrearg.'&token='.newToken().'" >' ;
			if (empty($id)) return '';
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}
		elseif ($option == 'DepartEcran') {
			//$result = '<a href="'.DOL_URL_ROOT.'/custom/agefodd/session/card.php?id='.$id.'&token='.newToken().'" >' ;
			$result = '<a href="'.DOL_MAIN_URL_ROOT.'/custom/cglinscription/fichedepart.php?id_depart='.$id.'&total=oui&token='.newToken().'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}

		elseif ($option == 'Moniteur') {
			$result = '<a href="'.DOL_MAIN_URL_ROOT.'/custom/cglinscription/fiche_moniteur.php?id='.$id.'&token='.newToken().'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}

		elseif ($option == 'Produit') {
			$result = '<a href="'.DOL_MAIN_URL_ROOT.'/product/card.php?id='.$id.'&token='.newToken().'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}
		elseif ($option == 'Lieu') {
			$result = '<a href="site.php?id='.$id.'&token='.newToken().'" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
		}
		elseif ($option == 'Remise') {
		/* Attente si l'on trouve un moyen d'atteindre un dictionnaire par son mon et non son code qui peut changer à toute migrantionelseif
			$result = '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=42" >' ;
			$result .= '<img border = 0 title="Choisir" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$withpicto.'" alt="'.$langs->trans("Afficher").'">';
			*/
			$result = '';
			}
		else
			$result = '' ;
			
		//$result .= '<img border = 0 title="Choisir" src="../../theme/eldy/img/object_company.png" alt="'.$langs->trans("Modif").'">';
       $result.=$lienfin;
       return $result;
	}//getNomUrl
	 
	function AfficheTrouveTiers()
	{ 
		global $id_client, $id_bull, $id_contrat, $langs, $db, $bull, $action, $conf, $type;
		global $tiersNom, $TiersVille, $TiersIdPays, $TiersTel, $options_s_tel2, $TiersMail, $TiersAdresse, $TiersCP, $Villegiature, $civility_id, $firstname;
		global  $INDICATIF_TEL_FR;
		global $VIDE_TIERS, $SEL_TIERS, $CREE_TIERS_BULL, $CREE_TIERS_BULL_DOSS, $CREE_BULL_DOSS ;

		$this->PreScript();
		$tiersNom = ''; $TiersVille = ''; $TiersIdPays = '';  $TiersTel = $INDICATIF_TEL_FR; $TiersTel2 = $INDICATIF_TEL_FR; $TiersMail = ''; $TiersAdresse = ''; 	$TiersCP = '';
		$this->AfficheEcranEnvironnement("Recherche du client",0,$bull->type, $type);
		
		if (empty($id_bull)) $id=$id_contrat;
		else $id=$id_bull;
		
		/* SELECTION */
		// reperer la session pour la methode POST
		$form = new Form($db);
		$wfcomDol = new  CglFonctionDolibarr ($this->db);
		print '<table class="liste" width="100%">';
		$moreforfilter='';
		$moreforfilter.=$langs->trans('RechercheTiers'). ': ';
		// Force la combo, c'est a dire la recherceh des 3 PREMIERS caractères
		//$moreforfilter.=$this->select_company($id_client,'id_client','',1,'',1, '', 1);
		$moreforfilter.=$this->select_compagnie($id_client,'id_client','',1,'',0,'', 1);
		print '<tr class="liste_titre width="80%"">';
		print '<form method="POST" name="ChercheTiers" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.$SEL_TIERS.'">';
		print '<td  >';
		print $moreforfilter;
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input class="button" action="'.$SEL_TIERS.'" type="submit" value="Selectionner" align="right" >';	
		print '</td>';
		print "</form>";
		if (!empty($action) and  !($action == $VIDE_TIERS  ))
		{
			print '<td>';
			print "</form>";
			print '<form method="POST" name="ChercheTiers" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" value="'.$VIDE_TIERS.'" name="action" ">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input class="button"  type="submit" value="'.$langs->trans("BtCreerNouvTiers").'" align="right" >';
			print "</form>";
			print '</td>';
		}
		print "</table>";
		
		/* SAISIE Pour MISE A JOUR */
		print '<p>&nbsp;</p>';
		print '<p>&nbsp;</p>';
		if (empty($action))		{
			print '<form method="POST" name="ChercheTiers" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="action" value="'.$VIDE_TIERS.'">';
			print '<input class="button"  type="submit" value="'.$langs->trans("BtCreerNouvTiers").'" align="right" >';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print "</form>";
		}
					
		if ($action == $VIDE_TIERS or  $action == $SEL_TIERS)		{
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.newtoken().'">';

			if ($bull->type == 'Insc' )			
				print '<input type="hidden" name="id_bull" value="'.$id.'">';
			elseif ($bull->type == 'Loc' )			
				print '<input type="hidden" name="id_contrat" value="'.$id.'">';
			elseif ($bull->type == 'Resa' )			
				print '<input type="hidden" name="id_resa" value="'.$id.'">';
			print '<input type="hidden" name="id_client" value="'.$id_client.'">';

			//print '<table id="TiersSuivi_Niv1"><body><tr><td>';
			print '<div id="DivTiersSuivi_Niv1" style="margin:0 auto;" >';
			print '<div id="DivTiersSuivi_Niv2_Tiers" style="float:left;width:50%">';
			print '<table id="TiersSuivi_Niv2_Tiers">';
				$moreforfilter='';
				if ($action==$SEL_TIERS or $action == $VIDE_TIERS or $action == '' or !isset($action) or is_null($action))
													$moreforfilter.=$langs->trans('CreationTiers'). ': ';
				elseif ($action==$SEL_TIERS)  	$moreforfilter.=$langs->trans('ModifTiers'). ': ';
				$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
				
				print '<tr class="liste_titre">';
				print '<td colspan="4" >';
				print $moreforfilter;
				print '</td></tr>';
				
				// affiche le client
				if ($id_client >0 or $id>0) 		$this->RechercheTiers($id_client);

				print '<tr  > ';
				print '<td>'.$langs->trans("Nom").'</td><td colspan=2><input class="flat" size="50" type="text" name="tiersNom" value="'.$tiersNom.'"></td>';
				// simplement en création
				if ($id_client ==0 )  {
					print '</tr><tr>';
					print '<td>'.$langs->trans("Prenom").'</td><td colspan=2><input class="flat" size="50" type="text" name="firstname" value="'.$firstname.'"></td>';
					print '</tr><tr>';
					print '<td>'.$langs->trans("Civilite").'</td>';
					$formcompany = new FormCompany($db);	
					print '<td  colspan=2>'.$formcompany->select_civility('MR', "civility_id").'</td>';	
					unset ($formcompany);								
				}			
				//$s=$langs->trans("AideNomTiers");
				//print '<td>'.$formaide->textwithpicto('',$s,1).'</td>';
				print '</tr><tr  >';
				print '<td>'.$langs->trans("Phone").'</td><td><input class="flat" size="15" type="text" name="TiersTel" id="TiersTel" value="'.$TiersTel.'" onchange="AffBut(this);"></td>';
				print '</tr><tr>';
				print '<td>'.$langs->trans("PhoneSup").'</td><td><input class="flat" size="15" type="text" name="options_s_tel2" value="'.$TiersTel2.'"></td>';
				print '</tr><tr>';
				print '<td>'.$langs->trans("Mail").'</td><td><input class="flat" size="50" type="text" name="TiersMail" id="TiersMail" value="'.$TiersMail.'" onchange="AffBut(this);" ></td>';
		//		print '<td>'.$langs->trans("Autorisation NewLetter").'</td><td>'$form->selectyesno('AuthMail',1,1).'></td>';
				print '</tr><tr  >';
				print '<td>'.$langs->trans("Adresse").'</td><td colspan=2><input class="flat" size="50" type="text" name="TiersAdresse" value="'.$TiersAdresse.'"></td>';
				print '</tr><tr  >';
				print '<td>'.$langs->trans("Zip").'</td><td colspan=2><input class="flat" size="10" type="text" name="TiersCP" value="'.$TiersCP.'"></td>';
				print '</tr><tr  >';
				print '<td>'.$langs->trans("Town").'</td><td colspan=2><input class="flat" size="20" type="text" name="TiersVille" value="'.$TiersVille.'"></td>';
				print '</tr><tr  >';
				print '<td>'.$langs->trans("Country").'</td><td colspan=2>';
				
				if (empty($TiersIdPays) or $TiersIdPays == -1) $TiersIdPays =1;
				print $form->select_country($TiersIdPays, 'TiersIdPays');
				if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
			
				//print '<td>'.$langs->trans("Country").'</td><td colspan=2><input class="flat" size="20" type="text" name="TiersIdPays" value="'.$TiersIdPays.'"></td>';
				print '</td></tr>';
				print '<tr><td>';
				print '<tr><td>'.$langs->trans('Source').'</td><td>';	
				$form->selectInputReason($bull->fk_origine,'TiersOrig','',1);
				print '</td></tr>';
				print '<tr>';
				print '<td>'.$langs->trans("TiersLieuVacances").'</td><td colspan=2><input class="flat" size="10" type="text" name="Villegiature" value="'.$bull->Villegiature.'"></td>';
				print '</tr>';
			//  select_demand_reason($selected='',$htmlname='demandreasonid',$exclude='',$addempty=0)
			print "</table></td><td>"; // Fermetuiure table "TiersSuivi_Niv2_Tiers"
			print '</div >'; // Fermeture DivTiersSuivi_Niv1
			
				print '<div id="DivTiersSuivi_Niv2_Suivi" style="width:50%;align:center;margin-left:50%;height:335px;">';
			if ($conf->cahiersuivi)  {
				//print '<table id="TiersSuivi_Niv2_Suivi">';

				$this->BoiteSuiviClient($bull->id_client);	
				//print '</table>	';// Fermeture table TiersSuivi_Niv2_Suivi
			}		
			print '</div >'; // Fermeture div DivTiersSuivi_Niv2_Suivi
			//print '</td></tr></body></table>';	// Fermeture table TiersSuivi_Niv1	
          print ' <div style="clear: both;"></div>'; // Permet de terminer la différence hauteur des deux blocs. La ligne suivante revient en bas des deux blocs

		// Active ou désactive le bouton de création			

			// BU/LO technique ou facturé
		if ($type == 'Insc' or $type == 'Loc')  {			
			print '<div align=center>';
			if ($type == 'Insc') $wtexte = "bulletin";
			elseif ($type == 'Loc') $wtexte = "contrat";
			print '<td><b>'.$langs->trans("BulletinFacture", $wtexte).'</b>';
//			$events  = 'onchange=AffBut(this,"'.$type.'")';
			$events  = 'onchange="AffBut(this);"';
			
			print $wfcomDol->selectyesno('BullFacturable', "yes" ,  0, false, 1,0,'',  $events );
			print '</td>';
			print '</div>';	
		}
			// encapsulage
			
			// BOUTON DE VALIDATION
			print '<p style="text-align:center;">';				
			//if (!isset($action) or is_null($action) or $action == $VIDE_TIERS or $action == '' or  $action == $SEL_TIERS) {
				if ($conf->cahiersuivi) {
					if ($type == 'Loc') $value = $langs->trans("BtCreerContratSuivi");
					elseif ($type == 'Insc') $value = $langs->trans("BtCreerBulletinSuivi");
					elseif ($type == 'Resa') $value = $langs->trans("BtCreerResaSuivi");
					if ($id_client)  $action = $CREE_BULL_DOSS;
					else $action = $CREE_TIERS_BULL_DOSS;
						print '<input type="hidden" name="action" value="'.$action.'">';
					
				}
				else {
					if ($type == 'Loc') $value = $langs->trans("BtCreerContrat");
					elseif ($type == 'Insc') $value = $langs->trans("BtCreerBulletin");
					elseif ($type == 'Resa') $value = $langs->trans("BtCreerResa");
					print '<input type="hidden" name="action" value="'.$CREE_TIERS_BULL.'">';
				}
				if ( empty($bull->TiersTel) and empty($bull->TiersMail)) 
					print '<input class="button butActionRefused" id="btCreerBull" name="btCreerBull"  type="submit" value="'.$value.'" disabled ></p>';	
				else
					print '<input class="button " id="btCreerBull" name="btCreerBull"  type="submit" value="'.$value.'"  ></p>';	
			//}
			print '</p>';		
			print '</div >'; // Fermeture div DivTiersSuivi_Niv1	
			print "</form>";
			
			print '<p>&nbsp;</p>';	
			print '<p>&nbsp;</p>';	
			
			/* Liste des contrats de ce client */	
			$this->BoiteAccompteClient($bull->id_client,'');
			$this->BoiteFacturationClient($bull->id_client);
			$this->BoiteContratClient($bull->id_client);
			$this->BoitePropalClient($bull->id_client);					
		}
		// fin d'encapsulage 
		//print '</table		 id=ValideTiers>';
		
	}//AfficheTrouveTiers

	function BoiteSuiviClient($id)
	{	
		global $langs, $bull;
		print $this->html_BoiteSuiviClient($id,  $langs, $bull, 0);
	} //BoiteSuiviClient
	
	/* 
	*	Cherche les dossier d'un tiers et les affiche avec bouton-radio
	*
	*	$param	int		$id			identitiant du tiers
	*	$param	objet	$langs		objet langs
	*	$param	objet	$bull		objet bulletin	
	*	$param	int		$origine	0 si on affiche dans écran normal, 1 sinon on est dans une modale
	*	$retour	code html
	*/
	function html_BoiteSuiviClient($id,  $langs, $bull, $origine = 0)
	{
		$w= New CglCommunLocInsc ($this->db);
		if (empty($id)) $id = -1;
		$resql = $w->SqlChercheRelationTiers ($id, 'SUIVI');
		unset($w);
		$out = '';
		if ($resql)
		{
			$var=true;
			$num = $this->db->num_rows($resql);
			if ($num > 7) $out .= '<div id="DivTiersSuivi_Niv3_Suivi" style="height:335px;overflow:scroll;">';
			$out .= '<table class="noborder" width="50%" id="BoiteSuiviClient">';

			$out .= '<tr class="liste_titre" >';
			$out .= '<td></td>';
			$out .= '<td>'.$langs->trans("DossierSuivi",$num).'</td>';
			$out .= '<td>Statut</td>';
			$out .= '<td></td>';
			$out .= '</tr>';
			
			// Ligne Nouveau dossier
			//if ($origine == 0)
				$out .= '<tr><td><input  type="radio" checked class="flat" id="rdnvdoss"  name="rdselectdoss"   value="-1" onclick="AffBtCreer(this)"></td>';
			//else 
			//	$out .= '<tr><td><input  type="radio" checked class="flat" id="rdnvdoss"  name="rdselectdoss"   value="-1" ></td>';


			if (empty($valuenv)) {
				$valuenv = 'nouveau dossier'; 
				$style='color:#C0C0C0;'; 
			} 
			else $style='color="#000000"'; 
			//if ($origine == 0)
				$out .= '<td><input id="nvdossier" type="text" name="nvdossier" value="'.$valuenv.'"  style="'.$style.'" onclick="LibNvDoss(this)" onblur="LibNvDoss(this)" >'; 
			//else 
			//	$out .= '<td><input id="nvdossier" type="text" name="nvdossier" value="'.$valuenv.'"  style="'.$style.'"  >'; 
				
				$out.= '<script> '."\n";	
				$out.= ' function LibNvDoss(o) {
						if ( o.value == "nouveau dossier") {				
							document.getElementById("nvdossier").value="";  					
							document.getElementById("nvdossier").style.color="#000000"; 
							document.getElementById("rdnvdoss").checked=true;';
							if ($origine == 0) $out .= 'document.getElementById("btCreerBull").disabled = false;';												
				$out .='							
						}
						else if (o.value == "") {			
							document.getElementById("nvdossier").value="nouveau dossier";  					
							document.getElementById("nvdossier").style.color="#C0C0C0";  
							document.getElementById("rdnvdoss").checked=false;';
							if ($origine == 0) $out .= 'document.getElementById("btCreerBull").disabled = true;';												
				$out .='		
						};
					}
					function AffBtCreer(o) <!-- pour la ligne Dossier nouveau -->
					{';
						if ($origine == 0) $out .='document.getElementById("btCreerBull").disabled = false;	';												
				$out .='							
					}
					function AffBtCreerL(o) <!-- pour les lignes  dossier -->
					{';
					if ($origine == 0) $out .=' document.getElementById("btCreerBull").disabled = false;';												
				$out .='		
						document.getElementById("rdnvdoss").checked=false;	
					}
					</script>'."\n";
				$out .= '</td>';

			$out .= '</tr>';
		
			$i = 0;
			while ($i < $num )			{
				$img ='';
				$texte='';
				$objp = $this->db->fetch_object($resql);
				if (!empty($objp->abandon)) {
					// BU/LO Abandonne
					$style = 'style=color:grey';
				}	
				else $style = '';
				if (empty($style)) $out .= "<tr ".$bc[$var].">";
				else $out .= "<tr ".$style." >";
				
				if ($objp->statut == $bull->BULL_ENCOURS) {  // en gras les bulletins en cours
					$gras = '<b>';
					$fingras = '</b>';
				}				
				else  {  // retour normal
					$gras = '';
					$fingras = '';
				}
					
				//$out .= '<td><input class="flat checkselection_" name="rowid['.$line->id.']" type="checkbox" value="'.$line->id.'" size="1"'.($flgcheked?' checked="checked"':'').'></td>';
				$out .= '<td><input  type="radio"  class="flat" id="rowid['.$objp->rowid.']"  name="rdselectdoss"  value="'.$objp->rowid.'" onclick="AffBtCreerL(this)"></td>';
				$out .= '<td>';
				
				$out .= '<a href="../CahierSuivi/suivi_client/list_dossier.php?typeliste=dossier&Refdossier='.$objp->rowid.'&Reftiers='.$id.'&socid='.$id.'" >' ;
				$out .= '<img border = 0 title="Dossier" src="'.DOL_URL_ROOT.'/theme/eldy/img/object_company.png" alt="'.$langs->trans("Dossier").'">';
				$out .= '</a>&nbsp&nbsp';
				$out .= $objp->libelle ;
				$out .= '</td>';
				$out .= '<td style="background-color:#'.$objp->color.'">'.$objp->label .'</td>';		
				$out .= '</tr>';
				$var=!$var;
				$i++;
			} // While
			$this->db->free($resql);
			$out .= "</table>"; // Fermeture table html_BoiteSuiviClient
			if ($origine == 0) $out .= '</div>'; // Fermeture de DivTiersSuivi_Niv3_Suivi
		}
		else	{
			dol_print_error($this->db);
		}
		return $out;
	} // html_BoiteSuiviClient

	function BoiteContratClient($id)
	{	
		global $langs, $bull;
		$w= New CglCommunLocInsc ($this->db);
		$resql = $w->SqlChercheRelationTiers ($id, 'BULO');
		unset($w);
		
		if ($resql)
		{
			$var=true;
			$num = $this->db->num_rows($resql);

			if ($num > 0)             {
				print '<div style="width:100%;height:175px;overflow:scroll;" id="DivScroll">'; 
				print '<table class="noborder" width="50%" id="BoiteContratClient">';

				print '<tr class="liste_titre" >';
				print '<td colspan="2">'.$langs->trans("LstContAct",($num<=$MAXLIST?"":$MAXLIST)).'</td>';
				print '<td>Statut</td><td>Reglement</td>';
				print '</td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num)			{
				$img ='';
				$texte='';
				$objp = $this->db->fetch_object($resql);
				if (!empty($objp->abandon)) {
					// BU/LO Abandonne
					$style = 'style=color:grey';
				}	
				else $style = '';
				if (empty($style)) print "<tr ".$bc[$var].">";
				else print "<tr ".$style." >";
				
				if ($objp->statut == $bull->BULL_ENCOURS) {  // en gras les bulletins en cours
					$gras = '<b>';
					$fingras = '</b>';
				}				
				else  {  // retour normal
					$gras = '';
					$fingras = '';
				}		
				/*print '<td class="nowrap"><a href="propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a>'."\n";
				if ( ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 )
				{
					print " ".img_warning();
				}
				*/
				print '<td  '.$style.'  >'.$gras.dol_print_date($this->db->jdate($objp->datec),'day').$fingras ."</td>\n";
				print '<td  '.$style.' >';	
				if ($objp->type == 'Insc') $ChoixUrl= 'MAJInscritp';
				elseif ($objp->type == 'Loc') $ChoixUrl= 'MAJLocation';
				elseif ($objp->type == 'Resa') $ChoixUrl= 'MAJResa';
				print $this->getNomUrl("object_company.png", $ChoixUrl,0,$objp->rowid, $objp->type);
				print '&nbsp;'.$gras.$objp->ref.$fingras .'</td>';
				//print '<td align="right" style="min-width: 60px">'.$objp->statut.'</td>';				
				
				if ($objp->type == 'Loc'){
					if ($objp->statut == $bull->BULL_ENCOURS) { 	 $img=$bull->IMG_ENCOURS; $texte=$bull->LIB_CNT_ENCOURS;}
					elseif ($objp->regle < $bull->BULL_FACTURE and $objp->statut == $bull->BULL_CLOS and !empty($objp->fk_facture)) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACT_INC;}
					elseif ($objp->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_CNT_FACT_INC; $texte=$bull->LIB_CNT_FACT_INC;}
					elseif ($objp->statut == $bull->BULL_VAL ) { $img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
					elseif ($objp->statut == $bull->BULL_DEPART ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_DEPART;}
					elseif ($objp->statut == $bull->BULL_RETOUR ) { $img=$bull->IMG_RETOUR; $texte=$bull->LIB_RETOUR;}
					elseif ($objp->statut == $bull->BULL_CLOS ) { $img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
					elseif ($objp->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
					elseif ($objp->statut == $bull->BULL_ANNULCLIENT ) { $img=$bull->IMG_ANNULCLIENT; $texte=$bull->LIB_ANNULCLIENT;}
					else { $img = ''; $texte = 'inconnu '. $objp->statut;}
				}
				else {
					if ($objp->statut == $bull->BULL_ENCOURS ) { 	 $img=$bull->IMG_ENCOURS; $texte=$bull->LIB_ENCOURS;}
					elseif ($objp->regle ==0 and $objp->statut ==1 and !empty($objp->fk_facture)) {$img=$bull->IMG_FACT_INC; $texte=$bull->LIB_FACT_INC;}
					elseif ($objp->regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_FACTURE; $texte=$bull->LIB_FACT_INC;}
					elseif  ($objp->statut == $bull->BULL_INS)  {$img=$bull->IMG_INS; $texte=$bull->LIB_INS;}
					elseif ($objp->statut == $bull->BULL_PRE_INS) {$img=$bull->IMG_PRE_INS; $texte=$bull->LIB_PRE_INS;}
					elseif ($objp->statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
					elseif ($objp->statut == $bull->BULL_ANNULCLIENT ) { $img=$bull->IMG_ANNULCLIENT; $texte=$bull->LIB_ANNULCLIENT;}
					else { $img = ''; $texte = 'inconnu '. $objp->statut;				}
				}
				print '<td>';
				if (!empty($texte))  print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="../../theme/eldy/img/'.$img.'">';	
				print '</td>';	
				if ($objp->type != 'Resa') {
					if ($objp->regle == $bull->BULL_NON_PAYE  and $objp->montantdu > 0) { $img=$bull->IMG_NON_PAYE; $texte=$bull->LIB_NON_PAYE;}
					elseif ($objp->regle == $bull->BULL_NON_PAYE  and $objp->montantdu <= 0) {$img=''; $texte='';}
					elseif ($objp->regle ==$bull->BULL_INCOMPLET) {$img=$bull->IMG_INCOMPLET; $texte=$bull->LIB_INCOMPLET;}
					elseif ($objp->regle ==$bull->BULL_PAYE) {$img=$bull->IMG_PAYE; $texte=$bull->LIB_PAYE;}
					elseif ($objp->regle ==$bull->BULL_SURPLUS) {$img=$bull->IMG_SURPLUS; $texte=$bull->LIB_SURPLUS;}
					elseif ($objp->regle ==$bull->BULL_REMB) {$img=$bull->IMG_REMB; $texte=$bull->LIB_REMB;}
					else { $img = ''; $texte = 'inconnu '. $objp->regle;}
					if ($objp->type == 'Loc') { 
						if ($objp->regle ==$bull->BULL_FACTURE) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACTURE;}
						elseif ($objp->regle ==$bull->BULL_ARCHIVE) {$img=''; $texte='';}
					}
					else{
						if ($objp->regle ==$bull->BULL_FACTURE) {$img=''; $texte='';}
						elseif ($objp->regle ==$bull->BULL_ARCHIVE) {$img=$bull->IMG_ARCHIVE; $texte=$bull->LIB_ARCHIVE;}
					}
					print '<td>';
					if (!empty($texte))  print '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="../../theme/eldy/img/'.$img.'">';	
					print '</td>';	
				}
				else print '<td '.$style.' ></td>';
					

				//print '<td align="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
				print '</tr>';
				$var=!$var;
				$i++;
			}
			$this->db->free($resql);
			if ($num > 0) {
				print "</table>";// fermeture table BoiteContratClient
				print '</div>'; // Fermeture div DivScroll
			}
		}
		else	{
			dol_print_error($this->db);
		}
	} // BoiteContratClient
	function BoiteAccompteClient($id)
	{	
		global $langs , $bc, $bull;
		$w= New CglCommunLocInsc ($this->db);
		$resql = $w->SqlChercheRelationTiers ($id, 'ACOMPTE_DISPO');
		unset($w);

		
		if ($resql)			{
			$var=true;
			$num = $this->db->num_rows($resql);

			if ($num > 0)             {		
				print '<div style="width:100%;height:175px;overflow:scroll;" id="DivScroll">'; 		
				print '<table class="noborder" width="50%" id="BoiteAccompteClient">';
				print '<tr class="liste_titre" >';
					print '<td colspan="2">'.$langs->trans("LstAcompte",($num<=$MAXLIST?"":$MAXLIST));
				print '</td>';
				print '<td>'.$langs->trans("Montant").'</td><td>'.$langs->trans("BuCnt").' </td>';
				print '<td>Information';	
				print '</td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num)				{
				$objp = $this->db->fetch_object($resql);
				if ($objp->acomptelibre == 1 ) { $color = 'red' ; $bold = 'bold'; print '<tr BGCOLOR="OrangeRed" style="font-weight=bold;"><b>'; }
				else print "<tr ".$bc[$var].">";
				/*print '<td class="nowrap"><a href="propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a>'."\n";
				if ( ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 )
				{
					print " ".img_warning();
				}
				*/
				print '<td  >'.dol_print_date($this->db->jdate($objp->datef),'day')."</td>\n";
				print '<td>';
				print $this->getNomUrl("object_company.png", 'Facture',0,$objp->fid);
				print '&nbsp;'.$objp->ref.'</td>';
				print '<td>';
				print '&nbsp;'.price($objp->total_ttc).'</td>';				
				
				print '<td>';
				if (!empty($objp->bid)) {
				if ($objp->type == 'Insc') 
					if ($objp->type == 'Insc') $ChoixUrl= 'MAJInscritp';
					elseif ($objp->type == 'Loc') $ChoixUrl= 'MAJLocation';
					elseif ($objp->type == 'Resa') $ChoixUrl= 'MAJResa';
					print $this->getNomUrl("object_company.png", $ChoixUrl,0,$objp->bid, $objp->type);
				}
				print '&nbsp;'.$objp->ref.'</td>';	
				print '<td>';
				if (empty($objp->RemExcep) and $objp->nature == 'Acompte' ) print 'Acompte non transformé en remise';
				elseif ($objp->acomptelibre == 1) print 'Acompte utilisable';
				print '</td >';
				

				//print '<td align="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
				print '</b></tr>';
				$var=!$var;
				$i++;
			}
			$this->db->free($resql);
			if ($num > 0) {
				print "</table>"; // Fermeture table BoiteAccompteClient
				print "</div>"; // Fermeture div DivScroll				
			}
		}
		else
		{
			dol_print_error($this->db);
		}
	} // BoiteAccompteClient

	
	function BoiteFacturationClient($id)
	{	
		global $langs , $bc, $bull;
		$w= New CglCommunLocInsc ($this->db);
		$resql = $w->SqlChercheRelationTiers ($id, 'FACTURE');
		unset($w);

		if ($resql)			{
			$var=true;
			$num = $this->db->num_rows($resql);

			if ($num > 0)             {
				print '<div style="width:100%;height:175px;overflow:scroll;" id="DivScroll">'; 		
				print '<table class="noborder" width="50%" id="BoiteFacturationClient">';

				print '<tr class="liste_titre" >';
				print '<td colspan="2">'.$langs->trans("LstActivite",($num<=$MAXLIST?"":$MAXLIST)).'</td>';
				print '<td>'.$langs->trans("Montant").'</td><td>'.$langs->trans("BuCnt").' </td>';
				print '<td>Information</td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num)				{
				$objp = $this->db->fetch_object($resql);
				if ($objp->acomptelibre == 1 ) { $color = 'red' ; $bold = 'bold'; print '<tr BGCOLOR="OrangeRed" style="font-weight=bold;"><b>'; }
				else print "<tr ".$bc[$var].">";
				/*print '<td class="nowrap"><a href="propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a>'."\n";
				if ( ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 )
				{
					print " ".img_warning();
				}
				*/
				print '<td  >'.dol_print_date($this->db->jdate($objp->datef),'day')."</td>\n";
				print '<td>';
				print $this->getNomUrl("object_company.png", 'Facture',0,$objp->fid);
				print '&nbsp;'.$objp->ref.'</td>';
				print '<td>';
				print '&nbsp;'.price($objp->total_ttc).'</td>';
				print '<td>';
				//print '&nbsp'.$objp->fk_statut.'</td>';
				if ($objp->type == 'Insc') $ChoixUrl= 'MAJInscritp';
				elseif ($objp->type == 'Loc') $ChoixUrl= 'MAJLocation';
				elseif ($objp->type == 'Resa') $ChoixUrl= 'MAJResa';
				if (!empty($objp->bid)) print $this->getNomUrl("object_company.png", $ChoixUrl,0,$objp->bid, $objp->type);
				print '&nbsp;'.$objp->ref.'</td>';
				//print '<td align="right" style="min-width: 60px">'.$objp->statut.'</td>';		
				print '<td>';
				if (empty($objp->RemExcep) and $objp->nature == 'Acompte' ) print 'Acompte non transformé en remise';
				elseif ($objp->acomptelibre == 1) print 'Acompte utilisable';
				print '</td >';

				//print '<td align="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
				print '</b></tr>';
				$var=!$var;
				$i++;
			}
			$this->db->free($resql);
			if ($num > 0) {
				print "</table>"; // Fermeture table BoiteFacturationClient
				print "</div>"; // Fermeture div DivScroll				
			}
		}
		else
		{
			dol_print_error($this->db);
		}
	} // BoiteFacturationClient
	function BoitePropalClient($id)
	{	
		global $langs;
		$objectstatic=new Propal($db);
		$w= New CglCommunLocInsc ($this->db);
		$resql = $w->SqlChercheRelationTiers ($id, 'PROPAL');
		unset($w);		
		if ($resql)			{
			$var=true;
			$num = $this->db->num_rows($resql);

			if ($num > 0)             {
				print '<div style="width:100%;height:175px;overflow:scroll;" id="DivScroll">';
				print '<table class="noborder" width="50%" id="BoitePropalClient">';

				print '<tr class="liste_titre" >';
				print '<td colspan="2">'.$langs->trans("LstPropal",($num<=$MAXLIST?"":$MAXLIST)).'</td>';
				print '<td>Montant</td><td>Etat</td>';
				print '</td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num)				{
				$objp = $this->db->fetch_object($resql);
				print "<tr ".$bc[$var].">";
				/*print '<td class="nowrap"><a href="propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a>'."\n";
				if ( ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 )
				{
					print " ".img_warning();
				}
				*/
				print '<td  >'.dol_print_date($this->db->jdate($objp->datep),'day')."</td>\n";
				print '<td>';
				print $this->getNomUrl("object_company.png", 'Proposition',0,$objp->fid);
				print '&nbsp;'.$objp->ref.'</td>';
				print '<td>';
				print '&nbsp;'.price($objp->total_ttc).'</td>';
				print '<td align="right">'.$objectstatic->LibStatut($objp->fk_statut,5)."</td>\n";
				//print '<td align="right" style="min-width: 60px">'.$objp->statut.'</td>';		

				//print '<td align="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
				$var=!$var;
				$i++;
			}

			$this->db->free($resql);
			if ($num > 0) {
				print "</table>"; // Fermeture table BoitePropalClient
				print "</div>"; // Fermeture div DivScroll
				
			}
		}
		else
		{
			dol_print_error($this->db);
		}
	} // BoitePropalClient

	function BoiteFactureCoeurClient1($id)
	{	
		global $langs, $conf;
		
		if ($conf->global->MAIN_VERSION_LAST_UPGRADE <>'8.0.4')
			$sql = "SELECT f.ref as facnumber, ";
		else
			$sql = "SELECT f.facnumber, ";	
		$sql .= " f.rowid as fid, datef, total_ttc, fk_statut,  rem.rowid as RemExcep ";
		$sql.= " , CASE WHEN f.ref like 'AC%' THEN 'Acompte' ELSE 'Facture ou avoir' END as nature ";
		$sql.= " FROM  ".MAIN_DB_PREFIX."societe as s  ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on  s.rowid = f.fk_soc  ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cglinscription_bull as b on (b.fk_facture = f.rowid or b.fk_acompte = f.rowid ) ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_remise_except as rem on f.rowid = rem.fk_facture_source  ";
		$sql.= " WHERE   ISNULL( b.rowid) AND  f.total_ttc <> 0";
		if ($conf->global->MAIN_VERSION_LAST_UPGRADE <>'8.0.4')
			$sql.= " AND ( f.ref like 'FA%' or f.ref like '_PROV%' ) ";	
		else
			$sql.= " AND ( f.facnumber like 'FA%' or f.facnumber like '_PROV%' ) ";	
		$sql.= " AND s.rowid = '".$id."'";
		$sql.= " ORDER BY f.date_valid DESC";
		$resql=$this->db->query($sql);	
			
			if ($resql)			{
				$var=true;
				$num = $this->db->num_rows($resql);

				if ($num > 0)             {
					print '<div style="width:100%;height:175px;overflow:scroll;" id="DivScroll">';
					print '<table class="noborder" width="50%" id="BoiteFactureCoeurClient1">';

					print '<tr class="liste_titre" >';
					print '<td colspan="2">'.$langs->trans("LsFactureCoeurDolibarr",($num<=$MAXLIST?"":$MAXLIST)).'</td>';
					print '<td>'.$langs->trans("Montant").'</td><td>'.' </td>';
					print '<td>Information</td>';
					print '</tr>';
				}

				$i = 0;
				while ($i < $num)				{
					$objp = $this->db->fetch_object($resql);
					print "<tr ".$bc[$var].">";
					/*print '<td class="nowrap"><a href="propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a>'."\n";
					if ( ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 )
					{
						print " ".img_warning();
					}
					*/
					print '<td  >'.dol_print_date($this->db->jdate($objp->datef),'day')."</td>\n";
					print '<td>';
					print $this->getNomUrl("object_company.png", 'Facture',0,$objp->fid);
					print '&nbsp;'.$objp->facnumber.'</td>';
					print '<td>';
					print '&nbsp;'.price($objp->total_ttc).'</td>';
					print '<td>';
					if (empty($obj->RemExcep) and $objp->nature == 'Acompte' ) print 'Acompte non transformé en remise';
					print '</td >';

					//print '<td align="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
					print '</tr>';
					$var=!$var;
					$i++;
				}
				$this->db->free($resql);
				if ($num > 0) {
					print "</table>"; // Fermeture table BoiteFactureCoeurClient1
					print "</div>"; // Fermeture div DivScroll					
				}
			}
			else
			{
				dol_print_error($this->db);
			}
	} // BoiteAccompteClient
	/*
	* $nblig : nombre fde ligne blanche, permettant de tailler la table à la longueur des autres tables 
	*/
	function AfficheTiers($nblig=0)
	{

		global $action, $id_contrat, $langs, $bull, $INDICATIF_TEL_FR;
		global $TYPE_SESSION, $BUL_ANNULER, $MAJ_TIERS, $ENR_TIERS;
		global $TypeSessionCli_Agf, $langs;

		$this->PreScript();
		
		if ($bull->type == 'Loc') {
			$lb_id = 'id_contrat';
		}
		elseif ($bull->type == 'Insc') {
			$lb_id = 'id_bull';
		}
		elseif ($type == 'Resa') {
			$lb_id = 'id_resa';
		}
		
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="TypeSessionCli_Agf" value="'.$wfkflipflop.'">';
		print '<input type="hidden" name="'.$lb_id.'" value="'.$bull->id.'">';
		print '<input type="hidden" name="token" value="'.newtoken().'">';	
								
		$w = new CglLocation($this->db);
		$wf = new CglCommunLocInsc($this->db);
		$form= new Form ($this->db);
		
		print '<table  id=Niv2_Tiers width=100%>';
		print '<tr>';
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$wfctcomm->AfficheParagraphe("TiTiers", 3);
		print '</tr><tr><td>';
		if ($action == $MAJ_TIERS) $style=$langs->trans("ClPaveSaisie");
		else $style=$langs->trans("ClPaveTiersAff");
		print '<div class="tabBar" style="background-color:'.$style.';">';
		print '<table id=Niv3_tiers width=100%  ><tbody><tr><td>';
	
		print '<table id="Niv4_tiers"  ><tbody><tr>';
		print '<td>'.$langs->trans("Nom").'</td><td>';
		if ($action == $MAJ_TIERS) 
			print '<input class="flat"  type="text" name="tiersNom" value="'.$bull->tiersNom.'">';
		else				
			print $this->getNomUrl("object_company.png", 'Tiers',0,$bull->id_client, $bull->type)."&nbsp;<b>".$gras.$bull->tiersNom."</b>";	
					
			//print $bull->tiersNom;
		print '</td>';
		print '</tr><tr><td>&nbsp;</td></tr><tr>';
		print '<td>'.$langs->trans("Mail").'</td><td>';
		if ($action == $MAJ_TIERS) 
			print '<input class="flat"  type="text" name="TiersMail" id="TiersMail"  value="'.$bull->TiersMail.'" onchange="AffBut(this);">';
		else			
			print $bull->TiersMail;
		print '</td></tr><tr>';
		if ($bull->TiersTel == 'null' or empty($bull->TiersTel)) $temp = $INDICATIF_TEL_FR;
		else $temp = $bull->TiersTel;
		print '<td>'.$langs->trans("Phone").'</td><td>';
		if ($action == $MAJ_TIERS) 
			print '<input class="flat"  type="text" name="TiersTel" id="TiersTel" value="'.$temp.'" onchange="AffBut(this);">';
		else			
			print dol_print_phone($bull->TiersTel,   $bull->country_code, $bull->id_client, '', 'AC_TEL')		;
		print '</td></tr><tr>';
		if (($bull->TiersTel2 == 'null' or empty($bull->TiersTel2)) and $action == $MAJ_TIERS) $temp = $INDICATIF_TEL_FR;
		else $temp = $bull->TiersTel2;
		if ($bull->type <> 'Resa') {
			print '<td>'.$langs->trans("PhoneSup");
			print '</td><td>';
			if ($action == $MAJ_TIERS) 
				print '<input class="flat"  type="text" name="options_s_tel2" value="'.$temp.'">';
			else			
				print dol_print_phone($bull->TiersTel2,   $bull->country_code, $bull->id_client, '', 'AC_TEL')		;
			print '</td></tr>';
		}
		$i=0;
		print '<tr><td>&nbsp;</td></tr><tr>';
		print '<td> '.$langs->trans("Adresse").'</td><td>';
		if ($action == $MAJ_TIERS) 
			print '<input class="flat"  type="text" name="TiersAdresse" value="'.$bull->TiersAdresse.'">';
		else			
			print $bull->TiersAdresse;
		print '</td></tr><tr>';
		print '<td> '.$langs->trans("Zip").'</td><td>';
		if ($action == $MAJ_TIERS) 
			print '<input class="flat"  type="text" name="TiersCP" value="'.$bull->TiersCP.'">';
		else			
			print $bull->TiersCP;
		print '</td></tr><tr>';
		print '<td>'.$langs->trans("Town").'</td><td>';
		if ($action == $MAJ_TIERS) 
			print '<input class="flat"  type="text" name="TiersVille" value="'.$bull->TiersVille.'">';
		else			
			print $bull->TiersVille;		
		
		print '</td></tr><tr>';
		print '<td>'.$langs->trans("Country").'</td><td>';
		if ($action == $MAJ_TIERS) {
			if (empty($bull->TiersIdPays) or $bull->TiersIdPays == -1) $bull->TiersIdPays =1;
			print $form->select_country($bull->TiersIdPays, 'TiersIdPays');
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
			//print '<input class="flat"  type="text" name="TiersIdPays" value="'.$bull->TiersIdPays.'">';
		}
		else			
			print $bull->TiersPays;
		
		print '</tr>';
		$form = new Form($this->db);
		print '<tr><td>'.$langs->trans('Source').'</td><td>';	
		if ($action == $MAJ_TIERS) 			
			$form->selectInputReason($bull->fk_origine,'TiersOrig','',1);
		else			
			print $bull->lb_origine;
		print '</td></tr>';
		print '<tr>';
		print '<td>'.$langs->trans("TiersLieuVacances").'</td><td>';
		if ($action == $MAJ_TIERS) 
			print '<input class="flat"  type="text" name="Villegiature" value="'.$bull->Villegiature.'">';
		else			
			print $bull->Villegiature;
		print '</td></tr>';
		unset ($form);
			
		/*for ($i=0;$i<$nblig; $i++) 
			print '<tr><td>&nbsp;</td></tr>';	
		*/
		if ($bull->regle < $bull->BULL_ARCHIVE) {
			print '<tr><td colspan=2 align=center>';
			if ($bull->type == 'Loc') print '<input type="hidden" name="id_contrat" value="'.$bull->id.'">';
			elseif ($bull->type == 'Resa') print '<input type="hidden" name="id_resa" value="'.$bull->id.'">';
			else print '<input type="hidden" name="id_bull" value="'.$bull->id.'">';
			if ($action == $MAJ_TIERS) {
				print '<input type="hidden" name="action" value="'.$ENR_TIERS.'">';
				if (empty($bull->TiersTel) and empty ($bull->TiersMail)) $css="disabled";
				else $css="";
				print '<input class="button" id="EnrTiers" action="ENR_TIERS" type="submit" value="'.$langs->trans("BtEnregistrer").'" '.$css.'><br>';
			}
			else {
				print '<input type="hidden" name="action" value="'.$MAJ_TIERS.'">';
				print '<input class="button" action="'.$MAJ_TIERS.'" type="submit" value="'.$langs->trans("BtModifTiers").'"><br>';
			}
			print '</td ></tr>';	
		}
		print '</tbody></table>'; /*id="Niv4_Tiers"*/	
		print '</td></tr></tbody></table>'; /*  id=Niv3_tiers>*/
		
		print '</div>';
		print '</td></tr>';
		print '</table>';/* id=Niv2_Tiers*/
			print '</form>';
		unset ($wfctcomm);
	} // afficheTiers	
	function AfficheBulletin($nblig=1)
	{	
		global $action, $id_contrat, $id_bull, $langs, $bull, $user, $fl_BullFacturable, $BULLFacturable, $BULLNonFacturable;
		global  $TYPE_SESSION;
		global $TypeSessionCli_Agf;

		// Incohérence - Exist Paiement et BUll réputé nonfacturable
		if (($bull->ExistPmtNeg() or  $bull->RecupReglement() or  $bull->TotalPaimntStripeNonEncaisse() == 0) and $bull->facturable == 0) {
				//$bull->update_champs('facturable', 1);
				//$bull->facturable = 1;
		}
	
		$w = new CglLocation($this->db);		
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$wfcomDol = new  CglFonctionDolibarr ($this->db);
		
		print '<table  id=Niv2_Bulletin  width=100%>';
		print '<tr>';
		
		// PARAGRAPHE
		if ($bull->type == 'Loc') $wfctcomm->AfficheParagraphe("ContratLoc", 1);
		elseif ($bull->type == 'Insc')  $wfctcomm->AfficheParagraphe("Bulletin", 1);
		elseif ($bull->type == 'Resa')  $wfctcomm->AfficheParagraphe("CglResa", 1);
		print '</tr><tr><td>';
		print '<div class="tabBar" style="background-color:'.$langs->trans("ClPaveBullAff").'">';
		print '<table id=Niv3_Bulletin  width=100%><tbody><tr>';
		print '<td><table id=Niv4_Bulletin ><tbody><tr>';
		
		// NUMERO
		print '<td>'.$langs->trans("N_Bull").'</td><td  >'.$bull->ref.'</td>';
		
		print '</tr><tr><td>&nbsp;</td></tr><tr>';
		
		// STATUT
		$statut = $bull->transStrStatut();
		print '<td>'.$langs->trans("LbStatut").'<td><b>'.$statut.'</b></td>';
		print '</tr><tr><td></td>';
		print '</tr><tr><td>&nbsp;</td></tr><tr>';
		
		// ETAT DU REGLEMENT
		if ($bull->type <> 'Resa') {
			$regle = $bull->transStrRegle();
			print '<td>'.$langs->trans("Reglement").'</td><td  ><b>'.$regle.'</b></td>';
			print '</tr><tr><td>&nbsp;</td></tr><tr><td></td>';
			//$wfk = $bull->type_session_cgl - 1;  // valeur reele de fk_type_session
			//$wfkflipflop = ($wfk == 1) ?  0:1;
		}
		print '</tr>';
		
		// Etat facturation
		if ($bull->type <> 'Resa' and !empty($bull->fk_facture)) {			
			print '<td>'.$langs->trans("BuLoFacture").'</td><td  >';
			print $this->getNomUrl("object_company.png", 'Facture',0,$bull->fk_facture) ;
			print '&nbsp<b>'.$bull->facnumber.'</b></td>';
			print '</tr><tr><td>&nbsp;</td></tr><tr><td></td>';
			print '</tr><tr><td>&nbsp;</td></tr><tr><td></td>';
			//$wfk = $bull->type_session_cgl - 1;  // valeur reele de fk_type_session
			//$wfkflipflop = ($wfk == 1) ?  0:1;
		}
		print '</tr>';
		
		// TYPE DE BULLETIN
		/*if ( $bull->type_session_cgl == 1) $valueInverse = 'Individuel';
			else $valueInverse = 'Groupe';
		if ($bull->statut == 0)
		{
			print '<td>';
			if ($bull->type == 'Loc')
				print '<a href="'.$_SERVER["PHP_SELF"].'?id_contrat='.$bull->id.'&amp;action='.$TYPE_SESSION.'&amp;type='.$bull->type.'">';
			else
				print '<a href="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'&amp;action='.$TYPE_SESSION.'&amp;type='.$bull->type.'">';
			print img_picto($langs->trans("Activated"),'switch_on').'</a>';
			print '</td><td>';
		}
		else print '<td colspan=2 align="center"><b>';
		//if ( $bull->type_session_cgl == 2) print '&nbsp&nbsp'.$langs->trans('BullIndividuel');
		//else print ''.$langs->trans('BullGroupe');
		//print '</b>&nbsp';
		//if ($bull->type == 'Loc') print info_admin($langs->trans("DefGroupIndLoc"),1);
		//else print info_admin($langs->trans("DefGroupIndInsc"),1);
		print '</td></tr>';
		for ($i=0;$i<$nblig; $i++) 
			print '<tr><td>&nbsp;</td></tr>';	
		*/	
			
	
		
		// INFO de paiement negatif
		if ($bull->type <> 'Resa') {
			print '<tr><td align="center" colspan=2>';
			$i = $bull->ExistPmtNeg() ;
			if ($i > 0) $this->AffPmtNeg($i);
			print '</td></tr>';	
		}	

		// BU/LO technique ou facturé
		// Active ou désactive l'affichage des partie financières du BU/LO et le bouton de création			

	
	
				// INFO d'ANNULATION
		print '<tr><td align="center" colspan=2>';
		if (($bull->regle == $bull->BULL_ARCHIVE or  !empty($bull->abandon)) AND $bull->statut <> $bull->BULL_ANNULCLIENT) 
			print '&nbsp;<b>'.$langs->trans("TiInfoArchive").'</b> : '.$bull->abandon;
		else  $this->AfficheBtAnnulation(); 
		print '</td></tr><tr><td><p></td></tr>';	
		print '</td></tr><tr><td><p></td></tr>';
	// BU/LO technique ou facturé
		if ($bull->type == 'Insc' or $bull->type == 'Loc')  {			
			if ($bull->type == 'Insc') 	$wtexte = "bulletin";				
			elseif ($bull->type == 'Loc') $wtexte = "contrat";
			if ($bull->facturable == 1) {
				$waction = $BULLNonFacturable;
				$libelle = $langs->trans("BulletinFacturable", $wtexte) ;
			}
			elseif ($bull->facturable == 0) {
				$waction = $BULLFacturable;
				$libelle = $langs->trans("BulletinNonFacturable", $wtexte) ;
			}
			print '<tr ><td colspan = 2 align="center" ><b>'.$libelle.'</b>';
			// non modifiable s'il existe des paiements ou demande paiements Stripe
				if ($bull->type == 'Insc') $texteid = "id_bull";
				elseif ($bull->type == 'Loc') $texteid = "id_contrat";
			// Incohérence - Exist Paiement et BUll réputé nonfacturable
			if (!$bull->ExistPmtNeg() and  !$bull->RecupReglement() and  $bull->TotalPaimntStripeNonEncaisse() == 0) {
				print '<a class="butAction"   href="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$bull->id.'&action='.$waction.'">'.$langs->trans("Modify").'</a>';
			}
			elseif ($bull->facturable == 0)
				print '<a class="butAction"   href="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$bull->id.'&action='.$waction.'">'.$langs->trans("Modify").'</a>';
			else 
					// Si paiement exist - si paiement négatif exist - si demande Pmt Stripe > 0 - 
					print '<a class="butActionRefused" href="" title="'.$langs->trans("LbNonModifcarPaye", $wtexte).'">'.$langs->trans("Modify").'</a>';
			
			print '</td>';

			print '</tr><tr><td><p></td></tr>';	
		}
	
		print '</td></tr></tbody></table>';
		print '</td></tr></tbody></table>';
		print '</div >';
		print '</td ></tr>';
		print '</table>';
		unset($wfctcomm);
	} // AfficheBulletin
	function AfficheTiersBullInfo()
	{
		global $action, $id_contrat, $langs, $bull, $conf, $Refdossier;
		global $TypeSessionCli_Agf;
		
		if ($bull->type == 'Loc') {			
			require_once('class/html.formlocation.class.php');
			$w = new FormCglLocation($this->db);
			$taille='50%';
		}
		elseif ($bull->type == 'Resa') {			
			require_once('class/html.formreservation.class.php');
			$w = new FormCglReservation($this->db);
			$taille='30%';
		}
		$wf = new FormCglCommun($this->db);
		//		print '<table bgcolor="#EDEDED">';
		print '<table id=Niv1AffTiersInfo width=100%><tbody></tr><tr><td width=25%>';
			//print '<table  id=Niv2AffTiers><tbody><tr><td rowspan=2 >';
			$wf->AfficheTiers(2); 
			print '</td>';
			print '<td width=25%> ';
			$wf->AfficheBulletin(3);
			print '</td>';
			print '<td  width='.$taille.'>';
				
		//	print '</td>';
		//	print '</tr></tbody></table id=Niv2AffTiers>';
			
		//print '</td >';
		if ($bull->type == 'Loc')	$w->SaisieLocGlobal();
		elseif ($bull->type == 'Resa')	$w->SaisieResaGlobal();
		print '</td></tr></tbody></table>'; /* id=Niv1AffTiersInfo*/
		
		if ($conf->cahiersuivi)  {	
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
		
	} /*AfficheTiersBullInfo()*/
	function AfficheRemise()
	{		
		global $langs;
		
		// Fonction java pour affichage suivant choix 

		$w=new CglFonctionCommune ($this->db);	
		
		$txtRemPrctg = $langs->trans('RemPrctg');
		$txtRemPrctg = substr($txtRemPrctg, 0, strlen($txtRemPrctg) -1);
		$txtRemRaison = $langs->trans('RemRaison');
		$txtRemRaison = substr($txtRemRaison, 0, strlen($txtRemRaison) -1);
		$txtAlerteLignesSelect = $langs->trans('AlerteLignesSelect');
		$txtAlerteLignesSelect = substr($txtAlerteLignesSelect, 0, strlen($txtAlerteLignesSelect) -1);
		$txtBtModRemPartPrct = $langs->trans('BtModRemPartPrct');
		$txtBtModRemPartPrct = substr($txtBtModRemPartPrct, 0, strlen($txtBtModRemPartPrct) -1);
		$txtRemFix = $langs->trans('RemFix');
		$txtRemFix = substr($txtRemFix, 0, strlen($txtRemFix) -1);
		$txtRemRaisonFixe = $langs->trans('RemRaisonFixe');
		$txtRemRaisonFixe = substr($txtRemRaisonFixe, 0, strlen($txtRemRaisonFixe) -1);
		$txtBtModRemPartFix = $langs->trans('BtModRemPartFix');
		$txtBtModRemPartFix = substr($txtBtModRemPartFix, 0, strlen($txtBtModRemPartFix) -1);
		
				
		
		
		print '<tr><td colspan=2>';

		print "<script language='javascript' type='text/javascript'>
		function FctAffRem(o) {			
			if  (document.getElementById('selectRaisRemGen').options[document.getElementById('selectRaisRemGen').selectedIndex].text.indexOf('%') > 0) { 
				document.getElementById('divTilibRaisonAide').innerHTML = '';
				document.getElementById('divtimtt').innerHTML = '".$txtRemPrctg."';
				document.getElementById('divTilibRaison').innerHTML = '".$txtRemRaison."';		
				document.getElementById('divrempourcent').innerHTML = '".$txtAlerteLignesSelect."';
				document.getElementById('FctBtRemParticipation').value = '".$txtBtModRemPartPrct."';	
			}
			else { 
				document.getElementById('divrempourcent').innerHTML = '';
				document.getElementById('divtimtt').innerHTML = '".$txtRemFix."';
				document.getElementById('divTilibRaison').innerHTML = '".$txtRemRaison."';	
				document.getElementById('divTilibRaisonAide').innerHTML = '".$txtRemRaisonFixe."';
				document.getElementById('FctBtRemParticipation').value = '".$txtBtModRemPartFix."';
			}			
		}		
		</script>";

		
		print $this->select_nomremise('0','RaisRemGen', 0, 'onchange="FctAffRem(this)"');
		print '</td></tr><tr><td colspan=2  >'; 
		print '<div id="divrempourcent" ><b>'.$langs->trans("AlerteLignesSelect").'</div>'; 
		print '</td></tr><tr><td  width=20%>';
		print '<div name="divtimtt" id="divtimtt" >'.$langs->trans("RemPrctg").'</div>'; 
		print '</td><td  width=80%>';
		print '<input class="flat"  value="" type="text" name="mttremisegen" >';
		print '</td></tr><tr><td  >';
		print '<div id="divTilibRaison">'.$langs->trans("RemRaison").'</div>&nbsp;'; 
		print '</td><td>';
		print '<input class="flat"  value="" type="text" size="60px" name="textremisegen" >';
		print '</td></tr><tr><td colspan=2>';
		print '<div name="divTilibRaisonAide" id="divTilibRaisonAide" ></div>'; 
		print '</td></tr><tr><td colspan=2>';
		print '<div align="center"><input class="button" id=FctBtRemParticipation name="FctBtRemParticipation" type="submit" value="'.$langs->trans('BtModRemPartPrct').'" align="right" ></div>';
		//print '</td></tr';
		//print '</table id=Rem>';

} /*AfficheRemise()*/

	/*
	* Affiche le bouton suppression des lignes de détails dans inscription/location
	*
	*	@retour 	néant
	*/
	function AfficheDelLigneDetail ()
	{
		global $langs, $bull;
		
		if ($bull->type == 'Insc') 		$libtext = $langs->trans('BtModDelPart');
		else if ($bull->type == 'Loc') 	$libtext = $langs->trans('BtModDelMat');
		
		//$wf->AfficheParagrapheCol("TiDelParticipation", 3);
		print '<div align="center"><input class="button" name="FctBtDelParticipation" type="submit" value="'.$libtext.'" align="right" ></div>';
			
	}//AfficheDelLigneDetail
	
	/*
	* Affiche le bouton Saisie en masse des info annexes des lignes de détailes,  dans inscription/location
	*
	*	@retour 	néant
	*/

	
	function AffichePoidsTaille()
	{
		global $bull, $langs;
		$nb = 0;
		if (!empty($bull->lines )){
			foreach ($bull->lines as $line) {
				if ($line->type_enr <> 0 or $line->action == 'S' or $line->action == 'X') continue;
				$nb++;
			} // Foreach
		}
		if ($bull->ExistActivite() ) {	
			$urlcgl_PoidsTaille = DOL_MAIN_URL_ROOT."/public/cglinscription/cgl_participations.php?id=".$bull->id;
			// On met des bouton supplémentaire sur les gros bulletins, sinon, l'enregistrment n'est pas possible
			if		($nb > 	110) $limitlig = 110;
			else		$limitlig = $nb;	
			if ($bull->type == 'Insc') 	{
				$libtext = $langs->trans('BtAffPoidsTaille');
			}
			else if ($bull->type == 'Loc') {
				$libtext = $langs->trans('BtAffVeloTaille');
			}
				
			$url = DOL_MAIN_URL_ROOT.'/custom/cglinscription/saisieParticipations.php?id='.$bull->id.'&Dolibarr=oui.&limitlig='.$limitlig.'&limitdeb=0';
			print '<a class=button href="'.$url.'">'. $libtext.'</a>';
			$limitdeb = 0;
			while ($limitlig < $nb) {
				$limitdeb = $limitlig;
				$limitlig +=110;
				if ($bull->type == 'Insc') 	{
					$libtextSuite = $langs->trans('BtAffPoidsTailleSuite', $limitdeb, min($nb, $limitlig));
				}
				else if ($bull->type == 'Loc') {
					$libtextSuite = $langs->trans('BtAffVeloTailleSuite', $limitdeb, min($nb, $limitlig));
				}
					

				$url = DOL_MAIN_URL_ROOT.'/custom/cglinscription/saisieParticipations.php?id='.$bull->id.'&Dolibarr=oui.&limitdeb='.$limitdeb.'&limitlig='.$limitlig;
				print '<a class=button href="'.$url.'">'. $libtextSuite .'</a>';
			}
		}

	} // AffichePoidsTaille

	function RaisonPaiementNegatif( $id_bulldet, $PaimtNeg)
	{
		global $bull, $langs, $PAIE_CONFNEGATIF;
		global $PaimtMode, $PaimtOrg, $PaimtNomTireur, $PaimtCheque, $PaimtNeg, $PaimtMtt;
		global $PaimtDate;
		global  $closePaiement, $arrayreasonsl;
		$form = new Form($db);
  
		$formquestion=array();
		$question =  $langs->trans('PmtNeg');
		$html_name = $PAIE_CONFNEGATIF;
		//$text = $langs->trans('BtEnregistrer');
		$text = '';
		if ($bull->type == 'Insc') {
			$id_li = 'id_bull';
		}
		elseif ($bull->type == 'Loc') {
			$id_li = 'id_contrat';			
		} 			

		$url = $_SERVER['PHP_SELF'].'?'.$id_li.'='.$bull->id.'&type='.$bull->type;
		$url .= '&PaimtMode='.$PaimtMode.'&PaimtOrg='.$PaimtOrg.'&PaimtNomTireur='.$PaimtNomTireur.'&PaimtCheque='.$PaimtCheque.'&PaimtNeg='.$PaimtNeg.'&PaimtMtt='.$PaimtMtt;
		$url .= '&PaimtDate='.$PaimtDate;

		$formquestion = array(
			 'text' => $langs->trans("ConfirmCancelPaieQuestion"),
			array('type' => 'text','name' => 'PaimtNeg','value' => $PaimtNeg,'size' => '70')
		);				
		$formconfirm = $form->formconfirm($url, $question, $text, $html_name , $formquestion, "yes",1);
		print $formconfirm;	
	
	} //RaisonPaiementNegatif

	function AffPmtNeg($nb)
	{
		global $langs, $bull;
		
		print '<b>'.$langs->trans("PmtNeg").'</b>';
				print '</td></tr><tr><td colspan=2>';
			$i = 0;
			if (!empty($bull->lines)) {
				foreach ($bull->lines as $line) {
					if ($line->type_enr == 1 and $line->action <> 'X' and  $line->action <>'S' and $line->montant <0) {
						if ($nb > 1)  
							print '<i><u>'.$langs->trans("NumPaiement").$i."</u></i>: ";					
						if (empty($line->pmt_neg)) $wtemp = $langs->trans("NonRensg");
						else $wtemp = $line->pmt_neg;	
							
						print $wtemp;
						print '</td></tr><tr><td colspan=2>';
						$i++;
					}
				} // foreach
			}
		print '</td></tr><tr><td>';
		
	} // AffPmtNeg
	
	
	/*
	* obsolete
	*/
	
	function PaveObservPriv()
	{
		global $langs,  $bull;
		if ($bull->type == 'Loc') $this->PaveObservPrivLoc();
		else $this->PaveObservPrivInsc();
		
	}//PaveObservPriv
	
	/*
	* obsolete
	*/	
	function PaveObservPrivLoc()
	{
		global $langs, $MOD_LOCINFO, $action, $bull;
		global $ActionFuture;
	
		$wfctcomm = new FormCglFonctionCommune($this->db);
	
		if (!empty($ActionFuture)) $bull->ActionFuture = $ActionFuture;
		$affinfobull = info_admin($langs->trans("DefLibelle"),1);
		print '<tr>';	
		$wfctcomm->AfficheParagraphe("TiSuivi", 4, $affinfobull);
		print '</tr><tr><td>';	
		$taille = 80;
		print '<div class="tabBar" >';
		print '<table width=100%><tbody><tr>';
		/* print '<td width='.$taille.'%>';
		$taille = 160;
		print '<i>'.$langs->trans("InfoPrive").'</i>'; print info_admin($langs->trans("DefLibelle"),1);
		print '</td></tr>';	
		print '<tr><td>';	
//		if ($action == $MOD_LOCINFO) 			{			
			print '<textarea  cols="'.$taille.'"  rows="'.ROWS_1.'" wrap="soft" name="InfoPrive">';
			print $bull->ObsPriv.'</textarea>';
//		}
//		else
//			print 	$bull->ObsPriv;
				print '</td></tr>';	
		
		
		print '<tr><td>';	
		print '<i>'.$langs->trans("InfoActionFuture").'</i>';print info_admin($langs->trans("DefLibelle"),1);
		print '</td></tr>';
		*/		
		print '<tr><td>';			
			print '<textarea  cols="'.$taille.'"  rows="'.ROWS_1.'" wrap="soft" name="ActionFuture">';	
			print $bull->ActionFuture.'</textarea>';
		print '</td><td>';	
		print '</td></tr></tbody></table>'; /* id=NivE_InfoPriv*/
		print '</div>';
		print '</td></tr>';
		unset($wfctcomm);
	}//PaveObservPrivLoc
	
	/*
	* obsolete
	*/
	function PaveObservPrivInsc()
	{
		global $langs, $MOD_LOCINFO, $ENR_LOCINFO, $action, $bull;
		
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$affinfobull = info_admin($langs->trans("DefLibelle"),1);
		print '<tr>';	
		$wfctcomm->AfficheParagraphe("Info", 1,$affinfobull );
		print '</tr><tr><td>';	
		$taille="150";
		print '<div class="tabBar" style="background-color:'.$langs->trans("ClPaveSaisie").'">';
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'">';
		print '<table id=NivE_InfoPriv width=100%><tbody><tr>';
	
		print '<td>';			
			print '<textarea  cols="'.$taille.'"  rows="'.ROWS_3.'" wrap="soft" name="ActionFuture">';	
			print $bull->ActionFuture.'</textarea>';
		print '</td></tr>';	
		print '<tr><td  align=center>';
			print '<input type="hidden" name="action" value="'.$ENR_LOCINFO.'">';
			print '<input class="button"  type="submit" value="'.$langs->trans("BtEnrInfoPriv").'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';		
		
		print '</td></tr></tbody></table>';/* id=NivE_InfoPriv*/
		print '</form>';
		print '</div>';
		print '</td></tr>';
		unset($wfctcomm);
		
	}//PaveObservPrivInsc
	function AfficheBtAnnulation()
	{	
		global $bull, $langs, $BUL_ANNULER; 
		if ($bull->statut == $bull->BULL_ENCOURS and !($bull->regle == $bull->BULL_ARCHIVE))			{
				print '<form method="POST" name="ValideBull" action="'.$_SERVER["PHP_SELF"].'">';
				print '<input type="hidden" name="action" value="'.$BUL_ANNULER.'">';
				print '<input type="hidden" name="type" value="'.$bull->type.'">';
				if ($bull->type == 'Loc') $idtype = 'id_contrat';
				if ($bull->type == 'Insc') $idtype = 'id_bull';
				elseif ($bull->type == 'Resa')  $idtype = 'id_resa';
				print '<input type="hidden" name="'.$idtype.'" value="'.$bull->id.'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				if ($bull->regle == $bull->BULL_ENCOURS) {
					if ($bull->type == 'Loc') 	print '<input type="submit" class="button" value="'.$langs->trans("AnnCntCours").'">';	
					else print '<input type="submit" class="button" value="'.$langs->trans("AnnBullCours").'">';
				}
				print '</form>';
			}
			else			
				print '</tr><tr><td>&nbsp;</td></tr><tr>';
	}//AfficheBtAnnulation

 	function AfficheLigParagraphe($titre, $colspan, $affinfobull = '', $parametre='')
	{
		global $langs;
		
		if ($titre) 
		{
			print '<p class="nobordernopadding hideonsmartphone" align="left" '.$parametre.' ';
			if ($colspan > 1) print 'colspan='.$colspan;
			print '>'.img_picto('','title.png', '', 0).'&nbsp;';
			//print '<span style="font-size:14px; font-weight:bold">'.$langs->trans($titre).'</span></td>';
			print '<span style="font-size:12px; font-weight:bold">'.$langs->trans($titre).'</span>';
			if (!empty($affinfobull)) print $affinfobull;
			print '</p >';
		}
		
	} //AfficheLigParagraphe
	
	
 	function AfficheParagrapheCol($titre, $colspan=1, $parametre='', $affinfobull='')
	{
		global $langs;
		
		if ($titre) 
		{
			print '<td class="nobordernopadding hideonsmartphone" width="40" align="left" valign="middle" '.$parametre.' ';
			if ($colspan > 1) print 'colspan='.$colspan;
			print '>'.img_picto('','title.png', '', 0).'&nbsp;';
			//print '<span style="font-size:14px; font-weight:bold">'.$langs->trans($titre).'</span></td>';
			print '<span style="font-size:12px; font-weight:bold">'.$langs->trans($titre).'</span>';
			if (!empty($affinfobull)) print $affinfobull;
			print '</td>';
		}
		
	} //AfficheParagrapheCol
	function AfficheEcranEnvironnement($titre, $flag=0, $type='Insc')
	{
		global $langs;
		if ($flag==1) 
		{
			if ($type == 'Loc') {
				llxHeader('',$langs->trans('Lcgllocation'));
				$help_url='FR:Module_Location';
			}
			elseif ($type == 'Insc') {
				llxHeader('',$langs->trans('Lcglinscription'));
				$help_url='FR:Module_Inscription';
			}
			else if ($type == 'Resa') {
				llxHeader('',$langs->trans('Lcglreservation'));
				$help_url='FR:Module_Reservation';
			}
		}
		else
		{
			$title=$langs->trans($titre);
			print_barre_liste($title, $page, $_SERVER["PHP_SELF"]);
		}
	} /*AfficheEcranEnvironnement*/
    /*
	* Affiche la liste des paiements du BU/LOG 
	*/
	function AffichePaiement($action, $id, $id_det, $type, $texteid, $textedet)
	{
		global   $ACT_SUP_PAIMT, $ACT_SEL_PAIMT;
		global $id_paimt, $conf;
		global $id_contrat, $id_bull, $langs, $bull, $id_contratdet, $id_bulldet, $db, $bc;
		
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$wfctcomm->AfficheParagraphe("ListPaiemt", 1);
		print '</tr>';
		print '<a name="AncrePaiement" id="AncrePaiement"></a>';
		print '<tr><td width=100%>';
		$form=new Form($this->db);
		$w=New CglFonctionCommune($this->db);
			// table facturation
			print '<table width=100% id=Niv1_Paiement width="100%" ><tbody><tr><td>';
			print '<table  id=Niv2_Paiement  width="100%" ><tbody><tr>';
				
			print '<td colspan=1 width=60%>';
				
				// table Corps Facturation - Col1
				print '<table id=Niv3_Paiement width=100%><tbody><tr><td width=100%>';
				
					// table Liste Facturation 
					print '<table  id=Niv4_Paiement class="liste" width=95%><tbody><tr><td>';
						// affiche la barre grise des champs affichÃ©s
/*						print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?'.$texteid.'#AncrePaiement">';
						print '<input type="hidden" name="action" value="'.$action.'">';
						print '<input type="hidden" name="'.$textedet.'" value="'.$id_det.'">';
						print '<input type="hidden" name="token" value="'.newtoken().'">';
						print '<input type="hidden" name="token" value="'.newtoken().'">';
*/
						//print '<tr class="liste_titre"><th class="liste_titre" colspan=6>'.$langs->trans("ListPaiemt").'</th></tr>';
						//print '</td></tr><tr><td>&nbsp;';
						print '<tr class="liste_titre">';
							print '<td>'.$langs->trans("&nbsp; &nbsp;  &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp;  &nbsp; ").'</td>';
							print '<td>'.$langs->trans("ModePaiement").'</td>';
							print '<td>'.$langs->trans("Org").'</td>';
							print '<td>'.$langs->trans("Tireur").'</td>';
							print '<td>'.$langs->trans("Montant").'</td>';
							print '<td colspan=2>'.$langs->trans("Date").'</td>';
						print '</tr>';
						// zones de saisies
						// Boucle sur chaque ligne 	
						$j=100; 			
						$var = false;
						if (!empty($bull->lines)) {						
							foreach ($bull->lines as $line) {
								if ($line->type_enr == 1 and (empty($line->action) or  ($line->action != 'S' and $line->action != 'X')))	{	
									$line->rangecran=$j;
									$var = !$var;
									print "<tr $bc[$var]> ";
										//if ($bull->regle < $bull->BULL_ARCHIVE ) 	{
										print '<td>';
										if ($line->id_mode_paiement <> $conf->global->STRIPE_PAYMENT_MODE_FOR_PAYMENTS  
												or ( $line->id_mode_paiement == $conf->global->STRIPE_PAYMENT_MODE_FOR_PAYMENTS and empty($line ->pmt_StripeAut))) {
											// Pouvoir modifier seulement les paiements saisis manuellement (donc Hor mode paiement Stripe ou mode paiement Stripe marqué automatique
											 if ($line->pmt_rappro  == 0 and  $line->pmt_depose == 0 ) {
												print '<a href="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$bull->id.'&action='.$ACT_SEL_PAIMT.'&'.$textedet.'='.$line->id.'#AncrePaiement">'.img_edit().'</a>';
													/*if ($bull->regle < $bull->BULL_ARCHIVE ) 	
														//if ($bull->statut == $bull->BULL_ENCOURS)*/
												print '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$bull->id.'&action='.$ACT_SUP_PAIMT.'&'.$textedet.'='.$line->id.'#AncrePaiement">'.img_delete().'</a>';
											}
											else {
												if ($line->pmt_rappro  <> 0) {
													$texte = 'rapproche';
													$texte = $langs->trans("InfoPmtRapproDepose",$langs->trans($texte));
												}
												elseif ( $line->pmt_depose <> 0) { 
													$texte = 'depose';
													$texte = $langs->trans("InfoPmtRapproDepose",$langs->trans($texte, $line->pmt_refbordereau));
												}
												else $texte = "";
												print info_admin($texte,1);
											}
										}
										print '</td>';
										print '<td>'.$line->mode_paiement	.'</td>';
										print '<td size="30">'.$line->organisme.'</td>';
										print '<td size="20">'.$line->tireur.'</td>';
										print '<td>'.$line->montant.'&nbsp;';									
										if (!empty($line->pmt_neg)) print info_admin($line->pmt_neg,1);
										print '</td>';
										print '<td>'.$w->transfDateFr($line->date_paiement).'</td>';
									print '</tr>';
									$j++;
								}
							}// fin de foreach
						}
					// Fin table Liste Facturation
//					print '</form></tbody></table>';/* id=Niv4_Paiement*/
					print '</tbody></table>';/* id=Niv4_Paiement*/
				// table facturation Col2	
				//print '</td><td width="50%">';	
				print '</td><td width=40%>';
		
						print '</td></tr><tr><td>&nbsp;';
				// fin table Corps Facturation
				print '</td></tr></tbody></table>';/* id=Niv3_Paiement*/
			// fin table facturation
			print '</td></tr></tbody></table>';/* id=Niv2_Paiement*/
			print '</td></tr></tbody></table>';/* id=Niv1_Paiement*/
			// affichage des acompte et avoir dispo
			print '</td></tr><tr>';
			$this-> AfficheAccompteClient ($bull->id_client);
		unset ($form);
		unset ($w);	
		unset($wfctcomm);		
			
	} /*AffichePaiement*/
	/*
	* Prépare le code html permettant d'afficher le signe correspondant au statut du BU/LO
	*
	* param int $statut 	priorité du BU/LO
	* param int $regle 		état de règlement  du BU/LO
	* param $type'Insc' ou 'Loc' ou 'resa'
	* retour string 	chaine html permetant l'affichage de l'icone correspondant au statut
	*/
	function AffichImgStatutBull($statut, $regle, $type, $fk_facture) 
	{
		global $bull;	
		$texte='';
		$img = '';
		if ($type == 'Loc') {
			if ($statut == $bull->BULL_ENCOURS) { $img=$bull->IMG_ENCOURS ; $texte=$bull->LIB_CNT_ENCOURS;}
			if ($statut == $bull->BULL_PRE_INSCRIT) { $img=$bull->IMG_PRE_INS ; $texte=$bull->LIB_PRE_INS;}
			elseif ($statut == $bull->BULL_CLOS and !empty($fk_facture) and $regle <> $bull->BULL_PAYE and $regle < $bull->BULL_FACTURE) {$img=$bull->IMG_CNT_FACT_INC; $texte=$bull->LIB_CNT_FACT_INC;}
			elseif ($statut == $bull->BULL_CLOS and !empty($fk_facture)) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACTURE;}
			/* elseif ($obj->statut == $bull->BULL_DEPART ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_DEPART;}
			elseif ($obj->statut == $bull->BULL_RETOUR ) { $img=$bull->IMG_RETOUR; $texte=$bull->LIB_RETOUR;} */
			elseif ($statut == $bull->BULL_VAL) {$img=$bull->IMG_VAL; $texte=$bull->LIB_VAL;}
			elseif ($statut == $bull->BULL_CLOS) {$img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
			elseif ($statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
			elseif ($statut == $bull->BULL_ANNULCLIENT ) { $img=$bull->IMG_ANNULCLIENT; $texte=$bull->LIB_ANNULCLIENT;}
			elseif ($statut == $bull->BULL_DEPART ) { $img=$bull->IMG_DEPART; $texte=$bull->LIB_DEPART;}
		}
		else if ($type == 'Insc') {
			if ($statut == $bull->BULL_ENCOURS) { $img=$bull->IMG_ENCOURS ; $texte=$bull->LIB_ENCOURS;}
			if ($statut == $bull->BULL_PRE_INS) { $img=$bull->IMG_PRE_INS ; $texte=$bull->LIB_PRE_INS;}
			elseif ($regle ==0 and $statut ==1 and !empty($fk_facture)) {$img=$bull->IMG_FACT_INC; $texte=$bull->LIB_FACT_INC;}
			elseif ($regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_FACTURE; $texte=$bull->LIB_FACT_INC;}
			elseif ($statut == $bull->BULL_INS) {$img=$bull->IMG_INS; $texte=$bull->LIB_INS;}
			elseif ($statut == $bull->BULL_CLOS) {$img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
			elseif ($statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
			elseif ($statut == $bull->BULL_ANNULCLIENT ) { $img=$bull->IMG_ANNULCLIENT; $texte=$bull->LIB_ANNULCLIENT;}
		}
		else if ($type == 'Resa') {		
			if ($statut == $bull->BULL_ENCOURS) { $img=$bull->IMG_ENCOURS ; $texte=$bull->LIB_ENCOURS;}
			elseif ($statut == $bull->BULL_VAL) {$img=$bull->IMG_INS; $texte=$bull->LIB_INS;}
			elseif ($statut == $bull->BULL_CLOS) {$img=$bull->IMG_CLOS; $texte=$bull->LIB_CLOS;}
			elseif ($statut == $bull->BULL_ABANDON ) { $img=$bull->IMG_ABANDON; $texte=$bull->LIB_ABANDON;}
			elseif ($statut == $bull->BULL_ANNULCLIENT ) { $img=$bull->IMG_ANNULCLIENT; $texte=$bull->LIB_ANNULCLIENT;}
		}
		
		if (empty($img) and empty($texte)) return '';
		else if (empty($img) and !empty($texte)) 
			return info_admin($texte,1);		
		elseif (!empty($texte))
			return '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
		else return $statut;	

	} // AffichImgStatutBull
	
		/*
	* Prépare le code html permettant d'afficher le signe correspondant à l'état financier du BU/LO
	*
	* param int $regle 		état de règlement  du BU/LO
	* param $type'Insc' ou 'Loc' ou 'resa'
	* retour string chaine html permetant l'affichage de l'icone correspondant au statut
	*/
	function AffichImgRegleBull( $regle, $type, $statut, $dated, $fk_facture, $abandon) 
	{
		global $bull;


		$texte='';
		$img = '';
		if ($type == 'Loc') {
			/*if ($regle == $bull->BULL_NON_PAYE  and $montantdu <> 0) { $img=$bull->IMG_NON_PAYE; $texte=$bull->LIB_NON_PAYE;}
			else*/
			if ($regle == $bull->BULL_NON_PAYE  ) {$img=''; $texte='';}
			elseif ($regle ==$bull->BULL_INCOMPLET) {$img=$bull->IMG_INCOMPLET; $texte=$bull->LIB_INCOMPLET;}
			elseif ($regle ==$bull->BULL_PAYE) {$img=$bull->IMG_PAYE; $texte=$bull->LIB_PAYE;}
			elseif ($regle ==$bull->BULL_SURPLUS) {$img=$bull->IMG_SURPLUS; $texte=$bull->LIB_SURPLUS;}
			elseif ($regle ==$bull->BULL_REMB) {$img=$bull->IMG_REMB; $texte=$bull->LIB_REMB;}
			elseif ($regle ==$bull->BULL_FACTURE) {$img=$bull->IMG_CNT_FACTURE; $texte=$bull->LIB_CNT_FACTURE;}
			elseif ($regle ==$bull->BULL_ARCHIVE) {$img=''; $texte='';}
			else { $img = ''; $texte = 'inconnu '. $regle;}			
		}
		else 
		{
			if ($regle == $bull->BULL_NON_PAYE  and ($statut == $bull->BULL_INS  and !empty($dated))) { $img=$bull->IMG_NON_PAYE; $texte = $bull->LIB_NON_PAYE;}
			elseif ($regle == $bull->BULL_NON_PAYE){ $img=''; $texte = '';}
			elseif ($regle ==$bull->BULL_INCOMPLET) {$img=$bull->IMG_INCOMPLET; $texte=$bull->LIB_INCOMPLET;}
			elseif ($regle ==$bull->BULL_PAYE or ($regle == $bull->BULL_ARCHIVE and !empty($fk_facture))) { $img=$bull->IMG_PAYE; $texte=$bull->LIB_PAYE;}
			elseif ($regle == $bull->BULL_FACTURE ) { $img=$bull->IMG_CNT_FACT_INC; $texte=$bull->LIB_CNT_FACT_INC;}
			elseif ($regle ==$bull->BULL_SURPLUS) {$img=$bull->IMG_SURPLUS; $texte=$bull->LIB_SURPLUS;}
			elseif ($regle ==$bull->BULL_REMB) {$img=$bull->IMG_REMB; $texte=$bull->LIB_REMB;}
			elseif (!empty($abandon)) { $img=''; $texte = '';}
			else { $img = ''; $texte = 'inconnu '. $regle;}
		}
		if (empty($img) and empty($texte)) 
			return '';
		elseif (empty($img) and !empty($texte)) 
			return info_admin($texte,1);		
		elseif (!empty($texte))
			return '<img border="0" title="'.$texte.'" alt="'.$texte.'" src="'.DOL_URL_ROOT.'/theme/eldy/img/'.$img.'">';
		else return $regle;	

	} // AffichImgRegleBull
	
	function AfficheEdition()
	{
		global $bull, $conf, $langs, $db, $type;
		$objet = new CglFonctionDolibarr($db);

		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre
		
		if ($type == 'Loc') {
			$texte=$langs->trans('Edicontrat');
			$modulesubdir = 'contratLoc/';
			$nomchamp= 'id_contrat';
			
		}
		elseif ($type == 'Resa') {
			$texte=$langs->trans('EdiResa');
			$modulesubdir = 'reservation/';			
			$nomchamp= 'id_resa';
		}
		
		// Documents generes
		//$filedir = $conf->cgllocation->dir_output .'/'. dol_sanitizeFileName($bull->ref);
		$filedir = $conf->cglinscription->dir_output ;
		$modulesubdir .= dol_sanitizeFileName($bull->ref);
		$urlsource = $_SERVER['PHP_SELF'] . '?'.$nomchamp.'=' . $bull->id.'';
		//$genallowed = $user->rights->cglinscription->creer;
		$genallowed = 1;
		//$delallowed = $user->rights->cglinscription->supprimer;
		//$delallowed = $user->rights->cgllocation->supprimer;
		print $objet->showdocuments('cglinscription',$modulesubdir, $filedir, $urlsource, $genallowed, $delallowed, $bull->modelpdf, 1, 0, 0, 28, '', '', '', $texte, $soc->default_lang);
			//print $this->show_bull( $file, $line);
		
		print '</div></div>';
		
	} /*AfficheEdition*/
	
	function Annuler()
	{
		global  $langs, $bull, $BUL_CONFANNULER, $type;
		// confirmation de suppression 
		$form = new Form($db);
		//$formquestion=array();
		$question =  $langs->trans('Annulation');
		if ($bull->type == 'Loc') {
			$textlang='ConfirmSupCnt';
			$lb_id = 'id_contrat';
		}
		elseif ($bull->type == 'Insc') {
			$textlang='ConfirmSupBull';
			$lb_id = 'id_bull';
		}
		elseif ($type == 'Resa') {
			$textlang='ConfirmSupResa';
			$lb_id = 'id_resa';
		}
		$text = $langs->trans($textlang , $bull->ref);
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'.$lb_id.'='.$bull->id.'&type='.$bull->type,$question,$text,$BUL_CONFANNULER,'','',1);
		print $formconfirm;
	} /*Annuler*/

	// Demande confirmation Annulation par Client
	function AnnuleParClient ()
	{
		global $bull;

		global $bull, $langs, $BUL_CONFANULCLIENT;
	
/*		if ($bull->TotalPaimnt() > 0 ) {
					$error++; setEventMessage($langs->trans("RefusAnul",$langs->transnoentitiesnoconv("Service")),'errors');
		} 
*/
		// confirmation d'abandon 	
		$form = new Form($db);
		$formquestion=array();
		$question =  $langs->trans('AnnulClient');
		if ($bull->type == 'Loc') {
			//	$text = $langs->trans('ConfirmAbnCnt').' '.$bull->ref;
			$lb_id = 'id_contrat';
		}
		elseif ($bull->type == 'Insc') {			
			$lb_id = 'id_bull';
		}		
		$text = $langs->trans ('ConfirmRefusAnulCl');
		$text .= " ".$langs->trans('ConfirmAnuCl');
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'.$lb_id.'='.$bull->id.'&type='.$bull->type,$question,$text,$BUL_CONFANULCLIENT,'','',1);
		print $formconfirm;
	} //AnnuleParClient
		
	function Abandon()
	{
		global $bull, $langs, $BUL_CONFABANDON;;
	
		if ($bull->TotalPaimnt() > 0 ) {
					$error++; setEventMessage($langs->trans("RefusAnul",$langs->transnoentitiesnoconv("Service")),'errors');
		} 
		// confirmation d'abandon 	
		$form = new Form($db);
		$formquestion=array();
		$question =  $langs->trans('Abandon');
		$text = '';
		if ($bull->TotalPaimnt() <> 0 ) {
			$text = $langs->trans ('ConfirmRefusAnul');
		}
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
	} //Abandon
	

	function AfficheBoutonValidation()
	{
		global $CNT_RESERVER, $CNT_PRE_RESERVER, $CNT_DEPART, $BUL_ANNULER, $MAJ_TIERS, $CNTLOC_CLOS;
		global $CNTLOC_DEPARTFAIT , $CNTLOC_REOUVRIR, $CNTLOC_DESARCHIVER, $RESA_REOUVRIR, $RESA_CLOS, $BUL_ABANDON  ,  $BUL_ANULCLIENT;
		global $ACT_PRE_INSCRIRE, $ACT_INSCRIRE, $BUL_DESARCHIVER, $PREP_MAIL, $PREP_SMS, $CNT_NONRESERVER;
		global $CNTLOC_DEPARTNONFAIT;
		global $id_client, $action, $langs, $bull, $conf;
		
		print '<div class="tabsAction" style="align:center">';
		// encapsulage se terminant après édition
		if ($bull->type == 'Loc') {
			$nomchamp = 'id_contrat';
			$type="contrat";
		}	
		elseif ($bull->type == 'Insc'){
			$nomchamp = 'id_bull';	
			$type="bulletin";		
		}	
		elseif ($bull->type == 'Resa') {
			$nomchamp = 'id_resa';
		}		

		if ('BOUTONINFOTIERS' == 'BOUTONINFOTIERS' ) 
		{		
			// BOUTON 	- Info Client
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?'.$nomchamp.'='.$bull->id.'&action='.$MAJ_TIERS.'" title="' . $langs->trans("TxtInfSupTiers") . '">' . $langs->trans('btInfSupTiers') . '</a></div>';
		}			
		if ('BOUTONMAIL' == 'BOUTONMAIL' ) 
		{
			// BOUTON COL 1b	- Envoyer un mail

			$langs->load("mails");
			if (empty($bull->TiersMail) and empty($TiersMail2))	 {
				print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans('SendMail').'</a></div>';
			}
			else  {
				$pasgMail = GETPOST('pasgMail', 'int');
				if (!isset($pasgMail) or empty($pasgMail)) $pasgMail = 1; else $pasgMail += 1;		
				if ($bull->type == 'Resa') $Etapeval =  'M1';
				else $Etapeval = CglStripe::STRIPE_MAIL_GENERAL;
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?'.$nomchamp.'='.$bull->id.'&amp;action='.$PREP_MAIL.'&amp;mode=init&amp;pasgMail='.$pasgMail.'&amp;etape='.$Etapeval.'#formmail" >';
				print $langs->trans('SendMail').'</a></div>';
			}
		}
		if ('BOUTONSMS' == 'BOUTONSMS1' ) 
		{
			if ($conf->ovh->enabled) {
	//			$ret = strpos( reset($emails), 'Pas d');
	//			if ($ret > 0) {
	//				$IsNoMobile = true ;
	//			}
	//			else {
					$IsNoMobile =false ;
	//			}
				$langs->load("mails");
				if ($IsNoMobile)	        
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoMobilePhone")).'">'.$langs->trans('BtEnvoiSMS').'</a></div>';
					else {			
					if ($bull->type == 'Resa') $Etapeval =  'S1';
					else $Etapeval = CglStripe::STRIPE_SMS_GENERAL;
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?'.$nomchamp.'='.$bull->id.'&amp;action='.$PREP_SMS.'&amp;mode=init&amp;pasgMail='.$pasgMail.'&amp;etape='.$Etapeval.'#AncreMailSms" >';
					print $langs->trans('BtEnvoiSMS').'</a></div>';
				}
			}
		}
		if ('BOUTONREPRESERV' == 'BOUTONREPRESERV' ) 
		{	
			if ($bull->regle < $bull->BULL_ARCHIVE and $bull->statut == $bull->BULL_ENCOURS )
			{	
				// BOUTON COL 0.5	- PreReserve	
				if ($bull->type == 'Loc') {
					$libBt = $langs->trans('CntPreReserver');
					$actionbt = $CNT_PRE_RESERVER;
					$titre = $langs->trans("CntLbPreValider");
				}
				elseif ($bull->type == 'Insc') {
					$libBt = $langs->trans("BtPreInscrit");
					$actionbt = $ACT_PRE_INSCRIRE;
					$titre = $langs->trans("BuLbPreReserver");
				}
				if ($bull->ExistActivite())
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?'.$nomchamp.'='.$bull->id.'&action='.$actionbt.'#AncrePaiement" title="' . $titre . '">' . $libBt . '</a></div>';
				else
					print '<div class="inline-block butActionRefused"><a class="butActionRefused" href="' . $_SERVER['PHP_SELF'] . '?'.$nomchamp.'='.$bull->id.'&action='.$actionbt.'#AncrePaiement" title="' . $titre . '">' . $libBt . '</a></div>';
			}
		}
		if ('BOUTONRESERV' == 'BOUTONRESERV' ) 
		{
			if ($bull->regle < $bull->BULL_ARCHIVE and $bull->statut <= $bull->BULL_PRE_INS ) 
			{	
				if ($bull->type == 'Loc') {
					$TilibBt = $langs->trans("CntLbValider");
					$libBt = $langs->trans('CntValider');
					$actionbt = $CNT_RESERVER;
				}
				if ($bull->type == 'Insc') {
					$TilibBt = $langs->trans("BuLbInscrire");
					$libBt = $langs->trans('BulBtInscrire');
					$actionbt = $ACT_INSCRIRE;
					$actioninversebt=$ACT_NONINSCRIT;
				}
				else $titre = $langs->trans("ResaLbValider");

				if ($bull->ExistActivite())
					$classebouton = "butAction";
				else {
					$classebouton = "butActionRefused";
					if ($bull->type == 'Loc')
						$TilibBt = $langs->trans("CntLbInscrireRefused");
					elseif ($bull->type == 'Insc')
						$TilibBt = $langs->trans("BuLbInscrireRefused");
				}
			}
			elseif ($bull->regle < $bull->BULL_ARCHIVE and $bull->type == 'Loc' and   $bull->statut == $bull->BULL_VAL )
			{
					$classebouton = "butAction";
					$actionbt=$CNT_NONRESERVER;
					$libBt = $langs->trans('CntNonReserver');
					$TilibBt=$langs->trans('CntLbNonReserver');
			}
			elseif ($bull->regle < $bull->BULL_ARCHIVE and $bull->type == 'Insc' and   $bull->statut == $bull->BULL_INS )
			{
					$classebouton = "butAction";
					$actionbt=$CNT_NONRESERVER;
					$libBt = $langs->trans('BuNonInscrit');
					$TilibBt=$langs->trans('BuLbNonInscrit');
			}

			$this->affiche_bouton($classebouton, $nomchamp, $bull->id, $actionbt, $libBt, $TilibBt);	
	
		}
		if ('BOUTONDEPART_RETOUR' == 'BOUTONDEPART_RETOUR' ) 
		{// BOUTON COL 1c  - Depart Fait	
			if ($bull->type == 'Loc' and $bull->regle < $bull->BULL_ARCHIVE 
				and  $bull->statut >= (float)$bull->BULL_VAL
				and $bull->statut <= $bull->BULL_DEPART 
				and $bull->statut <> $bull->BULL_ENCOURS)
			{	
				$TilibBt = $langs->trans("CntLbDepartfait");
					$actionbt = $CNTLOC_DEPARTFAIT;
					$libBt = $langs->trans('CntDepartFait');
				if ($bull->statut == $bull->PRE_INSCRIT  )
				{
					$classebouton = "butActionRefused";
				}
				elseif ($bull->statut == $bull->BULL_VAL )
				{
					$classebouton = "butAction";
				} 
				elseif ( $bull->statut == $bull->BULL_DEPART ) 
				{
					$classebouton = "butAction";
					$libBt = $langs->trans('CntDepartNonFait');
					$TilibBt = $langs->trans("CntLbDepartNonFfait");
					$actionbt = $CNTLOC_DEPARTNONFAIT ;
				}  

				/*
				// BOUTON COL 3	- Retour
				if ($bull->statut >= $bull->BULL_VAL ) $temp ="butAction";
				else  $temp ="butActionRefused";
				print '<div class="inline-block divButAction"><a class="'.$temp .'" href="' . $_SERVER['PHP_SELF'] . '?'.$nomchamp.'='.$bull->id.'&action='.$CNT_RETOUR.'#AncrePaiement" title="' . $langs->trans("CntLbRetour") . '">' . $langs->trans('CntRetour') . '</a></div>';
				*/	
				$this->affiche_bouton($classebouton, $nomchamp, $bull->id, $actionbt, $libBt, $TilibBt);	
			}
		}
		if ('BOUTONCLORE_REOUVRIR' == 'BOUTONCLORE_REOUVRIR' ) 
		{	// BOUTON COL 4	- Clore ou Reouvrir
			if ( $bull->type == 'Loc' and $bull->regle < $bull->BULL_ARCHIVE)
			{	
				if ( $bull->statut > $bull->BULL_VAL and $bull->statut <= $bull->BULL_CLOS) 
				{
					if ($bull->statut == $bull->BULL_CLOS ){
						$classebouton = "butAction";
						$actionbt = $CNTLOC_REOUVRIR;
						$TilibBt = $langs->trans("CntLbReouvrir");
						$libBt = $langs->trans('Reouvrir');
					}
					elseif ($bull->statut == $bull->BULL_DEPART ) {
						$classebouton = "butAction";
						$actionbt = $CNTLOC_CLOS;
						$TilibBt = $langs->trans("CntLbClore");
						$libBt = $langs->trans('CntClore');
					}
					$this->affiche_bouton($classebouton, $nomchamp, $bull->id, $actionbt, $libBt, $TilibBt);	
				}
			}
			elseif (  $bull->regle == $bull->BULL_ARCHIVE) {
						$classebouton = "butAction";
						$actionbt = $CNTLOC_REOUVRIR;
						if ($bull->type == 'Loc') 
							$TilibBt = $langs->trans("CntLbDesarchiver");
						elseif ($bull->type == 'Insc') 
							$TilibBt = $langs->trans("BuLibDesarchiver");
						$libBt = $langs->trans('Reouvrir');
				$this->affiche_bouton($classebouton, $nomchamp, $bull->id, $actionbt, $libBt, $TilibBt);	
			}

			/*if ($bull->type == 'Resa') {
					if ($bull->statut == $bull->BULL_CLOS )
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?'.$nomchamp.'='.$bull->id.'&action='.$RESA_REOUVRIR.'" title="' . $langs->trans("ResaLbReouvrir") . '">' . $langs->trans('ResaReouvrir') . '</a></div>';
					elseif ($bull->statut >= $bull->BULL_VAL and $bull->statut <> $bull->BULL_CLOS )
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?'.$nomchamp.'='.$bull->id.'&action='.$RESA_CLOS.'" title="' . $langs->trans("ResaLbClore") . '">' . $langs->trans('ResaClore') . '</a></div>';
					elseif ($bull->statut < $bull->BULL_VAL )
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("ResaLbClore") . '">' . $langs->trans('ResaClore') . '</a></div>';
				}
				*/
			
		}
		if ('BOUTONANNULE_ABANDON' == 'BOUTONANNULE_ABANDON' ) 
		{// BOUTON COL 5	- Abandon/ Annulert/Client non venu (Annulé par client)
			if (  $bull->regle < $bull->BULL_ARCHIVE)
			{
				// si bulletin facturé ==> pas de bouton
				if (!empty($bull->fk_facture) and $bull->statut < $bull->BULL_CLOS) {					
					$classebouton = "butActionRefused";
					$libBt = $langs->trans('Abandon');
					$actionbt = "";
					$TilibBt = $langs->trans("LbBtFact", $type);
					$this->affiche_bouton($classebouton, $nomchamp, $bull->id, $actionbt, $libBt, $TilibBt);
				}
					// le bulletin/contrat non encore réservé peut être annulé sauf s'il y a des paiement
				elseif ( $bull->statut  < $bull->BULL_CLOS)
				{
					if ($bull->statut < $bull->BULL_PRE_INS )  
					{	
						$libBt = $langs->trans('BtAnnuler');
						$actionbt = $BUL_ANNULER;
						if ($bull->type == 'Loc') {
							$TilibBt = $langs->trans("CntAnnul");
						}
						elseif ($bull->type == 'Insc') {
							$TilibBt = $langs->trans("BullAnnul");
						}
					}	
					// Si le contrat n'est pas clos, il peut être abandonné
					elseif	($bull->statut < $bull->BULL_CLOS )	 
					{					
						$libBt = $langs->trans('BtAbandon');
						$actionbt = $BUL_ABANDON;
						if ($bull->type == 'Loc') 
							$TilibBt = $langs->trans("CntLbAbandon");
						elseif ($bull->type == 'Insc') 	
							$TilibBt = $langs->trans("BuLbAbandon");
					}
					
					
					// si Bulletin sans activité et paiement existant 			==> bouton Annulé par le Client
					//if (  $bull->TotalPaimnt() != 0  or ($bull->TotalPaimnt() == 0 and $bull->ExistPmtNeg()))
					if ($bull->TotalFac() == 0 and ($bull->TotalPaimnt() > 0 or $bull->ExistPmtNeg()))
					{					
						$classebouton = "butAction";
						$actionbt = $BUL_ANULCLIENT;	
						$libBt = $langs->trans('CntAnnuleClient');
						$TilibBt = $langs->trans("CntLbAnnuleClient");
					}
					// si bulletin avec paiement ==>  bouton grisé
					else if ($bull->ExistPmtNeg() or $bull->TotalPaimnt() > 0 ) 
					{	
						$classebouton = "butActionRefused";
						$libBt = $langs->trans('BtAbandon');
						//if ($bull->TotalFac() != 0 or $bull->ExistActivite() ) 
						//	$TilibBt = $langs->trans("InfoExistActivite", $type);
						//elseif ($bull->TotalFac() == 0  ) 
							$TilibBt = $langs->trans("InfoExistPaiement");
					}
					// si Bulletin sans activité et sans paiement  			==> bouton Abandon
					else  {					
						$classebouton = "butAction";
					}
				
					if ($bull->regle == $bull->BULL_ARCHIVE)
					{					
						$libBt = $langs->trans('CntDesarchiver');
						if ($bull->type == 'Insc') 
							$TilibBt = $langs->trans('BuLibDesarchiver');
						elseif ($bull->type == 'Loc') {
							$TilibBt = $langs->trans('CntLibDesarchiver');
							$actionbt = $BUL_DESARCHIVER;
						}
						$this->affiche_bouton($classebouton, $nomchamp, $bull->id, $actionbt, $libBt, $TilibBt);	
							print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?'.$nomchamp.'='.$bull->id.'&action='.$BUL_DESARCHIVER.'#AncrePaiement" title="' . $titre . '">' . $langs->trans('CntDesarchiver') . '</a></div>';
					}
					$this->affiche_bouton($classebouton, $nomchamp, $bull->id, $actionbt, $libBt, $TilibBt);	
				}				
			}
		}


		print '</form>';
		print '</div>';
		print '</div>';
		
		print '<p>&nbsp;</p>';		
		print '<p>&nbsp;</p>';		
		print '<p>&nbsp;</p>';		
	} /*AfficheBoutonValidation*/
	
	/*
	* Ordre d'affichage d'un bouton href
	*
	*	@param	$classe		Nom de la classe (ex butAction)
	*	@param	$nomchamp	Nom du champ de l'url à afficher si bouton activé
	*	@param	$valueid	Valeur de l'argument précedent
	*	@param	$actionbt	Action du bouton 
	*	@param	$libBt		Libellé affiché sur le bouton
	*	@param	$TilibBt	Message d'info sur hoover
	*
	*/
	function affiche_bouton($classe, $nomchamp, $valueid, $actionbt,$libBt, $TilibBt)
	{
		print '<div class="inline-block divButAction"><a class="'.$classe.'" href="' . $_SERVER['PHP_SELF'] . '?'.$nomchamp.'='.$valueid.'&action='.$actionbt.'" title="' . $TilibBt . '">' . $libBt . '</a></div>';
	} //affiche_bouton

	// Affiche els acompte inutilé du clietnt sauf ceuxx concernant ce bulletin
	function AfficheAccompteClient($id)
	{	
		global $langs , $bc, $bull;
		
		
		$wfctcomm = new FormCglFonctionCommune($this->db);		
		
		$w= New CglCommunLocInsc ($this->db);
		$resql = $w->SqlChercheRelationTiers ($id, 'ACOMPTE_DISPO');
		unset($w);

		
		if ($resql)			{
			$var=true;
			$num = $this->db->num_rows($resql);

			if ($num > 0)             {
				
				$texte = $langs->trans("LstAcompteAUtiliser") .  info_admin($langs->trans("InfoProcAcompte"),1);
				$wfctcomm->AfficheParagraphe($texte, 0);
				print '<tr><td>';

				print '<table class="noborder" width="50%">';

				print '<tr class="liste_titre" >';
				print '<td>'.$langs->trans("Date").'</td><td>'.$langs->trans("BuCnt").' </td>';
				print '<td>'.$langs->trans("Montant").' </td>';
				
				print '</tr>';
			}

			$i = 0;
			while ($i < $num)				{
				$objp = $this->db->fetch_object($resql);
				if ($objp->fid == $bull->fk_acompte) break;
				print "<tr ".$bc[$var].">";
				/*print '<td class="nowrap"><a href="propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a>'."\n";
				if ( ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 )
				{
					print " ".img_warning();
				}
				*/
				print '<td  >'.dol_print_date($this->db->jdate($objp->datef),'day')."</td>\n";
				print '<td>';
				print $this->getNomUrl("object_company.png", 'Facture',0,$objp->fid);
				print '&nbsp;'.$objp->ref.'</td>';
				print '<td>';
				print '&nbsp;'.price($objp->total_ttc).'</td>';
			

				//print '<td align="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
				print '</b></tr>';
				$var=!$var;
				$i++;
			}
			$this->db->free($resql);
			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($this->db);
		}
		unset($wfctcomm);
	} // AfficheAccompteClient

	function BtNelEncaissement($id, $type, $texteid, $fl_aff = 'complet')
	{
		global $langs, $conf, $bull;

		if ($fl_aff == 'complet')  {
			print '<div class="inline-block divButAction" width=30%><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?'.$texteid.'='.$id.'&amp;action='.$PREP_SMS.'&amp;mode=init&amp;pasgMail='.$pasgMail.'&BtEncais=CRE_ENCAIS&token='.newToken().'#AncrePaiement" >';
			print $langs->trans('NvEncais').'</a></div>';
		}
		
		if ($conf->stripe) {		 
			$pasgMail = GETPOST('pasgMail', 'int');
			if (!isset($pasgMail) or empty($pasgMail)) $pasgMail = 1; else $pasgMail += 1;		
			print '<div class="inline-block divButAction" width=30%>';
			if (!empty($bull->TiersMail) or !empty($bull->TiersMail2)) {
				$out = '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?'.$texteid.'='.$id.'&amp;action='.$PREP_MAIL.'&amp;mode=init&amp;pasgMail='.$pasgMail;
				$out .=  '&BtStripeMail=STRIPE_MAIL&token='.newToken().'&etape='.CglStripe::STRIPE_MAIL_STRIPE.'#AncreMailSms" >';
				print $out;
				print $langs->trans('NvStripeMail').'</a></div>';
			}
			else  {
				$out = '<a class="butActionRefused" href="" >';
				print $out;
				print $langs->trans('NvStripeMail').'</a></div>';
			}
			  
			if ($conf->ovh->enabled) 			{
				$IsNoMobile =false ;
		/*			$ret = strpos( reset($emails), 'Pas d');
		//			if ($ret > 0) {
		//				$IsNoMobile = true ;
		//			}
		//			else {
						
		//			}*/
				$langs->load("mails");
				if (!empty($SMS)) {
					if (!empty($bull->TiersTel) or !empty($bull->TiersTel2)) {
						$out =  '<div class="inline-block divButAction" width=30%>';
						$out .= '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?'.$texteid.'='.$id.'&amp;action='.$PREP_SMS;
						$out .=  '&amp;mode=init&amp;pasgMail='.$pasgMail.'&BtStripeSMS=STRIPE_SMS&token='.newToken().'&etape='.CglStripe::STRIPE_SMS_STRIPE.'#AncreMailSms" >';
						}
					else  {
						$out = '<a class="butActionRefused" href="" >';
					}
					print $out;
					print $langs->trans('NvStripeSMS').'</a></div>';
				}
			}
		}	
	} //BtNelEncaissement	
	
	function BtPaiementLigne($id, $type, $texteid)
	{
		global $langs, $bull, $CRE_PMTLIGNE;
		/*
		FctCreUrlPmt
			créer l'url, la stocker dans table bull
				print '<br><!-- Link to pay -->'."\n";
				require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
				print showOnlinePaymentUrl('invoice', $object->ref).'<br>';
		

		
			créer dossier suivi tiers/BU/LO sauf s'il existe
			Ajouter échange envoie url
		Hors FctCreUrlPmt
			Afficher url sur le cadre
			tester l'activation du bouton BtPaiementLigne avec acompte créé, donc avec réservation faite
		*/
/*
ReqPmtLigneInit.php
		if ($object->statut != Facture::STATUS_DRAFT && $useonlinepayment)
		{
			print '<br><!-- Link to pay -->'."\n";
			require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
			print showOnlinePaymentUrl('invoice', $object->ref).'<br>';
		}

*/
			print '<script language="javascript" type="text/javascript">
			function FctCreUrlPmt() {
				alert("toto");				
				}		
			</script>';
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$bull->id.'#AncrePaiement">';
		print '<input type="hidden" name="action" value="'.$CRE_PMTLIGNE.'">';
		print '<input type="hidden" name="'.$texteid.'" value="'.$id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		if ($bull->statut < $bull->BULL_ARCHIVE ) {
			if ($bull->statut >= $bull->BULL_INS or $bull->statut >= $bull->BULL_VAL)
				print '<input class="button" action="'.$CRE_PMTLIGNE.'"   value="'.$langs->trans("LibPmtLigne").'" align="center" width=40% onclick="FctCreUrlPmt()">';
			else
				print '<input class="butActionRefused" action="'.$CRE_PMTLIGNE.'"   value="'.$langs->trans("LibPmtLigne").'" align="center" width=40% onclick="FctCreUrlPmt()">';
		}

		print '</form>';	
	} //BtPaiementLigne
	
	
	function SaisieEncaissement($action, $id, $id_det, $type, $texteid, $textedet) 
	{
		global   $ACT_MAJ_PAIMT,  $ACT_SEL_PAIMT;
		global  $langs, $bull,   $db, $conf;
		global $PaimtMode, $PaimtOrg, $PaimtNomTireur, $PaimtCheque, $PaimtMtt, $PaimtDate;
		

		$wfctcomm = new FormCglFonctionCommune($this->db);
		$wfctcomm->AfficheParagraphe("Encaissement", 1);
		print '<tr><td align=center>';
		$form=new Form($db);
		$w=New CglLocation($this->db);
		// Table Encaissement 
		if (isset($action) and !($action == '' ) and $action == $ACT_SEL_PAIMT)
			$linepaiment = $bull->RechercheLign ($id_det);
		else $linepaiment = new BulletinLigne ($db , $bull->type);
		
		// on récupère ce qu'il y a dans les variables GETPOST si elles ne sont pas vides
		if (!empty($PaimtMode))			$linepaiment->id_mode_paiement	=$PaimtMode;
		//if (!empty($PaimtOrg))			$linepaiment->banque			=$PaimtOrg;
		if (!empty($PaimtNomTireur))	$linepaiment->tireur			=$PaimtNomTireur;
		if (!empty($PaimtOrg))	$linepaiment->organisme					=$PaimtOrg;
		if (!empty($PaimtCheque))		$linepaiment->num_cheque		=$PaimtCheque;
		if (!empty($PaimtMtt))			$linepaiment->montant			=$PaimtMtt;
		if (!empty($PaimtDate))			$linepaiment->date_paiement		=$PaimtDate;

			
		print '<div class="tabBar" >';
		print    '<table id=Niv3_Encaissement ><tbody><tr><td>';
		//print    '<table id=Niv4_Encaissement class="liste" ><tbody>';
		print    '<table id=Niv4_Encaissement  ><tbody>';
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'#AncrePaiement">';
		print '<input type="hidden" name="action" value="'.$ACT_MAJ_PAIMT.'">';
		print '<input type="hidden" name="'.$texteid.'" value="'.$id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
				if ($linepaiment->pmt_rappro != 0 and $action == $ACT_SEL_PAIMT) 
					print    '<tr><td> </td><td width="30% colspan=2"><b>'.$langs->trans('SomEncais'). ' </b> </td></tr>';
				print    '<tr>';
				print    '<td  width="30%">'.$langs->trans('ModePaiement'). ' : </td><td>';
				$moreforfilter='';
				$moreforfilter.=$form->select_types_paiements($linepaiment->id_mode_paiement,"PaimtMode",'',0, 1, 1,0);
				print $moreforfilter;
				print '</td>';
			print    '</tr>';
			print    '<tr><td>Organisme</td>';
				if ($linepaiment->pmt_rappro != 0 and $action == $ACT_SEL_PAIMT) {
						print '<td size="40">'.$linepaiment->organisme.'</td>';
				}
				else 				{
					print    '<td colspan=2> <input class="flat" size="45" type="text" value="'.$linepaiment->organisme.'"  name="PaimtOrg" ></td>';
			}
			print    '</tr>';
			print    '<tr><td>Tireur</td>';
			
						/*
						print '<td>'.$w->transfDateFr($linepaiment->date_paiement).'</td>';*/
					if (empty($linepaiment->tireur)) $tireur = $bull->tiersNom;
					else $tireur =$linepaiment->tireur;
					print    '<td colspan=2> <input class="flat" size="45" type="text" value="'.$tireur.'"  name="PaimtNomTireur" ></td>';								
			print    '</tr>';	
			print    '<tr>';
				print    '<td>'.$langs->trans("NumeCheque").'</td><td colspan=2>';
				print '<input class="flat" size="45" type="text"  value="'.$linepaiment->num_cheque.'"   name="PaimtCheque" ></td>';
			print    '</tr>';
			print    '<tr><td>Montant</td>';
				if ($bull->regle == $bull->BULL_ARCHIVE or ($linepaiment->pmt_rappro != 0 and $action == $ACT_SEL_PAIMT))
						print '<td>'.$linepaiment->montant.' &nbsp;euros</td>';
					else
				print    '<td><input class="flat" size="10" type="text"  value="'.$linepaiment->montant.'"   name="PaimtMtt" >&nbsp;euros';
				print '<img class="hideonsmartphone" border="0" title="Obligatoire" alt="Obligatoire" src="../../theme/eldy/img/star.png"></td>';
				print '   <td>';
					
				if (isset($action) and !($action == '' ) and $action == $ACT_SEL_PAIMT and empty($PaimtMode))
				{
					print '<input type="hidden" name="'.$textedet.'" value="'.$id_det.'">';
					print '<input class="button"   type="submit" value="Enregistrer" align="center">';	
				}
				elseif ($bull->regle < $bull->BULL_ARCHIVE)
				{
					print '<input type="hidden" name="'.$texte2.'" value="">';
					if ($bull->regle <$bull->BULL_ARCHIVE)
					print '  <input class="button"  type="submit" value="Ajouter" align="right">';
				}
				print '</td>';
			print    '</tr>';
			print    '<tr>';
				print    '<td>Date Encaissement</td><td colspan=2>';
				if ($bull->regle < $bull->BULL_ARCHIVE) {

				$form->select_date($linepaiment->date_paiement,'PaimtDate','','','',"add",1,1);
					print '&nbsp;<img class="hideonsmartphone" border="0" title="Obligatoire" alt="Obligatoire" src="../../theme/eldy/img/star.png">';
						//print '<input class="flat" size="20" type="text" value="'.$linepaiment->date_paiement.'"   name="PaimtDate" >';
				}
				else
					print $linepaiment->date_paiement;
				print '</td>';
			print    '</tr>';
			//	print '<tr><td>&nbsp;</td></tr>';
			//	print '<tr><td>&nbsp;</td></tr>';
			//	print '<tr><td>&nbsp;</td></tr>';
			//	print '<tr><td>&nbsp;</td></tr>';
		print '</form>';
		// Fin table Encaissement
		print    '</tbody></table>';/* id=Niv4_Encaissement*/
		print    '</td></tr></tbody></table>';/* id=Niv3_Encaissement*/
		print '</div>';
		unset($wfctcomm);
		
	} // SaisieEncaissement

	function AffichePaiemRem($id, $id_det, $type, $texteid, $textedet)
	{
		global $action,  $ACT_SEL_PAIMT, $ENR_PROCREGL, $bull, $langs, $BtEncais, $conf;

		$wfctcomm = new FormCglFonctionCommune($this->db);
		
		print '<table  id=Niv1_AffichePaiemRem border = 1 width=100%  ><tbody ><tr>';
		print '<td  width=40% heigth=100% style="background-color:'.$langs->trans("ClPavePaiementAff").';">';
		print '<table  id=Niv2_AffichePaiement width=100%><tbody><tr>';
		$this->AffichePaiement($action, $id, $id_det, $type, $texteid, $textedet);
		print '</tr></tbody></table>';/*  id=Niv2_AffichePaiement*/
		print '</td>';			
		print  '<td  width=30% rowspan=2 align="center" >';
		if ((empty($action) or  (!empty($BtEncais) and $action <> $ACT_SEL_PAIMT  )))  $style = 'style="background-color:'.$langs->trans("ClPavePaiementAff").'"';
		else $style = 'style="background-color:'.$langs->trans("ClPaveSaisie").'"';
		print '<table  id=Niv3_AffichePaiement width=100%><tbody><tr><td  align=center '.$style.'>';
		//if ((empty($action) and empty($BtEncais) ) or  (!empty($BtEncais) and !empty($action) and $action <> $ACT_SEL_PAIMT  )) {
		if (!empty($BtEncais) or $action == $ACT_SEL_PAIMT) {
			if ($bull->regle < $bull->BULL_ARCHIVE) {
				$this->BtNelEncaissement($id, $type, $texteid, 'partiel'); 
				print '</td>'; 
			}
			print '<table  id=Niv2_SaisieEncaissement width=100%  height=100%><tbody><tr>';
			$this->SaisieEncaissement($action, $id, $id_det, $type, $texteid, $textedet);
			print '</tr></tbody></table></td>';/*  id=Niv2_SaisieEncaissement*/
		}
		else  			{
			if ($bull->regle < $bull->BULL_ARCHIVE) {
				$this->BtNelEncaissement($id, $type, $texteid, 'complet'); 
				print '</td>'; 
				}
			}
		print '</td>';
		/*print '</td></tr><tr>';
		$affinfobull = info_admin($langs->trans("DefLibelle"),1);
		$wfctcomm->AfficheParagraphe("ProcRegl", 2, $affinfobull, 'style="background-color:'.$langs->trans("ClPaveSaisie").'"'); 
		print '</tr><tr><td colspan=2 style="background-color:'.$langs->trans("ClPaveSaisie").'">';
			print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$bull->id.'#AncrePaiement">';
			print '<input type="hidden" name="token" value="'.newtoken().'">';
			print '<input type="hidden" name="action" value="'.$ENR_PROCREGL.'">';
			print '<input type="hidden" name="'.$texteid.'" value="'.$id.'">';	
			print '<textarea  cols="70"  rows="'.ROWS_3.'" wrap="soft" name="PmtFutur">';	
			print $bull->PmtFutur.'</textarea>';
			print '</td><td style="background-color:'.$langs->trans("ClPaveSaisie").'"><br>';
			print '<input class="button" action="ENR_PROCREGL"  type="submit" value="'.$langs->trans("BtEnregistrer").'" align="center" width=40%>';
			print '</form></td>';
		*/
			print '</td></tr><tr>';

		if ($conf->stripe->enabled and !(!empty($BtEncais) or $action == $ACT_SEL_PAIMT) ) {
			require_once(DOL_DOCUMENT_ROOT."/custom/cglinscription/class/html.formstripe.class.php");
//			print '</td></tr>';

			$wfStrC = new FormStripeCAV($this->db);
			print $wfStrC ->Affiche_Demandes_Stripe($id);			
			unset ($wfStrC);
		}
			print '</td></tr>';
		print '</tr></tbody></table>';/*  id=Niv3_AffichePaiement*/
		//print '</div>';
			print '</td></tr></tbody></table>';/*  id=Niv1_AffichePaiemRem*/
		unset($wfctcomm);
	} //AffichePaiemRem
	function AfficheTotalFacture()	
	{
		global $bull, $langs;	
			
		print '<table id=Nivn_TotalFacture class="liste"  width="100%">';
		
		/* Total facture */
		$moreforfilter='';
		$moreforfilter.=$langs->trans('TotalFact');
		
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" width="90%">';
		print $moreforfilter;
		print '</td>';
		$ptt=$bull->TotalFac();
		$prssrem = $bull->TotalFacssRem();
		print '<td  width="10%" align="right" ><font size=4>'.$ptt.'</font></td>';
		//print '<td  width="10%" >$<font size=4>'.$prssrem.  ' - '.$bull->rem.'           '.$ptt.'</font></td>'; 
		print '<td align="right">euros</td>';
		print '</tr><tr></tr>';
		
			/* Total paiement */			
		$moreforfilter='';
		$moreforfilter.=$langs->trans('Totalpaimnt');
		
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" width="90%">';
		print $moreforfilter;
		print '</td>';
		$ptt1=$bull->TotalPaimnt();
		print '<td  width="10%" align="right" ><font size=4>'.$ptt1.'</font></td>';
		print '<td align="right">euros</td>';
		print '</tr>';
		
			
		
			/* Solde */			
		$moreforfilter='';
		$moreforfilter.=$langs->trans('Solde');
		
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" width="90%">';
		print $moreforfilter;
		print '</td>';
		$ptt2 = $ptt - $ptt1;
		if ($ptt2 < 0.01 and $ptt2 > -0.01) $ptt2 = 0;
		print '<td  width="10%" align="right" ><font size=4>'.$ptt2.'</font></td>';
		print '<td align="right">euros</td>';
		print '</tr>';
		
		$ptt2=$bull->TotalPaimntStripeNonEncaisse();
		if ($ptt2 > 0) {
			/* Total paiement Stripe restant à encaisser */			
			$moreforfilter='';
			$moreforfilter.=$langs->trans('TotalpaimntStripe');
			
			print '<tr class="liste_titre">';
			print '<td class="liste_titre" width="90%"><i>';
			print $moreforfilter;
			print '</td>';
			print '<td  width="10%" align="right" ><font size=4><i>'.$ptt2.'</font></td>';
			print '<td align="right"><i>euros</td>';
			print '</tr>';	
		}
		
		print '</table>';
	} //AfficheTotalFacture

	function MajTiers()
	{
		global $bull, $db, $id_client, $action, $conf, $CREE_BULL;
		global $tiersNom, $TiersVille, $TiersIdPays, $TiersTel,  $TiersMail, $TiersAdresse, $TiersCP, $AuthMail, $firstname, $civility_id, $TiersTel2;	
		global $societemajtiers;
		global $INDICATIF_TEL_FR;
		
		$societemajtiers=new Societe($db);
		if (!empty($id_client)) $ret = $societemajtiers->fetch($id_client);
		if ($action <> $CREE_BULL) {
			if (!empty($tiersNom ) ) 	{			$societemajtiers->nom =$tiersNom;	}
			if (!empty($TiersVille ))	{			$societemajtiers->town =$TiersVille;		}
			if (!empty($TiersIdPays ))	{			$societemajtiers->country_id =$TiersIdPays;		}
			if (!empty($TiersTel ) and $TiersTel <> $INDICATIF_TEL_FR)	
										{			$societemajtiers->phone =$TiersTel;	}
			elseif (!empty($TiersTel ) and $TiersTel == $INDICATIF_TEL_FR) $societemajtiers->phone = '';
			if (!empty($TiersMail ))	{			$societemajtiers->email =$TiersMail;		}
			else $societemajtiers->email = '';
			if (!empty($TiersAdresse ))	{			$societemajtiers->address  =$TiersAdresse;	}
			if (!empty($TiersCP ))		{			$societemajtiers->zip =$TiersCP;		}
			if (!empty($firstname ))	{			$societemajtiers->firstname =$firstname	;	}
			if (!empty($civility_id ))	{			$societemajtiers->civility_id =$civility_id	;	}
			$societemajtiers->name              = dolGetFirstLastname(strtoupper($tiersNom),$firstname);
			$societemajtiers->name_bis          = $tiersNom;		
			$extrafields = new ExtraFields($db);
			$societemajtiers->id = $id_client;
			$extralabels=$extrafields->fetch_name_optionals_label($societemajtiers->table_element);
			$extrafields->setOptionalsFromPost($extralabels,$societemajtiers);
			if (!empty($TiersTel2 ) and $TiersTel2 <> $INDICATIF_TEL_FR)		
										{		$societemajtiers->array_options['options_s_tel2'] =$TiersTel2;		}
			elseif (!empty($TiersTel2 ) and $TiersTel2 == $INDICATIF_TEL_FR)
										$societemajtiers->array_options[options_s_tel2] = '';
			// les tiers créé dans les écrans CGL  sont tous des individus.
			// Si ce sont des personne morale, on les aura créé préalablement avec Dolibarr_Client
			$societemajtiers->particulier = 1;

			// Donner un code client à ce prospect
			if ($societemajtiers->client <> 1 or empty($societemajtiers->code_client) ){
				$societemajtiers->client = 1;
				$societemajtiers->get_codeclient($societemajtiers,0);
			}
			if ($societemajtiers->fournisseur == 1 or empty($societemajtiers->code_fournisseur) ) 
					$societemajtiers->get_codefournisseur($societemajtiers,1);

			if ( empty($id_client)) 
			{
				$this->CreeTiers($id_client);
			}
			//if (!isset($bull) and empty($bull->id_client) and empty($id_client))
			else $this->UpdateTiers($id_client);
		}
	} //MajTiers
	
	function UpdateTiers($MajTiersid_client)
	{	
		global  $action,$id_contrat, $db, $user, $id_client;
		global $societemajtiers, $id_contactTiers;	
	/*	
		if (!isset ($societemajtiers)){ 	
			$societemajtiers=new Societe($db); 
			if (!empty($id_client)) $ret = $societemajtiers->fetch($id_client);
			$extralabels=$extrafields->fetch_name_optionals_label($societemajtiers->table_element);
			$extrafields->setOptionalsFromPost($extralabels,$societemajtiers);
		}*/
		
//		$ret=$societe->update($id_client, $user,0,0,0,'update
		$ret=$societemajtiers->update($societemajtiers->id, $user,0,0,0,'update');
		$id_client = $societemajtiers->id;
		$id_contactTiers=$this->RechercheContactTiers($societemajtiers->id);
		if (empty($id_contactTiers) or $id_contactTiers <= 0) {
			$result=$societemajtiers->create_individual($user);
				if (! $result >= 0)  {
				}				
			$id_contactTiers=$this->RechercheContactTiers($societemajtiers->id);
		}		
	}/*  UpdateTiers*/

	function CreeTiers($id_client)
	{
		global $db, $user, $conf, $id_client;
		global $societemajtiers, $id_contactTiers;

		if (!isset ($societemajtiers)) 	$societemajtiers=new Societe($db);

		$ret=$societemajtiers->create( $user);
		$fnow= new DateTime();	
		// Créer un contact comme Dolibarr
        if ($ret >= 0    and $societemajtiers->particulier)  {
				dol_syslog("This thirdparty is a personal people",LOG_DEBUG);
				$result=$societemajtiers->create_individual($user);
				if (! $result >= 0)  {
					$error=$societemajtiers->error; $errors=$societemajtiers->errors;
				}				
		}			
		//tiers particulier	
		$id_client = $societemajtiers->id;
		//$this->maj_typetiers($conf->global->CGL_TYPEENT_ID_PARTICULIER);

		$id_client = $societemajtiers->id;
		$id_contactTiers=$this->RechercheContactTiers($societemajtiers->id);
		
	} /*CreeTiers()*/
	
	function RechercheContactTiers($id_client)
	{	
		global  $conf;
			if (! $id_client) $id_client=$bull->id_client;
			// construction SQL de recherche
			$sql = "SELECT min(sp.rowid) as rowid ";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s ,".MAIN_DB_PREFIX."socpeople as sp" ;
			$sql .= "  WHERE s.entity =1 and s.rowid = '".$id_client."'";
			$sql .= "  AND sp.fk_soc = s.rowid ";			
			if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND sp.statut <> 0 ";		
			$sql .= "  AND s.nom like '%'||lastname||'%'";
			 
			$result = $this->db->query($sql);
			$obj=$this->db->fetch_object($result);
			return($obj->rowid);	
		
	} // RechercheContactTiers
	
	function UpdateTiersOrigine()
	{
		global $bull, $TiersOrig, $TiersTel, $Villegiature;
		global $INDICATIF_TEL_FR;
	
		if (!empty($TiersOrig))  $bull->fk_origine = $TiersOrig;
		if (!empty($TiersTel) and $TiersTel <> $INDICATIF_TEL_FR)  $bull->TiersTel = $TiersTel;
		if (!empty($Villegiature))  $bull->Villegiature = $Villegiature;
		$bull->update();
	}
   
	function maj_typetiers($val)
	{
		global $bull, $id_client;
		
		if (empty($val)) $val = 0;
		
		$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET ";
		$sql .= " fk_typent = ".$val;
		$sql .= " WHERE rowid='".$id_client."'";
		dol_syslog(get_class($this).'::maj_typetiersl='.$sql, LOG_DEBUG);
		$result = $this->db->query($sql);	
		if ($result <= 0)
			 {
					$error=$object->error; 
			 }	
		$this->db->free($result);
	} // maj_typetiers
	
	function RechercheTiers($idclient)
	{
		global $id_client, $action,$id_bull, $bull;
		global $tiersNom, $TiersVille, $TiersIdPays, $TiersTel, $options_s_tel2, $TiersMail, $TiersAdresse, $TiersCP;
		global $INDICATIF_TEL_FR;

			if (! $id_client) $id_client=$bull->id_client;
			// construction SQL de recherche
			$sql = "SELECT s.rowid , nom, address , zip , town , phone , email,  s_tel2, fk_pays  ";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s ";
			$sql .= "LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on fk_object = s.rowid " ;
			$sql .= "  WHERE entity =1 and s.rowid = '".$idclient."'";
			$result = $this->db->query($sql);
			$obj=$this->db->fetch_object($result);
			$bull->id_client	= $obj->rowid;
			$bull->tiersNom		= $obj->nom;
			$bull->TiersAdresse	= $obj->address;
			$bull->TiersTel		= $obj->phone;
			if (empty($bull->TiersTel)) $bull->TiersTel = $INDICATIF_TEL_FR;
			$bull->TiersTel2	= $obj->s_tel2;
			if (empty($bull->TiersTel2)) $bull->TiersTel2 = $INDICATIF_TEL_FR;
			$bull->TiersCP		= $obj->zip;
			$bull->TiersVille	= $obj->town;
			$bull->TiersIdPays	= $obj->fk_pays;			
			$bull->TiersMail	= $obj->email;
			$bull->ref_client	= $obj->code_client;
			
			$id_client	= $obj->rowid;
			$tiersNom	= $obj->nom;
			$TiersVille	= $obj->town	;
			$TiersIdPays = $obj->fk_pays;	
			$TiersTel	= $obj->phone;
			if (empty($bull->TiersTel)) $TiersTel = $INDICATIF_TEL_FR;
			$TiersTel2	= $obj->s_tel2;
			if (empty($bull->TiersTel2)) $TiersTel2 = $INDICATIF_TEL_FR;
			$TiersMail	= $obj->email;
			$TiersAdresse	= $obj->address;
			$TiersCP	= $obj->zip;
			
			return;
		
	}/*  RechercheTiers*/
	/*
	// ROUTINES RECUPEREE DE DOLIBARR - voir fichier	 cglavt/class/cglFctDolibarrRevues.class.php"
   
   /**
     *  Output html form to select a third party
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     *  @param  string	$filter         Optionnal filters criteras (example: 's.rowid <> x')
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$event			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 *	@param	int		$flgVille		1 si on veut la ville dans le libelle du tiers
     * 	@return	string					HTML string with
     */
    function select_compagnie($selected='',$htmlname='socid',$filter='',$showempty=0, $showtype=0, $forcecombo=0, $event=array(), $flgVille)
    {
		$w = new CglFonctionDolibarr($this->db);
		return $w->select_company($selected,$htmlname,$filter,$showempty, $showtype, $forcecombo, $event, $flgVille);
 
    }//select_compagnie

	function Preparation_Mail_Init($BtStripeComm, $id_bull_det_stripe)
	{
		global $langs, $user, $bull, $conf, $action;
		global $StripeNomPayeur, $libelleCarteStripe, $StripeMailPayeur, $StripeMtt, $modelmailchoisi, $BtStripeMail, $stripeUrl, $ACT_STRIPERELMAIL, $formmail;			

		$TraitCommun = new CglCommunLocInsc($this->db);
//		$ret = $TraitCommun->rapatrie_pdf($bull, true);
		$user->gender = $bull->type;
		//if ($ret == false) return;
		/*
		 * Affiche formulaire mail
		*/	
		
		if ($bull->type == 'Loc') 	{		
			$lb_id = 'id_contrat';
			$topicmail='SendContratRef';
			$modelmail='cgllocation';
		}
		elseif ($bull->type == 'Insc') {
			$lb_id = 'id_bull';
			$modelmail='cglbulletin';
			$topicmail='SendBullRef';
		}
		elseif ($bull->type == 'Resa') {
			$lb_id = 'id_resa';
			$modelmail='cglresa';
			$topicmail='SendResaRef';
		}

//		$action='send';	
		if (!empty( $id_bull_det_stripe) and empty(GETPOST('modelselected', 'int'))) {
			$wbs = new BulletinDemandeStripe ($this->db);
			$ret = $wbs->fetchDemandeStripe( $id_bull_det_stripe);
			if (empty($modelmailchoisi)) $modelmailchoisi = $wbs->ModelMail;
			if (empty($StripeNomPayeur)) $StripeNomPayeur = $wbs->Nompayeur;
			if (empty($StripeMtt)) $StripeMtt = $wbs->montant;
			if (empty($StripeMailPayeur)) $StripeMailPayeur = $wbs->mailpayeur;
			if (empty($libelleCarteStripe)) $libelleCarteStripe = $wbs->libelleCarteStripe;
			if (empty($stripeUrl)) $stripeUrl = $wbs->stripeUrl;
			unset($wbs);
		}
		elseif ($bull->type <> 'Resa' and empty( $id_bull_det_stripe) and empty(GETPOST('modelselected', 'int')))
		{
		// Ramener le Nom et le mail du tiers si c'est la première demande Stripe			
			if (empty($StripeNomPayeur)) $StripeNomPayeur =  $bull->tiersNom;
			if (empty($StripeMailPayeur)) $StripeMailPayeur =  $bull->TiersMail;
			// Montant présaisie = montant total si Insc, 30% montant total si Loc
			if (empty($StripeMtt)) {
				if ( $bull->type == 'Insc') $StripeMtt = $bull->solde;
				elseif ( $bull->type == 'Loc') $StripeMtt =  $bull->CalculAcompte();
			}
		}
		if (empty($libelleCarteStripe)) {
			if ($bull->type == 'Loc') $libelleCarteStripe =  $conf->global->CGL_STRIPE_LIB_CARTE_PAIEMENT_LOC." ".$langs->trans("LibStripeCarteRef");
			else $libelleCarteStripe =  $conf->global->CGL_STRIPE_LIB_CARTE_PAIEMENT_INSC." ".$langs->trans("LibStripeCarteRef");

		}

		if ($bull->type <> 'Resa' and !empty(GETPOST('modelselected', 'int')) or $action == $ACT_STRIPERELMAIL) {
			$_SESSION['StripeNomPayeur'] =  $StripeNomPayeur; 
			$_SESSION['StripeMailPayeur'] = $StripeMailPayeur;  
			$_SESSION['StripeUrl'] = $stripeUrl; 			
			$_SESSION['StripeMtt'] = $StripeMtt; 
			$_SESSION['modelmailselected'] = $modelmailselected;
			$_SESSION['id_stripe'] = $id_bull_det_stripe;	
			$_SESSION['libelleCarteStripe'] = $libelleCarteStripe; 
			
		}	
		print '<br><a name="AncreMailSms" id="AncreMailSms"></a>';
		print '<div id="DivMailSms" class="fichecenter"  style="background-color:'.$langs->trans("ClPaveSaisie").'">';

		// Charger le tiers
		$soc = New Societe ($this->db);
		$soc->id = $bull->id_client;
		$soc->nom = $bull->tiersNom;
		$soc->email = $bull->TiersMail;
		// Cree l'objet formulaire mail
		if (!class_exists('FormMail')) include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($this->db);


		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		$formmail->withfrom=1;
		$formmail->withtopic=1;
		$liste=array();
		$i=0;
		if (!empty($bull->lines)) {		
			foreach ($soc->thirdparty_and_contact_email_array(1) as $key=>$value) {
				$liste[$i]=$value;
			}// foreach
		}
		$sendto = GETPOST('sendto', 'alpha');
		if  (!empty($sendto)) $temp = $sendto;	// Cas du réaffichage du mail, pour erreur d'envoi		
		elseif (!empty($id_bull_det_stripe)) $temp = $StripeMailPayeur; // cas d'une première demande Stripe	
		elseif (!empty($bull->TiersMail)) $temp = $bull->TiersMail; // cas d'une relance d'une demande Stripe
		else  $temp =  strtoupper($user->email); // Autre cas
		
		$formmail->withto = $temp;
		$formmail->withtofree=1;
		$formmail->withtocc=$liste;
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withform=0;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;
		if (empty($bull->ref_client)) {
			$formmail->withtopic = $langs->transnoentities($topicmail, '__BULREF__');
		}
		else if (! empty($bull->ref_client)) {
			$formmail->withtopic = $langs->transnoentities($topicmail, '__BULREF__(__REF_CLIENT__)');
		}	

		// Prise en charge du Stripe courante
		$bull->stripeModelMail = $modelmailchoisi;
		$bull->stripeNomPayeur = $StripeNomPayeur;
		$bull->stripeMtt = $StripeMtt;
		$bull->stripeMailPayeur = $StripeMailPayeur;
		$bull->stripeUrl = $stripeUrl;
		$bull->libelleCarteStripe = $libelleCarteStripe;
		
		// Tableau des substitutions
		$formmail->withsubstit	= 1;
			//Variables communes valorisées ';
			$arrayoffamiliestoexclude=array('member', 'objectamount');
			if (! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude=null;
			$substitutionarray = getCommonSubstitutionArray($langs, 0, $arrayoffamiliestoexclude, '');

			//Variables inscriptions valorisées ';
			$parameters = array(	
					'mode' => 'formemailwithlines'
				);
			$temptab=array();
			require_once(DOL_DOCUMENT_ROOT.'/custom/cglinscription/core/substitutions/functions_inscription.lib.php');
			inscription_completesubstitutionarray ($temptab,$outputlangs,$bull,$parameters, 2);
			// Variables lignes valorisées ';
			$temptablig=array();
			inscription_completesubstitutionarray ($temptablig,$outputlangs,$bull,$parameters, 1);
			// Separer substit et substit_lines
			$formmail->substit = array_merge($temptab, $substitutionarray);
			$formmail->substit_lines =  $temptablig;

		// Tableau des parametres complementaires du post
		$formmail->param['action']=$action;
		$formmail->param['models']=$modelmail;
		//$formmail->param['models_id']=GETPOST('modelmailselected','int');
		$formmail->param['models_id']=$modelmailchoisi;
		if ($formmail->param['models_id'] == 0) 
				$formmail->param['models_id'] = $TraitCommun->RechModelInit();
		$formmail->param['socid']=$soc->id;
		$formmail->param['returnurl']=$_SERVER["PHP_SELF"];
		
		// Init list of files
		if (GETPOST("mode", 'alpha') == 'init' or GETPOST("modelselected", 'int')  or GETPOST("addfile", 'int') )
		{
			if (empty(GETPOST("addfile",'int'))) $formmail->clear_attached_files();
			//$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
		
		/* rechercher les fichiers spécifiques rajoutés à la main*/
			if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $lstfiles=explode(';',$_SESSION["listofpaths".$keytoavoidconflict]);
			$TraitLocation = New CglLocation ($this->db);
			$TraitInscription = New CglInscription ($this->db);			
			$datelocstockee = null;
			$fichlocstocke = '';
			$dateInsstockee = array();
			$fichInsstockee = array();
			$passloc = false;	
			$passInsc =array();
			$ExtMineur = 0;
			if (!empty($bull->lines)) {		
				foreach ($bull->lines as $line) {
					if ($line->type_enr == 0  AND $line->action != 'X'  AND $line->action != 'S' ) {
						// Rechercher les fichiers pdf du répertoire de ref et prendre le plus récent s'il existe sinon rien						
						$rep_bull = $conf->cglinscription->dir_output.'/';
						$rep_bull = str_replace('/','\\',$rep_bull);
						if ($bull->type == 'Loc') 		$rep_bull .= 'contratLoc\\';			
						elseif ($bull->type == 'Insc')  $rep_bull .= 'bulletin\\';		
						elseif ($bull->type == 'Resa')  $rep_bull .= 'reservation\\';

						$rep_bull .= $bull->ref.'\\';
						if ($bull->type == 'Loc')  $fichcomplet = $rep_bull.$bull->ref.'_CONTRAT_LOC';
						elseif ($bull->type == 'Insc') 	$fichcomplet = $bull->NommageEditionBulletin('fichier', $line->id_act);
						elseif ($bull->type == 'Resa') 	$fichcomplet  = $bull->NommageEditionBulletin('fichier');
						//elseif ($bull->type == 'Resa') 	$fichcomplet  = $rep_bull.$bull->ref.'_'.$bull->model;
						$fichpdfgenericcomplet = $fichcomplet.'*.pdf';
						if (file_exists($fichcomplet.'.pdf'))
							$datefichcomplet = filemtime($fichcomplet.'.pdf');
						$fichiersdef = array();	
			
						// on ne cherche le fichier pdf le plus récent uniquement 1 fois pour Loc et une fois pour Insc et id_act, let celui qui est plus récent que l'odt
						if ((($bull->type == 'Loc' or $bull->type == 'Resa') and $passloc == false  ) or ($bull->type == 'Insc' and (empty($passInsc[$line->id_act]) or  $passInsc[$line->id_act] == 0))) {
							$fichiersdef = glob($fichpdfgenericcomplet);					
							if (!empty($fichiersdef)) {
								foreach($fichiersdef as $fichierpdf )	{
									$datefichier = filemtime ($fichierpdf);
									if (($bull->type == 'Loc' or $bull->type == 'Resa') and (empty($datelocstockee ) or $datelocstockee < $datefichier ) and $datefichcomplet <= $datefichier  ){
										$datelocstockee = $datefichier;
										$fichlocstocke = $fichierpdf;
									}
									elseif ($bull->type == 'Insc' 
										and (empty($passInsc[$line->id_act])  or  $passInsc[$line->id_act] == 0)
										and  (empty( $dateInsstockee[$line->id_act]) or  $dateInsstockee[$line->id_act] < $datefichier ) 
										and $datefichcomplet <= $datefichier 
										and filemtime($fichcomplet.'.odt') <= $datefichier) {
											$dateInsstockee[$line->id_act] = $datefichier;
											$fichInsstockee[$line->id_act] = $fichierpdf;									
									}	
									// Réservation est traité plus loin car un seul billet	
								}// foreach
							}
						}
				
						
						// Recherche du/des fichiers sites
						if ($bull->type == 'Insc'  and (empty($passInsc[$line->id_act]) or  $passInsc[$line->id_act] === false)
							and !empty( $line->ficsite)) 					
								$lstfiles[] = $conf->cglinscription->dir_output.'/'.'site/'.$line->ficsite;	

						if ($line->PartAge <18 or $line->PartAge == 100 ) $ExtMineur = 1;
						if ( $bull->type == 'Loc' or  $bull->type == 'Resa')  $passloc = true ;
						if ($bull->type == 'Insc' )  $passInsc[$line->id_act] = 1;	
					}	
				}			// foreach	
			}
						
			// historique des pdf. 
			if ($bull->type == 'Loc' and !empty($fichlocstocke)){
					$fichstocke1 = $fichcomplet.'.pdf';
					copy($fichlocstocke, $fichstocke1);
					$lstfiles[] = $fichstocke1;
			}	
			elseif		($bull->type == 'Insc' and !empty($fichInsstockee)) {
				foreach ($fichInsstockee as $fich){
						$fichstocke1 = substr($fich,0,99 ).'.pdf';
						copy($fich, $fichstocke1);
						$lstfiles[] = $fichstocke1;	
				} // foreach
			}	
			elseif (($bull->type == 'Resa'  ) ){
					$fichstocke1 = $fichcomplet.'.odt';
					// le nom du fichier est compléter par le nom de la liste retournée par liste_modeles($db,$maxfilenamelength=0)	, sans les incrémentations terminales du à Acrobat		
                    if (!class_exists('ModeleCglinscription')) include_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/core/modules/cglinscription/modules_cglinscription.php';
                    $modellist=ModeleCglinscription::liste_modeles($this->db);

				$lstfiles[] = $fichstocke1;
			}
			// Récupérer l'autorisation parentale
			/*if ($ExtMineur == 1)
					$lstfiles[] = $conf->cglinscription->dir_output.'/'.'Autorisation/autorisation.pdf';
			*/		
			// Recherche fichier des CGV dans cglinscription/CGV
			if ( file_exists( $conf->cglinscription->dir_output.'/'.'CGV/CGV'.$bull->type.'.pdf')) $lstfiles[] = $conf->cglinscription->dir_output.'/'.'CGV/CGV'.$bull->type.'.pdf';
			unset($TraitLocation);
			unset($TraitInscription);
			// Attachment des fichiers au message
			$formmail->param['fileinit'] = $lstfiles;
		}

		// on utlise gender pour passer l'information Loc ou Insc momentanément, tant qu'un nouveau module Velo n'a pas été fait
		$ancval = $user->gender;
		$user->gender = $bull->type;

		$formmail->withfile = 2; //(avec 0, par de fichiers, avec 1 par d'ajout de fichier possible)
		if (!is_array($formmail->withtoccc)) $formmail->withtoccc = 1;


		unset($TraitCommun);
} //Preparation_Mail_Init

	/*
	* Prépare le pavé de composition du Mail
	*
	* @param	string $BtStripeComm	Indique s'il faut saisir les info d'une url stripe (nom, montant, url)
	*/
	function Preparation_Mail_Stripe($BtStripeComm, $id_bull_det_stripe)
	{
		global $langs, $bull, $conf;
		global $StripeNomPayeur, $libelleCarteStripe, $StripeMailPayeur, $StripeMtt, $modelmailchoisi, $BtStripeMail, $stripeUrl, $ACT_STRIPERELMAIL;	
		// Pour les étapres  STRIPE_MAIL_STRIPE (MS1), STRIPE_REL_MAIL_STRIPE (RM1),
		//			STRIPE_MAIL_APPLY_STRIPE (MS2) ou STRIPE_REL_MAIL_APPLY_STRIPE (RM2)


		require_once(DOL_DOCUMENT_ROOT."/custom/cglinscription/class/html.formstripe.class.php");
		//PaveMail (mail, nom, montant, modèle);
		$wfStrC = new FormStripeCAV($this->db);
		$this->Preparation_Mail_Init($BtStripeComm, $id_bull_det_strip); // Initialise $StripeNomPayeur, $StripeMtt, $StripeMailPayeur, $modelmailchoisi, $libelleCarteStripe
		if ($bull->type <> 'Resa' and $BtStripeComm == 'STRIPE_MAIL')  $titreform='SendStripeByMail';
		$titreform = $langs->trans($titreform);
		print_titre($langs->trans($titreform));
		print $wfStrC->SaisieDemandeStripe($StripeNomPayeur, $StripeMtt, $StripeMailPayeur, $modelmailchoisi, $libelleCarteStripe, 'Mail');			
		unset ($wfStrC);		
} //Preparation_Mail_Stripe



	function Preparation_Mail($BtStripeComm, $id_bull_det_stripe)
	{
		global $langs, $user, $bull, $conf, $action;
		global $StripeNomPayeur, $libelleCarteStripe, $StripeMailPayeur, $StripeMtt, $modelmailchoisi, $BtStripeMail, $ACT_STRIPERELMAIL, $formmail;			

		$this->Preparation_Mail_Init($BtStripeComm, $id_bull_det_stripe); // Initialise $formail, $StripeMtt,$StripeMailPayeur


		if ($bull->type == 'Loc') 	{	
			$lb_id = 'id_contrat';
		}			
		elseif ($bull->type == 'Insc') {
			$lb_id = 'id_bull';
		}							
		elseif ($bull->type == 'Resa') {
			$lb_id = 'id_resa';
		}	
		if ($bull->type <> 'Resa' and (strpos(GETPOST('etape', 'alpha'), '2') >0 or strpos(GETPOST('etape', 'alpha'), 'R') ==0 ))  $titreform='SendStripeByMail';
		elseif ($bull->type == 'Loc') 	{	
			$titreform='SendContratByMail';	
		}			
		elseif ($bull->type == 'Insc') {
			$titreform='SendBulletinByMail';
		}				
		elseif ($bull->type <> 'Resa' and $BtStripeComm == 'STRIPE_MAIL') {
			$titreform='SendStripeByMail';
		}			
		elseif ($bull->type == 'Resa') {
			$titreform='SendResaByMail';
		}	
		
		$titreform = $langs->trans($titreform).' ' . info_admin($langs->trans('AideFichierMailBInscription'), 'CGV'.$bull->type."\n". $langs->trans('AideFichierMailBInscriptionBis'));
		print_titre($langs->trans($titreform));

		print '<form method="POST" name="mailform" id="mailform" enctype="multipart/form-data" action="'.$formmail->param['returnurl'].'#formmail">'."\n";
		print '<input type="hidden" name="'.$lb_id.'" value="'.$bull->id.'">';	
		print '<input type="hidden" name="token" value="'.newToken().'">';

		print'<a id="formmail" name="formmail"></a>';
		if ($bull->type <> 'Resa') {
			if (GETPOST("etape", 'alpha') == CglStripe::STRIPE_MAIL_APPLY or GETPOST("etape", 'alpha') == CglStripe::STRIPE_MAIL_GENERAL) 
				print '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_MAIL_APPLY.'">';	
			if (GETPOST("etape", 'alpha') == CglStripe::STRIPE_MAIL_APPLY_STRIPE or GETPOST("etape", 'alpha') == CglStripe::STRIPE_MAIL_STRIPE) 
				print '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_MAIL_APPLY_STRIPE.'">';	
			if (GETPOST("etape", 'alpha') == CglStripe::STRIPE_REL_MAIL_STRIPE or GETPOST("etape", 'alpha') == CglStripe::STRIPE_REL_MAIL_APPLY_STRIPE) 
				print '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_REL_MAIL_APPLY_STRIPE.'">';	
		}
		else 
			print '<input type="hidden" name="etape" value="M2">';


		print $formmail->get_form( );


		print '<input type="hidden" name="id_stripe" id="id_stripe" value="'.$id_bull_det_stripe.'">';	
		print '<br><div class="center">';
		if ((empty(GETPOST("BtEncStripe", 'alpha'))) || (!empty(GETPOST("BtEncStripe", 'alpha')) && !empty($StripeMtt)  && !empty($StripeMailPayeur))) {
			print '<input disabled type="hidden" id="StripeMtt" name="StripeMtt" value="'.$StripeMtt.'"/>';
			print '<input disabled type="hidden" id="StripeMailPayeur" name="StripeMailPayeur" value="'.$StripeMailPayeur.'"/>';

				print '<input class="button" type="submit" id="sendmail_CAV" name="sendmail" value="'.$langs->trans("SendMail").'"';
			// Add a javascript test to avoid to forget to submit file before sending email
			if ($this->withfile == 2 && $conf->use_javascript_ajax)
			{
				$out.= ' onClick="if (document.mailform.addedfile.value != \'\') { alert(\''.dol_escape_js($langs->trans("FileWasNotUploaded")).'\'); return false; } else { return true; }"';
			}
			print ' />';
			
			  $_SESSION['StripeMailPayeur'] = $StripeMailPayeur ;
			  $_SESSION['StripeMtt'] = $StripeMtt ;
			  $_SESSION['libelleCarteStripe'] = $libelleCarteStripe ;
			  $_SESSION['StripeNomPayeur'] = $StripeNomPayeur  ;
			  $_SESSION['modelmailchoisi'] = $modelmailchoisi  ;
			  $_SESSION['id_stripe'] = $id_stripe  ;
		}
		else
			// Désactiver le bouton si on est sur une demande Stripe et que Montant ou Adresse payeur est vide
			print '<input class="button" disabled type="submit" id="sendmail_CAV" name="sendmail" value="'.$langs->trans("SendMail").'"';

print '</form>';
		
		$user->gender = $ancval;
		print '</div>';	
		//document.getElementById('trMailTo').style.visibility = 'hidden';	*
		if ($this->RechEnvDenStripe() ) {
			//Eteindre en ajax les champs Modèle et rendre insaississable le champ destinataire
			print "<script type='text/javascript' language='javascript'>
			$(document).ready(function() {
				document.getElementById('DivSelectMailModel').style.visibility = 'hidden';	
				document.getElementById('sendto').readOnly = true;	
				});
			</script>";	
			
		}		
		
	} // Preparation_Mail
	
		/* 
		* Recherche si l'on est sur une demande Stripe ou sur un envoi mail classique
		*
		* @return	True ou False
		*/
	function RechEnvDenStripe() {
		global $conf, $bull;		
		
		$wetape = GETPOST('etape', 'alpha');
		if ($bull->type <> 'Resa' ) 
			$flg = ($wetape ==  CglStripe::STRIPE_MAIL_STRIPE  
//					or $wetape ==  CglStripe::STRIPE_REL_MAIL_STRIPE 
					or $wetape ==  CglStripe::STRIPE_MAIL_APPLY_STRIPE 
//					or  $wetape ==  CglStripe::STRIPE_REL_MAIL_APPLY_STRIPE
					or $wetape ==  CglStripe::STRIPE_SMS_STRIPE  
//					or $wetape ==  CglStripe::STRIPE_REL_SMS_STRIPE 
					or $wetape ==  CglStripe::STRIPE_SMS_APPLY_STRIPE 
					or $wetape ==  CglStripe::STRIPE_REL_SMS_APPLY_STRIPE
					);
		$fl_afficheBoiteSaiseDemandeStripe  = ( $bull->type <> 'Resa') && $conf->stripe->enabled && $flg;
		return $fl_afficheBoiteSaiseDemandeStripe;

	} //RechEnvDenStripe

	/**
	 *	Return a HTML select string, built from an array of key+value.
	 *  Note: Do not apply langs->trans function on returned content, content may be entity encoded twice.
	 *
	 *	@param	string			$selected			Valeur courante
	 *	@param	string			$htmlname			Nom of html 
	 *	@param	int|string		$useempty			0 no empty value allowed, 1 or string to add an empty value into list (key is -1 and value is '' or '&nbsp;' if 1, key is -1 and value is text if string), <0 to add an empty value with key that is this value.
	 *	@param	int				$disabled			Html select box is disabled
	 * 	@return	string								HTML select string.
	 *  @see multiselectarray(), selectArrayAjax(), selectArrayFilter()
	 */
	/*
	* Prépare le pavé de composition du Mail
	*
	* @param	string $BtStripe	Indique s'il faut saisir les info d'une url stripe (nom, montant, url)
	*/
	function Preparation_SMS($BtStripeComm)
	{
		global $langs, $user, $bull, $conf, $action;
		global $StripeNomPayeur, $libelleCarteStripe, $StripeSmsPayeur, $StripeMtt, $modelmailchoisi, $BtStripeSMS, $stripeUrl, $ACT_STRIPERELSMS;			
		/*
		 * Affiche formulaire SMS
		*/		
		if ($bull->type == 'Loc') {
			$titreform='SendContratBySMS';
			$lb_id = 'id_contrat';
			$topicSMS='SendContratRef';
			$modelSMS='cgllocation';
		}
		elseif ($bull->type == 'Insc') {
			$titreform='SendBulletinBySMS';
			$lb_id = 'id_bull';
			$modelSMS='cglbulletin';
			$topicSMS='SendBullRef';
		}
		elseif ($bull->type == 'Resa') {
			$titreform='SendResaBySMS';
			$lb_id = 'id_resa';
			$modelSMS='cglresa';
			$topicSMS='SendResaRef';
		}
		$action='sendSMS';			
		if (!empty( $id_bull_det_stripe) and empty(GETPOST('modelselected', 'int'))) {
			$wbs = new BulletinDemandeStripe ($this->db);
			$ret = $wbs->fetchDemandeStripe( $id_bull_det_stripe);
			if (empty($modelmailchoisi)) $modelmailchoisi = $wbs->ModelMail;
			if (empty($StripeNomPayeur)) $StripeNomPayeur = $wbs->Nompayeur;
			if (empty($StripeMtt)) $StripeMtt = $wbs->montant;
			if (empty($StripeSmsPayeur)) $StripeSmsPayeur = $wbs->smspayeur;
			if (empty($libelleCarteStripe)) $libelleCarteStripe = $wbs->libelleCarteStripe;
			if (empty($stripeUrl)) $stripeUrl = $wbs->stripeUrl;
			unset($wbs);
		}
		elseif (empty( $id_bull_det_stripe) and empty(GETPOST('SMSmodelselected', 'int')))
		{
		// Ramener le Nom et le mail du tiers si c'est la première demande Stripe			
			if (empty($StripeNomPayeur)) $StripeNomPayeur =  $bull->tiersNom;
			print '<div  class="fichecenter"  > </div>';

			if (empty($StripeSmsPayeur)) $StripeSmsPayeur =  $bull->TiersTel;
			// Montant présaisie = montant total si Insc, 30% montant toal si Loc
			if ( $bull->type == 'Insc') $StripeMtt = $bull->solde;
			elseif ( $bull->type == 'Loc') $StripeMtt = ( 0.3 * $bull->solde) +1;
		}
		// Mise au format internationnal du téléphone
		$w_mobile = $StripeSmsPayeur;
		if (strpos($StripeSmsPayeur, '+') === false) setEventMessage('Inconnu', 'warnings');
		$StripeSmsPayeur = $w_mobile;
		
		if (empty($libelleCarteStripe)) {
			if ($bull->type == 'Loc') $libelleCarteStripe =  $conf->global->CGL_STRIPE_LIB_CARTE_PAIEMENT_LOC;
			else $libelleCarteStripe =  $conf->global->CGL_STRIPE_LIB_CARTE_PAIEMENT_INSC;
		}

		if (!empty(GETPOST('SMSmodelselected', 'int')) or $action == $ACT_STRIPERELSMS) {
			$_SESSION['StripeNomPayeur'] =  $StripeNomPayeur; 
			$_SESSION['StripeSmsPayeur'] = $StripeSmsPayeur; 
			$_SESSION['StripeUrl'] = $stripeUrl; 			
			$_SESSION['StripeMtt'] = $StripeMtt; 
			$_SESSION['modelmailselected'] = $modelmailselected;
			$_SESSION['id_stripe'] = $id_bull_det_stripe;	
			$_SESSION['libelleCarteStripe'] = $libelleCarteStripe; 			
		}
		print '<a name="AncreMailSms" id="AncreMailSms"></a>';
		print '<div id="DivMailSms" class="fichecenter"  style="background-color:'.$langs->trans("ClPaveSaisie").'">';
		print_titre($langs->trans($titreform));

		// Charger le tiers
		$soc = New Societe ($this->db);
		$soc->id = $bull->id_client;
		$soc->nom = $bull->tiersNom;
		$soc->SMS = $bull->TiersTel;
		if (!class_exists('formSMS')) include_once DOL_DOCUMENT_ROOT.'/core/class/html.formsms.class.php';
		$formSMS = new FormSms($this->db);


		$formSMS->fromtype = 'user';
		$formSMS->fromid   = $user->id;
		$formSMS->fromname = $user->getFullName($langs);
		$formSMS->frommail = $user->user_mobile;
		$formSMS->withfrom=1;
		$formSMS->withform=0;
		$formSMS->withtosocid=$socid;
		$formSMS->withfromreadonly=0;
		// CCA Placer ici  l'utilisation de $soc->thirdparty_and_contact_phone_array sur le modele de $soc->thirdparty_and_contact_email_array
		/*foreach ($soc->thirdparty_and_contact_phone_array(1) as $key=>$value) {
			$liste[$i]=$value;
		}
		$formmail->withtocc=$liste;
*/
		$sendto = GETPOST('sendto', 'alpha');
		if  (!empty($sendto)) $temp = GETPOST('sendto', 'alpha');			
		elseif (!empty($bull->TiersTel)) $temp = $bull->TiersTel;
		else  $temp =  $user->user_mobile;
		
		$formSMS->withto = $temp;
		$formSMS->withbody=1;
		$formSMS->withcancel=1;
		// Prise en charge du Stripe courante
		$bull->stripeModelMail = $modelmailchoisi;
		$bull->stripeNomPayeur = $StripeNomPayeur;
		$bull->stripeMtt = $StripeMtt;
		$bull->stripeMailPayeur = $StripeMailPayeur;
		$bull->stripeUrl = $stripeUrl;
		$bull->libelleCarteStripe = $libelleCarteStripe;
		

		
		// Tableau des substitutions
		$formSMS->substit['__THIRDPARTYREF__']=$object->ref;
		$formSMS->withsubstit	= 1;
		//Variables communes valorisées ';
		$arrayoffamiliestoexclude=array('member', 'objectamount');
		if (! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude=null;
		$substitutionarray = getCommonSubstitutionArray($langs, 0, $arrayoffamiliestoexclude, '');
//verifier substit_lines , substit semble fonctionner
		//Variables inscriptions valorisées ';
		$parameters = array(	
				'mode' => 'formemailwithlines'
			);
		$temptab=array();
		require_once(DOL_DOCUMENT_ROOT.'/custom/cglinscription/core/substitutions/functions_inscription.lib.php');
		inscription_completesubstitutionarray ($temptab,$outputlangs,$bull,$parameters, 2);
		// Variables lignes valorisées ';
		$temptablig=array();
		inscription_completesubstitutionarray ($temptablig,$outputlangs,$bull,$parameters, 1);
		// Separer substit et substit_lines
		$formSMS->substit = array_merge($temptab, $substitutionarray);
		$formSMS->substit_lines =  $temptablig;

		// on utlise gender pour passer l'information Loc ou Insc momentanément, tant qu'un nouveau module Velo n'a pas été fait
		$ancval = $user->gender;
		$user->gender = $bull->type;
		
	// Recherche modele SMS doit être fait ici, par n'est pas fait dans html.formsms.class.php
	if (!class_exists('CglSms')) include_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglsms.class.php';
	$wgsms = new CglSms ($this->db);
	$arraydefaultmessage=$wgsms->getSmsTemplate($this->db, $modelSMS, $user, $langs, $modelmailchoisi);		// If $model_id is empty, preselect the first one
	unset($wgsms);
	$_POST["message"] = $arraydefaultmessage->content;
	$_POST["message_lines"] .= $arraydefaultmessage->content_lines;
   // Tableau des substitutions 
    $formsms->substit['__THIRDPARTYREF__']=$soc->ref;
    // Tableau des parametres complementaires du post
	$formSMS->param['action']=$action;
	$formSMS->param['models']=$modelSMS;
	$formSMS->param['models_id']=$modelmailchoisi;
    $formSMS->param['id']=$soc->id;
	
	$formSMS->param['returnurl']=$_SERVER["PHP_SELF"].'?'.$lb_id.'='.$bull->id;
		
		//print '<a name="AncreMailSms" id="AncreMailSms"></a>';
		print '<br><div id="DiveMailSms" class="fichecenter"  style="background-color:'.$langs->trans("ClPaveSaisie").'">';

	/*$fl_afficheBoiteSaiseDemandeStripe = false;
	$fl_afficheBoiteSaiseDemandeStripe = (!empty($BtStripeComm) or (GETPOST ('SMSmodelselected') and !empty($modelmailchoisi) and !empty($StripeMtt)) or $action == $ACT_STRIPERELSMS);
	$fl_afficheBoiteSaiseDemandeStripe  = ($conf->stripe and ($fl_afficheBoiteSaiseDemandeStripe));
	*/
	$fl_afficheBoiteSaiseDemandeStripe  =  $conf->stripe->enabled;
		$fl_afficheBoiteSaiseDemandeStripe  = ($fl_afficheBoiteSaiseDemandeStripe) && (!empty(strpos(GETPOST('etape', 'alpha'), 'S')) );
		$fl_afficheBoiteSaiseDemandeStripe  = ($fl_afficheBoiteSaiseDemandeStripe)  and (strpos(GETPOST('etape', 'alpha'), 'S') >= 0);

	if ($fl_afficheBoiteSaiseDemandeStripe){
		require_once(DOL_DOCUMENT_ROOT."/custom/cglinscription/class/html.formstripe.class.php");
		//PaveSMS (mail, nom, montant, modèle);
		$wfStrC = new FormStripeCAV($this->db);
		print $wfStrC->SaisieDemandeStripe($StripeNomPayeur, $StripeMtt, '' , $modelmailchoisi, $libelleCarteStripe, 'Sms', $StripeSmsPayeur);			
		unset ($wfStrC);
	}
	else {
		print "<form method=\"POST\" name=\"smsform\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"].'?'.$lb_id.'='.$bull->id."&action=presendsms#AncreMailSms\">\n";
		print $langs->trans('SelectMailModel').$this->select_model($modelmailchoisi, $modelSMS, 'Sms',  'modelmailstripe', 0, 0) ;
		print '<input class="button" type="submit" value="'.$langs->trans('Apply').'" name="SMSmodelselected" id="SMSmodelselected">';

		if (GETPOST("etape", 'alpha') == CglStripe::STRIPE_SMS_APPLY or GETPOST("etape", 'alpha') == CglStripe::STRIPE_SMS_GENERAL) 
			print '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_SMS_APPLY.'">';	
		if (GETPOST("etape", 'alpha') == CglStripe::STRIPE_SMS_APPLY_STRIPE or GETPOST("etape", 'alpha') == CglStripe::STRIPE_SMS_STRIPE) 
			print '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_SMS_APPLY_STRIPE.'">';	
		if (GETPOST("etape", 'alpha') == CglStripe::STRIPE_REL_SMS_STRIPE or GETPOST("etape", 'alpha') == CglStripe::STRIPE_REL_SMS_APPLY_STRIPE) 
			print '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_REL_SMS_APPLY_STRIPE.'">';	
 		print '<input type="hidden" name="token" value="'.newToken().'">';
 	}
  $formSMS->show_form('', 0);
			//Eteindre en ajax les champs Modèle et Destinataire
		if ($fl_afficheBoiteSaiseDemandeStripe) {
			// Apres affichage show_form, eteindre la saisie du destinataire
			print "<script type='text/javascript' language='javascript'>
			$(document).ready(function() {
				document.getElementById('trSmsTo').style.visibility = 'hidden';			
				});
			</script>";	
		}	
print '</div';
 		 print "<form method=\"POST\" name=\"smsform\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."#AncreMailSms>\n";
		print '<input type="hidden" name="'.$lb_id.'" value="'.$bull->id.'">';	
		print '<input type="hidden" name="action" value="presendsms">';	
		print '<input type="hidden" name="token" value="'.newToken().'">';

        print '<div class="center">';
		if (GETPOST("etape", 'alpha') == CglStripe::STRIPE_SMS_APPLY or GETPOST("etape", 'alpha') == CglStripe::STRIPE_SMS_GENERAL) 
			print '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_SMS_ENVOI.'">';
		if (GETPOST("etape", 'alpha') == CglStripe::STRIPE_SMS_APPLY_STRIPE or GETPOST("etape", 'alpha') == CglStripe::STRIPE_SMS_STRIPE) 
			print '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_SMS_ENVOI_STRIPE.'">';	
		if (GETPOST("etape", 'alpha') == CglStripe::STRIPE_REL_SMS_STRIPE or GETPOST("etape", 'alpha') == CglStripe::STRIPE_REL_SMS_APPLY_STRIPE) 	
			print '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_REL_SMS_ENVOI_STRIPE.'">';
        print '<input class="button" type="submit" name="sendSms" value="'.dol_escape_htmltag($langs->trans("SendSms")).'">';
        if ($formsms->withcancel)
        {
            print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            print '<input class="button" type="submit" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
        }
        print '</div>';
	 print "</form>";


		$user->gender = $ancval;
		print '</div>';		
/*
		unset($TraitCommun);		
*/
	} // Preparation_SMS
	
	function select_model($selected, $typemodele, $fl_MailMobile, $htmlname,   $useempty = 0,  $disabled = 0)
	{		
		global $langs;

		$disabled = ($disabled ? ' disabled' : '');	  
        $out='';
		if ($fl_MailMobile == 'Mail') $table = 'c_email_templates';
		else  $table = 'c_sms_templates';
		
        $sql .= "SELECT distinct cem.rowid as rowid , label ";
        $sql.= " FROM ".MAIN_DB_PREFIX.$table." as cem ";
        $sql.= " WHERE active = 1 ";
        if ($typemodele <> 'tous') $sql.= " AND type_template = '".$typemodele."'";
        $sql.= " ORDER BY position";
		
        dol_syslog(get_class($this)."::select_model ");
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" '.$disabled.'  >';			
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {

               $foundselected=false;
				if ($useempty) $out .= '<option value="-1"'.(($value < 0)?' selected':'').'></option>'."\n";
                while ($i < $num)
                {
 
					$obj = $this->db->fetch_object($resql);
					if ($selected == $obj->rowid  )
                    {
                        $out.= '<option value="'.$obj->rowid.'" selected="selected">';
                    }
                    else
					{
                        $out.= '<option value="'.$obj->rowid.'">';
                    }
                    $out.= $obj->label;					
                    $out.= '</option>';
					$i++;
                }
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }
        return $out;
		
	} //select_model
	
	function PreScript()
	{
		print '<script>
		
		function AffBut(o) {
			let tel=document.getElementById("TiersTel").value;
			let mail = document.getElementById("TiersMail").value;
			let objfct = document.getElementById("BullFacturable");
			let facturable;
			if (objfct != null)  facturable= document.getElementById("BullFacturable").value;
			else facturable =0;
			let objCrB = document.getElementById("btCreerBull");
			let objTi = document.getElementById("EnrTiers");

			if (tel.length == 0 && mail.length == 0 ) {
				if (objCrB != null) {
					objCrB.className= "button butActionRefused";
					objCrB.disabled = true;	
				}
				if (objTi != null) {
					objTi.className= "button butActionRefused";
					objTi.disabled = true;	
				}
			}	
			else if ( facturable == -1) {
				if (objCrB != null) {
					objCrB.className= "button butActionRefused";
					objCrB.disabled = true;	
				}
			}
			else {
				if (objCrB != null) {
					objCrB.className= "button butAction";
					objCrB.disabled = false;	
				}
				if (objTi != null) {
					objTi.className= "button butAction";
					objTi.disabled = false;	
				}
			}			

		}
		</script>';		
	} //PreScript
	 
	

	function thirdparty_and_contact_email_array($addthirdparty=0, $email, $id)
    {
        global $langs;
        $contact_emails = $this->contact_property_array('email', 0, $id);
        if ($this->email && $addthirdparty)
        {
            if (empty($this->name)) $this->name=$this->nom;
            // TODO: Tester si email non deja present dans tableau contact
            $contact_emails['thirdparty']=$email;
        }
        return $contact_emails;
   
	} //thirdparty_and_contact_email_array
	
	function contact_property_array($mode='email', $hidedisabled=0, $id)
    {
    	global $langs;

        $contact_property = array();

		dol_syslog('contact_property_array');
        $sql = "SELECT rowid, email, statut, phone_mobile, lastname, poste, firstname";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
        $sql.= " WHERE fk_soc = '".$id."'";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $nump = $this->db->num_rows($resql);
            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($mode == 'email') $property=$obj->email;
                    else if ($mode == 'mobile') $property=$obj->phone_mobile;

					// Show all contact. If hidedisabled is 1, showonly contacts with status = 1
                    if ($obj->statut == 1 || empty($hidedisabled))
                    {
                    	if (empty($property))
                    	{
                    		if ($mode == 'email') $property=$langs->trans("NoEMail");
                    		else if ($mode == 'mobile') $property=$langs->trans("NoMobilePhone");
                    	}

	                    if (!empty($obj->poste))
    	                {
							$contact_property[$obj->rowid] = $property;
						}
						else
						{
							$contact_property[$obj->rowid] = trim(dolGetFirstLastname($obj->firstname,$obj->lastname))." &lt;".$property."&gt;";
						}
                    }
                    $i++;
                }
            }
        }
        else
        {
            dol_print_error($this->db);
        }
        return $contact_property;
    } //contact_property_array
		// Copie danss info_picto
	function info_bulle($texte, $picto, $options) {
		
		static $PassePrem;
		global $conf;
		
		if ('Image' == 'Image') {
			// Recherche de l'image
			// By default, we search $url/theme/$theme/img/$picto
			$url = DOL_URL_ROOT;
			$theme = $conf->theme;

			$path = 'theme/'.$theme;
			if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) $path = $conf->global->MAIN_OVERWRITE_THEME_RES.'/theme/'.$conf->global->MAIN_OVERWRITE_THEME_RES;
			if (preg_match('/^([^@]+)@([^@]+)$/i',$picto,$regs))		{
				$picto = $regs[1];
				$path = $regs[2];	// $path is $mymodule
			}
			// Clean parameters
			if (! preg_match('/(\.png|\.gif)$/i',$picto)) $picto .= '.png';
			// If alt path are defined, define url where img file is, according to physical path
			if (!empty($conf->file->dol_document_root)) {	
				foreach ($conf->file->dol_document_root as $type => $dirroot)	// ex: array(["main"]=>"/home/maindir/htdocs", ["alt0"]=>"/home/moddir/htdocs", ...)
				{
					if ($type == 'main') continue;
					if (file_exists($dirroot.'/'.$path.'/img/'.$picto))
					{
						$url=DOL_URL_ROOT.$conf->file->dol_url_root[$type];
						break;
					}
				} // foreach
			}

			// $url is '' or '/custom', $path is current theme or
			$fullpathpicto = $url.'/'.$path.'/img/'.$picto;
		}
		
		$out = '';
		//$texte = "Ici je met tout le texte que je veux, <b>meme de l\'html<br>et un retour à <br>laa ligne</b> !";
		$out .= '	    <span id="curseur" class="infobulle"></span>';
		$out .= ' <span onmouseover="montre ('."'".$texte."'".');" onmouseout="cache();">';
		$out .= "	<img src='".$fullpathpicto."' ".($options?' '.$options:'').' alt=" "/>
			</span>	';
		
		return $out;

				
	}//info_bulle
	
	/*
	* Obsolette - Utiliser select_typeremise de cglavt/core/class/cglFctCommune.class.php
	*/
	function select_typeremise($value='',$htmlname,$option='',$disabled=false,$useempty='') {
		      global $langs;
        $remfixe="remfixe"; $rempourc="rempourc";

        $disabled = ($disabled ? ' disabled' : '');

        $result = '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'"'.$disabled.' '.$option.'>'."\n";
        if ($useempty) $result .= '<option value="-1"'.(($value < 0)?' selected':'').'></option>'."\n";
        if (($value == 'remfixe') || ($value == 1))
        {
            $result .= '<option value="1" selected>'.$langs->trans("RemiseFix").'</option>'."\n";
            $result .= '<option value="2">'.$langs->trans("RemPourc").'</option>'."\n";
        }
        else
       {
       		$selected=(($useempty && $value != '0' && $value != 'rempourc')?'':' selected');
            $result .= '<option value="1">'.$langs->trans("RemiseFix").'</option>'."\n";
            $result .= '<option value="2"'.$selected.'>'.$langs->trans("RemPourc").'</option>'."\n";
        }
        $result .= '</select>'."\n";
        return $result;
	} //select_typeremise
	
	
	function select_nomremise($selected='',$htmlname, $typerem = 1, $option='',$disabled=false,$useempty='') {
		      global $langs;

		$disabled = ($disabled ? ' disabled' : '');	  
        $out='';
        $TabActPart=array();
        $label=array();
        $sql .= "SELECT distinct crem.rowid as rowid , libelle, fl_type ";
        $sql.= " FROM ".MAIN_DB_PREFIX."cgl_c_raison_remise as crem ";
        $sql.= " WHERE active = 1 ";
        if (!empty($typerem)) $sql.= " AND fl_type = '".$typerem."'";
        $sql.= " ORDER BY ordre";
        dol_syslog(get_class($this)."::select_nomremise ");
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" '.$disabled.' '.$option.' '.$htmloption.'>';			
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
				if ($useempty) $out .= '<option value="-1"'.(($value < 0)?' selected':'').'></option>'."\n";
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
					if ($selected == $obj->rowid && $selected != '-1' )
                    {
                        $out.= '<option value="'.$obj->rowid.'" selected="selected">';
                    }
                    else
					{
                        $out.= '<option value="'.$obj->rowid.'">';
                    }
                    $out.= $obj->libelle;
					if ($obj->fl_type == 1)  $out.= '('. $langs->trans("Euro").')';
					elseif ($obj->fl_type == 2) $out.=  '(%)';
                    $out.= '</option>';
					$i++;
                }
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }
        return $out;
	} //select_nomremise
	
	function select_tva ($selected='',$htmlname, $option='',$disabled=false,$useempty='')
	{
		global $mysoc;		

		$sql = "SELECT rowid, taux ";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as cv";
		$sql.= " WHERE cv.fk_pays = ".$mysoc->country_id;
		$sql.= " AND cv.active = 1";
		$resql = $this->db->query($sql);
		$out = '';
		if ($resql)
		{
			 $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'" '.$disabled.' '.$option.' '.$htmloption.'>';			
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
				if ($useempty) $out .= '<option value="-1"'.(($selected < 0)?' selected':'').'></option>'."\n";
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
					if ($selected == $obj->rowid  )
                    {
                        $out.= '<option value="'.$obj->rowid.'" selected="selected">';
                    }
                    else
					{
                        $out.= '<option value="'.$obj->rowid.'">';
                    }
                    $out.= $obj->taux;
					$out.=  '(%)';
                    $out.= '</option>';
					$i++;
                }
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }
        return $out;
	} //select_tva

	/*
	*	Préparer l'affichage de l'écran de saisie des poids/taille en masse
	*
	*	@parametersobjet	$bull	Objet bulletin
	*/
	function html_PoidsTaille($bull, $origine="", $Nbligdeb, $Nbligfin)
	{
		global $user, $conf, $langs, $mysoc;
		global $event_filtre_car_saisie;

		$wfctc = new CglFonctionCommune($db);
		$wcgl = new CglInscription($db);
		$out = "";	
		$out .= '<div class="div-table-responsive">';
		$out .= '<div ">'."\n";
		$out .= '<form id="Participations" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
		$out .= '<input type="hidden" name="token" value="'.newToken().'">'."\n";
		$out .= '<input type="hidden" name="id" value="'.$bull->id.'">'."\n";
		if (!empty($origine))
			$out .= '<input type="hidden" name="Dolibarr" value="oui">'."\n";
		$out .= "\n";
/*
		$out .= '<div class="conteneur center" >';
		$out .= '<div class="flex">'.$mysoc->name.'<p>'.$mysoc->address.'<p>'.$mysoc->zip.' '.$mysoc->town.'<p>Tel : '.$mysoc->phone.'<p>'.$mysoc->email.'</div>';
		$out .= '<div class="flex">';
		$out .= '<img id="mysoc-info-header-logo" style="max-width:100%" alt="" src="'.DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL)).'">';
		$out .= '</div>';
		$out .= '</div>';
		$out .= "\n";
*/
		if ($bull->type == 'Loc') {
			$objettraite="contrat";
			$nbcolonne=10;
		}
		elseif ($bull->type == 'Insc') {
			$objettraite="bulletin";
			$nbcolonne=9;
		}
		if (!isset($bull->lines) or empty($bull->lines)) {
				$out .= $langs->trans('Pas d"activités')." - ".$objettraite.":".$bull->ref;
				exit;
			
		}
		if ($bull->type == 'Loc') {
//			$out .= '<div class="tabBar " style="background-color:#FAF0E6;width:60%;align:center;margin-left:400px">';
			$out .= '<div class="tabBar " style="background-color:#FAF0E6;">';
		}
		elseif ($bull->type == 'Insc') {
//			$out .= '<div class="tabBar " style="background-color:#FAF0E6;width:50%;align:center;margin-left:500px">';
			$out .= '<div class="tabBar " style="background-color:#FAF0E6;">';
		}
		$out .= "\n";
		$out .= '<table class="liste" id="Niv2_ListeParticip" width="100%"><tr>';
		// affiche la barre grise des champs affichés
		$out .= "\n";
		$out .= '<th class="liste_titre"  colspan = '.$nbcolonne.'><b>';
		$out .= $langs->trans('LbPartPoidsTaille', ($bull->type == 'Loc')? 'contrat':'bulletin',  $bull->ref);
		$out .= '</b></th></tr>';	
		$out .= "\n";
		$out .= '<tr><th class="liste_titre"  colspan = '.$nbcolonne.'>';
		$out .= $langs->trans('LbConsigne');
		$out .= '</th></tr>';
		$out .= '<tr class="liste_titre">';	
		if ($bull->type == 'Insc') 
		{			
			$out .= getTitleFieldOfList("Date",'','','','','','','');
			$out .= getTitleFieldOfList("Depart",'','','','','','','');
			$out .= getTitleFieldOfList("Lieu",'','','','','class="lieutaille"','','');
		}
		elseif ($bull->type == 'Loc') 
		{
			$out .= getTitleFieldOfList("Date",'','','','','','','');
			$out .= getTitleFieldOfList("Materiel",'','','','','','','');
			$out .= getTitleFieldOfList("NumVelo",'','','','','','','');
			$out .= getTitleFieldOfList("Complement",'','','','','','','');
		}
		$out .= getTitleFieldOfList("Nom_Prenom",'','','','','','','');
		if ($bull->type == 'Insc') 
			$out .= getTitleFieldOfList("Age",'','','','','','','');
		$out .= getTitleFieldOfList("Taille",'','','','','','','');
		if ($bull->type == 'Insc') 
			$out .= getTitleFieldOfList("Poids",'','','','','','','');
		if ($bull->type == 'Loc') {
			$out .= getTitleFieldOfList("Duree",'','','','','','','');
		}
		$out .= getTitleFieldOfList("TiPrix",'','','','','','','');
		$out .= getTitleFieldOfList("Observation",'','','','','','','');
		$out .= '</tr>';
		$NbLigne = 0;
		
		// Appel Vérification Conflit de location par JSON
		// modification pour voir method":"getContacts" par method":"get"
			$url = DOL_MAIN_URL_ROOT.'/custom/cglinscription/ajaxconflitvelo.php';
		//	?? récupérer en dynamique l'id de la ligne - $htmlname ="NumVelo['".$line->id."']";

$out .= '<script>
			let tabAncColor = [];
			$(document).ready(function() {
				var autoselect = 0;
				var options = [];
				$(".NumVelo").change(function(event) {
					var idElemVelo = event.currentTarget.id;						
					var idElemMateriel=idElemVelo.replace("NumVelo","Materiel" );
					elemMateriel = document.getElementById (idElemMateriel);
					elemVelo = document.getElementById (idElemVelo);
					var argRefMateriel=elemMateriel.value;
					var argRefVelo=elemVelo.value;	
					var argidBullDet=idElemVelo.replace("NumVelo[","" );
					argidBullDet=argidBullDet.replace("]","" );

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
				
				$(".NumVelo").blur(function(event) {
					let idbulldet = event.currentTarget.id.replace ("NumVelo[","").replace ("]","");
					var iddiv= "divInfoConflitVelo[".concat(idbulldet).concat("]");
					let idElemVelo = event.currentTarget.id;
					if (tabAncColor[idbulldet] == "red") {
							document.getElementById (idElemVelo).style.color="red";
							document.getElementById (idElemVelo).style.fontWeight="bold";
							document.getElementById(iddiv).style.display = "block";
					tabAncColor[idbulldet] = "";
					}
					else if (event.currentTarget.value.length != 3) 	{
						document.getElementById (event.currentTarget.id).style.background="red";					
					}	
				});			
				
				$(".NumVelo").click(function(event) {					
					var idbulldet = event.currentTarget.id.replace ("NumVelo[","").replace ("]","");
					tabAncColor[idbulldet] = document.getElementById (event.currentTarget.id).style.color;
					var iddiv= "divInfoConflitVelo[".concat(idbulldet).concat("]");
					document.getElementById (event.currentTarget.id).style.color="black";
					document.getElementById (event.currentTarget.id).style.fontWeight="normal";
					document.getElementById(iddiv).style.display = "none";	
					document.getElementById (event.currentTarget.id).style.background="white";							
					//document.getElementById (iddiv).childNodes[0].title = "";
						
				});
			}); 

			
			
			function runJsCodeForEventarg_NumVelo(obj, idElemVelo) {
				let method = obj.method;
				let url = obj.url;
				let id = obj.id;
				let htmlname = obj.htmlname;
				let response = "";
				
				let ValBulldet_conflitlocal =  VerifConflitLocal(idElemVelo);
				$.getJSON("'.$url.'",
					obj.params,
					function(response) {
						let idbulldet = idElemVelo.replace ("NumVelo[","").replace ("]","");
						let iddiv= "divInfoConflitVelo[".concat(idbulldet).concat("]");
						//var iddivConflit= "divInfoConflitVelo[".concat(ValBulldet_conflitlocal).concat("]");
						if (response != null  ||	 ValBulldet_conflitlocal > 0) {
							document.getElementById (idElemVelo).style.color="red";
							document.getElementById (idElemVelo).style.fontWeight="bold";
							document.getElementById(iddiv).style.display = "block";
							titretemp = "'.$langs->trans("VelosEnConflit").'".concat(" ")
							if (response != null) titretemp  = titretemp.concat(response);
							if (response != null && ValBulldet_conflitlocal > 0 )  titretemp  = titretemp.concat(" et ");
							if (ValBulldet_conflitlocal > 0) titretemp = titretemp.concat("Bulletin courant");
								document.getElementById (iddiv).childNodes[0].title = titretemp;
						}
						else {
							document.getElementById (idElemVelo).style.color="black";
							document.getElementById (idElemVelo).style.fontWeight="normal";
							tabAncColor[idbulldet] = "";
							document.getElementById(iddiv).style.display = "none";
						}
					},
					function(error) {
						  console.log(error.status);
						  console.log(error.statusText);
						  console.log(error.headers);
					});
			}
			
			function VerifConflitLocal(idElemVelo) {
				var valbulldet=idElemVelo.replace("NumVelo[","" );
				valbulldet=valbulldet.replace("]","" );
				var idservice = idElemVelo.replace("NumVelo","Materiel" );
				var elemService = document.getElementById(idservice);
				var valservice = elemService.value;
				var elemNumVelo = document.getElementById(idElemVelo);
				var valNumVelo = elemNumVelo.value;
				var flg_retourl = 0;			
				var tabElemNumVelo = document.getElementsByClassName("NumVelo");
				for (const ElemClassNumVelo of tabElemNumVelo) {
					 var valNumVelolocal = ElemClassNumVelo.value;
					 var identNumVelo = ElemClassNumVelo.id;
					var valbulldetlocal=identNumVelo.replace("NumVelo[","" );
					valbulldetlocal=valbulldetlocal.replace("]","" );
					var idservicelocal = identNumVelo.replace("NumVelo","Materiel" );
					var elemServiceLocal = document.getElementById(idservicelocal);
					var valServiceLocal = elemServiceLocal.value;

					 if (valbulldetlocal == valbulldet) continue;
					 if (valServiceLocal == valservice &&
								valNumVelolocal == valNumVelo) {
							flg_retourl = valbulldetlocal;
								}
				}
				return flg_retourl;
			}

		</script>';

		$out .= '<script> 
		function VerifIdentmat (o) {
				if (o.value.length != 3) {
					 o.style.background="red";
					 let idbulldet = o.id.replace ("NumVelo[","").replace ("]","");
					 let iddiv= "divInfoConflitVelo[".concat(idbulldet).concat("]");
					 document.getElementById(iddiv).style.display = "none";							
					
					alert ("ATTENTION - 3 caracteres obligatoires");
				}
				else  o.style.background="white";
			}
		function EffaceChamp(o) {				
				o.value = "";
				o.style.color="black";
			}
		function RemetVide(o) {
				if (o.value == "" ) {
					o.style.color="grey";
					o.value=o.defaultValue;
				}					
			}
		</script>
		<style>
		.NumVelo {
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
		$out .= '<input type="hidden" value="'.$bull->id.'"  id="idbull"  name="Materiel['.$bull->id.']" ></td>';
		$Nbligcur = $Nbligdeb;
		$i=0;
		foreach($bull->lines as $line) {		
			if ($line->type_enr <> 0 or $line->action == 'S' or $line->action == 'X') continue;
			if ($Nbligcur >= $Nbligfin) break;
			if ($i++ < $Nbligdeb) continue;
			$Nbligcur++;
			$out .= '<tr>';
			$out .= '<input type="hidden" class="idline" name="idline['.$line->id.']" value="'.$line->id.'">'."\n";
			if ($bull->type == 'Loc') 
			{	
				$out .= '<td>'.$wfctc->transfDateFr( $line->dateretrait) .'</td>';	
				$out .= '<td>'.$line->service;
				$out .= '<input type="hidden" value="'.$line->fk_service.'"  id="Materiel['.$line->id.']"  name="Materiel['.$line->id.']" >';
				$out .= '</td>';
				if ($line->fl_conflitIdentmat == true) {
					// Mettre rouge et gras
					$style = "style='color:red;font-weight:bold;width:50px;'";
				}	
				else $style = "style='width:50px;'";
				$out .= '<td>';				
				$out .= '<div class="FlexIdentVelo">';

				$out .= '<div class="FlexIdentVeloItem" ><input class="NumVelo "   value="'.$line->identmat.'" type="text" ';
					$out .= 'id="NumVelo['.$line->id.']"  name="NumVelo['.$line->id.']" '.$style.' onchange="VerifIdentmat(this)"></div>';
				if ($line->fl_conflitIdentmat	== true  ) {
					$out .= '<div class="FlexIdentVeloItem"  id="divInfoConflitVelo['.$line->id.']"  >'.info_admin($langs->trans("VelosEnConflit", $line->lstCntConflit),1).'&nbsp</div>';
				}
				else
					$out .= '<div class="FlexIdentVeloItem" style="display:none"  id="divInfoConflitVelo['.$line->id.']"  >'.info_admin($langs->trans("VelosEnConflit", $line->lstCntConflit),1).'&nbsp</div>';

				$out .= '</div>'; // fin de FlexIdentVelo
				$out .= '</td>';

//				$out .= '<td><input class="flat" class="NumVelo"   value="'.$line->identmat.'" type="text" name="NumVelo['.$line->id.']" ></td>';
				$out .= '<td><input class="flat" class="Marque"   value="'.$line->marque.'" type="text" name="Marque['.$line->id.']" ></td>';
			}
			elseif ($bull->type == 'Insc') 
			{	
				$out .= '<td>'.$wfctc->transfDateFr( $line->activite_dated) .'</td>';	
				$out .= '<td>'.$line->activite_label.'</td>';
				$out .= '<td class="lieutaille" >'.$line->activite_lieu.'</td>';
			}
			// Si NomPrenom Vide ou egal au nom du tiers, le mettre en grise
			// L'effacer lors du click dans la zones
			// S'il reste egal au nom du tiers, remettre en noir à la sortie
			$NomPrenom = $line->NomPrenom;
		if (empty($line->NomPrenom) or $line->NomPrenom == $bull->tiersNom) {
				$style = 'style="color:grey"';
				if (empty($NomPrenom)) 	{
					$NomPrenom = $bull->tiersNom;
				}
//				$fctclick = "EffaceChamp(this)";	$fctchange = "RemetVide(this)";
				$fctclick = "";	$fctchange = "";
			}
			else {
				$style = $fctclick =  $fctchange = '';
			}
			$out .= '<td><input class="flat" class="NomPrenom"   value="'.$NomPrenom.'" type="text" 
					name="NomPrenom['.$line->id.']" '.$style.' onclick="'.$fctclick.'" onchange="'.$fctchange.'"></td>';
			if ($bull->type == 'Insc') 
			{
				if (empty($line->PartAge) or $line->PartAge == -1 ) $temp = 99;
				else $temp =$line->PartAge;	
				$out .= '<td>'.$wcgl->select_age($temp, 'Age['.$line->id.']',1,0, "", "Age").'</td>';				
			}
			$out .= '<td><input class="flat" class="Taille"  style="width:60px;" value="'.$line->PartTaille.'" type="text" name="Taille['.$line->id.']" ></td>';
			if ($bull->type == 'Insc') 
				$out .= '<td><input class="flat" class="Poids"  style="width:60px;"  value="'.$line->PartPoids.'" type="text" name="Poids['.$line->id.']" ></td>';
			if ($bull->type == 'Loc') {
				$out .= '<td><input class="flat" class="Duree"  style="width:60px;" value="'.$line->duree.'" type="text" name="Duree['.$line->id.']" ></td>';
			}
			$out .= '<td><input class="flat" class="Prix"  style="width:80px;" value="'.$line->pu.'" type="text" name="Prix['.$line->id.']" ></td>';
			$out .= '<td align="left"><textarea cols="40" rows="'.ROWS_1.'" wrap="soft" name="Observation['.$line->id.']" '.$event_filtre_car_saisie.' >';
			$out .= $line->observation.'</textarea></td>';		

			$out .= '</tr>';
			$NbLigne++;
		} // foreach

		if (empty($origine)) {
		//Interlocuteur
			$out .= '<tr></tr><tr>';
			$out .= '<td colspan='.(int)($nbcolonne - 2).'>'.$langs->trans('PoidsTaillInterlocuteur').'</td>';
			$out .= '<td><input class="flat"  value="" type="text" name="Interlocuteur" onchange="AffBt(this)"></td>';
			$out .= '<td colspan=2></td>';
		}
		$moreevent="";
		$disabled="";
		$butActionRefused = "";
		if (empty($origine))  $moreevent='onclick="EnrPoidsTaille(this, `'.$origine.'`,'.$NbLigne.')"';
		if (empty($origine))  $disabled='disabled';
		if (empty($origine))  $butActionRefused='butActionRefused';
		$out .= '</tr><tr><td colspan = '.$nbcolonne.'>';
		$out .= '<input class="button '.$butActionRefused.'" id="btEnrPoidsTaille"  name="btEnrPoidsTaille" type="submit" value="'.$langs->trans("BtLbEnrPoidsTaille").'" '.$disabled.' '.$moreevent.'>';
		$out .= '</td></tr>';
		$out .= '</t>';
		$out .= '</div></form>'."\n";
		$out .= '</div></div>'."\n";
					unset ($wcgl);	
		$out .= '<br>';
		$out .= '<style> .lieutaille{width:20%;	}	</style>';
		$out .= "\n";
		$out .= '<style>
		.conteneur{
		   display: flex;
		   width:50%;
		   align:center;
		}
		.flex{
		   flex-grow: 1;
		}
		</style>';
		$out .= "\n";
		$out .= '<script>
		function AffBt(o) 
		{
			let objBt = document.getElementById("btEnrPoidsTaille");
			if (o.value.length == 0) {
				objBt.className= "button butActionRefused";
				objBt.disabled = true;	
			}
			else {
				objBt.className= "button butAction";
				objBt.disabled = false;	
			}
		};

		</script>'."\n";
		return $out;
	} //html_poidstaille
}//Class

/*
Forme Modale sous div et non tabAge

<!DOCTYPE html> 
<html> 
<head>
<meta charset="UTF-8">
<title>Comment positionner des DIV côte à côte en HTML et CSS ?</title>
<style>
.gauche{
   float: left;
   width:10%
}
.milieu{
   display: inline-block;
   width:10%
}
.droite{
   display: inline-block;
   width:10%
}
.lieu{
   width:20%;
}
.pair{
    background-color:#eceaea;
}
.barretitre{
    background-color:#e2e1e1;
}
</style>
</head>
<body>
<div class="titre">Titre</div>
<div class="conteneur barretitre">
   <div class="gauche">date</div>
   <div class="gauche">départ</div>
   <div class="gauche">lieu</div>
   <div class="gauche">nom</div>
   <div class="gauche">age</div>
   <div class="milieu">taille</div>
   <div class="droite">poids</div>
</div>
<div class="conteneur ">
   <div class="gauche">1/2/22</div>
   <div class="gauche">Grp Titi</div>
   <div class="gauche">Taurac</div>
   <div class="gauche">Pierre</div>
   <div class="gauche">15</div>
   <div class="milieu"><input class=flat id=taille></div>
   <div class="droite"><input class=flat id=poids></div>
</div>
<div class="conteneur pair">
   <div class="gauche">1/2/22</div>
   <div class="gauche">Grp Titi</div>
   <div class="gauche">Taurac</div>
   <div class="gauche">Pierre</div>
   <div class="gauche">15</div>
   <div class="milieu"><input class=flat id=taille></div>
   <div class="droite"><input class=flat id=poids></div>
</div>
</body>
</html>



************************

sauvegarde avant lodif

/*
 	function html_poidstaille1($bull, $origine="")
	{
		global $user, $conf, $langs, $mysoc;
		
		$wfctc = new CglFonctionCommune($db);
		$wcgl = new CglInscription($db);
		$out = "";	

		$out .= '<div class="div-table-responsive">';
		$out .= '<div class="center">'."\n";
		$out .= '<form id="Participations" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
		$out .= '<input type="hidden" name="token" value="'.newToken().'">'."\n";
		$out .= '<input type="hidden" name="id" value="'.$id.'">'."\n";
		$out .= "\n";

		$out .= '<style>
		.conteneur{
		   display: flex;
		   width:50%;
		   align:center;
		}
		.flex{
		   flex-grow: 1;
		}
		</style>';
		$out .= '<div class="conteneur" >';
		$out .= '<div class="flex">'.$mysoc->name.'<p>'.$mysoc->address.'<p>'.$mysoc->zip.' '.$mysoc->town.'<p>Tel : '.$mysoc->phone.'<p>'.$mysoc->email.'</div>';
		$out .= '<div class="flex">';
		$out .= '<img id="mysoc-info-header-logo" style="max-width:100%" alt="" src="'.DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL)).'">';
		$out .= '</div>';
		$out .= '</div>';
		$out .= "\n";
		if ($bull->type == 'Loc') {
			$objettraite="contrat";
			$nbcolonne=4;
		}
		elseif ($bull->type == 'Insc') {
			$objettraite="bulletin";
			$nbcolonne=7;
		}
		if (!isset($bull->lines) or empty($bull->lines)) {
				$out .= $langs->trans('Pas d"activités')." - ".$objettraite.":".$bull->ref;
				exit;
			
		}
		$out .= '<div class="tabBar" style="background-color:#FAF0E6;width:50%;align:center">';
		$out .= "\n";
		$out .= '<table class="liste" id="Niv2_ListeParticip" width="100%"><tbody><tr>';
		// affiche la barre grise des champs affichés
		$out .= "\n";
		$out .= '<style> .lieutaille{width:20%;	}	</style>';
		$out .= "\n";
		$out .= '<th class="liste_titre"  colspan = '.$nbcolonne.'><b>';
		$out .= $langs->trans('LbPartPoidsTaille');
		$out .= '</b></th"></tr>';	
		$out .= "\n";
		$out .= '<tr><th class="liste_titre"  colspan = '.$nbcolonne.'>';
		$out .= $langs->trans('LbConsigne');
		$out .= '</th"></tr>';	
		$out .= '<tr class="liste_titre">';	
		if ($bull->type == 'Insc') 
		{
			
		//	getTitleFieldOfList("Titre",0,'','','','','',0,'','');


			$out .= getTitleFieldOfList("Date",'','','','','','','');
			$out .= getTitleFieldOfList("Depart",'','','','','','','');
			$out .= getTitleFieldOfList("Lieu",'','','','','class="lieutaille"','','');
		}
		elseif ($bull->type == 'Loc') 
		{
			$out .= getTitleFieldOfList("Date",'','','','','','','');
			$out .= getTitleFieldOfList("Materiel",'','','','','','','');
		}
		$out .= getTitleFieldOfList("Nom_Prenom",'','','','','','','');
		if ($bull->type == 'Insc') 
			$out .= getTitleFieldOfList("Age",'','','','','','','');
		$out .= getTitleFieldOfList("Taille",'','','','','','','');
		if ($bull->type == 'Insc') 
			$out .= getTitleFieldOfList("Poids",'','','','','','','');
		$out .= '</tr>';
		$NbLigne = 0;
		foreach($bull->lines as $line) {
			if ($line->type <> 0 or $line->action == 'S' or $line->action == 'X') break;
			$out .= '<tr>';
			$out .= '<input type="hidden" class="idline" name="idline['.$line->id.']" value="'.$line->id.'">'."\n";
			if ($bull->type == 'Loc') 
			{	
				$out .= '<td>'.$wfctc->transfDateFr( $line->dateretrait) .'</td>';	
				$out .= '<td>'.$line->service.'</td>';
			}
			elseif ($bull->type == 'Insc') 
			{	
				$out .= '<td>'.$wfctc->transfDateFr( $line->activite_dated) .'</td>';	
				$out .= '<td>'.$line->activite_label.'</td>';
				$out .= '<td class="lieutaille" >'.$line->activite_lieu.'</td>';
			}
			$out .= '<td><input class="flat" class="NomPrenom"   value="'.$line->NomPrenom.'" type="text" name="NomPrenom['.$line->id.']" ></td>';
			if ($bull->type == 'Insc') 
			{
				if (empty($line->PartAge) or $line->PartAge == -1 ) $temp = 99;
				else $temp =$line->PartAge;	
				$out .= '<td>'.$wcgl->select_age($temp, 'Age['.$line->id.']',1,0, "", "Age").'</td>';				
			}
			$out .= '<td><input class="flat" class="Taille"   value="'.$line->PartTaille.'" type="text" name="Taille['.$line->id.']" ></td>';
			if ($bull->type == 'Insc') 
				$out .= '<td><input class="flat" class="Poids"   value="'.$line->PartPoids.'" type="text" name="Poids['.$line->id.']" ></td>';
			$out .= '</tr>';
			$NbLigne++;
		} // foreach

		//Interlocuteur
		$out .= '<tr></tr><tr>';
		$out .= '<td colspan='.(int)($nbcolonne - 2).'>'.$langs->trans('PoidsTaillInterlocuteur').'</td>';
		$out .= '<td><input class="flat"  value="" type="text" name="Interlocuteur" onchange="AffBt(this)"></td>';
		$out .= '<td colspan=2></td>';
		$out .= '<script>
		function AffBt(o) 
		{
			let objBt = document.getElementById("btEnrPoidsTaille");
			if (o.value.length == 0) {
				objBt.className= "button butActionRefused";
				objBt.disabled = true;	
			}
			else {
				objBt.className= "button butAction";
				objBt.disabled = false;	
			}
		};
		function AffPoidsTailleModale(o) 
		{
			let objBt = document.getElementById("btEnrPoidsTaille");
			if (o.value.length == 0) {
				objBt.className= "button butActionRefused";
				objBt.disabled = true;	
			}
			else {
				objBt.className= "button butAction";
				objBt.disabled = false;	
			}
		};

		function EnrPoidsTaille(this, origine, nbligne)
		{
			if (origine == "modale")
			{
				let tabident = document.getElementsByClass("idline");{
				let tabNom = document.getElementsByClass("NomPrenom");{
				let tabAge = document.getElementsByClass("Age");{
				let tabPoids = document.getElementsByClass("class="Taille"  ");
				let tabtaille = document.getElementsByClass("Taille");
				
		alert(	"tabident_0".concat(tabident[0]));	
				

			};
		};
		</script>';
		$out .= '</tr><tr><td colspan = '.$nbcolonne.'>';
		$out .= '<input class="button butActionRefused" id="btEnrPoidsTaille"  name="btEnrPoidsTaille" type="submit" value="'.$langs->trans("BtLbEnrPoidsTaille").' " disabled onclick="EnrPoidsTaille(this, `'.$origine.'`,'.$NbLigne.')">';
		$out .= '</td></tr></tbody></table>';
		$out .= '</div></form>'."\n";
		$out .= '</div></div>'."\n";
					unset ($wcgl);	
		$out .= '<br>';
		return $out;
	} //html_poidstaille

*/
 /*		
				
				let argid='.$bull->id.';
				let argorigine="&origine=modale";
				// &rowid[5859]=5859
				let argtype="&type='.$bull->type.'";
				// construire les tableaux Taille, Prénom pour les passer à ReqEnrPoidsTaille
				let $tabAge = array();
				let $tabNom = array();
				let $tabPoids = array();
				let $tabTaille = array();
				if ($bull->type == 'Insc') {
				// construire les tableaux Age, Poids pour les passer à ReqEnrPoidsTaille
				}
				$out .= let url="'.DOL_URL_ROOT.'/custom/cglinscription/ReqEnrPoidsTaille.php?ID=".concat(argid);
				url=url.concat(argorigine);
				url=url.concat(argtype);
				url=url.concat(argnom);
				url=url.concat(argage);
				url=url.concat(argpoids);
				url=url.concat(argtaille);
					var	Retour = creerobjet(url); 
			   var tableau = Retour.split('|');

*************************************
la fenetre en html pur
<div class="tabBar" style="background-color:#FAF0E6;width:50%;align:center">
<table class="liste" id="Niv2_ListeParticip" width="100%"><tr>
<th class="liste_titre"  colspan = '.$nbcolonne.'><b>LbPartPoidsTaille</b></th></tr>
<tr><th class="liste_titre"  colspan = '.$nbcolonne.'>LbConsigne</th></tr>	
<tr class="liste_titre"><td>	 getTitleFieldOfLis  Poids</td></tr>
		$NbLigne = 0;
		foreach($bull->lines as $line) {
			if ($line->type <> 0 or $line->action == 'S' or $line->action == 'X') break;
<input type="hidden" class="idline" name="idline['.$line->id.']" value="'.$line->id.'">'."\n
<td>'.$wfctc->transfDateFr( $line->dateretrait) .'</td>	
<td>'.$line->service.'</td>
<td>'.$wfctc->transfDateFr( $line->activite_dated) .'</td>	
<td>'.$line->activite_label.'</td>
<td class="lieutaille" >'.$line->activite_lieu.'</td>
<td><input class="flat" class="NomPrenom"   value="NomPrenom" type="text" name="NomPrenom[id]" ></td>
<td>Age</td>	
<td><input class="flat" class="Taille"   value="PartTaille" type="text" name="Taille[id]" ></td>
<td><input class="flat" class="Poids"   value="PartPoids" type="text" name="Poids[id]" ></td>
</tr>

<tr></tr><tr>
<td colspan='.(int)(nbcolonne - 2).'>PoidsTaillInterlocuteur</td>
<td><input class="flat"  value="" type="text" name="Interlocuteur" onchange="AffBt(this)"></td>
<td colspan=2></td>

</tr><tr><td colspan = '.$nbcolonne.'>
<input class="button butActionRefused" id="btEnrPoidsTaille"  name="btEnrPoidsTaille" type="submit" 
		
		value=BtLbEnrPoidsTaille disabled onclick="EnrPoidsTaille(this, `modale`,3)">
</td></tr>
</table>
</div></form>'
*/

 ?>