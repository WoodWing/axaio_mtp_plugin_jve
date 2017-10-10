/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmTemplateObjectAccess")]

	public class AdmTemplateObjectAccess
	{
		private var _TemplateObjectId:Number;
		private var _UserGroupId:Number;

		public function AdmTemplateObjectAccess() {
		}

		public function get TemplateObjectId():Number {
			return this._TemplateObjectId;
		}
		public function set TemplateObjectId(TemplateObjectId:Number):void {
			this._TemplateObjectId = TemplateObjectId;
		}

		public function get UserGroupId():Number {
			return this._UserGroupId;
		}
		public function set UserGroupId(UserGroupId:Number):void {
			this._UserGroupId = UserGroupId;
		}

	}
}
