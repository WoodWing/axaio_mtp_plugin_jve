/**
 * CreateObjectOperations.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class CreateObjectOperations  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion haveVersion;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation[] operations;

    public CreateObjectOperations() {
    }

    public CreateObjectOperations(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion haveVersion,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation[] operations) {
           this.ticket = ticket;
           this.haveVersion = haveVersion;
           this.operations = operations;
    }


    /**
     * Gets the ticket value for this CreateObjectOperations.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this CreateObjectOperations.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the haveVersion value for this CreateObjectOperations.
     * 
     * @return haveVersion
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion getHaveVersion() {
        return haveVersion;
    }


    /**
     * Sets the haveVersion value for this CreateObjectOperations.
     * 
     * @param haveVersion
     */
    public void setHaveVersion(com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion haveVersion) {
        this.haveVersion = haveVersion;
    }


    /**
     * Gets the operations value for this CreateObjectOperations.
     * 
     * @return operations
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation[] getOperations() {
        return operations;
    }


    /**
     * Sets the operations value for this CreateObjectOperations.
     * 
     * @param operations
     */
    public void setOperations(com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation[] operations) {
        this.operations = operations;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CreateObjectOperations)) return false;
        CreateObjectOperations other = (CreateObjectOperations) obj;
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
            ((this.haveVersion==null && other.getHaveVersion()==null) || 
             (this.haveVersion!=null &&
              this.haveVersion.equals(other.getHaveVersion()))) &&
            ((this.operations==null && other.getOperations()==null) || 
             (this.operations!=null &&
              java.util.Arrays.equals(this.operations, other.getOperations())));
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
        if (getHaveVersion() != null) {
            _hashCode += getHaveVersion().hashCode();
        }
        if (getOperations() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getOperations());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getOperations(), i);
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
        new org.apache.axis.description.TypeDesc(CreateObjectOperations.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectOperations"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("haveVersion");
        elemField.setXmlName(new javax.xml.namespace.QName("", "HaveVersion"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectVersion"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("operations");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Operations"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectOperation"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectOperation"));
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
