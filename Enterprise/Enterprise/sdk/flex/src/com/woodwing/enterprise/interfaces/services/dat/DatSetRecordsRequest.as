/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.DatSetRecordsRequest")]

	public class DatSetRecordsRequest
	{
		private var _Ticket:String;
		private var _ObjectID:String;
		private var _DatasourceID:String;
		private var _QueryID:String;
		private var _Params:Array;
		private var _Records:Array;

		public function DatSetRecordsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get ObjectID():String {
			return this._ObjectID;
		}
		public function set ObjectID(ObjectID:String):void {
			this._ObjectID = ObjectID;
		}

		public function get DatasourceID():String {
			return this._DatasourceID;
		}
		public function set DatasourceID(DatasourceID:String):void {
			this._DatasourceID = DatasourceID;
		}

		public function get QueryID():String {
			return this._QueryID;
		}
		public function set QueryID(QueryID:String):void {
			this._QueryID = QueryID;
		}

		public function get Params():Array {
			return this._Params;
		}
		public function set Params(Params:Array):void {
			this._Params = Params;
		}

		public function get Records():Array {
			return this._Records;
		}
		public function set Records(Records:Array):void {
			this._Records = Records;
		}

	}
}
