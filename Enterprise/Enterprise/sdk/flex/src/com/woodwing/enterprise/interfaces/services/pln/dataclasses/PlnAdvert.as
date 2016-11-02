/*
	Enterprise Planning Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pln.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment;
	import com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPage;
	import com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPlacement;
	import com.woodwing.enterprise.interfaces.services.WoodWingUtils;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnAdvert")]

	public class PlnAdvert
	{
		private var _Id:String;
		private var _AlienId:String;
		private var _Publication:String;
		private var _Issue:String;
		private var _PubChannel:String;
		private var _Section:String;
		private var _Status:String;
		private var _Name:String;
		private var _AdType:String;
		private var _Comment:String;
		private var _Source:String;
		private var _ColorSpace:String;
		private var _Description:String;
		private var _PlainContent:String;
		private var _File:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment;
		private var _HighResFile:String;
		private var _PageOrder:Number;
		private var _Page:com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPage;
		private var _Placement:com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPlacement;
		private var _PreferredPlacement:String;
		private var _PublishPrio:String;
		private var _Rate:Number;
		private var _Editions:Array;
		private var _Deadline:String;
		private var _PageSequence:Number;
		private var _Version:String;

		public function PlnAdvert() {
		}

		public function get Id():String {
			return this._Id;
		}
		public function set Id(Id:String):void {
			this._Id = Id;
		}

		public function get AlienId():String {
			return this._AlienId;
		}
		public function set AlienId(AlienId:String):void {
			this._AlienId = AlienId;
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

		public function get PubChannel():String {
			return this._PubChannel;
		}
		public function set PubChannel(PubChannel:String):void {
			this._PubChannel = PubChannel;
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

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get AdType():String {
			return this._AdType;
		}
		public function set AdType(AdType:String):void {
			this._AdType = AdType;
		}

		public function get Comment():String {
			return this._Comment;
		}
		public function set Comment(Comment:String):void {
			this._Comment = Comment;
		}

		public function get Source():String {
			return this._Source;
		}
		public function set Source(Source:String):void {
			this._Source = Source;
		}

		public function get ColorSpace():String {
			return this._ColorSpace;
		}
		public function set ColorSpace(ColorSpace:String):void {
			this._ColorSpace = ColorSpace;
		}

		public function get Description():String {
			return this._Description;
		}
		public function set Description(Description:String):void {
			this._Description = Description;
		}

		public function get PlainContent():String {
			return this._PlainContent;
		}
		public function set PlainContent(PlainContent:String):void {
			this._PlainContent = PlainContent;
		}

		public function get File():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment {
			return this._File;
		}
		public function set File(File:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment):void {
			this._File = File;
		}

		public function get HighResFile():String {
			return this._HighResFile;
		}
		public function set HighResFile(HighResFile:String):void {
			this._HighResFile = HighResFile;
		}

		public function get PageOrder():Number {
			return this._PageOrder;
		}
		public function set PageOrder(PageOrder:Number):void {
			this._PageOrder = PageOrder;
		}

		public function get Page():com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPage {
			return this._Page;
		}
		public function set Page(Page:com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPage):void {
			this._Page = Page;
		}

		public function get Placement():com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPlacement {
			return this._Placement;
		}
		public function set Placement(Placement:com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnPlacement):void {
			this._Placement = Placement;
		}

		public function get PreferredPlacement():String {
			return this._PreferredPlacement;
		}
		public function set PreferredPlacement(PreferredPlacement:String):void {
			this._PreferredPlacement = PreferredPlacement;
		}

		public function get PublishPrio():String {
			return this._PublishPrio;
		}
		public function set PublishPrio(PublishPrio:String):void {
			this._PublishPrio = PublishPrio;
		}

		public function get Rate():Number {
			return this._Rate;
		}
		public function set Rate(Rate:Number):void {
			this._Rate = Rate;
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

		public function get PageSequence():Number {
			return this._PageSequence;
		}
		public function set PageSequence(PageSequence:Number):void {
			this._PageSequence = PageSequence;
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

	}
}
