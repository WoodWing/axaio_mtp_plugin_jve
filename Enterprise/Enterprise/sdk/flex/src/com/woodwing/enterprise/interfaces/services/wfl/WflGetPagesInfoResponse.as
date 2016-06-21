/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetPagesInfoResponse")]

	public class WflGetPagesInfoResponse
	{
		private var _ReversedReadingOrder:String;
		private var _ExpectedPages:Number;
		private var _PageOrderMethod:String;
		private var _EditionsPages:Array;
		private var _LayoutObjects:Array;
		private var _PlacedObjects:Array;

		public function WflGetPagesInfoResponse() {
		}


		// _ReversedReadingOrder should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get ReversedReadingOrder():String {
			return this._ReversedReadingOrder;
		}

		// _ReversedReadingOrder should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set ReversedReadingOrder(ReversedReadingOrder:String):void {
			this._ReversedReadingOrder = ReversedReadingOrder;
		}

		public function get ExpectedPages():Number {
			return this._ExpectedPages;
		}
		public function set ExpectedPages(ExpectedPages:Number):void {
			this._ExpectedPages = ExpectedPages;
		}

		public function get PageOrderMethod():String {
			return this._PageOrderMethod;
		}
		public function set PageOrderMethod(PageOrderMethod:String):void {
			this._PageOrderMethod = PageOrderMethod;
		}

		public function get EditionsPages():Array {
			return this._EditionsPages;
		}
		public function set EditionsPages(EditionsPages:Array):void {
			this._EditionsPages = EditionsPages;
		}

		public function get LayoutObjects():Array {
			return this._LayoutObjects;
		}
		public function set LayoutObjects(LayoutObjects:Array):void {
			this._LayoutObjects = LayoutObjects;
		}

		public function get PlacedObjects():Array {
			return this._PlacedObjects;
		}
		public function set PlacedObjects(PlacedObjects:Array):void {
			this._PlacedObjects = PlacedObjects;
		}

	}
}
