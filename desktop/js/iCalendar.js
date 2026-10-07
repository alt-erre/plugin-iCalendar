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
 * Logique d'interface de configuration du plugin iCalendar (Desktop Jeedom)
 *
 * @author abarrau
 * @author Alterre (modifications & compatibilité Jeedom 4.4+)
 *
 * Architecture des commandes iCalendar :
 * - Commande J0 (originalCmdId == '') :
 *   Commande principale représentant la journée en cours. Chaque agenda configuré
 *   dans l'équipement possède un tableau dédié (.tableCmd) avec les paramètres d'accès
 *   (fichier ICS ou serveur CalDAV), les options de filtrage et d'affichage, ainsi que
 *   les réglages d'automatisation (lancement d'actions / scénarios / interactions).
 * - Commandes J1 à J6 (originalCmdId == id_J0) :
 *   Sous-commandes de projection temporelle pour J+1 jusqu'à J+6 (selon la période à traiter).
 *   Elles héritent des données de la commande J0 et sont regroupées sous la ligne principale.
 *   L'icône liste (fa-list-alt) permet de déplier/replier ces commandes associées.
 * - Commande Action 'ExecuteFunction' :
 *   Commande système créée pour exécuter des fonctions d'interrogation dans les scénarios
 *   (getTimeStart, getTimeEnd, getTitle, getUid, getLocation, etc.).
 */

/**
 * Génère et insère dans le DOM la structure de configuration d'une commande iCalendar
 *
 * Si la commande est une commande principale (J0 sans originalCmdId), crée un tableau complet
 * (.tableCmd) avec les formulaires de configuration (ICS/CalDAV, affichage, actions).
 * Si la commande est une commande secondaire (J1 à J6 avec originalCmdId), l'ajoute comme
 * ligne enfant (.iCalOtherCmd) rattachée à sa commande parente J0.
 *
 * @param {Object} [_cmd] - Données de la commande fournies par Jeedom
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {},'type':'info'};
    }
	if (_cmd.type=='info') {
		if (init(_cmd.configuration.originalCmdId) == '') {
			//-- info agenda //
			var table = '<table class="table table-bordered panel-default tableCmd" style="padding-bottom:10px;" data-groupcmd_id="' + init(_cmd.id) + '">';
				table += '<thead class="panel-heading"><tr style="background-color:#A395E2!important;">';
					table += "<th style=''><i class='techno techno-fleches cursor' title=\"{{Déplacement de l'agenda}}\"></i> | {{Nom, Type et paramètres d'accès}}</th>";
					table += "<th style='width:30%;'>{{Données d'utilisation}}</th>";
					table += "<th style='width:28%;'>{{Options graphique}}</th>";
					table += "<th style='width:5%;'>{{Actions}}</th>";
				table += "</tr></thead><tbody>";
			table += '<tr class="cmd iCalCmdJ0" data-cmd_id="' + init(_cmd.id) + '">';
			table += '<td style="font-size:90%;">';
				table += '<div class="" style="height:50px;"><span class="col-sm-6" style="padding:0px;">{{Nom}} : <br/><input class="cmdAttr form-control input-sm" data-l1key="name" /></span>';
				table += '<span class="col-sm-6" style="">{{Nom ics}} : <br/><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="icsCalendarName" readonly /></span></div>';
				table += '<div class="" style="height:35px;margin-top:10px;"><span class="col-sm-4" style="padding-left:0px;">{{Type d\'agenda}} :</span><span class="col-sm-8"><select class="cmdAttr form-control input-sm" style="margin-bottom:3px;" data-l1key="configuration" data-l2key="iCalendarType">';
					table += '<option value=\'ics_down_file\'> {{récupération d\'un fichier ics}} </option>';
					table += '<option value=\'caldav_read\'> {{lecture d\'un agenda CalDav}} </option>';
				table += '</select></span></div>';
				table += '<div class="" style="height:57px;">{{URL de l\'agenda}} : <br/><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="iCalendarUrl" style="width:98%;" /></div>';
				table +='<div class="divShowCnxInfo" style="height:105px;display:none;">';
					table += '<div><span class="col-sm-5">{{Utilisateur}} :</span><span class="col-sm-7" style="padding-left:0px!important;"><input class="cmdAttr form-control input-sm" style="margin-bottom:3px;" data-l1key="configuration" data-l2key="calDavUser"></span></div>';
					table += '<div><span class="col-sm-5" style="padding-right:1px!important;">{{Mot de passe}} :</span>';
						table += '<span class="col-sm-7" style="padding-left:0px!important;"><div class="input-group" style="margin-bottom:3px;"><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="calDavPwd" type="password"><div class="input-group-addon cursor"><i class="fa fa-eye" id="showPwd"></i></div></div></span>';
					table += '<div>';
						table += '<span class="col-sm-5">{{Nom agenda}} <span class="helpTT"><sup><i class="fa fa-question-circle cursor" style="font-size:1em;color:grey;"></i></sup>';
						table += '<span>{{Afficher la liste des agendas connus au niveau du serveur CalDAV}}.</span></span></span>';
						table += '<span class="col-sm-7" style="padding-left:0px!important;"><input class="cmdAttr form-control input-sm" style="margin-bottom:3px;" data-l1key="configuration" data-l2key="calDavCalName">';
					table += '</div>';
				table += '</div>';
			table += '</td>';
			//-- info paramètre données //
			table += '<td style="font-size:90%;">';
				table += '<div class="" style="height:40px;"><span class="col-sm-5">{{Format donnée}} :</span><span class="col-sm-7"><select class="cmdAttr form-control input-sm" style="margin-bottom:3px;" data-l1key="configuration" data-l2key="viewStyle">';
					table += '<option value=\'1day_today\'> {{évènements sur la journée}} </option>';
					table += '<option value=\'1day_next1hour\'> {{évènements sur heure à venir}} </option>';
					table += '<option value=\'current\'> {{évènements courants}} </option>';
				table += '</select></span></div>';
				table += '<div class="divPeriodeWorking" style="height:40px;"><span class="col-sm-6">{{Période à traiter}} : &nbsp;<i class="fa fa-list-alt cursor" style="color:#F8B04C!important;" title="{{Afficher la liste des commandes supplémentaires associées à cet agenda.}}"></i></span><span class="col-sm-6"><select class="cmdAttr form-control input-sm" style="margin-bottom:3px;" data-l1key="configuration" data-l2key="periodeWorking">';
					table += '<option value=\'0\'> {{jour courant}} </option>';
					table += '<option value=\'1\'> + {{lendemain}} </option>';
					table += '<option value=\'2\'> + 2 {{jours}} </option>';
					table += '<option value=\'6\'> 1 {{semaine}} </option>';
				table += '</select></span><input class="cmdAttr" data-l1key="configuration" data-l2key="periodeWorkingBack" style="display:none;"></div>';
				table += '<div class="divTitleOnly" style="height:40px;"><span class="col-sm-5">{{Titre uniquement}} :</span><span class="col-sm-7"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="titleOnly"/></span></div>';
				table += '<div class="" style="height:45px;padding:5px 0px;margin-bottom:6px;border-top:solid 1px #999999;border-bottom:solid 1px #999999;"><span class="col-sm-5">{{Valeur par défaut}} :</span><span class="col-sm-7"><input class="cmdAttr form-control input-sm" style="margin-bottom:3px;" data-l1key="configuration" data-l2key="defaultValue"></span></div>';
				table += '<div class="divIndicDebFin" style="height:30px;"><span class="col-sm-8">{{Indicateurs début/fin}} :</span><span class="col-sm-4"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="indicDebFin" /></span></div>';
				table += '<div class="divLaunchActSc" style="height:30px;"><span class="col-sm-8">{{Autoriser les actions/scénarios}} ';
					table += '<span class="helpTT"><sup><i class="fa fa-question-circle cursor" style="font-size:1em;color:grey;"></i></sup>';
					table += '<span>{{Autorise et définie la trame pour lancer un scénario ou une action directement <u>par le plugin</u>.}}</span></span>';
				table += ':</span><span class="col-sm-4"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="acceptLaunchActSc" /></span></div>';
				table += '<div class="divLaunchInteract" style="height:30px;"><span class="col-sm-8">{{Autoriser les intéractions}} ';
					table += '<span class="helpTT"><sup><i class="fa fa-question-circle cursor" style="font-size:1em;color:grey;"></i></sup>';
					table += '<span>{{Autorise l\'interprétation par les "interactions" (ne fonctionne qu\'en début d\'événement)}}</span></span>';
				table += ':</span><span class="col-sm-4"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="acceptLaunchInteract" /></span></div>';
				table += '<div class="divHistory" style="height:30px;display:none;"><span class="col-sm-8">{{Historiser les actions}} :</span><span class="col-sm-4"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="actionIsHistorized" /></span></div>';
				table += '<div class="" style="height:40px;padding:5px;border-top:solid 1px #999999;"><span class="col-sm-6">{{Fréquence synchro}} : </span><span class="col-sm-6"><select class="cmdAttr form-control input-sm" style="margin-bottom:3px;" data-l1key="configuration" data-l2key="refreshPeriod">';
					table += '<option value="30">30 min.</option>';
					table += '<option value="60">1 h.</option>';
					table += '<option value="180">3 h.</option>';
					table += '<option value="360">6 h.</option>';
					table += '<option value="720">12 h.</option>';
					table += '<option value="1440">24 h.</option>';
				table += '</select></span></div>';
			table += '</td>';
			//-- info paramètre graphique //
			table += '<td style="font-size:90%;">';
				table += '<div class="" style="height:30px;"><span class="col-sm-8">{{Afficher calendrier}} :</span><span class="col-sm-4"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked /></span></div>';
				table += '<div class="divShowHour" style="height:30px;"><span class="col-sm-8">{{Afficher heure}} :</span><span class="col-sm-4"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="showHour"/></span></div>';
				table += '<div class="divShowHour24H" style="height:30px;"><span class="col-sm-8">{{Afficher heure event de 24h}} :</span><span class="col-sm-4"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="showHour24H"/></span></div>';
				table += '<div class="divLocation" style="height:30px;"><span class="col-sm-8">{{Afficher l\'emplacement}} :</span><span class="col-sm-4"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="showLocation"/></span></div>';
				table += '<div class="divPeriodeView" style="height:45px;"><span class="col-sm-6">{{Période à afficher}} :</span><span class="col-sm-6"><select class="cmdAttr form-control input-sm" style="margin-bottom:3px;" data-l1key="configuration" data-l2key="periodeView">';
					table += '<option value=\'0\'> {{jour courant}} </option>';
				table += '</select></span></div>';
				table += '<div class="divLocation" style="height:30px;"><span class="col-sm-8">{{Ne pas afficher le nombre d\'évènement}} :</span><span class="col-sm-4"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="showNotNbEvent"/></span></div>';
			table += '</td>';
			table += '<td style="width:100px;" rowspan="2">';
			table += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="originalCmdId" value="" style="display:none;">';
			table += '<input class="cmdAttr form-control input-sm" data-l1key="type" value="info" style="display:none;">';
			table += '<input class="cmdAttr form-control input-sm" data-l1key="subType" value="string" style="display:none;">';
			table += '<input class="cmdAttr" data-l1key="id" style="display:none;">'; // CHAMPS OBLIGATOIRE //
			if (is_numeric(_cmd.id)) {
				table += '<a class="btn btn-default btn-xs cmdAction" data-action="configure" style="margin-bottom:6px;"><i class="fa fa-cogs"></i> {{Config.}}</a><br/> ';
				table += '<a class="btn btn-success btn-xs cmdAction" data-action="test" style="margin-bottom:6px;"><i class="fa fa-rss"></i> {{Tester}}</a><br/>';
			}
			table += '<a class="btn btn-danger btn-xs cmdAction" data-action="remove" style="margin-bottom:6px;"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>';
			table += '</td></tr>';
          	// info de téléchargement et synchro //
       		table += '<tr class="iCalTR"  data-cmd_id="' + init(_cmd.id) + '">';
				table += '<td><span style="font-weight:bold;">{{Information}} : </span>';
				table += '<span style="font-size:85%;padding:0px 15px;">{{Date du fichier}} : ' + init(_cmd.configuration.dateSaveFile) + '</span>';
				if (init(_cmd.configuration.dateSaveFile)!='') {
					table += '<a class="btn btn-success btn-xs" id="bt_downloadICS"><i class="fa fa-cloud-download"></i> {{Télécharger}}</a>';
				}
				table += '</td>';
				table += '<td style="font-size:85%;">{{Date synchro précédente}} : ' + init(_cmd.configuration.dateSyncLast) + '</td>';
				table += '<td style="font-size:85%;">{{Date synchro suivante}} : ' + init(_cmd.configuration.dateSyncNext) + '</td>';
       		table += '</tr></tbody></table>';
			$('#table_cmd').append(table);
			_tableCmd = '.tableCmd[data-groupcmd_id="' + init(_cmd.id) + '"]';
			$(_tableCmd + ' tr:nth-last-child(2)').setValues(_cmd, '.cmdAttr');
			$(_tableCmd + ' tr:nth-last-child(2) .cmdAttr[data-l1key="configuration"][data-l2key="periodeWorkingBack"]').value($(_tableCmd + ' tr:nth-last-child(2) .cmdAttr[data-l1key="configuration"][data-l2key="periodeWorking"]').value());
			if ($(_tableCmd + ' tr:nth-last-child(2) .cmdAttr[data-l1key="configuration"][data-l2key="viewStyle"]').value()!='current') {
				$(_tableCmd + ' tr:nth-last-child(2) .divTitleOnly').hide();
				$(_tableCmd + ' tr:nth-last-child(2) .cmdAttr[data-l1key="configuration"][data-l2key="titleOnly"]').value(0);
			}
			// recupère les commandes déjà créées (astuce au cas où les commandes J+ arrivent avant le commande J (tableau non créé)) //
			if ($('#tr_cmd_tmp .tbody_tmp[data-cmd_id="'+ init(_cmd.id) +'"]').length>0) {
				$(_tableCmd + ' tbody').append($('#tr_cmd_tmp .tbody_tmp[data-cmd_id="'+ init(_cmd.id) +'"]').html());
				$('#tr_cmd_tmp .tbody_tmp[data-cmd_id="'+ init(_cmd.id) +'"]').remove();
			}
			
		} else {
			var tr = '<tr class="iCalTR iCalOtherCmd" data-cmd_id="' + init(_cmd.id) + '" style="display:none;" data-original_id="'+ init(_cmd.configuration.originalCmdId) + '">';
			tr += '<td style="font-size:85%;background-color:#F8B04C!important;" colspan="3">';
				tr += '{{Nom}} : <span class="cmdAttr" data-l1key="name"></span>';
          		tr += '<span style="float:right;">(id=<span class="cmdAttr" data-l1key="id"></span>)</span>';
				tr += '<div style="display:none;">';
					tr += '<span class="cmdAttr" data-l1key="type"></span>';
					tr += '<span class="cmdAttr" data-l1key="subType" value="string"></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="defaultValue"></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="viewStyle"></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="indicDebFin"/></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="periodeWorking"></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="periodeWorkingBack"></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="showHour"/></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="showHour24H"/></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="showLocation"/></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="periodeView"></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="originalCmdId"></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="actionIsHistorized"></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="acceptLaunchActSc"></span>';
					tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="acceptLaunchInteract"></span>';
				tr += '</div>';
			tr += '<td style="background-color:#F8B04C!important;">';
				if (is_numeric(_cmd.id)) {
					tr += '<a class="btn btn-default btn-xs cmdAction" data-action="iCalTest"><i class="fa fa-rss"></i> {{Tester}}</a>';
				}
			tr +='</td></tr>';
			// crée temporaire les lignes dans un Div, le temps que le tableau de la commande J0 soit créé //
			if ($('.tableCmd[data-groupcmd_id="' + init(_cmd.configuration.originalCmdId) + '"] tbody').length>0) {
				$('.tableCmd[data-groupcmd_id="' + init(_cmd.configuration.originalCmdId) + '"] tbody').append(tr);
				$('.tableCmd[data-groupcmd_id="' + init(_cmd.configuration.originalCmdId) + '"] tbody tr:last').setValues(_cmd, '.cmdAttr');
			} else {
				if ($('#tr_cmd_tmp .tbody_tmp[data-cmd_id="'+ init(_cmd.configuration.originalCmdId) +'"]').length==0) {
					$('#tr_cmd_tmp').append('<div class="tbody_tmp" data-cmd_id="'+ init(_cmd.configuration.originalCmdId) +'"></div>');
				}
				$('#tr_cmd_tmp .tbody_tmp[data-cmd_id="'+ init(_cmd.configuration.originalCmdId) +'"]').append(tr);
				$('#tr_cmd_tmp .tbody_tmp[data-cmd_id="'+ init(_cmd.configuration.originalCmdId) +'"] tr:last').setValues(_cmd, '.cmdAttr');
			}
		}
	}
}
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".tableCmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

/**
 * Met à jour l'interface lors de l'affichage ou du rafraîchissement d'un équipement iCalendar
 *
 * Réinitialise les drapeaux de configuration (forceSynchro, importantDataChanged)
 * et purge les anciennes tables de commandes avant reconstruction.
 *
 * @param {Object} _eqLogic - Données de l'équipement affiché
 */
function printEqLogic(_eqLogic) {
	// ajoute le nom de l'équipement au "tab"
	$('.nav-tabs li:nth-child(2) .eqNameTab').value("Eql:[" + _eqLogic.name + "]");
	$('.eqLogicAttr[data-l1key="configuration"][data-l2key="forceSynchro"]').value(0);
	$('.eqLogicAttr[data-l1key="configuration"][data-l2key="importantDataChanged"]').value(0);
	$('.iCalTR').remove();
	$('.tableCmd').remove();
}

/**
 * Intercepte la sauvegarde de l'équipement pour modifications éventuelles avant envoi au backend
 *
 * @param {Object} _eqLogic - Objet équipement à sauvegarder
 * @returns {Object} Objet équipement validé
 */
function saveEqLogic(_eqLogic) {
	return _eqLogic;
}
//------------------------------------------------------------------------------ POPUP //
// pour l'aide à l'écriture d'un événement dans l'agenda : nouvelle trame | scénario //
$('#bt_helpWriteICalEventDecription').on('click', function (event) {
	$('#md_modal').dialog({title: "{{Assistance à la création d'un événement \"Scénario\" ou \"Action\" dans la description d'un événement Agenda}}"});
	$('#md_modal').load('index.php?v=d&plugin=iCalendar&modal=help-eventByDescription&id=' + $('.eqLogicAttr[data-l1key=id]').value(), function () {
		//-- type //
		$('#iCalendar_type').on('change', function (event) {
			if ($(this).value()=='action') {
				$('.viewScenario').hide();
				$('.viewAction').show();
			} else if ($(this).value()=='scenario') {
				$('.viewScenario').show();
				$('.viewAction').hide();
			}
			$('#iCalendar_EventFormat').value('');
		});
		// récupération des commandes actions //
		$('.btnSelectCmd').on('click', function () {
			var el = $(this);
		    jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
				_formatCmd = el.closest('.viewAction').find('#iCalendar_cmdFormat').value();
				if (el.hasClass('btnCmdDeb')) {
					if (el.closest('.viewAction').find('#iCalendar_actionDoDeb').value()=='#execute') {
						if (result.cmd.subType=='message') {
							el.closest('.viewAction').find('#iCalendar_titleDeb').value('');
							el.closest('.viewAction').find('#iCalendar_messageDeb').value('');
							el.closest('.viewAction').find('.actIsMsgDeb').show();
							el.closest('.viewAction').find('.actIsSliderDeb').hide();
						} else if (result.cmd.subType=='slider') {
							el.closest('.viewAction').find('#iCalendar_sliderDeb').value('');
							el.closest('.viewAction').find('.actIsMsgDeb').hide();
							el.closest('.viewAction').find('.actIsSliderDeb').show();
						} else {
							el.closest('.viewAction').find('.actIsMsgDeb').hide();
							el.closest('.viewAction').find('.actIsSliderDeb').hide();
						}
					} else {
						el.closest('.viewAction').find('.actIsMsgDeb').hide();
						el.closest('.viewAction').find('.actIsSliderDeb').hide();
					}
					el.closest('.viewAction').find('#iCalendar_actionTypeDeb').value(result.cmd.subType);
					el.closest('.viewAction').find('#iCalendar_actionDeb').value((_formatCmd=='id')?result.cmd.id:result.human);
				} else if (el.hasClass('btnCmdFin')) {
					if (el.closest('.viewAction').find('#iCalendar_actionDoFin').value()=='#execute') {
						if (result.cmd.subType=='message') {
							el.closest('.viewAction').find('#iCalendar_titleFin').value('');
							el.closest('.viewAction').find('#iCalendar_messageFin').value('');
							el.closest('.viewAction').find('.actIsMsgFin').show();
							el.closest('.viewAction').find('.actIsSliderFin').hide();
						} else if (result.cmd.subType=='slider') {
							el.closest('.viewAction').find('#iCalendar_sliderFin').value('');
							el.closest('.viewAction').find('.actIsMsgFin').hide();
							el.closest('.viewAction').find('.actIsSliderFin').show();
						} else {
							el.closest('.viewAction').find('.actIsMsgFin').hide();
							el.closest('.viewAction').find('.actIsSliderFin').hide();
						}
					} else {
						el.closest('.viewAction').find('.actIsMsgFin').hide();
						el.closest('.viewAction').find('.actIsSliderFin').hide();
					}
					el.closest('.viewAction').find('#iCalendar_actionTypeFin').value(result.cmd.subType);
					el.closest('.viewAction').find('#iCalendar_actionFin').value((_formatCmd=='id')?result.cmd.id:result.human);
				}
			});
		});		
		// raz de la zone de génération //
		$('#bt_raz').on('click', function (event) { $('#iCalendar_EventFormat').value(''); });
		// generation des valeurs //
		$('#bt_genEventFormat').on('click', function (event) {
			if ($('#iCalendar_type').value()=='scenario') {
				//-- scenario //
				sEventFormat = "DA|sc=" + $('#iCalendar_idScenarioDeb').value();
				if ($('#iCalendar_varNameDeb').value().substring(0,1)=='#') {
					sEventFormat += "|" + $('#iCalendar_varNameDeb').value().replace('|', '').replace('=', '');
				} else {
					if (($('#iCalendar_varNameDeb').value()!='') && ($('#iCalendar_varValDeb').value()!='')) {
						sEventFormat += "|" + $('#iCalendar_varNameDeb').value().replace('|', '').replace('=', '') + "=" + $('#iCalendar_varValDeb').value().replace('|', '').replace('=', '');
					}
				}
				sEventFormat += "\r\n";
				if ($('#iCalendar_idScenarioFin').value()!='') {
					sEventFormat += "FA|sc=" + $('#iCalendar_idScenarioFin').value();
					if ($('#iCalendar_varNameFin').value().substring(0,1)=='#') {
						sEventFormat += "|" + $('#iCalendar_varNameFin').value().replace('|', '').replace('=', '');
					} else {
						if (($('#iCalendar_varNameFin').value()!='') && ($('#iCalendar_varValFin').value()!='')) {
							sEventFormat += "|" + $('#iCalendar_varNameFin').value().replace('|', '').replace('=', '') + "=" + $('#iCalendar_varValFin').value().replace('|', '').replace('=', '');
						}
					}
					sEventFormat += "\r\n";
				}
			} else if ($('#iCalendar_type').value()=='action') {
				sEventFormat = '';
				if ($('#iCalendar_actionDeb').value() != '') {
					sEventFormat += "DA|act=" + $('#iCalendar_actionDeb').value();
					if ($('#iCalendar_actionDoDeb').value()=='#execute') {
						if (($('#iCalendar_actionTypeDeb').value()=='slider') && ($('#iCalendar_sliderDeb').value()!='')) {
							sEventFormat += "|slider=" + $('#iCalendar_sliderDeb').value().replace('|', '').replace('=', '');
						} else if (($('#iCalendar_actionTypeDeb').value()=='message') && (($('#iCalendar_titleDeb').value()!='')||($('#iCalendar_messageDeb').value()!=''))) {
							sEventFormat += "|message=" + $('#iCalendar_titleDeb').value().replace('|', '').replace('=', '')+'|'+$('#iCalendar_messageDeb').value().replace('|', '').replace('=', '');
						}
					} else {
						if ($('#iCalendar_actionDoDeb').value()=='#active') { sEventFormat += "|active=1"; }
						if ($('#iCalendar_actionDoDeb').value()=='#desactive') { sEventFormat += "|active=0"; }
					}
					sEventFormat += "\r\n";
				}
				if ($('#iCalendar_actionFin').value() != '') {
					sEventFormat += "FA|act=" + $('#iCalendar_actionFin').value();
					if ($('#iCalendar_actionDoFin').value()=='#execute') {
						if (($('#iCalendar_actionTypeFin').value()=='slider') && ($('#iCalendar_sliderFin').value()!='')) {
							sEventFormat += "|slider=" + $('#iCalendar_sliderFin').value().replace('|', '').replace('=', '');
						} else if (($('#iCalendar_actionTypeFin').value()=='message') && (($('#iCalendar_titleFin').value()!='')||($('#iCalendar_messageFin').value()!=''))) {
							sEventFormat += "|message=" + $('#iCalendar_titleFin').value().replace('|', '').replace('=', '')+'|'+$('#iCalendar_messageFin').value().replace('|', '').replace('=', '');
						}
					} else {
						if ($('#iCalendar_actionDoFin').value()=='#active') { sEventFormat += "|active=1"; }
						if ($('#iCalendar_actionDoFin').value()=='#desactive') { sEventFormat += "|active=0"; }
					}
					sEventFormat += "\r\n";
				}
			}
			$('#iCalendar_EventFormat').value($('#iCalendar_EventFormat').value() + sEventFormat);
		});
		$('.btn-danger[data-dismiss=modal]').on('click', function () { $('#md_modal').dialog('close'); });
	}).dialog('open');
});
//------------------------------------------------------------------------------ EQLOGIC & CMD //
// masque le message d'information/erreur lors du retour sur la page d'accueil //
$('.eqLogic').on('click','.eqLogicAction[data-action="returnToThumbnailDisplay"]',function() {
	$('.jqAlert').hide(); 
	$('.nav-tabs li').removeClass('active');
	$('.nav-tabs li:nth-child(2)').addClass('active'); // li = équipement
	$('.tab-pane').removeClass('active');
	$('.tab-pane:nth-child(1)').addClass('active'); // tab = équipement
});
// affichages des options des commandes, en fonction la valeur d'autres //
$('#paramOther').on('change', '.eqLogicAttr[data-l1key="configuration"][data-l2key="catchAccepted"]',function(){
	if ($(this).value()==1) { $('.eqLogic .catchViewPeriod').show(); }
		else { $('.eqLogic .catchViewPeriod').hide(); }
});
// type d'agenda (ics/caldev) //
$('#commandtab').on('change','.iCalCmdJ0 .cmdAttr[data-l1key="configuration"][data-l2key="iCalendarType"]',function(){
	if ($(this).value()=='caldav_read') {
		$(this).closest('.cmd').find('.divShowCnxInfo').show();
	} else {
		$(this).closest('.cmd').find('.divShowCnxInfo').hide();
	}
});
$('#commandtab').on('click','#showPwd',function() {
	_objPwd = $(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="calDavPwd"]');
	if (_objPwd.attr('type') == "password") {
		_objPwd.attr('type','text');
		$(this).removeClass('fa-eye').addClass('fa-eye-slash');
	} else {
		_objPwd.attr('type','password');
		$(this).removeClass('fa-eye-slash').addClass('fa-eye');
	}
});
// Téléchargement direct du fichier ICS en cache
$('#commandtab').on('click', '#bt_downloadICS', function () {
	window.open('plugins/iCalendar/core/ajax/iCalendar.ajax.php?action=downloadICS&cmdId=' + $(this).closest('.iCalTR').attr("data-cmd_id"));
});

// -----------------------------------------------------------------------------
// GESTION DYNAMIQUE DE L'AFFICHAGE DES OPTIONS DE COMMANDE
// -----------------------------------------------------------------------------

// Ajuste les champs affichés selon le style de vue sélectionné (current, 1day_next1hour, 1day_today)
$('#commandtab').on('change', '.iCalCmdJ0 .cmdAttr[data-l1key="configuration"][data-l2key="viewStyle"]',function(){
	// indique si des données importantes ont été mise à jour //
	$('.eqLogicAttr[data-l1key="configuration"][data-l2key="importantDataChanged"]').value(1);
	switch ($(this).value()) {
		case "current": 
			$(this).closest('.cmd').find('.divTitleOnly').show();
			$(this).closest('.cmd').find('.divPeriodeWorking').hide();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="periodeWorking"]').value(0);
			$(this).closest('.cmd').find('.divPeriodeView').hide();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="periodeView"]').value(0);
			$(this).closest('.cmd').find('.divShowHour').show();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="showHour"]').value(1);
			$(this).closest('.cmd').find('.divLocation').hide();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="showLocation"]').value(0);
			break;
		case "1day_next1hour": 
			$(this).closest('.cmd').find('.divTitleOnly').hide();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="titleOnly"]').value(0);
			$(this).closest('.cmd').find('.divPeriodeWorking').hide();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="periodeWorking"]').value(0);
			$(this).closest('.cmd').find('.divPeriodeView').hide();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="periodeView"]').value(0);
			$(this).closest('.cmd').find('.divShowHour').show();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="showHour"]').value(1);
			$(this).closest('.cmd').find('.divLocation').show();
			break;
		case "1day_today": 
			$(this).closest('.cmd').find('.divTitleOnly').hide();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="titleOnly"]').value(0);
			$(this).closest('.cmd').find('.divPeriodeWorking').show();
			$(this).closest('.cmd').find('.divPeriodeView').show();
			$(this).closest('.cmd').find('.divShowHour').show();
			$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="showHour"]').value(1);
			$(this).closest('.cmd').find('.divLocation').show();
			break;
	}
});
$('#commandtab').on('change', '.iCalCmdJ0 .cmdAttr[data-l1key="configuration"][data-l2key="titleOnly"]',function(){
	if ($(this).value()==1) {
		$(this).closest('.cmd').find('.divShowHour').hide();
		$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="showHour"]').value(0);
		$(this).closest('.cmd').find('.divShowHour24H').hide();
		$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="showHour24H"]').value(0);
		$(this).closest('.cmd').find('.divIndicDebFin').hide();
		$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="indicDebFin"]').value(0);
		$(this).closest('.cmd').find('.divLocation').hide();
		$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="showLocation"]').value(0);
		$(this).closest('.cmd').find('.divLaunchActSc').hide();
		$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="acceptLaunchActSc"]').value(0);
		$(this).closest('.cmd').find('.divLaunchInteract').hide();
		$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="acceptLaunchInteract"]').value(0);
		$(this).closest('.cmd').find('.divHistory').hide();
		$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="actionIsHistorized"]').value(0);
	} else {
		$(this).closest('.cmd').find('.divShowHour').show();
		$(this).closest('.cmd').find('.divIndicDebFin').show();
		$(this).closest('.cmd').find('.divLocation').show();
		$(this).closest('.cmd').find('.divLaunchActSc').show();
		$(this).closest('.cmd').find('.divLaunchInteract').show();
		$(this).closest('.cmd').find('.divHistory').show();
	}
});
// affiche les options liées à l'heure //
$('#commandtab').on('change', '.iCalCmdJ0 .cmdAttr[data-l1key="configuration"][data-l2key="showHour"]',function(){
	if ($(this).value()==1) { $(this).closest('.cmd').find('.divShowHour24H').show(); } 
		else { $(this).closest('.cmd').find('.divShowHour24H').hide(); }
});
// paramétrage des "actions" automatisées //
$('#commandtab').on('change', '.iCalCmdJ0 .cmdAttr[data-l1key="configuration"][data-l2key="acceptLaunchActSc"]',function(){
	if ($(this).value()==1) {
		$(this).closest('.cmd').find('.divHistory').show();
	} else if ($(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="acceptLaunchInteract"]').value()==0) {
		$(this).closest('.cmd').find('.divHistory').hide();
		$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="actionIsHistorized"]').value(0);
	}
});
$('#commandtab').on('change', '.iCalCmdJ0 .cmdAttr[data-l1key="configuration"][data-l2key="acceptLaunchInteract"]',function(){
	if ($(this).value()==1) {
		$(this).closest('.cmd').find('.divHistory').show();
	} else if ($(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="acceptLaunchActSc"]').value()==0) {
		$(this).closest('.cmd').find('.divHistory').hide();
		$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="actionIsHistorized"]').value(0);
	}
});
// paramétrage de la période //
$('#commandtab').on('change', '.iCalCmdJ0 .cmdAttr[data-l1key="configuration"][data-l2key="periodeWorking"]',function(){
	// indique si des données importantes ont été mise à jour //
	$('.eqLogicAttr[data-l1key="configuration"][data-l2key="importantDataChanged"]').value(1);
	$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="periodeView"]').empty();
	_htmlOptions = '<option value=\'0\'> {{jour courant}} </option>';
	if ($(this).value()>=1) { _htmlOptions += '<option value=\'1\'> + {{lendemain}} </option>'; }
	if ($(this).value()>=2) { _htmlOptions += '<option value=\'2\'> + 2 {{jours}} </option>'; }
	if ($(this).value()>=6) { _htmlOptions += '<option value=\'6\'> 1 {{semaine}} </option>'; }
	$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="periodeView"]').append(_htmlOptions);
	_pWorking = $(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="periodeWorking"]').value();
	$(this).closest('.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="periodeView"]').value(_pWorking);
	if ($(this).value()>0) {
		$(this).closest('.cmd').find('.divPeriodeWorking .fa-list-alt').show();
	} else {
		$(this).closest('.cmd').find('.divPeriodeWorking .fa-list-alt').hide();
	}
});

// -----------------------------------------------------------------------------
// DÉCOUVERTE ET ASSIGNATION DES CALENDRIERS CALDAV
// -----------------------------------------------------------------------------
// Ouvre la boîte modale de recherche CalDAV, interroge le serveur distant via AJAX,
// affiche la liste des agendas trouvés et permet de copier l'identifiant retenu.
$('#commandtab').on('click','.iCalCmdJ0  .divShowCnxInfo i.fa-question-circle',function(event){
	_cmdId= $(this).closest('.cmd').attr("data-cmd_id");
	$('#md_modal').dialog({title: "{{Recherche d'un nom de calendrier CalDAV}}"});
	$('#md_modal').load('index.php?v=d&plugin=iCalendar&modal=caldav-getCalendarList&id=' + $('.eqLogicAttr[data-l1key=id]').value(), function () {
		$.ajax({
			type: 'POST',
			url: 'plugins/iCalendar/core/ajax/iCalendar.ajax.php',
			data: {
				action: 'getCalDAVList',
				cmdId: _cmdId
			},
			dataType: 'json',
			error: function (request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function (data) {
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message: data.result, level: 'danger'});
					return;
				}
				for (i=0;i<count(data.result);i++) {
					var tr = '<tr class="calDAVList">';
						tr += '<td style="background-color:'+ data.result[i]['bgcolor'] +';">&nbsp;</td>';
						tr += '<td style="text-align:center;"><a class="btn btn-info" style="font-size:97%;">Copier</a></td>';
						tr += '<td style="text-align:center;" class="calDAVCalId">'+ data.result[i]['id'] +'</td>';
						tr += '<td style="text-align:center;">'+ data.result[i]['name'] +'</td>';
					tr += '</tr>';
					$('#table_caldavList tbody').append(tr);
				}
				$('#table_caldavList').on('click', '.calDAVList a.btn-info',function(event) {
					_backCalId = $('.cmd[data-cmd_id="'+ _cmdId +'"] .cmdAttr[data-l1key="configuration"][data-l2key="calDavCalName"]').value();
					_calDAVCalId = $(this).closest('.calDAVList').find('.calDAVCalId').html();
					$('.cmd[data-cmd_id="'+ _cmdId +'"] .cmdAttr[data-l1key="configuration"][data-l2key="calDavCalName"]').value(_calDAVCalId);
					if (_backCalId != _calDAVCalId) $('.eqLogicAttr[data-l1key="configuration"][data-l2key="forceSynchro"]').value(1);
					if (_calDAVCalId != '') $('#md_modal').dialog('close');
				});
			}
		});
		$('.btn-danger[data-dismiss=modal]').on('click', function () { $('#md_modal').dialog('close'); });
	}).dialog('open')
});
// suppression des lignes complémentaires associées à une Cmd J0 //
$('#commandtab').on('click','.iCalCmdJ0 .cmdAction[data-action="remove"]', function() {
	_idCmd = $(this).closest('.cmd').attr('data-cmd_id');
	$('.iCalTR[data-cmd_id="'+ _idCmd +'"]').remove();
	$('.iCalTR[data-original_id="'+ _idCmd +'"]').remove();
	$('.tableCmd[data-groupcmd_id="' + _idCmd + '"').remove();
}); 
// gestion des jours J+x //
$('#commandtab').on('click','.iCalCmdJ0 .divPeriodeWorking .fa-list-alt',function(){
	var cmd_id = $(this).closest('.cmd').attr('data-cmd_id');
	if ($('.iCalOtherCmd[data-original_id="'+ cmd_id +'"]').is(":hidden")) {
		$('.iCalOtherCmd[data-original_id="'+ cmd_id +'"]').show();
    } else {
        $('.iCalOtherCmd[data-original_id="'+ cmd_id +'"]').hide();
    }
});

// je duplique cette fonction Jeedom pour les boutons tests des commandes J+x //
// (car ces lignes ne sont pas des .cmd (pour éviter le double enregistrement)). //
$('#commandtab').on('click','.cmdAction[data-action=iCalTest]',function (event) {
    $.hideAlert();
    if ($('.eqLogicAttr[data-l1key=isEnable]').is(':checked')) {
        var id = $(this).closest('.iCalTR').attr('data-cmd_id');
        jeedom.cmd.test({id: id});
    } else {
        $('#div_alert').showAlert({message: '{{Veuillez activer l\'équipement avant de tester une de ses commandes}}', level: 'warning'});
    }
});
