/*
	Enterprise Planning Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pln.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPlacement")]

	public class PlnPlacement
	{
		private var _Left:Number;
		private var _Top:Number;
		private var _Columns:Number;
		private var _Width:Number;
		private var _Height:Number;
		private var _Fixed:String;
		private var _Layer:String;
		private var _ContentDx:Number;
		private var _ContentDy:Number;
		private var _ScaleX:Number;
		private var _ScaleY:Number;

		public function PlnPlacement() {
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

		public function get Columns():Number {
			return this._Columns;
		}
		public function set Columns(Columns:Number):void {
			this._Columns = Columns;
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


		// _Fixed should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Fixed():String {
			return this._Fixed;
		}

		// _Fixed should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Fixed(Fixed:String):void {
			this._Fixed = Fixed;
		}

		public function get Layer():String {
			return this._Layer;
		}
		public function set Layer(Layer:String):void {
			this._Layer = Layer;
		}

		public function get ContentDx():Number {
			return this._ContentDx;
		}
		public function set ContentDx(ContentDx:Number):void {
			this._ContentDx = ContentDx;
		}

		public function get ContentDy():Number {
			return this._ContentDy;
		}
		public function set ContentDy(ContentDy:Number):void {
			this._ContentDy = ContentDy;
		}

		public function get ScaleX():Number {
			return this._ScaleX;
		}
		public function set ScaleX(ScaleX:Number):void {
			this._ScaleX = ScaleX;
		}

		public function get ScaleY():Number {
			return this._ScaleY;
		}
		public function set ScaleY(ScaleY:Number):void {
			this._ScaleY = ScaleY;
		}

	}
}
