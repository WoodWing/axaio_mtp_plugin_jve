/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.PubGetDossierOrderResponse")]

	public class PubGetDossierOrderResponse
	{
		private var _DossierIDs:Array;

		public function PubGetDossierOrderResponse() {
		}

		public function get DossierIDs():Array {
			return this._DossierIDs;
		}
		public function set DossierIDs(DossierIDs:Array):void {
			this._DossierIDs = DossierIDs;
		}

	}
}
