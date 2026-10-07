<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
// ----------------------------------------------------------- //
// chargement de la class "olindoteTools" //
require_once dirname(__FILE__) . '/../3rdparty/olindote/Tools/olindoteTools.inc.php';

// Check if olindoteTools class exist //
if (!class_exists('olindoteTools')) {
	$cache = cache::byKey('dependancyiCalendar');
	if (is_array($cache)) $cache->remove();
	die(__("Installer la dépendance \"class olindoteTools\" pour accéder aux paramètres du plugin.", __FILE__));
}

// Add info zone and other javascript/css option for OLINDOTE //
if (method_exists('olindoteTools','pluginInfo_writeHtml')) { echo olindoteTools::pluginInfo_writeHtml('iCalendar'); }
if (method_exists('olindoteTools','help_writeHtmlCss')) { echo olindoteTools::help_writeHtmlCss(); }
// ----------------------------------------------------------- //
?>

<form class="form-horizontal">
	<fieldset>
		<div class="form-group">
			<label class="col-sm-3 control-label">
				{{TimeOut de Synchro}}
				<?php $oT_txtHelp = array(__('Timeout au niveau de la synchro pour la récupération du fichier ics.',__FILE__));
					echo olindoteTools::help_writeHtmlIcone($oT_txtHelp); ?>
			</label>
			<div class="col-sm-2">
				<select class="configKey form-control" data-l1key="olindote::synchro::timeout">
					<option value="5">5 sec.</option>
					<option value="15">15 sec.</option>
					<option value="30">30 sec.</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">
				{{Panel: vue par defaut}}
				<?php $oT_txtHelp = array(__('Définie l\'affichage par défaut, lors de l\'ouverture de la page "panel" iCalendar.',__FILE__));
					echo olindoteTools::help_writeHtmlIcone($oT_txtHelp); ?>
			</label>
			<div class="col-sm-3">
				<select class="configKey form-control" data-l1key="olindote::panel::defaultView">
					<option value="cal_agendaDay">{{Calendrier: jour}}</option>
					<option value="cal_agendaFourDay">{{Calendrier: 4 jours}}</option>
					<option value="cal_agendaWeek">{{Calendrier: semaine}}</option>
					<option value="cal_month">{{Calendrier: mois}}</option>
					<option value="act_plan">{{Actions: planning}}</option>
				</select>
			</div>
		</div>
	</fieldset>
</form>
