/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetSuggestionsResponse")]

	public class WflGetSuggestionsResponse
	{
		private var _Suggestions:Array;

		public function WflGetSuggestionsResponse() {
		}

		public function get Suggestions():Array {
			return this._Suggestions;
		}
		public function set Suggestions(Suggestions:Array):void {
			this._Suggestions = Suggestions;
		}

	}
}