/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflPreviewArticlesAtWorkspaceRequest")]

	public class WflPreviewArticlesAtWorkspaceRequest
	{
		private var _Ticket:String;
		private var _WorkspaceId:String;
		private var _Articles:Array;
		private var _Action:String;
		private var _LayoutId:String;
		private var _EditionId:String;
		private var _PreviewType:String;
		private var _RequestInfo:Array;

		public function WflPreviewArticlesAtWorkspaceRequest() {
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

		public function get Articles():Array {
			return this._Articles;
		}
		public function set Articles(Articles:Array):void {
			this._Articles = Articles;
		}

		public function get Action():String {
			return this._Action;
		}
		public function set Action(Action:String):void {
			this._Action = Action;
		}

		public function get LayoutId():String {
			return this._LayoutId;
		}
		public function set LayoutId(LayoutId:String):void {
			this._LayoutId = LayoutId;
		}

		public function get EditionId():String {
			return this._EditionId;
		}
		public function set EditionId(EditionId:String):void {
			this._EditionId = EditionId;
		}

		public function get PreviewType():String {
			return this._PreviewType;
		}
		public function set PreviewType(PreviewType:String):void {
			this._PreviewType = PreviewType;
		}

		public function get RequestInfo():Array {
			return this._RequestInfo;
		}
		public function set RequestInfo(RequestInfo:Array):void {
			this._RequestInfo = RequestInfo;
		}

	}
}
