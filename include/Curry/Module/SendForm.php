<?php
/**
 * Curry CMS
 *
 * LICENSE
 *
 * This source file is subject to the GPL license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://currycms.com/license
 *
 * @category   Curry CMS
 * @package    Curry
 * @copyright  2011-2012 Bombayworks AB (http://bombayworks.se)
 * @license    http://currycms.com/license GPL
 * @link       http://currycms.com
 */

/**
 * Module to send POST-data to an email.
 * 
 * Can be be used both with and without a template. Available variables:
 * 
 * * Success (bool): True if the form was posted.
 * * Error (bool): True if there was an error sending the mail.
 * 
 * @package Curry\Module
 */
class Curry_Module_SendForm extends Curry_Module {
	/**
	 * Email address to send the mail to.
	 *
	 * @var string
	 */
	protected $to;
	
	/**
	 * What email are we sending from?
	 *
	 * @var string
	 */
	protected $from;
	
	/**
	 * What name are we sending from?
	 *
	 * @var string
	 */
	protected $sender;
	
	/**
	 * Email subject.
	 *
	 * @var string
	 */
	protected $subject;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->to = "";
		$this->from = Curry_Core::$config->curry->adminEmail;
		$this->sender = Curry_Core::$config->curry->name;
		$this->subject = Curry_Core::$config->curry->name . ', form';
	}
	
	/** {@inheritdoc} */
	public function toTwig()
	{
		$success = false;
		$error = false;
		if($this->getRequest()->isPost()) {
			try {
				$this->sendMail();
				$success = true;
			}
			catch (Exception $e) {
				$error = $e->getMessage();
			}
		}
		return array(
			'Sent' => $success, // deprecated, use Success instead
			'Success' => $success,
			'Error' => $error,
		);
	}
	
	/** {@inheritdoc} */
	protected function sendMail()
	{
		$html = $this->getMailHtml();
		$text = strip_tags($html);
		
		$mail = new Curry_Mail();
		$mail->setBodyText($text);
		$mail->setBodyHtml($html);
		$mail->setSubject($this->subject);
		$mail->setFrom($this->from, $this->sender);
		if(isset($this->getRequest()->post['email']))
			$mail->setReplyTo($this->getRequest()->post['email']);
		foreach(explode(",", $this->to) as $email)
			$mail->addTo(trim($email));
		$mail->send();
	}
	
	/**
	 * Generates the mail HTML content.
	 *
	 * @return string
	 */
	protected function getMailHtml()
	{
		$html = "<html><body><h1>".$this->subject."</h1>\n";
		foreach($this->getRequest()->post as $key => $value)
			$html .= "<p><strong>$key:</strong><br />".nl2br(htmlspecialchars($value))."</p>\n";
		$html .= "<hr />\n".
			"<p>Submitted at: ".date("r")."<br />\n".
			"Submitted from: ".$_SERVER['REMOTE_ADDR']."\n".
			"</p></body></html>";
		return $html;
	}
	
	/** {@inheritdoc} */
	public function showBack()
	{
		$form = new Curry_Form_SubForm(array(
			'elements' => array(
				'to' => array('text', array(
					'label' => 'To',
					'description' => 'List of email adresses to send an email when the form is submitted. Separate multiple emails with a comma (,).',
					'value' => $this->to
				)),
				'subject' => array('text', array(
					'label' => 'Subject',
					'required' => true,
					'value' => $this->subject
				)),
				'from' => array('text', array(
					'label' => 'From email',
					'required' => true,
					'value' => $this->from
				)),
				'sender' => array('text', array(
					'label' => 'From name',
					'required' => true,
					'value' => $this->sender
				))
			),
		));
		return $form;
	}
	
	/** {@inheritdoc} */
	public function saveBack(Zend_Form_SubForm $form)
	{
		$values = $form->getValues(true);
		$this->to = $values['to'];
		$this->from = $values['from'];
		$this->sender = $values['sender'];
		$this->subject = $values['subject'];
	}
}
