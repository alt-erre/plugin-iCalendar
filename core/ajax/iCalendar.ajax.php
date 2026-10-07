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

/**
 * Point d'entrée AJAX pour le plugin iCalendar
 *
 * @author abarrau
 * @author Alterre (modifications & compatibilité Jeedom 4.4+)
 *
 * Actions gérées :
 * - downloadICS : Téléchargement du fichier ICS généré en cache
 * - getEventsInCalendar : Récupération des événements formatés pour FullCalendar
 * - getHistoryActions : Récupération de l'historique des actions déclenchées
 * - deleteHistoryActions : Suppression de l'historique des actions pour une date donnée
 * - getCalDAVList : Découverte et listing des calendriers disponibles sur le serveur CalDAV
 */

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');
	if (!isConnect()) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	// ajoute la class //
	$_plugin = 'iCalendar';
	include_file('core', $_plugin, 'class', $_plugin);	

	// ------------------------------- ID Obligatoire //
	if (init('cmdId') > 0) {
		// récupère les information de l'équipement //
		$iCalendarCmd = cmd::byId(init('cmdId'));
		$iCalendarCmd->_whatLog = 'AJAX';
		if (!is_object($iCalendarCmd)) {
			throw new Exception(__('Commande inconnue, verifié l\'id', __FILE__).': '.init('cmdId'));
		}
		
		// Action = downloadICS : Téléchargement du fichier ICS en cache
		if (init('action') == 'downloadICS') {
			$_prevName = $iCalendarCmd->getFileCacheName(true);
			if (file_exists($_prevName)) {
				$path_parts = pathinfo($_prevName);
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename=' . $path_parts['basename']);
				readfile($_prevName);
				log::add($iCalendarCmd->logFN(), 'debug', '['.$iCalendarCmd->_whatLog.'|'.$iCalendarCmd->getEqLogic()->getId().'|'.$iCalendarCmd->getId().'] downloadICS() file is ok for download :'.$_prevName);
			} else {
				log::add($iCalendarCmd->logFN(), 'debug', '['.$iCalendarCmd->_whatLog.'|'.$iCalendarCmd->getEqLogic()->getId().'|'.$iCalendarCmd->getId().'] downloadICS() ERROR: file not exist :'.$_prevName);
			}
			die();
		}
		
		// Action = getEventsInCalendar : Récupère les données d'événements pour l'affichage de l'agenda
		if (init('action') == 'getEventsInCalendar') {
			$_aParam = array('cmdId'=>init('cmdId'));
			$_today = iCalendarCmd::getCacheDateStart();
			if (init('dateStart')!='') {
				list($_dsY,$_dsM,$_dsD) = explode('-',init('dateStart'));
				$_aParam['dStart'] = mktime('00','00','00',$_dsM,$_dsD,$_dsY);
			} else {
				$_aParam['dStart'] = date('U', strtotime('-3 months', $_today));
			}
			if (init('dateEnd')!='') {
				list($_deY,$_deM,$_deD) = explode('-',init('dateEnd'));
				$_aParam['dEnd'] = mktime('23','59','59',$_deM,$_deD,$_deY)+1;
			} else {
				$_aParam['dEnd'] = date('U', strtotime('+6 months', $_today));
			}
			log::add($iCalendarCmd->logFN(), 'debug', '['.$iCalendarCmd->_whatLog.'|'.$iCalendarCmd->getEqLogic()->getId().'|'.$iCalendarCmd->getId().'] getEventsInCalendar() starting... with param: '.print_r($_aParam, true));
          	if (($_aRes = $iCalendarCmd->getEventsInCalendar($_aParam))!==false) {
				ajax::success(array('json'=>$_aRes, 'dStart'=>date('Y-m-d',$_aParam['dStart']), 'dEnd'=>date('Y-m-d',$_aParam['dEnd'])));
			} else {
				ajax::error(__('La requête "getEventsInCalendar()" n\'a pas put être exécutée correctement (voir la Log).',__FILE__));
			}
		}
		
		// Action = getHistoryActions : Récupère les données historiques des actions
		if (init('action') == 'getHistoryActions') {
			$_aParam = array('cmdId'=>init('cmdId'), 'withCache'=>1);
			if (init('dateStart')!='') $_aParam['dStart'] = init('dateStart');
			if (init('dateEnd')!='') $_aParam['dEnd'] = init('dateEnd');
			log::add($iCalendarCmd->logFN(), 'debug', '['.$iCalendarCmd->_whatLog.'|'.$iCalendarCmd->getEqLogic()->getId().'|'.$iCalendarCmd->getId().'] getHistoryActions() starting... with param: '.print_r($_aParam, true));
			if (($_aRes = $iCalendarCmd->getHistoryActions($_aParam))!==false) {
				if (count($_aRes)>0) {
					ajax::success($_aRes);
				} else {
					if ($iCalendarCmd->getConfiguration('actionIsHistorized','1')=='0') {
						ajax::error(__('Cette commande n\'accepte pas l\'historisation. Activez l\'option avant.',__FILE__));
					} else {
						ajax::success(__('Aucune action historisée sur cette période pour cette commande.',__FILE__));
					}
				}
			} else {
				ajax::error(__('La requête "getHistoryActions()" n\'a pas put être exécutée correctement (voir la Log).',__FILE__));
			}
		}
		
		// Action = deleteHistoryActions : Supprime les données historiques des actions
		if (init('action') == 'deleteHistoryActions') {
			$_aParam = array('cmdId'=>init('cmdId'), 'dtDelete'=>init('dtDelete'));
			log::add($iCalendarCmd->logFN(), 'debug', '['.$iCalendarCmd->_whatLog.'|'.$iCalendarCmd->getEqLogic()->getId().'|'.$iCalendarCmd->getId().'] deleteHistoryActions() starting... with param: '.print_r($_aParam, true));
			if (($_aRes = $iCalendarCmd->deleteHistoryActions($_aParam))==true) {
				ajax::success(__('Les actions de la journée du '.init('dtDelete').', ont correctement été supprimées.',__FILE__));
			} else {
				ajax::error(__('La requête "deleteHistoryActions()" n\'a pas put être exécutée correctement (voir la Log).',__FILE__));
			}
        }
		
		// Action = getCalDAVList : Découvre et liste les calendriers CalDAV
		if (init('action') == 'getCalDAVList') {
			log::add($iCalendarCmd->logFN(), 'debug', '['.$iCalendarCmd->_whatLog.'|'.$iCalendarCmd->getEqLogic()->getId().'|'.$iCalendarCmd->getId().'] getCalDAVList() starting... for cmdId='.init('cmdId'));
			if ((($_sURL = $iCalendarCmd->getConfiguration('iCalendarUrl',''))!='') 
				&& (($_sUser = $iCalendarCmd->getConfiguration('calDavUser',''))!='')
				&& (($_sPwd = $iCalendarCmd->getConfiguration('calDavPwd',''))!='') ) {
					$_oSCDC = new SimpleCalDAVClientExt();
					try {
						$_oSCDC->connect($_sURL, $_sUser, $_sPwd);
					}
					catch (Exception $e) {
						$_msg = __("La connexion n'a pas put être réalisée, pour le flux suivant", __FILE__).': '. $_sURL;
						log::add($iCalendarCmd->logFN(), 'debug', '['.$iCalendarCmd->_whatLog.'|'.$iCalendarCmd->getEqLogic()->getId().'|'.$iCalendarCmd->getId().'] getCalDAVList(): ERROR: '.$e->__toString());
						log::add($iCalendarCmd->logFN(), 'info', '['.$iCalendarCmd->_whatLog.'|'.$iCalendarCmd->getEqLogic()->getId().'|'.$iCalendarCmd->getId().'] getCalDAVList(): ERROR: '.$_msg);
						$iCalendarCmd->setHealthNetwork('X');
						ajax::error($_msg);
					}
					$_arrayOfCalendars = $_oSCDC->findCalendars();
					$_aCalDAV = array();
					foreach($_arrayOfCalendars as $_oCalDAV) {
						$_aCalDAV[] = array('id'=>$_oCalDAV->getCalendarID(), 'name'=>$_oCalDAV->getDisplayName(), 'bgcolor'=>$_oCalDAV->getRBGcolor());
					}
					if (count($_aCalDAV)>0) {
						ajax::success($_aCalDAV);
					} else {
						ajax::error(__('Aucun calendrier n\'est configuré sur ce serveur.',__FILE__));
					}
			} else {
				ajax::error(__('Les informations de connexion ne sont pas correctement enregistrées (sauvegarder une 1ère fois avant de lancer la recherche).',__FILE__));
			}
		}
	}

	//---- Action liée à la class olindoteTools ---- //
	if (method_exists('olindoteTools','execAjaxRequest')) {
		$_oT_return = olindoteTools::execAjaxRequest($_plugin, init('action'));
		if (is_array($_oT_return)) {
			switch($_oT_return['type']) {
				case "na": break;
				case "success": ajax::success($_oT_return['value']); break;
				case "error": ajax::error($_oT_return['value']); break;
			}
		}
	}
	//---- END:: Action liée à la class olindoteTools ---- //
	
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}