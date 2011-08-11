<?php

namespace sylma\storage\fs\basic;
use \sylma\core, \sylma\dom, \sylma\storage\fs;

abstract class Resource {
  
  const NS = 'http://www.sylma.org/storage/fs/basic/resource';
  
  protected $aRights = array();
  
  protected $sPath = '';
  protected $sName = '';
  protected $sFullPath = '';
  
  /**
   * Parent directory
   * @var fs\directory
   */
  protected $parent = null;
  protected $controler = null;
  
  private $bExist = false;
  private $bSecured = false;
  
  public function getControler() {
    
    if ($this->getParent()) return $this->getParent()->getControler();
    return $this->controler;
  }
  
  public function doExist($bExist = null) {
    
    if ($bExist !== null) $this->bExist = $bExist;
    return $this->bExist;
  }
  
  public function getOwner() {
    
    return $this->aRights['owner'];
  }
  
  public function getGroup() {
    
    return $this->aRights['group'];
  }
  
  public function getMode() {
    
    return $this->aRights['mode'];
  }
  
  public function getName() {
    
    return $this->sName;
  }
  
  public function isOwner() {
    
    return \Controler::getUser()->getName() == $this->getOwner();
  }
  
  public function getFullPath() {
    
    return $this->sFullPath;
  }
  
  public function getParents(fs\directory $target = null) {
    
    $parent = $this;
    $aResult = array();
    
    while (($parent = $parent->getParent()) && (!$target || ($parent != $target))) {
      
      array_unshift($aResult, $parent);
    }
    
    if ($target && !$parent) return null;
    else return $aResult;
  }
  
  public function getParent() {
    
    return $this->parent;
  }
  
  protected function getUserMode() {
    
    // if (!array_key_exists('user-mode', $this->aRights)) \Controler::addMessage($this, 'success');
    return $this->aRights['user-mode'];
  }
  
  /**
   * Read or set if resource accesses has ever been loaded
   * 
   * @param bool|null $bSecured if given, the parameter will change to this value
   * @return boolean TRUE if resource has been secured, FALSE elsewhere
   */
  protected function isSecured($bSecured = null) {
    
    if ($bSecured === null) return $this->bSecured;
    else $this->bSecured = $bSecured;
  }
  
  protected function getRights() {
    
    return $this->aRights;
  }
  
  /**
   * Put all rights into object
   * @param array|DOMElement|null $mRights Rights to use
   * @return array Rights used
   */
  protected function setRights(array $aRights = array()) {
    
    if (!$aRights) {
      
      $aRights = array(
        'owner' => $this->getOwner(),
        'group' => $this->getGroup(),
        'mode' => $this->getMode(),
        'user-mode' => $this->getUserMode());
      
      if (\Controler::getUser()) {
        
        $aRights['user-mode'] = \Controler::getUser()->getMode(
          $aRights['owner'],
          $aRights['group'],
          $aRights['mode']
        );
      }
    }
    
    $this->aRights = $aRights;
    $this->isSecured(true);
    
    return $aRights;
  }
  /**
   * Check rights arguments for update in @method updateRights()
   */
  protected function checkRightsArguments($sOwner, $sGroup, $sMode) {
    
    if ($this->isOwner()) {
      
      $bOwner = $sOwner !== $this->getOwner();
      $bGroup = $sGroup !== $this->getGroup();
      $bMode  = $sMode !== $this->getMode();
      
      if ($bOwner || $bGroup || $bMode) {
        
        $bResult = true;
        
        // Check validity
        
        if ($bOwner) {
          
          $bOwner = false;
          dspm(t('Changement d\'utilisateur impossible pour le moment'), 'file/warning');
        }
        
        if ($bGroup && !\Controler::getUser()->isMember($sGroup)) {
          
          $bResult = false;
          dspm(t('Vous n\'avez pas les droits sur ce groupe ou il n\'existe pas !'), 'file/warning');
        }
        
        $iMode = \Controler::getUser()->getMode($sOwner, $sGroup, $sMode);
        
        if ($bMode && $iMode === null) {
          
          $bResult = false;
          dspm(t('Les arguments pour la mise-à-jour ne sont pas valides'), 'file/warning');
        }
        
        if ($bMode && !($iMode & MODE_READ)) {
          
          $bResult = false;
          dspm(t('Vous ne pouvez pas retirer tous les droits de lecture'), 'file/warning');
        }
        
        // all datas are ok, or not modified
        
        if ($bResult && ($bOwner || $bGroup || $bMode)) return true;
      }
      
    } else dspm('Vous n\'avez pas les droits pour faire des modifications !', 'file/warning');
    
    return false;
  }
  
  protected function log($sMessage, $sStatut = Sylma::LOG_STATUT_DEFAULT) {
    
    $aPath = array(
      '@namespace ' . self::NS,
      '@path ' . $this->getFullPath(),
    );
    
    return Sylma::log($aPath, $mMessage, $sStatut);
  }
  
  public function __toString() {
    
    return $this->getFullPath();
  }
}


