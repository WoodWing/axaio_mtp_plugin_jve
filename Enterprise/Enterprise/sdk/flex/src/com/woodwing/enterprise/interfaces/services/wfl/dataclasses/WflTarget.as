/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPubChannel;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflTarget")]

	public class WflTarget
	{
		private var _PubChannel:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPubChannel;
		private var _Issue:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue;
		private var _Editions:Array;
		private var _PublishedDate:String;
		private var _PublishedVersion:String;

		public function WflTarget() {
		}

		public function get PubChannel():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPubChannel {
			return this._PubChannel;
		}
		public function set PubChannel(PubChannel:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPubChannel):void {
			this._PubChannel = PubChannel;
		}

		public function get Issue():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue {
			return this._Issue;
		}
		public function set Issue(Issue:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue):void {
			this._Issue = Issue;
		}

		public function get Editions():Array {
			return this._Editions;
		}
		public function set Editions(Editions:Array):void {
			this._Editions = Editions;
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

		public function get PublishedVersion():String {
			return this._PublishedVersion;
		}
		public function set PublishedVersion(PublishedVersion:String):void {
			this._PublishedVersion = PublishedVersion;
		}

	}
}
