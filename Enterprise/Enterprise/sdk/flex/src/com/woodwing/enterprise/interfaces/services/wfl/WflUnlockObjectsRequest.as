/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflUnlockObjectsRequest")]

	public class WflUnlockObjectsRequest
	{
		private var _Ticket:String;
		private var _IDs:Array;
		private var _ReadMessageIDs:Array;
		private var _MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;

		public function WflUnlockObjectsRequest() {
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

		public function get ReadMessageIDs():Array {
			return this._ReadMessageIDs;
		}
		public function set ReadMessageIDs(ReadMessageIDs:Array):void {
			this._ReadMessageIDs = ReadMessageIDs;
		}

		public function get MessageList():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList {
			return this._MessageList;
		}
		public function set MessageList(MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList):void {
			this._MessageList = MessageList;
		}

	}
}
