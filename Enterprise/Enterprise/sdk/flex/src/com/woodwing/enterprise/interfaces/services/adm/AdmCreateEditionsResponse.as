/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmCreateEditionsResponse")]

	public class AdmCreateEditionsResponse
	{
		private var _PublicationId:Number;
		private var _PubChannelId:Number;
		private var _IssueId:Number;
		private var _Editions:Array;

		public function AdmCreateEditionsResponse() {
		}

		public function get PublicationId():Number {
			return this._PublicationId;
		}
		public function set PublicationId(PublicationId:Number):void {
			this._PublicationId = PublicationId;
		}

		public function get PubChannelId():Number {
			return this._PubChannelId;
		}
		public function set PubChannelId(PubChannelId:Number):void {
			this._PubChannelId = PubChannelId;
		}

		public function get IssueId():Number {
			return this._IssueId;
		}
		public function set IssueId(IssueId:Number):void {
			this._IssueId = IssueId;
		}

		public function get Editions():Array {
			return this._Editions;
		}
		public function set Editions(Editions:Array):void {
			this._Editions = Editions;
		}

	}
}
