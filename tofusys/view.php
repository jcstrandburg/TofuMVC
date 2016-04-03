<?php
class View {
	protected $path;
	protected $data;

	public function __construct($path, $data) {
		$this->path = Config::$basepath.'application\\v\\'.$path.'.view.php';
		$this->data = $data;
	}

	/**
	 * Factory method for creating new 
	 * @param string $path
	 * @param array $data
	 * @return \View
	 */
	static public function Make($path, $data=[]) {
		return new View($path, $data);
	}
	
	/**
	 * Includes a sub view in the current view
	 * @param string $path
	 */
	private function includeFragment($path) {
		$v = new View($path, $this->data);
		$v->render();
	}
	
	/**
	 * Includes a sub view in the current view
	 * @param string $path
	 */	
	private function includeWithData($path, $data) {
		$v = new View($path, $data);
		$v->render();
	}
	
	/**
	 * Adds the given data to the views data array, returns a reference to $this.
	 * @param array $data
	 * @return \View
	 */
	public function withData(array $data) {
		$this->data = array_merge($this->data, $data);
		return $this;
	}

	/**
	 * Renders the view into html.
	 */
	public function render() {
		extract($this->data);
		require($this->path);
	}

	/**
	 * Creates html to include the given script from the scripts folder
	 * @param string $script
	 */
	public function script($script) {
		if (strpos($script, 'http') === false) {
			echo "<script src='http://".Config::$baseurl."js/{$script}'></script>";
		} else {
			echo "<script src='{$script}'></script>";
		}
	}

	/**
	 * Creates html to include the given stylesheet from the styles folder
	 * @param string $style
	 */
	public function style($style) {
		if (strpos($style, 'http') === false) {		
			echo "<link href='http://".Config::$baseurl."css/{$style}' rel='stylesheet'>";
		} else {
			echo "<link href='css/{$style}' rel='stylesheet'>";		
		}
	}

	/**
	 * Creates html for the given image location in the images folder
	 * @param string $img
	 */	
	public function image($img) {
		if (strpos($img, 'http') === false) {		
		} else {
			echo "<img src='img/{$img}'>";
		}
	}

	/**
	 * Echos a form open tag for the given http method and linking to the given route in the application
	 * @param string $method
	 * @param string $route
	 */
	public function formopen($method, $route) {
		ob_start();
		$action = '//'.Config::$baseurl.$route;
		echo "<form method='{$method}' action='{$action}'>";
		echo ob_get_clean();
	}

	public function formclose() {
		echo '</form>';
	}
	
	
	public function routeURL($route) {
		$url = "http://".Config::$baseurl.$route;
		echo $url;
	}	
}