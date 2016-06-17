/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetDatasourceResponse")]

	public class AdsGetDatasourceResponse
	{
		private var _Queries:Array;
		private var _Settings:Array;
		private var _Publications:Array;

		public function AdsGetDatasourceResponse() {
		}

		public function get Queries():Array {
			return this._Queries;
		}
		public function set Queries(Queries:Array):void {
			this._Queries = Queries;
		}

		public function get Settings():Array {
			return this._Settings;
		}
		public function set Settings(Settings:Array):void {
			this._Settings = Settings;
		}

		public function get Publications():Array {
			return this._Publications;
		}
		public function set Publications(Publications:Array):void {
			this._Publications = Publications;
		}

	}
}
