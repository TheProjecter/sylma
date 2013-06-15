<?php

namespace sylma\modules\tester;
use sylma\core, sylma\dom, sylma\modules\tester, sylma\storage\fs;

class Parser extends tester\Prepare {

  protected static $sArgumentClass = 'sylma\core\argument\Readable';

  const NS = 'http://www.sylma.org/modules/tester/parser';
  const TRASH_MANAGER = 'fs/trash';

  protected $exportDirectory;

  public function __construct() {

    $this->setNamespace(self::NS, self::PREFIX);

    $this->setControler($this);

    $this->exportDirectory = $this->loadCacheDirectory($this->getDirectory());

    $this->setArguments(array());
    $this->setSettings($this->getArguments());
    //$this->setFiles(array($this->getFile('basic.xml')));
  }

  protected function loadCacheDirectory(fs\directory $dir) {

    $cache = $this->getManager('fs/cache');
    $result = $cache->getDirectory()->addDirectory((string) $dir);

    return $result;
  }

  public function getExportDirectory() {

    if (!$this->exportDirectory) {

      $this->throwException('No export directory');
    }

    return $this->exportDirectory;
  }

  public function setExportDirectory(fs\directory $exportDirectory) {

    $this->exportDirectory = $exportDirectory;
  }

  protected function parseResult(dom\element $test, fs\file $file, array $aArguments = array()) {

    $result = null;

    if ($document = $test->getx('self:document', array(), false)) {

      require_once('core/functions/Path.php');
      $sName = core\functions\path\urlize($file->getName() . '-' . $test->readx('@name'));

      $cache = $this->getExportDirectory()->createFile($sName);
      $cache->saveText((string) $this->createDocument($document->getFirst()));

      $manager = $this->getManager(self::PARSER_MANAGER);
      $result = $this->buildResult($test, $manager, $cache, $aArguments);
    }

    $this->set('result', $result);

    return $result;
  }

  protected function buildResult(dom\element $test, $manager, fs\file $file, array $aArguments) {

    $manager->build($file, $this->getDirectory());

    if ($sLoad = $test->readx('self:load', array(), false)) {

      if (is_null(eval('$closure = function($manager) { ' . $sLoad . '; };'))) {

        $aArguments = $this->evaluate($closure, $this);
      }
    }

    return $this->loadResult($manager, $file, $aArguments, !$test->getx('self:expected', array(), false));
  }

  protected function loadResult($manager, fs\file $file, array $aArguments, $bDelete = true) {

    $this->setFile($file);

    $result = $manager->load($file, $aArguments, false);
    if ($bDelete) $file->delete();

    return $result;
  }

  protected function loadResultNode(dom\element $test) {

    $this->setArgument('node', null);

    foreach($test->queryx('self:node', array(), false) as $node) {

      if ($sName = $node->readx('@name', array(), false)) $sName = "node/$sName";
      else $sName = 'node';

      if ($node->countChildren() > 1) {

        $this->set($sName, $node->getChildren());
      }
      else {

        $this->set($sName, $node->getFirst());
      }

    }
  }

  protected function test(dom\element $test, $sContent, $controler, dom\document $doc, fs\file $file) {

    try {

      $this->parseResult($test, $file);
    }
    catch (core\exception $e) {

      $e->addPath('Test ID : ' . $test->readx('@name'));
      $e->addPath($file->asToken());

      $e->save(false);

      return false;
    }

    $this->loadResultNode($test);

    return parent::test($test, $sContent, $controler, $doc, $file);
  }

  public function loadScript(array $aArguments = array(), array $aPosts = array(), array $aContexts = array()) {

    return $this->getScriptFile($this->getFile(), $this->buildScriptArguments($aArguments, $aContexts, $aPosts));
  }

  public function getScript($sPath, array $aArguments = array(), array $aContexts = array(), array $aPosts = array()) {

    return $this->getManager(self::PARSER_MANAGER)->load($this->getFile($sPath), $this->buildScriptArguments($aArguments, $aContexts, $aPosts), true);
  }

  public function set($sPath, $mValue = null) {

    return parent::set($sPath, $mValue);
  }

  public function get($sPath, $bDebug = true) {

    return parent::get($sPath, $bDebug);
  }

  public function read($sPath, $bDebug = true) {

    return parent::read($sPath, $bDebug);
  }

  public function getArgument($sPath, $bDebug = true, $mDefault = null) {

    return $this->get($sPath, $bDebug);
  }

  public function readArgument($sPath, $bDebug = true, $mDefault = null) {

    return $this->read($sPath, $bDebug);
  }

  public function setArgument($sPath, $mValue) {

    return $this->set($sPath, $mValue);
  }
}

