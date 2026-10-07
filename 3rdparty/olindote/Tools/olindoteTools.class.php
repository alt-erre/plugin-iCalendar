<?php
/**
 * This file is part of Olindote.
 */

/* ----- ************************** INIT ****************************** */
// ---- définition des constantes ---- //
if (!defined('OLINDOTE_USER_INFO_URL'))	define('OLINDOTE_USER_INFO_URL', 'https://olindote.fr/jeedom.php');
if (!defined('OLINDOTE_DIR_BACKUP'))	define('OLINDOTE_DIR_BACKUP', dirname(__FILE__) .'/../../../../backup/olindotePlugins');
if (!defined('OLINDOTE_DIR_PLUGINS'))	define('OLINDOTE_DIR_PLUGINS', dirname(__FILE__) .'/../../../../plugins');

/**
 * Class regroupant les différentes fonctions utilisées dans les plugins créés par "Abarrau"
 * @author abarrau
 */
class olindoteTools {
	private static $_VERSION="1.18"; // <<<< PENSER à MODIFIER le paramètre "_oT_toolsVersion" dans le fichier "info.json" //
	//public static $_PATH_BACKUP = '/../../../../../../olindotePluginArchive';

/* ---------------------------------------------------------------------------------- */
/* ----- ************************** Interne à la CLass ****************************** */
	/**
	 * Get Class Version
	 */	
 	public static function getVersion() {
		return self::$_VERSION;
	}
	
/* ----------------------------------------------------------------------------------------------------- */
/* ----- ************************** Gestion des fonctions "olindoteTools" ****************************** */
	/**
	 * Récupère (et crée, si besoin) le "UserId" pour OlindoteTools
	 */	
	public static function ot_checkUserId() {
		$_userId = config::byKey('userId','olindoteTools',0);
		if (empty($_userId)||is_null($_userId)||($_userId==0)) {
			$_userId = md5(date('Ymdhis').rand(0,100));
			config::save('userId', $_userId, 'olindoteTools');
		}
		return $_userId;
	}
	/**
	 * Envoi infos plugin à olindote
	 */	
	public static function ot_sendInfoToOlindote($_plugin='',$_type='',$debug=false,$_cVersion='n/a') {
		if (config::byKey('sendInfo::allow','olindoteTools','-1') == '1') {
			$_plugins = (function_exists('olindoteCheckListPlugin'))?olindoteCheckListPlugin():array(); 
			$_class = config::byKey('classInstalled','olindoteTools',array('d'=>''));
			$_aReq['olindoteTools'] = json_encode(array('id'=> self::ot_checkUserId(), 'origine'=>$_type.'|'.$_plugin.'|'.$_cVersion, 'class'=>$_class, 'plugins'=> $_plugins));
			log::add('olindoteTools','debug','[ot_sendInfoToOlindote()] data send='.print_r($_aReq,true));
			$_sUrl = ($debug)?OLINDOTE_USER_INFO_URL.'?debug':OLINDOTE_USER_INFO_URL;
			self::network_sendRequestByCurl('olindoteTools', $_sUrl, $_aReq);
		}
	}
	
/* ------------------------------------------------------------------------------------------------------------- */
/* ----- ************************** Gestion des fonctions "d'archivage du plugin" ****************************** */
	/**
	 * Création du backup
	 * @param String $_plugin nom du plugin	 
	 */	
	public static function backup_createBackup($_plugin='', $_aPath=array(), $_lastDelete=true) {
		if ($_plugin=='') return false;
		/*$_backupDirectory = config::byKey('backup::directory','olindoteTools','');
		if ($_backupDirectory!='') {
			self::$_PATH_BACKUP = $_backupDirectory; 
		} else {
			self::$_PATH_BACKUP = cleanPath(dirname(__FILE__).self::$_PATH_BACKUP);
			config::save('backup::directory', self::$_PATH_BACKUP, $_plugin);
		}*/
		// crée le répertoire //
		if (!self::checkAndCreateDirectory($_plugin, OLINDOTE_DIR_BACKUP)) {
			log::add($_plugin.'_backup', 'warning', '[olindoteTools::backup_createBackup()] can NOT create backup directory !');
			return false;
		}
		// supprimer les fichiers temporaires dans le répertoire (si besoin)
		self::backup_deleteTmpPath($_plugin, $_aPath=array());
		// suppression de l'ancien fichier  (correspond à l'archive X-2) //
		if ($_lastDelete) {
			$_lastFN = config::byKey('backup::lastFile',$_plugin,'');
			if ($_lastFN != '') {
				if (file_exists(OLINDOTE_DIR_BACKUP.'/'.$_lastFN)) {
					unlink(OLINDOTE_DIR_BACKUP.'/'.$_lastFN);
					log::add($_plugin.'_backup', 'debug', '[olindoteTools::backup_createBackup()] oldest file are delete ('.OLINDOTE_DIR_BACKUP.'/'.$_lastFN.')');
				}
			}
			config::save('backup::lastFile', '', $_plugin);
		}
		// création du fichier temporaire //
		$_newFN = self::backup_getFileName($_plugin);
		$_zipDest = OLINDOTE_DIR_BACKUP.'/'.$_newFN.'.zip';
		$_repSource = getRootPath().'/plugins/'.$_plugin; //cleanPath(dirname(__FILE__) .'/../../../../plugins/'.$_plugin);
		if (file_exists($_zipDest)) {
			unlink($_zipDest);
			log::add($_plugin.'_backup', 'debug', '[olindoteTools::backup_createBackup()] new file name already exist, delete it ('.$_zipDest.')');
		}
		if (!create_zip($_repSource, $_zipDest)) {
			log::add($_plugin.'_backup', 'warning', '[olindoteTools::backup_createBackup()] ERROR during create zip file (source="'.$_repSource.'" / zip="'.$_zipDest.'")');
			return false;
		}
		log::add($_plugin.'_backup', 'debug', '[olindoteTools::backup_createBackup()] Plugin directory ('.$_repSource.') is archived on zip file ('.$_zipDest.')');
		
		config::save('backup::lastFile', config::byKey('backup::currentFile',$_plugin,''), $_plugin);
		config::save('backup::currentFile', $_newFN, $_plugin);
		return true;
	}
	/**
	 * Retourne le nom du fichier d'archive
	 * 	-- plugin_versionPlugin_versionJeedom_dateArchive
	 * @param String $_plugin nom du plugin	 
	 */	
	public static function backup_getFileName($_plugin='') {
		if ($_plugin=='') return false;
		return $_plugin.'_'.str_replace('.','-',self::getInfoOnInfoJson($_plugin,'_oT_pluginVersion')).'_'.str_replace('.','-',jeedom::version()).'_'.date('YmdHis');
	}
	/**
	 * Suppression des fichiers temporaires (s'il y a)
	 * @param String $_plugin nom du plugin	 
	 */	
	public static function backup_deleteTmpPath($_plugin='', $_aPath=array()) {
		//$_pathPlugin = cleanPath(dirname(__FILE__) .'/../../../../plugins/'.$_plugin.'/');
		$_pathPlugin = getRootPath().'/plugins/'.$_plugin.'/';
		if (count($_aPath)>0) {
			for($i=0;$i<count($_aPath);$i++) {
				$_pathDir = $_pathPlugin.'/'.$_aPath[$i];
				if (file_exists($_pathDir)) {
					$_aFiles = glob($_pathDir.'/*');
					$_nbTmp = count($_aFiles);
					if ($_nbTmp > 0) {
						foreach ($_aFiles as $_f) unlink($_f);
						log::add($_plugin, 'debug', '[olindoteTools::backup_deleteTmpPath()] nb file = "'.$_nbTmp.'" on "'.$_pathDir.'"');
					} else log::add($_plugin, 'debug', '[olindoteTools::backup_deleteTmpPath()] no files on this directory: "'.$_pathDir.'"');			
				}
			}
		}
	}
	/**
	 * Initialisation des données pour la fonction backup
	 * @param String $_plugin nom du plugin	 
	 */	
	public static function backup_init($_plugin='') {
		if ($_plugin=='') return false;
		config::save('backup::directory', '', $_plugin);
		config::save('backup::currentFile', '', $_plugin);	
		config::save('backup::lastFile', '', $_plugin);	
	}

/* ------------------------------------------------------------------------------------------------ */
/* ----- ************************** Gestion de la variable "Version" ****************************** */
	/**
	 * Initialise les informations et sauvegarde (retourne le tableau, au besoin)
	 * @param String $_plugin nom du plugin
	 * @param boolean $_save définie s'il faut sauvegarder en table de config
	 */	
	public static function version_setValue($_plugin='', $_save=true) {
		if ($_plugin=='') return false;	
		$_versionDefault = '0.0.1';
		$_aVersion = config::byKey('olindote::version',$_plugin,0);
		if (!is_array($_aVersion)) $_aVersion = array();
		$_aVersion['lastVersion'] = (isset($_aVersion['curVersion']))?$_aVersion['curVersion']:false;
		$_aVersion['lastVersionDate'] = (isset($_aVersion['curVersionDate']))?$_aVersion['curVersionDate']:false;
		$_aVersion['lastVJeedom'] = jeedom::version();
		$_versionInJson = self::getInfoOnInfoJson($_plugin,'_oT_pluginVersion'); 
		$_aVersion['curVersion'] = ($_versionInJson===false)?$_versionDefault:$_versionInJson;
		$_aVersion['curVersionDate'] = date('Y-m-d H:i:s');
		if ($_save) config::save('olindote::version', $_aVersion, $_plugin);
	}
	/**
	 * Récupère les informations depuis la config jeedom
	 * @param String $_plugin nom du plugin
	 */	
	public static function version_getPluginInfo($_plugin='') {
		$_aV = array();
		if ($_plugin=='') { return false; }
		$_aV = config::byKey('olindote::version',$_plugin,'?');
		$_aVersionReturn['curVersion'] = (is_array($_aV)&&(isset($_aV['curVersion'])))?$_aV['curVersion']:$_aV;
		$_aVersionReturn['curVersionDate'] = (is_array($_aV)&&(isset($_aV['curVersionDate'])))?$_aV['curVersionDate']:false;
		$_aVersionReturn['lastVersion'] = (is_array($_aV)&&(isset($_aV['lastVersion'])))?$_aV['lastVersion']:false;
		$_aVersionReturn['lastVersionDate'] = (is_array($_aV)&&(isset($_aV['lastVersionDate'])))?$_aV['lastVersionDate']:false;
		$_aVersionReturn['lastVJeedom'] = (is_array($_aV)&&(isset($_aV['lastVJeedom'])))?$_aV['lastVJeedom']:false;
		return $_aVersionReturn;
	}

/* ---------------------------------------------------------------------------------------------- */
/* ----- ************************** Gestion des échanges "Réseaux" ****************************** */
	/**
	 * Met à jour l'état de la santé du réseau pour l'équipement/commande concernée
	 * @param array $_aStat tableau avec l'état et la date : 'date'=>'statut'
	 * @param object $_obj eqlogic ou cmd, 
	 * @param string $_plugin nom du plugin
	 */
	public static function network_setHealth($_aStat, $_obj) {
		$_plugin = self::getObjName($_obj);
		if ($_plugin=='') return false;
		if ((is_array($_aStat)) && (count($_aStat)>0)) {
			$_healthNetwork = $_obj->getConfiguration('healthNetwork',false); 	// TODO: A SUPPRIER à la prochaine version //
			if ($_healthNetwork===false) { $_healthNetwork = $_obj->getConfiguration('oT::Network::health',array()); }
				else { $_obj->setConfiguration('healthNetwork', false);	}		// TODO: trouver le moyen de supprimer ce paramètre de "configuration" //
			$_nbLast = config::byKey('health::network::nbLast', $_plugin ,10);
			$_healthNetwork = (count($_healthNetwork)>=$_nbLast)?array_slice($_healthNetwork,0,($_nbLast-1)):$_healthNetwork;
			array_unshift($_healthNetwork, json_encode($_aStat));
			if (current($_aStat)=='X') { 
				self::network_setStatut($_obj,false); 
			} else { 
				self::network_setStatut($_obj,true); 
				if (substr(strtolower(get_class($_obj)),-3)!='cmd') $_obj->setStatus(array('lastCommunication' => date('Y-m-d H:i:s'), 'timeout' => 0));
			}
			log::add($_plugin, 'debug', '['.((isset($_obj->_whatLog))?$_obj->_whatLog:'').'|'.$_obj->getName().'('.$_obj->getId().')] network_setHealth()._healthNetwork='.print_r($_healthNetwork,true));
			// sauvegarde des informations //
			$_obj->_isSimpleSave = true;
			$_obj->setConfiguration('oT::Network::health', $_healthNetwork);
			$_obj->save();
			return true;
		}
		log::add($_plugin, 'debug', '['.((isset($_obj->_whatLog))?$_obj->_whatLog:'').'|'.$_obj->getName().'('.$_obj->getId().')] network_setHealth(): ERROR: data information is not an array');
		return false;
	}
	/**
	 * Retourne l'état du réseau à afficher sur la page santé
	 */
	public static function network_setArrayHealthForHealthPage($_obj) {
		$_plugin = self::getObjName($_obj);
		if ($_plugin=='') return false;
		if ($_obj->getIsEnable()) {
			$_iconOK = "<i style='color:#5cb85c;' class='fa fa-check-circle'></i>";
			$_iconKO = "<i style='color:#d9534f;' class='fa fa-times-circle'></i>";
			$_nbError=0;
			$_healthNetwork = $_obj->getConfiguration('healthNetwork',false); // TODO: A SUPPRIER à la prochaine version //
			if ($_healthNetwork===false) {
				$_healthNetwork = $_obj->getConfiguration('oT::Network::health',array());
			}
			$_resEqL['state'] = true;
			$_resEqL['test'] = __('Etat réseau de',__FILE__).' : "'.$_obj->getName().'"';
			$_resEqL['result'] = ''; 
			$_resEqL['advice'] = '';
			foreach($_healthNetwork as $_aState) {
				$_aStateJson = json_decode($_aState);
				if (count((array)$_aStateJson) > 0) {
					foreach($_aStateJson as $_k => $_v) {
						if($_v=='X') {	
							$_resEqL['state'] = false;
							$_nbError++;
						}
						$_resEqL['result'] = $_resEqL['result'].'<span class="cursor" title="'.__('Executé à',__FILE__).': '.$_k.'">'.(($_v=='o')?$_iconOK:$_iconKO).'</span> ';
					}
				}
			}
			if ($_resEqL['result'] == '') return false;
			if (!$_resEqL['state']) {
				$_resEqL['advice'] = __('Au cours des ',__FILE__).config::byKey('health::network::nbLast', $_plugin, 10).__(' derniers accès, ',__FILE__).$_nbError.__(' sont en erreur.',__FILE__);
			}
			return $_resEqL;
		}
		return false;
	}
	/**
	 * Retourne le résultat d'une requête CURL (error/info/data)
	 */
	public static function network_sendRequestByCurl($_plugin='', $_sURL='', $_aData=null, $_timeOut=10, $_setUserAgent=false) {
		if ((is_null($_sURL))||($_sURL=='')||(empty($_sURL))) {
			return false;
		}
		$_ch = curl_init();
		$_return['fError'] = "";
		curl_setopt($_ch, CURLOPT_HEADER, false);
		curl_setopt($_ch, CURLINFO_HEADER_OUT, true);
                if ($_setUserAgent!=false) {
                    if ($_setUserAgent===true) $_setUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36';
                    curl_setopt($_ch, CURLOPT_USERAGENT, $_setUserAgent);
                }
		curl_setopt($_ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($_ch, CURLOPT_URL, $_sURL);
		curl_setopt($_ch, CURLOPT_TIMEOUT, (($_timeOut!=null)?$_timeOut:10));
		if (substr($_sURL,0,6)=='https:') {
			curl_setopt($_ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		if ((is_array($_aData))&&(count($_aData)>0)) {
			curl_setopt($_ch, CURLOPT_POST, 1);
			curl_setopt($_ch, CURLOPT_POSTFIELDS, $_aData);
		}
		$_return['fData'] = curl_exec($_ch);
		$_return['fInfo'] = curl_getinfo($_ch);
		if ((isset($_fInfo['http_code']))&&($_fInfo['http_code']==0)) {
			$_return['fError'] = curl_error($_ch);
		}
		curl_close($_ch);
		log::add($_plugin,'debug','[olindoteTools::network_sendRequestByCurl()] _return='.print_r($_return,true));
		return $_return;
	}
	/**
	 * Enregistre le dernier état réseau
	 */
	public static function network_setStatut($_obj, $_value) {
		$_obj->_isSimpleSave = true;
		$_obj->setConfiguration('oT::Network::statut', $_value);
		if ($_value==false) {
			if (empty($_obj->getConfiguration('oT::Network::healthLastDate',''))) $_obj->setConfiguration('oT::Network::healthLastDate',date('Y-m-d H:i:s'));
		} else { $_obj->setConfiguration('oT::Network::healthLastDate',''); }
		$_obj->save();
	}
	/**
	 * Récupère le dernier état réseau
	 */
	public static function network_getStatut($_obj) {
		return $_obj->getConfiguration('oT::Network::statut', false);
	}
	/**
	 * Récupère la date du dernier état réseau
	 */
	public static function network_getLastDateStatut($_obj) {
		if (!is_object($_obj)) return '';
		return $_obj->getConfiguration('oT::Network::healthLastDate','');
	}
	
/* -------------------------------------------------------------------------------------------------------------------------- */
/* ----- ************************** HTML // Gestion d'affichage des zones communes dans Jeedom ****************************** */
	/**
	 * Définie la zone des boutons de configuration
	 * @param array $_aMenus tableau listant les menus autorisés
	 */
	public static function eqlogicHtml_zoneGestion($_plugin, $_aMenus) {
		$_sHtml = '<legend><i class="fa fa-cog"></i>  '.__("Gestion",__FILE__).'</legend><div class="eqLogicThumbnailContainer">';
		$_sOneMenu = "<div class='cursor eqLogicAction	' %EVENT-ACTION%><i class='%PICTO-CLASS%' style='color:%PICTO-COLOR%;'></i><br/><span style='color:%PICTO-COLOR%;'>%TEXT%</span></div>";
		if (count($_aMenus)>0) {
			foreach ($_aMenus as $_menu) {
				if (is_array($_menu)) {
					if (isset($_menu['id']) && isset($_menu['class']) && isset($_menu['color']) && isset($_menu['txt'])) {
						$_sHtml .= str_replace(array('%EVENT-ACTION%','%PICTO-CLASS%','%PICTO-COLOR%','%TEXT%'), array($_menu['id'],$_menu['class'],$_menu['color'],$_menu['txt']), $_sOneMenu);
					} else {
						log::add($_plugin, 'warning', '[olindoteTools::eqlogicHtml_zoneGestion()] ERROR: param menu is not good ; _menu='.print_r($_menu,true));
					}
				} else {
					switch($_menu) {
						case "add":		$_sHtml .= str_replace(array('%EVENT-ACTION%','%PICTO-CLASS%','%PICTO-COLOR%','%TEXT%'), array('data-action="add"','fa fa-plus-circle','var(--logo-primary-color)',__('Ajouter',__FILE__)), $_sOneMenu); break;
						case "config":	$_sHtml .= str_replace(array('%EVENT-ACTION%','%PICTO-CLASS%','%PICTO-COLOR%','%TEXT%'), array('data-action="gotoPluginConf"','fa fa-wrench','var(--logo-secondary-color)',__('Configuration',__FILE__)), $_sOneMenu); break;
					}
				}
			}
		}
		return $_sHtml.'</div>';
	}
	/**
	 * Définie la zone d'affichage des eqLogic
	 * @param array $_aEqLogics tableau des eqLogic
	 * @param array $__titleTable nom du tableau
	 */
	public static function eqlogicHtml_zoneEqlogicList($_plugin, $_titleTable, $_aEqLogics, $_showTableList=array('btn'=>false,'tab'=>false,'search'=>true,'btnTabJeedom'=>false), $_hideObj=array(), $_txtNoData='') {
		$_sHtml = '<legend style="margin-top:10px;"><i class="fa fa-table"></i> '.$_titleTable.'</legend>';
		$_sHtmlTableItem = '';
		$opacity = '';
		if ((!is_array($_showTableList))&&($_showTableList)) $_showTableList = array('btn'=>true,'tab'=>true,'search'=>true);
		$_hasColumnFromPlugin = false;
		if (isset($_showTableList['search'])&&($_showTableList['search'])) {
                    //$_sHtml .= "<input class='form-control input-sm' placeholder='Rechercher' style='width:calc(100% - 40px);display:inline-block;margin-bottom:4px;position:absolute;top:45px;' id='in_searchEqlogic'>";
                    $_sHtml .= "<div class='input-group' style='margin:5px;'><input class='form-control roundedLeft' placeholder='Rechercher' id='in_searchEqlogic'><div class='input-group-btn'><a id='bt_resetSearch' class='btn' style='width:30px'><i class='fas fa-times'></i></a>";
                    if (isset($_showTableList['btnTabJeedom'])&&($_showTableList['btnTabJeedom'])) { $_sHtml .= "<a class='btn roundedRight' id='bt_pluginDisplayAsTable' data-coresupport='1' data-state='0'><i class='fas fa-grip-lines'></i></a>"; }
                    if (isset($_showTableList['btn'])&&($_showTableList['btn'])) { $_sHtml .= "<a class='btn' id='btn_oT_changeView'><i class='fa fa-table'></i></a>"; }
                    $_sHtml .= "</div></div>";
                }		
                $_sHtml .= "<div class='eqLogicThumbnailContainer'>";
                if (count($_aEqLogics) == 0) {
			$_txtNoData = ($_txtNoData!='')?$_txtNoData:__('Vous n\'avez pas d\'équipement configuré ('.$_titleTable.'). Cliquez sur "Ajouter" pour créer votre 1er équipement.',__FILE__);
			$_sHtml .= "</div>"; // fin du eqLogicThumbnailContainer en cas de "no data" (le alerte ne doit pas être dans le div) //
			$_sHtml .= "<div id='oT_noEqLogic' class='alert alert-warning'>".$_txtNoData."</div>";
		} else {
			$_sHtml .= "<div style='width:100%;display:none;' class='oT_eqLogicIcons'>"; //
			foreach ($_aEqLogics as $eqLogic) {
				if (!in_array($eqLogic->getLogicalId(),$_hideObj)) {
					if (method_exists($eqLogic,'getIsEnable')) { 
						$opacity = ($eqLogic->getIsEnable())?'':'opacity:0.45!important;filter:grayscale(20%);';  //jeedom::getConfiguration('eqLogic:style:noactive'); // fonction plus utilisé en core 4.2.14//
						$inactive = ($eqLogic->getIsEnable())?'':'inactive';
					}
					$_sHtml .= "<div class='eqLogicDisplayCard cursor".$inactive."' data-eqLogic_id='".$eqLogic->getId()."' style='text-align:center;background-color:#ffffff;height:200px;margin-bottom:10px;padding:5px;border-radius:2px;width:160px;margin-left:10px;position:absolute;left:0px;".$opacity."' >"; //
					$_sHtml .= "	<img src='".$eqLogic->getImage()."' height='105' width='95' /><br/>";
					$_sHtml .= "	<span class='name' style='font-size:1.1em;position:relative;top:15px;word-break:break-all;white-space: pre-wrap;word-wrap:break-word;'>".((get_class($eqLogic)==$_plugin)?$eqLogic->getHumanName(true,true):$eqLogic->getName())."</span>";
					$_sHtml .= "</div>";
					if (isset($_showTableList['tab'])&&($_showTableList['tab'])) { 
						$_visibility = ($eqLogic->getIsVisible()=='1')?"<span class='label label-success'>OK</span>":"<span class='label label-danger'>NOK</span>";
						$_sHtmlTableItem .= "<tr class='eqLogicDisplayCard cursor' data-eqLogic_id='".$eqLogic->getId()."' style='height:10px!important;'><td style='text-align:center;'><span class='label label-info' style='font-size:0.9em;'>".$eqLogic->getId()."</span></td>";
						$_sHtmlTableItem .= "<td style='font-size:1.1em;".$opacity."' class='name'>".((get_class($eqLogic)==$_plugin)?$eqLogic->getHumanName(true):$eqLogic->getName())."</td><td style='font-size:0.9em;text-align:center;'>".$_visibility."</td>";
						$_sHtmlTableItem .= "<td style='text-align:center;'>".$eqLogic->getConfiguration('createtime')."</td>"; 
						if ((get_class($eqLogic)==$_plugin)&&(method_exists($_plugin,'olindote_SpecialEqlogicList'))) { $_hasColumnFromPlugin = true; eval('$_sHtmlTableItem .= '.$_plugin.'::olindote_SpecialEqlogicList($eqLogic);'); }
						$_sHtmlTableItem .= "</tr>";
					}
				}
			}
			$_sHtml .= "</div>";
			$_sHtml .= "<style>.table .tablesorter-headerRow i.tablesorter-icon { font-size:11px!important; }</style>";
			if (isset($_showTableList['tab'])&&($_showTableList['tab'])) {
				$_sHtmlTable = "<div style='width:100%;display:none;' class='oT_eqLogicList'><table class='table table-condensed tablesorter'>"; 
				eval('$_sHtmlTableSpecial = ($_hasColumnFromPlugin)?'.$_plugin.'::olindote_SpecialEqlogicList("TITLE"):"";');
				$_sHtmlTable .= "<thead><tr><th style='text-align:center;width:8%;'>ID</th><th>".__('Objet / Nom',__FILE__)."</th><th style='text-align:center;'>".__('Visibilité',__FILE__)."</th><th style='text-align:center;'>".__('Date création',__FILE__)."</th>".$_sHtmlTableSpecial."</tr></thead><tbody>%TABLE_LIST%</tbody></table></div>";
				$_sHtml .= str_replace('%TABLE_LIST%',$_sHtmlTableItem,$_sHtmlTable);
			}
			$_sHtml .= "</div>"; // fin du eqLogicThumbnailContainer en cas de "data" //
		}
		if (isset($_showTableList['btn'])&&($_showTableList['btn'])) {
			$_eqLViewType = config::byKey('olindote::eqLogic::viewType',$_plugin,'Icons');
			$_sHtml .= "<script language='javascript'>
				$('#btn_oT_changeView').on('click',function(){ 
					if ($('.oT_eqLogicList').is(':hidden')) { _eqLViewType='Array'; } else { _eqLViewType='Icons'; }
					oT_showList(_eqLViewType);
					$.ajax({ type:'POST',url:'plugins/".$_plugin."/core/ajax/".$_plugin.".ajax.php',dataType:'json', data:{action:'oT_saveEqLogicViewType', eqLogicViewType:_eqLViewType}, 
						error:function(request,status,error){handleAjaxError(request,status,error);}, success:function(data){ if(data.state=='ok'){ console.log('[".$_plugin."] olindote::eqLogic::viewType saved.') }}});
				});
				function oT_showList(_eqLViewType) { 
					_eqLViewType = (_eqLViewType=='Array')?'Array':'Icons';
					switch (_eqLViewType) {
						case 'Array': $('.oT_eqLogicList').show(); $('.oT_eqLogicIcons').hide(); break;
						case 'Icons': $('.oT_eqLogicList').hide(); $('.oT_eqLogicIcons').show(); $('.oT_eqLogicIcons').packery(); break;
					}
					$('.eqLogicThumbnailContainer').packery();
				} 
				oT_showList('".$_eqLViewType."'); </script>";
		}
		return $_sHtml;
	}
	/**
	 * Définie la liste des menus d'action à afficher
	 * @param array $_aMenus tableau listant les menus autorisés
	 */
	public static function eqlogicHtml_writeMenuAction($_aMenus) {
		$_sHtml = '<div class="input-group pull-right" style="display:inline-flex"><span class="input-group-btn">';
		if (count($_aMenus)>0) {
			foreach ($_aMenus as $_menu) {
				if (is_array($_menu)) {
					$_sHtml .= '<a class="btn '.$_menu['btn'].' btn-sm '.$_menu['class'].'"><i class="'.$_menu['icone'].'"></i> '.$_menu['name'].'</a>'; break;
				} else {
					switch($_menu) {
						case "save":	$_sHtml .= '<a class="btn btn-success btn-sm eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>'; break;
						case "remove":	$_sHtml .= '<a class="btn btn-danger btn-sm eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>'; break;
						case "copy":	$_sHtml .= '<a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-file"></i> {{Dupliquer}}</a>'; break;
						case "configure":	$_sHtml .= '<a class="btn btn-default btn-sm eqLogicAction" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>'; break;
					}
				}
			}
		}
		return $_sHtml."</span></div>";
	}
	/**
	 * Définie la liste du menu de gauche
	 * @param array $_eqLogics tableau listant les eqLogics
	 *	// ATTENTION : zone condannée en Jeedom V4 (maintenue en V3)
	 */
	public static function eqlogicHtml_zoneLeftMenu($_eqLogics, $_showAdd=true, $_showSearch=true, $_hideObj=array()) {
		$_sHtml = '';
		if (!self::jeedom_isCompatibleVersion('3.3.28')) {
			$_sHtml .= '<div class="col-xs-2">';
			$_sHtml .= '	<div class="bs-sidebar">';
			$_sHtml .= '		<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">';
			if ($_showAdd)		{ $_sHtml .= '<a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> '.__('Ajouter',__FILE__).'</a>'; }
			if ($_showSearch)	{ $_sHtml .= '<li class="filter" style="margin-bottom:5px;"><input class="filter form-control input-sm" placeholder="'.__('Rechercher',__FILE__).'" style="width:100%"/></li>'; }
			if (count($_eqLogics) > 0) {
				foreach ($_eqLogics as $eqLogic) {
					$opacity = ($eqLogic->getIsEnable())?'':jeedom::getConfiguration('eqLogic:style:noactive');
					if (in_array($eqLogic->getLogicalId(),$_hideObj))  $opacity .= "display:none;";
					$_sHtml .= '	<li class="cursor li_eqLogic" data-eqLogic_id="'.$eqLogic->getId().'" style="'.$opacity.'"><a>'.$eqLogic->getHumanName(true).'</a></li>';
				}
			}
			$_sHtml .= '		</ul>';
			$_sHtml .= '	</div>';
			$_sHtml .= '</div>';
		}
		return $_sHtml;
	}
	/**
	 * Retourne le nom du paramètre en cache pour le contenu du Widget
	 */
	public static function eqlogicHtml_getWidgetCacheName($_version, $_obj) {
		$_objId = (is_object($_obj))?$_obj->getId():'Z9Z';
		$user_id = '';
		if (isset($_SESSION) && isset($_SESSION['user']) && is_object($_SESSION['user'])) {
			$user_id = $_SESSION['user']->getId();
		}
		return 'widgetHtml'.$_objId.$_version.$user_id;
	}
	/**
	 * Définie la zone des onglets sur la page équipement
	 */
	public static function eqlogicHtml_tabList($_aTabList) {
		$_aDefaultTab = array(
			'#eqlogictab'=>array('h'=>'#eqlogictab','ctl'=>'home','i'=>'fa fa-terminal','n'=>__('Equipement',__FILE__),'c'=>'oT_eqTabName'),
			'#paramtab'=>array('h'=>'#paramtab','ctl'=>'profile','i'=>'fas fa-check-square','n'=>__('Paramètres',__FILE__),'c'=>'')
		);
		$_sHtml = '<ul class="nav nav-tabs" role="tablist">';
		$_sHtml .= '<li role="presentation" class=""><a class="eqLogicAction" aria-controls="home" role="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>';
		$_sOneTab = '<li role="presentation" class="%CLASS%"><a href="%HREF%" aria-controls="%CONTROL%" role="tab" data-toggle="tab"><i class="%ICON%"></i> %NAME%</a></li>';
		if (count($_aTabList)>0) {
			foreach ($_aTabList as $_tablist) {
				if (array_key_exists($_tablist['h'],$_aDefaultTab)) {
					$_tablist['ctl'] = ((isset($_tablist['ctl']))&&(strlen($_tablist['ctl'])>0))?$_tablist['ctl']:$_aDefaultTab[$_tablist['h']]['ctl'];
					$_tablist['i'] = ((isset($_tablist['i']))&&(strlen($_tablist['i'])>0))?$_tablist['i']:$_aDefaultTab[$_tablist['h']]['i'];
					$_tablist['n'] = ((isset($_tablist['n']))&&(strlen($_tablist['n'])>0))?$_tablist['n']:$_aDefaultTab[$_tablist['h']]['n'];
					$_tablist['c'] = ((isset($_tablist['c']))&&(strlen($_tablist['c'])>0))?$_tablist['c']:$_aDefaultTab[$_tablist['h']]['c'];
				}
				$_tablist['c'] = ((isset($_tablist['c']))&&(strlen($_tablist['c'])>0))?$_tablist['c']:'';
				$_sHtml .= str_replace(array('%HREF%','%CONTROL%','%ICON%','%NAME%','%CLASS%'), array($_tablist['h'],$_tablist['ctl'],$_tablist['i'],$_tablist['n'],$_tablist['c']), $_sOneTab);
			}
		}
		$_sHtml .= '</ul>';
		$_sHtml .= '<script language="javascript">';
		$_sHtml .= 'function oT_initReturnToThumbnailDisplay(){ $("#div_alert").hide(); $(".eqLogic ul.nav[role=\"tablist\"] li[role=\"presentation\"]").removeClass("active"); $(".eqLogic .tab-content .tab-pane").removeClass("active"); }';
		$_sHtml .= 'function oT_setEqlogicTablist(_n){';
		$_sHtml .= '	$(".jqAlert").hide(); $(".eqLogic .nav[role=\"tablist\"] .oT_eqTabName a").html($(".eqLogic .nav[role=\"tablist\"] .oT_eqTabName a").html().replace($(".eqLogic .nav[role=\"tablist\"] .oT_eqTabName a").prop("innerText")," Eql:["+_n+"]"));';
		$_sHtml .= '	$(".eqLogic .nav[role=\"tablist\"]").off("click",".eqLogicAction[data-action=\"returnToThumbnailDisplay\"]",oT_initReturnToThumbnailDisplay).on("click",".eqLogicAction[data-action=\"returnToThumbnailDisplay\"]",oT_initReturnToThumbnailDisplay);';
		$_sHtml .= '}';
		$_sHtml .= '</script>';
		return $_sHtml;
	}
	/**
	 * Affiche les données propres à JEEDOM page équipement (nom, catégorie, ...)
	 */
	public static function eqlogicHtml_zoneEquipement($_hideField=array(),$_specificHtml='') {
		$_sHtml = '';
		$_sHtml .= '<div role="tabpanel" class="tab-pane active" id="eqlogictab">';
		$_sHtml .= '	<form class="form-horizontal"><fieldset>';
		$_sHtml .= '		<legend>'.self::html_setIcone('fa fa-tachometer').' '.__('Equipement Jeedom',__FILE__).'</legend>';
		$_sHtml .= '		<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;" />';
		$_sHtml .= '		<input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" style="display:none;" />';
		$_sHtml .= '		<div class="form-group">';
		$_sHtml .= '			<label class="col-sm-3 control-label">'.__('Nom de l\'équipement',__FILE__).'</label>';
		$_sHtml .= '			<div class="col-sm-3"><input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="'.__('Nom de l\'équipement',__FILE__).'"/></div>';
		$_sHtml .= '		</div>';
		if (!in_array('object',$_hideField)) {
			$_sHtml .= '		<div class="form-group">';
			$_sHtml .= '			<label class="col-sm-3 control-label" >'.__('Objet parent',__FILE__).'</label>';
			$_sHtml .= '			<div class="col-sm-3">';
			$_sHtml .= '				<select class="eqLogicAttr form-control" data-l1key="object_id"><option value="">'.__('Aucun',__FILE__).'</option>';
			foreach (jeeObject::buildTree(null, false) as $object) { $_sHtml .= ' <option value="'.$object->getId().'">'.str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')).$object->getName().'</option>'; }
			$_sHtml .= '				</select>';
			$_sHtml .= '			</div>';
			$_sHtml .= '		</div>';
		}
		$_sHtml .= '		<div class="form-group">';
		$_sHtml .= '			<label class="col-sm-3 control-label">'.__('Statut',__FILE__).'</label>';
		$_sHtml .= '			<div class="col-sm-4">';
		$_sHtml .= __('Activé',__FILE__).' : <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />&nbsp;';
		if (!in_array('isVisible',$_hideField)) {
			$_sHtml .= __('Visible',__FILE__).' : <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />';
		}
		$_sHtml .= '			</div>';
		$_sHtml .= '		</div>';
		if (!in_array('category',$_hideField)) {
			$_sHtml .= '		<div class="form-group">';
			$_sHtml .= '			<label class="col-sm-3 control-label">'.__('Catégorie',__FILE__).'</label>';
			$_sHtml .= '			<div class="col-sm-7">';
			foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
				$_sHtml .= ' <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="'.$key.'" />'.$value['name'].' </label>';
			}
			$_sHtml .= '			</div>';
			$_sHtml .= '		</div>';
		}
		if (strlen($_specificHtml)>0) { $_sHtml .= $_specificHtml; }
		$_sHtml .= '	</fieldset></form>';
		$_sHtml .= '</div>';
		return $_sHtml;
	}
	/**
	 * Retourne la class icone en fonction de la version de Jeedom
	 *	// Impossé suite au passage en V4 Jeedom > pourra être supprimé à terme
	 */	
	public static function html_setIcone($_class,$_classOnly=false) {
		$_html = "<i class='%%CLASS%%'></i>";
		switch ($_class) {
			case "fa fa-refresh" : $_class=(self::jeedom_isCompatibleVersion('4.0.00'))?'fa fa-sync':'fa fa-refresh'; break;
			case "fa fa-pencil-square-o" : $_class=(self::jeedom_isCompatibleVersion('4.0.00'))?'fas fa-pencil-alt':'fa fa-pencil-square-o'; break;
			case "fa fa-tachometer" : $_class=(self::jeedom_isCompatibleVersion('4.0.00'))?'fas fa-tachometer-alt':'fa fa-tachometer'; break;
		}
		return ($_classOnly)?$_class:str_replace('%%CLASS%%',$_class,$_html);
	}

	
	
/* ----- ************************** HTML // Ajout des zones d'info sur la page configuration ****************************** */
	/**
	 * Tableaux de synthèse de la page Configuration 
	 */	
	public static function pluginInfo_writeHtml($_plugin='') {
		$debug=true;
		$_sHtml = "<script language='javascript'>";
		// gestion de l'afficahge des "champs cachés" (esterEggs) //
		$_sHtml .= "$('body').on('click','.panel-title .fa-cogs',function(){ $('.showExpertOnly').show(); });";
		// gestion de suppression des zones olindote //
		$_sHtml .= "$('#div_confPlugin').off('change').on('change',function(){ if ($('#span_plugin_id').value()!='".$_plugin."'){ $('#div_confPlugin').off('change'); $('.olindoteInfoZones').remove(); ".(($debug)?"console.log('(debug) SET OFF div_confPlugin');":"")."} });";
		// gestion des 3 zones d'informations //
		$_sHtml .= "var _panel_oT_PluginInfos=\"<div class='row olindoteInfoZones'>";
		// ***************** Tableau 1 //
		$_aVersion = ($_plugin!='')?self::version_getPluginInfo($_plugin):array();
		$_sHtml .= "	<div class='col-md-4 col-sm-12'><div class='panel panel-info' id='div_oT_pluginInfos'>";
		$_sHtml .= "		<div class='panel-heading'><h3 class='panel-title'><i class='fa fa-info-circle'></i> ".__("(olindoteTools) Information pour ce plugin",__FILE__)."</h3></div>";
		$_sHtml .= "		<div class='panel-body form-horizontal'><fieldset>";
		$_sHtml .= "			<div class='form-group'>"; 
		$_sHtml .= "				<label class='col-sm-5 control-label' style='text-align:right;'>".__("Version installée",__FILE__)." ";
			$_txtHelp = array(__('Version et date d\'installation du plugin',__FILE__));
			$_sHtml .= self::help_writeHtmlIcone($_txtHelp);
		$_sHtml .= "				</label>";
		$_sHtml .= "				<div class='col-sm-7'>".$_aVersion['curVersion']." <i>".(($_aVersion['curVersionDate']!=false)?"(".$_aVersion['curVersionDate'].")":"")."</i></div>";
		$_sHtml .= "			</div>";
		if (($_aVersion['lastVersion']!==false)&&($_aVersion['lastVersionDate']!==false)&&($_aVersion['lastVJeedom']!=false)) {
			$_sHtml .= "			<div class='form-group'>"; 
			$_sHtml .= "				<label class='col-sm-5 control-label' style='text-align:right;'>".__("Version archivée",__FILE__)." ";
				$_txtHelp = array(__('Version et date d\'archive de la précédente version du plugin',__FILE__), "(".__("Version Jeedom",__FILE__).":".$_aVersion['lastVJeedom'].")");
				$_sHtml .= self::help_writeHtmlIcone($_txtHelp);
			$_sHtml .= "				</label>";
			$_sHtml .= "				<div class='col-sm-7'>".$_aVersion['lastVersion']." <i>".(($_aVersion['lastVersionDate']!=false)?"(".$_aVersion['lastVersionDate'].")":"")."</i></div>";
			$_sHtml .= "			</div>";
		}
		$_sHtml .= "		</fieldset></div>";
		$_sHtml .= "	</div></div>";
		// ***************** Tableau 2 //
		$_sHtml .= "	<div class='col-md-4 col-sm-12'><div class='panel panel-info' id='div_oT_pluginInfos2'>";
		$_sHtml .= "		<div class='panel-heading'><h3 class='panel-title'><i class='fas fa-download'></i> ".__("(olindoteTools) Information sur la class olindoteTools",__FILE__)."</h3></div>";
		$_sHtml .= "		<div class='panel-body form-horizontal'>";
		$_sHtml .= "			<div class='form-group' style='color:#31708f;'>";
		$_sHtml .= "				<label class='col-sm-7 control-label' style='text-align:right;'>".__("Plus d'infos (documentation)",__FILE__)." </label>";
		$_sHtml .= "				<div><a href='https://abarrau.github.io/jeedom-plugins-doc/class-olindoteTools/' target='_blank'><i class='fas fa-question-circle' style='font-size:125%;'></i></a></div>";
		$_sHtml .= "			</div>";
		$_sHtml .= "			<div class='form-group'>"; 
		$_sHtml .= "				<label class='col-sm-5 control-label' style='text-align:right;'>User Id ";
			$_txtHelp = array(__('Identifiant unique de votre système (id indépendant des id Jeedom), uniquement utilisé par olindoteTools',__FILE__));
			$_sHtml .= self::help_writeHtmlIcone($_txtHelp);
		$_sHtml .= "				</label>";
		$_sHtml .= "				<div class='col-sm-7'>".self::ot_checkUserId()."</div>";
		$_sHtml .= "			</div>";
		$_sHtml .= "			<div class='form-group'>"; 
		$_sHtml .= "				<label class='col-sm-5 control-label' style='text-align:right;'>".__('Version installée',__FILE__)." ";
			$_txtHelp = array(__('Version de la class olindoteTools installée et utilisée par tous les plugins développé par abarrau/olindote.',__FILE__));
			$_sHtml .= self::help_writeHtmlIcone($_txtHelp);
		$_sHtml .= "				</label>";
		$_sHtml .= "				<div class='col-sm-7'>".self::getVersion()."</div>";
		$_sHtml .= "			</div>";
			$_oT_aLastVersion = olindoteGetListPlugin(true);
			$_oT_lastVersion = (isset(current($_oT_aLastVersion)['vC']))?current($_oT_aLastVersion)['vC']:-1;
		if (($_oT_lastVersion!=-1)&&($_oT_lastVersion>self::getVersion())) {
			$_sHtml .= "			<div class='form-group'>"; 
			$_sHtml .= "				<label class='col-sm-5 control-label' style='text-align:right;'>".__('Dernière Version',__FILE__)." ";
				$_txtHelp = array(__('Dernière version de la class olindoteTools disponible sur un autre plugin, mais non installer.',__FILE__),
									__('Vous pouvez lancer les dépendences pour mettre à jour la class.',__FILE__));
				$_sHtml .= self::help_writeHtmlIcone($_txtHelp);
			$_sHtml .= "				</label>";
			$_sHtml .= "				<div class='col-sm-7'>".$_oT_lastVersion." (".key($_oT_aLastVersion).")</div>";
			$_sHtml .= "			</div>";
		}
		$_sHtml .= "			<div class='form-group' style='font-size:90%;'>";
		$_sHtml .= "				<label class='col-sm-7 control-label' style='text-align:right;'>".__("Données d'install./update du plugin",__FILE__)." ";
			$_txtHelp = array(__('En cochant, vous autorisez le système à informer le développeur de l\'installation/update du plugin.',__FILE__), 
								__('Remarque: aucune donnée personnelle n\'est envoyée; uniquement le nom des plugins du développeur (abarrau/olindote) et leur version.',__FILE__));
			$_sHtml .= self::help_writeHtmlIcone($_txtHelp);
		$_sHtml .= "				</label>";
			$_sendInfoAllow = config::byKey('sendInfo::allow','olindoteTools','0');
		$_sHtml .= "				<div class='col-sm-1'><input type='checkbox' class='configKeyOT' data-l1key='sendInfo::allow' value='1' ".(($_sendInfoAllow)?"checked":"")."/></div>";
		$_sHtml .= "			</div>";
		$_sHtml .= "		</div>";
		$_sHtml .= "	</div></div>";
		// ***************** Tableau 3 //
		$_sHtml .= "	<div class='col-md-4 col-sm-12'><div class='panel panel-info' id='div_oT_debugHelp'>";
		$_sHtml .= "		<div class='panel-heading'><h3 class='panel-title'><i class='fas fa-pencil-alt'></i> ".__("(olindoteTools) Dysfonctionnement et assistance",__FILE__)."</h3></div>";
		$_sHtml .= "		<div class='panel-body form-horizontal'>";
		$_sHtml .= "			<div class='form-group'>";
		$_sHtml .= "				<label class='col-sm-4 control-label'>".__("Remontée de dysfonctionnement",__FILE__)."</label>";
		$_urlIssue = self::getInfoOnInfoJson($_plugin,'issue');
		$_sHtml .= "				<div class='col-sm-8' style='font-style:italic;'>".__("Toute remonté de cas de dysfonctionnement, clairement identifié (description claire et log à l'appui) peut être réalisée sur",__FILE__)." <a href='".$_urlIssue."' target='_blank' style=''>".__("le site dédié",__FILE__)."</a>.<br/>".__("Merci d'indiquer le nom du plugin dans le titre !",__FILE__)."</div>";
		$_sHtml .= "			</div>";
		$_sHtml .= "		</div>";
		$_sHtml .= "	</div></div>";
		$_sHtml .= "</div>\"; ";
		$_sHtml .= "$('#div_confPlugin .olindoteInfoZones').remove();";
		$_sHtml .= "$('#div_confPlugin').append(_panel_oT_PluginInfos);";
		$_sHtml .= "$('.olindoteInfoZones .configKeyOT[data-l1key=\'sendInfo::allow\']').off('change').on('change',function(){ $.ajax({ type:'POST',url:'plugins/".$_plugin."/core/ajax/".$_plugin.".ajax.php',dataType:'json',";
		$_sHtml .= "	data:{action:'oT_saveSendInfo', sendInfo_allow:$('.olindoteInfoZones .configKeyOT[data-l1key=\'sendInfo::allow\']').value() }, error:function(request,status,error){handleAjaxError(request,status,error);}, ";
		$_sHtml .= "	success:function(data){ if(data.state=='ok'){ $('#div_alert').showAlert({message:'(olindoteTools) Modification prise en compte ('+$('.olindoteInfoZones .configKeyOT[data-l1key=\'sendInfo::allow\']').value()+')',level:'success'}); }}}); });";
		$_sHtml .= "</script>";
		return $_sHtml;
	}
	
/* ----- ************************** HTML // Gestion des icones d'aide ou d'info ****************************** */
	/**
	 * Retourne le zone html correspondant à la partie "help"
	 * @param String $_txt texte à afficher
	 * @param String $_icone icone à afficher
	 */	
	public static function help_writeHtmlIcone($_txt, $_sup=true, $_icone='', $_txtStyle='') {
		if (is_array($_txt)) $_txt = implode('<br/>',$_txt);
		if ($_icone=='') $_icone = "<i class='fa fa-question-circle cursor' style='font-size:1em;color:grey;'></i>";
		if ($_txtStyle!='') $_txtStyle = "style='".$_txtStyle."'";
		$_sHtml = "<span class='helpTT'>".((!$_sup)?$_icone:"<sup>".$_icone."</sup>")."<span ".$_txtStyle.">".$_txt."</span></span>";
		return $_sHtml;
	}
	/**
	 * Retourne le zone html correspondant à la partie CSS du "help"
	 */	
	public static function help_writeHtmlCss() {
		$_sHtml = "<style>";
		$_sHtml .= ".helpTT span {	position:absolute; margin-top:20px; margin-left:-50px; max-width:500px; min-width:300px; z-index:1000; padding:10px; line-height:16px;
									color:#fff; background-color:#111;  border-radius:3px; font-size:12px; font-weight:500; text-align:left; opacity:0; 
									transform:scale(0) rotate(-12deg); transition:all .25s; }
					.helpTT:hover span, .helpTT:focus span{ transform:scale(1) rotate(0); opacity:0.8; } ";
		$_sHtml .= ".showExpertOnly { display:none; } ";
		return $_sHtml."</style>";
	}
	
/* ----- ************************** Calcul Gestion des listes ****************************** */
	/**
	 * Récupère les paramètres/valeurs d'une liste
	 * @param String $_plugin 
	 * @param String $_listName
	 * @param String $_activeOnly
	 */	
	public static function getListValues($_plugin='', $_listName='', $_activeOnly='1') {
		$_plugin = ($_plugin=='')?'olindoteTools':$_plugin;
		if ($_listName=='') {
			log::add($_plugin, 'debug', '[olindoteTools::getListValues()] _listName ('.$_listName.') is empty // STOP');
			return false;
		}
		if (!class_exists($_plugin.'Tools')) {
			log::add($_plugin, 'debug', '[olindoteTools::getListValues()] this class ('.$_plugin.'Tools) does not exist ! // STOP');
			return false;
		}
		$_return = array();
		eval('$_return = (isset('.$_plugin.'Tools'.'::$'.$_listName.'))?'.$_plugin.'Tools'.'::$'.$_listName.':array();');
		if (($_activeOnly==1)&&(count($_return)>0)) {
			for ($i=0; $i<count($_return); $i++) { if ((isset($_return[$i]['a']))&&($_return[$i]['a']!=1)) { array_splice($_return,$i,1); } }
		}
		log::add($_plugin,'debug','[olindoteTools::getListValues()] _listName='.$_listName.' - _return='.print_r($_return,true));
		return $_return;
	}
	/**
	 * Formate la liste "select" au format HTML
	 * @param String $_plugin 
	 * @param String $_listValues
	 */	
	public static function setHtmlSelectOptions($_plugin='', $_listValues='') {
		$_plugin = ($_plugin=='')?'olindoteTools':$_plugin;
		if ($_listValues=='') {
			log::add($_plugin, 'debug', '[olindoteTools::setHtmlSelectOptions()] _listValues is empty // STOP');
			return false;
		}
		$_return = '';
		if (!is_array($_listValues)) {
			$_l1 = explode('|',$_listValues);
			$_listValues = array();
			for($j=0;$j<count($_l1);$j++) {
				$_l2 = explode(';',$_l1[$j]);
				$_listValues[] = array('k'=>$_l2[0],'n'=>$_l2[1]); 
			}
		}
		if (count($_listValues)>0) { 
			for ($i=0; $i<count($_listValues); $i++) { $_return .= "<option value='".$_listValues[$i]['k']."'>".$_listValues[$i]['n']."</option>"; } 
		}
		return $_return; 
	}

/* ----- ************************** FILE // Action sur des fichiers ****************************** */
	/**
	 * Retourne une liste de fichier
	 */
	public static function file_getList($_plugin='olindoteTools',$_dir='.',$_excludeDir=true,$_excludeExt=array()) {
		if (!file_exists($_dir)) {
			log::add($_plugin, 'warning', '[olindoteTools::file_getList()] directory not exist ('.$_dir.')');
			return false;
		}
		$_aFiles = scandir($_dir);
		if (($k1=array_search('.',$_aFiles))!==false) unset($_aFiles[$k1]);
		if (($k2=array_search('..',$_aFiles))!==false) unset($_aFiles[$k2]);
		sort($_aFiles);
		if ($_excludeDir) { for ($i=0;$i<count($_aFiles);$i++) { if (is_dir($_aFiles[$i])) unset($_aFiles[$i]); } sort($_aFiles); }
		return $_aFiles;
	}

	/**
	 * Redimentionne une image
	 * stackoverflow.com/questions/14649645/resize-image-in.php
	 */
	public static function file_resizePicture($_plugin='olindoteTools',$_file,$_wAsk,$_hAsk) {
		$_return = false;
		if (!file_exists($_file)) {
			log::add($_plugin, 'warning', '[olindoteTools::file_resizePicture()] picture not exist ('.$_file.')');
			return $_return;
		}
		list($width, $height) = getimagesize($_file);
		if (($width>$_wAsk)||($height > $_hAsk)) {
			if ($width > $height) {
				$r = $height/$width;
				$newwidth = $_wAsk;
				$newheight = ceil($_hAsk*$r);
			} else {
				$r = $width/$height;
				$newwidth = ceil($_wAsk*$r);
				$newheight = $_hAsk;
			}
			log::add($_plugin, 'info', '[olindoteTools::file_resizePicture()] file w='.$width.',h='.$height.' - ask w='.$_wAsk.',h='.$_hAsk.' - new w='.$newwidth.',h='.$newheight);
		} else {
			$newwidth = $width;
			$newheight = $height;
		}
		$_extFile = strtolower(strrchr($_file, '.'));
		if ($_extFile == '.png') {
			$srcPic = imagecreatefrompng($_file);
		} else if (in_array($_extFile, array('.jpg','.jpeg'))) {
			$srcPic = imagecreatefromjpeg($_file);
		} else {
			log::add($_plugin, 'warning', '[olindoteTools::file_resizePicture()] picture with not a good extension (found='.$_extFile.' - allow=.jpg, .jpeg, .png)');
			return $_return;
		}		$dstPic = imagecreatetruecolor($newwidth,$newheight);
		imagecopyresampled($dstPic,$srcPic,0,0,0,0,$newwidth,$newheight,$width,$height);
		if ($_extFile == '.png') {
			$_return = imagepng($dstPic, $_file);
		} else if (in_array($_extFile, array('.jpg','.jpeg'))) {
			$_return = imagejpeg($dstPic, $_file);
		}
		return $_return;
	}
	
	/**
	 * Supprime les caractères spéciaux d'un string
	 */
	static function file_removeSpecialString($_t) {
		$utf8 = array(	'/[áàâãªä]/u'=>'a',	'/[ÁÀÂÃÄ]/u'=>'A',
						'/[ÍÌÎÏ]/u'=>'I', '/[íìîï]/u'=>'i',
						'/[éèêë]/u'=>'e', '/[ÉÈÊË]/u'=>'E',
						'/[óòôõºö]/u'=>'o', '/[ÓÒÔÕÖ]/u'=>'O',
						'/[úùûü]/u'=>'u', '/[ÚÙÛÜ]/u'=>'U',
						'/ç/'=>'c', '/Ç/'=>'C',
						'/ñ/'=>'n', '/Ñ/'=>'N',
						'/\[\]/u'=>'', '/[«»]/u'=>'_', '/ /'=>'_'
		); /* '//'=>'-',  */
		return preg_replace(array_keys($utf8), array_values($utf8), $_t);
	}	
	
	
/* ----- ************************** Calcul sur les dates ****************************** */
	/**
	 * Convertie la date dans un format
	 */
	public static function convertDate($_d, $_type) {
		if ($_d!='') {
			switch($_type) {
				case "DUREE2D": // "Seconde" > "day"
					$_d = intval($_d/(24*3600));
					break;
				case "DUREE2HMS": // "Seconde" > "hh:mm:ss"
					$_tz = date_default_timezone_get();
					date_default_timezone_set('UTC');
					$_d = date('d',$_d);
					date_default_timezone_set($_tz);
					break;
				case "TS2FC": // "TS" > "YYYY-MM-DDThh:mm:ss"
					$_d = date('Y-m-d\TH:i:s',$_d);
					break;
				case "TS2ICS": // "TS GM" > "YYYYMMDDThhmmssZ"
					$_d = date('Ymd\THis\Z',$_d);
					break;
				case "ONLYNUMBER": // A SUPPRIMER !!! //
				case "HUMANCLEARED": // "Y-m-d H:i:s" > "YmdHis"
					$_d = str_replace('-','',str_replace(':','',str_replace(' ','',$_d)));
					break;
				case "HUMAN2TS": // "Y-m-d H:i:s" > "TS"
					if ($_d==0) $_d = time();
					else {
						list($_ad, $_ah) = explode(' ',$_d);
						list($_dy, $_dm, $_dd) = explode('-',$_ad);
						list($_hh, $_hi, $_hs) = explode(':',$_ah);
						$_d = mktime($_hh,$_hi,$_hs,$_dm,$_dd,$_dy);
					}
					break;
				case "DAYJEEDOM": // "Ymd" > "Y-m-d"
					$_d = substr($_d,0,4).'-'.substr($_d,-4,2).'-'.substr($_d,-2);
					break;
				case "DAYHUMAN": // "Y-m-d 00:00:00" > "j F Y"
					list($_dy, $_dm, $_dd) = explode('-',str_replace(' 00:00:00','',$_d));
					$_d = date('j F Y', mktime('0','0','0',$_dm,$_dd,$_dy)); // date_fr()
					break;
				case "TODAY": 
					$_dEng = (config::byKey('language', 'core', 'fr_FR')=='fr_FR')?date('j F Y (\SW)'):date('j F Y (\WW)');
					$_d = date_fr($_dEng);
					break;
			}
		}
		return $_d;
	}
	/**
	 * Définie la date en fonction d'une date actuelle et d'un temps en plus. 
	 * @param String $_refreshP période à ajouter
	 * @param String $_dt1 date/time initiale
	 * @param boolean $_isSpecificTest cas particulier
	 */
	/* public static function getDateNext($_refreshP, $_dt1='', $_isSpecificTest=false) {
		$_next = 0;
		$_dt = ($_dt1=='')?time():$_dt1;
		if ($_isSpecificTest) {
			if ($_refreshP == '30') {
				if (($_m=date('i',$_dt)) < 30) $_next = $_dt+((30-$_m)*60);
					else $_next = $_dt+((60-$_m)*60);
			} elseif ($_refreshP == '1440') {
				$_next = mktime('00','00','00', $_dt) + (1440*60);
			}
		}
		if ($_next==0) {
			$_refreshP = $_refreshP/60;
			$_h=$_refreshP-(date('H',$_dt)%$_refreshP);	// (modulo=nb heure passée après la précédente synchro);
			$_next= $_dt + ($_h*60*60) - (date('i',$_dt)*60);
		}
		log::add($_plugin, 'debug', '[olindoteTools::getDateNext()] _next='.$_next);
		return $_next;
	}*/
	

/* ----- ************************** Gestion fonction "ajax" ****************************** */
	/**
	 * Traite les demandes Ajax des plugins liées à la class olindoteTools
	 */	
	public static function execAjaxRequest($_plugin, $_action) {
		log::add($_plugin,'debug','[AJAX] execAjaxRequest(): action='.$_action);
		$_return = array('type'=>'na', 'value'=>'');
		// méthode = sauvegarde le nom de la commande //
		if ($_action == 'oT_saveSendInfo') {
			config::save('sendInfo::allow', init('sendInfo_allow'), 'olindoteTools');
			$_return = array('type'=>'success', 'value'=>'');
		}
		// méthode = sauvegarde le format de vue sur une page équipement //
		if ($_action == 'oT_saveEqLogicViewType') {
			config::save('olindote::eqLogic::viewType', init('eqLogicViewType'), $_plugin);
			$_return = array('type'=>'success', 'value'=>'');
		}
		log::add($_plugin,'debug','[AJAX] execAjaxRequest(): _return='.print_r($_return,true));
		return $_return;
	}

	
/* ----- ************************** Fonctions génériques ****************************** */
	/**
	 * Définie si la version courante est compatible avec celle demandée
	 */	
	public static function jeedom_isCompatibleVersion($_v) {
		$_aV = explode('.',$_v); $_aJeedomV = explode('.',jeedom::version());
		if ($_aJeedomV[2] < 10) $_aJeedomV[2] = '0'.$_aJeedomV[2];
		if (implode('',$_aJeedomV) >= implode('',$_aV)) return true; else return false;
	}
	/**
	 * Récupère un paramètre/valeur dans le fichier Json du plugin (jeedom)
	 * @param String $_plugin nom du plugin
	 */	
	public static function getInfoOnInfoJson($_plugin='', $_what='') {
		if (($_plugin=='') || ($_what=='')) {
			$_plugin = ($_plugin=='')?'olindoteTools':$_plugin;
			log::add($_plugin, 'debug', '[olindoteTools::getInfoOnInfoJson()] _plugin or _what is empty // STOP');
			return false;
		}
		$_jsonFile = getRootPath().'/plugins/'.$_plugin.'/plugin_info/info.json';
		if (!file_exists($_jsonFile)) {
			log::add($_plugin, 'debug', '[olindoteTools::getInfoOnInfoJson()] _jsonFile="'.$_jsonFile.'", not exist ! // STOP');
			return false;
		}
		return self::getJsonValue(file_get_contents($_jsonFile), $_what, $_plugin);
	}
	/**
	 * Retourne le contenu d'une valeur Json
	 * @param String $_data donnée au format json
	 * @param String $_what valeur recherchée
	 * @param String $_plugin nom du plugin
	 */
	public static function getJsonValue($_data, $_what, $_plugin='olindoteTools') {
		log::add($_plugin, 'debug', '[olindoteTools::getJsonValue()] _data='.$_data);
		$_jData = json_decode($_data,true);
		if ((is_array($_jData)) && (array_key_exists($_what, $_jData))) {
			log::add($_plugin, 'debug', '[olindoteTools::getJsonValue()] return ('.$_what.')= '.print_r($_jData,true));
			return $_jData[$_what];
		} else {
			log::add($_plugin, 'debug', '[olindoteTools::getJsonValue()] key ('.$_what.') not found  in '.print_r($_jData,true));
			return false;
		}
	}
	/**
	 * Exécute un fichier SQL
	 * @param String $_plugin nom du plugin
	 * @param String $_name nom du fichier sql
	 * @param String $_whatLog 
	 */
	public static function execSQL($_plugin, $_name, $_whatLog='UPDATE') {
		if (($_whatLog=='INSTALL')||($_whatLog=='UPDATE')) { $_pluginLog = $_plugin.'_update'; } else { $_pluginLog = $_plugin; }
		if (($_plugin=='') || ($_name=='')) {
			$_plugin = ($_plugin=='')?'olindoteTools':$_plugin;
			log::add($_pluginLog, 'debug', '[olindoteTools::execSQL()] _plugin or _name is empty // STOP');
			return false;
		}
		$_f = getRootPath().'/plugins/'.$_plugin.'/plugin_info/'.$_name.'.sql';
		log::add($_pluginLog, 'info', '['.$_whatLog.'|execSQL()] verif sql file = "'.$_f.'"');
		if (file_exists($_f)) {
			log::add($_pluginLog, 'info', '['.$_whatLog.'|execSQL()] sql file exist !');
			try {
				exec('sudo chmod 777 '.$_f);
				$_sql = file_get_contents($_f);
				DB::Prepare($_sql, array(), DB::FETCH_TYPE_ROW);
			} catch(Exception $_e) {
				log::add($_pluginLog, 'info', '['.$_whatLog.'|execSQL()] ERROR/Exception : '.$_e);
			}
			log::add($_pluginLog, 'info', '['.$_whatLog.'|execSQL()] executed sql file: '.$_f);
		}
	}
	/**
	 * Retourne le nom du paramètre en cache pour le contenu du Widget
	 * @param string $_version version de l'affichage
	 */
	public static function getWidgetCacheName($_version, $_id) {
		$user_id = '';
		if (isset($_SESSION) && isset($_SESSION['user']) && is_object($_SESSION['user'])) {
			$user_id = $_SESSION['user']->getId();
		}
		return 'widgetHtml'.$_id.$_version.$user_id;
	}
	/**
	 * Création d'un répertoire
	 */
	public static function checkAndCreateDirectory($_plugin='', $_path='') {
		if ($_plugin=='') $_plugin = 'olindoteTools';
		if ($_path=='') {
			log::add($_plugin, 'debug', '[olindoteTools::checkAndCreateDirectory()] _path is empty // STOP');
			return false;
		}
		$_path = cleanPath($_path);
		if (file_exists($_path)) {
			return true;
		}
		log::add($_plugin, 'debug', '[olindoteTools::checkAndCreateDirectory()] directory not exist ('.$_path.'), try to create it !');
		//shell_exec('sudo mkdir -p '.$_path);
		//shell_exec('sudo chmod 755 -R '.$_path);
		//shell_exec('sudo chown -R www-data:www-data '.$_path);
		com_shell::execute(system::getCmdSudo().'mkdir -p '.$_path);
		com_shell::execute(system::getCmdSudo().'chmod 755 -R '.$_path);
		com_shell::execute(system::getCmdSudo().'chown '.system::get('www-uid').':'.system::get('www-gid').' '.$_path);
		if (file_exists($_path)) {
			return true;
		}
		log::add($_plugin, 'warning', '[olindoteTools::checkAndCreateDirectory()] directory can not be create ('.$_path.') !!??');
		return false;
	}
	/**
	 * Retourne le nom de la class de l'objet
	 */
	public static function getObjName($_obj=null) {
		if (!is_object($_obj)) return '';
		return (substr(strtolower(get_class($_obj)),-3)=='cmd')?substr(get_class($_obj),0,-3):get_class($_obj);
	}

}

?>