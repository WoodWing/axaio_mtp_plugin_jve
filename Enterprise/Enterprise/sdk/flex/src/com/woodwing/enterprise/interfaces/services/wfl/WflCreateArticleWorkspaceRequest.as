/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateArticleWorkspaceRequest")]

	public class WflCreateArticleWorkspaceRequest
	{
		private var _Ticket:String;
		private var _ID:String;
		private var _Format:String;
		private var _Content:String;

		public function WflCreateArticleWorkspaceRequest() {
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

		public function get Format():String {
			return this._Format;
		}
		public function set Format(Format:String):void {
			this._Format = Format;
		}

		public function get Content():String {
			return this._Content;
		}
		public function set Content(Content:String):void {
			this._Content = Content;
		}

	}
}
