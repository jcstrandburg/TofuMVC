<?php 
require_once('../tofusys/config.php');
require_once('../tofusys/view.php');
require_once('../tofusys/request.php');
require_once('../tofusys/model.php');
require_once('../tofusys/basecontroller.php');

/**
 * Helper function to dump a human readable version of a variable to the page
 * @param mixed $printme
 * @param depth $depth
 * @return string
 */
function pprint($printme, $depth=1) {
	if (is_null($printme)) { return 'NULL'; }

	ob_start();
	switch (gettype($printme)) {
		case 'array':
			echo 'Array ('.count($printme).') {<br>';
			foreach ($printme as $key=>$value) {
				echo str_repeat('&nbsp;', 3*$depth);
				echo pprint($key, 0)." => ".pprint($value, $depth+1).",<br>";
			}
			echo str_repeat('&nbsp;', 3*($depth-1));
			echo '}';
			break;
		case 'object':
			$classname = get_class($printme);
			if ($classname == 'Record') {
				echo 'Record-';
				echo pprint($printme->toArray(), $depth);
			} else {
				echo "Class `{$classname}` {<br>";
				foreach ($printme as $key=>$value) {
					echo str_repeat('&nbsp;', 3*$depth);
					echo $key." => ".pprint($value, $depth+1).",<br>";
				}			
				echo '}';
			}
			break;
		case 'string':
			echo '`'.$printme.'` ['.gettype($printme).'('.strlen($printme).')]';
			break;
		case 'boolean':
			if ($printme) {
				echo 'TRUE [boolean]';
			} else {
				echo 'FALSE [boolean]';
			}
			break;
		default:
			echo $printme.' ['.gettype($printme).']';
			break;
	}
	return ob_get_clean();
}

/**
 * Main framework driver class, does all the important stuff
 */
class Tofu {
	static $errorStrings = [];
	static $warningStrings = [];

	/**
	 * @var string $controllerName
	 */
	private $controllerName;

	/**
	 * @var string $action
	 */
	private $action;

	/**
	 * @var array $params
	 */
	private $params;

	public function __construct() {
		$fullurl = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];		
		$this->parseURL($fullurl);

		set_include_path(get_include_path() . PATH_SEPARATOR . '..\\application' 
			. PATH_SEPARATOR . '..\\application\\c' . PATH_SEPARATOR . '..\\application\\m');
		spl_autoload_register('Tofu::autoloadClass');

		$c = self::loadController($this->controllerName);
		$result = $c->invoke($this->action, $this->params);
		if (is_a($result, 'View')) {
			$result->render();
		} else {
			$object = ['status'=>'success', 'warnings'=>self::getWarningStrings(), 'errors'=>self::getErrorStrings(), 'data'=>$result];
			if (count($object['errors']) > 0) {
				$object['status'] = 'error';
			} else if (count($object['warnings']) > 0) {
				$object['status'] = 'warning';
			}
			echo json_encode($object);
		}
	}

	/**
	 * Parses the controller, action, and parameters from the url, stored them in object properties
	 * @param type $fullurl
	 * @throws Exception On malformed url or url not in expected format
	 */
	public function parseURL($fullurl) {
		$controllerName = 'Base';
		$action = 'index';
		$params = [];

		$urlparsed = parse_url($fullurl);
		if ($urlparsed === null) {
			throw new Exception('Could not parse url '.$urlparsed);			
		}else if (strpos($urlparsed['path'], Config::$baseurl) !== 0) {
			throw new Exception('Could not find base url as expected');
		} else {
			//parse controller, action, and params from url
			$tokens = explode('/', substr($urlparsed['path'], strlen(Config::$baseurl)));
			if (count($tokens) > 0 && strlen($tokens[0]) > 0) {
				$controllerName = $tokens[0];
			}
			if (count($tokens) > 1 && strlen($tokens[1]) > 0) {
				$action = $tokens[1];
			}
			if (count($tokens) > 2) {
				$params = array_slice($tokens, 2);
				if ($params[count($params)-1] === '') {
					unset($params[count($params)-1]);
				}
			}

			$this->controllerName = $controllerName;
			$this->action = $action;
			$this->params = $params;
		}
	}
	
	/**
	 * Loads the requested controller and returns an instance of it
	 * @param string $controller
	 * @return \BaseController
	 */
	public static function loadController($controller) {
		$controller = $controller.'Controller';
		return new $controller();
	}

	/**
	 * Autoloader for spl_autoload_register
	 * @param string $classname
	 */
	public static function autoloadClass($classname) {
		include_once(strtolower($classname).'.php');
	}	

	/**
	 * Addes the given error to the list of accumulated errors
	 * @param string $error
	 */
	public static function raiseError($error) {
		self::$errorStrings[] = $error;
	}

	/**
	 * @return array[string]
	 */
	public static function getErrorStrings() {
		return self::$errorStrings;
	}

	/**
	 * Addes the given warning to the list of accumulated warning
	 * @param string $warning
	 */
	public static function raiseWarning($warning) {
		self::$warningStrings[] = $warning;
	}

	/**
	 * @return array[string]
	 */
	public static function getWarningStrings() {
		return self::$warningStrings;
	}
	
	/**
	 * Redirects to the specified route
	 * @param  string $route
	 */
	public static function redirect($route) {
		$redirectLocation = "http://".Config::$baseurl.$route;
		//die($redirectLocation);
		header("Location: ".$redirectLocation);
	}
}

$t = new Tofu();