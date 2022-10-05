<!-- Se incluye conexion a Base de datos -->
<?php include("db.php"); ?>

<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       test1/test1index.php
 *	\ingroup    test1
 *	\brief      Home page of test1 top menu
 */


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("test1@test1"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->test1->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("Test1Area"));

print load_fiche_titre($langs->trans("Test1Area"), '', 'test1.png@test1');

print '<div class="fichecenter"><div class="fichethirdleft">';

include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$data = array(
	array('X', 1, 2, 3)
);
$labels = array("a", "b", "c");

$result = '<div class="div-table-responsive-no-min">';
$result .= '<table class="noborder nohover centpercent">';
$result .= '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>'."\n";
$result .= '<tr class="impair"><td align="center" colspan="2">';
$total = 1;  

include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
$dolgraph = new DolGraph();
$dolgraph->SetData($data);
//$dolgraph->SetDataColor($labels);
$dolgraph->SetLegend($labels);
$dolgraph->setShowLegend(2);
$dolgraph->setShowPercent(1);
$dolgraph->SetType(array('bars'));
$dolgraph->setHeight('150');
$dolgraph->setWidth('300');
$dolgraph->draw('idgraphstatus');
$result .= $dolgraph->show($total ? 0 : 1);

$result .= '</td></tr>';

print($result);

// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
/*if (! empty($conf->test1->enabled) && $user->rights->test1->read)
{
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= ", s.code_client";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	if ($socid)	$sql.= " AND c.fk_soc = ".((int) $socid);

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftMyObjects").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$row->id=$obj->rowid;
				$row->ref=$obj->ref;
				$row->ref_client=$obj->ref_client;
				$row->total_ht = $obj->total_ht;
				$row->total_tva = $obj->total_tva;
				$row->total_ttc = $obj->total_ttc;

				print $row->getNomUrl(1);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '<td class="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{

				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}*/
//END MODULEBUILDER DRAFT MYOBJECT 


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

// BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
/* if (! empty($conf->test1->enabled) && $user->rights->test1->read)
{ */

	$sql = "SELECT *";
	$sql.= " FROM ".MAIN_DB_PREFIX."test1_campoprueba as s";
	//if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.status = 1";
	//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	//if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = mysqli_query($conn, $sql);

	//print("<div>$sql</div>");
	if ($resql)
	{
		$num = $db->num_rows($resql);
		//print("<div>num: $num</div>");
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("Últimas Entradas Modificadas", $max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$row->id=$objp->rowid;
				$row->ref=$objp->ref;
				$row->label=$objp->label;
				$row->status = $objp->status;
				$row->nombre = $objp->nombre;

				//print("rowid: $row->id");

				print '<tr class="oddeven">';
				print '<td class="nowrap"><a href="http://localhost/custom/test1/campoprueba_card.php?id='. $row->id .'">'. $row->nombre .'</a></td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	} else {
		print("<div>:V</div>");
	}
//}


print '</div></div></div>';


// End of page
llxFooter();
$db->close();
