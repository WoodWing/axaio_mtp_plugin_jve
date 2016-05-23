/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflExtraMetaData")]

	public class WflExtraMetaData
	{
		private var _Property:String;
		private var _Values:Array;

		public function WflExtraMetaData() {
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

	}
}
