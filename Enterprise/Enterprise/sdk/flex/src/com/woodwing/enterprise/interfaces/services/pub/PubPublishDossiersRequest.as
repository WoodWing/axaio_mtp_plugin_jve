/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.PubPublishDossiersRequest")]

	public class PubPublishDossiersRequest
	{
		private var _Ticket:String;
		private var _DossierIDs:Array;
		private var _Targets:Array;
		private var _PublishedDossiers:Array;
		private var _RequestInfo:Array;
		private var _OperationId:String;

		public function PubPublishDossiersRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get DossierIDs():Array {
			return this._DossierIDs;
		}
		public function set DossierIDs(DossierIDs:Array):void {
			this._DossierIDs = DossierIDs;
		}

		public function get Targets():Array {
			return this._Targets;
		}
		public function set Targets(Targets:Array):void {
			this._Targets = Targets;
		}

		public function get PublishedDossiers():Array {
			return this._PublishedDossiers;
		}
		public function set PublishedDossiers(PublishedDossiers:Array):void {
			this._PublishedDossiers = PublishedDossiers;
		}

		public function get RequestInfo():Array {
			return this._RequestInfo;
		}
		public function set RequestInfo(RequestInfo:Array):void {
			this._RequestInfo = RequestInfo;
		}

		public function get OperationId():String {
			return this._OperationId;
		}
		public function set OperationId(OperationId:String):void {
			this._OperationId = OperationId;
		}

	}
}
