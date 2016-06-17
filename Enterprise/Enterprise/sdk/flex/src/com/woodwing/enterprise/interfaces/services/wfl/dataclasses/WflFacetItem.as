/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflFacetItem")]

	public class WflFacetItem
	{
		private var _Name:String;
		private var _DisplayName:String;
		private var _Numbers:Number;

		public function WflFacetItem() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get DisplayName():String {
			return this._DisplayName;
		}
		public function set DisplayName(DisplayName:String):void {
			this._DisplayName = DisplayName;
		}

		public function get Numbers():Number {
			return this._Numbers;
		}
		public function set Numbers(Numbers:Number):void {
			this._Numbers = Numbers;
		}

	}
}
