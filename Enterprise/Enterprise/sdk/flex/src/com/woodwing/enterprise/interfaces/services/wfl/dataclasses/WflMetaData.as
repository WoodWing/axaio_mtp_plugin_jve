/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflBasicMetaData;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflRightsMetaData;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflSourceMetaData;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflContentMetaData;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData")]

	public class WflMetaData
	{
		private var _BasicMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflBasicMetaData;
		private var _RightsMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflRightsMetaData;
		private var _SourceMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflSourceMetaData;
		private var _ContentMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflContentMetaData;
		private var _WorkflowMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData;
		private var _ExtraMetaData:Array;

		public function WflMetaData() {
		}

		public function get BasicMetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflBasicMetaData {
			return this._BasicMetaData;
		}
		public function set BasicMetaData(BasicMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflBasicMetaData):void {
			this._BasicMetaData = BasicMetaData;
		}

		public function get RightsMetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflRightsMetaData {
			return this._RightsMetaData;
		}
		public function set RightsMetaData(RightsMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflRightsMetaData):void {
			this._RightsMetaData = RightsMetaData;
		}

		public function get SourceMetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflSourceMetaData {
			return this._SourceMetaData;
		}
		public function set SourceMetaData(SourceMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflSourceMetaData):void {
			this._SourceMetaData = SourceMetaData;
		}

		public function get ContentMetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflContentMetaData {
			return this._ContentMetaData;
		}
		public function set ContentMetaData(ContentMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflContentMetaData):void {
			this._ContentMetaData = ContentMetaData;
		}

		public function get WorkflowMetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData {
			return this._WorkflowMetaData;
		}
		public function set WorkflowMetaData(WorkflowMetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData):void {
			this._WorkflowMetaData = WorkflowMetaData;
		}

		public function get ExtraMetaData():Array {
			return this._ExtraMetaData;
		}
		public function set ExtraMetaData(ExtraMetaData:Array):void {
			this._ExtraMetaData = ExtraMetaData;
		}

	}
}
