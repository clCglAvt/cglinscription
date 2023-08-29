<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.6.1.4  du 1 aout 2022
 * Version CAV - 2.8.3 printemps 2023 - première étape POST_ACTIVITE
 * Version CAV - 2.8.4 printemps 2023 -
 *		- Verrue pour enlever https aléatoiremennt en dojlbe (315)
 *
 * ATTENTION, la gestion des action d'un formateur-moniteur n'est pas valide
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
 *   	\file       custum/cglinscription/class/cglstripe.class.php
 *		\ingroup    cglinscription
 *		\brief      Objet permettant le traitement spécifique Stripr
 */

 /**************************/
 /*
require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

require_once  DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once  DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once  DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once  "./core/modules/cglinscription/modules_cglinscription.php";
require_once '../agefodd/class/agsession.class.php';
require_once '../agefodd/class/agefodd_session_formateur.class.php';
require_once '../agefodd/class/agefodd_session_calendrier.class.php';
require_once '../agefodd/class/agefodd_session_formateur_calendrier.class.php';
require_once './class/cglFonctionAgefodd.class.php';
*/
	require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/bulletin.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	require_once DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctDolibarrRevues.class.php';
	
/**
 *	Put here description of your class
 */
class CglStripe
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='CglStripe';			//!< Id that identify managed objects
	var $table_element='cglinscription_bull_det';		//!< Name of table without prefix where object is stored

    var $id;
    var $prop1;
    var $prop2;
	var $FormInscription;
	
	// Automate Stripe
	const STRIPE_MAIL_GENERAL = 'M1';	// Mail classique envoyé au tiers
	const STRIPE_SMS_GENERAL = 'S1';	// SMS classique envoyé au tiers
	const STRIPE_MAIL_STRIPE = 'MS1';	// Mail pour paiement par un payeur possiblement différent du tiers
	const STRIPE_SMS_STRIPE = 'SS1';	// SMS pour paiement par un payeur possiblement différent du tiersv
	const STRIPE_REL_MAIL_STRIPE = 'RM1';	// Relance Mail pour paiement par un payeur possiblement différent du tiers
	const STRIPE_REL_SMS_STRIPE = 'RS1';	// Relance SMS pour paiement par un payeur possiblement différent du tiers
	const STRIPE_MAIL_APPLY = 'M2';	// Recherche Modèle Mail dans l'écran de préparation du Mail classique envoyé au tiers
	const STRIPE_SMS_APPLY = 'S2';	// Recherche Modèle SMS dans l'écran de préparation du SMS classique envoyé au tiers
	const STRIPE_MAIL_APPLY_STRIPE = 'MS2';	// Recherche Modèle Mail dans l'écran de préparation du Mail pour paiement
	const STRIPE_SMS_APPLY_STRIPE = 'SS2';	// Recherche Modèle SMS dans l'écran de préparation du SMS pour paiement
	const STRIPE_REL_MAIL_APPLY_STRIPE = 'RM2';	// Recherche Modèle Mail dans l'écran de préparation du Mail de relance
	const STRIPE_REL_SMS_APPLY_STRIPE = 'RS2';	// Recherche Modèle SMS dans l'écran de préparation du SMS de relance
	const STRIPE_MAIL_ENVOI = 'M3';	// Envoi Mail classique envoyé au tiers
	const STRIPE_SMS_ENVOI= 'S3';	// Envoi SMS classique envoyé au tiers
	const STRIPE_MAIL_ENVOI_STRIPE = 'MS3';	// Envoi Mail pour paiement
	const STRIPE_SMS_ENVOI_STRIPE = 'SS3';	// Envoi  SMS pour paiement
	const STRIPE_REL_MAIL_ENVOI_STRIPE = 'RM3';	// Envoi du Mail de relance
	const STRIPE_REL_SMS_ENVOI_STRIPE = 'RS3';	// Envoi du SMS de relance
	
	//...

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }
	/* 
	* Opérations à réaliser lors d'un envoi de mail STRIPE_MAIL_APPLY
	*
	* retour	Flag pour indiquer la néessité d'une préinscription/pré_reservation du BO/LO
	*/	
	function GestionEnvoiDemandeStripe()			
	{
		global $id_stripe, $bull, $user, $langs;
		global $StripeNomPayeur, $libelleCarteStripe, $StripeMtt, $StripeMailPayeur, $StripeSmsPayeur, $modelmailchoisi;
			
		//$id_stripe = $_SESSION['id_stripe'];
		// Assignation pour formail
		$bull->stripeNomPayeur = $StripeNomPayeur;
		$bull->stripeMtt = $StripeMtt;
		$bull->stripeMailPayeur = $StripeMailPayeur;
		$bull->StripeSmsPayeur = $StripeSmsPayeur;
		$bull->stripeModelMail = $modelmailchoisi;
		$bull->libelleCarteStripe = $libelleCarteStripe;
		$wbuldet5	 = new BulletinDemandeStripe ($this->db);
		// Recherche demande Stripe
		if (!empty($id_stripe)) {		
			$ret = $wbuldet5->fetchDemandeStripe ( $id_stripe) ;
			$id_acompte = $wbuldet5->fk_facture;
			$wbuldet5->dateDerniereRelance =  dol_print_date(dol_now('tzuser'), '%Y-%m-%d %H:%M:%S');
			$wbuldet5->nbRelance++  ;
		}		
			
		$desc = 'Acompte venu de Stripe par '.$StripeNomPayeur;
		if (!empty($StripeMailPayeur)) $desc .=' ( mail - '.$StripeMailPayeur.')';
		if (!empty($StripeMailPayeur) and !empty($StripeSmsPayeur)) $desc .=' - ';
		if (!empty($StripeSmsPayeur)) $desc .=' ( Mobile - '.$StripeSmsPayeur.')';
		if (empty($id_stripe)) {
			if ($bull->type == 'Insc') $note = $langs->trans('Bulletin') ;
			else $note = $langs->trans('Location') ;
			$note .= ' : '.$bull->ref;	
			$wcDol = new cglInscDolibarr ($this->db);			
			$id_acompte = $wcDol->createAcompte($bull, $StripeMtt, false, $desc , $note, false, false);
			$wbuldet5->fk_acompte = $id_acompte;
			unset($wcDol);
			// Création enr de societe_remise_excep	- obsolette - l'acompte sera remise exceptionnelle à la ffacturation		
			/*$pwai = new DiscountAbsolute ($this->db);
			$pwai->fk_soc = $bull->id_client;
			$pwai->discount_type = 0;
			$pwai->description = '(DEPOSIT)';
			$pwai->amount_ht = $StripeMtt;
			$pwai->amount_tva = 0;
			$pwai->amount_ttc = $StripeMtt;
			$pwai->tva_tx = 0;
			$pwai->fk_facture_source = $id_acompte;
			$ret = $pwai->create($user);
			$wbuldet5->fk_soc_rem_execpt = $ret;
			*/
			$wbuldet5->nbRelance = 0;
			if (! empty($modelmailchoisi)) $wbuldet5->ModelMail = $modelmailchoisi;
			if (! empty($StripeNomPayeur)) $wbuldet5->Nompayeur = $StripeNomPayeur;
			if (! empty($StripeMtt)) $wbuldet5->montant = $StripeMtt;
			if (! empty($StripeMailPayeur)) $wbuldet5->mailpayeur = $StripeMailPayeur;
			if (! empty($StripeSmsPayeur)) $wbuldet5->smspayeur = $StripeSmsPayeur;
			 $wbuldet5->dateenvoi= dol_print_date(dol_now('tzuser'), '%Y-%m-%d %H:%M:%S');
			$this->ModifierLibAcompte($desc, $wbuldet5->fk_acompte);

			// Récupération du libellé affiché dans la boite Stripe lors du paiement par le client
			if (stripos($libelleCarteStripe, '<reference') > 0)  {
					$wac = new Facture ($this->db);
					$ret = $wac->fetch($id_acompte);
					$libelleCarteStripe = substr($libelleCarteStripe, 0, stripos($libelleCarteStripe,'<reference') -1).' ' .$wac->ref;
					//$libelleCarteStripe = $wac->ref;
					unset ($wac);				
			}
			if (! empty($libelleCarteStripe)) $wbuldet5->libelleCarteStripe = $libelleCarteStripe;
			if (empty($wbuldet5->fk_bull)) $wbuldet5->fk_bull = $bull->id;
		
		}
		
		if (empty($id_stripe)) {
			// Crtéation  bulletin	demande Stripe
			$ret = $wbuldet5->InsertDemandeStripe ($user);
			if ($ret > 0) $id_stripe = $ret;
		}
		elseif (!empty($id_stripe)) {
			// Modification  bulletin	demande Stripe	
			$ret = $wbuldet5->RelanceDemandeStripe($user);
		}
	
		if (empty($id_stripe) or empty($wbuldet5->stripeUrl)) {
			$wfctDol = new CglFonctionDolibarr($this->db);
			$wurl = getOnlinePaymentUrl(0, 'free', '', $StripeMtt, $wbuldet5->libelleCarteStripe.'.INV='.$id_acompte.'&email='.$wbuldet5->mailpayeur.'&sms='.$wbuldet5->smspayeur);
			//Verrue visant à régler un problème aléatoire du doublement de 'https:' en tête du lien
			if (substr($wurl, 6, 5) == 'https')
				$wurl = substr($wurl, 6);
			$bull->stripeUrl = $wfctDol->urlEncode($wurl);
			unset($wfctDol);
		}
		else  $bull->stripeUrl = $wbuldet5->stripeUrl;
		$derRelMailSms='Mail or Sms';
		$ret1 = $wbuldet5->UpdateUrlStripe ($user, $id_stripe, $bull->stripeUrl, $derRelMailSms);

		if ($ret < 0) return -1;
		if ($ret1 < 0) return -2;
		if ($bull->statut == $bull->BULL_ENCOURS) $fl_PreInscrire = 1;				
		else $fl_PreInscrire = 0;	
		unset ($wbuldet5);	
		return 		  $fl_PreInscrire;
	} //GestionEnvoiDemandeStripe

	
	function ModifierLibAcompte($desc, $id_acompte)
	{
		global $user;	
		$objacompte = new Facture($this->db);
		$objacompte->id = $id_acompte;
		$ret = $objacompte->fetch_lines();
		
		$objacomptedet = new FactureLigne ($this->db);
		if (is_array ($objacompte->lines) and !empty($objacompte->lines)) {
			$objacomptedet = $objacompte->lines[0];		
			$objacomptedet->desc =$desc;		
			$objacomptedet->label = $desc;	
			$objacomptedet->update($user, 0);
			unset($objacomptedet);
		}		
	} //ModifierLibAcompte

	Function ConfSupDemandeAcompte($id_stripe)
	{
		$wbulline = new BulletinLigne ($this->db);
		$wbulline->id = $id_stripe;
		$wbulline5 = new BulletinDemandeStripe ($this->db);
		$ret = $wbulline5->fetchDemandeStripe ( $id_stripe);
		$wacompte = new Facture ($this->db);
		$wacompte->id = $wbulline5->fk_acompte ;
		$wacompte->ref = $wbulline5->RefAcompte;
		$wacompte->brouillon = 1;
		$wsoc =  new Societe($this->db);
		$wacompte->socid = $bull->id_client;
		$wacompte->type = 3;
		$ret = $wacompte->delete($user);
		
		$wbulline->update_champs('action','X');
		unset ($wacompte);
		unset ($wbulline);
		unset ($wbulline5);
		unset ($wsoc);	
	} //ConfSupDemandeAcompte


	Function SupDemandeAcompte($id_stripe)
	{
		global  $id_contratdet, $db, $langs, $bull, $confirm, $CONF_STRIPESUPP;

		//print "<p>SUP Loc Materiel - Confirmation Suppression - id_bulldet:".$id_bulldet."</p>";
		if ($bull->type == 'Insc') $parurl = 'id_bull';
		elseif ($bull->type == 'Loc') $parurl = 'id_contrat';
		if ($bull->type == 'Insc') $parurldet = 'id_bulldet';
		elseif ($bull->type == 'Loc') $parurldet = 'id_contratdet';	
		$wbulline5 = new BulletinDemandeStripe ($this->db);
		$ret = $wbulline5->fetchDemandeStripe ( $id_stripe);
		$text=$langs->trans('ConfDemandeSupStripe',$wbulline5->Nompayeur,$wbulline5->montant,$wbulline5->mailpayeur);
		
		$form = new Form($db);
		$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?'. $parurl.'='.$bull->id.'&'.$parurldet.'='.$id_stripe.'&id_stripe='.$id_stripe,$langs->trans('DeleteStripe'),$text,$CONF_STRIPESUPP,'','',1);
	
		print $formconfirm;

	} //SupDemandeAcompte

	function fetchPaiementBanquebyIdAcompte($idacompte)
	{
		$sql = "SELECT pf.fk_paiement , p.fk_bank ";
		$sql.= " FROM `llx_facture` as f  ";
		$sql.= " LEFT JOIN llx_paiement_facture as pf on pf.fk_facture = f.rowid ";
		$sql.= " LEFT JOIN llx_paiement as p on pf.fk_paiement = p.rowid ";
		$sql.= " WHERE f.rowid= '". $idacompte."'";
        dol_syslog(get_class($this)."::fetchPaiementBanquebyIdAcompte ");
        $resql=$this->db->query($sql);
        if ($resql)
        {			
                $obj = $this->db->fetch_object($resql);
				$line = new  BulletinDemandeStripe ($this->bd);
				$id_paiement = $obj->fk_paiement;
				$id_banque = $obj->fk_bank;
		}		
		return array($id_paiement, $id_banque);
		
	}// fetchPaiementBanquebyIdAcompte
	/*
	* Construit et Envoi le message spécifique au paiement par Stripe des BU/LO
	*
	* @param	object 	$bull	contient les informations à passer au message	
	* @param	string	$MailPayeur	Destinataire du mail
	* @param	string	$MailCopie	Copie du mail 
	* @param	string	$nomPayeur	Nom du destinataire
	* @param	int		$id_model	identifiant du modèle de mail
	* @param	string	$type_model	type du modèle de mail (optionnel)
	*
	* @return	int		rapport d'envoie
	* voir EnvoiMessageAutomatique
	*/	
	function EnvoiMessageAutomatiqueCgl ($bull, $MailPayeur, $MailCopie, $nomPayeur, $id_model ,$type_model ='')
	{
		return $this->EnvoiMessageAutomatique($bull->id_client, $bull, $MailPayeur, $MailCopie, $nomPayeur, $id_model ,$type_model ='', 1);
	} //EnvoiMessageAutomatiqueCgl
	
	/*
	* Construit et Envoi le message 
	*
	* @param	int 	$id_soc		Id du client
	* @param	object 	$donnees	contient les informations à passer au message	
	* @param	string	$MailPayeur	Destinataire du mail
	* @param	string	$MailCopie	Copie du mail 
	* @param	string	$nomPayeur	Nom du destinataire
	* @param	int		$id_model	identifiant du modèle de mail
	* @param	string	$type_model	type du modèle de mail (optionnel)
	* @param	int		$type_cgl	1 - charge les variables Dolibarr et CGL, 0 charhe les seules variables Dolibarr
	*
	* @return	int		rapport d'envoie
	*/	
	function EnvoiMessageAutomatique($id_soc, $donnees, $MailPayeur, $MailCopie, $nomPayeur, $id_model ,$type_model ='', $type_cgl =1)
	{
		global $langs, $conf, $user, $db;	

		$actiontypecode='AC_OTH_AUTO';
		$object = New Societe($db);
		// préparation de l'objet de 
		$id = $object->id = $id_soc;
		$trigger_name = '';

		// Emetteur
		$_POST['frommail']= $conf->global->MAIN_MAIL_EMAIL_FROM;
		$_POST['fromname']= $conf->global->MAIN_INFO_SOCIETE_NOM;
		// Destinataire
		$object->nom = $nomPayeur;
		$object->Environ = 'Stripe';
		$_POST['sendto'] = $MailPayeur;
		$_POST['deliveryreceipt'] = 0;
		if (!empty($MailCopie) and $MailPayeur <> $MailCopie)
			{
				$_POST['sendtocc'] = $MailCopie;
			}
		
		// Recherche modèle
		$formmail = new FormMail ($this->db);
		$arraydefaultmessage=$formmail->getEMailTemplate ($this->db, $type_model, $user, $langs, $id_model );
		//Variables communes valorisées ';
		$arrayoffamiliestoexclude=array('member', 'objectamount');
		if (! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude=null;
		if ($type_cgl == 1) $tmpObj = null;
		else $tmpObj = $donnees; 
		$substitutionarray = getCommonSubstitutionArray($langs, 0, $arrayoffamiliestoexclude, $tmpObj);

		//Variables inscriptions valorisées ';
		$parameters = array(	
				'mode' => 'formemailwithlines'
			);
		$temptab=array();
		if ($type_cgl == 1) {
			require_once(DOL_DOCUMENT_ROOT.'/custom/cglinscription/core/substitutions/functions_inscription.lib.php');
			inscription_completesubstitutionarray ($temptab,$outputlangs,$donnees,$parameters, 2);
			// Variables lignes valorisées ';
			$temptablig=array();
			inscription_completesubstitutionarray ($temptablig,$outputlangs,$donnees,$parameters, 1);
		}
		else $temptab = $temptablig =null;
		
		// Separer substit et substit_lines
		$formmail->substit = array_merge($temptab, $substitutionarray);	
		$formmail->substit_lines = array();
		//$formmail->substit_lines =  array_merge($temptablig,$formmail->substit);
		$formmail->substit_lines =  $temptablig;

		// MESSAGE
		// Substitution
		if ($arraydefaultmessage && $arraydefaultmessage->content) {
			$defaultmessage = $arraydefaultmessage->content;
		}
			
		//Add lines substitution key from each line
		$lines = '';
		$defaultlines = $arraydefaultmessage->content_lines;
		
		if (isset($defaultlines) and ! empty($formmail->substit_lines))
		{
			foreach ($formmail->substit_lines as $substit_line)
			{
				$lines .= make_substitutions($defaultlines, $substit_line)."\n";
			}
		}
		$lines=make_substitutions($lines, $formmail->substit);
		$formmail->substit['__LINES__']=$lines;

		$defaultmessage=make_substitutions($defaultmessage, $formmail->substit);
		// Clean first \n and br
		$defaultmessage=preg_replace("/^(<br>)+/", "", $defaultmessage);
		$defaultmessage=preg_replace("/^\n+/", "", $defaultmessage);
		$_POST['message'] = $defaultmessage;

		// SUJET
		//$_POST['subject'] = $formmail->getHtmlForTopic($arraydefaultmessage, $helpforsubstitution);
		$defaulttopic=make_substitutions($arraydefaultmessage->topic, $formmail->substit);
		$_POST['subject'] = $defaulttopic;

		if (isset($arraydefaultmessage) and !empty($arraydefaultmessage->joinfiles))
		{
			//foreach ($arraydefaultmessage->joinfiles as $$file) 
			// à passer dans SESSION à partir de Template
			//	$formail->add_attached_files($file, basename($file), dol_mimetype($file));
		}
		// passage à Action_sendmails
		$_POST['joinfiles'] = $arraydefaultmessage->joinfiles;

		$action = 'send';		
		include DOL_DOCUMENT_ROOT.'/custom/cglavt/core/actions_sendmails.inc.php';
			
	} //EnvoiMessageAutomatiqueCgl

	
} // fin de classe
?>