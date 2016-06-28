/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetAutocompleteTermsResponse")]

	public class AdmGetAutocompleteTermsResponse
	{
		private var _Terms:Array;
		private var _FirstEntry:Number;
		private var _ListedEntries:Number;
		private var _TotalEntries:Number;

		public function AdmGetAutocompleteTermsResponse() {
		}

		public function get Terms():Array {
			return this._Terms;
		}
		public function set Terms(Terms:Array):void {
			this._Terms = Terms;
		}

		public function get FirstEntry():Number {
			return this._FirstEntry;
		}
		public function set FirstEntry(FirstEntry:Number):void {
			this._FirstEntry = FirstEntry;
		}

		public function get ListedEntries():Number {
			return this._ListedEntries;
		}
		public function set ListedEntries(ListedEntries:Number):void {
			this._ListedEntries = ListedEntries;
		}

		public function get TotalEntries():Number {
			return this._TotalEntries;
		}
		public function set TotalEntries(TotalEntries:Number):void {
			this._TotalEntries = TotalEntries;
		}

	}
}
