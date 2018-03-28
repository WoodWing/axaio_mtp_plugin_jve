/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetRelatedPagesRequest")]

	public class WflGetRelatedPagesRequest
	{
		private var _Ticket:String;
		private var _LayoutId:String;
		private var _PageSequences:Array;
		private var _Rendition:String;

		public function WflGetRelatedPagesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get LayoutId():String {
			return this._LayoutId;
		}
		public function set LayoutId(LayoutId:String):void {
			this._LayoutId = LayoutId;
		}

		public function get PageSequences():Array {
			return this._PageSequences;
		}
		public function set PageSequences(PageSequences:Array):void {
			this._PageSequences = PageSequences;
		}

		public function get Rendition():String {
			return this._Rendition;
		}
		public function set Rendition(Rendition:String):void {
			this._Rendition = Rendition;
		}

	}
}
