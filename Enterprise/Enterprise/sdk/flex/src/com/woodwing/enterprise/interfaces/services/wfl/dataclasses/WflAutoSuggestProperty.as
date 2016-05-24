/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAutoSuggestProperty")]

	public class WflAutoSuggestProperty
	{
		private var _Name:String;
		private var _Entity:String;
		private var _IgnoreValues:Array;

		public function WflAutoSuggestProperty() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Entity():String {
			return this._Entity;
		}
		public function set Entity(Entity:String):void {
			this._Entity = Entity;
		}

		public function get IgnoreValues():Array {
			return this._IgnoreValues;
		}
		public function set IgnoreValues(IgnoreValues:Array):void {
			this._IgnoreValues = IgnoreValues;
		}

	}
}
