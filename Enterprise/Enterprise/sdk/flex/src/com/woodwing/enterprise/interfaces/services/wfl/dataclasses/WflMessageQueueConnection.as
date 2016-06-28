/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageQueueConnection")]

	public class WflMessageQueueConnection
	{
		private var _Instance:String;
		private var _Protocol:String;
		private var _Url:String;
		private var _User:String;
		private var _Password:String;
		private var _VirtualHost:String;

		public function WflMessageQueueConnection() {
		}

		public function get Instance():String {
			return this._Instance;
		}
		public function set Instance(Instance:String):void {
			this._Instance = Instance;
		}

		public function get Protocol():String {
			return this._Protocol;
		}
		public function set Protocol(Protocol:String):void {
			this._Protocol = Protocol;
		}

		public function get Url():String {
			return this._Url;
		}
		public function set Url(Url:String):void {
			this._Url = Url;
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

		public function get VirtualHost():String {
			return this._VirtualHost;
		}
		public function set VirtualHost(VirtualHost:String):void {
			this._VirtualHost = VirtualHost;
		}

	}
}
