<?php
/**
 * @package plugins.attachment
 * @subpackage api.filters.base
 * @abstract
 */
abstract class KalturaAttachmentAssetBaseFilter extends KalturaAssetFilter
{
	static private $map_between_objects = array
	(
		"formatEqual" => "_eq_format",
		"formatIn" => "_in_format",
		"statusEqual" => "_eq_status",
		"statusIn" => "_in_status",
		"statusNotIn" => "_notin_status",
	);

	static private $order_by_map = array
	(
	);

	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), KalturaAttachmentAssetBaseFilter::$map_between_objects);
	}

	public function getOrderByMap()
	{
		return array_merge(parent::getOrderByMap(), KalturaAttachmentAssetBaseFilter::$order_by_map);
	}

	/**
	 * @var KalturaAttachmentType
	 */
	public $formatEqual;

	/**
	 * @dynamicType KalturaAttachmentType
	 * @var string
	 */
	public $formatIn;

	/**
	 * @var KalturaAttachmentAssetStatus
	 */
	public $statusEqual;

	/**
	 * @var string
	 */
	public $statusIn;

	/**
	 * @var string
	 */
	public $statusNotIn;
}
