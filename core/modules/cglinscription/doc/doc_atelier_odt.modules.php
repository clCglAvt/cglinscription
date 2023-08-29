<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<ely@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>

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
 *	\file       htdocs/custum/cglinscription/core/modules/cglinscription/doc/doc_generic_invoice_odt.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build ODT documents for third parties
 */

require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/core/modules/cglinscription/modules_cglinscription.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';
require_once '../cglavt/class/cglFctCommune.class.php';


/**
 *	Class to build documents using ODF templates generator
 */
//class doc_generic_lst_atelieretin_odt extends ModelePDFFactures
class doc_atelier_odt extends CommonDocGenerator
{
	var $emetteur;	// Objet societe qui emet

	var $phpmin = array(5,2,0);	// Minimum version of PHP required by module
	var $version = 'dolibarr';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;
		// Dimension page pour format A4
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=0;
		$this->marge_droite=0;
		$this->marge_haute=0;
		$this->marge_basse=0;
	}


	/**
	 * Define array with couple substitution key => substitution value
	 *
	 * @param   Object			$object             Main object to use as data source
	 * @param   Translate		$outputlangs        Lang object to use for output
	 * @return	array								Array of substitution
	 */
	function get_substitutionarray_object($lst_atelier, $outputlangs, $array_key = "")
	{
		global $conf, $langs, $search_Date;
		
		
		$resarray=array(
				'loc_date' => $search_Date
				);		
		return $resarray;
	}

	/**
	 * Define array with couple substitution key => substitution value
	 *
	 * @param   array		$line			Array of lines
	 * @param   Translate	$outputlangs    Lang object to use for output
	 * @return	array						Return substitution array
	 */
	function get_substitutionarray_lines($line,$outputlangs, $line_number = 0)
	{
		
		if (!empty($line->duree)) {
			if ( $line->duree <1) $duree = ' 1/2 j';
			else $duree = substr($line->duree, 0, strlen ($duree = $line->duree)-3) . ' j';
		}
		
		global $anc_client, $anc_date;
		if (empty($anc_client)) {
				 $anc_client = $line->Nom; 
				 $anc_date =$heure; 
		}
		elseif ($anc_client <> $line->Nom or $anc_date <> $heure) {
				 $anc_client = $line->Nom; 
				  $anc_date =$line->heure; 
		}
		else {
				$line->Nom = '';
				 $line->heure = '';		
		}		
		
		$resarray=array(
		'line_client'=>$line->nom,
		'line_heureret' => $line->heure,
		'line_ddep' => $line->datedepose,
		'line_dret' => $line->dateretrait,
		'line_duree' => $duree,
		'line_mat' => $line->materiel,
		'line_marq' => $line->marque,
		'line_ref' => $line->refmat,
		'line_tail' => $line->taille,
		'line_fourn' => $line->fournisseur,
		'line_nom' => $line->NomPrenom,
		'line_ldep' => $line->lieudepose,
		'line_lret' => $line->lieuretrait,
		'line_obs' => $line->observation		
		);
		return $resarray;
		
	} //get_substitutionarray_lines
	/**
	 * Return description of a module (voir facture)
	 *
	 * @param	Translate	$langs      Lang object to use for output
	 * @return	string      			Description
	 */
	function info($langs)
	{
	}

	/**
	 *	Function to build a document on disk using the generic odt module.
	 *
	 *	@param		lst_atelieretin	$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	function write_file($object, $outputlangs,$srctemplatepath,$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc, $lst_atelier, $search_Date;
		
		if (empty($srctemplatepath))
		{
			dol_syslog("doc_generic_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}
		if (! is_object($outputlangs)) $outputlangs=$langs;
		$sav_charset_output=$outputlangs->charset_output;
		$outputlangs->charset_output='UTF-8';

		$outputlangs->load("main");
		$outputlangs->load("dict");

		//if ($conf->cgllocation->dir_output)
		//{
	
			$dirgen = $conf->cglinscription->dir_output;
			
			$wf = new CglFonctionCommune($db);
			$wdate = $wf->transfDateMysql($search_Date);
			unset ($wf);
		
			$objectref = $wdate;
			// rajouter  $dir/contratLoc trouver dans srctemplatepath lst_atelieretin ou  feuilleroute
			if ( preg_match('/atelier/i',$srctemplatepath)) 
			{
				$dirrel = 'atelier';
				$dir= $dirgen."/".'atelier';
			}			
			if (! preg_match('/specimen/i',$objectref))$dir.= "/" . $objectref;	
			//$file = $dir . "/" . $objectref .".odt";
			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return -2;
				}
			}

			if (file_exists($dir))
			{
				//print "srctemplatepath=".$srctemplatepath;	// Src filename
				$newfile=basename($srctemplatepath);
				$newfiletmp=preg_replace('/\.od(t|s)/i','',$newfile);
				$newfiletmp=preg_replace('/template_/i','',$newfiletmp);
				$newfiletmp=preg_replace('/modele_/i','',$newfiletmp);
	
				$newfiletmp=$objectref.'_'.$newfiletmp;
				// Get extension (ods or odt)
	
	$newfileformat=substr($newfile, strrpos($newfile, '.')+1);
				if ( ! empty($conf->global->MAIN_DOC_USE_TIMING))
	
{
					$filename=$newfiletmp.'.'.dol_print_date(dol_now('tzuser'),'%Y%m%d%H%M%S')."_".'.'.$newfileformat;
				}
				else
				{
					//$filename=$newfiletmp."_".'.'.$newfileformat;
					$filename='atelier-'.$wdate.'.odt';
				}			
				 //
				$file=$dirgen.'/atelier/'.$objectref.'/'.$filename;
				//$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now('tzuser'),'%Y%m%d%H%M%S').'.odt';
				//print "<p>file=".$file.'</p>';;
				//print "newfile=".$newfile;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;

				//dol_mkdir($conf->cgllocation->dir_temp);
				dol_mkdir($dirgen.'/temp');

/*
				// Make substitution
				$substitutionarray=array(
				'__FROM_NAME__' => $this->emetteur->nom,
				'__FROM_EMAIL__' => $this->emetteur->email,
				'__TOTAL_TTC__' => $object->total_ttc,
				'__TOTAL_HT__' => $object->total_ht,
				'__TOTAL_VAT__' => $object->total_tva
				);
	*/
				complete_substitutions_array($substitutionarray, $langs, $object);

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				$odfHandler = new odf(
					$srctemplatepath,
					array(
					'PATH_TO_TMP'	  => $dirgen.'/temp',
					'ZIP_PROXY'		  => 'PclZipProxy',	// PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
					'DELIMITER_LEFT'  => '{',
					'DELIMITER_RIGHT' => '}'
					)
				);
				// After construction $odfHandler->contentXml contains content and
				// [!-- BEGIN row.lines --]*[!-- END row.lines --] has been replaced by
				// [!-- BEGIN lines --]*[!-- END lines --]
				//print html_entity_decode($odfHandler->__toString());
				//print exit;

				// Make substitutions into odt of freetext
				try {
					$odfHandler->setVars('free_text', $newfreetext, true, 'UTF-8');
				}
				catch(OdfException $e)
				{
				}

				// Make substitutions into odt of user info
				//$array_user=$this->get_substitutionarray_user($user,$outputlangs);
				$array_soc=$this->get_substitutionarray_mysoc($mysoc,$outputlangs);
				//$array_thirdparty=$this->get_substitutionarray_thirdparty($socobject,$outputlangs);
				$array_objet=$this->get_substitutionarray_object($object, $outputlangs);

				$tmparray = array_merge($array_objet, $array_soc);
				complete_substitutions_array($tmparray1, $outputlangs, $object);
				$tmparray = array_merge($tmparray,$tmparray1);
				foreach($tmparray as $key=>$value)
				{
					try {
						if (preg_match('/logo$/',$key)) // Image
						{
							//var_dump($value);exit;
							if (file_exists($value)) $odfHandler->setImage($key, $value);
							else $odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
						}
						else    // Text
						{
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					}
					catch(OdfException $e)
					{
					}
				}				
		
				// Replace tags of lines location
				try
				{
					$listlines = $odfHandler->setSegment('lines');
					foreach ($object->lines as $line)
					{
							$tmparray=$this->get_substitutionarray_lines($line,$outputlangs);
							complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
							foreach($tmparray as $key => $val)
							{
								try
								{
									$listlines->setVars($key, $val, true, 'UTF-8');
								}
								catch(OdfException $e)
								{
								}
								catch(SegmentException $e)
								{
								}
							}
							$listlines->merge();
					}
					$odfHandler->mergeSegment($listlines);
					
				}
				catch(OdfException $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -3;
				}

				// Write new file
///* mis en commentaire tant dque conversion PDF non resolu			
/*				if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
					try 
					{
						$odfHandler->exportAsAttachedPDF($file);
					}
					catch (Exception $e)
					{
						$this->error=$e->getMessage();
						return -4;
					}
				}
				else 				{
					try {
					$odfHandler->saveToDisk($file);
					}
					catch (Exception $e){
						$this->error=$e->getMessage();
						return -5;
					}
				}	
*/

				try {
					$odfHandler->saveToDisk($file);
					}
					catch (Exception $e){
						$this->error=$e->getMessage();
						return -5;
				}
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$odfHandler=null;	// Destroy object
			
				return 1;   // Success
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return -6;
			}
		//}
		//return -9;
	}//write_file


}

?>