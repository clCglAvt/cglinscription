<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * CCA 20/9/2014 - Génération automatique des pdf de facture entre deux numéros de factures
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
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Put here some comments
 */


// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once ('./class/cglinscription.class.php');
	
	
// Change this following line to use the correct relative path from htdocs
require_once 'class/pdf.class.php';

// Load traductions files requiredby by page
$langs->load("cglinscription@cglinscription");
// Get parameters
$facdeb		= GETPOST('facdeb','alpha');
$facfin		= GETPOST('facfin','alpha');
$action	= GETPOST('action','alpha');



// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

if ($action == 'generer')
{
/*
	$object=new Skeleton_Class($db);
	$object->prop1=$_POST["field1"];
	$object->prop2=$_POST["field2"];
	$result=$object->create($user);
*/
	/* recherche des factures */

// Example 3 : List of data
    $sql = "SELECT f.rowid ,";
	if ($conf->global->MAIN_VERSION_LAST_UPGRADE <>'8.0.4')
		$sql .= " f.ref as facnumber ";
	else
		$sql .= " f.facnumber ";	
    $sql.= " FROM ".MAIN_DB_PREFIX."facture as f ";

	if ($conf->global->MAIN_VERSION_LAST_UPGRADE <>'8.0.4') {
 		$sql.= " WHERE f.ref between '".$facdeb."' and '".$facfin. "'"; 
		$sql.= " ORDER BY f.ref ASC";
	}
	else {
		$sql.= " WHERE f.facnumber between '".$facdeb."' and '".$facfin. "'"; 
		$sql.= " ORDER BY f.facnumber ASC";
	}	
    
	$files = array();
    dol_syslog("Generation facture par lot -  sql=".$sql, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num)
        {
			// pour chaque facture 
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                if ($obj)
                {
					$objfac = new Facture ($db);
					$retfac = $objfac->fetch($obj->rowid);
					$objfac->fetch_thirdparty();
					$ret = $objfac->fetch_lines();
					
					// gestion de la langue client
					$outputlangs = $langs;
					$newlang='';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'int')) $newlang=GETPOST('lang_id', 'int');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$objfac->client->default_lang;
					if (! empty($newlang))
					{
						$outputlangs = new Translate("",$conf);
						$outputlangs->setDefaultLang($newlang);
					}	

					$result=facture_pdf_create($db, $objfac, $objfac->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
					$file = DOL_DATA_ROOT."/facture/".$obj->facnumber.'/'.$obj->facnumber.".pdf";
					$files[] = $file;
					if ($result <= 0)
					{
						$error++;
						$rapport[$row]->msg.="La facture n'est pas générée. ";
					}
					else					
					unset ($objfac);

                }
                $i++;
            }
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
    }
	if ($result > 0)
	{
		// Creation OK
	}
	{
		// Creation KO
		$mesg=$object->error;
	}
	// generation du pdf global
	//http://garridodiaz.com/wp-content/cache/supercache/neo22s.com/concatenate-pdf-in-php/index.html.gz 
	// voir aussi http://www.miasmatech.net/scripts/accueil/permalink.php?post_id=20 mais non utilise
	$pdf = new concat_pdf();
	$pdf->setFiles($files);
	$pdf->concat();
	//$pdf->Output(DOL_DATA_ROOT.'/facture/'."newpdf.pdf", "I") ;//pour conserver les factures sur le serveur;
	//$pdf->Output("newpdf.pdf", "F") pour conserver les factures sur le serveur;
	$pdf->Output();
	
}


if ($action == 'envoi')
{
/*
	$object=new Skeleton_Class($db);
	$object->prop1=$_POST["field1"];
	$object->prop2=$_POST["field2"];
	$result=$object->create($user);
*/
	if ($result > 0)
	{
		// Creation OK
	}
	{
		// Creation KO
		$mesg=$object->error;
	}
}




/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','Lcglinscription');
$title=$langs->trans("EnvoiFactureComptable");
// permet d'afficher le petit livre, le titre la succession des page et le num? de la  page courante
print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$nbtotalofrecords,$nbtotalofrecords);

$form=new Form($db);
print '<form>';
	print '<input type="hidden" name="action" value="generer">';
	print '<input type="hidden" name="token" value="'.newtoken().'">';

// Put here content of your page
print '<p>';
print '&nbsp';
print '</p><p>';
print $langs->trans('EnvoiFacDeb').' :';
print   '<input class="flat" size="40" type="text" name="facdeb" value="'.$facdeb.'">';
print '&nbsp &nbsp Format : FA1408-0035';
// format FA4408_0025

print '</p><p>';
print '&nbsp';
print '</p><p>';
print $langs->trans(EnvoiFacFin).' :';
print   '<input class="flat" size="40" type="text" name="facfin" value="'.$facfin.'">';
print '</p><p>';
print '&nbsp';
print '</p><p>';
print '</p>';

print '<input class="button" type="submit" value="'.$langs->trans("GenererFacture").'">';
print "</form>\n";


?>