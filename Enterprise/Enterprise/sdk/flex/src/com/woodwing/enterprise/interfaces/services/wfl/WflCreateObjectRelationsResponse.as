/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateObjectRelationsResponse")]

	public class WflCreateObjectRelationsResponse
	{
		private var _Relations:Array;

		public function WflCreateObjectRelationsResponse() {
		}

		public function get Relations():Array {
			return this._Relations;
		}
		public function set Relations(Relations:Array):void {
			this._Relations = Relations;
		}

	}
}
