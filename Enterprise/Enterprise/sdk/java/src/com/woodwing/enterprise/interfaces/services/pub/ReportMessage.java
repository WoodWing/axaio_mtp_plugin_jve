/**
 * ReportMessage.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class ReportMessage  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.pub.UserMessage userMessage;

    private com.woodwing.enterprise.interfaces.services.pub.MessageContext context;

    public ReportMessage() {
    }

    public ReportMessage(
           com.woodwing.enterprise.interfaces.services.pub.UserMessage userMessage,
           com.woodwing.enterprise.interfaces.services.pub.MessageContext context) {
           this.userMessage = userMessage;
           this.context = context;
    }


    /**
     * Gets the userMessage value for this ReportMessage.
     * 
     * @return userMessage
     */
    public com.woodwing.enterprise.interfaces.services.pub.UserMessage getUserMessage() {
        return userMessage;
    }


    /**
     * Sets the userMessage value for this ReportMessage.
     * 
     * @param userMessage
     */
    public void setUserMessage(com.woodwing.enterprise.interfaces.services.pub.UserMessage userMessage) {
        this.userMessage = userMessage;
    }


    /**
     * Gets the context value for this ReportMessage.
     * 
     * @return context
     */
    public com.woodwing.enterprise.interfaces.services.pub.MessageContext getContext() {
        return context;
    }


    /**
     * Sets the context value for this ReportMessage.
     * 
     * @param context
     */
    public void setContext(com.woodwing.enterprise.interfaces.services.pub.MessageContext context) {
        this.context = context;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ReportMessage)) return false;
        ReportMessage other = (ReportMessage) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.userMessage==null && other.getUserMessage()==null) || 
             (this.userMessage!=null &&
              this.userMessage.equals(other.getUserMessage()))) &&
            ((this.context==null && other.getContext()==null) || 
             (this.context!=null &&
              this.context.equals(other.getContext())));
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
        if (getUserMessage() != null) {
            _hashCode += getUserMessage().hashCode();
        }
        if (getContext() != null) {
            _hashCode += getContext().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(ReportMessage.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "ReportMessage"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userMessage");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserMessage"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "UserMessage"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("context");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Context"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "MessageContext"));
        elemField.setNillable(false);
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
