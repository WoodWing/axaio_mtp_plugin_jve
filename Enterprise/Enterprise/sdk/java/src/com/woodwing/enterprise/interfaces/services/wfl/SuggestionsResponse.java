/**
 * SuggestionsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class SuggestionsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.EntityTags[] suggestedTags;

    public SuggestionsResponse() {
    }

    public SuggestionsResponse(
           com.woodwing.enterprise.interfaces.services.wfl.EntityTags[] suggestedTags) {
           this.suggestedTags = suggestedTags;
    }


    /**
     * Gets the suggestedTags value for this SuggestionsResponse.
     * 
     * @return suggestedTags
     */
    public com.woodwing.enterprise.interfaces.services.wfl.EntityTags[] getSuggestedTags() {
        return suggestedTags;
    }


    /**
     * Sets the suggestedTags value for this SuggestionsResponse.
     * 
     * @param suggestedTags
     */
    public void setSuggestedTags(com.woodwing.enterprise.interfaces.services.wfl.EntityTags[] suggestedTags) {
        this.suggestedTags = suggestedTags;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof SuggestionsResponse)) return false;
        SuggestionsResponse other = (SuggestionsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.suggestedTags==null && other.getSuggestedTags()==null) || 
             (this.suggestedTags!=null &&
              java.util.Arrays.equals(this.suggestedTags, other.getSuggestedTags())));
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
        if (getSuggestedTags() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSuggestedTags());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSuggestedTags(), i);
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
        new org.apache.axis.description.TypeDesc(SuggestionsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">SuggestionsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("suggestedTags");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SuggestedTags"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "EntityTags"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "EntityTags"));
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
