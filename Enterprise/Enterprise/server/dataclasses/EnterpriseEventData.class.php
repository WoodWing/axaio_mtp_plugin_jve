<?php
/**
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * EnterpriseEventData class definition, all the data are serialized and saved in jobdata field in smart_serverjobs table.
 * The description of JobData (what is in JobData) is saved in dataentity field in smart_serverjobs table.
 *
 */

class EnterpriseEventData
{
	private $data; // The event data, passed to prepare and process methods. The data content depends on ServerJob::DataEntity.
	private $pluginData = array(); // Prepared data per plugin, with key set to the plugin class
	private $preparedDataBefore = false; // To keep track if the prepareData method has been called already or not. False by default.
	private $jobStatusPerPlugin = array(); // To keep track for each and every plugin if the prepareData method has been successfully executed.

	/**
	 * The $data property has a specific structure to be able to transfer the necessary information.
	 *
	 * Array
	 *	'data' => //Contains information regarding the content of an enterprise event.
	 *		Array
	 *			[0] => issue or object
	 *			([1] =? MetaDataValues (in case of MultiObjects)
	 *	'extra' => //Contains descriptive data of the event that is saved upon creation of the event.
	 *		Array
	 *			'clientName' => client name
	 *			'clientVersion => client version
	 *
	 *
	 *
	 * @param $data
	 */
	public function setData( $data )
	{
		$this->data = $data;
	}
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Setting the $preparedDataBefore member to be true.
	 * This is to indicate that the Event data has been built up before by the core and the plug-ins,
	 * by calling the prepareData method.
	 * Once called, this flag is set to true and the prepareData method is no longer called.
	 */
	public function setCalledPrepareDataBefore()
	{
		$this->preparedDataBefore = true;
	}

	public function hasCalledPrepareDataBefore()
	{
		return $this->preparedDataBefore;
	}

	public function setPluginData( $connClass, $pluginData )
	{
		$this->pluginData[$connClass] = $pluginData;
	}
	public function getPluginData( $connClass )
	{
		return $this->pluginData[$connClass];
	}

	public function setJobStatusPerPlugin( $jobStatusPerPlugin )
	{
		$this->jobStatusPerPlugin = $jobStatusPerPlugin;
	}
	public function getJobStatusPerPlugin()
	{
		return $this->jobStatusPerPlugin;
	}
}
