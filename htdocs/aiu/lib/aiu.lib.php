<?php
function aiu_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();
	
	$head[$h][0] = DOL_URL_ROOT."/aiu/admin/acercade.php";
	$head[$h][1] = $langs->trans('About');
	$head[$h][2] = 'acercade';
	
	return $head;
}
?>