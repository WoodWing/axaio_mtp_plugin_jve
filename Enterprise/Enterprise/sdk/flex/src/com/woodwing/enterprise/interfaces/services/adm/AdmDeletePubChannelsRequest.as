/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmDeletePubChannelsRequest")]

	public class AdmDeletePubChannelsRequest
	{
		private var _Ticket:String;
		private var _PublicationId:Number;
		private var _PubChannelIds:Array;

		public function AdmDeletePubChannelsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get PublicationId():Number {
			return this._PublicationId;
		}
		public function set PublicationId(PublicationId:Number):void {
			this._PublicationId = PublicationId;
		}

		public function get PubChannelIds():Array {
			return this._PubChannelIds;
		}
		public function set PubChannelIds(PubChannelIds:Array):void {
			this._PubChannelIds = PubChannelIds;
		}

	}
}
