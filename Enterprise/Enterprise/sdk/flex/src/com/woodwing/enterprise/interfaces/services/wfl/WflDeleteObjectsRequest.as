/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflDeleteObjectsRequest")]

	public class WflDeleteObjectsRequest
	{
		private var _Ticket:String;
		private var _IDs:Array;
		private var _Permanent:String;
		private var _Params:Array;
		private var _Areas:Array;
		private var _Context:String;

		public function WflDeleteObjectsRequest() {
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


		// _Permanent should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Permanent():String {
			return this._Permanent;
		}

		// _Permanent should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Permanent(Permanent:String):void {
			this._Permanent = Permanent;
		}

		public function get Params():Array {
			return this._Params;
		}
		public function set Params(Params:Array):void {
			this._Params = Params;
		}

		public function get Areas():Array {
			return this._Areas;
		}
		public function set Areas(Areas:Array):void {
			this._Areas = Areas;
		}

		public function get Context():String {
			return this._Context;
		}
		public function set Context(Context:String):void {
			this._Context = Context;
		}

	}
}
