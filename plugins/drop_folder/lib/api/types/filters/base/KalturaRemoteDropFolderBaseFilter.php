<?php
/**
 * @package plugins.dropFolder
 * @subpackage api.filters.base
 * @abstract
 */
abstract class KalturaRemoteDropFolderBaseFilter extends KalturaDropFolderFilter
{
	static private $map_between_objects = array
	(
	);

	static private $order_by_map = array
	(
	);

	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), KalturaRemoteDropFolderBaseFilter::$map_between_objects);
	}

	public function getOrderByMap()
	{
		return array_merge(parent::getOrderByMap(), KalturaRemoteDropFolderBaseFilter::$order_by_map);
	}
}
