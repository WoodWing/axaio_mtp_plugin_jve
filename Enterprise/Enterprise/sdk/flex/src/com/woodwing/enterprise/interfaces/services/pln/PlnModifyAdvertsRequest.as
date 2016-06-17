/*
	Enterprise Planning Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pln
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pln.PlnModifyAdvertsRequest")]

	public class PlnModifyAdvertsRequest
	{
		private var _Ticket:String;
		private var _LayoutId:String;
		private var _LayoutName:String;
		private var _Adverts:Array;

		public function PlnModifyAdvertsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get LayoutId():String {
			return this._LayoutId;
		}
		public function set LayoutId(LayoutId:String):void {
			this._LayoutId = LayoutId;
		}

		public function get LayoutName():String {
			return this._LayoutName;
		}
		public function set LayoutName(LayoutName:String):void {
			this._LayoutName = LayoutName;
		}

		public function get Adverts():Array {
			return this._Adverts;
		}
		public function set Adverts(Adverts:Array):void {
			this._Adverts = Adverts;
		}

	}
}
