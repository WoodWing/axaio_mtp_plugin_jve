/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflSourceMetaData")]

	public class WflSourceMetaData
	{
		private var _Credit:String;
		private var _Source:String;
		private var _Author:String;

		public function WflSourceMetaData() {
		}

		public function get Credit():String {
			return this._Credit;
		}
		public function set Credit(Credit:String):void {
			this._Credit = Credit;
		}

		public function get Source():String {
			return this._Source;
		}
		public function set Source(Source:String):void {
			this._Source = Source;
		}

		public function get Author():String {
			return this._Author;
		}
		public function set Author(Author:String):void {
			this._Author = Author;
		}

	}
}
