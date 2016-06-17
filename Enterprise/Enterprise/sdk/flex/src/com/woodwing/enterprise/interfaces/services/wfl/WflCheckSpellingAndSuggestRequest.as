/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCheckSpellingAndSuggestRequest")]

	public class WflCheckSpellingAndSuggestRequest
	{
		private var _Ticket:String;
		private var _Language:String;
		private var _PublicationId:String;
		private var _WordsToCheck:Array;

		public function WflCheckSpellingAndSuggestRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Language():String {
			return this._Language;
		}
		public function set Language(Language:String):void {
			this._Language = Language;
		}

		public function get PublicationId():String {
			return this._PublicationId;
		}
		public function set PublicationId(PublicationId:String):void {
			this._PublicationId = PublicationId;
		}

		public function get WordsToCheck():Array {
			return this._WordsToCheck;
		}
		public function set WordsToCheck(WordsToCheck:Array):void {
			this._WordsToCheck = WordsToCheck;
		}

	}
}
