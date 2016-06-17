/**
 * GetDatasourceTypeResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class GetDatasourceTypeResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.ads.DatasourceType datasourceType;

    public GetDatasourceTypeResponse() {
    }

    public GetDatasourceTypeResponse(
           com.woodwing.enterprise.interfaces.services.ads.DatasourceType datasourceType) {
           this.datasourceType = datasourceType;
    }


    /**
     * Gets the datasourceType value for this GetDatasourceTypeResponse.
     * 
     * @return datasourceType
     */
    public com.woodwing.enterprise.interfaces.services.ads.DatasourceType getDatasourceType() {
        return datasourceType;
    }


    /**
     * Sets the datasourceType value for this GetDatasourceTypeResponse.
     * 
     * @param datasourceType
     */
    public void setDatasourceType(com.woodwing.enterprise.interfaces.services.ads.DatasourceType datasourceType) {
        this.datasourceType = datasourceType;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetDatasourceTypeResponse)) return false;
        GetDatasourceTypeResponse other = (GetDatasourceTypeResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.datasourceType==null && other.getDatasourceType()==null) || 
             (this.datasourceType!=null &&
              this.datasourceType.equals(other.getDatasourceType())));
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
        if (getDatasourceType() != null) {
            _hashCode += getDatasourceType().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetDatasourceTypeResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypeResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("datasourceType");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DatasourceType"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", "DatasourceType"));
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
