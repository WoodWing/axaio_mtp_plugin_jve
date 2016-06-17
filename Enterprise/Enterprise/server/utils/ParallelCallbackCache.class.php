<?php
/**
 * @package Enterprise
 * @subpackage Utils
 * @since v7.6.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * This is a very simple class that helps other classes to cache data in situations whereby 
 * parallel network request are sent out and responses are returned in random order.
 * This is typically suitable for multi-curl solutions for which waiting classes needs to
 * leave their request data pending for a while until the response comes back from network
 * for which request data needs to be brought back together again.
 *
 * A class could simply use just one instance of this class, even when more function of the
 * class need to read/write different data independently. Each function should use a so called
 * context to read/write. This could be simply the function name.
 *
 * Introduced to support the parallel upload feature for Adobe DPS.
 */
 
class WW_Utils_ParallelCallbackCache
{
	private $cache = null;
	
	public function __construct()
	{
		$this->clearCache();
	}
	
	/**
	 * Delete all data from the whole cache (for all contexts).
	 */
	public function clearCache()
	{
		$this->cache = array();
	}
	
	/**
	 * Delete all data from the cache for a certain context.
	 *
	 * @param string $context The data context. Typically the function name using the cache.
	 */
	public function clearContext( $context )
	{
		unset( $this->cache[ $context ] );
	}
	
	/**
	 * Delete a specific data structure from the cache.
	 * 
	 * @param string $context The data context. Typically the function name using the cache.
	 * @param string $id The data identifier. Should be unique. For example an object id.
	 */
	public function clearData( $context, $id )
	{
		unset( $this->cache[ $context ][ $id ] );
	}
	
	/**
	 * Write any data structure into the cache.
	 * 
	 * @param string $context The data context. Typically the function name using the cache.
	 * @param string $id The data identifier. Should be unique. For example an object id.
	 * @param mixed $data The data to be stored in cache. 
	 */
	public function saveData( $context, $id, $data )
	{
		$this->cache[ $context ][ $id ] = $data;
	}

	/**
	 * Read any data structure from the cache.
	 * 
	 * @param string $context The data context. Typically the function name using the cache.
	 * @param string $id The data identifier. Should be unique. For example an object id.
	 * @return mixed The data retrieved from cache. 
	 */
	public function loadData( $context, $id )
	{
		return isset($this->cache[ $context ][ $id ]) ? $this->cache[ $context ][ $id ] : null;
	}
}