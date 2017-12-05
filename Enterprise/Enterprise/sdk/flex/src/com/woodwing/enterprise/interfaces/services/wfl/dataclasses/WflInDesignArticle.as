/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflInDesignArticle")]

	public class WflInDesignArticle
	{
		private var _Id:String;
		private var _Name:String;
		private var _SplineIDs:Array;

		public function WflInDesignArticle() {
		}

		public function get Id():String {
			return this._Id;
		}
		public function set Id(Id:String):void {
			this._Id = Id;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get SplineIDs():Array {
			return this._SplineIDs;
		}
		public function set SplineIDs(SplineIDs:Array):void {
			this._SplineIDs = SplineIDs;
		}

	}
}
