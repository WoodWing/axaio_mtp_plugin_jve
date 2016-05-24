/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyUsage")]

	public class WflPropertyUsage
	{
		private var _Name:String;
		private var _Editable:String;
		private var _Mandatory:String;
		private var _Restricted:String;
		private var _RefreshOnChange:String;
		private var _InitialHeight:Number;
		private var _MultipleObjects:String;

		public function WflPropertyUsage() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}


		// _Editable should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Editable():String {
			return this._Editable;
		}

		// _Editable should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Editable(Editable:String):void {
			this._Editable = Editable;
		}


		// _Mandatory should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Mandatory():String {
			return this._Mandatory;
		}

		// _Mandatory should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Mandatory(Mandatory:String):void {
			this._Mandatory = Mandatory;
		}


		// _Restricted should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Restricted():String {
			return this._Restricted;
		}

		// _Restricted should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Restricted(Restricted:String):void {
			this._Restricted = Restricted;
		}


		// _RefreshOnChange should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RefreshOnChange():String {
			return this._RefreshOnChange;
		}

		// _RefreshOnChange should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RefreshOnChange(RefreshOnChange:String):void {
			this._RefreshOnChange = RefreshOnChange;
		}

		public function get InitialHeight():Number {
			return this._InitialHeight;
		}
		public function set InitialHeight(InitialHeight:Number):void {
			this._InitialHeight = InitialHeight;
		}


		// _MultipleObjects should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get MultipleObjects():String {
			return this._MultipleObjects;
		}

		// _MultipleObjects should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set MultipleObjects(MultipleObjects:String):void {
			this._MultipleObjects = MultipleObjects;
		}

	}
}
