<?php
class Request {
	private static $method = null;
	private static $inputs = [];
	private static $isajax = null;
	private static $isbuilt = false;

	static public function Build() {
		//parse input from url/input stream and consolidate it
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$ajax = true;
		} else {
			$ajax = false;
		}
		parse_str(file_get_contents("php://input"), $inputs);		
		$inputs = array_merge($_GET, $inputs);
		$method = $_SERVER['REQUEST_METHOD'];
		if ($method === 'POST' && isset($inputs['__method'])) {
			$method = $inputs['__method'];
			unset($inputs['method']);
		}
		if (!in_array($method, ['POST', 'PUT', 'PATCH', 'GET', 'DELETE'])) {
			throw new Exception('Unrecognized method verb: '.$method);
		}
		
		self::$method = $method;
		self::$inputs = $inputs;
		self::$isajax = $ajax;
		self::$isbuilt = true;
	}

	public static function isAjax() {
		if (!self::$isbuilt) { self::Build(); }
		return self::$isajax;
	}
	
	/**
	 * Gets a single named value from the 
	 * @param type $fieldname
	 * @return type
	 */
	public static function input($fieldname) {
		if (!self::$isbuilt) { self::Build(); }
		return self::$inputs[$fieldname];
	}
	
	/**
	 * Gets a combination of all possible GET, POST, PUT, etc.. inputs in a single array
	 * @return array
	 */
	public static function allInputs() {
		if (!self::$isbuilt) { self::Build(); }		
		return self::$inputs;
	}

	/**
	 * Gets the HTTP verb for the request
	 * @return string
	 */
	public static function getMethod() {
		if (!self::$isbuilt) { self::Build(); }		
		return self::$method;
	}
}