<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>

<div class="form-horizontal">
	<div class="alert alert-info" style="font-size:90%;">
		{{Cette fenêtre vous permet de créer le format de l'événement interprété par jeedom, renseigné dans le champs description d'un événement Agenda iCal, pour pouvoir lancer automatiquement des scénarios Jeedom.<br/>
		Le format attendu doit respecter les points suivants <i>(reportez vous à la documentation pour plus d'information)</i> : <br/>
		<br/>
		Remarque: les noms et valeurs des variables ne doivent pas contenir de "=" ou de "|".}}
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label" >{{Type d'action (action ou scénario)}}</label>
		<div class="col-sm-4">
			<select id="iCalendar_type" class="form-control">
				<option value="scenario">Scénario</option>
				<option value="action">Action</option>
			</select>
		</div>
	</div>
	<div class="viewScenario">
		<div class="form-group">
			<label class="col-sm-3 control-label" >{{1ère minute: nom du scénario}}</label>
			<div class="col-sm-4">
				<select id="iCalendar_idScenarioDeb" class="form-control">
				<?php
				foreach (scenario::all() as $_oScenario) {
					echo '<option value="' . $_oScenario->getId() . '">[' . (($_oScenario->getGroup()=='')?'Aucun':$_oScenario->getGroup()). '] '. $_oScenario->getName() . '</option>';
				}
				?>
				</select>
			</div>
			<span>({{Valeur obligatoire}})</span>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" >{{1ère minute: nom de la variable (ou action)}}</label>
			<div class="col-sm-4">
				<select id="iCalendar_varNameDeb" class="form-control">
					<option value="">{{Aucune}}</option>
					<?php
					foreach (utils::o2a(dataStore::byTypeLinkId('scenario')) as $_aVarScenario) {
						echo '<option value="' . $_aVarScenario['key'] . '">' . $_aVarScenario['key']. '</option>';
					}
					?>
					<option value="">------</option>
					<option value="#active">{{Active scénario}}</option>
					<option value="#desactive">{{Désactive scénario}}</option>
				</select>
			</div>
			<span>({{Variable obligatoire pour passer des données au sénario, lors de la 1ère minute}})</span>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">{{1ère minute : valeur de la variable}}</label>
			<div class="col-sm-4">
				<input type="text" id="iCalendar_varValDeb" class="form-control"/>
			</div>
			<span>({{Valeur non obligatoire}} ; {{permet d'envoyer une valeur à la variable utilisée dans le scénario, pour la 1ère minute de la période}})</span>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" >{{dernière minute: nom du scénario}}</label>
			<div class="col-sm-4">
				<select id="iCalendar_idScenarioFin" class="form-control">
					<option value="">{{Aucun}}</option>
					<?php
					foreach (scenario::all() as $_oScenario) {
						echo '<option value="' . $_oScenario->getId() . '">[' . (($_oScenario->getGroup()=='')?'Aucun':$_oScenario->getGroup()). '] '. $_oScenario->getName() . '</option>';
					}
					?>
				</select>
			</div>
			<span>({{Valeur obligatoire}})</span>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" >{{dernière minute : nom de la variable (ou action)}}</label>
			<div class="col-sm-4">
				<select id="iCalendar_varNameFin" class="form-control">
					<option value="">{{Aucune}}</option>
					<?php
					foreach (utils::o2a(dataStore::byTypeLinkId('scenario')) as $_aVarScenario) {
						echo '<option value="' . $_aVarScenario['key'] . '">' . $_aVarScenario['key']. '</option>';
					}
					?>
					<option value="">------</option>
					<option value="#active">{{Active scénario}}</option>
					<option value="#desactive">{{Désactive scénario}}</option>
				</select>
			</div>
			<span>({{Variable obligatoire pour passer des données au sénario, lors de la 1ère minute}})</span>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">{{dernière minute : valeur de la variable}}</label>
			<div class="col-sm-4">
				<input type="text" id="iCalendar_varValFin" class="form-control"/>
			</div>
			<span>({{Valeur non obligatoire}} ; {{permet d'envoyer une valeur à la variable utilisée dans le scénario, pour la dernière minute de la période}})</span>
		</div>
	</div>
	<div class="viewAction" style="display:none;">
		<div class="form-group">
			<label class="col-sm-3 control-label" >{{Format de la commande}}</label>
			<div class="col-sm-4">
				<select id="iCalendar_cmdFormat" class="form-control">
					<option value="id">id</option>
					<option value="name">nom (ex: [obj][eq][cmd])</option>
				</select>
			</div>
			<span>({{Remarque: avec l'id, si votre nom de commande change, votre action dans l'événement sera toujours opérationnelle.}})</span>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" >{{1ère minute: commmande action}}</label>
			<div class="col-sm-4">
				<div class="input-group input-group-sm">
					<input class="form-control" id="iCalendar_actionTypeDeb" value="" style="display:none;"/>
					<input class="form-control" id="iCalendar_actionDeb" value="" autocomplete="off" />
					<span class="input-group-btn"><button class="btn btn-default btnSelectCmd btnCmdDeb" type="button" title="Sélectionner la commande"><i class="fa fa-list-alt"></i></button></span>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" >{{1ère minute: action sur la commande}}</label>
			<div class="col-sm-4">
				<select id="iCalendar_actionDoDeb" class="form-control">
					<option value="#execute">{{Execute la commande (standard)}}</option>
					<option value="#active">{{Active l'équipement associé}}</option>
					<option value="#desactive">{{Désactive l'équipement associé}}</option>
				</select>
			</div>
		</div>
		<div class="form-group actIsSliderDeb" style="display:none;">
			<label class="col-sm-3 control-label" >{{1ère minute: valeur}}</label>
			<div class="col-sm-4">
				<input class="form-control" id="iCalendar_sliderDeb" value="" />
			</div>
		</div>
		<div class="form-group actIsMsgDeb" style="display:none;">
			<label class="col-sm-3 control-label" >{{1ère minute: titre}}</label>
			<div class="col-sm-4">
				<input class="form-control" id="iCalendar_titleDeb" value="" />
			</div>
		</div>
		<div class="form-group actIsMsgDeb" style="display:none;">
			<label class="col-sm-3 control-label" >{{1ère minute: message}}</label>
			<div class="col-sm-4">
				<input class="form-control" id="iCalendar_messageDeb" value="" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" >{{dernière minute: commmande action}}</label>
			<div class="col-sm-4">
				<div class="input-group input-group-sm">
					<input class="form-control" id="iCalendar_actionTypeFin" value="" style="display:none;"/>
					<input class="form-control" id="iCalendar_actionFin" value="" autocomplete="off" />
					<span class="input-group-btn"><button class="btn btn-default btnSelectCmd btnCmdFin" type="button" title="Sélectionner la commande"><i class="fa fa-list-alt"></i></button></span>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label" >{{dernière minute: action sur la commande}}</label>
			<div class="col-sm-4">
				<select id="iCalendar_actionDoFin" class="form-control">
					<option value="#execute">{{Execute la commande (standard)}}</option>
					<option value="#active">{{Active l'équipement associé}}</option>
					<option value="#desactive">{{Désactive l'équipement associé}}</option>
				</select>
			</div>
		</div>
		<div class="form-group actIsSliderFin" style="display:none;">
			<label class="col-sm-3 control-label" >{{dernière minute: valeur}}</label>
			<div class="col-sm-4">
				<input class="form-control" id="iCalendar_sliderFin" value="" autocomplete="off" />
			</div>
		</div>
		<div class="form-group actIsMsgFin" style="display:none;">
			<label class="col-sm-3 control-label" >{{dernière minute: titre}}</label>
			<div class="col-sm-4">
				<input class="form-control" id="iCalendar_titleFin" value="" />
			</div>
		</div>
		<div class="form-group actIsMsgFin" style="display:none;">
			<label class="col-sm-3 control-label" >{{dernière minute: message}}</label>
			<div class="col-sm-4">
				<input class="form-control" id="iCalendar_messageFin" value="" />
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-3" style="text-align:right;">
			<a class="btn btn-success" id="bt_genEventFormat" style="margin-top:6px;"><i class="fa fa-check-circle"></i> {{Générer}}</a><br/>
			<a class="btn btn-warning" id="bt_raz" style="margin-top:6px;"><i class="fa fa-check-circle"></i> {{RAZ}}</a>
		</div>
		<div class="col-sm-6">
			<textarea id="iCalendar_EventFormat" class="form-control" style="height:90px;background-color:#EEEEEE;"/></textarea>
		</div>
		<span style="color:#FF0000;">(<i class="fa fa-pencil"></i> {{Après génération, faite un "copier" de la trame et la "coller" dans le champs description de l'événement}})</span>
	</div>
	<center><a class="btn btn-danger" data-dismiss="modal"><i class="fa fa-minus-circle"></i> {{Fermer}}</a></center>
</div>
