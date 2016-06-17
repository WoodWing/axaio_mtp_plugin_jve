/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetPagesRequest")]

	public class WflGetPagesRequest
	{
		private var _Ticket:String;
		private var _Params:Array;
		private var _IDs:Array;
		private var _PageOrders:Array;
		private var _PageSequences:Array;
		private var _Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;
		private var _Renditions:Array;
		private var _RequestMetaData:String;
		private var _RequestFiles:String;

		public function WflGetPagesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Params():Array {
			return this._Params;
		}
		public function set Params(Params:Array):void {
			this._Params = Params;
		}

		public function get IDs():Array {
			return this._IDs;
		}
		public function set IDs(IDs:Array):void {
			this._IDs = IDs;
		}

		public function get PageOrders():Array {
			return this._PageOrders;
		}
		public function set PageOrders(PageOrders:Array):void {
			this._PageOrders = PageOrders;
		}

		public function get PageSequences():Array {
			return this._PageSequences;
		}
		public function set PageSequences(PageSequences:Array):void {
			this._PageSequences = PageSequences;
		}

		public function get Edition():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition {
			return this._Edition;
		}
		public function set Edition(Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition):void {
			this._Edition = Edition;
		}

		public function get Renditions():Array {
			return this._Renditions;
		}
		public function set Renditions(Renditions:Array):void {
			this._Renditions = Renditions;
		}


		// _RequestMetaData should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RequestMetaData():String {
			return this._RequestMetaData;
		}

		// _RequestMetaData should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RequestMetaData(RequestMetaData:String):void {
			this._RequestMetaData = RequestMetaData;
		}


		// _RequestFiles should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RequestFiles():String {
			return this._RequestFiles;
		}

		// _RequestFiles should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RequestFiles(RequestFiles:String):void {
			this._RequestFiles = RequestFiles;
		}

	}
}
