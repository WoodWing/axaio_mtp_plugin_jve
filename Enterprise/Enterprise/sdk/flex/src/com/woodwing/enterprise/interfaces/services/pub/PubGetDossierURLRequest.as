/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub
{
	import com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.PubGetDossierURLRequest")]

	public class PubGetDossierURLRequest
	{
		private var _Ticket:String;
		private var _DossierID:String;
		private var _Target:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget;

		public function PubGetDossierURLRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get DossierID():String {
			return this._DossierID;
		}
		public function set DossierID(DossierID:String):void {
			this._DossierID = DossierID;
		}

		public function get Target():com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget {
			return this._Target;
		}
		public function set Target(Target:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget):void {
			this._Target = Target;
		}

	}
}
