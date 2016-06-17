/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflChangeOnlineStatusRequest")]

	public class WflChangeOnlineStatusRequest
	{
		private var _Ticket:String;
		private var _IDs:Array;
		private var _OnlineStatus:String;

		public function WflChangeOnlineStatusRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get IDs():Array {
			return this._IDs;
		}
		public function set IDs(IDs:Array):void {
			this._IDs = IDs;
		}

		public function get OnlineStatus():String {
			return this._OnlineStatus;
		}
		public function set OnlineStatus(OnlineStatus:String):void {
			this._OnlineStatus = OnlineStatus;
		}

	}
}
