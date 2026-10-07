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
 * Logique du Panel iCalendar (visualisation calendrier FullCalendar & historique des actions)
 *
 * @author abarrau
 * @author Alterre (modifications & compatibilité Jeedom 4.4+)
 */

//// ************************* ALL ************************* ////
var _currentLangCode = 'fr';
var _defaultViewFC = 'agendaWeek';
var _periodeZone = {};

//
var _doAutoHide = false;
if (typeof userProfile !== 'undefined' && userProfile && userProfile.doNotAutoHideMenu == 0) {
	_doAutoHide = true;
} else if (typeof userProfils !== 'undefined' && userProfils && userProfils.doNotAutoHideMenu == 0) {
	_doAutoHide = true;
}

if (_doAutoHide || (!('ontouchstart' in window) && $(window).width() < 768)) {
	$('#sd_objectList').hide();
    $('#div_graphiqueDisplay').removeClass('col-xs-10').addClass('col-xs-12');
	$('#bt_displayObjectList').on('mouseenter',function(){
		var timer = setTimeout(function(){
			$('#bt_displayObjectList').find('i').hide();
			$('#div_graphiqueDisplay').addClass('col-xs-10').removeClass('col-xs-12');
			$('#sd_objectList').show();
			$(window).resize();
		}, 100);
		$(this).data('timerMouseleave', timer);
	}).on("mouseleave", function(){
		clearTimeout($(this).data('timerMouseleave'));
	});
	$('#sd_objectList').on('mouseleave',function(){
		var timer = setTimeout(function(){
			$('#sd_objectList').hide();
			$('#bt_displayObjectList').find('i').show();
			$('#div_graphiqueDisplay').removeClass('col-xs-10').addClass('col-xs-12');
			setTimeout(function(){ $(window).resize(); },100);
			setTimeout(function(){ $(window).resize(); },300);
			setTimeout(function(){ $(window).resize(); },500);
		}, 300);
		$(this).data('timerMouseleave', timer);
	}).on("mouseenter", function(){
		clearTimeout($(this).data('timerMouseleave'));
	});
}

// Filtre de recherche dans la liste des agendas
$('#sd_objectList input.filter').on('keyup', function () {
	var search = $(this).val().toLowerCase();
	$('#ul_object .li_object').each(function () {
		var text = $(this).text().toLowerCase();
		$(this).toggle(text.indexOf(search) > -1);
	});
});
//
$('#div_graphiqueDisplay').on('click','#btn_calendrier',function() { 
	$('#btn_actions').removeClass('btn-actif');
	$(this).addClass('btn-actif');
	if (_cmdId > 0) {
		$('#div_alert').hide();
		$('.iCalendar_ActionsTb').hide();
		setPeriodeZone(false);
		$('.iCalendar_CalendarZone').show();
		getEventsInCalendar(_cmdId); //, $('#in_startDate').value(), $('#in_endDate').value());
	} else {
		$('#div_alert').showAlert({message: '{{Sélectionner un agenda pour visualiser le calendrier.}}', level: 'danger'});
	}
});
//
$('#div_graphiqueDisplay').on('click','#btn_actions',function() { 
	$('#btn_calendrier').removeClass('btn-actif');
	$(this).addClass('btn-actif');
	if (_cmdId > 0) {
		$('#div_alert').hide();
		setPeriodeZone(true);
		updateActionsStateList(_cmdId, $('#in_startDate').val(), $('#in_endDate').val());
		$('.iCalendar_CalendarZone').hide();
		$('.iCalendar_ActionsTb').show();
	} else {
		$('#div_alert').showAlert({message: '{{Sélectionner un agenda pour visualiser la liste des actions.}}', level: 'danger'});
	}
});
//
$('#bt_validChangeDate').on('click', function () {
	$('#div_alert').hide();
	if (_cmdId > 0) {
		if ($('#btn_actions').hasClass('btn-actif')) {
			updateActionsStateList(_cmdId, $('#in_startDate').val(), $('#in_endDate').val());
		} else if ($('#btn_calendrier').hasClass('btn-actif')) {
			getEventsInCalendar(_cmdId, $('#in_startDate').val(), $('#in_endDate').val());
		} else {
			$('#div_alert').showAlert({message: "{{Sélectionner un type de contenu avant de changer de période d'affichage.}}", level: 'danger'});
		}
	} else {
		$('#div_alert').showAlert({message: "{{Sélectionner un agenda avant de changer de période d'affichage.}}", level: 'danger'});
	}
});
//
if ((_cmdId > 0) && (!$('#btn_calendrier').hasClass('btn-actif')) && (!$('#btn_actions').hasClass('btn-actif')) && (_defaultView!='')) {
	_v = _defaultView.split("_");
	if (_v[0]=='cal') {
		_defaultViewFC = _v[1];
		$('#btn_calendrier').click();
	} else if (_v[0]=='act') {
		$('#btn_actions').click();
	}
}
/**
 * Active ou désactive la zone de sélection de période (date début / fin)
 * Utilisé principalement pour le panneau d'historique des actions.
 *
 * @param {boolean} active - true pour afficher/activer les champs de date, false pour masquer
 */
function setPeriodeZone(active) {
	if (active) {
		$('#in_startDate').val(_periodeZone.dStart);
		$('#in_endDate').val(_periodeZone.dEnd);
		$('.iCalendar_Periode .in_datepicker').prop('readonly', false);
		$('.iCalendar_Periode').show();
		$('#bt_validChangeDate').show();
	} else {
		_periodeZone.dStart = $('#in_startDate').val();
		_periodeZone.dEnd = $('#in_endDate').val();
		$('.iCalendar_Periode .in_datepicker').prop('readonly', true);
		$('.iCalendar_Periode').hide();
		$('#bt_validChangeDate').hide();
	}

}

//// ************************* GESTION DES ACTIONS ************************* ////

/**
 * Récupère par requête AJAX la liste des actions historisées pour une commande et une période
 *
 * @param {number|string} _cmdId - Identifiant de la commande iCalendar
 * @param {string} _dStart - Date de début (format YYYY-MM-DD)
 * @param {string} _dEnd - Date de fin (format YYYY-MM-DD)
 */
function updateActionsStateList(_cmdId, _dStart, _dEnd) {
  	$('#div_alert').hide();
	$.ajax({
        type: 'POST',
        url: 'plugins/iCalendar/core/ajax/iCalendar.ajax.php',
        data: {
            action: 'getHistoryActions',
            cmdId: _cmdId,
            dateStart : _dStart,
            dateEnd : _dEnd,
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
			if (typeof data.result === 'object') {
				generateArray(data.result);
			} else {
				$('#div_alert').showAlert({message: data.result, level: 'info'});
			}
        }
    });
}

/**
 * Construit et injecte les lignes du tableau HTML récapitulatif des actions par journée
 *
 * @param {Object} _result - Données retournées par le backend, groupées par jour
 */
function generateArray(_result) {
	var _oToday = new Date();
	var _today = _oToday.getFullYear()+'-'+ (((_oToday.getMonth()+1)<10)?'0'+(_oToday.getMonth()+1):(_oToday.getMonth()+1)) +'-'+ ((_oToday.getDate()<10)?'0'+_oToday.getDate():_oToday.getDate());
	var tr = '';
	for (var i in _result) {
		_action = json_decode(_result[i].list);
		_nbAction = Object.keys(_action).length;
		tr += actionListAddDay(i, _result[i].dayH, _today, _nbAction);
		if (_nbAction > 0) {
			for (var j in _action) {
				tr += actionListAction(_action[j]);
			}
		}
	}
  	$('.iCalendar_ActionsTb tbody').empty().append(tr);
	$('.iCalendar_ActionsTb .maison-poubelle').on('click', function() {
		deleteActions($(this).attr('data-date'));
	});	
	$('.iCalendar_ActionsTb .btActionParamMore').on('click', function() {
		_idDiv = $(this).attr('data-idDiv');
		if ($('.iCalendar_ActionsTb .actionParamMore[data-idDiv="'+ _idDiv +'"]').is(":hidden")) {
			$('.iCalendar_ActionsTb .actionParamMore[data-idDiv="'+ _idDiv +'"]').show();
			$('.iCalendar_ActionsTb .btActionParamMore[data-idDiv="'+ _idDiv +'"]').removeClass('fa-plus-circle').addClass('fa-minus-circle');
		} else {
			$('.iCalendar_ActionsTb .actionParamMore[data-idDiv="'+ _idDiv +'"]').hide();
			$('.iCalendar_ActionsTb .btActionParamMore[data-idDiv="'+ _idDiv +'"]').removeClass('fa-minus-circle').addClass('fa-plus-circle');
		}
	});
}

/**
 * Génère la structure HTML de l'en-tête de jour pour le tableau des actions
 *
 * @param {string} _day - Date du jour au format ISO (ex: '2026-10-07')
 * @param {string} _dayH - Libellé lisible du jour (ex: '07 Octobre 2026')
 * @param {string} _today - Date du jour courant pour mise en surbrillance
 * @param {number} _nbRow - Nombre d'actions dans cette journée (pour l'attribut rowspan)
 * @returns {string} Trame HTML de la ligne de jour
 */
function actionListAddDay(_day, _dayH, _today, _nbRow) {
	var tr = '<tr class="actionDay" data-date="'+ _day +'">';
		_active = (_day == _today)?'background-color:#AC77BC;color:#FFFFFF;':'';
  		_aDayH = _dayH.split(" ");
		tr += '<td style="padding-left:10px;'+ _active +'" rowspan="'+ (_nbRow+1) +'">';
  			tr += '<div style="float:left;font-size:32px;margin-right:8px;">'+ _aDayH[0] +'</div><div>'+ _aDayH[1] +'</div><div>'+ _aDayH[2] +'</div>';
  			tr += '<div style="padding-top:12px;font-size:12px;font-style:italic;">(<i class="maison maison-poubelle cursor" data-date="'+ _day +'" title="{{Suppression des actions de cette journée}}"></i> {{Suppression}})</div>';
        tr += '</td>';
		tr += '</tr>';
	return tr;
}

/**
 * Génère la ligne HTML d'une action exécutée (scénario, interaction ou commande)
 * avec ses paramètres détaillés rétractables
 *
 * @param {Object} _action - Objet décrivant l'action exécutée
 * @returns {string} Trame HTML de la ligne d'action
 */
function actionListAction(_action) {
	var tr = '<tr class="actionList">';
		tr += '<td style="text-align:center;">';
			_idDiv = _action.tsExec+''+_action.id;
  			_oDtEx = new Date(_action.tsExec * 1000);
  			_oDtInit = new Date(_action.tsInit * 1000);
  			tr += '<div title="{{Heure de traitement}}"> '+ ((_oDtEx.getHours()<10)?'0'+_oDtEx.getHours():_oDtEx.getHours()) + ':' + ((_oDtEx.getMinutes()<10)?'0'+_oDtEx.getMinutes():_oDtEx.getMinutes()) + ':' + ((_oDtEx.getSeconds()<10)?'0'+_oDtEx.getSeconds():_oDtEx.getSeconds()) + ' </div>';
  		tr += '</td>';
		switch(_action.type) {
			case 'S': _typeAndName = '<i class="fas fa-cogs" title="{{Scénario}} (id:'+ _action.id +')"></i> '+ _action.name; break;
			case 'I': 
				if (init(_action.reply)!='') { _value = init(_action.reply.reply); } else { _value = _action.name + ' (???)'; }
				_typeAndName = '<i class="fas fa-comment" title="{{Intéraction}}"></i> "'+ _value +'"'; break;
			case 'A':
			default: _typeAndName = '<i class="far fa-play-circle" title="{{Action}} (id:'+ _action.id +')"></i> '+ _action.name;
		}
		tr += '<td style="">';
			tr += '<div style="font-weight:bold;">'+ _typeAndName + '</div>';
		tr += '</td>';
		tr += '<td style="font-size:13px;">';
			tr += '<div style="font-style:italic;"> <i class="fa fa-plus-circle btActionParamMore cursor" style="color:#AC77BC" data-idDiv="'+ _idDiv +'" title="{{Afficher plus d\'information}}"></i> <span style="padding-left:5px">{{événement}} : ' + _action.title + '</span></div>';
			tr += '<div class="actionParamMore" data-idDiv="'+ _idDiv +'" style="display:none;font-style:italic;padding-left:17px;">';
				tr += '<div>{{heure initialement prévue}} : '+ ((_oDtInit.getHours()<10)?'0'+_oDtInit.getHours():_oDtInit.getHours()) + ':' + ((_oDtInit.getMinutes()<10)?'0'+_oDtInit.getMinutes():_oDtInit.getMinutes()) +' ('+ _action.when +') </div>';
				if ((_action.val!='')||(_action.var!='')) {
					tr += '<div> param : ' + _action.var + (((_action.val!='')&&(_action.var!=''))?' = ':'') + _action.val + '</div>';
				}
				tr += '<div> uid event : ' + _action.uid + '</div>';
			tr += '</div>';
		tr += '</td>';
		tr += '</tr>';
	return tr;
}

/**
 * Demande confirmation par boîte modale et supprime l'historique des actions d'une date donnée
 *
 * @param {string} _dtDelete - Date de la journée à purger (format YYYY-MM-DD)
 */
function deleteActions(_dtDelete) {
	if (_dtDelete=='') {
		$('#div_alert').showAlert({message:'{{La valeur de la journée à supprimer ne peut pas être vide.}}', level: 'danger'});
		return;
	}
	bootbox.confirm("{{Etes-vous sur de vouloir supprimer les actions de cette journée ?}}", function(result) {
		if (result) {
			$.ajax({
				type: 'POST',
				url: 'plugins/iCalendar/core/ajax/iCalendar.ajax.php',
				data: {
					action: 'deleteHistoryActions',
					cmdId: _cmdId,
					dtDelete : _dtDelete
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
					$('#div_alert').showAlert({message: data.result, level: 'success'});
					updateActionsStateList(_cmdId, $('#in_startDate').val(), $('#in_endDate').val());
				}
			});
		}
	});
}
//

//// ************************* GESTION DES CALENDRIERS ************************* ////

/**
 * Calcule la hauteur optimale du calendrier en fonction de la fenêtre navigateur
 * et applique cette dimension à FullCalendar si demandé
 *
 * @param {boolean} [_changeParam=false] - Si true, applique la hauteur dynamiquement au calendrier
 * @returns {number|void} Hauteur calculée en pixels (si _changeParam est falsy)
 */
function getCalendarSize(_changeParam) {
	var headerH = $('#page-top').outerHeight(true) || $('header').outerHeight(true) || $('.navbar').outerHeight(true) || 50;
	var _return = $(window).height() - (headerH + 90);
	if (isNaN(_return) || _return < 500) {
		_return = 680;
	}
	if (_changeParam) {
		if ($('.iCalendar_CalendarZone').data('fullCalendar')) {
			$('.iCalendar_CalendarZone').fullCalendar('option', 'height', _return);
		}
	} else {
		return _return;
	}
}

/**
 * Initialise ou actualise l'instance FullCalendar avec la liste d'événements fournie
 * Configure les vues (agendaDay, agendaFourDay, agendaWeek, month) et la modale de détail
 *
 * @param {string|Array} _eventsList - Liste des événements au format JSON ou tableau d'objets
 */
function renderCalendar(_eventsList) {
	var $calendar = $('.iCalendar_CalendarZone');
	if ($calendar.data('fullCalendar')) {
		$calendar.fullCalendar('destroy');
	}
	$calendar.show();

	var _height = getCalendarSize();
	$calendar.fullCalendar({
		timezone: 'local',
		slotEventOverlap: false,
		views: {
			agendaFourDay: {
				type: 'agenda',
				duration: { days: 4 },
				buttonText: '{{4 jours}}'
			}
		},
		header: {
			left: 'title',
			right: 'prev,next today agendaDay,agendaFourDay,agendaWeek,month'
		},
		scrollTime: '07:00:00',
		minTime: '00:00:00',
		maxTime: '24:00:00',
		slotDuration: '00:30:00',
		allDaySlot: true,
		allDayText: '{{Toute la journée}}',
		windowResize: function(view) {
			getCalendarSize(true);
		},
		eventRender: function (event, element) {
			element.attr('href', '#');
			element.click(function(e) {
				e.preventDefault();
				$('#iCalendar_eventTitle').val(event.title);
				var dateS = moment(event.start).format('YYYY-MM-DD');
				var dateE = event.end ? moment(event.end).format('YYYY-MM-DD') : dateS;
				if (event.allDay) {
					$('#iCalendar_eventDateS').val(dateS);
					$('#iCalendar_eventDateE').val(dateE);
				} else {
					$('#iCalendar_eventDateS').val(dateS + ' - ' + moment(event.start).format('HH:mm'));
					$('#iCalendar_eventDateE').val(dateE + ' - ' + (event.end ? moment(event.end).format('HH:mm') : ''));
				}
				$('#iCalendar_eventLocation').val(event.loc || '');
				$('#iCalendar_eventDateUpdate').text((init(event.dUpdate)!='') ? moment(event.dUpdate).format('YYYY-MM-DD HH:mm:ss') : '');
				$('#iCalendar_eventDescription').empty().css('background-color','#eee');
				if (event.description && event.description != "") {
					var _act = event.description.split('\\n');
					for(var i=0; i<_act.length; i++) {
						$('#iCalendar_eventDescription').append(_act[i] + '<br/>');
					}
				}
				$('#iCalendar_eventContent .btn-danger[data-action="cancel"]').off('click').on('click', function () {
					$('#iCalendar_eventContent').dialog('close');
				});
				$('#iCalendar_eventContent input').prop('readonly', true);
				$('#iCalendar_eventContent').dialog({
					modal: true,
					title: '{{Détail événement}}: ' + event.title,
					width: 550,
					height: 330
				});
			});
		},
		height: _height,
		handleWindowResize: true,
		defaultView: _defaultViewFC,
		locale: 'fr',
		weekNumbers: true,
		editable: false,
		eventLimit: true,
		nowIndicator: true,
		events: json_decode(_eventsList)
	});

	setTimeout(function() {
		$calendar.fullCalendar('render');
	}, 100);
}

/**
 * Récupère les événements du calendrier par requête AJAX et déclenche le rendu FullCalendar
 *
 * @param {number|string} _cmdId - Identifiant de la commande agenda
 * @param {string} [_dStart] - Date de début optionnelle
 * @param {string} [_dEnd] - Date de fin optionnelle
 */
function getEventsInCalendar(_cmdId, _dStart, _dEnd) {
  	$.ajax({
        type: 'POST',
        url: 'plugins/iCalendar/core/ajax/iCalendar.ajax.php',
        data: {
            action: 'getEventsInCalendar',
            cmdId: _cmdId,
            dateStart : _dStart,
            dateEnd : _dEnd,
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
			renderCalendar(data.result.json);
			$('#in_startDate').val(data.result.dStart);
			$('#in_endDate').val(data.result.dEnd);
        }
    });
}
