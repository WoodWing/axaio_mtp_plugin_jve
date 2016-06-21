/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublicationInfo")]

	public class WflPublicationInfo
	{
		private var _Id:String;
		private var _Name:String;
		private var _Issues:Array;
		private var _States:Array;
		private var _ObjectTypeProperties:Array;
		private var _ActionProperties:Array;
		private var _Editions:Array;
		private var _FeatureAccessList:Array;
		private var _CurrentIssue:String;
		private var _PubChannels:Array;
		private var _Categories:Array;
		private var _Dictionaries:Array;
		private var _ReversedRead:String;

		public function WflPublicationInfo() {
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

		public function get Issues():Array {
			return this._Issues;
		}
		public function set Issues(Issues:Array):void {
			this._Issues = Issues;
		}

		public function get States():Array {
			return this._States;
		}
		public function set States(States:Array):void {
			this._States = States;
		}

		public function get ObjectTypeProperties():Array {
			return this._ObjectTypeProperties;
		}
		public function set ObjectTypeProperties(ObjectTypeProperties:Array):void {
			this._ObjectTypeProperties = ObjectTypeProperties;
		}

		public function get ActionProperties():Array {
			return this._ActionProperties;
		}
		public function set ActionProperties(ActionProperties:Array):void {
			this._ActionProperties = ActionProperties;
		}

		public function get Editions():Array {
			return this._Editions;
		}
		public function set Editions(Editions:Array):void {
			this._Editions = Editions;
		}

		public function get FeatureAccessList():Array {
			return this._FeatureAccessList;
		}
		public function set FeatureAccessList(FeatureAccessList:Array):void {
			this._FeatureAccessList = FeatureAccessList;
		}

		public function get CurrentIssue():String {
			return this._CurrentIssue;
		}
		public function set CurrentIssue(CurrentIssue:String):void {
			this._CurrentIssue = CurrentIssue;
		}

		public function get PubChannels():Array {
			return this._PubChannels;
		}
		public function set PubChannels(PubChannels:Array):void {
			this._PubChannels = PubChannels;
		}

		public function get Categories():Array {
			return this._Categories;
		}
		public function set Categories(Categories:Array):void {
			this._Categories = Categories;
		}

		public function get Dictionaries():Array {
			return this._Dictionaries;
		}
		public function set Dictionaries(Dictionaries:Array):void {
			this._Dictionaries = Dictionaries;
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
