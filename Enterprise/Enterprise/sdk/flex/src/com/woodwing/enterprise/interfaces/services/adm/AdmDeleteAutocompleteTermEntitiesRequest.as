/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmDeleteAutocompleteTermEntitiesRequest")]

	public class AdmDeleteAutocompleteTermEntitiesRequest
	{
		private var _Ticket:String;
		private var _TermEntities:Array;

		public function AdmDeleteAutocompleteTermEntitiesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get TermEntities():Array {
			return this._TermEntities;
		}
		public function set TermEntities(TermEntities:Array):void {
			this._TermEntities = TermEntities;
		}

	}
}
