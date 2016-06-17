/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	import com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsDatasourceType;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetDatasourceTypeResponse")]

	public class AdsGetDatasourceTypeResponse
	{
		private var _DatasourceType:com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsDatasourceType;

		public function AdsGetDatasourceTypeResponse() {
		}

		public function get DatasourceType():com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsDatasourceType {
			return this._DatasourceType;
		}
		public function set DatasourceType(DatasourceType:com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsDatasourceType):void {
			this._DatasourceType = DatasourceType;
		}

	}
}
