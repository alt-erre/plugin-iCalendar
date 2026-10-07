
// Met à jour le Widget //
function iCal_refreshWidget(_eqLogId, _iCalsCmd) {
	if (Object.keys(_iCalsCmd).length > 0) {
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_div').empty();
		for (var _order in _iCalsCmd) {
			_idCmd = _iCalsCmd[_order].param.id;
			if (init(_iCal_selectedDay[_eqLogId +'-'+ _idCmd]) == '') {
				_iCal_selectedDay[_eqLogId +'-'+ _idCmd]='J0'; 
			}
			iCal_generateFullCalendar(_eqLogId, _iCalsCmd[_order]);
			if (_iCalsCmd[_order].param.calendarStyle=='1dayAndNav') iCal_showCalendar(_eqLogId, _idCmd, _iCal_selectedDay[_eqLogId +'-'+ _idCmd]);
		}
	}
	//$('.eqLogic[data-eqLogic_id='+ _eqLogId +']').on('resize',function(){iCal_resizeWidget(_eqLogId, true);});
}
// Formate les données pour les afficher dans le calendrier (1 agenda, avec 1 ou plusieurs jours) //
function iCal_generateFullCalendar(_eqLogId, _iCal) {
	var _vHtml="<div class='tooltips iCalendar_cal cmd' data-cmd_id='"+ _iCal.param.id +"'>";
	if ((_iCal.param.periodeView>0)&&(_iCal.param.calendarStyle=='1dayAndNav')) { _vHtml +="<div class='iCalendar_calSelectDay' style='display:none;'></div>"; }
	_vHtml +="<div class='iCalendar_calFull'></div></div>";
	$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_div').append(_vHtml);
	if (_iCal.param.widgetStyle=='H') {
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_div .cmd[data-cmd_id='+ _iCal.param.id +']').addClass('iCalendar_viewHorizontal');
	} else {
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_div .cmd[data-cmd_id='+ _iCal.param.id +']').removeClass('iCalendar_viewHorizontal');
	}
	// génération du contenu de l'agenda //
	for (var _oneCal in _iCal) {
		if (_oneCal!='param') { iCal_generateOneCalendar(_eqLogId, _iCal[_oneCal], _iCal.param); }
	}
}
// Formate les données pour 1 journée de l'agenda //
function iCal_generateOneCalendar(_eqLogId, _iCal, _param) {
	_vHtml ="<div class='iCalendar_calOneDay' data-day_id='"+ _iCal.dayView +"' "+ ((_param.calendarStyle=='1dayAndNav')?'style=\'display:none;\'':'') + ">";
	if ((_param.calendarStyle=='1dayAndNav')||((_param.calendarStyle=='all')&&(_iCal.dayView=='J0'))) {
		_vHtml +="<div class='iCalendar_calTitle'>";
		_vHtml +="<div style='position:absolute;padding:2px;'><i class='icon divers-calendar2' style='font-size:14px;'></i></div>";
		_vHtml +="<div style='position:absolute;padding-left:20px;padding-top:2px;'><span class='iCalendar_calTitleTxt' title='"+ _iCal_txtView +": "+ _param.viewTxt +" | "+ _iCal_txtValueDate +" "+ _iCal.dValue +" | "+ _iCal_txtCollecteDate +" "+ _iCal.dCollect +"'>"+ _iCal.name +"</span>";
		if ((_iCal.nbEvent>0)&&(_param.showNotNbEvent!=1)&&(_param.calendarStyle!='all')){_vHtml +="<span class='iCalendar_nbEvent'>("+ _iCal.nbEvent +" even.)</span>";}
		_vHtml +="</div>";
		if (_param.gCalUrl!='') {_vHtml +="<div style='float:right;padding:3px 3px 0px 0px;'><a href='"+ _param.gCalUrl +"' target='_blank'><i class='fa fa-rss' style='font-size:14px;' title='"+ _iCal_txtGotoAG +": "+ _param.icsCalName +"'></i></a></div>";}
		_vHtml +="</div>";
	}
	if (_param.calendarStyle=='all') {
		_vHtml +="<div class='iCalendar_calTitle iCalendar_calTitleDay'>"+ _iCal.dayHumanFull;
		if ((_iCal.nbEvent>0)&&(_param.showNotNbEvent!=1)){_vHtml +="<span class='iCalendar_nbEvent'>("+ _iCal.nbEvent +" even.)</span>";}
		_vHtml +="</div>";
	}
	_vHtml +="<div class='iCalendar_items'>";
	for (i=0; i<_iCal.items.length; i++) {
		_itemId = _iCal.id +"-"+ i;
		_sEventHours = ((_iCal.items[i].hd!='')&&(_iCal.items[i].hf!=''))?"<span class='iCalendar_hour'>(" + _iCal.items[i].hd +"-"+ _iCal.items[i].hf +")</span>":"";
		_sEventClass = "iCalendar_itemActif";
		switch(_iCal.items[i].s) {
			case "DA": _sEventState = "<i class='fa fa-plus-circle' title='" + _iCal_txtEventActif + ": " + _iCal_txtFirstMin + "' style='color:#FF0000;'></i>"; break;
			case "FA": _sEventState = "<i class='fa fa-ban' title='" + _iCal_txtEventActif + ": " + _iCal_txtLastMin + "' style='color:#FF0000;'></i>"; break;
			case "A": _sEventState = "<i class='far fa-check-circle' title='" + _iCal_txtEventActif +"' style='color:#0000FF;'></i>"; break;
			case "P": _sEventState = ""; _sEventClass = "iCalendar_itemInactif"; break;
			default: _sEventState = "";
		}
		_sEventClass = (i<(_iCal.items.length -1))?_sEventClass+" iCalendar_itemBorder":_sEventClass;
		_sEventIsAction = "";
		if ((_param.launchInteract=='1')&&(isset(_iCal.items[i].actSc))&&(_iCal.items[i].actSc=='inter')) {
			_sEventIsAction = "<i class='fa fa-comment' data-item_id='"+ _itemId +"'></i> ";
		} else if ((_param.launchActSc=='1')&&(isset(_iCal.items[i].actSc))) {
			_sEventIsAction = "<i class='fa fa-cogs cursor ot_showList' data-item_id='"+ _itemId +"'></i> ";
		}
		_sEventTxt = _sEventIsAction + _iCal.items[i].t;
		_sEventLocation = ((_param.showLocation==1)&&(_iCal.items[i].loc!=''))?' ('+_iCal.items[i].loc+')':'';
		_vHtml +="<div class='"+ _sEventClass +"'>" + _sEventHours + " "+ _sEventState +" "+ _sEventTxt + _sEventLocation +"</div>";
		if ((_param.launchActSc=='1') && (isset(_iCal.items[i].actSc))) {
			_vHtml +="<div class='"+ _sEventClass +" iCalendar_zoneListAct' data-item_id='"+ _itemId +"' style='display:none;padding:3px 1px;'>";
			_vHtml += iCal_generateActScList(_iCal.items[i].actSc, _param.todayDM, _itemId);
			_vHtml +="</div>";
		}
	}
	_vHtml +="</div></div>";
	$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .cmd[data-cmd_id='+ _param.id +'] .iCalendar_calFull').append(_vHtml);
	// zone menu des dates //
	if ((_param.periodeView>0)) { // (_param.calendarStyle=='1dayAndNav')&&
		_vHtml ="<span class='iCalendar_day cursor' data-day_id='"+ _iCal.dayView +"' style='' title='"+ _iCal.dayHumanFull +"'>"+ _iCal.dayHumanD +"</span>";
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .cmd[data-cmd_id='+ _param.id +'] .iCalendar_calSelectDay').append(_vHtml).show();
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .cmd[data-cmd_id='+ _param.id +'] .iCalendar_day').on('click', function () {
			iCal_showCalendar(_eqLogId, _iCal.originId, $(this).attr('data-day_id') ,true);
		});
		// infos sur les actions //
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .cmd[data-cmd_id='+ _param.id +'] .iCalendar_calOneDay[data-day_id='+ _iCal.dayView +'] .ot_showList').on('click', function () {
			iCal_showHideListAct(_eqLogId, $(this).attr('data-item_id'));
		});
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .cmd[data-cmd_id='+ _param.id +'] .iCalendar_calOneDay[data-day_id='+ _iCal.dayView +'] i.fa-times-circle').on('click', function () {
			iCal_showHideListAct(_eqLogId, $(this).parent().attr('data-item_id'));
		});
	}
}
// Permet l'affichage/masquage de la zone d'action/scénario //
function iCal_showHideListAct(_eqLogId, _itemId) {
	if ($('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_zoneListAct[data-item_id='+ _itemId +']').is(":hidden")) {
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_zoneListAct').hide();
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_zoneListAct[data-item_id='+ _itemId +']').show();
	} else {
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_zoneListAct').hide();
	}
}
// Affiche le jour sélectionné //
function iCal_showCalendar(_eqLogId, _cmdId, _idDay, _select) {
	$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .cmd[data-cmd_id='+ _cmdId +'] .iCalendar_calOneDay').hide();
	$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .cmd[data-cmd_id='+ _cmdId +'] .iCalendar_day').removeClass('iCalSelected');
	$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .cmd[data-cmd_id='+ _cmdId +'] .iCalendar_calOneDay[data-day_id='+ _idDay +']').show();
	$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .cmd[data-cmd_id='+ _cmdId +'] .iCalendar_day[data-day_id='+ _idDay +']').addClass('iCalSelected');
	if (_select) _iCal_selectedDay[_eqLogId +'-'+ _cmdId]=_idDay;
	iCal_resizeHeight(_eqLogId);
}
// Formate les données pour afficher les informations de début de fin d'événement //
function iCal_generateActScList(_listActSc, _today, _itemId) {
	var _vHtml = "<div class='iCalendar_titleListAct'>"+_iCal_txtListAct+"<div style='float:right;padding-right:3px;' class='cursor' data-item_id='"+ _itemId +"'><i class='fa fa-times-circle' style='font-size:12px;'></i></div></div>";
	_vHtml += "<div class='iCalendar_listAct'>";
	if (typeof _listActSc === 'object') {
		$.each(_listActSc, function(_ts, _aAction){
			_vHtml += iCal_generateActScLine(_aAction) + "<br/>";
		});
	} else { _vHtml += "<i>" + _iCal_txtNotActSc +"</i>"; }
	_vHtml += "</div>";
	return _vHtml;
}
// Formate la ligne //
function iCal_generateActScLine(_actSc) {
	var _vHtml = _vVarVal = _vExec = "";
	if (_actSc.var=='#active') { _vVarVal = " (" + _iCal_txtActive +")"; }
		else if (_actSc.var=='#desactive') _vVarVal = " (" + _iCal_txtDesactive +")";
		else if ((_actSc.var!='')&&(_actSc.val!='')) _vVarVal = " (" + _actSc.var + " = " + _actSc.val + ")";
	if (init(_actSc.tsExec)!='') {
		_vExec = "<i class='fa fa-check-circle' title='" + _iCal_txtExecTS + ": " + _actSc.tsExec + "' style='color:#00EF00;'></i> ";
	}
	if (_actSc.t=='S') { _vHtml += "("+ _actSc.tsWhenTimeHuman +") "+ _vExec + _iCal_txtScenario + ": <a href='index.php?v=d&p=scenario&id="+ _actSc.id +"'>" + _actSc.name + "</a> " + _vVarVal; } 
	else if (_actSc.t=='A') { _vHtml += "("+ _actSc.tsWhenTimeHuman +") "+ _vExec + _iCal_txtAction + ": <a href='index.php?v=d&m="+ _actSc.eqLType +"&p="+ _actSc.eqLType +"&id="+ _actSc.eqLId +"'>" + _actSc.name + "</a> " + _vVarVal; }
	return _vHtml;
}
// Permet de vérifier la gestion de la taille du widget //
/*function iCal_resizeWidget(_eqLogId, doNotify) {
	_widthData = parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_cal').width()) + 10; // 10=marge //
	if (parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +']').width()) < _widthData) {
		if (doNotify) notify('iCalendar',_iCal_txtErrorNotMinWidth, 'error');
		_newWidth = ((parseInt(_widthData + 10)/40)+1)*40;
		//_newWidth = ((parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +']').width())/40)+1)*40;
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +']').css('width', _newWidth+"px");
	}
	_heightData=parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .widget-name').height()) + parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_date').height());
	_heightData=_heightData + parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_div').height());
	if (parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +']').height()) < parseInt(_heightData)) {
		if (doNotify) notify('iCalendar',_iCal_txtErrorNotMinHeight, 'error');
		_newHeight = parseInt(parseInt(_heightData/80)+1)*80;
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +']').css('height', _newHeight+"px");
	}
}*/
// Définie les couleurs de fond principaux //
function iCal_setCSSOption(_eqLogId, _bgColor, _bgTitleColor, _bgItemColor, _jeedomBgColor, _jeedomColor) {
	if (( _bgItemColor!='')&&(_bgItemColor.replace('#','')!='bgItemColor')) {
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_div .tooltips').css('background-color', _bgItemColor);
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_zoneListAct').css('background-color', _bgItemColor);
	}
	if (( _bgTitleColor!='')&&(_bgTitleColor.replace('#','')!='bgTitleColor')) {
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_calTitle').css('background-color', _bgTitleColor);
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_calTitleDay').css('border-color', _bgTitleColor);
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_day').css('background-color', _bgTitleColor);
		$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_titleListAct').css('background-color', _bgTitleColor);
	}
}
// Définie la hauteur de la tuile //
function iCal_resizeHeight(_eqLogId) {
	eval('_allowResize = (_iCalendar'+ _eqLogId +'_allowAutoResize==1)?_iCalendar'+ _eqLogId +'_allowAutoResize:false;');
	if (_allowResize) {
		setTimeout(function(){
			_multiple = 80;
			_height = 1 + parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .widget-name').height())
				+ parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_date').height());
			if ($('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_cal').hasClass('iCalendar_viewHorizontal')) {
				_maxH = 0;
				$('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_cal').each(function(){
					if (parseInt($(this).height()) > _maxH) {
						_maxH = parseInt($(this).height());
					}
				});
				_height += _maxH;
			} else {
				_height += parseInt($('.eqLogic[data-eqLogic_id='+ _eqLogId +'] .iCalendar_div').height());
			}
			_heightNew = Math.ceil(_height/_multiple)*_multiple;
			$('.eqLogic[data-eqLogic_id='+ _eqLogId +']').css('height',_heightNew+'px');
			$('.eqLogic[data-eqLogic_id='+ _eqLogId +']').closest('.div_displayEquipement').packery();
		},200);
	}
}