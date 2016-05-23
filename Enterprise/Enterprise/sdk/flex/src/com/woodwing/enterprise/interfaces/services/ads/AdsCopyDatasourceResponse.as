/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsCopyDatasourceResponse")]

	public class AdsCopyDatasourceResponse
	{
		private var _NewDatasourceID:String;

		public function AdsCopyDatasourceResponse() {
		}

		public function get NewDatasourceID():String {
			return this._NewDatasourceID;
		}
		public function set NewDatasourceID(NewDatasourceID:String):void {
			this._NewDatasourceID = NewDatasourceID;
		}

	}
}
