/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflUpdateObjectLabelsResponse")]

	public class WflUpdateObjectLabelsResponse
	{
		private var _ObjectLabels:Array;

		public function WflUpdateObjectLabelsResponse() {
		}

		public function get ObjectLabels():Array {
			return this._ObjectLabels;
		}
		public function set ObjectLabels(ObjectLabels:Array):void {
			this._ObjectLabels = ObjectLabels;
		}

	}
}
