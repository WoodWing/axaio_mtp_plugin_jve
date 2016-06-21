/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPageObject")]

	public class WflPageObject
	{
		private var _IssuePagePosition:Number;
		private var _PageOrder:Number;
		private var _PageNumber:String;
		private var _PageSequence:Number;
		private var _Height:Number;
		private var _Width:Number;
		private var _ParentLayoutId:String;
		private var _OutputRenditionAvailable:String;
		private var _PlacementInfos:Array;

		public function WflPageObject() {
		}

		public function get IssuePagePosition():Number {
			return this._IssuePagePosition;
		}
		public function set IssuePagePosition(IssuePagePosition:Number):void {
			this._IssuePagePosition = IssuePagePosition;
		}

		public function get PageOrder():Number {
			return this._PageOrder;
		}
		public function set PageOrder(PageOrder:Number):void {
			this._PageOrder = PageOrder;
		}

		public function get PageNumber():String {
			return this._PageNumber;
		}
		public function set PageNumber(PageNumber:String):void {
			this._PageNumber = PageNumber;
		}

		public function get PageSequence():Number {
			return this._PageSequence;
		}
		public function set PageSequence(PageSequence:Number):void {
			this._PageSequence = PageSequence;
		}

		public function get Height():Number {
			return this._Height;
		}
		public function set Height(Height:Number):void {
			this._Height = Height;
		}

		public function get Width():Number {
			return this._Width;
		}
		public function set Width(Width:Number):void {
			this._Width = Width;
		}

		public function get ParentLayoutId():String {
			return this._ParentLayoutId;
		}
		public function set ParentLayoutId(ParentLayoutId:String):void {
			this._ParentLayoutId = ParentLayoutId;
		}


		// _OutputRenditionAvailable should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get OutputRenditionAvailable():String {
			return this._OutputRenditionAvailable;
		}

		// _OutputRenditionAvailable should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set OutputRenditionAvailable(OutputRenditionAvailable:String):void {
			this._OutputRenditionAvailable = OutputRenditionAvailable;
		}

		public function get PlacementInfos():Array {
			return this._PlacementInfos;
		}
		public function set PlacementInfos(PlacementInfos:Array):void {
			this._PlacementInfos = PlacementInfos;
		}

	}
}
