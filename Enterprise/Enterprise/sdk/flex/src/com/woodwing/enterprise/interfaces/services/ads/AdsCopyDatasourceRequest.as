/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsCopyDatasourceRequest")]

	public class AdsCopyDatasourceRequest
	{
		private var _Ticket:String;
		private var _DatasourceID:String;
		private var _NewName:String;
		private var _CopyQueries:String;

		public function AdsCopyDatasourceRequest() {
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

		public function get NewName():String {
			return this._NewName;
		}
		public function set NewName(NewName:String):void {
			this._NewName = NewName;
		}

		public function get CopyQueries():String {
			return this._CopyQueries;
		}
		public function set CopyQueries(CopyQueries:String):void {
			this._CopyQueries = CopyQueries;
		}

	}
}
