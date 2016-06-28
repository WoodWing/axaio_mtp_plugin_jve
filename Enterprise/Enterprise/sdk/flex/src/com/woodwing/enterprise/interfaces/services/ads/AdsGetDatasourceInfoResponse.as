/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	import com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsDatasourceInfo;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetDatasourceInfoResponse")]

	public class AdsGetDatasourceInfoResponse
	{
		private var _DatasourceInfo:com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsDatasourceInfo;

		public function AdsGetDatasourceInfoResponse() {
		}

		public function get DatasourceInfo():com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsDatasourceInfo {
			return this._DatasourceInfo;
		}
		public function set DatasourceInfo(DatasourceInfo:com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsDatasourceInfo):void {
			this._DatasourceInfo = DatasourceInfo;
		}

	}
}
