/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflDeleteUserSettingsRequest")]

	public class WflDeleteUserSettingsRequest
	{
		private var _Ticket:String;
		private var _Settings:Array;

		public function WflDeleteUserSettingsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Settings():Array {
			return this._Settings;
		}
		public function set Settings(Settings:Array):void {
			this._Settings = Settings;
		}

	}
}
