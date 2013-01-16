<?php

namespace sylma\parser\languages\php\basic;
use \sylma\core, \sylma\parser\languages\common, \sylma\parser\languages\php;

abstract class _Var extends common\basic\Controled implements common\_var {

  private $sName = '';
  protected $instance;

  protected $bInserted = false;
  protected $bStatic = false;

  protected $content;

  public function __construct(php\window $controler, common\_instance $instance, $sName, common\argumentable $content) {

    $this->setName($sName);
    $this->setControler($controler);
    $this->setInstance($instance);
    $this->setContent($content);
  }

  public function getInstance() {

    return $this->instance;
  }

  protected function setContent(common\argumentable $content) {

    $this->content = $content;
  }

  protected function getContent() {

    return $this->content;
  }

  public function insert(common\argumentable $content = null) {

    $window = $this->getControler();

    if (!$content && !$this->getContent()) {

      $window->throwException(sprintf('Variable "%s" cannot be inserted, no content defined', $this->getName()));
    }

    if (!$this->bInserted || $content) {

      if (!$content) $content = $this->getContent();

      $assign = $window->createAssign($this, $content);
      $window->add($assign);

      $this->bInserted = true;
    }
  }

  public function isStatic($bValue = null) {

    if (!is_null($bValue)) $this->bStatic = $bValue;
    return $this->bStatic;
  }

  protected function setInstance(common\_instance $instance) {

    $this->instance = $instance;
  }

  protected function setName($sName) {

    $this->sName = $sName;
  }

  public function getName() {

    return $this->sName;
  }

  protected function checkInserted() {

    if (!$this->isStatic() && !$this->bInserted && $this->getContent()) {

      $this->getControler()->throwException(sprintf('Variable "%s" has not been inserted', $this->getName()));
    }
  }

  public function asArgument() {

    $this->checkInserted();

    return $this->getControler()->createArgument(array(
      'var' => array(
        '@name' => $this->sName,
      ),
    ));
  }
}