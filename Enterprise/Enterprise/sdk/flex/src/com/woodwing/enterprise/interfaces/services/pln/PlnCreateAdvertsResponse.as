/*
	Enterprise Planning Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pln
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pln.PlnCreateAdvertsResponse")]

	public class PlnCreateAdvertsResponse
	{
		private var _Adverts:Array;

		public function PlnCreateAdvertsResponse() {
		}

		public function get Adverts():Array {
			return this._Adverts;
		}
		public function set Adverts(Adverts:Array):void {
			this._Adverts = Adverts;
		}

	}
}
