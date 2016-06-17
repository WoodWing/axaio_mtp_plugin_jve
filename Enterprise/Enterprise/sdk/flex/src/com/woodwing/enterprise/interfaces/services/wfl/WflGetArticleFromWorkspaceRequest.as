/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetArticleFromWorkspaceRequest")]

	public class WflGetArticleFromWorkspaceRequest
	{
		private var _Ticket:String;
		private var _WorkspaceId:String;

		public function WflGetArticleFromWorkspaceRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get WorkspaceId():String {
			return this._WorkspaceId;
		}
		public function set WorkspaceId(WorkspaceId:String):void {
			this._WorkspaceId = WorkspaceId;
		}

	}
}
