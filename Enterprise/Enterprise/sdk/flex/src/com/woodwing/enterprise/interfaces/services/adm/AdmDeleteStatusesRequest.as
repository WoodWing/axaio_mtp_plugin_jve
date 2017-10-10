/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmDeleteStatusesRequest")]

	public class AdmDeleteStatusesRequest
	{
		private var _Ticket:String;
		private var _StatusIds:Array;

		public function AdmDeleteStatusesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get StatusIds():Array {
			return this._StatusIds;
		}
		public function set StatusIds(StatusIds:Array):void {
			this._StatusIds = StatusIds;
		}

	}
}
