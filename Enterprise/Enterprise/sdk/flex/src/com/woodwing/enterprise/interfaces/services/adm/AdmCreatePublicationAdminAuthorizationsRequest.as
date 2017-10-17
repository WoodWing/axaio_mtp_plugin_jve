/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmCreatePublicationAdminAuthorizationsRequest")]

	public class AdmCreatePublicationAdminAuthorizationsRequest
	{
		private var _Ticket:String;
		private var _PublicationId:Number;
		private var _UserGroupIds:Array;

		public function AdmCreatePublicationAdminAuthorizationsRequest() {
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

		public function get UserGroupIds():Array {
			return this._UserGroupIds;
		}
		public function set UserGroupIds(UserGroupIds:Array):void {
			this._UserGroupIds = UserGroupIds;
		}

	}
}
