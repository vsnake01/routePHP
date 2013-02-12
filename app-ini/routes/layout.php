<?php
namespace routes;

/**
 * Description of layout
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Layout extends \Route
{
	public function scope_global()
	{
		$this->viewName = 'layout';
		$this->scopeName = 'RENDER';
	}
	
	protected function scope_body()
	{
		$this->viewName = 'index';
		$this->scopeName = 'BODY';
	}
	
	protected function scope_header()
	{
		$this->viewName = 'header';
		$this->scopeName = 'HEADER';
	}

	protected function scope_messenger()
	{
		$this->viewName = 'messenger';
		$this->scopeName = 'MESSENGER';
	}

	protected function scope_footer()
	{
		$this->viewName = 'footer';
		$this->scopeName = 'FOOTER';
	}
}
