/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetSettingsDetailsResponse")]

	public class AdsGetSettingsDetailsResponse
	{
		private var _SettingsDetails:Array;

		public function AdsGetSettingsDetailsResponse() {
		}

		public function get SettingsDetails():Array {
			return this._SettingsDetails;
		}
		public function set SettingsDetails(SettingsDetails:Array):void {
			this._SettingsDetails = SettingsDetails;
		}

	}
}
