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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
?>
  



<div class="col-md-12">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><center>Equipements ayant une commande On et Off</center>
              <a id="btSave" class="btn btn-success btn-xs pull-right" style="top: -2px !important; right: -6px !important;"><i class="far fa-check-circle icon-white"></i> {{Sauvegarder}}</a>
            </h3>
  			<h3 class="panel-title">
  			<?php
  			    $cfg_h1_stop = config::byKey('cfg_h1_stop', 'energysaver'); // Récupération du paramètre global cfg_h1_stop
    			$cfg_m1_stop = config::byKey('cfg_m1_stop', 'energysaver'); // Récupération du paramètre global cfg_m1_stop    
    			$cfg_h1_start = config::byKey('cfg_h1_start', 'energysaver'); // Récupération du paramètre global cfg_h1_start
    			$cfg_m1_start = config::byKey('cfg_m1_start', 'energysaver'); // Récupération du paramètre global cfg_m1_start
				echo 'Paramétrage actuel : Energy Saver entre '.$cfg_h1_stop.'h'.$cfg_m1_stop.' et '.$cfg_h1_start.'h'.$cfg_m1_start;
  			?>
            </h3>

        </div>
        <div class="panel-body">
            <table class="table-bordered table-condensed" style="width: 100%; margin: -5px -5px 10px 5px;">
                <thead>
                    <tr >
  						<th style="text-align: center; width:30px;">Prendre en charge ?</th>
  						<th style="text-align: center; width:30px;">{{Etat dans le plugin}}</th>
 						<th style="width:30px;">{{Id}}</th>
                        <th style="width:30px;">{{Plugin}}</th>
                        <th style="width:180px;">{{Equipement}}</span></th>
              			<th style="width:50px;">{{Consommation depuis la mise en service}}</span></th>
                        <th style="width:50px;">{{Puissance Instantanée}}</span></th>                        
                		<th style="width:50px;">{{Cosommation sur les 30 derniers jours pendant la période paramétrée}}</span></th>
  						<th style="width:50px;">{{Durée de fonctionnement sur les 30 derniers jours pendant la période paramétrée}}</span></th>
                    </tr>
                </thead>
                <tbody>
				<?php
				//energysaver::createMainEquipement(); Temporaire pour test

              	log::add('energysaver', 'debug', '-- Recherche des équipements eligibles (START) --');
  				$num = 1;
                $plugins = plugin::listPlugin();				
                foreach ($plugins as $plugin) {
                  //log::add('energysaver', 'debug', 'foreach');
                  $plugin_id = $plugin->getId();
                  //log::add('energysaver', 'debug', $plugin_id);
                  foreach (eqLogic::byType($plugin_id) as $eqLogic) {
                    //log::add('energysaver', 'debug', $eqLogic->getName());
                    if ($eqLogic->getIsEnable()) {                      
                      if (energysaver::isEligible($eqLogic)) {
                        //log::add('energysaver', 'debug', 'isEligible ok ');
                      	$cmd_power_value = energysaver::getPowerCmdValue($eqLogic);
                        //log::add('energysaver', 'debug', 'getPowerCmdValue ok');
                        if (is_null($cmd_power_value)) {
                          $cmd_power_value = 'Introuvable';
                        }                        
                       
                        //log::add('energysaver', 'debug', 'cmd_power_value ok');
                        $cmd_consumption_value = energysaver::getCmdConsumptionValue($eqLogic);
                        if (is_null($cmd_consumption_value)) {
                          $cmd_consumption_value = 'Introuvable';
                        }                       
						//log::add('energysaver', 'debug', 'cmd_consumption_value ok');
                         						
                        
                        $cmd_state_duration_30 = energysaver::getStateDuration($eqLogic, 1); // Durée pendant laquel l'équipement était On sur 30 jours durant la période où elle aurait pu être éteinte
						//log::add('energysaver', 'debug', 'cmd_state_duration30 ok');
                        
                        $cmd_avg_power_30 = energysaver::getAveragePower($eqLogic, 30); // Durée pendant laquel l'équipement était On sur 30 jours durant la période où elle aurait pu être éteinte
                       
                        // Equipement déjà géré dans le plugin (crée actif ou pas) ?
                        $eqLogic_id = $eqLogic->getId();
                        $isManaged = energysaver::isManaged($eqLogic_id); // 1 : Yes Enable ; 2 : Yes Disable ; 0 : No
                        if ($isManaged == 1) {
                          $color = 'green';
                        } elseif ($isManaged == 2) {
                          $color = 'grey';
                        } else {
                          $color = 'red';
                        }
                        //log::add('energysaver', 'debug', 'isManaged ok');
                        
                        // Modification de la couleur de la ligne pour appuyer sur le fait qu'il faudrait prendre en charge l'équipempent
                        if ($isManaged != 1 && $cmd_power_value != 'Introuvable' && $cmd_consumption_value != 'Introuvable' && $cmd_state_duration30 != 'Introuvable' && $cmd_state_duration_30 != 'Non historisée' && $cmd_state_duration_30 != 'Aucune activité') {
                          $line_color ='rgba(98, 21, 21, 0.6) !important';
                        } else {
                          $line_color = 'var(--bg-modal-color) !important;';
                        }

                        
                        echo '<tr style="background-color: '.$line_color.'">'
                        //echo '<tr>'
                          . '<td style="text-align:center; height:34px !important;"><input type="checkbox" id="checked_input_' . $num++  . '" data-id="' . $eqLogic_id . '" style="border: 1px solid var(--link-color) !important;" class="form-control"';
                        if ($isManaged != 0) { echo 'checked'; }
                        echo '></td>'
                          . '<td><center>'. energysaver::drawCircle("15px", $color) . '</center></td>'
                            . '<td>' . $eqLogic_id . '</td>'
                            . '<td>' . $plugin_id . '</td>'
                            . '<td>' . $eqLogic->getName() . '</span></td>'
                            . '<td>' . $cmd_consumption_value . '</span></td>'
                            . '<td>' . $cmd_power_value . '</span></td>'                            
                            . '<td>' . $cmd_avg_power_30 . '</span></td>'
                            . '<td>' . $cmd_state_duration_30 . '</span></td>'     
                            . '</tr>';  
                   	  }
                    }
                  }
                }
				log::add('energysaver', 'debug', '-- Recherche des équipements eligibles (END) --');
				?>
                </tbody>
            </table>
        </div>
    </div>
</div>
                  
<script>
      
$("#btSave").click(function() {
	updateManagedEquipements(<?php echo $num ?>);
});

</script>  
  
<?php include_file('desktop', 'energysaver.search', 'js', 'energysaver');?>