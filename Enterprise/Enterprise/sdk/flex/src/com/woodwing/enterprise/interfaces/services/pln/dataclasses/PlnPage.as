/*
	Enterprise Planning Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pln.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPage")]

	public class PlnPage
	{
		private var _PageOrder:Number;
		private var _Width:Number;
		private var _Height:Number;
		private var _Files:Array;
		private var _Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;
		private var _Master:String;
		private var _PageSequence:Number;
		private var _PageNumber:String;

		public function PlnPage() {
		}

		public function get PageOrder():Number {
			return this._PageOrder;
		}
		public function set PageOrder(PageOrder:Number):void {
			this._PageOrder = PageOrder;
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

		public function get Files():Array {
			return this._Files;
		}
		public function set Files(Files:Array):void {
			this._Files = Files;
		}

		public function get Edition():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition {
			return this._Edition;
		}
		public function set Edition(Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition):void {
			this._Edition = Edition;
		}

		public function get Master():String {
			return this._Master;
		}
		public function set Master(Master:String):void {
			this._Master = Master;
		}

		public function get PageSequence():Number {
			return this._PageSequence;
		}
		public function set PageSequence(PageSequence:Number):void {
			this._PageSequence = PageSequence;
		}

		public function get PageNumber():String {
			return this._PageNumber;
		}
		public function set PageNumber(PageNumber:String):void {
			this._PageNumber = PageNumber;
		}

	}
}
