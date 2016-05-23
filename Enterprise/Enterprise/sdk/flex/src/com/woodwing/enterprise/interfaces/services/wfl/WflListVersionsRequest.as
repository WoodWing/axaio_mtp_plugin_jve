/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflListVersionsRequest")]

	public class WflListVersionsRequest
	{
		private var _Ticket:String;
		private var _ID:String;
		private var _Rendition:String;
		private var _Areas:Array;

		public function WflListVersionsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get Rendition():String {
			return this._Rendition;
		}
		public function set Rendition(Rendition:String):void {
			this._Rendition = Rendition;
		}

		public function get Areas():Array {
			return this._Areas;
		}
		public function set Areas(Areas:Array):void {
			this._Areas = Areas;
		}

	}
}
