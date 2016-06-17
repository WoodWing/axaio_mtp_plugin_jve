/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflLogOffRequest")]

	public class WflLogOffRequest
	{
		private var _Ticket:String;
		private var _SaveSettings:String;
		private var _Settings:Array;
		private var _ReadMessageIDs:Array;
		private var _MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;

		public function WflLogOffRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}


		// _SaveSettings should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get SaveSettings():String {
			return this._SaveSettings;
		}

		// _SaveSettings should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set SaveSettings(SaveSettings:String):void {
			this._SaveSettings = SaveSettings;
		}

		public function get Settings():Array {
			return this._Settings;
		}
		public function set Settings(Settings:Array):void {
			this._Settings = Settings;
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
