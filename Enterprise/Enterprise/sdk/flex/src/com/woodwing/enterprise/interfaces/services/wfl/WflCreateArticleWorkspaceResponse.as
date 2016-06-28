/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateArticleWorkspaceResponse")]

	public class WflCreateArticleWorkspaceResponse
	{
		private var _WorkspaceId:String;

		public function WflCreateArticleWorkspaceResponse() {
		}

		public function get WorkspaceId():String {
			return this._WorkspaceId;
		}
		public function set WorkspaceId(WorkspaceId:String):void {
			this._WorkspaceId = WorkspaceId;
		}

	}
}
