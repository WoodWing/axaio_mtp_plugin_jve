/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	import com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmTermEntity;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmDeleteAutocompleteTermsRequest")]

	public class AdmDeleteAutocompleteTermsRequest
	{
		private var _Ticket:String;
		private var _TermEntity:com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmTermEntity;
		private var _Terms:Array;

		public function AdmDeleteAutocompleteTermsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get TermEntity():com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmTermEntity {
			return this._TermEntity;
		}
		public function set TermEntity(TermEntity:com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmTermEntity):void {
			this._TermEntity = TermEntity;
		}

		public function get Terms():Array {
			return this._Terms;
		}
		public function set Terms(Terms:Array):void {
			this._Terms = Terms;
		}

	}
}
