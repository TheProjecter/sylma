<?php

namespace sylma\parser;
use sylma\core, sylma\storage\fs, sylma\parser;

class Manager extends parser\compiler\Builder {

  const MANAGER_PATH = 'manager';
  const REFLECTOR_PATH = 'reflector';
  const CACHED_PATH = 'cached';

  protected $aNamespaces = array();

  protected $aParserManagers = array();
  protected $aReflectors = array();

  protected $aContexts = array();

  public function __construct() {

    $this->setDirectory(__FILE__);
    $this->setArguments('manager.yml');

    $this->loadNamespaces($this->getArgument('namespaces'));

  }

  protected function loadNamespaces(core\argument $namespaces) {

    $this->aNamespaces = $namespaces->asArray();
  }

  public function load(fs\file $file, array $aArguments = array()) {

    return parent::load($file, $aArguments);
  }

  public function build(fs\file $file, fs\directory $dir) {

    if (!\Sylma::read('debug/enable')) {

      $this->throwException('This function is low performance and must not be used in production environnement');
    }

    $doc = $file->getDocument();
    $manager = $this->getParserManager($doc->getRoot()->getNamespace());

    return $manager->build($file, $dir);
  }

  /**
   *
   * @param type $sNamespace
   * @param boolean $bDebug
   * @return \sylma\parser\compiler\Manager
   */
  public function getParserManager($sNamespace, $bDebug = true) {

    $result = null;

    if (array_key_exists($sNamespace, $this->aParserManagers)) {

      $result = $this->aParserManagers[$sNamespace];
    }
    else if (array_key_exists($sNamespace, $this->aNamespaces[self::MANAGER_PATH])) {

      $sClass = $this->aNamespaces[self::MANAGER_PATH][$sNamespace];
      $result = $this->createParser($sClass, array($this->findClass($sClass)));
    }
    else if ($bDebug) {

      $this->throwException(sprintf('No manager parser associated to namespace %s', $sNamespace));
    }

    return $result;
  }

  public function getCachedParser($sNamespace, $parent, $bDebug = true) {

    if (array_key_exists($sNamespace, $this->aNamespaces[self::CACHED_PATH])) {

      $sClass = $this->aNamespaces[self::CACHED_PATH][$sNamespace];
      $result = $this->createParser($sClass, array($parent));
    }
    else if ($bDebug) {

      $this->throwException(sprintf('No cached parser associated to namespace %s', $sNamespace));
    }

    return $result;
  }

  /**
   *
   * @param string $sNamespace
   * @param unknown $parent TODO : set type (between cached parser and reflector\elemented)
   * @param boolean $bDebug
   * @return \sylma\parser\reflector\container
   */
  public function getParser($sNamespace, parser\reflector\documented $documented, parser\reflector\domed $parent = null, $bDebug = true) {

    $result = null;

    if (0 && array_key_exists($sNamespace, $this->aReflectors)) {

      $result = $this->aReflectors[$sNamespace];
    }
    else if (array_key_exists($sNamespace, $this->aNamespaces[self::REFLECTOR_PATH])) {

      $sClass = $this->aNamespaces[self::REFLECTOR_PATH][$sNamespace];
      $result = $this->createParser($sClass, array($this, $documented, $parent, $this->findClass($sClass)));
    }
    else if ($bDebug) {

      $this->throwException(sprintf('No elemented parser associated to namespace %s', $sNamespace));
    }

    return $result;
  }

  protected function findClass($sPath) {

    return $this->getFactory()->findClass($sPath);
  }

  protected function createParser($sAlias, array $aArguments = array()) {

    $result = $this->create($sAlias, $aArguments);

    return $result;
  }

  public function setContext($sName, $context) {

    $this->aContexts[$sName] = $context;
  }

  /**
   *
   * @param type $sName
   * @param type $bLoad
   * @return \sylma\parser\context|\sylma\parser\handler
   */
  public function getContext($sName, $bLoad = true) {

    $result = null;

    if (!array_key_exists($sName, $this->aContexts)) {

      if ($bLoad) {

        $result = $this->create($sName);
        $this->setContext($sName, $result);
      }
    }
    else {

      $result = $this->aContexts[$sName];
    }

    return $result;
  }
}