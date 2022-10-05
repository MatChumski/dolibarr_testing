<?php
/*   Copyright (C) 2018 Alexis José Turruella Sánchez
     Desarrollado en el 2018
     Correo electrónico: alexturruella@gmail.com 
     Módulo para la gestión del precios del producto en correspondencia al volumen
	 Cuarta versión del módulo compatible con la versión 7.0.2 de dolibarr
	 Fichero modAIU.class.php
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

/**
 * 		\class      modAIU
 *      \brief      Descripcion del modulo AIU
 */
class modAIU extends DolibarrModules
{
	/**
	 *   \brief      Constructor. Define names, constants, directories, boxes, permissions
	 *   \param      DB      Database handler
	 */
	function modAIU($DB)
	{
		global $langs, $conf;
		$this->db = $DB;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 10020;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'aiu';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "products";
		$this->module_position = 50;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "AIU - Administración, Imprevisto y Utilidad";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='aiu.png@aiu';

		// Defined if the directory /mymodule/inc/triggers/ contains triggers or not
		
		$this->module_parts = array('triggers' => 0);
		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();
		$r=0;

		// Relative path to module style sheet if exists. Example: '/mymodule/css/mycss.css'.
		//$this->style_sheet = '/aiu/css/aiu.css';
		$this->module_parts = array('css' => array('/aiu/css/aiu.css'),'triggers' => 1);
		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array('acercade.php@aiu');

		// Dependencies
		// List of modules id that must be enabled if this module is enabled
		$this->depends = array("modSociete","modProduct","modFacture","modPropale");
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		
		
		$this->langfiles = array("aiu@aiu");

		// Constants
		
		$this->const = array();			// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')

		// Array to add new pages in new tabs
		$this->tabs = array('invoice:+TabFacturaAIU:AIU:@aiu:/aiu/tab_factura.php?facid=__ID__',
							'order:+TabPedidoAIU:AIU:@aiu:/aiu/tab_pedido.php?id=__ID__',
							'propal:+TabPresupuestoAIU:AIU:@aiu:/aiu/tab_presupuesto.php?id=__ID__');
		
		// dictionnarys
		if (!isset($conf->aiu->enabled))
        {
        	$conf->aiu = new stdClass();
        	$conf->aiu->enabled = 0;
        }
		$this->dictionaries = array(
			'langs'=>array('aiu@aiu'),
			'tabname'=>array(
							MAIN_DB_PREFIX."c_aiu_administracion",
							MAIN_DB_PREFIX."c_aiu_imprevisto",
							MAIN_DB_PREFIX."c_aiu_utilidad"
							),
			'tablib'=>array(
							$this->name.": Valor en porciento administrativo",
							$this->name.": Valor en porciento del imprevisto",
							$this->name.": Valor en porciento de la utilidad"	
							),
			'tabsql'=>array(
							"SELECT rowid,valor,active FROM ".MAIN_DB_PREFIX."c_aiu_administracion",
							"SELECT rowid,valor,active FROM ".MAIN_DB_PREFIX."c_aiu_imprevisto",
							"SELECT rowid,valor,active FROM ".MAIN_DB_PREFIX."c_aiu_utilidad"
							),
			'tabsqlsort'=>array(
								"valor ASC",
								"valor ASC",
								"valor ASC"
								),
			'tabfield'=>array(
								'valor',
								'valor',
								'valor'
							 ),
			'tabfieldvalue'=>array(
									'valor',
									'valor',
									'valor'
								   ),
			'tabfieldinsert'=>array(
									'valor',
									'valor',
									'valor'
									), 
			'tabrowid'=>array(
								"rowid",
								"rowid",
								"rowid"
							  ),
			'tabcond'=>array(
								$conf->aiu->enabled,
								$conf->aiu->enabled,
								$conf->aiu->enabled
							)
		);

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		

		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

		//generar los productos del AIU
		$this->generar_productos_aiu();
		
	}


	private function generar_productos_aiu()
	{
		global $langs, $conf, $user;
		// Create instance of object
		$product_administracion = new Product($this->db);
		
		if($product_administracion->fetch((isset($conf->global->AIU_PRODUCTO_ADMINISTRACION)?$conf->global->AIU_PRODUCTO_ADMINISTRACION:-1)))
		{
			// Definition of product instance properties
			$product_administracion->ref                = 'AIU-1';
			$product_administracion->label              = 'Administración';
			$product_administracion->price              = '0';
			$product_administracion->price_base_type    = 'HT';
			$product_administracion->tva_tx             = '';
			$product_administracion->type               = Product::TYPE_PRODUCT;
			$product_administracion->status             = 1;
			$product_administracion->description        = '';
			$product_administracion->note               = '';
			$product_administracion->weight             = 0;
			$product_administracion->weight_units       = 0;
			
			// Create product in database
			$idobject_administracion = $product_administracion->create($user);
			if ($idobject_administracion > 0)
			{
				//Guardar el valor en constante
				dolibarr_set_const($this->db, "AIU_PRODUCTO_ADMINISTRACION", $idobject_administracion,'chaine',0,"Producto para facturar el AIU Administración",$conf->entity);
			}
		}
		
		$product_imprevisto = new Product($this->db);
		
		if($product_imprevisto->fetch((isset($conf->global->AIU_PRODUCTO_IMPREVISTO)?$conf->global->AIU_PRODUCTO_IMPREVISTO:-1)))
		{
			// Definition of product instance properties
			$product_imprevisto->ref                = 'AIU-2';
			$product_imprevisto->label              = 'Imprevisto';
			$product_imprevisto->price              = '0';
			$product_imprevisto->price_base_type    = 'HT';
			$product_imprevisto->tva_tx             = '';
			$product_imprevisto->type               = Product::TYPE_PRODUCT;
			$product_imprevisto->status             = 1;
			$product_imprevisto->description        = '';
			$product_imprevisto->note               = '';
			$product_imprevisto->weight             = 0;
			$product_imprevisto->weight_units       = 0;
			
			// Create product in database
			$idobject_imprevisto = $product_imprevisto->create($user);
			if ($idobject_imprevisto > 0)
			{
				//Guardar el valor en constante
				dolibarr_set_const($this->db, "AIU_PRODUCTO_IMPREVISTO", $idobject_imprevisto,'chaine',0,"Producto para facturar el AIU Imprevisto",$conf->entity);
			}
		}
		
		$product_utilidad = new Product($this->db);
		
		if($product_utilidad->fetch((isset($conf->global->AIU_PRODUCTO_UTILIDAD)?$conf->global->AIU_PRODUCTO_UTILIDAD:-1)))
		{
			// Definition of product instance properties
			$product_utilidad->ref                = 'AIU-3';
			$product_utilidad->label              = 'Utilidad';
			$product_utilidad->price              = '0';
			$product_utilidad->price_base_type    = 'HT';
			$product_utilidad->tva_tx             = '';
			$product_utilidad->type               = Product::TYPE_PRODUCT;
			$product_utilidad->status             = 1;
			$product_utilidad->description        = '';
			$product_utilidad->note               = '';
			$product_utilidad->weight             = 0;
			$product_utilidad->weight_units       = 0;
			
			// Create product in database
			$idobject_utilidad = $product_utilidad->create($user);
			if ($idobject_utilidad > 0)
			{
				//Guardar el valor en constante
				dolibarr_set_const($this->db, "AIU_PRODUCTO_UTILIDAD", $idobject_utilidad,'chaine',0,"Producto para facturar el AIU Utilidad",$conf->entity);
			}
		}
	}

	/**
	 *		\brief      Function called when module is enabled.
	 *					The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *					It also creates data directories.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function init()
	{
		$sql = array();

		$result=$this->load_tables();

		return $this->_init($sql);
	}

	/**
	 *		\brief		Function called when module is disabled.
	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
	 *					Data directories are not deleted.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}


	/**
	 *		\brief		Create tables, keys and data required by module
	 * 					Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 					and create data commands must be stored in directory /mymodule/sql/
	 *					This function is called by this->init.
	 * 		\return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/aiu/sql/');
	}
}
?>