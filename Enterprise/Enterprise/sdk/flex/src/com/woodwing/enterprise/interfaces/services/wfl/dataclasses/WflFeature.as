/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflFeature")]

	public class WflFeature
	{
		private var _Key:String;
		private var _Value:String;

		public function WflFeature() {
		}

		public function get Key():String {
			return this._Key;
		}
		public function set Key(Key:String):void {
			this._Key = Key;
		}

		public function get Value():String {
			return this._Value;
		}
		public function set Value(Value:String):void {
			this._Value = Value;
		}

	}
}
