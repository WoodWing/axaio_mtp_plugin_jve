/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetServersResponse")]

	public class WflGetServersResponse
	{
		private var _Servers:Array;
		private var _CompanyLanguage:String;

		public function WflGetServersResponse() {
		}

		public function get Servers():Array {
			return this._Servers;
		}
		public function set Servers(Servers:Array):void {
			this._Servers = Servers;
		}

		public function get CompanyLanguage():String {
			return this._CompanyLanguage;
		}
		public function set CompanyLanguage(CompanyLanguage:String):void {
			this._CompanyLanguage = CompanyLanguage;
		}

	}
}
