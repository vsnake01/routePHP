<?php

/**
 * Description of dispatcher
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Dispatcher
{
	private $prefix = '';
	
	public function __construct()
	{
		if (LOGGED) {
			$this->prefix = LOGGED_TYPE.'\\';
		}
		$action = 'routes\\' . $this->prefix . $this->getActionName();
		
		if (!class_exists($action)) {
			$action = 'routes\\' . $this->getActionName();
			
			if (!class_exists($action)) {
				$action = 'routes\\index';
			}
		}
		
		$route = new $action;
		
		$route->render();
	}
	
	private function getActionName()
	{
		return Url::getPart(1) ? Url::getPart(1) : 'index';
	}
}
