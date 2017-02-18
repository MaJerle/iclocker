<?php

namespace Inc;

use Inc\ErrorMiddleware;
use Inc\Helper;
use Inc\Database;
use Inc\Auth\User;
use Inc\Auth\AuthMiddleware;
use \Model\Comment;
use \Model\Usertoken;

class Controller extends \Slim\Slim {
	protected $data;
	protected $TMS;
	public $config;
	public $is_api;
	public $validationErrors = [];

	//Site title
	private $__siteTitle = '';

	// set to true for a user who was logged in after registration. Gets clearead automaticaly
	public $user_registered;
	private $requests = [];

	public function __construct() {
		$config = require __DIR__ . '/../config.php';
		$this->config = $config;

		$this->startTimer();
		parent::__construct($config['slim']);

		//Setup DB
		$this->db = new \Inc\Database\StorageDatabase(
			$this,
			$this->config['database']['host'],
			$this->config['database']['user'],
			$this->config['database']['password'],
			$this->config['database']['db']
		);

		//For our Bogus MVC implementation
		//We just have to init it once
		new \Inc\Model($this, $this->db);

		// Views setup
		$twig = new \Slim\Views\Twig();
		$view = $this->view($twig);
		$view->parserOptions = $config['twig'];
		
		//Create twig view helper
		$this->ViewHelper = new Helper();

		$view->parserExtensions = [
	        new \Slim\Views\TwigExtension(),
	        new \Twig_Extension_Debug(),
	        $this->ViewHelper
	    ];
		$this->view->setData('config', $config['slim']);

		//Session cookie middleware
		/*$this->add(new \Slim\Middleware\SessionCookie(array(
			'expires' => '+1 day',
			'path' => '/',
			'domain' => null,
			'secure' => false,
			'httponly' => false,		
			'name' => 'slim_session',
			'secret' => $this->config['slim']['session_cookie_secret'],
			'cipher' => MCRYPT_RIJNDAEL_256,
			'cipher_mode' => MCRYPT_MODE_CBC
		)));*/

		//Auth middleware
		$this->add(new AuthMiddleware());

		//URL prefix
		if (array_key_exists('url_prefix', $config['slim'])) {
			$this->view()->setData('prefix', $config['slim']['url_prefix']);			
		} else {
			$this->view()->setData('prefix', '/');
		}

		//Template prefix
		if (array_key_exists('template_prefix', $config['slim'])) {
			$this->view()->setData('template_prefix', $config['slim']['template_prefix']);
		} else {
			$this->view()->setData('template_prefix', '/');
		}

		//Check for index prefix
		if ($this->config['slim']['index_prefix']) {
    		$request_uri = preg_replace('/' . preg_quote($_SERVER['CONTEXT_PREFIX'], '/') . '/', '', $_SERVER['REQUEST_URI'], 1);
    		$startText = '/index.php';
			if (substr($request_uri, 0, strlen($startText)) != $startText) {
				//Create new link and redirect there
				$request_uri = $_SERVER['SCRIPT_NAME'] . $request_uri;

				//Make redirection
				header('Location: ' . $request_uri);
				exit;
			}
		}
	}

	//Called from AUTH middleware
	public function setLanguage($language = null) {
		//Invalidate first
		if (isset($this->TranslateEntries)) {
			unset($this->TranslateEntries);
		}

		//Setup translate if needed
		$languages = array();

		//Add first language
		if (!empty($language)) {
			$languages[] = $language;
		}

		//Get langauge from GET method
		if (isset($_GET['language']) && !empty($_GET['language'])) {
			$languages[] = htmlspecialchars($_GET['language']);
		}

		//Check if user is logged IN
		if ((isset($this->user->authenticated) && $this->user->authenticated) || isset($this->user->Language)) {
			if (isset($this->user->Language)) {
				$languages[] = $this->user->Language;
			}
		}

		//Check browser cookie
		if (isset($_COOKIE['language'])) {
			$languages[] = $_COOKIE['language'];
		}

		//Check HTTP headers from browser
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$langs = explode(";", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			foreach ($langs as $r) {
				$r = str_replace('-', '_', $r);
				$rs = explode(',', $r);
				foreach ($rs as $r) {
					if (preg_match('/[a-z]{2}_[A-Z]{2}/i', $r, $d)) {
						$languages[] = $r;
					}
				}
			}
		}

		//Set english language which is for sure used
		$languages[] = 'en_US';

		//FIX IT!
		$languages = ['en_us'];

		//Set proper language if possible
		foreach ($languages as $language) {
			$path = $this->config['languages']['path'] . $language . '.';
			
			foreach ($this->config['languages']['file_extensions'] as $type) {
				if (!isset($this->TranslateEntries) && file_exists($path . $type)) {
					$poHandler = new \Sepia\FileHandler($path . $type);
					$poParser = new \Sepia\PoParser($poHandler);
					$entries = $poParser->parse();

					$vals = [];
					foreach ($entries as $key => $value) {
						$key = str_replace(array("\n", '<##EOL##>'), '', $key);
						$vals[$key] = $value;
					}

					//Save translate entries
					$this->TranslateEntries = $vals;

					//Save language
					$this->Language = $language;
				}
			}
		}

		//Set default language
		if (!isset($this->Language)) {
			$this->Language = 'en_US';
		}
	}

	public function apiNetworkError($message) {
		$this->view()->set('error', $message);
		$this->render('network_error.html');
		exit();
	}

	public function setMenu($menu, $submenu) {
		$this->view()->setData(['menu' => $menu, 'submenu' => $submenu]);
	}

	public function render($name, $data = array(), $status = null) {	
		$menu = $this->view()->get('menu');

		//Set from value if possible from GET request
		if ($this->request()->get('from', 'none') != 'none') {
			$this->view()->set('from', $this->request()->get('from', 'none'));
		}

		//Set current language
		$this->view()->set('language', $this->Language);

		//Set controller name and method to view
		$this->view()->set('controller', $this->Controller);
		$this->view()->set('method', $this->Method);

		//Set if ajax request
		$this->view()->set('is_ajax', $this->request()->isAjax());
		$this->view()->set('is_modal', $this->isModal());

		//Output route params
		$this->view()->set('route_params', $this->RouteParams);

		//If debug mode, print 
		if ($this->config['slim']['debug']) {
			ob_start();
			passthru('svn info');
			$dat = ob_get_clean();
			if ($dat) {
				preg_match("/Last Changed Date: (.*) \+.*/", $dat, $time);
				preg_match("/Last Changed Rev: (.*)/", $dat, $number);
				preg_match("/Last Changed Author: (.*)/", $dat, $author);
				$this->view()->set('svnDate', $time[1]);
				$this->view()->set('svnAuthor', $author[1]);
				$this->view()->set('svnRevision', $number[1]);
			}
		}

		$this->endTimer();
		$this->view()->set('template', $name);
		$this->view()->set('requests', $this->requests);
		$this->view()->set('site_title', $this->__siteTitle);

		parent::render($name, $data, $status);
	}	

	public function access_denied() {
		if ($this->request()->isAjax()) {
			print json_encode(array('error' => 'access denied'));
		} else {
			return $this->render('denied');
		}
	}

	public function startTimer() {
		$this->TMS = microtime(true);
	}

	public function endTimer() {
		$TME = microtime(true);
		$TMD = $TME - $this->TMS;
		$this->view()->setData('TMD', round($TMD * 1000,1));
	}

	public function redirect($where, $code = 200) {
		if ($this->config['slim']['url_prefix'] && substr($where, 0, 1) != '/') {
			$link = $this->config['slim']['url_prefix'] . $where;
		} else if (substr($where, 0, 1) != '/') {
			$link = '/' . $where;
		} else{
			$link = $where;
		}

		//Check for ajax request
		if ($this->request()->isAjax()) {
			print '<script data-type="refresh" type="text/javascript">';
			print 'jQuery(location).attr("href", "' . $link . '");';
			print '</script>';
			$this->stop();
			exit;
		}
/*
		try {
			$this->stop();
		} catch (\Slim\Exception\Stop $e) {
			
		}
*/

		header("HTTP/1.1 301 Moved Permanently");
		parent::redirect($link, $code);
		exit;

		//Temp fix!
		//$this->flashKeep();
		header('Location: ' . $link, true, $code);
		header('Location: ' . $link, true);
		exit;
		parent::redirect($link, $code);
		exit;

		//Redirect with header
		//parent::redirect($link, $code);
	}

	public function redirectAfterForm($where, $formValues) {
		if ($this->config['slim']['url_prefix']) {
			parent::redirect($this->config['slim']['url_prefix'] . $where, $code);
		} else {
			parent::redirect($where, $code);
		}
	}

	public function toJSON($content, $stop = false, $statuscode = false) {
		//Check status code
		if (!$statuscode) {
			$statuscode = 200;
		}

	    //Set response
	    $response = $this->response();
	    $response['Content-Type'] = 'application/json';
	    if ($this->isAPI() && isset($this->User['Usertoken'])) {
	    	//If user has chosen dynamic token
	    	if ($this->User['User']->dynamic_token) {
	    		//Output dynamic token to view
	    		$response['DynamicAuthentication'] = Usertoken::updateDynamicToken();
	    	} else {
	    		$response['DynamicAuthentication'] = Usertoken::updateDynamicToken();
	    	}
	    }

	    //Format response
	    //First all controls..
	    $resp = [
	    	'Error' => [],
	    	//'NextToken' => 'to_do_list_forever',
	    	'StatusCode' => $statuscode,
	    	'Count' => 0
	    ];
	    //For changes content
	    if (isset($content['Changes'])) {
	    	$resp['Changes'] = $content['Changes'];
	    	unset($content['Changes']);
	    }
	    //..then data
	    $resp['Values'] = [];

	    //Set special
	    if (is_array($content)) {
	    	if (isset($content['Error'])) {
	    		$resp['Error'] = $content['Error'];
	    	} else if (isset($content[0])) {
	    		//Set values
	    		$resp['Values'] = $content;
	    		$resp['Count'] = is_array($content) ? count($content) : 1;
	    	} else {
	    		$resp = array_merge($resp, $content);
	    	}
	    } else if ($statuscode == 400) {
	    	//Bad request, response is ERROR
	    	$resp['Error'] = $content;
	    }

	    if ($this->request()->get('debug')) {
	    	pr($resp); exit;
	    }

	    //Fill response body
	    $body = json_encode($resp);
	    $response->body($body);
	    $response['Content-Length'] = strlen($body);

	    //Set status code
	    if (is_int($statuscode)) {
	    	$response->setStatus($statuscode);
	    }

	    //Check for stop
	    if ($stop) {
	    	$this->Stop();
	    }
	}

	public function toStatusCode($status, $ok = 200, $error = 400) {
		return $status ? $ok : $error;
	}

	public function addRequestDuration($time, $url) {
		$this->requests[] = [$time, $url];
	}

	public function FormatDate($date) {
		return (new \DateTime($date))->format($this->Constants->DATE_FORMAT);
	}

	//Returns a list of all possible fields we can update on POST /update in each controller.
	public function getPostUpdateFields() {
		$get = $_GET;

		//Get what we can
		$update = [];
		foreach ($get as $key => $value) {
			if ($key != 'id') {
				$update[$key] = $value;
			}
		}

		return $update;
	}

	public function isMobile() {
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		if (
			preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent) ||
			preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))
		) {
			return true;
		}
		return false;
	}

	//Sets paginate options for view helper
	public function setPaginate($obj) {
		$paginate = [
			'PageSize' => $obj->PageSize,
			'PageNumber' => $obj->PageNumber,
			'Total' => $obj->Total,
			'PagesNumber' => ceil($obj->Total / $obj->PageSize)
		];
		$this->Paginate = $paginate;
	}

	//Gets paginate options from request
	public function getPaginate() {
		return [
			'PageSize' => $this->request()->get('pagesize', $this->Constants->PAGINATE_DEFAULT_PAGESIZE),
			'PageNumber' => $this->request()->get('page', 1),
			'OrderDescending' => 'true'
		];
	}

	//Default search filter for measurements in patient or doctor archive
	public function searchFilter() {
		//Get values
		$options = [
			'DateFrom' => $this->request()->get('DateFrom', ''),
			'DateTo' => $this->request()->get('DateTo', ''),
			'Type' => strtolower($this->request->get('Type', ''))
		];

		//Check for valid input measurement
		if (!in_array($options['Type'], $this->Constants->MEASUREMENTS)) {
			unset($options['Type']);
		} else {
			$this->view()->setData('search_type', $options['Type']);
		}
		if (!empty($options['DateFrom'])) {
			$this->view()->setData('search_datefrom', $options['DateFrom']);
			$options['DateFrom'] = date('c', strtotime($options['DateFrom']));
		}
		if (!empty($options['DateTo'])) {
			$this->view()->setData('search_dateto', $options['DateTo']);
			$options['DateTo'] = date('c', strtotime($options['DateTo']) + 3600 * 24);
		}

		return $options;
	}

	//Validates data, in case data == false, it returns false or redirects if needed
	public function validate($data, $redirect = true) {
		if ($data !== false) {
			return $data;
		}
		if ($redirect) {
			$this->flashError(__('Unknown error has occurred!'));
			$this->redirect($this->urlFor('dashboard'));
		}
		return false;
	}

	/** Flash messages **/
	public function flashSuccess($message) {
		$this->flash('success', $message);
	}
	public function flashWarning($message) {
		$this->flash('warning', $message);
	}
	public function flashNote($message) {
		$this->flash('info', $message);
	}
	public function flashError($message) {
		$this->flash('danger', $message);
	}
	public function flashSuccessNow($message) {
		$this->flashNow('success', $message);
	}
	public function flashWarningNow($message) {
		$this->flashNow('warning', $message);
	}
	public function flashNoteNow($message) {
		$this->flashNow('info', $message);
	}
	public function flashErrorNow($message) {
		$this->flashNow('danger', $message);
	}

	//Returns true if web is hosted on production server
	public function isProductionServer() {
		return strpos($_SERVER['HTTP_HOST'], $this->config['slim']['production_server']) !== false;
	}

	//Returns true if web is hosted on development server
	public function isDevelopmentServer() {
		return !$this->isProductionServer();
	}

	//Functions checks if browser is valid
	public function isValidBrowser() {
		//Get current browser
		$b = $this->getBrowser();

		//Go through all blacklisted browsers
		foreach ($this->config['browsers'] as $browser) {
			//Check if exists on black list
			if ($browser[0] == $b['name'] && $browser[1] == $b['version']) {
				return false;
			}
		} 

		//Browser is valid
		return true;
	}


	//Gets browser used for request
	public function getBrowser() { 
	    $u_agent = $_SERVER['HTTP_USER_AGENT']; 
	    $bname = 'Unknown';
	    $platform = 'Unknown';
	    $version= "";

	    //First get the platform?
	    if (preg_match('/linux/i', $u_agent)) {
	        $platform = 'linux';
	    } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
	        $platform = 'mac';
	    } elseif (preg_match('/windows|win32/i', $u_agent)) {
	        $platform = 'windows';
	    }
	    
	    // Next get the name of the useragent yes seperately and for good reason
	    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
	        $bname = 'Internet Explorer'; 
	        $ub = "MSIE"; 
	    } elseif (preg_match('/Firefox/i',$u_agent)) { 
	        $bname = 'Mozilla Firefox'; 
	        $ub = "Firefox"; 
	    } elseif (preg_match('/Chrome/i',$u_agent)) { 
	        $bname = 'Google Chrome'; 
	        $ub = "Chrome"; 
	    } elseif (preg_match('/Safari/i',$u_agent)) { 
	        $bname = 'Apple Safari'; 
	        $ub = "Safari"; 
	    } elseif (preg_match('/Opera/i',$u_agent)) { 
	        $bname = 'Opera'; 
	        $ub = "Opera"; 
	    } elseif (preg_match('/Netscape/i',$u_agent)) { 
	        $bname = 'Netscape'; 
	        $ub = "Netscape"; 
	    } 
	    
	    // finally get the correct version number
	    $known = array('Version', $ub, 'other');
	    $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
	    if (!preg_match_all($pattern, $u_agent, $matches)) {
	        // we have no matching number just continue
	    }
	    
	    // see how many we have
	    $i = count($matches['browser']);
	    if ($i != 1) {
	        //we will have two since we are not using 'other' argument yet
	        //see if version is before or after the name
	        if (strripos($u_agent,"Version") < strripos($u_agent, $ub)) {
	            $version= $matches['version'][0];
	        } else {
	            $version= $matches['version'][1];
	        }
	    } else {
	        $version= $matches['version'][0];
	    }
	    
	    // check if we have a number
	    if ($version == null || $version == "") {
	    	$version = "?";
	    }
	    
	    return array(
	        'userAgent' => $u_agent,
	        'name'      => $bname,
	        'version'   => $version,
	        'platform'  => $platform,
	        'pattern'   => $pattern
	    );
	}

	//Check user access
	public function userAccess() {
		//Admin has access to everywhere
		if ($this->user_logged && $this->User->user_group == 1) {
			return true;
		}
		return false;
	}

	//urlFor custom
	public function urlFor($namedRoute, $params = array()) {
		//Add collection ID to link if possible
		if (is_array($params) && isset($this->RouteParams['collection_id']) && !isset($params['collection_id'])) {
			$params['collection_id'] = $this->RouteParams['collection_id'];
		}

		return parent::urlFor($namedRoute, $params);
	}

    //Returns true if request is from API
    public function isAPI() {
    	return (strcmp($this->Controller, 'Rest') == 0);
    }

    //Returns true if request was from android
    public function isAndroid() {
    	return (isset($_SERVER['HTTP_ANDROID']) && $_SERVER['HTTP_ANDROID'] == 'Android');
    }

    //Convert object to array
    public function obj2array($obj) {
    	//Return json string as array
    	return array_merge([], (array)json_decode(json_encode($obj), true));
    }

    //Convert object to array
    public function array2obj($obj) {
    	//Return json string as array
    	return json_decode(json_encode($obj), false);
    }

    //Gets user ID
    public function userid() {
    	return $this->User['User']->id;
    }

    //Gets user access group
    public function usergroup() {
    	return $this->User['User']->access_group;
    }

    //Returns true if user is admin or false if not
    public function isAdmin() {
    	return $this->User['User']->access_group == 1 && isset($_SESSION['admin_' . $this->User['User']->id]);
    }

    //Sets title for layout
    public function setTitle($title) {
    	//TODO
    	$this->__siteTitle = $title;
    }
    
    //Adds new comment to database
    public function commentAdd($model, $foreign_id, $values) {
    	if (!isset($values['comment'])) {
    		return false;
    	}

    	$data = [
    		'model' => $model,
    		'foreign_id' => $foreign_id,
    		'comment' => $values['comment']
    	];

    	return Comment::insert($data);
    }

    //Get comments and set it to view
    public function commentSetView($model, $foreign_id) {
    	$this->view()->set('comments', Comment::getComments(null, $model, $foreign_id));
		$this->view()->set('comments_model', $model);
		$this->view()->set('comments_foreign_id', $foreign_id);
    }

    //Adds data to session
    public function addDataToSession($key, $data) {
    	if (!isset($_SESSION['data'])) {
    		$_SESSION['data'] = [];
    	}
    	$_SESSION['data'][$key] = $data;
    }

    //Get data from session
    public function getDataFromSession($key, $default = false, $reset = true) {
    	if (isset($_SESSION['data'][$key])) {
    		$data = $_SESSION['data'][$key];
    		if ($reset) {
    			unset($_SESSION['data'][$key]);
    		}
    		return $data;
    	}
    	return $default;
    }

    //Returns URI starting with "http..."
    public function getHostUri() {
    	$r = $this->request();
    	$uri = $r->getScheme();
    	$uri .= '://';
    	$uri .= $r->getHost();

    	return $uri;
    }

    //Returns true if we should open as modal if content supports it
    public function isModal() {
    	$isModal = (bool)$this->request()->get('modal', false);
    	return $isModal && $this->request()->isAjax();
    }

    //Hash input string
    public function hash($input) {
    	return hash('sha256', $input);
    }

    //Returns user setting value
    public function get_user_setting($setting, $user = null) {
		if (!$user) {
            $user = $this->User;
        }
        if (!$user || !isset($user['Usersetting'])) {
            return null;
        }

        //Return all settings
        if ($setting == null) {
            return $user['Usersetting'];
        }

        //Return specific setting
        if (isset($user['Usersetting'][$setting])) {
            return $user['Usersetting'][$setting];
        }
        return null;
    }

    //Log user from system
    public function logout() {
    	if (isset($_COOKIE['user'])) {
    		setcookie('user', null, -1, '/');
    		unset($_COOKIE['user']);
    	}
    }


    ///////////////////////////////////
    ///////////////////////////////////
    ///////////////////////////////////
    ///////////////////////////////////
    ///////////////////////////////////
    // View helper functions for controller
    public function fa($name, $text = null) {
        return '<i class="fa fa-' . $name . '">' . $text . '</i>';
    }

    //Returns number for model name
    public function get_comments_type($modelName) {
        switch (strtolower($modelName)) {       
            case 'collection':
                return Comment::MODEL_COLLECTION;
            case 'category':
                return Comment::MODEL_CATEGORY;
            case 'element':
                return Comment::MODEL_ELEMENT;
            case 'property':
                return Comment::MODEL_PROPERTY;
            case 'product':
                return Comment::MODEL_PRODUCT;
            case 'order': 
            case 'elementorder': 
                return Comment::MODEL_ORDER;
            case 'user':
                return Comment::MODEL_USER;
            default: 
                return 0;
        }

        return 0;
    }

    //Returns event date time
    //Created at: 2 minutes ago, etc
    public function get_eventdatetime($datetimestring) {
        if (!$datetimestring) {
            return '';
        }
        $date = new \DateTime($datetimestring);
        $display = $date->format('d.m.Y' . ' ' . 'H:i');
        $original = $date->format(\DateTime::ISO8601);

        return '<span class="event_datetime" data-datetime="' . $original . '">' . $display . '</span>';
    }

    //Returns event date time and user name
    //Created at: 2 minutes ago, etc
    public function get_event_user_datetime($user, $date) {
        return sprintf(__('%s, %s'), $this->get_user_fullname($user), $this->get_eventdatetime($date));
    }

    //Returns user full name
    public function get_user_fullname($user) {
        return $user->first_name . ' ' . $user->last_name;
    }
}
