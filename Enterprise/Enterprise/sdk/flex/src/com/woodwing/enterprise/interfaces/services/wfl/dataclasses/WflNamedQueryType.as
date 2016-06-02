/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflNamedQueryType")]

	public class WflNamedQueryType
	{
		private var _Name:String;
		private var _Params:Array;

		public function WflNamedQueryType() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Params():Array {
			return this._Params;
		}
		public function set Params(Params:Array):void {
			this._Params = Params;
		}

	}
}
