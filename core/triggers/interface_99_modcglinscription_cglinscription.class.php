<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * Version CAV - 2.7.1 automne 2022 -Positinner le dossier CGL_SUIVI_FACT_PAYE_STRIPE à Action Automatique si paiement Stripe facture du coeur est arrivé
 * Version CAV - 2.8 - hiver 2023 
 *			 - correction technique
 *			 - Fenêtre modale pour modif pour echange
 *			  - fiabilisation des foreach
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
 *  \file       htdocs/core/triggers/interface_99_modcglinscription_cglinscription.class.php
 *  \ingroup    core
 *  \brief      Fichier pour la gestion des triggers de CglInscription
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */


require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/bulletin.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglstripe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglInscDolibarr.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cgllocation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cglinscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/CahierSuivi/class/suivi_client.class.php';
/**
 *  Class of triggers for cglinscription module
 */
class Interfacecglinscription
{
    var $db;
    
    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "cglinscription";
        $this->description = "Triggers pour CglInscription";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }
    
    
    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }
    function addEvent($action, $object)
	{
		global $user;
	
        // Add entry in event table
			$now=dol_now('tzuser');

			if(isset($_SESSION['listofnames']))
			{
				$attachs=$_SESSION['listofnames'];
				if($attachs && strpos($action,'SENTBYMAIL'))
				{
					 $object->actionmsg.="\n".$langs->transnoentities("AttachedFiles").': '.$attachs;
				}
			}

            require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
            require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$contactforaction=new Contact($this->db);
            $societeforaction=new Societe($this->db);
            if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
            if ($object->socid > 0)    $societeforaction->fetch($object->socid);

			// Insertion action
			require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code   = $object->actiontypecode;		// code of parent table llx_c_actioncomm (will be deprecated)
			$actioncomm->code='AC_'.$action;
			$actioncomm->label       = $object->actionmsg2;
			$actioncomm->note        = $object->actionmsg;
			$actioncomm->datep       = $now;
			$actioncomm->datef       = $now;
			$actioncomm->durationp   = 0;
			$actioncomm->punctual    = 1;
			$actioncomm->percentage  = -1;   // Not applicable
			$actioncomm->contact     = $contactforaction;
			$actioncomm->societe     = $societeforaction;
			$actioncomm->author      = $user;   // User saving action
			$actioncomm->usertodo    = $user;	// User action is assigned to (owner of action)
			$actioncomm->userdone    = $user;	// User doing action (deprecated, not used anymore)

			$actioncomm->fk_element  = $object->id;
			$actioncomm->elementtype = $object->element;

			$ret=$actioncomm->create($user);       // User qui saisit l'action
			if ($ret > 0)
			{
				$_SESSION['LAST_ACTION_CREATED'] = $ret;
				return 1;
			}
			else
			{
                $error ="Failed to insert event : ".$actioncomm->error." ".join(',',$actioncomm->errors);
                $this->error=$error;
                $this->errors=$actioncomm->errors;

                dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
                return -1;
			}		
	} //addEvent
    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
//	function run_trigger($action,$object,$user,$langs,$conf)
	function runTrigger($action,$object,$user,$langs,$conf)
    {
		global $langs, $conf;
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
            $langs->load("cglinscription");
		// Customer orders
        if ($action == 'ORDER_CANCEL' and (
				substr($object->note_public , 0, strlen($langs->trans('Bulletin'))) == $langs->trans('Bulletin')
				or substr($object->note_public , 0, strlen($langs->trans('Contrat'))) == $langs->trans('Contrat') 
				))
        {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("orders");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderCancelInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("OrderCancelInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;			
			$this-> addEvent($action, $object);
		}
		else if ($action == 'PAYMENTONLINE_PAYMENT_OK') {

			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
 			require_once DOL_DOCUMENT_ROOT .'/user/class/user.class.php';
			// rédupération user Stripe dans $user
			$user = new User($this->db);
			global $user;
			$ret = $user->fetch($conf->global->STRIPE_USER);
			if ($ret <= 0) $ret = $user->fetch(1);
			if ( $conf->stripe->enabled){	
				// Information mail va être envoyé
				
				print $langs->trans("MailConfPaiement")."<br>\n";
				
				$amount = $_SESSION["FinalPaymentAmt"];
				$identPaiementStripe= $_SESSION["TRANSACTIONID"];
				$cardholder_name = $_SESSION['cardholder-name'];
				$tag = GETPOST('tag','alpha');
				if (empty($tag)) {
					// Url venant du coeur
					// recherche de l'id facture
					if ( $conf->cahiersuivi->enabled) {	
						$fctref = GETPOST('ref','alpha');
						$langs->load("cglinscription@cglinscription");
						$fct = new facture($this->db);
						$ret = $fct->fetch('',$fctref);
						if ($ret) {
							$id_acompte = $fct->id;
						}
						else {
							// GEstion des erreurs
							$error++;
						}
						// Signaler paiement dans dossier commun		
						if (empty($conf->global->CGL_SUIVI_FACT_PAYE_STRIPE)) 
							setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CGL_SUIVI_FACT_PAYE_STRIPE")),'',$texterror);
						else {
							$echange = new cgl_dossier($this->db);
							$suivi_action=$langs->trans("MesActionEchgPmtLigne" , 'MesActionEchgPmtLigneFact', $fctref); 
							$suivi_titre = $langs->trans("MesSuiviFectTi",$fctref);
							$suivi_description =  $langs->trans("MesSuiviFact",$amount, $cardholder_name);
							 $id_dossier = $conf->global->CGL_SUIVI_FACT_PAYE_STRIPE;
							$ret = $echange->Maj_echange( '' , $id_dossier,  $suivi_action, $suivi_titre, $suivi_description,$fct->socid , '' , $user->id, '');
							if ($ret < 0 ) {
								$error++;
								dol_syslog($langs->trans("ErrorSQL").' '.$langs->transnoentitiesnoconv("ErrCREEchange").'MessageConfirm',LOG_ERR);
							}
							else {
								
								$dossier = new cgl_dossier($this->db);
								$ret = $dossier->Maj_dossier( $id_dossier, '', '', '', '',  $conf->global->CGL_SUIVI_PRIORITE_AUTO, '', '', '', '') ;
								unset ($dossier);
							}
							unset ($echange);
						}
					}
				}
				else {
						// Url venant de cglinscription
					$pos = strpos ($tag, 'INV=');
					$id_acompte = substr($tag, $pos+4);
					//print '<br> CCA----------------GESTION DES ERREURS A FAIRE----';
					$fbulldet5 = new BulletinDemandeStripe ($this->db);
					$ret = $fbulldet5->fetchDemandeStripe ('', $id_acompte) ;

					// Recherche bulletin 
					global $bull, $form;
					$isbullpaye = false;	
					$islopaye = false; 		
					$bull = new Bulletin ($this->db);
					$bull->id = $fbulldet5->fk_bull;
					
					// création ligne de paiement dans bulletin
					$ret = $fbulldet5->CopieStripePaiement ($fbulldet5, $cardholder_name, $identPaiementStripe, $id_bank, $fk_paiement);
					if ($ret > 0)
					{
						$id_bulldet1 = $ret;
						// Recherche BulletinDemandeStripe
						$fbulldet5->fk_bulldet= $id_bulldet1;
						$fbulldet5->fk_bank= $id_bank;
						$fbulldet5->fk_paiement= $fk_paiement;
		
						$ret = $fbulldet5->UpdateDemandeStripe($user,  '',  false, true);
						// rechercher la remise exceptionnelle associé à l'acomte et la supprimer, elle sera refait à la facturation
						require_once DOL_DOCUMENT_ROOT .'/core/class/discount.class.php';
						$wrem = new DiscountAbsolute($this->db);
						$ret = $wrem->fetch('',$id_acompte);
						if ($ret > 0)  $ret = $wrem->delete($user);
						unset ($wrem);
						
						// Indiquer Acompte payé
						$objacompte = new Facture($this->db);
						$objacompte->id = $fbulldet5->fk_acompte;
						$objacompte->fetch_lines();
						$objacompte->set_paid($user);
						unset ($objacompte);
					}
					else {
						dol_syslog('<br>CCA--------------GEStion des erreur', 'errors');
					}
					//-A REPRENDRE car des HOOK de sendfile dans CMailFile devrait me permettre de simplifier, en attaquant directement formail dans le triggers</b>';
					// Chargement du bulletin
					$bull->fetch( $fbulldet5->fk_bull);
					// Test Bulletin totalement payé
					$w_total_pai = $bull->TotalPaimnt();
					$w_total_fac = $bull->TotalFac();
					$isbullpaye = false;
					$islopayeincomplet = false;
					if ( $w_total_pai >= $w_total_fac) $isbullpaye = true;
					if ( $bull->type == 'Loc' and  ($w_total_pai >= 0.3*$w_total_fac) and $isbullpaye == false)		  
						$islopayeincomplet = true;	
					
					// Valorisation des variables de substitution
					$bull->stripeNomPayeur = $fbulldet5->Nompayeur;
					$bull->stripeMtt = $fbulldet5->montant;
					$bull->stripeMailPayeur =  $fbulldet5->mailpayeur;
					$bull->stripeUrl = $fbulldet5->stripeUrl;
					if (!isset($form)) $form = new Form ($this->db);			
				
					// prépare et envoi le message Au client (dans action_send, le message est déjà subsituté en présentation à l'écran de get_form. Ici, il faut le faire
					$wstr = new CglStripe ($this->db);
					if ($conf->global->CGL_STRIPE_MAIL_COPY_CLIENT)  $MailCopie = $bull->TiersMail;
//					$wstr->EnvoiMessageAutomatique($bull, $fbulldet5->mailpayeur, $MailCopie, $fbulldet5->Nompayeur, $conf->global->CGL_STRIPE_MAIL_TEMPL_CONF, 'cglStripe');
					$wstr->EnvoiMessageAutomatique($bull->id_client,  $bull,  $fbulldet5->mailpayeur,$MailCopie, $fbulldet5->Nompayeur, $conf->global->CGL_STRIPE_MAIL_TEMPL_CONF, 'cglStripe');
					// Création de l'échange dans le dossier du bulletin

/*				if ( $conf->cahiersuivi->enabled) {	
					$nbEnc = $bull->NbStripeinBull('Encaisse');
					$nbAEnc = $bull->NbStripeinBull('NonEncaisse');
					$suivi_titre = $langs->trans('MesSuiviConfTi',$nbEnc,$nbAEnc);
					$suivi_description = $langs->trans('MesSuiviConfDesc',$fbulldet5->montant,$fbulldet5->Nompayeur );
					// Signalement d'un contrat/bulletin prêt à la Réservation
					if ($isbullpaye == true or $islopayeincomplet == true) {
							$suivi_titre .= ' -- ';
							$suivi_description .= '<br>';
							if ( $bull->type == 'Loc') {
								$suivi_titre .= $langs->trans('MesSuiviResTi',$bull->ref );
								if ($islopayeincomplet == true) $suivi_titre .= $langs->trans('MesSuiviResTiComp');
								if ($isbullpaye) $suivi_description .= $langs->trans('MesSuiviResDesc',$bull->ref );
								else $suivi_description .= $langs->trans('MesSuiviResDescIncComp',$bull->ref, $w_total_pai, $w_total_fac);
								if ($nbAEnc > 0) {
									if ($nbAEnc == 1) $suivi_description .= $langs->trans('MesSuiviResDescComp1');
									else $suivi_description .= $langs->trans('MesSuiviResDescCompN',$nbAEnc);									
								}
						}
							elseif ( $bull->type == 'Insc') {
								$suivi_titre .= $langs->trans('MesSuiviInscTi',$bull->ref );
								$suivi_description .= $langs->trans('MesSuiviInscDesc',$bull->ref );
							}
					}
*/
					if ( $conf->cahiersuivi->enabled) {	
						$nbEnc = $bull->NbStripeinBull('Encaisse');
						$nbAEnc = $bull->NbStripeinBull('NonEncaisse');
						if ($isbullpaye == true and $islopayeincomplet == false) {
								$suivi_titre = $langs->trans('MesSuiviResTi',$bull->ref);
								if ($bull->type == 'Insc') 
									$suivi_description = $langs->trans('MesSuiviConfDesc1', $fbulldet5->montant,$fbulldet5->Nompayeur, 'Bulletin' );
								elseif ($bull->type == 'Loc') 
									$suivi_description = $langs->trans('MesSuiviConfDesc1', $fbulldet5->montant,$fbulldet5->Nompayeur , 'Contrat');
						}
						elseif ($isbullpaye == true and $islopayeincomplet == true) {
								$suivi_titre = $langs->trans('MesSuiviResLocTi',$bull->ref);
								$suivi_description = $langs->trans('MesSuiviConfDesc1', $fbulldet5->montant,$fbulldet5->Nompayeur , 'Contrat');
						}
						elseif ($isbullpaye == false) {
								$suivi_titre = $langs->trans('MesSuiviConfTi',$bull->ref,$nbEnc, $nbAEnc  );
								$suivi_description = $langs->trans('MesSuiviConfDesc',$bull->ref) ;							
						}
						if ($nbAEnc > 0) {
							if ($nbAEnc == 1) $suivi_description .= $langs->trans('MesSuiviResDescComp1');
							else $suivi_description .= $langs->trans('MesSuiviResDescCompN',$nbAEnc);									
						}
						

				
						$echange = new cgl_dossier($this->db);
						$suivi_action=$langs->trans("MesActionEchgPmtLigne" , ($byll->type == 'Insc')?'bulletin':'contrat', $bull->ref); 
						$ret = $echange->Maj_echange( $Idechange , $bull->fk_dossier,  $suivi_action, $suivi_titre, $suivi_description,'' , '' , $user->id, $user->id);
						if ($ret < 0 ) {
							$error++;
							dol_syslog($langs->trans("ErrorSQL").' '.$langs->transnoentitiesnoconv("ErrEchg").'MessageConfirm',LOG_ERR);
						}
						unset ($echange);

						$dossier = new cgl_dossier($this->db);

						$ret = $dossier->Maj_dossier( $bull->fk_dossier, '', '', '', '',  $conf->global->CGL_SUIVI_PRIORITE_AUTO, '', '', '', '') ;
						// Change statut du bulletin - Pré-réservé si pas déla 
						 if ($bull->type == 'Insc' and $bull->statut <= $bull->BULL_PRE_INS) {
							$cglInscDolibar = new cglInscDolibarr($this->db);
							 $ret = $cglInscDolibar->TransfertDataDolibarr('Pre_inscrit', '');
							$bull->updateStat ($bull->BULL_PRE_INS,'M');
						}
						elseif ($bull->type == 'Loc'  and $bull->statut <= $bull->BULL_PRE_INSCRIT) {
							$TraitLocation = new CglLocation($this->db);
							$TraitLocation->PreReserver();
							$bull->updateStat ($bull->BULL_PRE_INSCRIT,'M');	
						}					

						if ($ret <0 )
						dol_syslog('<br>CCA--------------GEStion des erreur', 'errors');
					}			
					// Bulletin/contrat totatelement payé
					if ($isbullpaye == true or $islopayeincomplet == true)  {	
						// prépare et envoi le message à client indiquant la confirmation
						if ($bull->type == 'Loc') {
							$id_model = $conf->global->CGL_STRIPE_MAIL_TEMPL_RES;
							$type_model = 'cgllocation';
						}
						elseif ($bull->type == 'Insc')  {
							$id_model = $conf->global->CGL_STRIPE_MAIL_TEMPL_INSC; 
							$type_model = 'cglbulletin'; 
						}
						if ($conf->global->CGL_STRIPE_MAIL_COPY_PAYEUR) $MailCopie = $bull->ListPayeurStripe('paye');
						else $MailCopie = '';
//						$ret = $wstr->EnvoiMessageAutomatique($bull, $bull->TiersMail, $MailCopie,  $fbull->Nompayeur,  $id_model, $type_model);
						$ret = $wstr->EnvoiMessageAutomatique($bull->id_client,  $bull, $bull->TiersMail, $MailCopie,  $fbull->Nompayeur,  $id_model, $type_model);
						unset ($wstr);
						// Change statut du bulletin - réservé
						if ($bull->type == 'Insc') {
							$bull->updateStat ($bull->BULL_INS,'M');
							$cglInscDolibar = new cglInscDolibarr($this->db);
							 $ret = $cglInscDolibar->TransfertDataDolibarr('Inscrit', 'Inscrire');
							$bull->statut = $bull->BULL_INS;
							$bull->updateStat ($bull->BULL_INS,'');
						}
						elseif ($bull->type == 'Loc') {
							$bull->updateStat ($bull->BULL_VAL,'M');	
							$TraitLocation = new CglLocation($this->db);
							$TraitLocation->Reserver();
						}					
						
					}
					
				}
			}
		}
		
		else if ($action == 'PAYMENTONLINE_PAYMENT_KO') {
			require_once DOL_DOCUMENT_ROOT .'/user/class/user.class.php';
			// rédupération user Stripe dans $user
			$user = new User($this->db);
			$user->fetch($conf->global->STRIPE_USER);
			if ( $conf->stripe->enabled){
				// Création de l'échange dans le dossier du bulletin
				if ( $conf->cahiersuivi->enabled) {	
					$amount = $_SESSION["FinalPaymentAmt"];
					$identPaiementStripe= $_SESSION["TRANSACTIONID"];
					$cardholder_name = $_SESSION['cardholder-name'];
					$tag = GETPOST('tag','alpha');
					$pos = strpos ($tag, 'INV=');
					$id_acompte = substr($tag, $pos+4);
					//print '<br>CCA----------------GESTION DES ERREURS A FAIRE----';
					$fbulldet5 = new BulletinDemandeStripe ($this->db);
					$ret = $fbulldet5->fetchDemandeStripe ('', $id_acompte) ;

					// Recherche bulletin 
					global $bull, $form;
					$isbullpaye = false;	
					$islopaye = false; 		
					$bull = new Bulletin ($this->db);
					$bull->id = $fbulldet5->fk_bull;
					$bull->fetch($fbulldet5->fk_bull);				
					$suivi_titre = $langs->trans('MesSuiviNOKTI');
					$suivi_description = $langs->trans('MesSuiviNOKDesc',$fbulldet5->Nompayeur,$fbulldet5->montant );
					$suivi_description .= $langs->trans('MesSuiviNOKDescComp',$cardholder_name );
			
					$echange = new cgl_dossier($this->db);
					$ret = $echange->Maj_echange( $Idechange , $bull->fk_dossier,  $suivi_action, $suivi_titre, $suivi_description,'' , '' , $user->id, $user->id);
					if ($ret < 0 ) {
						$error++;
						dol_syslog($langs->trans("ErrorSQL").' '.$langs->transnoentitiesnoconv("ErrEchg").'MessageConfirm',LOG_ERR);
					}
					unset ($echange);

					$dossier = new cgl_dossier($this->db);
					
					$ret = $dossier->Maj_dossier( $bull->fk_dossier, '', '', '', '',  $conf->global->CGL_SUIVI_PRIORITE_AUTO, '', '', '', '') ;
					if ($ret <0 )					
						dol_syslog('<br>CCA--------------GEStion des erreur', 'errors');
				}			

			}
		}
		else 
			{
			// Customer orders
			if ($action == 'ORDER_CREATE')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'ORDER_CLONE')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'ORDER_VALIDATE')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'ORDER_DELETE')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'ORDER_BUILDDOC')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'ORDER_SENTBYMAIL')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'ORDER_CLASSIFY_BILLED')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			// Bills
			elseif ($action == 'BILL_CANCEL')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			// Categories
			elseif ($action == 'CATEGORY_CREATE')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'CATEGORY_MODIFY')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'CATEGORY_DELETE')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			
			// Task time spent
			elseif ($action == 'TASK_TIMESPENT_CREATE')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'TASK_TIMESPENT_MODIFY')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			elseif ($action == 'TASK_TIMESPENT_DELETE')
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			}
			
		}
		return 0;
    } //runTrigger

}
?>