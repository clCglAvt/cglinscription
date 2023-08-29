<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com--->
 *
 * Version CAV - 2.8 hiver 2023
 *				- Variable __CGL_URL_POIDS_TAILLE__
 * Version CAV - 2.8.3 printemps 2023 - première étape POST_ACTIVITE
 * Version CAV - 2.8.4 printemps 2023
 *		- Modification du RDV2 en CONSEIL(bug 295)
 *		- Verrue pour enlever https aléatoiremennt en dojlbe (315)
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
 *	\file       htdocs/custum/cglinscription/class/bull.class.php
 *	\ingroup    societe
 *	\brief      File for third party class
 */

/**
 *	 Substitution des variables 
 *	@string $IsDetail	 0 pour le chargement des variables générales t des variabes lignes non valorises (help),
 *						 '1' pour le chargement des variables de lignes de détails,
 *						 '2' pour le chargement des variables spécifiques CGL générales,
 *
 */
 require_once(DOL_DOCUMENT_ROOT."/custom/cglavt/class/cglFctCommune.class.php");

function inscription_completesubstitutionarray (&$substitutionarray,$outputlangs,$object,$parameters, $IsDetail = 0)
{
	global $user, $langs, $db;
	global $cglOrigineDemande;

	//Verrue visant à régler un problème aléatoire du doublement de 'https:' en tête du lien
	if (!empty($object->stripeUrl) and !!(($iposurl = strpos($object->stripeUrl, 'https', 3)) === 'https'))
		$object->stripeUrl = substr($object->stripeUrl, $iposurl);

	$wfcom =  new CglFonctionCommune ($db);
		if ($IsDetail <> 1) {
			$substitutionarraytmp ['__CGL_SIGNATURE__']=$user->signature;
			$substitutionarraytmp ['__CGL_REF__'] = $object->ref;
			$substitutionarraytmp ['__CGL_CLIENT_NAME__'] = $object->tiersNom;
			$substitutionarraytmp ['__CGL_STRIPE_NOM_PAYEUR__'] = $object->stripeNomPayeur;
			$substitutionarraytmp ['__CGL_STRIPE_MONTANT__'] = $object->stripeMtt;
			$substitutionarraytmp ['__CGL_STRIPE_MAIL_PAYEUR__'] = $object->stripeMailPayeur;
			$substitutionarraytmp ['__CGL_STRIPE_URL__'] = $object->stripeUrl;
//			if (empty($object->stripeUrl)) $substitutionarraytmp ['__CGL_STRIPE_URL__'] = $langs->trans('ComptPlusTard');
			if (!isset($object) || $object->type == 'Loc' || $object->type == 'Insc') 
			{
				$substitutionarraytmp ['__CGL_TOTAL_PAYE__'] = number_format($object->paye,2);
				$substitutionarraytmp ['__CGL_TOTAL_FACTURE__'] = number_format($object->ptavecrem,2);	
				$substitutionarraytmp ['__CGL_RESTANT_A_PAYER__'] = number_format($object->solde,2);
				$substitutionarraytmp ['__CGL_URL_POIDS_TAILLE__'] = DOL_MAIN_URL_ROOT.'/public/cglinscription/cgl_participations.php?id='.$object->id;
			}
			
			if (!isset($object) || $object->type == 'Loc') 
			{
				$substitutionarraytmp ['__CGL_LOC_CAUTION__'] = $object->mttcaution;	
				$substitutionarraytmp ['__CGL_LOC_LIEU_RETRAIT__'] = $object->loclieuretrait;
				$substitutionarraytmp ['__CGL_LOC_LIEU_DEPOSE__'] = $object->loclieudepose;
				if (isset($object->locdateretrait)) {
					$date_fr = $wfcom->transfDateFr($object->locdateretrait);
					$jourSem = $wfcom->transfDateJourSem($date_fr);	
					$moisFr = $wfcom->transfDateMoisFr($date_fr);	
					$substitutionarraytmp ['__CGL_LOC_DATE_DEPART__']  = $jourSem.' '.substr($date_fr,0 ,2) .' '.$moisFr;
					$heure=$wfcom->transfHeureEn($object->locdateretrait);	
					$substitutionarraytmp ['__CGL_LOC_HEURE_DEPART__']  = $heure;
					$substitutionarraytmp ['__CGL_LOC_HEURE_FR_DEPART__']  = substr($heure,0,2).'h '.substr($heure,3,2);
				}
				if (isset($object->locdatedepose)) {
					$date_fr = $wfcom->transfDateFr($object->locdatedepose);
					$jourSem = $wfcom->transfDateJourSem($date_fr);	
					$moisFr = $wfcom->transfDateMoisFr($date_fr);	
					$substitutionarraytmp ['__CGL_LOC_DATE_RETOUR__'] = $jourSem.' '.substr($date_fr,0 ,2) .' '.$moisFr;
					$heure=$wfcom->transfHeureEn($object->locdatedepose);	
					$substitutionarraytmp ['__CGL_LOC_HEURE_RETOUR__'] =  $heure;
					$substitutionarraytmp ['__CGL_LOC_HEURE_FR_RETOUR__'] = substr($heure,0,2).'h '.substr($heure,3,2);
				}
			}	

			if (!isset($object) || $object->type == 'Resa') 
			{
				$substitutionarraytmp ['__CGL_RESA_ACT__'] = $object->ResaActivite;
				//$substitutionarraytmp ['__CGL_RESA_SITE__'] = $object->a_suivre;
				//$substitutionarraytmp ['__CGL_RESA_LOC_SITE__'] = $object->a_suivre;
				//$substitutionarraytmp ['__CGL_RESA_SITE_TEL__'] = $object->a_suivre;
				$date_fr = $wfcom->transfDateFr($object->heured);
				$jourSem = $wfcom->transfDateJourSem($date_fr);	
				$moisFr = $wfcom->transfDateMoisFr($date_fr);	
				
				$substitutionarraytmp ['__CGL_RESA_DATE__'] = $jourSem.' '.substr($date_fr,0 ,2) .' '.$moisFr;

				$heure=$wfcom->transfHeureEn($object->heured);		
				$substitutionarraytmp ['__CGL_RESA_HEURE__'] = $heure;
				$substitutionarraytmp ['__CGL_RESA_FR_HEURE__'] = substr($heure,0,2).'h '.substr($heure,3,2);
			}
		}
	// Mise en place des regroupements

	if ($IsDetail == 1) 
	{
		$tabIdAct = array();
		$tabNomAct = array();
		$tabNbEnf = array();
		$tabNbAdlt = array();
		$tabNbPart = array();
		$tabRdv = array();	
		$tabSiteConseil = array();
		$tabSite = array();	
		$tabLocSite = array();	
		$tabMoniteur = array();	
		$tabMonTel = array();	
		$tabMonMail = array();	
		$tabHeure = array();	
		
		$tabNbMat = array();
		$tabTypeMat = array();
		$tabHeureFrd = array();	
		$tabHeureEnd = array();	

		foreach ($object->lines as $objectline) 
		{
			if ($objectline->type_enr == 0 and $objectline->action <> 'X' and $objectline->action <> 'S')
			{
				if (!isset($object) || $object->type == 'Insc') 
				{		
					$tabIdAct[$objectline->id_act] = $objectline->id_act;
					$tabNomAct[$objectline->id_act] = $objectline->activite_label;
					if ($objectline->PartENF == 'Enfant') $tabNbEnf[$objectline->id_act]++;
					if ($objectline->PartENF == 'Adulte') $tabNbAdlt[$objectline->id_act]++;
					$tabNbPart[$objectline->id_act]++;
					
					$tabRdv[$objectline->id_act]	 = $objectline->rdv_lib;
					$tabSiteConseil[$objectline->id_act] = $objectline->rdv2_lib;	
					$tabSite [$objectline->id_act] = $objectline->activite_lieu;	
					$tabLocSite[$objectline->id_act] = $objectline->url_loc_site;	
					if (empty($objectline->act_moniteur_id)) {
						$tabMoniteur [$objectline->id_act] = $langs->trans('SubMonNonDedigne');	 
						$tabMonTel [$objectline->id_act] = ''	;
						$tabMonMail[$objectline->id_act]  ='';
					}
					else {
						$tabMoniteur [$objectline->id_act] = $objectline->act_moniteur_prenom . ' '.$objectline->act_moniteur_nom;	 
						$tabMonTel [$objectline->id_act] = $objectline->act_moniteur_tel;	
						$tabMonMail[$objectline->id_act]  = $objectline->act_moniteur_email;
					}					
$date_fr = $wfcom->transfDateFr($objectline->activite_dated);
$jourSem = $wfcom->transfDateJourSem($date_fr);	
$moisFr = $wfcom->transfDateMoisFr($date_fr);					
					$tabDated[$objectline->id_act]  = $jourSem.' '.substr($wfcom->transfDateFrCourt($objectline->activite_dated),0 ,2) .' '.$moisFr;
					//$tabHeured[$objectline->id_act]  = $objectline->activite_dated;

$heure=$wfcom->transfHeureEn($objectline->activite_dated);				
					$tabHeureEnd[$objectline->id_act]  = $heure;
											
$heure=$wfcom->transfHeureFr($objectline->activite_dated);
					$tabHeureFrd[$objectline->id_act]  = $heure;

$heure=$wfcom->transfHeureFr($objectline->activite_heuref);
					$tabHeureFrf[$objectline->id_act]  = $heure;
$heure=$wfcom->transfHeureEn($objectline->activite_heuref);				
					$tabHeureEnf[$objectline->id_act]  = $heure;									
				}
				if (!isset($object) || $object->type == 'Loc') 
				{	
					$tabNbMat[$objectline->fk_service]++;
					$tabTypeMat[$objectline->fk_service] = $objectline->service;	
				}
			}				
		}

		if (!isset($object) || $object->type == 'Insc') 
		{
			foreach ($tabIdAct as $IdAct) 
			{	
				$substitutionlinearray = array();
				$substitutionlinearray ['__CGL_LG_INSC_ACTIVITE__']		= $tabNomAct[$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_RDV__'] 			= $tabRdv[$IdAct];	
				$substitutionlinearray ['__CGL_LG_SITE_CONSEIL__'] 		= $tabSiteConseil[$IdAct];	
				$substitutionlinearray ['__CGL_LG_INSC_SITE__'] 		= $tabSite [$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_LOC_SITE__'] 	= $tabLocSite[$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_NB__'] 			= $tabNbPart[$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_NB_ENF__'] 		= $tabNbEnf[$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_NB_ADLT__']	 	= $tabNbAdlt[$bIdAct];
				$substitutionlinearray ['__CGL_LG_INSC_MONITEUR_NOM__'] = $tabMoniteur [$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_MONITEUR_TEL__'] = $tabMonTel [$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_MONITEUR_MAIL__'] = $tabMonMail[$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_DATE_DEB__'] 	= $tabDated[$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_HEURE_DEB__'] 	= $tabHeureEnd[$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_HEURE_FR_DEB__'] 	= $tabHeureFrd[$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_HEURE_FIN__'] 	= $tabHeureEnf[$IdAct];
				$substitutionlinearray ['__CGL_LG_INSC_HEURE_FR_FIN__'] 	= $tabHeureFrf[$IdAct];
				$substitutionarraytmp[]=$substitutionlinearray;
			}
		}
		
		if (!isset($object) || $object->type == 'Loc') 			 
		{
			foreach ($tabTypeMat as $key => $val)
			{	
				$substitutionlinearray = array();
				$substitutionlinearray ['__CGL_LG_LOC_NB_MAT__'] = $tabNbMat[$key];
				$substitutionlinearray ['__CGL_LG_LOC_TYPE_MAT__'] = $val;
				$substitutionarraytmp[]=$substitutionlinearray;
			}
		}
	
	}
if (isset($object->SubMailStripe)) $IsDetail = 1;
if (empty($IsDetail)) {
	$substitutionarraytmp ['__CGL_REF__'] = $langs->trans('CGL_REF');
	$substitutionarraytmp ['__CGL_CLIENT_NAME__'] = $langs->trans('CGL_CLIENT_NAME');
	$substitutionarraytmp ['__CGL_STRIPE_NOM_PAYEUR__'] = $langs->trans('CGL_STRIPE_NOM_PAYEUR');
	$substitutionarraytmp ['__CGL_STRIPE_MONTANT__'] = $langs->trans('CGL_STRIPE_MONTANT');
	$substitutionarraytmp ['__CGL_STRIPE_MAIL_PAYEUR__'] = $langs->trans('CGL_STRIPE_MAIL_PAYEUR');
	$substitutionarraytmp ['__CGL_STRIPE_URL__'] = $langs->trans('CGL_STRIPE_URL');
}
if (empty($IsDetail) && (!isset($object) || $object->type == 'Loc' || $object->type == 'Insc') )
{
	$substitutionarraytmp ['__CGL_TOTAL_PAYE__'] = $langs->trans('CGL_TOTAL_PAYE');
	$substitutionarraytmp ['__CGL_TOTAL_FACTURE__'] = $langs->trans('CGL_TOTAL_FACTURE');	
	$substitutionarraytmp ['__CGL_RESTANT_A_PAYER__'] = $langs->trans('CGL_RESTANT_A_PAYER');
	$substitutionarraytmp ['__CGL_URL_POIDS_TAILLE__'] = $langs->trans('CGL_URL_POIDS_TAILLE');
	
}
if (empty($IsDetail) && (!isset($object) || $object->type == 'Loc') )
{	
	$substitutionarraytmp ['__CGL_LOC_CAUTION__']  = $langs->trans('CGL_LOC_CAUTION');	
	$substitutionarraytmp ['__CGL_LOC_LIEU_RETRAIT__']  = $langs->trans('CGL_LOC_LIEU_RETRAIT');
	$substitutionarraytmp ['__CGL_LOC_LIEU_DEPOSE__']  = $langs->trans('CGL_LOC_LIEU_DEPOSE');
	$substitutionarraytmp ['__CGL_LOC_DATE_DEPART__']  = $langs->trans('CGL_LOC_DATE_DEPART'); 
	$substitutionarraytmp ['__CGL_LOC_HEURE_DEPART__']  = $langs->trans('CGL_LOC_HEURE_DEPART'); 
	$substitutionarraytmp ['__CGL_LOC_HEURE_FR_DEPART__']  = $langs->trans('CGL_LOC_HEURE_FR_DEPART'); 			
	$substitutionarraytmp ['__CGL_LOC_DATE_RETOUR__'] = $langs->trans('CGL_LOC_DATE_RETOUR');
	$substitutionarraytmp ['__CGL_LOC_HEURE_RETOUR__'] = $langs->trans('CGL_LOC_HEURE_RETOUR');	
	$substitutionarraytmp ['__CGL_LOC_HEURE_FR_RETOUR__'] = $langs->trans('CGL_LOC_HEURE_FR_RETOUR');
	}
if (empty($IsDetail) && (!isset($object) || $object->type == 'Resa')) 
{	
	$substitutionarraytmp ['__CGL_RESA_ACT__'] =  $langs->trans('CGL_RESA_ACT'); 
	$substitutionarraytmp ['__CGL_RESA_DATE__'] = $langs->trans('CGL_RESA_DATE'); 
	$substitutionarraytmp ['__CGL_RESA_HEURE__'] = $langs->trans('CGL_RESA_HEURE'); 
}	
if (empty($IsDetail) && (!isset($object) || $object->type == 'Insc')) 
{		
	$substitutionarraytmp ['__CGL_LG_INSC_ACTIVITE__'] = $langs->trans('CGL_LG_INSC_ACTIVITE');
	$substitutionarraytmp ['__CGL_LG_INSC_RDV__'] = $langs->trans('CGL_LG_INSC_RDV');	
	$substitutionarraytmp ['__CGL_LG_SITE_CONSEIL__'] = $langs->trans('CGL_LG_INSC_CONSEIL');	
	$substitutionarraytmp ['__CGL_LG_INSC_SITE__'] = $langs->trans('CGL_LG_INSC_SITE');	
	$substitutionarraytmp ['__CGL_LG_INSC_LOC_SITE__'] = $langs->trans('CGL_LG_INSC_LOC_SITE'); 
	$substitutionarraytmp ['__CGL_LG_INSC_NB__'] =  $langs->trans('CGL_LG_INSC_NB'); 
	$substitutionarraytmp ['__CGL_LG_INSC_NB_ENF__'] = $langs->trans('CGL_LG_INSC_NB_ENF'); 
	$substitutionarraytmp ['__CGL_LG_INSC_NB_ADLT__'] = $langs->trans('CGL_LG_INSC_NB_ADLT'); 
	$substitutionarraytmp ['__CGL_LG_INSC_MONITEUR_NOM__'] =  $langs->trans('CGL_LG_INSC_MONITEUR_NOM'); 
	$substitutionarraytmp ['__CGL_LG_INSC_MONITEUR_TEL__'] =  $langs->trans('CGL_LG_INSC_MONITEUR_TEL'); 
	$substitutionarraytmp ['__CGL_LG_INSC_MONITEUR_MAIL__'] =  $langs->trans('CGL_LG_INSC_MONITEUR_MAIL'); 
	$substitutionarraytmp ['__CGL_LG_INSC_DATE_DEB__'] =  $langs->trans('CGL_LG_INSC_DATE_DEB'); 
	$substitutionarraytmp ['__CGL_LG_INSC_HEURE_DEB__'] =  $langs->trans('CGL_LG_INSC_HEURE_DEB'); 
	$substitutionarraytmp ['__CGL_LG_INSC_HEURE_FR_DEB__'] =  $langs->trans('CGL_LG_INSC_HEURE_FR_DEB'); 
	$substitutionarraytmp ['__CGL_LG_INSC_HEURE_FIN__'] =  $langs->trans('CGL_LG_INSC_HEURE_FIN'); 
	$substitutionarraytmp ['__CGL_LG_INSC_HEURE_FR_FIN__'] =  $langs->trans('CGL_LG_INSC_HEURE_FR_FIN'); 
}
if (empty($IsDetail) && (!isset($object) || $object->type == 'Loc')) 
{	
	$substitutionarraytmp ['__CGL_LG_LOC_NB_MAT__'] =  $langs->trans('CGL_LG_LOC_NB_MAT'); 
	$substitutionarraytmp ['__CGL_LG_LOC_TYPE_MAT__'] =  $langs->trans('CGL_LG_LOC_TYPE_MAT'); 
}
	//$substitutionarray = array_merge($substitutionarraytmp, $substitutionarray);

	$substitutionarray = $substitutionarraytmp;
}

?>