/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflRendition")]

	public class WflRendition
	{
		private var _Rendition:String;
		private var _FileSize:String;
		private var _Format:String;
		private var _Attachment:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment;

		public function WflRendition() {
		}

		public function get Rendition():String {
			return this._Rendition;
		}
		public function set Rendition(Rendition:String):void {
			this._Rendition = Rendition;
		}

		public function get FileSize():String {
			return this._FileSize;
		}
		public function set FileSize(FileSize:String):void {
			this._FileSize = FileSize;
		}

		public function get Format():String {
			return this._Format;
		}
		public function set Format(Format:String):void {
			this._Format = Format;
		}

		public function get Attachment():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment {
			return this._Attachment;
		}
		public function set Attachment(Attachment:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment):void {
			this._Attachment = Attachment;
		}

	}
}
