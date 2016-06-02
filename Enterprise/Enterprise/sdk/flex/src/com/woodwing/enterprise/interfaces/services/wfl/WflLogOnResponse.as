/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflServerInfo;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflUser;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflLogOnResponse")]

	public class WflLogOnResponse
	{
		private var _Ticket:String;
		private var _Publications:Array;
		private var _NamedQueries:Array;
		private var _FeatureSet:Array;
		private var _LimitationSet:Array;
		private var _ServerInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflServerInfo;
		private var _Settings:Array;
		private var _Users:Array;
		private var _UserGroups:Array;
		private var _Membership:Array;
		private var _ObjectTypeProperties:Array;
		private var _ActionProperties:Array;
		private var _Terms:Array;
		private var _FeatureProfiles:Array;
		private var _Messages:Array;
		private var _TrackChangesColor:String;
		private var _Dictionaries:Array;
		private var _MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;
		private var _CurrentUser:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflUser;
		private var _MessageQueueConnections:Array;
		private var _MessageQueue:String;

		public function WflLogOnResponse() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Publications():Array {
			return this._Publications;
		}
		public function set Publications(Publications:Array):void {
			this._Publications = Publications;
		}

		public function get NamedQueries():Array {
			return this._NamedQueries;
		}
		public function set NamedQueries(NamedQueries:Array):void {
			this._NamedQueries = NamedQueries;
		}

		public function get FeatureSet():Array {
			return this._FeatureSet;
		}
		public function set FeatureSet(FeatureSet:Array):void {
			this._FeatureSet = FeatureSet;
		}

		public function get LimitationSet():Array {
			return this._LimitationSet;
		}
		public function set LimitationSet(LimitationSet:Array):void {
			this._LimitationSet = LimitationSet;
		}

		public function get ServerInfo():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflServerInfo {
			return this._ServerInfo;
		}
		public function set ServerInfo(ServerInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflServerInfo):void {
			this._ServerInfo = ServerInfo;
		}

		public function get Settings():Array {
			return this._Settings;
		}
		public function set Settings(Settings:Array):void {
			this._Settings = Settings;
		}

		public function get Users():Array {
			return this._Users;
		}
		public function set Users(Users:Array):void {
			this._Users = Users;
		}

		public function get UserGroups():Array {
			return this._UserGroups;
		}
		public function set UserGroups(UserGroups:Array):void {
			this._UserGroups = UserGroups;
		}

		public function get Membership():Array {
			return this._Membership;
		}
		public function set Membership(Membership:Array):void {
			this._Membership = Membership;
		}

		public function get ObjectTypeProperties():Array {
			return this._ObjectTypeProperties;
		}
		public function set ObjectTypeProperties(ObjectTypeProperties:Array):void {
			this._ObjectTypeProperties = ObjectTypeProperties;
		}

		public function get ActionProperties():Array {
			return this._ActionProperties;
		}
		public function set ActionProperties(ActionProperties:Array):void {
			this._ActionProperties = ActionProperties;
		}

		public function get Terms():Array {
			return this._Terms;
		}
		public function set Terms(Terms:Array):void {
			this._Terms = Terms;
		}

		public function get FeatureProfiles():Array {
			return this._FeatureProfiles;
		}
		public function set FeatureProfiles(FeatureProfiles:Array):void {
			this._FeatureProfiles = FeatureProfiles;
		}

		public function get Messages():Array {
			return this._Messages;
		}
		public function set Messages(Messages:Array):void {
			this._Messages = Messages;
		}

		public function get TrackChangesColor():String {
			return this._TrackChangesColor;
		}
		public function set TrackChangesColor(TrackChangesColor:String):void {
			this._TrackChangesColor = TrackChangesColor;
		}

		public function get Dictionaries():Array {
			return this._Dictionaries;
		}
		public function set Dictionaries(Dictionaries:Array):void {
			this._Dictionaries = Dictionaries;
		}

		public function get MessageList():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList {
			return this._MessageList;
		}
		public function set MessageList(MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList):void {
			this._MessageList = MessageList;
		}

		public function get CurrentUser():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflUser {
			return this._CurrentUser;
		}
		public function set CurrentUser(CurrentUser:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflUser):void {
			this._CurrentUser = CurrentUser;
		}

		public function get MessageQueueConnections():Array {
			return this._MessageQueueConnections;
		}
		public function set MessageQueueConnections(MessageQueueConnections:Array):void {
			this._MessageQueueConnections = MessageQueueConnections;
		}

		public function get MessageQueue():String {
			return this._MessageQueue;
		}
		public function set MessageQueue(MessageQueue:String):void {
			this._MessageQueue = MessageQueue;
		}

	}
}
