<?php

require_once(dirname(__FILE__) . '/../../../bootstrap.php');

/**
 * eventNotificationTemplate service test case.
 */
class EventNotificationTemplateServiceTest extends EventNotificationTemplateServiceTestBase
{
	/**
	 * Tests eventNotificationTemplate->add action
	 * @param KalturaEventNotificationTemplate $eventNotificationTemplate 
	 * @param KalturaEventNotificationTemplate $reference
	 * @return KalturaEventNotificationTemplate
	 * @dataProvider provideData
	 */
	public function testAdd(KalturaEventNotificationTemplate $eventNotificationTemplate, KalturaEventNotificationTemplate $reference)
	{
		$eventNotificationTemplate->systemName = uniqid('unit_test');
		$resultObject = $this->client->eventNotificationTemplate->add($eventNotificationTemplate);
		if(method_exists($this, 'assertInstanceOf'))
			$this->assertInstanceOf('KalturaEventNotificationTemplate', $resultObject);
		else
			$this->assertType('KalturaEventNotificationTemplate', $resultObject);
		$this->assertAPIObjects($reference, $resultObject, array('createdAt', 'updatedAt', 'id', 'thumbnailUrl', 'downloadUrl', 'rootEntryId', 'operationAttributes', 'deletedAt', 'statusUpdatedAt', 'widgetHTML', 'totalCount', 'objects', 'cropDimensions', 'dataUrl', 'requiredPermissions', 'confFilePath', 'feedUrl'));
		$this->assertNotNull($resultObject->id);
		$this->validateAdd($resultObject);
		
		return $resultObject->id;
	}
	/* (non-PHPdoc)
	 * @see EventNotificationTemplateServiceTestBase::validateAdd()
	 */
	protected function validateAdd(KalturaEventNotificationTemplate $resultObject)
	{
		// TODO - add your own validations here
	}

	/**
	 * Tests eventNotificationTemplate->cloneAction action
	 * @param int $id source template to clone
	 * @param KalturaEventNotificationTemplate $eventNotificationTemplate overwrite configuration object
	 * @param KalturaEventNotificationTemplate $reference
	 * @dataProvider provideData
	 */
	public function testCloneAction($id, KalturaEventNotificationTemplate $eventNotificationTemplate = null, KalturaEventNotificationTemplate $reference)
	{
		try 
		{
			$eventNotificationTemplate->systemName = uniqid('unit_test');
			$resultObject = $this->client->eventNotificationTemplate->cloneAction($id, $eventNotificationTemplate, $reference);
			
			if(method_exists($this, 'assertInstanceOf'))
				$this->assertInstanceOf('KalturaEventNotificationTemplate', $resultObject);
			else
				$this->assertType('KalturaEventNotificationTemplate', $resultObject);
			$this->assertAPIObjects($reference, $resultObject, array('createdAt', 'updatedAt', 'id', 'thumbnailUrl', 'downloadUrl', 'rootEntryId', 'operationAttributes', 'deletedAt', 'statusUpdatedAt', 'widgetHTML', 'totalCount', 'objects', 'cropDimensions', 'dataUrl', 'requiredPermissions', 'confFilePath', 'feedUrl'));
		}
		catch (KalturaException $e)
		{
			if ($e->getCode() == "EVENT_NOTIFICATION_TEMPLATE_DUPLICATE_SYSTEM_NAME")
			{
				
			}
			else
			{
				$this->fail('Unexpected error occured.');
			}
		}
	}

	/* (non-PHPdoc)
	 * @see EventNotificationTemplateServiceTestBase::validateGet()
	 */
	protected function validateGet(KalturaEventNotificationTemplate $resultObject)
	{
		// TODO - add your own validations here
	}

	/* (non-PHPdoc)
	 * @see EventNotificationTemplateServiceTestBase::validateUpdate()
	 */
	protected function validateUpdate(KalturaEventNotificationTemplate $resultObject)
	{
		// TODO - add your own validations here
	}

	/**
	 * Tests eventNotificationTemplate->updatestatus action
	 * @param int $id 
	 * @param KalturaEventNotificationTemplateStatus $status 
	 * @param KalturaEventNotificationTemplate $reference
	 * @dataProvider provideData
	 */
	public function testUpdatestatus($id, $status, KalturaEventNotificationTemplate $reference)
	{
		try
		{
			$resultObject = $this->client->eventNotificationTemplate->updatestatus($id, $status, $reference);
			$this->fail('Method should not be allowed for non-admin partner.');
		}
		catch (KalturaException $e)
		{
			if ($e->getCode() == 'SERVICE_FORBIDDEN')
			{
				
			}
			else
			{
				$this->fail('Wrong error type thrown');
			}
		}
		
	}
	
	/**
	 * Tests eventNotificationTemplate->delete action
	 * @param int $id 
	 * @param KalturaEventNotificationTemplate $reference
	 * @depends testAdd with data set #2
	 * @dataProvider provideData
	 */
	public function testDelete($id, KalturaEventNotificationTemplate $reference)
	{
		$resultObject = $this->client->eventNotificationTemplate->delete($id);
		try
		{
			$this->client->eventNotificationTemplate->get ($id);
			$this->fail("Template not deleted");
		}
		catch (Exception $e)
		{
		}
	}

	/* (non-PHPdoc)
	 * @see EventNotificationTemplateServiceTestBase::validateDelete()
	 */
	protected function validateDelete(KalturaEventNotificationTemplate $resultObject)
	{
		// TODO - add your own validations here
	}

	/* (non-PHPdoc)
	 * @see EventNotificationTemplateServiceTestBase::validateListAction()
	 */
	protected function validateListAction(KalturaEventNotificationTemplateListResponse $resultObject)
	{
		// TODO - add your own validations here
	}

	/**
	 * Tests eventNotificationTemplate->listbypartner action
	 * @param KalturaPartnerFilter $filter 
	 * @param KalturaFilterPager $pager 
	 * @param KalturaEventNotificationTemplateListResponse $reference
	 * @dataProvider provideData
	 */
	public function testListbypartner(KalturaPartnerFilter $filter = null, KalturaFilterPager $pager = null, KalturaEventNotificationTemplateListResponse $reference)
	{
		$resultObject = $this->client->eventNotificationTemplate->listbypartner($filter, $pager, $reference);
		if(method_exists($this, 'assertInstanceOf'))
			$this->assertInstanceOf('KalturaEventNotificationTemplateListResponse', $resultObject);
		else
			$this->assertType('KalturaEventNotificationTemplateListResponse', $resultObject);
		$this->assertAPIObjects($reference, $resultObject, array('createdAt', 'updatedAt', 'id', 'thumbnailUrl', 'downloadUrl', 'rootEntryId', 'operationAttributes', 'deletedAt', 'statusUpdatedAt', 'widgetHTML', 'totalCount', 'objects', 'cropDimensions', 'dataUrl', 'requiredPermissions', 'confFilePath', 'feedUrl'));
		// TODO - add here your own validations
		$this->validateListbypartner($resultObject);
	}

	/**
	 * Tests eventNotificationTemplate->dispatch action
	 * @param int $id 
	 * @param KalturaEventNotificationDispatchJobData $data
	 * @param int $reference
	 * @dataProvider provideData
	 */
	public function testDispatch($id, KalturaEventNotificationDispatchJobData $data, $reference)
	{
		try 
		{
			$resultObject = $this->client->eventNotificationTemplate->dispatch($id, $data, $reference);
			if(method_exists($this, 'assertInstanceOf'))
				$this->assertInstanceOf('int', $resultObject);
			else
				$this->assertType('int', $resultObject);
			$this->assertAPIObjects($reference, $resultObject, array('createdAt', 'updatedAt', 'id', 'thumbnailUrl', 'downloadUrl', 'rootEntryId', 'operationAttributes', 'deletedAt', 'statusUpdatedAt', 'widgetHTML', 'totalCount', 'objects', 'cropDimensions', 'dataUrl', 'requiredPermissions', 'confFilePath', 'feedUrl'));
			// TODO - add here your own validations
			$this->validateDispatch($resultObject);
		}
		catch (KalturaException $e)
		{
			if ($e->getCode() == 'EVENT_NOTIFICATION_DISPATCH_DISABLED' || $e->getCode() == 'SERVICE_FORBIDDEN')
			{
				
			}
			else
			{
				$this->fail('Unexpected error type thrown');
			}
		}
	}

}

