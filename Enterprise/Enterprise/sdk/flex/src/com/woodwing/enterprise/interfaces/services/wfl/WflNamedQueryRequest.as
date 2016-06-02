/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflNamedQueryRequest")]

	public class WflNamedQueryRequest
	{
		private var _Ticket:String;
		private var _Query:String;
		private var _Params:Array;
		private var _FirstEntry:Number;
		private var _MaxEntries:Number;
		private var _Hierarchical:String;
		private var _Order:Array;

		public function WflNamedQueryRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Query():String {
			return this._Query;
		}
		public function set Query(Query:String):void {
			this._Query = Query;
		}

		public function get Params():Array {
			return this._Params;
		}
		public function set Params(Params:Array):void {
			this._Params = Params;
		}

		public function get FirstEntry():Number {
			return this._FirstEntry;
		}
		public function set FirstEntry(FirstEntry:Number):void {
			this._FirstEntry = FirstEntry;
		}

		public function get MaxEntries():Number {
			return this._MaxEntries;
		}
		public function set MaxEntries(MaxEntries:Number):void {
			this._MaxEntries = MaxEntries;
		}


		// _Hierarchical should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Hierarchical():String {
			return this._Hierarchical;
		}

		// _Hierarchical should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Hierarchical(Hierarchical:String):void {
			this._Hierarchical = Hierarchical;
		}

		public function get Order():Array {
			return this._Order;
		}
		public function set Order(Order:Array):void {
			this._Order = Order;
		}

	}
}
