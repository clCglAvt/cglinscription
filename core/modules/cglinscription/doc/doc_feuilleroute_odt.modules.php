<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<ely@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 *
 * 
 * CAV Version 2.8 - hiver 2023 - Ajout d'une variable depart_note dans la feuille de route
 * CAV Version 2.8.4 - printemps 2023 - bug 312
 * Version CAV - 2.8.5 - printemps 2023
 *			- absence des bulletin d'un départ si celui-ci n'a pas de moniteur (bug 325)

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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
//class doc_generic_bulletin_odt extends ModelePDFFactures
class doc_feuilleroute_odt 
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
/*
		$langs->load("main");
		$langs->load("companies");

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'FACTURE_ADDON_PDF_ODT_PATH';	// Name of constant that is used to save list of directories to scan
*/
		// Dimension page pour format A4
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=0;
		$this->marge_droite=0;
		$this->marge_haute=0;
		$this->marge_basse=0;
/*
		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 0;                 // Affiche mode reglement
		$this->option_condreg = 0;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 0;               // Dispo en plusieurs langues
		$this->option_escompte = 0;                // Affiche si il y a eu escompte
		$this->option_credit_note = 0;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 0;		   // Support add of a watermark on drafts

		// Recupere emetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini
*/
	}


	/**
	 * Define array with couple substitution key => substitution value
	 *
	 * @param   Object			$object             Main object to use as data source
	 * @param   Translate		$outputlangs        Lang object to use for output
	 * @return	array								Array of substitution
	 */
	function get_substitutionarray_object($feuille, $idSes, $outputlangs, $array_key = "")
	{
		global $conf;
			$duree = 	price2num($feuille->duree);
		$resarray=array(
			'depart_label'=>$feuille->activite_label,
			'depart_date'=>$feuille->activite_dated,
			'depart_lieu'=>$feuille->activite_lieu,
			//'depart_lieu'=>dol_print_date($object->date,'day'),
			'depart_heured'=>$feuille->activite_heured,
			'depart_heuref'=>$feuille->activite_heuref,
			'depart_monit_1'=>$feuille->moniteur1,
			'depart_monit_2'=>$feuille->moniteur2,
			'depart_monit_3'=>$feuille->moniteur3,
			'depart_plc_ins'=>$feuille->activite_nbplcIns,
			'depart_plc_pins'=>$feuille->activite_nbplcPins,
			'depart_plc_nb'=>$feuille->activite_nbmax,			
			//'bull_nom_pers'=>($outputlangs->transnoentitiesnoconv('PaymentType'.$object->mode_reglement_code)!='PaymentType'.$object->mode_reglement_code?$outputlangs->transnoentitiesnoconv('PaymentType'.$object->mode_reglement_code):$object->mode_reglement),
			'depart_rdv_princ'=>$feuille->lib_rdvPrinc,
			'depart_note'=>$feuille->notes,
			'depart_nb_rdv1'=>$feuille->depart_nb_rdv1 ,
			'depart_etq_personnes1'=>$feuille->depart_etq_personnes1 ,			
			'depart_label_RDV2'=>$feuille->depart_label_RDV2,
			'depart_rdv_sec'=>$feuille->lib_rdvSec,
			'depart_etq_pour'=>$feuille->depart_etq_pour,
			'depart_nb_rdv2'=>$feuille->depart_nb_rdv2 ,			
			'depart_etq_personnes2'=>$feuille->depart_etq_personnes2,
			'depart_puadl'=>$feuille->depart_puadl,
			'depart_puenf'=>$feuille->depart_puenf,
			'lib_grp'=>$feuille->lib_grp,
			'depart_pugrp'=>$feuille->depart_pugrp,
			'lib_enf'=>$feuille->lib_enf,
			'lib_euro'=>$feuille->lib_euro,
			'lib_adl'=>$feuille->lib_adl	,
			'duree'=>$duree
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
		return array(
		'line_inscrit'=>$line->inscrit,
		'line_nom'=>$line->NomPrenom,
		'line_tiers'=>$line->TiersNom,
		'line_prenom'=>$line->NomPrenom,
		'line_tel'=>$line->PartTel,
		'line_age'=>$line->PartAge,
		'line_poids'=>$line->PartPoids,
		'line_taille'=>$line->PartTaille,
		'line_RDV'=>$line->ActPartRdv,
		'line_contact'=>$line->pers_nom,
		'line_pay'=>price2num($line->pay),
		//'line_solderestant'=>$line->solderestant,
		'line_pt'=>price2num($line->pt),
		'line_rem'=>price2num($line->rem),
//		,
		'line_obs'=>$line->observation,
		//'line_pt'=>price2num($line->pu,'MU'),
//		'line_rem'=>$line->rem
		'line_bull'=>$line->bullref,
		'line_ActionFuture' => $line->ActionFuture,
		'line_PmtFutur' => $line->PmtFutur
		);
	}//get_substitutionarray_lines

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
	 *	@param		Bulletin	$object				Object source to build document
	 *	@param		int			$idSes				Identifiant de l'activité du bulletin
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	function write_file($object, $idSes, $outputlangs,$srctemplatepath,$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc;

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
		if ($conf->cglinscription->dir_output)
		{
			$dir = $conf->cglinscription->dir_output;
			/* enlever les / et ' et "" du nom de la référence pour avoir un nom de fichier */
			$objectref = dol_sanitizeFileName($object->ref);
			if ( preg_match('/feuilleroute/i',$srctemplatepath)) 
			{
				$dirrel = 'feuilleroute';
				$dir.= "/" . 'feuilleroute';
			}
			elseif (! preg_match('/bulletin/i',$srctemplatepath)) 
			{		
				$dirrel = 'bulletin';
				$dir.= "/" . 'bulletin';
			}
			if (! preg_match('/specimen/i',$objectref))$dir.= "/" . $objectref;	
			$file = $dir . "/" . $objectref .".odt";

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return -1;
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
					$filename=$newfiletmp.'.'.dol_print_date(dol_now('tzuser'),'%Y%m%d%H%M%S').'.'.$newfileformat;
				}
				else
				{
					$filename=$newfiletmp.'.'.$newfileformat;
				}

				$file=$dir.'/'.$filename;
				//$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now('tzuser'),'%Y%m%d%H%M%S').'.odt';
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;

				dol_mkdir($conf->cglinscription->dir_temp);

				complete_substitutions_array($substitutionarray, $langs, $object);

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				$odfHandler = new odf(
					$srctemplatepath,
					array(
					'PATH_TO_TMP'	  => $conf->cglinscription->dir_temp,
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
				//$array_soc=$this->get_substitutionarray_mysoc($mysoc,$outputlangs);
				//$array_thirdparty=$this->get_substitutionarray_thirdparty($socobject,$outputlangs);
				$array_objet=$this->get_substitutionarray_object($object,$idSes, $outputlangs);
				//$array_propal=is_object($propal_object)?$this->get_substitutionarray_propal($propal_object,$outputlangs,'propal'):array();
				//$array_other=$this->get_substitutionarray_other($user,$outputlangs);

				$tmparray = array_merge($array_objet);
				complete_substitutions_array($tmparray1, $outputlangs, $object);
				$tmparray = array_merge($array_objet,$tmparray1);
				//var_dump($tmparray); exit;
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
				// Replace tags of lines
				try
				{
					$listlines = $odfHandler->setSegment('lines');
					foreach ($object->lines as $line)
					{
						//if ($line->id_act == $idSes)
						//{
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
						//}
					}
					$odfHandler->mergeSegment($listlines);
				}
				catch(OdfException $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -1;
				}

				// Write new file
/* Mis en commentaire tant que conversion PDF non resolue
				if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
					try 
					{
						$odfHandler->exportAsAttachedPDF($file);
					}
					catch (Exception $e)
					{
						$this->error=$e->getMessage();
						return -1;
					}
				}
				else {
					try {
					$odfHandler->saveToDisk($file);
					}catch (Exception $e){
						$this->error=$e->getMessage();
						return -1;
					}
				}	
*/			
				try {
					$odfHandler->saveToDisk($file);
					}catch (Exception $e){
						$this->error=$e->getMessage();
						return -1;
				}

				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$odfHandler=null;	// Destroy object
				// stocker le nom du fichier en base
				$object->ficsess = $file;
				$object->updateFic($file);
				return 1;   // Success
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return -1;
			}
		}
	

		return -1;
	}//write_file


}

?>