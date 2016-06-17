/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflListVersionsResponse")]

	public class WflListVersionsResponse
	{
		private var _Versions:Array;

		public function WflListVersionsResponse() {
		}

		public function get Versions():Array {
			return this._Versions;
		}
		public function set Versions(Versions:Array):void {
			this._Versions = Versions;
		}

	}
}
