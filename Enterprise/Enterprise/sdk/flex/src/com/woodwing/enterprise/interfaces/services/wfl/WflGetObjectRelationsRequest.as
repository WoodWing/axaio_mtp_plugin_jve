/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetObjectRelationsRequest")]

	public class WflGetObjectRelationsRequest
	{
		private var _Ticket:String;
		private var _ID:String;

		public function WflGetObjectRelationsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

	}
}
