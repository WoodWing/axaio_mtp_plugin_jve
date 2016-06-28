/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateObjectOperationsResponse")]

	public class WflCreateObjectOperationsResponse
	{
		private var _Operations:Array;
		private var _Reports:Array;

		public function WflCreateObjectOperationsResponse() {
		}

		public function get Operations():Array {
			return this._Operations;
		}
		public function set Operations(Operations:Array):void {
			this._Operations = Operations;
		}

		public function get Reports():Array {
			return this._Reports;
		}
		public function set Reports(Reports:Array):void {
			this._Reports = Reports;
		}

	}
}
