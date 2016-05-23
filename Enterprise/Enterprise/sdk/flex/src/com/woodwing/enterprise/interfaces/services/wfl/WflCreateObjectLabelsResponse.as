/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateObjectLabelsResponse")]

	public class WflCreateObjectLabelsResponse
	{
		private var _ObjectLabels:Array;

		public function WflCreateObjectLabelsResponse() {
		}

		public function get ObjectLabels():Array {
			return this._ObjectLabels;
		}
		public function set ObjectLabels(ObjectLabels:Array):void {
			this._ObjectLabels = ObjectLabels;
		}

	}
}
