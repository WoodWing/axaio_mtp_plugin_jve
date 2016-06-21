/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.DatGetUpdatesRequest")]

	public class DatGetUpdatesRequest
	{
		private var _Ticket:String;
		private var _UpdateID:String;
		private var _ObjectID:String;

		public function DatGetUpdatesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get UpdateID():String {
			return this._UpdateID;
		}
		public function set UpdateID(UpdateID:String):void {
			this._UpdateID = UpdateID;
		}

		public function get ObjectID():String {
			return this._ObjectID;
		}
		public function set ObjectID(ObjectID:String):void {
			this._ObjectID = ObjectID;
		}

	}
}
