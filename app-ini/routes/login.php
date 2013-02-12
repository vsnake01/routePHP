<?php
namespace routes;

/**
 * Description of user
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Login extends Layout
{
	public function _POST()
	{
		$user = new \User;
		
		if (
			$_POST['email'] && $_POST['password']
			&& $id=$user->check((string) $_POST['email'], (string) $_POST['password'])
		) {
			// User credentials correct
			$user->load($id);
			
			\Session::set('logged', $id);
			\Session::set('logged_type', $user->get('type'));
			\Session::set('logged_name', $user->get('name'));
			\Session::set('logged_email', $user->get('email'));
			
			\Redirect::url('/');
		} else {
			\Redirect::error($this->t('Wrong credentials. Please, check your email and password or reset your password.'));
		}
	}
	
	public function scope_body()
	{
		$this->viewName = 'login';
		$this->scopeName = 'BODY';
	}
}
