<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
 * Copyright (C) 2012 Florian Henry  <florian.henry@open-concept.pro>
 *
 * Version CAV - 2.8.3 printemps 2023 - première étape POST_ACTIVITE
 * Version CAV - 2.8.4 - printemps 2023
 *		- PostActivité complété

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
 *		\file       cglinscription/core/modules/cglinscription/modules_cglinscription.php
 *      \ingroup    project
 *      \brief      File that contain parent class for projects models
 *                  and parent class for projects numbering models
*/
require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");


/**
 *  \class      ModelePDFCommandes
 *  \brief      Classe mere des modeles de commandes
*/
//abstract class ModelePDFCglInscription extends CommonDocGenerator
abstract class Modelecglinscription extends CommonDocGenerator
{
	var $error='';

	/**
	 *  Return list of active generation modules
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  string	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf, $bull, $atelier, $langs, $bull;

		//il faudra venir mettre tout cela dans les table document_modele comme facture
		$liste=array();
			if ($bull->type == 'Insc') $liste[]='';
			elseif ($bull->type == 'Loc') {
				$liste['location']= $langs->trans('location');
				$temp=$langs->trans('CntVelo1');
				if ( $temp <> '' and  $langs->trans('CntVelo1') <>  'CntVelo1') $liste[$temp]= $langs->trans('CntVelo1');
				$temp=$langs->trans('CntVelo2');
				if ($temp <> '' and $langs->trans('CntVelo2') <>  'CntVelo2') $liste[]= $langs->trans('CntVelo2');
				$temp=$langs->trans('CntVelo3');
				if ( $temp <> '' and $langs->trans('CntVelo3') <>  'CntVelo3') $liste[]= $langs->trans('CntVelo3');
				}
			elseif ($bull->type == 'Resa') {
				$liste['canoe']= $langs->trans('canoe');
				$liste['ane']= $langs->trans('ane');
				$liste['acrobranche']= $langs->trans('acrobranche');
			}
		return $liste;
	}
}
abstract class ModelePDFCglInscription extends CommonDocGenerator
{
	var $error='';

	/**
	 *  Return list of active generation modules
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  string	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf, $bull, $atelier, $langs, $bull;

		if (isset ($atelier)) {
			$liste['Atelier']= $langs->trans('atelier');			
		}
		return $liste;
	} //liste_modeles
} // class ModelePDFCglInscription

/**
 *  Classe mere des modeles de numerotation des references de cglinscription
 */
abstract class ModeleNumRefCglInscription
{
	var $error='';

	/**
	 *  Return if a module can be used or not
	 *
	 *  @return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 *  Renvoi la description par defaut du modele de numerotation
	 *
	 *  @return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("cglinscription@cglinscription");
		return $langs->trans("CglNoDescription");
	}

	/**
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("cglinscription@cglinscription");
		return $langs->trans("AgfNoExample");
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**
	 *  Renvoi prochaine valeur attribuee
	 *
	 *	@param	Societe		$objsoc		Object third party
	 *	@param	Project		$project	Object project
	 *	@return	string					Valeur
	 */
	function getNextValue($objsoc, $project)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *  Renvoi version du module numerotation
	 *
	 *  @return     string      Valeur
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}

/**
 *	\brief   	Crée un document PDF pour le bulletin / Clone de Agefodd non travaillé
 *	\param   	db  			objet base de donnee
 *  \param   	id	  			can be object or rowid
 *	\param   	modele  		modele à utiliser
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return  	int        		<0 if KO, >0 if OK
 */
function cgl_bull_create($db, $idSes,  $modele, $outputlangs, $file, $socid, $courrier='')
{		
	global $conf,$user,$langs,$bull;
	$langs->load('cglinscription@cglinscription');
	$langs->load('bills');
	
	$langs->load("orders");

	$error=0;

	$srctemplatepath='';

	// Positionne le modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
	    if (! empty($conf->global->COMMANDE_ADDON_PDF))
	    {
	        $modele = $conf->global->COMMANDE_ADDON_PDF;
	    }
	    else
	    {
	        $modele = 'bulletin_odt';
	    }
	}

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }
	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array('/');
	
	if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels,$conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
    	foreach(array('doc','pdf') as $prefix)
    	{
    	    $file = $prefix."_".$modele.".modules.php";

    		// On verifie l'emplacement du modele
	        $file=dol_buildpath($reldir."core/modules/cglinscription/doc/".$file,0);
    		if (file_exists($file))
    		{
    			$filefound=1;
    			$classname=$prefix.'_'.$modele;
    			break;
    		}
    	}
    	if ($filefound) break;
    }
	// Charge le modele
	if ($filefound)
	{
		require_once $file;

		$obj = new $classname($db);
		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		//write_file($object, $idSes, $outputlangs,$srctemplatepath,$hidedetails=0,$hidedesc=0,$hideref=0)
//		if ($obj->write_file($bull, $idSes, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0)
		if ($obj->write_file($bull, $idSes, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($bull);

			// Success in building document. We build meta file.
			dol_meta_create($bull);
			
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"cgl_bull_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
	}
}//cgl_bull_create
/**
 *	\brief   	Crée un document PDF pour la feuille de route
 *	\param   	db  			objet base de donnee
 *  \param   	id	  			can be object or rowid
 *	\param   	modele  		modele à utiliser
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return  	int        		<0 if KO, >0 if OK
 */
function cgl_feuille_create($db, $idSes,  $modele, $outputlangs, $file, $feuille)
{		
	global $conf,$user,$langs;
	$langs->load('cglinscription@cglinscription');
	$langs->load('bills');
	
	$langs->load("orders");

	$error=0;

	$srctemplatepath='';

	// Positionne le modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
	    if (! empty($conf->global->COMMANDE_ADDON_PDF))
	    {
	        $modele = $conf->global->COMMANDE_ADDON_PDF;
	    }
	    else
	    {
	        $modele = 'feuille_odt';
	    }
	}

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array('/');
	
	if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels,$conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
    	foreach(array('doc','pdf') as $prefix)
    	{
    	    $file = $prefix."_".$modele.".modules.php";

    		// On verifie l'emplacement du modele
	        $file=dol_buildpath($reldir."core/modules/cglinscription/doc/".$file,0);
    		if (file_exists($file))
    		{
    			$filefound=1;
    			$classname=$prefix.'_'.$modele;
    			break;
    		}
    	}
    	if ($filefound) break;
    }

	// Charge le modele
	if ($filefound)
	{
		require_once $file;

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($feuille, $idSes, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($feuille);

			// Success in building document. We build meta file.
			$ret = dol_meta_create($feuille);
		
			return 1;
		}
		else
		{	
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"cgl_feuille_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
	}
}//cgl_feuille_create

/**
 *	\brief   	Crée un document doc pour le rapport de facturation
 *	\param   	db  			objet base de donnee
 *  \param   	id	  			can be object or rowid
 *	\param   	modele  		modele à utiliser
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return  	int        		<0 if KO, >0 if OK
 */
function cgl_RapFact_create($db,  $modele, $outputlangs,  $rapport, $PostActivité)
{		
	global $conf,$user,$langs;
	$langs->load('cglinscription@cglinscription');
	$langs->load('bills');
	
	
	$langs->load("orders");

	$error=0;
	$srctemplatepath='';
	// Positionne le modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
	    if (! empty($conf->global->COMMANDE_ADDON_PDF))
	    {
	        $modele = $conf->global->COMMANDE_ADDON_PDF;
	    }
	    else
	    {
	        $modele = 'rapport_odt';
	    }
	}

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array('/');
	
	if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels,$conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
    	foreach(array('doc','pdf') as $prefix)
    	{
    	    $file = $prefix."_".$modele.".modules.php";

    		// On verifie l'emplacement du modele
	        $file=dol_buildpath($reldir."core/modules/cglinscription/doc/".$file,0);
    		if (file_exists($file))
    		{
    			$filefound=1;
    			$classname=$prefix.'_'.$modele;
    			break;
    		}
    	}
    	if ($filefound) break;
    }
	
	// Charge le modele
	if ($filefound)
	{
		require_once $file;

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
	
		if ($obj->write_file($rapport,	 $srctemplatepath, $PostActivité)> 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			//dol_delete_preview($rapport);

			// Success in building document. We build meta file.
			dol_meta_create($rapport);
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"cgl_RapFact_create Error: ".$obj->error);
			return -1;
		}	
	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
	}
}//cgl_RapFact_create

/**
 *	\brief   	Crée un document PDF pour le contrat d elocation 
 *	\param   	db  			objet base de donnee
 *  \param   	id	  			can be object or rowid
 *	\param   	modele  		modele à utiliser
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return  	int        		<0 if KO, >0 if OK
 */
function cgl_cnt_create($db,   $modele, $outputlangs, $file, $socid, $courrier='')
{		
	global $conf,$user,$langs,$bull;
	$langs->load('cglinscription@cglinscription');
	$langs->load('bills');
	$langs->load("orders");

	$error=0;

	$srctemplatepath='';

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array('/');
	
	if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels,$conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
    	foreach(array('doc','pdf') as $prefix)
    	{
    	    $file = $prefix."_".$modele.".modules.php";

    		// On verifie l'emplacement du modele
	        $file=dol_buildpath($reldir."core/modules/cglinscription/doc/".$file,0);
    		if (file_exists($file))
    		{
    			$filefound=1;
    			$classname=$prefix.'_'.$modele;
    			break;
    		}
    	}
    	if ($filefound) break;
    }
	
	// Charge le modele
	if ($filefound)
	{
		require_once $file;
	    if ( ! class_exists($classname)  ) require_once DOL_DOCUMENT_ROOT.'/core/modules/cglinscription/doc/doc_location_odt.modules.php';
		
		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		$ret =$obj->write_file($bull, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref);

		if ( $ret > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($bull);

			// Success in building document. We build meta file.
			dol_meta_create($bull);			
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"cgl_cnt_create Error: ".$obj->error.'retour:'.$ret.'--- path du fichier :'.$srctemplatepath);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
	}
}//cgl_cnt_create

/**
 *	\brief   	Crée un document PDF pour la fiche d'atelier
 *	\param   	db  			objet base de donnee
 *  \param   	id	  			can be object or rowid
 *	\param   	modele  		modele à utiliser
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return  	int        		<0 if KO, >0 if OK
 */
function cgl_atelier_create($db,   $modele, $outputlangs, $file,  $courrier='')
{
	
	global $conf,$user,$langs,$atelier;
	
	$langs->load('cglinscription@cglinscription');
	$error=0;

	$srctemplatepath='';

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array('/');
	
	if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels,$conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
    	foreach(array('doc','pdf') as $prefix)
    	{
    	    $file = $prefix."_".$modele.".modules.php";

    		// On verifie l'emplacement du modele
	        $file=dol_buildpath($reldir."core/modules/cglinscription/doc/".$file,0);
    		if (file_exists($file))    		{
    			$filefound=1;
    			$classname=$prefix.'_'.$modele;
    			break;
    		}
    	}
    	if ($filefound) break;
    }
	
	// Charge le modele
	if ($filefound)
	{
		require_once $file;

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		$ret =$obj->write_file($atelier, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref);
		if ( $ret > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($bull);

			// Success in building document. We build meta file.
			dol_meta_create($bull);
			
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"cgl_atelier_create Error: ".$obj->error.'retour:'.$ret.'--- path du fichier :'.$srctemplatepath);

			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
	}
}//cgl_atelier_create

/**
 *	\brief   	Crée un document PDF pour la ballade en ane
 *	\param   	db  			objet base de donnee
 *  \param   	id	  			can be object or rowid
 *	\param   	modele  		modele à utiliser
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return  	int        		<0 if KO, >0 if OK
 */
function cgl_reservation_create($db,   $modele, $outputlangs, $file, $socid, $courrier='')
{
	
	global $conf,$user,$langs,$bull;
	$langs->load('cglinscription@cglinscription');
	$error=0;

	$srctemplatepath='';

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$file = "doc_".$modele.".module.php";
	// On verifie l'emplacement du modele
	$file=dol_buildpath("/custom/cglinscription/core/modules/cglinscription/doc/".$file,0);
	if (file_exists($file))    		{
		$filefound=1;
		$classname='doc_'.$modele;		
	}


	// Charge le modele
	if ($filefound)
	{
		require_once $file;

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		$ret =$obj->write_file($bull, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref);
		if ( $ret > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($bull);

			// Success in building document. We build meta file.
			dol_meta_create($bull);
			
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"cgl_".$modele."_create Error: ".$obj->error.'retour:'.$ret.'--- path du fichier :'.$srctemplatepath);

			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
	}
}//cgl_reservation_create

/**
* Header empty
*
* @return	void
*/

//function llxFooter() { }

/*
* param 	$urlsource	// Do not use urldecode here ($_GET are already decoded by PHP).
*
*/
function affichefichier( $original_file, $modulepart,  $typeparam = '')
{
	global $conf, $langs;

	define('NOTOKENRENEWAL',1); // Disables token renewal
	// Pour autre que bittorrent, on charge environnement + info issus de logon (comme le user)
	if (isset($modulepart) && $modulepart == 'bittorrent' && ! defined("NOLOGIN"))
	{
		define("NOLOGIN",1);
		define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
	}
	if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
	if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
	if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');
	//require 'main.inc.php';	// Load $user and permissions
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$encoding = '';

	// Security check
	if (empty($modulepart)) accessforbidden('Bad value for parameter modulepart');


	/*
	 * View
	 */

	// Define mime type
	$type = 'application/octet-stream';
	if ($typeparam) $type=$typeparam;
	else $type=dol_mimetype($original_file);

	// Define attachment (attachment=true to force choice popup 'open'/'save as')
	$attachment = true;
	if (preg_match('/\.(html|htm)$/i',$original_file)) $attachment = false;
	if (isset($_GET["attachment"])) $attachment = GETPOST("attachment")?true:false;
	if (! empty($conf->global->MAIN_DISABLE_FORCE_SAVEAS)) $attachment=false;

	// Suppression de la chaine de caractere ../ dans $original_file
	$original_file = str_replace("../","/", $original_file);

	// Find the subdirectory name as the reference
	$refname=basename(dirname($original_file)."/");

	// Security check
	if (empty($modulepart)) accessforbidden('Bad value for parameter modulepart');
	$check_access = dol_check_secure_access_document($modulepart,$original_file,$conf->entity);
	$accessallowed              = $check_access['accessallowed'];
	$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
	$original_file              = $check_access['original_file'];

	// Basic protection (against external users only)
	if ($user->societe_id > 0)
	{
		if ($sqlprotectagainstexternals)
		{
			$resql = $this->db->query($sqlprotectagainstexternals);
			if ($resql)
			{
				$num=$this->db->num_rows($resql);
				$i=0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($user->societe_id != $obj->fk_soc)
					{
						$accessallowed=0;
						break;
					}
					$i++;
				}
			}
		}
	}

	// Security:
	// Limite acces si droits non corrects
	if (! $accessallowed)
	{
		accessforbidden();
	}

	// Security:
	// On interdit les remontees de repertoire ainsi que les pipe dans
	// les noms de fichiers.
	if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
	{
		dol_syslog("Refused to deliver file ".$original_file);
		$file=basename($original_file);		// Do no show plain path of original_file in shown error message
		dol_print_error(0,$langs->trans("ErrorFileNameInvalid",$file));
		exit;
	}


	clearstatcache();

	$filename = basename($original_file);

	// Output file on browser
	dol_syslog("document.php download $original_file $filename content-type=$type");
	$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

	// This test if file exists should be useless. We keep it to find bug more easily
	if (! file_exists($original_file_osencoded))
	{
		dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file));
		exit;
	}

	// Les drois sont ok et fichier trouve, on l'envoie

	header('Content-Description: File Transfer');
	if ($encoding)   header('Content-Encoding: '.$encoding);
	if ($type)       header('Content-Type: '.$type.(preg_match('/text/',$type)?'; charset="'.$conf->file->character_set_client:''));
	// Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, atachment=need user action to open)
	if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
	else header('Content-Disposition: inline; filename="'.$filename.'"');
	header('Content-Length: ' . dol_filesize($original_file));
	// Ajout directives pour resoudre bug IE
	header('Cache-Control: Public, must-revalidate');
	header('Pragma: public');

	//ob_clean();
	//flush();

	readfile($original_file_osencoded);
} //affichefichier

?>