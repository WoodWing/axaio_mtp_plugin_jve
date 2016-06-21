/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsSaveSettingRequest")]

	public class AdsSaveSettingRequest
	{
		private var _Ticket:String;
		private var _DatasourceID:String;
		private var _Name:String;
		private var _Value:String;

		public function AdsSaveSettingRequest() {
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

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Value():String {
			return this._Value;
		}
		public function set Value(Value:String):void {
			this._Value = Value;
		}

	}
}
