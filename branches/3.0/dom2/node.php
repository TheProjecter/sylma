<?php

namespace sylma\dom;
use sylma\core;

require_once('core/tokenable.php');
require_once('core/stringable.php');

interface node extends core\tokenable, core\stringable {

  const HANDLER = 66;
  const ELEMENT = \XML_ELEMENT_NODE;
  const ATTRIBUTE = \XML_ATTRIBUTE_NODE;
  const TEXT = \XML_TEXT_NODE;
  const CDATA = \XML_CDATA_SECTION_NODE;
  const COMMENT = \XML_COMMENT_NODE;
  const FRAGMENT = \XML_DOCUMENT_FRAG_NODE;

  /**
   * @return dom\document The parent document
   */
  function getDocument();
  function getType();
  function getParent();
  function __toString();
}