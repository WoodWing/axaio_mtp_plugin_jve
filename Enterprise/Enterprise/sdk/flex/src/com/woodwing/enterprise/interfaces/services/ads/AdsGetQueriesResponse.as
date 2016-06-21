/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetQueriesResponse")]

	public class AdsGetQueriesResponse
	{
		private var _Queries:Array;

		public function AdsGetQueriesResponse() {
		}

		public function get Queries():Array {
			return this._Queries;
		}
		public function set Queries(Queries:Array):void {
			this._Queries = Queries;
		}

	}
}
