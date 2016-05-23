/*
	Enterprise Planning Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pln.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.WoodWingUtils;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnLayout")]

	public class PlnLayout
	{
		private var _Id:String;
		private var _Name:String;
		private var _Publication:String;
		private var _Issue:String;
		private var _Section:String;
		private var _Status:String;
		private var _Pages:Array;
		private var _Editions:Array;
		private var _Deadline:String;
		private var _Version:String;

		public function PlnLayout() {
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

		public function get Publication():String {
			return this._Publication;
		}
		public function set Publication(Publication:String):void {
			this._Publication = Publication;
		}

		public function get Issue():String {
			return this._Issue;
		}
		public function set Issue(Issue:String):void {
			this._Issue = Issue;
		}

		public function get Section():String {
			return this._Section;
		}
		public function set Section(Section:String):void {
			this._Section = Section;
		}

		public function get Status():String {
			return this._Status;
		}
		public function set Status(Status:String):void {
			this._Status = Status;
		}

		public function get Pages():Array {
			return this._Pages;
		}
		public function set Pages(Pages:Array):void {
			this._Pages = Pages;
		}

		public function get Editions():Array {
			return this._Editions;
		}
		public function set Editions(Editions:Array):void {
			this._Editions = Editions;
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

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

	}
}
