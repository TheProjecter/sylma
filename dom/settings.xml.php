<?php
    return new \sylma\core\argument\parser\Cached(array(
'stats' => array(
  'enable' => '0'),
'namespaces' => array(
  'html' => 'http://www.w3.org/1999/xhtml',
  'dom' => 'http://www.sylma.org/dom'),
'import-depth' => '5',
'classes' => array(
  'handler' => array(
    'file' => '\sylma\dom\basic\handler\Rooted.php',
    'name' => '\sylma\dom\basic\handler\Rooted'),
  'document' => array(
    'file' => '\sylma\dom\basic\Document.php',
    'name' => '\sylma\dom\basic\Document'),
  'element' => array(
    'file' => '\sylma\dom\basic\Element.php',
    'name' => '\sylma\dom\basic\Element'),
  'attribute' => array(
    'file' => '\sylma\dom\basic\Attribute.php',
    'name' => '\sylma\dom\basic\Attribute'),
  'collection' => array(
    'file' => '\sylma\dom\basic\Collection.php',
    'name' => '\sylma\dom\basic\Collection'),
  'fragment' => array(
    'file' => '\sylma\dom\basic\Fragment.php',
    'name' => '\sylma\dom\basic\Fragment'),
  'text' => array(
    'file' => '\sylma\dom\basic\Text.php',
    'name' => '\sylma\dom\basic\Text'),
  'comment' => array(
    'file' => '\sylma\dom\basic\Comment.php',
    'name' => '\sylma\dom\basic\Comment'),
  'cdata' => array(
    'file' => '\sylma\dom\basic\CData.php',
    'name' => '\sylma\dom\basic\CData'),
  'instruction' => array(
    'file' => '\sylma\dom\basic\Instruction.php',
    'name' => '\sylma\dom\basic\Instruction'),
  'argument' => array(
    'file' => '\sylma\dom\argument\Tokened.php',
    'name' => '\sylma\dom\argument\Tokened',
    'classes' => array(
      'filed' => array(
        'file' => '\sylma\dom\argument\Filed.php',
        'name' => '\sylma\dom\argument\Filed'))))));
  