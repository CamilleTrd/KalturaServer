<?php
/**
 * @package plugins.externalMedia
 * @subpackage model
 */
class ExternalMediaEntry extends entry
{
	const CUSTOM_DATA_FIELD_EXTERNAL_SOURCE = 'externalSource';
	
	/* (non-PHPdoc)
	 * @see entry::getDownloadFileSyncAndLocal($version, $format, $sub_type)
	 */
	public function getDownloadFileSyncAndLocal ( $version = NULL , $format = null , $sub_type = null )
	{
		return null;
	}
	
	/* (non-PHPdoc)
	 * @see entry::getDownloadUrl($version)
	 */
	public function getDownloadUrl( $version = NULL )
	{
		return null;
	}
	
	/**
	 * @return int external source, of enum ExternalMediaSourceType
	 */
	public function getExternalSourceType()
	{
		return (int) $this->getFromCustomData(self::CUSTOM_DATA_FIELD_EXTERNAL_SOURCE);
	}
	
	/**
	 * @param int $v external source, of enum ExternalMediaSourceType
	 */
	public function setExternalSourceType($v)
	{
		$this->putInCustomData(self::CUSTOM_DATA_FIELD_EXTERNAL_SOURCE, $v);
	}

	/* (non-PHPdoc)
	 * @see entry::getCreateThumb()
	 */
	public function getCreateThumb()
	{
		return false;
	}
}