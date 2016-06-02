/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmUserGroup")]

	public class AdmUserGroup
	{
		private var _Id:Number;
		private var _Name:String;
		private var _Description:String;
		private var _Admin:String;
		private var _Routing:String;
		private var _ExternalId:String;
		private var _Users:Array;

		public function AdmUserGroup() {
		}

		public function get Id():Number {
			return this._Id;
		}
		public function set Id(Id:Number):void {
			this._Id = Id;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Description():String {
			return this._Description;
		}
		public function set Description(Description:String):void {
			this._Description = Description;
		}


		// _Admin should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Admin():String {
			return this._Admin;
		}

		// _Admin should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Admin(Admin:String):void {
			this._Admin = Admin;
		}


		// _Routing should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Routing():String {
			return this._Routing;
		}

		// _Routing should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Routing(Routing:String):void {
			this._Routing = Routing;
		}

		public function get ExternalId():String {
			return this._ExternalId;
		}
		public function set ExternalId(ExternalId:String):void {
			this._ExternalId = ExternalId;
		}

		public function get Users():Array {
			return this._Users;
		}
		public function set Users(Users:Array):void {
			this._Users = Users;
		}

	}
}
