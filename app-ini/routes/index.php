<?php

namespace routes;
/**
 * Description of index
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Index extends Layout
{
	public function scope_body()
	{
		$this->scopeName = 'BODY';
		$this->viewName = 'index';
	}
}
