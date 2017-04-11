/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmAccessProfile")]

	public class AdmAccessProfile
	{
		private var _Id:Number;
		private var _Name:String;
		private var _SortOrder:Number;
		private var _Description:String;
		private var _ProfileFeatures:Array;

		public function AdmAccessProfile() {
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

		public function get SortOrder():Number {
			return this._SortOrder;
		}
		public function set SortOrder(SortOrder:Number):void {
			this._SortOrder = SortOrder;
		}

		public function get Description():String {
			return this._Description;
		}
		public function set Description(Description:String):void {
			this._Description = Description;
		}

		public function get ProfileFeatures():Array {
			return this._ProfileFeatures;
		}
		public function set ProfileFeatures(ProfileFeatures:Array):void {
			this._ProfileFeatures = ProfileFeatures;
		}

	}
}
