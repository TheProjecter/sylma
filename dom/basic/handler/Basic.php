<?php

namespace sylma\dom\basic\handler;
use \sylma\dom, \sylma\storage\fs, \sylma\core;

/**
 * Existenz of this class mainly due to https://bugs.php.net/bug.php?id=28473
 * Allow too extension of document methods with others arguments
 */
abstract class Basic extends core\module\Controled implements dom\handler {

  /**
   * See @method setFile()
   * @var fs\file
   */
  private $file;

  protected $aClasses = array();

  /**
   * See @method getMode() for details
   * @var integer
   */
  private $iMode = null;

  public function __construct($mContent = '', $iMode = \Sylma::MODE_READ, array $aNamespaces = array()) {

    $controler = \Sylma::getControler('dom');

    $this->setControler($controler);
    $this->setMode($iMode);

    $this->setDocument($controler->create('document'));

    $this->registerClasses();
    $this->registerNamespaces($aNamespaces);

    $this->setFragment($this->getDocument()->createDocumentFragment());

    if ($mContent) {

      if (is_object($mContent)) $this->set($mContent);
      else if (is_string($mContent)) $this->startString($mContent);
    }
  }

  public function getType() {

    return self::HANDLER;
  }

  public function getDocument() {

    return $this->document;
  }

  public function getParent() {

    return null;
  }

  protected function setDocument(dom\document $doc) {

    $doc->setHandler($this);
    $this->document = $doc;
  }

  private function setMode($iMode) {

    $aModes = array(\Sylma::MODE_EXECUTE, \Sylma::MODE_WRITE, \Sylma::MODE_READ);

    if (in_array($iMode, $aModes)) $this->iMode = $iMode;
  }

  public function getMode() {

    return $this->iMode;
  }

  public function startString($sValue) {

    $bResult = false;

    if ($sValue{0} == '/') {

      $fs = \Sylma::getControler('fs');
      $file = $fs->getFile($sValue);

      $this->setFile($file);
      $bResult = $this->loadFile();
    }
    else if ($sValue{0} == '<') {

      $bResult = $this->loadText($sValue);
    }
    else {

      $bResult = (bool) $this->set($this->createElement($sValue, '', array(), '', $this));
    }

    return $bResult;
  }

  public function registerNamespaces(array $aNS = array()) {

    $this->setNamespaces($aNS);
  }

  public function registerClasses(core\argument $settings = null) {

    $aClasses = $this->getControler()->getClasses($settings);

    foreach ($aClasses as $sOrigin => $sReplacement) {

      $this->getDocument()->registerNodeClass($sOrigin, $sReplacement);
    }
  }

  public function setFile(fs\file $file) {

    $this->file = $file;
  }

  public function getFile() {

    return $this->file;
  }

  public function loadFile($bSecured = true) {

    $sContent = '';

    if (!$this->getFile()) {

      $this->throwException(t('No file associated'));
    }

    if ($bSecured) {

      if ($this->getMode() == \Sylma::MODE_READ) $sContent = $this->getFile()->read();
      else if ($this->getMode() == \Sylma::MODE_EXECUTE) $sContent = $this->getFile()->execute();
    }
    else {

      $sContent = $this->getFile()->freeRead();
    }

    return $this->loadText($sContent);
  }

  public function loadText($sContent) {

    try {

      $bResult = $this->document->loadXML($sContent);
    }
    catch (core\exception $e) {

      $this->throwException($e->getMessage());
    }

    return $bResult;
  }

  public function mergeNamespaces(array $aNamespaces = array()) {

    return parent::mergeNamespaces($aNamespaces);
  }

  protected function parseNamespaces($sContent) {

    $reader = new \XMLReader;
    $reader->XML($sContent);

    $aNS = $this->lookupNamespaces($reader);
    $this->registerNamespaces($aNS);
  }

  private function lookupNamespaces(\XMLReader $reader) {

    $aNS = array();

    while ($reader->read()) {

      switch ($reader->nodeType) {

        // case \XMLReader::NONE : break;
        case \XMLReader::ELEMENT :

          $aNS[$reader->prefix] = $reader->namespaceURI;

          if($reader->hasAttributes) {

            while($reader->moveToNextAttribute()) {

              $aNS[$reader->prefix] = $reader->namespaceURI;
            }
          }

          if (!$reader->isEmptyElement) {

            $aNS = array_merge($aNS, $this->lookupNamespaces($reader));
          }

        break;
        // case \XMLReader::ATTRIBUTE : break;
        // case \XMLReader::TEXT : break;
        case \XMLReader::END_ELEMENT : //dspf($reader->expand(new \XML_Element)); break 2;
        // case \XMLReader::XML_DECLARATION : break;
      }
    }

    return $aNS;
  }

  public function asToken() {

    if ($this->getFile()) $sResult = '@file ' . $this->getFile();
    else $sResult = '@file [unknown]';

    return $sResult;
  }

  public function asArgument() {

    $dom = $this->getControler();
    $content = null;

    if (!$this->isEmpty()) {

      // copy handler for display updates (add of whitespaces)
      $copy = $dom->create('handler', array($this));
      $copy->getRoot()->prepareHTML();

      $content = $copy->getContainer()->saveXML(null);
    }

    return $dom->createArgument(array(
        'handler' => array(
            '@class' => get_class($this),
            'content' => $this->getDocument(),
        ),
    ), $dom->getNamespace());
  }

  public function elementAsString(dom\node $el = null, $iMode = 0) {

    if (!$sResult = $this->getContent()) {

      $doc = $this->getContainer();

      if ($el) $sResult = $doc->saveXML($el);
      else $sResult = $doc->saveXML();
    }

    return trim($sResult);
  }

  public function asString($iMode = 0) {

    if ($iMode & self::STRING_INDENT) {

      $doc = new static($this->getRoot());
      $doc->getRoot()->prepareHTML();
    }
    else {

      $doc = $this;
    }

    if ($iMode & self::STRING_HEAD) $sResult = $doc->elementAsString();
    else $sResult = $doc->elementAsString($doc->getRoot());

    return $sResult;
  }

  public function throwException($sMessage, $mSender = array(), $iOffset = 2) {

    $mSender = (array) $mSender;

    $mSender[] = '@namespace ' . self::NS;
    $mSender[] = $this->asToken();

    $dom = $this->getControler();
    \Sylma::throwException($sMessage, $mSender, $iOffset);
  }

  public function saveFile(fs\editable\file $file, $bFormat = false) {

    if ($this->isEmpty()) {

      $this->throwException(sprintf('You cannot save empty document in %s', $file->asToken()));
    }

    if ($bFormat) $this->getRoot()->prepareHTML();
    $file->saveText($this->asString(self::STRING_HEAD));
  }

  public function dsp() {

    dspm($this->asString());
  }
}