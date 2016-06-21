/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmUser")]

	public class AdmUser
	{
		private var _Id:Number;
		private var _Name:String;
		private var _FullName:String;
		private var _Deactivated:String;
		private var _Password:String;
		private var _FixedPassword:String;
		private var _EmailAddress:String;
		private var _EmailUser:String;
		private var _EmailGroup:String;
		private var _PasswordExpired:Number;
		private var _ValidFrom:String;
		private var _ValidTill:String;
		private var _Language:String;
		private var _TrackChangesColor:String;
		private var _Organization:String;
		private var _Location:String;
		private var _EncryptedPassword:String;
		private var _UserGroups:Array;
		private var _ImportOnLogon:String;

		public function AdmUser() {
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

		public function get FullName():String {
			return this._FullName;
		}
		public function set FullName(FullName:String):void {
			this._FullName = FullName;
		}


		// _Deactivated should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Deactivated():String {
			return this._Deactivated;
		}

		// _Deactivated should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Deactivated(Deactivated:String):void {
			this._Deactivated = Deactivated;
		}

		public function get Password():String {
			return this._Password;
		}
		public function set Password(Password:String):void {
			this._Password = Password;
		}


		// _FixedPassword should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get FixedPassword():String {
			return this._FixedPassword;
		}

		// _FixedPassword should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set FixedPassword(FixedPassword:String):void {
			this._FixedPassword = FixedPassword;
		}

		public function get EmailAddress():String {
			return this._EmailAddress;
		}
		public function set EmailAddress(EmailAddress:String):void {
			this._EmailAddress = EmailAddress;
		}


		// _EmailUser should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get EmailUser():String {
			return this._EmailUser;
		}

		// _EmailUser should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set EmailUser(EmailUser:String):void {
			this._EmailUser = EmailUser;
		}


		// _EmailGroup should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get EmailGroup():String {
			return this._EmailGroup;
		}

		// _EmailGroup should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set EmailGroup(EmailGroup:String):void {
			this._EmailGroup = EmailGroup;
		}

		public function get PasswordExpired():Number {
			return this._PasswordExpired;
		}
		public function set PasswordExpired(PasswordExpired:Number):void {
			this._PasswordExpired = PasswordExpired;
		}

		public function get ValidFrom():String {
			return this._ValidFrom;
		}

		public function getValidFromAsDate():Date {
			return WoodWingUtils.stringToDate(this._ValidFrom);
		}

		public function set ValidFrom(ValidFrom:String):void {
			this._ValidFrom = ValidFrom;
		}


		public function setValidFromAsDate(ValidFrom:Date):void {
			this._ValidFrom = WoodWingUtils.dateToString(ValidFrom);
		}

		public function get ValidTill():String {
			return this._ValidTill;
		}

		public function getValidTillAsDate():Date {
			return WoodWingUtils.stringToDate(this._ValidTill);
		}

		public function set ValidTill(ValidTill:String):void {
			this._ValidTill = ValidTill;
		}


		public function setValidTillAsDate(ValidTill:Date):void {
			this._ValidTill = WoodWingUtils.dateToString(ValidTill);
		}

		public function get Language():String {
			return this._Language;
		}
		public function set Language(Language:String):void {
			this._Language = Language;
		}

		public function get TrackChangesColor():String {
			return this._TrackChangesColor;
		}
		public function set TrackChangesColor(TrackChangesColor:String):void {
			this._TrackChangesColor = TrackChangesColor;
		}

		public function get Organization():String {
			return this._Organization;
		}
		public function set Organization(Organization:String):void {
			this._Organization = Organization;
		}

		public function get Location():String {
			return this._Location;
		}
		public function set Location(Location:String):void {
			this._Location = Location;
		}

		public function get EncryptedPassword():String {
			return this._EncryptedPassword;
		}
		public function set EncryptedPassword(EncryptedPassword:String):void {
			this._EncryptedPassword = EncryptedPassword;
		}

		public function get UserGroups():Array {
			return this._UserGroups;
		}
		public function set UserGroups(UserGroups:Array):void {
			this._UserGroups = UserGroups;
		}


		// _ImportOnLogon should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get ImportOnLogon():String {
			return this._ImportOnLogon;
		}

		// _ImportOnLogon should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set ImportOnLogon(ImportOnLogon:String):void {
			this._ImportOnLogon = ImportOnLogon;
		}

	}
}
