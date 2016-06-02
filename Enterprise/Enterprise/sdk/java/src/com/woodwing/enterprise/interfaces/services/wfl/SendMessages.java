/**
 * SendMessages.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class SendMessages  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.wfl.Message[] messages;

    private com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList;

    public SendMessages() {
    }

    public SendMessages(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.wfl.Message[] messages,
           com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList) {
           this.ticket = ticket;
           this.messages = messages;
           this.messageList = messageList;
    }


    /**
     * Gets the ticket value for this SendMessages.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this SendMessages.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the messages value for this SendMessages.
     * 
     * @return messages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Message[] getMessages() {
        return messages;
    }


    /**
     * Sets the messages value for this SendMessages.
     * 
     * @param messages
     */
    public void setMessages(com.woodwing.enterprise.interfaces.services.wfl.Message[] messages) {
        this.messages = messages;
    }


    /**
     * Gets the messageList value for this SendMessages.
     * 
     * @return messageList
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MessageList getMessageList() {
        return messageList;
    }


    /**
     * Sets the messageList value for this SendMessages.
     * 
     * @param messageList
     */
    public void setMessageList(com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList) {
        this.messageList = messageList;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof SendMessages)) return false;
        SendMessages other = (SendMessages) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.messages==null && other.getMessages()==null) || 
             (this.messages!=null &&
              java.util.Arrays.equals(this.messages, other.getMessages()))) &&
            ((this.messageList==null && other.getMessageList()==null) || 
             (this.messageList!=null &&
              this.messageList.equals(other.getMessageList())));
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
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getMessages() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMessages());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMessages(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMessageList() != null) {
            _hashCode += getMessageList().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(SendMessages.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">SendMessages"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Messages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Message"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Message"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageList");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageList"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageList"));
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
