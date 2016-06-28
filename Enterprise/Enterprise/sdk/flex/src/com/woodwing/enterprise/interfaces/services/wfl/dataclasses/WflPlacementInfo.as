/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPlacementInfo")]

	public class WflPlacementInfo
	{
		private var _Id:String;
		private var _Left:Number;
		private var _Top:Number;
		private var _Width:Number;
		private var _Height:Number;

		public function WflPlacementInfo() {
		}

		public function get Id():String {
			return this._Id;
		}
		public function set Id(Id:String):void {
			this._Id = Id;
		}

		public function get Left():Number {
			return this._Left;
		}
		public function set Left(Left:Number):void {
			this._Left = Left;
		}

		public function get Top():Number {
			return this._Top;
		}
		public function set Top(Top:Number):void {
			this._Top = Top;
		}

		public function get Width():Number {
			return this._Width;
		}
		public function set Width(Width:Number):void {
			this._Width = Width;
		}

		public function get Height():Number {
			return this._Height;
		}
		public function set Height(Height:Number):void {
			this._Height = Height;
		}

	}
}
