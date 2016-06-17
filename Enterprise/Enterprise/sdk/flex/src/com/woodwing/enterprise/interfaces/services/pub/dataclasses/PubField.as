/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubField")]

	public class PubField
	{
		private var _Key:String;
		private var _Type:String;
		private var _Values:Array;

		public function PubField() {
		}

		public function get Key():String {
			return this._Key;
		}
		public function set Key(Key:String):void {
			this._Key = Key;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get Values():Array {
			return this._Values;
		}
		public function set Values(Values:Array):void {
			this._Values = Values;
		}

	}
}
