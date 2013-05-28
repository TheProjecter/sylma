<?php

namespace sylma\parser\languages\php\basic;
use sylma\core, sylma\parser\languages\common, sylma\parser\languages\php, sylma\dom;

class Condition extends common\basic\Structured implements common\argumentable, common\scope, common\structure {

  protected $aElse = array();
  protected $test;

  public function __construct(common\_window $controler, $test = null, $content = null) {

    $this->setControler($controler);
    if ($test) $this->setTest($test);

    if ($content) $this->addContent($content);
  }

  public function addElse($mVal) {

    $this->aElse[] = $this->addToContent($this->aElse, $mVal);
  }

  protected function getElse() {

    return $this->aElse;
  }

  public function getTest() {

    return $this->test;
  }

  public function setTest($test) {

    $this->test = $test;
  }

  public function _onAdd() {

    $window = $this->getWindow();

    $window->loadContent($this->getTest());
    $window->loadContent($this->getContent());
    $window->loadContent($this->getElse());
  }

  public function asArgument() {

    $sArgument = null;
    $window = $this->getControler();

    if ($this->useTemplate()) {

      $insert = $window->createInsert($window->argToInstance(true), '', null, false);
      $sArgument = $insert->getKey();
      $this->addContent($insert);
      $window->add($window->createInsert($window->argToInstance(false), '', $insert->getKey(), false));
    }

    return $this->getControler()->createArgument(array(
       'condition' => array(
         //'@context' => $window->getContext(),
         '@argument' => $sArgument,
         'test' => $this->test,
         'content' => $this->getContent(),
         'else' => $this->aElse ? $this->aElse : null,
       )
    ));
  }
}