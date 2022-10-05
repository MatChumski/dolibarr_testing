<?php
/*   Copyright (C) 2015 Alexis José Turruella Sánchez
     Desarrollado en el mes de septiemmbre de 2011
     Correo electrónico: alexturruella@gmail.com 
     Módulo para la facturación de consumo eléctrico
	 Fichero interface_modConsumoEnergia.class.php
 */
class InterfaceAIU
{
    var $db;
	var $aiu_products = array();
    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceAIU($DB)
    {
		global $conf;
        $this->db = $DB ;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "consumoenergia";
        $this->description = "Triggers para el tratamiento del AIU.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
		$this->aiu_products = array(
										$conf->global->AIU_PRODUCTO_ADMINISTRACION,
										$conf->global->AIU_PRODUCTO_IMPREVISTO,
										$conf->global->AIU_PRODUCTO_UTILIDAD										
									);
    }
    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }
    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }
    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }
    /**
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
		if ($conf->aiu->enabled)
		{
			require_once DOL_DOCUMENT_ROOT.'/aiu/class/PresupuestoAIU.class.php';
			require_once DOL_DOCUMENT_ROOT.'/aiu/class/PedidoAIU.class.php';
			require_once DOL_DOCUMENT_ROOT.'/aiu/class/FacturaAIU.class.php';
			if($action == 'ORDER_CREATE')
        	{
				if($object->origin == 'propal')
				{
					$objectPresupuestoAIU = new PresupuestoAIU($this->db);
					$objectPresupuestoAIU->fetch($object->origin_id);
					//Se crea a partir de un pedido
					$objectPedidoAIU = new PedidoAIU($this->db);
					$objectPedidoAIU->id = $object->id;
					$objectPedidoAIU->presupuesto_fk = $object->origin_id;
					$objectPedidoAIU->aiu_administracion_tipo = $objectPresupuestoAIU->aiu_administracion_tipo;
					$objectPedidoAIU->aiu_administracion_valor = $objectPresupuestoAIU->aiu_administracion_valor;
					$objectPedidoAIU->aiu_imprevisto_tipo = $objectPresupuestoAIU->aiu_imprevisto_tipo;
					$objectPedidoAIU->aiu_imprevisto_valor = $objectPresupuestoAIU->aiu_imprevisto_valor;
					$objectPedidoAIU->aiu_utilidad_tipo = $objectPresupuestoAIU->aiu_utilidad_tipo;
					$objectPedidoAIU->aiu_utilidad_valor = $objectPresupuestoAIU->aiu_utilidad_valor;
					$objectPedidoAIU->update($user);					
				}
            	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);					 
		 	}			
			if($action == 'BILL_CREATE')
        	{
				
				if($object->origin == 'propal')
				{					
					$objectPresupuestoAIU = new PresupuestoAIU($this->db);
					$objectPresupuestoAIU->fetch($object->origin_id);
					//Se crea a partir de un presupuesto
					$objectFacturaAIU = new FacturaAIU($this->db);
					$objectFacturaAIU->id = $object->id;
					$objectFacturaAIU->presupuesto_fk = $object->origin_id;
					$objectFacturaAIU->aiu_administracion_tipo = $objectPresupuestoAIU->aiu_administracion_tipo;
					$objectFacturaAIU->aiu_administracion_valor = $objectPresupuestoAIU->aiu_administracion_valor;
					$objectFacturaAIU->aiu_imprevisto_tipo = $objectPresupuestoAIU->aiu_imprevisto_tipo;
					$objectFacturaAIU->aiu_imprevisto_valor = $objectPresupuestoAIU->aiu_imprevisto_valor;
					$objectFacturaAIU->aiu_utilidad_tipo = $objectPresupuestoAIU->aiu_utilidad_tipo;
					$objectFacturaAIU->aiu_utilidad_valor = $objectPresupuestoAIU->aiu_utilidad_valor;
					$objectFacturaAIU->update($user);					
				}
				if($object->origin == 'commande')
				{
					$objectPedidoAIU = new PedidoAIU($this->db);
					$objectPedidoAIU->fetch($object->origin_id);
					//Se crea a partir de un pedido
					$objectFacturaAIU = new FacturaAIU($this->db);
					$objectFacturaAIU->id = $object->id;
					$objectFacturaAIU->pedido_fk = $object->origin_id;
					$objectFacturaAIU->aiu_administracion_tipo = $objectPedidoAIU->aiu_administracion_tipo;
					$objectFacturaAIU->aiu_administracion_valor = $objectPedidoAIU->aiu_administracion_valor;
					$objectFacturaAIU->aiu_imprevisto_tipo = $objectPedidoAIU->aiu_imprevisto_tipo;
					$objectFacturaAIU->aiu_imprevisto_valor = $objectPedidoAIU->aiu_imprevisto_valor;
					$objectFacturaAIU->aiu_utilidad_tipo = $objectPedidoAIU->aiu_utilidad_tipo;
					$objectFacturaAIU->aiu_utilidad_valor = $objectPedidoAIU->aiu_utilidad_valor;
					$objectFacturaAIU->update($user);					
				}
            	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);					 
		 	}
			if($action == 'LINEBILL_INSERT' || $action == 'LINEBILL_UPDATE' || $action == 'LINEBILL_DELETE')
			{				
				require_once DOL_DOCUMENT_ROOT.'/aiu/class/FacturaAIU.class.php';
				if(!in_array($object->fk_product,$this->aiu_products))
				{
					//No se actualiza si proviene de la creacion de la factura desde un pedido o presupuesto
					if(($object->origin != 'commande' || $object->origin != 'propal') && $object->origin_id == '')
					{					
						//Update los productos AIU aplicados
						$objectFacturaAIU = new FacturaAIU($this->db);
						$objectFacturaAIU->fetch($object->fk_facture);
						$objectFacturaAIU->update_products_aiu(($action == 'LINEBILL_DELETE'?$object->rowid:NULL));
					}
				}
			}
			if($action == 'LINEORDER_INSERT' || $action == 'LINEORDER_UPDATE' || $action == 'LINEORDER_DELETE')
			{
				require_once DOL_DOCUMENT_ROOT.'/aiu/class/PedidoAIU.class.php';
				if(!in_array($object->fk_product,$this->aiu_products))
				{					
					//Update los productos AIU aplicados
					$objectPedidoAIU = new PedidoAIU($this->db);
					$objectPedidoAIU->fetch($object->fk_commande);
					$objectPedidoAIU->update_products_aiu(($action == 'LINEORDER_DELETE'?$object->rowid:NULL));
				}
			}
			if($action == 'LINEPROPAL_INSERT' || $action == 'LINEPROPAL_UPDATE' || $action == 'LINEPROPAL_DELETE')
			{
				require_once DOL_DOCUMENT_ROOT.'/aiu/class/PresupuestoAIU.class.php';
				if(!in_array($object->fk_product,$this->aiu_products))
				{					
					//Update los productos AIU aplicados
					$objectPresupuestoAIU = new PresupuestoAIU($this->db);
					$objectPresupuestoAIU->fetch($object->fk_propal);
					$objectPresupuestoAIU->update_products_aiu(($action == 'LINEPROPAL_DELETE'?$object->rowid:NULL));
				}
			}
		}
		return 0;
    }
}
?>