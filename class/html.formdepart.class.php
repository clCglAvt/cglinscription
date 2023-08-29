<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 *  Version CAV - 2.6.1.4  du 1 aout 2022 - reprise code de ventilation du moniteur
 * Version CAV - 2.7 - été 2022
 *					 - Remplacer method="GET" par method="POST"
 *					 - Migration Dolibarr V15 et PHP7
 * Version CAV - 2.8 - hiver 2023 - affichage colonne à discretion - déplacement méthode
 *					- dans la sélection des horaire du départ, ajouter les heures 23 et 24h
 * Version CAV - 2.8.3 - printemps 2023
 *		- suppression des guillemets dans les textarea
 * Version CAV - 2.8.5 - printemps 2023
 *			- absence des bulletin d'un départ si celui-ci n'a pas de moniteur (bug 325)
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
require_once  DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once  DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once('../cglavt/class/html.cglFctCommune.class.php');


 class FormCglDepart extends Form  {
 
	
    function __construct($db)
    {
		global $langs, $db;
        $this->db = $db;
		$langs->load('cglinscription@cglinscription');
	}
	
	/*
	* param	$type	vide si l'écran est appelé par Inscription (dans une form existante, avec un type de session et un client connu)
	*				valorisé 'dep' si appelé par listedepart
	* param	$id_act	vide en cas de création, valorisé en cas de modification
	* param $novisible	 vide si l'écran doit être affiché, 1 s'il n'est provisoirement pas affiché
	* param 	$agf objet agfsession contentant les données de l'enregistrement an cas de modification et vide en création
	*/
	function SaisieDepart($type = '', $id_act='' , $affiche = 'non')
	{	
		global $langs, $bull, $conf, $event_filtre_car_saisie;
		global $ENR_DEPART, $ANUL_DEPART;
		global  $agsession , $fl_bullfacture , $FiltrPasse;	
		global $formation, $place, $nb_place, $TypeSessionDep_Agf, $notes, $duree_session, $intitule_custo;
		global $PrixGroupe, $PrixExclusif, $PrixAdulte, $PrixEnfant, $rdvprinc, $alterrdv, $DureeAct, $moniteur_id, $type_tva;
		global $code_ventil, $MtFixe, $Pourcent,  $DateNego;

		$agf = new Agsession($this->db);	
		$calendrier = new Agefodd_sesscalendar($this->db);
		$w1 = new CglFonctionDolibarr($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$ses_formateur = new Agefodd_session_formateur($this->db);
		$formAgefodd =  New FormAgefodd($this->db);	
		$stagiaires = new Agefodd_session_stagiaire($this->db);
		$wdep = new CglDepart($this->db);
		
		if ($id_act) {
			$result = $agf->fetch($id_act);	
			//$this->fetch_agf_calendrier($agf);
			if (!empty($agf->id)) {
				$calendrier->fetch_all($agf->id);
				$ses_formateur->fetch_formateur_per_session($agf->id);
				$resulttrainee = $stagiaires->fetch_stagiaire_per_session($agf->id);		
				if ($resulttrainee < 0) {
					setEventMessage($stagiaires->error, 'errors');
				}			
			}
		}
		else $agf->type_session = 1;	

		// Chargement des arguments de l'URL s'ils sont valorisés
		if (!empty($formation)) $agf->fk_formation_catalogue 	= $formation;
		if (!empty($formation)) $agf->fk_product 	= $formation;
		if (!empty($place)) $agf->fk_session_place 		= $place;
		if (!empty($nb_place)) $agf->nb_place 				= $nb_place;
		if (!empty($TypeSessionDep_Agf)) $agf->type_session 			= $TypeSessionDep_Agf;
		if (!empty($notes)) $agf->notes 					= $notes;
		$agf->status 					= 1;
		if (!empty($duree_session)) $agf->duree_session 			= $duree_session;
		if (!empty($intitule_custo)) $agf->intitule_custo 			= $intitule_custo;
		if ( $agf->type_session == 0) $agf->fk_soc 	= $bull->id_client;
		if (!empty($moniteur_id)) $ses_formateur->lines[0]->formid = $moniteur_id;

/*		preg_match('/^([0-9]+)\/([0-9]+)\/([0-9]+)/',$DepartDate, $reg);
		$annee = $reg[3];	
		$now = dol_now();	
		$annecourant = substr($this->db->idate($now),0,4);	
		if ($annee < $annecourant -1 or   $annee > $annecourant + 1 ) $annee = $annecourant;

		//$annee  = substr($DepartDate, dol_strlen($DepartDate)-4);
		$mois =	substr($DepartDate, 3,2);
		$jour =	substr($DepartDate, 0,2);
		$heure =	substr($HeureDeb, 0,2);
		$min =	substr($HeureDeb, 3,2);

		$heured = dol_mktime($heure,$min,0,$mois,$jour,$annee);
				
		$heure =	substr($HeureFin, 0,2);
		$min =	substr($HeureFin, 3,2);

		$heuref = dol_mktime($heure,$min,0,$mois,$jour,$annee);
		$agsession->dated 					= $heured;
		$agsession->datef 					= $heuref;
		
		$duree_session = ($heuref - $heured);
		$agsession->duree_session 	= $duree_session;
*/
		if (!empty($rdvprinc)) $agf->rdvprinc	= $rdvprinc;
		if (!empty($alterrdv)) $agf->alterrdv 	= $alterrdv;			
		if (!empty($PrixGroupe)) $agf->array_options['options_s_pvgroupe'] 	= price2num($PrixGroupe);
		if (!empty($PrixExclusif)) $agf->array_options['options_s_pvexclu'] 		= price2num($PrixExclusif);
		if (!empty($PrixAdulte)) $agf->array_options['options_s_PVIndAdl'] 	= price2num($PrixAdulte);
		if (!empty($PrixEnfant)) $agf->array_options['options_s_PVIndEnf'] 	= price2num($PrixEnfant);
		if (!empty($rdvprinc)) $agf->array_options['options_s_rdvPrinc'] 	= $rdvprinc;
		if (!empty($alterrdv)) $agf->array_options['options_s_rdvAlter']  	= $alterrdv;
		if (!empty($DureeAct)) $agf->array_options['options_s_duree_act'] 	= price2num($DureeAct);
		
		if (!empty($type_tva)) $agf->array_options['options_s_TypeTVA']  	= $type_tva;
		if (!empty($code_ventil)) $agf->array_options['options_s_code_ventil']  = $code_ventil;		
		if (!empty($MtFixe)) $agf->array_options['options_s_partmonit']  	= price2num($MtFixe);
		if (empty($MtFixe) and !empty($Pourcent)) $agf->array_options['options_s_partmonit']  	= "";
		if (!empty($Pourcent)) $agf->array_options['options_s_pourcent']  	= price2num($Pourcent);
		if (empty($Pourcent) and !empty($MtFixe)) $agf->array_options['options_s_pourcent']  	= "";
		if (!empty($DateNego)) $agf->array_options['options_s_date_nego']  	= $DateNego;
		
		/* chargement des script java */
		$w = new FormCglDepart ($this->db);
		$wf = new FormCglCommun ($this->db);
		if ('FORM'=='FORM') {	
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'">';
			print '<input type="hidden" name="action" value="'.$ENR_DEPART.'">';
			print '<input type="hidden" name="id_bull" value="'.$bull->id.'">';
			print '<input type="hidden" name="id_depart" value="'.$id_act.'">';
			print '<input type="hidden" name="total" value="'.$affiche.'">';
			print '<input type="hidden" name="FiltrPasse" value="'.$FiltrPasse.'">';
			print '<input type="hidden" name="type" value="'.GETPOST('type', 'alpha').'">';
			print '<input type="hidden" name="token" value="'.newtoken().'">';
			
			print '<table id=Niv2_SaisieDepart  width="100%"  style="'.$style.'" )" > <tbody><tr>';
			// paragraphe de saisie d'un nouveau dÃ©part
			if (empty($id_act)) $texte = "CreerDepart";
			else $texte = "Moddepart";
			$wfctcomm->AfficheParagraphe($texte, 2);
			print '</tr><tr><td>';
			print '<div class="tabBar" style="background-color:'.$langs->trans("ClPaveSaisie").'">';
			print '<table id=Niv3_SaisieDepart width=100%><tbody>';
			
			//print '<form name="update" action="'.$_SERVER['PHP_SELF'].'">'."\n";
			
						
			print '</td ></tr>';			
		}
$out .= '<script  type="text/javascript" language="javascript">';		

		
		$out.= 'function creerobjet(fichier)  '; 
		$out.= '{  '; 		
			$out.= '	if(window.XMLHttpRequest) ';  // FIREFOX 
			$out.= '		xhr_object = new XMLHttpRequest();  '; 
			$out.= '	else if(window.ActiveXObject)';  // IE  
			$out.= '		xhr_object = new ActiveXObject("Microsoft.XMLHTTP"); '; 
			$out.= '		else '; 
			$out.= '			return(false); '; 
			$out.= '	xhr_object.open("POST", fichier, false);'; 
			$out.= '	xhr_object.send(null); '; 
			$out.= '	if(xhr_object.readyState == 4)'; 
			$out.= '		return(xhr_object.responseText); '; 
			$out.= '	else'; 
			$out.= '		return(false); '; 
		$out.= '	}'; 
		$out.= "\n"; 
		
		
		$out.= 'function RechInfoSite(o) '; 
		$out.= '{ '; 	
		$out .= '  val = o.value;';
		$out.= '	if (val > -1) { ';  
		$out .=' 		url="ReqInfoSite.php?ID=".concat(val);';
		$out.= "		var	Retour = creerobjet(url); ";
		$out .= '   	var tableau = Retour.split("?",2);'; 
		$out .= ' 		document.getElementById("rdvprinc").value = tableau[0];';
		$out .= ' 		document.getElementById("alterrdv").value = tableau[1];';
		$out.= '	} '; 
		$out.= '	else { '; 
		$out .= ' 		document.getElementById("rdvprinc").value = "";';	
		$out .= ' 		document.getElementById("alterrdv").value = "";';	

		$out.= '	} '; 

		$out.= '} ';
		$out.= "\n"; 

		$out .= 'var 	tabprix	= {"PrixEnfant":0, "PrixAdulte":0, "PrixGroupe":0, "PrixExclusif":0 };'."\n";		
		$out.= 'function RechInfoProd(o) '; 
		$out.= '{ '; 
		$out .= '  val = o.value;';
		$out.= '	if (val > -1) { ';  
		$out .=' 		url="ReqInfoSess.php?ID=".concat(val);';
		$out.= "		var	Retour = creerobjet(url); ";
		$out .= '   	var tableau = Retour.split("?",4);'; 
		$out .= ' 		tabprix["PrixAdulte"] = tableau[0];'; 
		$out .= ' 		tabprix["PrixEnfant"] = tableau[1];';	
		$out .= ' 		tabprix["PrixGroupe"] = tableau[2];';
		$out .= ' 		tabprix["PrixExclusif"] = tableau[3];';
		$out.= '	} '; 
		$out.= '	else { ';  
		$out .= ' 		tabprix["PrixAdulte"] = "";';	
		$out .= ' 		tabprix["PrixEnfant"] = "";';	
		$out .= ' 		tabprix["PrixGroupe"] = "";';
		$out .= ' 		tabprix["PrixExclusif"] = "";';
		$out.= '	} '; 
/*		$out .= '	if (document.getElementById("TypeSessionDep_Agf").value == 0) {';
		$out .= '		document.getElementById("inputPrix1").value = tabprix["PrixGroupe"];';
		$out .= '		document.getElementById("inputPrix2").value = tabprix["PrixExclusif"];';
		$out .= '	}';
		$out .= '	else {';	
*/		$out .= '		document.getElementById("inputPrix1").value = tabprix["PrixAdulte"];';
		$out .= '		document.getElementById("inputPrix2").value = tabprix["PrixEnfant"] ;';
//		$out .= '	}';

		$out.= '} '; 
		$out.= "\n"; 
		
		
		$out.= 'function RechInfoForm(o) '; 
		$out.= '{ '; 
		$out .= '  document.getElementById("'.$ENR_DEPART.'").disabled=true;';			
		$out .= '  val = o.value;';	
		$out.= '	if (val > -1) { ';  
		$out .=' 		url="ReqInfoForm.php?ID=".concat(val);';
		$out.= "		Retour = creerobjet(url); ";
		$out .= '   	var tableau = Retour.split("?",5);'; 
		
		$out .= '		document.getElementById("type_tva").value = +tableau[0];';
		$out .= '		document.getElementById("code_ventil").value = tableau[1];';	
		$out .= '		document.getElementById("MtFixe").value = tableau[2];';
		$out .= '		document.getElementById("Pourcent").value = tableau[3];';	
		$out .= '		document.getElementById("DateNego").value = tableau[4];';		
		$out .= '	}';
		$out .= '  document.getElementById("'.$ENR_DEPART.'").disabled=false;';
		$out.= '} '; 
		$out.= "\n";
		
		$out.= '	 function SaisieExclusive(type) {
							if (type == 2)  { document.getElementById("MtFixe").value = ""  ;}
							if (type == 1)  {  document.getElementById("Pourcent").value = ""  ; };
		}';
		
$out .= '</script>';	
print $out;		
		// SITE
		if ('site' == 'site') {
			if ($affiche == "oui") $size = '';
			else $size = '35%';
			print '<tr><td  width="'.$size.'%"><span class="fieldrequired" >'.$langs->trans("AgfLieu").'</span></td>';
			print '<td>';
			$place = GETPOST('place','int');
			if ( empty($place) and !empty($agf)) $place = $agf->placeid;
			$event = 'onchange="RechInfoSite(this);"';
			print $w1->select_site($place,'place',1, 1, $event);			
			print '&nbsp&nbsp&nbsp&nbsp&nbsp';
			print '<a href="site.php?action=create&url_return='.urlencode($_SERVER['PHP_SELF'].'?action=create').'" title="'.$langs->trans('AgfCreateNewSite').'">'.$langs->trans('DepNvSite').'</a>';
			print '&nbsp'.info_admin($langs->trans("AgfCreateNewSiteHelp"),1,'help').'</td></tr>';
			//print '</td></tr></table></tbody></td></tr>';
		}

			
		// PRODUIT
		if ('PRODUIT' == 'PRODUIT') {
			print '<tr><td  ><span class="flat">'.$langs->trans("AgfFormIntitule").'</span></td>';
			$formation = GETPOST('formation','int');		
			if ( empty($formation) and !empty($agf)) $formation = $agf->fk_formation_catalogue;
			$event = 'onchange="RechInfoProd(this);" ';
			print '<td colspan="3">'.$w1->select_formation($formation, 'formation','intitule',0, $event).'</td></tr>';

		// INTITULE	
			print '<tr><td>' . $langs->trans ( "AgfFormIntituleCust" ) . '</td>';
			print '<td colspan="3"><input size="30" type="text" class="flat" id="intitule_custo" name="intitule_custo" value="'.$agf->intitule_custo.'" /></td></tr>';
			
		// TYPE DE SESSION
		/*	print '<tr><td>'.$langs->trans("AgfFormTypeSession").'</td>';
			$TypeSessionDep_Agf= GETPOST("TypeSessionDep_Agf", 'int'); 
			if (empty($type)) {	
				if (empty($bull)) $TypeSessionDep_Agf = 1;
					else $TypeSessionDep_Agf = $bull->type_session_cgl -1; // diffÃ©rence de 1 entre le stockage dans bull et dans agsession
			}
			elseif (empty($TypeSessionDep_Agf) and !empty($agf)) {
				$TypeSessionDep_Agf= $agf->type_session;
			}
			else $TypeSessionDep_Agf = 1;	
			print '<td colspan="4">'.$this->select_type_session($TypeSessionDep_Agf,'TypeSessionDep_Agf', $agf);
			print '</b>&nbsp';
			print info_admin($langs->trans("DefGroupIndInsc"),1);				
			print '</td></tr>';	
*/			
		}
		
		// CLIENT
		/*		if ($TypeSessionDep_Agf == 0 or $agf->type_session == 0) $style=' style="visibility:visible"';
		else 	 $style='style="visibility:hidden"';
		print '<tr><td><span id="libClient" '.$style.' >';
		print $langs->trans("Client");
		print '</span></td>';
		if ( empty($id_client) and !empty($id_act)) $id_client = $agf->fk_soc;
		elseif (!empty($bull))	$id_client = $bull->id_client;
		$morfilter =  $wf->select_company($id_client,'id_client','',1,'',0,'', 1);	
		print '<td><span id="SaisClient" '.$style.' >'.$morfilter;				
		print '</span></td></tr>';			
*/		
		// STATUS supprimé car non utilisé
		if ('STATUS' == 'STATUT') {
			print '<tr><td valign="top" >'.$langs->trans("AgfStatusSession").'</td>';
			print '<td colspan="2">';
			$defstat=GETPOST('AGF_DEFAULT_SESSION_STATUS', 'int');
			$session_status = GETPOST("session_status", 'int');
			if (empty($id_depart))  $defstat=1;
			elseif (!empty($session_status) and !empty($agf)) $defstat = $session_status;
			elseif (!empty($agf) and empty($agf->status)) $defstat = 0;
			elseif  (!empty($agf) and !empty($agf->status) ) $defstat = $agf->status;
			elseif (empty($agf)) $defstat=$conf->global->AGF_DEFAULT_SESSION_STATUS;
			
			print $formAgefodd->select_session_status($defstat,"session_status",'t.active=1', 1, 0);
			print '</td></tr>';	
		}
		
			print '</td></tr>';			
			//print '<tr><td>';
			//print '<table id="tbdate" widht="100%"><tbody>';
		// DATES
		if ('DATE' == 'DATE') {
			print '<tr><td  width=8%>'.$langs->trans("DepHeureDeb").'</td>';
			print '<td colspan="3" >';
			if (empty($agf)) { $heured = dol_now('tzuser'); }
			else $heured = $calendrier->lines[0]->heured;			
			print $w1->select_date($heured,'HeureDeb',1,1,'',"add",1,1,0,0,'','','',6,24,15);


			print '<script type="text/javascript" language="javascript">
			$(document).ready(function() {
				$("#HeureDeb").change( function () {
						document.getElementById("HeureFin").value = document.getElementById("HeureDeb").value;
					})
				});
			</script>';
		
			print '</td></tr>';
			print '<tr><td width=8%>'.$langs->trans("DepHeureFin").'</td>';
			print '<td colspan="3" >';
			if (empty($agf)) { $heuref = dol_now('tzuser'); }
			else $heuref = $calendrier->lines[0]->heuref;
			//print $formAgefodd->select_time($heuref,'HeureFin');
			print $w1->select_date($heuref,'HeureFin',1,1,'',"add",1,1,0,0,'','','',6,24,15);
//			print '</td></tr>';	
		}
 	
		
		// DUREE
		if ('DURE' == 'DUREE') {
			if (!$agf->array_options['options_s_duree_act']) $agf->array_options['options_s_duree_act']=1;
			print '<tr><td width= 31%><span class="flat">'.$langs->trans("DurAct").'</span></td>';
			print '<td>';
			if ($affiche == 'oui') {
				print '<input size="2" type="text" class="flat" id="DureeAct" name="DureeAct" value="'.number_format ($agf->array_options['options_s_duree_act'],1,","," ").'" />&nbspjours(s)';
			}
			else {				
				$dtemp = number_format ($agf->array_options['options_s_duree_act'],1,","," ");
				print $dtemp."&nbspjour(s)";;	
			}
			
			print '</td></tr>';				
			
		}
		
		//print '</tbody></table id="tbdate">';			
		print '</td></tr>';
		
		//print '<td width="30%">';
		//print '<table id="tbnbe"  widht="100%"><tbody>';
		
		// PRIX
		if ('PRIX' == 'PRIX') {
/*
		if ($TypeSessionDep_Agf == 0 or (!empty($id_act) and $agf->type_session == 0))		{
			$libellePrix1 = $langs->trans("PrixGroupe");
			$htmlname1 = 'PrixGroupe';
			$valPrix1 = $agf->array_options['options_s_pvgroupe'];
						
			$libellePrix2 = $langs->trans("PrixExclusif");
			$htmlname2 = 'PrixExclusif';
			$valPrix2 = $agf->array_options['options_s_pvexclu'];
			print '<input type="hidden" name="PrixAdulte" value="'.$agf->array_options['options_s_PVIndAdl'].'">';
			print '<input type="hidden" name="PrixEnfant" value="'.$agf->array_options['options_s_PVIndEnf'].'">';
			}
		else {
*/
			$libellePrix1 = $langs->trans("PrixAdulte");
			$htmlname1 = 'PrixAdulte';
			$valPrix1 = $agf->array_options['options_s_PVIndAdl'];
			
			$libellePrix2 = $langs->trans("PrixEnfant");
			$htmlname2 = 'PrixEnfant';
			$valPrix2 = $agf->array_options['options_s_PVIndEnf'];
			print '<input type="hidden" name="PrixGroupe" value="'.$agf->array_options['options_s_pvgroupe'].'">';
			print '<input type="hidden" name="PrixExclusif" value="'.$agf->array_options['options_s_pvexclu'].'">';
//		}
		
		// PRIX PRIX 1
			print '<tr><td><span id="lbPrix1">'.$libellePrix1.'</span></td>';
			print '<td  align="" colspan="3"><input size="3" type="text" class="flat" id="inputPrix1" name="'.$htmlname1.'" value="'.$valPrix1.'" />&nbsp'.$langs->trans("Euros").'</td>';
			print '</tr>';
			
		// PRIX PRIX 2
			print '<tr><td> <span id="lbPrix2">'.$libellePrix2.'</span></td>';
			print '<td  align="" colspan="3"><input size="3" type="text" class="flat" id="inputPrix2" name="'.$htmlname2.'" value="'.$valPrix2.'" />&nbsp'.$langs->trans("Euros").'</td>';
			print '</tr>';
		
		print '</tr><tr>';
 		}

		// NB PLACE
		if ('NB PLACE' == 'NB PLACE') {
			print '<td>'.$langs->trans("NumberPlaceAvailableShort").'</td>';
			print '<td colspan="3">';
			//print '<input type="text" class="flat" name="nb_place" size="4" value="'.GETPOST('nb_place','int').'"/>';
			if (empty($id_act)) $nb_place = 10;
			else $nb_place = $agf->nb_place; 
			print '<input type="text" class="flat" name="nb_place" size="4" value="'.$nb_place.'"/>';
			print '</td></tr>';
		}
		//print '</tbody></table id="tbnb">';			

		print '</td></tr><tr>';
		// NOTE
		
		if ('NOTE' == 'NOTE') {
			print '<td  valign="top" >'.$langs->trans("AgfNote").'</td>';
			//print '<td><textarea name="notes"  rows="3" cols="0" class="flat" style="width:260px;" '.$event_filtre_car_saisie.' >'.GETPOST('notes','aplha').'</textarea></td></tr>';
			if ($affiche == 'oui') $size = '500px';
			else $size = '400px';
			print '<td  colspan="3"><textarea name="notes"  rows="3" cols="0" class="flat" style="width:'.$size.';" '.$event_filtre_car_saisie.' >'.$agf->notes.'</textarea></td></tr>';
		}

		// RENDEZ-VOUS PRINCIPAL
		if ('RENDEZ-VOUS PRINCIPAL' == 'RENDEZ-VOUS PRINCIPAL') {
			print '<tr><td>'.$langs->trans("RdvPrincipal").'</td>';
			if ($affiche == 'oui') $size = 80;
			else $size = 50;
			print '<td colspan="3"><input size="'.$size.'" type="text" class="flat" id="rdvprinc" name="rdvprinc" value="'.$agf->array_options['options_s_rdvPrinc'].'" /></td></tr>';
		}
		
		// RENDEZ-VOUS ALTERNATIF
		if ('RENDEZ-VOUS ALTERNATIF' == 'RENDEZ-VOUS ALTERNATIF') {
			print '<tr><td>'.$langs->trans("AlterRdv").'</td>';
			if ($affiche == 'oui') $size = 80;
			else $size = 50;
			print '<td colspan="3"><input size="'.$size.'" type="text" class="flat" id="alterrdv" name="alterrdv" value="'.$agf->array_options['options_s_rdvAlter'].'" /></td></tr>';
		}				
		// FORMATEUR
		if ('FORMATEUR' == 'FORMATEUR') {
			print '<tr><td>'.$langs->trans("UNAgfFormateur").'</td><td colspan=2 >';
			if ( $affiche == 'oui') $events = 'onChange="RechInfoForm(this)";';
			else $events = '';
			if (empty($id_act)) {
				print $this->select_formateur( '', 'moniteur_id', '',1, 0,$events );			
				print '&nbsp';
				print info_admin($langs->trans("InfoMoniteurTVA"),1);
			}
			else {
				if (count($ses_formateur->lines ) == 0) print  $this->select_formateur( '', 'moniteur_id', '',1, 0,$events );
				else 
					foreach ( $ses_formateur->lines as $line) {
						print $this->select_formateur ( $line->formid, 'moniteur_id', '',1, 0, $events );
						print '&nbsp';
						print info_admin($langs->trans("InfoMoniteurTVA"),1);
					}
			}
		}
		if ($affiche == 'oui') {
			// STATUS FISCAL du DEPART
			if ('STFISSCAL' == 'STFISSCAL') {
				print '<tr><td>'.$langs->trans("LbStatFisc").'</td><td colspan="3">';
					if ( $agf->array_options['options_s_TypeTVA'] <> 1 and  (! isset($ses_formateur->lines) or empty($ses_formateur->lines))  )  $agf->array_options['options_s_TypeTVA'] = -1;
					print $this->select_StatutFiscal ( $agf->array_options['options_s_TypeTVA'], 'type_tva', '',1 );	
					print '&nbsp';
					print info_admin($langs->trans("InfoStatutFiscal"),1);
				print '</td>';	
			}
			// CODE VENTILATION*
			if ('VENTIL' == 'VENTIL') {
				print '<tr><td>'.$langs->trans("LbCdVentil").'</td><td colspan="3">';
					print $this->select_Ventil ( $agf->array_options['options_s_code_ventil'], 'code_ventil', '',1 );	
					print '&nbsp';
				print '</td></tr>';		
			}
			
			// Négociation des prix
			if ('NEGO' == 'NEGO') {	
					print '<tr>';			
					$wfctcomm->AfficheParagraphe($langs->trans('NegActuelle'), 4);	
					print '</td></tr><tr><td>';
					print $langs->trans('LbCoutMoniteur');
					print '</td><td colspan="3"> ';	
					print '<input class="flat" id="MtFixe" name="MtFixe" value="'.price2num($agf->array_options['options_s_partmonit']) .'" onchange="SaisieExclusive(1)" >';
					print '</td></tr><tr><td>';
					print $langs->trans('LbPartMoniteur');	
					print '</td><td colspan="3">';	
					print '<input class="flat"  id="Pourcent" name="Pourcent" value="'.price2num($agf->array_options['options_s_pourcent']) .'" onchange="SaisieExclusive(2)"  >';
					print '</td></tr><tr><td>';
					print $langs->trans('LbdateNego');
					print '</td><td colspan="3">';		
					if (empty($agf->array_options['options_s_date_nego'])) $wdate = dol_now('tzuser'); 
					else $wdate = $agf->array_options['options_s_date_nego'];
					$w1 = new CglFonctionDolibarr($db);
					print $w1->select_date($wdate,'DateNego',0,0,1,"",1,1);
					unset ($w1);			
			}
		}										
			print '</tr><tr>';
			$wfctcomm->AfficheParagraphe($langs->trans('TiListParticipants'), 3);
	
			/*
			 * Manage trainees
			*/
			if (!empty($id_act)) {
				print '&nbsp';
				//print '<table class="border" width="100%">';
				
				$nbstag = count($stagiaires->lines);
				
				$nbstagins = $wdep->NbPartDep(2, $id_act);
				
				$nbstagpreins = $wdep->NbPartDep(1, $id_act);
				$nbstagencourins = $wdep->NbPartDep(0, $id_act);
				if (empty($nbstagpreins)) $nbstagpreins = 0;
				if (empty($nbstagins)) $nbstagins = 0;
				if (empty($nbstagencourins)) $nbstagencourins = 0;
				if ($nbstagins + $nbstagpreins + $nbstagencourins < 1) {
					print  '<tr>';
					print '<td style="text-decoration: blink;">' . $langs->trans("AgfNobody") . '</td></tr>';
				}
				else {	
					print '<tr><td  colspan=2 >';
					print  $langs->trans("AgfParticipants");
					print ' :';
					if ($nbstagins > 0)	{			
						print $nbstagins.' '.$langs->trans('LbInscrit');
						if ($nbstagins > 1)	print  's';
					}
					if ($nbstagins > 0 and $nbstagpreins > 0)	 print '	et ';
					if ($nbstagpreins > 0)	{	
						print $nbstagpreins . ' '.$langs->trans('LbPreInscrit');
						if ($nbstagpreins > 1)	print  's';
					}	
					if (($nbstagins > 0 or $nbstagpreins > 0) and $nbstagencourins > 0)	 print '	et ';
					if ($nbstagencourins > 0)	{	
						print $nbstagencourins . ' '.$langs->trans('LbEnCoursInscrit', ($nbstagencourins > 0)?'s':'');
					}					
				}
		
/*				if (1 == 2) { 
					// code supprimé car n'arrive pas à tailler correctement le tableau quand il es invisible
					// affichage complet stagiraire 
					if	(empty( $type)) {
						$alt="afficher la liste complete";
						$img = "1downarrow_selected.png";
					}
					else {
						$alt="réduire la liste complete";
						$img = "1uparrow_selected.png";
					}				
					print img_picto($alt, $img, "onClick=AfficheListePart()") ;
					print '</td><td colspan=2>';
					// Si on vient par la liste des départ et non par le bulletin
					//if	(!empty( $type)){		
					print '<div id="divlistepart"><table id=tabListPart><tbody><tr>';
					for($i = 0; $i < $nbstag; $i ++) {
						print '<td witdth="20px" align="center">' . ($i + 1) . '</td>';
						print '<td "style="border-right: 0px;">';
						// Infos trainee
						if (strtolower($stagiaires->lines [$i]->nom) == "undefined") {
							print $langs->trans("AgfUndefinedStagiaire");
						} else {
							$trainee_info = '<a href="' . dol_buildpath('/agefodd/trainee/card.php', 1) . '?id=' . $stagiaires->lines [$i]->id . '">';
							$trainee_info .= img_object($langs->trans("ShowContact"), "contact") . ' ';
							$trainee_info .= strtoupper($stagiaires->lines [$i]->nom) . ' ' . ucfirst($stagiaires->lines [$i]->prenom) . '</a>';
							$contact_static = new Contact($db);
							$contact_static->civility_id = $stagiaires->lines [$i]->civilite;
							$trainee_info .= ' (' . $contact_static->getCivilityLabel() . ')';															
							print $trainee_info . ' ' . $stagiaires->LibStatut($stagiaires->lines [$i]->status_in_session, 3);								
						}
						print '</td>';
						print '<td style="border-left: 0px; border-right: 0px;">';
						// Info funding company
						if ($stagiaires->lines [$i]->socid) {
							print '<a href="' . DOL_URL_ROOT . '/comm/card.php?socid=' . $stagiaires->lines [$i]->socid . '">';
							print img_object($langs->trans("ShowCompany"), "company");
							if (! empty($stagiaires->lines [$i]->soccode))
								print ' ' . $stagiaires->lines [$i]->soccode . '-';
							print ' ' . dol_trunc($stagiaires->lines [$i]->socname, 20) . '</a>';
						} else {
							print '&nbsp;';
						}
						print '</td>';
						print "</tr>\n";
					}
					print '</tr><tbody>	</table></div></td></tr>';
				}					
*/				
				//print "</table>";
				//print '</div>';
		
			}
		if (!empty($type)) {
			//print '</td></tr><tr><td align="center" colspan="8">';
			
			// afficher les bulletins dont une participation est sur ce départ, le nb paiement		
				print '</td></tr>';	
				print '<tr><td colspan=3>';	
				$objet = $this->AfficheBull($id_act, $agf->status);
		}
		print '</td></tr>';		
		print '<tr><td align=center colspan=3>';		
		if (empty($id_act))
			$texte = "CreerDepart";
		else $texte = "Moddepart";
		print '<input class="button" id="'.$ENR_DEPART.'" name="'.$ENR_DEPART.'" type="submit" value="'.$langs->trans($texte).'"> &nbsp; ';	
			
				
		if (!empty($type) and !empty($id_act) and $fl_bullfacture === false)
		print '<input class="button" name="'.$ANUL_DEPART.'"  id="'.$ANUL_DEPART.'" type="submit" value="'.$langs->trans('BtSupDep').'">';	

		print '</td></tr>';	
		
		print '</form>';
		print '</tbody></table id=Niv3_SaisieDepart>';	

		print '</div>';
		print '</tbody></table id=Niv2_SaisieDepart>';
		print $w->depart_script($id_act, $agf, $type);

	}//SaisieDepart
	
	function AfficheBull($id_act, $fl_DepAnnule)
	{
		global $fl_bullfacture;
		 
		global $langs, $bull, $listbull;
		
		
		print '<table id=Niv4_ListBull width=100% border=1><tbody><tr><td>';
		print '<table id=Niv5_ListBull width=100% ><tbody>';
		print '<tr class="liste_titre">';
		print_liste_field_titre("Bulletin","","","","",'',"","");
		print_liste_field_titre("TiTiers","","","","",'',"","");
		print_liste_field_titre("TiNbPart","","","","",'',"","");
		print_liste_field_titre("TiStatut","","","","",'',"","");
		print_liste_field_titre("TiMttSessionPaye","","","","",'',"","");
		print_liste_field_titre("TiMttSessionDu","","","","",'',"","");
		print_liste_field_titre("TiBuFacture","","","","",'',"","");
		
		print "</tr>\n";
		if (empty($listbull)) {
			$listBull = array();
			$depart = new CglDepart($this->db);
			if (!empty($id_act)) $listBull = $depart->fetchbullbysession($id_act);
		}
		$fl_bullfacture = false;
		foreach ($listBull as $ligbull) {
			$color = '';
			if (!empty($ligbull->facnumber)) {
				$fl_bullfacture = true;
				$color='lightsalmon';
			}
			if ($ligbull->statut == $ligbull->BULL_ABANDON ) $colortexte='style = "color:#A4A4A4"';
			else $colortexte = '';
			print "<tr  ><td style='background-color:".$color.";'><font ".$colortexte.">\n";
			
			print '<a href="inscription.php?id_bull=' . $ligbull->id . '">';
			print $ligbull->ref.'</a>';
			print '</td><td ><font '.$colortexte.">\n";
			print $ligbull->tiersNom;
			print '</td><td><font '.$colortexte.">\n";
			print $ligbull->NbPart;
			print '</td><td><font '.$colortexte.">\n";
			print $ligbull->transStrRegle();
			print '</td><td><font '.$colortexte.">\n";
			print $ligbull->paye;
			print '</td><td><font '.$colortexte.">\n";
			$tot= $depart->CalculTotDepartBull($ligbull, $id_act);
			print $tot;
			print '</td><td><font '.$colortexte.">".$ftgrasdeb."\n";
			if (!empty($ligbull->facnumber)) print $ligbull->facnumber;
			elseif ($ligbull->statut == $ligbull->BULL_ENCOURS) print 'En cours';
			elseif ($ligbull->statut == $ligbull->BULL_ABANDON) print 'Abandonne';
			print "</td></tr>\n";
		}
		print '</td></tr>';	
		print '</tbody></table id=Niv5_ListBull>';
		print '</td></tr>';	
		print '</tbody></table id=Niv4_ListBull>';
	}

	/*
	* fonction reprise de Agefodd, car ne sait pas faire fonctionner envet de ajaxcombr
	*/	
	function depart_script($id_act, $agf,$type) 
	{
		global $langs, $bull;
		
		if (!empty($id_act)) { 	
			$tmpAdulte = $agf->array_options['options_s_PVIndAdl']; 
			$tmpEnfant = $agf->array_options['options_s_PVIndEnf']; 
			$tmpGroupe = $agf->array_options['options_s_pvgroupe']; 
			$tmpExlcu = $agf->array_options['options_s_pvexclu']; 
		}
		else { 
			 $tmpAdulte = 0;
			 $tmpEnfant = 0;
			 $tmpGroupe = 0;
			 $tmpExlcu = 0;
		}
		print '';
		$out= '<script > '."\n";	
		$out.= "\n";
		$out.= '$("#Niv3_SaisieDepart").ready (function(){initPrix('.$tmpAdulte.','.$tmpEnfant.','.$tmpGroupe.','.$tmpExlcu.');});';
		$out .= 'function initPrix(prixadulte, prixenfant, prixgroupe, prixexclusif, type) { '."\n";
		$out .= ' ';	
		if (!empty($id_act)) {	
				$out .= ' 		tabprix["PrixEnfant"] = prixenfant;';
				$out .= ' 		tabprix["PrixAdulte"] = prixadulte;';
				$out .= ' 		tabprix["PrixGroupe"] = prixgroupe;';
				$out .= ' 		tabprix["PrixExclusif"] = prixexclusif;'."\n";
		}
		else {
				$out .= ' 		tabprix["PrixEnfant"] = null;';
				$out .= ' 		tabprix["PrixAdulte"] = null;';
				$out .= ' 		tabprix["PrixGroupe"] = null;';
				$out .= ' 		tabprix["PrixExclusif"] = null;'."\n";			
		}
		/*if (!empty($agf) and $agf->type_session == 0 )  {
			//$out .= '		document.getElementById("SaisClient").style.visibility = "hidden";'."\n";
			$out .= '		document.getElementById("libClient").style.visibility = "hidden";'."\n";		
		}
	
		if ($bull->type_session_cgl == 2)  {
			//$out .= '		document.getElementById("SaisClient").style.visibility = "hidden";'."\n";
			$out .= '		document.getElementById("libClient").style.visibility = "hidden";'."\n";			
		}*/
//		if (empty($type) )
//			$out.= '		$(#"divlistepart").style.visibility="hidden";'."\n";
		$out.= '} ';
		$out.= "\n"; 
		
		
		$out .= '	 function formatNumber(nbr) {';		
		/*
		$out .= '		 var result = NaN;';		
		$out .= "		 if (typeof(nbr) == 'number') {";		
		$out .= '	    		 var st	r = new String(nbr);';		
		$out .= '	     		 var parts = str.split('.');';		
		$out .= "	     		 result = parts[0] + ',' + (parts.length == 1 ? '00' : (parts[1] + '0').substring(0,2));";		
		$out .= '	      }';
		*/
		$out .= '	       return result; }';
    				
		$out.= ' function FermerSsSauv() {alert("Attention, fermeture sans sauvegarde");}';
		$out.= "\n"; 
		$out.= ' function AfficheListePart() {';
			$out.= ' alert (document.getElementById("divlistepart").style.visibility);';
/*		$out.= ' if(document.getElementById("divlistepart").style.visibility == "hidden") {';
		$out.= 'document.getElementById("divlistepart").style.visibility="visible";';
		$out.= ' 	};';
		$out .= 'else {';
		$out.= 'document.getElementById("divlistepart").style.visibility="hidden";';
		$out.= ' 	};';
*/		$out .= '}';
		$out.= "\n"; 

		$out .= 'function Env_TypeSession(o) {';
		$out .= '	if (o.value == 0) {';
		$out .= '		document.getElementById("inputPrix1").value = tabprix["PrixGroupe"];';
		$out .= '		document.getElementById("inputPrix1").name = "PrixGroupe";';
		$out .= '		document.getElementById("lbPrix1").innerHTML = "'.$langs->trans("PrixGroupe").'";';
		$out .= '		document.getElementById("inputPrix2").value = tabprix["PrixExclusif"];';
		$out .= '		document.getElementById("lbPrix2").innerHTML = "'.$langs->trans("PrixExclusif").'";';
		$out .= '		document.getElementById("inputPrix2").name = "PrixExclusif";';			
		//$out .= '		document.getElementById("SaisClient").style.visibility = "visible";';	
		//$out .= '		document.getElementById("libClient").style.visibility = "visible";';			
		$out .= '	}';
		$out .= 'else {';
		$out .= '		document.getElementById("inputPrix1").value = tabprix["PrixAdulte"];';
		$out .= '		document.getElementById("inputPrix1").name = "PrixAdulte";';
		$out .= '		document.getElementById("inputPrix2").value = tabprix["PrixEnfant"] ;';
		$out .= '		document.getElementById("inputPrix2").name = "PrixEnfant";';			
		$out .= '		document.getElementById("lbPrix1").innerHTML = "'.$langs->trans("PrixAdulte").'";';
		$out .= '		document.getElementById("lbPrix2").innerHTML = "'.$langs->trans("PrixEnfant").'";';	
		//$out .= '		document.getElementById("SaisClient").style.visibility = "hidden";';
		//$out .= '		document.getElementById("libClient").style.visibility = "hidden";';	
		$out .= '	}';
		$out .= '}';
		$out.= "\n"; 

		

		

		$out.= '</script> '."\n";
		return $out;
	} //site_script

	/*
	* param	$type	vide si l'écran est appelé par Inscription (dans une form existante, avec un type de session et un client connu)
	*				valorisé si appelé par depart
	* param 	$agf objet agfsession contentant les données de l'enregistrement an cas de modification et vide en création
	*/
	function AfficheDepart($type='',$id_act='', $affiche)
	{	
		global $langs, $bull,  $event_filtre_car_saisie ;
		global $ENR_DEPART;
		global $extrafields, $agsession;

		$wf = new FormCglCommun ($this->db);
		$wfctcomm = new FormCglFonctionCommune($this->db);
		$agf = new Agsession($this->db);	
		$calendrier = new Agefodd_sesscalendar($this->db);
		$ses_formateur = new Agefodd_session_formateur($this->db);
		$stagiaires = new Agefodd_session_stagiaire($this->db);
		if (!empty($id_act)) $result = $agf->fetch($id_act);	
		//$this->fetch_agf_calendrier($agf);
			
		if (!empty($agf->id)) {
			$calendrier->fetch_all($agf->id);
		
			$ses_formateur->fetch_formateur_per_session($agf->id);	
			$resulttrainee = $stagiaires->fetch_stagiaire_per_session($agf->id);		
		}			
		$width = '';
		if (empty($type))  print '<td width="41%">';
		else $width = ' width="100%" ';
		print '<table id=Niv2_SaisieDepart '.$width.'> <tbody><tr>';
		// paragraphe de saisie d'un nouveau dÃ©part
		$wfctcomm->AfficheParagraphe('AfficheDepart', 2);
		print '</tr><tr><td>';
		//print '<div class="tabBar">';
		print '<table id=Niv3_SaisieDepart width=100% class=border><tbody><tbody><tr><td>';
		print '</td ></tr>';
		
		
		// SITE
		if (empty($type ) ) $texte = 'width="20%"' ;
		else $texte = '';
		print '<tr><td '.$texte.' ><span class="flat">'.$langs->trans("AgfLieu").'</span></td>';
		print '<td>';
		$place = GETPOST('place','int');
		if ( empty($place) and !empty($agf)) $place = $agf->placeid;
		print $agf->placecode;
		print '</td></tr>';
		
		// PRODUIT
		print '<tr><td><span class="flat">'.$langs->trans("AgfFormIntitule").'</span></td>';
		$formation = GETPOST('formation','int');		
		if ( empty($formation) and !empty($agf)) $formation = $agf->fk_formation_catalogue;
		print '<td>';
		print $agf->formintitule;
		print '</td></tr>';
		
		// INTITULE
		print '<tr><td>' . $langs->trans ( "AgfFormIntituleCust" ) . '</td>';
		print '<td colspan="3">'.$agf->intitule_custo.'</td></tr>';

		//TYPE SESSION
		//print '<tr><td>'.$langs->trans("AgfFormTypeSession").'</td>';
		//$TypeSessionDep_Agf= GETPOST("TypeSessionDep_Agf", 'int'); 
		//if (empty($type))$TypeSessionDep_Agf = $bull->type_session_cgl -1; // diffÃ©rence de 1 entre le stockage dans bull et dans agsession
		//elseif (empty($TypeSessionDep_Agf) and !empty($agf)) $TypeSessionDep_Agf= $agf->status;
		//print '<td colspan="4">'.($agf->type_session ? $langs->trans('AgfFormTypeSessionInter') : $langs->trans('AgfFormTypeSessionIntra'));		
		//print '</td></tr>';		
		
/*		// CLIENT
		if ($TypeSessionDep_Agf == 0)		{
			print '<tr><td>'.$langs->trans("Client").'</td>';
			if ($type = '') 
				print '<td colspan="4">'.$bull->tiersNom.'</td></tr>';
			else {
				$id_client = GETPOST('id_client','int');
				if ( empty($id_client) and !empty($agf)) $id_client = $agf->socid;
				$morfilter =  $this->select_company($id_client,'id_client','',1,'',0,'', 1);	
//				print '<td>'.$morfilter;
				print $agf->client;
				print '</td></tr>';				
			}
		}
*/		
		// STATUT du DEPART - supprimé
		if ('STATUS' =='STATUT') {
			print '<tr><td valign="top">'.$langs->trans("AgfStatusSession").'</td>';
			print '<td colspan="2">';
			$defstat=GETPOST('AGF_DEFAULT_SESSION_STATUS', 'int');
			$session_status = GETPOST("session_status", 'int');
			if (!empty($session_status) and !empty($agf)) $defstat = $session_status;
			elseif (!empty($agf) and empty($agf->statut)) $defstat = 0;
			elseif  (!empty($agf) and !empty($agf->status) ) $defstat = $agf->status;
			elseif (empty($agf)) $defstat=$conf->global->AGF_DEFAULT_SESSION_STATUS;
			print $agf->statuslib;
			print '</td></tr>';
		}
		
			print '<tr><td  colspan =3 width=55%>';
			print '<table id="tbdate" width="100%" class=border><tbody>';
		// DATES
		if ('DATE' == 'DATE') {
			print '<tr><td width= 31%><span class="flat">'.$langs->trans("Date").'</span></td>';
			print '<td><b>';
			print dol_print_date($calendrier->lines[0]->date_session, 'daytext');	
			print '</b>  de ';
			print  dol_print_date($calendrier->lines[0]->heured, 'hour') . 'h  -  ' . dol_print_date($calendrier->lines[0]->heuref, 'hour').'h';
		}

		
		// DUREE
		if ('DUREE' == 'DUREE') {
			print '<tr><td width= 31%><span class="flat">'.$langs->trans("DurAct").':&nbsp;</span></td>';
			print '<td><b>';
			$dtemp = number_format ($agf->array_options['options_s_duree_act'],1,","," ");
			print $dtemp."&nbspjour(s)";;	
		}
		print '</td></tr></tbody></table id="tbdate">';			
		print '</td>';
		
		
		print '<td  width=45% colspan=2>';
		print '<table id="tbnbe" class=border width="100%" ><tbody> <tbody>';
		// ATTENTION Si on reprend les groupes.
		// PRIX
		if ( empty($agf->type_session)) $agf->type_session = 1;
		if (!empty($id_act) and $agf->type_session == 0)		{
			$libellePrix1 = $langs->trans("PrixGroupe");
			$htmlname1 = 'PrixGroupe';
			$valPrix1 = $agf->array_options['options_s_pvgroupe'];
						
			$libellePrix2 = $langs->trans("PrixExclusif");
			$htmlname2 = 'PrixExclusif';
			$valPrix2 = $agf->array_options['options_s_pvexclu'];
			}
		else {
			$libellePrix1 = $langs->trans("PrixAdulte");
			$htmlname1 = 'PrixAdulte';
			$valPrix1 = $agf->array_options['options_s_PVIndAdl'];
			
			$libellePrix2 = $langs->trans("PrixEnfant");
			$htmlname2 = 'PrixEnfant';
			$valPrix2 = $agf->array_options['options_s_PVIndEnf'];
		}		
		$euro= $langs->trans("Euro");
		
		// PRIX PRIX 1
			print '<tr><td><span id="lbPrix1">'.$libellePrix1.'</span></td>';
			print '<td colspan="2" width="100%">'.$valPrix1.' '.$euro.'</td>';
			print '</tr>';
			
		// PRIX PRIX 2
			print '<tr><td width="31%"> <span id="lbPrix2">'.$libellePrix2.'</span></td>';
			print '<td colspan="2">'.$valPrix2.' '.$euro.'</td>';
			print '</tr>';	
 		
		// NB PLACE
		if ('NB PLACE' == 'NB PLACE') {
			print '<td>'.$langs->trans("NumberPlaceAvailableShort").'</td>';
			print '<td>';
			//print '<input type="text" class="flat" name="nb_place" size="4" value="'.GETPOST('nb_place','int').'"/>';
			if ($agf->nb_place > 1)
				print $agf->nb_place.' places';
			elseif ($agf->nb_place == 1)
				print $agf->nb_place.' place';
			else
				print 'non défini';
			print '</td></tr>';
		}
		print '</tbody></table id="tbnb">';				
		print '</td></tr>';
	// COMMENTAIRES
		print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td><td>';
		print $agf->notes;
		//print '<td  colspan="4"><textarea name="notes"  rows="3" cols="0" class="flat" style="width:260px;"  '.$event_filtre_car_saisie.' >'.GETPOST('notes','aplha').'</textarea></td></tr>';
		
		// RENDEZ-VOUS
		print '</td></tr>';
		print '<tr><td>'.$langs->trans("RdvPrincipal").'</td>';
		print '<td>'.$agf->array_options['options_s_rdvPrinc'].'</td>';

		print '<tr><td>'.$langs->trans("AlterRdv").'</td>';
		print '<td>'.$agf->array_options['options_s_rdvAlter'].'</td>';
	
		// FORMATEURS
		if ('FORMATEURS' =='FORMATEURS') {
			$formateurs = new Agefodd_session_formateur($this->db);
			$nbform = $formateurs->fetch_formateur_per_session($agf->id);		
			print '</td><tr><td>'.$langs->trans("UNAgfFormateur");
			if ($nbform > 0)
				print ' (' . $nbform . ')';
			print '</td>';
			if ($nbform < 1) {
				print '<td style="text-decoration: blink;">' . $langs->trans("AgfNobody") . '</td></tr>';
			}
			else {
				print '<td>';
				
				print '<table class="nobordernopadding">';
				for($i = 0; $i < $nbform; $i ++) {
					print '<tr><td width="50%">';
					// Infos trainers
					print '<a href="' . dol_buildpath('/cglinscription/fiche_moniteur.php', 1) . '?id=' . $formateurs->lines [$i]->formid . '">';
					print img_object($langs->trans("ShowContact"), "contact") . ' ';
					print strtoupper($formateurs->lines [$i]->lastname) . ' ' . ucfirst($formateurs->lines [$i]->firstname) . '</a>';
					print ' ' . $formateurs->lines [$i]->getLibStatut(3);
					print '</td>';
					
					// Print trainer calendar
					if ($conf->global->AGF_DOL_TRAINER_AGENDA) {
						
						print '<td>';
						
						print '<table class="nobordernopadding">';
						
						$alertday = false;
						require_once ('../class/agefodd_session_formateur_calendrier.class.php');
						$trainer_calendar = new Agefoddsessionformateurcalendrier($db);
						$result = $trainer_calendar->fetch_all($formateurs->lines [$i]->opsid);
						if ($result < 0) {
							setEventMessage($trainer_calendar->error, 'errors');
						}
						foreach ( $trainer_calendar->lines as $line ) {
							if (($line->date_session < $agf->dated) || ($line->date_session > $agf->datef))
								$alertday = true;
							print '<tr><td>';
							print dol_print_date($line->heured, 'dayhourtext');
							print '</td></tr>';
							if ($line->heuref != $line->heured) {
								print '<tr><td>';
								print dol_print_date($line->heuref, 'dayhourtext');
								print '</td></tr>';
							}
						}
						// Print warning message if trainer calendar date are not set within session date
						if ($alertday) {
							print img_warning($langs->trans("AgfCalendarDayOutOfScope"));
							print $langs->trans("AgfCalendarDayOutOfScope");
							setEventMessage($langs->trans("AgfCalendarDayOutOfScope"), 'warnings');
						}
						
						print '</table>';
						print '</td>';
					}
					print '</tr>';
				}
				
				print '</table>';
				
				print '</td>';
				print "</tr>\n";
			}
		}			
		

		if ($affiche == 'oui')  {
						// STATUS FISCAL du DEPART
			if ('STFISSCAL' == 'STFISSCAL') {
				print '<tr><td>'.$langs->trans("LbStatFisc").'</td><td>';
					print  $agf->array_options['options_s_TypeTVA'];
				print '</td><td colspan="1">';	
			}
			// CODE VENTILATION*
			if ('VENTIL' == 'VENTIL') {
				print '<tr><td>'.$langs->trans("LbCdVentil").'</td><td>';
					print $agf->array_options['options_s_code_ventil'];	
					print '&nbsp';
				print '</td></tr>';		
			}
			
			
			
			// Négociation des prix
			if ('NEGO' == 'NEGO') {	
					print '<tr>';		
					$wfctcomm->AfficheParagraphe('Négociation actuelle standard', 2);	
					print '</td></tr><tr><td>';
					print $langs->trans('LbCoutMoniteur');
					print '</td><td>';	
					print price2num($agf->array_options['options_s_partmonit']);
					print '</td></tr><tr><td>';
					print $langs->trans('LbPartMoniteur');	
					print '</td><td>';	
					print price2num($agf->array_options['options_s_pourcent']);
					print '</td></tr><tr><td>';
					print $langs->trans('LbdateNego');
					print '</td><td>';		
					$wdate = $agf->array_options['options_s_date_nego'];
					print dol_print_date($wdate, 'day');
					unset ($w1);			
			}		
		}
		
					
		/*
		 * Manage trainees
		*/
		
		print '<tr><td colspan=4>';	
		print '&nbsp';
/*
		print '<table class="border" width="100%">';		
		if ($resulttrainee < 0) {
			setEventMessage($stagiaires->error, 'errors');
		}
		$nbstag = count($stagiaires->lines);
		if ($nbstag > 1) $nbtemp =  ' (' . $nbstag . ')';
		else $nbtemp ='';
		$wf->AfficheParagraphe($langs->trans("AgfParticipants").$nbtemp, 2);	
		print '<tr><td  width="20%" valign="top" ';
		if ($nbstag < 1) {		
			print '<td style="text-decoration: blink;">' . $langs->trans("AgfNobody") . '</td></tr>';
		} else {				
			for($i = 0; $i < $nbstag; $i ++) {
				print '<td witdth="10%" align="center">' . ($i + 1) . '</td>';
				print '<td width="30%"style="border-right: 0px;">';
				// Infos trainee
				if (strtolower($stagiaires->lines [$i]->nom) == "undefined") {
					print $langs->trans("AgfUndefinedStagiaire");
				} else {
					$trainee_info = '<a href="' . dol_buildpath('/agefodd/trainee/card.php', 1) . '?id=' . $stagiaires->lines [$i]->id . '">';
					$trainee_info .= img_object($langs->trans("ShowContact"), "contact") . ' ';
					$trainee_info .= strtoupper($stagiaires->lines [$i]->nom) . ' ' . ucfirst($stagiaires->lines [$i]->prenom) . '</a>';
					$contact_static = new Contact($db);
					$contact_static->civility_id = $stagiaires->lines [$i]->civilite;
					$trainee_info .= ' (' . $contact_static->getCivilityLabel() . ')';															
					print $trainee_info . ' ' . $stagiaires->LibStatut($stagiaires->lines [$i]->status_in_session, 3);								
				}
				print '</td>';
				print '<td style="border-left: 0px; border-right: 0px;"  witdth="60%">';
				// Info funding company
				if ($stagiaires->lines [$i]->socid) {
					print '<a href="' . DOL_URL_ROOT . '/comm/card.php?socid=' . $stagiaires->lines [$i]->socid . '">';
					print img_object($langs->trans("ShowCompany"), "company");
					if (! empty($stagiaires->lines [$i]->soccode))
						print ' ' . $stagiaires->lines [$i]->soccode . '-';
					print ' ' . dol_trunc($stagiaires->lines [$i]->socname, 0) . '</a>';
				} else {
					print '&nbsp;';
				}
				print '</td>';
				print '<td style="border-left: 0px;">';
				// Info funding type
				if ($stagiaires->lines [$i]->type && (! empty($conf->global->AGF_USE_STAGIAIRE_TYPE))) {
					print '<div class=adminaction>';
					print $langs->trans("AgfStagiaireModeFinancement");
					print '-<span>' . stripslashes($stagiaires->lines [$i]->type) . '</span></div>';
				} else {
					print '&nbsp;';
				}
				print '</td>';
				print "</tr>\n";
			}
		}
		print "</table>";
*/		

		$objet = $this->AfficheBull($id_act, $agf->status);		
		print "</table>";
		print '</div>';
										
		print '</td><td colspan="2">';	
		if (!empty($type)) {
			global $MAJ_DEPART;

			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id_bull='.$bull->id.'">';
			print '<input type="hidden" name="action" value="'.$MAJ_DEPART.'">';
			print '<input type="hidden" name="id_depart" value="'.$id_act.'">';
			print '<input type="hidden" name="total" value=oui>';
			print '<input type="hidden" name="type" value="'.GETPOST('type', 'alpha').'">';
			print '<input type="hidden" name="token" value="'.newtoken().'">';

			print '<input class="button" name="'.$MAJ_DEPART.'"  id="'.$MAJ_DEPART.'"  type="submit" value="'.$langs->trans('Editer').'">';	
			print '</form>';
		}

		print '</td></tr>';	

		if (empty($type)) print '</form>';
		print '</tbody></table id=Niv3_SaisieDepart>';
		//print '</div>';
		print '</tbody></table id=Niv2_SaisieDepart>';
//		print '<p></p>';
	}//AfficheDepart
		
	function fetch_agf_calendrier($agf)
	{
		global $langs;
		
		$sql = "SELECT";
		$sql .= " s.rowid, s.date_session, s.heured, s.heuref";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier as s";
		$sql .= " WHERE s.fk_agefodd_session = " . $agf->id;
		$sql .= " ORDER BY s.rowid ASC";
		
		dol_syslog(get_class($this) . "::fetch_all sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			$i = 0;
				$line = new Agefodd_sesscalendar_line();
				
				$obj = $this->db->fetch_object($resql);			
				$line->id = $obj->rowid;
				$line->date_session = $this->db->jdate($obj->date_session);
				$line->heured = $this->db->jdate($obj->heured);
				$line->heuref = $this->db->jdate($obj->heuref);
				$line->sessid = $obj->sessid;
				
				$agf->cal = $line;				
				
//print '<p>DEBUG fetch_agf_calendrier heured :'.$agf->cal->heured.'<</p>'; 
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
			return - 1;
		}
	} //	fetch_agf_calendrier
	
	
	function fetch_agf_prix($id)
	{	
		$sql1 = 'SELECT sce.s_PVIndAdl as ActivitePV_Adlt,  pe.s_PVIndAdl as ProduitPV_Adlt,';
		$sql1.='  sce.s_PVIndEnf as ActivitePV_Enf,  pe.s_PVIndEnf as ProduitPV_Enf, ';
		$sql1.='  sce.s_pvgroupe as ActivitePV_Grp,  pe.s_pvgroupe as ProduitPV_grp, ';
		$sql1.='  sce.s_pvexclu as ActivitePV_Excl, ' ;
		$sql1.=' sc.fk_product as SesProduit' ;
		$sql1.=' FROM '.MAIN_DB_PREFIX.'agefodd_formation_catalogue as sc ';
		$sql1.=' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_formation_catalogue_extrafields as sce on  sce.fk_object = sc.rowid';
		$sql1.=' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as pe on  pe.fk_object = sc.fk_product ';
		 
		$sql1.=" WHERE sc.rowid = '".$id."'";
		
		dol_syslog(get_class($this) . "::fetch_agf_prix sql=" . $sql1, LOG_DEBUG);
		$resql = $this->db->query($sql1);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			$i = 0;
				$objp = $this->db->fetch_object($resql);	
				if (!empty($objp->ActivitePV_Adlt)) $tab['PrixAdulte'] = $objp->ActivitePV_Adlt ;
				elseif (!empty($objp->ProduitPV_Adlt)) $tab['PrixAdulte'] = $objp->ProduitPV_Adlt ;    
				else	$tab['PrixAdulte'] = 0;	

				if (!empty($objp->ActivitePV_Enf)) $tab['PrixEnfant'] = $objp->ActivitePV_Enf ;  
				elseif (!empty($objp->ProduitPV_Enf)) $tab['PrixEnfant'] = $objp->ProduitPV_Enf ;  
				else	$tab['PrixEnfant'] = 0;	
				
				if (!empty($objp->ActivitePV_Grp)) $tab['PrixGroupe'] = $objp->ActivitePV_Grp ;  
				elseif (!empty($objp->ProduitPV_grp)) $tab['PrixGroupe'] = $objp->ProduitPV_grp ;  				
				else	$tab['PrixGroupe'] = 0;
				
				if (!empty($objp->ActivitePV_Grp)) $tab['PrixExclu'] = $objp->ActivitePV_Excl ; 
				else	$tab['PrixExclu'] = 0;	
				
			$this->db->free($resql);
			return $tab;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_agf_prix " . $this->error, LOG_ERR);
			return - 1;
		}
	}		


	function select_type_session($selected='', $htmlname='type_session', $agf, $filter='' )
    {  
		global $conf,$user,$langs;
        $out=''; $num=0;
        //$outarray=array();
		// Construct $out and $outarray
		$out= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'" ';
		$out .= 'onchange="Env_TypeSession(this);"';
		$out .='>'."\n";
		if ($selected  == 0)  $out.= '<option value="0" selected="selected">Groupe constitue</option>';
		else $out.= '<option value="0" >Groupe constitue</option>';
		if ($selected  == 1) $out.= '<option value="1" selected="selected">Individuel</option>';
		else $out.= '<option value="1" >Individuel</option>';
		$out.= '</select>'."\n";		       
        return $out;				
	} // select_type_session
	
	function select_StatutFiscal ( $selected, $htmlname )						
    {  
		global $conf,$user,$langs;
        $out=''; $num=0;
        //$outarray=array();
		// Construct $out and $outarray
		$out= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'" >'."\n";
		$out .= '<option value="-1" ></option>';
		if ($selected  == 0)  $out.= '<option value="0" selected="selected" >'.$langs->trans("ChoixSsTVA").'</option>';
		else $out.= '<option value="0" >'.$langs->trans("ChoixSsTVA").'</option>';
		if ($selected  == 1) $out.= '<option value="1" selected="selected" >'.$langs->trans("ChoixTVA").'</option>';
		else $out.= '<option value="1" >'.$langs->trans("ChoixTVA").'</option>';
		$out.= '</select>'."\n";	       
        return $out;			 	
	} //select_StatutFiscal
	
	
	/**
	 * affiche un champs select contenant la liste des formateurs déjà référéencés.
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	function select_formateur($selectid = '', $htmlname = 'formateur', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;
		
		$sql = "SELECT";
		$sql .= " s.rowid, s.fk_socpeople, s.fk_user,";
		$sql .= " s.rowid, CONCAT(sp.lastname,' ',sp.firstname) as fullname_contact,";
		$sql .= " CONCAT(u.lastname,' ',u.firstname) as fullname_user";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sp";
		$sql .= " ON sp.rowid = s.fk_socpeople";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u";
		$sql .= " ON u.rowid = s.fk_user";
		$sql .= " WHERE s.archive = 0";
		if (! empty($filter)) {
			$sql .= ' AND ' . $filter;
		}
		$sql .= " ORDER BY sp.lastname,u.lastname";
		
		dol_syslog(get_class($this) . "::select_formateur sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {			
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '" '.$event.' >';
			if ($showempty)
				$out .= '<option value=""></option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);
					if (! empty($obj->fk_socpeople)) {
						$label = $obj->fullname_contact;
					}
					if (! empty($obj->fk_user)) {
						$label = $obj->fullname_user;
					}
					
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_formateur " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	function select_Ventil($selected, $htmlname)
	{
		global $conf, $langs;
		
		$sql = "SELECT account_number, p.label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as p, " . MAIN_DB_PREFIX . "accounting_system as m";
		$sql .= " WHERE  fk_pcg_version = pcg_version AND m.active = 1";
		$sql .= " AND account_number like '8%'";
		$sql .= " ORDER BY account_number";

		dol_syslog(get_class($this) . "::select_Ventil sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		
		if ($result) {
			$out = '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '" >';
			$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows($result);

			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);					
					if ($selected > 0 && $selected == $obj->account_number) {
						$out .= '<option value="' . $obj->account_number . '" selected="selected">' . $obj->account_number .' - '.$obj->label. '</option>';
					} else {
						$out .= '<option value="' .$obj->account_number . '">' .  $obj->account_number .' - '.$obj->label. '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_Ventil " . $this->error, LOG_ERR);
			
			return - 1;
		}
	} // select_Ventil

	/*
	* retour le code html permettant d'afficher la liste des BU d'un départ sous forme icone
	*
	*	@param	int		$id			Id du départ
	*	@param	array()	$filters	(champ=> value,...) 
	*
	*/
	function html_chercheBullDepart($id, $filters = '')
	{
		global $listBull;
		
		$listBull = array();
		// format id départ - ref BU/LO  - idbull
		$ret = '';
		$wdep = new CglDepart($this->db);
		
		if (empty($listBull)) $listBull = $wdep->chercheBullDepart($filters);
		if (!is_array($listBull)) return;
		$col=0;
		$num = count($listBull);
		while ($col< $num) {
			if ($listBull[$col] == $id) {
				if (!empty($ret))	$ret .= ' - ';
				$refbull = $listBull[$col+1];
				$idbull = $listBull[$col+2];
				$abandon = $listBull[$col+3];
				$client = $listBull[$col+4];
				$nbadlt = $listBull[$col+5];
				$nbenf  = $listBull[$col+6];
				$texteremises  = $listBull[$col+7];
				$facturable  = $listBull[$col+8];
			if (substr($refbull,0,2) == 'BU' ) $option = 'Bulletin' ; else $option = 'Contrat'; 
				$label = '';
				$label =  $refbull. ' [ ';
				// ajouter Nom client / nb participant adulte / Nb participants enfants pour cette activité
					$label .= $client;
					if ($nbadlt) {
							$label .= ' - '. $nbadlt.'  adl';
							if ($nbadlt > 1)  $label .= '(s)';
					}
					if ($nbenf) {
						$label .= ' - '.$nbenf.' enf';
							if ($nbenf > 1)  $label .= '(s)';
					}
						
				$label .= ' ]';
				if (!$facturable) $label .= ' non facturable';
				if (empty($abandon ) or (!empty($abandon ) and stripos($abandon , "Activit" ) === false  and    stripos($abandon, "Abandon" ) === false))  {
					$img = "object_company.png";	
				}
				else {
					  $img = "statut1.png";
				 }					 
				if (empty($texteremises))$ret .= getNomUrl ($img, $option, 0, $idbull, $label);
				else {
					$label .= ' avec remises';
					$ret .= getNomUrl ($img, $option, 0, $idbull, $label, 'border=1');
				}
			}
			$col +=9;
		}
		return $ret;
	}// html_chercheBullDepart

		
}//Class
 
?>