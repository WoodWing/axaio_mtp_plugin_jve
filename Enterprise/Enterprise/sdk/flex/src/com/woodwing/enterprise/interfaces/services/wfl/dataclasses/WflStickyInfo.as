/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflStickyInfo")]

	public class WflStickyInfo
	{
		private var _AnchorX:Number;
		private var _AnchorY:Number;
		private var _Left:Number;
		private var _Top:Number;
		private var _Width:Number;
		private var _Height:Number;
		private var _Page:Number;
		private var _Version:String;
		private var _Color:String;
		private var _PageSequence:Number;

		public function WflStickyInfo() {
		}

		public function get AnchorX():Number {
			return this._AnchorX;
		}
		public function set AnchorX(AnchorX:Number):void {
			this._AnchorX = AnchorX;
		}

		public function get AnchorY():Number {
			return this._AnchorY;
		}
		public function set AnchorY(AnchorY:Number):void {
			this._AnchorY = AnchorY;
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

		public function get Page():Number {
			return this._Page;
		}
		public function set Page(Page:Number):void {
			this._Page = Page;
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

		public function get Color():String {
			return this._Color;
		}
		public function set Color(Color:String):void {
			this._Color = Color;
		}

		public function get PageSequence():Number {
			return this._PageSequence;
		}
		public function set PageSequence(PageSequence:Number):void {
			this._PageSequence = PageSequence;
		}

	}
}
