/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmIssue")]

	public class AdmIssue
	{
		private var _Id:Number;
		private var _Name:String;
		private var _Description:String;
		private var _SortOrder:Number;
		private var _EmailNotify:String;
		private var _ReversedRead:String;
		private var _OverrulePublication:String;
		private var _Deadline:String;
		private var _ExpectedPages:Number;
		private var _Subject:String;
		private var _Activated:String;
		private var _PublicationDate:String;
		private var _ExtraMetaData:Array;
		private var _Editions:Array;
		private var _Sections:Array;
		private var _Statuses:Array;
		private var _UserGroups:Array;
		private var _Workflows:Array;
		private var _Routings:Array;
		private var _CalculateDeadlines:String;

		public function AdmIssue() {
		}

		public function get Id():Number {
			return this._Id;
		}
		public function set Id(Id:Number):void {
			this._Id = Id;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Description():String {
			return this._Description;
		}
		public function set Description(Description:String):void {
			this._Description = Description;
		}

		public function get SortOrder():Number {
			return this._SortOrder;
		}
		public function set SortOrder(SortOrder:Number):void {
			this._SortOrder = SortOrder;
		}


		// _EmailNotify should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get EmailNotify():String {
			return this._EmailNotify;
		}

		// _EmailNotify should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set EmailNotify(EmailNotify:String):void {
			this._EmailNotify = EmailNotify;
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

		public function get Deadline():String {
			return this._Deadline;
		}

		public function getDeadlineAsDate():Date {
			return WoodWingUtils.stringToDate(this._Deadline);
		}

		public function set Deadline(Deadline:String):void {
			this._Deadline = Deadline;
		}


		public function setDeadlineAsDate(Deadline:Date):void {
			this._Deadline = WoodWingUtils.dateToString(Deadline);
		}

		public function get ExpectedPages():Number {
			return this._ExpectedPages;
		}
		public function set ExpectedPages(ExpectedPages:Number):void {
			this._ExpectedPages = ExpectedPages;
		}

		public function get Subject():String {
			return this._Subject;
		}
		public function set Subject(Subject:String):void {
			this._Subject = Subject;
		}


		// _Activated should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Activated():String {
			return this._Activated;
		}

		// _Activated should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Activated(Activated:String):void {
			this._Activated = Activated;
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

		public function get ExtraMetaData():Array {
			return this._ExtraMetaData;
		}
		public function set ExtraMetaData(ExtraMetaData:Array):void {
			this._ExtraMetaData = ExtraMetaData;
		}

		public function get Editions():Array {
			return this._Editions;
		}
		public function set Editions(Editions:Array):void {
			this._Editions = Editions;
		}

		public function get Sections():Array {
			return this._Sections;
		}
		public function set Sections(Sections:Array):void {
			this._Sections = Sections;
		}

		public function get Statuses():Array {
			return this._Statuses;
		}
		public function set Statuses(Statuses:Array):void {
			this._Statuses = Statuses;
		}

		public function get UserGroups():Array {
			return this._UserGroups;
		}
		public function set UserGroups(UserGroups:Array):void {
			this._UserGroups = UserGroups;
		}

		public function get Workflows():Array {
			return this._Workflows;
		}
		public function set Workflows(Workflows:Array):void {
			this._Workflows = Workflows;
		}

		public function get Routings():Array {
			return this._Routings;
		}
		public function set Routings(Routings:Array):void {
			this._Routings = Routings;
		}


		// _CalculateDeadlines should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get CalculateDeadlines():String {
			return this._CalculateDeadlines;
		}

		// _CalculateDeadlines should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set CalculateDeadlines(CalculateDeadlines:String):void {
			this._CalculateDeadlines = CalculateDeadlines;
		}

	}
}
