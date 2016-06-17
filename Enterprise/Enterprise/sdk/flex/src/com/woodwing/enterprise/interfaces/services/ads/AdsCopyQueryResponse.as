/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsCopyQueryResponse")]

	public class AdsCopyQueryResponse
	{
		private var _NewQueryID:String;

		public function AdsCopyQueryResponse() {
		}

		public function get NewQueryID():String {
			return this._NewQueryID;
		}
		public function set NewQueryID(NewQueryID:String):void {
			this._NewQueryID = NewQueryID;
		}

	}
}
