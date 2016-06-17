/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetQueryFieldsResponse")]

	public class AdsGetQueryFieldsResponse
	{
		private var _QueryFields:Array;

		public function AdsGetQueryFieldsResponse() {
		}

		public function get QueryFields():Array {
			return this._QueryFields;
		}
		public function set QueryFields(QueryFields:Array):void {
			this._QueryFields = QueryFields;
		}

	}
}
