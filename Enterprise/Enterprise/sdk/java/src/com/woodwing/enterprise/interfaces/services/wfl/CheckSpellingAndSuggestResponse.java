/**
 * CheckSpellingAndSuggestResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class CheckSpellingAndSuggestResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.Suggestion[] suggestions;

    public CheckSpellingAndSuggestResponse() {
    }

    public CheckSpellingAndSuggestResponse(
           com.woodwing.enterprise.interfaces.services.wfl.Suggestion[] suggestions) {
           this.suggestions = suggestions;
    }


    /**
     * Gets the suggestions value for this CheckSpellingAndSuggestResponse.
     * 
     * @return suggestions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Suggestion[] getSuggestions() {
        return suggestions;
    }


    /**
     * Sets the suggestions value for this CheckSpellingAndSuggestResponse.
     * 
     * @param suggestions
     */
    public void setSuggestions(com.woodwing.enterprise.interfaces.services.wfl.Suggestion[] suggestions) {
        this.suggestions = suggestions;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CheckSpellingAndSuggestResponse)) return false;
        CheckSpellingAndSuggestResponse other = (CheckSpellingAndSuggestResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.suggestions==null && other.getSuggestions()==null) || 
             (this.suggestions!=null &&
              java.util.Arrays.equals(this.suggestions, other.getSuggestions())));
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
        if (getSuggestions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSuggestions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSuggestions(), i);
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
        new org.apache.axis.description.TypeDesc(CheckSpellingAndSuggestResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpellingAndSuggestResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("suggestions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Suggestions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Suggestion"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Suggestion"));
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
