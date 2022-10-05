<?php
/* Copyright (C) 2018      Alexis Turruella <alexturruella@gmail.com>

 */

/**
 *      \file       htdocs/aiu/tab_pedido.php
 *      \ingroup    commande
 *      \brief      Configuracion de los valores del AIU para el pedido
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT .'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/aiu/lib/aiu.lib.php';
require_once DOL_DOCUMENT_ROOT.'/aiu/class/Values.class.php';
require_once DOL_DOCUMENT_ROOT.'/aiu/class/PedidoAIU.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("aiu@aiu");

$id=GETPOST('id','int');  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');

// Security check
$socid=0;
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'facture',$id,'');

$object = new Commande($db);
$object->fetch($id);

$values = new Values($db);

/*
 * Actions
 */

if($action == 'aplicar_aiu')
{
	if ($id > 0 || ! empty($ref))
	{	
		$objectPedidoAIU = new PedidoAIU($db);
		$objectPedidoAIU->fetch($id);
		
		$objectPedidoAIU->aiu_administracion_tipo = GETPOST('aiu_tipo_administracion','alpha');
		$objectPedidoAIU->aiu_administracion_valor = $objectPedidoAIU->aiu_administracion_tipo == '$'?GETPOST('aiu_valor_administracion_m'):GETPOST('aiu_valor_administracion_p');
		
		$objectPedidoAIU->aiu_imprevisto_tipo = GETPOST('aiu_tipo_imprevisto','alpha');
		$objectPedidoAIU->aiu_imprevisto_valor = $objectPedidoAIU->aiu_imprevisto_tipo == '$'?GETPOST('aiu_valor_imprevisto_m'):GETPOST('aiu_valor_imprevisto_p');
		
		$objectPedidoAIU->aiu_utilidad_tipo = GETPOST('aiu_tipo_utilidad','alpha');
		$objectPedidoAIU->aiu_utilidad_valor = $objectPedidoAIU->aiu_utilidad_tipo == '$'?GETPOST('aiu_valor_utilidad_m'):GETPOST('aiu_valor_utilidad_p');
		
		$objectPedidoAIU->update_products_aiu();
		
	}
}


/*
 * View
 */

$title = $langs->trans('InvoiceCustomer') . " - " . $langs->trans('AIU');
$morejs=array("aiu/lib/js/jquery-numeric.js");
$helpurl = "ES:AIU";
llxHeader('', $title,$helpurl,'','','',$morejs);

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{	
	$object = new Commande($db);
	$object->fetch($id,$ref);

	$object->fetch_thirdparty();
	
	$objectPedidoAIU = new PedidoAIU($db);
	$objectPedidoAIU->fetch($object->id);

    $head = commande_prepare_head($object);

    dol_fiche_head($head, 'TabPedidoAIU', $langs->trans('CustomerOrder'), -1, 'bill');

    // Invoice content
 
    $linkback = '<a href="' . DOL_URL_ROOT . '/commande/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';


	$morehtmlref='<div class="refidno">';
	// Ref customer
	$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
	
	$morehtmlref.='</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	
			print '<div class="fichecenter fichecenterbis">';		
				print '<div class="div-table-responsive">';
				print '<form method="post">';
				print '<input type="hidden" name="action" value="aplicar_aiu"/>';
					print '<table class="tagtable liste" width="100%">';
						print '<tr class="liste_titre">';
							print '<th class="liste_titre">'.$langs->trans('aiu_pedido_encabezado').'</th>';							
						print '</tr>';
						print '<tr>';
							print '<td class="oddeven">';
							
										print '<fieldset class="aiu_box administracion">';
											print '<legend>'.$langs->trans('aiu_administracion').'</legend>';										
											print '<fieldset>';
											print '<legend>'.$langs->trans('aiu_forma_aplicar').'</legend>';
											print '<label for="aiu_tipo_a_m"> $ </label>';
											print '<input class="radio" data-target="a_valor_m" data-target-off="a_valor_p" type="radio" id="aiu_tipo_a_m" name="aiu_tipo_administracion" value="$" '.($objectPedidoAIU->aiu_administracion_tipo == '$'?'checked="checked"':'').'/> ';
											print '<label  for="aiu_tipo_a_p"> % </label>';
											print '<input class="radio" data-target="a_valor_p" data-target-off="a_valor_m" type="radio" id="aiu_tipo_a_p" name="aiu_tipo_administracion" value="%" '.($objectPedidoAIU->aiu_administracion_tipo == '%'?'checked="checked"':'').'/>';
											print '</fieldset>';
											print '<hr/>';
											print '<label>'.$langs->trans('Amount').'</label> ';
											print '<input type="text" id="a_valor_m" name="aiu_valor_administracion_m" class="numeric" value="'.price2num($objectPedidoAIU->aiu_administracion_valor).'" '.($objectPedidoAIU->aiu_administracion_tipo == '$'?'':'style="display:none;"').'/>';
											print '<select id="a_valor_p" name="aiu_valor_administracion_p" '.($objectPedidoAIU->aiu_administracion_tipo == '%'?'':'style="display:none;"').'>';
												$values_administracion = $values->getlist('a');
												foreach($values_administracion as $item)
												{
													print '<option '.($objectPedidoAIU->aiu_administracion_valor == $item->valor?'selected="selected"':'').' value="'.$item->valor.'">'.$item->valor.'</option>';
												}
											print '</select>';
										print '</fieldset>';
										
										
										print '<fieldset class="aiu_box imprevisto">';
											print '<legend>'.$langs->trans('aiu_imprevisto').'</legend>';										
											print '<fieldset>';
												print '<legend>'.$langs->trans('aiu_forma_aplicar').'</legend>';
												print '<label for="aiu_tipo_i_m"> $ </label>';
												print '<input class="radio" data-target="i_valor_m" data-target-off="i_valor_p" type="radio" id="aiu_tipo_i_m" name="aiu_tipo_imprevisto" value="$" '.($objectPedidoAIU->aiu_imprevisto_tipo == '$'?'checked="checked"':'').'/> ';
												print '<label  for="aiu_tipo_i_p"> % </label>';
												print '<input class="radio" data-target="i_valor_p" data-target-off="i_valor_m" type="radio" id="aiu_tipo_i_p" name="aiu_tipo_imprevisto" value="%" '.($objectPedidoAIU->aiu_imprevisto_tipo == '%'?'checked="checked"':'').'/>';
											print '</fieldset>';
											print '<hr/>';
											print '<label>'.$langs->trans('Amount').'</label> ';
											print '<input type="text" id="i_valor_m" name="aiu_valor_imprevisto_m" class="numeric" value="'.price2num($objectPedidoAIU->aiu_imprevisto_valor).'" '.($objectPedidoAIU->aiu_imprevisto_tipo == '$'?'':'style="display:none;"').'/>';
											print '<select id="i_valor_p" name="aiu_valor_imprevisto_p" '.($objectPedidoAIU->aiu_imprevisto_tipo == '%'?'':'style="display:none;"').'>';
												$values_administracion = $values->getlist('i');
												foreach($values_administracion as $item)
												{
													print '<option '.($objectPedidoAIU->aiu_imprevisto_valor == $item->valor?'selected="selected"':'').' value="'.$item->valor.'">'.$item->valor.'</option>';
												}
											print '</select>';
										print '</fieldset>';										
										
										print '<fieldset class="aiu_box utilidad">';
											print '<legend>'.$langs->trans('aiu_utilidad').'</legend>';										
											print '<fieldset>';
											print '<legend>'.$langs->trans('aiu_forma_aplicar').'</legend>';
											print '<label for="aiu_tipo_u_m"> $ </label>';
											print '<input class="radio" data-target="u_valor_m" data-target-off="u_valor_p" type="radio" id="aiu_tipo_u_m" name="aiu_tipo_utilidad" value="$" '.($objectPedidoAIU->aiu_utilidad_tipo == '$'?'checked="checked"':'').'/> ';
											print '<label  for="aiu_tipo_u_p"> % </label>';
											print '<input class="radio" data-target="u_valor_p" data-target-off="u_valor_m" type="radio" id="aiu_tipo_u_p" name="aiu_tipo_utilidad" value="%" '.($objectPedidoAIU->aiu_utilidad_tipo == '%'?'checked="checked"':'').'/>';
											print '</fieldset>';
											print '<hr/>';
											print '<label>'.$langs->trans('Amount').'</label> ';
											print '<input type="text" id="u_valor_m" name="aiu_valor_utilidad_m" class="numeric" value="'.price2num($objectPedidoAIU->aiu_utilidad_valor).'" '.($objectPedidoAIU->aiu_utilidad_tipo == '$'?'':'style="display:none;"').'/>';
											print '<select id="u_valor_p" name="aiu_valor_utilidad_p" '.($objectPedidoAIU->aiu_utilidad_tipo == '%'?'':'style="display:none;"').'>';
												$values_administracion = $values->getlist('a');
												foreach($values_administracion as $item)
												{
													print '<option '.($objectPedidoAIU->aiu_utilidad_valor == $item->valor?'selected="selected"':'').' value="'.$item->valor.'">'.$item->valor.'</option>';
												}
											print '</select>';
										print '</fieldset>';
							
							print '</td>';
						print '</tr>';
						print '<tr>';	
							print '<td class="oddeven">';
								if($object->statut <1)
								{
									print '<input type="submit" value="'.$langs->trans('Save').'"/>';
								}
							print '</td>';						
						print '</tr>';
					print '</table>';
				print '</form>';		
				print '</div>';	
			print '</div>';
	?>
    <script>
  	$( function()
  	{
		$("input.radio").checkboxradio();
		$("input.radio").click(function(){
			$('#'+$(this).attr('data-target')).show();
			$('#'+$(this).attr('data-target-off')).hide();
		});
  	});
  </script>
    <?php
	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/aiu/lib/js/aiu.js"></script>';

	dol_fiche_end();
}

llxFooter();

$db->close();