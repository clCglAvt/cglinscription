<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<ely@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 * 
 * Version CAV - 2.8.4 - printemps 2023
 *		- PostActivité 
 
 * Version CAV - 2.8.3 printemps 2023 - première étape POST_ACTIVITE
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


/**
 *	Class to build documents using ODF templates generator
 */
class doc_RapportFacturation_odt extends CommonDocGenerator
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
	function get_substitutionarray_object($object,$idSes, $outputlangs = '',$array_key = '')
	{
		global $conf;
/*
		$bulletin_source=new Bulletin($this->db);
		if ($object->fk_bulletin_source > 0)
		{
			//$bulletin_source->fetch($object->fk_bulletin_source);
			$bulletin_source->fetch_complet_filtre(0, $object->fk_bulletin_source);
		}
		$sumpayed = $object->getSommePaiement();
		$alreadypayed=price($sumpayed,0,$outputlangs);
*/	
		
		$resarray=array(
			'date_facturation'=>dol_now('tzuser')
		);
		return $resarray;
	}

	/**
	 * Define array with couple substitution key => substitution value - pour POST_ACTIVITE
	 *
	 * @param   array		$rapportligne			Array of lines
	 * @param   Translate	$outputlangs    Lang object to use for output
	 * @return	array						Return substitution array
	 */
	function get_substitutionarray_posactivite($rapportdepart,$outputlangs)
	{
		$wfcom  = new CglFonctionCommune ($db);
		
		
		$date_fr = $wfcom->transfDateFr($rapportdepart->datedeb);
		$jourSem = $wfcom->transfDateJourSem($date_fr);	
		$moisFr = $wfcom->transfDateMoisFr($date_fr);					
		if (!empty($rapportdepart->moniteur))
			$datedebRapport = $jourSem.' '.substr($wfcom->transfDateFrCourt($rapportdepart->datedeb),0 ,2) .' '.$moisFr;
		else $datedebRapport = "";					
		if (!empty($rapportdepart->moniteur))
			$datedeb = $wfcom->transfHeureFr($rapportdepart->heuredeb);
		else $datedeb = "";					

		if (empty($rapportdepart->TiersMail)) $rapportdepart->TiersMail = $rapportmail->TiersMail2;
		return array(
		'mail_tiers'=>$rapportdepart->tiersNom,
		'mail_email'=>$rapportdepart->TiersMail,
		'mail_moniteur'=>$rapportdepart->moniteur,	
		'mail_datedeb'=>$datedebRapport,
		'mail_heuredeb'=>$datedeb
		);
	}

	/**
	 * Define array with couple substitution key => substitution value
	 *
	 * @param   array		$rapportligne			Array of lines
	 * @param   Translate	$outputlangs    Lang object to use for output
	 * @return	array						Return substitution array
	 */
	function get_substitutionarray_lines($rapportligne,$outputlangs,$linenumber = 0)
	{
		return array(
		'line_Bulletin'=>$rapportligne->ref,
		'line_Facture'=>$rapportligne->facture,
		'line_Etat'=>$rapportligne->etat,
		'line_Note'=>$rapportligne->note,
		'line_Erreur'=>$rapportligne->msg
		);
	}

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
	 *	@param		array		$rapport			Tableau source to build document
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	function write_file($rapport, $srctemplatepath, $PostActivité = '')
	{
			global $type;
		global $user,$langs,$conf,$mysoc, $bull;
		/*foreach ($rapport as $one) {
			$type=$one->type;
			break;
		}	
		*/	
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
			$rapportref = dol_sanitizeFileName($rapport->ref);
			// rajouter  $dir/bulletin comme on aura $dir/FeuilleRoute trouver dans srctemplatepath bulletin ou  feuilleroute
			$dirrel = 'RapportFacturation';
			$dir.= "/" . 'RapportFacturation';
			$file = $dir . "/" . $type.$rapportref .".odt";
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
	
				$newfiletmp=$rapportref.'_'.$newfiletmp;
				// Get extension (ods or odt)
				$newfileformat=substr($newfile, strrpos($newfile, '.')+1);
				$filename=$type.$newfiletmp.'.'.dol_print_date(dol_now('tzuser'),'%Y%m%d%H%M%S').".".$newfileformat;
				
				 //
				$file=$dir.'/'.$filename;
				//$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now('tzuser'),'%Y%m%d%H%M%S').'.odt';
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;

				dol_mkdir($conf->cglinscription->dir_temp);


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
				complete_substitutions_array($substitutionarray, $langs, $rapport);

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
				$array_soc=$this->get_substitutionarray_mysoc($mysoc,$outputlangs);
				//$array_thirdparty=$this->get_substitutionarray_thirdparty($socobject,$outputlangs);
				$array_objet=$this->get_substitutionarray_object($object,$idSes, $outputlangs);
				
				//$array_propal=is_object($propal_object)?$this->get_substitutionarray_propal($propal_object,$outputlangs,'propal'):array();
				//$array_other=$this->get_substitutionarray_other($user,$outputlangs);
				$tmparray1 = array();
				$tmparray = array_merge($array_objet, $array_soc);
				complete_substitutions_array($tmparray1, $outputlangs, $rapport);
				$tmparray = array_merge($tmparray,$tmparray1);

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
					foreach ($rapport as $line)
					{
//						if ($line->id_act == $idSes and !empty($line->msg))
						if ($line->id_act == $idSes)
						{
							$tmparray=$this->get_substitutionarray_lines($line,$outputlangs);
							complete_substitutions_array($tmparray, $outputlangs, $rapport, $line, "completesubstitutionarray_lines");
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
					}
					$odfHandler->mergeSegment($listlines);
				}
				catch(OdfException $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -1;
				}

				// Replace tags of lines mail de tiers
				try
				{
					asort($PostActivité);
					$listmails = $odfHandler->setSegment('mails');
					if (!empty($PostActivité)) {
						// tri sur date/heure/moniteur/client
						
						foreach ($PostActivité as $depart)
						{
							$tmparray=$this->get_substitutionarray_posactivite($depart,$outputlangs);

							complete_substitutions_array($tmparray, $outputlangs, $object, $depart, "completesubstitutionarray_lines");
							foreach($tmparray as $key => $val)
							{
								try
								{
									$listmails->setVars($key, $val, true, 'UTF-8');
								}
								catch(OdfException $e)
								{
								}
								catch(SegmentException $e)
								{
								}
							}						
							$listmails->merge();
						
						}
					}
					$odfHandler->mergeSegment($listmails);
					
				}
				catch(OdfException $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -2;
				}
				// Write new file
				/* mis en commentaire tant dque conversion PDF non resolu	
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
				/*foreach ($bull->lines as $line)
				{
					if ($line->type_enr == 0 and !($line->action == 'X') and !($line->action == 'S') and $line->id_act == $idSes)
						$line->updateFic($file);
				}
				*/
				return 1;   // Success
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return -1;
			}
		}
	

		return -1;
	} //write_file


}

?>