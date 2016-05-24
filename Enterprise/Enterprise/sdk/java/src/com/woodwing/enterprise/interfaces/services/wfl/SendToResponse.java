/**
 * SendToResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class SendToResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.WorkflowMetaData sendTo;

    public SendToResponse() {
    }

    public SendToResponse(
           com.woodwing.enterprise.interfaces.services.wfl.WorkflowMetaData sendTo) {
           this.sendTo = sendTo;
    }


    /**
     * Gets the sendTo value for this SendToResponse.
     * 
     * @return sendTo
     */
    public com.woodwing.enterprise.interfaces.services.wfl.WorkflowMetaData getSendTo() {
        return sendTo;
    }


    /**
     * Sets the sendTo value for this SendToResponse.
     * 
     * @param sendTo
     */
    public void setSendTo(com.woodwing.enterprise.interfaces.services.wfl.WorkflowMetaData sendTo) {
        this.sendTo = sendTo;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof SendToResponse)) return false;
        SendToResponse other = (SendToResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.sendTo==null && other.getSendTo()==null) || 
             (this.sendTo!=null &&
              this.sendTo.equals(other.getSendTo())));
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
        if (getSendTo() != null) {
            _hashCode += getSendTo().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(SendToResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">SendToResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sendTo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SendTo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "WorkflowMetaData"));
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
