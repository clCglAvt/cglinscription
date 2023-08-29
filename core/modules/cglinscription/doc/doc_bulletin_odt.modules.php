<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<ely@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 *
 *
 * Version CAV - 2.7.1 automne 2022
 *					 - correction de variable $line->enr inexistante, remplacer par this->type ou line->type_enr suivant les cas
 *					 - fiabilisation des foreach
 *
 * Version CAV - 2.8 hiver 2023 - Ajout variable depart_note
 * Version CAV - 2.8.4 printemps 2023
 *		- Modification du RDV2 en CONSEIL(bug 295)
 * 		- une location  d'un contrat non facturable a un prix à O (bug 318)
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
class doc_bulletin_odt extends CommonDocGenerator
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
	function get_substitutionarray_object($bull, $outputlangs, $array_key = '')
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
		$idSes = $array_key['idSes'];
		$mtactivite = 0;
		$multi = false;
		$double = false;
		$texteautreactivite = '';
		if (!empty($bull->lines)) {
			foreach ($bull->lines as $line)
			{
				if ($line->type_enr == 0 and $line->action != 'X' and $line->action != 'S') {
					if ($line->id_act == $idSes) {
						$mtactivite += $line->qte * $line->pu * (100 - (int)$line->rem) / 100;
						$bull->sub_activite_label = $line->activite_label;

						$bull->sub_activite_dated = substr($line->activite_dated,8,2).'/'.substr($line->activite_dated,5,2).'/'.substr($line->activite_dated,0,4);

						//$bull->sub_activite_dated = $line->activite_dated;

							$bull->sub_activite_heuref = substr($line->activite_heuref,11 ,2).'h '. substr($line->activite_heuref,14 ,2);

							$bull->sub_activite_heured = substr($line->activite_heured,11 ,2).'h '. substr($line->activite_heuref,14 ,2);

		
						$bull->sub_activite_lieu = $line->activite_lieu;
						$bull->sub_notes = $line->dep_notes;
			//			$bull->sub_site_infopublic = $line->infopublic;	
						$bull->sub_activite_rdv = $line->rdv_lib;
						$bull->sub_activite_rdv2 = $line->rdv2_lib;
						$bull->sub_moniteur_nom = $line->act_moniteur_nom;
						$bull->sub_moniteur_prenom = $line->act_moniteur_prenom;
						$bull->sub_duree = price2num($line->duree);
						$bull->sub_moniteur_tel= $line->act_moniteur_tel;
					}
					else {
						if ($double == true) {
							$multi = true;
						}
						elseif ($double == false) $double = true;
						if (!empty($line->activite_label) and strpos($texteautreactivite,  $line->activite_label) === false) {
						if ($double == true) $texteautreactivite .= ' et ';
							$texteautreactivite .= $line->activite_label;	
						}					
					}
				}
			}//foreach
		}
		
		// bulletin multi activités
		if ($multi == true) $textetotalmultiactivite = "Total Ã  payer pour cette activite et les activites ";
		elseif ($double == true) $textetotalmultiactivite = "Total Ã  payer pour cette activite et l'activite ";
		
		$textetotalmultiactivite .=  $texteautreactivite;
		$texttotalmonoactivite ='Total Ã  payer';
		$texttotalactivite ="Montant de l'activite";
		$mttotal=$bull->pt;		
		$resarray=array(
			'bull_ref'=>$bull->ref,
			'depart_label'=>$bull->sub_activite_label,
			'depart_dated'=>$bull->sub_activite_dated,
			'depart_lieu'=>$bull->sub_activite_lieu,
			'depart_note'=>$bull->sub_notes,
//			'bull_site_infopublic'=>$bull->sub_site_infopublic,
			'depart_rdv'=>$bull->sub_activite_rdv,
			'depart_conseil' => $bull->sub_activite_rdv2,
			'depart_heured'=>$bull->sub_activite_heured ,
			'depart_heuref'=>$bull->sub_activite_heuref,
			'moniteur_nom'=>$bull->sub_moniteur_nom ,
			'moniteur_tel'=>$bull->sub_moniteur_tel,
			'moniteur_prenom'=>$bull->sub_moniteur_prenom,
			'duree'=>$bull->sub_duree,

			'bull_nom_cli'=>$bull->tiersNom,
			'bull_prenom_cli'=>$bull->TiersPreNom,
			'bull_tel_cli'=>$bull->TiersTel,
			'bull_adresse_cli'=>$bull->TiersAdresse.' '.$bull->TiersCP.' '.$bull->TiersVille,
			'bull_nom_pers'=>$bull->pers_nom,		
			'bull_ActionFuture'=>$bull->ActionFuture,
			'bull_PmtFutur'=>$bull->PmtFutur,
			//'bull_nom_pers'=>($outputlangs->transnoentitiesnoconv('PaymentType'.$object->mode_reglement_code)!='PaymentType'.$object->mode_reglement_code?$outputlangs->transnoentitiesnoconv('PaymentType'.$object->mode_reglement_code):$object->mode_reglement),
			'bull_pre_pers'=>$bull->pers_prenom,
			'bull_parent_pers'=>$bull->pers_parente,
			'bull_tel_pers'=>$bull->pers_tel,
			'Txt_reste_paye'=>($bull->solde>0)?'Reste Ã  payer':'',
			'bull_reste_paye'=>price2num($bull->solde),
			'bull_pt'=>price2num($mttotal),
			'bull_paye'=>price2num($bull->paye, 'MT'),
			'bull_textactivite'=>($double)?$texttotalactivite:'',
			'bull_mtactivite'=>($double)?price2num($mtactivite):'',
			'bull_textgeneral'=>($double)?$textetotalmultiactivite:$texttotalmonoactivite
			
			//'object_total_vat'=>price2num($object->total_tva),
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
		global $bull,
		
		if (!empty($bull->facturable)) $pt = price2num($line->calulPtAct($line->type_session,$line->pu,$line->qte,$line->remise_percent)) ;
		else $pt = 0;
		if (!empty($bull->facturable)) $pu = price2num($line->pu) ;
		else $pu = 0;

		return array(
		'line_nom'=>$line->NomPrenom,
		'line_prenom'=>$line->NomPrenom,
		'line_age'=>$line->PartAge,
		'line_taille'=>$line->PartTaille,
		'line_qte'=>$line->qte,
		'line_obs'=>$line->observation,
		'line_pu'=>$pu,
		'line_rem'=>$line->remise_percent,
		'line_pt'=>$pt
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

/*
		$outputlangs->load("companies");
		$outputlangs->load("bills");
*/
		if ($conf->cglinscription->dir_output)
		{
			$dir = $conf->cglinscription->dir_output;
			$objectref = dol_sanitizeFileName($object->ref);
			// rajouter  $dir/bulletin comme on aura $dir/FeuilleRoute trouver dans srctemplatepath bulletin ou  feuilleroute
			if ( preg_match('/bulletin/i',$srctemplatepath)) 
			{
				$dirrel = 'bulletin';
				$dir.= "/" . 'bulletin';
			}
			elseif (! preg_match('/feuilleroute/i',$srctemplatepath)) 
			{
		
				$dirrel = 'feuilleroute';
				$dir.= "/" . 'feuilleroute';
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
					$filename=$newfiletmp.'.'.dol_print_date(dol_now('tzuser'),'%Y%m%d%H%M%S')."_".$idSes.'.'.$newfileformat;
				}
				else
				{
					$filename=$newfiletmp."_".$idSes.'.'.$newfileformat;
				}
				
				$file=$object->NommageEditionBulletin('fichier', $idSes).'.odt';

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
				$array_soc=$this->get_substitutionarray_mysoc($mysoc,$outputlangs);
				//$array_thirdparty=$this->get_substitutionarray_thirdparty($socobject,$outputlangs);
				
				$array_key = array();
				$array_key['idSes'] = $idSes;
				$array_objet=$this->get_substitutionarray_object($object, $outputlangs, $array_key);

				//$array_propal=is_object($propal_object)?$this->get_substitutionarray_propal($propal_object,$outputlangs,'propal'):array();
				//$array_other=$this->get_substitutionarray_other($user,$outputlangs);

				$tmparray = array_merge($array_objet, $array_soc);
				complete_substitutions_array($tmparray1, $outputlangs, $object);
				//var_dump($tmparray); exit;
				$tmparray = array_merge($tmparray, $tmparray1);
				
				if (!empty($tmparray)) {
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
					}//foreach
				}
				// Replace tags of lines
				try
				{
					$listlines = $odfHandler->setSegment('lines');
					
					if (!empty($object->lines)) {
						foreach ($object->lines as $line)
						{

							if ($line->id_act == $idSes)
							{
								$line->type_session = $object->type_session;
								$tmparray=$this->get_substitutionarray_lines($line,$outputlangs);
								complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
								if (!empty($tmparray)) {
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
									} //foreach
								}
								$listlines->merge();
							}
						}//foreach
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
							
				// stocker le nom du fichier en base
				
				$bull->updateFicBull($file, $idSes);
				/*foreach ($bull->lines as $line)
				{
					if ($line->type_enr == 0 and !($line->action == 'X') and !($line->action == 'S') and $line->id_act == $idSes) {
						$file1=substr( $line->ficbull, 0, strlen($line->ficbull)-3).'pdf';
						if (file_exists($file1))$file=$file1;
						$line->updateFicBull($file);
					}
				}
				*/

///* mis en commentaire tant dque conversion PDF non resolu	
/*				if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
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
				else 				{
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

				// Ne trouvant ps le moyen de faire un pdf en automatique, on laisse l'utilisateur faire le pdf manuellement, le mettre dans dolibarr_/-pour pdf
				// On prendra le PDF créé et on le met dans bulletin/<num BU> - à l'ouverture du BU/LO, à la demande de mail, à l'ouverture des listes
				$odfHandler=null;	// Destroy object

				

				return 1;   // Success
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return -1;
			}
		}
	

		return -1;
	}
}

?>