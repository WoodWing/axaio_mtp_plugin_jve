/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialog;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublicationInfo;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;
	import com.woodwing.enterprise.interfaces.services.wfl.WflGetStatesResponse;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetDialogResponse")]

	public class WflGetDialogResponse
	{
		private var _Dialog:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialog;
		private var _Publications:Array;
		private var _PublicationInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublicationInfo;
		private var _MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;
		private var _GetStatesResponse:com.woodwing.enterprise.interfaces.services.wfl.WflGetStatesResponse;
		private var _Targets:Array;
		private var _RelatedTargets:Array;
		private var _Dossiers:Array;

		public function WflGetDialogResponse() {
		}

		public function get Dialog():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialog {
			return this._Dialog;
		}
		public function set Dialog(Dialog:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialog):void {
			this._Dialog = Dialog;
		}

		public function get Publications():Array {
			return this._Publications;
		}
		public function set Publications(Publications:Array):void {
			this._Publications = Publications;
		}

		public function get PublicationInfo():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublicationInfo {
			return this._PublicationInfo;
		}
		public function set PublicationInfo(PublicationInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublicationInfo):void {
			this._PublicationInfo = PublicationInfo;
		}

		public function get MetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData {
			return this._MetaData;
		}
		public function set MetaData(MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData):void {
			this._MetaData = MetaData;
		}

		public function get GetStatesResponse():com.woodwing.enterprise.interfaces.services.wfl.WflGetStatesResponse {
			return this._GetStatesResponse;
		}
		public function set GetStatesResponse(GetStatesResponse:com.woodwing.enterprise.interfaces.services.wfl.WflGetStatesResponse):void {
			this._GetStatesResponse = GetStatesResponse;
		}

		public function get Targets():Array {
			return this._Targets;
		}
		public function set Targets(Targets:Array):void {
			this._Targets = Targets;
		}

		public function get RelatedTargets():Array {
			return this._RelatedTargets;
		}
		public function set RelatedTargets(RelatedTargets:Array):void {
			this._RelatedTargets = RelatedTargets;
		}

		public function get Dossiers():Array {
			return this._Dossiers;
		}
		public function set Dossiers(Dossiers:Array):void {
			this._Dossiers = Dossiers;
		}

	}
}
