/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsDeletePublicationRequest")]

	public class AdsDeletePublicationRequest
	{
		private var _Ticket:String;
		private var _DatasourceID:String;
		private var _PublicationID:String;

		public function AdsDeletePublicationRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get DatasourceID():String {
			return this._DatasourceID;
		}
		public function set DatasourceID(DatasourceID:String):void {
			this._DatasourceID = DatasourceID;
		}

		public function get PublicationID():String {
			return this._PublicationID;
		}
		public function set PublicationID(PublicationID:String):void {
			this._PublicationID = PublicationID;
		}

	}
}
