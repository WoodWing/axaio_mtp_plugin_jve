/**
 * GetDatasourceTypesResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class GetDatasourceTypesResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.ads.DatasourceType[] datasourceTypes;

    public GetDatasourceTypesResponse() {
    }

    public GetDatasourceTypesResponse(
           com.woodwing.enterprise.interfaces.services.ads.DatasourceType[] datasourceTypes) {
           this.datasourceTypes = datasourceTypes;
    }


    /**
     * Gets the datasourceTypes value for this GetDatasourceTypesResponse.
     * 
     * @return datasourceTypes
     */
    public com.woodwing.enterprise.interfaces.services.ads.DatasourceType[] getDatasourceTypes() {
        return datasourceTypes;
    }


    /**
     * Sets the datasourceTypes value for this GetDatasourceTypesResponse.
     * 
     * @param datasourceTypes
     */
    public void setDatasourceTypes(com.woodwing.enterprise.interfaces.services.ads.DatasourceType[] datasourceTypes) {
        this.datasourceTypes = datasourceTypes;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetDatasourceTypesResponse)) return false;
        GetDatasourceTypesResponse other = (GetDatasourceTypesResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.datasourceTypes==null && other.getDatasourceTypes()==null) || 
             (this.datasourceTypes!=null &&
              java.util.Arrays.equals(this.datasourceTypes, other.getDatasourceTypes())));
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
        if (getDatasourceTypes() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getDatasourceTypes());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getDatasourceTypes(), i);
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
        new org.apache.axis.description.TypeDesc(GetDatasourceTypesResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypesResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("datasourceTypes");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DatasourceTypes"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", "DatasourceType"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "DatasourceType"));
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
