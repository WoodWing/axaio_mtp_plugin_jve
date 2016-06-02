/*
	Enterprise Planning Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pln
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pln.PlnCreateLayoutsResponse")]

	public class PlnCreateLayoutsResponse
	{
		private var _Layouts:Array;

		public function PlnCreateLayoutsResponse() {
		}

		public function get Layouts():Array {
			return this._Layouts;
		}
		public function set Layouts(Layouts:Array):void {
			this._Layouts = Layouts;
		}

	}
}
