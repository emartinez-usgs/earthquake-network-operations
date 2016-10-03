<?php

date_default_timezone_set('UTC');

$OLD_PWD = $_SERVER['PWD'];

// work from lib directory
chdir(dirname($argv[0]));


if ($argv[0] === './pre-install.php' || $_SERVER['PWD'] !== $OLD_PWD) {
  // pwd doesn't resolve symlinks
  $LIB_DIR = $_SERVER['PWD'];
} else {
  // windows doesn't update $_SERVER['PWD']...
  $LIB_DIR = getcwd();
}
$APP_DIR = dirname($LIB_DIR);
$HTDOCS_DIR = $APP_DIR . '/htdocs';
$CONF_DIR = $APP_DIR . '/conf';

$HTTPD_CONF = $CONF_DIR . '/httpd.conf';
$CONFIG_FILE_INI = $CONF_DIR . '/config.ini';
$CONFIG_FILE_PHP = $CONF_DIR . '/config.inc.php';

chdir($LIB_DIR);

if (!is_dir($CONF_DIR)) {
  mkdir($CONF_DIR, 0755, true);
}


// configuration defaults
$DEFAULTS = array(
  'MOUNT_PATH' => '',
  'DB_DSN' => 'mysql:dbname=earthquake;host=localhost;',
  'DB_USER' => 'web',
  'DB_PASS' => ''
);
$HELP_TEXT = array(
  'MOUNT_PATH' => 'Url path to application',
  'DB_DSN' => 'Database connection DSN string',
  'DB_USER' => 'Read/write username for database connections',
  'DB_PASS' => 'Password for database user'
);

foreach ($argv as $arg) {
  if ($arg === '--non-interactive') {
    define('NON_INTERACTIVE', true);
  }
}
if (!defined('NON_INTERACTIVE')) {
  define('NON_INTERACTIVE', false);
}

// Interactively prompts user for config. Writes CONFIG_FILE_INI
include_once 'configure.inc.php';


// Parse the configuration
$CONFIG = parse_ini_file($CONFIG_FILE_INI);

// Write the HTTPD configuration file
file_put_contents($HTTPD_CONF, '
  ## autogenerated at ' . date('r') . '

  Alias ' . $CONFIG['MOUNT_PATH'] . ' ' . $HTDOCS_DIR . '

  RewriteEngine on
  RewriteRule ^' . $CONFIG['MOUNT_PATH'] . '/(services|places|regions|layers)\.(json)$ ' . $CONFIG['MOUNT_PATH'] . '/$1.php?format=$2 [L,PT,QSA]

  <Location ' . $CONFIG['MOUNT_PATH'] . '>
    Order allow,deny
    Allow from all

    <LimitExcept GET>
      deny from all
    </LimitExcept>

    ExpiresActive on
    ExpiresDefault "access plus 1 days"
  </Location>
');


echo "\n";

// configure database
include_once 'install.php';

