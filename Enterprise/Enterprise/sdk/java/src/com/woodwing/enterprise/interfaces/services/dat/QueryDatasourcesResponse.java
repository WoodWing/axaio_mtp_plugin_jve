/**
 * QueryDatasourcesResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class QueryDatasourcesResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.dat.DatasourceInfo[] datasources;

    public QueryDatasourcesResponse() {
    }

    public QueryDatasourcesResponse(
           com.woodwing.enterprise.interfaces.services.dat.DatasourceInfo[] datasources) {
           this.datasources = datasources;
    }


    /**
     * Gets the datasources value for this QueryDatasourcesResponse.
     * 
     * @return datasources
     */
    public com.woodwing.enterprise.interfaces.services.dat.DatasourceInfo[] getDatasources() {
        return datasources;
    }


    /**
     * Sets the datasources value for this QueryDatasourcesResponse.
     * 
     * @param datasources
     */
    public void setDatasources(com.woodwing.enterprise.interfaces.services.dat.DatasourceInfo[] datasources) {
        this.datasources = datasources;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof QueryDatasourcesResponse)) return false;
        QueryDatasourcesResponse other = (QueryDatasourcesResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.datasources==null && other.getDatasources()==null) || 
             (this.datasources!=null &&
              java.util.Arrays.equals(this.datasources, other.getDatasources())));
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
        if (getDatasources() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getDatasources());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getDatasources(), i);
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
        new org.apache.axis.description.TypeDesc(QueryDatasourcesResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", ">QueryDatasourcesResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("datasources");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Datasources"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "DatasourceInfo"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "DatasourceInfo"));
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