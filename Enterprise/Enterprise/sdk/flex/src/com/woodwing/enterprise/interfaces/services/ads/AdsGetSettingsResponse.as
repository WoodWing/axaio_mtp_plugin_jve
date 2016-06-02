/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetSettingsResponse")]

	public class AdsGetSettingsResponse
	{
		private var _Settings:Array;

		public function AdsGetSettingsResponse() {
		}

		public function get Settings():Array {
			return this._Settings;
		}
		public function set Settings(Settings:Array):void {
			this._Settings = Settings;
		}

	}
}
