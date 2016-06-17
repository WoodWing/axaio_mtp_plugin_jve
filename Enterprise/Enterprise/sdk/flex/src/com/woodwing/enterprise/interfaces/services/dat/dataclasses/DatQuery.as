/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.dataclasses.DatQuery")]

	public class DatQuery
	{
		private var _ID:String;
		private var _Name:String;
		private var _Params:Array;
		private var _RecordFamily:String;

		public function DatQuery() {
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

		public function get Params():Array {
			return this._Params;
		}
		public function set Params(Params:Array):void {
			this._Params = Params;
		}

		public function get RecordFamily():String {
			return this._RecordFamily;
		}
		public function set RecordFamily(RecordFamily:String):void {
			this._RecordFamily = RecordFamily;
		}

	}
}
