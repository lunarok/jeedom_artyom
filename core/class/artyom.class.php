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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class artyom extends eqLogic {

  public static $_widgetPossibility = array('custom' => true);

  public static function health() {
    $return = array();
    if (strpos(network::getNetworkAccess('external'),'https') !== false) {
      $https = true;
    } else {
      $https = false;
    }


    $return[] = array(
      'test' => __('HTTPS', __FILE__),
      'result' => ($https) ?  __('OK', __FILE__) : __('NOK', __FILE__),
      'advice' => ($https) ? '' : __('Votre Jeedom ne permet pas le fonctionnement de Artyom sans HTTPS', __FILE__),
      'state' => $https,
    );
    return $return;
  }

  public function preUpdate() {
    if ($this->getConfiguration('keyword') == '') {
      throw new Exception(__('Le mot clef ne peut etre vide',__FILE__));
    }
    if ($this->getConfiguration('keyend') == '') {
      throw new Exception(__('Le mot de fin ne peut etre vide',__FILE__));
    }
  }



  public function postUpdate() {
    $text = $this->getCmd(null, 'tts');
    if (!is_object($text)) {
      $text = new artyomCmd();
      $text->setLogicalId('tts');
      $text->setIsVisible(0);
      $text->setName(__('Message', __FILE__));
    }
    $text->setType('action');
    $text->setSubType('message');
    $text->setEqLogic_id($this->getId());
    $text->save();
  }


  public function toHtml($_version = 'dashboard') {
    $replace = $this->preToHtml($_version);
    if (!is_array($replace)) {
      return $replace;
    }
    $version = jeedom::versionAlias($_version);
    if ($this->getDisplay('hideOn' . $version) == 1) {
      return '';
    }

    foreach ($this->getCmd('info') as $cmd) {
      $replace['#' . $cmd->getLogicalId() . '_history#'] = '';
      $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
      $replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
      $replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
      if ($cmd->getIsHistorized() == 1) {
        $replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
      }
    }

    if (!config::byKey('internalPort')) {
      $url = config::byKey('internalProtocol') . config::byKey('internalAddr') . config::byKey('internalComplement') . '/core/api/jeeApi.php?api=' . config::byKey('api');
    } else {
      $url = config::byKey('internalProtocol') . config::byKey('internalAddr'). ':' . config::byKey('internalPort') . config::byKey('internalComplement') . '/core/api/jeeApi.php?api=' . config::byKey('api');
    }

    $lang = explode('_',config::byKey('language'));

    $replace['#keyend#'] = $this->getConfiguration('keyend');
    $replace['#keyword#'] = $this->getConfiguration('keyword');
    $replace['#jeedom#'] = $url;
    $replace['#lang#'] = $lang[0];
    $replace['#continuous#'] = $this->getConfiguration('continuous');

    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'artyom', 'artyom')));
  }

}

class artyomCmd extends cmd {

  public function preSave() {
    if ($this->getSubtype() == 'message') {
      $this->setDisplay('title_disable', 1);
    }
  }

  public function execute($_options = null) {
    $text = trim($_options['title'] . ' ' . $_options['message']);
    event::add('artyom::stackData', $text);
    /*$('body').on('artyom::stackData', function (_event,param) {
    //var _cmd = JSON.parse(param);
    artyom.say(data);
  });*/
}

}

?>
