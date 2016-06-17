/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetPagesResponse")]

	public class WflGetPagesResponse
	{
		private var _ObjectPageInfos:Array;

		public function WflGetPagesResponse() {
		}

		public function get ObjectPageInfos():Array {
			return this._ObjectPageInfos;
		}
		public function set ObjectPageInfos(ObjectPageInfos:Array):void {
			this._ObjectPageInfos = ObjectPageInfos;
		}

	}
}
