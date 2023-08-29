<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2017		Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
 * Copyright (C) 2018-2019  Thibault FOUCART		<support@ptibogxiv.net>

 * Version CAV - 2.8 - hiver 2023 - ajout variable  CGL_FIN_ANNEE 
 * Version CAV - 2.8.1	hiver 2023	- ajour variable CGL_ANNEE_CHG_PLANCOMPATABLE
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
 * \file       htdocs/cglinscription/admin/cglinscription.php
 * \ingroup    cglinscription
 * \brief      Page to setup cglinscription module
 */
 
 // TODO - rajouter CGL_LOC_RANDO_MAT 0/1 pour déterminer si on gèrer le matériel prêté et les randons clients

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/html.formcommun.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglstripe.class.php';
$servicename='cglinscription';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'cglinscription', 'CglAvt'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
/*
 * Actions
 */

if (!empty($action) and $action == 'setvalue' && $user->admin)
{
	$db->begin();

		$result = dolibarr_set_const($db, "CGL_TAUX_TVA_DEFAUT", GETPOST('CGL_TAUX_TVA_DEFAUT', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "MAIN_GOOGLE_ACTIONAUTO_CGL_LOC", GETPOST('MAIN_GOOGLE_ACTIONAUTO_CGL_LOC', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_DEPART_SEUIL_RENTABILITE", GETPOST('CGL_DEPART_SEUIL_RENTABILITE', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_TYPEENT_ID_PARTICULIER", GETPOST('CGL_TYPEENT_ID_PARTICULIER', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_NOM_LOCATION", GETPOST('CGL_NOM_LOCATION', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_NOM_INSCRIPTION", GETPOST('CGL_NOM_INSCRIPTION', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_NOM_FACTURATION", GETPOST('CGL_NOM_FACTURATION', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_VENTIL_ACOMPTE", GETPOST('CGL_VENTIL_ACOMPTE', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_STAG_INCONNU", GETPOST('CGL_STAG_INCONNU', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_STRIPE_MAIL_COPY_CLIENT", GETPOST('CGL_STRIPE_MAIL_COPY_CLIENT', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_STRIPE_MAIL_COPY_PAYEUR", GETPOST('CGL_STRIPE_MAIL_COPY_PAYEUR', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		
		
		$result = dolibarr_set_const($db, "CGL_STRIPE_LIB_CARTE_PAIEMENT_INSC", GETPOST('CGL_STRIPE_LIB_CARTE_PAIEMENT_INSC', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_STRIPE_LIB_CARTE_PAIEMENT_LOC", GETPOST('CGL_STRIPE_LIB_CARTE_PAIEMENT_LOC', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;

		$result = dolibarr_set_const($db, "AGF_USE_STAGIAIRE_TYPE", GETPOST('AGF_USE_STAGIAIRE_TYPE', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "FACTURE_DEPOSITS_ARE_JUST_PAYMENTS", GETPOST('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_STRIPE_MAIL_TEMPL_CONF", GETPOST('CGL_STRIPE_MAIL_TEMPL_CONF', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_STRIPE_MAIL_TEMPL_INSC", GETPOST('CGL_STRIPE_MAIL_TEMPL_INSC', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
	
		$result = dolibarr_set_const($db, "CGL_STRIPE_MAIL_TEMPL_RES", GETPOST('CGL_STRIPE_MAIL_TEMPL_RES', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		
		$result = dolibarr_set_const($db, "CGL_SUIVI_PRIORITE_AUTO", GETPOST('CGL_SUIVI_PRIORITE_AUTO', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		
		$result = dolibarr_set_const($db, "STRIPE_PAYMENT_MODE_FOR_PAYMENTS", GETPOST('STRIPE_PAYMENT_MODE_FOR_PAYMENTS', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION", GETPOST('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_USER", GETPOST('STRIPE_USER', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "PROD_ACOMPTE_ACQUIS", GETPOST('PROD_ACOMPTE_ACQUIS', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_FIN_ANNEE", GETPOST('CGL_FIN_ANNEE', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_ANNEE_CHG_PLANCOMPATABLE", GETPOST('CGL_ANNEE_CHG_PLANCOMPATABLE', 'alpha'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_BANK_ACCOUNT_FOR_PAYMENTS", GETPOST('STRIPE_BANK_ACCOUNT_FOR_PAYMENTS', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS", GETPOST('STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_MAX_TRANSAC_RECUP", GETPOST('STRIPE_MAX_TRANSAC_RECUP', 'int'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_ACCOUNT_POUR_FRAIS", GETPOST('STRIPE_ACCOUNT_POUR_FRAIS', 'alpha'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "CGL_SUIVI_FACT_PAYE_STRIPE", GETPOST('CGL_SUIVI_FACT_PAYE_STRIPE', 'alpha'), 'int', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		
		$db->commit();
	if (! $error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		dol_print_error($db);
	}
}

/*
 *	View
 */

$form=new Form($db);
llxHeader('', $langs->trans("CglInscriptionSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' 4 saison - Vélo', $linkback);

$head=cglinscriptionadmin_prepare_head();

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newtoken().'">';
print '<input type="hidden" name="action" value="setvalue">';


print $langs->trans("4SaisonsDesc")."<br>\n";



print '<br>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td class="titlefield" width="60%">'.$langs->trans("ParametreGeneral").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

// FACTURE_DEPOSITS_ARE_JUST_PAYMENTS
print '<tr class="oddeven">';
	print '<tr class="oddeven"><td width="60%">';
print '<span >'.$langs->trans("AcompteJustPaiement").'</span></td><td >';
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("FACTURE_DEPOSITS_ARE_JUST_PAYMENTS", $arrval, $conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS);


//CGL_TAUX_TVA_DEFAUT  - obsolette
/*print '<tr class="oddeven">';
	print '<tr class="oddeven"><td  width="60%">';
	print '<span class="fieldrequired">'.$langs->trans("TauxTVAFrancais").'</span></td><td>';
	//$wfc = new FormCglCommun($db);
	//print $wfc->select_tva ($conf->global->CGL_TAUX_TVA_DEFAUT, 'CGL_TAUX_TVA_DEFAUT','',false,1);
	//unset ($wfc);
	print '<input class="minwidth300" type="text" name="CGL_TAUX_TVA_DEFAUT" value="'.$conf->global->CGL_TAUX_TVA_DEFAUT.'">';
	print '</td><td></td></tr>';
*/	
	
//MAIN_GOOGLE_ACTIONAUTO_CGL_LOC - non utilisé
/*print '<tr class="oddeven">';
	print '<tr class="oddeven"><td  width="60%">';
	print '<span class="fieldrequired">'.$langs->trans("GoogleLoc").'</span></td><td>';
  if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('MAIN_GOOGLE_ACTIONAUTO_CGL_LOC');
  } else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("AGF_USE_STAGIAIRE_TYPE", $arrval, $conf->global->MAIN_GOOGLE_ACTIONAUTO_CGL_LOC);
  }	
	print '</td><td></td></tr>';
*/	
	
print '</table>';
print '<br>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ParametreInscription").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td></td>';
print "</tr>\n";

//CGL_DEPART_SEUIL_RENTABILITE
print '<tr class="oddeven">';
	print '<tr class="oddeven"><td  width="60%">';
	print '<span class="fieldrequired">'.$langs->trans("DeparSeuilRentabilite").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="CGL_DEPART_SEUIL_RENTABILITE" value="'.$conf->global->CGL_DEPART_SEUIL_RENTABILITE.'">';
	print '</td></tr>';

//CGL_TYPEENT_ID_PARTICULIER  - obsolette
/*print '<tr class="oddeven">';
	print '<tr class="oddeven"><td>';
	print '<span >'.$langs->trans("TypeTiers").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="CGL_TYPEENT_ID_PARTICULIER" value="'.$conf->global->CGL_TYPEENT_ID_PARTICULIER.'">';
	print '</td></tr>';
*/
//AGF_USE_STAGIAIRE_TYPE valeur  - obsolette
/*print '<tr class="oddeven">';
	print '<tr class="oddeven"><td>';
print '<span>'.$langs->trans("TypeParticipant").'</span></td><td>';
  if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_USE_STAGIAIRE_TYPE');
  } else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("AGF_USE_STAGIAIRE_TYPE", $arrval, $conf->global->AGF_USE_STAGIAIRE_TYPE);
  }
	print '</td></tr>';
*/


if ( $conf->cahiersuivi->enabled) {	
	//CGL_STRIPE_LIB_CARTE_PAIEMENT_LOC et CGL_STRIPE_LIB_CARTE_PAIEMENT_INSC
	print '<tr class="oddeven">';
		print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("LibCBStripeInsc").'</span></td><td>';
		print '<input class="minwidth300" type="text" name="CGL_STRIPE_LIB_CARTE_PAIEMENT_INSC" value="'.$conf->global->CGL_STRIPE_LIB_CARTE_PAIEMENT_INSC.'">';
		print '</td></tr>';
	print '<tr class="oddeven">';
		print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("LibCBStripeLoc").'</span></td><td>';
		print '<input class="minwidth300" type="text" name="CGL_STRIPE_LIB_CARTE_PAIEMENT_LOC" value="'.$conf->global->CGL_STRIPE_LIB_CARTE_PAIEMENT_LOC.'">';
		print '</td></tr>';
	//CGL_STRIPE_MAIL_COPY_CLIENT
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleCBMailClient").'</span></td><td>';
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("CGL_STRIPE_MAIL_COPY_CLIENT", $arrval, $conf->global->CGL_STRIPE_MAIL_COPY_CLIENT);
		print '</td></tr>';
		//CGL_STRIPE_MAIL_COPY_PAYEUR
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleMailInscResPayeur").'</span></td><td>';
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		// Pour cause de bug dans l'envoi de 2 confirmation, on met no  en automatique dans cette variable
		//print $form->selectarray("CGL_STRIPE_MAIL_COPY_PAYEUR", $arrval, $conf->global->CGL_STRIPE_MAIL_COPY_PAYEUR);
		print 'Non';
		print '</td></tr>';

	//CGL_STRIPE_MAIL_TEMPL_CONF
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleStripeMailTemplConf").'</span></td><td>';
		$wfcom= new FormCglCommun ($db);
		print '<span class="opacitymedium"></span> '.$wfcom->select_model( $conf->global->CGL_STRIPE_MAIL_TEMPL_CONF, 'cglStripe' , 'Mail','CGL_STRIPE_MAIL_TEMPL_CONF', 1, 0, 0, '', 0, 0, 0, '', 'minwidth100'	);
		unset ($wfc);

		print '</td></tr>';
		
	//CGL_STRIPE_MAIL_TEMPL_INSC
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleStripeMailModelInsc").'</span></td><td>';
		print '<span class="opacitymedium"></span> '.$wfcom->select_model( $conf->global->CGL_STRIPE_MAIL_TEMPL_INSC, 'cglbulletin' ,'Mail','CGL_STRIPE_MAIL_TEMPL_INSC',  1, 0, 0, '', 0, 0, 0, '', 'minwidth100'	);
		print '</td></tr>';
		
	//CGL_STRIPE_MAIL_TEMPL_RES
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleStripeMailModelRes").'</span></td><td>';
		print '<span class="opacitymedium"></span> '.$wfcom->select_model( $conf->global->CGL_STRIPE_MAIL_TEMPL_RES,'cgllocation' ,'Mail','CGL_STRIPE_MAIL_TEMPL_RES',   1, 0, 0, '', 0, 0, 0, '', 'minwidth100'	);
		print '</td></tr>';
		
	if ($conf->cahiersuivi->enabled) {
		require_once DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/html.suivi_client.class.php';
		//CGL_SUIVI_PRIORITE_AUTO
			print '<tr class="oddeven"><td>';
			print '<span>'.$langs->trans("LibelleSuiviPrioriteAuto").'</span></td><td>';
			$wAfCom= new FormCglSuivi ($db);
			print $wAfCom->select_priorite($conf->global->CGL_SUIVI_PRIORITE_AUTO,'CGL_SUIVI_PRIORITE_AUTO','',1,1,1,'',0, '', 0, 0, 'saisie','',1);
			print '</td></tr>';
			unset($wAfCom);
	}	

	//STRIPE_PAYMENT_MODE_FOR_PAYMENTS
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleStripeModePaiement").'</span></td><td>';
		print $form->select_types_paiements($conf->global->STRIPE_PAYMENT_MODE_FOR_PAYMENTS,"STRIPE_PAYMENT_MODE_FOR_PAYMENTS",'',0, 1, 1,0);	
		print '</td></tr>';


	//STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleIntentAutoConf").'</span></td><td>';
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION", $arrval, $conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION);
		print '</td></tr>';	

	//STRIPE_USER
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleUserStripe").'</span></td><td>';
		print $form->select_users($conf->global->STRIPE_USER, "STRIPE_USER", $conf->global->STRIPE_USER, $user->id,0, '', 0, 1, 1,0);
	//	    public function select_users($selected = '', $htmlname = 'userid', $show_empty = 0, $exclude = null, $disabled = 0, $include = '', $enableonly = '', $force_entity = '0')
		print '</td></tr>';

	//STRIPE COMPTE 
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleCompteStripe").'</span></td><td>';
		$form->select_comptes($conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS, "STRIPE_BANK_ACCOUNT_FOR_PAYMENTS");
		print '</td></tr>';

	//STRIPE COMPTE de destination pour virements de Stripe
		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleCompteVirementStripe").'</span></td><td>';
		$form->select_comptes($conf->global->STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS, "STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS");
		print '</td></tr>';
		

	//STRIPE Nb max de ligne retournées par STRIPE/TRANSACTION

		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleNbMaxLigneStripe").'</span></td><td>';
		if (empty($conf->global->STRIPE_MAX_TRANSAC_RECUP)) $conf->global->STRIPE_MAX_TRANSAC_RECUP = 100;
		print '<input class="minwidth300" type="text" name="STRIPE_MAX_TRANSAC_RECUP" value="'.$conf->global->STRIPE_MAX_TRANSAC_RECUP.'">';

		print '</td></tr>';
		
	// Stripe CodeComptale pour  frais Stripe


		print '<tr class="oddeven"><td>';
		print '<span>'.$langs->trans("LibelleCodeComptableFrais").'</span></td><td>';
	 //   $form->select_comptes($conf->global->STRIPE_ACCOUNT_POUR_FRAIS, "STRIPE_ACCOUNT_POUR_FRAIS");
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
		$formaccounting = new FormAccounting($db);
		print $formaccounting->select_account($conf->global->STRIPE_ACCOUNT_POUR_FRAIS, 'STRIPE_ACCOUNT_POUR_FRAIS', 1, '', 1, 1, 'minwidth150 maxwidth300');
		print '</td></tr>';

		// Dossier Suivi permettant d'accuser réception des paiements de facture du coeur
		if ( $conf->cahiersuivi->enabled) {	
		// Stripe Dossier Suivi permettant d'accuser réception des paiement de facure du coeur
			print '<tr class="oddeven"><td>';
			print '<span>'.$langs->trans("LibelleSuiviFactPayeStripe").'</span></td><td>';
		 //   $form->select_comptes($conf->global->CGL_SUIVI_FACT_PAYE_STRIPE, "CGL_SUIVI_FACT_PAYE_STRIPE");
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
			$formDossier = new FormCglSuivi($db);
			print $formDossier->select_dossier($conf->global->CGL_SUIVI_FACT_PAYE_STRIPE, 'CGL_SUIVI_FACT_PAYE_STRIPE', 1, '', 1, 1, 'minwidth150 maxwidth300');
			print '</td></tr>';
		}
			
}

//PROD_ACOMPTE_ACQUIS
	print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("LibelleProductAcompteAcquis").'</span></td><td>';
    print $form->select_produits_list($conf->global->PROD_ACOMPTE_ACQUIS, "PROD_ACOMPTE_ACQUIS");
	print '</td></tr>';
	
	
//Analyse financière - Nb année
	print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("LibelleNbAnnee").'</span></td><td>';
 	if (empty($conf->global->CGL_FIN_ANNEE)) $conf->global->CGL_FIN_ANNEE = 4;
	print '<input class="minwidth300" type="text" name="CGL_FIN_ANNEE" value="'.$conf->global->CGL_FIN_ANNEE.'">';
	print '</td></tr>';
	
// Variable définissant un éventuel changement de plan comptable - on ne peut plus comparer les volmes d'activités
	print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("LibelleAnneChgPlanComptable").'</span></td><td>';
	if (empty($conf->global->CGL_ANNEE_CHG_PLANCOMPATABLE)) $conf->global->CGL_ANNEE_CHG_PLANCOMPATABLE = 2022;
	print '<input class="minwidth300" type="text" name="CGL_ANNEE_CHG_PLANCOMPATABLE" value="'.$conf->global->CGL_ANNEE_CHG_PLANCOMPATABLE.'">';

	print '</td></tr>';


	
print '</table>';



print '<br>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("ParametreLocation").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

//CGL_LOC_RANDO_MAT  
//print '<td class="titlefield">';
print '<tr class="oddeven">';
	print '<tr class="oddeven"><td  width="60%">';
print '<span >'.$langs->trans("RandoMat").'</span></td><td>';
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("CGL_LOC_RANDO_MAT", $arrval, $conf->global->CGL_LOC_RANDO_MAT);



print '</table>';

print '<br>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("ParameterInutilise").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";


//CGL_NOM_LOCATION 
print '<tr class="oddeven">';
	print '<tr class="oddeven"><td>';
	print '<span >'.$langs->trans("UrlLoc").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="CGL_NOM_LOCATION" value="'.$conf->global->CGL_NOM_LOCATION.'">';
	print '</td><td></td></tr>';
	
	
//CGL_NOM_INSCRIPTION
print '<tr class="oddeven">';
	print '<tr class="oddeven"><td>';
	print '<span >'.$langs->trans("UrlInsc").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="CGL_NOM_INSCRIPTION" value="'.$conf->global->CGL_NOM_INSCRIPTION.'">';
	print '</td><td></td></tr>';
	
//CGL_NOM_FACTURATION
print '<tr class="oddeven">';
	print '<tr class="oddeven"><td>';
	print '<span >'.$langs->trans("UrlFact").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="CGL_NOM_FACTURATION" value="'.$conf->global->CGL_NOM_FACTURATION.'">';
	print '</td><td></td></tr>';
	
//CGL_VENTIL_ACOMPTE 
print '<tr class="oddeven">';
	print '<tr class="oddeven"><td>';
	print '<span >'.$langs->trans("VentilAcompte").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="CGL_VENTIL_ACOMPTE" value="'.$conf->global->CGL_VENTIL_ACOMPTE.'">';
	print '</td><td></td></tr>';


// CGL_STAG_INCONNU
/*
print '<tr class="oddeven">';
	print '<tr class="oddeven"><td>';
	print '<span >'.$langs->trans("ParticipantInconnu").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="CGL_STAG_INCONNU" value="'.$conf->global->CGL_STAG_INCONNU.'">';
	print '</td><td></td></tr>';
	*/
	
print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br><br>';


//print info_admin($langs->trans("ExampleOfTestCreditCard", '4242424242424242 (no 3DSecure) or 4000000000003063 (3DSecure required) or 4000000000003220 (3DSecure2 required)', '4000000000000101', '4000000000000069', '4000000000000341'));


// End of page
llxFooter();
$db->close();

function cglinscriptionadmin_prepare_head()
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/custom/cglinscription/admin/cglinscription.php";
	$head[$h][1] = $langs->trans("cglinscription");
	$head[$h][2] = 'cglinscription';
	$h++;

	$object=new stdClass();

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'cglinscriptionadmin');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'cglinscriptionadmin', 'remove');

    return $head;
}

