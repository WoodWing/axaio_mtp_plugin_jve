/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmPubChannel")]

	public class AdmPubChannel
	{
		private var _Id:Number;
		private var _Name:String;
		private var _Type:String;
		private var _Description:String;
		private var _SortOrder:Number;
		private var _PublishSystem:String;
		private var _PublishSystemId:String;
		private var _CurrentIssueId:Number;
		private var _SuggestionProvider:String;
		private var _ExtraMetaData:Array;
		private var _DirectPublish:String;
		private var _SupportsForms:String;
		private var _Issues:Array;
		private var _Editions:Array;
		private var _SupportsCropping:String;

		public function AdmPubChannel() {
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

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
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

		public function get PublishSystem():String {
			return this._PublishSystem;
		}
		public function set PublishSystem(PublishSystem:String):void {
			this._PublishSystem = PublishSystem;
		}

		public function get PublishSystemId():String {
			return this._PublishSystemId;
		}
		public function set PublishSystemId(PublishSystemId:String):void {
			this._PublishSystemId = PublishSystemId;
		}

		public function get CurrentIssueId():Number {
			return this._CurrentIssueId;
		}
		public function set CurrentIssueId(CurrentIssueId:Number):void {
			this._CurrentIssueId = CurrentIssueId;
		}

		public function get SuggestionProvider():String {
			return this._SuggestionProvider;
		}
		public function set SuggestionProvider(SuggestionProvider:String):void {
			this._SuggestionProvider = SuggestionProvider;
		}

		public function get ExtraMetaData():Array {
			return this._ExtraMetaData;
		}
		public function set ExtraMetaData(ExtraMetaData:Array):void {
			this._ExtraMetaData = ExtraMetaData;
		}


		// _DirectPublish should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get DirectPublish():String {
			return this._DirectPublish;
		}

		// _DirectPublish should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set DirectPublish(DirectPublish:String):void {
			this._DirectPublish = DirectPublish;
		}


		// _SupportsForms should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get SupportsForms():String {
			return this._SupportsForms;
		}

		// _SupportsForms should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set SupportsForms(SupportsForms:String):void {
			this._SupportsForms = SupportsForms;
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


		// _SupportsCropping should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get SupportsCropping():String {
			return this._SupportsCropping;
		}

		// _SupportsCropping should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set SupportsCropping(SupportsCropping:String):void {
			this._SupportsCropping = SupportsCropping;
		}

	}
}
