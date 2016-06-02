/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.PubGetDossierURLResponse")]

	public class PubGetDossierURLResponse
	{
		private var _URL:String;

		public function PubGetDossierURLResponse() {
		}

		public function get URL():String {
			return this._URL;
		}
		public function set URL(URL:String):void {
			this._URL = URL;
		}

	}
}
