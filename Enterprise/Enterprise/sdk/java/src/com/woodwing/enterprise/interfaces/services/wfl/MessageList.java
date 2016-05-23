/**
 * MessageList.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class MessageList  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.Message[] messages;

    private java.lang.String[] readMessageIDs;

    private java.lang.String[] deleteMessageIDs;

    public MessageList() {
    }

    public MessageList(
           com.woodwing.enterprise.interfaces.services.wfl.Message[] messages,
           java.lang.String[] readMessageIDs,
           java.lang.String[] deleteMessageIDs) {
           this.messages = messages;
           this.readMessageIDs = readMessageIDs;
           this.deleteMessageIDs = deleteMessageIDs;
    }


    /**
     * Gets the messages value for this MessageList.
     * 
     * @return messages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Message[] getMessages() {
        return messages;
    }


    /**
     * Sets the messages value for this MessageList.
     * 
     * @param messages
     */
    public void setMessages(com.woodwing.enterprise.interfaces.services.wfl.Message[] messages) {
        this.messages = messages;
    }


    /**
     * Gets the readMessageIDs value for this MessageList.
     * 
     * @return readMessageIDs
     */
    public java.lang.String[] getReadMessageIDs() {
        return readMessageIDs;
    }


    /**
     * Sets the readMessageIDs value for this MessageList.
     * 
     * @param readMessageIDs
     */
    public void setReadMessageIDs(java.lang.String[] readMessageIDs) {
        this.readMessageIDs = readMessageIDs;
    }


    /**
     * Gets the deleteMessageIDs value for this MessageList.
     * 
     * @return deleteMessageIDs
     */
    public java.lang.String[] getDeleteMessageIDs() {
        return deleteMessageIDs;
    }


    /**
     * Sets the deleteMessageIDs value for this MessageList.
     * 
     * @param deleteMessageIDs
     */
    public void setDeleteMessageIDs(java.lang.String[] deleteMessageIDs) {
        this.deleteMessageIDs = deleteMessageIDs;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof MessageList)) return false;
        MessageList other = (MessageList) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.messages==null && other.getMessages()==null) || 
             (this.messages!=null &&
              java.util.Arrays.equals(this.messages, other.getMessages()))) &&
            ((this.readMessageIDs==null && other.getReadMessageIDs()==null) || 
             (this.readMessageIDs!=null &&
              java.util.Arrays.equals(this.readMessageIDs, other.getReadMessageIDs()))) &&
            ((this.deleteMessageIDs==null && other.getDeleteMessageIDs()==null) || 
             (this.deleteMessageIDs!=null &&
              java.util.Arrays.equals(this.deleteMessageIDs, other.getDeleteMessageIDs())));
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
        if (getReadMessageIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getReadMessageIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getReadMessageIDs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getDeleteMessageIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getDeleteMessageIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getDeleteMessageIDs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(MessageList.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageList"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Messages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Message"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Message"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("readMessageIDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReadMessageIDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deleteMessageIDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DeleteMessageIDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
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
