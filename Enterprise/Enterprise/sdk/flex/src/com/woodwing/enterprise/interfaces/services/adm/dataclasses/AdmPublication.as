/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmPublication")]

	public class AdmPublication
	{
		private var _Id:Number;
		private var _Name:String;
		private var _Description:String;
		private var _SortOrder:Number;
		private var _EmailNotify:String;
		private var _ReversedRead:String;
		private var _AutoPurge:Number;
		private var _DefaultChannelId:Number;
		private var _ExtraMetaData:Array;
		private var _PubChannels:Array;
		private var _Issues:Array;
		private var _Editions:Array;
		private var _Sections:Array;
		private var _Statuses:Array;
		private var _UserGroups:Array;
		private var _AdminGroups:Array;
		private var _Workflows:Array;
		private var _Routings:Array;
		private var _CalculateDeadlines:String;

		public function AdmPublication() {
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

		public function get AutoPurge():Number {
			return this._AutoPurge;
		}
		public function set AutoPurge(AutoPurge:Number):void {
			this._AutoPurge = AutoPurge;
		}

		public function get DefaultChannelId():Number {
			return this._DefaultChannelId;
		}
		public function set DefaultChannelId(DefaultChannelId:Number):void {
			this._DefaultChannelId = DefaultChannelId;
		}

		public function get ExtraMetaData():Array {
			return this._ExtraMetaData;
		}
		public function set ExtraMetaData(ExtraMetaData:Array):void {
			this._ExtraMetaData = ExtraMetaData;
		}

		public function get PubChannels():Array {
			return this._PubChannels;
		}
		public function set PubChannels(PubChannels:Array):void {
			this._PubChannels = PubChannels;
		}

		public function get Issues():Array {
			return this._Issues;
		}
		public function set Issues(Issues:Array):void {
			this._Issues = Issues;
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

		public function get AdminGroups():Array {
			return this._AdminGroups;
		}
		public function set AdminGroups(AdminGroups:Array):void {
			this._AdminGroups = AdminGroups;
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
