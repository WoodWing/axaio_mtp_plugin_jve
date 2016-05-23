/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyValue")]

	public class WflPropertyValue
	{
		private var _Value:String;
		private var _Display:String;
		private var _Entity:String;

		public function WflPropertyValue() {
		}

		public function get Value():String {
			return this._Value;
		}
		public function set Value(Value:String):void {
			this._Value = Value;
		}

		public function get Display():String {
			return this._Display;
		}
		public function set Display(Display:String):void {
			this._Display = Display;
		}

		public function get Entity():String {
			return this._Entity;
		}
		public function set Entity(Entity:String):void {
			this._Entity = Entity;
		}

	}
}
