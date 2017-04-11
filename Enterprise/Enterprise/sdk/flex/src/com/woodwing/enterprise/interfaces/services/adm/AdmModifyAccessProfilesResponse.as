/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmModifyAccessProfilesResponse")]

	public class AdmModifyAccessProfilesResponse
	{
		private var _AccessProfiles:Array;

		public function AdmModifyAccessProfilesResponse() {
		}

		public function get AccessProfiles():Array {
			return this._AccessProfiles;
		}
		public function set AccessProfiles(AccessProfiles:Array):void {
			this._AccessProfiles = AccessProfiles;
		}

	}
}
