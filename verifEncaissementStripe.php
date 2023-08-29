<?php
/* Copyright (C) 2018-2019  Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 *
 * CCA 06/2022
 *
 * Version CAV - 2.6.1.3 - Création
 * Version CAV - 2.7 - été 2022 - Intégration dans menu CAV
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/*
*
* Intègre à Dolibarr les virements Stripe et les frais des opérations
*
*/
// Put here all includes required by your class file

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctDolibarrRevues.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cglavt/class/cglFctCommune.class.php';
if (!empty($conf->accounting->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('compta',  'stripe', 'cglinscription'));

// Security check
$socid = GETPOST("socid", "int");
if ($user->socid) {
	$socid = $user->socid;
}
/* PARAMETRES URL */
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$tabcheck =  array();

$str_datedebsais = GETPOST('datedeb', 'string');
$datedebsai = dol_mktime(0, 0, 0,  GETPOST( 'datedebmonth', 'int'), GETPOST('datedebday', 'int'),GETPOST('datedebyear', 'int'));
$str_datefinsai = GETPOST('datefin', 'string');
$datefinsai = dol_mktime(0, 0, 0, GETPOST('datefinmonth', 'int'), GETPOST('datefinday', 'int'), GETPOST('datefinyear', 'int'));
// On affiche le mois entier


if (empty($conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS)) setEventMessages("LibCptePmtStripe", null, 'warnings');
if (empty($conf->global->STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS)) setEventMessages("LibCpteVirStripe", null, 'warnings');
if (empty($conf->global->STRIPE_MAX_TRANSAC_RECUP)) setEventMessages("LibNbMaxLigStripe",null, 'warnings');
/*

INSERT INTO `llx_const` (`rowid`, `name`, `entity`, `value`, `type`, `visible`, `note`, `tms`) VALUES (NULL, 'STRIPE_MAX_TRANSAC_RECUP', '0', '100', 'chaine', '1', 'Nombre maximum de lignes que Stripe renvoie lors des interrogations', '2013-12-27 15:40:11'); 

*/
/* VARIABLE */
//$societestatic = new Societe($db);
$stripe = new Stripe($db); 

/* Securité*/
$result = restrictedArea($user, 'banque');


/*
 * AFFICHAGE
 */

/* Affichage Cadre Dolibarr */
llxHeader('', $langs->trans("StripeDolVir"));


/* LECTURE DONNEES */
$resultsql = Lecture_DonneesEncaiss($stripe,  $stripeaccount ='');

/* VIEW */
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
//	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="token" value="'.newToken().'">';
 
$title = $langs->trans("EncaissementStripe sans Ecriture Dolibarr");
$title .= ($stripeaccount ? ' (Stripe connection with Stripe OAuth Connect account '.$stripeacc.')' : ' (Stripe connection with keys from Stripe module setup)');
print_barre_liste($title, "", $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', 0, 0, "", 0, '', '',"");


/* entete*/

print '</form>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';	
print '<table class="tagtable liste'.'">'."\n";


if (empty($listOp)) $flg =false;
elseif (count($listOp->data) == 0) $flg =true;
else $flg =false;
AfficheEntete($flg);

// Affichage des virements Stripe dans l'ordre décroissant des dates

		$num = $db->num_rows($resultsql);
	
		$i = 0; 
		while ($i < $num)
		{		
			$objp = $db->fetch_object($resultsql);
			AfficherLigne ( $objp);
			$i++;
		}





print '</div>';
print '</form>';
	
// End of page
llxFooter();
$db->close();



function	AfficherLigne ( $Op)
{	
	global $tabfrais, $tabcheck , $tabMttEcr ,  $tabtexteEcrInf, $tabExisteVir, $tabrowid;
	global $langs;

	print "<tr>";
	// Ref
	if (!empty($stripeacc)) {
		$connect = $stripeacc.'/';
	}

	// Stripe customer
	print "<td>".$Op->tiers."</td>\n";

	// Origine
	if ( !empty($Op->RefAcpt)) $text = $Op->RefAcpt;
	else $text = $Op->IdAcpt."(".$Op->ref.")";
	
	if (strpos($Op->RefAcpt, 'AC') == 0 or  strpos($Op->RefAcpt, 'FA%') == 0 )
			$url = 'href="' . DOL_MAIN_URL_ROOT . '/compta/facture/card.php?ref='.$Op->RefAcpt.'"';
	elseif (is_numeric($Op->IdAcpt)) 	$url = 'href="' . DOL_MAIN_URL_ROOT . '/compta/facture/card.php?facid='.$Op->IdAcpt.'"';
	else $url = '';

	print "<td  class='center' ><a ".$url." target='_stripe'>".$text."</a></td>\n";
	
	// Date payment Stripe
	print '<td class="center">'.dol_print_date($Op->dateStripe, '%d/%m/%Y %H:%M')."</td>\n";
	
	// Id Stripe

	$url = 'https://dashboard.stripe.com/'.$connect.'test/payments/'.$Op->idStripe;
	if ($servicestatus) {
		$url = 'https://dashboard.stripe.com/'.$connect.'payments/'.$Op->idStripe;
	}

	print "<td  class='center' ><a href='".$url."' target='_stripe'>".img_picto($langs->trans('ShowInStripe'), 'globe')." ".$Op->idStripe."</a></td>\n";
	
	
	// Amount
	print '<td class="right">';
	print price($Op->MttStripe , 0, '', 1, -1, -1, '€');
	print "</td>";
	// Status
	print "<td class='right'>";
	print $Op->EtatStripe;
	print "</td>";
		// Date Insertion Ecriture
	print '<td class="center">'.dol_print_date($Op->datev, '%d/%m/%Y %H:%M')."</td>\n";
		// Montant Insertion Ecriture
	print '<td class="right">';
	print price($Op->amount , 0, '', 1, -1, -1, '€');
	print "</td>";
	
	// Bulletin
	if (empty($Op->RefBull)) $wbull = $Op->note_private;
	else $wbull = $Op->RefBull;
	print '<td class="center">'.$wbull."</td>\n";
		// TIers Dolibarr
	print '<td class="center">'.$Op->nom."</td>\n";
	// Type
	print '<td>'.$Op->FullTag.'</td>';
	
	print "</tr>";
} // Afficher

function AfficheEntete($flListeVide) 
{
	global $langs;
	
	print '<tr class="liste_titre">';
	print_liste_field_titre("Tiers Payant", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("Acompte (Ident)",$_SERVER["PHP_SELF"],"","","","","", "");
	print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("Id Stripe", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("Montant Stripe", $_SERVER["PHP_SELF"], "", "", "", "", "", "");
	print_liste_field_titre("Statut Stripe", $_SERVER["PHP_SELF"], "", "", "", '', "", "", 'center ');
	print_liste_field_titre("DateEcrDolibarr", $_SERVER["PHP_SELF"], "", "", "", '', "", "", 'center ');
	print_liste_field_titre("MttEcr", $_SERVER["PHP_SELF"], "", "", "", '', "", "", 'left ');
	print_liste_field_titre("Bulletin", $_SERVER["PHP_SELF"], "", "", "", '', "", "", 'center ');
	print_liste_field_titre("TiersDolibarr", $_SERVER["PHP_SELF"], "", "", "", '', '', '', 'right ');
	print_liste_field_titre("Tag", $_SERVER["PHP_SELF"], "", "", "", '','', '', 'center ');
	
	print "</tr>";
	
}	//AfficheEnteteVir

function Lecture_DonneesEncaiss($stripe,  $stripeaccount ='')
{
	global $conf, $langs, $db;	
	
	/* recherche des info compte Stripe */
	$stripeacc = $stripe->getStripeAccount($service);
	if (empty($stripeaccount))
	{
		//setEventMessage( $langs->trans('ErrorStripeAccountNotDefined'),  'warning');
	}

	if (!empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'alpha'))) {
		$service = 'StripeTest';
		$servicestatus = '0';
		setEventMessage($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'),  'warning');
	} else {
		$service = 'StripeLive';
		$servicestatus = '1';
	}
	$listOp = array();
	
	$annee = dol_print_date(dol_now('tzuser'), '%Y');
	$datedeb = dol_mktime(0, 0, 0, 1, 1, (int)$annee);
	try {

		if ($stripeacc) {
				$listOp = \Stripe\Charge::all(array("limit" => $conf->global->STRIPE_MAX_TRANSAC_RECUP,  "created" => array("gt" => $dt_deb)), array("stripe_account" => $stripeacc));
			} else {
				$listOp = \Stripe\Charge::all(array("limit" => $conf->global->STRIPE_MAX_TRANSAC_RECUP,  "created" => array("gt" => $dt_deb)));
		}
	} catch (Exception $e) {
		print '<tr><td colspan="6">'.$langs->trans('ErreurRecupVirementStripe').$e->getMessage().'</td></td>';
	}


	// Effacer table venant précédente interrogation
		$sql='';
		$sql="DROP TABLE  IF EXISTS cav_EncStripe";
		
		$result = $db->query($sql);
		if (!$result) {
			dol_syslog('verifEncaissementStripe:: - sql:'.$sql, LOG_ERR);
			return NULL;
		}
	// SQL - Creer table EncStripe et inserer info de lisOp (id, montant, date, tiers, etat, Fulltag, IdFact)
		//$lines_mat_mad=array();
		$sql='';
		$sql="CREATE TABLE `cav_EncStripe` (
			 `rowid` INT(11) NOT NULL AUTO_INCREMENT, 
			 `idStripe` VARCHAR(100) NOT NULL ,
			 `idEcr` INT(11) NULL , 
			 `MttDol` FLOAT(11,2) NULL , 
			 `MttStripe` FLOAT(11,2) NULL ,
			 `Tiers` VARCHAR(100) NULL ,
			 `dateStripe` DATE NULL , 
			 `dateEcr` DATE NULL ,
			 `IdTiers` INT(11) NULL ,
			 `EtatStripe` VARCHAR(100) NOT NULL ,
			 `FullTag` VARCHAR(100) NULL ,
			 `RefAcpt` VARCHAR(15)  NULL , 
			 `IdAcpt` INT(11)  NULL , 
			 `RefBull` VARCHAR(15) NULL , 
			 PRIMARY KEY (`rowid`)) ENGINE = InnoDB";
	
		$result = $db->query($sql);
		if (!$result){
			//dol_syslog('verifEncaissementStripe::- sql:'.$sql, LOG_ERR);
			dol_syslog('verifEncaissementStripe::create', LOG_ERR);
			return NULL;
		}
		//Insertion Encaissement Stripe;
		foreach ($listOp as $Op)		{
			$sql='';
			$wtag = $Op->description ;
			$IdAcpt = 0;
			$RefAcpt = '';
			
			// 3 cas : 
			// Facture format: %INV=(*).%ref=(FA_*) : SI FULLTAG contient INV=, CUS= ref=

			if (strpos($wtag, 'INV=') > 0 and  strpos($wtag, 'CUS=') > 0 and strpos($wtag, 'ref=') > 0  ) {
					$posInv = strpos($wtag, 'INV=') + 4;
					$posCus = strpos($wtag, '.CUS=') ;
					$lgn = $posCus -$posInv;
					$IdAcpt = substr($wtag, $posInv, $lgn);
					$posInv = strpos($wtag, 'ref=') + 4;
					$RefAcpt = substr($wtag, $posInv ,11);

			}
			// Location format: %(AC_*)%INV=(*)SI FULLTAG contient INV= et TAG= et AC
			elseif ( strpos($wtag, 'INV=') > 0 and  strpos($wtag, 'TAG=') > 0 and  strpos($wtag, 'AC') > 0 ) {
				$IdAcpt = substr($wtag, strpos($wtag, 'INV=') + 4);
				$posRefAcpt = strpos($wtag, 'AC');				;
					$RefAcpt = substr($wtag, (int)($posRefAcpt) ,11);
			}
			// Inscription  format: %INV=(*)  SI FULLTAG contient INV= et TAG=
			elseif ( strpos($wtag, 'INV=') > 0 and  strpos($wtag, 'TAG=') > 0 ) {
				$IdAcpt = substr($wtag, strpos($wtag, 'INV=') + 4);
				$RefAcpt = '';
			}

			if (empty($IdAcpt)) $IdAcpt = 0;
			$dt = $db->idate($Op->created);
			$Mtt = $Op->amount/100;
//			$sql="INSERT INTO cav_EncStripe  (idStripe, MttStripe,Tiers,dateStripe, EtatStripe,RefAcpt ,IdAcpt, FullTag )
			$sql="INSERT INTO cav_EncStripe  (idStripe, MttStripe,Tiers,dateStripe, EtatStripe,RefAcpt ,IdAcpt, FullTag )
				VALUES (
					    '".$Op->payment_intent."' ".
					 ", '".$Mtt."' ".
					 ", '".$Op->billing_details->name."' ".
					 ", '".$dt."' ".
					 ", '".$Op->status." - ".$Op->outcome->seller_message."' ".
					 ", '".$RefAcpt."' ".
					 ", '".$IdAcpt."' ".
					 ", '".$Op->description."' ".
					 ") ";
			
			$result = $db->query($sql);
			if (!$result){
				//dol_syslog('verifEncaissementStripe::- sql:'.$sql, LOG_ERR);	
				dol_syslog('verifEncaissementStripe::Insert', LOG_ERR);				
			}
		} // foreach


		//Sql - Recuperer les enr de EncStripe qui n'ont pas d'image dans llx_bank (id = label) ou dont le montant dans Stripe est différent de celui de llx_bank
		$sql="

		SELECT 'FacCoeurDol',	s.rowid as Idcav_EncStripe, b.rowid as IdBank, tiers, IdAcpt , '' ,   idStripe , '',  '',
			  MttStripe, b.amount, fac.ref, st.nom, RefAcpt, EtatStripe, dateStripe,  FullTag,''
			FROM `cav_EncStripe` as s 
				LEFT JOIN llx_facture as fac on fac.ref = RefAcpt
				LEFT JOIN llx_paiement_facture as facpmt on facpmt.fk_facture = fac.rowid
				LEFT JOIN llx_paiement as pmt on pmt.rowid = facpmt.fk_paiement
				LEFT JOIN llx_societe as st on fac.fk_soc = st.rowid 
				LEFT JOIN llx_bank as b on pmt.fk_bank = b.rowid 
			WHERE 
			not isnull(fac.rowid)
			and FullTag  like  '%CUS%' and isnull(b.rowid) and EtatStripe not like '%insufficient_funds%'


		union
		SELECT 'BU/LO',	s.rowid as Idcav_EncStripe, b.rowid as IdBank, tiers, IdAcpt , bdpmt.fk_facture ,   idStripe , bdpmt.type,  bdpmt.rowid as IdBullDetStripe,
			  MttStripe, b.amount, fac.ref, st.nom, RefAcpt, EtatStripe, dateStripe,  FullTag, bull.ref as RefBull
			FROM `cav_EncStripe` as s
				LEFT JOIN llx_cglinscription_bull_det as bdpmt on  bdpmt.num_cheque = idStripe and type = 1
				LEFT JOIN llx_cglinscription_bull as bull on bull.rowid = bdpmt.fk_bull
				LEFT JOIN llx_bank as b on b.rowid = bdpmt.fk_banque
				LEFT JOIN llx_paiement as pmt on pmt.fk_bank = b.rowid
				LEFT JOIN llx_paiement_facture as facpmt on pmt.rowid = facpmt.fk_paiement
				LEFT JOIN llx_facture as fac on facpmt.fk_facture = fac.rowid
				LEFT JOIN llx_societe as st on fac.fk_soc = st.rowid 
				LEFT JOIN llx_facture as acmpt on IdAcpt = acmpt.rowid 				
			WHERE	 isnull(b.rowid) and FullTag not like  '%CUS%'  and EtatStripe not like '%insufficient_funds%'
		";
		$result = $db->query($sql);
		if (!$result){
				//dol_syslog('verifEncaissementStripe::Lien Stripe-Dol - sql:'.$sql, LOG_ERR);
				dol_syslog('verifEncaissementStripe::Lien Stripe-Dol ', LOG_ERR);
				return NULL;
			}			
	return $result;
} //Lecture_DonneesVir

/*
* transforme une tab au format tab[id] = id en un paramètre d'url
*
* @param$tabarray
*	@retour string   tab[<id>]=<id>&tab[<id1>]=<id1>
*/
	function TransfTabIdUrl($tab, $name)
	{
			$ret = '';
			foreach ($tab as $key => $value)
			{
				if (!empty($ret)) $ret .='&';
				$ret .= $name.'['.$key.']='.$value;				
			}
			return ($ret);
	
	}//TransfTabIdUrl
?>