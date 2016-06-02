/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPageInfo;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubMessageContext")]

	public class PubMessageContext
	{
		private var _Objects:Array;
		private var _Page:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPageInfo;

		public function PubMessageContext() {
		}

		public function get Objects():Array {
			return this._Objects;
		}
		public function set Objects(Objects:Array):void {
			this._Objects = Objects;
		}

		public function get Page():com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPageInfo {
			return this._Page;
		}
		public function set Page(Page:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPageInfo):void {
			this._Page = Page;
		}

	}
}
