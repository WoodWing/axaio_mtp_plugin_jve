/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.PubOperationProgressResponse")]

	public class PubOperationProgressResponse
	{
		private var _Phases:Array;

		public function PubOperationProgressResponse() {
		}

		public function get Phases():Array {
			return this._Phases;
		}
		public function set Phases(Phases:Array):void {
			this._Phases = Phases;
		}

	}
}
