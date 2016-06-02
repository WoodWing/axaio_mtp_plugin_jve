/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsQueryField")]

	public class AdsQueryField
	{
		private var _Name:String;
		private var _ID:String;
		private var _Priority:String;
		private var _ReadOnly:String;

		public function AdsQueryField() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get Priority():String {
			return this._Priority;
		}
		public function set Priority(Priority:String):void {
			this._Priority = Priority;
		}

		public function get ReadOnly():String {
			return this._ReadOnly;
		}
		public function set ReadOnly(ReadOnly:String):void {
			this._ReadOnly = ReadOnly;
		}

	}
}
