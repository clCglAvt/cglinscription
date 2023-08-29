<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
  *
  * Version CAV - 2.8 - hiver 2023 - 
  *		- Edition en un tableau des poids/taille/age/prenom par le client
  * Version CAV - 2.8.4 - printemps 2023 - 
  *		- Ajouter le champ Compl�ment de r�f�rence mat�riel 
  * 	et v�rification de la saisie de 3 caract�es pour la r�f�rence mat�riel  
  *		dans Taille-Poids et Age (308)
  * Version CAV - 2.8.5 - printemps 2023
  *		- v�rification � la vol�e des conflit de v�lo  (308b)
  
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
 *
 */

/**
 *     	\file       htdocs/custom/cglinscription/SaisieParticipations.php
 *	SaisiParticipations.php?entity=1&action=""&id=""
 *		\ingroup    cglinscription
 *		\brief      File to offer a way to saisir les poids et taille des diff�rentes participations d'un BU
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and get of entity must be done before including main.inc.php
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1))));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/bulletin.class.php';
require DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglinscription.class.php';
require DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/html.formcommun.class.php';

global $db;

// Hook to be used by external payment modules (ie Payzen, ...)
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('newpayment'));

// Load translation files
$langs->loadLangs(array("main", "cglinscription")); // File with generic data

// Security check
// No check on module enabled. Done later according to $validpaymentmethod

// Input are:
// id de BU/LO
// action - pour enregistrer

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'aZ09');
$btEnrPoidsTaille=GETPOST ("btEnrPoidsTaille");
$Interlocuteur=GETPOST ("Interlocuteur");

$$tbidline = array();
$tbidline = GETPOST("idline", 'array');
$$tbPoids = array();
$tbPoids = GETPOST("Poids", 'array');
$$tbTaille = array();
$tbTaille = GETPOST("Taille", 'array');
$$tbNomPrenom = array();
$tbNomPrenom = GETPOST("NomPrenom", 'array');
$$tbAge = array();
$tbAge = GETPOST("Age", 'array');
$tbMarque = array();
$tbMarque = GETPOST("Marque", 'array');

$tbNumVelo = array();
$tbNumVelo = GETPOST("NumVelo", 'array');
$tbPrix = array();
$tbPrix = GETPOST("Prix", 'array');
$tbObservation = array();
$tbObservation = GETPOST("Observation", 'array');
$tbDuree = array();
$tbDuree = GETPOST("Duree", 'array');


$bull = new Bulletin($db);

$ret = $bull->fetch_complet_filtre(-1, $id );
$wfctc = new FormCglCommun($db);
$now = $db->idate(dol_now('tzuser'));

if (empty($id) or $ret <0 ) {
		print $langs->trans('ErrorBadParameters')." - id du bulletin/Contrat";
		exit;
	} 
	
global $event_filtre_car_saisie;
$event_filtre_car_saisie = '';
$event_filtre_car_saisie .= " onchange='remplaceToutGuillements(this, event);return false;'";
// On privil�gie le travail lors de la fin de la saisie, pour r�cup�r� les copier/coller, plutot que le changement im�diat sur l'�cran, pour lisibilit�
//$event_filtre_car_saisie .= " onkeypress='remplaceGuillements(this, event);return false;'";


/* HOOK
// Initialize $validpaymentmethod
$validpaymentmethod = getValidOnlinePaymentMethods($paymentmethod);

// This hook is used to push to $validpaymentmethod by external payment modules (ie Payzen, ...)
$parameters = [
	'paymentmethod' => $paymentmethod,
	'validpaymentmethod' => &$validpaymentmethod
];
$reshook = $hookmanager->executeHooks('doValidatePayment', $parameters, $object, $action);
*/
// Check security token
/*
$tmpsource = $source;

$valid = true;
if (!empty($conf->global->PAYMENT_SECURITY_TOKEN)) {
	$tokenisok = false;
	if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) {
		if ($tmpsource && $REF) {
			// Use the source in the hash to avoid duplicates if the references are identical
			$tokenisok = dol_verifyHash($conf->global->PAYMENT_SECURITY_TOKEN.$tmpsource.$REF, $SECUREKEY, '2');
			// Do a second test for retro-compatibility (token may have been hashed with membersubscription in external module)
			if ($tmpsource != $source) {
				$tokenisok = dol_verifyHash($conf->global->PAYMENT_SECURITY_TOKEN.$source.$REF, $SECUREKEY, '2');
			}
		} else {
			$tokenisok = dol_verifyHash($conf->global->PAYMENT_SECURITY_TOKEN, $SECUREKEY, '2');
		}
	} else {
		$tokenisok = ($conf->global->PAYMENT_SECURITY_TOKEN == $SECUREKEY);
	}


	if (!$valid) {
		print '<div class="error">Bad value for key.</div>';
		//print 'SECUREKEY='.$SECUREKEY.' valid='.$valid;
		exit;
	}
}
*/

/*
 * Actions
 */
 // ENREGISTREMENT
	if (!empty($btEnrPoidsTaille)) {
		$bullline= new BulletinLigne ($db);
		$nbLigneRenseignee = 0;
		$nbligne=0;
		if (!empty($tbidline)) {
			foreach ($tbidline as $idline) {
				$bullline->id =$idline;
				if (!empty(GETPOST('Dolibarr')) and !empty($identmat) and strlen($tbNumVelo[$idline]) <> 3){
					setEventMessage($langs->trans("ErrorTailleRequired" ,"RefVelo",3),"errors");
				}
				else {
					$age=$tbAge[$idline];
					$refmat = $tbNumVelo[$idline];
					$marque = $tbMarque[$idline];
					$pu = $tbPrix[$idline];
					$observation = $tbObservation[$idline];
					$duree = $tbDuree[$idline];
					if (empty($age)) $age = 0;
					$db->begin();
					$bullline->update_champs('NomPrenom',$tbNomPrenom[$idline],
											'age',$age,
											'taille',$tbTaille[$idline],
											'poids',$tbPoids[$idline]);
					$bullline->update_champs('fk_user',$user->id,											
											'tms',$now,
											'refmat',$refmat,
											'marque', $marque);
					if (empty($pu)) $pu = 0;
					if (empty($duree)) $duree = 0;
					$bullline->update_champs('pu',$pu,											
											'duree',$duree,
											'observation',$observation);
					$db->commit();
					if ($bull->type == 'Insc' and $tbAge[$idline] and $tbTaille[$idline] and 	$tbPoids[$idline] )		
							$nbLigneRenseignee++;
					elseif ($bull->type == 'Loc'  and $tbTaille[$idline] )		
							$nbLigneRenseignee++;
					$nbligne ++;
				}
			} // foreach
		}
/* POur une saisie par client
		if (empty(GETPOST('Dolibarr')) and $conf->cahiersuivi)
		{
			require DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/suivi_client.class.php';
			$db->begin();
			$echg = new cgl_echange($db);
			$nbMq=$nbligne - $nbLigneRenseignee;
			if ($nbMq > 1) $Pluriel="s";
			else $Pluriel = "";
			if ($nbLigneRenseignee == $nbligne) $Titexte  = 'EchgTiPoidsTailleOK';
			else $Titexte =$langs->trans('EchgTiPoidsTailleMq', $nbMq, $Pluriel);
//			$echg->titre=$langs->trans('EchgTiPoidsTaillePublic', $Titexte);
			$echg->titre=$langs->trans('EchgTiPoidsTaillePublic').' '.$langs->trans( $Titexte);
			$echg->desc =$langs->trans('EchgLbPoidsTaillePublic', $Interlocuteur);
			$echg->fk_dossier=$bull->fk_dossier;
			$echg->fk_user_create=$user->id;
			$ret = $echg->create($user, false);
			unset($echg);
			if ($ret > 0) {
				$dos = new cgl_dossier($db);
				$dos->id = $bull->fk_dossier;
				$dos->fk_priorite = $conf->global->CGL_SUIVI_PRIORITE_AUTO;
				$arg_tiers = $bull->id_client;
				global $arg_tiers;			
				$ret = $dos->update($user, false);
				unset($dos);
			}
			if ($ret <0) {
				$db->rollback();
				return $ret;
			}
			else $db->commit();
			return ;
		}
*/
		if (!empty(GETPOST('Dolibarr'))) {
			
			if ($bull->type == 'Insc') 
					header('Location: ../../custom/cglinscription/inscription.php?id_bull='.$bull->id.'&idmenu=160&idmenu=16837&mainmenu=CglInscription&leftmenu=CglInscription');
			elseif ($bull->type == 'Loc') {
				setEventMessage($langs->trans("AlertConflitNonVerifier"), 'mesgs');
				header('Location: ../../custom/cglinscription/location.php?id_contrat='.$bull->id.'&idmenu=160&idmenu=16837&mainmenu=CglLocation&leftmenu=CglLocation');
			}
			exit();
		}
	}
		
/*
 * View
 */
$form = new Form($db);

$head = '';
if (!empty($conf->global->ONLINE_PAYMENT_CSS_URL)) {
	$head = '<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";
}

//$conf->dol_hide_topmenu = 1;
//$conf->dol_hide_leftmenu = 1;

//$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
//llxHeader($head, $langs->trans("TailleForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea);

llxHeader('',$langs->trans('TailleForm'));

print $wfctc->html_PoidsTaille($bull, GETPOST('Dolibarr', 'alpha'), GETPOST('limitdeb', 'int'), GETPOST('limitlig', 'int'));


llxFooter('', 'public');
print '</div>'."\n";

$db->close();
