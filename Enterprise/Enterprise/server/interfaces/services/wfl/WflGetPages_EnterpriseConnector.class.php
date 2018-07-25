<?php

/**
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/ServiceConnector.class.php';

abstract class WflGetPages_EnterpriseConnector extends ServiceConnector 
{
	final public function getConnectorType()    { return 'WorkflowService'; }
	final public function getInterfaceVersion() { return 1; }

	abstract public function runBefore( WflGetPagesRequest &$req );
	abstract public function runAfter( WflGetPagesRequest $req, WflGetPagesResponse &$resp );
	abstract public function runOverruled( WflGetPagesRequest $req );


	/**
	 * This function is called when an error occurs during a web service execution.
	 *
	 * It gives each service connector the chance to act on errors thrown by the core server,
	 * by other service connectors or by itself. This can be very useful to clean-up resources 
	 * created in the runBefore() function, because the runAfter() function won't be called 
	 * in case of an error.
	 *
	 * Please do -not- throw another error in context of this function.
	 *
	 * Note that this function may be called even before the runBefore() action. 
	 * This happens when other service connectors are called first and already throw a BizException. 
	 * Or, this function may be called even after the runAfter() action is executed.
	 * This happens when other service connectors are called which only then throw a BizException.
	 *
	 * @since 9.7.0
	 * @param WflGetPagesRequest $req The web service request.
	 * @param BizException $e The error thrown.
	 */
	public function onError( WflGetPagesRequest $req, BizException $e )
	{
	}
}
