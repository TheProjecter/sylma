<?php
  
define('PATH_SYLMA', '../../lib');
define('PATH_PHP', 'protected');
define('SITE_TITLE', 'Lemon.web');

// A mettre pour le d�buggage, renvoie Controler::isAdmin() � true et enl�ve le cache des templates
define('DEBUG', false);

$aDB = array(
  
  'host' => 'localhost',
  'user' => 'root',
  'password' => '',
);