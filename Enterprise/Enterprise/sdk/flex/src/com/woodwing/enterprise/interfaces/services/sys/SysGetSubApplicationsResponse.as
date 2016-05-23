/*
	Enterprise SysAdmin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.sys
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.sys.SysGetSubApplicationsResponse")]

	public class SysGetSubApplicationsResponse
	{
		private var _SubApplications:Array;

		public function SysGetSubApplicationsResponse() {
		}

		public function get SubApplications():Array {
			return this._SubApplications;
		}
		public function set SubApplications(SubApplications:Array):void {
			this._SubApplications = SubApplications;
		}

	}
}
