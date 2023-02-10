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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-sm-5 control-label">{{Eteindre un équipement qui a été rallumé durant la planification (1 fois)}}</label>
      <div class="col-sm-6">
        <input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_forceOff">
      </div>
    </div>
  </fieldset>

  <fieldset>
    <?php
    for ($n_eq = 1; $n_eq <= 3; $n_eq++) {
    ?>
      <div class="form-group">
        <label class="col-md-2 control-label" style="color: #337ab7">{{Planification n°<?= $n_eq ?>}}</label>
        <div class="col-md-4">
          <?php
          $daycheck = config::byKey('cfg_j1_' . $n_eq, 'energysaver');
          log::add('energysaver', 'debug', 'Aucune configuration pour le lundi de la planification ' . $n_eq . ' -> coche de chaque jour');
          if ($daycheck == '') {
            $check = 'checked';
          }
          ?>
          {{L }}<input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_j1_<?= $n_eq ?>" id="checkbox_j1_<?= $n_eq ?>" <?= $check ?> />
          {{M }}<input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_j2_<?= $n_eq ?>" id="checkbox_j2_<?= $n_eq ?>" <?= $check ?> />
          {{Me }}<input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_j3_<?= $n_eq ?>" id="checkbox_j3_<?= $n_eq ?>" <?= $check ?> />
          {{J }}<input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_j4_<?= $n_eq ?>" id="checkbox_j4_<?= $n_eq ?>" <?= $check ?> />
          {{V }}<input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_j5_<?= $n_eq ?>" id="checkbox_j5_<?= $n_eq ?>" <?= $check ?> />
          {{S }}<input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_j6_<?= $n_eq ?>" id="checkbox_j6_<?= $n_eq ?>" <?= $check ?> />
          {{D }}<input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_j7_<?= $n_eq ?>" id="checkbox_j7_<?= $n_eq ?>" <?= $check ?> />
        </div>


        <div class="col-md-2">
          <center>{{Mode déclencheur}}</center>
        </div>
        <div class="col-md-2">
          <input type="checkbox" onchange="modetrigger()" class="configKey eqLogicAttr" data-l1key="cfg_modetrigger_<?= $n_eq ?>" id="modetrigger_<?= $n_eq ?>" />
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-3">&nbsp;</div>
        <div class="col-md-1">
          <center>{{Heure}}</center>
        </div>
        <div class="col-md-1">
          <center>{{Minute}}</center>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-3 control-label">{{Arrêt des équipements}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Heure/Minutes d'arrêt des équipements}}"></i></sup>
        </label>
        <div class="col-md-1">
          <select class="configKey form-control" data-l1key="cfg_h<?= $n_eq ?>_stop" id="select_h<?= $n_eq ?>_stop" />
          <option value=""></option>
          <?php
          for ($i = 0; $i < 24; $i++) {
            if ($i < 10) {
              $j = '0' . $i;
            } else {
              $j = $i;
            }
            echo '<option value="' . $j . '">' . $j . '</option>';
          }
          ?>
          </select>
        </div>
        <div class="col-md-1">
          <select class="configKey form-control" data-l1key="cfg_m<?= $n_eq ?>_stop" id="select_m<?= $n_eq ?>_stop" />
          <option value=""></option>
          <?php
          for ($i = 0; $i < 60; $i = $i + 5) {
            if ($i < 10) {
              $j = '0' . $i;
            } else {
              $j = $i;
            }

            echo '<option value="' . $j . '">' . $j . '</option>';
          }
          ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-3 control-label">{{Mise en service des équipements}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Heure/Minutes de mise en service des équipements}}"></i></sup>
        </label>
        <div class="col-md-1">
          <select class="configKey form-control" data-l1key="cfg_h<?= $n_eq ?>_start" id="select_h<?= $n_eq ?>_start" />
          <option value=""></option>
          <?php
          for ($i = 0; $i < 24; $i++) {
            if ($i < 10) {
              $j = '0' . $i;
            } else {
              $j = $i;
            }
            echo '<option value="' . $j . '">' . $j . '</option>';
          }
          ?>
          </select>
        </div>
        <div class="col-md-1">
          <select class="configKey form-control" data-l1key="cfg_m<?= $n_eq ?>_start" id="select_m<?= $n_eq ?>_start" />
          <option value=""></option>
          <?php
          for ($i = 0; $i < 60; $i = $i + 5) {
            if ($i < 10) {
              $j = '0' . $i;
            } else {
              $j = $i;
            }

            echo '<option value="' . $j . '">' . $j . '</option>';
          }
          ?>
          </select>
        </div>
      </div>
      <br>
    <?php
    }
    ?>
  </fieldset>
</form>

<?php include_file('desktop', 'energysaver.configuration', 'js', 'energysaver'); ?>
<?php include_file('desktop', 'energysaver', 'css', 'energysaver'); ?>