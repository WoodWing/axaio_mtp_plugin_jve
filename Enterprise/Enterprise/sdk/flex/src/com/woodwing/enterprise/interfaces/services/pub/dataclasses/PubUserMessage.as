/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubUserMessage")]

	public class PubUserMessage
	{
		private var _Severity:String;
		private var _MessageID:Number;
		private var _Message:String;
		private var _Reason:String;

		public function PubUserMessage() {
		}

		public function get Severity():String {
			return this._Severity;
		}
		public function set Severity(Severity:String):void {
			this._Severity = Severity;
		}

		public function get MessageID():Number {
			return this._MessageID;
		}
		public function set MessageID(MessageID:Number):void {
			this._MessageID = MessageID;
		}

		public function get Message():String {
			return this._Message;
		}
		public function set Message(Message:String):void {
			this._Message = Message;
		}

		public function get Reason():String {
			return this._Reason;
		}
		public function set Reason(Reason:String):void {
			this._Reason = Reason;
		}

	}
}
