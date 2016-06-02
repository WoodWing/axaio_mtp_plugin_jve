/**
 * SendMessagesResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class SendMessagesResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList;

    private com.woodwing.enterprise.interfaces.services.wfl.ErrorReport[] reports;

    public SendMessagesResponse() {
    }

    public SendMessagesResponse(
           com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList,
           com.woodwing.enterprise.interfaces.services.wfl.ErrorReport[] reports) {
           this.messageList = messageList;
           this.reports = reports;
    }


    /**
     * Gets the messageList value for this SendMessagesResponse.
     * 
     * @return messageList
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MessageList getMessageList() {
        return messageList;
    }


    /**
     * Sets the messageList value for this SendMessagesResponse.
     * 
     * @param messageList
     */
    public void setMessageList(com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList) {
        this.messageList = messageList;
    }


    /**
     * Gets the reports value for this SendMessagesResponse.
     * 
     * @return reports
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ErrorReport[] getReports() {
        return reports;
    }


    /**
     * Sets the reports value for this SendMessagesResponse.
     * 
     * @param reports
     */
    public void setReports(com.woodwing.enterprise.interfaces.services.wfl.ErrorReport[] reports) {
        this.reports = reports;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof SendMessagesResponse)) return false;
        SendMessagesResponse other = (SendMessagesResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.messageList==null && other.getMessageList()==null) || 
             (this.messageList!=null &&
              this.messageList.equals(other.getMessageList()))) &&
            ((this.reports==null && other.getReports()==null) || 
             (this.reports!=null &&
              java.util.Arrays.equals(this.reports, other.getReports())));
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
        if (getMessageList() != null) {
            _hashCode += getMessageList().hashCode();
        }
        if (getReports() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getReports());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getReports(), i);
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
        new org.apache.axis.description.TypeDesc(SendMessagesResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">SendMessagesResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageList");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageList"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageList"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("reports");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Reports"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReport"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ErrorReport"));
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
