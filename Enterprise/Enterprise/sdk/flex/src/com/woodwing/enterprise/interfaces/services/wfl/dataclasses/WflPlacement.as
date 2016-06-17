/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPlacement")]

	public class WflPlacement
	{
		private var _Page:Number;
		private var _Element:String;
		private var _ElementID:String;
		private var _FrameOrder:Number;
		private var _FrameID:String;
		private var _Left:Number;
		private var _Top:Number;
		private var _Width:Number;
		private var _Height:Number;
		private var _Overset:Number;
		private var _OversetChars:Number;
		private var _OversetLines:Number;
		private var _Layer:String;
		private var _Content:String;
		private var _Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;
		private var _ContentDx:Number;
		private var _ContentDy:Number;
		private var _ScaleX:Number;
		private var _ScaleY:Number;
		private var _PageSequence:Number;
		private var _PageNumber:String;
		private var _Tiles:Array;
		private var _FormWidgetId:String;
		private var _InDesignArticleIds:Array;
		private var _FrameType:String;
		private var _SplineID:String;

		public function WflPlacement() {
		}

		public function get Page():Number {
			return this._Page;
		}
		public function set Page(Page:Number):void {
			this._Page = Page;
		}

		public function get Element():String {
			return this._Element;
		}
		public function set Element(Element:String):void {
			this._Element = Element;
		}

		public function get ElementID():String {
			return this._ElementID;
		}
		public function set ElementID(ElementID:String):void {
			this._ElementID = ElementID;
		}

		public function get FrameOrder():Number {
			return this._FrameOrder;
		}
		public function set FrameOrder(FrameOrder:Number):void {
			this._FrameOrder = FrameOrder;
		}

		public function get FrameID():String {
			return this._FrameID;
		}
		public function set FrameID(FrameID:String):void {
			this._FrameID = FrameID;
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

		public function get Overset():Number {
			return this._Overset;
		}
		public function set Overset(Overset:Number):void {
			this._Overset = Overset;
		}

		public function get OversetChars():Number {
			return this._OversetChars;
		}
		public function set OversetChars(OversetChars:Number):void {
			this._OversetChars = OversetChars;
		}

		public function get OversetLines():Number {
			return this._OversetLines;
		}
		public function set OversetLines(OversetLines:Number):void {
			this._OversetLines = OversetLines;
		}

		public function get Layer():String {
			return this._Layer;
		}
		public function set Layer(Layer:String):void {
			this._Layer = Layer;
		}

		public function get Content():String {
			return this._Content;
		}
		public function set Content(Content:String):void {
			this._Content = Content;
		}

		public function get Edition():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition {
			return this._Edition;
		}
		public function set Edition(Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition):void {
			this._Edition = Edition;
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

		public function get Tiles():Array {
			return this._Tiles;
		}
		public function set Tiles(Tiles:Array):void {
			this._Tiles = Tiles;
		}

		public function get FormWidgetId():String {
			return this._FormWidgetId;
		}
		public function set FormWidgetId(FormWidgetId:String):void {
			this._FormWidgetId = FormWidgetId;
		}

		public function get InDesignArticleIds():Array {
			return this._InDesignArticleIds;
		}
		public function set InDesignArticleIds(InDesignArticleIds:Array):void {
			this._InDesignArticleIds = InDesignArticleIds;
		}

		public function get FrameType():String {
			return this._FrameType;
		}
		public function set FrameType(FrameType:String):void {
			this._FrameType = FrameType;
		}

		public function get SplineID():String {
			return this._SplineID;
		}
		public function set SplineID(SplineID:String):void {
			this._SplineID = SplineID;
		}

	}
}
