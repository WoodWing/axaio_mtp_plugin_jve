/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetPublicationsResponse")]

	public class AdsGetPublicationsResponse
	{
		private var _Publications:Array;

		public function AdsGetPublicationsResponse() {
		}

		public function get Publications():Array {
			return this._Publications;
		}
		public function set Publications(Publications:Array):void {
			this._Publications = Publications;
		}

	}
}
