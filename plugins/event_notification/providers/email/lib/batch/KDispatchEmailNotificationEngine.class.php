<?php
/**
 * @package plugins.emailNotification
 * @subpackage Scheduler
 */
class KDispatchEmailNotificationEngine extends KDispatchEventNotificationEngine
{
	const TO_RECIPIENT_TYPE = 'Address';
	
	const CC_RECIPIENT_TYPE = 'CC';
	
	const BCC_RECIPIENT_TYPE = 'BCC';
	
	const REPLYTO_RECIPIENT_TYPE = 'ReplyTo';
	
	/**
	 * Old kaltura default
	 * @var strung
	 */
	protected $defaultFromMail = 'notifications@kaltura.com';
	 
	/**
	 * Old kaltura default
	 * @var strung
	 */
	protected $defaultFromName = 'Kaltura Notification Service';
	
	/**
	 * @var PHPMailer
	 */
	static protected $mailer = null;
	
	static protected $emailFooterTemplate = null;
	
	/* (non-PHPdoc)
	 * @see KDispatchEventNotificationEngine::__construct()
	 */
	public function __construct(KSchedularTaskConfig $taskConfig, KalturaClient $client)
	{
		if(isset($taskConfig->params->defaultFromMail) && $taskConfig->params->defaultFromMail)
			$this->defaultFromMail = $taskConfig->params->defaultFromMail;
			
		if(isset($taskConfig->params->defaultFromName) && $taskConfig->params->defaultFromName)
			$this->defaultFromName = $taskConfig->params->defaultFromName;

		if($this::$mailer)
		{
			$this::$mailer->ClearAllRecipients();
			$this::$mailer->ClearCustomHeaders();
			$this::$mailer->ClearReplyTos();
			$this::$mailer->ClearAttachments();
		}
		else
		{
			$this::$mailer = new PHPMailer();
			$this::$mailer->CharSet = 'utf-8';
			$this::$mailer->SMTPKeepAlive = true;
		
			if(isset($taskConfig->params->mailPriority) && $taskConfig->params->mailPriority)
				$this::$mailer->Priority = 	$taskConfig->params->mailPriority;
				
			if(isset($taskConfig->params->mailCharSet) && $taskConfig->params->mailCharSet)
				$this::$mailer->CharSet = 	$taskConfig->params->mailCharSet;
				
			if(isset($taskConfig->params->mailContentType) && $taskConfig->params->mailContentType)
				$this::$mailer->ContentType = 	$taskConfig->params->mailContentType;
				
			if(isset($taskConfig->params->mailEncoding) && $taskConfig->params->mailEncoding)
				$this::$mailer->Encoding = 	$taskConfig->params->mailEncoding;
				
			if(isset($taskConfig->params->mailWordWrap) && $taskConfig->params->mailWordWrap)
				$this::$mailer->WordWrap = 	$taskConfig->params->mailWordWrap;
				
			if(isset($taskConfig->params->mailMailer) && $taskConfig->params->mailMailer)
				$this::$mailer->Mailer = 	$taskConfig->params->mailMailer;
				
			if(isset($taskConfig->params->mailSendmail) && $taskConfig->params->mailSendmail)
				$this::$mailer->Sendmail = 	$taskConfig->params->mailSendmail;
				
			if(isset($taskConfig->params->mailSmtpHost) && $taskConfig->params->mailSmtpHost)
				$this::$mailer->Host = 	$taskConfig->params->mailSmtpHost;
				
			if(isset($taskConfig->params->mailSmtpPort) && $taskConfig->params->mailSmtpPort)
				$this::$mailer->Port = 	$taskConfig->params->mailSmtpPort;
				
			if(isset($taskConfig->params->mailSmtpHeloMessage) && $taskConfig->params->mailSmtpHeloMessage)
				$this::$mailer->Helo = 	$taskConfig->params->mailSmtpHeloMessage;
				
			if(isset($taskConfig->params->mailSmtpSecure) && $taskConfig->params->mailSmtpSecure)
				$this::$mailer->SMTPSecure = 	$taskConfig->params->mailSmtpSecure;
				
			if(isset($taskConfig->params->mailSmtpAuth) && $taskConfig->params->mailSmtpAuth)
				$this::$mailer->SMTPAuth = 	$taskConfig->params->mailSmtpAuth;
				
			if(isset($taskConfig->params->mailSmtpUsername) && $taskConfig->params->mailSmtpUsername)
				$this::$mailer->Username = 	$taskConfig->params->mailSmtpUsername;
				
			if(isset($taskConfig->params->mailSmtpPassword) && $taskConfig->params->mailSmtpPassword)
				$this::$mailer->Password = 	$taskConfig->params->mailSmtpPassword;
				
			if(isset($taskConfig->params->mailSmtpTimeout) && $taskConfig->params->mailSmtpTimeout)
				$this::$mailer->Timeout = 	$taskConfig->params->mailSmtpTimeout;
				
			if(isset($taskConfig->params->mailSmtpTimeout) && $taskConfig->params->mailSmtpTimeout)
				$this::$mailer->Timeout = 	$taskConfig->params->mailSmtpTimeout;
				
			if(isset($taskConfig->params->mailSmtpKeepAlive) && $taskConfig->params->mailSmtpKeepAlive)
				$this::$mailer->SMTPKeepAlive = 	$taskConfig->params->mailSmtpKeepAlive;
				
			if(isset($taskConfig->params->mailXMailerHeader) && $taskConfig->params->mailXMailerHeader)
				$this::$mailer->XMailer = 	$taskConfig->params->mailXMailerHeader;
				
			if(isset($taskConfig->params->mailErrorMessageLanguage) && $taskConfig->params->mailErrorMessageLanguage)
				$this::$mailer->SetLanguage($taskConfig->params->mailErrorMessageLanguage);
		}
		
		parent::__construct($taskConfig, $client);
	}
	
	/* (non-PHPdoc)
	 * @see KDispatchEventNotificationEngine::dispatch()
	 */
	public function dispatch(KalturaEventNotificationTemplate $eventNotificationTemplate, KalturaEventNotificationDispatchJobData $data)
	{
		$this->sendEmail($eventNotificationTemplate, $data);
	}

	/**
	 * @param KalturaEmailNotificationTemplate $emailNotificationTemplate
	 * @param KalturaEmailNotificationDispatchJobData $data
	 * @return boolean
	 */
	protected function sendEmail(KalturaEmailNotificationTemplate $emailNotificationTemplate, KalturaEmailNotificationDispatchJobData $data)
	{
		if(!count($data->to) && !count($data->cc) && !count($data->bcc))
			throw new Exception("Recipient e-mail address cannot be null");
			
		$this::$mailer->IsHTML($emailNotificationTemplate->format == KalturaEmailNotificationFormat::HTML);
		
		if($data->priority)
			$this::$mailer->Priority = 	$data->priority;
		if($data->confirmReadingTo)
			$this::$mailer->ConfirmReadingTo = $data->confirmReadingTo;
		if($data->hostname)
			$this::$mailer->Hostname = $data->hostname;
		if($data->messageID)
			$this::$mailer->MessageID = $data->messageID;

		$contentParameters = array();
		if(is_array($data->contentParameters) && count($data->contentParameters))
		{
			foreach($data->contentParameters as $contentParameter)
			{
				/* @var $contentParameter KalturaKeyValue */
				$contentParameters['{' .$contentParameter->key. '}'] = $contentParameter->value;
			}		
		}
			
		if($data->to)
		{
			$recipients = $this->getRecipientArray($data->to, $contentParameters);
			foreach ($recipients as $email=>$name)
			{
				KalturaLog::info("Adding recipient to TO recipients $name<$email>");
				self::$mailer->AddAddress($email, $name);
			}
		}
		
		if($data->cc)
		{
			$recipients = $this->getRecipientArray($data->cc, $contentParameters);
			foreach ($recipients as $email=>$name)
			{
				KalturaLog::info("Adding recipient to CC recipients $name<$email>");
				self::$mailer->AddCC($email, $name);
			}
		}
		
		if($data->bcc)
		{
			$recipients = $this->getRecipientArray($data->bcc, $contentParameters);
			foreach ($recipients as $email=>$name)
			{
				KalturaLog::info("Adding recipient to BCC recipients $name<$email>");
				self::$mailer->AddBCC($email, $name);
			}
		}
		
		if($data->replyTo)
		{
			$recipients = $this->getRecipientArray($data->replyTo, $contentParameters);
			foreach ($recipients as $email=>$name)
			{
				KalturaLog::info("Adding recipient to ReplyTo recipients $name<$email>");
				self::$mailer->AddReplyTo($email, $name);
			}
		}
			
		if(!is_null($data->fromEmail)) 
		{
			$email = $data->fromEmail;
			$name = $data->fromName;
			if(is_array($contentParameters) && count($contentParameters))
			{
					$email = str_replace(array_keys($contentParameters), $contentParameters, $email);
					$name = str_replace(array_keys($contentParameters), $contentParameters, $name);
			}
			
			$this::$mailer->Sender = $email;
			$this::$mailer->From = $email;
			$this::$mailer->FromName = $name;
		}
		else
		{
			$this::$mailer->Sender = $this->defaultFromMail;
			$this::$mailer->From = $this->defaultFromMail;
			$this::$mailer->FromName = $this->defaultFromName;
		}
		KalturaLog::info("Sender [{$this::$mailer->FromName}<{$this::$mailer->From}>]");
		
		$subject = $emailNotificationTemplate->subject;
		$body = $emailNotificationTemplate->body;

		$footer = $this->getEmailFooter();
		if(!is_null($footer))
		{
			$body .= "\n" . $footer;
		}
		
		if(is_array($contentParameters) && count($contentParameters))
		{		
			$subject = str_replace(array_keys($contentParameters), $contentParameters, $subject);
			$body = str_replace(array_keys($contentParameters), $contentParameters, $body);
		}
				
		KalturaLog::info("Subject [$subject]");
		KalturaLog::info("Body [$body]");
		
		$this::$mailer->Subject = $subject;
		$this::$mailer->Body = $body;
	
		if(is_array($data->customHeaders) && count($data->customHeaders))
		{
			foreach($data->customHeaders as $customHeader)
			{
				/* @var $customHeader KalturaKeyValue */
				$key = $customHeader->key;
				$value = $customHeader->value;
				/* @var $customHeader KalturaKeyValue */
				if(is_array($contentParameters) && count($contentParameters))
				{
					$key = str_replace(array_keys($contentParameters), $contentParameters, $key);
					$value = str_replace(array_keys($contentParameters), $contentParameters, $value);
				}
				$this::$mailer->AddCustomHeader("$key: $value");
			}
		}
		
		try
		{
			$success = $this::$mailer->Send();
			if(!$success)
				throw new kTemporaryException("Sending mail failed: " . $this::$mailer->ErrorInfo);
		}
		catch(Exception $e)
		{
			throw new kTemporaryException("Sending mail failed with exception: " . $e->getMessage(), $e->getCode());	
		}
			
		return true;
	}
	
	private function getEmailFooter()
	{
		if(is_null(self::$emailFooterTemplate))
		{
			$file_path = dirname(__FILE__)."/emailFooter.html";
			if(file_exists($file_path))
			{
				$file_content = file_get_contents($file_path);
				self::$emailFooterTemplate = $file_content;
			}
		}
		$forumsLink = kConf::get('forum_url');
		
		$footer = vsprintf( self::$emailFooterTemplate, array( $forumsLink) );	
		return $footer;
	} 
	
	/**
	 * Function to retrieve array of recipients for the email notifiation based on the data.
	 * @param KalturaEmailNotificationRecipientJobData $recipientJobData
	 * @param array $contentParameters
	 * @return array;
	 */
	protected function getRecipientArray (KalturaEmailNotificationRecipientJobData $recipientJobData, array $contentParameters)
	{
		$recipientEngine = KEmailNotificationRecipientEngine::getEmailNotificationRecipientEngine($recipientJobData, $this->client);
		$recipients = $recipientEngine->getRecipients($contentParameters);
		
		return $recipients;
	}
}
