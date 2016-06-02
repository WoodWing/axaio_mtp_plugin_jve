/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflSendToRequest")]

	public class WflSendToRequest
	{
		private var _Ticket:String;
		private var _IDs:Array;
		private var _WorkflowMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData;

		public function WflSendToRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get IDs():Array {
			return this._IDs;
		}
		public function set IDs(IDs:Array):void {
			this._IDs = IDs;
		}

		public function get WorkflowMetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData {
			return this._WorkflowMetaData;
		}
		public function set WorkflowMetaData(WorkflowMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData):void {
			this._WorkflowMetaData = WorkflowMetaData;
		}

	}
}
