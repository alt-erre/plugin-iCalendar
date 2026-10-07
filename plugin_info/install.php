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
// chargement des infos pour la class "olindoteTools" //
require_once dirname(__FILE__) . '/../3rdparty/olindote/Tools/olindoteTools.inc.php';
// last version //
define('OT_PLUGIN_THIS_VERSION','1.6.1');  // ------ !!!!! PENSER A CHANGER LA VALEUR !!!!! -------- //

// ---------------------------------------------------------------------------- INSTALL //
function iCalendar_install() {
	$_plugin = 'iCalendar';
	log::add($_plugin.'_update', 'info', '[INSTALL START] (v'.OT_PLUGIN_THIS_VERSION.') ===== ');
	if (olindotePluginInstallUpdate($_plugin,'INSTALL','_update_bdd',array(),OT_PLUGIN_THIS_VERSION)===true) {
		//-- ACTIONS DEDIEES au PLUGIN -- //
		// création du répertoire temporaire //
		$_tmpDir = jeedom::getTmpFolder($_plugin);
		log::add($_plugin.'_update', 'info', __('Le répertoire vient d\'être créé',__FILE__).' : '. $_tmpDir );
		config::save('olindote::panel::defaultView', 'cal_agendaFourDay', $_plugin);
		config::save('olindote::synchro::timeout', 15, $_plugin);
		config::save('olindote::health::network::nbLast', 15, $_plugin);
		//-- END // ACTIONS DEDIEES au PLUGIN -- //
	} else {
		log::add($_plugin.'_update', 'warning', 'ERROR during execute function "olindotePluginInstallUpdate(INSTALL)".');		
	}
	log::add($_plugin.'_update', 'info', '[INSTALL END] ===== ///');		
}

// ---------------------------------------------------------------------------- UPDATE //
	// ************** v0.0.02 : rien
	// ************** v0.1.00 : _update_0-1-00.php >> arrêt
	// ************** v0.1.01 : rien
	// ************** v0.1.02 : _update_0-1-02.php >> arrêt
	// ************** v0.1.03 : rien
	// ************** v1.0.01 : _update_1-0-01.php >> arrêt
	// ************** v1.0.02 - v1.1.00 : rien
	// ************** v1.2.0 (changement de numérotation) : rien
	// ************** v1.3.0 : _update_1-3-0.php >> arrêt
	// ************** v1.4.0 : rien
	// ************** v1.5.0 : rien
	// ************** v1.6.0/1.6.1/1.6.2 : rien
function iCalendar_update() {
	$_plugin = 'iCalendar';
        $_currentVersion = olindoteToolsGetValueInJsonInfo('_oT_pluginVersion',$_plugin);
	$_aVersUpdateFile = array();		// <<<<< ATTENTION >>>>> Bien ajouter la version ici.
	log::add($_plugin.'_update', 'info', '[UPDATE START] (v'.$_currentVersion.') ===== ');
	if (olindotePluginInstallUpdate($_plugin,'UPDATE','_update_bdd',$_aVersUpdateFile,$_currentVersion)===true) {
		//-- ACTIONS DEDIEES au PLUGIN -- //
				// déjà traité dans la fonction générique //
		//-- END // ACTIONS DEDIEES au PLUGIN -- //
	} else {
		log::add($_plugin.'_update', 'warning', 'ERROR during execute function "olindotePluginInstallUpdate(UPDATE)".');		
	}
	log::add($_plugin.'_update', 'info', '[UPDATE END] ===== ///');		
}

// ---------------------------------------------------------------------------- REMOVE //
function iCalendar_remove() {
	$_plugin = 'iCalendar';
        $_currentVersion = olindoteToolsGetValueInJsonInfo('_oT_pluginVersion',$_plugin);
	log::add($_plugin.'_update', 'info', '[REMOVE START] (v'.$_currentVersion.') ===== ');
	if (olindotePluginRemove($_plugin,$_currentVersion)===true) {
		//-- ACTIONS DEDIEES au PLUGIN -- //
			// ajoute la class //
			if (!class_exists('iCalendarTools')) {	include_file('core', 'iCalendar', 'class', 'iCalendar'); }
			// suppression des fichiers du répertoire //
			iCalendarTools::cleanCacheDirectory();
			// --- suppression de la table --- //
			$_sql = "DROP TABLE IF EXISTS `iCalendar_Actions`;";
			DB::Prepare($_sql, array(), DB::FETCH_TYPE_ROW);
		//-- END // ACTIONS DEDIEES au PLUGIN -- //
	} else {
		log::add($_plugin.'_update', 'warning', 'ERROR during execute function "olindotePluginRemove()".');		
	}
	log::add($_plugin.'_update', 'info', '[REMOVE END] ===== ///');			
}


// ********************************************************* Fonctions Complémentaires //


?>
