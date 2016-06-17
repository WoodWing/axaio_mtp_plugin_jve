/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateObjectTargetsResponse")]

	public class WflCreateObjectTargetsResponse
	{
		private var _IDs:Array;
		private var _Targets:Array;

		public function WflCreateObjectTargetsResponse() {
		}

		public function get IDs():Array {
			return this._IDs;
		}
		public function set IDs(IDs:Array):void {
			this._IDs = IDs;
		}

		public function get Targets():Array {
			return this._Targets;
		}
		public function set Targets(Targets:Array):void {
			this._Targets = Targets;
		}

	}
}
