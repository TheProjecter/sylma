<?php

namespace sylma\core\module;
use \sylma\core;

class Managed extends Controled {

  protected function setManager($manager, $sName = '') {

    return parent::setControler($manager, $sName);
  }

  protected function getManager($sName = '', $bDebug = true) {

    return parent::getControler($sName, $bDebug);
  }
}
