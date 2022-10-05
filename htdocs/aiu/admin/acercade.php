<?php
/*   Copyright (C) 2018 Alexis José Turruella Sánchez
     Correo electrónico: alexturruella@gmail.com 
     Módulo para la gestión de los valores del AIU en el pedido y las facturas
	 Fichero admin/acercade.php
*/
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/aiu/lib/aiu.lib.php';

$langs->load("admin");
$langs->load("install");
$langs->load("errors");
$langs->load("aiu@aiu");

if (!$user->admin) accessforbidden();

$value = GETPOST('value','alpha');
$action = GETPOST('action','alpha');



$title = $langs->trans("AIU");
$helpurl = "ES:AIU";
llxHeader("",$title,$helpurl);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($title,$linkback,'setup');
$head = aiu_admin_prepare_head();

dol_fiche_head($head, 'acercade',$langs->trans('AIU'), 0, 'aiu@aiu');

$form=new Form($db);


print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Description").'</td>'."\n";
		print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
	print '</tr>'."\n";
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
		print '<td>';
			print img_object($langs->trans('AIU'),'aiu.png@aiu');
			print '&nbsp;';
			print $langs->trans("Version");
		print '</td>';
		print '<td width="60%" align="right">';			
			print '1.0';	
		print '</td>';
	print '</tr>';
	$var=!$var;

	print '<tr '.$bc[$var].'>';
		print '<td>';
			print '<img src="'.dol_buildpath('/theme/'.$conf->theme.'/img/favicon.ico',1).'"/>';
			print '&nbsp;';
			print 'Dolibarr';
		print '</td>';
		print '<td align="right">';
			print '7.0.2';					
		print '</td>';
	print '</tr>';		
print '</table>';

print '<br/>';
print '<br/>';

print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("Developer").'</td>'."\n";
	print '</tr>'."\n";
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
		print '<td rowspan="6" width="10%"><img src="../img/perfil.jpg" width="100"/></td>';
		print '<td width="20%">'.$langs->trans("Name").'</td>';
		print '<td width="70%" align="left">';			
			print 'Alexis José Turruella Sánchez';	
		print '</td>';
	print '</tr>';
	$var=!$var;
	print '<tr '.$bc[$var].'>';
		print '<td>'.$langs->trans("Phone").'</td>';
		print '<td align="left">';			
			print '(01)  5028215130';	
		print '</td>';
	print '</tr>';
	$var=!$var;
	print '<tr '.$bc[$var].'>';
		print '<td>'.$langs->trans("Email").'</td>';
		print '<td align="left">';			
			print 'alexturruella@gmail.com';	
		print '</td>';
	print '</tr>';
	$var=!$var;
	print '<tr '.$bc[$var].'>';
		print '<td>'.$langs->trans("Web").'</td>';
		print '<td align="left">';			
			print '<a href="www.valecloud.com">www.valecloud.com</a>';	
		print '</td>';
	print '</tr>';
	$var=!$var;
	print '<tr '.$bc[$var].'>';
		print '<td>Linkedin</td>';
		print '<td align="left">';			
			print '<a href="https://ec.linkedin.com/in/alexisturruella" target="_blank"><img src="../img/link.png" width="20"/></a>';	
		print '</td>';
	print '</tr>';
	$var=!$var;
	print '<tr '.$bc[$var].'>';
		print '<td>Google+</td>';
		print '<td align="left">';			
			print '<a href="https://plus.google.com/u/0/+AlexisJoseTurruellaSánchez/" target="_blank"><img src="../img/google+.png" width="20"></a>';	
		print '</td>';
	print '</tr>';	
print '</table>';

print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/aiu/lib/js/aiu.js"></script>';

dol_fiche_end();
$db->close();
llxFooter();
?>