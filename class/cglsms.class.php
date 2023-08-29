<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 -CigaleAventure and claude@cigaleaventure.com---
 *
 * ATTENTION, la gestion des action d'un formateur-moniteur n'est pas valide
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
 *   	\file       custum/cglinscription/class/cglsms.class.php
 *		\ingroup    cglinscription
 *		\brief      Objet permettant le rapatriement des données de Dolibarr vers Sms
 */

 /**************************/
 
	require_once DOL_DOCUMENT_ROOT.'/custom/cglinscription/class/bulletin.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	
/**
 *	Put here description of your class
 */
class CglSms
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='CglStripe';			//!< Id that identify managed objects
	var $table_element='cglinscription_bull_det';		//!< Name of table without prefix where object is stored

    var $id;
    var $prop1;
    var $prop2;
	var $FormInscription;
	
	//...

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }
	public function getSmsTemplate($db, $type_template, $user, $outputlangs, $id = 0, $active = 1, $label = '')
	{
        $ret = new ModelMail();

		if ($id == -2 && empty($label)) {
			$this->error = 'LabelIsMandatoryWhenIdIs-2';
			return -1;
		}

		$languagetosearch = (is_object($outputlangs) ? $outputlangs->defaultlang : '');
		// Define $languagetosearchmain to fall back on main language (for example to get 'es_ES' for 'es_MX')
		$tmparray = explode('_', $languagetosearch);
		$languagetosearchmain = $tmparray[0].'_'.strtoupper($tmparray[0]);
		if ($languagetosearchmain == $languagetosearch) $languagetosearchmain = '';

		$sql = "SELECT rowid, label, content, content_lines, lang";
		$sql.= " FROM ".MAIN_DB_PREFIX.'c_sms_templates';
		$sql.= " WHERE (type_template='".$this->db->escape($type_template)."' OR type_template='all')";
		$sql.= " AND entity IN (1)";
		$sql.= " AND (private = 0 OR fk_user = ".$user->id.")";				// Get all public or private owned
		if ($active >= 0) $sql.=" AND active = ".$active;
		if ($label) $sql.=" AND label ='".$this->db->escape($label)."'";
		if (! ($id > 0) && $languagetosearch) $sql.= " AND (lang = '".$this->db->escape($languagetosearch)."'".($languagetosearchmain ? " OR lang = '".$this->db->escape($languagetosearchmain)."'" : "")." OR lang IS NULL OR lang = '')";
		if ($id > 0)   $sql.= " AND rowid=".$id;
		if ($id == -1) $sql.= " AND position=0";
		if ($languagetosearch) $sql.= $this->db->order("position,lang,label", "ASC,DESC,ASC");		// We want line with lang set first, then with lang null or ''
		else $sql.= $this->db->order("position,lang,label", "ASC,ASC,ASC");		// If no language provided, we give priority to lang not defined
		$sql.= $this->db->plimit(1);
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			// Get first found
			$obj = $this->db->fetch_object($resql);

			if ($obj) {
				$ret->id = $obj->rowid;
				$ret->label = $obj->label;
				$ret->lang = $obj->lang;
				$ret->content = $obj->content;
				$ret->content_lines = $obj->content_lines;
			}
			elseif($id == -2) {
				// Not found with the provided label
				return -1;
			}
			else {	// If there is no template at all
				$defaultmessage='';
				if ($type_template=='body')							{ $defaultmessage=$this->withbody; }		// Special case to use this->withbody as content
				elseif ($type_template=='facture_send')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendInvoice"); }
				elseif ($type_template=='facture_relance')			{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendInvoiceReminder"); }
				elseif ($type_template=='propal_send')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendProposal"); }
				elseif ($type_template=='supplier_proposal_send')	{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierProposal"); }
				elseif ($type_template=='order_send')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendOrder"); }
				elseif ($type_template=='order_supplier_send')		{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierOrder"); }
				elseif ($type_template=='invoice_supplier_send')	{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierInvoice"); }
				elseif ($type_template=='shipping_send')			{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendShipping"); }
				elseif ($type_template=='fichinter_send')			{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendFichInter"); }
				elseif ($type_template=='thirdparty')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentThirdparty"); }
				elseif ($type_template=='user')				        { $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentUser"); }
				elseif (!empty($type_template))				        { $defaultmessage=$outputlangs->transnoentities("PredefinedMailContent".ucfirst($type_template)); }

				$ret->label = 'default';
				$ret->lang = $outputlangs->defaultlang;
				$ret->content = $defaultmessage;
				$ret->content_lines ='';
			}

			$this->db->free($resql);
			return $ret;
		}
		else
		{
			dol_print_error($db);
			return -1;
		}
	}


} // fin de classe


