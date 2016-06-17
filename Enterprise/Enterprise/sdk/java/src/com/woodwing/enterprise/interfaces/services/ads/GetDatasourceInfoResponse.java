/**
 * GetDatasourceInfoResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class GetDatasourceInfoResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.ads.DatasourceInfo datasourceInfo;

    public GetDatasourceInfoResponse() {
    }

    public GetDatasourceInfoResponse(
           com.woodwing.enterprise.interfaces.services.ads.DatasourceInfo datasourceInfo) {
           this.datasourceInfo = datasourceInfo;
    }


    /**
     * Gets the datasourceInfo value for this GetDatasourceInfoResponse.
     * 
     * @return datasourceInfo
     */
    public com.woodwing.enterprise.interfaces.services.ads.DatasourceInfo getDatasourceInfo() {
        return datasourceInfo;
    }


    /**
     * Sets the datasourceInfo value for this GetDatasourceInfoResponse.
     * 
     * @param datasourceInfo
     */
    public void setDatasourceInfo(com.woodwing.enterprise.interfaces.services.ads.DatasourceInfo datasourceInfo) {
        this.datasourceInfo = datasourceInfo;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetDatasourceInfoResponse)) return false;
        GetDatasourceInfoResponse other = (GetDatasourceInfoResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.datasourceInfo==null && other.getDatasourceInfo()==null) || 
             (this.datasourceInfo!=null &&
              this.datasourceInfo.equals(other.getDatasourceInfo())));
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
        if (getDatasourceInfo() != null) {
            _hashCode += getDatasourceInfo().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetDatasourceInfoResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceInfoResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("datasourceInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DatasourceInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", "DatasourceInfo"));
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
