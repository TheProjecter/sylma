<?php

namespace sylma\parser\languages\php\basic;
use \sylma\core, \sylma\parser\languages\common, \sylma\parser\languages\php;

class CallMethod extends Called implements common\varable  {

  private $called;
  protected $bStatic = false;

  public function __construct(common\_window $controler, $called, $sMethod, common\_instance $return, array $aArguments = array()) {

    $this->setControler($controler);

    $this->setCalled($called);
    $this->setName($sMethod);

    $this->setReturn($return);
//dspf($aArguments, 'error');
    $this->setArguments($this->parseArguments($aArguments));
  }

  protected function setCalled($called) {

    if ($called instanceof self) {

      $this->called = $called;
    }
    else if ($called instanceof common\varable) {

      $this->called = $this->loadVarable($called);
    }
    else if ($called instanceof common\_object) {

      if ($called instanceof php\basic\instance\_Class) {

        $this->isStatic(true);
      }

      $this->called = $called;
    }
    else {

      $this->getControler()->throwException(sprintf('Cannot call object of type %s', \Sylma::show($called)));
    }
  }

  public function isStatic($bStatic = null) {

    if (!is_null($bStatic)) $this->bStatic = $bStatic;
    return $this->bStatic;
  }

  public function asArgument() {

    return $this->getControler()->createArgument(array(
      'call-method' => array(
          '@name' => $this->getName(),
          '@static' => $this->isStatic() ? true : null,
          'called' => $this->called,
          '#argument' => $this->getArguments(),
      ),
    ));
  }
}