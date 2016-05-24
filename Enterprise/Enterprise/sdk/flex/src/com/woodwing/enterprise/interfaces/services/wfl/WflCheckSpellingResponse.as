/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCheckSpellingResponse")]

	public class WflCheckSpellingResponse
	{
		private var _MisspelledWords:Array;

		public function WflCheckSpellingResponse() {
		}

		public function get MisspelledWords():Array {
			return this._MisspelledWords;
		}
		public function set MisspelledWords(MisspelledWords:Array):void {
			this._MisspelledWords = MisspelledWords;
		}

	}
}
