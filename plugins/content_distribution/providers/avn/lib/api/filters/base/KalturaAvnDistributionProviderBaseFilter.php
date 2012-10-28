<?php
/**
 * @package plugins.avnDistribution
 * @subpackage api.filters.base
 * @abstract
 */
abstract class KalturaAvnDistributionProviderBaseFilter extends KalturaDistributionProviderFilter
{
	static private $map_between_objects = array
	(
	);

	static private $order_by_map = array
	(
	);

	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), KalturaAvnDistributionProviderBaseFilter::$map_between_objects);
	}

	public function getOrderByMap()
	{
		return array_merge(parent::getOrderByMap(), KalturaAvnDistributionProviderBaseFilter::$order_by_map);
	}
}
