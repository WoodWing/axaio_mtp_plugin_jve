/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetDatasourceTypesResponse")]

	public class AdsGetDatasourceTypesResponse
	{
		private var _DatasourceTypes:Array;

		public function AdsGetDatasourceTypesResponse() {
		}

		public function get DatasourceTypes():Array {
			return this._DatasourceTypes;
		}
		public function set DatasourceTypes(DatasourceTypes:Array):void {
			this._DatasourceTypes = DatasourceTypes;
		}

	}
}
