<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2011      Remy Younes          <ryounes@gmail.com>
 * Copyright (C) 2012-2013 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2012      Christophe Battarel	<christophe.battarel@ltairis.fr>
 * Copyright (C) 2011-2012 Alexandre Spangaro	<alexandre.spangaro@gmail.com>
 * 
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
 *	    \file       htdocs/custum/cglinscription/mat_mad.php
 *		\ingroup    setup
 *		\brief      Gestion des éléments du matériel mis à disposition (id = 2) et des randos (id=1)
 */
if (file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once ('./class/cglinscription.class.php');
// Load traductions files requiredby by page
$langs->load("errors");
$langs->load("admin");
$langs->load("other");
$langs->load("cglinscription@cglinscription");

// Get parameters
$action=GETPOST('action','alpha')?GETPOST('action','alpha'):'view';
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$rowid=GETPOST('rowid','alpha');

if (!$user->admin) accessforbidden();

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"),'switch_off');
$actl[1] = img_picto($langs->trans("Activated"),'switch_on');

$listoffset=GETPOST('listoffset');
$listlimit=GETPOST('listlimit', 'int')>0?GETPOST('listlimit'):1000;
$active = 1;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');


// Gestion des pages d'affichage
if ($page == -1) { $page = 0 ; }
$offset = $listlimit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('admin'));


// Name of SQL tables of dictionaries
$tabname=array();
$tabname[1] = MAIN_DB_PREFIX."cgl_c_rando";
$tabname[2] = MAIN_DB_PREFIX."cgl_c_mat_mad";

// Dictionary labels
$tablib=array();
$tablib[1] = "DictionaryRando";
$tablib[2] = "DictionaryMatMad";

// Requete pour extraction des donnees des dictionnaires
$tabsql=array();
$tabsql[1] = "SELECT f.rowid as rowid, f.code, f.libelle, f.fk_service, p.ref as refservice, p.label as nomservice, f.active , f.ordre FROM ".MAIN_DB_PREFIX."cgl_c_rando as f LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = f.fk_service WHERE f.active = 1";
$tabsql[2] = "SELECT f.rowid as rowid, f.code, f.libelle, f.fk_service, p.ref as refservice, p.label as nomservice, f.active  , f.ordre FROM ".MAIN_DB_PREFIX."cgl_c_mat_mad as f LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = f.fk_service  WHERE  f.active = 1";

// Critere de tri du dictionnaire
$tabsqlsort=array();
$tabsqlsort[1] ="ordre ASC, code ASC";
$tabsqlsort[2] ="ordre ASC, code ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield=array();
$tabfield[1] = "code,libelle,nomservice,ordre";
$tabfield[2] = "code,libelle,nomservice,ordre";

// Nom des champs d'edition pour modification d'un enregistrement
$tabfieldvalue=array();
$tabfieldvalue[1] = "code,libelle,nomservice,ordre";
$tabfieldvalue[2] = "code,libelle,nomservice,ordre";

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert=array();
$tabfieldinsert[1] = "code,libelle,fk_service,ordre";
$tabfieldinsert[2] = "code,libelle,fk_service,ordre";

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid=array();
$tabrowid[1] = "";
$tabrowid[2] = "";

// Condition to show dictionary in setup page
$tabcond=array();
$tabcond[1] = true;
$tabcond[2] = true;
//$tabcond[5] = (! empty($conf->societe->enabled) || ! empty($conf->adherent->enabled));

// List of help for fields
$tabhelp=array();
$tabhelp[1]  = array();
$tabhelp[2]  = array();

// Complete all arrays with entries found into modules
//complete_dictionary_with_modules($taborder,$tabname,$tablib,$tabsql,$tabsqlsort,$tabfield,$tabfieldvalue,$tabfieldinsert,$tabrowid,$tabcond,$tabhelp);


// Actions ajout ou modification d'une entree dans un dictionnaire de donnee
if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha'))
{
    $listfield=explode(',',$tabfield[$id]);
    $listfieldinsert=explode(',',$tabfieldinsert[$id]);
    $listfieldmodify=explode(',',$tabfieldinsert[$id]);
    $listfieldvalue=explode(',',$tabfieldvalue[$id]);

    // Check that all fields are filled
    $ok=1;
    foreach ($listfield as $f => $value)
    {
		if ((! isset($_POST[$value]) || $_POST[$value]=='')
        	&& (! in_array($listfield[$f], array('ordre')))  // Fields that are not mandatory
		)		
			if (!($listfield[$f] == 'ordre'))	
        {
            $ok=0;
            $fieldnamekey=$listfield[$f];
 /*          // We take translate key of field
            if ($fieldnamekey == 'libelle' || ($fieldnamekey == 'label'))  $fieldnamekey='Label';
            if ($fieldnamekey == 'sortorder') $fieldnamekey = 'SortOrder';
*/
            setEventMessage($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)),'errors');
        }
    }
    // Other checks
    if ($tabname[$id] == MAIN_DB_PREFIX."c_actioncomm" && isset($_POST["type"]) && in_array($_POST["type"],array('system','systemauto'))) {
        $ok=0;
        setEventMessage($langs->transnoentities('ErrorReservedTypeSystemSystemAuto'),'errors');
    }
    if (isset($_POST["code"]))
    {
    	if ($_POST["code"]=='0')
    	{
        	$ok=0;
    		setEventMessage($langs->transnoentities('ErrorCodeCantContainZero'),'errors');
        }
    }

    // Si verif ok et action add, on ajoute la ligne
    if ($ok && GETPOST('actionadd', 'alpha'))
    {
        if ($tabrowid[$id])
        {
            // Recupere id libre pour insertion
            $newid=0;
            $sql = "SELECT max(".$tabrowid[$id].") newid from ".$tabname[$id];
            $result = $db->query($sql);
            if ($result)
            {
                $obj = $db->fetch_object($result);
                $newid=($obj->newid + 1);

            } else {
                dol_print_error($db);
            }
        }

        // Add new entry
        $sql = "INSERT INTO ".$tabname[$id]." (";
        // List of fields
        if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldinsert))
        	$sql.= $tabrowid[$id].",";
        $sql.= $tabfieldinsert[$id];
        $sql.=",active)";
        $sql.= " VALUES(";
        // List of values
        if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldinsert))
        	$sql.= $newid.",";
        $i=0;
        foreach ($listfieldinsert as $f => $value)
        {
            if ($i) $sql.=",";
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.=",1)";
		
        dol_syslog("actionadd sql=".$sql);
        $result = $db->query($sql);
        if ($result)	// Add is ok
        {
            setEventMessage($langs->transnoentities("RecordSaved"));
        	$_POST=array('id'=>$id);	// Clean $_POST array, we keep only
        }
        else
        {
            if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                setEventMessage($langs->transnoentities("ErrorRecordAlreadyExists"),'errors');
            }
            else {
                dol_print_error($db);
            }
        }
    }

    // Si verif ok et action modify, on modifie la ligne
    if ($ok && GETPOST('actionmodify', 'alpha'))
    {
        if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
        else { $rowidcol="rowid"; }

        // Modify entry
        $sql = "UPDATE ".$tabname[$id]." SET ";
        // Modifie valeur des champs
        if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldmodify))
        {
            $sql.= $tabrowid[$id]."=";
            $sql.= "'".$db->escape($rowid)."', ";
        }
        $i = 0;
        foreach ($listfieldmodify as $field)
        {
            if ($i) $sql.=",";
            $sql.= $field."=";
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.= " WHERE ".$rowidcol." = '".$rowid."'";

        dol_syslog("actionmodify sql=".$sql);
        //print $sql;
        $resql = $db->query($sql);
        if (! $resql)
        {
            setEventMessage($db->error(),'errors');
        }
    }
}

if (GETPOST('actioncancel', 'alpha'))
{
}

if ($action == 'confirm_delete' && $confirm == 'yes')       // delete
{
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    $sql = "DELETE from ".$tabname[$id]." WHERE ".$rowidcol."='".$rowid."'";

    dol_syslog("delete sql=".$sql);
    $result = $db->query($sql);
    if (! $result)
    {
        if ($db->errno() == 'DB_ERROR_CHILD_EXISTS')
        {
            setEventMessage($langs->transnoentities("ErrorRecordIsUsedByChild"),'errors');
        }
        else
        {
            dol_print_error($db);
        }
    }
}

// activate
if ($action == $acts[0])
{
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    if ($rowid) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE ".$rowidcol."='".$rowid."'";
    }
    elseif ($_GET["code"]) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE code='".$_GET["code"]."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}

// disable
if ($action == $acts[1])
{
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    if ($rowid) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE ".$rowidcol."='".$rowid."'";
    }
    elseif ($_GET["code"]) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE code='".$_GET["code"]."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}


/*
 * View
 */

$form = new Form($db);
$formadmin=new FormAdmin($db);

llxHeader('','Lcglinscription');

$titre=$langs->trans("DictionarySetup");
$linkback='';
if ($id)
{
    $titre.=' - '.$langs->trans($tablib[$id]);
    $linkback='<a href="'.$_SERVER['PHP_SELF'].'">'.$langs->trans("BackToDictionaryList").'</a>';
}
print_fiche_titre($titre,$linkback,'setup');

if (empty($id))
{
    print $langs->trans("DictionaryDesc");
    print " ".$langs->trans("OnlyActiveElementsAreShown")."<br>\n";
}
print "<br>\n";


// Confirmation de la suppression de la ligne
if ($action == 'delete')
{
    print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid.'&code='.$_GET["code"].'&id='.$id, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete','',0,1);
}

/*
 * Show a dictionary
 */

    // Complete requete recherche valeurs avec critere de tri
    $sql=$tabsql[$id];

    if ($sortfield)
    {
        $sql.= " ORDER BY ".$sortfield;
        if ($sortorder)
        {
            $sql.=" ".strtoupper($sortorder);
        }
        $sql.=", ";
        // Clear the required sort criteria for the tabsqlsort to be able to force it with selected value
        $tabsqlsort[$id]=preg_replace('/([a-z]+\.)?'.$sortfield.' '.$sortorder.',/i','',$tabsqlsort[$id]);
        $tabsqlsort[$id]=preg_replace('/([a-z]+\.)?'.$sortfield.',/i','',$tabsqlsort[$id]);
    }
    else {
        $sql.=" ORDER BY ";
    }
    $sql.=$tabsqlsort[$id];
    $sql.=$db->plimit($listlimit+1,$offset);
	//print $sql;

    $fieldlist=explode(',',$tabfield[$id]);

    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
    print '<input type="hidden" name="token" value="'.newtoken().'">';
    print '<table class="noborder" width="100%">';

    // Form to add a new line
    if ($tabname[$id])
    {
        $alabelisused=0;
        $var=false;

        $fieldlist=explode(',',$tabfield[$id]);

        // Line for title
        print '<tr class="liste_titre">';
        foreach ($fieldlist as $field => $value)
        {
		
            // Determine le nom du champ par rapport aux noms possibles
            // dans les dictionnaires de donnees
            $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
            $valuetoshow=$langs->trans($valuetoshow);   // try to translate
            $align="left";

            if ($fieldlist[$field]=='ordre')            { $valuetoshow=$langs->trans("ordre"); }
            if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
            if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') { $valuetoshow=$langs->trans("Label")."*"; }
            if ($fieldlist[$field]=='sortorder')       { $valuetoshow=$langs->trans("SortOrder"); }
            if ($valuetoshow != '')
            {
                print '<td align="'.$align.'">';
            	if (! empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i',$tabhelp[$id][$value])) print '<a href="'.$tabhelp[$id][$value].'" target="_blank">'.$valuetoshow.' '.img_help(1,$valuetoshow).'</a>';
            	else if (! empty($tabhelp[$id][$value])) print $form->textwithpicto($valuetoshow,$tabhelp[$id][$value]);
            	else print $valuetoshow;
                print '</td>';
             }
             if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') $alabelisused=1;
        }
        print '<td colspan="3">';
        print '<input type="hidden" name="id" value="'.$id.'">';
        print '&nbsp;</td>';
        print '</tr>';

        // Line to type new values
        print "<tr ".$bc[$var].">";

        $obj = new stdClass();
        // If data was already input, we define them in obj to populate input fields.
        if (GETPOST('actionadd', 'alpha'))
        {
            foreach ($fieldlist as $key=>$val)
            {
                if (GETPOST($val))
                	$obj->$val=GETPOST($val);
            }
        }

        $tmpaction = 'create';
        $parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
        $reshook=$hookmanager->executeHooks('createDictionaryFieldlist',$parameters, $obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
        $error=$hookmanager->error; $errors=$hookmanager->errors;

        if (empty($reshook)) fieldList($fieldlist,$obj);

        print '<td colspan="3" align="right"><input type="submit" class="button" name="actionadd" value="'.$langs->trans("Add").'"></td>';
        print "</tr>";

        if (! empty($alabelisused))  // Si un des champs est un libelle
        {
            print '<tr><td colspan="'.(count($fieldlist)+2).'">* '.$langs->trans("LabelUsedByDefault").'.</td></tr>';
        }
        print '<tr><td colspan="'.(count($fieldlist)+2).'">&nbsp;</td></tr>';
    }

    print '</form>';

    // List of available values in database
    dol_syslog("htdocs/admin/dict sql=".$sql, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var=true;
        if ($num)
        {
            // There is several pages
            if ($num > $listlimit)
            {
                print '<tr class="none"><td align="right" colspan="'.(3+count($fieldlist)).'">';
                print_fleche_navigation($page,$_SERVER["PHP_SELF"],'&id='.$id,($num > $listlimit),$langs->trans("Page").' '.($page+1));
                print '</td></tr>';
            }

            // Title of lines
            print '<tr class="liste_titre">';
            foreach ($fieldlist as $field => $value)
            {
                // Determine le nom du champ par rapport aux noms possibles
                // dans les dictionnaires de donnees
                $showfield=1;							  	// Par defaut
                $align="left";
                $sortable=1;
                $valuetoshow='';
                $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
                $valuetoshow=$langs->trans($valuetoshow);   // try to translate

                if ($fieldlist[$field]=='ordre')            { $valuetoshow=$langs->trans("ordre"); }
                if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
                if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') { $valuetoshow=$langs->trans("Label")."*"; }
                if ($fieldlist[$field]=='sortorder')       { $valuetoshow=$langs->trans("SortOrder"); }
                // Affiche nom du champ
                if ($showfield)
                {
                    print getTitleFieldOfList($valuetoshow,0,$_SERVER["PHP_SELF"],($sortable?$fieldlist[$field]:''),($page?'page='.$page.'&':'').'&id='.$id,"","align=".$align,$sortfield,$sortorder);
                }
            }
            print getTitleFieldOfList($langs->trans("Status"),0,$_SERVER["PHP_SELF"],"active",($page?'page='.$page.'&':'').'&id='.$id,"",'align="center"',$sortfield,$sortorder);
            print '<td colspan="2"  class="liste_titre">&nbsp;</td>';
            print '</tr>';
			//print $sql;
			
			
            // Lines with values
            while ($i < $num)
            {
                $var = ! $var;

                $obj = $db->fetch_object($resql);
	
                //print_r($obj);
                print '<tr '.$bc[$var].' id="rowid-'.$obj->rowid.'">';
                if ($action == 'edit' && ($rowid == (! empty($obj->rowid)?$obj->rowid:$obj->code)))
                {
                    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
                    print '<input type="hidden" name="token" value="'.newtoken().'">';
                    print '<input type="hidden" name="page" value="'.$page.'">';
                    print '<input type="hidden" name="rowid" value="'.$rowid.'">';

                    $tmpaction='edit';
                    $parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
                    $reshook=$hookmanager->executeHooks('editDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
                    $error=$hookmanager->error; $errors=$hookmanager->errors;

                    if (empty($reshook)) fieldList($fieldlist,$obj,$tabname[$id]);

                    print '<td colspan="3" align="right"><a name="'.(! empty($obj->rowid)?$obj->rowid:$obj->code).'">&nbsp;</a><input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
                    print '&nbsp;<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'"></td>';
                }
                else
                {
	              	$tmpaction = 'view';
                    $parameters=array('var'=>$var, 'fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
                    $reshook=$hookmanager->executeHooks('viewDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks

                    $error=$hookmanager->error; $errors=$hookmanager->errors;

                    if (empty($reshook))
                    {
                        foreach ($fieldlist as $field => $value)
                        {
                            $showfield=1;
                        	$align="left";
                            $valuetoshow=$obj->$fieldlist[$field];
							if ($valuetoshow=='all') {
                                $valuetoshow=$langs->trans('All');
                            }
                            else if ($fieldlist[$field]=='recuperableonly' || $fieldlist[$field]=='fdm' || $fieldlist[$field] == 'deductible') {
                                $valuetoshow=yn($valuetoshow);
                                $align="center";
                            }

							// Show value for field
							if ($showfield) print '<td align="'.$align.'">'.$valuetoshow.'</td>';
                        }
                    }

                    // Est-ce une entree du dictionnaire qui peut etre desactivee ?
                    // True by default
                    $iserasable=1;

                    if (isset($obj->code))
                    {
                    	if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i',$obj->code))) $iserasable = 0;
                    	else if ($obj->code == 'RECEP') $iserasable = 0;
                    	else if ($obj->code == 'EF0') $iserasable = 0;
                    }

                    if (isset($obj->type) && in_array($obj->type, array('system', 'systemauto'))) $iserasable=0;

                    $url = $_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(! empty($obj->rowid)?$obj->rowid:(! empty($obj->code)?$obj->code:'')).'&amp;code='.(! empty($obj->code)?$obj->code:'').'&amp;id='.$id.'&amp;';

                    // Active
                    print '<td align="center" class="nowrap">';
                    if ($iserasable) print '<a href="'.$url.'action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
                    else
                 	{
                  		if (isset($obj->type) && in_array($obj->type, array('system', 'systemauto')) && empty($obj->active)) print $langs->trans("Deprecated");
                    	else print $langs->trans("AlwaysActive");
                    }
                    print "</td>";

                    // Modify link
                    if ($iserasable) print '<td align="center"><a href="'.$url.'action=edit#'.(! empty($obj->rowid)?$obj->rowid:(! empty($obj->code)?$obj->code:'')).'">'.img_edit().'</a></td>';
                    else print '<td>&nbsp;</td>';

                    // Delete link
                    if ($iserasable) print '<td align="center"><a href="'.$url.'action=delete">'.img_delete().'</a></td>';
                    else print '<td>&nbsp;</td>';

                    print "</tr>\n";
                }
                $i++;
            }
        }
    }
    else {
        dol_print_error($db);
    }

    print '</table>';

    print '</form>';


print '<br>';


llxFooter();
$db->close();


/**
 *	Show field
 *
 * 	@param		array	$fieldlist		Array of fields
 * 	@param		Object	$obj			If we show a particular record, obj is filled with record fields
 *  @param		string	$tabname		Name of SQL table
 *	@return		void
 */
function fieldList($fieldlist,$obj='',$tabname='')
{
	global $conf,$langs,$db;
	global $form;
	global $region_id;
//	global $sourceList,$elementList;
	global $localtax_typeList;

	$formadmin = new FormAdmin($db);
	$formcompany = new FormCompany($db);

	foreach ($fieldlist as $field => $value)
	{
		if ($fieldlist[$field] == 'nomservice') {
			print '<td>';
			print $form->select_produits_list((! empty($obj->country_code)?$obj->refservice:(! empty($obj->nomservice)?$obj->nomservice:'')),'nomservice');
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'recuperableonly' || $fieldlist[$field] == 'fdm' || $fieldlist[$field] == 'deductible') {
			print '<td>';
			print $form->selectyesno($fieldlist[$field],(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:''),1);
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'code' && isset($obj->$fieldlist[$field])) {
			print '<td><input type="text" class="flat" value="'.(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'" size="10" name="'.$fieldlist[$field].'"></td>';
		}
		elseif ($fieldlist[$field] == 'ordre' && isset($obj->$fieldlist[$field]))
		{
			print '<td><input type="text" class="flat" value="'.(! empty($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'" size="10" name="'.$fieldlist[$field].'"></td>';
		}
		else
		{
			print '<td>';
			$size='10';
			print '<input type="text" '.$size.' class="flat" value="'.(isset($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
	}
}

