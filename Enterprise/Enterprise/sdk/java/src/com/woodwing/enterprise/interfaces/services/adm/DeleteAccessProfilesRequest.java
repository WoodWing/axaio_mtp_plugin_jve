/**
 * DeleteAccessProfilesRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class DeleteAccessProfilesRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.math.BigInteger[] accessProfileIds;

    public DeleteAccessProfilesRequest() {
    }

    public DeleteAccessProfilesRequest(
           java.lang.String ticket,
           java.math.BigInteger[] accessProfileIds) {
           this.ticket = ticket;
           this.accessProfileIds = accessProfileIds;
    }


    /**
     * Gets the ticket value for this DeleteAccessProfilesRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this DeleteAccessProfilesRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the accessProfileIds value for this DeleteAccessProfilesRequest.
     * 
     * @return accessProfileIds
     */
    public java.math.BigInteger[] getAccessProfileIds() {
        return accessProfileIds;
    }


    /**
     * Sets the accessProfileIds value for this DeleteAccessProfilesRequest.
     * 
     * @param accessProfileIds
     */
    public void setAccessProfileIds(java.math.BigInteger[] accessProfileIds) {
        this.accessProfileIds = accessProfileIds;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof DeleteAccessProfilesRequest)) return false;
        DeleteAccessProfilesRequest other = (DeleteAccessProfilesRequest) obj;
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
            ((this.accessProfileIds==null && other.getAccessProfileIds()==null) || 
             (this.accessProfileIds!=null &&
              java.util.Arrays.equals(this.accessProfileIds, other.getAccessProfileIds())));
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
        if (getAccessProfileIds() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAccessProfileIds());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAccessProfileIds(), i);
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
        new org.apache.axis.description.TypeDesc(DeleteAccessProfilesRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteAccessProfilesRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("accessProfileIds");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AccessProfileIds"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Id"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Id"));
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
