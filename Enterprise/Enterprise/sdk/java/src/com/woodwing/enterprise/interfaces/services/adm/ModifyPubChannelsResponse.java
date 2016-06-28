/**
 * ModifyPubChannelsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class ModifyPubChannelsResponse  implements java.io.Serializable {
    private java.math.BigInteger publicationId;

    private com.woodwing.enterprise.interfaces.services.adm.PubChannel[] pubChannels;

    public ModifyPubChannelsResponse() {
    }

    public ModifyPubChannelsResponse(
           java.math.BigInteger publicationId,
           com.woodwing.enterprise.interfaces.services.adm.PubChannel[] pubChannels) {
           this.publicationId = publicationId;
           this.pubChannels = pubChannels;
    }


    /**
     * Gets the publicationId value for this ModifyPubChannelsResponse.
     * 
     * @return publicationId
     */
    public java.math.BigInteger getPublicationId() {
        return publicationId;
    }


    /**
     * Sets the publicationId value for this ModifyPubChannelsResponse.
     * 
     * @param publicationId
     */
    public void setPublicationId(java.math.BigInteger publicationId) {
        this.publicationId = publicationId;
    }


    /**
     * Gets the pubChannels value for this ModifyPubChannelsResponse.
     * 
     * @return pubChannels
     */
    public com.woodwing.enterprise.interfaces.services.adm.PubChannel[] getPubChannels() {
        return pubChannels;
    }


    /**
     * Sets the pubChannels value for this ModifyPubChannelsResponse.
     * 
     * @param pubChannels
     */
    public void setPubChannels(com.woodwing.enterprise.interfaces.services.adm.PubChannel[] pubChannels) {
        this.pubChannels = pubChannels;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ModifyPubChannelsResponse)) return false;
        ModifyPubChannelsResponse other = (ModifyPubChannelsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.publicationId==null && other.getPublicationId()==null) || 
             (this.publicationId!=null &&
              this.publicationId.equals(other.getPublicationId()))) &&
            ((this.pubChannels==null && other.getPubChannels()==null) || 
             (this.pubChannels!=null &&
              java.util.Arrays.equals(this.pubChannels, other.getPubChannels())));
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
        if (getPublicationId() != null) {
            _hashCode += getPublicationId().hashCode();
        }
        if (getPubChannels() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPubChannels());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPubChannels(), i);
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
        new org.apache.axis.description.TypeDesc(ModifyPubChannelsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyPubChannelsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publicationId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublicationId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pubChannels");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PubChannels"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "PubChannel"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PubChannel"));
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
