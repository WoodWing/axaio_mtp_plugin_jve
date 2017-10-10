/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmModifyRoutingsResponse")]

	public class AdmModifyRoutingsResponse
	{
		private var _Routings:Array;

		public function AdmModifyRoutingsResponse() {
		}

		public function get Routings():Array {
			return this._Routings;
		}
		public function set Routings(Routings:Array):void {
			this._Routings = Routings;
		}

	}
}
