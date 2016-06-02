/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmExtraMetaData")]

	public class AdmExtraMetaData
	{
		private var _Property:String;
		private var _Values:Array;

		public function AdmExtraMetaData() {
		}

		public function get Property():String {
			return this._Property;
		}
		public function set Property(Property:String):void {
			this._Property = Property;
		}

		public function get Values():Array {
			return this._Values;
		}
		public function set Values(Values:Array):void {
			this._Values = Values;
		}

	}
}
