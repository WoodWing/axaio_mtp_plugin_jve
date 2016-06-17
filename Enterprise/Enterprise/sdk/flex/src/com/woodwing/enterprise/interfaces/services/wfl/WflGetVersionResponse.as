/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflVersionInfo;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetVersionResponse")]

	public class WflGetVersionResponse
	{
		private var _VersionInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflVersionInfo;

		public function WflGetVersionResponse() {
		}

		public function get VersionInfo():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflVersionInfo {
			return this._VersionInfo;
		}
		public function set VersionInfo(VersionInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflVersionInfo):void {
			this._VersionInfo = VersionInfo;
		}

	}
}
