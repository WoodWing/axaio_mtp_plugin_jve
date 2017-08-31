/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetWorkflowUserGroupAuthorizationsRequest")]

	public class AdmGetWorkflowUserGroupAuthorizationsRequest
	{
		private var _Ticket:String;
		private var _RequestModes:Array;
		private var _PublicationId:Number;
		private var _IssueId:Number;
		private var _UserGroupId:Number;
		private var _WorkflowUserGroupAuthorizationIds:Array;

		public function AdmGetWorkflowUserGroupAuthorizationsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get RequestModes():Array {
			return this._RequestModes;
		}
		public function set RequestModes(RequestModes:Array):void {
			this._RequestModes = RequestModes;
		}

		public function get PublicationId():Number {
			return this._PublicationId;
		}
		public function set PublicationId(PublicationId:Number):void {
			this._PublicationId = PublicationId;
		}

		public function get IssueId():Number {
			return this._IssueId;
		}
		public function set IssueId(IssueId:Number):void {
			this._IssueId = IssueId;
		}

		public function get UserGroupId():Number {
			return this._UserGroupId;
		}
		public function set UserGroupId(UserGroupId:Number):void {
			this._UserGroupId = UserGroupId;
		}

		public function get WorkflowUserGroupAuthorizationIds():Array {
			return this._WorkflowUserGroupAuthorizationIds;
		}
		public function set WorkflowUserGroupAuthorizationIds(WorkflowUserGroupAuthorizationIds:Array):void {
			this._WorkflowUserGroupAuthorizationIds = WorkflowUserGroupAuthorizationIds;
		}

	}
}
