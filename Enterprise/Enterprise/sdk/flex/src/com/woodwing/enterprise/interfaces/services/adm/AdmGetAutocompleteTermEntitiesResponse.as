/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetAutocompleteTermEntitiesResponse")]

	public class AdmGetAutocompleteTermEntitiesResponse
	{
		private var _TermEntities:Array;

		public function AdmGetAutocompleteTermEntitiesResponse() {
		}

		public function get TermEntities():Array {
			return this._TermEntities;
		}
		public function set TermEntities(TermEntities:Array):void {
			this._TermEntities = TermEntities;
		}

	}
}