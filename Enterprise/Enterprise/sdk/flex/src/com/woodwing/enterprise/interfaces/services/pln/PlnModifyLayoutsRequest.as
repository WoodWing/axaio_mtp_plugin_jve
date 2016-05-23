/*
	Enterprise Planning Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pln
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pln.PlnModifyLayoutsRequest")]

	public class PlnModifyLayoutsRequest
	{
		private var _Ticket:String;
		private var _Layouts:Array;

		public function PlnModifyLayoutsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Layouts():Array {
			return this._Layouts;
		}
		public function set Layouts(Layouts:Array):void {
			this._Layouts = Layouts;
		}

	}
}
