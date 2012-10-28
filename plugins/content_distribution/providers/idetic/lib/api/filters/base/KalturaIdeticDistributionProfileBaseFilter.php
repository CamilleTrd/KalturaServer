<?php
/**
 * @package plugins.ideticDistribution
 * @subpackage api.filters.base
 * @abstract
 */
abstract class KalturaIdeticDistributionProfileBaseFilter extends KalturaConfigurableDistributionProfileFilter
{
	static private $map_between_objects = array
	(
	);

	static private $order_by_map = array
	(
	);

	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), KalturaIdeticDistributionProfileBaseFilter::$map_between_objects);
	}

	public function getOrderByMap()
	{
		return array_merge(parent::getOrderByMap(), KalturaIdeticDistributionProfileBaseFilter::$order_by_map);
	}
}
