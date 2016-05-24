/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPage")]

	public class WflPage
	{
		private var _Width:Number;
		private var _Height:Number;
		private var _PageNumber:String;
		private var _PageOrder:Number;
		private var _Files:Array;
		private var _Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;
		private var _Master:String;
		private var _Instance:String;
		private var _PageSequence:Number;
		private var _Renditions:Array;
		private var _Orientation:String;

		public function WflPage() {
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

		public function get PageNumber():String {
			return this._PageNumber;
		}
		public function set PageNumber(PageNumber:String):void {
			this._PageNumber = PageNumber;
		}

		public function get PageOrder():Number {
			return this._PageOrder;
		}
		public function set PageOrder(PageOrder:Number):void {
			this._PageOrder = PageOrder;
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

		public function get Instance():String {
			return this._Instance;
		}
		public function set Instance(Instance:String):void {
			this._Instance = Instance;
		}

		public function get PageSequence():Number {
			return this._PageSequence;
		}
		public function set PageSequence(PageSequence:Number):void {
			this._PageSequence = PageSequence;
		}

		public function get Renditions():Array {
			return this._Renditions;
		}
		public function set Renditions(Renditions:Array):void {
			this._Renditions = Renditions;
		}

		public function get Orientation():String {
			return this._Orientation;
		}
		public function set Orientation(Orientation:String):void {
			this._Orientation = Orientation;
		}

	}
}
