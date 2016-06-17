/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget;
	import com.woodwing.enterprise.interfaces.services.WoodWingUtils;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishedIssue")]

	public class PubPublishedIssue
	{
		private var _Target:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget;
		private var _Version:String;
		private var _Fields:Array;
		private var _Report:Array;
		private var _PublishedDate:String;
		private var _DossierOrder:Array;

		public function PubPublishedIssue() {
		}

		public function get Target():com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget {
			return this._Target;
		}
		public function set Target(Target:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget):void {
			this._Target = Target;
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

		public function get Fields():Array {
			return this._Fields;
		}
		public function set Fields(Fields:Array):void {
			this._Fields = Fields;
		}

		public function get Report():Array {
			return this._Report;
		}
		public function set Report(Report:Array):void {
			this._Report = Report;
		}

		public function get PublishedDate():String {
			return this._PublishedDate;
		}

		public function getPublishedDateAsDate():Date {
			return WoodWingUtils.stringToDate(this._PublishedDate);
		}

		public function set PublishedDate(PublishedDate:String):void {
			this._PublishedDate = PublishedDate;
		}


		public function setPublishedDateAsDate(PublishedDate:Date):void {
			this._PublishedDate = WoodWingUtils.dateToString(PublishedDate);
		}

		public function get DossierOrder():Array {
			return this._DossierOrder;
		}
		public function set DossierOrder(DossierOrder:Array):void {
			this._DossierOrder = DossierOrder;
		}

	}
}
