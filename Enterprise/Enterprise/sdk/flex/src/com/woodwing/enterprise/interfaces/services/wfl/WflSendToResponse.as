/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflSendToResponse")]

	public class WflSendToResponse
	{
		private var _SendTo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData;

		public function WflSendToResponse() {
		}

		public function get SendTo():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData {
			return this._SendTo;
		}
		public function set SendTo(SendTo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData):void {
			this._SendTo = SendTo;
		}

	}
}
