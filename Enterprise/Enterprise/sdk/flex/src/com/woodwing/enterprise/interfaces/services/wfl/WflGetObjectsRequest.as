/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetObjectsRequest")]

	public class WflGetObjectsRequest
	{
		private var _Ticket:String;
		private var _IDs:Array;
		private var _Lock:String;
		private var _Rendition:String;
		private var _RequestInfo:Array;
		private var _HaveVersions:Array;
		private var _Areas:Array;
		private var _EditionId:String;
		private var _SupportedContentSources:Array;

		public function WflGetObjectsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get IDs():Array {
			return this._IDs;
		}
		public function set IDs(IDs:Array):void {
			this._IDs = IDs;
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

		public function get HaveVersions():Array {
			return this._HaveVersions;
		}
		public function set HaveVersions(HaveVersions:Array):void {
			this._HaveVersions = HaveVersions;
		}

		public function get Areas():Array {
			return this._Areas;
		}
		public function set Areas(Areas:Array):void {
			this._Areas = Areas;
		}

		public function get EditionId():String {
			return this._EditionId;
		}
		public function set EditionId(EditionId:String):void {
			this._EditionId = EditionId;
		}

		public function get SupportedContentSources():Array {
			return this._SupportedContentSources;
		}
		public function set SupportedContentSources(SupportedContentSources:Array):void {
			this._SupportedContentSources = SupportedContentSources;
		}

	}
}
