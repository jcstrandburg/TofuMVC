<?php
class BaseController {
	protected $routes = [];

	public function __construct() {		
	}
	
	/**
	 * Gets all mapped routes for the controller
	 * @return array[string=>string] An array index on action names with values being method names
	 */
	protected function makeRoutes() {
		return [		
			'index' => 'index',
		];
	}	

	//this method is here just to catch unconfigured installations
	public function index() {
		die("Application not configured. No default controller set!");
	}

	/**
	 * Locates the method used to fulfill the given action
	 * 
	 * Long description goes here for stuff and things.
	 * @param  string $action The name of the routed action to invoke
	 * @param  array[string] $params The parameters coming from the url
	 * @return mixed The return value of the method invoked to fulfill the action
	 * @deprecated jimmy john
	 */
	public function invoke($action, array $params) {
		$routes = $this->makeRoutes();
		if (isset($routes[$action])) {
			$method_name = $routes[$action];

			$ref = new ReflectionMethod(get_class($this), $method_name);
			$maxParams = $ref->getNumberOfParameters();
			$minParams = $ref->getNumberOfRequiredParameters();
			if ($maxParams < count($params) || 
				$minParams > count($params)) {
				throw new Exception("Invalid parameter count for route {$action}. Range is {$minParams}-{$maxParams}");
			}

			return call_user_func_array([$this, $method_name], $params);
		} else {
			throw new Exception("Unknown route: {$action}");
		}
	}
}