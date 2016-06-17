/*
	Enterprise SysAdmin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.sys
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.sys.SysGetSubApplicationsRequest")]

	public class SysGetSubApplicationsRequest
	{
		private var _Ticket:String;
		private var _ClientAppName:String;

		public function SysGetSubApplicationsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get ClientAppName():String {
			return this._ClientAppName;
		}
		public function set ClientAppName(ClientAppName:String):void {
			this._ClientAppName = ClientAppName;
		}

	}
}
