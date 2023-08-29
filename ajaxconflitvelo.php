<?php
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@capnetworks.com>
 *
 * Version CAV - 2.8.5 - printemps 2023
 *		- vérification à la volée des conflit de vélo  (308b)
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
 *       \file       custom/cglinscription/ajaxconflitvelo.php
 *       \brief      Controler les conflit de locations de vélo
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require '../../main.inc.php';
dol_syslog( 'CCA - ajaxconflitvelo - Entrée');
require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/cgllocation.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


$identmat	= GETPOST('refVelo','alpha');
$idservice		= GETPOST('RefMat','int');
$idbulldet		= GETPOST('idbulldet','int');
dol_syslog( 'CCA - ajaxconflitvelo - idbulldet:'.$idbulldet);
/*
 * Controle
 */	
//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";
$wfcom =  new CglLocation($db);	
$ListBullConflit =$wfcom->NbLocationParMateriel($idservice, $identmat, $idbulldet, "","") ;
unset ($wfcom);

echo json_encode($ListBullConflit);

