/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmDeletePublicationsRequest")]

	public class AdmDeletePublicationsRequest
	{
		private var _Ticket:String;
		private var _PublicationIds:Array;

		public function AdmDeletePublicationsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get PublicationIds():Array {
			return this._PublicationIds;
		}
		public function set PublicationIds(PublicationIds:Array):void {
			this._PublicationIds = PublicationIds;
		}

	}
}
