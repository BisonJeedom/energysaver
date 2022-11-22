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
    
    for ($i = 1; $i <=3; $i++) {
      $cfg_h_stop = config::byKey('cfg_h'.$i.'_stop', __CLASS__); // Récupération du paramètre global cgf_h1
      $cfg_m_stop = config::byKey('cfg_m'.$i.'_stop', __CLASS__); // Récupération du paramètre global cgf_m1    

      $cfg_h_start = config::byKey('cfg_h'.$i.'_start', __CLASS__); // Récupération du paramètre global cgf_h1
      $cfg_m_start = config::byKey('cfg_m'.$i.'_start', __CLASS__); // Récupération du paramètre global cgf_m1

      if (($cfg_h_stop != '') && ($cfg_m_stop != '') && ($cfg_h_start != '') && ($cfg_m_start != '')) {
        $array['stop'][$i] = $cfg_h_stop.$_separator.$cfg_m_stop;
        $array['start'][$i] = $cfg_h_start.$_separator.$cfg_m_start;
      } else {
        $array['stop'][$i] = '';
        $array['start'][$i] = '';
      }
    }

    return $array;
  }
  
  public static function getNumberOfDevices() {
   	foreach (eqLogic::byType('energysaver') as $eqLogic) {
      if ($eqLogic->getLogicalId() != 'main') {
        $count_total++;
      	//if ($eqLogic->getIsEnable()) {
        $cmd = $eqLogic->getCmd(null, 'Schedule'); // Récupération de la commande Schedule
      	if (is_object($cmd)) {
          	$schedule = $cmd->execCmd();
          	if ($schedule != 0) { // Si existe une planification
      			$count_schedule++;
        	}
        }
      }
    }
    $array['total'] = $count_total;
    $array['schedule'] = $count_schedule;
    
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
  
  

  
  public static function getStateDuration($_eqLogic, $_value) {
    


    $eqLogic_id = $_eqLogic->getId();
    $schedule = energysaver::getschedule($eqLogic_id); // Récupération de la planification	
    //log::add(__CLASS__, 'debug', 'schedule : '.$schedule.' pour equipement : '.$_eqLogic->getName());  
    if ($schedule <= 0) {
      $schedule = 1;
    }

    $_cmd = self::getStateCmd($_eqLogic);
    
    if (is_object($_cmd)) {
      	if ($_cmd->getIsHistorized()) {
        	if (config::byKey('version') < '4.3.1') { // durationbetween comporte un bug si Jeedom < 4.3.1 et affiche une erreur getDatetime() on null si pas de donnée dans l'historique
     	 		log::add(__CLASS__, 'debug', 'Version Jeedom : '.config::byKey('version'));  
    			return 'Jeedom < 4.3.1';
    		}
          	
            $_cmdname = '#'.$_cmd->getHumanName().'#';
            log::add(__CLASS__, 'debug', 'Nom de la commande binaire trouvée : '.$_cmdname);			

            
          	$cfg_planifications_1 = energysaver::getStopStartParameters();                    	
          	$hstop_1  = $cfg_planifications_1['stop'][$schedule];
            $hstart_1 = $cfg_planifications_1['start'][$schedule];
          
          	$cfg_planifications_2 = energysaver::getStopStartParameters(':');
            $hstop_2  = $cfg_planifications_2['stop'][$schedule];
            $hstart_2 = $cfg_planifications_2['start'][$schedule];
            
            if ($hstop_1 > $hstart_1) { // Si l'heure du stop est supérieur à celui du start (période nuit comme un stop entre 23h00 et 07h00)
              //log::add(__CLASS__, 'debug', $cfg_h1_stop.$cfg_m1_stop . ' > '. $cfg_h1_start.$cfg_m1_start);
              for ($i = 0; $i < 30; $i++) {
                $ii = $i+1;
                $duration = $duration + scenarioExpression::durationbetween($_cmdname, $_value, $ii.' day ago '.$hstop_2, $i.' day ago '.$hstart_2);
              }
            } else { // Si l'heure du stop est inférieur à celui du start (période jour comme un stop entre 10h00 et 13h00)
              //log::add(__CLASS__, 'debug', $cfg_h1_stop.$cfg_m1_stop . ' < '. $cfg_h1_start.$cfg_m1_start); 
              for ($i = 0; $i < 30; $i++) {
                  $duration = $duration + scenarioExpression::durationbetween($_cmdname, $_value, $i.' day ago '.$hstop_2, $i.' day ago '.$hstart_2);
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
    
    $eqLogic_id = $_eqLogic->getId();
    $schedule = energysaver::getschedule($eqLogic_id); // Récupération de la planification	
    //log::add(__CLASS__, 'debug', 'schedule : '.$schedule.' pour equipement : '.$_eqLogic->getName());  
    if ($schedule <= 0) {
      $schedule = 1;
    }
    
    $_cmd = self::getCmd_Power($_eqLogic);    
    if (is_object($_cmd)) {
      	if ($_cmd->getIsHistorized()) {          
            if (config::byKey('version') < '4.3.1') { // durationbetween comporte un bug si Jeedom < 4.3.1 et affiche une erreur getDatetime() on null si pas de donnée dans l'historique
     	 		log::add(__CLASS__, 'debug', 'Version Jeedom : '.config::byKey('version'));  
    			return 'Jeedom < 4.3.1';
    		}
          
          	$_cmdid = $_cmd->getId();
            $_cmdname = '#'.$_cmd->getHumanName().'#';
            log::add(__CLASS__, 'debug', 'Nom de la commande de puissance trouvée : '.$_cmdname);

            $cfg_planifications_1 = energysaver::getStopStartParameters();                    	
          	$hstop_1  = $cfg_planifications_1['stop'][$schedule];
            $hstart_1 = $cfg_planifications_1['start'][$schedule];
          
          	$cfg_planifications_2 = energysaver::getStopStartParameters(':');
            $hstop_2  = $cfg_planifications_2['stop'][$schedule];
            $hstart_2 = $cfg_planifications_2['start'][$schedule];
          
            if ($hstop_1 > $hstart_1) { // Si l'heure du stop est supérieur à celui du start (période nuit comme un stop entre 23h00 et 07h00)
            	$d_stop = date('Y-m-d '. $hstop_2. ':00');             
              	$d_start = date('Y-m-d '. $hstart_2. ':00', strtotime(' + 1 days'));
              	$diff = (strtotime($d_start)-strtotime($d_stop))/3600; // Nombre d'heure entre le stop et le  start
              	//log::add(__CLASS__, 'debug', '$d_stop (test) : '.$d_stop);
              	//log::add(__CLASS__, 'debug', '$d_start (test) : '.$d_start);              
              	//log::add(__CLASS__, 'debug', 'diff : '.$diff); 
                            
              	//log::add(__CLASS__, 'debug', $cfg_h1_stop.$cfg_m1_stop . ' > '. $cfg_h1_start.$cfg_m1_start);
              	for ($i = 0; $i < $_nbjour; $i++) {
                	$ii = $i+1;
                  	$v = scenarioExpression::averageTemporalBetween($_cmdid, $ii.' day ago '.$hstop_2, $i.' day ago '.$hstart_2); // $_cmdid et non $_cmdname comment pour durationbetween
                  	if ($v < 0) { $v = 0; } // Pour ne pas avoir de valeurs négatives
                	$duration = $duration + $v;
                	//log::add(__CLASS__, 'debug', $_cmdname .' -> ' . $duration);
              	}
            } else { // Si l'heure du stop est inférieur à celui du start (période jour comme un stop entre 10h00 et 13h00)
              	//log::add(__CLASS__, 'debug', $cfg_h1_stop.$cfg_m1_stop . ' < '. $cfg_h1_start.$cfg_m1_start); 
              	$d_stop = date('Y-m-d '. $hstop_2. ':00');
              	$d_start = date('Y-m-d '. $hstart_2. ':00');
                $diff = (strtotime($d_start)-strtotime($d_stop))/3600; // Nombre d'heure entre le stop et le  start
                //log::add(__CLASS__, 'debug', '$d_stop (test) : '.$d_stop);
              	//log::add(__CLASS__, 'debug', '$d_start (test) : '.$d_start);
              	//log::add(__CLASS__, 'debug', 'diff : '.$diff);
              
              	for ($i = 0; $i < $_nbjour; $i++) {
                  	$v = scenarioExpression::averageTemporalBetween($_cmdid, $i.' day ago '.$hstop_2, $i.' day ago '.$hstart_2); // $_cmdid et non $_cmdname comment pour durationbetween
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
  
  
  public static function isManaged($_search_id) {
 	foreach (eqLogic::byType('energysaver') as $eqLogic) {
      	$cmd = $eqLogic->getCmd(null, 'EqID');
      	if (is_object($cmd)) {    	
     		$id = $cmd->execCmd();          
            if ($id == $_search_id) {
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
  
  
  public static function getSchedule($_id) {
    //////////////////////////////////////////////////////////////////////////////////////////
    //		Retourne le numéro de la planification à partir de l'ID de l'équipement réel	//
    //////////////////////////////////////////////////////////////////////////////////////////
    
   	foreach (eqLogic::byType('energysaver') as $eqLogic) {
      	$cmd = $eqLogic->getCmd(null, 'EqID');
      	if (is_object($cmd)) {    	
     		$id = $cmd->execCmd();          
            if ($id == $_id) {
              if ($eqLogic->getIsEnable()) {
              	$cmd = $eqLogic->getCmd(null, 'Schedule');
                if (is_object($cmd)) {
                	$schedule = $cmd->execCmd();
					return $schedule; // Planification 1, 2 ou 3
                } else {
                	return 0; // Aucune planification
                }
              } else {
                return -1; // Equipement géré et inactif
              }
            }
        }
    }
    return -2; // Equipement non géré  
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
			
      		//log::add(__CLASS__, 'debug', 'managed id : '.$managed_id);      

            $exist = 0;
            foreach ($_array as $equ) {          
                $id = $equ[0]["id"];
              	$schedule = $equ[1]["schedule"];
                //log::add(__CLASS__, 'debug', $id. ' exist in array');
                if ($id == $managed_id) {
                  //log::add(__CLASS__, 'debug', $id. ' exist in plugin');
                  $exist = 1;
                  $eqLogic->checkAndUpdateCmd('Schedule', $schedule); 
                }
            }
            if ($exist == 0) {
              //log::add(__CLASS__, 'debug', $managed_id. ' -> No planification');
              $eqLogic->checkAndUpdateCmd('Schedule', 0); 
              //self::disableEquipement($eqLogic); // Désactivation de l'équipement managé car n'existe plus dans la selection envoyée depuis la modale
            }
        }
        
    }
    
    // Parcours de la selection issue de la modale afin d'ajouter des nouveaux équipements si nécéssaire
    foreach ($_array as $equ) {
      	$id = $equ[0]["id"];
      	$schedule = $equ[1]["schedule"];      	
        log::add(__CLASS__, 'debug', 'Transmis depuis la modale : '.$id.'->'.$schedule);
      	if ($id != '') {
          //if (self::isManaged($id) ==  0) {
          if (self::getSchedule($id) == -2 ) { // Aucun équipement donc à créer
              log::add(__CLASS__, 'debug', $id. ' to create');
              self::createEquipement($id, $schedule);
          }
        }
    }
          
  }
  
  // Création de l'équipement principal pour supporter le template //
  public static function createMainEquipement() {
    log::add(__CLASS__, 'info', 'Create Main Equipment');
    $eqLogic = eqLogic::byLogicalId('main', 'energysaver', true);
    if (count($eqLogic) == 0) {
      log::add(__CLASS__, 'debug', 'Création de l\'équipement principal');      
      $eqLogic = new energysaver();
      $eqLogic->setEqType_name('energysaver');
      $eqLogic->setName('Energy Saver');
      $eqLogic->setLogicalId('main');
      $eqLogic->setIsEnable(1);
      $eqLogic->setIsVisible(1);
      $eqLogic->save();
    }
    
    $eqLogic = eqLogic::byLogicalId('main', 'energysaver', false);
    // Création des commandes Schedule binaire pour savoir quelle planification est en cours
    for ($i = 1; $i <= 3; $i++) {
      log::add(__CLASS__, 'info', 'Create Main Equipment / Commandes Schedule'.$i);
      $info = $eqLogic->getCmd(null, 'Schedule'.$i);
      if (!is_object($info)) {
        $info = new energysaverCmd();
        $info->setName(__('Etat planification '.$i, __FILE__));
      }
      $info->setLogicalId('Schedule'.$i);
      $info->setEqLogic_id($eqLogic->getId());
      $info->setType('info');
      $info->setSubType('numeric');
      $info->setTemplate('dashboard','line');
      $info->setIsVisible(0);
      $info->save();
    }
    
    for ($i = 1; $i <= 3; $i++) {
      log::add(__CLASS__, 'info', 'Create Main Equipment / Commande Schedule'.$i.'_on');
      $info = $eqLogic->getCmd(null, 'Schedule'.$i.'_on');
      if (!is_object($info)) {
        $info = new energysaverCmd();
        $info->setName(__('Planification '.$i.' On', __FILE__));
      }
      $info->setLogicalId('Schedule'.$i.'_on');
      $info->setEqLogic_id($eqLogic->getId());
      $info->setType('action');
      $info->setSubType('other');
      $info->setIsVisible(0);
      $info->save();
      
      log::add(__CLASS__, 'info', 'Create Main Equipment / Commande Schedule'.$i.'_off');
      $info = $eqLogic->getCmd(null, 'Schedule'.$i.'_off');
      if (!is_object($info)) {
        $info = new energysaverCmd();
        $info->setName(__('Planification '.$i.' Off', __FILE__));
      }
      $info->setLogicalId('Schedule'.$i.'_off');
      $info->setEqLogic_id($eqLogic->getId());
      $info->setType('action');
      $info->setSubType('other');
      $info->setIsVisible(0);
      $info->save();
      
    }
  
  }
  
  

  
  
  public static function createEquipement($_id, $_schedule) {
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
    
    // Création de la commande Schedule pour stocker la planification
    $info = $eqLogic->getCmd(null, 'Schedule');
	if (!is_object($info)) {
		$info = new energysaverCmd();
		$info->setName(__('Planification', __FILE__));
	}
	$info->setLogicalId('Schedule');
	$info->setEqLogic_id($eqLogic->getId());
	$info->setType('info');
	$info->setSubType('numeric');
    $info->setTemplate('dashboard','line');
    $info->setIsVisible(0);
	$info->save();
    
    // Enregistrement du numéro de la planification pour l'équipement
    $eqLogic->checkAndUpdateCmd('Schedule', $_schedule);
    
  }
  
  public static function disableEquipement($_eqLogic) {
  	$_eqLogic->setIsEnable(0);
    $_eqLogic->save();
  }
 
  public static function executeAction($_eqLogic, $_action) { 
    if ($_action == 'stop') {
      $EqID = $_eqLogic->getCmd(null, 'EqID')->execCmd(); // Récupération de l'ID de la commande
      $EqName = eqLogic::byId($EqID)->getName();
      //log::add(__CLASS__, 'debug', 'Equipement ID : '.$EqID);
      //log::add(__CLASS__, 'debug', 'Equipement Name : '.$EqName);

      $cmdID_off = self::getOffAction($EqID); // Id de la commande Off de l'équipement

      //log::add(__CLASS__, 'debug', 'ID Equipement : '.$cmdID_off);
      cmd::byId($cmdID_off)->execCmd(); // Execution de la commande Off
      log::add(__CLASS__, 'info', 'Entrée dans le mode Energy Saver pour l\'équipement '.$EqName.' : commande Off');
      $_eqLogic->checkAndUpdateCmd('state', 1);
    }

    if ($_action == 'start') {
      //log::add(__CLASS__, 'debug', 'cfg_disableAutoOn : '.$this->getId().' '.$cfg_disableAutoOn);

      $EqID = $_eqLogic->getCmd(null, 'EqID')->execCmd(); // Récupération de l'ID de la commande
      $EqName = eqLogic::byId($EqID)->getName();
      //log::add(__CLASS__, 'debug', 'ID Equipement : '.$EqID);

      $cmdID_on = self::getOnAction($EqID);    
      //log::add(__CLASS__, 'debug', 'ID Equipement : '.$cmdID_on);

      $cfg_disableAutoOn = $_eqLogic->getConfiguration('cfg_disableAutoOn'); // Récupération du paramètre cfg_disableAutomaticyOn de l'équipement
      if ($cfg_disableAutoOn == 0) {
        log::add(__CLASS__, 'info', 'Sortie du mode Energy Saver pour l\'équipement '.$EqName.' : commande On');
        cmd::byId($cmdID_on)->execCmd(); // Execution de la commande On          
        $_eqLogic->checkAndUpdateCmd('state', 0);          
      } else {
        log::add(__CLASS__, 'info', 'L\'équipement '.$EqName.' est paramétré pour ne pas être rallumé automatiquement');
      }
    }  	
  }
     
 
  public static function getEqBySchedule($_schedule) {
    //////////////////////////////////////////////////////////////////////////////////
    //		Retourne un tableau des équipements affectés à une planification		//
    //////////////////////////////////////////////////////////////////////////////////
    
    foreach (eqLogic::byType('energysaver', true) as $eqLogic) {
      if ($eqLogic->getIsEnable() && $eqLogic->getLogicalId() != 'main') { // Parcours des équipements du plugin sauf le principal "main"
      	$EqSchedule = $eqLogic->getCmd(null, 'Schedule')->execCmd(); // Récupération du schedule de l'équipement
		if ($EqSchedule == $_schedule) {
        	//log::add(__CLASS__, 'debug', $eqLogic->getName().' sur planification '.$_schedule);
          	$array[] = $eqLogic;
        }
      }
    }    
    return $array;
  }
  
  public static function getCmdStateValueByPluginEqLogic($_eqLogic) {
    //////////////////////////////////////////////////////////////////////////////////
    //	Retourne la valeur de la commande d'état à partir de l'équipement du plugin	//
    //////////////////////////////////////////////////////////////////////////////////
    
    $EqID = $_eqLogic->getCmd(null, 'EqID')->execCmd(); // Récupération de l'ID de la commande EqID
    $Eq = eqLogic::byId($EqID); // Récupèration de l'eqLogic
    $EqName = $Eq->getName(); // Récupération du nom de l'équipement

    $cmd_state = self::getStateCmd($Eq); // Récupération de la commande Etat de l'équipement   
    if (is_object($cmd_state)) {      	
      	return $cmd_state->execCmd();
    } else {
    	return -1;
    }
      
  }
  
  
  public static function getCmdStateByPluginEqLogic() {
    //////////////////////////////////////////////////////////////////////
    //	Retourne la commande d'état à partir de l'équipement du plugin	//
    //////////////////////////////////////////////////////////////////////
    
    $EqID = $this->getCmd(null, 'EqID')->execCmd(); // Récupération de l'ID de la commande EqID
    $Eq = eqLogic::byId($EqID); // Récupèration de l'eqLogic
    $EqName = $Eq->getName(); // Récupération du nom de l'équipement

    log::add(__CLASS__, 'debug', 'Equipement ID : '.$EqID);
    log::add(__CLASS__, 'debug', 'Equipement Name : '.$EqName);

    $cmd_state = self::getStateCmd($Eq); // Récupération de la commande Etat de l'équipement   
    if (is_object($cmd_state)) {
      log::add(__CLASS__, 'debug', 'Etat de la commande '.$cmd_state->getName().' : '.$cmd_state->execCmd());
      return $cmd_state->execCmd();
    } else {
    	return -1;
    }
  }
  
  public static function updatePluginMultiSchedule() {
  	foreach (eqLogic::byType('energysaver') as $eqLogic) {
      if ($eqLogic->getIsEnable() == 0) {
      	$eqLogic->setIsEnable(1);
        $eqLogic->save();
      }
      
      if ($eqLogic->getLogicalId() != 'main') {
        // Création de la commande Schedule pour stocker la planification
        $info = $eqLogic->getCmd(null, 'Schedule');
        if (!is_object($info)) {
          $info = new energysaverCmd();
          $info->setName(__('Planification', __FILE__));

          $info->setLogicalId('Schedule');
          $info->setEqLogic_id($eqLogic->getId());
          $info->setType('info');
          $info->setSubType('numeric');
          $info->setTemplate('dashboard','line');
          $info->setIsVisible(0);
          $info->save();

          $eqLogic->checkAndUpdateCmd('Schedule', 1);
        }
      }
    }
  }
  
  
  public static function CheckScheduleAndExecute($_action = '', $_planification = '') {
    $heure = date("Hi");
    
    $cfg_forceOff = config::byKey('cfg_forceOff', __CLASS__); // Recupération du paramètre global cfg_forceOff

    $cfg_planifications = self::getStopStartParameters(''); // Récupération du paramétrage de la planification sans sépararteur
    
    for ($i = 1; $i <= 3 ; $i++) {
        $action = '';  
      	if ($_action == '') { // Cas d'un cron5 donc sans éxécution d'une commande action de on ou off
          $hstop = $cfg_planifications['stop'][$i];
          $hstart = $cfg_planifications['start'][$i];      
          //log::add(__CLASS__, 'debug', 'Plannif : '.$hstop.' -> '.$hstart);

          $cfg_modetrigger = config::byKey('cfg_modetrigger_'.$i, __CLASS__); // Récupération du paramètre global cfg_modetrigger pour la planification en cours (1 à 3)
          
          if ($hstop == '' && $hstart == '' || $cfg_modetrigger == 1) { // Aucune planification, mal configurée ou mode "déclencheur"
              log::add(__CLASS__, 'debug', $i.' non traité car planification incorrecte ou en mode déclencheur');
              continue;  
          }

          if ((scenarioExpression::time_between($heure, $hstop, $hstart)) && ($heure != $hstart)) {
              $action = 'stop';
          }

          // Test si heure du start
          if ($heure == $cfg_planifications['start'][$i]) {
              $action = 'start';          	
          }        
        }
      
      	if ($_action == 'start' && $_planification == $i) { // cas d'un start éxécuté via une commande action avec correspondance du numéro de la planification
        	$action = 'start';
        }
      
      	if ($_action == 'stop' && $_planification == $i) { // cas d'un stop éxécuté via une commande action avec correspondance du numéro de la planification
          	$action = 'stop';
        }

        // Action sur les équipements gérés par le plugin
        if ($action == 'stop' || $action == 'start') { // Action à lancer
          log::add(__CLASS__, 'debug', '-- Exécution des actions de '.$action.' liées à la planification n°'.$i.' (START) --');          
          $array = self::getEqBySchedule($i); // Récupération des équipements attachés à la planification       
          foreach ($array as $eqLogic) {
          	//log::add(__CLASS__, 'debug', 'Analyse en cours de l\équipement '.$eqLogic->getName());
            $value = self::getCmdStateValueByPluginEqLogic($eqLogic);
            //log::add(__CLASS__, 'debug', 'Etat de la commande : '.$value);
            
            if ($action == 'stop') {
              	if ($value == 1) {                  	
                  	$nb_off = $eqLogic->getCmd(null, 'NbOnDuringOff')->execCmd();
                  	if ($cfg_forceOff == 1) { // Si configuré pour éteindre un équipement qui a été rallumé durant la planification                      
                      if ($nb_off == 0) { // Premier arrêt au début de la planification
                          	self::executeAction($eqLogic, $action); // Execute stop                          
                          	$eqLogic->checkAndUpdateCmd('NbOnDuringOff', $nb_off+1);
                      } elseif ($nb_off == 1) { // Si allumé 1 fois durant la planification, nouveau Off
                      		log::add(__CLASS__, 'info', 'Mode activé : Eteindre un équipement qui a été rallumé durant la planification (1 fois)');	            
                          	self::executeAction($eqLogic, $action); // Execute stop                          
                          	$eqLogic->checkAndUpdateCmd('NbOnDuringOff', $nb_off+1);                        
                      } elseif ($nb_off == 2) { // Pas de nouveau Off, on averti dans dans le centre de message et on passage la commande NbOnDuringOff à -1 pour ne pas relancer un nouveau message durant la planification
                          	$eqID = $eqLogic->getCmd(null, 'EqID')->execCmd();
                          	$eqName = eqLogic::byId($eqID)->getName();
                          	log::add(__CLASS__, 'info', $eqName.' a été rallumé 2 fois durant la planification d\'arrêt !');	
                          	message::add('energysaver', $eqName.' a été rallumé 2 fois durant la planification d\'arrêt !');
                        	$eqLogic->checkAndUpdateCmd('NbOnDuringOff', -1);
                      } else {
                        	// Rien à faire
                      }
                    } else {
                    	if ($nb_off == 0) { // Si jamais éteint
                          self::executeAction($eqLogic, $action); // Execute stop                 	
                          $eqLogic->checkAndUpdateCmd('NbOnDuringOff', $nb_off+1);
                        }
                    }
                } elseif ($value == 0) {
					if ($heure == $cfg_planifications['stop'][$i] || $_action != '') { // Message juste à l'heure du début de la planification
                        log::add(__CLASS__, 'debug', 'Etat '.$value.' pour l\'équipement '.$eqLogic->getName().' -> Aucune action à faire');
                    }
                } else {
                	if ($heure == $cfg_planifications['stop'][$i] || $_action != '') { // Message juste à l'heure du début de la planification
                		log::add(__CLASS__, 'debug', 'Aucun état pour l\'équipement '.$eqLogic->getName());
                    }
                }
          	}
            
            if ($action == 'start') {
              	$eqLogic->checkAndUpdateCmd('NbOnDuringOff', 0);
              	if ($value == 0) {
            		self::executeAction($eqLogic, $action); // Execute start
                } elseif ($value == 1) {                	
            		log::add(__CLASS__, 'debug', 'Etat '.$value.' pour l\'équipement '.$eqLogic->getName().' -> Aucune action à faire');
                } else {              		
                    log::add(__CLASS__, 'debug', 'Aucun état pour l\'équipement '.$eqLogic->getName());
                }
          	}            
          }
          
           
          // Action sur l'équipement principal (main)
          $eqLogicMain = eqLogic::byLogicalId('main', 'energysaver');
          if ($action == 'stop') {
            //log::add(__CLASS__, 'debug', 'Main Stop');
            $eqLogicMain->checkAndUpdateCmd('state', 1); 
            $eqLogicMain->checkAndUpdateCmd('Schedule'.$i, 1);
          } 

          if ($action == 'start') {
            //log::add(__CLASS__, 'debug', 'Main Start');
            $eqLogicMain->checkAndUpdateCmd('state', 0);  
            $eqLogicMain->checkAndUpdateCmd('Schedule'.$i, 0);
          }

          $eqLogicMain->refreshWidget();
          
          log::add(__CLASS__, 'debug', '-- Exécution des actions de '.$action.' liées à la planification n°'.$i.' (END) --');  
        }      
     }    
  }
  
  
  public function toHtml($_version = 'dashboard') {
    $eqLogicalId = $this->getLogicalId();

    if ($eqLogicalId != 'main') { // Si le LogicalId de l'équipement n'est pas main -> pas de template
      return parent::toHtml($_version);
    } 
    $eqLogicName = $this->getName();
    //log::add(__CLASS__, 'debug', '--- Affichage du template pour '.$eqLogicName.' ---');
    
    $replace = $this->preToHtml($_version); // initialise les tag standards : #id#, #name# ...

    if (!is_array($replace)) {
      return $replace;
    }

    $version = jeedom::versionAlias($_version);
    
    
    //////////////////////////////////
    // Affichage des planifications //
    //////////////////////////////////;
    
    //$heure = date("Hhi");
    $cfg_planifications = self::getStopStartParameters('h'); // Récupération du paramétrage de la planification avec un séparateur "h"
    
    for ($i = 1; $i <= 3 ; $i++) {      
      $Schedule = $this->getCmd(null, 'Schedule'.$i)->execCmd();
      if ($Schedule == 1) {
        $active_schedule = '<span style="display: block; margin-right: 10px; margin-left: 10px; border: 1px solid rgb(200, 200, 200); border-radius: 4px;">';
      } else {
      	 $active_schedule = '<span style="display: block; margin-right: 10px; margin-left: 10px;">';  
      }
      
      $replace['#planification'.$i.'_name#'] = $active_schedule.'Planification '.$i.'</span>';
      
      $cfg_modetrigger = config::byKey('cfg_modetrigger_'.$i, __CLASS__);
      if ($cfg_modetrigger == 1) {
        //$replace['#planification'.$i.'_name#'] = $active_schedule.'Planification '.$i.'</span>';
        $replace['#planification'.$i.'#'] = '<span style="color: #ff8300eb;">Déclenchement</span>';  
      }
      elseif ($cfg_planifications['stop'][$i] == '' || $cfg_planifications['start'][$i] == '') {
      	//$replace['#planification'.$i.'_name#'] = $active_schedule.'Planification '.$i.'</span>';
        $replace['#planification'.$i.'#'] = '<span style="color: #ff8300eb;">Non configurée</span>';
      } else {
        //$replace['#planification'.$i.'_name#'] = $active_schedule.'Planification '.$i.'</span>';
        $replace['#planification'.$i.'#'] = ''
            . '<span style="display: block">'.$cfg_planifications['stop'][$i].'<img src="plugins/energysaver/core/template/img/energysaver_green.png" width="30"></span>'
            . '<span style="display: block">'.$cfg_planifications['start'][$i].'<img src="plugins/energysaver/core/template/img/energysaver_red.png" width="30"></span>';
      }
    }
    
    
    $NumberOfDevices = self::getNumberOfDevices(); // Nombre d'équipements total et actif
    $replace['#nb_total_device#'] = $NumberOfDevices['total'];
    $replace['#nb_schedule_device#'] = $NumberOfDevices['schedule'];

    
    $main_state = $this->getCmd(null, 'state')->execCmd(); // Etat du mode en cours (1 = energy saver ; 0 no energy saver)
    if ($main_state == '') {
      $main_state = '<img src="plugins/energysaver/core/template/img/energysaver_red.png" width="80">';
    } else {
    	 $main_state = '<img src="plugins/energysaver/core/template/img/energysaver_green.png" width="80">';	
    }
    $replace['#main_state#'] = $main_state;
    
  	/*
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
    */
    
  	$getTemplate = getTemplate('core', $version, 'energysaver.template', __CLASS__); // récupération du template 'energysaver.template'
  	$template_replace = template_replace($replace, $getTemplate); // rempalcement des tags
  	$postToHtml = $this->postToHtml($_version, $template_replace); // mise en cache du widget, si la config de l'user le permet
  	return $postToHtml; // renvoie le code du template du widget
  }


  
  
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
  	energysaver::CheckScheduleAndExecute();
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
    $eqLogic = $this->getLogicalId();
    log::add(__CLASS__, 'debug', 'eqLogic : '.$eqLogic);
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

    if ($this->getLogicalId() != 'main') {
        $info = $this->getCmd(null, 'nbOnDuringOff');
        if (!is_object($info)) {
          $info = new energysaverCmd();
          $info->setName(__('Nombre de marche pendant arrêt', __FILE__));
        }
        $info->setLogicalId('nbOnDuringOff');
        $info->setEqLogic_id($this->getId());
        $info->setType('info');
        $info->setSubType('numeric');
        $info->save();     
    }
        
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
    log::add('energysaver', 'debug', 'éxécution commande');  
    $eqLogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
    switch ($this->getLogicalId()) { //vérifie le logicalid de la commande
      case 'Schedule1_on': // LogicalId de la commande
        log::add('energysaver', 'debug', 'éxécution commande Schedule1_on');
        energysaver::CheckScheduleAndExecute('start', 1);      	
      	break;
      case 'Schedule1_off': // LogicalId de la commande
        log::add('energysaver', 'debug', 'éxécution commande Schedule1_off');
        energysaver::CheckScheduleAndExecute('stop', 1);      	
      	break;
      
      case 'Schedule2_on': // LogicalId de la commande
        log::add('energysaver', 'debug', 'éxécution commande Schedule2_on');
        energysaver::CheckScheduleAndExecute('start', 2);      	
      	break;
      case 'Schedule2_off': // LogicalId de la commande
        log::add('energysaver', 'debug', 'éxécution commande Schedule2_off');
        energysaver::CheckScheduleAndExecute('stop', 2);      	
      	break;
        
      case 'Schedule3_on': // LogicalId de la commande
        log::add('energysaver', 'debug', 'éxécution commande Schedule3_on');
        energysaver::CheckScheduleAndExecute('start', 3);      	
      	break;
      case 'Schedule3_off': // LogicalId de la commande
        log::add('energysaver', 'debug', 'éxécution commande Schedule3_off');
        energysaver::CheckScheduleAndExecute('stop', 3);      	
      	break;        
      default:
        break;        
    }
    
  }

  /*     * **********************Getteur Setteur*************************** */

}