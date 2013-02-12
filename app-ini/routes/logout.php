<?php
namespace routes;

/**
 * Description of user
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Logout extends Layout
{
	public function __construct()
	{
		\Session::destroy();
		\Redirect::index();
	}
	
}
