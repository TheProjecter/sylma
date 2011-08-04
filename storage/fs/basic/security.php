<?php

namespace \sylma\storage\fs\basic
use \sylma\core, \sylma\dom, \sylma\storage\fs;

class security extends ModuleBase {
  
  const NAME = 'directory.sml';
  
  private $doc;
  private $directory;
  
  public function __construct(fs\directory $directory) {
    
    $this->directory = $directory;
    $sPath = $directory->getFullPath() . '/' . self::NAME;
    
    if (file_exists(Sylma::ROOT . $sPath)) {
      
      $this->doc = new dom\document;
      $this->getDocument()->loadFreeFile($sPath);
    }
  }
  
  public function getDocument() {
    
    return $this->doc;
  }
  
  public function getParent() {
    
    return $this->oDirectory;
  }
  
  public function build() {
    
    if ($this->getDocument()) dspm(xt('Le fichier de sécurité dans %s existe déjà', $this->getParent()), 'file/error');
    else {
      
      $doc = new XML_Document;
      $doc->addNode('directory', null, null, SYLMA_NS_DIRECTORY);
      
      $this->getParent()->addFreeDocument(SYLMA_SECURITY_FILE, $doc);
      
      $this->doc = $doc;
    }
  }
  
  public function getDirectory() {
    
    if ($this->getDocument()) return $this->getDocument()->getByName('self', SYLMA_NS_DIRECTORY);
    else return null;
  }
  
  public function getPropagation() {
    
    if ($this->getDocument()) return $this->getDocument()->getByName('propagate', SYLMA_NS_DIRECTORY);
    else return null;
  }
  
  public function getFile($sName) {
    
    if ($this->getDocument()) return $this->getDocument()->get('ld:file[@name="'.xmlize($sName).'"]', 'ld', SYLMA_NS_DIRECTORY);
    else return null;
  }
  
  public function updateFileName($sName, $sNewName) {
    
    $bResult = null;
    
    if ($nFile = $this->getFile($sName)) {
      
      $nFile->setAttribute('name', $sNewName);
      $bResult = $this->save();
    }
    
    return $bResult;
  }
  
  public function updateFile($sName, $sOwner, $sGroup, $sMode) {
    
    if ($nFile = $this->getFile($sName)) $nFile->remove();
    else if (!$this->getDocument()) $this->build();
    
    $nFile = new XML_Element('file', 
      new XML_Element('ls:security', array(
          new XML_Element('ls:owner', $sOwner, null, SYLMA_NS_SECURITY),
          new XML_Element('ls:group', $sGroup, null, SYLMA_NS_SECURITY),
          new XML_Element('ls:mode', $sMode, null, SYLMA_NS_SECURITY)),
        null, SYLMA_NS_SECURITY),
      array('name' => $sName), SYLMA_NS_DIRECTORY);
    
    $this->getDocument()->add($nFile);
    
    return $this->save();
  }
  
  public function deleteFile($sName) {
    
    $bResult = null;
    
    if ($nFile = $this->getFile($sName)) {
      
      $nFile->remove(); // TODO check if empty
      $bResult = $this->save();
    }
    
    return $bResult;
  }
  
  public function updateDirectory($sOwner, $sGroup, $sMode) {
    
    if ($nDirectory = $this->getDirectory()) $nDirectory->remove();
    else if (!$this->getDocument()) $this->build();
    
    $nDirectory = new XML_Element('self', 
      new XML_Element('ls:security', array(
          new XML_Element('ls:owner', $sOwner, null, SYLMA_NS_SECURITY),
          new XML_Element('ls:group', $sGroup, null, SYLMA_NS_SECURITY),
          new XML_Element('ls:mode', $sMode, null, SYLMA_NS_SECURITY)),
        null, SYLMA_NS_SECURITY), SYLMA_NS_DIRECTORY);
    
    $this->getDocument()->add($nDirectory);
    
    return $this->save();
  }
  
  private function save() {
    
    if ($this->getDocument()) {
      
      if ($this->getDocument()->getRoot()->hasChildren()) return $this->getDocument()->saveFree($this->getParent(), SYLMA_SECURITY_FILE);
      else unlink(MAIN_DIRECTORY.$this->getParent()->getFullPath().'/'.SYLMA_SECURITY_FILE);
      
    } else return null;
  }
}

