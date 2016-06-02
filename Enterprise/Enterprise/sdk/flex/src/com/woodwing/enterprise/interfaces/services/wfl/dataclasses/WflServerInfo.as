/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflServerInfo")]

	public class WflServerInfo
	{
		private var _Name:String;
		private var _URL:String;
		private var _Developer:String;
		private var _Implementation:String;
		private var _Technology:String;
		private var _Version:String;
		private var _FeatureSet:Array;
		private var _CryptKey:String;
		private var _EnterpriseSystemId:String;

		public function WflServerInfo() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get URL():String {
			return this._URL;
		}
		public function set URL(URL:String):void {
			this._URL = URL;
		}

		public function get Developer():String {
			return this._Developer;
		}
		public function set Developer(Developer:String):void {
			this._Developer = Developer;
		}

		public function get Implementation():String {
			return this._Implementation;
		}
		public function set Implementation(Implementation:String):void {
			this._Implementation = Implementation;
		}

		public function get Technology():String {
			return this._Technology;
		}
		public function set Technology(Technology:String):void {
			this._Technology = Technology;
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

		public function get FeatureSet():Array {
			return this._FeatureSet;
		}
		public function set FeatureSet(FeatureSet:Array):void {
			this._FeatureSet = FeatureSet;
		}

		public function get CryptKey():String {
			return this._CryptKey;
		}
		public function set CryptKey(CryptKey:String):void {
			this._CryptKey = CryptKey;
		}

		public function get EnterpriseSystemId():String {
			return this._EnterpriseSystemId;
		}
		public function set EnterpriseSystemId(EnterpriseSystemId:String):void {
			this._EnterpriseSystemId = EnterpriseSystemId;
		}

	}
}
