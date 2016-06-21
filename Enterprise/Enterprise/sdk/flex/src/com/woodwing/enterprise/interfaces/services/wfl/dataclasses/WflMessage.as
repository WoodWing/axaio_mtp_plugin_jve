/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.WoodWingUtils;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflStickyInfo;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessage")]

	public class WflMessage
	{
		private var _ObjectID:String;
		private var _UserID:String;
		private var _MessageID:String;
		private var _MessageType:String;
		private var _MessageTypeDetail:String;
		private var _Message:String;
		private var _TimeStamp:String;
		private var _Expiration:String;
		private var _MessageLevel:String;
		private var _FromUser:String;
		private var _StickyInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflStickyInfo;
		private var _ThreadMessageID:String;
		private var _ReplyToMessageID:String;
		private var _MessageStatus:String;
		private var _ObjectVersion:String;

		public function WflMessage() {
		}

		public function get ObjectID():String {
			return this._ObjectID;
		}
		public function set ObjectID(ObjectID:String):void {
			this._ObjectID = ObjectID;
		}

		public function get UserID():String {
			return this._UserID;
		}
		public function set UserID(UserID:String):void {
			this._UserID = UserID;
		}

		public function get MessageID():String {
			return this._MessageID;
		}
		public function set MessageID(MessageID:String):void {
			this._MessageID = MessageID;
		}

		public function get MessageType():String {
			return this._MessageType;
		}
		public function set MessageType(MessageType:String):void {
			this._MessageType = MessageType;
		}

		public function get MessageTypeDetail():String {
			return this._MessageTypeDetail;
		}
		public function set MessageTypeDetail(MessageTypeDetail:String):void {
			this._MessageTypeDetail = MessageTypeDetail;
		}

		public function get Message():String {
			return this._Message;
		}
		public function set Message(Message:String):void {
			this._Message = Message;
		}

		public function get TimeStamp():String {
			return this._TimeStamp;
		}

		public function getTimeStampAsDate():Date {
			return WoodWingUtils.stringToDate(this._TimeStamp);
		}

		public function set TimeStamp(TimeStamp:String):void {
			this._TimeStamp = TimeStamp;
		}


		public function setTimeStampAsDate(TimeStamp:Date):void {
			this._TimeStamp = WoodWingUtils.dateToString(TimeStamp);
		}

		public function get Expiration():String {
			return this._Expiration;
		}

		public function getExpirationAsDate():Date {
			return WoodWingUtils.stringToDate(this._Expiration);
		}

		public function set Expiration(Expiration:String):void {
			this._Expiration = Expiration;
		}


		public function setExpirationAsDate(Expiration:Date):void {
			this._Expiration = WoodWingUtils.dateToString(Expiration);
		}

		public function get MessageLevel():String {
			return this._MessageLevel;
		}
		public function set MessageLevel(MessageLevel:String):void {
			this._MessageLevel = MessageLevel;
		}

		public function get FromUser():String {
			return this._FromUser;
		}
		public function set FromUser(FromUser:String):void {
			this._FromUser = FromUser;
		}

		public function get StickyInfo():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflStickyInfo {
			return this._StickyInfo;
		}
		public function set StickyInfo(StickyInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflStickyInfo):void {
			this._StickyInfo = StickyInfo;
		}

		public function get ThreadMessageID():String {
			return this._ThreadMessageID;
		}
		public function set ThreadMessageID(ThreadMessageID:String):void {
			this._ThreadMessageID = ThreadMessageID;
		}

		public function get ReplyToMessageID():String {
			return this._ReplyToMessageID;
		}
		public function set ReplyToMessageID(ReplyToMessageID:String):void {
			this._ReplyToMessageID = ReplyToMessageID;
		}

		public function get MessageStatus():String {
			return this._MessageStatus;
		}
		public function set MessageStatus(MessageStatus:String):void {
			this._MessageStatus = MessageStatus;
		}

		public function get ObjectVersion():String {
			return this._ObjectVersion;
		}
		public function set ObjectVersion(ObjectVersion:String):void {
			this._ObjectVersion = ObjectVersion;
		}

	}
}
