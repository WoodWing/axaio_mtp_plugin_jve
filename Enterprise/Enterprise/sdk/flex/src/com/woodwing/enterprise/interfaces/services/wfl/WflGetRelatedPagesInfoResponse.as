/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetRelatedPagesInfoResponse")]

	public class WflGetRelatedPagesInfoResponse
	{
		private var _EditionsPages:Array;
		private var _LayoutObjects:Array;

		public function WflGetRelatedPagesInfoResponse() {
		}

		public function get EditionsPages():Array {
			return this._EditionsPages;
		}
		public function set EditionsPages(EditionsPages:Array):void {
			this._EditionsPages = EditionsPages;
		}

		public function get LayoutObjects():Array {
			return this._LayoutObjects;
		}
		public function set LayoutObjects(LayoutObjects:Array):void {
			this._LayoutObjects = LayoutObjects;
		}

	}
}
