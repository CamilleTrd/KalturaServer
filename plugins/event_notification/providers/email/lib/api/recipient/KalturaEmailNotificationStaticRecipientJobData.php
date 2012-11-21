<?php
/**
 * JobData representing the static receipient array
 *
 * @package plugins.eventNotification
 * @subpackage model.data
 */
class KalturaEmailNotificationStaticRecipientJobData extends KalturaEmailNotificationRecipientJobData
{
	/**
	 * Email to emails and names
	 * @var KalturaKeyValueArray
	 */
	public $emailRecipients;
	
	private static $map_between_objects = array(
		'emailRecipients',
	);
	
	/* (non-PHPdoc)
	 * @see KalturaObject::getMapBetweenObjects()
	 */
	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), self::$map_between_objects);
	}
	
	/* (non-PHPdoc)
	 * @see KalturaEmailNotificationRecipientJobData::setProviderType()
	 */
	protected function setProviderType() 
	{
		$this->providerType = KalturaEmailNotificationRecipientProviderType::STATIC_LIST;	
	}
	
	/* (non-PHPdoc)
	 * @see KalturaObject::fromObject($source_object)
	 */
	public function fromObject($dbObject)
	{
		/* @var $dbObject kEmailNotificationStaticRecipientJobData */
		parent::fromObject($dbObject);
		$this->setProviderType();
		
		$this->emailRecipients = KalturaKeyValueArray::fromKeyValueArray($dbObject->getEmailRecipients());
	}

	/* (non-PHPdoc)
	 * @see KalturaObject::toObject($object_to_fill, $props_to_skip)
	 */
	public function toObject($dbObject, $propertiesToSkip = array())
	{
		if (is_null($dbObject))
			$dbObject = new kEmailNotificationStaticRecipientJobData();
		
		return parent::toObject($dbObject, $propertiesToSkip);
	}
}