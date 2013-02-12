<?php
namespace routes;

/**
 * Description of user
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Register extends Layout
{
	public function _POST()
	{
		$user = new \User;
		if ($user->check($_POST['email'])) {
			$this->logger->error ("Email already exists. " . print_r($_POST, true));
			\Redirect::errorHere($this->t('The email you are using is already exists in our database. Try to login or reset password.'));
		}
		
		if (!$user->beginTransaction()) {
			$this->logger->error ("Can not begin transaction");
			\Redirect::errorHere($this->t('The error has occured. Please contact our support. Error #R101'));
		}
		
		$password = \Password::create();
		$newID = $user->create($_POST['email'], $password, array ('name' => $_POST['name']));
		if (!$newID) {
			$user->rollbackTransaction();
			$this->logger->error ("Can not create new profile");
			\Redirect::errorHere($this->t('Can not create new profile. Error #R102'));
		}
		
		$uid = $user->getUID((int) $newID);
		if (!$uid) {
			$user->rollbackTransaction();
			$this->logger->error ("Can not detect UID of new profile");
			\Redirect::errorHere($this->t('The error has occured. Please contact our support. Error #R103'));
		}
		
		// Send email
		$queue = new \Queue();
		
		$email = $this->getVar ('emails', 'welcome');

		$email = str_replace ('###YOUR LOGIN###', $_POST['name'], $email);
		
		$params = array (
			'email_address' => $_POST['email'],
			'email_html' => $email,
			'email_subject' => 'Registration',
			'newID' => $newID,
		);
		
		if (!$queue->create('mail', serialize($params))) {
			$user->rollbackTransaction();
			$this->logger->error ("Can not create queue");
			\Redirect::errorHere($this->t('The error has occured. Please contact our support. Error #R105'));
		}
		
		if (!$user->commitTransaction()) {
			$this->logger->error ("Can not commit transaction");
			\Redirect::errorHere($this->t('The error has occured. Please contact our support. Error #R106'));
		}
		
		\Redirect::success($this->t('The profile has been created successfully. Check your mailbox for activation link.'));
	}
	
	public function scope_body()
	{
		$this->viewName = 'register';
		$this->scopeName = 'BODY';
	}
}
