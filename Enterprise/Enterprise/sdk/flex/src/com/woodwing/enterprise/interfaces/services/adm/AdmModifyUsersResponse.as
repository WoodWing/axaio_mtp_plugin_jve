/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmModifyUsersResponse")]

	public class AdmModifyUsersResponse
	{
		private var _Users:Array;

		public function AdmModifyUsersResponse() {
		}

		public function get Users():Array {
			return this._Users;
		}
		public function set Users(Users:Array):void {
			this._Users = Users;
		}

	}
}
