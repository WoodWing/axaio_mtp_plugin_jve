/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflLogOnRequest")]

	public class WflLogOnRequest
	{
		private var _User:String;
		private var _Password:String;
		private var _Ticket:String;
		private var _Server:String;
		private var _ClientName:String;
		private var _Domain:String;
		private var _ClientAppName:String;
		private var _ClientAppVersion:String;
		private var _ClientAppSerial:String;
		private var _ClientAppProductKey:String;
		private var _RequestTicket:String;
		private var _RequestInfo:Array;

		public function WflLogOnRequest() {
		}

		public function get User():String {
			return this._User;
		}
		public function set User(User:String):void {
			this._User = User;
		}

		public function get Password():String {
			return this._Password;
		}
		public function set Password(Password:String):void {
			this._Password = Password;
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Server():String {
			return this._Server;
		}
		public function set Server(Server:String):void {
			this._Server = Server;
		}

		public function get ClientName():String {
			return this._ClientName;
		}
		public function set ClientName(ClientName:String):void {
			this._ClientName = ClientName;
		}

		public function get Domain():String {
			return this._Domain;
		}
		public function set Domain(Domain:String):void {
			this._Domain = Domain;
		}

		public function get ClientAppName():String {
			return this._ClientAppName;
		}
		public function set ClientAppName(ClientAppName:String):void {
			this._ClientAppName = ClientAppName;
		}

		public function get ClientAppVersion():String {
			return this._ClientAppVersion;
		}
		public function set ClientAppVersion(ClientAppVersion:String):void {
			this._ClientAppVersion = ClientAppVersion;
		}

		public function get ClientAppSerial():String {
			return this._ClientAppSerial;
		}
		public function set ClientAppSerial(ClientAppSerial:String):void {
			this._ClientAppSerial = ClientAppSerial;
		}

		public function get ClientAppProductKey():String {
			return this._ClientAppProductKey;
		}
		public function set ClientAppProductKey(ClientAppProductKey:String):void {
			this._ClientAppProductKey = ClientAppProductKey;
		}


		// _RequestTicket should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RequestTicket():String {
			return this._RequestTicket;
		}

		// _RequestTicket should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RequestTicket(RequestTicket:String):void {
			this._RequestTicket = RequestTicket;
		}

		public function get RequestInfo():Array {
			return this._RequestInfo;
		}
		public function set RequestInfo(RequestInfo:Array):void {
			this._RequestInfo = RequestInfo;
		}

	}
}
