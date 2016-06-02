/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.DatQueryDatasourcesRequest")]

	public class DatQueryDatasourcesRequest
	{
		private var _Ticket:String;
		private var _PublicationID:String;

		public function DatQueryDatasourcesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get PublicationID():String {
			return this._PublicationID;
		}
		public function set PublicationID(PublicationID:String):void {
			this._PublicationID = PublicationID;
		}

	}
}
