/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmCreateIssuesRequest")]

	public class AdmCreateIssuesRequest
	{
		private var _Ticket:String;
		private var _RequestModes:Array;
		private var _PublicationId:Number;
		private var _PubChannelId:Number;
		private var _Issues:Array;

		public function AdmCreateIssuesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get RequestModes():Array {
			return this._RequestModes;
		}
		public function set RequestModes(RequestModes:Array):void {
			this._RequestModes = RequestModes;
		}

		public function get PublicationId():Number {
			return this._PublicationId;
		}
		public function set PublicationId(PublicationId:Number):void {
			this._PublicationId = PublicationId;
		}

		public function get PubChannelId():Number {
			return this._PubChannelId;
		}
		public function set PubChannelId(PubChannelId:Number):void {
			this._PubChannelId = PubChannelId;
		}

		public function get Issues():Array {
			return this._Issues;
		}
		public function set Issues(Issues:Array):void {
			this._Issues = Issues;
		}

	}
}
