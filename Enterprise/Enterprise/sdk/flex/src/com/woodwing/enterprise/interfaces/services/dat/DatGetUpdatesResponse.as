/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.DatGetUpdatesResponse")]

	public class DatGetUpdatesResponse
	{
		private var _Records:Array;

		public function DatGetUpdatesResponse() {
		}

		public function get Records():Array {
			return this._Records;
		}
		public function set Records(Records:Array):void {
			this._Records = Records;
		}

	}
}
