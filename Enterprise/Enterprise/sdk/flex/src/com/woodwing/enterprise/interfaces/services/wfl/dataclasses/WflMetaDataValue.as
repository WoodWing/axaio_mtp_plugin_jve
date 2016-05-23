/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaDataValue")]

	public class WflMetaDataValue
	{
		private var _Property:String;
		private var _Values:Array;
		private var _PropertyValues:Array;

		public function WflMetaDataValue() {
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

		public function get PropertyValues():Array {
			return this._PropertyValues;
		}
		public function set PropertyValues(PropertyValues:Array):void {
			this._PropertyValues = PropertyValues;
		}

	}
}
