/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflLockObjectsRequest")]

	public class WflLockObjectsRequest
	{
		private var _Ticket:String;
		private var _HaveVersions:Array;

		public function WflLockObjectsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get HaveVersions():Array {
			return this._HaveVersions;
		}
		public function set HaveVersions(HaveVersions:Array):void {
			this._HaveVersions = HaveVersions;
		}

	}
}
