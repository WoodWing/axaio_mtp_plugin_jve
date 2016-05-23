/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialogTab")]

	public class WflDialogTab
	{
		private var _Title:String;
		private var _Widgets:Array;
		private var _DefaultFocus:String;

		public function WflDialogTab() {
		}

		public function get Title():String {
			return this._Title;
		}
		public function set Title(Title:String):void {
			this._Title = Title;
		}

		public function get Widgets():Array {
			return this._Widgets;
		}
		public function set Widgets(Widgets:Array):void {
			this._Widgets = Widgets;
		}

		public function get DefaultFocus():String {
			return this._DefaultFocus;
		}
		public function set DefaultFocus(DefaultFocus:String):void {
			this._DefaultFocus = DefaultFocus;
		}

	}
}
