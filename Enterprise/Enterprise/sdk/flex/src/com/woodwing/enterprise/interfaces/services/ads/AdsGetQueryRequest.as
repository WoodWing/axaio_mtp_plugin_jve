/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsGetQueryRequest")]

	public class AdsGetQueryRequest
	{
		private var _Ticket:String;
		private var _QueryID:String;

		public function AdsGetQueryRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get QueryID():String {
			return this._QueryID;
		}
		public function set QueryID(QueryID:String):void {
			this._QueryID = QueryID;
		}

	}
}
