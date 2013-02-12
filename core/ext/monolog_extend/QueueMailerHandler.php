<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

/**
 * Base class for all mail handlers
 *
 * @author Valentin Balt
 */
class QueueMailerHandler extends MailHandler
{
	private $queue = null;
	
    public function __construct(\Queue $q, $level = \Monolog\Logger::ERROR, $bubble = true)
    {
        parent::__construct($level, $bubble);
		$this->queue = $q;
    }
	
    protected function send($content, array $records)
    {
		$params = array (
			'email_address' => ADMINEMAIL,
			'email_html' => (string) $content,
			'email_subject' => 'Log Message',
			'email_from' => ADMINEMAIL,
			'text_only' => true,
		);

		$this->queue->create('mail', serialize($params));
    }

}
