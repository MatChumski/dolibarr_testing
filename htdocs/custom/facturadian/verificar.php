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


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);
print load_fiche_titre($langs->trans("Verificacion de instalacion de FacturaDian", $langs->transnoentitiesnoconv("FacturaDian")));

	$sqlconfig = "SELECT value AS config FROM ".MAIN_DB_PREFIX."const WHERE name like '%FACTURADIAN_CONFIG_PATH%' LIMIT 1 ";	
	$resqlconfig = $db->query($sqlconfig);
	if ($resqlconfig) {
		if($db->num_rows($resqlconfig) > 0) {
			$objpconfig = $db->fetch_object($resqlconfig);
			
			echo "<br />1. Verificando la ubicacion del conf.php ($objpconfig->config)... ";
			if (file_exists($objpconfig->config)) {
				echo "<font color=green><b>Ok</b></font>";
			} else {
				echo "<font color=red><b>Error</b></font> La ubicacion del archivo php.conf que se coloco en la configuracion del modulo esta erronea.";
			}
		}
	}
	
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."facturadian_credenciales WHERE 1"; 	
	$resql = $db->query($sql);
	if ($resql) {
		echo "<br />2. Verificando que colocaste las credenciales de (portal.facturadian.com)... ";
		
		
		if($db->num_rows($resql) > 0) {
			echo "<font color=green><b>Ok</b></font>";
			$objp = $db->fetch_object($resql);
			echo "<br />3. Verificando las credenciales de (portal.facturadian.com)... ";
			define("URLTOKEN", "https://2jr7q76fbi.execute-api.us-east-1.amazonaws.com/prod/token" );
			$token = shell_exec("curl --request POST -d '{ \"username\":\"".$objp->username."\", \"password\":\"".$objp->password."\"}' ".URLTOKEN);
			if($token=="null") {
					echo "<font color=red><b>Error</b></font> El usuario y/o contrase??a de las credenciales no estan autenticando correctamente. ";
			} else {
					echo "<font color=green><b>Ok</b></font>";
			}
		} else echo "<font color=red><b>Error</b></font> Aun no has colocado las credenciales en la opcion <b>Credenciales Facturadian</b> ";
	}
	


	//$data = explode('.',$_SERVER['SERVER_NAME']);
	//print "<br /><br />";
	//print "Bienvenido <font color=red>".$data[0]."</font> al VideoSoporte de FacturaDian  favor dar clic sobre el boton Azul <font color=blue>Entrar</font>";
	//print "<br /><br />";
	//print "<iframe allow='camera; microphone' src = 'https://videosoporte.s3.amazonaws.com/index.html?usuario=$data[0]' style='width: 100%' height='400'></iframe>";
	
	


// End of page
llxFooter();
$db->close();
