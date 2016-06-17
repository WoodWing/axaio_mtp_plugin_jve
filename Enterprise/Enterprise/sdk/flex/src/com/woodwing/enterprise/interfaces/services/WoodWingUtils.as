/*
	Enterprise Services Utils
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services{

	public class WoodWingUtils
	{

		/*
		* Returns a date string formatted as yyyy-mm-ddThh:mm:ss
		* @param d Date
		*
		* @return String a yyyy-mm-ddThh:mm:ss formated date.
		*/
		public static function dateToString (d:Date):String {

			if (d != null) {
				var day:Number = d.date;
				var year:Number = d.fullYear;
				var month:Number = d.month + 1;
				var hours:Number = d.hours;
				var minutes:Number = d.minutes;
				var seconds:Number = d.seconds;
				var sb:String = new String();
				sb += year;
				sb += "-";

				if (month < 10) {
					sb += "0";
				}
				sb += month;
				sb += "-";

				if (day < 10) {
					sb += "0";
				}
				sb += day;
				sb += "T";

				if (hours < 10) {
					sb += "0";
				}
				sb += hours;
				sb += ":";
				if (minutes < 10) {
					sb += "0";
				}
				sb += minutes;
				sb += ":";
				if (seconds < 10) {
					sb += "0";
				}
				sb += seconds;

				return sb;

			}

			return null;
		}

		/**
		* Returns a date formatted by a String (yyyy-mm-ddThh:mm:ss formatted String)
		* (These are local time settings, not utc!)
		* @param str String the string containing the date information (format: yyyy-mm-ddThh:mm:ss)
		*
		* @returns Date a Date object.
		*/
		public static function stringToDate (str:String, ignoreErrors:Boolean = false):Date {
			var finalDate:Date;
			if (str != null && str != "") {
				//if the string doesn't contain time, call the fromDateString function
				if (str.indexOf("T") == -1)
					return fromDateString(str);

				try {
					var dateStr:String = str.substring(0, str.indexOf("T"));
					var timeStr:String = str.substring(str.indexOf("T") + 1, str.length);
					var dateArr:Array = dateStr.split("-");
					var year:Number = Number(dateArr.shift());
					var month:Number = Number(dateArr.shift());
					var date:Number = Number(dateArr.shift());

					//Remove Z
					if (timeStr.indexOf("Z") != -1) {
						timeStr = timeStr.substring(0, timeStr.indexOf("Z"));
					}
					if (timeStr.indexOf("+") != -1) {
						timeStr = timeStr.substring(0, timeStr.indexOf("+"));
					}
					if (timeStr.indexOf("-") != -1) {
						timeStr = timeStr.substring(0, timeStr.indexOf("-"));
					}

					var timeArr:Array = timeStr.split(":");
					var hour:Number = Number(timeArr.shift());
					var minutes:Number = Number(timeArr.shift());
					var secondsArr:Array = (timeArr.length > 0) ? String(timeArr.shift()).split() : null;
					var seconds:Number = (secondsArr != null && secondsArr.length > 0) ? Number(secondsArr.shift()) : 0;
					var milliseconds:Number = (secondsArr != null && secondsArr.length > 0) ? Number(secondsArr.shift()) : 0;

					finalDate = new Date(year, month - 1, date, hour, minutes, seconds, milliseconds);

					if (finalDate.toString() == "Invalid Date") {
						throw new Error("This date does not conform to local date.");
					}
				}
				catch (e:Error) {
					var eStr:String = "Unable to parse the string [" + str + "] into a date. ";
					eStr += "The internal error was: " + e.toString();

					trace(eStr);
				}
			}
			return finalDate;
		}

		/**
		* Returns a date formatted by a String (yyyy-mm-dd formatted String)
		* @param str String the string containing the date information (format: yyyy-mm-dd)
		*
		* @returns Date a Date object.
		*/
		public static function fromDateString (str:String):Date {
			var finalDate:Date;
			if (str != null && str != "") {
				var dateArr:Array = str.split("-");
				var year:Number = Number(dateArr[0]);
				var month:Number = Number(dateArr[1]) - 1; // subtract 1 to get the right month value
				var date:Number = Number(dateArr[2]);
				finalDate = new Date(year, month, date);
			}
			return finalDate;
		}

	}
}
