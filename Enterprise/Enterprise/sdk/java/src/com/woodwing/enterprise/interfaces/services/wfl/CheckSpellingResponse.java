/**
 * CheckSpellingResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class CheckSpellingResponse  implements java.io.Serializable {
    private java.lang.String[] misspelledWords;

    public CheckSpellingResponse() {
    }

    public CheckSpellingResponse(
           java.lang.String[] misspelledWords) {
           this.misspelledWords = misspelledWords;
    }


    /**
     * Gets the misspelledWords value for this CheckSpellingResponse.
     * 
     * @return misspelledWords
     */
    public java.lang.String[] getMisspelledWords() {
        return misspelledWords;
    }


    /**
     * Sets the misspelledWords value for this CheckSpellingResponse.
     * 
     * @param misspelledWords
     */
    public void setMisspelledWords(java.lang.String[] misspelledWords) {
        this.misspelledWords = misspelledWords;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CheckSpellingResponse)) return false;
        CheckSpellingResponse other = (CheckSpellingResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.misspelledWords==null && other.getMisspelledWords()==null) || 
             (this.misspelledWords!=null &&
              java.util.Arrays.equals(this.misspelledWords, other.getMisspelledWords())));
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
        if (getMisspelledWords() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMisspelledWords());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMisspelledWords(), i);
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
        new org.apache.axis.description.TypeDesc(CheckSpellingResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpellingResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("misspelledWords");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MisspelledWords"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
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
