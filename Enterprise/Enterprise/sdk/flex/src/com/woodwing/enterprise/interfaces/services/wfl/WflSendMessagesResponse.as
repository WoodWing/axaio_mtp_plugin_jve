/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflSendMessagesResponse")]

	public class WflSendMessagesResponse
	{
		private var _MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;
		private var _Reports:Array;

		public function WflSendMessagesResponse() {
		}

		public function get MessageList():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList {
			return this._MessageList;
		}
		public function set MessageList(MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList):void {
			this._MessageList = MessageList;
		}

		public function get Reports():Array {
			return this._Reports;
		}
		public function set Reports(Reports:Array):void {
			this._Reports = Reports;
		}

	}
}
