/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialog")]

	public class WflDialog
	{
		private var _Title:String;
		private var _Tabs:Array;
		private var _MetaData:Array;
		private var _ButtonBar:Array;

		public function WflDialog() {
		}

		public function get Title():String {
			return this._Title;
		}
		public function set Title(Title:String):void {
			this._Title = Title;
		}

		public function get Tabs():Array {
			return this._Tabs;
		}
		public function set Tabs(Tabs:Array):void {
			this._Tabs = Tabs;
		}

		public function get MetaData():Array {
			return this._MetaData;
		}
		public function set MetaData(MetaData:Array):void {
			this._MetaData = MetaData;
		}

		public function get ButtonBar():Array {
			return this._ButtonBar;
		}
		public function set ButtonBar(ButtonBar:Array):void {
			this._ButtonBar = ButtonBar;
		}

	}
}
