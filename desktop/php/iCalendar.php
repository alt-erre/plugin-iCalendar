<?php
// Verifie si l'utilisateur est connecté //
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// chargement de la class "olindoteTools" //
require_once __DIR__ . '/../../3rdparty/olindote/Tools/olindoteTools.inc.php';
//
$oT_plugin = 'iCalendar';
sendVarToJS('eqType', $oT_plugin);
$eqLogics = eqLogic::byType($oT_plugin);

if (method_exists('olindoteTools','help_writeHtmlCss')) { echo olindoteTools::help_writeHtmlCss(); }
?>

<div class="row row-overflow">
	<?php if (method_exists('olindoteTools','eqlogicHtml_zoneLeftMenu')) echo olindoteTools::eqlogicHtml_zoneLeftMenu($eqLogics, false); // MENU GAUCHE // ?>

	<!-- ******************************** EQLOGIC LIST ************* -->
    <div class="col-xs-12 eqLogicThumbnailDisplay">
		<?php 
			$oT_optBtnHelp = array('id'=>'id="bt_helpWriteICalEventDecription"','class'=>'fa fa-magic', 'color'=>'var(--logo-secondary-color)', 'txt'=>__('Aide à la saisie d\'actions',__FILE__));
			if (method_exists('olindoteTools','eqlogicHtml_zoneGestion')) echo olindoteTools::eqlogicHtml_zoneGestion($oT_plugin, array('add','config',$oT_optBtnHelp)); // MENU GESTION //
			if (method_exists('olindoteTools','eqlogicHtml_zoneEqlogicList')) echo olindoteTools::eqlogicHtml_zoneEqlogicList($oT_plugin, __('Les Calendriers',__FILE__),$eqLogics,true); // MENU EQLOGICS // 
		?>
    </div>

	<!-- ******************************** EQLOGIC DETAIL ************* -->
    <div class="col-xs-12 eqLogic" style="display:none;">
		<?php   echo olindoteTools::eqlogicHtml_writeMenuAction(array('configure','copy','save','remove'));  // MENU ONGLETS
				if (method_exists('olindoteTools','eqlogicHtml_tabList')) echo olindoteTools::eqlogicHtml_tabList(array(
					array('h'=>'#eqlogictab'), 
					array('h'=>'#paramOther','ctl'=>'profile','i'=>'fa fa-rss','n'=>__('Param. Complémentaires',__FILE__),'c'=>''), 
					array('h'=>'#commandtab','ctl'=>'profile','i'=>'fa fa-list-alt','n'=>__('Param. Agenda',__FILE__),'c'=>'')	));
		?>
		
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x:hidden;">
			<!-- TAB : Equipement (données jeedom) =================================================== -->
			<?php 
				$oT_txtHelp = array(__("Récupére et mettre à jour l'agenda (fichier ics) en cache, au moment de la sauvegarde.",__FILE__),
									__("(le traitement peut être long suivant la taille de votre agenda et les capacités de votre box)",__FILE__));
				$_specifiqueHtml = "<br/><legend class='expertModeVisible'><i class='fa fa-tachometer'></i> ".__('Options de sauvegarde',__FILE__)."</legend>";
				$_specifiqueHtml .= "<div class='form-group forceSynchro'>";
				$_specifiqueHtml .= "	<label class='col-sm-3 control-label'>".__('Forcer la synchronisation',__FILE__).olindoteTools::help_writeHtmlIcone($oT_txtHelp)."</label>";
				$_specifiqueHtml .= "	<div class='col-sm-3'><input type='checkbox' class='eqLogicAttr' data-l1key='configuration' data-l2key='forceSynchro' /></div>";
				$_specifiqueHtml .= "</div>";
				if (method_exists('olindoteTools','eqlogicHtml_zoneEquipement')) echo olindoteTools::eqlogicHtml_zoneEquipement(array(),$_specifiqueHtml); ?>
			
			<!-- TAB : Paramètres complémentaires =================================================== -->
			<div role="tabpanel" class="tab-pane" id="paramOther">
				<form class="form-horizontal">
					<fieldset>
						<legend><i class="icon kiko-picture"></i> {{Paramètres graphiques}}</legend>
						<div class="form-group">
							<label class="col-sm-3 control-label" >
								{{Autre Widget}}
								<?php $oT_txtHelp = array(__("A cocher si vous souhaitez utiliser un widget \"personnel\"; données brutes affichées dans ce cas. Laissez décocher pour utiliser le widget du plugin.",__FILE__));
									echo olindoteTools::help_writeHtmlIcone($oT_txtHelp); ?>
							</label>
							<div class="col-sm-3">
								<input type="checkbox" class="eqLogicAttr" data-l1key="configuration"  data-l2key="widgetOther" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label" >{{Ne pas afficher la date du jour}}</label>
							<div class="col-sm-4">
								{{Dashboard/Vue}} : <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="hideDateDashboard" checked />&nbsp;
								{{Mobile}} : <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="hideDateMobile" checked />&nbsp;
							</div>
						</div> 
						<div class="form-group">
							<label class="col-sm-3 control-label" >
								{{(multi journée) Format d'affichage}}
								<?php $oT_txtHelp = array(__("'1 journée avec jour de navigation': la journée sélectionné est affichée, pour voir le contenu des autres journées, cliquez sur la date souhaitée.",__FILE__),
									__("'tout afficher': toutes les journées sont affichées en 1 seule fois.",__FILE__));
									echo olindoteTools::help_writeHtmlIcone($oT_txtHelp); ?>
							</label>
							<div class="col-sm-3">
								<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="calendarStyle">
									<option value="1dayAndNav">{{1 journée avec jour de navigation (standard)}}</option>
									<option value="all">{{tout afficher}}</option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label" >
								{{(multi agenda) Format d'affichage}}
								<?php $oT_txtHelp = array(__("Utilisé pour l'affichage des calendriers au niveau du widget du Dashboard (ne s'applique pas à la version Mobile).",__FILE__));
									echo olindoteTools::help_writeHtmlIcone($oT_txtHelp); ?>
							</label>
							<div class="col-sm-2">
								<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="widgetStyle">
									<option value="V">{{Vertical (standard)}}</option>
									<option value="H">{{Horizontal}}</option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label" >
								{{Redessiner la tuille automatiquement}}
								<?php $oT_txtHelp = array(__("Coché: Permet de redimensionner la automatiquement la hauteur de le tuile, si le contenu (événement de l'agenda) dépasse la taille de la tuile.",__FILE__));
									echo olindoteTools::help_writeHtmlIcone($oT_txtHelp); ?>
							</label>
							<div class="col-sm-3">
								<input type="checkbox" class="eqLogicAttr" data-l1key="configuration"  data-l2key="allowAutoResize" />
							</div>
						</div>
						
						<legend><i class="far fa-check-circle"></i> {{Paramètres évènements & traitement des actions}}</legend>
						<div class="form-group showAddMessage">
							<label class="col-sm-3 control-label" >
								{{Ajouter Message sur "action"}}
								<?php $oT_txtHelp = array(__("Autorise l'ajout d'un \"Message\" lors du déclenchement d'une action/scénario/interaction par le plugin.",__FILE__));
									echo olindoteTools::help_writeHtmlIcone($oT_txtHelp); ?>
							</label>
							<div class="col-sm-2">
								<input type="checkbox" class="eqLogicAttr" data-l1key="configuration"  data-l2key="addMsgForActSc" />
							</div>
						</div>
						<div class="form-group catchAllow">
							<label class="col-sm-3 control-label" >
								{{Autorise "rattrapage" / Période }}
								<?php $oT_txtHelp = array(__("Définie la période sur laquelle une \"action/scénario\" peut être lancé, si cela n'a pas été fait à l'horaire définit.",__FILE__));
									echo olindoteTools::help_writeHtmlIcone($oT_txtHelp); ?>
							</label>
							<div class="col-sm-1">
								<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="catchAccepted" />&nbsp;
							</div>
							<div class="col-sm-3 catchViewPeriod" style="display:none;">
								<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="catchPeriod">
									<option value="2">{{les 2 dernières minutes}}</option>
									<option value="5">{{les 5 dernières minutes}}</option>
									<option value="15">{{les 15 dernières minutes}}</option>
									<option value="60">{{l'heure précédente}}</option>
								</select>
							</div>
						</div>

						<legend><i class="far fa-check-circle"></i> {{Paramètres traitement}}</legend>
						<div class="form-group">
							<label class="col-sm-3 control-label" >
								{{Log séparée par Agenda"}}
								<?php $oT_txtHelp = array(__("Permet d'avoir des fichiers de log séparé par Agenda.",__FILE__));
									echo olindoteTools::help_writeHtmlIcone($oT_txtHelp); ?>
							</label>
							<div class="col-sm-2">
								<input type="checkbox" class="eqLogicAttr" data-l1key="configuration"  data-l2key="logSepar" />
							</div>
						</div>
					</fieldset>
				</form>
			</div>

			<!-- TAB : Agenda =================================================== -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<form class="form-horizontal">
					<fieldset>
						<legend style="padding-top:7px;">
							<i class="fa fa-list-alt"></i> {{Mes Agendas}} 
							<a style="margin-right:5px;" class="btn btn-success btn-xs cmdAction pull-right" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un agenda}}</a>
						</legend>
						<div id="table_cmd"></div>
						<div id="tr_cmd_tmp" style="display:none;"></div>
					</fieldset>
				</form>
			</div>
		</div>
	</div>
</div>
<style>
#table_cmd tr { border-bottom:1px solid #AAAAAA!important; }
</style>

<?php include_file('desktop', $oT_plugin, 'js', $oT_plugin);?>
<?php include_file('core', 'plugin.template', 'js');?>