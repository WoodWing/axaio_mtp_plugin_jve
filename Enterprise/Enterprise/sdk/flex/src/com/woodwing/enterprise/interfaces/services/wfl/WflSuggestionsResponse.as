/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflSuggestionsResponse")]

	public class WflSuggestionsResponse
	{
		private var _SuggestedTags:Array;

		public function WflSuggestionsResponse() {
		}

		public function get SuggestedTags():Array {
			return this._SuggestedTags;
		}
		public function set SuggestedTags(SuggestedTags:Array):void {
			this._SuggestedTags = SuggestedTags;
		}

	}
}
