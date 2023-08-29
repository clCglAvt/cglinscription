<?php
/*
 * 
 */

/**
 * \file cglinscription/class/cglGeneral.class.php
 * \ingroup cglinscription
 * \brief Fonction générale
 */

require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
/**
 * Location Class
 */
class General {
	var $db;
	var $error;
	var $errors = array ();
	var $element = '';
	var $table_element = '';

	
	/**
	 * Constructor
	 *
	 */
	function __construct($db)
    {
        $this->db = $db;
	}
	
	/**
	 * Create object into database
	 *
	 * @param User $user that create
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	function init_close()
	{
		global  $closeAbandon, $arrayreasons, $langs;
		/* tableau constitutifs des raison d'abandon */
			
		$form = new Form($db);
		// Code
		$closeAbandon [1] ['code'] = 'anulactivite';
		$closeAbandon [2] ['code'] = 'badcustomer';
		$closeAbandon [3] ['code'] = 'abandon';
		// Help
		$closeAbandon [1] ['label'] = $langs->trans("ConfirmClassifyAbandonActiviteDesc");
		$closeAbandon [2] ['label'] = $langs->trans("ConfirmClassifyBullPartiallyReasonBadCustomerDesc");
		$closeAbandon [3] ['label'] = $langs->trans("ConfirmClassifyAbandonReasonOtherDesc");
		// texte stocke
		$closeAbandon [1] ['origine'] = $langs->trans("ConfirmClassifyAbandonActivite");
		$closeAbandon [2] ['origine'] = $langs->trans("ConfirmClassifyBullPartiallyReasonBadCustomer");
		$closeAbandon [3] ['origine'] = $langs->trans("ConfirmClassifyAbandonReasonOther");
		// Texte
		$closeAbandon [1] ['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyAbandonActivite"), $closeAbandon [1] ['label'], 1);
		$closeAbandon [2] ['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyBullPartiallyReasonBadCustomer", $object->ref), $closeAbandon [2] ['label'], 1);
		$closeAbandon [3] ['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyAbandonReasonOther"), $closeAbandon [3] ['label'], 1);
		// arrayreasons
		$arrayreasons [$closeAbandon [1] ['code']] = $closeAbandon [1] ['reason'];
		$arrayreasons [$closeAbandon [2] ['code']] = $closeAbandon [2] ['reason'];
		$arrayreasons [$closeAbandon [3] ['code']] = $closeAbandon [3] ['reason'];
	}//init_close
} // Classe General
?>