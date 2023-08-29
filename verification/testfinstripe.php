<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
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
 *     	\file       htdocs/public/payment/paymentok.php
 *		\ingroup    core
 *		\brief      File to show page after a successful payment
 *                  This page is called by payment system with url provided to it completed with parameter TOKEN=xxx
 *                  This token can be used to get more informations.
 */

define("NOLOGIN", 1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK", 1);	// We accept to go on this page from external web site.

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retreive from object ref and not from url.
$entity=(! empty($_GET['e']) ? (int) $_GET['e'] : (! empty($_POST['e']) ? (int) $_POST['e'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/bulletin.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglstripe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglInscDolibarr.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cgllocation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglinscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/suivi_client.class.php';

var_dump($conf->cahiersuivi);
if ( $conf->cahiersuivi->enabled) print 'OK';
	else  print 'NOK';
print '<br>CCA===================================PaymentOK - Init';
$langs->loadLangs(array("main","other","dict","bills","companies","paybox","paypal"));

			print '<br>CCA ---------------------TRIGGERs : PAYMENTONLINE_PAYMENT_OK';
			print '<br>CCA--------------------- DONNEES - Parametre entrée';
		/*	print '<br>CCA ================= Données - object - ';
			var_dump($object);
			print '<br><br>CCA ================= Données - SESSION - ';
			var_dump($_SESSION);
			print '<br>CCA ================= Données - GET <br>';
			var_dump($_GET);
			print '<br>CCA ================= Données - POST <br>';
			var_dump($_POST);
		*/
				

		if ( $conf->stripe->enabled){		
			$amount = 11;
			$identPaiementStripe= $_SESSION["TRANSACTIONID"];
			$cardholder_name = 'Pascal';
			print '<br>CCA ================= Données - cardholder-name:'.$cardholder_name;
			print '<br>CCA ================= Données - amount:'.$amount;
			print '<br>CCA ================= Données - identPaiementStripe:'.$identPaiementStripe;
			$tag = 'Sortie Vélo.INV=6041';
			$pos = strpos ($tag, 'INV=');
			$id_acompte = substr($tag, $pos+4);
			print '<br>CCA ================= Données - Acompte:'.$id_acompte;
			$id_acompte = 6041;
			
			
			print '<br>CCA---------------------------------------------';
//			print '<br>CCA----------------Création de  de bull_det.type=1 à partir de type 5 <br>';
		
			$fbulldet5 = new BulletinDemandeStripe ($db);
			$ret = $fbulldet5->fetchDemandesStripe ( '', $id_acompte) ;

			global $bull, $form;
			$isbullpaye = false;	
			$islopaye = false; 		
			$bull = new Bulletin ($db);
			
//			$ret = $fbulldet5->CopieStripePaiement ($fbulldet5, $cardholder_name, $identPaiementStripe);
//print '<br>CCA---------------retour CopieStripePaiement---:'; $ret;

				
				// création ligne de paiement dans bulletin
				$ret = $fbulldet5->CopieStripePaiement ($fbulldet5, $cardholder_name, $identPaiementStripe, $id_bank, $fk_paiement);
				print '<br>CCA---------------retour CopieStripePaiement---:'; $ret;
print '<br>CCA -------------------------------------Retour CopieStripePaiement  - id_bank:.'.$id_bank;
print '<br>CCA -------------------------------------Retour CopieStripePaiement  - fk_paiement:.'.$fk_paiement;
				if ($ret > 0)
				{
					$id_bulldet1 = $ret;
					// Recherche BulletinDemandeStripe
					$fbulldet5->fk_bulldet= $id_bulldet1;
					$fbulldet5->fk_bank= $id_bank;
					$fbulldet5->fk_paiement= $fk_paiement;
					
					$ret = $fbulldet5->UpdateDemandeStripe($user,  '',  false, true);
				}
				else {
					print '<br>CCA--------------GEStion des erreur';
				}
		if ( $bull->TotalPaimnt() == $bull->TotalFac()) $isbullpaye = true;
		if ( $bull->type == 'Loc' and  $bull->TotalPaimnt() > 0.3*$bull->TotalFac() and $isbullpaye == false)		  
			$islopayeincomplet = true;	
		
		
			$bull->stripeNomPayeur = $fbulldet5->Nompayeur;
			$bull->stripeMtt = $fbulldet5->montant;
			$bull->stripeMailPayeur =  $fbulldet5->mailpayeur;
			$bull->stripeUrl = $fbulldet5->stripeUrl;
			
			
		if (!isset($form)) $form = new Form ($db);			


			print '<br>CCA---------------<b>-ENVOI MESSAGE de CONFIRMATION de PAIEMENT au PAYEUR </b>';
			print '<br>CCA---------------<-A REPRENDRE car des HOOK de sendfile dans CMailFile devrait me permettre de simplifier, en attaquant directement formail</b>';
			print '<br>CCA----------------pourrait être fait pour le mail de stripe, mais cela on verra car c_est écrit';

			$wstr = new CglStripe ($db);
	
	// prépare et envoi le message (dans action_send, le message est déjà subsituté en présentation à l'écran de get_form. Ici, il faut le faire
			if ($conf->global->CGL_STRIPE_MAIL_COPY_CLIENT)  $MailCopie = $bull->TiersMail;
	//		$wstr->EnvoiMessageAutomatique($bull, $fbulldet5->mailpayeur, $MailCopie, $fbulldet5->Nompayeur, $conf->global->CGL_STRIPE_MAIL_TEMPL_CONF, 'cglStripe');

				print '<br>CCA----------------création message dans Suivi sous condition d_activation du module suivi ';
/*
			if ( $conf->cahiersuivi->enabled) {			
					$nbEnc = $bull->NbStripeinBull('Encaisse');
					$nbAEnc = $bull->NbStripeinBull('NonEncaisse');
					$suivi_titre = $langs->trans('MesSuiviConfTi',$nbEnc,$nbAEnc);
					$suivi_description = $langs->trans('MesSuiviConfDesc',$fbulldet5->montant,$fbulldet5->Nompayeur );
					if ($bull->statut == $bull->BULL_FACTURE ) 
						$suivi_description .=  $langs->trans('MesSuiviConfDescComp');
			
					$echange = new cgl_dossier($db);
					$ret = $echange->Maj_echange( $Idechange , $bull->fk_dossier,  $suivi_action, $suivi_titre, $suivi_description,'' , '' , $user->id, $user->id);
					if ($ret < 0 ) {
						$error++;
						dol_syslog($langs->trans("ErrorSQL").' '.$langs->transnoentitiesnoconv("ErrEchg").'MessageConfirm',LOG_ERR);
					}
					unset ($echange);

					$dossier = new cgl_dossier($db);
					$ret = $dossier->Maj_dossier( $bull->fk_dossier, '', '', '', '',  $conf->global->CGL_SUIVI_PRIORITE_AUTO, '', '', '', '') ;
					if ($ret <0 )
							print '<br>CCA--------------GEStion des erreur';
				}			


				print '<br>CCA ================= reste - si BU/LO entièrement payé - envoi du message tiers avec info standard';
*/
				if ($isbullpaye == true or $islopayeincomplet == true)  {	
/*					if ( $conf->cahiersuivi->enabled ) {					
							print '<br>CCA----------------création message Paiement total dans Suivi sous condition d_activation du module suivi ';
							if ( $bull->type == 'Loc') {
								$txtitre = $langs->trans('MesSuiviResTi',$bull->ref );
								if ($islopayeincomplet == true) $txtitre .= $langs->trans('MesSuiviResTiComp');
								$txdesc = $langs->trans('MesSuiviResDesc',$bull->ref );
								if ($$nbAEnc > 0) {
									if ($nbAEnc == 1) $txdesc .= $langs->trans('MesSuiviResDescComp1');
									else $txdesc .= $langs->trans('MesSuiviResDescCompN',$nbAEnc);
								}
							}
							elseif ( $bull->type == 'Insc') {
								$txtitre = $langs->trans('MesSuiviInscTi',$bull->ref );
								$txdesc = $langs->trans('MesSuiviInscDesc',$bull->ref );
							}
							$echange = new cgl_dossier($db);
							$ret = $echange->Maj_echange( $Idechange , $bull->fk_dossier,  $suivi_action, $txtitre, $txdesc,'' , '' , $user->id, $user->id);
							if ($ret < 0 ) {
								$error++;
								dol_syslog($langs->trans("ErrorSQL").' '.$langs->transnoentitiesnoconv("ErrEchg").'MessageReservation-Inscription',LOG_ERR);
							}
							unset ($echange);
							$dossier = new cgl_dossier($db);
							//$ret = $dossier->Maj_dossier( $bull->fk_dossier, '', '', '', '',  $conf->global->CGL_SUIVI_PRIORITE_AUTO, '', '', '', '') ;
				}					
*/
				print '<br>CCA----------------Envoi Message au tiers et éventuellement à tous les payeurs ';
					// prépare et envoi le message (dans action_send, le message est déjà subsituté en présentation à l'écran de get_form. Ici, il faut le faire
					if ($bull->type == 'Loc') {
						$id_model = $conf->global->CGL_STRIPE_MAIL_TEMPL_RES;
						$type_model = 'cgllocation';
					}
					elseif ($bull->type == 'Insc')  {
						$id_model = $conf->global->CGL_STRIPE_MAIL_TEMPL_INSC; 
						$type_model = 'cglbulletin'; 
					}
					if ($conf->global->CGL_STRIPE_MAIL_COPY_PAYEUR) $MailCopie = $bull->ListPayeurStripe('paye');
					$MailCopie='cl.castellano@free.fr;lacondamine.arrigas@free.fr;claude@cigaleaventure.com;claudecastellano@laposte.net';
					$wstr->EnvoiMessageAutomatique($bull, $fbulldet5->mailpayeur, $MailCopie,  $fbull->Nompayeur, $id_model, $type_model , $conf->global->CGL_STRIPE_MAIL_COPY_CLIENT, $conf->global->CGL_STRIPE_MAIL_COPY_PAYEUR);
					unset ($wstr);
					// Change statut du bulletin
// A TRANSFERER DANS TRIGGERS
					if ($bull->type == 'Insc') {
print '<br>CCA ------ Bulletin Inscrit';
						$bull->updateStat ($bul->BULL_INS,'M');
						$cglInscDolibar = new cglInscDolibarr($db);
						 $ret = $cglInscDolibarr->TransfertDataDolibarr('Inscrit', 'Inscrire');
					}
					elseif ($bull->type == 'Loc') {
print '<br>CCA ------ Contrat réservé';
						$bull->updateStat ($bul->BULL_VAL,'M');	
						$TraitLocation = new CglLocation($db);
						$TraitLocation->Reserver();
					}						
				}				
		}

		
