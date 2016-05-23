/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflFacet")]

	public class WflFacet
	{
		private var _Name:String;
		private var _DisplayName:String;
		private var _FacetItems:Array;

		public function WflFacet() {
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

		public function get FacetItems():Array {
			return this._FacetItems;
		}
		public function set FacetItems(FacetItems:Array):void {
			this._FacetItems = FacetItems;
		}

	}
}
