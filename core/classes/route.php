<?php
/**
 * Description of route
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */

use \User;

class Route extends Config
{
	protected $HTML;
	protected $prefix='';
	protected $theme='empty';
	protected $isAjax=false;
	protected $scoped=array();
	
	protected $viewName=null;
	protected $scopeName=null;
	protected $params=null;
	
	/**
	 * @var Token
	 */
	private $token;
	
	static protected $SCOPE=null;
	
	protected $compiled = array();
	
	public function __construct()
	{
		parent::__construct();
		
		if ($theme = $this->getConfig('theme')) {
			$this->theme = $theme['name'];
		}
		
		$this->token = new Token();
		
		$class = get_class($this);
		
		$this->logger = new Logger($class);

		$ns = explode('\\', $class);
		
		if (isset ($ns[1])) {
			//We are using namespace
			$this->prefix = strtolower($ns[1]);
		}
		
		$this->fireHooks();
		
		$this->fillAllScopes();
	}
	
	/**
	 * Takes all "scope_" methods and run them starting from parent
	 */
	private function fillAllScopes()
	{
		// we need to create list of methods to run
		// using first parent class, then current etc.
		$methods = array ();
		
		$parents = class_parents($this);
		
		$parents = array_reverse($parents);
		
		if (get_class($this) != get_class()) {
			$parents[] = get_class($this);
		}
		
		foreach ($parents as $class_name) {
			$m = get_class_methods($class_name);
			foreach ($m as $method) {
				if (
					preg_match ('/^scope_/i', $method)
				) {
					if (!in_array($method, $methods)) {
						$methods[] = $method;
					}
				}
			}
		}
	
		foreach ($methods as $method) {
			$this->runScope($method);
		}
	}
	
	/**
	 * Fire hooks
	 * @return boolean 
	 */
	protected function fireHooks()
	{
		/*self::$hookTrigger++;
		$parents = class_parents($this);
		
		if (!is_array ($parents)) {
			return false;
		}
		if (self::$hookTrigger < count($parents)) {
			return;
		}
		*/
		// Let do some hooks
		if (!empty($_GET)) {
			$this->_GET();
		}
		if (!empty($_POST)) {
			$this->_POST();
		}
		if (!empty($_FILES)) {
			$this->_FILES();
		}
		if (\Url::isSuccess()) {
			$this->_SUCCESS();
		}
		if (\Url::isError()) {
			$this->_ERROR();
		}
		if(
			!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		) {
			$this->isAjax = true;
			$this->_AJAX();
			exit;
		}
		
		$this->before();
	}
	
	public function __destruct() {
		;
	}
	
	protected function before()
	{
		
	}
	
	protected function after()
	{
		
	}
	
	/**
	 * Hook for AJAX 
	 */
	protected function _AJAX()
	{
		
	}
	
	/**
	 * Hook for POST 
	 */
	protected function _POST()
	{
		
	}
	
	/**
	 * Hook for FILES upload
	 */
	protected function _FILES()
	{
		
	}

	/**
	 * Hook for GET
	 */
	protected function _GET()
	{
		
	}
	
	/**
	 * Hook for success 
	 */
	protected function _SUCCESS()
	{
		
	}
	
	/**
	 * Hook for error
	 */
	protected function _ERROR()
	{
		
	}
	
	/**
	 * Return rendered HTML or set new one
	 * @param String $html Optional. 
	 * @return String
	 */
	public function html($html=null)
	{
		if ($html !== null) {
			$this->HTML = $html;
		}
		return $this->HTML;
	}
	
	/**
	 * External method to start rendering ALL 
	 */
	public function render()
	{
		echo $this->html();
		
		$this->after();
	}
	
	public function setLocalVariables()
	{
		;
	}

	/**
	 * Compile one view only
	 * @param String $name View name
	 * @param Array $params Array of parameters to pass to view
	 * @return String HTML
	 */
	protected function compileThis($name, $params=null)
	{
		return $this->compileElement($name, $params);
	}
	
	/**
	 * Compile View file with filling of all scopes
	 * @param String $name View name
	 * @param String $scope Scope to replace with content
	 * @param Array $params Array of parameters to pass to view
	 * @return void 
	 */
	protected function compile()
	{
		$name = $this->viewName;
		$scope = $this->scopeName;
		$params = $this->params;
		
		$html = $this->html();
		
		if ($scope && (stristr($html, '{'.$scope.'}')===false)) {
			// no such scope detected;
			// Prevent compilation
			return false;
		}
		
		$local_html = $this->compileElement($name, $params);
		
		$local_html = $this->fillTokens($local_html);
		
		if ($scope) {
			$html = str_replace ('{'.$scope.'}', $local_html, $html);
		} else {
			$html = $local_html;
		}
		
		$this->html($html);

	}
	
	/**
	 * Compile one only View file without recursion
	 * @param String $name View name
	 * @param Array $params Array of parameters to pass to view
	 * @return String HTML 
	 */
	private function compileElement($name, $params=null)
	{
		$view = strtolower(str_replace ('routes\\'.$this->prefix.'\\', '', $name));
		$view = strtolower(str_replace ('routes\\', '', $view));
		
		$FILE_S = null;
		$FOLDER_S = null;
		$FILE = null;
		$FOLDER = null;

		$FILE_S_BRAND = null;
		$FOLDER_S_BRAND = null;
		$FILE_BRAND = null;
		$FOLDER_BRAND = null;
		
		$folder = PATH_VIEWS . strtolower('/themes/' . $this->theme . '/' . $view);
		$file = $folder.'.php';
		
		$FILE = $file;
		$FOLDER = $folder;
		
		$folder = PATH_VIEWS . strtolower('/themes/' . BRAND . '/' . $view);
		$file = $folder.'.php';
		
		$FILE_BRAND = $file;
		$FOLDER_BRAND = $folder;
		
		if ($this->prefix) {
			// We have some special content
			$folder_ = PATH_VIEWS . strtolower('/themes/' . $this->theme . '/' . $this->prefix . '/' . $view);
			$file_ = $folder_.'.php';
			
			$FILE_S = $file_;
			$FOLDER_S = $folder_;
			
			$folder_brand_ = PATH_VIEWS . strtolower('/themes/' . BRAND . '/' . $this->prefix . '/' . $view);
			$file_brand_ = $folder_.'.php';
			
			$FILE_S_BRAND = $file_brand_;
			$FOLDER_S_BRAND = $folder_brand_;
		}
		
		// We need to check all paths like /route/bla/bla/bla for existing views
		for ($i=2; $i<10; $i++) {
			
			if (\Url::getPart($i)) {
				$view .= '/'.strtolower(\Url::getPart($i));
			} else {
				break;
			}
			
			// Insecure content
			$folder = PATH_VIEWS . strtolower('/themes/' . $this->theme . '/' . $view);
			$file = $folder.'.php';
			
			$folder_brand = PATH_VIEWS . strtolower('/themes/' . BRAND . '/' . $view);
			$file_brand = $folder.'.php';
			
			if ($this->prefix) {
				// We have some special content
				$folder_ = PATH_VIEWS . strtolower('/themes/' . $this->theme . '/' . $this->prefix . '/' . $view);
				$file_ = $folder_.'.php';

				$folder_brand_ = PATH_VIEWS . strtolower('/themes/' . BRAND . '/' . $this->prefix . '/' . $view);
				$file_brand_ = $folder_.'.php';
				
				if (file_exists($folder_brand_)) {
					continue;
				}
				
				if (file_exists($file_brand_)) {
					$FILE_S_BRAND = $file_;
				}
				
				if (file_exists($folder_)) {
					continue;
				}
				
				if (file_exists($file_)) {
					$FILE_S_BRAND = $file_;
				}
			}
			
			if (file_exists($folder_brand)) {
				// This is a folder
				continue;
			}
			
			if (file_exists($file_brand)) {
				$FILE_BRAND  = $file;
			}
			
			if (file_exists($folder)) {
				// This is a folder
				continue;
			}
			
			if (file_exists($file)) {
				$FILE  = $file;
			}
			
			
			break;
		}
		
		ob_start();
		
		if (is_file($FILE_S_BRAND)) {
			// If we have access to private area
			include $FILE_S_BRAND;
		} elseif (is_file($FILE_S)) {
			// If we have access to private area
			include $FILE_S;
		} elseif (file_exists($FOLDER_S_BRAND) && file_exists ($FOLDER_S_BRAND.'/index.php')) {
			// If we have access to private area
			include $FOLDER_S_BRAND.'/index.php';
		} elseif (file_exists($FOLDER_S) && file_exists ($FOLDER_S.'/index.php')) {
			// If we have access to private area
			include $FOLDER_S.'/index.php';
		} elseif (is_file($FILE_BRAND)) {
			// If we have access to private area
			include $FILE_BRAND;
		} elseif (is_file($FILE)) {
			// If we have access to private area
			include $FILE;
		} elseif (file_exists($FOLDER_BRAND) && file_exists ($FOLDER_BRAND.'/index.php')) {
			// If we have access to private area
			include $FOLDER_BRAND.'/index.php';
		} elseif (file_exists($FOLDER) && file_exists ($FOLDER.'/index.php')) {
			// If we have access to private area
			include $FOLDER.'/index.php';
		} else {
			include PATH_VIEWS . '/404.php';
		}
		
		$local_html = ob_get_contents();
		
		ob_end_clean();
		
		return $local_html;
	}
	
	public function scope_()
	{
		$this->viewName = 'route';
	}
	
	private function runScope($method) {
		if (isset($this->scoped[$method])) {
			return null;
		}
		$this->scoped[$method] = true;
		$this->$method();
		$this->compile($this->viewName, $this->scopeName);
	}
	
	/**
	 * Return TOKEN value
	 * @param String $name
	 * @return String
	 */
	public function t($name)
	{
		if (is_array ($name)) {
			$ret = array ();
			foreach ($name as $k=>$v) {
				$ret[$k] = $this->token->get($v);
			}
		} else {
			$ret = $this->token->get($name);
		}
		return $ret;
	}
	
	/**
	 * Outputs token value
	 * @param String $name 
	 */
	public function e($name)
	{
		echo $this->t($name, false);
	}
	
	/**
	 * Scan and replace hashes like ###TOKEN### with TOKEN value
	 * @param String $html
	 * @return String HTML with replaced hashes
	 */
	protected function fillTokens($html)
	{
		$htmlt = explode ('###', $html);
		$htmlc = count($htmlt);
		
		for ($i=1;$i<$htmlc;$i+=2) {
			$value = $this->token->get($htmlt[$i]);
			$html = str_replace ('###'.$htmlt[$i].'###', $value, $html);
		}
		
		return $html;
	}
}
