/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmAddTemplateObjectsResponse")]

	public class AdmAddTemplateObjectsResponse
	{
		private var _UserGroups:Array;
		private var _ObjectInfos:Array;

		public function AdmAddTemplateObjectsResponse() {
		}

		public function get UserGroups():Array {
			return this._UserGroups;
		}
		public function set UserGroups(UserGroups:Array):void {
			this._UserGroups = UserGroups;
		}

		public function get ObjectInfos():Array {
			return this._ObjectInfos;
		}
		public function set ObjectInfos(ObjectInfos:Array):void {
			this._ObjectInfos = ObjectInfos;
		}

	}
}
