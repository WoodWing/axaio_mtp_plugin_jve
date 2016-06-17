/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflChildRow")]

	public class WflChildRow
	{
		private var _Parents:Array;
		private var _Row:Array;

		public function WflChildRow() {
		}

		public function get Parents():Array {
			return this._Parents;
		}
		public function set Parents(Parents:Array):void {
			this._Parents = Parents;
		}

		public function get Row():Array {
			return this._Row;
		}
		public function set Row(Row:Array):void {
			this._Row = Row;
		}

	}
}
