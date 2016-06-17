/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAutoSuggestProperty;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflAutocompleteRequest")]

	public class WflAutocompleteRequest
	{
		private var _Ticket:String;
		private var _AutocompleteProvider:String;
		private var _PublishSystemId:String;
		private var _ObjectId:String;
		private var _Property:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAutoSuggestProperty;
		private var _TypedValue:String;

		public function WflAutocompleteRequest() {
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

		public function get PublishSystemId():String {
			return this._PublishSystemId;
		}
		public function set PublishSystemId(PublishSystemId:String):void {
			this._PublishSystemId = PublishSystemId;
		}

		public function get ObjectId():String {
			return this._ObjectId;
		}
		public function set ObjectId(ObjectId:String):void {
			this._ObjectId = ObjectId;
		}

		public function get Property():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAutoSuggestProperty {
			return this._Property;
		}
		public function set Property(Property:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAutoSuggestProperty):void {
			this._Property = Property;
		}

		public function get TypedValue():String {
			return this._TypedValue;
		}
		public function set TypedValue(TypedValue:String):void {
			this._TypedValue = TypedValue;
		}

	}
}
