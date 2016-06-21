/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflListArticleWorkspacesResponse")]

	public class WflListArticleWorkspacesResponse
	{
		private var _Workspaces:Array;

		public function WflListArticleWorkspacesResponse() {
		}

		public function get Workspaces():Array {
			return this._Workspaces;
		}
		public function set Workspaces(Workspaces:Array):void {
			this._Workspaces = Workspaces;
		}

	}
}
