<?php

namespace sylma\modules\stepper;
use sylma\core, sylma\dom, sylma\storage\fs;

class Browser extends core\module\Domed {

  const FACTORY_RELOAD = false;
  const FILE_MANAGER = 'fs/editable';

  const NS = 'http://2013.sylma.org/modules/stepper';

  public function __construct(core\argument $args, core\argument $post) {

    //$this->setDirectory(__DIR__);
    $this->setNamespace(self::NS);
    $this->loadDefaultSettings();

    $this->setSettings($post);
    $this->setSettings($args);

    if ($sDirectory = $this->read('dir', false)) {

      $this->setDirectory($this->getManager(self::FILE_MANAGER)->getDirectory($sDirectory));
    }
  }

  public function getDirectory($sPath = '', $bDebug = false) {

    return parent::getDirectory($sPath, $bDebug);
  }

  public function getCollection($bDebug = false) {

    return $this->read('file', $bDebug);
  }

  public function getItems() {

    if ($this->getCollection(false)) {

      $aResult = $this->getDirectories();
    }
    else {

      $aResult = $this->getTests();
    }

    return $aResult;
  }

  public function getTests() {

    $aResult = array();

    $args = $this->createArgument(array(
      'extensions' => array('tml'),
    ));

    $iBase = strlen((string) $this->getDirectory()) + 1;

    foreach ($this->getDirectory('', true)->browse($args, false) as $file) {

      $aResult['test'][] = array(
        'file' => substr($file, $iBase),
      );
    }

    return $aResult;
  }

  public function getDirectories() {

    $aResult = array();
    $file = $this->getManager(self::FILE_MANAGER)->getFile($this->getCollection());
    $collection = $this->createOptions($file->asDocument());

    $this->setDirectory($file->getParent());

    foreach ($collection as $dir) {

      $aResult['directory'][] = array(
        'path' => (string) $this->getDirectory($dir->read('@path'), true),
      );
    }

    return $aResult;
  }

  public function loadTest() {

    $aResult = $this->buildTest($this->createOptions($this->read('path')));

    return $aResult;
  }

  public function loadDirectory() {

    return $this->getTests();
  }

  protected function buildTest(core\argument $test) {

    $aResult = array();

    foreach ($test as $page) {

      $aPage = array(
        'url' => $page->read('@url', false),
        //'element' => $page->read('element'),
      );

      $aSteps = array();

      foreach ($page->get('steps') as $step) {

        $aStep = array(
          '_alias' => $step->getName(),
        );

        switch ($step->getName()) {

          case 'event' :

            $aStep['name'] = $step->read('@name');
            $aStep['element'] = $step->read('@element');
            break;

          case 'input' :

            $aStep['element'] = $step->read('@element');
            $aStep['value'] = $step->read();
            break;

          case 'watcher' :

            $aStep['element'] = $step->read('@element');
            $aStep['delay'] = $step->read('@delay', false);


            foreach ($step->query('property', false) as $property) {

              $aStep['property'][] = array(
                'name' => $property->read('@name'),
                'value' => $property->read(),
              );
            }

            $this->loadVariable($step, $aStep);

            break;

          case 'snapshot' :

            $aStep['element'] = $step->read('@element');
            $aStep['content'] = $step->read('content', false);

            foreach ($step->query('exclude', false) as $exclude) {

              $aStep['excludes'][] = array(
                'element' => $exclude->read('@element'),
              );
            }
            break;

          case 'call' :

            $aStep['path'] = $step->read('@path');
            $aStep['get'] = $step->read('@method', false) === 'get';

            $this->loadVariable($step, $aStep);
            break;

          case 'query' :

            $aStep['value'] = $step->read();
            $aStep['creation'] = $step->read('@creation');
            $aStep['timeshift'] = $step->read('@timeshift', false);
            break;
        }

        $aSteps[] = $aStep;
      }

      $aPage['steps'][] = array('_all' => $aSteps);
      $aResult['page'][] = $aPage;
    }

    return array_filter($aResult);
  }

  protected function loadVariable(core\argument $step, array &$aStep) {

    if ($var = $step->get('variable', false)) {

      $aStep['variable'][] = array(
        'name' => $var->read('@name'),
      );
    }
  }

  public function saveTest() {

    $aTest = json_decode($this->read('test'), true);
    $doc = $this->createArgument($aTest)->asDOM();
    $file = $this->getManager(self::FILE_MANAGER)->getFile($this->read('file'), $this->getDirectory(), fs\file::DEBUG_EXIST);

    $doc->saveFile($file, true);

    $this->getManager(self::PARSER_MANAGER)->getContext('messages')->add(array('content' => "File <strong>$file</strong> saved"));

    return true;
  }

  public function getCaptcha() {

    $captcha = new \sylma\modules\captcha\Type('');

    return $captcha->getKey();
  }

  public function runQuery() {

    $file = $this->getFile($this->read('file'));
    $creation = new \DateTime($this->read('creation'));
    $diff = $creation->diff(new \DateTime());

    $sContent = preg_replace_callback('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', function($aMatch) use($diff) {

      //dsp($sMatch);
      $date = new \DateTime($aMatch[0]);
      $new = $date->add($diff);

      return $new->format('Y-m-d H:i:s');

    }, $file->execute());
//$this->getDirectory()->createFile('temp')->saveText($sContent);
    $this->getManager(self::DB_MANAGER)->getConnection()->execute($sContent);

    return true;
  }
}

