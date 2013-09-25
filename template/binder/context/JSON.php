<?php

namespace sylma\template\binder\context;
use sylma\core, sylma\parser\context, sylma\parser\action, sylma\dom, sylma\core\window, sylma\modules\html, sylma\template;

class JSON extends window\classes\Container implements window\scripted, window\action {

  protected $action;
  const PARSER_MANAGER = 'parser';

  public function __construct() {

    //parent::__construct($aArray, $aNS, $parent);
  }

  public function setScript(core\request $path, core\argument $post, $sContext = '') {

    //$path->parse();
    $parser = \Sylma::getManager('parser');
    $messages = new html\context\Messages;

    $contexts = new core\argument\Readable(array(
      'errors' => $this->loadMessages(),
      'messages' => $messages,
      'js' => new html\context\JS(array(
        'load' => new template\binder\context\Load,
      )),
    ));

    $parser->setContext('messages', $messages);

    try {

      $sResult = $parser->load($path->getFile(), array(
        'arguments' => $path->getArguments(),
        'contexts' => $contexts,
        'post' => $post,
      ));
    }
    catch (core\exception $e) {

      $sResult = false;
      $e->save(false);
    }

    if (\Sylma::isAdmin()) {

      $errors = $contexts->get('errors');
    }
    else {

      $errors = null;

      if (!$contexts->get('errors')->isEmpty()) {

        $messages->add('An error happened, the adminstrator has been informed.');
      }
    }

    $classes = $contexts->get('js/classes', false);

    $this->setSettings(array(
      'content' => (string) $sResult,
      'objects' => $contexts->get('js/load/objects', false),
      'classes' => $classes ? $classes->asStringVar() : null,
      'errors' => $errors,
      'messages' => $contexts->get('messages'),
    ));
  }

  public function setAction(action\handler $action) {

    $this->action = $action;
  }

  protected function getAction() {

    return $this->action;
  }

  protected function loadMessages() {

    if (!$result = $this->getManager('parser')->getContext('errors', false)) {

      $result = new html\context\Messages;
      $this->getManager('parser')->setContext('errors', $result);
    }

    return $result;
  }

  protected function loadAction(action\handler $action) {

    $contexts = new core\argument\Readable(array(
      'errors' => $this->loadMessages(),
      'messages' => new html\context\Messages,
      'js' => new html\context\JS(),
    ));

    $action->setContexts($contexts);
    $context = new window\classes\Context(\Sylma::getManager('init'));

    try {

      $context->setAction($action, 'default');
      $sResult = $context->asString();
    }
    catch (core\exception $e) {

      $e->save(false);
      $sResult = '';
    }

    $this->setSettings(array(
      'content' => $sResult,
      'errors' => $contexts->get('errors'),
      'messages' => $contexts->get('messages'),
    ));
  }

  public function asString() {

    header('Vary: Accept');

    if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {

      \Sylma::getManager('init')->setHeaderContent('application/json');

    } else {

      \Sylma::getManager('init')->setHeaderContent('text/plain');
    }

    if ($action = $this->getAction()) {

      $this->loadAction($action);
    }

    return $this->getSettings()->asJSON();
  }
}
