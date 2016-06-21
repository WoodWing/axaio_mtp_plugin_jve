/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflBasicMetaData;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectTargetsInfo")]

	public class WflObjectTargetsInfo
	{
		private var _BasicMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflBasicMetaData;
		private var _Targets:Array;

		public function WflObjectTargetsInfo() {
		}

		public function get BasicMetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflBasicMetaData {
			return this._BasicMetaData;
		}
		public function set BasicMetaData(BasicMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflBasicMetaData):void {
			this._BasicMetaData = BasicMetaData;
		}

		public function get Targets():Array {
			return this._Targets;
		}
		public function set Targets(Targets:Array):void {
			this._Targets = Targets;
		}

	}
}
