/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetUserSettingsResponse")]

	public class WflGetUserSettingsResponse
	{
		private var _Settings:Array;

		public function WflGetUserSettingsResponse() {
		}

		public function get Settings():Array {
			return this._Settings;
		}
		public function set Settings(Settings:Array):void {
			this._Settings = Settings;
		}

	}
}
