/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDictionary")]

	public class WflDictionary
	{
		private var _Name:String;
		private var _Language:String;
		private var _DocLanguage:String;
		private var _WordChars:String;

		public function WflDictionary() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Language():String {
			return this._Language;
		}
		public function set Language(Language:String):void {
			this._Language = Language;
		}

		public function get DocLanguage():String {
			return this._DocLanguage;
		}
		public function set DocLanguage(DocLanguage:String):void {
			this._DocLanguage = DocLanguage;
		}

		public function get WordChars():String {
			return this._WordChars;
		}
		public function set WordChars(WordChars:String):void {
			this._WordChars = WordChars;
		}

	}
}
