/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetPublicationAdminAuthorizationsResponse")]

	public class AdmGetPublicationAdminAuthorizationsResponse
	{
		private var _UserGroupIds:Array;
		private var _UserGroups:Array;

		public function AdmGetPublicationAdminAuthorizationsResponse() {
		}

		public function get UserGroupIds():Array {
			return this._UserGroupIds;
		}
		public function set UserGroupIds(UserGroupIds:Array):void {
			this._UserGroupIds = UserGroupIds;
		}

		public function get UserGroups():Array {
			return this._UserGroups;
		}
		public function set UserGroups(UserGroups:Array):void {
			this._UserGroups = UserGroups;
		}

	}
}
