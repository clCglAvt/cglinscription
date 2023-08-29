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


/**
 *	Class to build documents using ODF templates generator
 */
//class doc_generic_bulletin_odt extends ModelePDFFactures
class doc_location_odt extends CommonDocGenerator
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
	function get_substitutionarray_object($bull, $outputlangs, $type_session = '')
	{
		global $conf, $langs;
		
		$w = new CglFonctionCommune($this->db);
		$datetempretrait = $w->transfDateFr($bull->locdateretrait);
		$datetempdepose = $w->transfDateFr($bull->locdatedepose);
		unset ($w);
		$resarray=array(
			'loc_dateretr' => $datetempretrait,
//			'loc_datedep' => $bull->locdatedepose->format('d/m/y'), 
			'loc_datedep' => $datetempdepose,  
			'loc_titre' => $bull->titre_contrat,
			'loc_ref' => $bull->ref,
			'loc_lb_fac' =>$bull->titre_fac,
			'loc_reffac' => $bull->facnumber,
			'loc_Nom' => $bull->tiersNom,
			'loc_adresse' => $bull->TiersAdresse,
			'loc_cp' => $bull->TiersCP,
			'loc_ville' => $bull->TiersVille,
			'loc_tel' => $bull->TiersTel,
			'loc_mail' => $bull->TiersMail,		
			'bull_ActionFuture'=>$bull->ActionFuture,
			'bull_PmtFutur'=>$bull->PmtFutur,
			'loc_lieuretr' => $bull->loclieuretrait,
			'loc_lieuretr' => $bull->loclieuretrait,
			'loc_lieudep' => $bull->loclieudepose,
			/*'loc_tiresa' =>$bull->titre_resa,
			'loc_libresa' => $bull->locResa,
			'loc_SttResa' => $bull->SttResa,*/
			'loc_pt' => price2num($bull->pt),
			'loc_mtt_acompte' => price2num($bull->acc_paye),
			'loc_lb_acc_paye' => $bull->lb_acc_paye,				
			'loc_mtt_caution' => price2num($bull->mttcaution),
			'loc_solde' => price2num($bull->solde),
			'loc_lb_mode_paiement' => $bull->modes_paiement ,
			'loc_rando_autre' => $bull->obs_rando,
			'loc_mad_autre' => $bull->obs_matmad,
			'loc_observation' => $bull->locObs,
			'loc_lb_mod_caution' => $bull->lb_modcaution,
			'loc_lb_doc_caution' => $bull->lbedi_caution,
			'loc_ActionFuture' => $bull->ActionFuture,
			'loc_PmtFutur' => $bull->PmtFutur,
			'loc_textremisesfixes'=>$bull->textremisesfixes
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
		global $bull;

		if (empty($line->duree) or $line->duree == -1)  $duree = ''; 
		elseif ($line->duree == 0.5 ) 	$duree = '0.5j';			
		elseif ($line->duree > 0 ) $duree = (int) $line->duree.'j';
		//$dateretrait =  $bull->locdateretrait->format('d/m/y G\h:i\m') ; 
		
		$w = new CglFonctionCommune($this->db);
		$temp = $w->transfDateEtHeure($bull->locdateretrait);
		$dateretrait = $temp  ; 
		$temp = $w->transfDateEtHeure($bull->locdatedepose);
		$datedepose =  $temp ; 
		unset ($w);
		if (!empty($bull->facturable)) $pt = $line->pu * $line->qte * (100 - (int)$line->remise_percent)/100;
		else $pt = 0;
		
		return array(
		'line_nom'=>$line->NomTrajet,
		'line_tail' => $line->PartTaille,
		'line_serv' => $line->service,
		'line_marq' => $line->marque,
		'line_mat' => $line->materiel,
		'line_ref' => $line->refmat,
		'line_retrait' => $dateretrait,
		'line_depose' => $datedepose,
		'line_duree' => $duree,
		'line_rem' => $line->remise_percent,
		'line_pt' => $pt,
		'line_ret' => $line->qteret
		
		);
		
	} //get_substitutionarray_lines
	function get_substitutionarray_rando($line,$outputlangs, $type_session)
	{	
		return array(
		'lb_rando'=>$line->lb_rando,
		'nb_rando' => $line->qte,
		'lb_ret_rando' => $line->lb_ret_rando,
		'ret_rando' => $line->qteret
		);		
	} //get_substitutionarray_rando
	
	function get_substitutionarray_mad($line,$outputlangs, $type_session)
	{	
		return array(
		'lb_mat_mad'=>$line->lb_mat_mad_tot,
		'nb_mat_mat' => $line->qte,
		'lb_ret_mat' => $line->lb_ret_mat,
		'ret_mat_mad' => $line->qteret
		);		
	} //get_substitutionarray_rando
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
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	function write_file($object, $outputlangs,$srctemplatepath,$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc, $bull;

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
			$objectref = dol_sanitizeFileName($object->ref);
			// rajouter  $dir/contratLoc trouver dans srctemplatepath bulletin ou  feuilleroute
			if ( preg_match('/contrat/i',$srctemplatepath)) 
			{
				$dirrel = 'contratLoc';
				$dir= $dirgen."/".'contratLoc';
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
					$filename=$newfiletmp.'.'.$newfileformat;
				}			
				 //
				$file=$dirgen.'/contratLoc/'.$objectref.'/'.$filename;
				//$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now('tzuser'),'%Y%m%d%H%M%S').'.odt';
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
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
				
				//$array_propal=is_object($propal_object)?$this->get_substitutionarray_propal($propal_object,$outputlangs,'propal'):array();
				//$array_other=$this->get_substitutionarray_other($user,$outputlangs);

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
				
				
				// Replace tags of lines rando
				try
				{
					$listrando = $odfHandler->setSegment('rando');
					foreach ($object->lines_rando as $line)
					{
						if ($line->qte > 0) {
							$tmparray=$this->get_substitutionarray_rando($line,$outputlangs, $object->type_session);
							complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
							foreach($tmparray as $key => $val)
							{
								try
								{
									$listrando->setVars($key, $val, true, 'UTF-8');
								}
								catch(OdfException $e)
								{
								}
								catch(SegmentException $e)
								{
								}
							}
							$listrando->merge();
						}
					}
					$odfHandler->mergeSegment($listrando);					
				}
				catch(OdfException $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -7;
				}
				// Replace tags of lines location
				try
				{
					$listlines = $odfHandler->setSegment('lines');
					foreach ($object->lines as $line)
					{
						if ($line->type_enr==0 )
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
					}
					$odfHandler->mergeSegment($listlines);
					
				}
				catch(OdfException $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -3;
				}

				// Replace tags of lines mad
				try
				{
					$listmad = $odfHandler->setSegment('mad');
					foreach ($object->lines_mat_mad as $line)
					{
						if ($line->qte>0 )
						{
							$tmparray=$this->get_substitutionarray_mad($line,$outputlangs, $object->type_session);
							complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
							foreach($tmparray as $key => $val)
							{
								try
								{
									$listmad->setVars($key, $val, true, 'UTF-8');
								}
								catch(OdfException $e)
								{
								}
								catch(SegmentException $e)
								{
								}
							}
							$listmad->merge();
						}						
					}
					$odfHandler->mergeSegment($listmad);
					
				}
				catch(OdfException $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -8;
				}
				// Write new file
/* mis en commentaire tant que conversion PDF non resolu			
				if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
					try 
					{
						$odfHandler->exportAsAttachedPDF($file);
					}
					catch (Exception $e)
					{
						$this->error=$e->getMessage();
						dol_syslog("exportAsAttachedPDF:".$this->error, LOG_WARNING);
						return -4;
					}
				}
				else {
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
				
				// stocker le nom du fichier en base
				$bull->updateFicBull($file);
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