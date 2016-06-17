/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPubChannelInfo")]

	public class WflPubChannelInfo
	{
		private var _Id:String;
		private var _Name:String;
		private var _Issues:Array;
		private var _Editions:Array;
		private var _CurrentIssue:String;
		private var _Type:String;
		private var _DirectPublish:String;
		private var _SupportsForms:String;

		public function WflPubChannelInfo() {
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

		public function get Editions():Array {
			return this._Editions;
		}
		public function set Editions(Editions:Array):void {
			this._Editions = Editions;
		}

		public function get CurrentIssue():String {
			return this._CurrentIssue;
		}
		public function set CurrentIssue(CurrentIssue:String):void {
			this._CurrentIssue = CurrentIssue;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
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

	}
}
