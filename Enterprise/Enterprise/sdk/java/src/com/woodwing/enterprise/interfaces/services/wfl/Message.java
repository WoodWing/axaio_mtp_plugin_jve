/**
 * Message.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Message  implements java.io.Serializable {
    private java.lang.String objectID;

    private java.lang.String userID;

    private java.lang.String messageID;

    private com.woodwing.enterprise.interfaces.services.wfl.MessageType messageType;

    private java.lang.String messageTypeDetail;

    private java.lang.String message;

    private java.util.Calendar timeStamp;

    private java.util.Calendar expiration;

    private com.woodwing.enterprise.interfaces.services.wfl.MessageLevel messageLevel;

    private java.lang.String fromUser;

    private com.woodwing.enterprise.interfaces.services.wfl.StickyInfo stickyInfo;

    private java.lang.String threadMessageID;

    private java.lang.String replyToMessageID;

    private com.woodwing.enterprise.interfaces.services.wfl.MessageStatus messageStatus;

    private java.lang.String objectVersion;

    public Message() {
    }

    public Message(
           java.lang.String objectID,
           java.lang.String userID,
           java.lang.String messageID,
           com.woodwing.enterprise.interfaces.services.wfl.MessageType messageType,
           java.lang.String messageTypeDetail,
           java.lang.String message,
           java.util.Calendar timeStamp,
           java.util.Calendar expiration,
           com.woodwing.enterprise.interfaces.services.wfl.MessageLevel messageLevel,
           java.lang.String fromUser,
           com.woodwing.enterprise.interfaces.services.wfl.StickyInfo stickyInfo,
           java.lang.String threadMessageID,
           java.lang.String replyToMessageID,
           com.woodwing.enterprise.interfaces.services.wfl.MessageStatus messageStatus,
           java.lang.String objectVersion) {
           this.objectID = objectID;
           this.userID = userID;
           this.messageID = messageID;
           this.messageType = messageType;
           this.messageTypeDetail = messageTypeDetail;
           this.message = message;
           this.timeStamp = timeStamp;
           this.expiration = expiration;
           this.messageLevel = messageLevel;
           this.fromUser = fromUser;
           this.stickyInfo = stickyInfo;
           this.threadMessageID = threadMessageID;
           this.replyToMessageID = replyToMessageID;
           this.messageStatus = messageStatus;
           this.objectVersion = objectVersion;
    }


    /**
     * Gets the objectID value for this Message.
     * 
     * @return objectID
     */
    public java.lang.String getObjectID() {
        return objectID;
    }


    /**
     * Sets the objectID value for this Message.
     * 
     * @param objectID
     */
    public void setObjectID(java.lang.String objectID) {
        this.objectID = objectID;
    }


    /**
     * Gets the userID value for this Message.
     * 
     * @return userID
     */
    public java.lang.String getUserID() {
        return userID;
    }


    /**
     * Sets the userID value for this Message.
     * 
     * @param userID
     */
    public void setUserID(java.lang.String userID) {
        this.userID = userID;
    }


    /**
     * Gets the messageID value for this Message.
     * 
     * @return messageID
     */
    public java.lang.String getMessageID() {
        return messageID;
    }


    /**
     * Sets the messageID value for this Message.
     * 
     * @param messageID
     */
    public void setMessageID(java.lang.String messageID) {
        this.messageID = messageID;
    }


    /**
     * Gets the messageType value for this Message.
     * 
     * @return messageType
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MessageType getMessageType() {
        return messageType;
    }


    /**
     * Sets the messageType value for this Message.
     * 
     * @param messageType
     */
    public void setMessageType(com.woodwing.enterprise.interfaces.services.wfl.MessageType messageType) {
        this.messageType = messageType;
    }


    /**
     * Gets the messageTypeDetail value for this Message.
     * 
     * @return messageTypeDetail
     */
    public java.lang.String getMessageTypeDetail() {
        return messageTypeDetail;
    }


    /**
     * Sets the messageTypeDetail value for this Message.
     * 
     * @param messageTypeDetail
     */
    public void setMessageTypeDetail(java.lang.String messageTypeDetail) {
        this.messageTypeDetail = messageTypeDetail;
    }


    /**
     * Gets the message value for this Message.
     * 
     * @return message
     */
    public java.lang.String getMessage() {
        return message;
    }


    /**
     * Sets the message value for this Message.
     * 
     * @param message
     */
    public void setMessage(java.lang.String message) {
        this.message = message;
    }


    /**
     * Gets the timeStamp value for this Message.
     * 
     * @return timeStamp
     */
    public java.util.Calendar getTimeStamp() {
        return timeStamp;
    }


    /**
     * Sets the timeStamp value for this Message.
     * 
     * @param timeStamp
     */
    public void setTimeStamp(java.util.Calendar timeStamp) {
        this.timeStamp = timeStamp;
    }


    /**
     * Gets the expiration value for this Message.
     * 
     * @return expiration
     */
    public java.util.Calendar getExpiration() {
        return expiration;
    }


    /**
     * Sets the expiration value for this Message.
     * 
     * @param expiration
     */
    public void setExpiration(java.util.Calendar expiration) {
        this.expiration = expiration;
    }


    /**
     * Gets the messageLevel value for this Message.
     * 
     * @return messageLevel
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MessageLevel getMessageLevel() {
        return messageLevel;
    }


    /**
     * Sets the messageLevel value for this Message.
     * 
     * @param messageLevel
     */
    public void setMessageLevel(com.woodwing.enterprise.interfaces.services.wfl.MessageLevel messageLevel) {
        this.messageLevel = messageLevel;
    }


    /**
     * Gets the fromUser value for this Message.
     * 
     * @return fromUser
     */
    public java.lang.String getFromUser() {
        return fromUser;
    }


    /**
     * Sets the fromUser value for this Message.
     * 
     * @param fromUser
     */
    public void setFromUser(java.lang.String fromUser) {
        this.fromUser = fromUser;
    }


    /**
     * Gets the stickyInfo value for this Message.
     * 
     * @return stickyInfo
     */
    public com.woodwing.enterprise.interfaces.services.wfl.StickyInfo getStickyInfo() {
        return stickyInfo;
    }


    /**
     * Sets the stickyInfo value for this Message.
     * 
     * @param stickyInfo
     */
    public void setStickyInfo(com.woodwing.enterprise.interfaces.services.wfl.StickyInfo stickyInfo) {
        this.stickyInfo = stickyInfo;
    }


    /**
     * Gets the threadMessageID value for this Message.
     * 
     * @return threadMessageID
     */
    public java.lang.String getThreadMessageID() {
        return threadMessageID;
    }


    /**
     * Sets the threadMessageID value for this Message.
     * 
     * @param threadMessageID
     */
    public void setThreadMessageID(java.lang.String threadMessageID) {
        this.threadMessageID = threadMessageID;
    }


    /**
     * Gets the replyToMessageID value for this Message.
     * 
     * @return replyToMessageID
     */
    public java.lang.String getReplyToMessageID() {
        return replyToMessageID;
    }


    /**
     * Sets the replyToMessageID value for this Message.
     * 
     * @param replyToMessageID
     */
    public void setReplyToMessageID(java.lang.String replyToMessageID) {
        this.replyToMessageID = replyToMessageID;
    }


    /**
     * Gets the messageStatus value for this Message.
     * 
     * @return messageStatus
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MessageStatus getMessageStatus() {
        return messageStatus;
    }


    /**
     * Sets the messageStatus value for this Message.
     * 
     * @param messageStatus
     */
    public void setMessageStatus(com.woodwing.enterprise.interfaces.services.wfl.MessageStatus messageStatus) {
        this.messageStatus = messageStatus;
    }


    /**
     * Gets the objectVersion value for this Message.
     * 
     * @return objectVersion
     */
    public java.lang.String getObjectVersion() {
        return objectVersion;
    }


    /**
     * Sets the objectVersion value for this Message.
     * 
     * @param objectVersion
     */
    public void setObjectVersion(java.lang.String objectVersion) {
        this.objectVersion = objectVersion;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Message)) return false;
        Message other = (Message) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.objectID==null && other.getObjectID()==null) || 
             (this.objectID!=null &&
              this.objectID.equals(other.getObjectID()))) &&
            ((this.userID==null && other.getUserID()==null) || 
             (this.userID!=null &&
              this.userID.equals(other.getUserID()))) &&
            ((this.messageID==null && other.getMessageID()==null) || 
             (this.messageID!=null &&
              this.messageID.equals(other.getMessageID()))) &&
            ((this.messageType==null && other.getMessageType()==null) || 
             (this.messageType!=null &&
              this.messageType.equals(other.getMessageType()))) &&
            ((this.messageTypeDetail==null && other.getMessageTypeDetail()==null) || 
             (this.messageTypeDetail!=null &&
              this.messageTypeDetail.equals(other.getMessageTypeDetail()))) &&
            ((this.message==null && other.getMessage()==null) || 
             (this.message!=null &&
              this.message.equals(other.getMessage()))) &&
            ((this.timeStamp==null && other.getTimeStamp()==null) || 
             (this.timeStamp!=null &&
              this.timeStamp.equals(other.getTimeStamp()))) &&
            ((this.expiration==null && other.getExpiration()==null) || 
             (this.expiration!=null &&
              this.expiration.equals(other.getExpiration()))) &&
            ((this.messageLevel==null && other.getMessageLevel()==null) || 
             (this.messageLevel!=null &&
              this.messageLevel.equals(other.getMessageLevel()))) &&
            ((this.fromUser==null && other.getFromUser()==null) || 
             (this.fromUser!=null &&
              this.fromUser.equals(other.getFromUser()))) &&
            ((this.stickyInfo==null && other.getStickyInfo()==null) || 
             (this.stickyInfo!=null &&
              this.stickyInfo.equals(other.getStickyInfo()))) &&
            ((this.threadMessageID==null && other.getThreadMessageID()==null) || 
             (this.threadMessageID!=null &&
              this.threadMessageID.equals(other.getThreadMessageID()))) &&
            ((this.replyToMessageID==null && other.getReplyToMessageID()==null) || 
             (this.replyToMessageID!=null &&
              this.replyToMessageID.equals(other.getReplyToMessageID()))) &&
            ((this.messageStatus==null && other.getMessageStatus()==null) || 
             (this.messageStatus!=null &&
              this.messageStatus.equals(other.getMessageStatus()))) &&
            ((this.objectVersion==null && other.getObjectVersion()==null) || 
             (this.objectVersion!=null &&
              this.objectVersion.equals(other.getObjectVersion())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getObjectID() != null) {
            _hashCode += getObjectID().hashCode();
        }
        if (getUserID() != null) {
            _hashCode += getUserID().hashCode();
        }
        if (getMessageID() != null) {
            _hashCode += getMessageID().hashCode();
        }
        if (getMessageType() != null) {
            _hashCode += getMessageType().hashCode();
        }
        if (getMessageTypeDetail() != null) {
            _hashCode += getMessageTypeDetail().hashCode();
        }
        if (getMessage() != null) {
            _hashCode += getMessage().hashCode();
        }
        if (getTimeStamp() != null) {
            _hashCode += getTimeStamp().hashCode();
        }
        if (getExpiration() != null) {
            _hashCode += getExpiration().hashCode();
        }
        if (getMessageLevel() != null) {
            _hashCode += getMessageLevel().hashCode();
        }
        if (getFromUser() != null) {
            _hashCode += getFromUser().hashCode();
        }
        if (getStickyInfo() != null) {
            _hashCode += getStickyInfo().hashCode();
        }
        if (getThreadMessageID() != null) {
            _hashCode += getThreadMessageID().hashCode();
        }
        if (getReplyToMessageID() != null) {
            _hashCode += getReplyToMessageID().hashCode();
        }
        if (getMessageStatus() != null) {
            _hashCode += getMessageStatus().hashCode();
        }
        if (getObjectVersion() != null) {
            _hashCode += getObjectVersion().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Message.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Message"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageType");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageType"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageTypeDetail");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageTypeDetail"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("message");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Message"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("timeStamp");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TimeStamp"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("expiration");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Expiration"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageLevel");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageLevel"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageLevel"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fromUser");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FromUser"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("stickyInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "StickyInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "StickyInfo"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("threadMessageID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ThreadMessageID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("replyToMessageID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReplyToMessageID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageStatus");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageStatus"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageStatus"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectVersion");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectVersion"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
