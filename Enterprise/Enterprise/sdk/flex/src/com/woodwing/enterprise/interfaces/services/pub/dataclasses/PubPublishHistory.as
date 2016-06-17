/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.WoodWingUtils;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishHistory")]

	public class PubPublishHistory
	{
		private var _PublishedDate:String;
		private var _SendDate:String;
		private var _PublishedBy:String;
		private var _PublishedObjects:Array;

		public function PubPublishHistory() {
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

		public function get SendDate():String {
			return this._SendDate;
		}

		public function getSendDateAsDate():Date {
			return WoodWingUtils.stringToDate(this._SendDate);
		}

		public function set SendDate(SendDate:String):void {
			this._SendDate = SendDate;
		}


		public function setSendDateAsDate(SendDate:Date):void {
			this._SendDate = WoodWingUtils.dateToString(SendDate);
		}

		public function get PublishedBy():String {
			return this._PublishedBy;
		}
		public function set PublishedBy(PublishedBy:String):void {
			this._PublishedBy = PublishedBy;
		}

		public function get PublishedObjects():Array {
			return this._PublishedObjects;
		}
		public function set PublishedObjects(PublishedObjects:Array):void {
			this._PublishedObjects = PublishedObjects;
		}

	}
}
