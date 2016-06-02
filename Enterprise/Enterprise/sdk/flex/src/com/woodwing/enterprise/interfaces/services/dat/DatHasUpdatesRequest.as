/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.DatHasUpdatesRequest")]

	public class DatHasUpdatesRequest
	{
		private var _Ticket:String;
		private var _DatasourceID:String;
		private var _FamilyValue:String;

		public function DatHasUpdatesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get DatasourceID():String {
			return this._DatasourceID;
		}
		public function set DatasourceID(DatasourceID:String):void {
			this._DatasourceID = DatasourceID;
		}

		public function get FamilyValue():String {
			return this._FamilyValue;
		}
		public function set FamilyValue(FamilyValue:String):void {
			this._FamilyValue = FamilyValue;
		}

	}
}
