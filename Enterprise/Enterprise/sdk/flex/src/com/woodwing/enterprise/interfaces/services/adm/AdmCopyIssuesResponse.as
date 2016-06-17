/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmCopyIssuesResponse")]

	public class AdmCopyIssuesResponse
	{
		private var _Issues:Array;

		public function AdmCopyIssuesResponse() {
		}

		public function get Issues():Array {
			return this._Issues;
		}
		public function set Issues(Issues:Array):void {
			this._Issues = Issues;
		}

	}
}
