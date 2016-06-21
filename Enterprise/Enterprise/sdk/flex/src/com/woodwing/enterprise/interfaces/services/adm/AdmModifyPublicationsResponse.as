/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmModifyPublicationsResponse")]

	public class AdmModifyPublicationsResponse
	{
		private var _Publications:Array;

		public function AdmModifyPublicationsResponse() {
		}

		public function get Publications():Array {
			return this._Publications;
		}
		public function set Publications(Publications:Array):void {
			this._Publications = Publications;
		}

	}
}
