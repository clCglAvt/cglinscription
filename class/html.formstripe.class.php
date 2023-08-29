<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr V15
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
 *  \file       cglinscription/class/html.formstripe.php
 *  \ingroup    cglinscription stripe
 *  \brief      Pour afficher les spécifités Stripe dans Cglinscription (Inscription et Location)
 *				
 */

 
/*
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT."/custom/agefodd/class/html.formagefodd.class.php";
require_once DOL_DOCUMENT_ROOT."/custom/agefodd/class/agsession.class.php";
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
*/
require_once(DOL_DOCUMENT_ROOT."/custom/cglavt/class/html.cglFctCommune.class.php");
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/bulletin.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/html.formcommun.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


 class FormStripeCAV extends Form  {
 
	
    function __construct($db)
    {
		global $langs, $db;
        $this->db = $db;
		$langs->load('cglinscription@cglinscription');
	}
	/*
	* Permet la saisie d'éléments spécifique Stripe lors de la préparation des M%ails ou Sms
	*
	* @param $StripeNomPayeur 			string		Nom du payeur
	* @param $StripeMtt					decimal		Montant du paiement Stripe
	* @param $StripeMailMobilePayeur	string		Mail ou numéro de mobile du payeur
	* @param $StripeModelMail			integer		Identifiant du Modèle Mail ou Sms
	* @param $libelleCarteStripe		string		Libellé à insérer dans la cartouche Stripe
	* @param $ModeMailSms				string		'Mail' si on envoie un Mail, 'Sms' si on envoie un SMS
	* return String à afficher pour la boite de saisie des données Stripe
	*/
	function SaisieDemandeStripe( $StripeNomPayeur, $StripeMtt, $StripeMailPayeur, $StripeModelMail, $libelleCarteStripe, $ModeMailSms, $StripeSmsPayeur ='')
	{
		global $langs, $id_stripe, $bull; 
		$wfc = new FormCglCommun ($this->db);
		
		if ($bull->type == 'Insc') {
			$lb_id = 'id_bull';
		}
		elseif ($bull->type == 'Loc') {
			$lb_id = 'id_contrat';
		}
		if ($ModeMailSms == 'Mail') $fl_Mail = true;
		elseif ($ModeMailSms == 'Sms') $fl_Mail = false;
		$out = ""; 
		$out .=  '<form method="POST" name="stripform" enctype="multipart/form-data"  id="stripform" action="'.$_SERVER["PHP_SELF"].'?'.$lb_id.'='.$bull->id.'&action=';
		if ($ModeMailSms == 'Mail') $out .= 'presendsms';
		elseif ($ModeMailSms == 'Sms') $out .= 'presend';		
		$out .= '#AncreMailSms">'."\n";

		if ($ModeMailSms == 'Mail') $fl_Mail = true;
		elseif ($ModeMailSms == 'Sms') $fl_Mail = false;
		else 	return -1;
		$out .=  '<a id="stripmail" name="stripmail"></a>';
		if ($fl_Mail == true) $out .=  '<input style="display:none" type="submit" id="sendmail" name="sendmail">';
		else  $out .=  '<input style="display:none" type="submit" id="sendSms" name="sendSms">';
		$out .=  '<input type="hidden" name="token" value="'.newtoken().'" />';
		$out .=  '<input type="hidden" name="BtEncStripe" value="oui" />';
		$out .=  '<input type="hidden" name="id_stripe" value="'.$id_stripe.'" />';
		if ($fl_Mail == true) {
			if (GETPOST("etape") == CglStripe::STRIPE_MAIL_STRIPE or GETPOST("etape") == CglStripe::STRIPE_MAIL_APPLY_STRIPE)
			{
				$out .=  '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_MAIL_APPLY_STRIPE.'">';
			}
			if (GETPOST("etape") == CglStripe::STRIPE_REL_MAIL_STRIPE or GETPOST("etape") == CglStripe::STRIPE_REL_MAIL_APPLY_STRIPE) {
				$out .=  '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_REL_MAIL_APPLY_STRIPE.'">';
			}
		}
		else {
			if (GETPOST("etape") == CglStripe::STRIPE_SMS_STRIPE or GETPOST("etape") == CglStripe::STRIPE_SMS_APPLY_STRIPE) {
				$out .=  '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_SMS_APPLY_STRIPE.'">';
			}
			if (GETPOST("etape") == CglStripe::STRIPE_REL_MAIL_STRIPE or GETPOST("etape") == CglStripe::STRIPE_REL_SMS_APPLY_STRIPE) {
				$out .=  '<input type="hidden" name="etape" value="'.CglStripe::STRIPE_REL_SMS_APPLY_STRIPE.'">';
			}
		}
		$out .=  '<input type="hidden" name="modelmailselected" value="" />';
/*		if ($fl_Mail == true) {
			$out .=  '<div id="DivInfoStripe" style="float:left;width:50%">';
			$out .=  $langs->trans('AideFichierMailBInscription', 'CGV'.$bull->type);
			$out .=  '<br>';
			$out .=  $langs->trans('AideFichierMailBInscriptionBis');
			$out .=  '</div >'; // Fermeture DivInfoStripe
			$out .=  '<div id="TbStripe" style="width:50%;align:center;margin-left:65%;" >';
		}
		else 
*/
		$out .=  '<div id="TbStripe"  >';
		$out .=  '<table border=1><tr><td>';
		$out .=  '<table id= "SaisieDemandeStripe"><tr><td>';	
		
		if ($bull->type == 'Loc') $typemodele='cgllocation';
		if ($bull->type == 'Insc') $typemodele='cglbulletin';

		if ($fl_Mail == true) {
			$out .=  '<span >'.$langs->trans('SelectMailModel').':</span> ';
		}
		else {
			$out .=  '<span >'.$langs->trans('SelectSmsModel').':</span> ';
		}
		$out .= $wfc->select_model($StripeModelMail, $typemodele, $ModeMailSms,  'modelmailstripe', 0, 0) ;
		$out .=  '</td></tr><tr><td>';
		$out .=  '</td></tr><tr><td>';
		$out .=  '</td></tr><tr><td>';
		$out .=  '<span >'.$langs->trans('StripeNomPayeur').':</span> ';
		$out .=  '<input class=flat type="text" name="StripeNomPayeur" value="'.$StripeNomPayeur.'">';
		$out .=  '</td></tr><tr><td>';
		
		$out .=  '<br><span style="color:red;font-weight:bold" >'.$langs->trans('StripeMttPaye').':</span> ';

		print '<script> function EteindreBtEnvoi(o) {
			document.getElementById("sendmail_CAV").disabled=true;
			};</script>';
		//if (empty($StripeMtt)) 
				$out .=  '<input class=flat type="text" name="StripeMtt" value="'.$StripeMtt.'" onchange="EteindreBtEnvoi(this)"> Euros ';
			
		if ($bull->type == 'Loc') $text = 'AidMnttstripeLoc';
		elseif ($bull->type == 'Insc') $text = 'AidMnttstripeInsc';
		else $text = '';
			if (!empty($text)) $out .=   info_admin($langs->trans($text),1);
		/*else {
			$out .=  $StripeMtt;
			$out .=  '<input type="hidden" name="StripeMtt" value="'.$StripeMtt.'" /> ';
		}
		*/

		//$out .=  '<input class=flat type="text" name="StripeMtt" value="'.$StripeMtt.'">';
		$out .=  '</td></tr><tr><td>';
		if ($fl_Mail == true) {
			if (empty($StripeMailPayeur)) $style = "style='color:red'";
			else $stype = '';
			$out .=  '<br><span  '.$style.' >'.$langs->trans('StripeMailPayeur').':</span  > '.'<input class=flat type="text" name="StripeMailPayeur"value="'.$StripeMailPayeur.'" onchange="EteindreBtEnvoi(this)">';
		}
		else $out .=  '<br><span >'.$langs->trans('StripeMobilePayeur').':</span> '.'<input class=flat type="text" name="StripeSmsPayeur"value="'.$StripeSmsPayeur.'" onchange="EteindreBtEnvoi(this)">';
		


		//Libelle sur carte bancaire
		$out .=  '</td></tr><tr><td>';
		if ($bull->type == 'Insc') $text = 'LibCBStripeInsc';
		elseif ($bull->type == 'Loc')$text = 'LibCBStripeLoc';
		$out .=  '<span >'.$langs->trans($text).':</span> ';
		$out .=  '</td></tr><tr><td  width="100%" max-width="100%">';
		$out .=  '<input class=flat type="text" name="libelleCarteStripe"value="'.$libelleCarteStripe.'" size=67>';
		

		
		$out .=  '</td></tr><tr><td align=center>';
		
		if ($fl_Mail == true) $out .=  '<input class="button" type="submit" value="'.$langs->trans('Apply').'" name="modelselected" id="modelselected">';
		else $out .=  '<input class="button" type="submit" value="'.$langs->trans('Apply').'" name="SMSmodelselected" id="SMSmodelselected">';
		$out .=  '</td></tr></table>'; // Fermeture table SaisieDemandeStripe
		$out .=  '</td></tr></table>';
		$out .=  '</div >'; // Fermeture div TbStripe;
		$out .=  '</form>';
		unset ($wfc);
		return $out;
	} //SaisieDemandeStripe
	function Affiche_Demandes_Stripe($id)
	{
		global $langs, $bull;
		global $ACT_STRIPESUPP, $ACT_STRIPEREMB, $ACT_STRIPERELMAIL, $ACT_STRIPERELSMS;

		$out = "";		
		if ($bull->type == 'Insc') {
			$texteid = 'id_bull';
		}
		elseif ($bull->type == 'Loc') {
			$texteid = 'id_contrat';
		}
		$pictochemin = DOL_URL_ROOT.'/custom/cglinscription/img/';
		
		$wfc = new FormCglCommun ($this->db);

		$numDemStripe = $bull->NbStripeinBull('total');	
		if ($numDemStripe > 0) {
		// Afficher entere
			$wfctcomm = new FormCglFonctionCommune($this->db);
			$wfctcomm->AfficheParagraphe("DemandeStripe", 3);
			
			$out .=  '<table class="liste" width="100%"><tr><td>';
			$out .=  '<table id="AfficheDemandesStripe" >';
			$out.= '<tr class="liste_titre">';
			$out.= '<td ></td>';
			$out.= '<td>'.$langs->trans("DateEnvoi").'</td>';
			$out.= '<td>'.$langs->trans("MontantStripe").'</td>';
			$out.= '<td>'.$langs->trans("NomPayeur").'</td>';
			$out.= '<td>'.$langs->trans("MailPayeur").'</td>';
			$out.= '<td>'.$langs->trans("Acompte").'</td>';
			$out.= '<td>'.$langs->trans("DateEncais").'</td>';
			$out.= '<td>'.$langs->trans("Relance").'</td>';
			unset($wfctcomm);
				foreach ($bull->lines_stripe as $fbulldet5) {
					$out .= '<tr><td width="15%" align="center">';						
					// Actions			
					if (empty($fbulldet5->date_paiement)) {
						$out .=  '<a href="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$id.'&action='.$ACT_STRIPESUPP.'&id_stripe='.$fbulldet5->id.'#AncrePaiement">'.img_delete().'</a>&nbsp;';
					    $out .=  '<a href="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$id.'&action='.$ACT_STRIPEREMB.'&id_stripe='.$fbulldet5->id.'#AncrePaiement">'.img_picto_common('', $pictochemin.'remb',  '', true).'</a>';
					    $out .=  '<a href="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$id.'&action='.$ACT_STRIPERELMAIL.'&id_stripe='.$fbulldet5->id.'&etape='.CglStripe::STRIPE_REL_MAIL_STRIPE.'#AncreMailSms">'.img_picto_common($langs->trans("BtRelDemStripeMail"), $pictochemin.'mail',  '', true).'</a>&nbsp;';
						if ($conf->ovh->enabled)   
							$out .=  '<a href="'.$_SERVER["PHP_SELF"].'?'.$texteid.'='.$id.'&action='.$ACT_STRIPERELSMS.'&id_stripe='.$fbulldet5->id.'&etape='.CglStripe::STRIPE_REL_SMS_STRIPE.'#AncreMailSms">'.img_picto_common($langs->trans("BtRelDemStripeSms"), $pictochemin.'sms',  '', true).'</a>&nbsp;';
					}
					$out .= '</td><td>';

					// Dater Envoi
					$out .= CglFonctionCommune::transfDateFr(substr($fbulldet5->dateenvoi,0,10));
					$out .= '</td><td>';	
					// Montant
					$out .= $fbulldet5->montant;
					$out .= '</td><td>';	
					// Nom Payeur
					$out .= $fbulldet5->Nompayeur;	
					$out .= '</td><td>';	
					// Mail/SMS
					$out .= $fbulldet5->mailpayeur;
					if (!empty($fbulldet5->mailpayeur) and !empty($fbulldet5->smspayeur))		$out .= 	' ou ';
					$out .= $fbulldet5->smspayeur;	
					$out .= '</td><td>';
					// Acompte			
					$out .=$wfc->getNomUrl("object_company.png", 'Facture',0,$fbulldet5->fk_acompte, '').$fbulldet5->RefAcompte;
					$out .= '</td><td>';
					// Etat	
					if (empty($fbulldet5->date_paiement)) $out .= $langs->trans( 'NoEncais'	); 
					else 	 $out .= CglFonctionCommune::transfDateFr($fbulldet5->date_paiement);	
					$out .= '</td><td align="center">';
					// Relance	
					$wstrdate = CglFonctionCommune::transfDateFr(substr($fbulldet5->dateDerniereRelance,0,10));
					$texte = $langs->trans('LibRelanceStripeLe',$fbulldet5->nbRelance, $wstrdate);
					if (!empty($fbulldet5->nbRelance) and $fbulldet5->nbRelance > 0) $out .=  info_admin($texte,1);
					$out .= '</td></tr>';
					
				}
			
			$out .=  '</td></tr></table>';// Fermeture table AfficheDemandesStripe
			$out .=  '</td></tr></table>';
			unset($wfc);
			return $out;
		}
	} //Affiche_Demandes_Stripe
 
}//Class
 
?>