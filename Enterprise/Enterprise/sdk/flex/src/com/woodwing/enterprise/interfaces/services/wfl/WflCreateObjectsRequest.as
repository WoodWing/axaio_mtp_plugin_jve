/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateObjectsRequest")]

	public class WflCreateObjectsRequest
	{
		private var _Ticket:String;
		private var _Lock:String;
		private var _Objects:Array;
		private var _Messages:Array;
		private var _AutoNaming:String;
		private var _ReplaceGUIDs:String;

		public function WflCreateObjectsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}


		// _Lock should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Lock():String {
			return this._Lock;
		}

		// _Lock should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Lock(Lock:String):void {
			this._Lock = Lock;
		}

		public function get Objects():Array {
			return this._Objects;
		}
		public function set Objects(Objects:Array):void {
			this._Objects = Objects;
		}

		public function get Messages():Array {
			return this._Messages;
		}
		public function set Messages(Messages:Array):void {
			this._Messages = Messages;
		}


		// _AutoNaming should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get AutoNaming():String {
			return this._AutoNaming;
		}

		// _AutoNaming should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set AutoNaming(AutoNaming:String):void {
			this._AutoNaming = AutoNaming;
		}


		// _ReplaceGUIDs should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get ReplaceGUIDs():String {
			return this._ReplaceGUIDs;
		}

		// _ReplaceGUIDs should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set ReplaceGUIDs(ReplaceGUIDs:String):void {
			this._ReplaceGUIDs = ReplaceGUIDs;
		}

	}
}
