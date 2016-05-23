/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflSuggestionsRequest")]

	public class WflSuggestionsRequest
	{
		private var _Ticket:String;
		private var _SuggestionProvider:String;
		private var _ObjectId:String;
		private var _MetaData:Array;
		private var _SuggestForProperties:Array;

		public function WflSuggestionsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get SuggestionProvider():String {
			return this._SuggestionProvider;
		}
		public function set SuggestionProvider(SuggestionProvider:String):void {
			this._SuggestionProvider = SuggestionProvider;
		}

		public function get ObjectId():String {
			return this._ObjectId;
		}
		public function set ObjectId(ObjectId:String):void {
			this._ObjectId = ObjectId;
		}

		public function get MetaData():Array {
			return this._MetaData;
		}
		public function set MetaData(MetaData:Array):void {
			this._MetaData = MetaData;
		}

		public function get SuggestForProperties():Array {
			return this._SuggestForProperties;
		}
		public function set SuggestForProperties(SuggestForProperties:Array):void {
			this._SuggestForProperties = SuggestForProperties;
		}

	}
}
