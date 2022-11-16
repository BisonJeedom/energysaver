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
    	<label class="col-sm-4 control-label" >{{Eteindre un équipement qui a été rallumé durant la planification (1 fois)}}</label>
    	<div class="col-sm-6">
    		<input type="checkbox" class="configKey eqLogicAttr" data-l1key="cfg_forceOff">
    	</div>
    </div>
  
  	<div class="form-group">
  		<label class="col-md-4 control-label" style="color: #337ab7">{{Planification n°1}}</label>
  		<div class="col-md-1"><center>{{Heure}}</center></div>
  		<div class="col-md-1"><center>{{Minute}}</center></div>
  	</div>  
    <div class="form-group">
      <label class="col-md-4 control-label">{{Arrêt des équipements}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Heure/Minutes d'arrêt des équipements}}"></i></sup>
      </label>
      <div class="col-md-1">  
        <select class="configKey form-control" data-l1key="cfg_h1_stop"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 24 ; $i++) {
                if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cfg_m1_stop"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 60 ; $i=$i+5) {
            	if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Mise en service des équipements}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Heure/Minutes de mise en service des équipements}}"></i></sup>
      </label>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cfg_h1_start"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 24 ; $i++) {
                if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cfg_m1_start"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 60 ; $i=$i+5) {
            	if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
    </div>
  </fieldset>
            
  <fieldset>
  	<div class="form-group">
  		<label class="col-md-4 control-label" style="color: #337ab7">{{Planification n°2}}</label>
  		<div class="col-md-1"><center>{{Heure}}</center></div>
  		<div class="col-md-1"><center>{{Minute}}</center></div>
  	</div>  
    <div class="form-group">
      <label class="col-md-4 control-label">{{Arrêt des équipements}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Heure/Minutes d'arrêt des équipements}}"></i></sup>
      </label>
      <div class="col-md-1">  
        <select class="configKey form-control" data-l1key="cfg_h2_stop"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 24 ; $i++) {
                if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cfg_m2_stop"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 60 ; $i=$i+5) {
            	if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Mise en service des équipements}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Heure/Minutes de mise en service des équipements}}"></i></sup>
      </label>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cfg_h2_start"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 24 ; $i++) {
                if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cfg_m2_start"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 60 ; $i=$i+5) {
            	if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
    </div>
  </fieldset>

            
  <fieldset>
  	<div class="form-group">
  		<label class="col-md-4 control-label" style="color: #337ab7">{{Planification n°3}}</label>
  		<div class="col-md-1"><center>{{Heure}}</center></div>
  		<div class="col-md-1"><center>{{Minute}}</center></div>
  	</div>  
    <div class="form-group">
      <label class="col-md-4 control-label">{{Arrêt des équipements}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Heure/Minutes d'arrêt des équipements}}"></i></sup>
      </label>
      <div class="col-md-1">  
        <select class="configKey form-control" data-l1key="cfg_h3_stop"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 24 ; $i++) {
                if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cfg_m3_stop"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 60 ; $i=$i+5) {
            	if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Mise en service des équipements}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Heure/Minutes de mise en service des équipements}}"></i></sup>
      </label>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cfg_h3_start"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 24 ; $i++) {
                if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cfg_m3_start"/>
          <option value=""></option>
          <?php
          	for ($i = 0; $i < 60 ; $i=$i+5) {
            	if ($i < 10) {
                	$j = '0'.$i;
                } else {
                	$j = $i;
                }
            	
            	echo '<option value="'.$j.'">'.$j.'</option>';
            }
          ?>
        </select>
      </div>
    </div>
  </fieldset>
            
            
</form>