/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyInfo;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyUsage;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialogWidget")]

	public class WflDialogWidget
	{
		private var _PropertyInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyInfo;
		private var _PropertyUsage:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyUsage;

		public function WflDialogWidget() {
		}

		public function get PropertyInfo():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyInfo {
			return this._PropertyInfo;
		}
		public function set PropertyInfo(PropertyInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyInfo):void {
			this._PropertyInfo = PropertyInfo;
		}

		public function get PropertyUsage():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyUsage {
			return this._PropertyUsage;
		}
		public function set PropertyUsage(PropertyUsage:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyUsage):void {
			this._PropertyUsage = PropertyUsage;
		}

	}
}
