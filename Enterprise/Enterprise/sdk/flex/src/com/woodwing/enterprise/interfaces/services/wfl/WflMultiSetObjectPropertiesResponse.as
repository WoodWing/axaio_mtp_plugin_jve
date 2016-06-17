/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflMultiSetObjectPropertiesResponse")]

	public class WflMultiSetObjectPropertiesResponse
	{
		private var _MetaData:Array;
		private var _Reports:Array;

		public function WflMultiSetObjectPropertiesResponse() {
		}

		public function get MetaData():Array {
			return this._MetaData;
		}
		public function set MetaData(MetaData:Array):void {
			this._MetaData = MetaData;
		}

		public function get Reports():Array {
			return this._Reports;
		}
		public function set Reports(Reports:Array):void {
			this._Reports = Reports;
		}

	}
}
