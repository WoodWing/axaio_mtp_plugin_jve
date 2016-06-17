/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflInstantiateTemplateResponse")]

	public class WflInstantiateTemplateResponse
	{
		private var _Objects:Array;
		private var _Reports:Array;

		public function WflInstantiateTemplateResponse() {
		}

		public function get Objects():Array {
			return this._Objects;
		}
		public function set Objects(Objects:Array):void {
			this._Objects = Objects;
		}

		public function get Reports():Array {
			return this._Reports;
		}
		public function set Reports(Reports:Array):void {
			this._Reports = Reports;
		}

	}
}
