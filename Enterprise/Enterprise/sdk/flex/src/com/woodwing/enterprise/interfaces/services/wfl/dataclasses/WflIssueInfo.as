/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssueInfo")]

	public class WflIssueInfo
	{
		private var _Id:String;
		private var _Name:String;
		private var _OverrulePublication:String;
		private var _Sections:Array;
		private var _States:Array;
		private var _Editions:Array;
		private var _Description:String;
		private var _Subject:String;
		private var _PublicationDate:String;
		private var _ReversedRead:String;

		public function WflIssueInfo() {
		}

		public function get Id():String {
			return this._Id;
		}
		public function set Id(Id:String):void {
			this._Id = Id;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}


		// _OverrulePublication should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get OverrulePublication():String {
			return this._OverrulePublication;
		}

		// _OverrulePublication should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set OverrulePublication(OverrulePublication:String):void {
			this._OverrulePublication = OverrulePublication;
		}

		public function get Sections():Array {
			return this._Sections;
		}
		public function set Sections(Sections:Array):void {
			this._Sections = Sections;
		}

		public function get States():Array {
			return this._States;
		}
		public function set States(States:Array):void {
			this._States = States;
		}

		public function get Editions():Array {
			return this._Editions;
		}
		public function set Editions(Editions:Array):void {
			this._Editions = Editions;
		}

		public function get Description():String {
			return this._Description;
		}
		public function set Description(Description:String):void {
			this._Description = Description;
		}

		public function get Subject():String {
			return this._Subject;
		}
		public function set Subject(Subject:String):void {
			this._Subject = Subject;
		}

		public function get PublicationDate():String {
			return this._PublicationDate;
		}

		public function getPublicationDateAsDate():Date {
			return WoodWingUtils.stringToDate(this._PublicationDate);
		}

		public function set PublicationDate(PublicationDate:String):void {
			this._PublicationDate = PublicationDate;
		}


		public function setPublicationDateAsDate(PublicationDate:Date):void {
			this._PublicationDate = WoodWingUtils.dateToString(PublicationDate);
		}


		// _ReversedRead should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get ReversedRead():String {
			return this._ReversedRead;
		}

		// _ReversedRead should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set ReversedRead(ReversedRead:String):void {
			this._ReversedRead = ReversedRead;
		}

	}
}
