/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmModifyStatusesRequest")]

	public class AdmModifyStatusesRequest
	{
		private var _Ticket:String;
		private var _Statuses:Array;

		public function AdmModifyStatusesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Statuses():Array {
			return this._Statuses;
		}
		public function set Statuses(Statuses:Array):void {
			this._Statuses = Statuses;
		}

	}
}
