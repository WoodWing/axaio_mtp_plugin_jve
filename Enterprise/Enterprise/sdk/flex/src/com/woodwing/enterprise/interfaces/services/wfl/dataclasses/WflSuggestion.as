/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflSuggestion")]

	public class WflSuggestion
	{
		private var _MisspelledWord:String;
		private var _Suggestions:Array;

		public function WflSuggestion() {
		}

		public function get MisspelledWord():String {
			return this._MisspelledWord;
		}
		public function set MisspelledWord(MisspelledWord:String):void {
			this._MisspelledWord = MisspelledWord;
		}

		public function get Suggestions():Array {
			return this._Suggestions;
		}
		public function set Suggestions(Suggestions:Array):void {
			this._Suggestions = Suggestions;
		}

	}
}
