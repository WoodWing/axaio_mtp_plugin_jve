/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetAutocompleteTermEntitiesRequest")]

	public class AdmGetAutocompleteTermEntitiesRequest
	{
		private var _Ticket:String;
		private var _AutocompleteProvider:String;

		public function AdmGetAutocompleteTermEntitiesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get AutocompleteProvider():String {
			return this._AutocompleteProvider;
		}
		public function set AutocompleteProvider(AutocompleteProvider:String):void {
			this._AutocompleteProvider = AutocompleteProvider;
		}

	}
}
