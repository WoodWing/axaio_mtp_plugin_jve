/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmTermEntity")]

	public class AdmTermEntity
	{
		private var _Id:Number;
		private var _Name:String;
		private var _AutocompleteProvider:String;
		private var _PublishSystemId:String;

		public function AdmTermEntity() {
		}

		public function get Id():Number {
			return this._Id;
		}
		public function set Id(Id:Number):void {
			this._Id = Id;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get AutocompleteProvider():String {
			return this._AutocompleteProvider;
		}
		public function set AutocompleteProvider(AutocompleteProvider:String):void {
			this._AutocompleteProvider = AutocompleteProvider;
		}

		public function get PublishSystemId():String {
			return this._PublishSystemId;
		}
		public function set PublishSystemId(PublishSystemId:String):void {
			this._PublishSystemId = PublishSystemId;
		}

	}
}
