/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsSettingsDetail")]

	public class AdsSettingsDetail
	{
		private var _Name:String;
		private var _Description:String;
		private var _Type:String;
		private var _List:String;

		public function AdsSettingsDetail() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Description():String {
			return this._Description;
		}
		public function set Description(Description:String):void {
			this._Description = Description;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get List():String {
			return this._List;
		}
		public function set List(List:String):void {
			this._List = List;
		}

	}
}
