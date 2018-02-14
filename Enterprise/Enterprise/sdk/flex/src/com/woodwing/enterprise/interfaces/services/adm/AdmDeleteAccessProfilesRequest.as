/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmDeleteAccessProfilesRequest")]

	public class AdmDeleteAccessProfilesRequest
	{
		private var _Ticket:String;
		private var _AccessProfileIds:Array;

		public function AdmDeleteAccessProfilesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get AccessProfileIds():Array {
			return this._AccessProfileIds;
		}
		public function set AccessProfileIds(AccessProfileIds:Array):void {
			this._AccessProfileIds = AccessProfileIds;
		}

	}
}
