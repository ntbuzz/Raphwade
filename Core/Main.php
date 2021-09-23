<?php
// session start parameter setup
define('SESSION_INI_MODIFIED', TRUE);
/* -------------------------------------------------------------
 * Object-orientation PHP mini_framework
 *   Main: routing and redirection process
 *      method naming basic rule.
 *      void = camel case (first upper is 'PUBLIC')
 *          PascalCase      Public method/function, void function
 *              PublicMethod
 *          camelCase       private/protected method/function,void
 *              privateFunction
 *          Pascal_snake    public void function
 *              Set_on_void
 *      return value = snake_case (first upper, all lower is 'PUBLIC')
 *          Camel_Snake     Public method/function, return value
 *              Get_Default
 *          snake_Camel     private/protected method/function with return value
 *              private_Function
 *          snake_case      Public function with return value
 *              all_public_function
 */
// for DEBUG utility
require_once('AppDebug.php');
// follow is DEPEND ON this program.
require_once('Config/appConfig.php');
require_once('Common/coreLibs.php');
// also autoload enabled, but not use for performance up
require_once('App.php');
require_once('Base/AppObject.php');
require_once('Base/LangUI.php');
require_once('Class/ClassManager.php');

// Setup TIMEZONE
date_default_timezone_set(TIME_ZONE);

$redirect = false;      // Redirect flag
$root = basename(dirname(__DIR__));        // Framework Folder
// REQUEST_URI analyze
list($appname,$app_uri,$module,$query) = get_routing_path($root);
list($fwroot,$approot) = $app_uri;
list($controller,$method,$filters,$params) = $module;

// is enabled application name
if(empty($appname) || !file_exists("app/$appname")) {
    // 404 not found page
    error_response('app-404.php',$appname,$app_uri,$module);
}
if($controller === 'Error') {       // ERROR PAGE
    $code = $params[0];
    error_response("page-{$code}.php",$appname,$app_uri,$module);
}
require_once("app/{$appname}/Config/config.php");
require_once('Base/AppController.php');
require_once('Base/AppModel.php');
require_once('Base/AppView.php');
require_once('Base/AppHelper.php');

// Check Default defined CONST
if(!defined('FORCE_REDIRECT'))	 define('FORCE_REDIRECT', FALSE);
if(!defined('DEFAULT_LANG'))	 define('DEFAULT_LANG', 'ja');				// Language
if(!defined('DEFAULT_REGION'))	 define('DEFAULT_REGION', 'jp');			// Region code
if(!defined('SHARE_FOLDER_USE')) define('SHARE_FOLDER_USE',false);			// autoload on .share

if(!is_extst_module($appname,$controller,'Controller')) {
    // if BAD controller name, try DEFAULT CONTROLLER and shift follows
    $cont = (DEFAULT_CONTROLLER === '') ? $appname : DEFAULT_CONTROLLER;
	if(empty($controller)) {
	    $module[0] = $controller = ucfirst(strtolower($cont));
	} else {
		array_unshift($filters,strtolower($method));    // move method to 0filters
		$module[0] = ucfirst(strtolower($cont));
		$module[1] = $controller;
		$module[2] = $filters;
		list($controller,$method) = $module;
	}
    // RE-TRY DEFAULT CONTROLLER,if FAILED,then NOT FOUND
    if(!is_extst_module($appname,$controller,'Controller')) {
        error_response('page-404.php',$appname,$app_uri,$module);
    }
}
// need REDIRECT, will be Controller name changed.
if($redirect) $module[0] = $controller;
$ReqCont = [
    'root' => $approot,
    'module' => $module,
//    'query' => array_to_query($query),
];
$requrl = array_to_URI($ReqCont);
if($redirect) {
    if(CLI_DEBUG) {
        echo "Location:{$requrl}/";
    } else {
        header("Location:{$requrl}/");
    }
    exit;
}
require_once('Class/ClassLoader.php');
ClassLoader::Setup($appname);   // AutoLoader for Application folder
MySession::InitSession($appname,$controller,SESSION_ENV_EXEC_ALL);         // Session Variable SETUP
MySession::set_paramIDs('debugger',DEBUGGER);  // SET DEBUGGER
MySession::set_paramIDs('sysinfo',[
    'platform'  => PLATFORM_NAME,
    'copyright' => COPYTIGHT,
    'version'   => CURRENT_VERSION,  // framework version
]);
// LANG and REGION parameter in URL query.
$lng = get_locale_lang($_SERVER['HTTP_ACCEPT_LANGUAGE']);
if(defined('LOCALE_REGION') && (array_key_exists($lng,LOCALE_REGION))) {
	$locale_set = LOCALE_REGION[$lng];
} else $locale_set = "{$lng}.??";
list($lang,$region) = explode('.',$locale_set);
foreach(['lang'=>$lang, 'region'=>$region] as $key => $val) {
	$uname = strtoupper($key);
	$def = (array_key_exists($key, $query)) ? $query[$key] : MySession::get_LoginValue($uname);
	if(empty($def) || $def === 'undefined') $def = $val;
	MySession::set_LoginValue([$uname => $def]);
	$$key = $def;
}
// INITIALIZED App static class.
App::__Init($appname,$app_uri,$module,$query,$requrl);
$method = App::$Method;
// Load if .SHARE use, common library load
if(SHARE_FOLDER_USE) {
	$libs = get_php_files("app/.share/common/");
	foreach($libs as $files) {
		require_once $files;
	}
}
// Load Application Common library
$libs = get_php_files(App::Get_AppPath("common/"));
foreach($libs as $files) {
    require_once $files;
}
if(empty($lang)) $lang = DEFAULT_LANG;
// Load Common Locale tranlate parameter
LangUI::construct($lang,App::Get_AppPath("View/lang/"),['#common',$controller]);
// Load Application MODULE files. (Controller,Model,View,Helpe)
App::LoadModuleFiles($controller);
$ContClass = "{$controller}Controller";
// Create Controller CLASS
//$controllerInstance = new $ContClass();
$controllerInstance = ClassManager::Create($ContClass,$ContClass,NULL);

// Method existance Check
if(!$controllerInstance->is_enable_action($method)) {
    if(FORCE_REDIRECT || $method==='') {
        $method = $controllerInstance->defaultAction;   // get DEFAULT method
    } else {
        $module[0] = $controller;       // may-be rewrited
        $module[1] = $method;           // may-be rewrited
        error_response('page-404.php',$appname,$app_uri,$module);
    }
}
if(strcasecmp($appname,$controller) === 0) {
    App::ChangeMethod('',$method,false);     // hide controller in URI
} else {
    App::ChangeMethod($controller,$method,false);
}
sysLog::__Init($appname,$controller,$method);
//=================================
sysLog::run_start();
LockDB::LockStart();
// Login unnecessary, or Login success returned TRUE.
if($controllerInstance->is_authorised($method)) {
    // Debugging Message
	$life_time = MySession::$SESSION_LIFE;
    debug_log(DBMSG_CLI|DBMSG_SYSTEM, [
		-1 => "#Opening",
        '#PathInfo' => [
            'SERVER'    => $_SERVER['SERVER_NAME'],
            "DOCROOT"   => App::$DocRoot,
            "REQ_URI"   => $_SERVER['REQUEST_URI'],
            "REFERER"   => App::$Referer,
        ],
        '#DebugInfo' => [
            "AppName"       => App::$AppName,
            "Class"         => $ContClass,
            "Controller"    => App::$Controller,
            "Action"        => App::$Method,
            "Filters"       => App::$Filters,
    //        "Param"         => App::$Params,
            "Re-Location" => App::Get_RelocateURL(),
        ],
		'FORM' => [
			"GET"	=> App::$Query,
			"POST"	=> MySession::$ReqData,
	        "EMPTY"	=> MySession::$is_EmptyData,
		],
        "SESSION-ALIVE"	=> date('Y/m/d H:i:s',$life_time)." ({$life_time})",
        "SESSION Variables" => [
            "SESSION_ID"=> MySession::$MY_SESSION_ID,
            "ENV"       => MySession::$EnvData,     // included App::[sysVAR]
        ],
        'LockDB OWNER' => LockDB::GetOwner(),
    ]);
    // Controller Method Dispacher
    $controllerInstance->ActionDispatch($method);
}
debug_log(DBMSG_CLI|DBMSG_SYSTEM, [
	-1 => "#Closing",
    "CLASS-MANAGER" => ClassManager::DumpObject(),
     "#SessionClose"  => MySession::$EnvData,     // included App::[sysVAR]
]);
sysLog::run_time(DBMSG_CLI|DBMSG_SYSTEM);
MySession::CloseSession();
// call OUTPUT terminate
$controllerInstance->__TerminateApp();
// Database connection closed.
LockDB::LockEnd();
DatabaseHandler::CloseConnection();
