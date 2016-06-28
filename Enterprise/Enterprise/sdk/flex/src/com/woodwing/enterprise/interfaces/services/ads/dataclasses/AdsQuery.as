/*
	Enterprise AdmDatSrc Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.ads.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.ads.dataclasses.AdsQuery")]

	public class AdsQuery
	{
		private var _ID:String;
		private var _Name:String;
		private var _Query:String;
		private var _Interface:String;
		private var _Comment:String;
		private var _RecordID:String;
		private var _RecordFamily:String;

		public function AdsQuery() {
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
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
