<?php

namespace routes\secure;
/**
 * Description of index
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Index extends \routes\Layout
{
	public function scope_body()
	{
		$this->viewName = 'index';
		$this->scopeName = 'BODY';
	}
}
