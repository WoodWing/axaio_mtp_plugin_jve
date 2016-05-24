/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsSaveQueryFieldRequest")]

	public class AdsSaveQueryFieldRequest
	{
		private var _Ticket:String;
		private var _QueryID:String;
		private var _Name:String;
		private var _Priority:String;
		private var _ReadOnly:String;

		public function AdsSaveQueryFieldRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get QueryID():String {
			return this._QueryID;
		}
		public function set QueryID(QueryID:String):void {
			this._QueryID = QueryID;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Priority():String {
			return this._Priority;
		}
		public function set Priority(Priority:String):void {
			this._Priority = Priority;
		}

		public function get ReadOnly():String {
			return this._ReadOnly;
		}
		public function set ReadOnly(ReadOnly:String):void {
			this._ReadOnly = ReadOnly;
		}

	}
}
