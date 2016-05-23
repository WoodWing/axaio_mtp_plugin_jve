/**
 * CopyDatasourceResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class CopyDatasourceResponse  implements java.io.Serializable {
    private java.lang.String newDatasourceID;

    public CopyDatasourceResponse() {
    }

    public CopyDatasourceResponse(
           java.lang.String newDatasourceID) {
           this.newDatasourceID = newDatasourceID;
    }


    /**
     * Gets the newDatasourceID value for this CopyDatasourceResponse.
     * 
     * @return newDatasourceID
     */
    public java.lang.String getNewDatasourceID() {
        return newDatasourceID;
    }


    /**
     * Sets the newDatasourceID value for this CopyDatasourceResponse.
     * 
     * @param newDatasourceID
     */
    public void setNewDatasourceID(java.lang.String newDatasourceID) {
        this.newDatasourceID = newDatasourceID;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CopyDatasourceResponse)) return false;
        CopyDatasourceResponse other = (CopyDatasourceResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.newDatasourceID==null && other.getNewDatasourceID()==null) || 
             (this.newDatasourceID!=null &&
              this.newDatasourceID.equals(other.getNewDatasourceID())));
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
        if (getNewDatasourceID() != null) {
            _hashCode += getNewDatasourceID().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(CopyDatasourceResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyDatasourceResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("newDatasourceID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "NewDatasourceID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
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
