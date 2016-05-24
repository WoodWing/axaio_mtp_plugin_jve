/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.PubAbortOperationRequest")]

	public class PubAbortOperationRequest
	{
		private var _Ticket:String;
		private var _OperationId:String;

		public function PubAbortOperationRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get OperationId():String {
			return this._OperationId;
		}
		public function set OperationId(OperationId:String):void {
			this._OperationId = OperationId;
		}

	}
}
