<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * CCA 2014 <claude@cigaleaventure.com>
 *AnalyseRemi
 * Version CAV - 2.7 - été 2022
 *					 - Migration Dolibarr V15
 *					 - intégration liste des moniteurs dans cglinscription
 *					 - intégration trois menus des Accompte avec Remise, Analyse des Remboursement, Analyse des remises
 *					 - intégration un export Acompte avec remboursement sur compte bancaire autre que Stripe
 * Version CAV - 2.8 - hiver 2023 	- suppression de l'appel à réservation
 *									- ajout Liste Conflit Location Velo
 *									- ajout export liste des tiers en vue campgagne de communication
 * Version CAV - 2.8.5 - printemps 2023
 *		- vérification à la volée des conflit de vélo  (308b)
 *			- absence des bulletin d'un départ si celui-ci n'a pas de moniteur (bug 325) 
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
 *  \defgroup   cglinscription Module Inscription pour boutique d'activités sportives
 *  \brief      Descripteur de moduledescriptor.
 *  \file       htdocs/cglinscription/core/modules/modcglinscription.class.php
 *  \ingroup    cglinscription
 *  \brief      Fichier de Description and d'activation file pour le module d'inscriptions activités sprotives en boutique
 *              version 1.0 mars 2014
 *              version 1.1 dec 2014 - adaptation version Dolibarr 3.6 
 *              version 1.2 mars 2015 - location 
 *              version 2.0 mars 2018 - adaptation version Dolibarr 8.0.3
 *              version 2.1  2018 	 - hébergement SiteGround
 *              version 2.2 mars 2018 - migration version Dolibarr 10.0.1
 *				version 2.5 - mars 2021 -  Prise en main par Marie - 
 *				version 2.6 - juin 2021 -   migration version Dolibarr 12.0.5
 *				version 2.6.1 - mars 2022 -   migration version Dolibarr 12.0.5
 
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module MyModule
 */
class Modcglinscription extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;


		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
	$this->numero = 875120;
		// Key text used to identify module (for permissions, menus, etc...)
	$this->rights_class = 'cglinscription';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
	$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
	$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
	$this->description = "Description du module Inscription/Location Activités sportives";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
	$this->version = '2.8.5';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
	$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
	$this->special = 2;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
	$this->picto='cglinscription@cglinscription';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /mymodule/core/modules/barcode)
		// for specific css file (eg: /mymodule/css/mymodule.css.php)
		//$this->module_parts = array(
		//                        	'triggers' => 0,                           // Set this to 1 if module has its own trigger directory (core/triggers)
		//					'login' => 0,                              // Set this to 1 if module has its own login method directory (core/login)
		//					'substitutions' => 0,                      // Set this to 1 if module has its own substitution function file (core/substitutions)
		//					'menus' => 0,                              // Set this to 1 if module has its own menus handler directory (core/menus)
		//					'theme' => 0,                              // Set this to 1 if module has its own theme directory (core/theme)
		//                        	'tpl' => 0,                                // Set this to 1 if module overwrite template dir (core/tpl)
		//					'barcode' => 0,                            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
		//					'models' => 0,                             // Set this to 1 if module has its own models directory (core/modules/xxx)
		//					'css' => array('/mymodule/css/mymodule.css.php'),	// Set this to relative path of css file if module has its own css file
	 	//					'js' => array('/mymodule/js/mymodule.js'),   // Set this to relative path of js file if module must load a js on all pages
		//					'hooks' => array('hookcontext1','hookcontext2')  // Set here all hooks context managed by module
		//					'dir' => array('output' => 'othermodulename'),   // To force the default directories names
		//					'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2'=>array('enabled'=>'! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto'=>'yourpicto@mymodule')) // Set here all workflow context managed by module
		//                        );
	$this->module_parts = array('models' => 1
						,'triggers' => 1
						, 'hooks' => array('admin','searchform')
						, 'css' => array('/cglinscription/css/cglinscription.css')
						, 'substitutions' => 1	
	);
	// Data directories to create when module is enabled.
	// Example: this->dirs = array("/mymodule/temp");
	$this->dirs = array('/cglinscription');

	// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
	$this->config_page_url = array('cglinscription.php@cglinscription');

		// Dependencies
//CCA
	//$this->depends = array('modSociete','modCommande','modFacture', 'modBanque', 'modFournisseur', 'modService','modAgenda', 'modAgefodd');	
	// List of modules id that must be enabled if this module is enabled
	$this->depends = array('modSociete','modCommande','modFacture', 'modBanque', 'modFournisseur', 'modService','modAgenda', 'modAgefodd');	
// Fin CCA	//$this->requiredby = array('modCashDesk');		// List of modules id to disable if this one is disabled
	$this->phpmin = array(5,0);		// Minimum version of PHP required by module
	$this->need_dolibarr_version = array(3,6);	// Minimum version of Dolibarr required by module
	$this->langfiles = array("cglinscription@cglinscription");

		// Constantes
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();
		
		// Constants
		$r = 0;
		
		$r ++;
		$this->const [$r] [0] = "CGL_TYPEENT_ID_PARTICULIER";
		$this->const [$r] [1] = "";
		$this->const [$r] [2] = '8';
		$this->const [$r] [3] = 'Type de Tiers de type Particulier (creer par Inscrire)';
		$this->const [$r] [4] = 1;
		$this->const [$r] [5] = 0;

		$r ++;
		$this->const [$r] [0] = "CGL_STAG_INCONNU";
		$this->const [$r] [1] = "";
		$this->const [$r] [2] = '543';
		$this->const [$r] [3] = 'Reference du stagiaire Participant Inconnu permettant la pre-inscription ';
		$this->const [$r] [4] = 1;
		$this->const [$r] [5] = 0;
		
		$r ++;
		$this->const [$r] [0] = "CGL_NOM_LOCATION";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'cglinscription/location';
		$this->const [$r] [3] = 'Nom de la page de saisie du contrat ';
		$this->const [$r] [4] = 1;
		$this->const [$r] [5] = 0;

		
		$r ++;
		$this->const [$r] [0] = "CGL_NOM_INSCRIPTION";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'cglinscription/inscription';
		$this->const [$r] [3] = 'Nom de la page de saisie du bulletin ';
		$this->const [$r] [4] = 1;
		$this->const [$r] [5] = 0;

		
		$r ++;
		$this->const [$r] [0] = "CGL_NOM_FACTURATION";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'cglinscription/facturation';
		$this->const [$r] [3] = 'Nom de la page de facturation ';
		$this->const [$r] [4] = 1;
		$this->const [$r] [5] = 0;

		// Array de nouvelles pages dans des onglets existants
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  	// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:mylangfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2
        //                              'objecttype:-tabname':NU:conditiontoremove);                                                     						// To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'order_supplier'   to add a tab in supplier order view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'stock'            to add a tab in stock view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view
		// 'user'             to add a tab in user view
		// 'group'            to add a tab in group view
		// 'contact'          to add a tab in contact view
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        $this->tabs = array(
			'thirdparty:+tabactivite:TiActivites:cglinscription@cglinscription:1:/custom/cglinscription/listactivite.php?id=__ID__'
		);

        // Dictionnaries
	    if (! isset($conf->cglinscription->enabled))
        {
        	$conf->cglinscription=new stdClass();
        	$conf->cglinscription->enabled=0;
        }
		$this->dictionnaries=array();
        /* Example:
        if (! isset($conf->cglinscription->enabled)) $conf->cglinsription->enabled=0;	// This is to avoid warnings
        $this->dictionnaries=array(
            'langs'=>'cglinscription@cglinscription',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionnary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->mymodule->enabled,$conf->mymodule->enabled,$conf->mymodule->enabled)												// Condition to show each dictionnary
        );
        */
       if (! isset($conf->cglinscription->enabled)) $conf->cglinscription->enabled=0;	// This is to avoid warnings
        $this->dictionnaries=array(
            'langs' => 'cglinscription',
            'tabname'=>array(
					MAIN_DB_PREFIX."cgl_c_raison_remise",
					MAIN_DB_PREFIX."cgl_c_stresa"	// statut resa
//					,MAIN_DB_PREFIX."cgl_c_poids"
//					,MAIN_DB_PREFIX."cgl_c_caution"	// type de pièce confirmant la caution
					),
		// List of tables we want to see into dictonnary editor
            'tablib'=>array(
					"DictTab2Raisonremise",
					"DictTab3EtatResa" 
//					,"DictTablPoids", 
//					,"DictTab5PieceContrepartie"
					),				// Label of tables
            'tabsql'=>array(
					'SELECT rowid, f.libelle, f.fl_type, f.fk_produit, f.ordre, f.active  FROM '.MAIN_DB_PREFIX.'cgl_c_raison_remise as f',
					'SELECT rowid, f.libelle, f.ordre, f.active  FROM '.MAIN_DB_PREFIX.'cgl_c_stresa as f'
//					,'SELECT rowid, f.code, f.libelle, f.ordre, f.active  FROM '.MAIN_DB_PREFIX.'cgl_c_poids as f'
//					,'SELECT rowid, f.libelle, f.ordre, f.code, f.active  FROM '.MAIN_DB_PREFIX.'cgl_c_caution as f'
					),	// Request to select fields
            'tabsqlsort'=>array(
					"ordre ASC", 
					"ordre ASC" 
//					,"ordre ASC" 
//					,"ordre ASC"
					),			// Sort order
            'tabfield'=>array( 
					"libelle,fl_type,fk_produit,ordre", 
					"libelle,ordre" 
//					,"code,libelle,ordre"
//					,"code,libelle,ordre"					
					),		// List of fields (result of select to show dictionnary)
            'tabfieldvalue'=>array(
					"libelle,fl_type,fk_produit,ordre", 
					"libelle,ordre"									
//					,"code,libelle,ordre" 
//					,"code,libelle,ordre"	
					),	// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array(
					"libelle,fl_type,fk_produit,ordre", 
					"libelle,ordre"
//					,"code,libelle,ordre" 
//					,"code,libelle,ordre"	
					), // List of fields (list of fields for insert)
            'tabrowid'=>array(
					"rowid", 
					"rowid"
//					"rowid", 
//					"rowid"
					),	// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array(
					$conf->cglinscription->enabled, 
					$conf->cglinscription->enabled, 
					//$conf->cglinscription->enabled, 
					//$conf->cglinscription->enabled
					)	// Condition to show each dictionnary
        );

		
        // Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
		// Example:
		//$this->boxes=array(array(0=>array('file'=>'myboxa.php','note'=>'','enabledbydefaulton'=>'Home'),1=>array('file'=>'myboxb.php','note'=>''),2=>array('file'=>'myboxc.php','note'=>'')););

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
	
		$this->rights[$r][0] = 875121; 		// Permission id (must not be already used)
		$this->rights[$r][1] = 'BullettinBoutique';		// Permission label
		$this->rights[$r][3] = 1; 		// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'lire';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		 $r++;
		$this->rights[$r][0] = 875122; 		// Permission id (must not be already used)
		$this->rights[$r][1] = 'InscrireBoutique';		// Permission label
		$this->rights[$r][3] = 0; 		// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'inscrire';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)



		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus
		//
		// Example to declare a new Top Menu entry and its Left menu entry:
		// $this->menu[$r]=array('fk_menu'=>0,			                // Put 0 if this is a top menu
		//				'type'=>'top',			                // This is a Top menu entry
		//				'titre'=>'MyModule top menu',
		//				'mainmenu'=>'mymodule',
		//				'leftmenu'=>'mymodule',
		//				'url'=>'/mymodule/pagetop.php',
		//				'langs'=>'mylangfile@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//				'position'=>100,
		//				'enabled'=>'$conf->mymodule->enabled',	// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
		//				'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//				'target'=>'',
		//				'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		// $r++;
		//
		// Example to declare a Left Menu entry into an existing Top menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=xxx',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		//							'type'=>'left',			                // This is a Left menu entry
		//							'titre'=>'MyModule left menu',
		//							'mainmenu'=>'xxx',
		//							'leftmenu'=>'mymodule',
		//							'url'=>'/mymodule/pagelevel2.php',
		//							'langs'=>'mylangfile@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'enabled'=>'$conf->mymodule->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//							'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		// $r++;
		$this->menu[$r]=array(	'fk_menu'=>0,
			'type'=>'top',
			'titre'=>'TiInscription',
			'mainmenu'=>'CglInscription',
			'leftmenu'=>'0',
			'url'=>'/custom/cglinscription/list.php?tf=&idmenu=160',
			'langs'=>'cglinscription@cglinscription',
			'position'=>9001,
		'enabled'=>'$conf->cglinscription->enabled',
		//'perms'=>'$user->rights->cglinscription->InscrireBoutique',
		'target'=>'',
		'user'=>0 );
		
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuLstBulletin',
			'leftmenu'=>'CglInscription01',
			'url'=>'/custom/cglinscription/list.php?tg=4&idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>110,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);			                // 0=Menu for internal users, 1=external users, 2=both

		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription01',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiNewInsc',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/inscription.php?idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>111,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
			
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription01',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuLstBulletin',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/list.php?th=3&idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>113,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription01',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuFeuilleRoute',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/listedepart.php?idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>114,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
		
		
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'GestInscription',
			'leftmenu'=>'CglInscription03',
			'mainmenu'=>'CglInscription',
			'url'=>'',
			'langs'=>'cglinscription@cglinscription',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>130,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
				
/*Modif 2018
		$r++;			
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription03',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'LstDeparts',
			'mainmenu'=>'CglInscription',
			'url'=>'/cglinscription/listedepart.php?idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>131,
		//	'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
						
*/	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription03',
			'type'=>'left',
			'titre'=>'AgfMenuSessNew',
			'url'=>'/cglinscription/fichedepart.php?action=create&total=oui',
			'langs'=>'agefodd@agefodd',
			'mainmenu'=>'CglInscription',
			'position'=>133,
			'enabled'=>'$conf->cglinscription->enabled',
			//'perms'=>'$user->rights->agefodd->creer',
			'target'=>'',
			'user'=>0);		
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription03',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiCatProd',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/listproduit.php?idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>134,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
		$r ++;
		$this->menu [$r] = array (	'fk_menu' => 'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription03',	
				'type' => 'left',
				'titre' => 'CglMenuNvProduit',
			'mainmenu'=>'CglInscription',
				'url'=>'/custom/cglinscription/fiche_produit.php?action=create',
				'langs' => 'cglinscription@cglinscription',
				'position' => 135,
				'enabled' => '$conf->agefodd->enabled',
				//'perms' => '$user->rights->agefodd->creer',
				'target' => '',
				'user' => 0 
		);
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription03',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiCatServices',
			'mainmenu'=>'CglInscription',
			'url'=>'/product/list.php?idmenu=160&type=1',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>136,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);			
				
				
		
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription03',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiGestSites',
			'mainmenu'=>'CglInscription',
			'url'=>'/cglinscription/listesite.php?idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>137,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);			
		
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription03',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiGestMoniteur',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/listmoniteur.php?idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>138,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);			
				
				
				
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription03',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'LstDepAnt',
			'mainmenu'=>'CglInscription',
			'url'=>'/cglinscription/listedepart.php?idmenu=160&type=passe',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>139,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);		
		$r++;
/*		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'GestLocation',
			'leftmenu'=>'CglInscription04',
			'mainmenu'=>'CglInscription',
			'url'=>'',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>140,
		//	'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription04',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'LstRando',
			'mainmenu'=>'CglInscription',
			'url'=>'/cglinscription/gestion.php?id=1',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>141,
		//	'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
				
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription04',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'LstMatMad',
			'mainmenu'=>'CglInscription',
			'url'=>'/cglinscription/gestion.php?id=2',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>142,
		//	'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
			
		$r++;
*/
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiCompta',
			'leftmenu'=>'CglInscription09',
			'mainmenu'=>'CglInscription',
			'url'=>'',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>190,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
						
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription09',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'FacturationIns',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/facturation.php?ecran=facture&type=Insc',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>191,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
						
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription09',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'FacturationMoniteur',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/facturMoniteur.php?idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>193,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);		
						
				                // 0=Menu for internal users, 1=external users, 2=both

					
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription09',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'ArchivagIns',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/facturation.php?idmenu=160&ecran=archive&type=Insc',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>196,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both

		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription09',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenArchive',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/facturation.php?idmenu=160&ecran=archivestock&type=Insc',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>197,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiVerifiAnalRemise',
			'leftmenu'=>'CglInscription29',
			'mainmenu'=>'CglInscription',
			'url'=>'',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>900,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both

		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		 
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuVerifTVAFAC',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/verification.php?idmenu=160&test=TestFactErrTVA',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>902,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both

		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		 
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuVerifDetTVAFAC',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/verification.php?idmenu=160&test=TestFactDetErrTVA',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>903,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
								
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuTestLOErrTVA',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/verification.php?idmenu=160&test=TestLOErrTVA',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>904,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
							
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuTVACompteGestion',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/verification.php?idmenu=160&test=TestErrTVACompteGestion',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>905,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
						
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuTestANCVCorr',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/verification.php?idmenu=160&test=TestEcrANCVRapp',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>906,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);	
						
		
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuTestFactPayee',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/verification.php?idmenu=160&test=TestFactPayee',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>907,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);	
						
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenuTestRecupStripe',
			'mainmenu'=>'CglInscription',
			'url'=>'/custom/cglinscription/verificationstripe.php?idmenu=160&test=TestRecupEcrituresStripe',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>908,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				
		
		
		$r++;
		$this->menu[$r] = array(
			'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',
			'titre' => 'AccompteAvecRemb',
			'url' => '/custom/cglinscription/verifRembNonStripe.php',
			'langs' => 'cglinscription',
			'position' => 910,
			'enabled' => '$conf->stripe->enabled && $conf->banque->enabled && $conf->global->MAIN_FEATURES_LEVEL >= 1',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);

		$r++;
		$this->menu[$r] = array(
			'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',
			'titre' => 'AnalyseRemboursement',
			'url' => '/custom/cglinscription/verifAnalyseRemb.php',
			'langs' => 'cglinscription',
			'position' => 911,
			'enabled' => '$conf->stripe->enabled && $conf->banque->enabled && $conf->global->MAIN_FEATURES_LEVEL >= 1',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);
		
		$r++;
		$this->menu[$r] = array(
			'fk_menu'=>'fk_mainmenu=CglInscription,fk_leftmenu=CglInscription29',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',
			'titre' => 'AnalyseRemise',
			'url' => '/custom/cglinscription/verifAnalyseRemise.php',
			'langs' => 'cglinscription',
			'position' => 912,
			'enabled' => '$conf->stripe->enabled && $conf->global->MAIN_FEATURES_LEVEL >= 1',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);

		// MENUS Location
		
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>0,
			'type'=>'top',
			'titre'=>'CglLocation',
			'mainmenu'=>'CglLocation',
			'leftmenu'=>'0',
			//'url'=>'/custom/cglinscription/listeloc.php?idmenu=109',
			'url'=>'/cglinscription/listematloue.php?type=materiel&idmenu=160',
			'langs'=>'cglinscription@cglinscription',
			'position'=>109,
			'enabled'=>'$conf->cglinscription->enabled',
			//'perms'=>'$user->rights->cglinscription->InscrireBoutique',
			'target'=>'',
			'user'=>0 );

		
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TitreLocation',
			'leftmenu'=>'CglLocation02',
			'mainmenu'=>'CglLocation',
			'url'=>'/cglinscription/listeloc.php?idmenu=109',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>120,
		    'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation02',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'LstLoc',
			'mainmenu'=>'CglLocation',
			'url'=>'/cglinscription/listeloc.php?idmenu=109',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>121,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
			
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation02',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'NvContrat',
			'mainmenu'=>'CglLocation',
			'url'=>'/custom/cglinscription/location.php?idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>122,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		

		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation02',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'LstLocMat',
			'mainmenu'=>'CglLocation',
			'url'=>'/cglinscription/listematloue.php?type=materiel&idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>123,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation02',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'LstLocRet',
			'mainmenu'=>'CglLocation',
			'url'=>'/cglinscription/listeloc.php?type=retour&idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>124,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
	
	
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation02',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'LstResa',
			'mainmenu'=>'CglLocation',
			'url'=>'/cglinscription/resa.php?idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>125,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);		
		
		/*
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation02',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'LstMatExt',
			'mainmenu'=>'CglLocation',
			'url'=>'/cglinscription/listeloc.php?type=exterieur&idmenu=160',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>126,
		//	'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);	
		$r++;
		*/

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiCompta',
			'leftmenu'=>'CglLocation09',
			'mainmenu'=>'CglLocation',
			'url'=>'',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>200,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation09',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'FacturationLoc',
			'mainmenu'=>'CglLocation',
			'url'=>'/custom/cglinscription/facturation.php?idmenu=160&ecran=facture&type=Loc',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>201,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);		
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation09',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'ArchivageLoc',
			'mainmenu'=>'CglLocation',
			'url'=>'/custom/cglinscription/facturation.php?idmenu=160&ecran=archive&type=Loc',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>202,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both

				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation09',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenArchive',
			'mainmenu'=>'CglLocation',
			'url'=>'/custom/cglinscription/facturation.php?idmenu=160&ecran=archivestock&type=Loc',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>203,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
			
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiVerifi',
			'leftmenu'=>'CglLocation08',
			'mainmenu'=>'CglLocation',
			'url'=>'',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>280,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
		
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglLocation,fk_leftmenu=CglLocation08',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiConfLoc',
			'mainmenu'=>'CglLocation',
			'url'=>'/custom/cglinscription/listeconflitlocvelo.php?idmenu=280&ecran=facture&type=Loc',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>281,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);		

/*
		// MENUS Réservation
		
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>0,
			'type'=>'top',
			'titre'=>'CglResa',
			'mainmenu'=>'CglResa',
			'leftmenu'=>'0',
			'url'=>'/custom/cglinscription/listeresa.php?idmenu=219',
			'langs'=>'cglinscription@cglinscription',
			'position'=>219,
			'enabled'=>'$conf->cglinscription->enabled',
			//'perms'=>'$user->rights->cglinscription->InscrireBoutique',
			'target'=>'',
			'user'=>0 );

		
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglResa',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TitreReservation',
			'leftmenu'=>'CglResa02',
			'mainmenu'=>'CglResa',
			'url'=>'/cglinscription/listeresa.php?idmenu=219',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>220,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglResa,fk_leftmenu=CglResa02',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'TiListe',
			'mainmenu'=>'CglResa',
			'url'=>'/cglinscription/listeresa.php?idmenu=219',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>221,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
			
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglResa,fk_leftmenu=CglResa02',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'Nvresa',
			'mainmenu'=>'CglResa',
			'url'=>'/cglinscription/reservation.php?idmenu=219',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>222,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
			
				
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglResa',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'MenArchive',
			'leftmenu'=>'CglResa09',
			'url'=>'',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>230,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglResa,fk_leftmenu=CglResa09',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'ArchiveResa',
			'mainmenu'=>'CglResa',
			'url'=>'/custom/cglinscription/facturation.php?idmenu=160&ecran=archive&type=Resa',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>231,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both

				
		$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=CglResa,fk_leftmenu=CglResa09',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'ArchiveHistResa',
			'mainmenu'=>'CglResa',
			'url'=>'/custom/cglinscription/facturation.php?idmenu=160&ecran=archivestock&type=Resa',
			'langs'=>'cglinscription@cglinscription',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>232,
			'enabled'=>'$conf->cglinscription->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//	'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
*/
// Ajout de deux menus dans banques/Stripe
		$r++;
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=bank,fk_leftmenu=stripe',
			'type' => 'left',
			'titre' => 'StripeVerifPaiementNonPropag',
			'url' => '/custom/cglinscription/verifEncaissementStripe.php',
			'langs' => 'cglinscription',
			'position' => 1100,
			'enabled' => '$conf->stripe->enabled && $conf->banque->enabled && $conf->global->MAIN_FEATURES_LEVEL >= 1',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);
		$r++;
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=bank,fk_leftmenu=stripe',
			'type' => 'left',
			'titre' => 'StripeIntegrationVirFrais',
			'url' => '/custom/cglinscription/stripevir.php',
			'langs' => 'cglinscription',
			'position' => 1110,
			'enabled' => '$conf->stripe->enabled && $conf->banque->enabled && $conf->global->MAIN_FEATURES_LEVEL >= 1',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);
		$r++;


		// EXPORTS
		$r=0;
		

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        // $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		// $this->export_permission[$r]=array(array("facture","facture","export"));
		// $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.ref'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.ref'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $this->export_sql_order[$r] .=' ORDER BY s.nom';
		// $r++;
			
		// Session export

		$this->export_code [$r] = $this->rights_class . '_' . $r;
		$this->export_label [$r] = $langs->trans('ExportDataset_session');
		$this->export_icon [$r] = 'bill';
		$this->export_permission [$r] = array (
				array (
						"facture", 
						"facture", 
				) 
		);
		$this->export_fields_array [$r] = array (
				's.rowid' => 'Id',
				'CASE WHEN s.type_session=0 THEN \'Intra\' ELSE \'Inter\' END as type_session' => 'AgfFormTypeSession',
				's.dated' => 'AgfDateDebut',
				's.datef' => 'AgfDateFin',
				's.nb_stagiaire' => 'AgfNbreParticipants',
				's.notes' => 'AgfNote',
				's.cost_trainer' => 'AgfCoutFormateur',
				's.cost_site' => 'AgfCoutSalle',
				's.cost_trip' => 'AgfCoutDeplacement',
				's.sell_price' => 'AgfCoutFormation',
				'statusdict.code as sessionstatus' => 'AgfStatusSession',
				's.is_opca as sessionisopca' => 'AgfSubrocation',
				'socsessopca.nom as sessionsocopca' => 'AgfOPCAName',
				'contactsessopca.civility as contactsessopcaciv' => 'AgfOPCASessContactCiv',
				'contactsessopca.lastname as contactsessopcalastname' => 'AgfOPCASessContactFirstName',
				'contactsessopca.firstname as contactsessopcalastname' => 'AgfOPCASessContactLastName',
				'c.intitule' => 'AgfFormIntitule',
				'c.ref' => 'Ref',
				'c.ref_interne' => 'AgfFormCodeInterne',
				'c.duree' => 'AgfDuree',
				'dictcat.code as catcode ' => 'AgfTrainingCategCode',
				'dictcat.intitule as catlib' => 'AgfTrainingCategLabel',
				'product.ref' => 'ProductRef',
				'product.label' => 'ProductLabel',
				'product.price' => 'SellingPriceTTC',
				'product.accountancy_code_buy' => 'ProductAccountancySellCode',
				'p.ref_interne' => 'AgfLieu',
				'p.adresse' => 'Address',
				'p.cp' => 'Zip',
				'p.ville' => 'Town',
				'p_pays.label as country' => 'Country',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.civility ELSE fp.civility END as trainerciv' => 'AgfTrainerCiv',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.lastname ELSE fp.lastname END as trainerlastname' => 'AgfTrainerLastname',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.firstname ELSE fp.firstname END as trainerfirstname' => 'AgfTrainerCivFirstname',
				'trainerdicttype.intitule as trainertype' => 'AgfTrainerType',
				'so.nom as cust_name' => 'Customer',
				'sta.civility as traineeciv' => 'Agfcivility',
				'sta.nom as traineelastname' => 'AgfStaLastname',
				'sta.prenom as traineefirstname' => 'AgfStaFirstname',
				'ssdicttype.intitule as statype' => 'AgfStagiaireModeFinancement',
				's.is_opca as staisopca' => 'AgfSubrocation',
				'socstaopca.nom as stasocopca' => 'AgfOPCAName',
				'contactstaopca.civility as contactstaopcaciv' => 'AgfOPCAStaContactCiv',
				'contactstaopca.lastname as contactstaopcalastname' => 'AgfOPCAStaContactLastName',
				'contactstaopca.firstname as contactstaopcafirstname' => 'AgfOPCAStaContactFirstName' 
		);
		$this->export_TypeFields_array [$r] = array (
				's.rowid' => "Text" ,
				's.dated' => 'Date',
				's.datef' => 'Date',
		);
		$this->export_entities_array [$r] = array (
				's.rowid' => "Id",
				'CASE WHEN s.type_session=0 THEN \'Intra\' ELSE \'Inter\' END as type_session' => 'AgfSessionDetail',
				's.dated' => 'AgfSessionDetail',
				's.datef' => 'AgfSessionDetail',
				's.nb_stagiaire' => 'AgfSessionDetail',
				's.notes' => 'AgfSessionDetail',
				's.cost_trainer' => 'AgfSessionDetail',
				's.cost_site' => 'AgfSessionDetail',
				's.cost_trip' => 'AgfSessionDetail',
				's.sell_price' => 'AgfSessionDetail',
				'statusdict.code as sessionstatus' => 'AgfSessionDetail',
				's.is_opca as sessionisopca' => 'AgfSessionDetail',
				'socsessopca.nom as sessionsocopca' => 'AgfSessionDetail',
				'contactsessopca.civility as contactsessopcaciv' => 'AgfSessionDetail',
				'contactsessopca.lastname as contactsessopcalastname' => 'AgfSessionDetail',
				'contactsessopca.firstname as contactsessopcalastname' => 'AgfSessionDetail',
				'c.intitule' => 'AgfCatalogDetail',
				'c.ref' => 'AgfCatalogDetail',
				'c.ref_interne' => 'AgfCatalogDetail',
				'c.duree' => 'AgfCatalogDetail',
				'dictcat.code as catcode ' => 'AgfCatalogDetail',
				'dictcat.intitule as catlib' => 'AgfCatalogDetail',
				'product.ref' => 'Product',
				'product.label' => 'Product',
				'product.price' => 'Product',
				'product.accountancy_code_buy' => 'Product',
				'p.ref_interne' => 'AgfSessPlace',
				'p.adresse' => 'AgfSessPlace',
				'p.cp' => 'AgfSessPlace',
				'p.ville' => 'AgfSessPlace',
				'p_pays.label as country' => 'AgfSessPlace',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.civility ELSE fp.civility END as trainerciv' => 'AgfTeacher',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.lastname ELSE fp.lastname END as trainerlastname' => 'AgfTeacher',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.firstname ELSE fp.firstname END as trainerfirstname' => 'AgfTeacher',
				'trainerdicttype.intitule as trainertype' => 'AgfTeacher',
				'so.nom as cust_name' => 'AgfSessionDetail',
				'sta.civility as traineeciv' => 'AgfNbreParticipants',
				'sta.nom as traineelastname' => 'AgfNbreParticipants',
				'sta.prenom as traineefirstname' => 'AgfNbreParticipants',
				'ssdicttype.intitule as statype' => 'AgfNbreParticipants',
				's.is_opca as staisopca' => 'AgfNbreParticipants',
				'socstaopca.nom as stasocopca' => 'AgfNbreParticipants',
				'contactstaopca.civility as contactstaopcaciv' => 'AgfNbreParticipants',
				'contactstaopca.lastname as contactstaopcalastname' => 'AgfNbreParticipants',
				'contactstaopca.firstname as contactstaopcafirstname' => 'AgfNbreParticipants' 
		);
		
		$this->export_sql_start [$r] = 'SELECT DISTINCT ';
		$this->export_sql_end [$r] = ' FROM ' . MAIN_DB_PREFIX . 'agefodd_session as s';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_place as p ON p.rowid = s.fk_session_place';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as ss ON s.rowid = ss.fk_session_agefodd';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as sta ON sta.rowid = ss.fk_stagiaire';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire_type as ssdicttype ON ssdicttype.rowid = ss.fk_agefodd_stagiaire_type';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as so ON so.rowid = s.fk_soc';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_formateur as sf ON sf.fk_session = s.rowid';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formateur_type as trainerdicttype ON trainerdicttype.rowid = sf.fk_agefodd_formateur_type';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formateur as f ON f.rowid = sf.fk_agefodd_formateur';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as fu ON fu.rowid = f.fk_user';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as fp ON fp.rowid = f.fk_socpeople';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formation_catalogue_type as dictcat ON dictcat.rowid = c.fk_c_category';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_country as p_pays ON p_pays.rowid = p.fk_pays';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product as product ON product.rowid = c.fk_product';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as socsessopca ON socsessopca.rowid = s.fk_soc_opca';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as contactsessopca ON contactsessopca.rowid = s.fk_socpeople_opca ';		
		if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND contactsessopca.statut <> 0 ";
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_opca as staopca ON staopca.fk_session_agefodd=s.rowid AND (staopca.fk_soc_trainee=sta.fk_soc OR staopca.fk_session_trainee=ss.rowid)';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as socstaopca ON socstaopca.rowid = staopca.fk_soc_opca';
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as contactstaopca ON contactstaopca.rowid = staopca.fk_socpeople_opca '; 		
		if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND contactstaopca.statut <> 0 ";
		$this->export_sql_end [$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_status_type as statusdict ON statusdict.rowid = s.status';
		
	
		// Statistiques 4 saisons
		$r++;
		$this->export_code [$r] = $this->rights_class . '_' . $r;
		$this->export_label [$r] = $langs->trans('CglExportStatInscription');
		$this->export_icon [$r] = 'bill';
		$this->export_permission [$r] = array (
				array (	
						"facture", 
						"facture", 
						"export" 
				) 
		);
		$this->export_fields_array [$r] = array (
		
		'annee' => 'CglAnnee',
		'nom' => 'CglTiers', 
		'categorie'=>'CglCategorieDepart',
		"NomMoniteur" =>'CglNomMoniteur',
		'tva_assuj' => 'CglMontiAssujetiTVA',
		's_pourcent' => 'CglPourcMonit', 
		's_partmonit' => 'CglFixMonit',
		'CodeVentilMon' => 'CglCodeVentilMon',
		'dated' => 'CglDate',
		'semaine' => 'Cglsemaine',
		'Site' => 'CglLieu',
		'type_session' => 'CglTypeDepart', 
		'NbAdulte' => 'CglNbAdulte',
		'NbEnfant' => 'CglNbEnfant',
		'PrixVente' => 'CglPrixVente',
		"partmonitBrute" => 'CglPartMoniteurHT',
		'partmonitBruteAct' => 'CglCoutMonitHT',
		'MargeHT' => 'CglMargeHT',	
		'FactureDol'	 => 'CglFactureDol',
		'FactureMon'	 => 'CglFactureMon',
		'IdSession' => 'CglIdSession',
		'CodeVentilSession' => 'CglCodeVentilSession',
		'IdMonit' => 'CglIdMoniteur',
		'duree'	=> 'duree'
		);
		$this->export_TypeFields_array [$r] = array (
			"NomMoniteur" => 'Text' ,
			'nom' => "Text" ,
			'categorie'=>'Text'	,
			'Site' => 'Text',
			'semaine' => 'cglinscription',
			'dated' => 'Date',
			'annee' => 'Numeric',
			'FactureDol' => 'Text',
			'duree'	=> 'decimal'
		);
		$this->export_entities_array [$r] = array (		
			'IdSession' => 'cglinscription',
			'IdMonit' => 'facture',
			'nom' => 'company', 
			'categorie'=>'company'		,
			"NomMoniteur" =>'CglMonitDetail',
			'tva_assuj' => 'cglinscription',
			'semaine' => 'cglinscription',
			'duree' => 'cglinscription',
			'Site' => 'cglinscription',
			'type_session' => 'cglinscription', 
			'NbAdulte' => 'cglinscription',
			'NbEnfant' => 'cglinscription',
			'PrixVente' => 'cglinscription',
			'dated' => 'cglinscription',
			'annee' => 'cglinscription',
			'MargeHT' => 'cglinscription',
			's_pourcent' => 'CglMonitDetail', 
			's_partmonit' => 'CglMonitDetail',
			'CodeVentilMon' => 'CglMonitDetail', 
			'partmonitBrute' => 'cglinscription',
			"partmonitBruteAct" => 'cglinscription',
			'FactureDol'	 => 'cglinscription',
			'FactureMon'	 => 'cglinscription',
			'IdSession' => 'CglIdSession'		,
			'CodeVentilSession' => 'CglIdSession'
		);
		
			
		
		$this->export_sql_start [$r] = 'SELECT DISTINCT ';
		
		
		$this->export_sql_end [$r] = ' FROM ';
		$this->export_sql_end [$r] .= "(select s.rowid as IdSession, se.s_duree_act as duree, m.rowid as IdMonit, st.rowid as IdTiers, ";
		$this->export_sql_end [$r] .= "se.s_code_ventil as CodeVentilSession, m.ventilation_vente as CodeVentilMon, s.categorie, ";
		$this->export_sql_end [$r] .= "	case when isnull(IdUser) then NomContact else NomUser end 	as	NomMoniteur, ";
		$this->export_sql_end [$r] .= "	semaine, annee, ";
		$this->export_sql_end [$r] .= "p.ref_interne as Site,	s.type_session, NbAdulte,NbEnfant,PrixVente,  se.s_pourcent, se.s_partmonit, 
		s.dated ,nom, ";
		$this->export_sql_end [$r] .= "f.ref  as FactureDol, f.ref_supplier as FactureMon, 	case when isnull(se.s_TypeTVA) then  st.tva_assuj else se.s_TypeTVA end as tva_assuj, ";
		
		
		$this->export_sql_end [$r] .= "	case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0 ";
		$this->export_sql_end [$r] .= "else 	case when s_partmonit > 0    then  s_partmonit ";
		$this->export_sql_end [$r] .= "	else 	case when (isnull(s_partmonit) or s_partmonit = 0)  and se.s_TypeTVA = 1 then  PrixVente * s_pourcent / 100 ";
		$this->export_sql_end [$r] .= "	else case when (isnull(s_partmonit) or s_partmonit = 0)  and se.s_TypeTVA = 0  then PrixVente *  s_pourcent  / 100 ";				 
		$this->export_sql_end [$r] .= "end end end  end as partmonitBrute, ";
		

		$this->export_sql_end [$r] .= "	case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0 ";
		$this->export_sql_end [$r] .= "	else case when s_partmonit > 0  and se.s_TypeTVA = 1  then PrixVente /1.2 - s_partmonit ";
		$this->export_sql_end [$r] .= "	else case when s_partmonit > 0 and se.s_TypeTVA = 0  then (PrixVente - s_partmonit )/1.2  ";
		$this->export_sql_end [$r] .= "	else case when (isnull(s_partmonit) or s_partmonit = 0)  and se.s_TypeTVA = 1 then PrixVente * (1 - s_pourcent /100)  ";
		$this->export_sql_end [$r] .= "	else case when (isnull(s_partmonit) or s_partmonit = 0)  and se.s_TypeTVA = 0  then PrixVente * (1 - s_pourcent/100 ) / 1.2 ";
		$this->export_sql_end [$r] .= "	end end end end end as MargeHT,	 ";
		
		$this->export_sql_end [$r] .= "	case when se.s_partmonit > 0 ";
		$this->export_sql_end [$r] .= "		then se.s_partmonit";
		$this->export_sql_end [$r] .= "		else case when se.s_pourcent > 0";
		$this->export_sql_end [$r] .= "		then	se.s_pourcent*(select sum(qte * pu * (100-bd.rem)/100 ) from  	 ".MAIN_DB_PREFIX ."cglinscription_bull as b ";
		$this->export_sql_end [$r] .= "			LEFT JOIN	 ".MAIN_DB_PREFIX ."cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0";
		$this->export_sql_end [$r] .= "			where  bd.fk_activite = s.rowid  )/100";
		$this->export_sql_end [$r] .= "	end end   as  partmonitBruteAct,  ";
		
		$this->export_sql_end [$r] .= "case when se.s_partmonit > 0 				then se.s_partmonit
			else		case when se.s_pourcent > 0 				then
						(select sum(qte * pu * (100-bd.rem)/100 ) from  	llx_cglinscription_bull as b 
						LEFT JOIN	llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0
						where  bd.fk_activite = s.rowid  )/100
					end 			end   as PVAct";
			
		$this->export_sql_end [$r] .= " FROM  ".MAIN_DB_PREFIX ."agefodd_formateur  as m ";	
		
		
		$this->export_sql_end [$r] .= "LEFT JOIN ";
		$this->export_sql_end [$r] .= "		(select sp.rowid as IdContact, ";
		$this->export_sql_end [$r] .= "	concat(concat(sp.lastname, ' '),sp.firstname) as NomContact ";
		$this->export_sql_end [$r] .= "	from  ".MAIN_DB_PREFIX ."socpeople as sp ) as mi on IdContact = m.fk_socpeople";
		$this->export_sql_end [$r] .= "	LEFT JOIN ";
		$this->export_sql_end [$r] .= "		(select u.rowid as IdUser, concat(concat(u.lastname, ' '),u.firstname) as NomUser ";
		$this->export_sql_end [$r] .= "	FROM  ".MAIN_DB_PREFIX ."user as u ) as mc on IdUser = m.fk_user  ";
		$this->export_sql_end [$r] .= "LEFT JOIN  ".MAIN_DB_PREFIX ."agefodd_session_formateur as sm on m.rowid = sm.fk_agefodd_formateur  ";
		$this->export_sql_end [$r] .= " LEFT JOIN 	(select  s1.rowid, s1.type_session, ";		
		$this->export_sql_end [$r] .= ' 	s1.dated,s1.fk_session_place, DATE_FORMAT(s1.dated,"%u") as semaine, year(s1.dated) as annee, ';
		$this->export_sql_end [$r] .= ' (select sum(qte) from  	 '.MAIN_DB_PREFIX .'cglinscription_bull as b ';
		$this->export_sql_end [$r] .= ' 	LEFT JOIN	 '.MAIN_DB_PREFIX .'cglinscription_bull_det as bd ';
		$this->export_sql_end [$r] .= " on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0 ";
		$this->export_sql_end [$r] .= ' 	where  bd.fk_activite = s1.rowid and   (bd.age <= 12 or bd.age = 100)   and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )) )	as NbEnfant, ';
		$this->export_sql_end [$r] .= ' (select sum(qte) from  	 '.MAIN_DB_PREFIX .'cglinscription_bull as b ';
		$this->export_sql_end [$r] .= ' 	LEFT JOIN	 '.MAIN_DB_PREFIX .'cglinscription_bull_det as bd ';
		$this->export_sql_end [$r] .= " on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0 ";
		$this->export_sql_end [$r] .= ' 	where  bd.fk_activite = s1.rowid  and (bd.age > 12 or bd.age = 99) and bd.age <> 100 and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )) )	as NbAdulte, ';
		$this->export_sql_end [$r] .= ' (select sum(qte * pu * (100-bd.rem)/100 ) from  	 '.MAIN_DB_PREFIX .'cglinscription_bull as b ';
		$this->export_sql_end [$r] .= "	LEFT JOIN	 ".MAIN_DB_PREFIX ."cglinscription_bull_det as bd ";
		$this->export_sql_end [$r] .= " on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0 ";
		$this->export_sql_end [$r] .= ' where  bd.fk_activite = s1.rowid  and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )) )	as PrixVente , ';
		$this->export_sql_end [$r] .= ' (select max(label) ';
		$this->export_sql_end [$r] .= ' 		from   '.MAIN_DB_PREFIX .'cglinscription_bull as b '; 
		$this->export_sql_end [$r] .= ' 		LEFT JOIN  '.MAIN_DB_PREFIX .'cglinscription_bull_det as bd  on bd.fk_bull = b.rowid and bd.action not in ("S","X") and bd.type = 0 ';
		$this->export_sql_end [$r] .= ' 		LEFT JOIN  '.MAIN_DB_PREFIX .'societe as tiers  on b.fk_soc= tiers.rowid ';
		$this->export_sql_end [$r] .= ' 		LEFT JOIN  '.MAIN_DB_PREFIX .'categorie_societe as cstiers  on cstiers.fk_soc = tiers.rowid  ';
		$this->export_sql_end [$r] .= ' 		LEFT JOIN  '.MAIN_DB_PREFIX .'categorie as ctiers on ctiers.rowid = cstiers.fk_categorie ';
		$this->export_sql_end [$r] .= ' 		where bd.fk_activite = s1.rowid  ) as categorie ';
		
		$this->export_sql_end [$r] .= ' FROM  '.MAIN_DB_PREFIX .'agefodd_session as s1) as s  on s.rowid = sm.fk_session ';
		$this->export_sql_end [$r] .= ' LEFT JOIN    '.MAIN_DB_PREFIX .'agefodd_session_extrafields as se on se.fk_object = s.rowid ';
		$this->export_sql_end [$r] .= ' LEFT JOIN 	 '.MAIN_DB_PREFIX .'societe as st on  st.rowid = m.fk_soc  ';		
		$this->export_sql_end [$r] .= ' LEFT JOIN 	 '.MAIN_DB_PREFIX .'facture_fourn as f on f.rowid = se.s_fk_facture ';

		$this->export_sql_end [$r] .= ' LEFT JOIN	 '.MAIN_DB_PREFIX .'agefodd_place as p on s.fk_session_place = p.rowid ';
		$this->export_sql_end [$r] .= ') as TB ';
		$this->export_sql_end [$r] .= ' WHERE 1 = 1 ';		
		$r++;

		
		// Export Acompte avec remboursement sur compte bancaire autre que Stripe
		//$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='AccompteAvecRemboursement';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_enabled[$r]='1';                          // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		$this->export_permission[$r]=array(array("user","societe","facture","compta","export"));
		$this->export_fields_array[$r]=array(
			'f.ref'=>"RefAcompe",
			's.nom'=>'CompanyName',
			's.rowid'=>'IdCompany',
			'p.amount'=>'Montant',
			'cpt.rowid'=>'CompteBrancaire'
			);
			
		$this->export_TypeFields_array [$r] = array (
			"f.ref" => 'Text' ,
			's.nom' => "Text" ,
			's.rowid'=>'Numeric'	,
			'p.amount' => 'Decimal',
			'cpt.rowid' => 'Numeric'
		);
		$this->export_entities_array[$r]=array(
			'f.ref'=>"facture",
			's.nom'=>'company',
			's.rowid'=>'company',
			'p.amount'=>'payment',
			'cpt.rowid'=>'bank'
			);
		$this->export_sql_start[$r]='SELECT DISTINCT ';


		$this->export_sql_end[$r]  =' 
			select f.ref,  s.nom, s.rowid as IdTiers, p.amount, cpt.ref as label, cpt.rowid as IdCompte
			from llx_facture  as f , llx_paiement_facture as pf, llx_paiement as p, llx_bank as b, llx_societe as s, 
			llx_bank_account as cpt
		 
			where pf.fk_facture = f.rowid and p.rowid = pf.fk_paiement and p.fk_bank = b.rowid and f.fk_soc = s.rowid and cpt.rowid = b.fk_account
			 and f.amount = 0 
			and p.amount < 0
			and b.fk_account <> 7';

		 $this->export_sql_order[$r] .=' ORDER BY f.ref';
		 $r++;

		
		
		
		// Export Mail pour Communication
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ExportMailTiers';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_enabled[$r]='1';              // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		$this->export_permission[$r]=array(
				array(
					"user",
					"facture",
					"export"
				));
		$this->export_fields_array[$r]=array(
			'rowid'=>'Identifiant',
			'nom'=>'CompanyName',
			'email'=>'Mail',
			'NomDepart'=>'CglNomDepart',
			'Moniteur'=>'Moniteur',
			'DateDepart'=>'DateDepart',
			'Dateretrait'=>'CglDateretrait',
			'Velo'=>'CglVelo',
			'site'=>'CglLieu',
			'zip'=>'CglCodePostal',
			'semaine' => 'Cglsemaine',
			'mois' => 'Cglmois',
			'DepNom'=>'CglDepartement'
			);
		$this->export_TypeFields_array [$r] = array (
			's.rowid'=>'Numeric',
			's.nom'=>'Text',
			's.email'=>'Text',
			'NomDepart'=>'Text',
			'Moniteur'=>'Text',
			'DateDepart'=>'Date',
			'Dateretrait'=>'Date',
			'Velo'=>'Text',
			'site'=>'Text',
			'zip'=>'Numeric',
			'semaine' => 'Numeric',
			'mois' => 'Numeric',
			'DepNom'=>'Text'
			);

		$this->export_entities_array[$r]=array(
			's.rowid'=>'cglinscription',
			's.nom'=>'cglinscription',
			's.email'=>'cglinscription',
			'NomDepart'=>'cglinscription',
			'Moniteur'=>'cglinscription',
			'DateDepart'=>'cglinscription',
			'Dateretrait'=>'cglinscription',
			'Velo'=>'cglinscription',
			'site'=>'cglinscription',
			'zip'=>'cglinscription',
			'semaine' => 'cglinscription',
			'mois' => 'cglinscription',
			'DepNom'=>'cglinscription'
			);
		$this->export_sql_start[$r]='SELECT DISTINCT ';


		$this->export_sql_end[$r]  =' 
			FROM (
			SELECT distinct s.nom, s.email, s.rowid,
			case when bull.typebull = "Insc" then month( depart.dated  ) 
					else case when bull.typebull = "Loc" then month(bulldet.dateretrait) end end as mois, 
			case when not isnull(socmon.nom)   then socmon.nom else  Concat(concat(monsal.lastname," "), monsal.firstname) end as Moniteur, 
			case when bull.typebull = "Insc" then depart.intitule_custo end  as NomDepart,

			case when bull.typebull = "Insc" then depart.dated end as DateDepart, 
			case when bull.typebull = "Loc" then bulldet.dateretrait end as Dateretrait,
			case when bull.typebull = "Loc" then 	produit.label  end  as   Velo, 

			case when bull.typebull = "Insc" then site.ref_interne end as site, 
			case when bull.typebull = "Insc" then DATE_FORMAT( depart.dated  ,"%u") 
					else case when bull.typebull = "Loc" then  DATE_FORMAT(bulldet.dateretrait   ,"%u" ) end end as semaine, 
			s.zip,  departement.ncc as DepNom
				
				
			FROM llx_societe as s
				LEFT JOIN llx_cglinscription_bull as bull ON bull.fk_soc = s.rowid
				LEFT JOIN llx_cglinscription_bull_det as bulldet ON bulldet.fk_bull = bull.rowid
				LEFT JOIN llx_agefodd_session  as depart ON bulldet.fk_activite = depart.rowid 
				LEFT JOIN llx_product  as produit ON bulldet.fk_produit = produit.rowid 
					 
				LEFT JOIN llx_agefodd_session_formateur  as depmon ON depmon.fk_session = depart.rowid
				LEFT JOIN llx_agefodd_formateur  as moniteur ON depmon.fk_agefodd_formateur = moniteur.rowid
				LEFT JOIN llx_societe as socmon ON moniteur.fk_soc = socmon.rowid 
				LEFT JOIN llx_user as monsal ON monsal.fk_user = monsal.rowid
				LEFT JOIN llx_agefodd_place as site ON depart.fk_session_place = site.rowid 
				LEFT JOIN llx_c_departements as departement ON substr(s.zip,1,2) = departement.code_departement

			where (not  isnull(depart.rowid) or  not  isnull(site.rowid) or  not  isnull(departement.rowid)
				or  not  isnull(produit.rowid)
				or  not  isnull(moniteur.rowid))
				and not isnull(departement.rowid)
			) as Tiers_Communication WHERE 1=1 ';
				
		 $this->export_sql_order[$r] .=' ORDER BY nom';
		 $r++;


	}
	/*SQL lisible  
SELECT DISTINCT *
FROM 
(select   s.categorie,
s.rowid as IdSession, se.s_duree_act as duree, m.rowid as IdMonit, st.rowid as IdTiers, se.s_code_ventil as CodeVentilSession, m.ventilation_vente as CodeVentilMon, 
case when isnull(IdUser) then NomContact else NomUser end 	as	NomMoniteur, 
semaine, annee, 
p.ref_interne as Site,	s.type_session, NbAdulte,NbEnfant,PrixVente,  se.s_pourcent, se.s_partmonit, 
s.dated ,nom, 
f.ref  as FactureDol, f.ref_supplier as FactureMon, 	case when isnull(se.s_TypeTVA) then  st.tva_assuj else se.s_TypeTVA end as tva_assuj, 
		
		
case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0 
		else 	case when s_partmonit > 0    then  s_partmonit 
		else 	case when (isnull(s_partmonit) or s_partmonit = 0)  and se.s_TypeTVA = 1 then  PrixVente * s_pourcent / 100 
		else case when (isnull(s_partmonit) or s_partmonit = 0)  and se.s_TypeTVA = 0  then PrixVente *  s_pourcent  / 100 				 
		end end end  end as partmonitBrute, 
		

case when (isnull(s_partmonit) or s_partmonit = 0) and (isnull(s_pourcent) or s_pourcent = 0)  then 0 
		else case when s_partmonit > 0  and se.s_TypeTVA = 1  then PrixVente /1.2 - s_partmonit 
		else case when s_partmonit > 0 and se.s_TypeTVA = 0  then (PrixVente - s_partmonit )/1.2  
		else case when (isnull(s_partmonit) or s_partmonit = 0)  and se.s_TypeTVA = 1 then PrixVente * (1 - s_pourcent /100)  
		else case when (isnull(s_partmonit) or s_partmonit = 0)  and se.s_TypeTVA = 0  then PrixVente * (1 - s_pourcent/100 ) / 1.2 
		end end end end end as MargeHT,	 
		
case when se.s_partmonit > 0 	then se.s_partmonit
	else case when se.s_pourcent > 0 			then	se.s_pourcent*(select sum(qte * pu * (100-bd.rem)/100 ) from  	 llx_cglinscription_bull as b 
				LEFT JOIN	 llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0
				where  bd.fk_activite = s.rowid  )/100
	end end   as  partmonitBruteAct,  
		
case when se.s_partmonit > 0 				then se.s_partmonit
			else		case when se.s_pourcent > 0 				then
						(select sum(qte * pu * (100-bd.rem)/100 ) from  	llx_cglinscription_bull as b 
						LEFT JOIN	llx_cglinscription_bull_det as bd on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0
						where  bd.fk_activite = s.rowid  )/100
					end 			end   as PVAct

FROM  llx_agefodd_formateur  as m 	
LEFT JOIN 	(select sp.rowid as IdContact, 
	concat(concat(sp.lastname, ' '),sp.firstname) as NomContact 
	from  llx_socpeople as sp ) as mi on IdContact = m.fk_socpeople
LEFT JOIN 		(select u.rowid as IdUser, concat(concat(u.lastname, ' '),u.firstname) as NomUser 
				FROM  llx_user as u ) as mc on IdUser = m.fk_user  
LEFT JOIN  llx_agefodd_session_formateur as sm on m.rowid = sm.fk_agefodd_formateur  
LEFT JOIN 	(select  s1.rowid, s1.type_session, 		
					s1.dated,s1.fk_session_place, DATE_FORMAT(s1.dated,"%u") as semaine, year(s1.dated) as annee, 
				 (select sum(qte) from  	llx_cglinscription_bull as b 
					LEFT JOIN	llx_cglinscription_bull_det as bd 
				 on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0 
					where  bd.fk_activite = s1.rowid and   (bd.age <= 12 or bd.age = 100)   and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )) )
					as NbEnfant, 
				 (select sum(qte) from  	llx_cglinscription_bull as b 
					LEFT JOIN	llx_cglinscription_bull_det as bd 
				 on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0 
					where  bd.fk_activite = s1.rowid  and (bd.age > 12 or bd.age = 99) and bd.age <> 100 and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )) )
					as NbAdulte, 
				(select sum(qte * pu * (100-bd.rem)/100 ) 	FROM  	llx_cglinscription_bull as b 
					LEFT JOIN	 llx_cglinscription_bull_det as bd 
					 on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0 
					 where  bd.fk_activite = s1.rowid  and (isnull(abandon) or (b.abandon NOT LIKE "Activit%" and b.abandon NOT LIKE "%Abandon%" )) )	
					 as PrixVente ,

					 (select max(label) 
							from  llx_cglinscription_bull as b  
							LEFT JOIN llx_cglinscription_bull_det as bd  on bd.fk_bull = b.rowid and bd.action not in ('S','X') and bd.type = 0 
							LEFT JOIN llx_societe as tiers  on b.fk_soc= tiers.rowid
							LEFT JOIN llx_categorie_societe as cstiers  on cstiers.fk_soc = tiers.rowid  
							LEFT JOIN llx_categorie as ctiers on ctiers.rowid = cstiers.fk_categorie
							where bd.fk_activite = s1.rowid  ) as categorie
						FROM llx_agefodd_session as s1) 

		as s  on s.rowid = sm.fk_session 
LEFT JOIN   llx_agefodd_session_extrafields as se on se.fk_object = s.rowid 
LEFT JOIN 	llx_societe as st on  st.rowid = m.fk_soc  		
LEFT JOIN 	llx_facture_fourn as f on f.rowid = se.s_fk_facture 

LEFT JOIN	llx_agefodd_place as p on s.fk_session_place = p.rowid 
) as TB 
WHERE 1 = 1
        order by IdSession desc
		*/
	
	

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		$sql = array();

		//$result=$this->load_tables();

		return $this->_init($sql, $options);
	}
	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
	/**
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /mymodule/sql/
	 *		This function is called by this->init
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		//return $this->_load_tables('/cglinsription/sql/');
	}
}

?>