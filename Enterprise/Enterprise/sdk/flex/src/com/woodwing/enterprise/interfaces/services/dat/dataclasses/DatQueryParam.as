/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.dataclasses.DatQueryParam")]

	public class DatQueryParam
	{
		private var _Property:String;
		private var _Operation:String;
		private var _Value:String;

		public function DatQueryParam() {
		}

		public function get Property():String {
			return this._Property;
		}
		public function set Property(Property:String):void {
			this._Property = Property;
		}

		public function get Operation():String {
			return this._Operation;
		}
		public function set Operation(Operation:String):void {
			this._Operation = Operation;
		}

		public function get Value():String {
			return this._Value;
		}
		public function set Value(Value:String):void {
			this._Value = Value;
		}

	}
}
