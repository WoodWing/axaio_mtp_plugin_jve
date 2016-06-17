/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.WoodWingUtils;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget")]

	public class PubPublishTarget
	{
		private var _PubChannelID:String;
		private var _IssueID:String;
		private var _EditionID:String;
		private var _PublishedDate:String;

		public function PubPublishTarget() {
		}

		public function get PubChannelID():String {
			return this._PubChannelID;
		}
		public function set PubChannelID(PubChannelID:String):void {
			this._PubChannelID = PubChannelID;
		}

		public function get IssueID():String {
			return this._IssueID;
		}
		public function set IssueID(IssueID:String):void {
			this._IssueID = IssueID;
		}

		public function get EditionID():String {
			return this._EditionID;
		}
		public function set EditionID(EditionID:String):void {
			this._EditionID = EditionID;
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

	}
}
