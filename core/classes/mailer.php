<?php
/**
 * Description of mailer
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Mailer extends Config
{
	public function __construct()
	{
		parent::__construct();
		
		$this->logger = new Logger(get_class());
	}
	
	public function getSwiftMailer()
	{
		return $this->configureSwift($this->getConfig('framework'));
	}
	
	private function configureSwift()
	{
		if ((@include 'swift_required.php') === false) {
			require 'Swift/swift_required.php';
		}

		$opt = $this->getConfig('options');

		$transport = Swift_SmtpTransport::newInstance($opt['host'], $opt['port'])
			->setUsername($opt['username'])
			->setPassword($opt['password'])
			->setEncryption($opt['encryption'])
			;

		return Swift_Mailer::newInstance($transport);

	}
	
	/**
	 * Send email using Swift
	 * @param string $address Email address to send to
	 * @param string $subject Subject of email
	 * @param string $text Message to send (HTML)
	 * @param string $from Optional. From address. Leave empty to use "info@yourdomain.tld"
	 */
	public function send($address, $subject, $text, $from=null, $text_only=false)
	{
		if (!$from) {
			$from = $this->getConfig('from');
		}
		
		$to = $address;

		$header = $this->getVar ('emails', 'header');
		$footer = $this->getVar ('emails', 'footer');
		
		$messageHTML = $header . ($text) . $footer;
		
		try {
			
			$mailer = $this->configureSwift();
			
			$message = Swift_Message::newInstance($subject)
					
			// Set To
			->setTo($to)

			// Set From message
			->setFrom($from);
			
			if ($bcc = $this->getConfig('bcc')) {
				$message->setBcc($bcc);
			}
			
			if (!$text_only) {
				// Add images
				if ($this->getConfig('images')) {
					foreach ($this->getConfig('images') as $file=>$info) {
						$messageHTML = str_replace (
								'"'.$info['src'].'"',
								'"'.$message->embed(Swift_Image::fromPath(PATH_WWW.$file)).'"',
								$messageHTML
							);
					}
				}

				// Set HTML message
				$message
					->setBody($messageHTML, 'text/html')
					->addPart($text);
			} else {
				$message->setBody($text);
			}

			$ret = $mailer->send($message);
			
			$this->logger->debug('Sent email to: '.$to.': '.(!$ret?'Error':'OK'));
		} catch (Exception $e) {
			
			$this->logger->error('Mailer error: '.$e->getMessage());
		}
	}
	
	public function queueRun($task)
	{
		$i = unserialize($task['params']);
		
		$this->send(
				$i['email_address'], 
				$i['email_subject'], 
				$i['email_html'], 
				empty($i['email_from']) ? null : $i['email_from'], 
				!empty($i['text_only']));
		
		return true;
	}
}
