

<?php

//Un essai - possède les développeents interessants pour la suite, entre autre formconfirm et formquestion 
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * Version CAV - 2.7 - été 2022 - Migration Dolibarr 
 *					 	- Remplacer method="GET" par method="POST"
 *						- correction requete principale d'affichage
 * Version CAV - 2.8 - hiver 2023 - Pagination (suppression Ajout)
 * 								  - vérification de la fiabilité des foreach
 *
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
 *
 *
 *   	\file       custom/cglinscription/list.php
 *		\ingroup    cglinscription
 *		\brief      Liste les inscriptions
 */
 
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

 require_once DOL_DOCUMENT_ROOT."/custom/CahierSuivi/class/html.suivi_client.class.php";

$res=0;
global $db, $conf, $langs;
// Load traductions files requiredby by page
$langs->load("other");
$langs->load("cglinscription@cglinscription");



$title = 'Essai Popup';
	llxHeader('',$langs->trans('LcglinscriptionFct'));
	
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="ecran" value="'.$ecran.'">';

	

//print '<button id="btModif" onclick="OuvreModale(this)">Modifier</button>'."\n";	
//print '<input class="button" type="submit"  value="Modifier" onclick="OuvreModale(this)">'."\n";
print '33849 -<a class="butAction"  href="#" onclick="OuvreModale(this, 33849)">Modifier</a> '."\n";
print '33849 -<a class="butAction"  href="#" onclick="OuvreModale(this, 0)">Creer</a> '."\n";
print '<p>';
print '33870 - <a class="butAction"  href="#" onclick="OuvreModale(this,33870)">Modifier</a> '."\n";

	print '</form>';


//<!-- updateButton.addEventListener("click", function onOpen() { -->


function AfficheModale( $ListContenu , $SepLigne)
{
	global $db;
	$form = New Form($db);
	print '
	<dialog id="fmodalDialog" close>
	  <form method="dialog">
	  <h1 id="titre" > 	 </h1>
	  <hr>';
	  
	if (is_array($ListContenu) && !empty($ListContenu)) {
		// First add hidden fields and value
		foreach ($ListContenu as $key => $input) {
			if (is_array($input) && !empty($input)) {
				if ($input['type'] == 'hidden') {
					print '<input type="hidden" id="'.dol_escape_htmltag($input['name']).'" name="'.dol_escape_htmltag($input['name']).'" value="'.dol_escape_htmltag($input['value']).'">'."\n";
				}
			}
		}
	}

	// Now add questions
	$moreonecolumn = '';
	//$more .= '<div class="tagtable paddingtopbottomonly centpercent noborderspacing">'."\n";
	foreach ($ListContenu as $key => $input) {
		if (is_array($input) && !empty($input)) {
			$size = (!empty($input['size']) ? ' size="'.$input['size'].'"' : '');	// deprecated. Use morecss instead.
			$moreattr = (!empty($input['moreattr']) ? ' '.$input['moreattr'] : '');
			$morecss = (!empty($input['morecss']) ? ' '.$input['morecss'] : '');

			if ($input['type'] == 'text') {
				print $SepLigne;	
	//					$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd"><input type="text" class="flat'.$morecss.'" id="'.dol_escape_htmltag($input['name']).'" name="'.dol_escape_htmltag($input['name']).'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></div></div>'."\n";
				print $input["label"].'<input type="text" class="flat'.$morecss.'" id="'.dol_escape_htmltag($input['name']).'" name="'.	dol_escape_htmltag($input['name']).'"'.$size.' value="'.$input['value'].'"'.$moreattr.' />';

			}
			 elseif ($input['type'] == 'password')	{
				print '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd"><input type="password" class="flat'.$morecss.'" id="'.dol_escape_htmltag($input['name']).'" name="'.dol_escape_htmltag($input['name']).'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></div></div>'."\n";
			}
			elseif ($input['type'] == 'textarea') {
				/*$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd">';
				$more .= '<textarea name="'.$input['name'].'" class="'.$morecss.'"'.$moreattr.'>';
				$more .= $input['value'];
				$more .= '</textarea>';
				$more .= '</div></div>'."\n";*/
				//print '<div class="margintoponly">';
				print $SepLigne;	
				print  '<div>';
				print  $input['label'];
				print  '<textarea name="'.dol_escape_htmltag($input['name']).'" id="'.dol_escape_htmltag($input['name']).'" class="'.$morecss.'"'.$moreattr.'>';
				print  $input['value'];
				print  '</textarea>';
				print  '</div>';
			}
			 elseif ($input['type'] == 'select') {
				if (empty($morecss)) {
					$morecss = 'minwidth100';
				}

				$show_empty = isset($input['select_show_empty']) ? $input['select_show_empty'] : 1;
				$key_in_label = isset($input['select_key_in_label']) ? $input['select_key_in_label'] : 0;
				$value_as_key = isset($input['select_value_as_key']) ? $input['select_value_as_key'] : 0;
				$translate = isset($input['select_translate']) ? $input['select_translate'] : 0;
				$maxlen = isset($input['select_maxlen']) ? $input['select_maxlen'] : 0;
				$disabled = isset($input['select_disabled']) ? $input['select_disabled'] : 0;
				$sort = isset($input['select_sort']) ? $input['select_sort'] : '';

				print '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">';
				if (!empty($input['label'])) {
					print $input['label'].'</div><div class="tagtd left">';
				}
				print $form->selectarray($input['name'], $input['values'], $input['default'], $show_empty, $key_in_label, $value_as_key, $moreattr, $translate, $maxlen, $disabled, $sort, $morecss);
				print '</div></div>'."\n";
			} 
			elseif ($input['type'] == 'checkbox') {
				print '<div class="tagtr">';
				print '<div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].' </div><div class="tagtd">';
				print '<input type="checkbox" class="flat'.$morecss.'" id="'.dol_escape_htmltag($input['name']).'" name="'.dol_escape_htmltag($input['name']).'"'.$moreattr;
				if (!is_bool($input['value']) && $input['value'] != 'false' && $input['value'] != '0' && $input['value'] != '') {
					print ' checked';
				}
				if (is_bool($input['value']) && $input['value']) {
					print ' checked';
				}
				if (isset($input['disabled'])) {
					print ' disabled';
				}
				print ' /></div>';
				print '</div>'."\n";
			}
			 elseif ($input['type'] == 'radio') {
				$i = 0;
				foreach ($input['values'] as $selkey => $selval) {
					print '<div class="tagtr">';
					if ($i == 0) {
						print '<div class="tagtd'.(empty($input['tdclass']) ? ' tdtop' : (' tdtop '.$input['tdclass'])).'">'.$input['label'].'</div>';
					} else {
						print '<div clas="tagtd'.(empty($input['tdclass']) ? '' : (' "'.$input['tdclass'])).'">&nbsp;</div>';
					}
					print '<div class="tagtd'.($i == 0 ? ' tdtop' : '').'"><input type="radio" class="flat'.$morecss.'" id="'.dol_escape_htmltag($input['name'].$selkey).'" name="'.dol_escape_htmltag($input['name']).'" value="'.$selkey.'"'.$moreattr;
					if ($input['disabled']) {
						$more .= ' disabled';
					}
					if (isset($input['default']) && $input['default'] === $selkey) {
						$more .= ' checked="checked"';
					}
					print ' /> ';
					print '<label for="'.dol_escape_htmltag($input['name'].$selkey).'">'.$selval.'</label>';
					print '</div></div>'."\n";
					$i++;
				}
			 }
			elseif ($input['type'] == 'date') {
				print '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div>';
				print '<div class="tagtd">';
				$addnowlink = (empty($input['datenow']) ? 0 : 1);
				print $this->selectDate($input['value'], $input['name'], 0, 0, 0, '', 1, $addnowlink);
				print' </div></div>'."\n";
				$ListContenu[] = array('name'=>$input['name'].'day');
				$ListContenu[] = array('name'=>$input['name'].'month');
				$ListContenu[] = array('name'=>$input['name'].'year');
				$ListContenu[] = array('name'=>$input['name'].'hour');
				$ListContenu[] = array('name'=>$input['name'].'min');
			}
			elseif ($input['type'] == 'other') {
				print '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">';
				if (!empty($input['label'])) {
					print $input['label'].'</div><div class="tagtd">';
				}
				print $input['value'];
				print '</div></div>'."\n";
			}
			elseif ($input['type'] == 'onecolumn') {
				$moreonecolumn .= '<div class="margintoponly">';
				$moreonecolumn .= $input['value'];
				$moreonecolumn .= '</div>'."\n";
			}
			elseif ($input['type'] == 'hidden') {
				// Do nothing more, already added by a previous loop
			}
			 elseif ($input['type'] == 'separator') {
				print '<br>';
				
	//CCA 12/12/2018 - Ancre					
			} elseif ($input['type'] == 'ancre') {
				$urlmoreancre.='#';
				$urlmoreancre.=$input['value'];
	// Fin Modif CCA 12/12/2018


			} else {
				print 'Error type '.$input['type'].' for the confirm box is not a supported type';
			}
		}
	}
	//$more .= '</div>'."\n";
	print $moreonecolumn;

	 print '   
	  </form>
	</dialog>
';


	print   '<script> 
	 function OuvreModale(o, id){
		alert("id"+ id);	
		let fmodalDialog = document.getElementById("fmodalDialog");
		 if (typeof fmodalDialog.showModal === "function") {
		if (id>0) titre = "Modification échange";
		else 	titre = "Nouvel echange";	
		document.getElementById("titre").innerHTML = titre;
		fmodalDialog.showModal();
		} 
		else alert ("erreur");
	}
	function EnrModal(o) {
		alert (o.value);
		
		if ( o.value == "Enregistrer") {
			alert (document.getElementById("dossier").value);
			alert (document.getElementById("secteur").value);
			<!-- alert("Fonction enregistrement données - ReqEnrEchange - reaffichier le tableau du dossier - dans Inscription PavéSuivi, dans Suivi, tout le tableau"); -->
		}
	};

	let selectEl = document.querySelector("select");
	<!-- sortie des données -->
	<!-- L"entrée "Animal favori" définit la valeur du bouton d"envoi. -->
	selectEl.addEventListener("change", function onSelect(e) {
	 <!--  confirmBtn.value = selectEl.value; -->
	});
	</script>';

} //AfficheModale

function Chargement($id)
{
	global $db;
	
	if (empty($id)) return null;
	
	$wsuivi = new cgl_echange ($db);	
	$wsuivi->fetch($id);
	return $wsuivi;
} // Chargement

function Constitution($wsuivi) 
{
	global $db;	
	
	$wformsuivi =  new FormCglSuivi($db);

	$ListContenu = array();
	if (empty($wsuivi)) $text = '';
	else $text = $wsuivi->id;
	$ListContenu[] = array ( "type"=>"hidden" ,"name"=>"Champ1" , "id"=>"Champ1", "value"=>$text);

	if (empty($wsuivi)) $text = '';
	else $text = $wsuivi->NomDossier;
	$ListContenu[] = array ("label"=>"Dossier", "type" =>"text",   "name"=>"dossier" , "id"=>"dossier" , "value"=>$text );
	if (empty($wsuivi)) $text = '';
	else $text = $wsuivi->desc;
	$ListContenu[] = array ("label"=>"Contenu", "type" =>"textarea",   "name"=>"contenu" , "id"=>"echange", "value"=>$wsuivi->desc);

	//if (empty($wsuivi)) 
	$ListContenu[] = array ("label"=>"Secteur", 'type' => 'other', 'name'=>'Secteur',
			"value" =>$wformsuivi->select_secteur('','secteur','',1,1,1,0, '', 0, 0));
	//			print $this->select_secteur($line->fk_secteur,'arg_secteur','',1,1,1,0, '', 0, 0);

	//		"values" =>array(	'Id1'=>array(("label" => "4 saisons","values"=>"Chat1", "css"=>"selected"))));
	//		"values" =>array(	'label' , array('value'=>"4 saisons", "disabled"));
	//		"values" =>array(	'Id1'=>"4 saisons"));

	//$ListContenu[] = array ("label"=>"Secteur", "type" =>"text",   "name"=>"Secteur" , "id"=>"Secteur", "value"=>"Vélo");
	if (empty($wsuivi)) $text = '';
	else $text = $wsuivi->action;
	$ListContenu[] = array ("label"=>"Action", "type" =>"text",   "name"=>"action" , "id"=>"action", "value"=>$text);

	if (empty($wsuivi)) $text = '';
	else $text = $wsuivi->id_interlocuteur;
	$ListContenu[] = array ("label"=>"Interlocuteur", "type" =>"other",   "name"=>"Interlocuteur" , "id"=>"Interlocuteur", "value"=>$text);

	if (empty($wsuivi)) $text = '';
	else $text = $wsuivi->interlocuteur;
	$ListContenu[] = array ("label"=>"Interlocuteur", "type" =>"text",   "name"=>"Interlocuteur" , "id"=>"Interlocuteur", "value"=>$text);


	if (empty($wsuivi)) $text = '';
	else $text = $wsuivi->id_interlocuteur;

	if (!empty($wsuivi))
		$ListContenu[] = array ("label"=>"TelMail", "type" =>"text",   "name"=>"Tel" , "id"=>"Tel", "value"=>$wsuivi->telmail);
	else {
		$ListContenu[] = array ("label"=>"Mail", "type" =>"text",   "name"=>"Mail" , "id"=>"Mail", "value"=>$wsuivi->Interphone);
		$ListContenu[] = array ("label"=>"Tel", "type" =>"text",   "name"=>"Tel" , "id"=>"Tel", "value"=>$wsuivi->Interemail);
		
	}

	$ListContenu[] = array ("type"=>"other", "label"=>"<menu>");
	$ListContenu[] = array ("type"=>"other","value" =>'<button value="Annuler" >Annuler</button><button id="confirmBtn" value="Enregistrer"  onclick="EnrModal(this)" >Enregistrer</button>' );
	$ListContenu[] = array ("type"=>"other", "label"=>"</menu>");
	return $ListContenu;
} // Constitution

$listchampVal = Constitution(Chargement($id));
AfficheModale($listchampVal, '<p>');


/*

print '
<script>
let updateButton = document.getElementById("btModif");
let outputBox = document.querySelector("output");
let confirmBtn = document.getElementById("confirmBtn");

<!--  A l"ouverture de la fenêtre modale récuoérer les données -->
<!-- Le bouton "Mettre à jour les détails" ouvre le <dialogue> ; modulaire -->
<!--fmodalDialog.addEventListener("DOMContentLoaded", function() { -->
<!-- { -->

<!-- L"entrée "Animal favori" définit la valeur du bouton d"envoi. -->
selectEl.addEventListener("change", function onSelect(e) {
  confirmBtn.value = selectEl.value;
});
<!-- Le bouton "Enregistrer" du formulaire déclenche la fermeture -->
<!-- de la boîte de dialogue en raison de [method="dialog"] -->
<!-- fmodalDialog.addEventListener("close", function onClose() { -->
<!-- cfunction ModalClose(o) -->
fmodalDialog.addEventListener("DOMContentLoaded", function(){fmodalDialog.addEventListener("DOMContentLoaded", function()
	if ( fmodalDialog.returnValue != "Annuler") {
		alert("Fonction enregistrement données - ReqEnrEchange - reaffichier le tableau du dossier (dans Inscription PavéSuivi, dans Suivi, tout le tableau");
		outputBox.value = fmodalDialog.returnValue + " bouton cliqué - " + (new Date()).toString();
	}
});
</script>

<!-- pourrait permettre le transfert des données vers le coeur de Inscription. Est-ce utile?
<output aria-live="polite"></output>

';
*/
