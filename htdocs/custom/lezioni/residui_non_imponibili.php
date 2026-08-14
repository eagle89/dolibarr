<?php
/* Copyright (C) 2026 Wheelbite ASD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       lezioni/residui_non_imponibili.php
 * \ingroup    lezioni
 * \brief      Residui non imponibili degli istruttori
 */

$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, $i + 1).'/main.inc.php')) {
	$res = @include substr($tmp, 0, $i + 1).'/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, $i + 1)).'/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, $i + 1)).'/main.inc.php';
}
if (!$res && file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once __DIR__.'/class/lezionistats.class.php';
require_once __DIR__.'/class/myuser.class.php';

$langs->loadLangs(array('lezioni@lezioni'));

if (!isModEnabled('lezioni')) {
	accessforbidden('Module not enabled');
}
if (!$user->hasRight('lezioni', 'lezione', 'read')) {
	accessforbidden();
}

$year = GETPOST('year_filter', 'int');
if (empty($year)) {
	$year = date('Y');
}

$stats = new LezioniStats($db);
$allResidui = $stats->getIstrYearlyResidui($year);
$residui = array();
foreach ($allResidui as $row) {
	if ($row[0] == substr($year, -2)) {
		$residui[] = $row;
	}
}

llxHeader('', 'Residui Non Imponibili', '', '', 0, 0, '', '', '', 'mod-lezioni page-index');
print load_fiche_titre('Residui Non Imponibili', '', 'lezioni.png@lezioni');

print '<div style="margin: 10px 0;">';
print '<form method="GET" action="">'.PHP_EOL;
print '<label>Anno: </label>';
print '<select name="year_filter" onchange="this.form.submit();">';
$yearsAvailable = $stats->getYearFilterValue();
rsort($yearsAvailable);
if (empty($yearsAvailable)) {
	$yearsAvailable[] = date('Y');
}
foreach ($yearsAvailable as $yearOpt) {
	$selected = ($yearOpt == $year) ? ' selected' : '';
	print '<option value="'.((int) $yearOpt).'"'.$selected.'>'.((int) $yearOpt).'</option>';
}
print '</select>';
print '</form>';
print '</div>'.PHP_EOL;

if (!empty($residui)) {
	print '<div class="div-table-responsive div-table-responsive-no-min">';
	print '<table class="tagtable nobottomiftotal liste" style="user-select:text; -webkit-user-select:text; -moz-user-select:text; -ms-user-select:text; table-layout:fixed; width:100%;">'."\n";
	print '<tr class="liste_titre"><th colspan="4">Residui Non Imponibili dal 13-01-'.$year.'</th></tr>';
	print '<tr class="liste_titre">';
	print '<th class="wrapcolumntitle liste_titre" title="Istruttore">Istruttore</th>';
	print '<th class="wrapcolumntitle liste_titre" title="Codice Fiscale">Codice Fiscale</th>';
	print '<th class="wrapcolumntitle liste_titre" title="Totale">Totale Compenso (€)</th>';
	print '<th class="wrapcolumntitle liste_titre" title="Residuo" data-toggle="Dal 13-01-'.$year.'" data-placement="top">Residuo Esentasse WB (€)</th>';
	print '</tr>';

	foreach ($residui as $residuRow) {
		$usrResidui = new MyUser($db);
		$usrResidui->fetch($residuRow[1]);
		$cf = trim((string) $usrResidui->national_registration_number);
		if (empty($cf) && !empty($usrResidui->fk_member)) {
			$adh = new Adherent($db);
			if ($adh->fetch($usrResidui->fk_member) > 0) {
				$cf = trim((string) ($adh->array_options['options_codicefiscale'] ?? ''));
			}
		}
		if (empty($cf)) {
			$cf = '-';
		}

		print '<tr class="oddeven">';
		print '<td>'.$usrResidui->getNomUrl(-1).'</td>';
		print '<td style="max-width:140px; white-space:normal; word-break:break-word;">'.dol_escape_htmltag($cf).'</td>';
		print '<td>'.number_format(round($residuRow[2], 2), 2, ',', '').'</td>';
		print '<td>'.number_format(round($residuRow[3], 2), 2, ',', '').'</td>';
		print '</tr>';
	}
	print '</table></div>';
} else {
	print '<div class="div-table-responsive div-table-responsive-no-min">';
	print '<table class="tagtable nobottomiftotal liste">'."\n";
	print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans('None').'</td></tr>';
	print '</table></div>';
}

llxFooter();
$db->close();
