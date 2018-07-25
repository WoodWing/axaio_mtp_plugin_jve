/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment")]

	public class WflAttachment
	{
		private var _Rendition:String;
		private var _Type:String;
		private var _Content:String;
		private var _FilePath:String;
		private var _FileUrl:String;
		private var _EditionId:String;
		private var _ContentSourceFileLink:String;
		private var _ContentSourceProxyLink:String;

		public function WflAttachment() {
		}

		public function get Rendition():String {
			return this._Rendition;
		}
		public function set Rendition(Rendition:String):void {
			this._Rendition = Rendition;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get Content():String {
			return this._Content;
		}
		public function set Content(Content:String):void {
			this._Content = Content;
		}

		public function get FilePath():String {
			return this._FilePath;
		}
		public function set FilePath(FilePath:String):void {
			this._FilePath = FilePath;
		}

		public function get FileUrl():String {
			return this._FileUrl;
		}
		public function set FileUrl(FileUrl:String):void {
			this._FileUrl = FileUrl;
		}

		public function get EditionId():String {
			return this._EditionId;
		}
		public function set EditionId(EditionId:String):void {
			this._EditionId = EditionId;
		}

		public function get ContentSourceFileLink():String {
			return this._ContentSourceFileLink;
		}
		public function set ContentSourceFileLink(ContentSourceFileLink:String):void {
			this._ContentSourceFileLink = ContentSourceFileLink;
		}

		public function get ContentSourceProxyLink():String {
			return this._ContentSourceProxyLink;
		}
		public function set ContentSourceProxyLink(ContentSourceProxyLink:String):void {
			this._ContentSourceProxyLink = ContentSourceProxyLink;
		}

	}
}
