<style> 
 input[type=submit].prefijos-submit  {
  background-color: #4CAF50;
  border: none;
  color: white;
  padding: 16px 32px;
  text-decoration: none;
  margin: 4px 2px;
  cursor: pointer;
}
</style>
<?php

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// load facturadian libraries
require_once __DIR__.'/class/cron.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("facturadian@facturadian", "other"));

$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'cronlist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new cron($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->facturadian->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('cronlist')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) $sortfield = "t.".key($object->fields); // Set here default search field. By default 1st field in definition.
if (!$sortorder) $sortorder = "ASC";

// Security check
if (empty($conf->facturadian->enabled)) accessforbidden('Module not enabled');
$socid = 0;
if ($user->socid > 0)	// Protection if external user
{
	//$socid = $user->socid;
	accessforbidden();
}
//$result = restrictedArea($user, 'facturadian', $id, '');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all", 'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key]=GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach($object->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key]=$val['label'];
}

// Definition of fields for list
$arrayfields=array();
foreach($object->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key])) {
			$arrayfields["ef.".$key] = array(
				'label'=>$extrafields->attributes[$object->table_element]['label'][$key],
				'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key]<0)?0:1),
				'position'=>$extrafields->attributes[$object->table_element]['pos'][$key],
				'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key])!=3 && $extrafields->attributes[$object->table_element]['perms'][$key])
			);
		}
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$permissiontoread = $user->rights->facturadian->cron->read;
$permissiontoadd = $user->rights->facturadian->cron->write;
$permissiontodelete = $user->rights->facturadian->cron->delete;


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($object->fields as $key => $val)
		{
			$search[$key] = '';
		}
		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'cron';
	$objectlabel = 'cron';
	$uploaddir = $conf->facturadian->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$form = new Form($db);

$now = dol_now();

//$help_url="EN:Module_cron|FR:Module_cron_FR|ES:Módulo_cron";
$help_url = '';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("crons"));


// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT ';
foreach ($object->fields as $key => $val)
{
	$sql .= 't.'.$key.', ';
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
if ($object->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntity($object->element).")";
else $sql .= " WHERE 1 = 1";
foreach ($search as $key => $val)
{
	if ($key == 'status' && $search[$key] == -1) continue;
	$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
	if (strpos($object->fields[$key]['type'], 'integer:') === 0) {
		if ($search[$key] == '-1') $search[$key] = '';
		$mode_search = 2;
	}
	if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
}
if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

/* If a group by is required
$sql.= " GROUP BY "
foreach($object->fields as $key => $val)
{
	$sql.='t.'.$key.', ';
}
// Add fields from extrafields
if (! empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.', ' : '');
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListGroupBy',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql=preg_replace('/,\s*$/','', $sql);
*/

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit)))
{
	$num = $nbtotalofrecords;
}
else
{
	if ($limit) $sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql)
	{
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

// Direct jump if only one record found
if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".dol_buildpath('/facturadian/cron_card.php', 1).'?id='.$id);
	exit;
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

print load_fiche_titre($langs->trans("Enviar documentos a la DIAN", $langs->transnoentitiesnoconv("FacturaDian")));

	//=============================================================================
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."facturadian_credenciales WHERE 1"; 	
	$resql = $db->query($sql);
	if ($resql)
	{
		if($db->num_rows($resql) > 0) {
			$objp = $db->fetch_object($resql);		
			$ejecutar = "php scripts/pagos.php ".$objp->username." \'".$objp->password."\'";
			$respuesta = shell_exec($ejecutar);		
		}
		$db->free($resql);
	}
	
	$datos = explode(",", $respuesta);
	
	// calculamos si esta en mora o dias por vencer
	if(strtolower(trim($datos[2]))    =="anual") {
		$vence = date('Y-m-d H:i:s', strtotime($datos[0] . " + 1 year"));
	}else {
		$vence = date('Y-m-d H:i:s', strtotime($datos[0] . " + 1 months"));
	}

	$dStart = new DateTime(date('Y-m-d H:i:s'));
	$dEnd  = new DateTime($vence);
	$dDiff = $dStart->diff($dEnd);
	$dias = $dDiff->format('%r%a');

	echo "<h4><font color='blue'>Tu plan <font color=red>$datos[2]</font> - ".$datos[1]." vence el ".$vence." en ".$dias." dias</font></h4>";

	if(number_format($dias) > 0 ) 
	{ 
	$estado = "Activo";
	echo "<h4><font color='green'>Tu servicio se encuentra ACTIVO<br /><br /></font>";
	} else {
	$estado = "Inactivo"; 
	echo "<h4><font color='red'>Tu servicio se encuentra INACTIVO<br /><br /></font>";
	}
		
	print '<form method="POST" action="./cron_card.php?action=enviar">';
	print '
	  <fieldset>
		<legend>Parametros de envio:</legend>
		<br />
		<label for="documento">Tipo de documento:</label>
			<select id="documento" name="documento">
			  <option value="fa">facturas</option>
			  <option value="nc">Notas credito</option>
			  <option value="nd">Notas debito</option>
			</select><br><br>
		<label for="etapa">Etapa:</label>
			<select id="etapa" name="etapa">
			  <option value="todas">Todo el proceso desde la Dian</option>
			  <option value="email">Solo volver a enviar el email</option>
			</select>
			Si solo envia el email, subira de nuevo el pdf y consultara el nuevo email en el cliente
			<br><br>			
		<label for="invoice">Documento empieza por (Ej: SETP o SETP-90000001 ):</label>
			<input type="text" size="15" id="invoice" name="invoice"> Para envio email debe ser exacto <br><br>';
			  
		print '
		<label for="cantidad">Cantidad de documentos a enviar:</label>
			<input type="text" size="10" id="cantidad" name="cantidad"> Para envio email siempre es 1<br><br>	
	  </fieldset>
	  <input type="submit" class="prefijos-submit" value="Enviar a la DIAN">
	  ';
	print '</form>';
	
	//=======================================================================================
	
	//print '<form method="POST" action="./cron_card.php?action=actualizar">';
	// print '<input type="submit" class="prefijos-submit" value="Actualizar dolibarr manualmente"> Use esta opcion despues de 3 minutos del ultimo envio';
	//print '</form>';

// End of page
llxFooter();
$db->close();
