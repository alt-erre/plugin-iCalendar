<?php

/* This file is part of Olindote.
 * ------------------------------------------------------
 * @author abarrau
 * ------------------------------------------------------
 * Ce fichier permet de gérer le fichier de Class "olindoteTools".
 */
/* ----- ************************** INIT ****************************** */
// ---- définition des constantes ---- //
if (!defined('OLINDOTE_FILE_INC'))		define('OLINDOTE_FILE_INC', '/3rdparty/olindote/Tools/olindoteTools.inc.php');
if (!defined('OLINDOTE_DIR_CLASS_OT'))	define('OLINDOTE_DIR_CLASS_OT', dirname(__FILE__) .'/../../../../../data/custom/olindote/php'); // data/custom/php/olindote

/* ----- ************************** Fonction de gestion de la class ****************************** */
/**
 * Chargement automatique des class olindoteTools
 */
if (!function_exists('olindoteToolsAutoload')) {
	function olindoteToolsAutoload($classname) {
		try {
			if ((substr($classname,0,13) != 'olindoteTools')||(strpos(OLINDOTE_DIR_CLASS_OT,'/data/custom/olindote/php')===false)) { // data/custom/php/olindote
				//log::add('olindoteTools', 'debug', '[olindoteToolsAutoload()] ERROR: classname='.$classname.' | OLINDOTE_DIR_CLASS_OT='.OLINDOTE_DIR_CLASS_OT);
				return false; 
			} else {
				if (file_exists(OLINDOTE_DIR_CLASS_OT.'/olindoteTools.class.php')) {
					include_once OLINDOTE_DIR_CLASS_OT.'/olindoteTools.class.php';
					//log::add('olindoteTools', 'debug', '[olindoteToolsAutoload()] OK include file: '.OLINDOTE_DIR_CLASS_OT.'/olindoteTools.class.php');	
				} else {
					log::add('olindoteTools', 'warning', '[olindoteToolsAutoload()] ERROR: file does not exist ('.OLINDOTE_DIR_CLASS_OT.'/olindoteTools.class.php). TRY TO INSTALL AGAIN THE CLASS.');	
					log::add('olindoteTools', 'error', '[ERROR]'.__("[installation olindoteTools class]: la class olindoteTools n'est pas installée. Installez la, via les dépendances du plugin.", __FILE__));
				}
			}
		} catch (Exception $e) {
			log::add('olindoteTools', 'debug', '[olindoteToolsAutoload('.$classname.')] Exception: '.$e);	
		}
	}
	// register de la fonction d'autoload des class //
	spl_autoload_register('olindoteToolsAutoload', true, true);
}
/**
 * Récupère les infos d'un plugin utilisant "olindoteTools"
 */
if (!function_exists('olindoteGetPluginsUsedTools')) {
	function olindoteGetPluginsUsedTools($plugin='') {
		if ($plugin!='') {
			$_aPluginsList = olindoteGetListPlugin();
			foreach($_aPluginsList as $k => $v) { if ($k==$plugin) { 
				log::add('olindoteTools', 'debug', '[olindoteGetPluginsUsedTools()] Find = '.print_r(array($k=>$v), true));
				return array($k=>$v); 
			}}
		} else {
			log::add('olindoteTools', 'warning', '[olindoteGetPluginsUsedTools()] ERROR: arguments are empty. ('.dirname(__FILE__).')');
		}
		return false;
	}
}
/**
 * Ajoute un plugin à la liste des versions pour "olindoteTools"
 */
if (!function_exists('olindoteAddPluginsUsedTools')) {
	function olindoteAddPluginsUsedTools($plugin='', $versionClass='0') {
		if ($plugin=='_olindoteTools') {
			$_classInstalled = array('vC'=>$versionClass,'d'=>date('Y-m-d H:i:s'));
			config::save('classInstalled',$_classInstalled,'olindoteTools');
			log::add('olindoteTools', 'debug', '[olindoteAddPluginsUsedTools()] classInstalled='.print_r($_classInstalled, true));			
		} else if ($plugin!='') {
			$versionTools = olindoteToolsGetValueInJsonInfo('_oT_toolsVersion','','-1');
			if (($versionTools!='')&&($versionTools!='-1')) {
				$_aPluginsList = olindoteGetListPlugin();
				if ((isset($_aPluginsList[$plugin])) && ($_aPluginsList[$plugin]['vC']==$versionTools) && ($plugin!='_olindoteTools')) {
					log::add('olindoteTools', 'debug', '[olindoteAddPluginsUsedTools()] '.$plugin.'|'.$versionTools.' already exist, no action.');
				} else {
					$_aPluginsList[$plugin] = array('vC'=>$versionTools, 'd'=>date('Y-m-d H:i:s'),'a'=>config::byKey('active',$plugin));
					config::save('pluginsInstalled', $_aPluginsList, 'olindoteTools');
				}
				log::add('olindoteTools', 'debug', '[olindoteAddPluginsUsedTools()] '.print_r($_aPluginsList, true));
			} else {
				log::add('olindoteTools', 'warning', '[olindoteAddPluginsUsedTools()] ERROR: versionTools is not correct. ('.$plugin.'|'.$versionTools.')');
			}
		} else {
			log::add('olindoteTools', 'warning', '[olindoteAddPluginsUsedTools()] ERROR: arguments are empty. ('.dirname(__FILE__).')');
		}
	}
}
/**
 * Supprime un plugin à la liste des versions pour "olindoteTools"
 */
if (!function_exists('olindoteRemovePluginsUsedTools')) {
	function olindoteRemovePluginsUsedTools($plugin='') {
		if ($plugin!='') {
			if ($plugin=='_olindoteTools') {
				$_isChange=true;
				config::save('classInstalled', array('vC'=>'?','d'=>'?'), 'olindoteTools');
			} else {
				$_aPluginsList = olindoteGetListPlugin();
				$_isChange = false;
				foreach($_aPluginsList as $k => $v) { 
					if ($k == $plugin) { unset($_aPluginsList[$k]); log::add('olindoteTools', 'debug', '[olindoteRemovePluginsUsedTools()] remove plugin = '.$plugin.' ('.$v['vC'].')'); $_isChange=true; }
				}
				if ($_isChange) {
					log::add('olindoteTools', 'debug', '[olindoteRemovePluginsUsedTools()] '.print_r($_aPluginsList, true));
					config::save('pluginsInstalled', $_aPluginsList, 'olindoteTools');
				} else {
					log::add('olindoteTools', 'debug', '[olindoteRemovePluginsUsedTools()] plugin not found, no action ('.$plugin.')');
				}
			}
		} else {
			log::add('olindoteTools', 'warning', '[olindoteRemovePluginsUsedTools()] ERROR: arguments are empty. ('.dirname(__FILE__).')');
		}
	}
}
/**
 * Install la class Tools 
 */	
if (!function_exists('olindoteInstallToolsClass')) {
	function olindoteInstallToolsClass() {
		$_aLastVersion = olindoteGetListPlugin(true);
		if (count($_aLastVersion) != 1) {
			log::add('olindoteTools', 'debug', '[olindoteInstallToolsClass()] plugins used olindoteTools are unknow. Installed is stopped !');
			return false;
		}
		$_sourceName = cleanPath(dirname(__FILE__) .'/../../../../'.key($_aLastVersion).'/3rdparty/olindote/Tools/olindoteTools.class.php');
		$_cibleName = OLINDOTE_DIR_CLASS_OT.'/olindoteTools.class.php';
		log::add('olindoteTools', 'debug', '[olindoteInstallToolsClass()] plugin path source = "'.$_sourceName .'"');//
		if (!file_exists($_sourceName)) { 
			log::add('olindoteTools', 'warning', '[olindoteInstallToolsClass()] ERROR: class file not exist ('.$_sourceName.')');	
			return false;
		}
		if (!file_exists(OLINDOTE_DIR_CLASS_OT)) { 
			if (mkdir(OLINDOTE_DIR_CLASS_OT, 0777, true) !== true) {
				log::add('olindoteTools', 'warning', '[olindoteInstallToolsClass()] ERROR: can not create directory class ('.OLINDOTE_DIR_CLASS_OT.')');	
				return false;
			}
		}
		if (file_exists($_cibleName)) { 
			log::add('olindoteTools', 'debug', '[olindoteInstallToolsClass()] try to delete exist file.');	
			if (!unlink($_cibleName)) {
				log::add('olindoteTools', 'warning', '[olindoteInstallToolsClass()] ERROR: can not delete exist file.');	
			}
		}
		if (!copy($_sourceName, $_cibleName)) {
			log::add('olindoteTools', 'warning', '[olindoteInstallToolsClass()] ERROR: can not copy class file ('.$_sourceName.')');	
			return false;
		} else {
			log::add('olindoteTools', 'debug', '[olindoteInstallToolsClass()] Copy class file is OK ('.$_sourceName.')');
		}
		if (file_exists($_cibleName)) { 
			// ----- AJOUT de ces lignes, car par moment le fichier est en "root" (sans comprendre pourquoi ???) //
			com_shell::execute(system::getCmdSudo().'chown '.system::get('www-uid').':'.system::get('www-gid').' '.$_cibleName.';');
			// ----- END //
			if (isset(current($_aLastVersion)['vC'])) { olindoteAddPluginsUsedTools('_olindoteTools',current($_aLastVersion)['vC']); log::add('olindoteTools', 'debug', '[olindoteInstallToolsClass()] Update version value="'.current($_aLastVersion)['vC'].'"'); }
			olindoteToolsAutoload('olindoteTools');
			log::add('olindoteTools', 'debug', '[olindoteInstallToolsClass()] Install is OK.');
			return true;
		}
		return false;
	}
}
/**
 * Check l'existance de la class Tools, sinon l'install & ajoute le plugin courant à la liste des plugin Tools 
 */
if (!function_exists('olindoteCheckClassTools')) {
	function olindoteCheckClassTools($plugin='',$versionTools=0,$force=false) {
		if (($plugin!='') && ($versionTools!=0)) {
			$_aPluginsInstalled = olindoteGetListPlugin();
			if (!isset($_aPluginsInstalled[$plugin])) {
				log::add('olindoteTools', 'debug', '[olindoteCheckClassTools()] plugin not listed: ADDING ...');
				olindoteAddPluginsUsedTools($plugin);
			}
		}
		olindoteCheckListPlugin();
		if (($force)&&(file_exists(OLINDOTE_DIR_CLASS_OT.'/olindoteTools.class.php'))) unlink(OLINDOTE_DIR_CLASS_OT.'/olindoteTools.class.php');
		if (!file_exists(OLINDOTE_DIR_CLASS_OT.'/olindoteTools.class.php')) {
			log::add('olindoteTools', 'debug', '[olindoteCheckClassTools()] class file NOT exist: INSTALLING ...');
			olindoteInstallToolsClass();
		} else {
			log::add('olindoteTools', 'debug', '[olindoteCheckClassTools()] class file EXIST: check version ...');
		}
		try {
			$_currentVT = ((class_exists('olindoteTools'))&&(method_exists('olindoteTools','getVersion')))?olindoteTools::getVersion():'999'; // 999=on considère que la version est à jour //
			$_lastT = olindoteGetListPlugin(true);
			$_lastVT = (isset(current($_lastT)['vC']))?current($_lastT)['vC']:'';
			log::add('olindoteTools', 'debug', '[olindoteCheckClassTools()] Version: current='.$_currentVT.' | last='.$_lastVT);
			if ($_currentVT >= $_lastVT) { 
				return true; 
			} else {
				log::add('olindoteTools', 'debug', '[olindoteCheckClassTools()] Current version is less than know last class ('.key($_lastT).'): TRY UPDATING ...');
				olindoteInstallToolsClass();
				if (class_exists('olindoteTools')) { 
					return true; 
				} else {
					log::add('olindoteTools', 'debug', '[olindoteCheckClassTools()] Can not install olindoteTools Class, install stopped !');
				}
			}
		} catch (Exception $e) {
			log::add('olindoteTools', 'warning', '[olindoteCheckClassTools()] ERROR/EXCEPTION: '.$e);
		}
		return false;
	}
}
/**
 * Vérifie si le droit "d'envoyer l'info" est coché. 
 */
if (!function_exists('olindoteCheckAllowSendInfo')) {
	function olindoteCheckAllowSendInfo() {
		$_sendInfoAllow = config::byKey('sendInfo::allow','olindoteTools','-1');
		if (is_null($_sendInfoAllow)||($_sendInfoAllow=='-1')) {
			config::save('sendInfo::allow', '1', 'olindoteTools');
		}
	}
}
/**
 * Récupération d'une valeur dans un contenu Json
 */
if (!function_exists('olindoteToolsGetValueInJsonInfo')) {
	function olindoteToolsGetValueInJsonInfo($_what,$_plugin='',$_default=false) {
		if ($_plugin!='') { $_jsonFile = dirname(__FILE__) .'/../../../../'.$_plugin.'/plugin_info/info.json'; }
			else { $_jsonFile = dirname(__FILE__) . '/../../../plugin_info/info.json'; }
		if (!file_exists($_jsonFile)) {
			return false;
		}
		$_jData = json_decode(file_get_contents($_jsonFile),true);
		if ((is_array($_jData)) && (array_key_exists($_what, $_jData))) {
			return $_jData[$_what];
		} else {
			return $_default;
		}
	}
}
/**
 * Liste les plugins réalisés par Abarrau/Olindote
 */
if (!function_exists('olindoteGetListPlugin')) {
	function olindoteGetListPlugin($lastOnly=false) {
		$_aPluginsInstalled = config::byKey('pluginsInstalled','olindoteTools',array());
		if (!$lastOnly)	return $_aPluginsInstalled;
		$_aLast = array('tmp'=> array('vC'=>0));
		foreach($_aPluginsInstalled as $k => $v) { if ($v['vC']>current($_aLast)['vC']) { $_aLast = array($k=>$v); }}
		log::add('olindoteTools', 'debug', '[olindoteGetListPlugin()] last version= '.print_r($_aLast, true));
		return $_aLast;
	}
}
/**
 * Vérifie les plugins réalisés par Abarrau/Olindote
 */
if (!function_exists('olindoteCheckListPlugin')) {
	function olindoteCheckListPlugin() {
		$_aPluginsExists = array();
		$_aPluginsInstalled = config::byKey('pluginsInstalled','olindoteTools',array());
		$_aDir = scandir(cleanPath(dirname(__FILE__).'/../../../../'));
		if (!file_exists(OLINDOTE_DIR_CLASS_OT.'/olindoteTools.class.php')) {
			olindoteRemovePluginsUsedTools('_olindoteTools');
		}
		if (is_array($_aDir) && (count($_aDir)>0)) {
			foreach($_aDir as $_fN) {
				if (file_exists(cleanPath(dirname(__FILE__) .'/../../../../'.$_fN.'/3rdparty/olindote/Tools/olindoteTools.class.php'))) {
					$_aPluginsInstalled[$_fN]['vP'] = olindoteToolsGetValueInJsonInfo('_oT_pluginVersion',$_fN);
					$_aPluginsInstalled[$_fN]['vC'] = olindoteToolsGetValueInJsonInfo('_oT_toolsVersion',$_fN);
					$_aPluginsInstalled[$_fN]['a'] = config::byKey('active',$_fN);
                                        $_aPluginsInstalled[$_fN]['vT'] = update::byLogicalId($_fN)->getConfiguration('version');
					array_push($_aPluginsExists, $_fN);
				}
			}
		} else {
			log::add('olindoteTools', 'debug', '[olindoteCheckListPlugin()] NO PLUGIN Found !?.');
		}
		// supprime les plugins qui n'existent plus //
		foreach($_aPluginsInstalled as $k=>$v) {
			if (!in_array($k, $_aPluginsExists)) unset($_aPluginsInstalled[$k]);
		}
		log::add('olindoteTools', 'debug', '[olindoteCheckListPlugin()] plugin Installed = '.print_r($_aPluginsInstalled, true));
		config::save('pluginsInstalled', $_aPluginsInstalled, 'olindoteTools');
		return $_aPluginsInstalled;
	}
}


// ------------------------------------------------------------------------------ //
// ------------ Gestion de l'install/update/remove d'un plugin ------------------ //
/**
 * Les actions minimum d'installation/mise à jour d'un plugin
 */
if (!function_exists('olindotePluginInstallUpdate')) {
	function olindotePluginInstallUpdate($_plugin='',$_type='UPDATE',$_sqlFile=null, $_aVersUpdateFile=array(), $_cVersion='n/a') {
		if ($_plugin=='') return false;
		if ($_type=='UPDATE') {
			$_whatLog = "UPDATE";
			$_aVersion = config::byKey('olindote::version',$_plugin,array());
			$_currentVersion = (is_array($_aVersion)&&(isset($_aVersion['curVersion'])))?$_aVersion['curVersion']:0;
			log::add($_plugin.'_update', 'info', '['.$_type.'] current version='.$_currentVersion.' | Nb EqL.=' . count(eqLogic::byType($_plugin)));
			log::add($_plugin.'_update', 'info', '['.$_type.'] _aVersion='.print_r($_aVersion,true));
			$_currentVersion = intval(str_replace('.','',$_currentVersion));
		}
		
		// Check la présence de la CLASS olindoteTools et l'install/met à jour //
		if (olindoteCheckClassTools($_plugin,olindoteToolsGetValueInJsonInfo('_oT_toolsVersion'),false)===true) {
			if ($_type=='UPDATE') {
				// met à jour les actions dédiées à la version //
				if (count($_aVersUpdateFile)>0) {
					foreach ($_aVersUpdateFile as $v) {
						if ($_currentVersion <= intval(str_replace('.','',$v))) {
							$_fileUpdate = dirname(__FILE__).'/../../../plugin_info/_update_'.str_replace('.','-',$v).'.php';
							//log::add($_plugin.'_update', 'info', '['.$_type.'] file='.$_fileUpdate.' - exist='.file_exists($_fileUpdate));
							if (file_exists($_fileUpdate)) require_once $_fileUpdate;
						}					
					}
				}
			}
			// met à jour la version //
			if(method_exists('olindoteTools','version_setValue')) olindoteTools::version_setValue($_plugin);
			// execute le script SQL //
			if (!is_null($_sqlFile)) olindoteTools::execSQL($_plugin,$_sqlFile,$_type);
			// archive le plugin //
			if(method_exists('olindoteTools','backup_createBackup')) olindoteTools::backup_createBackup($_plugin); // TODO : pourquoi à l'installation ?? //
			// informe olindote des données plugins //
			olindoteCheckAllowSendInfo();
			if(method_exists('olindoteTools','ot_sendInfoToOlindote')) olindoteTools::ot_sendInfoToOlindote($_plugin,$_type,false,$_cVersion);
			// Purge le cache et regénère les widget //
			foreach (eqLogic::byType($_plugin) as $eqLogic) { $eqLogic->refreshWidget(); }
			return true;
		} else {
			log::add($_plugin.'_update', 'error', 'ERROR during olindoteTools Class installation (check olindoteTools Log file).');
			return false;
		}		
		return false;
	}
}
/**
 * Les actions minimum d'installation/mise à jour d'un plugin
 */
if (!function_exists('olindotePluginRemove')) {
	function olindotePluginRemove($_plugin='', $_cVersion='n/a') {
		if ($_plugin=='') return false;		
		// suppression de la version de la class Tools //
		olindoteRemovePluginsUsedTools($_plugin);
		// informe olindote des données plugins //
		olindoteCheckAllowSendInfo();
		if(method_exists('olindoteTools','ot_sendInfoToOlindote')) olindoteTools::ot_sendInfoToOlindote($_plugin,'REMOVE',false,$_cVersion);
		return true;		
	}
}

// ------------------------------------------------------------------------------ //
// ------------ Gestion dépendances --------------------------------------------- //
/**
 * Vérifie l'installation des dépendences
 */
if (!function_exists('olindoteDependancyInfo')) {
	function olindoteDependancyInfo($_plugin='',$_aDependancyTest=array()) {
		if ($_plugin=='') return array('state'=>'nok');
		$return = array();
		$return['progress_file'] = jeedom::getTmpFolder($_plugin).'/dependance';
		if (class_exists('olindoteTools')) {
			$return['state'] = 'ok';
			if ((is_array($_aDependancyTest))&&(count($_aDependancyTest)>0)) {
				foreach($_aDependancyTest as $_dep) {
					if (olindoteDependancyIsInstalled($_dep)==false) {
						$return['state'] = 'nok';
						break;
					}
				}
			}
		} else {
			$return['state'] = 'nok';
		}
		return $return;		
	}
}
/**
 * Fonction permettant de savoir si un package est disponible
 */
if (!function_exists('olindoteDependancyIsInstalled')) {
	function olindoteDependancyIsInstalled($_what='') {
		if ($_what=='') return false;
		return (exec('which '.$_what.' | wc -l')==1)?true:false;
	}
}	
/**
 * Fonction permettant d'installer les dépendances
 *	$_arg = permet de passer d'autres argument au programme d'install
 */
if (!function_exists('olindoteDependancyInstall')) {
	function olindoteDependancyInstall($_plugin='', $_arg='') {
		if ($_plugin=='') return array();
		log::remove($_plugin.'_update');
		$_jeedomPathFile = getRootPath(); //cleanPath(dirname(__FILE__) . '/../../../../../');
		$_arg = ($_arg!='')?' '.$_arg:$_arg;
		return array('script' => $_jeedomPathFile.'/plugins/'.$_plugin.'/resources/install_#stype#.sh '.jeedom::getTmpFolder($_plugin).'/dependance '.$_jeedomPathFile.$_arg, 'log' => log::getPathToLog($_plugin.'_update'));
	}
}


// -------------------------------------------------------------------------------------- //
// ------------ APPEL depuis une page Web ou Script ------------------------------------- //
/**
 * Installation des dépendances
 *	UTILISATION = php ${PATH_JEEDOM_FILE}/plugins/%PLUGIN%/3rdparty/olindote/Tools/olindoteTools.inc.php dependancy=install plugin=%PLUGIN%
 */
global $JEEDOM_INTERNAL_CONFIG;
if (!is_array($JEEDOM_INTERNAL_CONFIG)) {
	// récupération des argements en ligne de commande + transforme en _GET //
	if (isset($argv)){ foreach(array_slice($argv,1) as $arg) { $a=explode('=',$arg); if (isset($a[1])){$_GET[$a[0]]=$a[1];} }}
	// lance l'installation si _GET dependancy trouvé //
	if ((isset($_GET['dependancy']))&&($_GET['dependancy']=='install')) {
		require_once dirname(__FILE__) . '/../../../../../core/php/core.inc.php';
		log::add('olindoteTools', 'debug', '[INSTALL()] ---------- Starting ...');
		// ----- AJOUT de ces lignes, car par moment le fichier est en "root" (sans comprendre pourquoi ???) //
		com_shell::execute(system::getCmdSudo().'chown '.system::get('www-uid').':'.system::get('www-gid').' '.getRootPath().'/log/olindoteTools;');
		// ----- END //
		if (isset($argv)) echo "INSTALL:: STARTING....\n";
		$_plugin = (isset($_GET['plugin']))?$_GET['plugin']:'';
		if ($_plugin!='') { $_version = olindoteToolsGetValueInJsonInfo('_oT_toolsVersion'); }
		$_version = ((isset($_version))&&($_version>0))?$_version:0;
		log::add('olindoteTools', 'debug', '[INSTALL()] _plugin='.$_plugin.' / _version='.$_version);
		if (olindoteCheckClassTools($_plugin,$_version,true)==true) {
			log::add('olindoteTools', 'debug', '[INSTALL()] ---------- END/olindoteTools class is ok.');
			if (isset($argv)) echo "INSTALL:: END/olindoteTools class is ok.\n";
		} else {
			echo "ERROR: ".__("installation en erreur, check log",__FILE__)."\n";
			log::add('olindoteTools', 'debug', '[INSTALL()] ---------- END/ERROR during intall olindoteTools class.');
			if (isset($argv)) echo "INSTALL:: END/ERROR during intall olindoteTools class, check olindoteTools log.\n";
		}
	} else { 
		echo "ERROR: invalid argument";
		if (isset($argv)) echo "INSTALL:: ERROR/Dependancy Argument is empty.\n"; 
	}
} //else { log::add('olindoteTools', 'debug', '--- JEEDOM_INTERNAL_CONFIG know, file is included !!'); }

?>