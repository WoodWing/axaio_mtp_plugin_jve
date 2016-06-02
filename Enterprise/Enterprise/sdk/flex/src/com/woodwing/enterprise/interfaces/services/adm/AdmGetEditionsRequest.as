/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetEditionsRequest")]

	public class AdmGetEditionsRequest
	{
		private var _Ticket:String;
		private var _PublicationId:Number;
		private var _PubChannelId:Number;
		private var _IssueId:Number;
		private var _EditionIds:Array;

		public function AdmGetEditionsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
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

		public function get IssueId():Number {
			return this._IssueId;
		}
		public function set IssueId(IssueId:Number):void {
			this._IssueId = IssueId;
		}

		public function get EditionIds():Array {
			return this._EditionIds;
		}
		public function set EditionIds(EditionIds:Array):void {
			this._EditionIds = EditionIds;
		}

	}
}
