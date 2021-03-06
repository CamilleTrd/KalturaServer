<?php


/**
 * Skeleton subclass for performing query and update operations on the 'cue_point' table.
 *
 * 
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package plugins.cuePoint
 * @subpackage model
 */
class CuePointPeer extends BaseCuePointPeer implements IMetadataPeer 
{
	const MAX_TEXT_LENGTH = 32700;
	const MAX_TAGS_LENGTH = 255;
	
	// the search index column names for additional fields
	const ROOTS = 'cue_point.ROOTS';
	const STR_ENTRY_ID = 'cue_point.STR_ENTRY_ID';
	const STR_CUE_POINT_ID = 'cue_point.STR_CUE_POINT_ID';
	const FORCE_STOP = 'cue_point.FORCE_STOP';
	const DURATION = 'cue_point.DURATION';
	
	// cache classes by their type
	protected static $class_types_cache = array();
	
	/* (non-PHPdoc)
	 * @see BaseCuePointPeer::setDefaultCriteriaFilter()
	 */
	public static function setDefaultCriteriaFilter()
	{
		if(self::$s_criteria_filter == null)
			self::$s_criteria_filter = new criteriaFilter();
		
		$c = new Criteria();
		$c->addAnd(CuePointPeer::STATUS, CuePointStatus::DELETED, Criteria::NOT_EQUAL);
		self::$s_criteria_filter->setFilter($c);
	}
	
	public static function setDefaultCriteriaFilterByKuser()
	{
		if(self::$s_criteria_filter == null)
			self::$s_criteria_filter = new criteriaFilter();
		
		$c = self::$s_criteria_filter->getFilter();
		if(!$c)
		{
			$c = new Criteria();
			$c->addAnd(CuePointPeer::STATUS, CuePointStatus::DELETED, Criteria::NOT_EQUAL);
		}
			
		$puserId = kCurrentContext::$ks_uid;
		$partnerId = kCurrentContext::$ks_partner_id;
		if ($puserId && $partnerId)
		{
			$kuser = kuserPeer::getKuserByPartnerAndUid($partnerId, $puserId);
		    if (! $kuser) {
				$kuser = kuserPeer::createKuserForPartner($partnerId, $puserId);
			}
			$c->addAnd(CuePointPeer::KUSER_ID, $kuser->getId());
		}
		self::$s_criteria_filter->setFilter($c);
	}

	/* (non-PHPdoc)
	 * @see BaseCuePointPeer::getOMClass()
	 */
	public static function getOMClass($row, $colnum)
	{
		$assetType = null;
		if($row)
		{
			$colnum += self::translateFieldName(self::TYPE, BasePeer::TYPE_COLNAME, BasePeer::TYPE_NUM);
			$assetType = $row[$colnum];
			if(isset(self::$class_types_cache[$assetType]))
				return self::$class_types_cache[$assetType];
				
			$extendedCls = KalturaPluginManager::getObjectClass(self::OM_CLASS, $assetType);
			if($extendedCls)
			{
				self::$class_types_cache[$assetType] = $extendedCls;
				return $extendedCls;
			}
		}
			
		throw new Exception("Can't instantiate un-typed [$assetType] cue point [" . print_r($row, true) . "]");
	}
	
	/* (non-PHPdoc)
	 * @see BaseCuePointPeer::doSelect()
	 */
	public static function doSelect(Criteria $criteria, PropelPDO $con = null)
	{
		$c = clone $criteria;
		
		if($c instanceof KalturaCriteria)
		{
			$c->applyFilters();
			$criteria->setRecordsCount($c->getRecordsCount());
		}
			
		return parent::doSelect($c, $con);
	}

	/**
	 * Retrieve a single object by system name.
	 * The cue point system name is unique per entry
	 *
	 * @param      string $entryId the entry id.
	 * @param      string $systemName the system name.
	 * @param      PropelPDO $con the connection to use
	 * @return     CuePoint
	 */
	public static function retrieveBySystemName($entryId, $systemName, PropelPDO $con = null)
	{
		$criteria = new Criteria();
		$criteria->add(CuePointPeer::ENTRY_ID, $entryId);
		$criteria->add(CuePointPeer::SYSTEM_NAME, $systemName);

		return CuePointPeer::doSelectOne($criteria, $con);
	}

	/**
	 * Retrieve a single object by entry id.
	 *
	 * @param      string $systemName the entry id.
	 * @param      int $type the cue point type from CuePointType enum
	 * @param      PropelPDO $con the connection to use
	 * @return     CuePoint
	 */
	public static function retrieveByEntryId($entryId, $type = null, PropelPDO $con = null)
	{
		$criteria = new Criteria();
		$criteria->add(CuePointPeer::ENTRY_ID, $entryId);
		if(!is_null($type))
			$criteria->add(CuePointPeer::TYPE, $type);

		return CuePointPeer::doSelect($criteria, $con);
	}
	public static function getCacheInvalidationKeys()
	{
		return array(array("cuePoint:id=%s", self::ID), array("cuePoint:entryId=%s", self::ENTRY_ID));		
	}
}
