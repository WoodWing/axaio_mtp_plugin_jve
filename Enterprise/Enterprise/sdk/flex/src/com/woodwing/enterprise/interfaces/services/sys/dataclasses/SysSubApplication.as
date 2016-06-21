/*
	Enterprise SysAdmin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.sys.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.sys.dataclasses.SysSubApplication")]

	public class SysSubApplication
	{
		private var _ID:String;
		private var _Version:String;
		private var _PackageUrl:String;
		private var _DisplayName:String;
		private var _ClientAppName:String;

		public function SysSubApplication() {
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

		public function get PackageUrl():String {
			return this._PackageUrl;
		}
		public function set PackageUrl(PackageUrl:String):void {
			this._PackageUrl = PackageUrl;
		}

		public function get DisplayName():String {
			return this._DisplayName;
		}
		public function set DisplayName(DisplayName:String):void {
			this._DisplayName = DisplayName;
		}

		public function get ClientAppName():String {
			return this._ClientAppName;
		}
		public function set ClientAppName(ClientAppName:String):void {
			this._ClientAppName = ClientAppName;
		}

	}
}
