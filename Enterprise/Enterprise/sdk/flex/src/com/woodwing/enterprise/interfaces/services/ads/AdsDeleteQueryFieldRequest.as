/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsDeleteQueryFieldRequest")]

	public class AdsDeleteQueryFieldRequest
	{
		private var _Ticket:String;
		private var _FieldID:String;

		public function AdsDeleteQueryFieldRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get FieldID():String {
			return this._FieldID;
		}
		public function set FieldID(FieldID:String):void {
			this._FieldID = FieldID;
		}

	}
}
