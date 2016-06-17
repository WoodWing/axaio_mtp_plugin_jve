/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflUnlockObjectsResponse")]

	public class WflUnlockObjectsResponse
	{
		private var _Reports:Array;

		public function WflUnlockObjectsResponse() {
		}

		public function get Reports():Array {
			return this._Reports;
		}
		public function set Reports(Reports:Array):void {
			this._Reports = Reports;
		}

	}
}
