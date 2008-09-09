<?php
/**
  * Caffeine configurations are used to control how Caffeine
  * behaves. The core configurations below must always be set.
  * Additionaly, users can set their own configurations can
  * retrieve their value by calling the get_config('config_name') function.
  *
  * To learn more about each configuration, visit the User Guide.
  * http://code.google.com/p/caffeine-php/wiki/Configurations
*/

// ===========================================================
// CORE CONFIGURATIONS
// ===========================================================
// Default Controller & Method
$arrConfig['default_controller']        = 'welcome';
$arrConfig['default_method']            = 'index';

// Autoload Libraries
$arrConfig['auto_load']                 = array();

// Database Settings
$arrConfig['db']['host']                = 'localhost';
$arrConfig['db']['name']                = 'test';
$arrConfig['db']['username']            = 'root';
$arrConfig['db']['password']            = 'root';

// Log Settings
$arrConfig['enable_logs']               = false;
$arrConfig['log_types']                 = array('error', 'debug');
$arrConfig['log_file_format']           = date('Y-m-d');
$arrConfig['log_date_format']           = 'Y-m-d H:i:s';

// Error Pages
$arrConfig['404_view']                  = 'errors/404';
$arrConfig['error_view']                = 'errors/error';


// ===========================================================
// CUSTOM CONFIGURATIONS
// Add your own configurations here
// ===========================================================
// Example: $arrConfig['thumbnail_size'] = 80;


/**
  * How PHP handles errors. Its a good idea to set this value to
  * 0 when in a live enviroment.
  */
error_reporting(E_ALL);


/**
  * Caffeine requires PHP 5+. Check if we're running
  * the right version before doing anything else.
  */
if(phpversion() < 5)
  die('PHP 5+ is required to use Caffeine. You\'re using PHP ' . phpversion());


/**
  * Caffeine constants are used to determine file locations
  * and URL paths.
  */
define('CAFFEINE_ROOT', str_replace('\\', '/', realpath('..')) . '/');
define('CAFFEINE_EXT', '.php');
define('CAFFEINE_LOGS', 'logs/');
define('CAFFEINE_CONTROLLERS', 'app/controllers/');
define('CAFFEINE_MODELS', 'app/models/');
define('CAFFEINE_SOURCES', 'web');
define('CAFFEINE_VIEWS', 'app/views/');
define('CAFFEINE_LIBRARIES', 'libraries/');
define('CAFFEINE_VERSION', '2.0');


/**
  * Used to get configuration values anywhere in application.
  *
  * @param string $strConfigKey The configuration you want a value for
  * @param array $arrConfigToLoad Configurations to be loaded into the local static array
  * @param boolean $boolLoad Loads $arrConfigToLoad into static local array if true
  *
  * @return mixed Configuration value associated with $strConfigKey
  */
function get_config($strConfigKey, $arrConfigToLoad = array(), $boolLoad = false)
{
  static $arrConfig;
  if($boolLoad)
  {
    $arrConfig = $arrConfigToLoad;
    write_log('debug', 'Loading configurations');
  }
  else
  {
    if(isset($arrConfig[$strConfigKey]))
      return $arrConfig[$strConfigKey];
    else
      throw new Exception('Configuration value doesn\'t exist: ' . $strConfigKey);
  }
}


/**
  * Holds view data until the next view() function is called.
  * Helpful when loading view data into one view from multiple
  * locations.
  *
  * @param string $strKey The variable name to use
  * @param mixed $mixValue The value assigned to $strKey
  * @param boolean $boolReturn Returns and clears view data if true
  * 
  * @return array Collection of view data
  */
function view_data($strKey, $mixValue, $boolReturn = false)
{
  static $arrViewData;
  if($boolReturn)
  {
    if(is_array($arrViewData))
    {
      $tmpData = $arrViewData;
      $arrViewData = array();
      return $tmpData;
    }
    return array();
  }
  $arrViewData[$strKey] = $mixValue;
}


/**
  * Loads view data into view file and outputs view file to browser
  *
  * @param string $strViewFile Name of view file (without .php extension) to be loaded
  * @param array $arrViewData Array of view data to be loaded into view
  */
function view($strViewFile, $arrViewData = array())
{
  $strViewFile = str_ireplace('.php', '', $strViewFile);
  $strViewFile = CAFFEINE_ROOT . CAFFEINE_VIEWS . $strViewFile . CAFFEINE_EXT;
  if(file_exists($strViewFile))
  {
    write_log('debug', 'Loading view: ' . $strViewFile);
    $arrViewData += view_data('', '', true);
    foreach($arrViewData as $strKey => $mixValue)
      $$strKey = $mixValue;
    require($strViewFile);
  }
  else
    throw new Exception('View file doesn\'t exist: ' . $strViewFile);
}


/**
  * Loads Libraries, Models and Controllers.
  * Only calls functions or objects if the $boolCall param is
  * true. This is helpful when doing includes for extending
  * other classes
  *
  * @param string $strType The type of file to load (ie: 'Controller', 'Model' or 'View')
  * @param string $strName The name of the file and class or function to call
  * @param boolean $boolCall Calls function and/or creates new instance of $strName if true
  *
  * @return object Returns and instance of $strName if a class is found
  */
function load($strType, $strName, $boolCall = true)
{
  write_log('debug', 'Loading ' . $strType . ': ' . $strName);
  $strType = strtolower($strType);
  $strName = str_ireplace('.php', '', $strName);
  if($strType == 'database')
  {
    $arrDBConfig = get_config($strName);
    $objDB = new PDO( 
      'mysql:host=' . $arrDBConfig['host'] .
      ';dbname=' . $arrDBConfig['name'],
      $arrDBConfig['username'],
      $arrDBConfig['password']
      );
    $objDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $objDB;
  }
  else
  {
    $arrType = array
    (
      'controller' => CAFFEINE_CONTROLLERS,
      'model' => CAFFEINE_MODELS,
      'library' => CAFFEINE_LIBRARIES
      );
    $strFile = CAFFEINE_ROOT . $arrType[$strType] . strtolower($strName) . CAFFEINE_EXT;
    if(file_exists($strFile))
    {
      require_once($strFile); // Require once is faster then checking a static array for loaded files
      if($boolCall)
      {
        $boolCall = ($boolCall) ? 'True' : 'False';
        if(function_exists($strName))
        {
          write_log('debug', 'Library function exists. Call function?: ' . $boolCall);
          $strName();
        }
        if(class_exists($strName))
        {
          write_log('debug', 'Library class exists. Instantiate class and return?: ' . $boolCall);
          return new $strName();
        }
      }
    }
    else
      throw new Exception(ucfirst($strType). ' file doesn\'t exist: ' . $strFile);
  }
}


/**
  * Gets the value of a URL segment.
  *
  * @param string $strSegment The segment name you want a value for
  * @param array $arrSegments Array of segments to be loaded into static $arrUri variable
  * @param boolean $boolLoadSegments If true, loads $arrSegments into static $arrUri variable
  *
  * @param mixed The value of $strSegment if exists, else false
  */
function uri($strSegment, $arrSegments = array(), $boolLoadSegments = false)
{
  static $arrUri;
  if($boolLoadSegments)
  {
    write_log('debug', 'Loading segments into URI function');
    $arrUri = $arrSegments;
    $arrBits = explode('index.php', $_SERVER['PHP_SELF']);
    $arrUri['base'] = 'http://' . $_SERVER['HTTP_HOST'] . $arrBits[0];
    $arrUri['current'] = $arrUri['base'] . implode('/', array_slice($arrUri, 0, 2));
    if(count($arrUri['params']) > 0)
      $arrUri['current'] .= '/' . implode('/', $arrUri['params']);
  }
  else
  {
    if(is_int($strSegment))
      return (isset($arrUri['params'][$strSegment])) ? $arrUri['params'][$strSegment] : false;
    else
      return (isset($arrUri[$strSegment])) ? $arrUri[$strSegment] : false;
  }
}


/**
  * Returns the full URL path to the given source file
  *
  * @param string $strSourceFile Source file name (ex: 'style.css')
  * @return string The full URL path to given source file
  */
function source($strSourceFile)
{
  return uri('base') . CAFFEINE_SOURCES . $strSourceFile;
}


/**
  * Writes a message to log file. Log file name, datestamp and allowed
  * log types are defined in configurations. Logs will only be written if
  * logging is enabled.
  *
  * @param string $strType The type of log to write (ie: error, debug, info)
  * @param string $strMessage The message to writesud
  */
function write_log($strType, $strMessage)
{
  if(!get_config('enable_logs'))
    return;

  $arrTypes = get_config('log_types');
  $strFileFormat = get_config('log_file_format');
  $strDateFormat = date(get_config('log_date_format'));

  if(is_array($arrTypes) && in_array($strType, $arrTypes))
  {
    $strTmp = '';
    $strType = strtoupper($strType);
    $strLogFile = CAFFEINE_ROOT . CAFFEINE_LOGS . $strFileFormat . ".log";

    // Load file into buffer for writing
    // If buffer fails, return silently
    if(!$strmBuffer = @fopen($strLogFile, 'a'))
      return;

    // Set string to write in var
    $strTmp .= $strType . ' - ' . $strDateFormat . ' --> ' . $strMessage . chr(10);

    // Write message and clear
    flock($strmBuffer, LOCK_EX);
    fwrite($strmBuffer, $strTmp);
    flock($strmBuffer, LOCK_UN);
    fclose($strmBuffer);
    unset($strmBuffer);
  }
}


/**
  * Acts as a default (catch-all) exception handler.
  *
  * @param object $objException A thrown exception
  */
function caffeine_exception($objException)
{
  $strMessage = $objException->getMessage();
  switch($strMessage)
  {
    case '404':
    view(get_config('404_view'));
    break;
    default:
    write_log('error', $strMessage);
    view_data('strMessage', $strMessage);
    view(get_config('error_view'));
    break;
  }
}
set_exception_handler('caffeine_exception');


/**
  * Parses segments out of the URL to determine which controller and methods
  * to call along with parameters. If a valid segment isn't found, a default from
  * configurations is used.
  *
  * @param array $arrConfig User-defined configurations
  */
function caffeine_router($arrConfig)
{
  write_log('debug', 'Parsing segments from URL');

  // Parse segments from URL
  $strUrl = trim(substr($_SERVER['PHP_SELF'], stripos($_SERVER['PHP_SELF'], 'index.php') + 9), '/');
  $arrUrlBits = (!empty($strUrl)) ? explode('/', $strUrl) : array();

  // Set segment defaults
  $arrSegments['controller'] = $arrConfig['default_controller'];
  $arrSegments['method'] = $arrConfig['default_method'];
  $arrSegments['params'] = array();

  // If URL segments exist, overwrite defaults
  $boolResult = true;
  if(count($arrUrlBits) > 0)
  {
    $boolResult = false;
    $arrPop = array();
    $strPath = CAFFEINE_ROOT . CAFFEINE_CONTROLLERS;
    while($arrUrlBits)
    {
      $strTmp = implode('/', $arrUrlBits);
      $arrPop[] = array_pop($arrUrlBits);

      // Test if last segment is controller
      if(file_exists($strPath . $strTmp . CAFFEINE_EXT))
      {
        $arrSegments['controller'] = $strTmp;
        $boolResult = true;
      }

      // Test if default controller exists in last segment
      elseif(file_exists($strPath . $strTmp . '/' . $arrSegments['controller'] . CAFFEINE_EXT))
      {
        $arrSegments['controller'] = $strTmp . '/' . $arrSegments['controller'];
        $boolResult = true;
      }

      // If we found a controller, get method and param segments
      // and break from loop
      if($boolResult)
      {
        $arrPop = array_reverse($arrPop);
        if(isset($arrPop[1])) $arrSegments['method'] = $arrPop[1];
        if(isset($arrPop[2])) $arrSegments['params'] = array_slice($arrPop, 2);
        break;
      }
    }
  }
  uri('', $arrSegments, true);
  if(!$boolResult) throw new Exception('404');
  return $arrSegments;
}


/**
  * Startup point for Caffeine. Calls caffeine_router to determine segments
  * and loads Controller and method accordingly.
  *
  * @param array $arrConfig User-defined configurations
  */
function caffeine_init($arrConfig)
{
  get_config('', $arrConfig, true);
  $arrSegments = caffeine_router($arrConfig);
  $strControllerFile = CAFFEINE_ROOT . CAFFEINE_CONTROLLERS . $arrSegments['controller'] . CAFFEINE_EXT;
  $strControllerClass = $arrSegments['controller'] . "Controller";
  if(file_exists($strControllerFile))
  {
    require($strControllerFile);
    if(ereg('/', $arrSegments['controller']))
      $arrSegments['controller'] = substr(strrchr($arrSegments['controller'], '/'), 1);
    $objController = new $strControllerClass($arrSegments['params']);

    // Check if controller method exists
    if(!method_exists($objController, $arrSegments['method']))
      throw new Exception('404');
    $objController->$arrSegments['method']($arrSegments['params']);
    write_log('debug', 'Finished');
  }
  else
    throw new Exception('404');
}


/**
  * Used to autoload libraries into Controllers, Models
  * or other Libraries
  */
class Autoload
{
  public function __construct()
  {
    write_log('debug', 'Autoloading libraries');
    $arrLibraries = get_config('auto_load');
    foreach($arrLibraries as $strLibrary)
      $this->$strLibrary = load('library', $strLibrary);
  }
}


/**
  * Run Caffeine
  */
caffeine_init($arrConfig);
