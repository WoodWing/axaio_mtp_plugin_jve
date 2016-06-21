/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState")]

	public class WflState
	{
		private var _Id:String;
		private var _Name:String;
		private var _Type:String;
		private var _Produce:String;
		private var _Color:String;
		private var _DefaultRouteTo:String;

		public function WflState() {
		}

		public function get Id():String {
			return this._Id;
		}
		public function set Id(Id:String):void {
			this._Id = Id;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}


		// _Produce should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Produce():String {
			return this._Produce;
		}

		// _Produce should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Produce(Produce:String):void {
			this._Produce = Produce;
		}

		public function get Color():String {
			return this._Color;
		}
		public function set Color(Color:String):void {
			this._Color = Color;
		}

		public function get DefaultRouteTo():String {
			return this._DefaultRouteTo;
		}
		public function set DefaultRouteTo(DefaultRouteTo:String):void {
			this._DefaultRouteTo = DefaultRouteTo;
		}

	}
}
