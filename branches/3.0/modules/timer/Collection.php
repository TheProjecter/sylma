<?php

namespace sylma\modules\timer;
use sylma\core;

require_once('core/module/Namespaced.php');

class Collection extends core\module\Namespaced {

  protected $aStack = array();
  protected $aClasses = array();

  public function open($sMethod) {

    list($sClass, $sMethod) = explode('::', $sMethod);

    if (!array_key_exists($sClass, $this->aClasses)) $this->aClasses[$sClass] = array();
    if (!array_key_exists($sMethod, $this->aClasses[$sClass])) $this->aClasses[$sClass][$sMethod] = array('@name' => $sMethod, 'calls' => 0, 'time' => 0);

    $aMethod =& $this->aClasses[$sClass][$sMethod];
    $aMethod['calls']++;

    $this->aStack[] = array($sClass, $sMethod, microtime(true));
  }

  public function close() {

    if (!count($this->aStack)) return;
    list($sClass, $sMethod, $iCurrent) = array_pop($this->aStack);

    $aMethod =& $this->aClasses[$sClass][$sMethod];

    $aMethod['time'] = $aMethod['time'] + (microtime(true) - $iCurrent);
  }

  public function parse() {

    $aResult = array();

    foreach ($this->aClasses as $sClass => $aClass) {

      $aResult['#class'][] = array('@name' => $sClass, '#method' => $aClass);
    }

    require_once('core/settings/Arguments.php');
    require_once('Controler.php');

    $result = \Arguments::buildDocument(array('classes' => $aResult), Controler::NS);

    return $result;
  }
}



