<?php
/* Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * Version CAV - 2.8 - hiver 2023 -
 *			- dans la boite des factures impayées (Factures/Factures Clients) ne plus faire apparaître les AC à 0
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/custom/CglInscription/class/actions_CglInscription.class.php
 *	\ingroup    CglInscription
 *	\brief      File to control actions
 */
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *	Class to manage hooks for module CglInscription
 */
class ActionsCglInscription
{
    var $db;
    var $error;
    var $errors=array();

    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }


    /**
     *    Execute action à l'envoi du mail, pour ajouter un fichier
     *
     *    @param	array	$parameters		Array of parameters
     *    @param    mixed	$object      	Deprecated. This field is not used
     *    @param    string	$action      	'add', 'update', 'view'
     *    @return   int         			<0 if KO,
     *                              		=0 if OK but we want to process standard actions too,
     *                              		>0 if OK and we want to replace standard actions.
     */
    function getFormMail($parameters,&$object,&$action)
    {
        global $db,$langs,$conf;	
		foreach($parameters as $lib=>$elementList){
				if ($lib == 'trackid') {
					if (!(substr($elementList,0,3) == 'inv' or substr($elementList,0,3) == 'pro')) 
						return (0);
					else $refobj='-'.$elementList;
				}
				if ($lib == 'formmail') break;
		}
		if ($conf->cglinscription->enabled) {
			
			$listofpaths=array();
        	$listofnames=array();
        	$listofmimes=array();
            $keytoavoidconflict = empty($this->trackid)?'':'-'.$this->trackid;   // this->trackid must be defined
			$file =  $conf->cglinscription->dir_output.'/CGV/CGV.pdf';
			if (!empty($_SESSION["listofpaths".$refobj])) $_SESSION["listofpaths".$refobj] .=';';
			$_SESSION["listofpaths".$refobj] .= $file;
			if (!empty($_SESSION["listofnames".$refobj])) $_SESSION["listofnames".$refobj] .=';';
			$_SESSION["listofnames".$refobj] .= 'CGV.pdf';
			if (!empty($_SESSION["listofmimes".$refobj])) $_SESSION["listofmimes".$refobj] .=';';
			$_SESSION["listofmimes".$refobj] .= 'application/pdf';

		}
        return 0;
    }

 
    
    /**
     *    Execute action
	 *	Cette liste est aussi utilisée dans cglcommunlocInsc.class.php
     *
     *    @param	array	$parameters		Array of parameters
     *    @param    mixed	$object      	Deprecated. This field is not used
     *    @param    string	$action      	'add', 'update', 'view'
     *    @return   int         			<0 if KO,
     *                              		=0 if OK but we want to process standard actions too,
     *                              		>0 if OK and we want to replace standard actions.
     */
    function emailElementlist_old($parameters,&$object,&$action)
    {
        global $db,$langs,$conf;
		foreach($parameters as $lib=>$elementList)
				if ($lib == 'elementList') break;
		$this->results['cgllocation']=$langs->trans('MailToContrat');
		$this->results['cglbulletin']=$langs->trans('MailToBulletin');
		$this->results['cglresa']=$langs->trans('MailToResa');	
		$this->results['cglstripe']=$langs->trans('TiSendStripe');			

	
		//if ($conf->cglinscription->enabled) {
		//	$elementList['CahierSuivi']=$langs->trans('MailToSuivi');
		//}
		
        return 0;
    }
	function smsElementlist_old($parameters,&$object,&$action)
    {
        global $db,$langs,$conf;
		foreach($parameters as $lib=>$elementList)
				if ($lib == 'elementList') break;
		$this->results ['cgllocationSMS'] = $langs->trans('TiSendSMSLocation');
		$this->results ['cglbulletinSMS'] = $langs->trans('TiSendSMSInscription');
		$this->results ['cglresaSMS'] = $langs->trans('TiSendSMSReservation');		
		$this->results ['cglStripeSMS'] = $langs->trans('TiSendSMSStripe');	

/*		
		$this->results['cgllocationSMS']=$langs->trans('SMSToContrat');
		$this->results['cglbulletinSMS']=$langs->trans('SMSToBulletin');
		$this->results['cglresaSMS']=$langs->trans('SMSToResa');
		$this->results['cglstripe']=$langs->trans('TiSendStripe');	
	*/	
        return 0;
    }

	function runTrigger()
	{
		//if (==PAYMENTONLINE_PAYMENT_OK) // on est dans l'appel depui l'arrivée du paiement
		// runTrigger ou execture Hook????
		
	}
	/**
	 * setLinkedObjectSourceTargetType
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return 1 avec modif, 0 sans modif
	 */
	function setLinkedObjectSourceTargetType($parameters,&$object,&$action)
	{
		$this->resArray['origin'] = 'bulletin';
		return 1;
	} //setLinkedObjectSourceTargetType


//CCA DOUBLE
	function addSearchEntry($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;
		
		$error = 0; // Error counter
		$myvalue = 'test'; // A result value
		$arrayresult = array();
		//print_r($parameters);
		//echo "action: " . $action;
		//print_r($object);

		if (in_array('searchform', explode(':', $parameters['context'])))
		{
		  if (! empty($conf->cglinscription->enabled) 
				&& empty($conf->global->MAIN_SEARCHFORM_CGLINSCRIPTION_DISABLED) 
				&& $user->rights->cglinscription->lire)
			{
				$arrayresult['SearchIntoBulls']=array('position'=>205, 'img'=>'object_order', 'label'=>$langs->trans("SearchIntoBulls", $search_boxvalue), 'text'=>img_picto('', 'object_order').' '.$langs->trans("SearchIntoBulls", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/custom/cglinscription/facturation.php?mainmenu=CglInscription&ecran=archivestock&type=Insc'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):''));
				$arrayresult['SearchIntoLocs']=array('position'=>210, 'img'=>'object_order', 'label'=>$langs->trans("SearchIntoLocs", $search_boxvalue), 'text'=>img_picto('', 'object_order').' '.$langs->trans("SearchIntoLocs", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/custom/cglinscription/facturation.php?mainmenu=CglLocation&ecran=archivestock&type=Loc'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):''));
				$arrayresult['SearchIntoResas']=array('position'=>215, 'img'=>'object_order', 'label'=>$langs->trans("SearchIntoResas", $search_boxvalue), 'text'=>img_picto('', 'object_order').' '.$langs->trans("SearchIntoResas", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/custom/cglinscription/facturation.php?mainmenu=CglResa&ecran=archivestock&type=Resa'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):''));
			}

		}
		
		// Define $searchform
		if ((( ! empty($conf->societe->enabled) && (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) || empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))) || ! empty($conf->fournisseur->enabled)) && empty($conf->global->MAIN_SEARCHFORM_SOCIETE_DISABLED) && $user->rights->societe->lire)
		{
			$arrayresult['searchintothirdparty']=array('position'=>40, 'shortcut'=>'T', 'img'=>'object_company', 'label'=>$langs->trans("SearchIntoThirdparties", $search_boxvalue), 'text'=>img_picto('', 'object_company').' '.$langs->trans("SearchIntoThirdparties", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/societe/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}

		if (! empty($conf->societe->enabled) && empty($conf->global->MAIN_SEARCHFORM_CONTACT_DISABLED) && $user->rights->societe->lire)
		{
			$arrayresult['searchintocontact']=array('position'=>45, 'shortcut'=>'A', 'img'=>'object_contact', 'label'=>$langs->trans("SearchIntoContacts", $search_boxvalue), 'text'=>img_picto('', 'object_contact').' '.$langs->trans("SearchIntoContacts", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/contact/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}

		if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
		&& empty($conf->global->MAIN_SEARCHFORM_PRODUITSERVICE_DISABLED))
		{
			$arrayresult['searchintoproduct']=array('position'=>50, 'shortcut'=>'P', 'img'=>'object_product', 'label'=>$langs->trans("SearchIntoProductsOrServices", $search_boxvalue),'text'=>img_picto('', 'object_product').' '.$langs->trans("SearchIntoProductsOrServices", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/product/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}

		if (! empty($conf->propal->enabled) && empty($conf->global->MAIN_SEARCHFORM_CUSTOMER_PROPAL_DISABLED) && $user->rights->propal->lire)
		{
			$arrayresult['searchintopropal']=array('position'=>60, 'img'=>'object_propal', 'label'=>$langs->trans("SearchIntoCustomerProposals", $search_boxvalue), 'text'=>img_picto('', 'object_propal').' '.$langs->trans("SearchIntoCustomerProposals", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/comm/propal/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->expedition->enabled) && empty($conf->global->MAIN_SEARCHFORM_CUSTOMER_SHIPMENT_DISABLED) && $user->rights->expedition->lire)
		{
			$arrayresult['searchintoshipment']=array('position'=>80, 'img'=>'object_sending', 'label'=>$langs->trans("SearchIntoCustomerShipments", $search_boxvalue), 'text'=>img_picto('', 'object_sending').' '.$langs->trans("SearchIntoCustomerShipments", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/expedition/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->facture->enabled) && empty($conf->global->MAIN_SEARCHFORM_CUSTOMER_INVOICE_DISABLED) && $user->rights->facture->lire)
		{
			$arrayresult['searchintoinvoice']=array('position'=>90, 'img'=>'object_bill', 'label'=>$langs->trans("SearchIntoCustomerInvoices", $search_boxvalue), 'text'=>img_picto('', 'object_bill').' '.$langs->trans("SearchIntoCustomerInvoices", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/compta/facture/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}

		if (! empty($conf->supplier_proposal->enabled) && empty($conf->global->MAIN_SEARCHFORM_SUPPLIER_PROPAL_DISABLED) && $user->rights->supplier_proposal->lire)
		{
			$arrayresult['searchintosupplierpropal']=array('position'=>100, 'img'=>'object_propal', 'label'=>$langs->trans("SearchIntoSupplierProposals", $search_boxvalue), 'text'=>img_picto('', 'object_propal').' '.$langs->trans("SearchIntoSupplierProposals", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/supplier_proposal/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_SEARCHFORM_SUPPLIER_ORDER_DISABLED) && $user->rights->fournisseur->commande->lire)
		{
			$arrayresult['searchintosupplierorder']=array('position'=>110, 'img'=>'object_order', 'label'=>$langs->trans("SearchIntoSupplierOrders", $search_boxvalue), 'text'=>img_picto('', 'object_order').' '.$langs->trans("SearchIntoSupplierOrders", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/fourn/commande/list.php'.($search_boxvalue?'?search_all='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_SEARCHFORM_SUPPLIER_INVOICE_DISABLED) && $user->rights->fournisseur->facture->lire)
		{
			$arrayresult['searchintosupplierinvoice']=array('position'=>120, 'img'=>'object_bill', 'label'=>$langs->trans("SearchIntoSupplierInvoices", $search_boxvalue), 'text'=>img_picto('', 'object_bill').' '.$langs->trans("SearchIntoSupplierInvoices", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/fourn/facture/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->commande->enabled) && empty($conf->global->MAIN_SEARCHFORM_CUSTOMER_ORDER_DISABLED) && $user->rights->commande->lire)
		{
			$arrayresult['searchintoorder']=array('position'=>125, 'img'=>'object_order', 'label'=>$langs->trans("SearchIntoCustomerOrders", $search_boxvalue), 'text'=>img_picto('', 'object_order').' '.$langs->trans("SearchIntoCustomerOrders", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/commande/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}
		
		if (! empty($conf->projet->enabled) && empty($conf->global->MAIN_SEARCHFORM_PROJECT_DISABLED) && $user->rights->projet->lire)
		{
			$arrayresult['searchintoprojects']=array('position'=>130, 'shortcut'=>'Q', 'img'=>'object_projectpub', 'label'=>$langs->trans("SearchIntoProjects", $search_boxvalue), 'text'=>img_picto('', 'object_projectpub').' '.$langs->trans("SearchIntoProjects", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/projet/list.php'.($search_boxvalue?'?search_all='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->projet->enabled) && empty($conf->global->MAIN_SEARCHFORM_TASK_DISABLED) && $user->rights->projet->lire)
		{
			$arrayresult['searchintotasks']=array('position'=>135, 'img'=>'object_task', 'label'=>$langs->trans("SearchIntoTasks", $search_boxvalue), 'text'=>img_picto('', 'object_task').' '.$langs->trans("SearchIntoTasks", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/projet/tasks/list.php'.($search_boxvalue?'?search_all='.urlencode($search_boxvalue):''));
		}


		if (! empty($conf->contrat->enabled) && empty($conf->global->MAIN_SEARCHFORM_CONTRACT_DISABLED) && $user->rights->contrat->lire)
		{
			$arrayresult['searchintocontract']=array('position'=>140, 'img'=>'object_contract', 'label'=>$langs->trans("SearchIntoContracts", $search_boxvalue), 'text'=>img_picto('', 'object_contract').' '.$langs->trans("SearchIntoContracts", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/contrat/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->ficheinter->enabled) && empty($conf->global->MAIN_SEARCHFORM_FICHINTER_DISABLED) && $user->rights->ficheinter->lire)
		{
			$arrayresult['searchintointervention']=array('position'=>150, 'img'=>'object_intervention', 'label'=>$langs->trans("SearchIntoInterventions", $search_boxvalue), 'text'=>img_picto('', 'object_intervention').' '.$langs->trans("SearchIntoInterventions", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/fichinter/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}

		// HR
		if (! empty($conf->user->enabled) && empty($conf->global->MAIN_SEARCHFORM_USER_DISABLED) && $user->rights->user->user->lire)
		{
			$arrayresult['searchintouser']=array('position'=>200, 'shortcut'=>'U', 'img'=>'object_user', 'label'=>$langs->trans("SearchIntoUsers", $search_boxvalue), 'text'=>img_picto('', 'object_user').' '.$langs->trans("SearchIntoUsers", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/user/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->expensereport->enabled) && empty($conf->global->MAIN_SEARCHFORM_EXPENSEREPORT_DISABLED) && $user->rights->expensereport->lire)
		{
			$arrayresult['searchintoexpensereport']=array('position'=>235, 'img'=>'object_trip', 'label'=>$langs->trans("SearchIntoExpenseReports", $search_boxvalue), 'text'=>img_picto('', 'object_trip').' '.$langs->trans("SearchIntoExpenseReports", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->holiday->enabled) && empty($conf->global->MAIN_SEARCHFORM_HOLIDAY_DISABLED) && $user->rights->holiday->read)
		{
			$arrayresult['searchintoleaves']=array('position'=>240, 'img'=>'object_holiday', 'label'=>$langs->trans("SearchIntoLeaves", $search_boxvalue), 'text'=>img_picto('', 'object_holiday').' '.$langs->trans("SearchIntoLeaves", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/holiday/list.php?mainmenu=hrm'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->ticket->enabled) && empty($conf->global->MAIN_SEARCHFORM_TICKET_DISABLED) && $user->rights->ticket->read)
		{
			$arrayresult['searchintotickets']=array('position'=>245, 'img'=>'object_ticket', 'label'=>$langs->trans("SearchIntoTickets", $search_boxvalue), 'text'=>img_picto('', 'object_ticket').' '.$langs->trans("SearchIntoTickets", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/ticket/list.php?mainmenu=ticket'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):''));
		}
		if (! empty($conf->adherent->enabled) && empty($conf->global->MAIN_SEARCHFORM_ADHERENT_DISABLED) && $user->rights->adherent->lire)
		{
			$arrayresult['searchintomember']=array('position'=>250, 'shortcut'=>'M', 'img'=>'object_user', 'label'=>$langs->trans("SearchIntoMembers", $search_boxvalue), 'text'=>img_picto('', 'object_user').' '.$langs->trans("SearchIntoMembers", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/adherents/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
		}

		if (! $error)
		{
			//$this->results = array('myreturn' => $myvalue);
			$this->results = $arrayresult;
			$this->resprints = 'A text to show';
			return 1; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message - Liste des bjets à consultés';
			return -1;
		}
	}
//CCA DOUBLE  

  /**
     *    Execute action à l'affichage de la boite des factures impayées dans factures/factures Clients
     *
     *    @param	array	$parameters		Array of parameters
     *    @param    mixed	$object      	Deprecated. This field is not used
     *    @param    string	$action      	'add', 'update', 'view'
     *    @return   int         			<0 if KO,
     *                              		=0 if OK but we want to process standard actions too,
     *                              		>0 if OK and we want to replace standard actions.
     */
	function printFieldListWhereCustomerUnpaid($parameters, &$object, &$action, $hookmanager) {
			$hookmanager->resPrint = " AND   f.total_ttc + f.paye <> 0   ";
			return 0;
dol_syslog('CCA - dans hook');
 	}
     
}
?>