/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPageInfo")]

	public class PubPageInfo
	{
		private var _PageNumber:String;
		private var _PageSequence:String;
		private var _PageOrder:String;

		public function PubPageInfo() {
		}

		public function get PageNumber():String {
			return this._PageNumber;
		}
		public function set PageNumber(PageNumber:String):void {
			this._PageNumber = PageNumber;
		}

		public function get PageSequence():String {
			return this._PageSequence;
		}
		public function set PageSequence(PageSequence:String):void {
			this._PageSequence = PageSequence;
		}

		public function get PageOrder():String {
			return this._PageOrder;
		}
		public function set PageOrder(PageOrder:String):void {
			this._PageOrder = PageOrder;
		}

	}
}
