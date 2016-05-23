/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflAutocompleteResponse")]

	public class WflAutocompleteResponse
	{
		private var _Tags:Array;

		public function WflAutocompleteResponse() {
		}

		public function get Tags():Array {
			return this._Tags;
		}
		public function set Tags(Tags:Array):void {
			this._Tags = Tags;
		}

	}
}
