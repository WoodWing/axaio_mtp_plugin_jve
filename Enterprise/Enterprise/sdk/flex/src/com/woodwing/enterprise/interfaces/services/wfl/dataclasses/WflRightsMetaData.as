/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflRightsMetaData")]

	public class WflRightsMetaData
	{
		private var _CopyrightMarked:String;
		private var _Copyright:String;
		private var _CopyrightURL:String;

		public function WflRightsMetaData() {
		}


		// _CopyrightMarked should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get CopyrightMarked():String {
			return this._CopyrightMarked;
		}

		// _CopyrightMarked should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set CopyrightMarked(CopyrightMarked:String):void {
			this._CopyrightMarked = CopyrightMarked;
		}

		public function get Copyright():String {
			return this._Copyright;
		}
		public function set Copyright(Copyright:String):void {
			this._Copyright = Copyright;
		}

		public function get CopyrightURL():String {
			return this._CopyrightURL;
		}
		public function set CopyrightURL(CopyrightURL:String):void {
			this._CopyrightURL = CopyrightURL;
		}

	}
}
