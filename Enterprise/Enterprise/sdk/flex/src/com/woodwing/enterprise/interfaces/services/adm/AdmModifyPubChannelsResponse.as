/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmModifyPubChannelsResponse")]

	public class AdmModifyPubChannelsResponse
	{
		private var _PublicationId:Number;
		private var _PubChannels:Array;

		public function AdmModifyPubChannelsResponse() {
		}

		public function get PublicationId():Number {
			return this._PublicationId;
		}
		public function set PublicationId(PublicationId:Number):void {
			this._PublicationId = PublicationId;
		}

		public function get PubChannels():Array {
			return this._PubChannels;
		}
		public function set PubChannels(PubChannels:Array):void {
			this._PubChannels = PubChannels;
		}

	}
}
