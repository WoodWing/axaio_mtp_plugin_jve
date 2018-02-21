/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmModifyWorkflowUserGroupAuthorizationsResponse")]

	public class AdmModifyWorkflowUserGroupAuthorizationsResponse
	{
		private var _WorkflowUserGroupAuthorizations:Array;

		public function AdmModifyWorkflowUserGroupAuthorizationsResponse() {
		}

		public function get WorkflowUserGroupAuthorizations():Array {
			return this._WorkflowUserGroupAuthorizations;
		}
		public function set WorkflowUserGroupAuthorizations(WorkflowUserGroupAuthorizations:Array):void {
			this._WorkflowUserGroupAuthorizations = WorkflowUserGroupAuthorizations;
		}

	}
}