<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>

<div class="form-horizontal">
	<div class="alert alert-info" style="">
		{{Cette fenêtre vous permet de lister les calendriers disponibles au niveau de votre serveur CalDAV et de récupérer l'id du calendrier à récupérer.<br/>
		<b>Vous devez ensuite faire un "copier/coller" de la valeur du "l'ID" correspondant ou cliquer sur le bouton "COPIER".</b><br/><br/>
		<u>Remarque:</u> si vous n'indiquer aucun nom, le 1er calendrier sera utilisé par défaut.}}
	</div>
	<center>
		<table id="table_caldavList" class="table table-bordered table-condensed" style="width:75%;">
			<thead>
				<tr>
					<th style="width:20px;"> </th>
					<th style="width:20%;"> </th>
					<th style="width:35%;text-align:center;">{{Id (valeur à copier)}}</th>
					<th style="text-align:center;">{{Nom affiché}}</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		</br>
		<a class="btn btn-danger" data-dismiss="modal"><i class="fa fa-minus-circle"></i> {{Fermer}}</a>
	</center>
</div>
