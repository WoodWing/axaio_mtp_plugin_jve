/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmModifyIssuesResponse")]

	public class AdmModifyIssuesResponse
	{
		private var _PublicationId:Number;
		private var _PubChannelId:Number;
		private var _Issues:Array;

		public function AdmModifyIssuesResponse() {
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

		public function get Issues():Array {
			return this._Issues;
		}
		public function set Issues(Issues:Array):void {
			this._Issues = Issues;
		}

	}
}
