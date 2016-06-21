/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsNewDatasourceRequest")]

	public class AdsNewDatasourceRequest
	{
		private var _Ticket:String;
		private var _Name:String;
		private var _Type:String;
		private var _Bidirectional:String;

		public function AdsNewDatasourceRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get Bidirectional():String {
			return this._Bidirectional;
		}
		public function set Bidirectional(Bidirectional:String):void {
			this._Bidirectional = Bidirectional;
		}

	}
}
