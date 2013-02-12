<?php
$form = array (
	'form' => array (
		'name' => 'Login',
		'legend' => $this->t('Sign In'),
		'action' => '/login',
		''
	),
	'elements' => array (
		array (
			'name' => 'email',
			'label' => $this->t('Email'),
			'type' => 'Email',
			'properties' => array (
				'required' => 1,
				'longDesc' => $this->t('Email address you register with.'),
			),
		),
		array (
			'name' => 'password',
			'label' => $this->t('Password'),
			'type' => 'Password',
			'properties' => array (
				'required' => 1,
			)
		),
		array (
			'name' => 'submit',
			'label' => $this->t('Sign In'),
			'type' => 'Button',
			'value' => $this->t('Sign In'),
		),
	),
);
?>
<?php echo \Form::render($form, true)?>

