/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateObjectRelationsRequest")]

	public class WflCreateObjectRelationsRequest
	{
		private var _Ticket:String;
		private var _Relations:Array;

		public function WflCreateObjectRelationsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Relations():Array {
			return this._Relations;
		}
		public function set Relations(Relations:Array):void {
			this._Relations = Relations;
		}

	}
}
