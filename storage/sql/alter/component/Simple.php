<?php

namespace sylma\storage\sql\alter\component;
use sylma\core, sylma\schema\xsd as schema;

class Simple extends schema\component\SimpleType {

  public function asString() {

    if ($this->doExtends($this->getParser()->getType('string', $this->getNamespace('self')))) {

      $iSize = $this->getDefine()->getRule('maxLength');

      if (!$iSize) {
dsp($this->getDefine());
dsp($this->getNode());
        $this->launchException('No maxLength defined');
      }

      if ($iSize > 255) {

        $sValue = 'TEXT';
      }
      else {

        $sValue = "VARCHAR({$iSize})";
      }
    }
    else {

      $iSize = 11;
      $sValue = "INT({$iSize})";
    }

    return $sValue;
  }
}

