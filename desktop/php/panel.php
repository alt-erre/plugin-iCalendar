<?php
if (!isConnect()) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$_dt = array(
	'dStart' => init('dStart', date('Y-m-d', strtotime('-7 days ' . date('Y-m-d')))),
	'dEnd' => init('dEnd', date('Y-m-d', strtotime('+7 days ' . date('Y-m-d')))),
);
//
$_cmdId = (init('cmd_id')!='')?init('cmd_id'):0;
if ($_cmdId == 0) {
	foreach (iCalendar::byType('iCalendar') as $iCalendar) {
		if ($iCalendar->getIsEnable()) {
			foreach ($iCalendar->getCmd('info') as $_oCmd) {
				if (substr($_oCmd->getLogicalId(),-2)=='J0') {
					$_cmdId = $_oCmd->getId();
					break 2;
				}
			}
		}
	}
}
sendVarToJs('_cmdId', $_cmdId);
//
$_defaultView = config::byKey('olindote::panel::defaultView','iCalendar','cal_agendaWeek');
sendVarToJs('_defaultView', $_defaultView);

?>

<style>
#div_zoneDisplay {      margin-left:10px ; }
.iCalendar_ActionsTb {  margin:0px 10px; }
.btn-actif {            background-color:#AC77BC; color:#FFFFFF; }
.iCalendar_CalendarZone {       margin:3px 10px; }
.iCalendar_EventIsEnd { opacity:0.5; }
.fc-left H2 {           font-size:1.4em; font-weight: bold; }
.fc-event {             font-size:0.9em!important; border-radius: 3px; }
.iCalendar_CalendarZone .fc-event-container a { color:#FFFFFF!important; }
.fc-time-grid-container { overflow-y: auto !important; }
.fc-unthemed .fc-today { background: rgba(252, 248, 227, 0.3); }
#sd_objectList .bs-sidebar { padding: 5px; }
#sd_objectList ul.bs-sidenav li.active a { background-color: #AC77BC; color: #FFFFFF; border-radius: 3px; }
</style>

<div style="position :fixed;height:100%;width:15px;top:50px;left:0px;z-index:998;background-color:#f6f6f6;" id="bt_displayObjectList"><i class="fa fa-arrow-circle-o-right" style="color : #b6b6b6;"></i></div>

<div class="row row-overflow" id="div_iCalendar">
	<div class="col-xs-2" id="sd_objectList" style="z-index:999">
        <div class="bs-sidebar">
            <ul id="ul_object" class="nav nav-list bs-sidenav">
                <li class="nav-header">{{Liste des agendas}}</li>
                <li class="filter" style="margin-bottom:5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
					foreach (iCalendar::byType('iCalendar') as $iCalendar) {
						if ($iCalendar->getIsEnable()) {
							foreach ($iCalendar->getCmd('info') as $_oCmd) {
								if (substr($_oCmd->getLogicalId(),-2)=='J0') {
									$_cssActive = ($_cmdId==$_oCmd->getId())?'active':'';
									echo '<li class="cursor li_object '.$_cssActive.'" ><a href="index.php?v=d&m=iCalendar&p=panel&cmd_id='.$_oCmd->getId().'" style="position:relative;">' . $_oCmd->getName() . ' ('.$iCalendar->getName().')</a></li>';
								}
							}
						}
					}
				?>
			</ul>
		</div>
	</div>
	<div class="col-xs-10" id="div_graphiqueDisplay">
		<div>
			<legend style="height: 40px;">
				<span class="pull-left">
					<a class='btn btn-default btn-sm' id="btn_calendrier">{{Calendrier}}</a>&nbsp;
					<a class='btn btn-default btn-sm' id="btn_actions">{{Status des "Actions"}}</a>
				</span>
				<span class="iCalendar_Periode pull-right">
					{{Du}} <input class="form-control input-sm in_datepicker" id='in_startDate' style="display : inline-block; width: 150px;" value='<?php echo $_dt['dStart']; ?>'/> {{au}}
					<input class="form-control input-sm in_datepicker" id='in_endDate' style="display : inline-block; width: 150px;" value='<?php echo $_dt['dEnd']; ?>'/>
					<a class="btn btn-success btn-sm tooltips" id='bt_validChangeDate' title="{{Attention une trop grande plage de date peut mettre très longtemps à être calculé ou même ne pas s'afficher}}">{{Ok}}</a>
				</span>
			</legend>
		</div>
		<!-- ACTIONS -->
		<table class='iCalendar_ActionsTb table table-condensed' style='display:none;'>
			<thead>
				<tr>
					<th width='13%'>{{Date}}</th>
					<th width='10%' style='text-align:center;'>{{Heure}}</th>
					<th width='30%'>{{Action / Scénario}}</th>
					<th>{{Infos / Paramètres}}</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>

		<!-- CALENDRIER: fullcalendar -->
		<div class='iCalendar_CalendarZone' style='display:none;'></div>
		<!-- CALENDRIER: planning -->
		<table class='iCalendar_Planning table table-condensed' style='display:none;'>
			<thead>
				<tr>
					<th width='13%'>{{Date}}</th>
					<th width='12%' style='text-align:center;'>{{Heure début/fin}}</th>
					<th width='30%'>{{Titre}}</th>
					<th>{{Infos / Paramètres}}</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		<!-- CALENDRIER: event -->
		<div id="iCalendar_eventContent" title="Event Details" style="display:none;">
			<div style="padding:5px 0px;" class="input-group">
				<label class="input-group-addon">{{Titre}}</label>
				<input class="form-control" id="iCalendar_eventTitle"/>
			</div>
			<div style="padding:5px 0px;" class="input-group">
				<label class="input-group-addon">{{Date}}</label>
				<input class="form-control" id="iCalendar_eventDateS"/>
				<label class="input-group-addon"> {{à}} </label>
				<input class="form-control" id="iCalendar_eventDateE"/>
			</div>
			<div style="padding:5px 0px;" class="input-group">
				<label class="input-group-addon">{{Lieu}}</label>
				<input class="form-control" id="iCalendar_eventLocation"/>
			</div>
			<div style="padding:5px 0px;height:80px;" class="input-group">
				<label class="input-group-addon">{{Description}}</label>
				<span class="form-control input-sm" style="height:80px!important;" id="iCalendar_eventDescription"></span>
			</div>
			<div class="form-actions">
				<span style="font-style:italic;font-size:0.9em;" >{{Mise à jour}} : <span id="iCalendar_eventDateUpdate"/></span></span>
				<!--a class="btn btn-danger pull-right" data-action="cancel"><i class="fa fa-minus-circle"></i> {{Annuler}}</a-->
				<!--a class="btn btn-success" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a-->
			</div>
		</div>		
		
	</div>
</div>

<?php 
	include_file('3rdparty', 'js.fullcalendar/fullcalendar.min', 'css', 'iCalendar');
	include_file('3rdparty', 'js.fullcalendar/moment.min', 'js', 'iCalendar');
//	include_file('3rdparty', 'js.fullcalendar/fullcalendar.min', 'js', 'iCalendar');
	include_file('3rdparty', 'js.fullcalendar/fullcalendar', 'js', 'iCalendar');
	include_file('3rdparty', 'js.fullcalendar/locale-all', 'js', 'iCalendar');
        include_file('3rdparty', 'js.fullcalendar/fr', 'js', 'iCalendar');
	include_file('desktop', 'panel', 'js', 'iCalendar');
?>