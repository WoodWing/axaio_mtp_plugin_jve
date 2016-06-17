/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	import com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsQuery;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetQueryResponse")]

	public class AdsGetQueryResponse
	{
		private var _Query:com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsQuery;

		public function AdsGetQueryResponse() {
		}

		public function get Query():com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsQuery {
			return this._Query;
		}
		public function set Query(Query:com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsQuery):void {
			this._Query = Query;
		}

	}
}
