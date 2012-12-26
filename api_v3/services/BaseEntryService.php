<?php

/**
 * Base Entry Service
 *
 * @service baseEntry
 * @package api
 * @subpackage services
 */
class BaseEntryService extends KalturaEntryService
{
    /* (non-PHPdoc)
     * @see KalturaEntryService::initService()
     */
    public function initService($serviceId, $serviceName, $actionName)
    {
        parent::initService($serviceId, $serviceName, $actionName);
        $partner = PartnerPeer::retrieveByPK($this->getPartnerId());
        if ($actionName == "anonymousRank" && $partner->getEnabledService(KalturaPermissionName::FEATURE_LIKE))
        {
            throw new KalturaAPIException(KalturaErrors::ACTION_FORBIDDEN, "anonymousRank");
        }
    }
    
	/* (non-PHPdoc)
	 * @see KalturaBaseService::globalPartnerAllowed()
	 */
	protected function globalPartnerAllowed($actionName)
	{
		if($actionName == 'getContextData')
			return true;
		
		return parent::globalPartnerAllowed($actionName);
	}
	
	/* (non-PHPdoc)
	 * @see KalturaBaseService::kalturaNetworkAllowed()
	 */
	protected function kalturaNetworkAllowed($actionName)
	{
		if ($actionName === 'get') {
			return true;
		}
		if ($actionName === 'getContextData') {
			return true;
		}
		return parent::kalturaNetworkAllowed($actionName);
	}
	
	/* (non-PHPdoc)
	 * @see KalturaBaseService::partnerRequired()
	 */
	protected function partnerRequired($actionName)
	{
		if ($actionName === 'flag') {
			return false;
		}
		return parent::partnerRequired($actionName);
	}
	
    /**
     * Generic add entry, should be used when the uploaded entry type is not known.
     *
     * @action add
     * @param KalturaBaseEntry $entry
     * @param KalturaEntryType $type
     * @return KalturaBaseEntry
     * @throws KalturaErrors::ENTRY_TYPE_NOT_SUPPORTED
     */
    function addAction(KalturaBaseEntry $entry, $type = -1)
    {
    	$dbEntry = parent::add($entry, $entry->conversionProfileId);
    	if($dbEntry->getStatus() != entryStatus::READY)
    	{
	   		$dbEntry->setStatus(entryStatus::NO_CONTENT);
	    	$dbEntry->save();
    	}
    	
		$trackEntry = new TrackEntry();
		$trackEntry->setEntryId($dbEntry->getId());
		$trackEntry->setTrackEventTypeId(TrackEntry::TRACK_ENTRY_EVENT_TYPE_ADD_ENTRY);
		$trackEntry->setDescription(__METHOD__ . ":" . __LINE__ . "::ENTRY_BASE");
		TrackEntry::addTrackEntry($trackEntry);
		
    	myNotificationMgr::createNotification(kNotificationJobData::NOTIFICATION_TYPE_ENTRY_ADD, $dbEntry, $dbEntry->getPartnerId(), null, null, null, $dbEntry->getId());
    	
	    $entry->fromObject($dbEntry);
	    return $entry;
    }
	
    /**
     * Attach content resource to entry in status NO_MEDIA
     *
     * @action addContent
	 * @param string $entryId
     * @param KalturaResource $resource
     * @return KalturaBaseEntry
     * @throws KalturaErrors::ENTRY_TYPE_NOT_SUPPORTED
     * @validateUser entry entryId edit
     */
    function addContentAction($entryId, KalturaResource $resource)
    {
    	$dbEntry = entryPeer::retrieveByPK($entryId);

		if (!$dbEntry)
			throw new KalturaAPIException(KalturaErrors::ENTRY_ID_NOT_FOUND, $entryId);
    	
		
		
		$kResource = $resource->toObject();
    	if($dbEntry->getType() == KalturaEntryType::AUTOMATIC || is_null($dbEntry->getType()))
    		$this->setEntryTypeByResource($dbEntry, $kResource);
		$dbEntry->save();
		
		$resource->validateEntry($dbEntry);
		$this->attachResource($kResource, $dbEntry);
		$resource->entryHandled($dbEntry);
    	
		return $this->getEntry($entryId);
    }

    /**
     * @param kResource $resource
     * @param entry $dbEntry
     * @param asset $asset
     */
    protected function attachResource(kResource $resource, entry $dbEntry, asset $asset = null)
    {
    	$service = null;
    	switch($dbEntry->getType())
    	{
			case entryType::MEDIA_CLIP:
				$service = new MediaService();
    			$service->initService('media', 'media', $this->actionName);
    			break;
				
			case entryType::MIX:
				$service = new MixingService();
    			$service->initService('mixing', 'mixing', $this->actionName);
    			break;
				
			case entryType::PLAYLIST:
				$service = new PlaylistService();
    			$service->initService('playlist', 'playlist', $this->actionName);
    			break;
				
			case entryType::DATA:
				$service = new DataService();
    			$service->initService('data', 'data', $this->actionName);
    			break;
				
			case entryType::LIVE_STREAM:
				$service = new LiveStreamService();
    			$service->initService('liveStream', 'liveStream', $this->actionName);
    			break;
    			
    		default:
    			throw new KalturaAPIException(KalturaErrors::ENTRY_TYPE_NOT_SUPPORTED, $dbEntry->getType());
    	}
    		
    	$service->attachResource($resource, $dbEntry, $asset);
    }
    
    /**
     * @param kResource $resource
     */
    protected function setEntryTypeByResource(entry $dbEntry, kResource $resource)
    {
    	$fullPath = null;
    	switch($resource->getType())
    	{
    		case 'kAssetParamsResourceContainer':
    			return $this->setEntryTypeByResource($dbEntry, $resource->getResource());
    			
			case 'kAssetsParamsResourceContainers':
    			return $this->setEntryTypeByResource($dbEntry, reset($resource->getResources()));
				
			case 'kFileSyncResource':
				$sourceEntry = null;
		    	if($resource->getFileSyncObjectType() == FileSyncObjectType::ENTRY)
		    		$sourceEntry = entryPeer::retrieveByPK($resource->getObjectId());
		    	if($resource->getFileSyncObjectType() == FileSyncObjectType::FLAVOR_ASSET)
		    	{
		    		$sourceAsset = assetPeer::retrieveByPK($resource->getObjectId());
		    		if($sourceAsset)
		    			$sourceEntry = $sourceAsset->getentry();
		    	}
		    	
				if($sourceEntry)
				{
					$dbEntry->setType($sourceEntry->getType());
					$dbEntry->setMediaType($sourceEntry->getMediaType());
				}
				return;
				
			case 'kLocalFileResource':
				$fullPath = $resource->getLocalFilePath();
				break;
				
			case 'kUrlResource':
			case 'kRemoteStorageResource':
				$fullPath = $resource->getUrl();
				break;
				
			default:
				return;
    	}
    	if($fullPath)
    		$this->setEntryTypeByExtension($dbEntry, $fullPath);
    }
    
    protected function setEntryTypeByExtension(entry $dbEntry, $fullPath)
    {
    	$ext = pathinfo($fullPath, PATHINFO_EXTENSION);
    	if(!$ext)
   			return;
    	
    	$mediaType = myFileUploadService::getMediaTypeFromFileExt($ext);
    	if($mediaType != entry::ENTRY_MEDIA_TYPE_AUTOMATIC)
    	{
			$dbEntry->setType(entryType::MEDIA_CLIP);
			$dbEntry->setMediaType($mediaType);
    	}
    }
    
    /**
     * Generic add entry using an uploaded file, should be used when the uploaded entry type is not known.
     *
     * @action addFromUploadedFile
     * @param KalturaBaseEntry $entry
     * @param string $uploadTokenId
     * @param KalturaEntryType $type
     * @return KalturaBaseEntry
     */
    function addFromUploadedFileAction(KalturaBaseEntry $entry, $uploadTokenId, $type = -1)
    {
		try
	    {
	    	// check that the uploaded file exists
			$entryFullPath = kUploadTokenMgr::getFullPathByUploadTokenId($uploadTokenId);
	    }
	    catch(kCoreException $ex)
	    {
	    	if ($ex->getCode() == kUploadTokenException::UPLOAD_TOKEN_INVALID_STATUS);
	    		throw new KalturaAPIException(KalturaErrors::UPLOAD_TOKEN_INVALID_STATUS_FOR_ADD_ENTRY);
	    		
    		throw $ex;
	    }

		if (!file_exists($entryFullPath))
		{
			$remoteDCHost = kUploadTokenMgr::getRemoteHostForUploadToken($uploadTokenId, kDataCenterMgr::getCurrentDcId());
			if($remoteDCHost)
			{
				kFileUtils::dumpApiRequest($remoteDCHost);
			}
			else
			{
				throw new KalturaAPIException(KalturaErrors::UPLOADED_FILE_NOT_FOUND_BY_TOKEN);
			}
		}
	    
	    // validate the input object
	    //$entry->validatePropertyMinLength("name", 1);
	    if (!$entry->name)
		    $entry->name = $this->getPartnerId().'_'.time();
			
	    // first copy all the properties to the db entry, then we'll check for security stuff
	    $dbEntry = $entry->toInsertableObject(new entry());
			
	    $dbEntry->setType($type);
	    $dbEntry->setMediaType(entry::ENTRY_MEDIA_TYPE_AUTOMATIC);
	        
	    $this->checkAndSetValidUserInsert($entry, $dbEntry);
	    $this->checkAdminOnlyInsertProperties($entry);
	    $this->validateAccessControlId($entry);
	    $this->validateEntryScheduleDates($entry, $dbEntry);
	    
	    $dbEntry->setPartnerId($this->getPartnerId());
	    $dbEntry->setSubpId($this->getPartnerId() * 100);
	    $dbEntry->setSourceId( $uploadTokenId );
	    $dbEntry->setSourceLink( $entryFullPath );
	    myEntryUtils::setEntryTypeAndMediaTypeFromFile($dbEntry, $entryFullPath);
	    $dbEntry->setDefaultModerationStatus();
		
		// hack due to KCW of version  from KMC
		if (! is_null ( parent::getConversionQualityFromRequest () ))
			$dbEntry->setConversionQuality ( parent::getConversionQualityFromRequest () );
		
	    $dbEntry->save();
	    
	    $kshow = $this->createDummyKShow();
	    $kshowId = $kshow->getId();
	    
	    // setup the needed params for my insert entry helper
	    $paramsArray = array (
		    "entry_media_source" => KalturaSourceType::FILE,
		    "entry_media_type" => $dbEntry->getMediaType(),
		    "entry_full_path" => $entryFullPath,
		    "entry_license" => $dbEntry->getLicenseType(),
		    "entry_credit" => $dbEntry->getCredit(),
		    "entry_source_link" => $dbEntry->getSourceLink(),
		    "entry_tags" => $dbEntry->getTags(),
	    );
			
	    $token = $this->getKsUniqueString();
	    $insert_entry_helper = new myInsertEntryHelper(null , $dbEntry->getKuserId(), $kshowId, $paramsArray);
	    $insert_entry_helper->setPartnerId($this->getPartnerId(), $this->getPartnerId() * 100);
	    $insert_entry_helper->insertEntry($token, $dbEntry->getType(), $dbEntry->getId(), $dbEntry->getName(), $dbEntry->getTags(), $dbEntry);
	    $dbEntry = $insert_entry_helper->getEntry();
	    
	    kUploadTokenMgr::closeUploadTokenById($uploadTokenId);
	    
	    myNotificationMgr::createNotification( kNotificationJobData::NOTIFICATION_TYPE_ENTRY_ADD, $dbEntry);

	    $entry->fromObject($dbEntry);
	    return $entry;
    }
    
	/**
	 * Get base entry by ID.
	 * 
	 * @action get
	 * @param string $entryId Entry id
	 * @param int $version Desired version of the data
	 * @return KalturaBaseEntry The requested entry
	 */
    function getAction($entryId, $version = -1)
    {
    	return $this->getEntry($entryId, $version);
    }

    /**
     * Get remote storage existing paths for the asset.
     *
     * @action getRemotePaths
     * @param string $entryId
     * @return KalturaRemotePathListResponse
     * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
     * @throws KalturaErrors::ENTRY_NOT_READY
     */
    public function getRemotePathsAction($entryId)
    {
		return $this->getRemotePaths($entryId);
	}
	
	/**
	 * Update base entry. Only the properties that were set will be updated.
	 * 
	 * @action update
	 * @param string $entryId Entry id to update
	 * @param KalturaBaseEntry $baseEntry Base entry metadata to update
	 * @param KalturaResource $resource Resource to be used to replace entry content
	 * @return KalturaBaseEntry The updated entry
	 * 
	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 * @validateUser entry entryId edit
	 */
	function updateAction($entryId, KalturaBaseEntry $baseEntry)
	{
    	$dbEntry = entryPeer::retrieveByPK($entryId);
		if (!$dbEntry)
			throw new KalturaAPIException(KalturaErrors::ENTRY_ID_NOT_FOUND, $entryId);
	
		$baseEntry = $this->updateEntry($entryId, $baseEntry);
    	return $baseEntry;
	}
	
	/**
	 * Update the content resource associated with the entry.
	 * 
	 * @action updateContent
	 * @param string $entryId Entry id to update
	 * @param KalturaResource $resource Resource to be used to replace entry content
	 * @param int $conversionProfileId The conversion profile id to be used on the entry
	 * @return KalturaBaseEntry The updated entry
	 * 
	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 * @validateUser entry entryId edit
	 */
	function updateContentAction($entryId, KalturaResource $resource, $conversionProfileId = null)
	{
    	$dbEntry = entryPeer::retrieveByPK($entryId);
		if (!$dbEntry)
			throw new KalturaAPIException(KalturaErrors::ENTRY_ID_NOT_FOUND, $entryId);
	
		
		
		$baseEntry = new KalturaBaseEntry();
		$baseEntry->fromObject($dbEntry);
		
		switch($dbEntry->getType())
    	{
			case entryType::MEDIA_CLIP:
				$service = new MediaService();
    			$service->initService('media', 'media', $this->actionName);
				$service->replaceResource($resource, $dbEntry, $conversionProfileId);
		    	$baseEntry->fromObject($dbEntry);
    			return $baseEntry;
				
			case entryType::MIX:
			case entryType::PLAYLIST:
			case entryType::DATA:
			case entryType::LIVE_STREAM:
    		default:
    			// TODO load from plugin manager other entry services such as document
    			throw new KalturaAPIException(KalturaErrors::ENTRY_TYPE_NOT_SUPPORTED, $baseEntry->type);
    	}
    	
    	return $baseEntry;
	}
	
	/**
	 * Get an array of KalturaBaseEntry objects by a comma-separated list of ids.
	 * 
	 * @action getByIds
	 * @param string $entryIds Comma separated string of entry ids
	 * @return KalturaBaseEntryArray Array of base entry ids
	 */
	function getByIdsAction($entryIds)
	{
		$entryIdsArray = explode(",", $entryIds);
		
		// remove white spaces
		foreach($entryIdsArray as &$entryId)
			$entryId = trim($entryId);
			
	 	$list = entryPeer::retrieveByPKs($entryIdsArray);
		$newList = array();
		
		$ks = $this->getKs();
		$isAdmin = false;
		if($ks)
			$isAdmin = $ks->isAdmin();
			
	 	foreach($list as $dbEntry)
	 	{
	 		$entry = KalturaEntryFactory::getInstanceByType($dbEntry->getType(), $isAdmin);
		    $entry->fromObject($dbEntry);
		    $newList[] = $entry;
	 	}
	 	
	 	return $newList;
	}

	/**
	 * Delete an entry.
	 *
	 * @action delete
	 * @param string $entryId Entry id to delete
	 * @validateUser entry entryId edit
	 */
	function deleteAction($entryId)
	{
		$this->deleteEntry($entryId);
	}
	
	/**
	 * List base entries by filter with paging support.
	 * 
	 * @action list
     * @param KalturaBaseEntryFilter $filter Entry filter
	 * @param KalturaFilterPager $pager Pager
	 * @return KalturaBaseEntryListResponse Wrapper for array of base entries and total count
	 */
	function listAction(KalturaBaseEntryFilter $filter = null, KalturaFilterPager $pager = null)
	{		
	    myDbHelper::$use_alternative_con = myDbHelper::DB_HELPER_CONN_PROPEL3;
		
	    list($list, $totalCount) = parent::listEntriesByFilter($filter, $pager);
	    
		$ks = $this->getKs();
		$isAdmin = false;
		if($ks)
			$isAdmin = $ks->isAdmin();
			
	    $newList = KalturaBaseEntryArray::fromEntryArray($list, $isAdmin);
		$response = new KalturaBaseEntryListResponse();
		$response->objects = $newList;
		$response->totalCount = $totalCount;
		return $response;
	}
	
	/**
	 * List base entries by filter according to reference id
	 * 
	 * @action listByReferenceId
	 * @param string $refId Entry Reference ID
	 * @param KalturaFilterPager $pager Pager
	 * @throws KalturaErrors::MISSING_MANDATORY_PARAMETER
	 */
	function listByReferenceId($refId, KalturaFilterPager $pager = null)
	{
		if (!$refId)
		{
			//if refId wasn't provided return an error of missing parameter
			throw new KalturaAPIException(KalturaErrors::MISSING_MANDATORY_PARAMETER, $refId);
		}
				
		if (!$pager){
			$pager = new KalturaFilterPager();
		}
		$entryFilter = new entryFilter();
		$entryFilter->setPartnerSearchScope(baseObjectFilter::MATCH_KALTURA_NETWORK_AND_PRIVATE);
		//setting reference ID	
		$entryFilter->set('_eq_reference_id', $refId);
		$c = KalturaCriteria::create(entryPeer::OM_CLASS);		
		$pager->attachToCriteria($c);	
		$entryFilter->attachToCriteria($c);		
		$c->add(entryPeer::DISPLAY_IN_SEARCH, mySearchUtils::DISPLAY_IN_SEARCH_SYSTEM, Criteria::NOT_EQUAL);
				
		KalturaCriterion::disableTag(KalturaCriterion::TAG_WIDGET_SESSION);
		$list = entryPeer::doSelect($c);
		KalturaCriterion::restoreTag(KalturaCriterion::TAG_WIDGET_SESSION);
		
		$totalCount = $c->getRecordsCount();
				
	    $newList = KalturaBaseEntryArray::fromEntryArray($list, false);
		$response = new KalturaBaseEntryListResponse();
		$response->objects = $newList;
		$response->totalCount = $totalCount;
		return $response;
	}
	
	/**
	 * Count base entries by filter.
	 * 
	 * @action count
     * @param KalturaBaseEntryFilter $filter Entry filter
	 * @return int
	 */
	function countAction(KalturaBaseEntryFilter $filter = null)
	{
	    return parent::countEntriesByFilter($filter);
	}
	
	/**
	 * Upload a file to Kaltura, that can be used to create an entry. 
	 * 
	 * @action upload
	 * @param file $fileData The file data
	 * @return string Upload token id
	 * 
	 * @deprecated use upload.upload or uploadToken.add instead
	 */
	function uploadAction($fileData)
	{
		$ksUnique = $this->getKsUniqueString();
		
		$uniqueId = substr(base_convert(md5(uniqid(rand(), true)), 16, 36), 1, 20);
		
		$ext = pathinfo($fileData["name"], PATHINFO_EXTENSION);
		$token = $ksUnique."_".$uniqueId.".".$ext;
		// filesync ok
		$res = myUploadUtils::uploadFileByToken($fileData, $token, "", null, true);
	
		return $res["token"];
	}
	
	/**
	 * Update entry thumbnail using a raw jpeg file.
	 * 
	 * @action updateThumbnailJpeg
	 * @param string $entryId Media entry id
	 * @param file $fileData Jpeg file data
	 * @return KalturaBaseEntry The media entry
	 * 
	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 * @throws KalturaErrors::PERMISSION_DENIED_TO_UPDATE_ENTRY
	 */
	function updateThumbnailJpegAction($entryId, $fileData)
	{
		return parent::updateThumbnailJpegForEntry($entryId, $fileData);
	}
	
	/**
	 * Update entry thumbnail using url.
	 * 
	 * @action updateThumbnailFromUrl
	 * @param string $entryId Media entry id
	 * @param string $url file url
	 * @return KalturaBaseEntry The media entry
	 * 
	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 * @throws KalturaErrors::PERMISSION_DENIED_TO_UPDATE_ENTRY
	 */
	function updateThumbnailFromUrlAction($entryId, $url)
	{
		return parent::updateThumbnailForEntryFromUrl($entryId, $url);
	}
	
	/**
	 * Update entry thumbnail from a different entry by a specified time offset (in seconds).
	 * 
	 * @action updateThumbnailFromSourceEntry
	 * @param string $entryId Media entry id
	 * @param string $sourceEntryId Media entry id
	 * @param int $timeOffset Time offset (in seconds)
	 * @return KalturaBaseEntry The media entry
	 * 
	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 * @throws KalturaErrors::PERMISSION_DENIED_TO_UPDATE_ENTRY
	 */
	function updateThumbnailFromSourceEntryAction($entryId, $sourceEntryId, $timeOffset)
	{
		return parent::updateThumbnailForEntryFromSourceEntry($entryId, $sourceEntryId, $timeOffset);
	}
	
	/**
	 * Flag inappropriate entry for moderation.
	 *
	 * @action flag
	 * @param string $entryId
	 * @param KalturaModerationFlag $moderationFlag
	 * 
 	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 */
	public function flagAction(KalturaModerationFlag $moderationFlag)
	{
		KalturaResponseCacher::disableCache();
		return parent::flagEntry($moderationFlag);
	}
	
	/**
	 * Reject the entry and mark the pending flags (if any) as moderated (this will make the entry non-playable).
	 *
	 * @action reject
	 * @param string $entryId
	 * 
	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 */
	public function rejectAction($entryId)
	{
		parent::rejectEntry($entryId);
	}
	
	/**
	 * Approve the entry and mark the pending flags (if any) as moderated (this will make the entry playable). 
	 *
	 * @action approve
	 * @param string $entryId
	 * 
	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 */
	public function approveAction($entryId)
	{
		parent::approveEntry($entryId);
	}
	
	/**
	 * List all pending flags for the entry.
	 *
	 * @action listFlags
	 * @param string $entryId
	 * @param KalturaFilterPager $pager
	 * 
	 * @return KalturaModerationFlagListResponse
	 */
	public function listFlags($entryId, KalturaFilterPager $pager = null)
	{
		return parent::listFlagsForEntry($entryId, $pager);
	}
	
	/**
	 * Anonymously rank an entry, no validation is done on duplicate rankings.
	 *  
	 * @action anonymousRank
	 * @param string $entryId
	 * @param int $rank
	 */
	public function anonymousRankAction($entryId, $rank)
	{
		KalturaResponseCacher::disableCache();
		return parent::anonymousRankEntry($entryId, null, $rank);
	}
	
	/**
	 * This action delivers entry-related data, based on the user's context: access control, restriction, playback format and storage information.
	 * @action getContextData
	 * @param string $entryId
	 * @param KalturaEntryContextDataParams $contextDataParams
	 * @return KalturaEntryContextDataResult
	 */
	public function getContextData($entryId, KalturaEntryContextDataParams $contextDataParams)
	{
		$dbEntry = entryPeer::retrieveByPK($entryId);
		if (!$dbEntry)
			throw new KalturaAPIException(KalturaErrors::ENTRY_ID_NOT_FOUND, $entryId);
			
		$ks = $this->getKs();
		$isAdmin = false;
		$isSecured = false; // access control or entitlement enabled
		if($ks)
			$isAdmin = $ks->isAdmin();
			
		$accessControl = $dbEntry->getAccessControl();
		/* @var $accessControl accessControl */
		$result = new KalturaEntryContextDataResult();
		$result->isAdmin = $isAdmin;
		$result->isScheduledNow = $dbEntry->isScheduledNow($contextDataParams->time);
		
		if (($dbEntry->getStartDate() && abs($dbEntry->getStartDate(null) - time()) <= 86400) ||
			($dbEntry->getEndDate() &&   abs($dbEntry->getEndDate(null) - time())   <= 86400))
		{
			KalturaResponseCacher::setConditionalCacheExpiry(600);
		}	

		if ($accessControl && $accessControl->hasRules())
		{
			$isSecured = true;
			$disableCache = true;
			if (kConf::hasMap("optimized_playback"))
			{
				$partnerId = $accessControl->getPartnerId();
				$optimizedPlayback = kConf::getMap("optimized_playback");
				if (array_key_exists($partnerId, $optimizedPlayback))
				{
					$params = $optimizedPlayback[$partnerId];
					if (array_key_exists('cache_kdp_acccess_control', $params) && $params['cache_kdp_acccess_control'])
						$disableCache = false;
				}
			}
			
			$accessControlScope = $accessControl->getScope();
            $contextDataParams->toObject($accessControlScope);
            $accessControlScope->setEntryId($entryId);
			$result->isAdmin = ($accessControlScope->getKs() && $accessControlScope->getKs()->isAdmin());
            
			$dbResult = new kEntryContextDataResult();
			if($accessControl->applyContext($dbResult) && $disableCache)
				KalturaResponseCacher::disableCache();
				
			$result->fromObject($dbResult);
		}
		
		$partner = PartnerPeer::retrieveByPK($dbEntry->getPartnerId());
		if(PermissionPeer::isValidForPartner(PermissionName::FEATURE_REMOTE_STORAGE_DELIVERY_PRIORITY, $dbEntry->getPartnerId()) &&
			$partner->getStorageServePriority() != StorageProfile::STORAGE_SERVE_PRIORITY_KALTURA_ONLY)
		{
			if (is_null($contextDataParams->flavorAssetId))
			{
				if ($contextDataParams->flavorTags)
				{
					$assets = assetPeer::retrieveReadyByEntryIdAndTag($entryId, $contextDataParams->flavorTags);
					$asset = reset($assets);
				}
				else 
					$asset = assetPeer::retrieveBestPlayByEntryId($entryId);
				
				if(!$asset)
					throw new KalturaAPIException(KalturaErrors::NO_FLAVORS_FOUND, $entryId);
			}
			else
			{
				$asset = assetPeer::retrieveByPK($contextDataParams->flavorAssetId);
				
				if(!$asset)
					throw new KalturaAPIException(KalturaErrors::FLAVOR_ASSET_ID_NOT_FOUND, $contextDataParams->flavorAssetId);
			}
			
			if(!$asset)
				throw new KalturaAPIException(KalturaErrors::FLAVOR_ASSET_ID_NOT_FOUND, $entryId);
								
			$assetSyncKey = $asset->getSyncKey(asset::FILE_SYNC_ASSET_SUB_TYPE_ASSET);
			$fileSyncs = kFileSyncUtils::getAllReadyExternalFileSyncsForKey($assetSyncKey);
		
			
			$storageProfilesXML = new SimpleXMLElement("<StorageProfiles/>");
			foreach ($fileSyncs as $fileSync)
			{
				$storageProfileId = $fileSync->getDc();
				
				$storageProfile = StorageProfilePeer::retrieveByPK($storageProfileId);
				
				if ( !$storageProfile->getDeliveryRmpBaseUrl()
					&& (!$contextDataParams->streamerType || $contextDataParams->streamerType == PlaybackProtocol::AUTO))
				{
					$contextDataParams->streamerType = PlaybackProtocol::HTTP;
					$contextDataParams->mediaProtocol = PlaybackProtocol::HTTP;

				}
				$storageProfileXML = $storageProfilesXML->addChild("StorageProfile");
				
				$storageProfileXML->addAttribute("storageProfileId",$storageProfileId);
				$storageProfileXML->addChild("Name", $storageProfile->getName());
				$storageProfileXML->addChild("SystemName", $storageProfile->getSystemName());
				
			}

			$result->storageProfilesXML = $storageProfilesXML->saveXML();
			
		}
		
		if($contextDataParams->streamerType && $contextDataParams->streamerType != PlaybackProtocol::AUTO)
		{
			$result->streamerType = $contextDataParams->streamerType;
			$result->mediaProtocol = $contextDataParams->mediaProtocol ? $contextDataParams->mediaProtocol : $contextDataParams->streamerType;
			return $result;
		}
		
		if ($dbEntry->getType() == entryType::LIVE_STREAM)
		{
			$config = kLiveStreamConfiguration::getSingleItemByPropertyValue($dbEntry, 'protocol', PlaybackProtocol::AKAMAI_HDS);
			if ($config)	
				$result->streamerType = KalturaPlaybackProtocol::AKAMAI_HDS;

			
			if (!$result->streamerType)
				$result->streamerType = KalturaPlaybackProtocol::RTMP;
				
			return $result;
		}
		
		$isSecured = $isSecured || PermissionPeer::isValidForPartner(PermissionName::FEATURE_ENTITLEMENT, $dbEntry->getPartnerId());
		
		$result->streamerType = $this->getPartner()->getStreamerType();
		if (!$result->streamerType)
		{
			if($isSecured)
				$result->streamerType = kConf::get('secured_default_streamer_type');
			elseif($dbEntry->getDuration() <= kConf::get('short_entries_max_duration'))
				$result->streamerType = kConf::get('short_entries_default_streamer_type');
			else
				$result->streamerType = kConf::get('default_streamer_type');
		}
			
		$result->mediaProtocol = $this->getPartner()->getMediaProtocol();
		if (!$result->mediaProtocol)
		{
			if($isSecured)
				$result->mediaProtocol = kConf::get('secured_default_media_protocol');
			elseif($dbEntry->getDuration() <= kConf::get('short_entries_max_duration'))
				$result->mediaProtocol = kConf::get('short_entries_default_media_protocol');
			else
				$result->mediaProtocol = kConf::get('default_media_protocol');
		}
		
		return $result;
	}
	
	/**
	 * @action export
	 * Action for manually exporting an entry
	 * @param string $entryId
	 * @param int $storageProfileId
	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 * @throws KalturaErrors::STORAGE_PROFILE_ID_NOT_FOUND
	 * @return KalturaBaseEntry The exported entry
	 */
	public function exportAction ( $entryId , $storageProfileId )
	{	    
	    $dbEntry = entryPeer::retrieveByPK($entryId);
	    if (!$dbEntry)
	    {
	        throw new KalturaAPIException(KalturaErrors::ENTRY_ID_NOT_FOUND, $entryId);
	    }
	    
	    $dbStorageProfile = StorageProfilePeer::retrieveByPK($storageProfileId);	    
	    if (!$dbStorageProfile)
	    {
	        throw new KalturaAPIException(KalturaErrors::STORAGE_PROFILE_ID_NOT_FOUND, $storageProfileId);
	    }
	    
	    kStorageExporter::exportEntry($dbEntry, $dbStorageProfile);
	    
	    //TODO: implement export errors
	    
		$entry = KalturaEntryFactory::getInstanceByType($dbEntry->getType());
		$entry->fromObject($dbEntry);
	    return $entry;
	    
	}
	
	/**
	 * Index an entry by id.
	 * 
	 * @action index
	 * @param string $id
	 * @param bool $shouldUpdate
	 * @return int entry int id
	 */
	function indexAction($id, $shouldUpdate = true)
	{
		if(kEntitlementUtils::getEntitlementEnforcement())
			throw new KalturaAPIException(KalturaErrors::CANNOT_INDEX_OBJECT_WHEN_ENTITLEMENT_IS_ENABLE);
			
		$entryDb = entryPeer::retrieveByPK($id);
		if (!$entryDb)
			throw new KalturaAPIException(KalturaErrors::ENTRY_ID_NOT_FOUND, $id);

		if (!$shouldUpdate)
		{
			$entryDb->indexToSearchIndex();
			
			return $entryDb->getIntId();
		}
		
		return myEntryUtils::index($entryDb);
	}
}
