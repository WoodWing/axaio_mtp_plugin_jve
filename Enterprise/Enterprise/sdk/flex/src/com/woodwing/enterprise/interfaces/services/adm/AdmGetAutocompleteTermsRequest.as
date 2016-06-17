/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	import com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmTermEntity;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetAutocompleteTermsRequest")]

	public class AdmGetAutocompleteTermsRequest
	{
		private var _Ticket:String;
		private var _TermEntity:com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmTermEntity;
		private var _TypedValue:String;
		private var _FirstEntry:Number;
		private var _MaxEntries:Number;

		public function AdmGetAutocompleteTermsRequest() {
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

		public function get TypedValue():String {
			return this._TypedValue;
		}
		public function set TypedValue(TypedValue:String):void {
			this._TypedValue = TypedValue;
		}

		public function get FirstEntry():Number {
			return this._FirstEntry;
		}
		public function set FirstEntry(FirstEntry:Number):void {
			this._FirstEntry = FirstEntry;
		}

		public function get MaxEntries():Number {
			return this._MaxEntries;
		}
		public function set MaxEntries(MaxEntries:Number):void {
			this._MaxEntries = MaxEntries;
		}

	}
}
