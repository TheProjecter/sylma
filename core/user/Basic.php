<?php

namespace sylma\core\user;
use \sylma\core, sylma\storage\fs;

class Basic extends core\module\Argumented implements core\user {

  const NS = 'http://2013.sylma.org/core/user';
  const PUBLIC_ALIAS = 'public';
  const GROUP_TEST = 'test';

  //private $sUser = '';
  private $bValid = false;
  protected $sID = '0';
  //private $sSID = ''; // session ID

  /**
   * Used by @method needProfile()
   */
  private $bProfil = false;
  private $bPrivate = false;

  private $aGroups = array();
  private $cookie;

  private $directory;

  // controler :: create, getDocument, createArgument

  public function __construct(Manager $manager, $sName = '', array $aGroups = array(), $bPrivate = false) {

    $this->setName($sName);
    $this->setNamespace(self::NS);
    $this->setManager($manager);

    $this->setArguments($manager->getArguments());

    /*if ($aOptions) {

    $options = new Arguments($aOptions);
    $this->setOptions($options->getOptions($this->createNode('user')));
    }*/

    $this->setGroups($aGroups);
    $this->setPrivate($bPrivate);
  }

  public function getArgument($sPath, $bDebug = true, $mDefault = null) {

    return parent::getArgument($sPath, $bDebug, $mDefault);
  }

  protected function setName($sName) {

    return $this->sName = $sName;
  }

  public function getName() {

    return $this->sName;
  }

  public function authenticate($sName, $sID, array $aGroups) {

    // Authentication successed !

    $this->loadSettings($sName, $sID, $aGroups);
    $this->isValid(true);

    return $sName;
  }

  protected function isValid($bValid = null) {

    if ($bValid !== null) $this->bValid = $bValid;
    return $this->bValid;
  }

  /**
   * @return bool
   */
  public function logout() {

    if ($this->getCookie()) $this->getCookie()->kill();

    $_SESSION = array();
    // setcookie(session_name(), '', time()-42000, '/');

    return $this->create('redirect', array('/'));
  }

  /**
   * Load the user, from either cookie, session or profil if authentication has been done
   */
  public function load($bRemember = false) {

    $this->buildCookie();

    if ($this->isValid() && $this->getName() && $this->getID()) {

      $this->save($bRemember);
    }
    else if (!$this->loadSession() && !$this->loadCookie()) {

      $this->loadDefault();
    }

    if (\Sylma::isAdmin()) {

      $this->aGroups[] = self::GROUP_TEST;
    }
  }

  /**
   * just authenticated via @method authenticate()
   */
  protected function save($bRemember) {

    //$this->loadProfile();
    $this->setPrivate();

    if ($this->getCookie()) {

      $this->getCookie()->save(array(
        'id' => $this->getID(),
        'name' => $this->getName(),
        'groups' => $this->getGroups(),
      ), $bRemember);
    }
  }

  protected function loadDefault() {

    $manager = $this->getManager();

    $server = $manager->getArgument('server');

    if ($_SERVER['REMOTE_ADDR'] === $server->read('ip')) {

      $options = $server;
    }
    else {

      $options = $manager->getArgument(self::PUBLIC_ALIAS);
    }

    $this->loadSettings($options->read('name'), '0', $options->query('groups'), $options->get('arguments'));
  }

  public function loadPublic() {

    $this->loadSettings($this->getManager()->getArgument(self::PUBLIC_ALIAS));
  }

  protected function loadSettings($sName, $sID, array $aGroups, core\argument $args = null) {

    $this->setName($sName);
    $this->sID = $sID;
    $this->setGroups($aGroups);

    if ($args) {

      $this->setArguments($args);
    }
  }

  protected function setDirectory(fs\directory $dir) {

    $this->directory = $dir;
  }

  public function getDirectory($sPath = '', $bDebug = true) {

    $result = null;

    if ($sPath) {

      $result = $this->getDirectory()->getDistantDirectory($sPath, $bDebug);
    }
    else if (!$result = $this->directory) {

      if (!$this->getName()) {

        $this->throwException(t('Cannot get directory of an unnamed user'));
      }

      $fs = $this->getManager('fs');
      $dir = $fs->getDirectory($this->readArgument('path') . '/' . $this->getName());

      $this->setDirectory($dir);
      $result = $this->getDirectory();
    }

    return $result;
  }

  /**
   * Define if the profil need to be load by main Controler.
   * Used when session is lost, profil cannot be loaded before filesys
   */
  public function needProfile() {

    return $this->bProfil;
  }

  /*
  public function loadProfile() {

    $sProfil = $this->readArgument('profil');
    $dProfil = $this->getDocument($sProfil);

    if (!$dProfil || $dProfil->isEmpty()) {

      $this->log($this->readArgument('path') . '/' . $this->getName());
      $this->log(sprintf('Cannot load profile in @file %s', $this->getDirectory().'/'.$sProfil));
    }
    else {

      $dProfil->addNode('full-name', $dProfil->readByName('first-name') . ' ' . $dProfil->readByName('last-name'));
      //$this->setOptions($dProfil);
    }

    $this->loadGroups();
    $this->saveSession();
  }
  */
/*
  protected function loadGroups() {

    $aGroups = $this->getArgument('authenticated/groups')->query();
    $sUser = $this->getName();

    $oAllGroups = $this->getDocument('/config/groups.xml', MODE_EXECUTION);

    if ($oAllGroups) {

      $oGroups = $oAllGroups->query("group[@owner = '$sUser']/@name | group[member = '$sUser']/@name");
      foreach ($oGroups as $oAttribute) $aGroups[] = $oAttribute->getValue();
    }

    if (Controler::isAdmin()) $aGroups = array_merge($aGroups, $this->getArgument('root/groups')->query());

    $this->setGroups(array_unique($aGroups));
  }
*/
  public function getCookie() {

    return $this->cookie;
  }

  protected function buildCookie() {

    $this->cookie = $this->getManager()->create('cookie', array($this->getManager(), $this->getArgument('cookies')));
  }

  protected function loadCookie() {

    $aGroups = array();
    $sID = $sName = '';

    if ($this->getCookie()) {

      $aContent = $this->getCookie()->getContent();

      if (is_array($aContent)) {

        if (isset($aContent['id'])) $sID = $aContent['id'];
        if (isset($aContent['name'])) $sName = $aContent['name'];
        if (isset($aContent['groups'])) $aGroups = $aContent['groups'];
      }
    }

    $bResult = $sID && $sName;

    if ($bResult) {

      // has cookie

      $this->loadSettings($sName, $sID, $aGroups);
      $this->setPrivate();

      $this->bProfil = true;
    }

    return $bResult;
  }

  protected function loadSession() {

    $sKey = $this->readArgument('session/name');
    $sSession = array_key_exists($sKey, $_SESSION) ? $_SESSION[$sKey] : '';

    if ($sSession) {

      $aSession = unserialize($sSession);

      $this->sID = $aSession[0];
      $this->setName($aSession[1]);
      $this->setGroups($aSession[2]);
      //if ($aSession[2]) $this->setOptions($aSession[2]);
    }

    return $this->getName();
  }

  protected function saveSession() {

    $options = null;
    //if ($this->getOptions()) $options = $this->getOptions()->getDocument();

    $_SESSION[$this->readArgument('session/name')] = serialize(array(
      $this->getID(),
      $this->getName(),
      $this->getGroups(),
      $options));
  }

  /*** Groups ***/

  protected function setGroups(array $aGroups) {

    $this->aGroups = $aGroups;
  }

  public function getGroups() {

    return $this->aGroups;
  }

  public function isMember($mGroup) {

    if (is_array($mGroup)) {

      foreach ($mGroup as $sGroup) if (!$this->isMember($sGroup)) return false;
      return true;
    }
    else {

      return in_array($mGroup, $this->aGroups, true);
    }

    return false;
  }

  public function getMode($sOwner, $sGroup, $sMode, $sSource = '[undefined]') {

    if (\Sylma::read('debug/rights')) return 7;

    $sMode = (string) $sMode;

    // Validity control of the arguments

    if (!$sOwner) {

      $this->throwException(sprintf('Owner not defined in %s', $sSource));
    }

    if (strlen($sMode) < 3 || !is_numeric($sMode)) {

      $this->throwException(sprintf('Invalid mode in %s', $sSource));
    }

    if (!strlen($sGroup)) {

      $this->throwException(sprintf('Group not defined in %s', $sSource));
    }

    $iOwner = intval($sMode{0});
    $iGroup = intval($sMode{1});
    $iPublic = intval($sMode{2});

    if ($iOwner > 7 || $iGroup > 7 || $iPublic > 7) {

      $this->throwException(sprintf('Invalid mode in %s', $sSource));
    }

    // everything is ok
    $iMode = $iPublic;

    if ($sOwner == $this->getName()) $iMode |= $iOwner;
    if ($this->isMember($sGroup)) $iMode |= $iGroup;

    return $iMode;
  }

  protected function getID() {

    return $this->sID;
  }

  protected function setPrivate($bValue = true) {

    $this->bPrivate = $bValue;
  }

  public function isPublic() {

    return !$this->isPrivate();
  }

  public function isPrivate() {

    if (\Sylma::read('debug/rights')) return true;

    return $this->bPrivate;
  }

  public function asArgument() {
/*
    $sName = $this->getName() . ' [' . implode(', ', $this->getGroups()) . ']';

    return $controler->createArgument(array(
      'a' => array(
        '@href' => $this->readArgument('edit') . '/' . $this->getName(),
        '' => $sName,
      ),
    ), \Sylma::read('namespaces/html'));
 */
  }

  public function __toString() {

    return $this->getID();
  }
}
