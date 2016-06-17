/**
 * GetSettingsDetailsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class GetSettingsDetailsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.ads.SettingsDetail[] settingsDetails;

    public GetSettingsDetailsResponse() {
    }

    public GetSettingsDetailsResponse(
           com.woodwing.enterprise.interfaces.services.ads.SettingsDetail[] settingsDetails) {
           this.settingsDetails = settingsDetails;
    }


    /**
     * Gets the settingsDetails value for this GetSettingsDetailsResponse.
     * 
     * @return settingsDetails
     */
    public com.woodwing.enterprise.interfaces.services.ads.SettingsDetail[] getSettingsDetails() {
        return settingsDetails;
    }


    /**
     * Sets the settingsDetails value for this GetSettingsDetailsResponse.
     * 
     * @param settingsDetails
     */
    public void setSettingsDetails(com.woodwing.enterprise.interfaces.services.ads.SettingsDetail[] settingsDetails) {
        this.settingsDetails = settingsDetails;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetSettingsDetailsResponse)) return false;
        GetSettingsDetailsResponse other = (GetSettingsDetailsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.settingsDetails==null && other.getSettingsDetails()==null) || 
             (this.settingsDetails!=null &&
              java.util.Arrays.equals(this.settingsDetails, other.getSettingsDetails())));
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
        if (getSettingsDetails() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSettingsDetails());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSettingsDetails(), i);
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
        new org.apache.axis.description.TypeDesc(GetSettingsDetailsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetSettingsDetailsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("settingsDetails");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SettingsDetails"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", "SettingsDetail"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "SettingsDetail"));
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
