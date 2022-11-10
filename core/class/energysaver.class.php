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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class energysaver extends eqLogic {  
    
  public static function getStopStartParameters($_separator = '') {
    // Renvoi d'un tableau contenant l'heure de stop et de start suivant le séparateur et donc suivant le besoin
    // array['stop'] = 20:30 ou array['stop'] = 2030
    // array['start'] = 07:00 ou array['start'] = 07000
    $cfg_h1_stop = config::byKey('cfg_h1_stop', __CLASS__); // Récupération du paramètre global cgf_h1
    $cfg_m1_stop = config::byKey('cfg_m1_stop', __CLASS__); // Récupération du paramètre global cgf_m1    

    $cfg_h1_start = config::byKey('cfg_h1_start', __CLASS__); // Récupération du paramètre global cgf_h1
    $cfg_m1_start = config::byKey('cfg_m1_start', __CLASS__); // Récupération du paramètre global cgf_m1
    
    $array['stop'] = $cfg_h1_stop.$_separator.$cfg_m1_stop;
    $array['start'] = $cfg_h1_start.$_separator.$cfg_m1_start;
        
    return $array;
  }
  
  public static function getNumberOfDevices() {
   	foreach (eqLogic::byType('energysaver') as $eqLogic) {
      if ($eqLogic->getLogicalId() != 'main') {
        $count_total++;
      	if ($eqLogic->getIsEnable()) {
      		$count_enable++;          
        }
      }
    }
    $array['total'] = $count_total;
    $array['enable'] = $count_enable;
    
    return $array; 
  }
      
  public static function drawCircle($_size, $_color){
    $r = '<div style="margin: 0;">';
    $r .= '<div style="width: '.$_size.'; height: '.$_size.'; border-radius: '.$_size.'; background: '.$_color.';"></div>';
    $r .= '</div>';
    return $r;
  }
  
  public static function convertToHoursMins($time, $format = '%02d:%02d') {
    if ($time < 1) {
        return 'Aucune activité';
    }
    $hours = floor($time / 60);
    $minutes = ($time % 60);
    return sprintf($format, $hours, $minutes);
  }
  
  public static function isEligible($eqLogic) {
    foreach ($eqLogic->getCmd('action') as $cmd) {
      $cmd_name = $cmd->getName();
      if (strtolower($cmd_name) == "on" || strtolower($cmd_name) == "off") {
        $count++;
      }
    }

    if ($count == 2) { // Commande On ET Off
      return 1;
    } else {
      return 0;
    }
  }
  
  public static function getPowerCmdValue($eqLogic) {
    foreach ($eqLogic->getCmd('info') as $cmd) { // Parcours des commandes info
      //$cmd_name = $cmd->getName();
      $unit = $cmd->getUnite();
      if (trim(strtolower($unit)) == "w") {
        return $cmd->execCmd().' '.$unit;
      }
    }
    return null;
  }
  
  public static function getCmd_Power($eqLogic) {
    foreach ($eqLogic->getCmd('info') as $cmd) { // Parcours des commandes info
      $unit = $cmd->getUnite();
      if (trim(strtolower($unit)) == "w") {
        //log::add(__CLASS__, 'debug', 'Commande power trouvée : '.$cmd->getName());
        return $cmd;
      }
    }
    return null;
  } 
  
  public static function getCmdConsumptionValue($eqLogic) {
    foreach ($eqLogic->getCmd('info') as $cmd) { // Parcours des commandes info
      $unit = $cmd->getUnite();
      if (trim(strtolower($unit)) == "kwh" || trim(strtolower($unit)) == "wh") {
        return $cmd->execCmd().' '.$unit;
      }
    }
    return null;
  } 
  
  
  public static function getStateCmd($eqLogic) {
    foreach ($eqLogic->getCmd('info') as $cmd) { // Parcours des commandes info
      $name = $cmd->getName();
      //log::add(__CLASS__, 'debug', 'Nom de la commande binaire : '.$name);
      if (trim(strtolower($name)) == "etat" || trim(strtolower($name)) == "state") {
		return $cmd;
      }
    }
  } 
  
  public static function getStateDuration($_eqLogic, $_value) {
    $_cmd = self::getStateCmd($_eqLogic);    
    if (is_object($_cmd)) {
      	if ($_cmd->getIsHistorized()) {
          	$_cmdid = $_cmd->getId();
            $_cmdname = '#'.$_cmd->getHumanName().'#';
            log::add(__CLASS__, 'debug', 'Nom de la commande binaire trouvée : '.$_cmdname);

            $cfg_h1_stop = config::byKey('cfg_h1_stop', __CLASS__); // Récupération du paramètre global cgf_h1
            $cfg_m1_stop = config::byKey('cfg_m1_stop', __CLASS__); // Récupération du paramètre global cgf_m1    

            $cfg_h1_start = config::byKey('cfg_h1_start', __CLASS__); // Récupération du paramètre global cgf_h1
            $cfg_m1_start = config::byKey('cfg_m1_start', __CLASS__); // Récupération du paramètre global cgf_m1 

            $stop = $cfg_h1_stop.':'.$cfg_m1_stop; // Heure à laquelle l'équipement aurait pu être stoppé
            $start = $cfg_h1_start.':'.$cfg_m1_start; // Heure à laquelle l'équipement aurait pu être redémarré
            
            if ($cfg_h1_stop.$cfg_m1_stop > $cfg_h1_start.$cfg_m1_start) { // Si l'heure du stop est supérieur à celui du start (période nuit comme un stop entre 23h00 et 07h00)
              //log::add(__CLASS__, 'debug', $cfg_h1_stop.$cfg_m1_stop . ' > '. $cfg_h1_start.$cfg_m1_start);
              for ($i = 0; $i < 30; $i++) {
                $ii = $i+1;
                $duration = $duration + scenarioExpression::durationbetween($_cmdname, $_value, $ii.' day ago '.$stop, $i.' day ago '.$start);
              }
            } else { // Si l'heure du stop est inférieur à celui du start (période jour comme un stop entre 10h00 et 13h00)
              //log::add(__CLASS__, 'debug', $cfg_h1_stop.$cfg_m1_stop . ' < '. $cfg_h1_start.$cfg_m1_start); 
              for ($i = 0; $i < 30; $i++) {
                  $duration = $duration + scenarioExpression::durationbetween($_cmdname, $_value, $i.' day ago '.$stop, $i.' day ago '.$start);
              }
            }

      		return self::convertToHoursMins($duration, '%02d heure(s) et %02d minute(s)');
          
        } else {
          return 'Non historisée';
        }      
    } else {
    	return 'Introuvable';
    }
  }

  
  public static function getAveragePower($_eqLogic, $_nbjour) {
    $_cmd = self::getCmd_Power($_eqLogic);    
    if (is_object($_cmd)) {
      	if ($_cmd->getIsHistorized()) {
          	$_cmdid = $_cmd->getId();
            $_cmdname = '#'.$_cmd->getHumanName().'#';
            log::add(__CLASS__, 'debug', 'Nom de la commande de puissance trouvée : '.$_cmdname);

            $cfg_h1_stop = config::byKey('cfg_h1_stop', __CLASS__); // Récupération du paramètre global cgf_h1
            $cfg_m1_stop = config::byKey('cfg_m1_stop', __CLASS__); // Récupération du paramètre global cgf_m1    

            $cfg_h1_start = config::byKey('cfg_h1_start', __CLASS__); // Récupération du paramètre global cgf_h1
            $cfg_m1_start = config::byKey('cfg_m1_start', __CLASS__); // Récupération du paramètre global cgf_m1 

            $stop = $cfg_h1_stop.':'.$cfg_m1_stop; // Heure à laquelle l'équipement aurait pu être stoppé
            $start = $cfg_h1_start.':'.$cfg_m1_start; // Heure à laquelle l'équipement aurait pu être redémarré
            
            if ($cfg_h1_stop.$cfg_m1_stop > $cfg_h1_start.$cfg_m1_start) { // Si l'heure du stop est supérieur à celui du start (période nuit comme un stop entre 23h00 et 07h00)
            	$d_stop = date('Y-m-d '. $stop. ':00');             
              	$d_start = date('Y-m-d '. $start. ':00', strtotime(' + 1 days'));
              	$diff = (strtotime($d_start)-strtotime($d_stop))/3600; // Nombre d'heure entre le stop et le  start
              	//log::add(__CLASS__, 'debug', '$d_stop (test) : '.$d_stop);
              	//log::add(__CLASS__, 'debug', '$d_start (test) : '.$d_start);              
              	//log::add(__CLASS__, 'debug', 'diff : '.$diff); 
                            
              	//log::add(__CLASS__, 'debug', $cfg_h1_stop.$cfg_m1_stop . ' > '. $cfg_h1_start.$cfg_m1_start);
              	for ($i = 0; $i < $_nbjour; $i++) {
                	$ii = $i+1;
                  	$v = scenarioExpression::averageTemporalBetween($_cmdid, $ii.' day ago '.$stop, $i.' day ago '.$start); // $_cmdid et non $_cmdname comment pour durationbetween
                  	if ($v < 0) { $v = 0; } // Pour ne pas avoir de valeurs négatives
                	$duration = $duration + $v;
                	//log::add(__CLASS__, 'debug', $_cmdname .' -> ' . $duration);
              	}
            } else { // Si l'heure du stop est inférieur à celui du start (période jour comme un stop entre 10h00 et 13h00)
              	//log::add(__CLASS__, 'debug', $cfg_h1_stop.$cfg_m1_stop . ' < '. $cfg_h1_start.$cfg_m1_start); 
              	$d_stop = date('Y-m-d '. $stop. ':00');
              	$d_start = date('Y-m-d '. $start. ':00');
                $diff = (strtotime($d_start)-strtotime($d_stop))/3600; // Nombre d'heure entre le stop et le  start
                //log::add(__CLASS__, 'debug', '$d_stop (test) : '.$d_stop);
              	//log::add(__CLASS__, 'debug', '$d_start (test) : '.$d_start);
              	//log::add(__CLASS__, 'debug', 'diff : '.$diff);
              
              	for ($i = 0; $i < $_nbjour; $i++) {
                  	$v = scenarioExpression::averageTemporalBetween($_cmdid, $i.' day ago '.$stop, $i.' day ago '.$start); // $_cmdid et non $_cmdname comment pour durationbetween
                  	if ($v < 0) { $v = 0; } // Pour ne pas avoir de valeurs négatives
                	$duration = $duration + $v;
              		//log::add(__CLASS__, 'debug', $_cmdname .' ->> ' . $duration);
              	}
            }
          
          
      		return round($duration*$diff).' Wh'; // Puissance moyenne * nombre d'heure de la coupure pour avoir l'estimation de la consommation
          
        } else {
          return 'Non historisée';
        }      
    } else {
    	return 'Introuvable';
    }
  }
  
  
  public static function isManaged($search_id) {
 	foreach (eqLogic::byType('energysaver') as $eqLogic) {
      	$cmd = $eqLogic->getCmd(null, 'EqID');
      	if (is_object($cmd)) {    	
     		$id = $cmd->execCmd();          
            if ($id == $search_id) {
              if ($eqLogic->getIsEnable()) {
                return 1; // Equipement géré et actif
              } else {
                return 2; // Equipement géré et inactif
              }
            }
        }
    }
    return 0; // Equipement non géré
  }
  
  
  public static function getOnAction($eqLogicID) {
    $eqLogic = eqLogic::byId($eqLogicID);
    foreach ($eqLogic->getCmd('action') as $cmd) {
      $cmd_name = $cmd->getName();
      if (strtolower($cmd_name) == "on") {
        return $cmd->getId();
      }
    }
  }
  
  public static function getOffAction($eqLogicID) {
    $eqLogic = eqLogic::byId($eqLogicID);
    foreach ($eqLogic->getCmd('action') as $cmd) {
      $cmd_name = $cmd->getName();
      if (strtolower($cmd_name) == "off") {
        return $cmd->getId();
      }
    }
  }
  
  
  public static function updateManagedEquipements($_array) {
    // Parcours des équipements managés pour les désactiver si retiré de la selection issue dans la modale
    foreach (eqLogic::byType('energysaver') as $eqLogic) {
      	$cmd_EqID = $eqLogic->getCmd(null, 'EqID');
        if (is_object($cmd_EqID)) { 
       		$managed_id = $cmd_EqID->execCmd();

      		log::add(__CLASS__, 'debug', 'managed id : '.$managed_id);
      

            $exist = 0;
            foreach ($_array as $equ) {          
                $id = $equ[0]["id"];
                log::add(__CLASS__, 'debug', $id. ' exist in array');
                if ($id == $managed_id) {
                  log::add(__CLASS__, 'debug', $id. ' exist in plugin');
                  $exist = 1;              
                }
            }
            if ($exist == 0) {
              log::add(__CLASS__, 'debug', $managed_id. 'to disable');
              self::disableEquipement($eqLogic); // Désactivation de l'équipement managé car n'existe plus dans la selection envoyée depuis la modale
            }
        }
        
    }
    
    // Parcours de la selection issue de la modale afin d'ajouter des nouveaux équipements si nécéssaire
    foreach ($_array as $equ) {
      	$id = $equ[0]["id"];
        log::add(__CLASS__, 'debug', $id. ' transmis depuis la modale');
      	if ($id != '') {
          if (self::isManaged($id) ==  0) {
              log::add(__CLASS__, 'debug', $id. 'to create');
              self::createEquipement($id);
          }
        }
    }
          
  }
  
  // Création de l'équipement principal pour supporter le template //
  public static function createMainEquipement() {   
    $eqLogic_main = eqLogic::byLogicalId('main', 'energysaver', true);
    if (count($eqLogic_main) == 0) {
      log::add(__CLASS__, 'debug', 'Création de l\'équipement principal');      
      $eqLogic = new energysaver();
      $eqLogic->setEqType_name('energysaver');
      $eqLogic->setName('Energy Saver');
      $eqLogic->setLogicalId('main');
      $eqLogic->setIsEnable(1);
      $eqLogic->setIsVisible(1);
      $eqLogic->save();
    }   
  }
      
  public static function createEquipement($_id) {
    // Création de l'équipement
    $name = 'energysaver_'.$_id.' '.eqLogic::byId($_id)->getName(); // Nom du nouvel équipement
    log::add(__CLASS__, 'debug', 'Création d\'un nouvel équipement '.$name);    
    
    $eqLogic = new energysaver();
    $eqLogic->setEqType_name("energysaver");
    $eqLogic->setIsEnable(1);
    $eqLogic->setIsVisible(0);
    $eqLogic->setName($name);
    $eqLogic->save();
    
    // Création de la commande EqID pour stocker l'équipement à contrôler
    $info = $eqLogic->getCmd(null, 'EqID');
	if (!is_object($info)) {
		$info = new energysaverCmd();
		$info->setName(__('ID Equipement Energy Saver', __FILE__));
	}
	$info->setLogicalId('EqID');
	$info->setEqLogic_id($eqLogic->getId());
	$info->setType('info');
	$info->setSubType('numeric');
    $info->setTemplate('dashboard','line');
    $info->setIsVisible(0);
	$info->save();
    
    // Enregistrement de l'ID de l'équipement à contrôler dans la commande EqID
    $eqLogic->checkAndUpdateCmd('EqID', $_id);
    
  }
  
  public static function disableEquipement($_eqLogic) {
  	$_eqLogic->setIsEnable(0);
    $_eqLogic->save();
  }
  
  public function executeAction($_action) {    
    
    if ($_action == 'stop') {
        $EqID = $this->getCmd(null, 'EqID')->execCmd(); // Récupération de l'ID de la commande
      	$EqName = eqLogic::byId($EqID)->getName();
      	//log::add(__CLASS__, 'debug', 'Equipement ID : '.$EqID);
     	//log::add(__CLASS__, 'debug', 'Equipement Name : '.$EqName);
    
    	$cmdID_off = self::getOffAction($EqID); // Id de la commande Off de l'équipement
      	
    	//log::add(__CLASS__, 'debug', 'ID Equipement : '.$cmdID_off);
    	cmd::byId($cmdID_off)->execCmd(); // Execution de la commande Off
      	log::add(__CLASS__, 'info', 'Entrée dans le mode Energy Saver pour l\'équipement '.$EqName.' : commande Off');
      	$this->checkAndUpdateCmd('state', 1);
    }
    
    if ($_action == 'start') {
        //log::add(__CLASS__, 'debug', 'cfg_disableAutoOn : '.$this->getId().' '.$cfg_disableAutoOn);
      
        $EqID = $this->getCmd(null, 'EqID')->execCmd(); // Récupération de l'ID de la commande
      	$EqName = eqLogic::byId($EqID)->getName();
      	//log::add(__CLASS__, 'debug', 'ID Equipement : '.$EqID);
    
        $cmdID_on = self::getOnAction($EqID);    
        //log::add(__CLASS__, 'debug', 'ID Equipement : '.$cmdID_on);
      
      	$cfg_disableAutoOn = $this->getConfiguration('cfg_disableAutoOn'); // Récupération du paramètre cfg_disableAutomaticyOn de l'équipement
      	if ($cfg_disableAutoOn == 0) {
          log::add(__CLASS__, 'info', 'Sortie du mode Energy Saver pour l\'équipement '.$EqName.' : commande On');
          cmd::byId($cmdID_on)->execCmd(); // Execution de la commande On          
          $this->checkAndUpdateCmd('state', 0);          
        } else {
        	log::add(__CLASS__, 'info', 'L\'équipement '.$EqName.' est paramétré pour ne pas être rallumé automatiquement');
        }
    }
    
  }

  
  public function toHtml($_version = 'dashboard') {

    $eqLogicalId = $this->getLogicalId();

    /*
    if ($this->getConfiguration('usePluginTemplate') != 1) {
      return parent::toHtml($_version);
    }
    */

    if ($eqLogicalId != 'main') { // Si le LogicalId de l'équipement n'est pas main -> pas de template
      return parent::toHtml($_version);
    } 
    $eqLogicName = $this->getName();
    log::add(__CLASS__, 'debug', '--- Affichage du template pour '.$eqLogicName.' ---');
    
    $replace = $this->preToHtml($_version); // initialise les tag standards : #id#, #name# ...

    if (!is_array($replace)) {
      return $replace;
    }

    $version = jeedom::versionAlias($_version);
    
    
    $cfg_planification = self::getStopStartParameters('h'); // Récupération du paramétrage de la planification avec un séparateur "h"
    //$replace['#planification#'] = 'Planification : arrêt des équipements à '.$cfg_planification['stop'].' et remise en service à '.$cfg_planification['start'];    
    if ($cfg_planification['stop'] == 'h' || $cfg_planification['start'] == 'h') {
    	$replace['#planification#'] = '<span style="color: red;">Planification en attente</span>';
    } else {
      $replace['#planification#'] = ''
          . '<span style="display: block">'.$cfg_planification['stop'].'<img src="plugins/energysaver/core/template/img/energysaver_green.png" width="30"></span>'
          . '<span style="display: block">'.$cfg_planification['start'].'<img src="plugins/energysaver/core/template/img/energysaver_red.png" width="30"></span>';
    }
    
    
    $NumberOfDevices = self::getNumberOfDevices(); // Nombre d'équipements total et actif
    $replace['#nb_total_device#'] = $NumberOfDevices['total'];
    $replace['#nb_enable_device#'] = $NumberOfDevices['enable'];

    
    $main_state = $this->getCmd(null, 'state')->execCmd(); // Etat du mode en cours (1 = energy saver ; 0 no energy saver)
    if ($main_state == '') {
      $main_state = '<img src="plugins/energysaver/core/template/img/energysaver_red.png" width="80">';
    } else {
    	 $main_state = '<img src="plugins/energysaver/core/template/img/energysaver_green.png" width="80">';	
    }
    $replace['#main_state#'] = $main_state;
    
    /*
    foreach (eqLogic::byType('energysaver') as $eqLogic) {
      	$cmd_EqID = $eqLogic->getCmd(null, 'EqID');
      	if (is_object($cmd_EqID)) {    	
	        if ($eqLogic->getIsEnable()) {
            	$cmd_state = $eqLogic->getCmd(null, 'state')->ExecCmd();
              
                $replace['#data#'] .= ''
                  . ' <div class="content-sm"> '
                  . ' <h1 style="font-size: 14px; font-weight:bold"> ' . $eqLogic->getName() ;                  
              	if ($cmd_state == 1) {
                	$replace['#data#'] .= ' <span class="iconCmd"><i class="icon jeedom-lumiere-off"></i></span> ' ;
                } else {
                  	$replace['#data#'] .= ' <span class="iconCmd"><i class="icon_yellow icon jeedom-lumiere-on"></i></span> ' ;
                }
                $replace['#data#'] .= ''
                  . ' </h1> '
                  . ' </div>' ;
            	
            }           
        }

    }  
    */

    $d1 = date('Y-m-d 08:00:00');
    log::add(__CLASS__, 'debug', 'd1 (test) : '.$d1);
    $d2 = date('Y-m-d 09:00:00', strtotime(' + 1 days'));
    log::add(__CLASS__, 'debug', 'd2 (test) : '.$d2);

    $td1 = strtotime($d1);
    log::add(__CLASS__, 'debug', 'd1 (test) : '.$td1);
    
    $td2 = strtotime($d2);
    log::add(__CLASS__, 'debug', 'd2 (test) : '.$td2);
    
    $d = $td2-$td1;
    
   	log::add(__CLASS__, 'debug', 'date_diff (test) : '.$d);
    
  	$getTemplate = getTemplate('core', $version, 'energysaver.template', __CLASS__); // récupération du template 'energysaver.template'
  	$template_replace = template_replace($replace, $getTemplate); // rempalcement des tags
  	$postToHtml = $this->postToHtml($_version, $template_replace); // mise en cache du widget, si la config de l'user le permet
  	return $postToHtml; // renvoie le code du template du widget
  }

/* 
  public function refreshData() {
    $heure_en_cours = date("H"); // Heure au format 24h avec les zéros initiaux
    $minute_en_cours = date("i"); // Minute au format 24h avec les zéros initiaux 
    
    $cfg_h1_stop = config::byKey('cfg_h1_stop', __CLASS__); // Récupération du paramètre global cgf_h1
    $cfg_m1_stop = config::byKey('cfg_m1_stop', __CLASS__); // Récupération du paramètre global cgf_m1    
    log::add(__CLASS__, 'debug', 'Heure Stop : '.$cfg_h1_stop.':'.$cfg_m1_stop);   
   	
    $cfg_h1_start = config::byKey('cfg_h1_start', __CLASS__); // Récupération du paramètre global cgf_h1
    $cfg_m1_start = config::byKey('cfg_m1_start', __CLASS__); // Récupération du paramètre global cgf_m1
    log::add(__CLASS__, 'debug', 'Heure Start : '.$cfg_h1_start.':'.$cfg_m1_start);   
    
    // Test si heure du stop
    if ($heure_en_cours == $cfg_h1_stop && $minute_en_cours == $cfg_m1_stop) {
    	log::add(__CLASS__, 'debug', 'Entrée dans le mode Energy Saver (STOP)');      

        $EqID = $this->getCmd(null, 'EqID')->execCmd(); // Récupération de l'ID de la commande 
      	log::add(__CLASS__, 'debug', 'ID Equipement : '.$EqID);
    
    	$cmdID_off = self::getOffAction($EqID);    
    	log::add(__CLASS__, 'debug', 'ID Equipement : '.$cmdID_off);
    	cmd::byId($cmdID_off)->execCmd(); // Execution de la commande Off
      	log::add(__CLASS__, 'debug', 'Commande OFF éxécutée');
      	$this->checkAndUpdateCmd('state', 1);
    }
    
    // Test si heure du start
    if ($heure_en_cours == $cfg_h1_start && $minute_en_cours == $cfg_m1_start) {
    	log::add(__CLASS__, 'debug', 'Sortie du mode Energy Saver (START)');      

        $EqID = $this->getCmd(null, 'EqID')->execCmd(); // Récupération de l'ID de la commande 
      	log::add(__CLASS__, 'debug', 'ID Equipement : '.$EqID);
    
    	$cmdID_on = self::getOffAction($EqID);    
    	log::add(__CLASS__, 'debug', 'ID Equipement : '.$cmdID_on);
    	cmd::byId($cmdID_on)->execCmd(); // Execution de la commande On
      	log::add(__CLASS__, 'debug', 'Commande ON éxécutée');
      	$this->checkAndUpdateCmd('state', 0);
    }
  }
*/
  
  
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */
  

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  */
  public static function cron5() {
    //log::add(__CLASS__, 'debug', 'cron');
    $heure_en_cours = date("H"); // Heure au format 24h avec les zéros initiaux
    $minute_en_cours = date("i"); // Minute au format 24h avec les zéros initiaux 
    
    $cfg_h1_stop = config::byKey('cfg_h1_stop', __CLASS__); // Récupération du paramètre global cfg_h1_stop
    $cfg_m1_stop = config::byKey('cfg_m1_stop', __CLASS__); // Récupération du paramètre global cfg_m1_stop    
    //log::add(__CLASS__, 'debug', 'Heure Stop : '.$cfg_h1_stop.':'.$cfg_m1_stop);   
   	
    $cfg_h1_start = config::byKey('cfg_h1_start', __CLASS__); // Récupération du paramètre global cfg_h1_start
    $cfg_m1_start = config::byKey('cfg_m1_start', __CLASS__); // Récupération du paramètre global cfg_m1_start
    //log::add(__CLASS__, 'debug', 'Heure Start : '.$cfg_h1_start.':'.$cfg_m1_start);
    
    // Test si heure du stop
    if ($heure_en_cours == $cfg_h1_stop && $minute_en_cours == $cfg_m1_stop) {
      $action = 'stop';
    }
    
    // Test si heure du start
    if ($heure_en_cours == $cfg_h1_start && $minute_en_cours == $cfg_m1_start) {
      $action = 'start';
    }
    
    // Action sur l'équipement principal (main)
    $eqLogic = eqLogic::byLogicalId('main', 'energysaver');
    if ($action == 'stop') {
      	log::add(__CLASS__, 'debug', 'Main Stop');
      	$eqLogic->checkAndUpdateCmd('state', 1); 
    } 
    
    if ($action == 'start') {
      	log::add(__CLASS__, 'debug', 'Main Start');
    	$eqLogic->checkAndUpdateCmd('state', 0);  
    }
    
    // Action sur les équipements gérés par le plugin
    if ($action == 'stop' || $action == 'start') { // Action à lancer si l'équipement est actif
      	log::add(__CLASS__, 'debug', '-- déclenchement du cron pour éxécution des actions --');
    	foreach (eqLogic::byType('energysaver', true) as $eqLogic) {
        	if ($eqLogic->getIsEnable() && $eqLogic->getLogicalId() != 'main') { // Parcours des équipements du plugin sauf le principal "main"
    			$eqLogic->executeAction($action);
    		}
    	}
      	$this->refreshWidget();
    }
    

    
    
    // Temporaire
    /*
    foreach (eqLogic::byType('energysaver', true) as $eqLogic) {
      if ($eqLogic->getIsEnable()) {
        $cfg_disableAutoOn = $eqLogic->getConfiguration('cfg_disableAutoOn'); // Récupération du paramètre cfg_disableAutomaticyOn de l'équipement
        log::add(__CLASS__, 'debug', 'cfg_disableAutoOn : '.$eqLogic->getId().' '.$cfg_disableAutoOn);
      }
    }
    */
    
  }

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    /*
    log::add(__CLASS__, 'debug', '-- postSave --');
    $eqid = $this->getId();
    $eqname = $this->getName();
    log::add(__CLASS__, 'debug', $eqid);
    log::add(__CLASS__, 'debug', $eqname);
    */
    
    // Commande info binaire pour indiquer l'état (1 pour sauvegarde de l'énérgie en cours)
    $info = $this->getCmd(null, 'state');
    if (!is_object($info)) {
      $info = new energysaverCmd();
      $info->setName(__('Etat du mode', __FILE__));
    }
    $info->setLogicalId('state');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('binary');
    $info->setdisplay('invertBinary', 1);
    $info->setIsHistorized(1);
    $info->setConfiguration('historizeMode','avg');
    $info->setTemplate('dashboard','light');
    $info->save();    
    
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*     * **********************Getteur Setteur*************************** */

}

class energysaverCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
  }

  /*     * **********************Getteur Setteur*************************** */

}