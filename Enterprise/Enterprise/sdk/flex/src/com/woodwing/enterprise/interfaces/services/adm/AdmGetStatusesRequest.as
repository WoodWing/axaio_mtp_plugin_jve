/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetStatusesRequest")]

	public class AdmGetStatusesRequest
	{
		private var _Ticket:String;
		private var _PublicationId:Number;
		private var _IssueId:Number;
		private var _ObjectType:String;
		private var _StatusIds:Array;

		public function AdmGetStatusesRequest() {
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

		public function get IssueId():Number {
			return this._IssueId;
		}
		public function set IssueId(IssueId:Number):void {
			this._IssueId = IssueId;
		}

		public function get ObjectType():String {
			return this._ObjectType;
		}
		public function set ObjectType(ObjectType:String):void {
			this._ObjectType = ObjectType;
		}

		public function get StatusIds():Array {
			return this._StatusIds;
		}
		public function set StatusIds(StatusIds:Array):void {
			this._StatusIds = StatusIds;
		}

	}
}
