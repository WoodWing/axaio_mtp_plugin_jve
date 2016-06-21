/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.dataclasses.DatList")]

	public class DatList
	{
		private var _Name:String;
		private var _Value:String;
		private var _Attributes:Array;

		public function DatList() {
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

		public function get Attributes():Array {
			return this._Attributes;
		}
		public function set Attributes(Attributes:Array):void {
			this._Attributes = Attributes;
		}

	}
}
