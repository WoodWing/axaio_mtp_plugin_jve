/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflPreviewArticleAtWorkspaceRequest")]

	public class WflPreviewArticleAtWorkspaceRequest
	{
		private var _Ticket:String;
		private var _WorkspaceId:String;
		private var _ID:String;
		private var _Format:String;
		private var _Content:String;
		private var _Elements:Array;
		private var _Action:String;
		private var _LayoutId:String;
		private var _EditionId:String;
		private var _PreviewType:String;
		private var _RequestInfo:Array;

		public function WflPreviewArticleAtWorkspaceRequest() {
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

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get Format():String {
			return this._Format;
		}
		public function set Format(Format:String):void {
			this._Format = Format;
		}

		public function get Content():String {
			return this._Content;
		}
		public function set Content(Content:String):void {
			this._Content = Content;
		}

		public function get Elements():Array {
			return this._Elements;
		}
		public function set Elements(Elements:Array):void {
			this._Elements = Elements;
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
