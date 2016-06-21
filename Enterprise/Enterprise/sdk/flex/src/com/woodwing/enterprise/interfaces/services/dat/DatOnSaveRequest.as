/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.DatOnSaveRequest")]

	public class DatOnSaveRequest
	{
		private var _Ticket:String;
		private var _DatasourceID:String;
		private var _Placements:Array;

		public function DatOnSaveRequest() {
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

		public function get Placements():Array {
			return this._Placements;
		}
		public function set Placements(Placements:Array):void {
			this._Placements = Placements;
		}

	}
}
