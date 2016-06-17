/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflSaveObjectsRequest")]

	public class WflSaveObjectsRequest
	{
		private var _Ticket:String;
		private var _CreateVersion:String;
		private var _ForceCheckIn:String;
		private var _Unlock:String;
		private var _Objects:Array;
		private var _ReadMessageIDs:Array;
		private var _Messages:Array;

		public function WflSaveObjectsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}


		// _CreateVersion should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get CreateVersion():String {
			return this._CreateVersion;
		}

		// _CreateVersion should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set CreateVersion(CreateVersion:String):void {
			this._CreateVersion = CreateVersion;
		}


		// _ForceCheckIn should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get ForceCheckIn():String {
			return this._ForceCheckIn;
		}

		// _ForceCheckIn should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set ForceCheckIn(ForceCheckIn:String):void {
			this._ForceCheckIn = ForceCheckIn;
		}


		// _Unlock should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Unlock():String {
			return this._Unlock;
		}

		// _Unlock should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Unlock(Unlock:String):void {
			this._Unlock = Unlock;
		}

		public function get Objects():Array {
			return this._Objects;
		}
		public function set Objects(Objects:Array):void {
			this._Objects = Objects;
		}

		public function get ReadMessageIDs():Array {
			return this._ReadMessageIDs;
		}
		public function set ReadMessageIDs(ReadMessageIDs:Array):void {
			this._ReadMessageIDs = ReadMessageIDs;
		}

		public function get Messages():Array {
			return this._Messages;
		}
		public function set Messages(Messages:Array):void {
			this._Messages = Messages;
		}

	}
}
