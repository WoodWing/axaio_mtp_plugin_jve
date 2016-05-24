/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmSection")]

	public class AdmSection
	{
		private var _Id:Number;
		private var _Name:String;
		private var _Description:String;
		private var _SortOrder:Number;
		private var _Deadline:String;
		private var _ExpectedPages:Number;
		private var _Statuses:Array;

		public function AdmSection() {
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

		public function get Statuses():Array {
			return this._Statuses;
		}
		public function set Statuses(Statuses:Array):void {
			this._Statuses = Statuses;
		}

	}
}
