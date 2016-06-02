/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.AdsNewQueryRequest")]

	public class AdsNewQueryRequest
	{
		private var _Ticket:String;
		private var _DatasourceID:String;
		private var _Name:String;
		private var _Query:String;
		private var _Interface:String;
		private var _Comment:String;
		private var _RecordID:String;
		private var _RecordFamily:String;

		public function AdsNewQueryRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get DatasourceID():String {
			return this._DatasourceID;
		}
		public function set DatasourceID(DatasourceID:String):void {
			this._DatasourceID = DatasourceID;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Query():String {
			return this._Query;
		}
		public function set Query(Query:String):void {
			this._Query = Query;
		}

		public function get Interface():String {
			return this._Interface;
		}
		public function set Interface(Interface:String):void {
			this._Interface = Interface;
		}

		public function get Comment():String {
			return this._Comment;
		}
		public function set Comment(Comment:String):void {
			this._Comment = Comment;
		}

		public function get RecordID():String {
			return this._RecordID;
		}
		public function set RecordID(RecordID:String):void {
			this._RecordID = RecordID;
		}

		public function get RecordFamily():String {
			return this._RecordFamily;
		}
		public function set RecordFamily(RecordFamily:String):void {
			this._RecordFamily = RecordFamily;
		}

	}
}
