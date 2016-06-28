/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectPageInfo")]

	public class WflObjectPageInfo
	{
		private var _MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;
		private var _Pages:Array;
		private var _Messages:Array;
		private var _MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;

		public function WflObjectPageInfo() {
		}

		public function get MetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData {
			return this._MetaData;
		}
		public function set MetaData(MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData):void {
			this._MetaData = MetaData;
		}

		public function get Pages():Array {
			return this._Pages;
		}
		public function set Pages(Pages:Array):void {
			this._Pages = Pages;
		}

		public function get Messages():Array {
			return this._Messages;
		}
		public function set Messages(Messages:Array):void {
			this._Messages = Messages;
		}

		public function get MessageList():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList {
			return this._MessageList;
		}
		public function set MessageList(MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList):void {
			this._MessageList = MessageList;
		}

	}
}
