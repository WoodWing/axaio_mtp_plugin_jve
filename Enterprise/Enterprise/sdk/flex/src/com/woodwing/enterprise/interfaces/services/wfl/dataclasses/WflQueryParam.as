/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflQueryParam")]

	public class WflQueryParam
	{
		private var _Property:String;
		private var _Operation:String;
		private var _Value:String;
		private var _Special:String;
		private var _Value2:String;

		public function WflQueryParam() {
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


		// _Special should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Special():String {
			return this._Special;
		}

		// _Special should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Special(Special:String):void {
			this._Special = Special;
		}

		public function get Value2():String {
			return this._Value2;
		}
		public function set Value2(Value2:String):void {
			this._Value2 = Value2;
		}

	}
}
