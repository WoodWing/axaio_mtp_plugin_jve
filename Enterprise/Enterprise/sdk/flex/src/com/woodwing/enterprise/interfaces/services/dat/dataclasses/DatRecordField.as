/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.dataclasses.DatRecordField")]

	public class DatRecordField
	{
		private var _UpdateType:String;
		private var _UpdateResponse:String;
		private var _ReadOnly:String;
		private var _Priority:String;
		private var _Name:String;
		private var _Attributes:Array;

		public function DatRecordField() {
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


		// _ReadOnly should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get ReadOnly():String {
			return this._ReadOnly;
		}

		// _ReadOnly should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set ReadOnly(ReadOnly:String):void {
			this._ReadOnly = ReadOnly;
		}


		// _Priority should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Priority():String {
			return this._Priority;
		}

		// _Priority should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Priority(Priority:String):void {
			this._Priority = Priority;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Attributes():Array {
			return this._Attributes;
		}
		public function set Attributes(Attributes:Array):void {
			this._Attributes = Attributes;
		}

	}
}
