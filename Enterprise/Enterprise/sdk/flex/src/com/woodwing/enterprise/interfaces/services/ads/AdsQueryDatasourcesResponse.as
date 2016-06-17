/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsQueryDatasourcesResponse")]

	public class AdsQueryDatasourcesResponse
	{
		private var _Datasources:Array;

		public function AdsQueryDatasourcesResponse() {
		}

		public function get Datasources():Array {
			return this._Datasources;
		}
		public function set Datasources(Datasources:Array):void {
			this._Datasources = Datasources;
		}

	}
}
