/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubUserMessage;
	import com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubMessageContext;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubReportMessage")]

	public class PubReportMessage
	{
		private var _UserMessage:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubUserMessage;
		private var _Context:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubMessageContext;

		public function PubReportMessage() {
		}

		public function get UserMessage():com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubUserMessage {
			return this._UserMessage;
		}
		public function set UserMessage(UserMessage:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubUserMessage):void {
			this._UserMessage = UserMessage;
		}

		public function get Context():com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubMessageContext {
			return this._Context;
		}
		public function set Context(Context:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubMessageContext):void {
			this._Context = Context;
		}

	}
}
