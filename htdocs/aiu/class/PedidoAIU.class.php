<?php
/*   Copyright (C) 2018 Alexis José Turruella Sánchez
     Desarrollado en el mes de junio de 2018
     Correo electrónico: alexturruella@gmail.com
	 Fichero PedidoAIU.class.php
 */
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
class PedidoAIU  
{
    var $db;
    var $id;
	var $presupuesto_fk;
	var $aiu_administracion_tipo;
	var $aiu_administracion_valor;
	var $aiu_imprevisto_tipo;
	var $aiu_imprevisto_valor;	
	var $aiu_utilidad_tipo;
	var $aiu_utilidad_valor;

    function PedidoAIU($DB) 
    {
        $this->db = $DB;
        return 1;
    }    
    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
	
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " p.rowid,";
		$sql.= " p.presupuesto_fk,";
		$sql.= " p.aiu_administracion_tipo,";
		$sql.= " p.aiu_administracion_valor,";
		$sql.= " p.aiu_imprevisto_tipo,";
		$sql.= " p.aiu_imprevisto_valor,";
		$sql.= " p.aiu_utilidad_tipo,";
		$sql.= " p.aiu_utilidad_valor";     
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as p";
        $sql.= " WHERE p.rowid = ".$id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
 
                $this->id    = $obj->rowid;
                $this->presupuesto_fk = $obj->presupuesto_fk;
				$this->aiu_administracion_tipo = $obj->aiu_administracion_tipo;
				$this->aiu_administracion_valor = $obj->aiu_administracion_valor;
				$this->aiu_imprevisto_tipo = $obj->aiu_imprevisto_tipo;
				$this->aiu_imprevisto_valor = $obj->aiu_imprevisto_valor;
				$this->aiu_utilidad_tipo = $obj->aiu_utilidad_tipo;
				$this->aiu_utilidad_valor = $obj->aiu_utilidad_valor;			
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
            return -1;
        }
    }    
    function update($user=0, $notrigger=0)
    {		
    	global $conf, $langs;
		$error=0;    	
		// Clean parameters
        if (isset($this->numero_contador)) $this->numero_contador=trim($this->numero_contador);
		
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET";

		//$sql.= " presupuesto_fk='".$this->presupuesto_fk."',";
		$sql.= " presupuesto_fk=".(isset($this->presupuesto_fk)?"'".addslashes($this->presupuesto_fk)."'":"null").",";

		$sql.= " aiu_administracion_tipo='".$this->aiu_administracion_tipo."',";
		$sql.= " aiu_administracion_valor='".$this->aiu_administracion_valor."',";
		$sql.= " aiu_imprevisto_tipo='".$this->aiu_imprevisto_tipo."',";
		$sql.= " aiu_imprevisto_valor='".$this->aiu_imprevisto_valor."',";
		$sql.= " aiu_utilidad_tipo='".$this->aiu_utilidad_tipo."',";
		$sql.= " aiu_utilidad_valor='".$this->aiu_utilidad_valor."'";

        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();
        
		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }        
        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}	
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}		
    }
	function update_products_aiu($linea_id_eliminar = NULL)
	{
		global $conf;
		$object = new Commande($this->db);
		$object->fetch($this->id);		
		$object->info($this->id);
		$object->fetch_thirdparty();
		//Eliminar los productos existentes del AIU que esten en la factura
		$importe_pedido = 0;				
		foreach ($object->lines as $line)
		{			
			if($line->fk_product == $conf->global->AIU_PRODUCTO_ADMINISTRACION)
			{
				$object->deleteline(NULL,$line->id);				
			}
			elseif($line->fk_product == $conf->global->AIU_PRODUCTO_IMPREVISTO)
			{
				$object->deleteline(NULL,$line->id);
			}
			elseif($line->fk_product == $conf->global->AIU_PRODUCTO_UTILIDAD)
			{
				$object->deleteline(NULL,$line->id);
			}
			else
			{
				if($linea_id_eliminar != $line->id)
				{
					//Obtener el importe total de la factura
					$importe_pedido+= $line->total_ht;
				}
			}
		}
		if($importe_pedido > 0)
		{			
			$object->fetch($this->id);
			//Crear los productos del AIU con los nuevos valores si el importe es mayor que cero
			//**INICIO ADMINISTRACION**
			$aiu_valor_administracion_aplicar = $this->aiu_administracion_valor;
			if($this->aiu_administracion_tipo == '%')
			{
				$aiu_valor_administracion_aplicar = $importe_pedido*$aiu_valor_administracion_aplicar/100;
			}			
			if($aiu_valor_administracion_aplicar > 0)
			{
						
				$producto_administracion = new Product($this->db);
							
				$producto_administracion->fetch($conf->global->AIU_PRODUCTO_ADMINISTRACION);
				if($producto_administracion->id > 0)
				{
					$desc = '';
					$label = $producto_administracion->label;
					$qty = 1;
					$tva_tx = $producto_administracion->tva_tx;					
					$price_base_type = $producto_administracion->price_base_type;
					$type = $producto_administracion->type;
					
					$pu_ht	= price2num($aiu_valor_administracion_aplicar, 'MU');
					$pu_ttc	= price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');					
				
					$object->addline(
							$desc,$pu_ht,$qty,$tva_tx,0,0,$producto_administracion->id,0,0,0,$price_base_type,0,'','',$type,-1,0,0,null,0,$label,0,null,'',0,0);
							
					
				}
			}
			
			//**FIN ADMINISTRACION**
			//**INICIO IMPREVISTO**
			$aiu_valor_imprevisto_aplicar = $this->aiu_imprevisto_valor;		
			if($this->aiu_imprevisto_tipo == '%')
			{
				$aiu_valor_imprevisto_aplicar = $importe_pedido*$aiu_valor_imprevisto_aplicar/100;
			}
			if($aiu_valor_imprevisto_aplicar > 0)
			{					
				$producto_imprevisto = new Product($this->db);				
				$producto_imprevisto->fetch($conf->global->AIU_PRODUCTO_IMPREVISTO);
				if($producto_imprevisto->id > 0)
				{
					$desc = '';
					$label = $producto_imprevisto->label;
					$qty = 1;
					$tva_tx = $producto_imprevisto->tva_tx;					
					$price_base_type = $producto_imprevisto->price_base_type;
					$type = $producto_imprevisto->type;
					
					$pu_ht	= price2num($aiu_valor_imprevisto_aplicar, 'MU');
					$pu_ttc	= price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');					
				
					$object->addline(
							$desc,$pu_ht,$qty,$tva_tx,0,0,$producto_imprevisto->id,0,0,0,$price_base_type,0,'','',$type,-1,0,0,null,0,$label,0,null,'',0,0);
				}
			}
			//**FIN IMPREVISTO**
			//**INICIO UTILIDAD**
			$aiu_valor_utilidad_aplicar = $this->aiu_utilidad_valor;		
			if($this->aiu_utilidad_tipo == '%')
			{
				$aiu_valor_utilidad_aplicar = $importe_pedido*$aiu_valor_utilidad_aplicar/100;
			}
			if($aiu_valor_utilidad_aplicar > 0)
			{					
				$producto_utilidad = new Product($this->db);				
				$producto_utilidad->fetch($conf->global->AIU_PRODUCTO_UTILIDAD);
				if($producto_utilidad->id > 0)
				{
					$desc = '';
					$label = $producto_utilidad->label;
					$qty = 1;
					$tva_tx = $producto_utilidad->tva_tx;					
					$price_base_type = $producto_utilidad->price_base_type;
					$type = $producto_utilidad->type;
					
					$pu_ht	= price2num($aiu_valor_utilidad_aplicar, 'MU');
					$pu_ttc	= price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');					
				
					$object->addline(
							$desc,$pu_ht,$qty,$tva_tx,0,0,$producto_utilidad->id,0,0,0,$price_base_type,0,'','',$type,-1,0,0,null,0,$label,0,null,'',0,0);
				}
			}
			//**FIN UTILIDAD**
			//Guardar la configuracion del AIU en la factura
		}
		$this->update();
	}
}
?>