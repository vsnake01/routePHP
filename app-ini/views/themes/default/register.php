<?php
$form = array (
	'form' => array (
		'name' => 'Deposit',
		'legend' => $this->t('Open New Profile'),
		'action' => '/register',
	),
	'elements' => array (
		array (
			'name' => 'name',
			'label' => $this->t('Full Name'),
			'type' => 'Textbox',
			'properties' => array (
				'required' => 1,
				'longDesc' => $this->t('We need your Full Name'),
			),
		),
		array (
			'name' => 'email',
			'label' => $this->t('Email'),
			'type' => 'Email',
			'properties' => array (
				'required' => 1,
				'longDesc' => $this->t('We need existing and valid Email address.'),
			)
		),
		array (
			'name' => 'submit',
			'label' => $this->t('Sign Up for Free'),
			'type' => 'Button',
			'value' => $this->t('Sign Up for Free'),
		),
		array (
			'type' => 'HTML',
			'value' => '<i class="icon-info-sign"></i>&nbsp;###You will receive a confirmation email. Please, check your SPAM/JUNK folder in case you can not find it in your INBOX###',
		),
	),
);
echo \Form::render($form, true);
