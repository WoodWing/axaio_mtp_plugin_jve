/**
 * GetQueriesResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class GetQueriesResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.ads.Query[] queries;

    public GetQueriesResponse() {
    }

    public GetQueriesResponse(
           com.woodwing.enterprise.interfaces.services.ads.Query[] queries) {
           this.queries = queries;
    }


    /**
     * Gets the queries value for this GetQueriesResponse.
     * 
     * @return queries
     */
    public com.woodwing.enterprise.interfaces.services.ads.Query[] getQueries() {
        return queries;
    }


    /**
     * Sets the queries value for this GetQueriesResponse.
     * 
     * @param queries
     */
    public void setQueries(com.woodwing.enterprise.interfaces.services.ads.Query[] queries) {
        this.queries = queries;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetQueriesResponse)) return false;
        GetQueriesResponse other = (GetQueriesResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.queries==null && other.getQueries()==null) || 
             (this.queries!=null &&
              java.util.Arrays.equals(this.queries, other.getQueries())));
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
        if (getQueries() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getQueries());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getQueries(), i);
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
        new org.apache.axis.description.TypeDesc(GetQueriesResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueriesResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("queries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Queries"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", "Query"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Query"));
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
