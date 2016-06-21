/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflFeatureProfile")]

	public class WflFeatureProfile
	{
		private var _Name:String;
		private var _Features:Array;

		public function WflFeatureProfile() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Features():Array {
			return this._Features;
		}
		public function set Features(Features:Array):void {
			this._Features = Features;
		}

	}
}
