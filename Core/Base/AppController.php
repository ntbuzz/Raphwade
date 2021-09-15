<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 * 	AppController: Controller Processing
 */
class AppController extends AppObject {
	public $defaultAction = 'List';		// Omitted URI, Default Action 
	public $disableAction = [];			// Ban Action
	private $my_method;					// active method list on Instance
	protected $needLogin = FALSE;		// Login NEED flag
	protected $aliasAction = [];		// Action Method Alias [ Alias => Real,... ]
	protected $discardParams = [];		// Params Discard method
    protected $noLogging = NULL;		// execute log save exception method list ex. [ 'List',... ]
	protected $LoggingMethod = NULL;	// execute log save Model "class.method". ex. 'Access.Logging'
	protected $BypassMethod = '';		// Login bypass method,if NEDD LOGIN(ex.Logout)
//==============================================================================
// constructor: create MODEL, VIEW(with HELPER)
	function __construct($owner = NULL){
		parent::__construct($owner);
		$model = "{$this->ModuleName}Model";
		$model_class = (class_exists($model)) ? $model : 'AppModel';	// file not exists, Use basic Class
		$this->Model = ClassManager::Create($model,$model_class,$this);
		$view = "{$this->ModuleName}View";
		$view_class = (class_exists($view)) ? $view : 'AppView';		// file not exists, Use basic Class
		$this->View = ClassManager::Create($view,$view_class,$this);
		$this->Helper = $this->View->Helper;			// Helper class short-cut
		if(empty(App::$Filter) && isset($this->defaultFilter)) {
			App::$Filters[0] = App::$Filter = $this->defaultFilter;
		}
			// filter of '*Action' method
		$map_conv = function($nm) { return (substr_compare($nm,'Action',-6) === 0) ? substr($nm,0,-6):''; };
		// mekae active method list
		$en = $this->defaultAction;		// default Action must be ENABLED
		if(is_scalar($this->disableAction)) {
			$except = array_filter( array_map( $map_conv,get_class_methods('AppController')),
						function($v) use ($en) { return !empty($v) && ($en !== $v);});
		} else $except = $this->disableAction;
		// Instance method
		$this->my_method = array_filter( 		// $except array filter
							array_filter(		// *Action method pickup filter
								array_map( $map_conv,get_class_methods($this)),'strlen'),
								function($v) use ($except) {
									return !in_array($v,$except,true);
								});
	}
//==============================================================================
// Initialized Class Property
	protected function class_initialize() {
		// Deadlock occurs when a AppModel construtor, Controller runs on behalf of AppModel.
		$this->LocalePrefix = $this->Model->LocalePrefix;
		parent::class_initialize();                       // Call Initialize method chain.
	}
//==============================================================================
// Terminated Contorller
public function __TerminateApp() {
	$this->View->__TerminateView();
}
//==============================================================================
// VIEW CREATE for command-line
public function CreateAction() {
	$this->Model->CreateMyView();
}
//==============================================================================
// check active METHOD
public function is_enable_action($action) {
	if(	array_key_exists($action,$this->aliasAction) ||		// exists Alias Action
		in_array($action,$this->my_method,true)) return TRUE;	// exist ENABLED List
	return FALSE;	// diable ActionMethod
}
//==============================================================================
// setup login user language and region
	private function setup_user_lang_region($data,$userdata,$loginkey = NULL) {
		list($userid,$lang,$region) = $data;
		$login_data = [
			'LANG' => $lang,
			'REGION' => $region,
		];
		if($loginkey !== NULL) $login_data[$loginkey] = $userdata[$loginkey];
		MySession::set_LoginValue($login_data);
		$this->Model->ResetSchema();
	}
//==============================================================================
// authorised login mode, if need view LOGIN form, return FALSE
public function is_authorised($method) {
	if(defined('LOGIN_CLASS')) {			// enable Login Class
		$model = LOGIN_CLASS . 'Model';
		$Login = $this->$model;
		$login_key = isset($Login->LoginID) ? $Login->LoginID:'login-user';
		$bypass_method = (is_array($this->BypassMethod)) ? $this->BypassMethod : [$this->BypassMethod];
		$bypass_method[] = 'Logout';	// must be append LogoutAction
		if($this->needLogin && !in_array($method,$bypass_method)) {
			$data = (CLI_DEBUG) ? $Login->defaultUser() : $Login->is_validLogin(MySession::$ReqData);
			if(is_array($data)) {	// POST data Login Success
				$this->setup_user_lang_region($data,$model::$LoginUser,$login_key);
			} else {	// No-POST or Login FAIL
				$userid = MySession::get_LoginValue($login_key);
				if($data === FALSE || $userid === NULL) {	// LOGIN-FAIL or Not LOGIN
					$msg = $this->__('.Login');
					$err_msg = $Login->error_type;
					$login_page = (defined('LOGIN_PAGE')) ? LOGIN_PAGE : 'app-login.php';
					page_response($login_page,[
						'page_title'	=> $this->__('Login.LoginPage'),
						'msg_title'		=> $this->__('Login.LoginTitle'),
						'user_title'	=> $this->__('Login.UserName'),
						'pass_title'	=> $this->__('Login.Password'),
						'send_button'	=> $this->__('Login.Submit'),
						'msg_body'		=> $Login->error_type,
						'login_user'	=> $userid,
					]);     // LOGIN PAGE Response, NO returned HERE!
				}
			}
		}
		// Login-Success or NoNEED or BYPASS-METHOD reached HERE
		$data = MySession::get_LoginValue([$login_key,'LANG','REGION']);
		$userid = $data[0];
		// Check Already Logined
		if(!empty($userid) && !isset($model::$LoginUser)) {
			// Reload UserData, bu LANG & REGION is remind in SESSION
			$Login->reload_userdata($data);
			$this->setup_user_lang_region($data,$model::$LoginUser);
		}
		LockDB::SetOwner($userid);
	}
	return TRUE;
}
//==============================================================================
// Logout Processing
public function LogoutAction() {
//	MySession::setup_Login(NULL);
	MySession::ClearSession();
	$url = App::Get_SysRoot('index.html');
	if(CLI_DEBUG) echo "Location:{$url}\n";
	else header("Location:{$url}");
}
//==============================================================================
// Set Property value on Helper Class
public function SetHelperProps($arr) {
	$this->Helper->setProperty($arr);
}
//==============================================================================
// Pre-Processing before Action method invoke
protected function ActionPreProcess($action) {
	return TRUE;
}
//==============================================================================
// Post-Processing after Action method complete
protected function ActionPostProcess($action) {
	return TRUE;
}
//==============================================================================
// execute log output Model call, after $action invoked.
private function exec_Logging($action) {
	$no_log = (in_array($this->noLogging,['*',$action],true)) ||
			(is_array($this->noLogging) && in_array($action,$this->noLogging,true));
	list($model,$method) = explode('.',"{$this->LoggingMethod}.");
	if(!empty($model) && !$no_log) {
		if(empty($method)) $method = 'Logged';
		$class_name = "{$model}Model";
		if(class_exists($class_name)) $this->$model->$method($this,$action);
	}
}
//==============================================================================
// Method Dispatcher before Pre-Process, after Post-Processing
public function ActionDispatch($action) {
	$discard = (is_scalar($this->discardParams)) ? [$this->discardParams] : $this->discardParams;
	if(in_array($action,$discard)) App::CleareParams();
	if($this->ActionPreProcess($action)) {
		if(array_key_exists($action,$this->aliasAction)) {
			$action = $this->aliasAction[$action];
		}
		$method = "{$action}Action";
		$this->$method();
		$this->ActionPostProcess($action);
		$this->exec_Logging($action);
	}
}
//==============================================================================
// Auto Paging.
public function AutoPaging($cond, $max_count = 100) {
	list($num,$size) = array_intval(App::$Params);
	if($num === 0) $num = 1;
	$cond = re_build_array($cond);
	$Page = MySession::getPagingIDs('Setup');
	list($sCond,$sSize,$sURI,$sQuery) = array_filter_values($Page,['Cond','Size','URI','QUERY']);
	$uri = App::Get_PagingPath();
	if($uri === $sURI && empty($_REQUEST)) {	// POST & GET nothing
		$cond = $sCond;			// same condition
//		App::$Query = $sQuery;
		$this->SetHelperProps(['QUERY' => $sQuery]);
	} else {
		$Page['Cond'] = $cond;
		$Page['URI'] = $uri;
		$Page['QUERY'] = App::$Query;
	}
	if(!empty($sSize) && $size === 0) {
		$size = intval($sSize);
	}
	$cnt = $this->Model->getCount($cond);
	if($cnt < $size )  $size = 0;
	else if($cnt > $max_count && $size === 0) $size = $max_count;
	if($size > 0) {
		$Page['Size'] = $size;
		$Page['Page'] = App::$Params[0] = $num;
		$this->Model->SetPage($size,$num);
	} else $Page = NULL;	// remove Paging.Setup
	MySession::setPagingIDs('Setup',$Page);
	return $cond;
}
//==============================================================================
// Auto Paging, and Model Finder
public function PagingFinder($cond, $max_count=100,$filter=[],$sort=[]) {
	$cond = $this->AutoPaging($cond, $max_count);
	$this->Model->RecordFinder(NULL,$filter,$sort);
}
//==============================================================================
// Default List Action
public function ListAction() {
	$this->Model->RecordFinder([]);
	$this->View->PutLayout();
}
//==============================================================================
// Default Page Action
public function PageAction() {
	$this->AutoPaging([],50);
	$this->ListAction();
}
//==============================================================================
// Default Find Action
public function FindAction() {
	if(!empty(App::$Filter) ) {
		$row = array(App::$Filter => "=".App::$Params[0]);
	} else {
		$row = array();
	}
	$this->Model->RecordFinder($row);
	$this->View->PutLayout();
}
//==============================================================================
// Default View Action (Full Page)
public function ViewAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num,TRUE,TRUE);
	$this->View->PutLayout('ContentView');
}
//==============================================================================
// Default Item View Action (Parts of Page)
public function ItemAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num,TRUE,TRUE);
	$this->View->ViewTemplate('ItemView');
}
//==============================================================================
// Delete Record
public function DeleteAction() {
	$num = App::$Params[0];
	$this->Model->DeleteRecord($num);
	header('Location:' . App::$Referer);
}
//==============================================================================
// Contents Template Action in AJAX access for like a SPA
// app/(cont)/contents/(templatename)/(rec-number)
public function ContentsAction() {
	$num = App::$Params[0];
	$template = ucfirst(strtolower(App::$Filter));
	if($num > 0) $this->Model->GetRecord($num,TRUE,TRUE);
	$this->View->ViewTemplate("{$template}Parts");
}
//==============================================================================
// Default Add Record Action
public function AddAction() {
	$url = App::$Referer;
	$this->Model->AddRecord(MySession::$ReqData);
	if(empty($url)) $url = App::Get_AppRoot($this->ModuleName,TRUE) . '/list/'.App::$Filter;
	header('Location:' . $url);
//	echo App::$Referer;
}
//==============================================================================
// Default Update Action
public function UpdateAction() {
	$num = App::$Params[0];
	$url = App::$Referer;
	MySession::setEnvVariables(['RecordNo' => $num]);
	$this->Model->UpdateRecord($num,MySession::$ReqData);
	if(empty($url)) $url = App::Get_AppRoot($this->ModuleName,TRUE) . '/list/'.App::$Filter;
	header('Location:' . $url);
}
//==============================================================================
// Default PDF Convert Action
public function MakepdfAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num,TRUE);
	$this->View->ViewTemplate('MakePDF');
}

}
