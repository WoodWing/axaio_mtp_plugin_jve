/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsDatasourceInfo")]

	public class AdsDatasourceInfo
	{
		private var _ID:String;
		private var _Name:String;
		private var _Bidirectional:String;
		private var _Type:String;

		public function AdsDatasourceInfo() {
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Bidirectional():String {
			return this._Bidirectional;
		}
		public function set Bidirectional(Bidirectional:String):void {
			this._Bidirectional = Bidirectional;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

	}
}
