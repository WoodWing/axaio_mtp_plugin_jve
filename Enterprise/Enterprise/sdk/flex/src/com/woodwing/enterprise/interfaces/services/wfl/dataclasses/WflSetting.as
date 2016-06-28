/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflSetting")]

	public class WflSetting
	{
		private var _Setting:String;
		private var _Value:String;

		public function WflSetting() {
		}

		public function get Setting():String {
			return this._Setting;
		}
		public function set Setting(Setting:String):void {
			this._Setting = Setting;
		}

		public function get Value():String {
			return this._Value;
		}
		public function set Value(Value:String):void {
			this._Value = Value;
		}

	}
}
