/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.dataclasses.DatRecord")]

	public class DatRecord
	{
		private var _ID:String;
		private var _UpdateType:String;
		private var _UpdateResponse:String;
		private var _Hidden:String;
		private var _Fields:Array;

		public function DatRecord() {
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get UpdateType():String {
			return this._UpdateType;
		}
		public function set UpdateType(UpdateType:String):void {
			this._UpdateType = UpdateType;
		}

		public function get UpdateResponse():String {
			return this._UpdateResponse;
		}
		public function set UpdateResponse(UpdateResponse:String):void {
			this._UpdateResponse = UpdateResponse;
		}


		// _Hidden should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Hidden():String {
			return this._Hidden;
		}

		// _Hidden should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Hidden(Hidden:String):void {
			this._Hidden = Hidden;
		}

		public function get Fields():Array {
			return this._Fields;
		}
		public function set Fields(Fields:Array):void {
			this._Fields = Fields;
		}

	}
}
