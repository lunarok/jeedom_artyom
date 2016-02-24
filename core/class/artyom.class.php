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

  public static function cronHourly() {
      foreach (eqLogic::byType('artyom', true) as $artyom) {
        $mc = cache::byKey('artyomWidgetdashboard' . $artyom->getId());
        $mc->remove();
        $artyom->toHtml('dashboard');
        $mc = cache::byKey('artyomWidgetmobile' . $artyom->getId());
        $mc->remove();
        $artyom->toHtml('mobile');
        $artyom->refreshWidget();
      }
  }

  public function postUpdate() {
      foreach (eqLogic::byType('artyom', true) as $artyom) {
        $mc = cache::byKey('artyomWidgetdashboard' . $artyom->getId());
        $mc->remove();
        $artyom->toHtml('dashboard');
        $mc = cache::byKey('artyomWidgetmobile' . $artyom->getId());
        $mc->remove();
        $artyom->toHtml('mobile');
        $artyom->refreshWidget();
      }

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

    $mc = cache::byKey('artyomWidget' . $_version . $this->getId());
    if ($mc->getValue() != '') {
      return $mc->getValue();
    }
    if ($this->getIsEnable() != 1) {
            return '';
        }
        if (!$this->hasRight('r')) {
            return '';
        }
        $_version = jeedom::versionAlias($_version);
        if ($this->getDisplay('hideOn' . $_version) == 1) {
            return '';
        }
        $vcolor = 'cmdColor';
        if ($_version == 'mobile') {
            $vcolor = 'mcmdColor';
        }
        $parameters = $this->getDisplay('parameters');
        $cmdColor = ($this->getPrimaryCategory() == '') ? '' : jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
        if (is_array($parameters) && isset($parameters['background_cmd_color'])) {
            $cmdColor = $parameters['background_cmd_color'];
        }

        if (($_version == 'dview' || $_version == 'mview') && $this->getDisplay('doNotShowNameOnView') == 1) {
            $replace['#name#'] = '';
            $replace['#object_name#'] = (is_object($object)) ? $object->getName() : '';
        }
        if (($_version == 'mobile' || $_version == 'dashboard') && $this->getDisplay('doNotShowNameOnDashboard') == 1) {
            $replace['#name#'] = '<br/>';
            $replace['#object_name#'] = (is_object($object)) ? $object->getName() : '';
        }

        if (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $replace['#' . $key . '#'] = $value;
            }
        }

        if (!config::byKey('internalPort')) {
          $url = config::byKey('internalProtocol') . config::byKey('internalAddr') . config::byKey('internalComplement') . '/core/api/jeeApi.php?api=' . config::byKey('api');
        } else {
          $url = config::byKey('internalProtocol') . config::byKey('internalAddr'). ':' . config::byKey('internalPort') . config::byKey('internalComplement') . '/core/api/jeeApi.php?api=' . config::byKey('api');
        }

        $lang = explode('_',config::byKey('language'));

        $replace = array(
            		'#name#' => $this->getName(),
                	'#id#' => $this->getId(),
                  '#keyend#' => $this->getConfiguration('keyend'),
                  '#keyword#' => $this->getConfiguration('keyword'),
                  '#jeedom#' => $url,
                  '#lang#' => $lang[0],
                  '#continuous#' => $this->getConfiguration('continuous'),
                	'#background_color#' => $this->getBackgroundColor(jeedom::versionAlias($_version)),
                	'#eqLink#' => ($this->hasRight('w')) ? $this->getLinkToConfiguration() : '#',
            	);

      $html = template_replace($replace, getTemplate('core', $_version, 'artyom', 'artyom'));
      cache::set('artyomWidget' . $_version . $this->getId(), $html, 0);
      return $html;
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
