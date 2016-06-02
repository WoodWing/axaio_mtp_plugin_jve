/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflChangePasswordRequest")]

	public class WflChangePasswordRequest
	{
		private var _Ticket:String;
		private var _Old:String;
		private var _New:String;
		private var _Name:String;

		public function WflChangePasswordRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Old():String {
			return this._Old;
		}
		public function set Old(Old:String):void {
			this._Old = Old;
		}

		public function get New():String {
			return this._New;
		}
		public function set New(New:String):void {
			this._New = New;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

	}
}
