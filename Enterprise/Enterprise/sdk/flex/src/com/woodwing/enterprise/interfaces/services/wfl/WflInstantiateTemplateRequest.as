/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflInstantiateTemplateRequest")]

	public class WflInstantiateTemplateRequest
	{
		private var _Ticket:String;
		private var _Lock:String;
		private var _Rendition:String;
		private var _RequestInfo:Array;
		private var _TemplateId:String;
		private var _Objects:Array;

		public function WflInstantiateTemplateRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}


		// _Lock should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Lock():String {
			return this._Lock;
		}

		// _Lock should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Lock(Lock:String):void {
			this._Lock = Lock;
		}

		public function get Rendition():String {
			return this._Rendition;
		}
		public function set Rendition(Rendition:String):void {
			this._Rendition = Rendition;
		}

		public function get RequestInfo():Array {
			return this._RequestInfo;
		}
		public function set RequestInfo(RequestInfo:Array):void {
			this._RequestInfo = RequestInfo;
		}

		public function get TemplateId():String {
			return this._TemplateId;
		}
		public function set TemplateId(TemplateId:String):void {
			this._TemplateId = TemplateId;
		}

		public function get Objects():Array {
			return this._Objects;
		}
		public function set Objects(Objects:Array):void {
			this._Objects = Objects;
		}

	}
}
