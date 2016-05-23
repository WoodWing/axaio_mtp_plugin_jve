/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsCopyQueryRequest")]

	public class AdsCopyQueryRequest
	{
		private var _Ticket:String;
		private var _QueryID:String;
		private var _TargetID:String;
		private var _NewName:String;
		private var _CopyFields:String;

		public function AdsCopyQueryRequest() {
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

		public function get TargetID():String {
			return this._TargetID;
		}
		public function set TargetID(TargetID:String):void {
			this._TargetID = TargetID;
		}

		public function get NewName():String {
			return this._NewName;
		}
		public function set NewName(NewName:String):void {
			this._NewName = NewName;
		}

		public function get CopyFields():String {
			return this._CopyFields;
		}
		public function set CopyFields(CopyFields:String):void {
			this._CopyFields = CopyFields;
		}

	}
}
