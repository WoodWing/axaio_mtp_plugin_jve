/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyNotification")]

	public class WflPropertyNotification
	{
		private var _Type:String;
		private var _Message:String;

		public function WflPropertyNotification() {
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get Message():String {
			return this._Message;
		}
		public function set Message(Message:String):void {
			this._Message = Message;
		}

	}
}
