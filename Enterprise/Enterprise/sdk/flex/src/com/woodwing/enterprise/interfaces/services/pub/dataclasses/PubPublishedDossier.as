/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget;
	import com.woodwing.enterprise.interfaces.services.WoodWingUtils;
	import com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubUserMessage;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishedDossier")]

	public class PubPublishedDossier
	{
		private var _DossierID:String;
		private var _Target:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget;
		private var _PublishedDate:String;
		private var _PublishMessage:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubUserMessage;
		private var _Online:String;
		private var _URL:String;
		private var _Fields:Array;
		private var _History:Array;

		public function PubPublishedDossier() {
		}

		public function get DossierID():String {
			return this._DossierID;
		}
		public function set DossierID(DossierID:String):void {
			this._DossierID = DossierID;
		}

		public function get Target():com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget {
			return this._Target;
		}
		public function set Target(Target:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishTarget):void {
			this._Target = Target;
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

		public function get PublishMessage():com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubUserMessage {
			return this._PublishMessage;
		}
		public function set PublishMessage(PublishMessage:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubUserMessage):void {
			this._PublishMessage = PublishMessage;
		}


		// _Online should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Online():String {
			return this._Online;
		}

		// _Online should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Online(Online:String):void {
			this._Online = Online;
		}

		public function get URL():String {
			return this._URL;
		}
		public function set URL(URL:String):void {
			this._URL = URL;
		}

		public function get Fields():Array {
			return this._Fields;
		}
		public function set Fields(Fields:Array):void {
			this._Fields = Fields;
		}

		public function get History():Array {
			return this._History;
		}
		public function set History(History:Array):void {
			this._History = History;
		}

	}
}
