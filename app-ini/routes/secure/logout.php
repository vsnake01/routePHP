<?php
namespace routes\secure;

/**
 * Description of user
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Logout extends \routes\Layout
{
	public function __construct()
	{
		\Session::destroy();
		\Redirect::index();
	}
	
}
