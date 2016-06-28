/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.WoodWingUtils;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflWorkflowMetaData")]

	public class WflWorkflowMetaData
	{
		private var _Deadline:String;
		private var _Urgency:String;
		private var _Modifier:String;
		private var _Modified:String;
		private var _Creator:String;
		private var _Created:String;
		private var _Comment:String;
		private var _State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;
		private var _RouteTo:String;
		private var _LockedBy:String;
		private var _Version:String;
		private var _DeadlineSoft:String;
		private var _Rating:Number;
		private var _Deletor:String;
		private var _Deleted:String;

		public function WflWorkflowMetaData() {
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

		public function get Urgency():String {
			return this._Urgency;
		}
		public function set Urgency(Urgency:String):void {
			this._Urgency = Urgency;
		}

		public function get Modifier():String {
			return this._Modifier;
		}
		public function set Modifier(Modifier:String):void {
			this._Modifier = Modifier;
		}

		public function get Modified():String {
			return this._Modified;
		}

		public function getModifiedAsDate():Date {
			return WoodWingUtils.stringToDate(this._Modified);
		}

		public function set Modified(Modified:String):void {
			this._Modified = Modified;
		}


		public function setModifiedAsDate(Modified:Date):void {
			this._Modified = WoodWingUtils.dateToString(Modified);
		}

		public function get Creator():String {
			return this._Creator;
		}
		public function set Creator(Creator:String):void {
			this._Creator = Creator;
		}

		public function get Created():String {
			return this._Created;
		}

		public function getCreatedAsDate():Date {
			return WoodWingUtils.stringToDate(this._Created);
		}

		public function set Created(Created:String):void {
			this._Created = Created;
		}


		public function setCreatedAsDate(Created:Date):void {
			this._Created = WoodWingUtils.dateToString(Created);
		}

		public function get Comment():String {
			return this._Comment;
		}
		public function set Comment(Comment:String):void {
			this._Comment = Comment;
		}

		public function get State():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState {
			return this._State;
		}
		public function set State(State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState):void {
			this._State = State;
		}

		public function get RouteTo():String {
			return this._RouteTo;
		}
		public function set RouteTo(RouteTo:String):void {
			this._RouteTo = RouteTo;
		}

		public function get LockedBy():String {
			return this._LockedBy;
		}
		public function set LockedBy(LockedBy:String):void {
			this._LockedBy = LockedBy;
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

		public function get DeadlineSoft():String {
			return this._DeadlineSoft;
		}

		public function getDeadlineSoftAsDate():Date {
			return WoodWingUtils.stringToDate(this._DeadlineSoft);
		}

		public function set DeadlineSoft(DeadlineSoft:String):void {
			this._DeadlineSoft = DeadlineSoft;
		}


		public function setDeadlineSoftAsDate(DeadlineSoft:Date):void {
			this._DeadlineSoft = WoodWingUtils.dateToString(DeadlineSoft);
		}

		public function get Rating():Number {
			return this._Rating;
		}
		public function set Rating(Rating:Number):void {
			this._Rating = Rating;
		}

		public function get Deletor():String {
			return this._Deletor;
		}
		public function set Deletor(Deletor:String):void {
			this._Deletor = Deletor;
		}

		public function get Deleted():String {
			return this._Deleted;
		}

		public function getDeletedAsDate():Date {
			return WoodWingUtils.stringToDate(this._Deleted);
		}

		public function set Deleted(Deleted:String):void {
			this._Deleted = Deleted;
		}


		public function setDeletedAsDate(Deleted:Date):void {
			this._Deleted = WoodWingUtils.dateToString(Deleted);
		}

	}
}
